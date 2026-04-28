<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = "192.168.1.101";
$user = "root";
$pass = "1Sys9Admeen72";
$db   = "nccleb_test";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}

$conn->set_charset("utf8mb4"); // Important for accented characters

// Get search term
$term = $_GET['q'] ?? '';
$term = trim($term);

if (strlen($term) < 2) {
    // Too short, return empty array
    echo json_encode([]);
    exit();
}

// Use prepared statement to prevent SQL injection
$likeTerm = "%$term%";
$stmt = $conn->prepare("
    SELECT id, nom, prenom, number, company 
    FROM client 
    WHERE nom LIKE ? 
       OR prenom LIKE ? 
       OR number LIKE ?
    ORDER BY nom ASC
    LIMIT 20
");
$stmt->bind_param("sss", $likeTerm, $likeTerm, $likeTerm);
$stmt->execute();
$result = $stmt->get_result();

$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = [
        'id'      => $row['id'],
        'name'    => $row['nom'] . ' ' . $row['prenom'],
        'number'  => $row['number'],
        'company' => $row['company']
    ];
}

// Return JSON
echo json_encode($clients);
$stmt->close();
$conn->close();
