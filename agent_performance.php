<?php
session_start();

// Database connection
$idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if (!$idr) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get filter parameters
$start_date = $_POST['sta'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_POST['end'] ?? date('Y-m-d');
$agent = $_POST['age'] ?? '';
$status_filter = $_POST['con'] ?? ''; // 'yes' = answered, 'no' = missed, '' = all

// Build query
$query = "SELECT 
    line_number as agent,
    COUNT(*) as total_calls,
    SUM(CASE WHEN call_status = 'answered' THEN 1 ELSE 0 END) as answered_calls,
    SUM(CASE WHEN call_status = 'missed' THEN 1 ELSE 0 END) as missed_calls,
    SUM(CASE WHEN call_status = 'ringing' THEN 1 ELSE 0 END) as ringing_calls,
    ROUND(SUM(CASE WHEN call_status = 'answered' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as answer_rate
FROM call_history 
WHERE call_time >= ? AND call_time <= DATE_ADD(?, INTERVAL 1 DAY)";

$params = [$start_date, $end_date];
$types = "ss";

// Add agent filter
if (!empty($agent)) {
    $query .= " AND line_number LIKE ?";
    $params[] = "%{$agent}%";
    $types .= "s";
}

// Add status filter
if ($status_filter === 'yes') {
    $query .= " AND call_status = 'answered'";
} elseif ($status_filter === 'no') {
    $query .= " AND call_status = 'missed'";
}

$query .= " GROUP BY line_number 
            HAVING line_number IS NOT NULL AND line_number != ''
            ORDER BY total_calls DESC";

$stmt = mysqli_prepare($idr, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Performance Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .date-range {
            color: #666;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-label {
            color: #888;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-value {
            color: #333;
            font-size: 32px;
            font-weight: bold;
        }

        .table-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tbody tr:hover {
            background: #f8f9ff;
        }

        .agent-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .answered {
            color: #10b981;
            font-weight: 600;
        }

        .missed {
            color: #ef4444;
            font-weight: 600;
        }

        .ringing {
            color: #f59e0b;
            font-weight: 600;
        }

        .rate-good {
            color: #10b981;
        }

        .rate-medium {
            color: #f59e0b;
        }

        .rate-poor {
            color: #ef4444;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.3s ease;
        }

        .btn-back {
            display: inline-block;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-data svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Agent Performance Report</h1>
            <p class="date-range">
                <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>
                <?php if (!empty($agent)) echo " | Agent: {$agent}"; ?>
                <?php if ($status_filter === 'yes') echo " | Filter: Answered Only"; ?>
                <?php if ($status_filter === 'no') echo " | Filter: Missed Only"; ?>
            </p>
        </div>

        <?php
        // Calculate totals
        mysqli_data_seek($result, 0);
        $total_all_calls = 0;
        $total_all_answered = 0;
        $total_all_missed = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $total_all_calls += $row['total_calls'];
            $total_all_answered += $row['answered_calls'];
            $total_all_missed += $row['missed_calls'];
        }
        mysqli_data_seek($result, 0);
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Calls</div>
                <div class="stat-value"><?php echo number_format($total_all_calls); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Answered</div>
                <div class="stat-value answered"><?php echo number_format($total_all_answered); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Missed</div>
                <div class="stat-value missed"><?php echo number_format($total_all_missed); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Answer Rate</div>
                <div class="stat-value">
                    <?php 
                    $overall_rate = $total_all_calls > 0 ? round($total_all_answered * 100 / $total_all_calls, 1) : 0;
                    echo $overall_rate; 
                    ?>%
                </div>
            </div>
        </div>

        <div class="table-container">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Total Calls</th>
                            <th>Answered</th>
                            <th>Missed</th>
                            <th>Ringing</th>
                            <th>Answer Rate</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $rate = floatval($row['answer_rate']);
                            $rate_class = $rate >= 80 ? 'rate-good' : ($rate >= 60 ? 'rate-medium' : 'rate-poor');
                            ?>
                            <tr>
                                <td>
                                    <span class="agent-badge">Agent <?php echo htmlspecialchars($row['agent']); ?></span>
                                </td>
                                <td><strong><?php echo number_format($row['total_calls']); ?></strong></td>
                                <td class="answered"><?php echo number_format($row['answered_calls']); ?></td>
                                <td class="missed"><?php echo number_format($row['missed_calls']); ?></td>
                                <td class="ringing"><?php echo number_format($row['ringing_calls']); ?></td>
                                <td class="<?php echo $rate_class; ?>">
                                    <strong><?php echo $row['answer_rate']; ?>%</strong>
                                </td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $row['answer_rate']; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <h2>No Data Found</h2>
                    <p>No calls found for the selected criteria.</p>
                </div>
            <?php endif; ?>
        </div>

        <a href="test476.php" class="btn-back">← Back to Search</a>
    </div>
</body>
</html>
<?php
mysqli_stmt_close($stmt);
mysqli_close($idr);
?>