<?php
session_start();
header('Content-Type: application/json');

// Accept session vars from either test204.php (oop) or test321.php (o/os)
$agent = $_SESSION['oop'] ?? $_SESSION['o'] ?? $_SESSION['os'] ?? '';
if (empty($agent)) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$client_id = (int)($_GET['client_id'] ?? 0);
if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid client ID']);
    exit();
}

$conn = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit();
}
mysqli_set_charset($conn, 'utf8mb4');

// Get client name
$cl = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nom, prenom FROM client WHERE id = $client_id LIMIT 1"));
$client_name = $cl ? trim(($cl['prenom'] ?? '') . ' ' . ($cl['nom'] ?? '')) : 'Client #' . $client_id;

// Summary stats
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) as total_sales,
        COALESCE(SUM(final_total), 0) as total_spent,
        COALESCE(SUM(discount), 0) as total_discounts,
        MAX(created_at) as last_visit
     FROM pos_sales
     WHERE client_id = $client_id AND status IN ('completed','pending')"
));

// Sales list
$res = mysqli_query($conn,
    "SELECT s.*,
        (SELECT GROUP_CONCAT(product_name, ' x', qty SEPARATOR ', ')
         FROM pos_sale_items WHERE sale_id = s.id) as items_summary,
        (SELECT COUNT(*) FROM pos_sale_items WHERE sale_id = s.id) as item_count
     FROM pos_sales s
     WHERE s.client_id = $client_id
     ORDER BY s.created_at DESC"
);

$sales = [];
while ($r = mysqli_fetch_assoc($res)) {
    $sales[] = [
        'id'             => $r['id'],
        'date'           => date('d M Y', strtotime($r['created_at'])),
        'time'           => date('H:i', strtotime($r['created_at'])),
        'items_summary'  => $r['items_summary'] ?? '',
        'item_count'     => (int)$r['item_count'],
        'payment_method' => $r['payment_method'],
        'currency'       => $r['currency'],
        'discount'       => $r['discount'],
        'final_total'    => $r['final_total'],
        'status'         => $r['status'],
        'agent_name'     => $r['agent_name'],
    ];
}

mysqli_close($conn);

echo json_encode([
    'success'         => true,
    'client_name'     => $client_name,
    'total_sales'     => (int)$stats['total_sales'],
    'total_spent'     => (float)$stats['total_spent'],
    'total_discounts' => (float)$stats['total_discounts'],
    'last_visit'      => $stats['last_visit'] ? date('d M Y', strtotime($stats['last_visit'])) : '—',
    'sales'           => $sales,
]);
?>
