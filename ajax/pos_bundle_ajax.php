<?php
/**
 * ajax/pos_bundle_ajax.php
 * Bundle SKU AJAX — resolve, list, save, delete
 * NCC CRM POS v4.4
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['oop']) || empty($_SESSION['ooq'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']); exit();
}

$agent_name = $_SESSION['oop'];
$agent_id   = (int)$_SESSION['ooq'];
$is_super   = ($agent_name === 'super');

$conn = mysqli_connect("192.168.1.19","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

require_once __DIR__ . '/pos_log.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Helpers ────────────────────────────────────────────────────────────────
function getBundleItems($conn, int $bundle_id): array {
    $res = mysqli_query($conn,"
        SELECT bi.product_id, bi.qty,
               p.nomp AS product_name, p.price, p.unit, p.onhand, p.is_weighted, p.category
        FROM pos_bundle_sku_items bi
        JOIN produit p ON p.codep = bi.product_id
        WHERE bi.bundle_id = $bundle_id
        ORDER BY bi.id
    ");
    $items = [];
    while ($r = mysqli_fetch_assoc($res)) $items[] = $r;
    return $items;
}

switch ($action) {

    // ── Resolve bundle barcode ─────────────────────────────────────────────
    // Called when scanner reads a barcode — checks if it's a bundle wrapper
    case 'resolve':
        $barcode = trim($_GET['barcode'] ?? '');
        if (!$barcode) { echo json_encode(['success'=>false,'error'=>'No barcode']); break; }

        $esc = mysqli_real_escape_string($conn, $barcode);
        $bundle = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM pos_bundle_skus WHERE barcode='$esc' AND active=1 LIMIT 1"));

        if (!$bundle) {
            echo json_encode(['success'=>false,'is_bundle'=>false,'error'=>'Not a bundle barcode']);
            break;
        }

        $items = getBundleItems($conn, (int)$bundle['id']);
        if (empty($items)) {
            echo json_encode(['success'=>false,'is_bundle'=>true,'error'=>'Bundle has no components configured']);
            break;
        }

        // Calculate regular total so we can split bundle price proportionally
        $regular_total = array_sum(array_map(fn($i) => (float)$i['price'] * (float)$i['qty'], $items));

        // Distribute bundle price proportionally across components
        $bundle_price = (float)$bundle['bundle_price'];
        $ratio        = $regular_total > 0 ? $bundle_price / $regular_total : 1;

        foreach ($items as &$item) {
            $item['unit_price_lbp']      = round((float)$item['price'] * $ratio / 5000) * 5000;
            $item['unit_price_regular']  = (float)$item['price'];
            $item['line_total_lbp']      = $item['unit_price_lbp'] * (float)$item['qty'];
        }
        unset($item);

        echo json_encode([
            'success'        => true,
            'is_bundle'      => true,
            'bundle_id'      => (int)$bundle['id'],
            'bundle_name'    => $bundle['name'],
            'bundle_price'   => $bundle_price,
            'regular_total'  => $regular_total,
            'savings'        => max(0, $regular_total - $bundle_price),
            'description'    => $bundle['description'],
            'items'          => $items,
        ]);
        break;

    // ── List all bundles ───────────────────────────────────────────────────
    case 'list':
        $res = mysqli_query($conn,"SELECT * FROM pos_bundle_skus ORDER BY active DESC, name ASC");
        $bundles = [];
        while ($b = mysqli_fetch_assoc($res)) {
            $b['items'] = getBundleItems($conn, (int)$b['id']);
            $bundles[] = $b;
        }
        echo json_encode(['success'=>true,'bundles'=>$bundles]);
        break;

    // ── Save bundle (insert or update) ────────────────────────────────────
    case 'save':
        if (!$is_super) { echo json_encode(['success'=>false,'error'=>'Super only']); break; }

        $id          = (int)($_POST['id'] ?? 0);
        $name        = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
        $barcode     = mysqli_real_escape_string($conn, trim($_POST['barcode'] ?? ''));
        $price       = (float)($_POST['bundle_price'] ?? 0);
        $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
        $active      = isset($_POST['active']) ? (int)$_POST['active'] : 1;
        $items       = json_decode($_POST['items'] ?? '[]', true);

        if (!$name)    { echo json_encode(['success'=>false,'error'=>'Bundle name required']); break; }
        if (!$barcode) { echo json_encode(['success'=>false,'error'=>'Barcode required']); break; }
        if ($price <= 0) { echo json_encode(['success'=>false,'error'=>'Bundle price must be > 0']); break; }
        if (empty($items)) { echo json_encode(['success'=>false,'error'=>'At least one component required']); break; }

        if ($id) {
            // Update
            $ok = mysqli_query($conn,"UPDATE pos_bundle_skus
                SET name='$name', barcode='$barcode', bundle_price=$price,
                    description='$description', active=$active
                WHERE id=$id");
            if (!$ok) {
                echo json_encode(['success'=>false,'error'=>'Barcode already used by another bundle']);
                break;
            }
            mysqli_query($conn,"DELETE FROM pos_bundle_sku_items WHERE bundle_id=$id");
            $action_log = 'bundle_updated';
        } else {
            // Insert
            $ok = mysqli_query($conn,"INSERT INTO pos_bundle_skus (name,barcode,bundle_price,description,active)
                VALUES ('$name','$barcode',$price,'$description',$active)");
            if (!$ok) {
                echo json_encode(['success'=>false,'error'=>'Barcode already exists']);
                break;
            }
            $id = mysqli_insert_id($conn);
            $action_log = 'bundle_created';
        }

        // Insert components
        foreach ($items as $item) {
            $pid = (int)($item['product_id'] ?? 0);
            $qty = (float)($item['qty'] ?? 1);
            if ($pid > 0 && $qty > 0) {
                mysqli_query($conn,"INSERT INTO pos_bundle_sku_items (bundle_id,product_id,qty)
                    VALUES ($id,$pid,$qty)");
            }
        }

        posLog($conn,$agent_id,$agent_name,$action_log,
            "Bundle: $name — barcode: $barcode — LL ".number_format($price)." — ".count($items)." components",
            $id,'bundle');

        echo json_encode(['success'=>true,'bundle_id'=>$id]);
        break;

    // ── Toggle active ──────────────────────────────────────────────────────
    case 'toggle':
        if (!$is_super) { echo json_encode(['success'=>false,'error'=>'Super only']); break; }
        $id  = (int)($_POST['id'] ?? 0);
        $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT name,active FROM pos_bundle_skus WHERE id=$id LIMIT 1"));
        if (!$row) { echo json_encode(['success'=>false,'error'=>'Not found']); break; }
        $new = $row['active'] ? 0 : 1;
        mysqli_query($conn,"UPDATE pos_bundle_skus SET active=$new WHERE id=$id");
        posLog($conn,$agent_id,$agent_name,'bundle_toggled',
            "Bundle: {$row['name']} — ".($new?'enabled':'disabled'),$id,'bundle');
        echo json_encode(['success'=>true,'active'=>$new]);
        break;

    // ── Delete bundle ──────────────────────────────────────────────────────
    case 'delete':
        if (!$is_super) { echo json_encode(['success'=>false,'error'=>'Super only']); break; }
        $id  = (int)($_POST['id'] ?? 0);
        $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT name FROM pos_bundle_skus WHERE id=$id LIMIT 1"));
        if (!$row) { echo json_encode(['success'=>false,'error'=>'Not found']); break; }
        mysqli_query($conn,"DELETE FROM pos_bundle_sku_items WHERE bundle_id=$id");
        mysqli_query($conn,"DELETE FROM pos_bundle_skus WHERE id=$id");
        posLog($conn,$agent_id,$agent_name,'bundle_deleted',"Bundle: {$row['name']}",$id,'bundle');
        echo json_encode(['success'=>true]);
        break;

    // ── Search products for bundle builder ────────────────────────────────
    case 'search_products':
        $q = '%'.mysqli_real_escape_string($conn, trim($_GET['q']??'')).'%';
        $res = mysqli_query($conn,"SELECT codep,nomp,price,unit,category,onhand
            FROM produit WHERE active=1 AND (nomp LIKE '$q' OR barcode LIKE '$q')
            ORDER BY nomp LIMIT 20");
        $products = [];
        while ($r = mysqli_fetch_assoc($res)) $products[] = $r;
        echo json_encode(['success'=>true,'products'=>$products]);
        break;

    default:
        echo json_encode(['success'=>false,'error'=>'Unknown action']);
}
mysqli_close($conn);
