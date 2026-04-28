<?php
// test204.php - Standalone Location Data Cleanup Utility
session_start();

$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

// Handle cleanup actions
if ($_POST) {
    if (isset($_POST['manual_cleanup'])) {
        $days = intval($_POST['cleanup_days']);
        $confirm = isset($_POST['confirm_manual']);
        
        if ($confirm && $days >= 1 && $days <= 365) {
            $deleted = performManualCleanup($idr, $days);
            if ($deleted !== false) {
                $success_msg = "Manually deleted {$deleted} location records older than {$days} days.";
            } else {
                $error_msg = "Error during manual cleanup.";
            }
        }
    }
    
    if (isset($_POST['emergency_cleanup'])) {
        $confirm = isset($_POST['confirm_emergency']);
        if ($confirm) {
            $deleted = performEmergencyCleanup($idr);
            $success_msg = "Emergency cleanup completed. Deleted {$deleted} records, keeping only last 24 hours.";
        }
    }
    
    if (isset($_POST['analyze_cleanup'])) {
        $days = intval($_POST['analyze_days']);
        $analysis = analyzeCleanupImpact($idr, $days);
    }
}

// Functions
function performManualCleanup($idr, $days) {
    $stmt = $idr->prepare("DELETE FROM driver_locations WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $days);
    
    if ($stmt->execute()) {
        $deleted = $stmt->affected_rows;
        $stmt->close();
        
        // Optimize table after cleanup
        mysqli_query($idr, "OPTIMIZE TABLE driver_locations");
        
        return $deleted;
    }
    
    $stmt->close();
    return false;
}

function performEmergencyCleanup($idr) {
    // Keep only last 24 hours
    $result = mysqli_query($idr, "DELETE FROM driver_locations WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $deleted = mysqli_affected_rows($idr);
    
    // Optimize table
    mysqli_query($idr, "OPTIMIZE TABLE driver_locations");
    
    return $deleted;
}

function analyzeCleanupImpact($idr, $days) {
    $analysis = [];
    
    // Records to be deleted
    $stmt = $idr->prepare("SELECT COUNT(*) as count FROM driver_locations WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $result = $stmt->get_result();
    $analysis['to_delete'] = $result->fetch_assoc()['count'];
    $stmt->close();
    
    // Records to keep
    $stmt = $idr->prepare("SELECT COUNT(*) as count FROM driver_locations WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $result = $stmt->get_result();
    $analysis['to_keep'] = $result->fetch_assoc()['count'];
    $stmt->close();
    
    // Date range of records to delete
    $stmt = $idr->prepare("SELECT MIN(created_at) as oldest, MAX(created_at) as newest FROM driver_locations WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $result = $stmt->get_result();
    $date_range = $result->fetch_assoc();
    $analysis['oldest_to_delete'] = $date_range['oldest'];
    $analysis['newest_to_delete'] = $date_range['newest'];
    $stmt->close();
    
    return $analysis;
}

function getDetailedStats($idr) {
    $stats = [];
    
    // Total count and size
    $result = mysqli_query($idr, "SELECT 
        COUNT(*) as total,
        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
        FROM information_schema.tables t
        CROSS JOIN driver_locations dl
        WHERE t.table_schema = 'nccleb_test' AND t.table_name = 'driver_locations'
        GROUP BY t.table_name");
    $stats['total'] = mysqli_fetch_assoc($result);
    
    // By time periods
    $result = mysqli_query($idr, "SELECT 
        'Last 1 Day' as period,
        COUNT(*) as count,
        MIN(created_at) as oldest,
        MAX(created_at) as newest
        FROM driver_locations 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        
        UNION ALL
        
        SELECT 
        'Last 7 Days' as period,
        COUNT(*) as count,
        MIN(created_at) as oldest,
        MAX(created_at) as newest
        FROM driver_locations 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION ALL
        
        SELECT 
        'Last 30 Days' as period,
        COUNT(*) as count,
        MIN(created_at) as oldest,
        MAX(created_at) as newest
        FROM driver_locations 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        
        UNION ALL
        
        SELECT 
        'Older than 30 Days' as period,
        COUNT(*) as count,
        MIN(created_at) as oldest,
        MAX(created_at) as newest
        FROM driver_locations 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
    $stats['periods'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['periods'][] = $row;
    }
    
    // Most active drivers (last 7 days)
    $result = mysqli_query($idr, "SELECT 
        dl.driver_id,
        d.name_d,
        COUNT(*) as location_updates,
        MIN(dl.created_at) as first_update,
        MAX(dl.created_at) as last_update
        FROM driver_locations dl
        LEFT JOIN drivers d ON dl.driver_id = d.idx
        WHERE dl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY dl.driver_id, d.name_d
        ORDER BY location_updates DESC
        LIMIT 10");
    
    $stats['active_drivers'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['active_drivers'][] = $row;
    }
    
    return $stats;
}

$detailed_stats = getDetailedStats($idr);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Data Cleanup Utility</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        .cleanup-section {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        .emergency-section {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 15px 0;
        }
        .analysis-section {
            background-color: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>📍 Location Data Cleanup Utility</h1>
        <a href="dispatcher_assignments.php" class="btn btn-secondary">Back to Assignments</a>
    </div>
    
    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Current Statistics -->
    <div class="card stat-card mb-4">
        <div class="card-header">
            <h4>Current Location Data Overview</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h2><?php echo number_format($detailed_stats['total']['total'] ?? 0); ?></h2>
                    <p>Total Location Records</p>
                </div>
                <div class="col-md-6">
                    <h2><?php echo $detailed_stats['total']['size_mb'] ?? 0; ?> MB</h2>
                    <p>Database Storage Used</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Breakdown -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Location Records by Time Period</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Time Period</th>
                            <th>Record Count</th>
                            <th>Percentage</th>
                            <th>Date Range</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_records = $detailed_stats['total']['total'] ?? 1;
                        foreach ($detailed_stats['periods'] as $period): 
                            $percentage = round(($period['count'] / $total_records) * 100, 1);
                            $row_class = '';
                            if ($period['period'] === 'Older than 30 Days' && $percentage > 50) {
                                $row_class = 'table-warning';
                            }
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo $period['period']; ?></td>
                            <td><?php echo number_format($period['count']); ?></td>
                            <td><?php echo $percentage; ?>%</td>
                            <td>
                                <?php if ($period['oldest']): ?>
                                    <?php echo date('M j, Y', strtotime($period['oldest'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($period['newest'])); ?>
                                <?php else: ?>
                                    No records
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Most Active Drivers -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Most Active Drivers (Last 7 Days)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>Location Updates</th>
                            <th>First Update</th>
                            <th>Last Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailed_stats['active_drivers'] as $driver): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($driver['name_d'] ?? 'Unknown'); ?></td>
                            <td><?php echo number_format($driver['location_updates']); ?></td>
                            <td><?php echo date('M j, g:i A', strtotime($driver['first_update'])); ?></td>
                            <td><?php echo date('M j, g:i A', strtotime($driver['last_update'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Cleanup Impact Analysis -->
    <div class="analysis-section">
        <h5>Cleanup Impact Analysis</h5>
        <form method="POST">
            <div class="row">
                <div class="col-md-3">
                    <label>Analyze cleanup for days older than:</label>
                    <input type="number" name="analyze_days" value="30" min="1" max="365" class="form-control">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" name="analyze_cleanup" class="btn btn-info">Analyze Impact</button>
                </div>
            </div>
        </form>
        
        <?php if (isset($analysis)): ?>
        <div class="mt-3">
            <h6>Analysis Results:</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="alert alert-danger">
                        <strong>Will Delete:</strong><br>
                        <?php echo number_format($analysis['to_delete']); ?