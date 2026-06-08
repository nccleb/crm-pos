<?php
// ════════════════════════════════════════════════════════════════
// ADD THIS BLOCK TO ajax/pos_ajax.php — inside the action router
// Handles: get_all_products, add_product, update_product, delete_product, import_products
// ════════════════════════════════════════════════════════════════
// NOTE: Your produit table uses:
//   codep     = primary key (NOT id)
//   nomp      = product name (NOT nom)
// ════════════════════════════════════════════════════════════════

// ── GET ALL PRODUCTS ─────────────────────────────────────────────
if ($action === 'get_all_products') {
    $rows = [];
    $r = $conn->query("
        SELECT codep, nomp, category, barcode, price, cost_price, unit,
               onhand, low_stock_threshold, description, active, is_deleted,
               sold_by_weight, plu_code, price_per_kg, points_price, redeemable
        FROM produit
        WHERE is_deleted = 0 OR is_deleted IS NULL
        ORDER BY nomp ASC
    ");
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'products' => $rows]);
    exit;
}

// ── ADD PRODUCT ──────────────────────────────────────────────────
if ($action === 'add_product') {
    $nomp               = trim($data['nomp'] ?? '');
    $category           = trim($data['category'] ?? '');
    $unit               = trim($data['unit'] ?? 'piece');
    $barcode            = trim($data['barcode'] ?? '');
    $price              = floatval($data['price'] ?? 0);
    $cost_price         = floatval($data['cost_price'] ?? 0);
    $onhand             = floatval($data['onhand'] ?? 0);
    $low_stock_threshold= floatval($data['low_stock_threshold'] ?? 0);
    $description        = trim($data['description'] ?? '');
    $active             = intval($data['active'] ?? 1);
    $sold_by_weight     = intval($data['sold_by_weight'] ?? 0);
    $plu_code           = trim($data['plu_code'] ?? '');
    $price_per_kg       = floatval($data['price_per_kg'] ?? 0);

    if (!$nomp) { echo json_encode(['success'=>false,'message'=>'Name required']); exit; }

    $stmt = $conn->prepare("
        INSERT INTO produit
            (nomp, category, unit, barcode, price, cost_price, onhand,
             low_stock_threshold, description, active,
             sold_by_weight, plu_code, price_per_kg)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        'ssssddddsisis',
        // Note: price/cost/onhand/threshold are DECIMAL — using d
        $nomp, $category, $unit, $barcode,
        $price, $cost_price, $onhand, $low_stock_threshold,
        $description, $active,
        $sold_by_weight, $plu_code, $price_per_kg
    );
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>'Product added','codep'=>$conn->insert_id]);
    } else {
        echo json_encode(['success'=>false,'message'=>$conn->error]);
    }
    exit;
}

// ── UPDATE PRODUCT ───────────────────────────────────────────────
if ($action === 'update_product') {
    $codep              = intval($data['codep'] ?? 0);
    $nomp               = trim($data['nomp'] ?? '');
    $category           = trim($data['category'] ?? '');
    $unit               = trim($data['unit'] ?? 'piece');
    $barcode            = trim($data['barcode'] ?? '');
    $price              = floatval($data['price'] ?? 0);
    $cost_price         = floatval($data['cost_price'] ?? 0);
    $onhand             = floatval($data['onhand'] ?? 0);
    $low_stock_threshold= floatval($data['low_stock_threshold'] ?? 0);
    $description        = trim($data['description'] ?? '');
    $active             = intval($data['active'] ?? 1);
    $sold_by_weight     = intval($data['sold_by_weight'] ?? 0);
    $plu_code           = trim($data['plu_code'] ?? '');
    $price_per_kg       = floatval($data['price_per_kg'] ?? 0);

    if (!$codep || !$nomp) { echo json_encode(['success'=>false,'message'=>'Invalid data']); exit; }

    $stmt = $conn->prepare("
        UPDATE produit SET
            nomp=?, category=?, unit=?, barcode=?,
            price=?, cost_price=?, onhand=?, low_stock_threshold=?,
            description=?, active=?,
            sold_by_weight=?, plu_code=?, price_per_kg=?
        WHERE codep=?
    ");
    $stmt->bind_param(
        'ssssddddsisidi',
        $nomp, $category, $unit, $barcode,
        $price, $cost_price, $onhand, $low_stock_threshold,
        $description, $active,
        $sold_by_weight, $plu_code, $price_per_kg,
        $codep
    );
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>'Product updated']);
    } else {
        echo json_encode(['success'=>false,'message'=>$conn->error]);
    }
    exit;
}

// ── DELETE PRODUCT (soft delete) ─────────────────────────────────
if ($action === 'delete_product') {
    $codep = intval($data['codep'] ?? 0);
    if (!$codep) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }
    $stmt = $conn->prepare("UPDATE produit SET is_deleted=1, deleted_at=NOW() WHERE codep=?");
    $stmt->bind_param('i', $codep);
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>'Product deleted']);
    } else {
        echo json_encode(['success'=>false,'message'=>$conn->error]);
    }
    exit;
}

// ── IMPORT PRODUCTS (batch) ──────────────────────────────────────
if ($action === 'import_products') {
    $rows    = $data['rows'] ?? [];
    $imported = 0;
    $errors   = 0;
    $stmt = $conn->prepare("
        INSERT INTO produit (nomp, category, barcode, price, cost_price, unit, onhand, active)
        VALUES (?,?,?,?,?,?,?,1)
        ON DUPLICATE KEY UPDATE price=VALUES(price), onhand=VALUES(onhand)
    ");
    foreach ($rows as $row) {
        $nomp       = trim($row['nomp'] ?? '');
        $category   = trim($row['category'] ?? '');
        $barcode    = trim($row['barcode'] ?? '');
        $price      = floatval($row['price'] ?? 0);
        $cost_price = floatval($row['cost_price'] ?? 0);
        $unit       = trim($row['unit'] ?? 'piece');
        $onhand     = floatval($row['onhand'] ?? 0);
        if (!$nomp) { $errors++; continue; }
        $stmt->bind_param('sssddsd', $nomp, $category, $barcode, $price, $cost_price, $unit, $onhand);
        $stmt->execute() ? $imported++ : $errors++;
    }
    echo json_encode(['success'=>true,'imported'=>$imported,'errors'=>$errors]);
    exit;
}

// ════════════════════════════════════════════════════════════════
// ALSO FIX decodeScaleBarcode() — replace wrong column names
// ════════════════════════════════════════════════════════════════
/*
  FIND this query in your existing decodeScaleBarcode() function:
  
    SELECT id, nom, price ...
    FROM produit WHERE plu_code = ? ...
  
  REPLACE WITH:
  
    SELECT codep, nomp, price, price_per_kg, category, unit, sold_by_weight
    FROM produit
    WHERE plu_code = ? AND sold_by_weight = 1 AND (is_deleted = 0 OR is_deleted IS NULL)
    LIMIT 1
  
  Then wherever you return/use 'id' → use 'codep'
  And wherever you return/use 'nom' → use 'nomp'
*/
