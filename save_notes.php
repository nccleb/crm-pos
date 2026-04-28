<?php
// save_notes.php
header('Content-Type: application/json');

// Database connection
$servername = "192.168.1.101";
$username = "root";
$password = "1Sys9Admeen72";
$dbname = "nccleb_test";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get the action
$action = $_POST['action'] ?? '';

if ($action === 'save_notes') {
    $clientId = $_POST['client_id'] ?? '';
    $agent = $_POST['agent'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($clientId)) {
        echo json_encode(['success' => false, 'message' => 'Client ID is required']);
        exit;
    }
    
    // Check if notes already exist for this client
    $checkSql = "SELECT id FROM client_notes WHERE client_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $clientId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // Update existing notes
        $updateSql = "UPDATE client_notes SET notes = ?, agent = ?, updated_at = NOW() WHERE client_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sss", $notes, $agent, $clientId);
        
        if ($updateStmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating notes: ' . $conn->error]);
        }
        
        $updateStmt->close();
    } else {
        // Insert new notes
        $insertSql = "INSERT INTO client_notes (client_id, notes, agent, created_at, updated_at) 
                      VALUES (?, ?, ?, NOW(), NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("sss", $clientId, $notes, $agent);
        
        if ($insertStmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error saving notes: ' . $conn->error]);
        }
        
        $insertStmt->close();
    }
    
    $checkStmt->close();
} elseif ($action === 'load_notes') {
    $clientId = $_POST['client_id'] ?? '';
    
    if (empty($clientId)) {
        echo json_encode(['success' => false, 'message' => 'Client ID is required']);
        exit;
    }
    
    $sql = "SELECT notes FROM client_notes WHERE client_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $clientId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'notes' => $row['notes']]);
    } else {
        echo json_encode(['success' => true, 'notes' => '']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>