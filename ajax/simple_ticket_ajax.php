<?php
// Simple AJAX handler for tickets
session_start();

$host = "192.168.1.101";
$user = "root";
$pass = "1Sys9Admeen72";
$db = "nccleb_test";

$conn = @mysqli_connect($host, $user, $pass, $db);

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create_ticket') {
    $contact = mysqli_real_escape_string($conn, $_POST['contact'] ?? '');
    $ticket_name = mysqli_real_escape_string($conn, $_POST['ticket_name'] ?? '');
    $last_activity = mysqli_real_escape_string($conn, $_POST['last_activity'] ?? '');
    $complaint = mysqli_real_escape_string($conn, $_POST['complaint'] ?? '');
    $priority = mysqli_real_escape_string($conn, $_POST['priority'] ?? 'Medium');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Not Resolved');
    
    $sql = "INSERT INTO crm (contact, task, la, incident, priority, status, idfc, lcd) 
            VALUES ('$contact', '$ticket_name', '$last_activity', '$complaint', '$priority', '$status', 1, NOW())";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Ticket created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

mysqli_close($conn);
?>