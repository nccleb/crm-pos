<?php

// ==================== SETTINGS MANAGER START ====================
$settingsFile = __DIR__ . '/call_history_settings.json';

$defaultSettings = [
    'company_name' => 'Your Company Name',
    'company_address' => 'Your Address',
    'company_phone' => 'Your Phone',
    'company_email' => 'Your Email',
    'logo_path' => 'logo.png',
    'report_title' => 'Call History Report',
    'report_subtitle' => 'Comprehensive Call Analytics & Details',
    'footer_text' => 'Generated from Call History System',
    'use_logo' => true,
    'logo_width' => 150,
    'recording_path' => 'c:/rec/'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $newSettings = [
        'company_name' => $_POST['company_name'] ?? $defaultSettings['company_name'],
        'company_address' => $_POST['company_address'] ?? $defaultSettings['company_address'],
        'company_phone' => $_POST['company_phone'] ?? $defaultSettings['company_phone'],
        'company_email' => $_POST['company_email'] ?? $defaultSettings['company_email'],
        'logo_path' => $_POST['logo_path'] ?? $defaultSettings['logo_path'],
        'report_title' => $_POST['report_title'] ?? $defaultSettings['report_title'],
        'report_subtitle' => $_POST['report_subtitle'] ?? $defaultSettings['report_subtitle'],
        'footer_text' => $_POST['footer_text'] ?? $defaultSettings['footer_text'],
        'use_logo' => isset($_POST['use_logo']),
        'logo_width' => intval($_POST['logo_width'] ?? $defaultSettings['logo_width']),
        'recording_path' => $_POST['recording_path'] ?? $defaultSettings['recording_path']
    ];
    
    file_put_contents($settingsFile, json_encode($newSettings, JSON_PRETTY_PRINT));
    $settingsMessage = "Settings saved successfully!";
}

if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
    if (!$settings) $settings = $defaultSettings;
} else {
    $settings = $defaultSettings;
}

$settingsJson = json_encode($settings);
// ==================== SETTINGS MANAGER END ====================

session_start();

/**
 * Call History - Enhanced Version with Inbound + Outbound Calls
 * Properly handles UCM CDR structure with nested records
 */

// Performance optimization
ini_set('memory_limit', '512M');
set_time_limit(300);
ini_set('max_execution_time', 300);

// API Configuration
$apiUrl = $_SESSION['ucm_api_url'] ?? 'https://192.168.22.100:8089/api';
$apiUser = $_SESSION['ucm_api_user'] ?? 'cdrapi';
$apiPass = $_SESSION['ucm_api_pass'] ?? 'cdrapi123';
$defaultQueue = $_SESSION['default_queue'] ?? '6500';

// Super User Configuration - Using same logic as navbar.php
// Check multiple sources for the "super" value
if (!isset($nam)) {
    // Try GET parameter first (from URL: ?page=super)
    $nam = $_GET['page'] ?? '';
    // If empty, try session variable
    if (empty($nam)) {
        $nam = $_SESSION['oop'] ?? '';
    }
}

$isSuperUser = (isset($nam) && $nam == "super");

// TEMPORARY DEBUG - REMOVE AFTER TESTING
$debugMode = false; // Set to false after fixing
if ($debugMode) {
    $debugInfo = [
        'nam_variable' => $nam ?? 'NOT SET',
        'get_page' => $_GET['page'] ?? 'NOT SET',
        'session_oop' => $_SESSION['oop'] ?? 'NOT SET',
        'isSuperUser' => $isSuperUser ? 'YES ✓' : 'NO ✗',
    ];
}
// END DEBUG

// Recording Configuration
$recordingPath = $settings['recording_path'] ?? 'c:/rec/'; // Path to recordings folder — set in Settings
// Normalize: forward slashes, trailing slash
$recordingPath = rtrim(str_replace('\\', '/', $recordingPath), '/') . '/';
$recordingExtensions = ['wav', 'mp3', 'gsm', 'WAV', 'MP3', 'GSM']; // Supported formats

/**
 * Handle recording deletion (super user only)
 */
if (isset($_POST['delete_recording']) && $isSuperUser) {
    $fileToDelete = $_POST['delete_recording'];
    
    // Security: Prevent directory traversal
    $fileToDelete = str_replace(['../', '..\\'], ['', ''], $fileToDelete);
    $fileToDelete = str_replace('\\', '/', $fileToDelete);
    $fileToDelete = ltrim($fileToDelete, '/');
    
    // Build full path
    $fullPath = rtrim($recordingPath, '/\\') . '/' . $fileToDelete;
    
    // Try Windows path format too
    if (!file_exists($fullPath)) {
        $fullPath = str_replace('/', '\\', $fullPath);
    }
    
    // Verify file exists and is within recording directory
    if (file_exists($fullPath)) {
        $realPath = realpath($fullPath);
        $realRecordingPath = realpath($recordingPath);
        
        // Security check: ensure file is within recording directory
        if ($realPath && $realRecordingPath && strpos($realPath, $realRecordingPath) === 0) {
            if (unlink($fullPath)) {
                $deleteMessage = "Recording deleted successfully.";
                $deleteSuccess = true;
            } else {
                $deleteMessage = "Failed to delete recording.";
                $deleteSuccess = false;
            }
        } else {
            $deleteMessage = "Access denied.";
            $deleteSuccess = false;
        }
    } else {
        $deleteMessage = "Recording not found.";
        $deleteSuccess = false;
    }
}

/**
 * Find recording file for a call.
 *
 * FORMAT A (queue recordings): q[queue]-[caller]-[YYYYMMDD]-[HHMMSS]-[uniqueid]-[ext].wav
 *   Example: q6500-103-20260404-105835-1775293107.25-105.wav
 *   Datetime is local time — no UTC offset needed.
 *
 * FORMAT B (legacy auto):  auto-[unix_ts]-[ext]-[caller].wav  /  auto-[unix_ts]-[caller]-[ext].wav
 *   Timestamp is UTC — add 7200s for Lebanon (UTC+2).
 */
function findRecording($callTime, $extension, $phoneNumber, $recordingPath, $extensions) {
    if (empty($callTime) || empty($extension)) return null;

    $callTimestamp = strtotime($callTime);
    if (!$callTimestamp) return null;

    $recordingPath = rtrim(str_replace('\\', '/', $recordingPath), '/') . '/';
    if (!is_dir($recordingPath) || !is_readable($recordingPath)) return null;

    $normalizeNum = function($n) {
        $n = preg_replace('/[\s\-\(\)]/', '', (string)$n);
        $n = ltrim($n, '+');
        if (substr($n, 0, 2) === '00') $n = substr($n, 2);
        return $n;
    };

    $normPhone = $normalizeNum($phoneNumber);
    $normExt   = trim((string)$extension);

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($recordingPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $fn      = $file->getFilename();
            $fileExt = strtolower($file->getExtension());
            if (!in_array($fileExt, array_map('strtolower', $extensions))) continue;

            $matched = false; $fileTimestamp = 0;

            // FORMAT A: q[queue]-[caller]-[YYYYMMDD]-[HHMMSS]-[uniqueid]-[ext].wav
            if (preg_match('/^q\d+-(.+?)-(\d{8})-(\d{6})-[\d.]+-(\d+)\.\w+$/i', $fn, $m)) {
                $fCaller = $normalizeNum($m[1]);
                $dtStr   = substr($m[2],0,4).'-'.substr($m[2],4,2).'-'.substr($m[2],6,2)
                          .' '.substr($m[3],0,2).':'.substr($m[3],2,2).':'.substr($m[3],4,2);
                $fileTimestamp = strtotime($dtStr); // already local time
                $fExt    = $m[4];
                if ($fExt === $normExt && (empty($normPhone) || $fCaller === $normPhone)) $matched = true;
                if (!$matched && $normalizeNum($fExt) === $normExt && (empty($normPhone) || $fCaller === $normPhone)) $matched = true;
            }

            // FORMAT B: auto-[unix_ts]-[f2]-[f3].wav
            if (!$matched && preg_match('/^auto-(\d+)-([^-]+)-([^.]+)\.\w+$/i', $fn, $m)) {
                $fileTimestamp = (int)$m[1] + 7200;
                $nF2 = $normalizeNum($m[2]); $nF3 = $normalizeNum($m[3]);
                if ($nF2 === $normExt && (empty($normPhone) || $nF3 === $normPhone)) $matched = true;
                if (!$matched && $nF3 === $normExt && (empty($normPhone) || $nF2 === $normPhone)) $matched = true;
            }

            if ($matched && $fileTimestamp && abs($fileTimestamp - $callTimestamp) <= 120) {
                $fullPath = str_replace('\\', '/', $file->getPathname());
                $basePath = rtrim(str_replace('\\', '/', $recordingPath), '/');
                return ltrim(str_replace($basePath, '', $fullPath), '/');
            }
        }
    } catch (Exception $e) {
        error_log('findRecording error: ' . $e->getMessage());
    }
    return null;
}

