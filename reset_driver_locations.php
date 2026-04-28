<?php
header('Content-Type: application/json');

// Database connection
$conn = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (!$conn) {
    die(json_encode(['error' => 'Connection failed: ' . mysqli_connect_error()]));
}

// Get driver_id from query parameter
$driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : null;

if ($driver_id) {
    // Reset coordinates for a specific driver
    $query = "UPDATE driver_status 
              SET current_latitude = NULL, current_longitude = NULL 
              WHERE driver_id = $driver_id";
} else {
    // Reset coordinates for all drivers with the problematic fixed values
    $query = "UPDATE driver_status 
              SET current_latitude = NULL, current_longitude = NULL 
              WHERE ABS(current_latitude - 33.88211200) < 0.000001 
              AND ABS(current_longitude - 35.52051200) < 0.000001";
}

$result = mysqli_query($conn, $query);
if (!$result) {
    die(json_encode(['error' => 'Reset failed: ' . mysqli_error($conn)]));
}

$affected = mysqli_affected_rows($conn);
echo json_encode([
    'success' => true,
    'message' => "Reset $affected driver(s) coordinates",
    'reset_count' => $affected
], JSON_PRETTY_PRINT);

mysqli_close($conn);
?>