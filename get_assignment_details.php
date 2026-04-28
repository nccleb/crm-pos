
<?php
session_start();



// Database connection
$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");

if (mysqli_connect_errno()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => mysqli_connect_error()]);
    exit();
}

// Get assignment ID from GET parameter
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

if ($assignment_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid assignment ID']);
    exit();
}

// Get assignment details
$stmt = $idr->prepare("SELECT da.delivery_address, da.delivery_instructions, da.client_id 
                       FROM dispatch_assignments da WHERE da.id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();
$stmt->close();

if (!$assignment) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Assignment not found']);
    exit();
}

// Get client address
$client_stmt = $idr->prepare("SELECT city, zone, street, building, apartment, floor, near, address 
                             FROM client WHERE id = ?");
$client_stmt->bind_param("i", $assignment['client_id']);
$client_stmt->execute();
$client_result = $client_stmt->get_result(); 
$client_data = $client_result->fetch_assoc();
$client_stmt->close();

// Build address
$client_address = "";
if (isset($client_data['city']) && $client_data['city']) {
    $client_address .= $client_data['city'] . ", ";
}
if (isset($client_data['zone']) && $client_data['zone']) {
    $client_address .= "Zone " . $client_data['zone'] . ", ";
}
if (isset($client_data['street']) && $client_data['street']) {
    $client_address .= "Street " . $client_data['street'] . ", ";
}
if (isset($client_data['building']) && $client_data['building']) {
    $client_address .= "Building " . $client_data['building'] . ", ";
}
if (isset($client_data['apartment']) && $client_data['apartment']) {
    $client_address .= "Apartment " . $client_data['apartment'] . ", ";
}
if (isset($client_data['floor']) && $client_data['floor']) {
    $client_address .= "Floor " . $client_data['floor'] . ", ";
}
if (isset($client_data['near']) && $client_data['near']) {
    $client_address .= "Near " . $client_data['near'] . ", ";
}
if (isset($client_data['address']) && $client_data['address']) {
    $client_address .= $client_data['address'];
}

// Trim trailing comma and space
$client_address = rtrim($client_address, ", ");

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'client_address' => $client_address,
    'order_details' => $assignment['delivery_address'] ?? '',
    'delivery_instructions' => $assignment['delivery_instructions'] ?? ''
]);

mysqli_close($idr);
?>
