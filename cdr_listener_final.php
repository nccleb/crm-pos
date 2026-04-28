<?php
// FINAL UCM6202 CDR Listener - Handles ANSWERED and NO ANSWER
echo "=== UCM6202 Real-time CDR Listener (FINAL) ===\n";
echo "Listening on port 8087 for CDR data...\n";
echo "Press Ctrl+C to stop\n\n";

$address = '0.0.0.0';
$port = 8087;

$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 1, 'usec' => 0]);
socket_bind($sock, $address, $port);
socket_listen($sock, 5);

echo "✅ Listening on {$address}:{$port}\n\n";

$idr = @mysqli_connect("localhost", "root", "1Sys9Admeen72", "nccleb_test");
if (!$idr) {
    die("❌ Database connection failed: " . mysqli_connect_error() . "\n");
}
echo "✅ Database connected\n\n";

$connection_count = 0;

while (true) {
    $client = @socket_accept($sock);
    if ($client === false) continue;
    
    $connection_count++;
    
    $data = '';
    $read_start = microtime(true);
    while ($buffer = @socket_read($client, 4096, PHP_NORMAL_READ)) {
        $data .= $buffer;
        if (microtime(true) - $read_start > 2) break;
    }
    
    socket_close($client);
    
    if (empty($data)) continue;
    
    // Parse CSV
    $lines = explode("\n", trim($data));
    foreach ($lines as $line) {
        $fields = str_getcsv($line);
        if (count($fields) < 30) continue;
        
        // Extract phone number
        $src = preg_replace('/[^0-9+]/', '', trim($fields[2] ?? '', '"\''));
        if (empty($src) || strlen($src) < 7) continue;
        
        $field_count = count($fields);
        
        if ($field_count >= 35) {
            // AGENT RECORD (40 fields) - Can be ANSWERED or NO ANSWER
            $dst = trim($fields[3] ?? '', '"\'');
            $calldate = trim($fields[17] ?? '', '"\'');
            $answered_time = trim($fields[18] ?? '', '"\'');
            $end_time = trim($fields[19] ?? '', '"\'');
            $duration = intval(trim($fields[20] ?? 0, '"\''));
            $billsec = intval(trim($fields[21] ?? 0, '"\''));
            $disposition = strtoupper(trim($fields[22] ?? '', '"\''));
            
            // Only process agent extensions (6001-6099)
            if (!is_numeric($dst)) continue;
            $dst_num = intval($dst);
            if ($dst_num < 6001 || $dst_num > 6099) continue;
            
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "[" . date('Y-m-d H:i:s') . "] CDR #{$connection_count}\n";
            echo "📞 From: {$src} → Agent: {$dst}\n";
            echo "   Disposition: {$disposition}\n";
            echo "   Duration: {$duration}s\n";
            echo "   Billsec: {$billsec}s\n";
            
            // Determine status based on disposition AND billsec
            if ($disposition === 'ANSWERED' && $billsec > 0) {
                echo "   ✅ STATUS: ANSWERED (talked {$billsec}s)\n\n";
                updateCall($src, $dst, 'answered', $answered_time, $idr);
            } elseif ($disposition === 'NO ANSWER') {
                echo "   ❌ STATUS: MISSED (disposition: NO ANSWER)\n\n";
                updateCall($src, $dst, 'missed', null, $idr);
            } elseif ($disposition === 'ANSWERED' && $billsec == 0) {
                echo "   ❌ STATUS: MISSED (disposition ANSWERED but billsec=0)\n\n";
                updateCall($src, $dst, 'missed', null, $idr);
            } else {
                echo "   ❌ STATUS: MISSED (disposition: {$disposition})\n\n";
                updateCall($src, $dst, 'missed', null, $idr);
            }
        }
    }
}

socket_close($sock);
mysqli_close($idr);

function updateCall($phone, $extension, $status, $answered_time, $db) {
    // Find matching call
    $query = "SELECT id FROM call_history 
              WHERE phone_number = ? 
              AND call_status = 'ringing'
              AND call_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
              ORDER BY call_time DESC
              LIMIT 1";
    
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $phone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $call_id = $row['id'];
        
        $update = "UPDATE call_history 
                  SET call_status = ?, 
                      line_number = ?,
                      answered_time = ?,
                      channel_state = 'cdr_realtime'
                  WHERE id = ?";
        
        $upd_stmt = mysqli_prepare($db, $update);
        mysqli_stmt_bind_param($upd_stmt, "sssi", $status, $extension, $answered_time, $call_id);
        
        if (mysqli_stmt_execute($upd_stmt)) {
            echo "✅ Updated call #{$call_id}:\n";
            echo "   Status: {$status}\n";
            echo "   Extension: {$extension}\n";
            if ($answered_time) {
                echo "   Answered: {$answered_time}\n";
            }
            echo "\n";
        } else {
            echo "❌ Update failed: " . mysqli_error($db) . "\n\n";
        }
        
        mysqli_stmt_close($upd_stmt);
    } else {
        echo "⚠️ No matching ringing call found for {$phone}\n\n";
    }
    
    mysqli_stmt_close($stmt);
}
?>