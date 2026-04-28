<?php
// ajax/search_clients.php
session_start();

if (!isset($_SESSION["ses"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$search = mysqli_real_escape_string($idr, $_GET['q'] ?? '');

if (strlen($search) < 2) {
    echo json_encode([]);
    exit();
}

$query = "SELECT id, nom, prenom, number, company 
          FROM client 
          WHERE nom LIKE '%$search%' 
             OR prenom LIKE '%$search%' 
             OR number LIKE '%$search%'
             OR company LIKE '%$search%'
          ORDER BY nom, prenom
          LIMIT 10";

$result = mysqli_query($idr, $query);

$clients = [];
while ($row = mysqli_fetch_assoc($result)) {
    $clients[] = [
        'id' => $row['id'],
        'name' => $row['nom'] . ' ' . $row['prenom'],
        'number' => $row['number'],
        'company' => $row['company'] ?? ''
    ];
}

header('Content-Type: application/json');
echo json_encode($clients);
?>