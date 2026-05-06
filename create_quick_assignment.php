<?php
// create_quick_assignment.php - API for creating quick assignments
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["oop"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$client_id = $input['client_id'] ?? null;
$dispatcher_id = $input['dispatcher_id'] ?? null;
$delivery_address = $input['delivery_address'] ?? null;
$status = $input['status'] ?? 'assigned';

if (!$dispatcher_id || !$delivery_address) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: dispatcher_id and delivery_address']);
    exit();
}

try {
    // Create the assignment
    $stmt = $idr->prepare("INSERT INTO dispatch_assignments 
        (client_id, dispatcher_id, delivery_address, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, NOW(), NOW())");
    
    $stmt->bind_param("iiss", $client_id, $dispatcher_id, $delivery_address, $status);
    
    if ($stmt->execute()) {
        $assignment_id = $idr->insert_id;
        
        // FIXED: Update driver status to show current assignment AND set as busy
        $update_stmt = $idr->prepare("UPDATE driver_status 
            SET current_assignment_id = ?, status = 'busy' 
            WHERE driver_id = ?");
        $update_stmt->bind_param("ii", $assignment_id, $dispatcher_id);
        $update_stmt->execute();
        
        // Get assignment details for response
        $details_stmt = $idr->prepare("SELECT 
            da.id,
            da.delivery_address,
            da.status,
            da.created_at,
            d.name_d as driver_name,
            d.num_d as driver_phone,
            CONCAT(c.nom, ' ', c.prenom) as client_name
            FROM dispatch_assignments da
            LEFT JOIN drivers d ON da.dispatcher_id = d.idx
            LEFT JOIN client c ON da.client_id = c.id
            WHERE da.id = ?");
        
        $details_stmt->bind_param("i", $assignment_id);
        $details_stmt->execute();
        $result = $details_stmt->get_result();
        $assignment = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'assignment_id' => $assignment_id,
            'assignment' => $assignment,
            'message' => 'Assignment created successfully - Driver status updated to busy'
        ]);
        
        $stmt->close();
        $update_stmt->close();
        $details_stmt->close();
        
    } else {
        throw new Exception('Failed to create assignment: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

mysqli_close($idr);
?>