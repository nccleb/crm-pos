<?php
/**
 * ajax/pos_ajax.php
 * Handles all POS AJAX requests
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['oop']) || empty($_SESSION['ooq'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$agent_name = $_SESSION['oop'];
$agent_id   = (int)$_SESSION['ooq'];

$conn = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit();
}
mysqli_set_charset($conn, 'utf8mb4');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── Search clients ─────────────────────────────────────────────────────
    case 'search_clients':
        $q = '%' . mysqli_real_escape_string($conn, $_GET['q'] ?? '') . '%';
        $res = mysqli_query($conn,
            "SELECT id, nom, prenom, company, number
             FROM client
             WHERE nom LIKE '$q' OR prenom LIKE '$q'
             OR company LIKE '$q' OR number LIKE '$q'
             ORDER BY nom, prenom LIMIT 10"
        );
        $clients = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $clients[] = [
                'id'      => $r['id'],
                'name'    => trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? '')),
                'company' => $r['company'] ?? '',
                'number'  => $r['number'] ?? ''
            ];
        }
        echo json_encode(['success' => true, 'data' => $clients]);
        break;

    // ── Search products ────────────────────────────────────────────────────
    case 'search_products':
        $raw = trim($_GET['q'] ?? '');
        $cat = trim($_GET['cat'] ?? '');
        $cat_sql = $cat ? "AND category = '" . mysqli_real_escape_string($conn, $cat) . "'" : '';

        // Check if is_weighted column exists (requires pos_weight.sql to have been run)
        $col_check  = mysqli_query($conn, "SHOW COLUMNS FROM produit LIKE 'is_weighted'");
        $has_weight = $col_check && mysqli_num_rows($col_check) > 0;
        $weight_col = $has_weight ? ', is_weighted' : ', 0 AS is_weighted';

        if ($raw === '') {
            $res = mysqli_query($conn,
                "SELECT codep, nomp, price, onhand, unit, category, image, barcode $weight_col
                 FROM produit WHERE active = 1 $cat_sql
                 ORDER BY nomp LIMIT 500"
            );
        } else {
            $q = '%' . mysqli_real_escape_string($conn, $raw) . '%';
            $res = mysqli_query($conn,
                "SELECT codep, nomp, price, onhand, unit, category, image, barcode $weight_col
                 FROM produit
                 WHERE active = 1 $cat_sql
                 AND (nomp LIKE '$q' OR barcode LIKE '$q')
                 ORDER BY nomp LIMIT 500"
            );
        }
        $products = [];
        while ($r = mysqli_fetch_assoc($res)) $products[] = $r;
        echo json_encode(['success' => true, 'data' => $products]);
        break;

    // ── Get product by ID ──────────────────────────────────────────────────
    case 'get_product':
        $id  = (int)($_GET['id'] ?? 0);
        $res = mysqli_query($conn, "SELECT * FROM produit WHERE codep = $id AND active = 1 LIMIT 1");
        if ($row = mysqli_fetch_assoc($res)) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Product not found']);
        }
        break;

    // ── Complete a sale ────────────────────────────────────────────────────
    case 'complete_sale':
        $client_id      = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null;
        $client_name    = mysqli_real_escape_string($conn, $_POST['client_name'] ?? 'Walk-in Customer');
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'cash');
        $currency       = 'LBP';
        $discount       = (float)($_POST['discount'] ?? 0);
        $notes          = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        $items          = json_decode($_POST['items'] ?? '[]', true);
        $paid_usd       = (float)($_POST['paid_usd']   ?? 0);
        $paid_lbp       = (float)($_POST['paid_lbp']   ?? 0);
        $change_usd     = (float)($_POST['change_usd'] ?? 0);  // USD bills to return
        $change_lbp     = (float)($_POST['change_lbp'] ?? 0);  // LBP remainder to return

        // Get exchange rate AND VAT rate
        $co_rate    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT usd_to_lbp, vat_rate FROM company_settings LIMIT 1"));
        $usd_to_lbp = (float)($co_rate['usd_to_lbp'] ?? 89500);
        $vat_rate   = (float)($co_rate['vat_rate']   ?? 0);

        if (empty($items)) {
            echo json_encode(['success' => false, 'error' => 'No items in sale']);
            break;
        }

        // Items come in as LBP prices — convert to USD for DB storage
        // All amounts stored in LBP directly — no USD conversion
        $total = 0;
        foreach ($items as $item) {
            $total += (float)$item['unit_price'] * (float)$item['qty'];
        }
        $discount_lbp = $discount;               // discount arrives in LBP
        $final_total  = max(0, $total - $discount_lbp); // pre-VAT, stored in LBP
        // change_usd / change_lbp already computed by frontend and passed in POST

        // Insert sale header
        $client_id_sql = $client_id ? $client_id : 'NULL';
        try {
            $insert = mysqli_query($conn,
                "INSERT INTO pos_sales (client_id, client_name, total, discount, final_total, payment_method, currency, notes, agent_id, agent_name, paid_usd, paid_lbp, change_usd, change_lbp, status)
                 VALUES ($client_id_sql, '$client_name', $total, $discount_lbp, $final_total, '$payment_method', '$currency', '$notes', $agent_id, '$agent_name', $paid_usd, $paid_lbp, $change_usd, $change_lbp, 'completed')"
            );
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
            break;
        }

        if (!$insert) {
            echo json_encode(['success' => false, 'error' => 'DB error: ' . mysqli_error($conn)]);
            break;
        }

        $sale_id = mysqli_insert_id($conn);

        // Insert sale items, update stock and log movements
        foreach ($items as $item) {
            $product_id     = (int)$item['product_id'];
            $product_name   = mysqli_real_escape_string($conn, $item['product_name']);
            $qty            = (float)$item['qty'];          // float — supports 0.750 kg
            $is_weighted    = !empty($item['is_weighted']) ? 1 : 0;
            $unit_price = (float)$item['unit_price'];  // stored in LBP directly
            $subtotal   = $qty * $unit_price;

            mysqli_query($conn,
                "INSERT INTO pos_sale_items (sale_id, product_id, product_name, qty, unit_price, subtotal)
                 VALUES ($sale_id, $product_id, '$product_name', $qty, $unit_price, $subtotal)"
            );

            // Stock deduction — works for both pieces and kg
            $stock_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT onhand FROM produit WHERE codep = $product_id LIMIT 1"));
            $qty_before = (float)($stock_row['onhand'] ?? 0);
            $qty_after  = max(0, round($qty_before - $qty, 3));
            $qty_change = round($qty_after - $qty_before, 3);

            mysqli_query($conn, "UPDATE produit SET onhand = $qty_after WHERE codep = $product_id");

            mysqli_query($conn,
                "INSERT INTO stock_movements (product_id, product_name, type, qty_change, qty_before, qty_after, reference_id, note, agent_id, agent_name)
                 VALUES ($product_id, '$product_name', 'sale', $qty_change, $qty_before, $qty_after, $sale_id, 'Sale #$sale_id', $agent_id, '$agent_name')"
            );
        }

        // ── Auto thermal print if mode = automatic ─────────────────────
        $print_result = null;
        $co = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT print_mode, printer_name, cash_drawer FROM company_settings LIMIT 1"));
        if ($co && ($co['print_mode'] ?? 'manual') === 'automatic' && !empty($co['printer_name'])) {
            require_once dirname(__FILE__) . '/../pos_escpos.php';
            $print_result = printEscPos($sale_id, $conn);
        }

        // ── Auto open cash drawer if set to automatic ──────────────────
        $drawer_result = null;
        if ($co && ($co['cash_drawer'] ?? 'disabled') === 'automatic' && !empty($co['printer_name'])) {
            if (!function_exists('openCashDrawer')) {
                require_once dirname(__FILE__) . '/../pos_escpos.php';
            }
            $drawer_result = openCashDrawer($conn);
        }

        $usd_equiv = $usd_to_lbp > 0 ? round($final_total / $usd_to_lbp, 2) : 0;
        echo json_encode([
            'success'       => true,
            'sale_id'       => $sale_id,
            'final_total'   => $final_total,
            'usd_equiv'     => $usd_equiv,
            'print_result'  => $print_result,
            'drawer_result' => $drawer_result
        ]);
        break;

    // ── Get sale for receipt ───────────────────────────────────────────────
    case 'get_sale':
        $sale_id = (int)($_GET['id'] ?? 0);
        $sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pos_sales WHERE id = $sale_id LIMIT 1"));
        if (!$sale) {
            echo json_encode(['success' => false, 'error' => 'Sale not found']);
            break;
        }
        $items_res = mysqli_query($conn, "SELECT * FROM pos_sale_items WHERE sale_id = $sale_id");
        $items = [];
        while ($i = mysqli_fetch_assoc($items_res)) $items[] = $i;
        echo json_encode(['success' => true, 'sale' => $sale, 'items' => $items]);
        break;

    // ── Manual restock / adjustment ────────────────────────────────────────
    case 'adjust_stock':
        $product_id   = (int)$_POST['product_id'];
        $type         = in_array($_POST['type'], ['restock','adjustment','return']) ? $_POST['type'] : 'adjustment';
        $qty_change   = (int)$_POST['qty_change'];
        $note         = mysqli_real_escape_string($conn, $_POST['note'] ?? '');

        // Get current stock
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nomp, onhand FROM produit WHERE codep = $product_id LIMIT 1"));
        if (!$row) { echo json_encode(['success'=>false,'error'=>'Product not found']); break; }

        $qty_before = (int)$row['onhand'];
        $qty_after  = max(0, $qty_before + $qty_change);
        $product_name = mysqli_real_escape_string($conn, $row['nomp']);

        mysqli_query($conn, "UPDATE produit SET onhand = $qty_after WHERE codep = $product_id");
        mysqli_query($conn,
            "INSERT INTO stock_movements (product_id, product_name, type, qty_change, qty_before, qty_after, note, agent_id, agent_name)
             VALUES ($product_id, '$product_name', '$type', $qty_change, $qty_before, $qty_after, '$note', $agent_id, '$agent_name')"
        );

        echo json_encode(['success'=>true, 'qty_before'=>$qty_before, 'qty_after'=>$qty_after]);
        break;


    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

mysqli_close($conn);
?>
