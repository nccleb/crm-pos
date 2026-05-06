<?php
// ============================================================
// NCC CRM POS — Promotions AJAX Backend
// ajax/pos_promotions_ajax.php
// ============================================================
session_start();
if (empty($_SESSION['oop'])) { echo json_encode(['error'=>'Unauthorized']); exit; }
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);
header('Content-Type: application/json');

$conn = mysqli_connect("172.18.208.1","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── List all promotions ───────────────────────────────────
if ($action === 'list') {
    $rows = [];
    $res = mysqli_query($conn,
        "SELECT * FROM pos_promotions ORDER BY active DESC, id DESC");
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    echo json_encode(['promotions' => $rows]);
    exit;
}

// ── Get active promotions for POS (used by pos.php) ──────
if ($action === 'get_active') {
    $today = date('Y-m-d');
    $rows  = [];
    $res = mysqli_query($conn,
        "SELECT * FROM pos_promotions
         WHERE active = 1
         AND (date_from IS NULL OR date_from <= '$today')
         AND (date_to   IS NULL OR date_to   >= '$today')
         ORDER BY type ASC");
    while ($r = mysqli_fetch_assoc($res)) {
        if ($r['bundle_items']) $r['bundle_items'] = json_decode($r['bundle_items'], true);
        $rows[] = $r;
    }
    echo json_encode(['promotions' => $rows]);
    exit;
}

// ── Save (add or edit) ────────────────────────────────────
if ($action === 'save') {
    $id             = (int)($_POST['id'] ?? 0);
    $name           = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $type           = mysqli_real_escape_string($conn, $_POST['type'] ?? 'percentage');
    $apply_to       = mysqli_real_escape_string($conn, $_POST['apply_to'] ?? 'product');
    $product_id     = (int)($_POST['product_id'] ?? 0);
    $product_name   = mysqli_real_escape_string($conn, trim($_POST['product_name'] ?? ''));
    $category       = mysqli_real_escape_string($conn, trim($_POST['category'] ?? ''));
    $discount_value = (float)($_POST['discount_value'] ?? 0);
    $buy_qty        = max(1, (int)($_POST['buy_qty'] ?? 1));
    $free_qty       = max(1, (int)($_POST['free_qty'] ?? 1));
    $bundle_price   = (float)($_POST['bundle_price'] ?? 0);
    $date_from      = !empty($_POST['date_from']) ? "'{$_POST['date_from']}'" : 'NULL';
    $date_to        = !empty($_POST['date_to'])   ? "'{$_POST['date_to']}'"   : 'NULL';

    if (!$name) { echo json_encode(['error' => 'Promotion name is required']); exit; }

    // Build bundle items JSON
    $bundle_items_sql = 'NULL';
    if ($type === 'bundle') {
        $bids   = $_POST['bundle_product_ids']   ?? [];
        $bnames = $_POST['bundle_product_names'] ?? [];
        $bqtys  = $_POST['bundle_product_qtys']  ?? [];
        $bundle = [];
        foreach ($bids as $i => $bid) {
            if (!$bid) continue;
            $bundle[] = [
                'product_id'   => (int)$bid,
                'product_name' => $bnames[$i] ?? '',
                'qty'          => max(1, (int)($bqtys[$i] ?? 1)),
            ];
        }
        if (count($bundle) < 2) {
            echo json_encode(['error' => 'Bundle needs at least 2 products']); exit;
        }
        $bundle_items_sql = "'" . mysqli_real_escape_string($conn, json_encode($bundle)) . "'";
    }

    if ($id > 0) {
        mysqli_query($conn,
            "UPDATE pos_promotions SET
             name='$name', type='$type', apply_to='$apply_to',
             product_id=" . ($product_id ?: 'NULL') . ",
             product_name='$product_name', category='$category',
             discount_value=$discount_value, buy_qty=$buy_qty, free_qty=$free_qty,
             bundle_items=$bundle_items_sql, bundle_price=$bundle_price,
             date_from=$date_from, date_to=$date_to
             WHERE id=$id");
    } else {
        mysqli_query($conn,
            "INSERT INTO pos_promotions
             (name, type, apply_to, product_id, product_name, category,
              discount_value, buy_qty, free_qty, bundle_items, bundle_price,
              date_from, date_to, active)
             VALUES
             ('$name','$type','$apply_to',
              " . ($product_id ?: 'NULL') . ",
              '$product_name','$category',
              $discount_value,$buy_qty,$free_qty,
              $bundle_items_sql,$bundle_price,
              $date_from,$date_to,1)");
        $id = mysqli_insert_id($conn);
    }
    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

// ── Toggle active ─────────────────────────────────────────
if ($action === 'toggle') {
    $id  = (int)($_POST['id'] ?? 0);
    $val = (int)($_POST['active'] ?? 0);
    mysqli_query($conn, "UPDATE pos_promotions SET active=$val WHERE id=$id");
    echo json_encode(['success' => true]);
    exit;
}

// ── Delete ────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    mysqli_query($conn, "DELETE FROM pos_promotions WHERE id=$id");
    echo json_encode(['success' => true]);
    exit;
}

// ── Product search (for building promotions) ──────────────
if ($action === 'search_products') {
    $q    = '%' . mysqli_real_escape_string($conn, $_GET['q'] ?? '') . '%';
    $rows = [];
    $res  = mysqli_query($conn,
        "SELECT codep AS id, nomp AS name, price, category, unit
         FROM produit WHERE active=1 AND nomp LIKE '$q'
         ORDER BY nomp LIMIT 20");
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    echo json_encode(['products' => $rows]);
    exit;
}

echo json_encode(['error' => "Unknown action: $action"]);
