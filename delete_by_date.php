<?php
session_start();
$conn = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

// Check if dates are provided
if (isset($_POST['from']) && isset($_POST['to'])) {
    $from = $_POST['from'];
    $to = $_POST['to'];

    // Delete the call history records within the date range
    $sql = "DELETE FROM call_history WHERE call_time BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $from, $to);
    
    if ($stmt->execute()) {
        echo "Calls deleted successfully.";
    } else {
        echo "Error deleting calls: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    echo "Invalid request.";
}

$conn->close();
?>