// Get filter parameters
$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-7 days'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$queue_name = $_GET['queue'] ?? $defaultQueue;
$search = $_GET['search'] ?? '';
$call_status_filter = $_GET['call_status'] ?? 'all';
$call_direction_filter = $_GET['call_direction'] ?? 'all';
$call_type_filter = $_GET['call_type'] ?? 'all';
$extension_filter = $_GET['extension'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = intval($_GET['limit'] ?? 50);
$limit = in_array($limit, [20, 50, 100, 200, 500]) ? $limit : 50;

/**
 * Fetch inbound queue call data
 */
function getQueueCallData($apiUrl, $apiUser, $apiPass, $startDate, $endDate, $queueName) {
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
        return ['error' => 'Challenge failed'];
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
        return ['error' => 'Authentication failed'];
    }

    // Queue API request
    $queueRequest = json_encode([
        'request' => [
            'action' => 'queueapi',
            'endTime' => $endDate,
            'startTime' => $startDate,
            'queue' => $queueName,
            'format' => 'json',
            'statisticsType' => 'calldetail',
            'cookie' => $cookie
        ]
    ]);
    
    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $queueRequest);
    $queueResponse = curl_exec($curlHandle);
    $queueData = json_decode($queueResponse);

    curl_close($curlHandle);

    if (empty($queueData->queue_statistics)) {
        return ['success' => true, 'data' => []];
    }

    return ['success' => true, 'data' => $queueData->queue_statistics];
}

/**
 * Fetch CDR call data (includes both inbound and outbound) - FIXED FOR UCM6302
 */
function getCDRCallData($apiUrl, $apiUser, $apiPass, $startDate, $endDate) {
    // UCM6302 REQUIRES DATETIME FORMAT (YYYY-MM-DD HH:MM:SS)
    $startDateTime = $startDate . ' 00:00:00';
    $endDateTime = $endDate . ' 23:59:59';
    
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
        return ['error' => 'Challenge failed'];
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
        return ['error' => 'Authentication failed'];
    }

    // CDR API request - FIXED: Use datetime format and check response structure
    $cdrRequest = json_encode([
        'request' => [
            'action' => 'cdrapi',
            'format' => 'json',
            'startTime' => $startDateTime,
            'endTime' => $endDateTime,
            'cookie' => $cookie
        ]
    ]);
    
    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $cdrRequest);
    $cdrResponse = curl_exec($curlHandle);
    $cdrData = json_decode($cdrResponse, true);

    curl_close($curlHandle);

    // FIXED: UCM6302 returns data in response->cdr_root structure
    if (isset($cdrData['response']['cdr_root']) && is_array($cdrData['response']['cdr_root'])) {
        // Convert to object format for compatibility
        $cdrRoot = json_decode(json_encode($cdrData['response']['cdr_root']));
        return ['success' => true, 'data' => $cdrRoot];
    }

    return ['success' => true, 'data' => []];
}


/**
 * Process a single CDR record (handles both simple and nested structures)
 */
function processCDRRecord($record) {
    $calls = [];
    
    // Check if this is a complex record with sub_cdr entries
    if (isset($record->main_cdr)) {
        // Process each sub_cdr
        $subIndex = 1;
        while (isset($record->{"sub_cdr_$subIndex"})) {
            $subCdr = $record->{"sub_cdr_$subIndex"};
            $calls[] = extractCDRFields($subCdr);
            $subIndex++;
        }
    } else {
        // Simple record structure
        $calls[] = extractCDRFields($record);
    }
    
    return $calls;
}

/**
 * Extract relevant fields from a CDR record
 */
function extractCDRFields($cdr) {
    return [
        'src' => $cdr->src ?? '',
        'dst' => $cdr->dst ?? '',
        'start' => $cdr->start ?? '',
        'answer' => $cdr->answer ?? '',
        'end' => $cdr->end ?? '',
        'duration' => intval($cdr->duration ?? 0),
        'billsec' => intval($cdr->billsec ?? 0),
        'disposition' => strtoupper($cdr->disposition ?? ''),
        'userfield' => $cdr->userfield ?? '',
        'action_type' => $cdr->action_type ?? '',
        'src_trunk_name' => $cdr->src_trunk_name ?? '',
        'dcontext' => $cdr->dcontext ?? '',
        'dstanswer' => $cdr->dstanswer ?? '',
        'uniqueid' => $cdr->uniqueid ?? ''
    ];
}

// Initialize arrays
$callRecords = [];
$error_message = '';

// Fetch inbound queue calls if needed
if ($call_direction_filter === 'all' || $call_direction_filter === 'inbound') {
    $queueResult = getQueueCallData($apiUrl, $apiUser, $apiPass, $from_date, $to_date, $queue_name);
    
    if (!isset($queueResult['error']) && !empty($queueResult['data'])) {
        foreach ($queueResult['data'] as $stat) {
            if (!isset($stat->agent)) continue;

            $agentName = $stat->agent->agent ?? 'Unknown';
            $extension = $stat->agent->extension ?? 'N/A';
            $callerNum = $stat->agent->callernum ?? '';
            $connect = $stat->agent->connect ?? 'no';
            $startTime = $stat->agent->start_time ?? '';
            $waitTime = intval($stat->agent->wait_time ?? 0);
            $talkTime = intval($stat->agent->talk_time ?? 0);

            // Skip NONE, queue (6500), IVR (7000), and keep only agent extensions (4 digits like 6003, 6004)
            if ($extension === 'NONE' || $agentName === 'NONE' || 
                $extension === '6500' || $extension === '7000' ||
                !is_numeric($extension) || strlen($extension) !== 4) {
                continue;
            }

            if (!empty($search)) {
                if (stripos($callerNum, $search) === false && stripos($extension, $search) === false) {
                    continue;
                }
            }

            if (!empty($extension_filter) && $extension !== $extension_filter) {
                continue;
            }

            $call_status = ($connect === 'yes') ? 'answered' : 'missed';

            if ($call_status_filter !== 'all' && $call_status !== $call_status_filter) {
                continue;
            }

            $answered_time = null;
            if ($call_status === 'answered' && !empty($startTime) && $waitTime > 0) {
                $answered_time = date('Y-m-d H:i:s', strtotime($startTime) + $waitTime);
            }

            $callRecords[] = [
                'call_time' => $startTime,
                'phone_number' => $callerNum,
                'extension' => $extension,
                'agent' => $agentName,
                'call_status' => $call_status,
                'call_direction' => 'inbound',
                'call_type' => 'Queue',
                'answered_time' => $answered_time,
                'wait_time' => $waitTime,
                'talk_time' => $talkTime,
                'uniqueid' => '',
                'recording' => findRecording($startTime, $extension, $callerNum, $recordingPath, $recordingExtensions)
            ];
        }
    }
}

