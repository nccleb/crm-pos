<?php
// delete_all_clients.php
session_start();

// Set header for JSON response
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if action is set
if (!isset($_POST['action']) || $_POST['action'] !== 'delete_all') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Optional: Add authentication check here
// if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit();
// }

// Database connection
$host = "192.168.1.101";
$user = "root";
$pass = "1Sys9Admeen72";
$db = "nccleb_test";

$idr = @mysqli_connect($host, $user, $pass, $db);

if (!$idr) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed: ' . mysqli_connect_error()
    ]);
    exit();
}

// Begin transaction for safety
mysqli_begin_transaction($idr);

try {
    // Disable foreign key checks
    $result1 = mysqli_query($idr, "SET foreign_key_checks = 0");
    
    if (!$result1) {
        throw new Exception("Failed to disable foreign key checks");
    }
    
    // Truncate the client table
    $result2 = mysqli_query($idr, "TRUNCATE TABLE client");
    
    if (!$result2) {
        throw new Exception("Failed to truncate table: " . mysqli_error($idr));
    }
    
    // Re-enable foreign key checks
    $result3 = mysqli_query($idr, "SET foreign_key_checks = 1");
    
    if (!$result3) {
        throw new Exception("Failed to re-enable foreign key checks");
    }
    
    // Reset auto increment
    $result4 = mysqli_query($idr, "ALTER TABLE client AUTO_INCREMENT = 1");
    
    if (!$result4) {
        throw new Exception("Failed to reset auto increment");
    }
    
    // Commit transaction
    mysqli_commit($idr);
    
    // Close connection
    mysqli_close($idr);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'All clients have been successfully deleted'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($idr);
    mysqli_close($idr);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>