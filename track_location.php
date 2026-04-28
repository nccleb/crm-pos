<?php
// track_location.php - Handle driver location updates and retrieval

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$idr = mysqli_connect("localhost", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit();
}

// Set charset
mysqli_set_charset($idr, 'utf8');

// CONFIGURATION: Offline timeout settings
const OFFLINE_TIMEOUT_MINUTES = 20; // Changed from 10 to 30 minutes
const SINGLE_DRIVER_OFFLINE_TIMEOUT_MINUTES = 20; // Changed from 5 to 20 minutes

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Route requests
try {
    switch($action) {
        case 'update_location':
            updateDriverLocation();
            break;
        case 'get_all_locations':
            getAllDriverLocations();
            break;
        case 'get_driver_location':
            getDriverLocation();
            break;
        case 'heartbeat':
            handleHeartbeat();
            break;
        case 'check_status':
            checkDriverStatus();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Error in track_location.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

// --- FUNCTIONS ---

function updateDriverLocation() {
    global $idr;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }
    
    $driver_id = $input['driver_id'] ?? null;
    $latitude = $input['latitude'] ?? null;
    $longitude = $input['longitude'] ?? null;
    $accuracy = $input['accuracy'] ?? null;
    $heading = $input['heading'] ?? null;
    $speed = $input['speed'] ?? null;
    $status = $input['status'] ?? 'online';
    $battery_level = $input['battery_level'] ?? null;
    
    if (!$driver_id || !$latitude || !$longitude) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: driver_id, latitude, longitude']);
        return;
    }
    
    // Validate coordinates
    if (!is_numeric($latitude) || !is_numeric($longitude)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid latitude or longitude']);
        return;
    }
    
    // Check for recent duplicates
    $stmt_check = mysqli_prepare($idr, "SELECT id FROM driver_locations 
        WHERE driver_id = ? 
        AND ABS(latitude - ?) < 0.0001 
        AND ABS(longitude - ?) < 0.0001 
        AND timestamp > DATE_SUB(NOW(), INTERVAL 30 SECOND)");
    
    if (!$stmt_check) {
        http_response_code(500);
        echo json_encode(['error' => 'Database prepare error: ' . mysqli_error($idr)]);
        return;
    }
    
    mysqli_stmt_bind_param($stmt_check, "idd", $driver_id, $latitude, $longitude);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        // Similar location within 30 seconds - just update status table
        $update_stmt = mysqli_prepare($idr, "INSERT INTO driver_status 
            (driver_id, current_latitude, current_longitude, status, last_update) 
            VALUES (?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            current_latitude = VALUES(current_latitude), 
            current_longitude = VALUES(current_longitude), 
            status = VALUES(status), 
            last_update = NOW()");
            
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "idds", $driver_id, $latitude, $longitude, $status);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }
        
        echo json_encode(['success' => true, 'message' => 'Status updated (duplicate location skipped)']);
        mysqli_stmt_close($stmt_check);
        return;
    }
    mysqli_stmt_close($stmt_check);
    
    // Insert new location record
    $stmt = mysqli_prepare($idr, "INSERT INTO driver_locations 
        (driver_id, latitude, longitude, accuracy, heading, speed, status, battery_level) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Database prepare error: ' . mysqli_error($idr)]);
        return;
    }
    
    mysqli_stmt_bind_param($stmt, "iddddisi", 
        $driver_id, $latitude, $longitude, $accuracy, $heading, $speed, $status, $battery_level);
    
    if (mysqli_stmt_execute($stmt)) {
        // Update current status
        $update_stmt = mysqli_prepare($idr, "INSERT INTO driver_status 
            (driver_id, current_latitude, current_longitude, status, last_update) 
            VALUES (?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            current_latitude = VALUES(current_latitude), 
            current_longitude = VALUES(current_longitude), 
            status = VALUES(status), 
            last_update = NOW()");
            
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "idds", $driver_id, $latitude, $longitude, $status);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }
        
        echo json_encode(['success' => true, 'message' => 'Location updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update location: ' . mysqli_error($idr)]);
    }
    
    mysqli_stmt_close($stmt);
}

