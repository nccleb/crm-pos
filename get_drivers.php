<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["oop"]) || empty($_SESSION["oop"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Database connection
$idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit();
}

try {
    // Fetch all drivers
    $stmt = $idr->prepare("SELECT idx, name_d, num_d, email FROM drivers ORDER BY name_d");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $drivers = [];
    while ($row = $result->fetch_assoc()) {
        $drivers[] = [
            'idx' => $row['idx'],
            'name_d' => $row['name_d'],
            'num_d' => $row['num_d'],
            'email' => $row['email']
        ];
    }
    
    $stmt->close();
    mysqli_close($idr);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($drivers);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching drivers: ' . $e->getMessage()]);
}
?>