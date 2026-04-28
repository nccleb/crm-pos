<?php
session_start();

/**
 * Agent Performance Dashboard
 * Single file with hardcoded API credentials
 */

// ========================================
// API Configuration - UPDATE THESE VALUES
// ========================================
$API_URL  = 'https://192.168.22.100:8089/api';
$API_USER = 'cdrapi';
$API_PASS = 'cdrapi123';
// ========================================

// Check if this is a form submission
$isSubmitted = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isSubmitted) {
    // Get parameters from POST
    $queueName = $_POST['queue'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $agentFilter = $_POST['agent_filter'] ?? '';
    $statusFilter = $_POST['status_filter'] ?? '';
    
    // Validate inputs
    if (empty($queueName) || empty($startDate) || empty($endDate)) {
        $error = "Queue and dates are required!";
        $isSubmitted = false;
    } else {
        
        /**
         * Fetch queue statistics from UCM6202 API
         */
        function getQueueStatistics($apiUrl, $apiUser, $apiPass, $startDate, $endDate, $queueName, $agentName = '') {
            $curlHandle = curl_init($apiUrl);
            $headers = ["Connection: close", "Content-Type: application/json"];
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

            // Challenge
            $challengeRequest = json_encode([
                'request' => ['action' => 'challenge', 'user' => $apiUser]
            ]);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $challengeRequest);
            $challengeResponse = curl_exec($curlHandle);
            $challengeData = json_decode($challengeResponse, true);
            $challenge = $challengeData['response']['challenge'] ?? '';

            if (empty($challenge)) {
                curl_close($curlHandle);
                return ['error' => 'Challenge failed - Check API URL and Username'];
            }

            // Login
            $token = md5($challenge . $apiPass);
            $loginRequest = json_encode([
                'request' => ['action' => 'login', 'token' => $token, 'user' => $apiUser]
            ]);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $loginRequest);
            $loginResponse = curl_exec($curlHandle);
            $loginData = json_decode($loginResponse, true);
            $cookie = $loginData['response']['cookie'] ?? '';

            if (empty($cookie)) {
                curl_close($curlHandle);
                return ['error' => 'Authentication failed - Check API Password'];
            }

            // Queue API request
            $queueRequestArray = [
                'request' => [
                    'action' => 'queueapi',
                    'endTime' => $endDate,
                    'startTime' => $startDate,
                    'queue' => $queueName,
                    'format' => 'json',
                    'statisticsType' => 'calldetail',
                    'cookie' => $cookie
                ]
            ];

            if (!empty($agentName)) {
                $queueRequestArray['request']['agent'] = $agentName;
            }

            $queueRequest = json_encode($queueRequestArray);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $queueRequest);
            $queueResponse = curl_exec($curlHandle);
            $queueData = json_decode($queueResponse);

            curl_close($curlHandle);

            if (empty($queueData->queue_statistics)) {
                return ['error' => 'No data available for this queue/date range'];
            }

            return ['success' => true, 'data' => $queueData->queue_statistics];
        }
        
        // Fetch data using hardcoded API credentials
        $apiResult = getQueueStatistics($API_URL, $API_USER, $API_PASS, $startDate, $endDate, $queueName, $agentFilter);
        
        if (isset($apiResult['error'])) {
            $error = $apiResult['error'];
            $isSubmitted = false;
        } else {
            $rawData = $apiResult['data'];
            
            // Process agent statistics
            $agentStats = [];
            
            foreach ($rawData as $stat) {
                if (!isset($stat->agent)) continue;

                $agentName = $stat->agent->agent ?? 'Unknown';
                $extension = $stat->agent->extension ?? 'N/A';
                $connect = $stat->agent->connect ?? 'no';
                $waitTime = intval($stat->agent->wait_time ?? 0);
                $talkTime = intval($stat->agent->talk_time ?? 0);

                // Skip NONE
                if ($extension === 'NONE' || $agentName === 'NONE') continue;

                // Apply status filter
                if ($statusFilter === 'answered' && $connect !== 'yes') continue;
                if ($statusFilter === 'missed' && $connect !== 'no') continue;

                // Initialize
                if (!isset($agentStats[$agentName])) {
                    $agentStats[$agentName] = [
                        'agent' => $agentName,
                        'extension' => $extension,
                        'total_calls' => 0,
                        'answered_calls' => 0,
                        'missed_calls' => 0,
                        'total_wait_time' => 0,
                        'total_talk_time' => 0,
                    ];
                }

                // Aggregate
                $agentStats[$agentName]['total_calls']++;
                
                if ($connect === 'yes') {
                    $agentStats[$agentName]['answered_calls']++;
                    $agentStats[$agentName]['total_talk_time'] += $talkTime;
                } else {
                    $agentStats[$agentName]['missed_calls']++;
                }
                
                $agentStats[$agentName]['total_wait_time'] += $waitTime;
            }

            // Calculate rates
            foreach ($agentStats as $agent => &$stats) {
                $stats['answer_rate'] = $stats['total_calls'] > 0 
                    ? round(($stats['answered_calls'] / $stats['total_calls']) * 100, 1) 
                    : 0;
                
                $stats['avg_wait_time'] = $stats['total_calls'] > 0 
                    ? round($stats['total_wait_time'] / $stats['total_calls'], 1) 
                    : 0;
                
                $stats['avg_talk_time'] = $stats['answered_calls'] > 0 
                    ? round($stats['total_talk_time'] / $stats['answered_calls'], 1) 
                    : 0;
            }
            unset($stats);

            // Sort
            usort($agentStats, function($a, $b) {
                return $b['total_calls'] - $a['total_calls'];
            });

            // Totals
            $total_all_calls = array_sum(array_column($agentStats, 'total_calls'));
            $total_all_answered = array_sum(array_column($agentStats, 'answered_calls'));
            $total_all_missed = array_sum(array_column($agentStats, 'missed_calls'));
            $overall_rate = $total_all_calls > 0 ? round(($total_all_answered / $total_all_calls) * 100, 1) : 0;
            $overall_avg_wait = $total_all_calls > 0 
                ? round(array_sum(array_column($agentStats, 'total_wait_time')) / $total_all_calls, 1) 
                : 0;
            $overall_avg_talk = $total_all_answered > 0 
                ? round(array_sum(array_column($agentStats, 'total_talk_time')) / $total_all_answered, 1) 
                : 0;

            // Chart data
            $agents = array_column($agentStats, 'agent');
            $totals = array_column($agentStats, 'total_calls');
            $answered = array_column($agentStats, 'answered_calls');
            $missed = array_column($agentStats, 'missed_calls');
            $rates = array_column($agentStats, 'answer_rate');
            
            // CSV Export
            if (isset($_POST['export_csv'])) {
                $filename = "agent_performance_" . date('Y-m-d_His') . ".csv";
                
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                
                fputcsv($output, ['Agent Performance Report']);
                fputcsv($output, ['Period:', $startDate . ' to ' . $endDate]);
                fputcsv($output, ['Queue:', $queueName]);
                fputcsv($output, ['Generated:', date('Y-m-d H:i:s')]);
                fputcsv($output, []);
                
                fputcsv($output, [
                    'Agent', 'Extension', 'Total Calls', 'Answered', 'Missed',
                    'Answer Rate %', 'Avg Wait Time (s)', 'Avg Talk Time (s)'
                ]);
                
                foreach ($agentStats as $stats) {
                    fputcsv($output, [
                        $stats['agent'], $stats['extension'], $stats['total_calls'],
                        $stats['answered_calls'], $stats['missed_calls'], $stats['answer_rate'],
                        $stats['avg_wait_time'], $stats['avg_talk_time']
                    ]);
                }
                
                fclose($output);
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Performance Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-card h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group { display: flex; flex-direction: column; }
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .required::after {
            content: " *";
            color: #ef4444;
        }
        .form-group input, .form-group select {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .help-text {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }
        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }
        .btn-full {
            grid-column: 1 / -1;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .stat-card .label {
            color: #666;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }
        .stat-card .subvalue {
            font-size: 14px;
            color: #888;
            margin-top: 5px;
        }
        .stat-card.green { border-left: 4px solid #10b981; }
        .stat-card.red { border-left: 4px solid #ef4444; }
        .stat-card.blue { border-left: 4px solid #3b82f6; }
        .stat-card.purple { border-left: 4px solid #8b5cf6; }
        .card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card h2 { color: #333; font-size: 20px; margin-bottom: 20px; }
        .chart-container { position: relative; height: 300px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
        }
        tbody tr:hover { background: #f9fafb; }
        .rate-good { color: #10b981; font-weight: 600; }
        .rate-medium { color: #f59e0b; font-weight: 600; }
        .rate-poor { color: #ef4444; font-weight: 600; }
        .no-data { text-align: center; padding: 40px; color: #6b7280; }
        @media (max-width: 768px) {
            .button-group { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Search Form -->
        <div class="form-card">
            <h1><i class="fas fa-chart-line"></i> Agent Performance Dashboard</h1>

            <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="required"><i class="fas fa-calendar"></i> Start Date</label>
                        <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate ?? date('Y-m-d', strtotime('-7 days'))); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="required"><i class="fas fa-calendar"></i> End Date</label>
                        <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate ?? date('Y-m-d')); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="required"><i class="fas fa-phone-volume"></i> Queue</label>
                        <input type="text" name="queue" value="<?php echo htmlspecialchars($queueName ?? '6500'); ?>" placeholder="6500" required>
                        <div class="help-text">Queue extension number</div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Agent (Optional)</label>
                        <input type="text" name="agent_filter" value="<?php echo htmlspecialchars($agentFilter ?? ''); ?>" placeholder="Leave blank for all">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-filter"></i> Status</label>
                        <select name="status_filter">
                            <option value="">All Calls</option>
                            <option value="answered" <?php echo ($statusFilter ?? '') === 'answered' ? 'selected' : ''; ?>>Answered Only</option>
                            <option value="missed" <?php echo ($statusFilter ?? '') === 'missed' ? 'selected' : ''; ?>>Missed Only</option>
                        </select>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-search"></i> Generate Report
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="location.reload()">
                        <i class="fas fa-redo"></i> Reload
                    </button>
                    <button type="button" class="btn btn-danger" onclick="quit()">
                        <i class="fas fa-sign-out-alt"></i> Quit
                    </button>
                </div>
            </form>
        </div>

        <?php if ($isSubmitted && !empty($agentStats)): ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="label"><i class="fas fa-phone"></i> Total Calls</div>
                <div class="value"><?php echo number_format($total_all_calls); ?></div>
                <div class="subvalue"><?php echo count($agentStats); ?> agents active</div>
            </div>
            <div class="stat-card green">
                <div class="label"><i class="fas fa-check-circle"></i> Answered</div>
                <div class="value"><?php echo number_format($total_all_answered); ?></div>
                <div class="subvalue"><?php echo $overall_rate; ?>% answer rate</div>
            </div>
            <div class="stat-card red">
                <div class="label"><i class="fas fa-phone-slash"></i> Missed</div>
                <div class="value"><?php echo number_format($total_all_missed); ?></div>
                <div class="subvalue"><?php echo round(100 - $overall_rate, 1); ?>% miss rate</div>
            </div>
            <div class="stat-card purple">
                <div class="label"><i class="fas fa-clock"></i> Avg Times</div>
                <div class="value"><?php echo $overall_avg_talk; ?>s</div>
                <div class="subvalue">Wait: <?php echo $overall_avg_wait; ?>s | Talk: <?php echo $overall_avg_talk; ?>s</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="card">
            <h2><i class="fas fa-chart-bar"></i> Call Volume by Agent</h2>
            <div class="chart-container">
                <canvas id="callVolumeChart"></canvas>
            </div>
        </div>

        <div class="card">
            <h2><i class="fas fa-percentage"></i> Answer Rate by Agent</h2>
            <div class="chart-container">
                <canvas id="answerRateChart"></canvas>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-table"></i> Agent Statistics</h2>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="queue" value="<?php echo htmlspecialchars($queueName); ?>">
                    <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                    <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                    <input type="hidden" name="agent_filter" value="<?php echo htmlspecialchars($agentFilter); ?>">
                    <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($statusFilter); ?>">
                    <button type="submit" name="export_csv" value="1" class="btn btn-success">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Agent</th>
                        <th><i class="fas fa-phone-alt"></i> Extension</th>
                        <th><i class="fas fa-phone"></i> Total</th>
                        <th><i class="fas fa-check"></i> Answered</th>
                        <th><i class="fas fa-times"></i> Missed</th>
                        <th><i class="fas fa-percentage"></i> Rate</th>
                        <th><i class="fas fa-hourglass-half"></i> Wait</th>
                        <th><i class="fas fa-comments"></i> Talk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agentStats as $stats): 
                        $rateClass = $stats['answer_rate'] >= 80 ? 'rate-good' : 
                                    ($stats['answer_rate'] >= 60 ? 'rate-medium' : 'rate-poor');
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($stats['agent']); ?></strong></td>
                        <td><?php echo htmlspecialchars($stats['extension']); ?></td>
                        <td><?php echo number_format($stats['total_calls']); ?></td>
                        <td style="color: #10b981;"><?php echo number_format($stats['answered_calls']); ?></td>
                        <td style="color: #ef4444;"><?php echo number_format($stats['missed_calls']); ?></td>
                        <td class="<?php echo $rateClass; ?>"><?php echo $stats['answer_rate']; ?>%</td>
                        <td><?php echo number_format($stats['avg_wait_time'], 1); ?>s</td>
                        <td><?php echo number_format($stats['avg_talk_time'], 1); ?>s</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f9fafb; font-weight: 600;">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <td><?php echo number_format($total_all_calls); ?></td>
                        <td style="color: #10b981;"><?php echo number_format($total_all_answered); ?></td>
                        <td style="color: #ef4444;"><?php echo number_format($total_all_missed); ?></td>
                        <td class="rate-good"><?php echo $overall_rate; ?>%</td>
                        <td><?php echo number_format($overall_avg_wait, 1); ?>s</td>
                        <td><?php echo number_format($overall_avg_talk, 1); ?>s</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <script>
            const callVolumeCtx = document.getElementById('callVolumeChart').getContext('2d');
            new Chart(callVolumeCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($agents); ?>,
                    datasets: [
                        {
                            label: 'Answered',
                            data: <?php echo json_encode($answered); ?>,
                            backgroundColor: '#10b981'
                        },
                        {
                            label: 'Missed',
                            data: <?php echo json_encode($missed); ?>,
                            backgroundColor: '#ef4444'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true }
                    }
                }
            });

            const answerRateCtx = document.getElementById('answerRateChart').getContext('2d');
            new Chart(answerRateCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($agents); ?>,
                    datasets: [{
                        label: 'Answer Rate %',
                        data: <?php echo json_encode($rates); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });
        </script>

        <?php elseif ($isSubmitted && empty($agentStats)): ?>
        <div class="card">
            <div class="no-data">
                <i class="fas fa-inbox fa-3x" style="color: #d1d5db; margin-bottom: 15px;"></i>
                <h3>No Data Available</h3>
                <p>No agent statistics found for the selected criteria.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function quit() {
            window.location.replace("test204.php?page=<?php echo $_SESSION["oop"] ?? ''; ?>&page1=<?php echo $_SESSION["ooq"] ?? ''; ?>");
        }
    </script>
</body>
</html>