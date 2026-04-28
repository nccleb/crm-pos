<?php
// ============================================
// CDR UPDATE - Runs every 2 minutes
// Updates extension and status from CDR
// ============================================

$apiUrl = 'https://192.168.22.100:8089/api';
$apiUser = 'cdrapi';
$apiPass = 'cdrapi123';

$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    echo "DB Error: " . mysqli_connect_error() . "\n";
    exit();
}

$curlHandle = curl_init($apiUrl);
$headers = ["Connection: close", "Content-Type: application/json"];
curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10);

// Login to UCM
$fields = json_encode(['request' => ['action' => 'challenge', 'user' => $apiUser]]);
curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $fields);
$response = curl_exec($curlHandle);
$data = json_decode($response, true);
$token = md5(($data['response']['challenge'] ?? '') . $apiPass);

$fields = json_encode(['request' => ['action' => 'login', 'token' => $token, 'user' => $apiUser]]);
curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $fields);
$response = curl_exec($curlHandle);
$data = json_decode($response, true);
$cookie = $data['response']['cookie'] ?? '';

// Get CDR from last 10 minutes
$startTime = date('Y-m-d H:i:s', strtotime('-10 minutes'));
$endTime = date('Y-m-d H:i:s');

$fields = json_encode([
    'request' => [
        'action' => 'cdrapi',
        'cookie' => $cookie,
        'format' => 'json',
        'start_time' => $startTime,
        'end_time' => $endTime
    ]
]);

curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $fields);
$response = curl_exec($curlHandle);
$cdrData = json_decode($response, true);

$updated = 0;

if (isset($cdrData['response']['cdr_root']) && is_array($cdrData['response']['cdr_root'])) {
    foreach ($cdrData['response']['cdr_root'] as $cdr) {
        $src = preg_replace('/[^0-9+]/', '', $cdr['src'] ?? '');
        $dst = $cdr['dst'] ?? '';
        $disposition = $cdr['disposition'] ?? '';
        $billsec = intval($cdr['billsec'] ?? 0);
        $duration = intval($cdr['duration'] ?? 0);
        $calldate = $cdr['calldate'] ?? '';
        
        if (empty($src) || strlen($src) < 4 || empty($calldate)) continue;
        if (preg_match('/DAHDI|SIP|IAX/i', $src)) continue;
        
        // Get agent extension (6001-6099)
        $agentExt = '';
        if (is_numeric($dst)) {
            $dstNum = intval($dst);
            if ($dstNum >= 6001 && $dstNum <= 6099) {
                $agentExt = $dst;
            }
        }
        
        // Determine status
        $status = '';
        if ($disposition === 'ANSWERED' && $billsec > 0) {
            $status = 'answered';
        } elseif ($disposition === 'NO ANSWER' || $disposition === 'FAILED' || $disposition === 'BUSY') {
            $status = 'missed';
        }
        
        if (empty($status)) continue;
        
        // Find matching call in database (within ±30 seconds)
        $check_query = "SELECT id, call_status, line_number FROM call_history 
                        WHERE phone_number = ? 
                        AND ABS(TIMESTAMPDIFF(SECOND, call_time, ?)) <= 30
                        ORDER BY call_time DESC
                        LIMIT 1";
        $check_stmt = mysqli_prepare($idr, $check_query);
        $call_time = date('Y-m-d H:i:s', strtotime($calldate));
        mysqli_stmt_bind_param($check_stmt, "ss", $src, $call_time);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $existing = mysqli_fetch_assoc($result);
        mysqli_stmt_close($check_stmt);
        
        if ($existing && $existing['call_status'] === 'ringing') {
            // Update with CDR data
            if ($status === 'answered') {
                $answered_time = date('Y-m-d H:i:s', strtotime($calldate) + $duration - $billsec);
                $update_query = "UPDATE call_history 
                                SET call_status = 'answered', 
                                    line_number = ?,
                                    answered_time = ?,
                                    channel_state = 'answered'
                                WHERE id = ?";
                $update_stmt = mysqli_prepare($idr, $update_query);
                mysqli_stmt_bind_param($update_stmt, "ssi", $agentExt, $answered_time, $existing['id']);
            } else {
                $update_query = "UPDATE call_history 
                                SET call_status = 'missed',
                                    line_number = ?,
                                    channel_state = 'not_answered'
                                WHERE id = ?";
                $update_stmt = mysqli_prepare($idr, $update_query);
                mysqli_stmt_bind_param($update_stmt, "si", $agentExt, $existing['id']);
            }
            
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
            $updated++;
            
            echo "Updated: {$src} -> {$agentExt} [{$status}]\n";
        }
    }
}

echo "Complete: {$updated} calls updated at " . date('H:i:s') . "\n";

mysqli_close($idr);
curl_close($curlHandle);
?>