<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

header('Content-Type: application/json');

// Database connection
$host = "172.18.208.1";
$user = "root";
$pass = "1Sys9Admeen72";
$db = "nccleb_test";
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Check if ticket ID is provided
if (!isset($_POST['idc']) || empty($_POST['idc'])) {
    echo json_encode(['success' => false, 'error' => 'Ticket ID is required']);
    exit;
}

$ticket_id = mysqli_real_escape_string($conn, $_POST['idc']);

// Delete the ticket
$sql = "DELETE FROM crm WHERE idc = '$ticket_id'";

if (mysqli_query($conn, $sql)) {
    if (mysqli_affected_rows($conn) > 0) {
        echo json_encode(['success' => true, 'message' => 'Ticket deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>