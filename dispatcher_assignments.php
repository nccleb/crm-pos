<?php
// UTF-8 Fix for Arabic
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

session_start();
$nam = urldecode($_GET['page'] ?? '');
$idf = urldecode($_GET['page1'] ?? '');
$_SESSION["oop"] = $nam;
$_SESSION["ooq"] = $idf;



$idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
mysqli_set_charset($idr, "utf8mb4");  // ADD THIS LINE

// Handle clear assignments action
if (isset($_POST['clear_assignments'])) {
    $confirm_clear = isset($_POST['confirm_clear']);
    if (!$confirm_clear) {
        $error_msg = "Please check the confirmation checkbox to proceed with clearing assignments.";
    } else {
        try {
            $clear_query = "UPDATE driver_status ds 
                           LEFT JOIN dispatch_assignments da ON ds.current_assignment_id = da.id 
                           SET ds.current_assignment_id = NULL 
                           WHERE ds.current_assignment_id IS NOT NULL 
                           AND (da.status IN ('delivered', 'failed') OR da.id IS NULL)";
            $result = mysqli_query($idr, $clear_query);
            if ($result) {
                $affected_rows = mysqli_affected_rows($idr);
                $_SESSION['success_msg'] = "Successfully cleared assignment IDs for {$affected_rows} driver(s).";
                header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
                exit();
            } else {
                $error_msg = "Error clearing assignments: " . mysqli_error($idr);
            }
        } catch (Exception $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Get current tracking URL
function getCurrentTrackingUrl() {
    $urlFile = __DIR__ . '/tracking_url.txt';
    if (file_exists($urlFile)) {
        $url = trim(file_get_contents($urlFile));
        return $url ?: 'https://8d9285c2f0dc.ngrok-free.app';
    }
    return 'https://8d9285c2f0dc.ngrok-free.app';
}
$current_tracking_url = getCurrentTrackingUrl();

// Handle bulk delete by date range
if (isset($_POST['delete_by_date_range'])) {
    $delete_from_date = $_POST['delete_from_date'];
    $delete_to_date = $_POST['delete_to_date'];
    $confirm_delete = isset($_POST['confirm_delete']);
    
    if (!$confirm_delete) {
        $error_msg = "Please check the confirmation checkbox to proceed with deletion.";
    } elseif (empty($delete_from_date) || empty($delete_to_date)) {
        $error_msg = "Both start and end dates are required for deletion.";
    } else {
        $count_stmt = $idr->prepare("SELECT COUNT(*) as count FROM dispatch_assignments WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?");
        $count_stmt->bind_param("ss", $delete_from_date, $delete_to_date);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $records_to_delete = $count_row['count'];
        $count_stmt->close();
        
        if ($records_to_delete > 0) {
            $delete_stmt = $idr->prepare("DELETE FROM dispatch_assignments WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?");
            $delete_stmt->bind_param("ss", $delete_from_date, $delete_to_date);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success_msg'] = "Successfully deleted {$records_to_delete} assignment(s) from {$delete_from_date} to {$delete_to_date}.";
                header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
                exit();
            } else {
                $error_msg = "Error deleting assignments: " . $delete_stmt->error;
            }
            $delete_stmt->close();
        } else {
            $_SESSION['success_msg'] = "No assignments found in the specified date range to delete.";
            header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
            exit();
        }
    }
}

// Handle delete all and reset ID
if (isset($_POST['delete_all_reset_id'])) {
    $confirm_delete_all = isset($_POST['confirm_delete_all']);
    
    if (!$confirm_delete_all) {
        $error_msg = "Please check the confirmation checkbox to proceed with deleting all records.";
    } else {
        $count_result = mysqli_query($idr, "SELECT COUNT(*) as count FROM dispatch_assignments");
        $count_row = mysqli_fetch_assoc($count_result);
        $total_records = $count_row['count'];
        
        $delete_all_result = mysqli_query($idr, "DELETE FROM dispatch_assignments");
        
        if ($delete_all_result) {
            $reset_result = mysqli_query($idr, "ALTER TABLE dispatch_assignments AUTO_INCREMENT = 1");
            
            if ($reset_result) {
                $_SESSION['success_msg'] = "Successfully deleted all {$total_records} assignment(s) and reset ID counter to 0.";
            } else {
                $_SESSION['success_msg'] = "Deleted all {$total_records} assignment(s), but failed to reset ID counter.";
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
            exit();
        } else {
            $error_msg = "Error deleting all assignments: " . mysqli_error($idr);
        }
    }
}

// Get client information
$id = $name = $lname = $ui = '';
if (isset($_GET['page']) && !empty($_GET['page'])) {
    $ui = $_GET['page'];
    $stmt = $idr->prepare("SELECT * FROM client WHERE number=? OR inumber=? OR telmobile=? OR telother=?");
    $stmt->bind_param("ssss", $ui, $ui, $ui, $ui);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $name = $row['nom'];
        $lname = $row['prenom'];
        $_SESSION["id"] = $id;
    }
    $stmt->close();
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['create_assignment'])) {
        $client_id = $_POST['client_id'];
        $order_id = $_POST['order_id'] ? $_POST['order_id'] : NULL;
        $dispatcher_id = $_POST['dispatcher_id'];
        $delivery_address = $_POST['delivery_address'];
        $delivery_instructions = $_POST['delivery_instructions'];
        $estimated_arrival = $_POST['estimated_arrival'];
        $status = $_POST['status'] ? $_POST['status'] : 'pending';
        
        $stmt = $idr->prepare("INSERT INTO dispatch_assignments 
            (client_id, order_id, dispatcher_id, delivery_address, delivery_instructions, status, estimated_arrival, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("iiissss", $client_id, $order_id, $dispatcher_id, $delivery_address, $delivery_instructions, $status, $estimated_arrival);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Assignment created successfully!";
            header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
            exit();
        } else {
            $error_msg = "Error creating assignment: " . $stmt->error;
        }
        $stmt->close();
    }
    
    if (isset($_POST['update_status'])) {
        $assignment_id = $_POST['assignment_id'];
        $new_status = $_POST['new_status'];
        $actual_arrival = $_POST['actual_arrival'] ? $_POST['actual_arrival'] : NULL;
        
        $stmt = $idr->prepare("UPDATE dispatch_assignments SET status = ?, actual_arrival = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $new_status, $actual_arrival, $assignment_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Status updated successfully!";
            header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
            exit();
        } else {
            $error_msg = "Error updating status: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Handle single assignment deletion
    if (isset($_POST['delete_single_assignment'])) {
        $assignment_id = $_POST['assignment_id'];
        
        $delete_stmt = $idr->prepare("DELETE FROM dispatch_assignments WHERE id = ?");
        $delete_stmt->bind_param("i", $assignment_id);
        
        if ($delete_stmt->execute()) {
            if ($delete_stmt->affected_rows > 0) {
                $_SESSION['success_msg'] = "Assignment deleted successfully!";
            } else {
                $_SESSION['error_msg'] = "Assignment not found or already deleted.";
            }
        } else {
            $_SESSION['error_msg'] = "Error deleting assignment: " . $delete_stmt->error;
        }
        $delete_stmt->close();
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
        exit();
    }
}

// Check for session messages
if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $error_msg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// Get filter parameters
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_client_id = $_GET['client_id'] ?? '';
$filter_dispatcher_id = $_GET['dispatcher_id'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];
$types = "";

if ($filter_date_from) {
    $where_conditions[] = "DATE(da.created_at) >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}
if ($filter_date_to) {
    $where_conditions[] = "DATE(da.created_at) <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}
if ($filter_client_id) {
    $where_conditions[] = "da.client_id = ?";
    $params[] = $filter_client_id;
    $types .= "i";
}
if ($filter_dispatcher_id) {
    $where_conditions[] = "da.dispatcher_id = ?";
    $params[] = $filter_dispatcher_id;
    $types .= "i";
}
if ($filter_status) {
    $where_conditions[] = "da.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$where_clause = "";
if (count($where_conditions) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$query = "SELECT da.*, 
    CONCAT(c.nom, ' ', c.prenom) as client_name,
    c.number as client_phone,
    d.name_d as dispatcher_name
    FROM dispatch_assignments da
    LEFT JOIN client c ON da.client_id = c.id
    LEFT JOIN drivers d ON da.dispatcher_id = d.idx
    $where_clause
    ORDER BY da.created_at DESC";

if (count($params) > 0) {
    $stmt = $idr->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = mysqli_query($idr, $query);
}

// FIXED: Analytics Calculations
function getAnalyticsData($idr) {
    $analytics = [];
    
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Total assignments today
    $result = mysqli_query($idr, "SELECT COUNT(*) as count FROM dispatch_assignments WHERE DATE(created_at) = '$today'");
    $analytics['today_total'] = mysqli_fetch_assoc($result)['count'];
    
    // Yesterday's total
    $result = mysqli_query($idr, "SELECT COUNT(*) as count FROM dispatch_assignments WHERE DATE(created_at) = '$yesterday'");
    $analytics['yesterday_total'] = mysqli_fetch_assoc($result)['count'];
    
    // Success rate (last 30 days)
    $result = mysqli_query($idr, "SELECT 
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        COUNT(*) as total 
        FROM dispatch_assignments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $row = mysqli_fetch_assoc($result);
    $analytics['success_rate'] = $row['total'] > 0 ? round(($row['delivered'] / $row['total']) * 100, 1) : 0;
    
    // Active dispatchers today
    $result = mysqli_query($idr, "SELECT COUNT(DISTINCT dispatcher_id) as count FROM dispatch_assignments WHERE DATE(created_at) = '$today'");
    $analytics['active_dispatchers'] = mysqli_fetch_assoc($result)['count'];
    
    // Status breakdown (last 7 days)
    $result = mysqli_query($idr, "SELECT status, COUNT(*) as count FROM dispatch_assignments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY status");
    $analytics['status_breakdown'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $analytics['status_breakdown'][$row['status']] = $row['count'];
    }
    
    // FIXED: Top dispatchers with proper ranking
    $result = mysqli_query($idr, "SELECT 
        d.name_d, 
        d.idx as driver_id,
        COUNT(*) as total_assignments,
        SUM(CASE WHEN da.status = 'delivered' THEN 1 ELSE 0 END) as successful,
        AVG(CASE 
            WHEN da.actual_arrival IS NOT NULL 
            AND da.estimated_arrival IS NOT NULL 
            AND TIMESTAMPDIFF(MINUTE, da.estimated_arrival, da.actual_arrival) BETWEEN -60 AND 120
            THEN TIMESTAMPDIFF(MINUTE, da.estimated_arrival, da.actual_arrival) 
            ELSE NULL 
        END) as avg_delay_minutes,
        COUNT(CASE 
            WHEN da.actual_arrival IS NOT NULL 
            AND da.estimated_arrival IS NOT NULL 
            THEN 1 
        END) as completed_with_times
        FROM dispatch_assignments da
        INNER JOIN drivers d ON da.dispatcher_id = d.idx
        WHERE da.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND d.name_d IS NOT NULL
          AND d.name_d != ''
        GROUP BY d.idx, d.name_d
        HAVING total_assignments > 0
        ORDER BY 
            (successful / total_assignments) DESC,
            COALESCE(avg_delay_minutes, 999) ASC,
            total_assignments DESC
        LIMIT 5");
    
    $analytics['top_dispatchers'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $success_rate = $row['total_assignments'] > 0 ? round(($row['successful'] / $row['total_assignments']) * 100, 1) : 0;
        
        $avg_delay = null;
        if ($row['completed_with_times'] > 0 && $row['avg_delay_minutes'] !== null) {
            $avg_delay = round($row['avg_delay_minutes'], 1);
        }
        
        $analytics['top_dispatchers'][] = [
            'name' => $row['name_d'],
            'driver_id' => $row['driver_id'],
            'total' => $row['total_assignments'],
            'successful' => $row['successful'],
            'success_rate' => $success_rate,
            'avg_delay' => $avg_delay,
            'completed_with_times' => $row['completed_with_times']
        ];
    }
    
    // Hourly distribution
    $result = mysqli_query($idr, "SELECT HOUR(created_at) as hour, COUNT(*) as count 
        FROM dispatch_assignments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY HOUR(created_at) 
        ORDER BY hour");
    $analytics['hourly_distribution'] = array_fill(0, 24, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $analytics['hourly_distribution'][$row['hour']] = $row['count'];
    }
    
    // Daily trend
    $result = mysqli_query($idr, "SELECT DATE(created_at) as date, 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered
        FROM dispatch_assignments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date");
    $analytics['daily_trend'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $analytics['daily_trend'][] = [
            'date' => $row['date'],
            'total' => $row['total'],
            'delivered' => $row['delivered']
        ];
    }
    
    // FIXED: Average delivery time with detailed breakdown
    $result = mysqli_query($idr, "SELECT 
        COUNT(*) as total_deliveries,
        AVG(TIMESTAMPDIFF(MINUTE, created_at, actual_arrival)) as avg_delivery_time,
        MIN(TIMESTAMPDIFF(MINUTE, created_at, actual_arrival)) as min_delivery_time,
        MAX(TIMESTAMPDIFF(MINUTE, created_at, actual_arrival)) as max_delivery_time,
        SUM(TIMESTAMPDIFF(MINUTE, created_at, actual_arrival)) as total_minutes
        FROM dispatch_assignments 
        WHERE status = 'delivered' 
          AND actual_arrival IS NOT NULL
          AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND TIMESTAMPDIFF(MINUTE, created_at, actual_arrival) BETWEEN 1 AND 1440");
    
    $row = mysqli_fetch_assoc($result);
    $analytics['avg_delivery_time'] = round($row['avg_delivery_time'] ?? 0, 1);
    $analytics['min_delivery_time'] = $row['min_delivery_time'] ?? 0;
    $analytics['max_delivery_time'] = $row['max_delivery_time'] ?? 0;
    $analytics['total_deliveries'] = $row['total_deliveries'] ?? 0;
    $analytics['total_minutes'] = $row['total_minutes'] ?? 0;
    
    return $analytics;
}

$analytics = getAnalyticsData($idr);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispatcher Assignment Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .btn-gradient-danger {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-gradient-danger:hover {
            background: linear-gradient(45deg, #ee5a24, #ff6b6b);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(238, 90, 36, 0.3);
            color: white;
        }
        .status-pending { background-color: #fff3cd; }
        .status-assigned { background-color: #d1ecf1; }
        .status-in_transit { background-color: #d4edda; }
        .status-delivered { background-color: #f8d7da; }
        .status-failed { background-color: #f5c6cb; }
        .card { margin-bottom: 20px; }
        .table { font-size: 0.9em; }
        .analytics-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        .metric-card {
            border-left: 4px solid;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .metric-value {
            font-size: 2em;
            font-weight: bold;
        }
        .metric-change {
            font-size: 0.9em;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .delete-section {
            background-color: #ffe6e6;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 10px 0;
        }
        .danger-zone {
            border: 2px dashed #dc3545;
            background-color: #ffeaea;
        }
        .nav-tabs .nav-link.active {
            background-color: #667eea;
            color: white;
            border-color: #667eea;
        }
    </style>
</head>
<body>
<?php
include 'navbar.php';
?>
<div class="container-fluid mt-3">
    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($success_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Analytics Dashboard -->
    <div class="card analytics-card">
        <div class="card-header">
            <h4 class="mb-0">📊 Business Analytics Dashboard</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="metric-card p-3 mb-3" style="border-left-color: #28a745;">
                        <div class="metric-value text-success"><?php echo $analytics['today_total']; ?></div>
                        <div>Today's Assignments</div>
                        <div class="metric-change">
                            <?php 
                            $change = $analytics['today_total'] - $analytics['yesterday_total'];
                            $change_class = $change >= 0 ? 'text-success' : 'text-danger';
                            $change_icon = $change >= 0 ? '▲' : '▼';
                            echo "<span class='$change_class'>$change_icon " . abs($change) . " from yesterday</span>";
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card p-3 mb-3" style="border-left-color: #17a2b8;">
                        <div class="metric-value text-info"><?php echo $analytics['success_rate']; ?>%</div>
                        <div>Success Rate (30 days)</div>
                        <div class="metric-change text-muted">Delivered assignments</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card p-3 mb-3" style="border-left-color: #ffc107;">
                        <div class="metric-value text-warning"><?php echo $analytics['active_dispatchers']; ?></div>
                        <div>Active Dispatchers</div>
                        <div class="metric-change text-muted">Working today</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card p-3 mb-3" style="border-left-color: #6f42c1;">
                        <div class="metric-value text-primary" title="Total: <?php echo $analytics['total_deliveries']; ?> deliveries, <?php echo $analytics['total_minutes']; ?> total minutes. Range: <?php echo $analytics['min_delivery_time']; ?>-<?php echo $analytics['max_delivery_time']; ?> min">
                            <?php echo $analytics['avg_delivery_time']; ?> min
                        </div>
                        <div>Avg Delivery Time</div>
                        <div class="metric-change text-muted">
                            Last 30 days (<?php echo $analytics['total_deliveries']; ?> deliveries)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="analyticsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button">Performance</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="trends-tab" data-bs-toggle="tab" data-bs-target="#trends" type="button">Trends</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="dispatchers-tab" data-bs-toggle="tab" data-bs-target="#dispatchers" type="button">Drivers</button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="analyticsTabContent">
                <!-- Performance Tab -->
                <div class="tab-pane fade show active" id="performance" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Status Distribution (Last 7 Days)</h6>
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Hourly Activity (Last 7 Days)</h6>
                            <div class="chart-container">
                                <canvas id="hourlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trends Tab -->
                <div class="tab-pane fade" id="trends" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <h6>Daily Assignment Trend (Last 14 Days)</h6>
                            <div class="chart-container">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FIXED: Dispatchers Tab -->
                <div class="tab-pane fade" id="dispatchers" role="tabpanel">
                    <h6>Top Performing Drivers (Last 30 Days)</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Rank</th>
                                    <th>Driver</th>
                                    <th>Total Assignments</th>
                                    <th>Successful</th>
                                    <th>Success Rate</th>
                                    <th>Avg Delay</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($analytics['top_dispatchers'] as $dispatcher): 
                                ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $medal = '';
                                        if ($rank == 1) $medal = '🥇';
                                        elseif ($rank == 2) $medal = '🥈';
                                        elseif ($rank == 3) $medal = '🥉';
                                        echo $medal . ' ' . $rank;
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($dispatcher['name']); ?></td>
                                    <td><?php echo $dispatcher['total']; ?></td>
                                    <td><?php echo $dispatcher['successful']; ?></td>
                                    <td>
                                        <?php
                                        $rate = $dispatcher['success_rate'];
                                        $badge_class = $rate >= 90 ? 'success' : ($rate >= 70 ? 'warning' : 'danger');
                                        echo "<span class='badge bg-$badge_class'>{$rate}%</span>";
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $delay = $dispatcher['avg_delay'];
                                        
                                        if ($delay === null) {
                                            echo '<span class="text-muted" title="No completed deliveries with estimated times">N/A</span>';
                                        } else {
                                            if ($delay <= 0) {
                                                $delay_class = 'text-success';
                                                $icon = '✓';
                                            } elseif ($delay <= 5) {
                                                $delay_class = 'text-info';
                                                $icon = '→';
                                            } elseif ($delay <= 15) {
                                                $delay_class = 'text-warning';
                                                $icon = '⚠';
                                            } else {
                                                $delay_class = 'text-danger';
                                                $icon = '✗';
                                            }
                                            
                                            echo "<span class='$delay_class' title='Average delay from estimated time'>
                                                    $icon " . ($delay > 0 ? '+' : '') . $delay . " min
                                                  </span>";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php 
                                $rank++;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create New Assignment -->
    <div class="card">
        <div class="card-header">
            <h5>Create New Driver Assignment</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Client</label>
                        <select name="client_id" class="form-select" required>
                            <?php if ($id): ?>
                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name . " " . $lname . " " . $ui); ?></option>
                            <?php endif; ?>
                            <?php
                            $clients = mysqli_query($idr, "SELECT id, CONCAT(nom, ' ', prenom) as name, number FROM client ORDER BY nom");
                            while ($client = mysqli_fetch_assoc($clients)) {
                                echo "<option value='{$client['id']}'>" . htmlspecialchars($client['name']) . " ({$client['number']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Order ID (Optional)</label>
                        <input type="number" name="order_id" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Driver</label>
                        <select name="dispatcher_id" class="form-select" required>
                            <option value="">Select Driver</option>
                            <?php
                            $dispatchers = mysqli_query($idr, "SELECT idx, name_d FROM drivers ORDER BY name_d");
                            while ($dispatcher = mysqli_fetch_assoc($dispatchers)) {
                                echo "<option value='{$dispatcher['idx']}'>" . htmlspecialchars($dispatcher['name_d']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estimated Arrival</label>
                        <input type="datetime-local" name="estimated_arrival" class="form-control">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Order Details</label>
                        <textarea name="delivery_address" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Delivery Instructions</label>
                        <textarea name="delivery_instructions" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="create_assignment" class="btn btn-primary">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- WhatsApp Assignment Section -->
    <div class="card" style="background: #e8f5e9; border: 2px solid #4caf50;">
        <div class="card-header bg-success text-white">
            <h5>📱 Send Assignment via WhatsApp</h5>
        </div>
        <div class="card-body">
            <div id="whatsappSectionContent" style="display: none;">
                <div class="mb-3">
                    <label class="form-label"><strong>Selected Driver:</strong></label>
                    <input type="text" id="selectedDriverName" readonly class="form-control bg-light">
                    <input type="hidden" id="selectedDriverId">
                </div>
                
                <!-- Assignment Message -->
                <div class="mb-3">
                    <label class="form-label"><strong>STEP 1: Assignment Details</strong></label>
                    <textarea id="assignmentMessage" readonly class="form-control" rows="5" style="font-family: monospace;">Click WhatsApp button in the assignments table to load details here...</textarea>
                    <button onclick="copyAssignment()" id="copyAssignmentBtn" class="btn btn-success mt-2">
                        <i class="fas fa-copy"></i> Copy Assignment Message
                    </button>
                </div>
                
                <!-- Tracking Section -->
                <div class="card" style="background: #fff8e1; border: 2px solid #ffc107;">
                    <div class="card-header bg-warning">
                        <strong>STEP 2: Driver Tracking Information</strong>
                    </div>
                    <div class="card-body">
                        <!-- Base URL -->
                        <div class="mb-3 p-3" style="background: #e8f5e8; border: 2px solid #4caf50; border-radius: 5px;">
                            <label class="form-label"><strong>📱 App URL (Copy First):</strong></label>
                            <div class="input-group">
                                <input type="text" id="baseTrackingUrl" readonly class="form-control" style="font-family: monospace;">
                                <button onclick="copyBaseUrl()" class="btn btn-success">
                                    <i class="fas fa-copy"></i> Copy URL
                                </button>
                            </div>
                            <small class="text-muted">Driver pastes this in "Enter Ngrok URL" field</small>
                        </div>
                        
                        <!-- Driver ID -->
                        <div class="mb-3 p-3" style="background: #fff3e0; border: 2px solid #ff9800; border-radius: 5px;">
                            <label class="form-label"><strong>🔑 Driver ID (Copy Second):</strong></label>
                            <div class="input-group">
                                <input type="text" id="driverIdOnly" readonly class="form-control text-center fw-bold" style="font-family: monospace; font-size: 1.2em; color: #ff6600;">
                                <button onclick="copyDriverId()" class="btn btn-warning">
                                    <i class="fas fa-copy"></i> Copy ID
                                </button>
                            </div>
                            <small class="text-muted">Driver enters this in "Driver ID" field</small>
                        </div>
                        
                        <!-- Complete WhatsApp Message -->
                        <div class="mb-3 p-3" style="background: #e8f5e8; border: 2px solid #25D366; border-radius: 5px;">
                            <label class="form-label"><strong>📨 Complete WhatsApp Message:</strong></label>
                            <textarea id="whatsappOptimizedMessage" readonly class="form-control" rows="8" style="font-family: monospace; font-size: 0.9em;"></textarea>
                            <button onclick="copyOptimizedMessage()" class="btn btn-success mt-2">
                                <i class="fas fa-copy"></i> Copy Complete Message
                            </button>
                        </div>
                        
                        <button onclick="openWhatsApp()" class="btn btn-success btn-lg">
                            <i class="fab fa-whatsapp"></i> Open WhatsApp Web
                        </button>
                    </div>
                </div>
            </div>
            
            <div id="noDriverSelected" style="padding: 20px; background: #ffebee; border: 1px solid #f44336; border-radius: 5px;">
                <p class="mb-0"><strong>ℹ️ Please click the WhatsApp button in the assignments table to load driver and assignment details.</strong></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card">
        <div class="card-header">
            <h5>Filter & Reports</h5>
        </div>
        <div class="card-body">
            <form method="GET">
                <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page'] ?? ''); ?>">
                <input type="hidden" name="page1" value="<?php echo htmlspecialchars($_GET['page1'] ?? ''); ?>">
                
                <div class="row">
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $filter_date_from; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $filter_date_to; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Client</label>
                        <select name="client_id" class="form-select">
                            <option value="">All Clients</option>
                            <?php
                            $clients = mysqli_query($idr, "SELECT id, CONCAT(nom, ' ', prenom) as name, number FROM client ORDER BY nom");
                            while ($client = mysqli_fetch_assoc($clients)) {
                                $selected = ($filter_client_id == $client['id']) ? 'selected' : '';
                                echo "<option value='{$client['id']}' $selected>" . htmlspecialchars($client['name']) . " ({$client['number']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Driver</label>
                        <select name="dispatcher_id" class="form-select">
                            <option value="">All Drivers</option>
                            <?php
                            $dispatchers = mysqli_query($idr, "SELECT idx, name_d FROM drivers ORDER BY name_d");
                            while ($dispatcher = mysqli_fetch_assoc($dispatchers)) {
                                $selected = ($filter_dispatcher_id == $dispatcher['idx']) ? 'selected' : '';
                                echo "<option value='{$dispatcher['idx']}' $selected>" . htmlspecialchars($dispatcher['name_d']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="assigned" <?php echo ($filter_status == 'assigned') ? 'selected' : ''; ?>>Assigned</option>
                            <option value="in_transit" <?php echo ($filter_status == 'in_transit') ? 'selected' : ''; ?>>In Transit</option>
                            <option value="delivered" <?php echo ($filter_status == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                            <option value="failed" <?php echo ($filter_status == 'failed') ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Apply
                        </button>
                        <a href="dispatcher_assignments.php?page=<?php echo urlencode($nam); ?>&page1=<?php echo urlencode($idf); ?>" class="btn btn-light border">
                            <i class="fas fa-eraser me-1"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignments List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>Delivery Assignments</h5>
            <button onclick="exportToCSV()" class="btn btn-success btn-sm">Export to CSV</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="assignmentsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Phone</th>
                            <th>Driver</th>
                            <th>Order</th>
                            <th>Instructions</th>
                            <th>Status</th>
                            <th>Est. Arrival</th>
                            <th>Actual Arrival</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="status-<?php echo $row['status']; ?>">
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['client_phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['dispatcher_name']); ?></td>
                            <td title="<?php echo htmlspecialchars($row['delivery_address']); ?>">
                                <?php echo htmlspecialchars(substr($row['delivery_address'], 0, 50) . '...'); ?>
                            </td>
                            <td title="<?php echo htmlspecialchars($row['delivery_instructions']); ?>">
                                <?php echo htmlspecialchars(substr($row['delivery_instructions'], 0, 30) . '...'); ?>
                            </td>
                            <td>
                               <?php
                                $status_classes = [
                                    'pending'    => 'warning',
                                    'assigned'   => 'info',
                                    'in_transit' => 'primary',
                                    'delivered'  => 'success',
                                    'failed'     => 'danger'
                                ];
                                $badge_class = isset($status_classes[$row['status']]) ? $status_classes[$row['status']] : 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $row['estimated_arrival'] ? date('M j, g:i A', strtotime($row['estimated_arrival'])) : '-'; ?></td>
                            <td><?php echo $row['actual_arrival'] ? date('M j, g:i A', strtotime($row['actual_arrival'])) : '-'; ?></td>
                            <td><?php echo date('M j, g:i A', strtotime($row['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="updateStatus(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')">
                                    Update
                                </button>
                                <button class="btn btn-sm btn-outline-success" 
                                    onclick="setupWhatsAppFromTable(
                                        <?php echo $row['dispatcher_id']; ?>,
                                        '<?php echo htmlspecialchars($row['dispatcher_name'], ENT_QUOTES); ?>',
                                        <?php echo $row['id']; ?>
                                    )">
                                    WhatsApp
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteSingleAssignment(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['client_name'], ENT_QUOTES); ?>')">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete Section -->
    <div class="card danger-zone">
        <div class="card-header bg-danger text-white">
            <h5>⚠️ Delete Assignments - DANGER ZONE</h5>
        </div>
        <div class="card-body delete-section">
            <div class="alert alert-warning">
                <strong>WARNING:</strong> Deletion is permanent and cannot be undone. Please be careful!
            </div>
            
            <!-- Delete by Date Range -->
            <form method="POST" onsubmit="return confirmBulkDelete()">
                <h6>Delete All Assignments by Date Range</h6>
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Delete From Date</label>
                        <input type="date" name="delete_from_date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Delete To Date</label>
                        <input type="date" name="delete_to_date" class="form-control" required>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="confirm_delete" id="confirmDelete" required>
                            <label class="form-check-label text-danger" for="confirmDelete">
                                <strong>I understand this will permanently delete all assignments in the selected date range</strong>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="delete_by_date_range" class="btn btn-danger">
                        🗑️ DELETE ASSIGNMENTS BY DATE RANGE
                    </button>
                </div>
            </form>
            
            <hr class="my-4">
            
            <!-- Delete ALL and Reset ID -->
            <form method="POST" onsubmit="return confirmDeleteAllReset()">
                <h6 class="text-danger">🔥 Delete ALL Assignments and Reset ID Counter</h6>
                <div class="alert alert-danger">
                    <strong>⚠️ EXTREME CAUTION:</strong> This will delete EVERY assignment record in the database and reset the ID counter to 0.
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="confirm_delete_all" id="confirmDeleteAll" required>
                            <label class="form-check-label text-danger" for="confirmDeleteAll">
                                <strong>I understand this will PERMANENTLY DELETE ALL assignments and reset the ID counter. This cannot be undone!</strong>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="delete_all_reset_id" class="btn btn-danger btn-lg">
                        💥 DELETE ALL & RESET ID TO 0
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Clear Assignments Section -->
    <div class="card" style="border: 2px solid #28a745;">
        <div class="card-header bg-success text-white">
            <h5>🔧 Fix Driver Status - Clear Completed Assignments</h5>
        </div>
        <div class="card-body" style="background-color: #f8fff8;">
            <div class="alert alert-info">
                <strong>What this does:</strong> This will clear assignment IDs from drivers who have completed deliveries (delivered/failed status). 
                This fixes the issue where drivers always show as "busy" in live tracking.
            </div>
            <form method="POST" onsubmit="return confirmClearAssignments()">
                <div class="row">
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="confirm_clear" id="confirmClear" required>
                            <label class="form-check-label text-success" for="confirmClear">
                                <strong>I understand this will clear assignment IDs for drivers with completed deliveries</strong>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="clear_assignments" class="btn btn-success">
                        🔧 Clear Completed Assignments (Fix Driver Status)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Update Assignment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="assignment_id" id="modal_assignment_id">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="new_status" id="modal_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Actual Arrival (for delivered/failed)</label>
                        <input type="datetime-local" name="actual_arrival" id="modal_actual_arrival" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Single Assignment Modal -->
<div class="modal fade" id="deleteSingleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Delete Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="assignment_id" id="delete_assignment_id">
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    <p>Are you sure you want to delete this assignment for client: <strong id="delete_client_name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_single_assignment" class="btn btn-danger">Delete Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

function initializeCharts() {
    // Status Distribution Chart
    const statusData = <?php echo json_encode($analytics['status_breakdown']); ?>;
    const statusLabels = Object.keys(statusData).map(status => status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' '));
    const statusValues = Object.values(statusData);
    
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusValues,
                backgroundColor: ['#ffc107', '#17a2b8', '#007bff', '#28a745', '#dc3545'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Hourly Distribution Chart
    const hourlyData = <?php echo json_encode($analytics['hourly_distribution']); ?>;
    const hourLabels = Array.from({length: 24}, (_, i) => i + ':00');
    
    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: hourLabels,
            datasets: [{
                label: 'Assignments',
                data: hourlyData,
                backgroundColor: 'rgba(102, 126, 234, 0.6)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Daily Trend Chart
    const trendData = <?php echo json_encode($analytics['daily_trend']); ?>;
    const trendLabels = trendData.map(item => {
        const date = new Date(item.date);
        return (date.getMonth() + 1) + '/' + date.getDate();
    });
    const totalData = trendData.map(item => item.total);
    const deliveredData = trendData.map(item => item.delivered);
    
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Total Assignments',
                data: totalData,
                borderColor: 'rgba(102, 126, 234, 1)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4
            }, {
                label: 'Delivered',
                data: deliveredData,
                borderColor: 'rgba(40, 167, 69, 1)',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function updateStatus(assignmentId, currentStatus) {
    document.getElementById('modal_assignment_id').value = assignmentId;
    document.getElementById('modal_status').value = currentStatus;
    document.getElementById('modal_actual_arrival').value = '';
    
    if (currentStatus === 'delivered' || currentStatus === 'failed') {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('modal_actual_arrival').value = now.toISOString().slice(0,16);
    }
    
    var updateModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    updateModal.show();
}

document.getElementById('modal_status')?.addEventListener('change', function() {
    if (this.value === 'delivered' || this.value === 'failed') {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('modal_actual_arrival').value = now.toISOString().slice(0,16);
    } else {
        document.getElementById('modal_actual_arrival').value = '';
    }
});

function deleteSingleAssignment(assignmentId, clientName) {
    document.getElementById('delete_assignment_id').value = assignmentId;
    document.getElementById('delete_client_name').textContent = clientName;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteSingleModal'));
    deleteModal.show();
}

function confirmBulkDelete() {
    const fromDate = document.querySelector('input[name="delete_from_date"]').value;
    const toDate = document.querySelector('input[name="delete_to_date"]').value;
    const confirmed = document.getElementById('confirmDelete').checked;
    
    if (!confirmed) {
        alert('Please check the confirmation box to proceed with deletion.');
        return false;
    }
    
    return confirm(`⚠️ FINAL CONFIRMATION ⚠️\n\nAre you absolutely sure you want to delete ALL assignments from ${fromDate} to ${toDate}?\n\nThis action cannot be undone!`);
}

function confirmDeleteAllReset() {
    const confirmed = document.getElementById('confirmDeleteAll').checked;
    
    if (!confirmed) {
        alert('Please check the confirmation box to proceed with deleting all records.');
        return false;
    }
    
    const firstConfirm = confirm('🔥 CRITICAL WARNING 🔥\n\nYou are about to DELETE ALL assignment records and reset the ID counter.\n\nAre you ABSOLUTELY SURE?');
    
    if (!firstConfirm) {
        return false;
    }
    
    const secondConfirm = confirm('⚠️ FINAL CONFIRMATION ⚠️\n\nThis is your last chance to cancel.\n\nClick OK to permanently delete ALL assignments and reset IDs.\nClick Cancel to abort.');
    
    return secondConfirm;
}

function confirmClearAssignments() {
    const confirmed = document.getElementById('confirmClear').checked;
    
    if (!confirmed) {
        alert('Please check the confirmation box to proceed.');
        return false;
    }
    
    return confirm('This will clear assignment IDs for drivers with completed deliveries.\n\nThis is safe and will help fix the "always busy" status issue.\n\nProceed?');
}

function exportToCSV() {
    const table = document.getElementById('assignmentsTable');
    const rows = table.querySelectorAll('tr');
    
    let csvContent = '';
    
    const headers = Array.from(rows[0].querySelectorAll('th')).slice(0, -1);
    csvContent += headers.map(h => h.textContent).join(',') + '\n';
    
    for (let i = 1; i < rows.length; i++) {
        const cells = Array.from(rows[i].querySelectorAll('td')).slice(0, -1);
        csvContent += cells.map(c => {
            const text = c.title || c.textContent;
            return '"' + text.replace(/"/g, '""') + '"';
        }).join(',') + '\n';
    }
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'dispatcher_assignments_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// WhatsApp Functions
let currentTrackingUrl = '<?php echo $current_tracking_url; ?>';

async function fetchCurrentTrackingUrl() {
    try {
        const response = await fetch('get_tracking_url.php');
        const data = await response.json();
        if (data.success && data.url) {
            currentTrackingUrl = data.url;
            console.log('Updated tracking URL:', currentTrackingUrl);
            return true;
        }
    } catch (error) {
        console.error('Failed to fetch tracking URL:', error);
    }
    return false;
}

async function setupWhatsAppFromTable(driverId, driverName, assignmentId) {
    document.getElementById('selectedDriverId').value = driverId;
    document.getElementById('selectedDriverName').value = driverName;
    
    document.getElementById('assignmentMessage').value = 'Loading assignment details...';
    
    try {
        const response = await fetch(`get_assignment_details.php?assignment_id=${assignmentId}`);
        const data = await response.json();
        
        if (!data.success) {
            alert('Error loading assignment details: ' + (data.error || 'Unknown error'));
            return;
        }
        
        let msg = "";
        
        if (data.client_address && data.client_address.trim() !== '') {
            msg += "CLIENT ADDRESS:\n" + data.client_address + "\n\n";
        }
        
        if (data.order_details && data.order_details.trim() !== '') {
            msg += "ORDER DETAILS:\n" + data.order_details + "\n\n";
        }
        
        if (data.delivery_instructions && data.delivery_instructions.trim() !== '') {
            msg += "DELIVERY INSTRUCTIONS:\n" + data.delivery_instructions + "\n\n";
        }
        
        if (data.client_address && data.client_address.trim() !== '') {
            const link = "https://maps.google.com/maps?q=" + encodeURIComponent(data.client_address);
            msg += "Google Maps Link:\n" + link;
        }
        
        document.getElementById('assignmentMessage').value = msg || "No assignment details available";
        
    } catch (error) {
        console.error('Error fetching assignment details:', error);
        alert('Failed to load assignment details. Please try again.');
        document.getElementById('assignmentMessage').value = 'Error loading details';
        return;
    }
    
    await fetchCurrentTrackingUrl();
    
    let baseUrl = currentTrackingUrl;
    
    const host = window.location.host;
    if (host.includes('localhost') || host.includes('127.0.0.1')) {
        const path = window.location.pathname.replace('dispatcher_assignments.php', '');
        baseUrl = 'https://172.18.208.1' + path;
    }
    
    document.getElementById('baseTrackingUrl').value = baseUrl;
    document.getElementById('driverIdOnly').value = driverId;
    
    const whatsappMessage = createSeparatedWhatsAppMessage(driverName, baseUrl, driverId);
    document.getElementById('whatsappOptimizedMessage').value = whatsappMessage;
    
    document.getElementById('whatsappSectionContent').style.display = 'block';
    document.getElementById('noDriverSelected').style.display = 'none';
    
    document.querySelector('.card.bg-success').scrollIntoView({ 
        behavior: 'smooth',
        block: 'start'
    });
    
    const whatsappSection = document.getElementById('whatsappSectionContent');
    whatsappSection.style.border = '3px solid #25D366';
    setTimeout(() => {
        whatsappSection.style.border = '';
    }, 2000);
}

function createSeparatedWhatsAppMessage(driverName, baseUrl, driverId) {
    let message = `Hi ${driverName}! 👋\n\n`;
    message += `🚚 NEW DELIVERY ASSIGNMENT\n\n`;
    message += `Please follow these steps to start tracking:\n\n`;
    message += `📱 STEP 1: Open Driver Tracker App\n\n`;
    message += `🔗 STEP 2: Copy and paste this URL in "Enter Ngrok URL":\n`;
    message += `${baseUrl}\n\n`;
    message += `🔑 STEP 3: Enter this Driver ID:\n`;
    message += `${driverId}\n\n`;
    message += `✅ STEP 4: Tap "Start Tracking"\n`;
    message += `✅ STEP 5: Allow location permissions\n\n`;
    message += `💡 TIP: Copy the URL and ID separately from this message!\n\n`;
    message += `Thank you! 🙏`;
    return message;
}

function copyAssignment() {
    const textarea = document.getElementById('assignmentMessage');
    textarea.select();
    document.execCommand('copy');
    
    const btn = document.getElementById('copyAssignmentBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    btn.classList.remove('btn-success');
    btn.classList.add('btn-dark');
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.remove('btn-dark');
        btn.classList.add('btn-success');
    }, 2000);
}

function copyBaseUrl() {
    const urlInput = document.getElementById('baseTrackingUrl');
    urlInput.select();
    document.execCommand('copy');
    
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
    button.classList.remove('btn-success');
    button.classList.add('btn-dark');
    
    setTimeout(() => {
        button.innerHTML = originalHtml;
        button.classList.remove('btn-dark');
        button.classList.add('btn-success');
    }, 2000);
}

function copyDriverId() {
    const idInput = document.getElementById('driverIdOnly');
    idInput.select();
    document.execCommand('copy');
    
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
    button.classList.remove('btn-warning');
    button.classList.add('btn-dark');
    
    setTimeout(() => {
        button.innerHTML = originalHtml;
        button.classList.remove('btn-dark');
        button.classList.add('btn-warning');
    }, 2000);
}

function copyOptimizedMessage() {
    const messageArea = document.getElementById('whatsappOptimizedMessage');
    messageArea.select();
    document.execCommand('copy');
    
    alert('✅ Complete WhatsApp message copied!\n\nPaste and send to driver via WhatsApp.');
}

function openWhatsApp() {
    window.open('https://web.whatsapp.com/', '_blank');
}
</script>

</body>
</html>