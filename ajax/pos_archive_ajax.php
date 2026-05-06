<?php
/**
 * ajax/pos_archive_ajax.php
 * Archive, export and import AJAX backend
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['oop'])) {
    echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit();
}

$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$conn = mysqli_connect("172.18.208.1","root","1Sys9Admeen72","nccleb_test");
if (!$conn) { echo json_encode(['success'=>false,'error'=>'DB connection failed']); exit(); }
mysqli_set_charset($conn,'utf8mb4');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── List all archives ──────────────────────────────────────────────────
    case 'list_archives':
        $res = mysqli_query($conn,
            "SELECT * FROM archive_registry ORDER BY created_at DESC");
        $archives = [];
        while ($r = mysqli_fetch_assoc($res)) $archives[] = $r;
        echo json_encode(['success'=>true,'data'=>$archives]);
        break;

    // ── Create archive ─────────────────────────────────────────────────────
    case 'create_archive':
        $table_name  = in_array($_POST['table_name'],['pos_sales','stock_movements'])
                       ? $_POST['table_name'] : null;
        $type        = $_POST['type'] === 'date_based' ? 'date_based' : 'full';
        $date_to     = !empty($_POST['date_to']) ? $_POST['date_to'] : null;

        if (!$table_name) { echo json_encode(['success'=>false,'error'=>'Invalid table']); break; }
        if ($type === 'date_based' && !$date_to) {
            echo json_encode(['success'=>false,'error'=>'Date required for date-based archive']); break;
        }

        $archive_name = $table_name . '_archive_' . date('Ymd_His');
        $suffix = str_replace('pos_','',$table_name); // sales or stock_movements

        // Build WHERE clause
        $where = $type === 'date_based' ? "WHERE DATE(created_at) < '$date_to'" : '';

        // Count records to be archived
        $count_res = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) as cnt FROM `$table_name` $where"));
        $count = (int)$count_res['cnt'];

        if ($count === 0) {
            echo json_encode(['success'=>false,'error'=>'No records match the criteria']); break;
        }

        // Get date range
        $range = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT MIN(DATE(created_at)) as dfrom, MAX(DATE(created_at)) as dto
             FROM `$table_name` $where"));
        $date_from = $range['dfrom'];

        mysqli_begin_transaction($conn);
        $ok = true;

        // 1. Create archive table (copy structure)
        $ok = $ok && mysqli_query($conn,
            "CREATE TABLE `$archive_name` LIKE `$table_name`");

        // 2. Insert records into archive
        $ok = $ok && mysqli_query($conn,
            "INSERT INTO `$archive_name` SELECT * FROM `$table_name` $where");

        // 3. Delete moved records from main table
        $ok = $ok && mysqli_query($conn,
            "DELETE FROM `$table_name` $where");

        // 4. Register in archive_registry
        $esc_name = mysqli_real_escape_string($conn, $archive_name);
        $esc_tn   = mysqli_real_escape_string($conn, $table_name);
        $esc_agent= mysqli_real_escape_string($conn, $agent_name);
        $esc_df   = $date_from ? "'$date_from'" : 'NULL';
        $esc_dt   = $date_to   ? "'$date_to'"   : 'NULL';

        $ok = $ok && mysqli_query($conn,
            "INSERT INTO archive_registry
             (table_name, archive_table, archive_type, date_from, date_to, records_count, created_by)
             VALUES ('$esc_tn','$esc_name','$type',$esc_df,$esc_dt,$count,'$esc_agent')");

        if ($ok) {
            mysqli_commit($conn);
            echo json_encode([
                'success' => true,
                'archive_table' => $archive_name,
                'count' => $count
            ]);
        } else {
            mysqli_rollback($conn);
            // Try to drop the archive table if it was created
            mysqli_query($conn, "DROP TABLE IF EXISTS `$archive_name`");
            echo json_encode(['success'=>false,'error'=>mysqli_error($conn)]);
        }
        break;

    // ── Get archive records ────────────────────────────────────────────────
    case 'get_archive_records':
        $archive_table = mysqli_real_escape_string($conn, $_GET['archive_table'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        // Verify table exists in registry
        $reg = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM archive_registry WHERE archive_table = '$archive_table' LIMIT 1"));
        if (!$reg) { echo json_encode(['success'=>false,'error'=>'Archive not found']); break; }

        $count_res = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) as cnt FROM `$archive_table`"));
        $total = (int)$count_res['cnt'];

        $res = mysqli_query($conn,
            "SELECT * FROM `$archive_table` ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;

        echo json_encode([
            'success' => true,
            'registry' => $reg,
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'pages' => ceil($total / $per_page),
        ]);
        break;

    // ── Delete archive ─────────────────────────────────────────────────────
    case 'delete_archive':
        $archive_table = mysqli_real_escape_string($conn, $_POST['archive_table'] ?? '');

        $reg = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM archive_registry WHERE archive_table = '$archive_table' LIMIT 1"));
        if (!$reg) { echo json_encode(['success'=>false,'error'=>'Archive not found']); break; }

        mysqli_begin_transaction($conn);
        $ok  = mysqli_query($conn, "DROP TABLE IF EXISTS `$archive_table`");
        $ok  = $ok && mysqli_query($conn,
            "DELETE FROM archive_registry WHERE archive_table = '$archive_table'");

        if ($ok) { mysqli_commit($conn); echo json_encode(['success'=>true]); }
        else { mysqli_rollback($conn); echo json_encode(['success'=>false,'error'=>mysqli_error($conn)]); }
        break;

    // ── Export CSV ─────────────────────────────────────────────────────────
    case 'export_csv':
        $source     = mysqli_real_escape_string($conn, $_GET['source'] ?? 'pos_sales');
        $date_from  = $_GET['date_from'] ?? '';
        $date_to    = $_GET['date_to'] ?? '';

        // Allow live tables and archive tables
        $allowed_live = ['pos_sales','stock_movements','produit'];
        $is_live = in_array($source, $allowed_live);
        $is_archive = false;

        if (!$is_live) {
            $reg = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT id FROM archive_registry WHERE archive_table = '$source' LIMIT 1"));
            $is_archive = !empty($reg);
        }

        if (!$is_live && !$is_archive) {
            echo json_encode(['success'=>false,'error'=>'Invalid source']); break;
        }

        // produit has no created_at — handle separately
        $is_produit = ($source === 'produit');

        $where_parts = [];
        if (!$is_produit) {
            if ($date_from) $where_parts[] = "DATE(created_at) >= '$date_from'";
            if ($date_to)   $where_parts[] = "DATE(created_at) <= '$date_to'";
        }
        $where = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

        $order = $is_produit ? 'ORDER BY nomp ASC' : 'ORDER BY created_at DESC';

        $res = mysqli_query($conn, "SELECT * FROM `$source` $where $order");
        if (!$res) { echo json_encode(['success'=>false,'error'=>mysqli_error($conn)]); break; }

        // Build CSV content
        $rows = [];
        $headers_set = false;
        $headers = [];
        while ($row = mysqli_fetch_assoc($res)) {
            if (!$headers_set) { $headers = array_keys($row); $headers_set = true; }
            $rows[] = $row;
        }

        $csv = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(function($v) {
                return '"' . str_replace('"','""',$v) . '"';
            }, array_values($row))) . "\n";
        }

        // Return as base64 so JS can trigger download
        echo json_encode([
            'success'   => true,
            'filename'  => $source . '_export_' . date('Ymd') . '.csv',
            'content'   => base64_encode($csv),
            'count'     => count($rows),
        ]);
        break;

    // ── Import products from CSV/Excel data ────────────────────────────────
    case 'import_products':
        $rows = json_decode($_POST['rows'] ?? '[]', true);
        if (empty($rows)) { echo json_encode(['success'=>false,'error'=>'No data provided']); break; }

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $i => $row) {
            $nomp = mysqli_real_escape_string($conn, trim($row['nomp'] ?? $row['name'] ?? $row['product_name'] ?? ''));
            if (empty($nomp)) { $skipped++; continue; }

            $category  = mysqli_real_escape_string($conn, trim($row['category'] ?? 'General'));
            $price     = (float)($row['price'] ?? $row['selling_price'] ?? 0);
            $cost      = (float)($row['cost_price'] ?? $row['cost'] ?? 0);
            $onhand    = (int)($row['onhand'] ?? $row['stock'] ?? $row['quantity'] ?? 0);
            $unit      = mysqli_real_escape_string($conn, trim($row['unit'] ?? 'piece'));
            $barcode   = mysqli_real_escape_string($conn, trim($row['barcode'] ?? ''));
            $desc      = mysqli_real_escape_string($conn, trim($row['description'] ?? $row['desc'] ?? ''));

            // Check duplicate name
            $dup = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT codep FROM produit WHERE nomp = '$nomp' LIMIT 1"));
            if ($dup) { $skipped++; $errors[] = "Row " . ($i+1) . ": '$nomp' already exists — skipped"; continue; }

            $r = mysqli_query($conn,
                "INSERT INTO produit (nomp, category, price, cost_price, onhand, unit, barcode, description, ond, active, is_deleted)
                 VALUES ('$nomp','$category',$price,$cost,$onhand,'$unit','$barcode','$desc','$unit',1,0)");

            if ($r) { $imported++; }
            else { $errors[] = "Row " . ($i+1) . ": " . mysqli_error($conn); $skipped++; }
        }

        echo json_encode([
            'success'  => true,
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ]);
        break;

    default:
        echo json_encode(['success'=>false,'error'=>'Unknown action']);
}

mysqli_close($conn);
?>
