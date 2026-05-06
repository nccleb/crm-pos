<?php
// delete_call.php
session_start();

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION["ses"])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$conn = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$action = $_POST['action'] ?? 'delete_single';

switch ($action) {
    case 'delete_single':
        deleteSingleCall($conn);
        break;
    
    case 'preview':
        previewDeleteByDate($conn);
        break;
    
    case 'delete_by_date':
        deleteByDateRange($conn);
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

function deleteSingleCall($conn) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM call_history WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Call deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete call: ' . $conn->error
        ]);
    }
    
    $stmt->close();
}

function previewDeleteByDate($conn) {
    $from = $_POST['from'] ?? '';
    $to = $_POST['to'] ?? '';
    
    if (empty($from) || empty($to)) {
        echo json_encode(['success' => false, 'error' => 'Date range required']);
        return;
    }
    
    // Validate date format
    $from_date = date('Y-m-d H:i:s', strtotime($from));
    $to_date = date('Y-m-d H:i:s', strtotime($to));
    
    if (!$from_date || !$to_date) {
        echo json_encode(['success' => false, 'error' => 'Invalid date format']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM call_history WHERE call_time BETWEEN ? AND ?");
    $stmt->bind_param("ss", $from_date, $to_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'count' => $row['count']
    ]);
    
    $stmt->close();
}

function deleteByDateRange($conn) {
    $from = $_POST['from'] ?? '';
    $to = $_POST['to'] ?? '';
    
    if (empty($from) || empty($to)) {
        echo json_encode(['success' => false, 'error' => 'Date range required']);
        return;
    }
    
    // Validate date format
    $from_date = date('Y-m-d H:i:s', strtotime($from));
    $to_date = date('Y-m-d H:i:s', strtotime($to));
    
    if (!$from_date || !$to_date) {
        echo json_encode(['success' => false, 'error' => 'Invalid date format']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM call_history WHERE call_time BETWEEN ? AND ?");
    $stmt->bind_param("ss", $from_date, $to_date);
    
    if ($stmt->execute()) {
        $deleted = $stmt->affected_rows;
        echo json_encode([
            'success' => true,
            'deleted' => $deleted,
            'message' => "$deleted call(s) deleted successfully"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete calls: ' . $conn->error
        ]);
    }
    
    $stmt->close();
}

mysqli_close($conn);
?>