// Fetch CDR calls (both inbound and outbound)
$cdrResult = getCDRCallData($apiUrl, $apiUser, $apiPass, $from_date, $to_date);

if (!isset($cdrResult['error']) && !empty($cdrResult['data'])) {
    foreach ($cdrResult['data'] as $record) {
        $processedCalls = processCDRRecord($record);
        
        foreach ($processedCalls as $cdr) {
            // Skip empty records
            if (empty($cdr['src']) || empty($cdr['dst'])) {
                continue;
            }
            
            $dst = $cdr['dst'];
            $src = $cdr['src'];
            
            // SKIP FEATURE CODES ONLY (start with * or #)
            if (preg_match('/^[*#]/', $dst)) {
                continue;
            }
            
            // Determine call direction - PRIORITY MATTERS!
            $isOutbound = false;
            $isInbound = false;
            
            // HIGHEST PRIORITY: Check for trunk name (ALWAYS means inbound)
            if (!empty($cdr['src_trunk_name'])) {
                $isInbound = true;
            }
            
            // SECOND PRIORITY: Check userfield
            if (strtolower($cdr['userfield']) === 'inbound') {
                $isInbound = true;
            }
            
            // ONLY check for outbound if not already marked as inbound
            if (!$isInbound) {
                // Check action_type for DIAL (outbound indicator)
                if (stripos($cdr['action_type'], 'DIAL') !== false) {
                    $isOutbound = true;
                }
                
                // Check if userfield indicates outbound
                if (strtolower($cdr['userfield']) === 'outbound') {
                    $isOutbound = true;
                }
                
                // Pattern check: src is extension and dst is external
                $srcIsExtension = strlen($cdr['src']) >= 3 && strlen($cdr['src']) <= 5 && is_numeric($cdr['src']);
                $dstIsExtension = strlen($cdr['dst']) >= 3 && strlen($cdr['dst']) <= 5 && is_numeric($cdr['dst']);
                
                if ($srcIsExtension && !$dstIsExtension) {
                    $isOutbound = true;
                }
            }
            
            // Determine final direction
            $call_direction = $isInbound ? 'inbound' : ($isOutbound ? 'outbound' : 'inbound');
            
            // ADDITIONAL FILTERS for outbound - only filter obvious non-calls
            if ($call_direction === 'outbound') {
                // Skip very short destinations (1-2 digits) - likely speed dials or shortcuts
                if (strlen($cdr['dst']) <= 2) {
                    continue;
                }
                
                // Skip destinations with special routing (*, **, #) - feature codes
                if (strpos($cdr['dst'], '*') !== false || strpos($cdr['dst'], '#') !== false) {
                    continue;
                }
            }
            
            // Apply direction filter
            if ($call_direction_filter !== 'all' && $call_direction !== $call_direction_filter) {
                continue;
            }
            
            // Determine which number to display
            $displayNumber = $call_direction === 'outbound' ? $cdr['dst'] : $cdr['src'];
            $displayExtension = $call_direction === 'outbound' ? $cdr['src'] : ($cdr['dstanswer'] ?: $cdr['dst']);
            
            // Apply search filter
            if (!empty($search)) {
                if (stripos($displayNumber, $search) === false && stripos($displayExtension, $search) === false) {
                    continue;
                }
            }
            
            // Apply extension filter
            if (!empty($extension_filter) && $displayExtension !== $extension_filter) {
                continue;
            }
            
            // Map disposition to call status
            $call_status = 'missed';
            if ($cdr['disposition'] === 'ANSWERED') {
                $call_status = 'answered';
            }
            
            // Apply status filter
            if ($call_status_filter !== 'all' && $call_status !== $call_status_filter) {
                continue;
            }
            
            // Use full action_type from UCM CDR (VM, IVR[7000], QUEUE[6500], etc.)
            $call_type = !empty($cdr['action_type']) ? trim($cdr['action_type']) : 'DIAL';
            
            // Apply call type filter (partial match for flexibility)
            if ($call_type_filter !== 'all') {
                if (stripos($call_type, $call_type_filter) === false) {
                    continue;
                }
            }
            
            $callRecords[] = [
                'call_time' => $cdr['start'],
                'phone_number' => $displayNumber,
                'extension' => $displayExtension,
                'agent' => 'N/A',
                'call_status' => $call_status,
                'call_direction' => $call_direction,
                'call_type' => $call_type,
                'answered_time' => $call_status === 'answered' ? $cdr['answer'] : null,
                'wait_time' => 0,
                'talk_time' => $cdr['billsec'],
                'uniqueid' => $cdr['uniqueid'],
                'recording' => findRecording($cdr['start'], $displayExtension, $displayNumber, $recordingPath, $recordingExtensions)
            ];
        }
    }
}

// Sort by call_time descending
usort($callRecords, function($a, $b) {
    return strtotime($b['call_time']) - strtotime($a['call_time']);
});

// ── Calculate statistics by UNIQUE SESSION, not individual stages ──
// Group records into sessions using uniqueid (CDR) or timestamp+phone (queue)
$sessionsSeen   = [];
$total_calls    = 0;
$inbound_calls  = 0;
$outbound_calls = 0;
$answered_calls = 0;
$missed_calls   = 0;

foreach ($callRecords as $r) {
    $sid = !empty($r['uniqueid'])
        ? $r['uniqueid']
        : (date('Y-m-d H:i', strtotime($r['call_time'])) . '-' . $r['phone_number']);

    if (isset($sessionsSeen[$sid])) {
        // Session already counted — upgrade status to answered if any stage was answered
        if ($r['call_status'] === 'answered' && $sessionsSeen[$sid] === 'missed') {
            $sessionsSeen[$sid] = 'answered';
            $answered_calls++;
            $missed_calls--;
        }
        continue;
    }

    $sessionsSeen[$sid] = $r['call_status'];
    $total_calls++;
    if ($r['call_direction'] === 'inbound')  $inbound_calls++;
    else                                      $outbound_calls++;
    if ($r['call_status'] === 'answered')    $answered_calls++;
    else                                      $missed_calls++;
}

$answer_rate = $total_calls > 0 ? round(($answered_calls / $total_calls) * 100, 1) : 0;

$raw_record_count = count($callRecords);
$show_warning = $raw_record_count > 10000;
$warning_message = '';
if ($raw_record_count > 50000) {
    $warning_message = 'Very large dataset (' . number_format($raw_record_count) . ' records). Consider narrowing your date range.';
} elseif ($raw_record_count > 10000) {
    $warning_message = 'Large dataset (' . number_format($raw_record_count) . ' records). Page loading may be slower.';
}

