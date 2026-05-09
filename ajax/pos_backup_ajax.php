<?php
/**
 * ajax/pos_backup_ajax.php
 * Database backup and restore for NCC CRM POS
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['oop']) || $_SESSION['oop'] !== 'super') {
    echo json_encode(['success'=>false,'error'=>'Super admin only']); exit();
}

$db_host = '172.18.208.1';
$db_user = 'root';
$db_pass = '1Sys9Admeen72';
$db_name = 'nccleb_test';

$agent_id   = (int)($_SESSION['ooq'] ?? 0);
$agent_name = $_SESSION['oop'];

$action = $_POST['action'] ?? $_GET['action'] ?? '';
require_once __DIR__ . '/pos_log.php';

// ── BACKUP ────────────────────────────────────────────────────────────────────
if ($action === 'backup') {
    set_time_limit(120);

    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) { echo json_encode(['success'=>false,'error'=>'DB connection failed']); exit(); }
    mysqli_set_charset($conn, 'utf8mb4');

    $filename = $db_name . '_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $sql  = "-- NCC CRM POS — Database Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Database: $db_name\n\n";
    $sql .= "SET NAMES utf8mb4;\n";
    $sql .= "SET foreign_key_checks = 0;\n\n";

    // Get all tables
    $tables_res = mysqli_query($conn, "SHOW TABLES");
    $tables = [];
    while ($t = mysqli_fetch_row($tables_res)) $tables[] = $t[0];

    foreach ($tables as $table) {
        // Table structure
        $create_res = mysqli_fetch_assoc(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
        $create_sql = $create_res['Create Table'];
        $sql .= "-- Table: $table\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $create_sql . ";\n\n";

        // Table data
        $rows_res = mysqli_query($conn, "SELECT * FROM `$table`");
        $num_rows = mysqli_num_rows($rows_res);
        if ($num_rows === 0) { $sql .= "\n"; continue; }

        $fields_res = mysqli_query($conn, "DESCRIBE `$table`");
        $fields = [];
        while ($f = mysqli_fetch_assoc($fields_res)) $fields[] = '`' . $f['Field'] . '`';
        $fields_str = implode(', ', $fields);

        // Batch inserts in groups of 100
        $batch = [];
        $count = 0;
        while ($row = mysqli_fetch_row($rows_res)) {
            $vals = array_map(function($v) use ($conn) {
                return $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $v) . "'";
            }, $row);
            $batch[] = '(' . implode(',', $vals) . ')';
            $count++;
            if ($count % 100 === 0) {
                $sql .= "INSERT INTO `$table` ($fields_str) VALUES\n" . implode(",\n", $batch) . ";\n";
                $batch = [];
            }
        }
        if (!empty($batch)) {
            $sql .= "INSERT INTO `$table` ($fields_str) VALUES\n" . implode(",\n", $batch) . ";\n";
        }
        $sql .= "\n";
    }

    $sql .= "SET foreign_key_checks = 1;\n";
    $sql .= "-- End of backup\n";

    posLog($conn, $agent_id, $agent_name, 'backup_downloaded',
        "Database backup downloaded: $filename (" . count($tables) . " tables)");

    mysqli_close($conn);

    echo json_encode([
        'success'  => true,
        'filename' => $filename,
        'content'  => base64_encode($sql),
        'tables'   => count($tables),
    ]);
    exit();
}

// ── RESTORE ───────────────────────────────────────────────────────────────────
if ($action === 'restore') {
    set_time_limit(300);

    if (empty($_FILES['sqlfile']['tmp_name'])) {
        echo json_encode(['success'=>false,'error'=>'No file uploaded']); exit();
    }

    $file = $_FILES['sqlfile']['tmp_name'];
    $orig = $_FILES['sqlfile']['name'];

    // Validate it's a .sql file
    if (strtolower(pathinfo($orig, PATHINFO_EXTENSION)) !== 'sql') {
        echo json_encode(['success'=>false,'error'=>'Only .sql files are accepted']); exit();
    }

    $content = file_get_contents($file);
    if (empty($content)) {
        echo json_encode(['success'=>false,'error'=>'File is empty']); exit();
    }

    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) { echo json_encode(['success'=>false,'error'=>'DB connection failed']); exit(); }
    mysqli_set_charset($conn, 'utf8mb4');

    // Split SQL into statements
    // Remove comments, split on semicolons
    $lines = explode("\n", $content);
    $clean = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '--') === 0 || strpos($line, '#') === 0) continue;
        $clean[] = $line;
    }
    $content_clean = implode("\n", $clean);

    // Split by semicolon
    $statements = array_filter(
        array_map('trim', explode(';', $content_clean)),
        fn($s) => strlen($s) > 3
    );

    mysqli_query($conn, "SET foreign_key_checks = 0");
    mysqli_query($conn, "SET NAMES utf8mb4");

    $executed = 0;
    $errors   = [];

    foreach ($statements as $stmt) {
        if (empty(trim($stmt))) continue;
        if (!mysqli_query($conn, $stmt)) {
            $errors[] = mysqli_error($conn);
            if (count($errors) > 5) break; // stop on repeated errors
        } else {
            $executed++;
        }
    }

    mysqli_query($conn, "SET foreign_key_checks = 1");
    mysqli_close($conn);

    if (!empty($errors) && $executed === 0) {
        echo json_encode(['success'=>false,'error'=>'Restore failed: ' . implode('; ', array_slice($errors,0,3))]);
    } else {
        posLog($conn, $agent_id, $agent_name, 'backup_restored',
            "Database restored from: $orig — $executed statements executed" . (count($errors) ? " (" . count($errors) . " warnings)" : ''));
        echo json_encode([
            'success'    => true,
            'statements' => $executed,
            'warnings'   => count($errors) > 0 ? $errors : null,
        ]);
    }
    exit();
}

// ── Export Activity Log to CSV ─────────────────────────────────────────────
if ($action === 'export_activity') {
    $conn2 = mysqli_connect($db_host,$db_user,$db_pass,$db_name);
    mysqli_set_charset($conn2,'utf8mb4');

    $res = mysqli_query($conn2,
        "SELECT id, agent_name, action, details, reference_id, reference_type, ip_address, created_at
         FROM pos_activity_log ORDER BY created_at DESC");

    $rows = [];
    $headers = ['ID','User','Action','Details','Ref ID','Ref Type','IP','Date/Time'];
    while ($r = mysqli_fetch_row($res)) $rows[] = $r;

    $csv  = implode(',', $headers) . "\n";
    foreach ($rows as $row) {
        $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"','""',$v??'') . '"', $row)) . "\n";
    }

    $filename = 'activity_log_backup_' . date('Y-m-d_H-i-s') . '.csv';
    $count    = count($rows);

    // UTF-8 BOM so Excel opens the file with correct encoding
    $bom = "\xEF\xBB\xBF";

    posLog($conn2, $agent_id, $agent_name, 'activity_log_exported',
        "Activity log exported: $count records — $filename");

    mysqli_close($conn2);
    echo json_encode(['success'=>true,'filename'=>$filename,'content'=>base64_encode($bom.$csv),'count'=>$count]);
    exit;
}

// ── Clear Activity Log ────────────────────────────────────────────────────
if ($action === 'clear_activity') {
    $conn2 = mysqli_connect($db_host,$db_user,$db_pass,$db_name);
    mysqli_set_charset($conn2,'utf8mb4');

    $count = (int)mysqli_fetch_assoc(mysqli_query($conn2,"SELECT COUNT(*) AS c FROM pos_activity_log"))['c'];
    mysqli_query($conn2, "TRUNCATE TABLE pos_activity_log");

    // Log the clear action itself (first entry in fresh log)
    posLog($conn2, $agent_id, $agent_name, 'activity_log_cleared',
        "Activity log cleared — $count records deleted after CSV export");

    mysqli_close($conn2);
    echo json_encode(['success'=>true,'deleted'=>$count]);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Unknown action']);
