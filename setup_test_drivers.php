<?php
// Setup test drivers and verify location tracking system
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    die("Database connection FAILED: " . mysqli_connect_error());
}

echo "=== SETTING UP TEST DRIVERS ===\n\n";

// 1. Check existing drivers
echo "1. CHECKING EXISTING DRIVERS:\n";
echo "----------------------------\n";
$result = mysqli_query($idr, "SELECT idx, name_d, num_d FROM drivers ORDER BY idx");
if ($result) {
    $existing_count = mysqli_num_rows($result);
    echo "Found $existing_count existing drivers:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "  ID {$row['idx']}: {$row['name_d']} - {$row['num_d']}\n";
    }
} else {
    echo "Error checking drivers: " . mysqli_error($idr) . "\n";
}

echo "\n2. ADDING TEST DRIVERS (if needed):\n";
echo "----------------------------------\n";

// Test drivers to create
$test_drivers = [
    ['name' => 'Ahmed Hassan', 'phone' => '+961-1-234567'],
    ['name' => 'Omar Khalil', 'phone' => '+961-3-345678'],
    ['name' => 'Samir Fares', 'phone' => '+961-7-456789']
];

$added_drivers = [];

foreach ($test_drivers as $driver) {
    // Check if driver already exists
    $check_stmt = $idr->prepare("SELECT idx FROM drivers WHERE name_d = ? OR num_d = ?");
    $check_stmt->bind_param("ss", $driver['name'], $driver['phone']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existing = $result->fetch_assoc();
        echo "Driver '{$driver['name']}' already exists with ID {$existing['idx']}\n";
        $added_drivers[] = $existing['idx'];
    } else {
        // Add new driver
        $insert_stmt = $idr->prepare("INSERT INTO drivers (name_d, num_d, email) VALUES (?, ?, ?)");
        $email = strtolower(str_replace(' ', '.', $driver['name'])) . "@example.com";
        $insert_stmt->bind_param("sss", $driver['name'], $driver['phone'], $email);
        
        if ($insert_stmt->execute()) {
            $new_id = mysqli_insert_id($idr);
            echo "Added driver '{$driver['name']}' with ID $new_id\n";
            $added_drivers[] = $new_id;
        } else {
            echo "Failed to add driver '{$driver['name']}': " . $insert_stmt->error . "\n";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

echo "\n3. TESTING LOCATION UPDATES WITH REAL DRIVERS:\n";
echo "---------------------------------------------\n";

// Test with first available driver
if (!empty($added_drivers)) {
    $test_driver_id = $added_drivers[0];
    echo "Testing with driver ID: $test_driver_id\n";
    
    // Test location data around Beirut area
    $test_locations = [
        ['lat' => 33.8938, 'lng' => 35.5018, 'status' => 'online'],   // Beirut center
        ['lat' => 33.8869, 'lng' => 35.5131, 'status' => 'busy'],     // Hamra
        ['lat' => 33.8959, 'lng' => 35.4851, 'status' => 'available'] // Raouche
    ];
    
    foreach ($test_locations as $i => $location) {
        echo "\nTest location " . ($i + 1) . ":\n";
        
        // Insert into driver_locations
        $stmt1 = $idr->prepare("INSERT INTO driver_locations 
            (driver_id, latitude, longitude, status, timestamp, accuracy, speed) 
            VALUES (?, ?, ?, ?, NOW(), ?, ?)");
        $accuracy = 10.0;
        $speed = rand(0, 50) / 10.0; // Random speed 0-5 m/s
        $stmt1->bind_param("iddsdd", 
            $test_driver_id, $location['lat'], $location['lng'], 
            $location['status'], $accuracy, $speed);
            
        if ($stmt1->execute()) {
            echo "  ✓ Location history inserted\n";
        } else {
            echo "  ✗ Location history failed: " . $stmt1->error . "\n";
        }
        $stmt1->close();
        
        // Update driver_status
        $stmt2 = $idr->prepare("INSERT INTO driver_status 
            (driver_id, current_latitude, current_longitude, status, last_update) 
            VALUES (?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            current_latitude = VALUES(current_latitude), 
            current_longitude = VALUES(current_longitude), 
            status = VALUES(status), 
            last_update = NOW()");
            
        $stmt2->bind_param("idds", 
            $test_driver_id, $location['lat'], $location['lng'], $location['status']);
            
        if ($stmt2->execute()) {
            echo "  ✓ Current status updated\n";
        } else {
            echo "  ✗ Status update failed: " . $stmt2->error . "\n";
        }
        $stmt2->close();
        
        // Wait a moment between updates
        sleep(1);
    }
}

echo "\n4. TESTING THE GET_ALL_LOCATIONS QUERY:\n";
echo "--------------------------------------\n";

$query = "SELECT 
    ds.driver_id,
    d.name_d as driver_name,
    d.num_d as driver_phone,
    ds.current_latitude,
    ds.current_longitude,
    ds.status,
    ds.last_update,
    TIMESTAMPDIFF(MINUTE, ds.last_update, NOW()) as minutes_ago
FROM driver_status ds
LEFT JOIN drivers d ON ds.driver_id = d.idx
WHERE ds.current_latitude IS NOT NULL 
AND ds.current_longitude IS NOT NULL
AND ds.last_update > DATE_SUB(NOW(), INTERVAL 60 MINUTE)
ORDER BY ds.last_update DESC";

$result = mysqli_query($idr, $query);
if ($result) {
    $count = mysqli_num_rows($result);
    echo "Found $count active drivers with locations:\n";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo sprintf("  Driver %d: %-15s | %s | %.6f, %.6f | %-9s | %d min ago\n",
            $row['driver_id'],
            $row['driver_name'],
            $row['driver_phone'],
            $row['current_latitude'],
            $row['current_longitude'],
            $row['status'],
            $row['minutes_ago']
        );
    }
} else {
    echo "Query failed: " . mysqli_error($idr) . "\n";
}

echo "\n5. TESTING API ENDPOINT SIMULATION:\n";
echo "----------------------------------\n";

// Simulate what the mobile app would send
if (!empty($added_drivers)) {
    $api_test_data = [
        'driver_id' => $added_drivers[0],
        'latitude' => 33.8938 + (rand(-100, 100) / 10000), // Small random offset
        'longitude' => 35.5018 + (rand(-100, 100) / 10000),
        'accuracy' => 15.5,
        'heading' => 180.0,
        'speed' => 2.5,
        'status' => 'online',
        'battery_level' => 85
    ];
    
    echo "Simulating mobile app POST request:\n";
    echo json_encode($api_test_data, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test the full insert with all fields
    $full_stmt = $idr->prepare("INSERT INTO driver_locations 
        (driver_id, latitude, longitude, accuracy, heading, speed, status, battery_level, timestamp) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
    $full_stmt->bind_param("iddddisi", 
        $api_test_data['driver_id'],
        $api_test_data['latitude'],
        $api_test_data['longitude'],
        $api_test_data['accuracy'],
        $api_test_data['heading'],
        $api_test_data['speed'],
        $api_test_data['status'],
        $api_test_data['battery_level']
    );
    
    if ($full_stmt->execute()) {
        echo "✓ Full API simulation insert successful\n";
    } else {
        echo "✗ Full API simulation failed: " . $full_stmt->error . "\n";
    }
    $full_stmt->close();
}

echo "\n6. GENERATING MOBILE APP URLS:\n";
echo "-----------------------------\n";

if (!empty($added_drivers)) {
    $result = mysqli_query($idr, "SELECT idx, name_d FROM drivers WHERE idx IN (" . implode(',', $added_drivers) . ")");
    echo "Use these URLs to test the mobile app:\n\n";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $driver_name = urlencode($row['name_d']);
        $url = "driver_mobile.php?driver_id={$row['idx']}&driver_name=$driver_name";
        echo "Driver {$row['idx']} ({$row['name_d']}):\n";
        echo "  $url\n\n";
    }
}

echo "7. NEXT STEPS:\n";
echo "-------------\n";
echo "1. Use the mobile app URLs above to test location tracking\n";
echo "2. Open live_tracking.php to see the drivers on the map\n";
echo "3. Make sure drivers grant location permission in their browsers\n";
echo "4. Check that the tracking interface shows the test locations\n";

mysqli_close($idr);
echo "\n=== SETUP COMPLETE ===\n";
?>