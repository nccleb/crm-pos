<?php
header('Content-Type: application/json');

// Database connection
$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");

if (mysqli_connect_errno()) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to connect to MySQL: ' . mysqli_connect_error()
    ]);
    exit();
}

// Check if action is delete_all
if ($_POST['action'] === 'delete_all') {
    // Disable foreign key checks
    $req1 = mysqli_query($idr, "SET foreign_key_checks=0");
    
    // Truncate the crm table
    $req2 = mysqli_query($idr, "TRUNCATE TABLE crm");
    
    // Re-enable foreign key checks
    $req3 = mysqli_query($idr, "SET foreign_key_checks=1");
    
    // Reset auto increment
    $req4 = mysqli_query($idr, "ALTER TABLE crm AUTO_INCREMENT=1");
    
    if ($req2 && $req4) {
        echo json_encode([
            'success' => true,
            'message' => 'All complaints have been successfully deleted'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting complaints: ' . mysqli_error($idr)
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}

mysqli_close($idr);
?>