// Paginate on raw records (sessions expand in the table)
$total_pages = ceil($raw_record_count / $limit);
$offset = ($page - 1) * $limit;
$paginated_records = array_slice($callRecords, $offset, $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call History - Complete</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            max-width: 1800px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #2d3748;
            font-size: 28px;
            font-weight: 700;
        }

        .header h1 i {
            color: #667eea;
            margin-right: 10px;
        }

        .btn-quit {
            background: #ef4444;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-quit:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .stat-card.total { border-left: 4px solid #667eea; }
        .stat-card.total i { color: #667eea; }
        
        .stat-card.inbound { border-left: 4px solid #10b981; }
        .stat-card.inbound i { color: #10b981; }
        
        .stat-card.outbound { border-left: 4px solid #f59e0b; }
        .stat-card.outbound i { color: #f59e0b; }
        
        .stat-card.answered { border-left: 4px solid #22c55e; }
        .stat-card.answered i { color: #22c55e; }
        
        .stat-card.missed { border-left: 4px solid #ef4444; }
        .stat-card.missed i { color: #ef4444; }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin: 5px 0;
        }

        .stat-label {
            color: #6b7280;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            color: #4b5563;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .btn-filter {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #4b5563;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
            position: sticky;
            top: 0;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }

        th i {
            margin-right: 8px;
            color: #667eea;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-answered {
            background: #d1fae5;
            color: #065f46;
        }

        .status-missed {
            background: #fee2e2;
            color: #991b1b;
        }

        .direction-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .direction-inbound {
            background: #dbeafe;
            color: #1e40af;
        }

        .direction-outbound {
            background: #fed7aa;
            color: #92400e;
        }

        .type-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            background: #f3f4f6;
            color: #6b7280;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .page-link {
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.3s;
        }

        .page-link:hover:not(.disabled) {
            background: #667eea;
            color: white;
        }

        .page-link.active {
            background: #667eea;
            color: white;
        }

        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .no-data i {
            font-size: 64px;
            margin-bottom: 20px;
        }

        /* Recording Player Styles */
        .recording-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .play-btn {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .play-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.4);
        }

        .play-btn:disabled {
            background: #cbd5e0;
            color: #a0aec0;
            cursor: not-allowed;
            transform: none;
        }

        .no-recording {
            color: #a0aec0;
            font-size: 13px;
            font-style: italic;
        }

        /* Delete Button */
        .delete-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .delete-btn:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .delete-btn:active {
            transform: translateY(0);
        }

        /* Audio Player Modal */
        .audio-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .audio-modal.active {
            display: flex;
        }

        .audio-modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .audio-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .audio-modal-header h3 {
            color: #2d3748;
            font-size: 20px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            color: #a0aec0;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #2d3748;
        }

        .audio-player-wrapper {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
        }

        audio {
            width: 100%;
            margin-bottom: 15px;
        }

        .call-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            font-size: 14px;
            color: #4a5568;
        }

        .call-info-item {
            display: flex;
            flex-direction: column;
        }

        .call-info-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 3px;
        }

/* Print button and checkboxes */
.btn-print {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}
.btn-print:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.select-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #667eea;
}

/* Settings Button */
.btn-settings {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}
.btn-settings:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* Settings Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 20px;
}
.settings-modal {
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
}
.modal-content {
    background: white;
    border-radius: 15px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    width: 100%;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 30px;
    border-bottom: 2px solid #e2e8f0;
}
.modal-header h2 {
    margin: 0;
    color: #2d3748;
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.modal-close {
    background: #ef4444;
    color: white;
    border: none;
    width: 35px;
    height: 35px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}
.modal-close:hover {
    background: #dc2626;
    transform: scale(1.1);
}
.settings-form {
    padding: 30px;
}
.settings-section {
    margin-bottom: 30px;
    padding-bottom: 25px;
    border-bottom: 1px solid #e2e8f0;
}
.settings-section:last-of-type {
    border-bottom: none;
}
.settings-section h3 {
    color: #667eea;
    font-size: 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.form-group {
    margin-bottom: 20px;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
.form-group label {
    display: block;
    color: #2d3748;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
}
.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="number"] {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}
.form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.form-group small {
    display: block;
    color: #718096;
    font-size: 12px;
    margin-top: 5px;
}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: 500;
}
.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #667eea;
}
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
    margin-top: 10px;
    border-top: 2px solid #e2e8f0;
}
.btn-primary, .btn-secondary {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}
.btn-secondary {
    background: #e2e8f0;
    color: #2d3748;
}
.btn-secondary:hover {
    background: #cbd5e0;
}


/* Session-Based Call Grouping */
.session-parent-row {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-left: 4px solid #3b82f6;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.session-parent-row:hover {
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    transform: translateX(2px);
}
.session-parent-row td {
    padding: 15px 12px !important;
}
.session-toggle {
    font-size: 14px;
    color: #3b82f6;
    margin-right: 8px;
    transition: transform 0.3s ease;
    display: inline-block;
}
.session-toggle.expanded {
    transform: rotate(90deg);
}
.session-child-row {
    background: #ffffff;
    border-left: 3px solid #cbd5e0;
}
.session-child-row td:first-child {
    padding-left: 35px !important;
}
.session-child-row.hidden {
    display: none;
}
.session-badge {
    display: inline-block;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 10px;
}
.session-flow {
    font-size: 13px;
    color: #3b82f6;
    font-weight: 600;
}
.stage-indicator {
    display: inline-block;
    background: #ede9fe;
    color: #7c3aed;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
    margin-right: 5px;
}


.btn-csv {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    display: none;
}
.btn-csv:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

    </style>
</head>
<body>
    <div class="container">

<!-- Settings Modal -->
<div id="settingsModal" class="modal-overlay" style="display: none;">
    <div class="modal-content settings-modal">
        <div class="modal-header">
            <h2><i class="fas fa-cog"></i> Report Settings</h2>
            <button class="modal-close" onclick="closeSettingsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" class="settings-form">
            <div class="settings-section">
                <h3><i class="fas fa-building"></i> Company Information</h3>
                
                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" 
                           value="<?php echo htmlspecialchars($settings['company_name']); ?>" 
                           placeholder="ABC Telecom Inc.">
                </div>
                
                <div class="form-group">
                    <label for="company_address">Address</label>
                    <input type="text" id="company_address" name="company_address" 
                           value="<?php echo htmlspecialchars($settings['company_address']); ?>" 
                           placeholder="Beirut, Lebanon">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_phone">Phone</label>
                        <input type="text" id="company_phone" name="company_phone" 
                               value="<?php echo htmlspecialchars($settings['company_phone']); ?>" 
                               placeholder="+961 1 234567">
                    </div>
                    
                    <div class="form-group">
                        <label for="company_email">Email</label>
                        <input type="email" id="company_email" name="company_email" 
                               value="<?php echo htmlspecialchars($settings['company_email']); ?>" 
                               placeholder="info@company.com">
                    </div>
                </div>
            </div>
            
            <div class="settings-section">
                <h3><i class="fas fa-image"></i> Logo Settings</h3>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="use_logo" id="use_logo" 
                               <?php echo $settings['use_logo'] ? 'checked' : ''; ?>>
                        <span>Show logo in reports</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="logo_path">Logo Path/URL</label>
                    <input type="text" id="logo_path" name="logo_path" 
                           value="<?php echo htmlspecialchars($settings['logo_path']); ?>" 
                           placeholder="logo.png">
                    <small>Example: logo.png or images/logo.png or http://yoursite.com/logo.png</small>
                </div>
                
                <div class="form-group">
                    <label for="logo_width">Logo Width (pixels)</label>
                    <input type="number" id="logo_width" name="logo_width" 
                           value="<?php echo $settings['logo_width']; ?>" 
                           min="50" max="300" step="10">
                </div>
                
                <div class="form-group">
                    <label for="recording_path">📁 Recordings Folder Path</label>
                    <input type="text" id="recording_path" name="recording_path" 
                           value="<?php echo htmlspecialchars($settings['recording_path'] ?? 'c:/rec/'); ?>" 
                           placeholder="c:/rec/">
                    <small>Windows path where UCM stores call recordings. Example: c:/rec/ or d:/recordings/</small>
                </div>
            </div>
            
            <div class="settings-section">
                <h3><i class="fas fa-file-alt"></i> Report Content</h3>
                
                <div class="form-group">
                    <label for="report_title">Report Title</label>
                    <input type="text" id="report_title" name="report_title" 
                           value="<?php echo htmlspecialchars($settings['report_title']); ?>" 
                           placeholder="Call History Report">
                </div>
                
                <div class="form-group">
                    <label for="report_subtitle">Report Subtitle</label>
                    <input type="text" id="report_subtitle" name="report_subtitle" 
                           value="<?php echo htmlspecialchars($settings['report_subtitle']); ?>" 
                           placeholder="Comprehensive Call Analytics & Details">
                </div>
                
                <div class="form-group">
                    <label for="footer_text">Footer Text</label>
                    <input type="text" id="footer_text" name="footer_text" 
                           value="<?php echo htmlspecialchars($settings['footer_text']); ?>" 
                           placeholder="Generated from Call History System">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeSettingsModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" name="save_settings" class="btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($settingsMessage)): ?>
<div class="alert alert-success" style="margin: 20px;">
    <i class="fas fa-check-circle"></i> <?php echo $settingsMessage; ?>
</div>
<script>
setTimeout(function() {
    var alert = document.querySelector('.alert-success');
    if (alert) alert.style.display = 'none';
}, 3000);
</script>
<?php endif; ?>


        <div class="header">
            <h1>
                <i class="fas fa-phone-volume"></i>
                Complete Call History
            </h1>
            <div style="display: flex; gap: 10px;">
                <button class="btn-settings" onclick="openSettingsModal()">
                    <i class="fas fa-cog"></i> Settings
                </button>
                <button class="btn-csv" onclick="exportToCSV()" id="csvBtn" style="display: none;">
                    <i class="fas fa-file-csv"></i> Export CSV (<span id="csvCount">0</span>)
                </button>
                <button class="btn-print" onclick="printSelected()" id="printBtn" style="display: none;">
                    <i class="fas fa-print"></i> Print (<span id="selectedCount">0</span>)
                </button>
                <button class="btn-quit" onclick="quit()">
                    <i class="fas fa-sign-out-alt"></i> Back
                </button>
            </div>
        </div>

        <?php if (isset($deleteMessage)): ?>
        <div class="alert <?php echo $deleteSuccess ? 'alert-success' : 'alert-danger'; ?>" style="margin-bottom: 20px;">
            <i class="fas <?php echo $deleteSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($deleteMessage); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($debugMode) && $debugMode): ?>
        <div class="alert" style="background: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 20px;">
            <div style="font-weight: bold; margin-bottom: 10px;">
                <i class="fas fa-bug"></i> DEBUG INFO (Remove after fixing)
            </div>
            <table style="width: 100%; font-size: 13px; background: white; border-radius: 5px;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>$_GET['page']:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($debugInfo['get_page']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>$_SESSION['oop']:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($debugInfo['session_oop']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>$nam Variable:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php echo htmlspecialchars($debugInfo['nam_variable']); ?></strong></td>
                </tr>
                <tr style="background: <?php echo strpos($debugInfo['isSuperUser'], 'YES') !== false ? '#d4edda' : '#fee2e2'; ?>;">
                    <td style="padding: 8px;"><strong>Is Super User:</strong></td>
                    <td style="padding: 8px; font-weight: bold;">
                        <?php echo $debugInfo['isSuperUser']; ?>
                        <?php if (strpos($debugInfo['isSuperUser'], 'YES') !== false): ?>
                            <span style="color: green;"> Delete column should be visible</span>
                        <?php else: ?>
                            <span style="color: red;"> Delete column will NOT show</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <div style="margin-top: 10px; padding: 10px; background: #e3f2fd; border-radius: 5px;">
                <strong>What's happening:</strong><br>
                • Looking for "super" in <code>$_GET['page']</code> first (from URL)<br>
                • Then checking <code>$_SESSION['oop']</code> if GET is empty<br>
                • Setting <code>$nam</code> to whichever has a value<br>
                • If <code>$nam = "super"</code> → Delete column shows<br><br>
                <strong>To hide this message:</strong> Set <code>$debugMode = false;</code> on line ~25
            </div>
        </div>
        <?php endif; ?>

        <?php if ($show_warning && !empty($warning_message)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo $warning_message; ?>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card total">
                <i class="fas fa-phone-alt"></i>
                <div class="stat-value"><?php echo number_format($total_calls); ?></div>
                <div class="stat-label">Total Calls</div>
            </div>
            
            <div class="stat-card inbound">
                <i class="fas fa-phone-volume"></i>
                <div class="stat-value"><?php echo number_format($inbound_calls); ?></div>
                <div class="stat-label">Inbound</div>
            </div>
            
            <div class="stat-card outbound">
                <i class="fas fa-phone"></i>
                <div class="stat-value"><?php echo number_format($outbound_calls); ?></div>
                <div class="stat-label">Outbound</div>
            </div>
            
            <div class="stat-card answered">
                <i class="fas fa-check-circle"></i>
                <div class="stat-value"><?php echo number_format($answered_calls); ?></div>
                <div class="stat-label">Answered</div>
            </div>
            
            <div class="stat-card missed">
                <i class="fas fa-times-circle"></i>
                <div class="stat-value"><?php echo number_format($missed_calls); ?></div>
                <div class="stat-label">Missed</div>
            </div>
        </div>

        <div class="content-card">
            <form method="GET" action="">
                <div class="filters">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> From Date</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> To Date</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-list"></i> Queue</label>
                        <input type="text" name="queue" value="<?php echo htmlspecialchars($queue_name); ?>" placeholder="e.g., 6500">
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-exchange-alt"></i> Direction</label>
                        <select name="call_direction">
                            <option value="all" <?php echo $call_direction_filter === 'all' ? 'selected' : ''; ?>>All Calls</option>
                            <option value="inbound" <?php echo $call_direction_filter === 'inbound' ? 'selected' : ''; ?>>Inbound Only</option>
                            <option value="outbound" <?php echo $call_direction_filter === 'outbound' ? 'selected' : ''; ?>>Outbound Only</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-info-circle"></i> Status</label>
                        <select name="call_status">
                            <option value="all" <?php echo $call_status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="answered" <?php echo $call_status_filter === 'answered' ? 'selected' : ''; ?>>Answered</option>
                            <option value="missed" <?php echo $call_status_filter === 'missed' ? 'selected' : ''; ?>>Missed</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-tag"></i> Type</label>
                        <select name="call_type">
                            <option value="all" <?php echo $call_type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="vm" <?php echo $call_type_filter === 'vm' ? 'selected' : ''; ?>>VM (Voicemail)</option>
                            <option value="ivr" <?php echo $call_type_filter === 'ivr' ? 'selected' : ''; ?>>IVR</option>
                            <option value="queue" <?php echo $call_type_filter === 'queue' ? 'selected' : ''; ?>>Queue</option>
                            <option value="dial" <?php echo $call_type_filter === 'dial' ? 'selected' : ''; ?>>Dial</option>
                            <option value="transfer" <?php echo $call_type_filter === 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                            <option value="conference" <?php echo $call_type_filter === 'conference' ? 'selected' : ''; ?>>Conference</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-phone-alt"></i> Extension</label>
                        <input type="text" name="extension" value="<?php echo htmlspecialchars($extension_filter); ?>" placeholder="Filter by ext">
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Phone or ext">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter btn-primary">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                        <a href="?" class="btn-filter btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
                <div style="color: #6b7280; font-size: 14px;">
                    Showing <?php echo number_format($offset + 1); ?> - <?php echo number_format(min($offset + $limit, $raw_record_count)); ?> 
                    of <?php echo number_format($raw_record_count); ?> records &nbsp;|&nbsp; <?php echo number_format($total_calls); ?> unique calls
                </div>
                <div>
                    <label style="color: #6b7280; font-size: 14px; margin-right: 10px;">Per Page:</label>
                    <select onchange="changePageSize(this.value)" style="padding: 6px 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        <option value="20" <?php echo $limit === 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                        <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200</option>
                        <option value="500" <?php echo $limit === 500 ? 'selected' : ''; ?>>500</option>
                    </select>
                </div>
            </div>

            <?php if (empty($paginated_records)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <h3>No Call Records Found</h3>
                    <p>Try adjusting your filters or date range.</p>
                </div>
            <?php else: ?>
                
                <div style="display: flex; gap: 10px; margin-bottom: 15px; justify-content: flex-end;">
                    <button onclick="expandAllGroups()" class="btn-secondary" style="padding: 8px 16px; font-size: 13px;">
                        <i class="fas fa-plus-square"></i> Expand All
                    </button>
                    <button onclick="collapseAllGroups()" class="btn-secondary" style="padding: 8px 16px; font-size: 13px;">
                        <i class="fas fa-minus-square"></i> Collapse All
                    </button>
                </div>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAll" class="select-checkbox" onchange="toggleSelectAll(this)" title="Select All">
                                </th>
                                <th><i class="fas fa-clock"></i> Time</th>
                                <th><i class="fas fa-exchange-alt"></i> Direction</th>
                                <th><i class="fas fa-tag"></i> Type</th>
                                <th><i class="fas fa-phone"></i> Number</th>
                                <th><i class="fas fa-phone-alt"></i> Extension</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                                <th><i class="fas fa-comments"></i> Talk Time</th>
                                <th><i class="fas fa-microphone"></i> Recording</th>
                                <?php if ($isSuperUser): ?>
                                <th style="width: 100px;"><i class="fas fa-trash"></i> Delete</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Group records by session (uniqueid)
                            $session_groups = [];
                            foreach ($paginated_records as $idx => $rec) {
                                // Use uniqueid as session identifier, or create one from timestamp+phone
                                $session_id = !empty($rec['uniqueid']) ? $rec['uniqueid'] : 
                                              (strtotime($rec['call_time']) . '-' . $rec['phone_number']);
                                
                                if (!isset($session_groups[$session_id])) {
                                    $session_groups[$session_id] = [
                                        'stages' => [],
                                        'stage_count' => 0,
                                        'first_time' => $rec['call_time'],
                                        'caller' => $rec['phone_number'],
                                        'first_destination' => $rec['extension'],
                                        'first_type' => $rec['call_type'],
                                        'total_duration' => 0,
                                        'action_types' => []
                                    ];
                                }
                                
                                $session_groups[$session_id]['stages'][] = ['index' => $idx, 'record' => $rec];
                                $session_groups[$session_id]['stage_count']++;
                                $session_groups[$session_id]['total_duration'] += $rec['talk_time'];
                                
                                // Track action type flow
                                if (!empty($rec['call_type']) && !in_array($rec['call_type'], $session_groups[$session_id]['action_types'])) {
                                    $session_groups[$session_id]['action_types'][] = $rec['call_type'];
                                }
                            }
                            ?>
                            
                            <?php foreach ($session_groups as $session_id => $session_data): 
                                $session_hash = md5($session_id);
                                $stage_count = $session_data['stage_count'];
                                $action_flow = implode(' → ', $session_data['action_types']);
                            ?>
                            <!-- Parent Row (Session Summary) -->
                            <tr class="session-parent-row" onclick="toggleGroup('<?php echo $session_hash; ?>')">
                                <td colspan="2">
                                    <span class="session-toggle" id="<?php echo $session_hash; ?>_toggle">▶</span>
                                    <strong><?php echo htmlspecialchars($session_data['caller']); ?></strong>
                                    →
                                    <strong><?php echo htmlspecialchars($session_data['first_destination']); ?></strong>
                                    <span class="session-badge"><?php echo $stage_count; ?> stage<?php echo $stage_count > 1 ? 's' : ''; ?></span>
                                </td>
                                <td colspan="2">
                                    <span class="session-flow"><?php echo htmlspecialchars($action_flow); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($session_data['first_time']); ?></td>
                                <td colspan="2">Total: <?php echo gmdate('i:s', $session_data['total_duration']); ?></td>
                                <td colspan="2"><small>Session: <?php echo substr($session_hash, 0, 8); ?></small></td>
                            </tr>
                            
                            <!-- Child Rows (Individual Stages) -->
                            <?php foreach ($session_data['stages'] as $stage_idx => $stage_item): 
                                $loop_index = $stage_item['index'];
                                $record = $stage_item['record'];
                                $statusClass = 'status-' . $record['call_status'];
                                $directionClass = 'direction-' . $record['call_direction'];
                            ?>
                            <tr class="session-child-row hidden" data-group="<?php echo $session_hash; ?>">
                                <td>
                                    <span class="stage-indicator">Stage <?php echo $stage_idx + 1; ?></span>
                                    <input type="checkbox" class="call-checkbox select-checkbox" 
                                           value="<?php echo $loop_index; ?>" 
                                           onchange="toggleCallSelection(this)">
                                </td>
                                <td><?php echo htmlspecialchars($record['call_time']); ?></td>
                                <td><span class="direction-badge <?php echo $directionClass; ?>">
                                    <?php echo $record['call_direction'] === 'inbound' ? '📞 In' : '📱 Out'; ?>
                                </span></td>
                                <td><span class="type-badge"><?php echo htmlspecialchars($record['call_type']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($record['phone_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($record['extension']); ?></td>
                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo ucfirst($record['call_status']); ?></span></td>
                                <td><?php echo gmdate('i:s', $record['talk_time']); ?></td>
                                <td>
                                    <?php if (!empty($record['recording'])): ?>
                                        <button class="play-btn" onclick='playRecording(<?php echo json_encode($record['recording']); ?>, <?php echo htmlspecialchars(json_encode($record), ENT_QUOTES); ?>)'>
                                            <i class="fas fa-play"></i> Play
                                        </button>
                                    <?php else: ?>
                                        <span class="no-recording">
                                            <i class="fas fa-times"></i> No recording
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($isSuperUser): ?>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this call record?');">
                                        <input type="hidden" name="delete_call_id" value="<?php echo htmlspecialchars($record['id']); ?>">
                                        <button type="submit" class="delete-btn" title="Delete Call">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $query_params = $_GET;
                    unset($query_params['page']);
                    
                    if ($page > 1):
                        $query_params['page'] = $page - 1;
                    ?>
                        <a href="?<?php echo http_build_query($query_params); ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php else: ?>
                        <span class="page-link disabled">
                            <i class="fas fa-chevron-left"></i> Previous
                        </span>
                    <?php endif; ?>

                    <?php
                    $range = 2;
                    $start_page = max(1, $page - $range);
                    $end_page = min($total_pages, $page + $range);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                        $query_params['page'] = $i;
                        $active = $i === $page ? 'active' : '';
                    ?>
                        <a href="?<?php echo http_build_query($query_params); ?>" class="page-link <?php echo $active; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages):
                        $query_params['page'] = $page + 1;
                    ?>
                        <a href="?<?php echo http_build_query($query_params); ?>" class="page-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="page-link disabled">
                            Next <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>

                    <span style="margin-left: 10px; color: #6b7280; font-size: 14px;">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </span>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>


    <!-- Audio Player Modal -->
    <div class="audio-modal" id="audioModal">
        <div class="audio-modal-content">
            <div class="audio-modal-header">
                <h3><i class="fas fa-headphones"></i> Call Recording</h3>
                <button class="close-modal" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="audio-player-wrapper">
                <audio id="audioPlayer" controls controlsList="nodownload">
                    Your browser does not support the audio element.
                </audio>
                <div class="call-info" id="callInfo">
                    <!-- Call details will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <script>

        function playRecording(filename, callData) {
            const modal = document.getElementById('audioModal');
            const audioPlayer = document.getElementById('audioPlayer');
            const callInfo = document.getElementById('callInfo');
            
            // Set audio source
            audioPlayer.src = 'play_recording.php?file=' + encodeURIComponent(filename);
            
            // Populate call information
            callInfo.innerHTML = "<div class=\"call-info-item\"><span class=\"call-info-label\">Call Time:</span><span>" + callData.call_time + "</span></div>" +
                "<div class=\"call-info-item\"><span class=\"call-info-label\">Phone Number:</span><span>" + callData.phone_number + "</span></div>" +
                "<div class=\"call-info-item\"><span class=\"call-info-label\">Extension:</span><span>" + callData.extension + "</span></div>" +
                "<div class=\"call-info-item\"><span class=\"call-info-label\">Direction:</span><span>" + callData.call_direction + "</span></div>" +
                "<div class=\"call-info-item\"><span class=\"call-info-label\">Status:</span><span>" + callData.call_status + "</span></div>" +
                "<div class=\"call-info-item\"><span class=\"call-info-label\">Talk Time:</span><span>" + Math.floor(callData.talk_time / 60) + "m " + (callData.talk_time % 60) + "s</span></div>";
            
            // Show modal
            modal.classList.add('active');
            
            // Auto-play
            audioPlayer.play();
        }
        
        function closeModal() {
            const modal = document.getElementById('audioModal');
            const audioPlayer = document.getElementById('audioPlayer');
            
            // Stop playback
            audioPlayer.pause();
            audioPlayer.currentTime = 0;
            
            // Hide modal
            modal.classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('audioModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        function quit() {
            // Try to close the window/tab
            window.close();
            
            // If window.close() doesn't work (page wasn't opened by script),
            // redirect to main page after a short delay
            setTimeout(function() {
                window.location.href = "test204.php?page=<?php echo $_SESSION["oop"] ?? ''; ?>&page1=<?php echo $_SESSION["ooq"] ?? ''; ?>";
            }, 100);
        }

        function changePageSize(newLimit) {
            const url = new URL(window.location);
            url.searchParams.set('limit', newLimit);
            url.searchParams.set('page', 1);
            window.location = url.toString();
        }

    </script>

<script>

// ==================== SETTINGS JAVASCRIPT ====================
var reportSettings = <?php echo $settingsJson; ?>;

function openSettingsModal() {
    document.getElementById('settingsModal').style.display = 'flex';
}

function closeSettingsModal() {
    document.getElementById('settingsModal').style.display = 'none';
}

window.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('settingsModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSettingsModal();
            }
        });
    }
});
// ==================== END SETTINGS JAVASCRIPT ====================


// Session expand/collapse functionality
function toggleGroup(groupId) {
    var childRows = document.querySelectorAll('[data-group="' + groupId + '"]');
    var toggle = document.getElementById(groupId + '_toggle');
    
    var isExpanded = !childRows[0].classList.contains('hidden');
    
    for (var i = 0; i < childRows.length; i++) {
        if (isExpanded) {
            childRows[i].classList.add('hidden');
        } else {
            childRows[i].classList.remove('hidden');
        }
    }
    
    if (toggle) {
        if (isExpanded) {
            toggle.textContent = '▶';
            toggle.classList.remove('expanded');
        } else {
            toggle.textContent = '▼';
            toggle.classList.add('expanded');
        }
    }
}

// Expand all sessions
function expandAllGroups() {
    var allToggles = document.querySelectorAll('.session-toggle');
    for (var i = 0; i < allToggles.length; i++) {
        var groupId = allToggles[i].id.replace('_toggle', '');
        var childRows = document.querySelectorAll('[data-group="' + groupId + '"]');
        if (childRows[0] && childRows[0].classList.contains('hidden')) {
            toggleGroup(groupId);
        }
    }
}

// Collapse all sessions
function collapseAllGroups() {
    var allToggles = document.querySelectorAll('.session-toggle');
    for (var i = 0; i < allToggles.length; i++) {
        var groupId = allToggles[i].id.replace('_toggle', '');
        var childRows = document.querySelectorAll('[data-group="' + groupId + '"]');
        if (childRows[0] && !childRows[0].classList.contains('hidden')) {
            toggleGroup(groupId);
        }
    }
}






// CSV Export functionality
function exportToCSV() {
    if (selectedCalls.size === 0) {
        alert('Please select at least one call to export');
        return;
    }
    
    var selectedData = [];
    var checkboxes = document.querySelectorAll('.call-checkbox');
    
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            var row = checkboxes[i].closest('tr');
            var cells = row.querySelectorAll('td');
            if (cells.length >= 9) {
                // Clean text - remove emoji icons for CSV compatibility
                var directionText = cells[2].textContent.trim();
                directionText = directionText.replace(/📞|📱/g, '').trim(); // Remove phone/mobile icons
                
                var statusText = cells[6].textContent.trim();
                var typeText = cells[3].textContent.trim();
                
                selectedData.push({
                    time: cells[1].textContent.trim(),
                    direction: directionText,
                    type: typeText,
                    number: cells[4].textContent.trim(),
                    extension: cells[5].textContent.trim(),
                    status: statusText,
                    talkTime: cells[7].textContent.trim()
                });
            }
        }
    }
    
    if (selectedData.length === 0) {
        alert('No valid calls selected');
        return;
    }
    
    // Create CSV content with UTF-8 BOM for Excel compatibility
    var csv = '\uFEFFTime,Direction,Type,Phone Number,Extension,Status,Talk Time\n';
    
    for (var i = 0; i < selectedData.length; i++) {
        var call = selectedData[i];
        csv += '"' + call.time + '",';
        csv += '"' + call.direction + '",';
        csv += '"' + call.type + '",';
        csv += '"' + call.number + '",';
        csv += '"' + call.extension + '",';
        csv += '"' + call.status + '",';
        csv += '"' + call.talkTime + '"\n';
    }
    
    // Create download
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    
    var filename = 'call_history_' + new Date().toISOString().slice(0,10) + '.csv';
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Print functionality  
var selectedCalls = new Set();

function toggleSelectAll(checkbox) {
    var checkboxes = document.querySelectorAll('.call-checkbox');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = checkbox.checked;
        if (checkbox.checked) {
            selectedCalls.add(checkboxes[i].value);
        } else {
            selectedCalls.delete(checkboxes[i].value);
        }
    }
    updatePrintButton();
}

