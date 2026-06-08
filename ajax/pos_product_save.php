<?php
/**
 * pos_product_save.php
 * Standalone add/update/delete product handler.
 * Place at: C:\wamp64\www\ajax\pos_product_save.php
 * This file is independent of pos_ajax.php
 */
session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['oop'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$conn = mysqli_connect("192.168.1.19", "root", "1Sys9Admeen72", "nccleb_test");
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . mysqli_connect_error()]);
    exit();
}
mysqli_set_charset($conn, 'utf8mb4');

$body   = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $body['action'] ?? '';

// ── Add Product ────────────────────────────────────────────────────────────
if ($action === 'add_product') {
    $nomp           = mysqli_real_escape_string($conn, trim($body['nomp']                ?? ''));
    $category       = mysqli_real_escape_string($conn, trim($body['category']            ?? ''));
    $unit           = mysqli_real_escape_string($conn, trim($body['unit']                ?? 'piece'));
    $barcode        = mysqli_real_escape_string($conn, trim($body['barcode']             ?? ''));
    $price          = (float)($body['price']                ?? 0);
    $cost_price     = (float)($body['cost_price']           ?? 0);
    $onhand         = (float)($body['onhand']               ?? 0);
    $low_stock      = (float)($body['low_stock_threshold']  ?? 0);
    $description    = mysqli_real_escape_string($conn, trim($body['description']         ?? ''));
    $active         = (int)($body['active']                 ?? 1);
    $sold_by_weight = (int)($body['sold_by_weight']         ?? 0);
    $plu_code       = mysqli_real_escape_string($conn, trim($body['plu_code']            ?? ''));
    $price_per_kg   = (float)($body['price_per_kg']         ?? 0);
    $ond            = '';

    if (!$nomp) {
        echo json_encode(['success' => false, 'message' => 'Product name is required']);
        exit();
    }

    $ok = mysqli_query($conn,
        "INSERT INTO produit
            (nomp, category, unit, barcode, price, cost_price, onhand,
             low_stock_threshold, description, active,
             sold_by_weight, plu_code, price_per_kg, ond)
         VALUES
            ('$nomp','$category','$unit','$barcode',
             $price, $cost_price, $onhand, $low_stock,
             '$description', $active,
             $sold_by_weight, '$plu_code', $price_per_kg, '$ond')");

    if ($ok) {
        $new_id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'message' => 'Product added', 'codep' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn), 'errno' => mysqli_errno($conn)]);
    }
    exit();
}

// ── Update Product ─────────────────────────────────────────────────────────
if ($action === 'update_product') {
    $codep          = (int)($body['codep']                  ?? 0);
    $nomp           = mysqli_real_escape_string($conn, trim($body['nomp']                ?? ''));
    $category       = mysqli_real_escape_string($conn, trim($body['category']            ?? ''));
    $unit           = mysqli_real_escape_string($conn, trim($body['unit']                ?? 'piece'));
    $barcode        = mysqli_real_escape_string($conn, trim($body['barcode']             ?? ''));
    $price          = (float)($body['price']                ?? 0);
    $cost_price     = (float)($body['cost_price']           ?? 0);
    $onhand         = (float)($body['onhand']               ?? 0);
    $low_stock      = (float)($body['low_stock_threshold']  ?? 0);
    $description    = mysqli_real_escape_string($conn, trim($body['description']         ?? ''));
    $active         = (int)($body['active']                 ?? 1);
    $sold_by_weight = (int)($body['sold_by_weight']         ?? 0);
    $plu_code       = mysqli_real_escape_string($conn, trim($body['plu_code']            ?? ''));
    $price_per_kg   = (float)($body['price_per_kg']         ?? 0);

    if (!$codep || !$nomp) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }

    $ok = mysqli_query($conn,
        "UPDATE produit SET
            nomp='$nomp', category='$category', unit='$unit', barcode='$barcode',
            price=$price, cost_price=$cost_price, onhand=$onhand,
            low_stock_threshold=$low_stock, description='$description',
            active=$active, sold_by_weight=$sold_by_weight,
            plu_code='$plu_code', price_per_kg=$price_per_kg
         WHERE codep=$codep");

    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Product updated']);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn), 'errno' => mysqli_errno($conn)]);
    }
    exit();
}

// ── Delete Product ─────────────────────────────────────────────────────────
if ($action === 'delete_product') {
    $codep = (int)($body['codep'] ?? 0);
    if (!$codep) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit();
    }
    $ok = mysqli_query($conn, "UPDATE produit SET is_deleted=1, deleted_at=NOW() WHERE codep=$codep");
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Product deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
