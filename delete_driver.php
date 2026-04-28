<?php
session_start();

// Check if user is logged in and has proper permissions
if (!isset($_SESSION["oop"]) || empty($_SESSION["oop"])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Optional: Add role-based access control
$user_role = $_SESSION["oop"];
// You might want to restrict this to admin users only
// if ($user_role !== 'user' && $user_role !== 'admin') {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
//     exit();
// }

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get driver ID from POST data
$driver_id = $_POST['driver_id'] ?? '';

if (empty($driver_id)) {
    echo json_encode(['success' => false, 'message' => 'Driver ID is required']);
    exit();
}

// Database connection
$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit();
}

// Start transaction for data integrity
mysqli_begin_transaction($idr);

try {
    // First, check if the driver exists
    $check_stmt = $idr->prepare("SELECT idx, name_d FROM drivers WHERE idx = ? OR name_d = ?");
    $check_stmt->bind_param("is", $driver_id, $driver_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Driver not found with ID or name: " . $driver_id);
    }
    
    $driver_data = $result->fetch_assoc();
    $actual_driver_id = $driver_data['idx'];
    $driver_name = $driver_data['name_d'];
    $check_stmt->close();
    
    // Delete related records first (to maintain referential integrity)
    
    // 1. Delete driver locations
    $delete_locations_stmt = $idr->prepare("DELETE FROM driver_locations WHERE driver_id = ?");
    $delete_locations_stmt->bind_param("i", $actual_driver_id);
    $delete_locations_stmt->execute();
    $locations_deleted = $delete_locations_stmt->affected_rows;
    $delete_locations_stmt->close();
    
    // 2. Delete driver status
    $delete_status_stmt = $idr->prepare("DELETE FROM driver_status WHERE driver_id = ?");
    $delete_status_stmt->bind_param("i", $actual_driver_id);
    $delete_status_stmt->execute();
    $status_deleted = $delete_status_stmt->affected_rows;
    $delete_status_stmt->close();
    
    // 3. Update dispatch assignments (set driver to NULL instead of deleting)
    $update_assignments_stmt = $idr->prepare("UPDATE dispatch_assignments SET dispatcher_id = NULL WHERE dispatcher_id = ?");
    $update_assignments_stmt->bind_param("i", $actual_driver_id);
    $update_assignments_stmt->execute();
    $assignments_updated = $update_assignments_stmt->affected_rows;
    $update_assignments_stmt->close();
    
    // 4. Handle vente table (sales/delivery records)
    $update_vente_stmt = $idr->prepare("UPDATE vente SET idx = NULL WHERE idx = ?");
    $update_vente_stmt->bind_param("i", $actual_driver_id);
    $update_vente_stmt->execute();
    $vente_updated = $update_vente_stmt->affected_rows;
    $update_vente_stmt->close();
    
    // 5. Handle calendar table
    $update_calendar_stmt = $idr->prepare("UPDATE calendar SET dr = NULL WHERE dr = ?");
    $update_calendar_stmt->bind_param("i", $actual_driver_id);
    $update_calendar_stmt->execute();
    $calendar_updated = $update_calendar_stmt->affected_rows;
    $update_calendar_stmt->close();
    
    // Finally, delete the driver
    $delete_driver_stmt = $idr->prepare("DELETE FROM drivers WHERE idx = ?");
    $delete_driver_stmt->bind_param("i", $actual_driver_id);
    $delete_driver_stmt->execute();
    
    if ($delete_driver_stmt->affected_rows === 0) {
        throw new Exception("Failed to delete driver");
    }
    
    $delete_driver_stmt->close();
    
    // Commit transaction
    mysqli_commit($idr);
    
    // Log the deletion activity (optional)
    $log_message = "Driver deleted: ID={$actual_driver_id}, Name={$driver_name}, By={$user_role}";
    error_log($log_message);
    
    // Prepare response with details
    $response = [
        'success' => true,
        'message' => "Driver '{$driver_name}' has been successfully deleted",
        'details' => [
            'driver_name' => $driver_name,
            'driver_id' => $actual_driver_id,
            'locations_deleted' => $locations_deleted,
            'status_deleted' => $status_deleted,
            'assignments_updated' => $assignments_updated,
            'vente_updated' => $vente_updated,
            'calendar_updated' => $calendar_updated
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($idr);
    
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
    
} finally {
    // Close database connection
    mysqli_close($idr);
}
?>