function getAllDriverLocations() {
    global $idr;

    $timeout_minutes = OFFLINE_TIMEOUT_MINUTES;
    
    $query = "SELECT 
        ds.driver_id,
        d.name_d as driver_name,
        d.num_d as driver_phone,
        ds.current_latitude,
        ds.current_longitude,
        CASE 
            WHEN ds.last_update < DATE_SUB(NOW(), INTERVAL $timeout_minutes MINUTE) THEN 'offline'
            WHEN ds.current_assignment_id IS NOT NULL THEN 'busy'
            ELSE ds.status
        END AS status,
        ds.last_update,
        ds.current_assignment_id,
        da.delivery_address as current_delivery
    FROM driver_status ds
    LEFT JOIN drivers d ON ds.driver_id = d.idx
    LEFT JOIN dispatch_assignments da ON ds.current_assignment_id = da.id
    WHERE ds.current_latitude IS NOT NULL 
    AND ds.current_longitude IS NOT NULL
    AND ds.current_latitude != 0
    AND ds.current_longitude != 0
    ORDER BY ds.last_update DESC";
    
    $result = mysqli_query($idr, $query);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query error: ' . mysqli_error($idr), 'drivers' => []]);
        return;
    }
    
    $locations = [];

    while($row = mysqli_fetch_assoc($result)) {
        $locations[] = [
            'driver_id' => $row['driver_id'],
            'driver_name' => $row['driver_name'] ?: 'Driver ' . $row['driver_id'],
            'driver_phone' => $row['driver_phone'] ?: 'N/A',
            'latitude' => floatval($row['current_latitude']),
            'longitude' => floatval($row['current_longitude']),
            'status' => $row['status'],
            'last_update' => $row['last_update'],
            'current_delivery' => $row['current_delivery']
        ];
    }

    echo json_encode([
        'success' => true,
        'drivers' => $locations,
        'total_count' => count($locations),
        'timestamp' => time(),
        'offline_timeout_minutes' => $timeout_minutes
    ]);
}

function getDriverLocation() {
    global $idr;
    
    $driver_id = $_GET['driver_id'] ?? null;
    
    if (!$driver_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Driver ID required']);
        return;
    }
    
    $timeout_minutes = SINGLE_DRIVER_OFFLINE_TIMEOUT_MINUTES;
    
    $stmt = mysqli_prepare($idr, "SELECT 
        ds.driver_id,
        d.name_d as driver_name,
        ds.current_latitude,
        ds.current_longitude,
        CASE 
            WHEN ds.last_update < DATE_SUB(NOW(), INTERVAL ? MINUTE) THEN 'offline'
            WHEN ds.current_assignment_id IS NOT NULL THEN 'busy'
            ELSE ds.status
        END AS status,
        ds.last_update,
        ds.current_assignment_id
    FROM driver_status ds
    LEFT JOIN drivers d ON ds.driver_id = d.idx
    WHERE ds.driver_id = ? AND ds.current_latitude IS NOT NULL");
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Database prepare error: ' . mysqli_error($idr)]);
        return;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $timeout_minutes, $driver_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode([
            'success' => true,
            'driver_id' => $row['driver_id'],
            'driver_name' => $row['driver_name'],
            'latitude' => floatval($row['current_latitude']),
            'longitude' => floatval($row['current_longitude']),
            'status' => $row['status'],
            'last_update' => $row['last_update'],
            'current_assignment_id' => $row['current_assignment_id']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Driver location not found']);
    }
    
    mysqli_stmt_close($stmt);
}

function handleHeartbeat() {
    global $idr;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        return;
    }
    
    $driver_id = $input['driver_id'] ?? null;
    
    if (!$driver_id) {
        echo json_encode(['success' => false, 'error' => 'Driver ID required']);
        return;
    }
    
    try {
        // Update last heartbeat in driver_status table
        $stmt = mysqli_prepare($idr, "UPDATE driver_status SET last_update = NOW() WHERE driver_id = ?");
        
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Database prepare error']);
            return;
        }
        
        mysqli_stmt_bind_param($stmt, "i", $driver_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo json_encode([
            'success' => true, 
            'timestamp' => time(),
            'message' => 'Heartbeat received'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function checkDriverStatus() {
    global $idr;
    
    try {
        $timeout_minutes = OFFLINE_TIMEOUT_MINUTES;
        
        // Mark drivers as offline if no update for more than the configured timeout
        $query = "UPDATE driver_status 
                  SET status = 'offline' 
                  WHERE last_update < DATE_SUB(NOW(), INTERVAL $timeout_minutes MINUTE) 
                  AND status != 'offline'";
        
        $result = mysqli_query($idr, $query);
        
        if (!$result) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($idr)]);
            return;
        }
        
        $affected = mysqli_affected_rows($idr);
        
        echo json_encode([
            'success' => true, 
            'message' => "Marked $affected drivers as offline (timeout: $timeout_minutes minutes)",
            'timeout_minutes' => $timeout_minutes
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// Close database connection
mysqli_close($idr);
?>