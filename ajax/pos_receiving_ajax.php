<?php
// ============================================================
// NCC CRM POS — Receiving AJAX Backend
// ajax/pos_receiving_ajax.php
// ============================================================

session_start();
if (!isset($_SESSION["oop"]) || !isset($_SESSION["ooq"])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$agent_name = $_SESSION["oop"];
$agent_id   = $_SESSION["ooq"];

header('Content-Type: application/json');

// ── DB Connection ──────────────────────────────────────────
$db = new mysqli("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if ($db->connect_error) {
    echo json_encode(["error" => "DB connection failed: " . $db->connect_error]);
    exit;
}
$db->set_charset("utf8mb4");

// ── Exchange rate from settings ────────────────────────────
$rate_row = $db->query("SELECT usd_to_lbp FROM company_settings LIMIT 1")->fetch_assoc();
$usd_to_lbp = $rate_row ? (float)$rate_row["usd_to_lbp"] : 89700;

$action = $_GET["action"] ?? $_POST["action"] ?? "";

// ============================================================
// SUPPLIERS
// ============================================================

if ($action === "list_suppliers") {
    $rows = [];
    $res = $db->query("SELECT * FROM pos_suppliers WHERE active = 1 ORDER BY name ASC");
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(["suppliers" => $rows]);
    exit;
}

if ($action === "save_supplier") {
    $id      = (int)($_POST["id"] ?? 0);
    $name    = trim($db->real_escape_string($_POST["name"] ?? ""));
    $contact = trim($db->real_escape_string($_POST["contact_person"] ?? ""));
    $phone   = trim($db->real_escape_string($_POST["phone"] ?? ""));
    $address = trim($db->real_escape_string($_POST["address"] ?? ""));
    $notes   = trim($db->real_escape_string($_POST["notes"] ?? ""));

    if (!$name) { echo json_encode(["error" => "Supplier name is required"]); exit; }

    if ($id > 0) {
        $db->query("UPDATE pos_suppliers SET name='$name', contact_person='$contact',
            phone='$phone', address='$address', notes='$notes' WHERE id=$id");
        echo json_encode(["success" => true, "message" => "Supplier updated"]);
    } else {
        $db->query("INSERT INTO pos_suppliers (name, contact_person, phone, address, notes)
            VALUES ('$name','$contact','$phone','$address','$notes')");
        echo json_encode(["success" => true, "id" => $db->insert_id, "message" => "Supplier added"]);
    }
    exit;
}

if ($action === "deactivate_supplier") {
    $id = (int)($_POST["id"] ?? 0);
    $db->query("UPDATE pos_suppliers SET active = 0 WHERE id = $id");
    echo json_encode(["success" => true]);
    exit;
}

// ============================================================
// PRODUCT SEARCH FOR RECEIVING
// ============================================================

if ($action === "search_products") {
    $q    = $db->real_escape_string(trim($_GET["q"] ?? ""));
    $rows = [];

    // Check if is_deleted column exists (requires pos_archive.sql to have been run)
    $col_check = $db->query("SHOW COLUMNS FROM produit LIKE 'is_deleted'");
    $has_is_deleted = $col_check && $col_check->num_rows > 0;
    $deleted_clause = $has_is_deleted ? "AND is_deleted = 0" : "";

    $sql = "SELECT codep AS id, nomp, barcode, price, cost_price, onhand, unit, category
            FROM produit
            WHERE active = 1 $deleted_clause
            AND (nomp LIKE '%$q%' OR barcode LIKE '%$q%')
            ORDER BY nomp ASC LIMIT 30";
    $res = $db->query($sql);
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(["products" => $rows]);
    exit;
}

if ($action === "get_last_cost") {
    // Returns the last recorded cost price for a product from previous receivings
    $product_id = (int)($_GET["product_id"] ?? 0);
    $row = $db->query(
        "SELECT i.cost_price_lbp, r.received_date, r.supplier_name
         FROM stock_receiving_items i
         JOIN stock_receivings r ON r.id = i.receiving_id
         WHERE i.product_id = $product_id AND r.status = 'completed'
         ORDER BY r.received_date DESC, i.id DESC LIMIT 1"
    )->fetch_assoc();
    echo json_encode(["last_cost" => $row]);
    exit;
}

// ============================================================
// SAVE RECEIVING
// ============================================================

if ($action === "save_receiving") {
    $data = json_decode(file_get_contents("php://input"), true);

    $supplier_id    = (int)($data["supplier_id"] ?? 0);
    $supplier_name  = $db->real_escape_string($data["supplier_name"] ?? "");
    $invoice_number = $db->real_escape_string($data["invoice_number"] ?? "");
    $received_date  = $db->real_escape_string($data["received_date"] ?? date("Y-m-d"));
    $notes          = $db->real_escape_string($data["notes"] ?? "");
    $items          = $data["items"] ?? [];
    $exchange_rate  = (float)($data["exchange_rate"] ?? $usd_to_lbp);

    if (empty($items)) {
        echo json_encode(["error" => "No items in this receiving"]);
        exit;
    }

    $db->begin_transaction();
    try {
        // Calculate totals — all in LBP
        $total_lbp = 0;
        foreach ($items as $item) {
            $total_lbp += (float)$item["cost_price_lbp"] * (float)$item["qty"];
        }
        $total_usd = $total_lbp / $exchange_rate; // keep for reference only

        // Insert receiving header
        $db->query("INSERT INTO stock_receivings
            (supplier_id, supplier_name, invoice_number, received_date, agent_id, agent_name,
             exchange_rate, total_cost_usd, total_cost_lbp, notes, status)
            VALUES
            ($supplier_id, '$supplier_name', '$invoice_number', '$received_date',
             $agent_id, '$agent_name', $exchange_rate, $total_usd, $total_lbp,
             '$notes', 'completed')");
        $receiving_id = $db->insert_id;

        foreach ($items as $item) {
            $product_id    = (int)$item["product_id"];
            $product_name  = $db->real_escape_string($item["product_name"]);
            $qty           = (float)$item["qty"];
            $cost_lbp      = (float)$item["cost_price_lbp"];
            $cost_usd      = $cost_lbp / $exchange_rate; // reference only
            $subtotal_lbp  = $qty * $cost_lbp;
            $subtotal_usd  = $qty * $cost_usd;
            $expiry        = !empty($item["expiry_date"])
                             ? "'" . $db->real_escape_string($item["expiry_date"]) . "'"
                             : "NULL";
            $item_notes    = $db->real_escape_string($item["notes"] ?? "");

            // Insert receiving line item
            $db->query("INSERT INTO stock_receiving_items
                (receiving_id, product_id, product_name, qty_received,
                 cost_price_usd, cost_price_lbp, subtotal_usd, subtotal_lbp,
                 expiry_date, notes)
                VALUES
                ($receiving_id, $product_id, '$product_name', $qty,
                 $cost_usd, $cost_lbp, $subtotal_usd, $subtotal_lbp,
                 $expiry, '$item_notes')");

            // Get current stock for movement log
            $cur = $db->query("SELECT onhand FROM produit WHERE codep = $product_id")->fetch_assoc();
            $qty_before = $cur ? (float)$cur["onhand"] : 0;
            $qty_after  = $qty_before + $qty;

            // Update product stock + cost price (stored in LBP) + last received
            $db->query("UPDATE produit SET
                onhand = onhand + $qty,
                cost_price = $cost_lbp,
                last_received_at = NOW()
                WHERE codep = $product_id");

            // Update expiry if provided
            if (!empty($item["expiry_date"])) {
                $exp = $db->real_escape_string($item["expiry_date"]);
                $db->query("UPDATE produit SET expiry_date = '$exp' WHERE codep = $product_id");
            }

            // Build a clean note
            $note_parts = ["Receiving #$receiving_id"];
            if ($supplier_name && $supplier_name !== 'Walk-in / Unknown') {
                $note_parts[] = "Supplier: $supplier_name";
            }
            if ($invoice_number) {
                $note_parts[] = "Inv: $invoice_number";
            }
            if ($notes) {
                $note_parts[] = $notes;
            }
            $movement_note = implode(' — ', $note_parts);

            // Log to stock_movements (shows up on pos_stock.php)
            $db->query("INSERT INTO stock_movements
                (product_id, product_name, type, qty_change, qty_before, qty_after,
                 reference_id, note, agent_id, agent_name)
                VALUES
                ($product_id, '$product_name', 'restock', $qty, $qty_before, $qty_after,
                 $receiving_id, '$movement_note', $agent_id, '$agent_name')");
        }

        $db->commit();
        echo json_encode([
            "success"      => true,
            "receiving_id" => $receiving_id,
            "total_usd"    => round($total_usd, 2),
            "total_lbp"    => round($total_lbp, 0),
            "message"      => "Receiving #$receiving_id saved successfully"
        ]);

    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(["error" => "Failed to save: " . $e->getMessage()]);
    }
    exit;
}

// ============================================================
// LIST & DETAIL
// ============================================================

if ($action === "list_receivings") {
    // Check table exists first
    $tbl = $db->query("SHOW TABLES LIKE 'stock_receivings'");
    if (!$tbl || $tbl->num_rows === 0) {
        echo json_encode(["receivings" => [], "rate" => $usd_to_lbp,
            "warning" => "stock_receivings table not found — please run pos_suppliers.sql"]);
        exit;
    }

    $date_from    = $db->real_escape_string($_GET["date_from"] ?? date("Y-m-d", strtotime("-30 days")));
    $date_to      = $db->real_escape_string($_GET["date_to"]   ?? date("Y-m-d"));
    $supplier_id  = (int)($_GET["supplier_id"] ?? 0);

    $where = "WHERE r.received_date BETWEEN '$date_from' AND '$date_to'";
    if ($supplier_id > 0) $where .= " AND r.supplier_id = $supplier_id";

    $rows = [];
    $res = $db->query("
        SELECT r.*,
               COUNT(i.id) AS item_count
        FROM stock_receivings r
        LEFT JOIN stock_receiving_items i ON i.receiving_id = r.id
        $where
        GROUP BY r.id
        ORDER BY r.received_date DESC, r.id DESC
    ");
    while ($row = $res->fetch_assoc()) $rows[] = $row;
    echo json_encode(["receivings" => $rows, "rate" => $usd_to_lbp]);
    exit;
}

if ($action === "get_receiving") {
    $id  = (int)($_GET["id"] ?? 0);
    $hdr = $db->query("SELECT * FROM stock_receivings WHERE id = $id")->fetch_assoc();
    if (!$hdr) { echo json_encode(["error" => "Not found"]); exit; }

    $items = [];
    $res   = $db->query("SELECT * FROM stock_receiving_items WHERE receiving_id = $id ORDER BY id ASC");
    while ($r = $res->fetch_assoc()) $items[] = $r;

    echo json_encode(["receiving" => $hdr, "items" => $items, "rate" => $usd_to_lbp]);
    exit;
}

if ($action === "get_batches") {
    $product_id = (int)($_GET["product_id"] ?? 0);
    $rows = [];
    $res  = $db->query(
        "SELECT i.expiry_date, i.qty_received, i.notes,
                r.received_date, r.supplier_name, r.invoice_number
         FROM stock_receiving_items i
         JOIN stock_receivings r ON r.id = i.receiving_id
         WHERE i.product_id = $product_id
         AND i.expiry_date IS NOT NULL
         ORDER BY i.expiry_date ASC"
    );
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(["batches" => $rows]);
    exit;
}

if ($action === "deactivate_product") {
    $product_id = (int)($_POST["product_id"] ?? 0);
    if (!$product_id) { echo json_encode(["error" => "No product ID"]); exit; }
    $db->query("UPDATE produit SET active = 0 WHERE codep = $product_id");
    echo json_encode(["success" => true]);
    exit;
}

echo json_encode(["error" => "Unknown action: $action"]);
