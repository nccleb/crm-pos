<?php
// update_handler.php - With phone number validation
header('Content-Type: application/json');

// Database connection
$host = "172.18.208.1";
$user = "root";
$pass = "1Sys9Admeen72";
$db = "nccleb_test";

$conn = mysqli_connect($host, $user, $pass, $db);

if (mysqli_connect_errno()) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit();
}

mysqli_set_charset($conn, "utf8mb4");

$action = $_POST['action'] ?? '';

// UPDATE ACTION
if ($action === 'update') {
    $data = json_decode($_POST['data'], true);
    
    if (!$data || !isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    $id = (int)$data['id'];
    
    // Extract phone numbers
    $number = trim($data['number'] ?? '');
    $inumber = trim($data['inumber'] ?? '');
    $telmobile = trim($data['telmobile'] ?? '');
    $telother = trim($data['telother'] ?? '');
    
    // Collect all non-empty phone numbers
    $phoneNumbers = array_filter([
        $number,
        $inumber,
        $telmobile,
        $telother
    ], function($phone) {
        return !empty($phone);
    });
    
    // Check for duplicates in other clients (excluding current client)
    if (!empty($phoneNumbers)) {
        $placeholders = implode(',', array_fill(0, count($phoneNumbers), '?'));
        
        $checkSql = "SELECT id, number, inumber, telmobile, telother, 
                     CONCAT(nom, ' ', prenom) as client_name
                     FROM client 
                     WHERE id != ? 
                     AND (number IN ($placeholders) 
                          OR inumber IN ($placeholders) 
                          OR telmobile IN ($placeholders) 
                          OR telother IN ($placeholders))
                     LIMIT 1";
        
        $checkStmt = mysqli_prepare($conn, $checkSql);
        
        // Build bind parameters: id + phone numbers repeated 4 times
        $types = 'i' . str_repeat('s', count($phoneNumbers) * 4);
        $params = array_merge([$id], $phoneNumbers, $phoneNumbers, $phoneNumbers, $phoneNumbers);
        
        mysqli_stmt_bind_param($checkStmt, $types, ...$params);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            $duplicate = mysqli_fetch_assoc($checkResult);
            
            // Find which number is duplicate
            $duplicateNumber = '';
            foreach ([$duplicate['number'], $duplicate['inumber'], $duplicate['telmobile'], $duplicate['telother']] as $existingPhone) {
                if (in_array($existingPhone, $phoneNumbers)) {
                    $duplicateNumber = $existingPhone;
                    break;
                }
            }
            
            mysqli_stmt_close($checkStmt);
            mysqli_close($conn);
            
            echo json_encode([
                'success' => false, 
                'message' => "Phone number '$duplicateNumber' already exists for client: " . $duplicate['client_name'] . " (ID: " . $duplicate['id'] . ")",
                'duplicate' => true
            ]);
            exit();
        }
        
        mysqli_stmt_close($checkStmt);
    }
    
    // Proceed with update if no duplicates found
    $updateSql = "UPDATE client SET 
        nom = ?,
        prenom = ?,
        category = ?,
        source = ?,
        grade = ?,
        payment = ?,
        card = ?,
        community = ?,
        company = ?,
        job = ?,
        decision_authority = ?,
        communication_style = ?,
        customer_priority = ?,
        key_preferences = ?,
        number = ?,
        inumber = ?,
        telmobile = ?,
        telother = ?,
        email = ?,
        google_maps_url = ?,
        business = ?,
        city = ?,
        street = ?,
        floor = ?,
        apartment = ?,
        building = ?,
        zone = ?,
        near = ?,
        remark = ?,
        address = ?,
        address_two = ?,
        best_delivery_time = ?
        WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $updateSql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . mysqli_error($conn)]);
        exit();
    }
    
    // Bind all parameters
    mysqli_stmt_bind_param($stmt, "ssssssssssssssssssssssssssssssssi",
        $data['nom'],
        $data['prenom'],
        $data['category'],
        $data['source'],
        $data['grade'],
        $data['payment'],
        $data['card'],
        $data['community'],
        $data['company'],
        $data['job'],
        $data['decision_authority'],
        $data['communication_style'],
        $data['customer_priority'],
        $data['key_preferences'],
        $data['number'],
        $data['inumber'],
        $data['telmobile'],
        $data['telother'],
        $data['email'],
        $data['google_maps_url'],
        $data['business'],
        $data['city'],
        $data['street'],
        $data['floor'],
        $data['apartment'],
        $data['building'],
        $data['zone'],
        $data['near'],
        $data['remark'],
        $data['address'],
        $data['address_two'],
        $data['best_delivery_time'],
        $id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Client updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . mysqli_stmt_error($stmt)]);
    }
    
    mysqli_stmt_close($stmt);
}

// DELETE ACTION
elseif ($action === 'delete') {
    $id = (int)$_POST['id'];
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit();
    }
    
    $deleteSql = "DELETE FROM client WHERE id = ?";
    $stmt = mysqli_prepare($conn, $deleteSql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Client deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed: ' . mysqli_stmt_error($stmt)]);
    }
    
    mysqli_stmt_close($stmt);
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

mysqli_close($conn);
?>