function toggleCallSelection(checkbox) {
    if (checkbox.checked) {
        selectedCalls.add(checkbox.value);
    } else {
        selectedCalls.delete(checkbox.value);
        var selectAll = document.getElementById('selectAll');
        if (selectAll) selectAll.checked = false;
    }
    updatePrintButton();
}

function updatePrintButton() {
    var printBtn = document.getElementById('printBtn');
    var countSpan = document.getElementById('selectedCount');
    var csvBtn = document.getElementById('csvBtn');
    var csvCountSpan = document.getElementById('csvCount');
    var count = selectedCalls.size;
    
    // Update Print button
    if (countSpan) countSpan.textContent = count;
    if (printBtn) {
        printBtn.style.display = count > 0 ? 'block' : 'none';
    }
    
    // Update CSV button
    if (csvCountSpan) csvCountSpan.textContent = count;
    if (csvBtn) {
        csvBtn.style.display = count > 0 ? 'block' : 'none';
    }
}

function printSelected() {
    if (selectedCalls.size === 0) {
        alert('Please select at least one call to print');
        return;
    }
    
    var selectedData = [];
    var checkboxes = document.querySelectorAll('.call-checkbox');
    
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            var row = checkboxes[i].closest('tr');
            var cells = row.querySelectorAll('td');
            if (cells.length >= 9) {
                selectedData.push({
                    time: cells[1].textContent.trim(),
                    direction: cells[2].textContent.trim(),
                    type: cells[3].textContent.trim(),
                    number: cells[4].textContent.trim(),
                    extension: cells[5].textContent.trim(),
                    status: cells[6].textContent.trim(),
                    talkTime: cells[7].textContent.trim()
                });
            }
        }
    }
    
    if (selectedData.length === 0) {
        alert('No valid calls selected');
        return;
    }
    
    var stats = {
        total: selectedData.length,
        inbound: 0,
        outbound: 0,
        answered: 0,
        missed: 0
    };
    
    for (var i = 0; i < selectedData.length; i++) {
        if (selectedData[i].direction.indexOf('In') >= 0) stats.inbound++;
        if (selectedData[i].direction.indexOf('Out') >= 0) stats.outbound++;
        if (selectedData[i].status.toLowerCase().indexOf('answered') >= 0) stats.answered++;
        if (selectedData[i].status.toLowerCase().indexOf('missed') >= 0) stats.missed++;
    }
    
    createPrintReport(selectedData, stats);
}

