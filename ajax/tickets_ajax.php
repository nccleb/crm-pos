<?php
// ajax/tickets_ajax.php
session_start();

header('Content-Type: application/json');

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

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard_stats':
        getDashboardStats($idr);
        break;
    
    case 'recent_tickets':
        getRecentTickets($idr);
        break;
    
    case 'update_status':
        updateTicketStatus($idr);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function getDashboardStats($idr) {
    $query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('Not Resolved', 'In Progress') THEN 1 ELSE 0 END) as open,
        SUM(CASE WHEN status = 'Not Resolved' THEN 1 ELSE 0 END) as not_resolved,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed,
        SUM(CASE WHEN priority = 'High' AND status NOT IN ('Resolved', 'Closed') THEN 1 ELSE 0 END) as high_priority
    FROM crm";
    
    $result = mysqli_query($idr, $query);
    $stats = mysqli_fetch_assoc($result);
    
    echo json_encode([
        'total' => (int)$stats['total'],
        'open' => (int)$stats['open'],
        'not_resolved' => (int)$stats['not_resolved'],
        'in_progress' => (int)$stats['in_progress'],
        'resolved' => (int)$stats['resolved'],
        'closed' => (int)$stats['closed'],
        'high_priority' => (int)$stats['high_priority']
    ]);
}

function getRecentTickets($idr) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    
    $query = "SELECT cr.idc, cr.task, cr.status, cr.priority, cr.lcd,
              c.nom, c.prenom
              FROM crm cr
              INNER JOIN client c ON cr.id = c.id
              WHERE cr.status IN ('Not Resolved', 'In Progress')
              ORDER BY 
                FIELD(cr.priority, 'High', 'Medium', 'Low'),
                cr.lcd DESC
              LIMIT $limit";
    
    $result = mysqli_query($idr, $query);
    $tickets = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $tickets[] = [
            'id' => $row['idc'],
            'title' => $row['task'],
            'client' => $row['nom'] . ' ' . $row['prenom'],
            'status' => $row['status'],
            'priority' => $row['priority'],
            'date' => $row['lcd']
        ];
    }
    
    echo json_encode($tickets);
}

function updateTicketStatus($idr) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $ticket_id = (int)$_POST['ticket_id'];
    $new_status = mysqli_real_escape_string($idr, $_POST['status']);
    
    $valid_statuses = ['Not Resolved', 'In Progress', 'Resolved', 'Closed'];
    if (!in_array($new_status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        return;
    }
    
    $query = "UPDATE crm SET status = '$new_status', lcd = NOW() WHERE idc = $ticket_id";
    
    if (mysqli_query($idr, $query)) {
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to update status',
            'details' => mysqli_error($idr)
        ]);
    }
}

mysqli_close($idr);
?>