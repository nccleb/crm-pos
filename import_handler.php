<?php
// Disable error display, log only
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set header FIRST before any output
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if action exists
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit();
}

$action = $_POST['action'];

// Check if this is import action
if ($action !== 'import_csv') {
    echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    exit();
}

// Check if data exists
if (!isset($_POST['data'])) {
    echo json_encode(['success' => false, 'message' => 'No data provided']);
    exit();
}

// Decode JSON data
$jsonData = $_POST['data'];
$data = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'JSON decode error: ' . json_last_error_msg()]);
    exit();
}

if (!is_array($data) || empty($data)) {
    echo json_encode(['success' => false, 'message' => 'Data is empty or invalid']);
    exit();
}

// Database connection
$host = "172.18.208.1";
$user = "root";
$pass = "1Sys9Admeen72";
$db = "nccleb_test";

$conn = @mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed: ' . mysqli_connect_error()
    ]);
    exit();
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

$successCount = 0;
$errorCount = 0;
$errors = [];

// Process each row
foreach ($data as $index => $row) {
    $rowNum = $index + 1;
    
    // Skip completely empty rows
    $hasData = false;
    foreach ($row as $value) {
        if (!empty(trim($value))) {
            $hasData = true;
            break;
        }
    }
    
    if (!$hasData) {
        continue;
    }
    
    // Check required fields
    if (empty(trim($row['nom'] ?? '')) || empty(trim($row['prenom'] ?? ''))) {
        $errorCount++;
        $errors[] = "Row $rowNum: Missing first name or last name";
        continue;
    }
    
    // Prepare data - extract and clean all fields
    $nom = trim($row['nom'] ?? '');
    $prenom = trim($row['prenom'] ?? '');
    $category = trim($row['category'] ?? '');
    $source = trim($row['source'] ?? '');
    $grade = trim($row['grade'] ?? '');
    $payment = trim($row['payment'] ?? '');
    $card = trim($row['card'] ?? '');
    $community = trim($row['community'] ?? '');
    $company = trim($row['company'] ?? '');
    $job = trim($row['job'] ?? '');
    
    // Intelligence fields
    $decision_authority = trim($row['decision_authority'] ?? '');
    $communication_style = trim($row['communication_style'] ?? '');
    $customer_priority = trim($row['customer_priority'] ?? '');
    $key_preferences = trim($row['key_preferences'] ?? '');
    
    // Clean phone numbers (remove Excel formatting)
    $number = preg_replace('/[^0-9+]/', '', trim($row['number'] ?? ''));
    $inumber = preg_replace('/[^0-9+]/', '', trim($row['inumber'] ?? ''));
    $telmobile = preg_replace('/[^0-9+]/', '', trim($row['telmobile'] ?? ''));
    $telother = preg_replace('/[^0-9+]/', '', trim($row['telother'] ?? ''));
    
    $email = trim($row['email'] ?? '');
    $google_maps_url = trim($row['google_maps_url'] ?? '');
    $business = trim($row['business'] ?? '');
    
    // Address fields
    $city = trim($row['city'] ?? '');
    $street = trim($row['street'] ?? '');
    $floor = trim($row['floor'] ?? '0');
    $apartment = trim($row['apartment'] ?? '');
    $building = trim($row['building'] ?? '');
    $zone = trim($row['zone'] ?? '');
    $near = trim($row['near'] ?? '');
    $remark = trim($row['remark'] ?? '');
    $address = trim($row['address'] ?? '');
    $address_two = trim($row['address_two'] ?? '');
    $best_delivery_time = trim($row['best_delivery_time'] ?? '');
    
    // Default values for idf and idx
    $idf = 1;
    $idx = 1;
    
    // Check for duplicate phone number
    if (!empty($number)) {
        $checkSql = "SELECT id FROM client WHERE number=? OR inumber=? OR telmobile=? OR telother=? LIMIT 1";
        $checkStmt = mysqli_prepare($conn, $checkSql);
        
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "ssss", $number, $number, $number, $number);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                $errorCount++;
                $errors[] = "Row $rowNum: Duplicate phone number ($number)";
                mysqli_stmt_close($checkStmt);
                continue;
            }
            mysqli_stmt_close($checkStmt);
        }
    }
    
    // Insert the record - all 34 columns
    $sql = "INSERT INTO client (
        nom, prenom, category, source, grade, payment, card, community, company, job,
        decision_authority, communication_style, customer_priority, key_preferences,
        number, inumber, telmobile, telother, email, google_maps_url, business,
        city, street, floor, apartment, building, zone, near, remark,
        address, address_two, best_delivery_time, idf, idx
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        $errorCount++;
        $errors[] = "Row $rowNum: Database prepare error - " . mysqli_error($conn);
        continue;
    }
    
    // Bind all 34 parameters
    // Type string breakdown:
    // - 23 strings before floor: sssssssssssssssssssssss
    // - 1 string for floor: s (keeping as string for safety)
    // - 8 strings after floor: ssssssss
    // - 2 integers for idf and idx: ii
    // Total: 32 's' + 2 'i' = 34 parameters
    mysqli_stmt_bind_param($stmt, "ssssssssssssssssssssssssssssssssii",
        $nom, $prenom, $category, $source, $grade, $payment, $card, $community, $company, $job,
        $decision_authority, $communication_style, $customer_priority, $key_preferences,
        $number, $inumber, $telmobile, $telother, $email, $google_maps_url, $business,
        $city, $street, $floor, $apartment, $building, $zone, $near, $remark,
        $address, $address_two, $best_delivery_time, $idf, $idx
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $successCount++;
    } else {
        $errorCount++;
        $errors[] = "Row $rowNum: Insert failed - " . mysqli_stmt_error($stmt);
    }
    
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);

// Return result
if ($successCount > 0) {
    $message = "Successfully imported $successCount record(s)";
    if ($errorCount > 0) {
        $message .= " ($errorCount failed)";
    }
    
    echo json_encode([
        'success' => true,
        'count' => $successCount,
        'message' => $message,
        'errors' => $errorCount > 0 ? array_slice($errors, 0, 5) : []
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Import failed: ' . ($errorCount > 0 ? implode(', ', array_slice($errors, 0, 3)) : 'No valid records found'),
        'errors' => array_slice($errors, 0, 10)
    ]);
}
?>