function createPrintReport(selectedData, stats) {
    var printDiv = document.createElement('div');
    printDiv.id = 'printReport';
    printDiv.style.cssText = 'display:none;font-family:Arial,sans-serif;padding:40px;background:white;';
    
    var styleEl = document.createElement('style');
    styleEl.id = 'printStyle';
    styleEl.textContent = '@media print{body>*:not(#printReport){display:none!important}#printReport{display:block!important}@page{margin:1cm}}';
    document.head.appendChild(styleEl);
    
    var html = '<div style="text-align:center;margin-bottom:30px;padding-bottom:20px;border-bottom:3px solid #667eea">';
    
    // Logo (using settings)
    if (reportSettings.use_logo && reportSettings.logo_path) {
        html += '<img src="' + reportSettings.logo_path + '" style="width:' + reportSettings.logo_width + 'px;height:auto;margin:0 auto 15px;display:block" onerror="this.style.display=' + "'" + 'none' + "'" + '">';
    }
    
    // Company Info (using settings)
    html += '<div style="margin-bottom:20px">';
    html += '<h3 style="font-size:20px;color:#2d3748;margin:0;font-weight:600">' + reportSettings.company_name + '</h3>';
    if (reportSettings.company_address) {
        html += '<p style="font-size:12px;color:#718096;margin:4px 0 0 0">' + reportSettings.company_address + '</p>';
    }
    if (reportSettings.company_phone || reportSettings.company_email) {
        html += '<p style="font-size:11px;color:#a0aec0;margin:3px 0 0 0">';
        if (reportSettings.company_phone) html += '☎ ' + reportSettings.company_phone;
        if (reportSettings.company_phone && reportSettings.company_email) html += ' | ';
        if (reportSettings.company_email) html += '✉ ' + reportSettings.company_email;
        html += '</p>';
    }
    html += '</div>';
    
    // Report Title (using settings)
    html += '<h1 style="font-size:32px;margin:0 0 8px 0;color:#2d3748;font-weight:700">' + reportSettings.report_title + '</h1>';
    if (reportSettings.report_subtitle) {
        html += '<p style="color:#718096;font-size:14px;margin:0 0 5px 0">' + reportSettings.report_subtitle + '</p>';
    }
    html += '<p style="color:#a0aec0;font-size:12px;margin:0">📅 Generated: ' + new Date().toLocaleString() + '</p>';
    html += '</div>';
    
    html += '<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:25px">';
    html += '<div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:18px;border-radius:8px;text-align:center">';
    html += '<div style="font-size:28px;font-weight:700;margin-bottom:5px">' + stats.total + '</div><div style="font-size:11px;text-transform:uppercase">TOTAL</div></div>';
    html += '<div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:18px;border-radius:8px;text-align:center">';
    html += '<div style="font-size:28px;font-weight:700;margin-bottom:5px">' + stats.inbound + '</div><div style="font-size:11px;text-transform:uppercase">INBOUND</div></div>';
    html += '<div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:18px;border-radius:8px;text-align:center">';
    html += '<div style="font-size:28px;font-weight:700;margin-bottom:5px">' + stats.outbound + '</div><div style="font-size:11px;text-transform:uppercase">OUTBOUND</div></div>';
    html += '<div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:18px;border-radius:8px;text-align:center">';
    html += '<div style="font-size:28px;font-weight:700;margin-bottom:5px">' + stats.answered + '</div><div style="font-size:11px;text-transform:uppercase">ANSWERED</div></div>';
    html += '<div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:18px;border-radius:8px;text-align:center">';
    html += '<div style="font-size:28px;font-weight:700;margin-bottom:5px">' + stats.missed + '</div><div style="font-size:11px;text-transform:uppercase">MISSED</div></div></div>';
    
    html += '<table style="width:100%;border-collapse:collapse;margin-bottom:25px"><thead style="background:#f7fafc"><tr>';
    html += '<th style="padding:12px;text-align:left;border-bottom:2px solid #667eea;font-size:11px;text-transform:uppercase">Time</th>';
    html += '<th style="padding:12px;text-align:left;border-bottom:2px solid #667eea;font-size:11px;text-transform:uppercase">Direction</th>';
    html += '<th style="padding:12px;text-align:left;border-bottom:2px solid #667eea;font-size:11px;text-transform:uppercase">Type</th>';
    html += '<th style="padding:12px;text-align:left;border-bottom:2px solid #667eea;font-size:11px;text-transform:uppercase">Number</th>';
    html += '<th style="padding:12px;text-align:left;border-bottom:2px solid #667eea;font-size:11px;text-transform:uppercase">Extension</th>';
    html += '<th style="padding:12px;text-align:left;border-bottom:2px solid #667eea;font-size:11px;text-transform:uppercase">Status</th>';
    html += '<th style="padding:12px;text-align:left;border-bottom:2px solid #667eea;font-size:11px;text-transform:uppercase">Talk Time</th>';
    html += '</tr></thead><tbody>';
    
    for (var i = 0; i < selectedData.length; i++) {
        var call = selectedData[i];
        var bg = (i % 2 === 0) ? '#f7fafc' : 'white';
        var dirColor = call.direction.indexOf('In') >= 0 ? '#10b981' : '#3b82f6';
        var statusColor = call.status.toLowerCase().indexOf('answered') >= 0 ? '#10b981' : '#ef4444';
        
        html += '<tr style="background:' + bg + '">';
        html += '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px">' + call.time + '</td>';
        html += '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:' + dirColor + ';font-weight:600">' + call.direction + '</td>';
        html += '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px">' + call.type + '</td>';
        html += '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px"><strong>' + call.number + '</strong></td>';
        html += '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px">' + call.extension + '</td>';
        html += '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:' + statusColor + ';font-weight:600">' + call.status + '</td>';
        html += '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px">' + call.talkTime + '</td>';
        html += '</tr>';
    }
    
    html += '</tbody></table>';
    
    // Footer (using settings)
    html += '<div style="margin-top:30px;padding-top:15px;border-top:2px solid #e2e8f0;text-align:center;color:#718096;font-size:12px">';
    if (reportSettings.footer_text) {
        html += '<p style="margin:0">' + reportSettings.footer_text + '</p>';
    }
    html += '<p style="margin:8px 0 0 0">© ' + new Date().getFullYear() + ' ' + reportSettings.company_name + '</p>';
    html += '</div>';
    
    printDiv.innerHTML = html;
    document.body.appendChild(printDiv);
    
    setTimeout(function() {
        window.print();
        setTimeout(function() {
            if (document.getElementById('printReport')) document.body.removeChild(printDiv);
            if (document.getElementById('printStyle')) document.head.removeChild(styleEl);
        }, 1000);
    }, 100);
}
</script>

</body>
</html>