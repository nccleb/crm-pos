<?php
/**
 * pos_expiry_alert_cron.php
 * Scheduled expiry alert runner — called by Windows Task Scheduler daily
 * Checks thresholds and sends email/SMS only when days_left crosses alert_days_1/2/3
 *
 * Usage: C:\wamp64\bin\php\php8.3.14\php.exe C:\wamp64\www\pos_expiry_alert_cron.php
 *
 * Log file: C:\wamp64\www\logs\expiry_cron.log
 */

// Force output immediately — no buffering
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

define('POS_ROOT', __DIR__);
define('LOG_FILE', POS_ROOT . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'expiry_cron.log');

// ── Ensure log directory exists ────────────────────────────────────────────
$log_dir = dirname(LOG_FILE);
if (!is_dir($log_dir)) {
    if (!mkdir($log_dir, 0777, true)) {
        echo '[' . date('Y-m-d H:i:s') . '] FATAL: Cannot create log directory: ' . $log_dir . PHP_EOL;
        exit(1);
    }
}

// ── Logger ─────────────────────────────────────────────────────────────────
function logMsg(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    // Write directly to file — no @ suppressor so errors are visible
    $result = file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    if ($result === false) {
        echo 'LOG WRITE FAILED: ' . LOG_FILE . PHP_EOL;
    }
    echo $line;
}

// ── Log rotation — keep last 30 days only ─────────────────────────────────
function rotateLog(): void {
    if (!file_exists(LOG_FILE)) return;

    $lines    = file(LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return;

    $cutoff   = date('Y-m-d', strtotime('-30 days'));
    $filtered = array_filter($lines, function(string $line) use ($cutoff): bool {
        // Line format: [2026-05-13 20:16:49] ...
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2})/', $line, $m)) {
            return $m[1] >= $cutoff;
        }
        return true; // keep lines that don't match the format
    });

    $kept    = count($filtered);
    $removed = count($lines) - $kept;

    if ($removed > 0) {
        file_put_contents(LOG_FILE, implode(PHP_EOL, $filtered) . PHP_EOL, LOCK_EX);
        logMsg("Log rotated — removed $removed old line(s), kept $kept line(s) (last 30 days).");
    }
}

logMsg('=== Expiry Alert Cron Started ===');
rotateLog();

// ── DB connection ──────────────────────────────────────────────────────────
$conn = mysqli_connect("192.168.1.14", "root", "1Sys9Admeen72", "nccleb_test");
if (!$conn) {
    logMsg('ERROR: DB connection failed — ' . mysqli_connect_error());
    exit(1);
}
mysqli_set_charset($conn, 'utf8mb4');

// ── Load settings ──────────────────────────────────────────────────────────
$cfg = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM pos_expiry_alert_settings WHERE id=1 LIMIT 1"));

if (!$cfg) {
    logMsg('ERROR: No alert settings found. Run the SQL setup first.');
    mysqli_close($conn);
    exit(1);
}

$email_enabled = (int)$cfg['email_enabled'];
$sms_enabled   = (int)$cfg['sms_enabled'];
$thresholds    = [(int)$cfg['alert_days_1'], (int)$cfg['alert_days_2'], (int)$cfg['alert_days_3']];

logMsg("Settings loaded — Email: " . ($email_enabled ? 'ON' : 'OFF')
    . " | SMS: " . ($sms_enabled ? 'ON' : 'OFF')
    . " | Thresholds: " . implode('/', $thresholds) . " days");

if (!$email_enabled && !$sms_enabled) {
    logMsg('Both channels disabled — nothing to do. Exiting.');
    mysqli_close($conn);
    exit(0);
}

// ── Discount helper ────────────────────────────────────────────────────────
function discountForDays(int $days, array $cfg): array {
    if ($days <= 0)  return ['pct' => 100, 'label' => 'EXPIRED — Pull',  'urgent' => 'expired'];
    if ($days <= 3)  return ['pct' => (float)$cfg['disc_3d'],  'label' => $cfg['disc_3d'].'% off',  'urgent' => 'critical'];
    if ($days <= 7)  return ['pct' => (float)$cfg['disc_7d'],  'label' => $cfg['disc_7d'].'% off',  'urgent' => 'high'];
    if ($days <= 15) return ['pct' => (float)$cfg['disc_15d'], 'label' => $cfg['disc_15d'].'% off', 'urgent' => 'medium'];
    if ($days <= 30) return ['pct' => (float)$cfg['disc_30d'], 'label' => $cfg['disc_30d'].'% off', 'urgent' => 'low'];
    return ['pct' => 0, 'label' => 'No discount', 'urgent' => 'ok'];
}

// ── Fetch batches that hit a threshold TODAY ───────────────────────────────
// A batch "hits" a threshold when days_left equals one of the configured values
// This prevents sending duplicate alerts on non-threshold days
$threshold_in = implode(',', array_map('intval', $thresholds));

$res = mysqli_query($conn, "
    SELECT
        sri.id AS batch_id,
        sri.product_id,
        p.nomp AS product_name,
        p.price,
        p.category,
        sri.expiry_date,
        sri.qty_received,
        sri.cost_price_lbp,
        DATEDIFF(sri.expiry_date, CURDATE()) AS days_left,
        (SELECT SUM(si2.qty) FROM pos_sale_items si2
         JOIN pos_sales s2 ON si2.sale_id = s2.id
         WHERE si2.product_id = sri.product_id AND s2.status='completed'
         AND s2.created_at >= (SELECT sr2.received_date FROM stock_receivings sr2 WHERE sr2.id = sri.receiving_id)
        ) AS qty_sold,
        sup.name AS supplier_name
    FROM stock_receiving_items sri
    JOIN stock_receivings sr ON sri.receiving_id = sr.id
    JOIN produit p ON sri.product_id = p.codep
    LEFT JOIN pos_suppliers sup ON sr.supplier_id = sup.id
    WHERE p.active = 1
      AND sri.expiry_date IS NOT NULL
      AND DATEDIFF(sri.expiry_date, CURDATE()) IN ($threshold_in)
    ORDER BY sri.expiry_date ASC, p.nomp ASC
");

$batches = [];
while ($r = mysqli_fetch_assoc($res)) {
    $days  = (int)$r['days_left'];
    $disc  = discountForDays($days, $cfg);
    $sold  = (float)($r['qty_sold'] ?? 0);
    $remaining = max(0, (float)$r['qty_received'] - $sold);
    $batches[] = [
        'batch_id'      => $r['batch_id'],
        'product_name'  => $r['product_name'],
        'category'      => $r['category'],
        'price'         => (float)$r['price'],
        'expiry_date'   => $r['expiry_date'],
        'days_left'     => $days,
        'qty_remaining' => round($remaining, 2),
        'cost_price'    => (float)$r['cost_price_lbp'],
        'supplier'      => $r['supplier_name'] ?? '—',
        'disc_pct'      => $disc['pct'],
        'disc_label'    => $disc['label'],
        'urgent'        => $disc['urgent'],
    ];
}

if (empty($batches)) {
    logMsg('No batches hit a threshold today (' . $threshold_in . ' days). No alerts sent.');
    mysqli_close($conn);
    exit(0);
}

logMsg(count($batches) . ' batch(es) hit a threshold today — preparing alerts.');

// ── SMTP send ──────────────────────────────────────────────────────────────
function sendSmtpMail(array $cfg, string $to, string $subject, string $htmlBody): array {
    $host  = $cfg['smtp_host']      ?? '';
    $port  = (int)($cfg['smtp_port'] ?? 587);
    $user  = $cfg['smtp_user']      ?? '';
    $pass  = $cfg['smtp_pass']      ?? '';
    $from  = $cfg['smtp_from']      ?? $user;
    $fname = $cfg['smtp_from_name'] ?? 'NCC POS';

    if (!$host || !$user || !$pass) return ['ok' => false, 'err' => 'SMTP not configured'];

    $prefix = ($port === 465) ? 'ssl://' : '';
    $errno = 0; $errstr = '';
    $sock = @fsockopen($prefix.$host, $port, $errno, $errstr, 15);
    if (!$sock) return ['ok' => false, 'err' => "Cannot connect: $errstr ($errno)"];

    $recv = function() use ($sock) {
        $r = ''; while (!feof($sock)) { $l = fgets($sock, 512); $r .= $l; if (substr($l,3,1) === ' ') break; } return $r;
    };
    $send = function($cmd) use ($sock) { fputs($sock, $cmd."\r\n"); };

    $recv();
    $send("EHLO $host"); $ehlo = $recv();

    if ($port === 587 && strpos($ehlo,'STARTTLS') !== false) {
        $send('STARTTLS'); $recv();
        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $send("EHLO $host"); $recv();
    }

    $send('AUTH LOGIN'); $recv();
    $send(base64_encode($user)); $recv();
    $send(base64_encode($pass)); $auth = $recv();
    if (strpos($auth,'235') === false) { fclose($sock); return ['ok'=>false,'err'=>'Auth failed: '.$auth]; }

    $send("MAIL FROM:<$from>"); $recv();
    $send("RCPT TO:<$to>"); $recv();
    $send("DATA"); $recv();

    $msg  = "From: =?UTF-8?B?".base64_encode($fname)."?= <$from>\r\n";
    $msg .= "To: $to\r\n";
    $msg .= "Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\n";
    $msg .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $msg .= chunk_split(base64_encode($htmlBody))."\r\n.";
    $send($msg); $res = $recv();
    $send("QUIT"); fclose($sock);

    return strpos($res,'250') !== false ? ['ok'=>true] : ['ok'=>false,'err'=>'Send failed: '.$res];
}

// ── SMS send ───────────────────────────────────────────────────────────────
function sendSms(array $cfg, string $to, string $msg): array {
    $provider = $cfg['sms_provider'] ?? 'http_api';
    if ($provider === 'twilio') {
        $sid  = $cfg['sms_api_key'] ?? '';
        $tok  = $cfg['sms_api_secret'] ?? '';
        $from = $cfg['sms_from'] ?? '';
        if (!$sid || !$tok || !$from) return ['ok'=>false,'err'=>'Twilio not configured'];
        $url  = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
        $data = http_build_query(['To'=>$to,'From'=>$from,'Body'=>$msg]);
        $ctx  = stream_context_create(['http'=>['method'=>'POST',
            'header'=>"Authorization: Basic ".base64_encode("$sid:$tok")."\r\nContent-Type: application/x-www-form-urlencoded\r\n",
            'content'=>$data,'timeout'=>15]]);
        $resp = @file_get_contents($url, false, $ctx);
        $json = $resp ? json_decode($resp,true) : null;
        return ($json && isset($json['sid'])) ? ['ok'=>true] : ['ok'=>false,'err'=>$json['message']??'Twilio error'];
    }
    $url = $cfg['sms_api_url'] ?? '';
    $key = $cfg['sms_api_key'] ?? '';
    if (!$url) return ['ok'=>false,'err'=>'SMS API URL not configured'];
    $data = http_build_query(['to'=>$to,'message'=>$msg,'key'=>$key,'from'=>$cfg['sms_from']??'NCCPOS']);
    $ctx  = stream_context_create(['http'=>['method'=>'POST',
        'header'=>"Content-Type: application/x-www-form-urlencoded\r\n",'content'=>$data,'timeout'=>15]]);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp !== false ? ['ok'=>true] : ['ok'=>false,'err'=>'No response from SMS API'];
}

// ── Build email HTML ───────────────────────────────────────────────────────
function buildEmailHtml(array $batches, array $cfg): string {
    $rows = '';
    foreach ($batches as $b) {
        $color = match($b['urgent']) {
            'expired'  => '#6b7280',
            'critical' => '#dc2626',
            'high'     => '#f97316',
            'medium'   => '#eab308',
            default    => '#3b82f6',
        };
        $rows .= "<tr>
            <td style='padding:10px 14px;border-bottom:1px solid #f0f0f0;font-weight:600;'>{$b['product_name']}</td>
            <td style='padding:10px 14px;border-bottom:1px solid #f0f0f0;'>{$b['expiry_date']}</td>
            <td style='padding:10px 14px;border-bottom:1px solid #f0f0f0;font-weight:800;color:$color;'>{$b['days_left']} days</td>
            <td style='padding:10px 14px;border-bottom:1px solid #f0f0f0;'>{$b['qty_remaining']}</td>
            <td style='padding:10px 14px;border-bottom:1px solid #f0f0f0;font-weight:700;color:$color;'>{$b['disc_label']}</td>
        </tr>";
    }
    $date      = date('d M Y');
    $shopName  = $cfg['smtp_from_name'] ?? 'NCC Store';
    $threshold = implode('/', [(int)$cfg['alert_days_1'],(int)$cfg['alert_days_2'],(int)$cfg['alert_days_3']]);
    return "<!DOCTYPE html><html><body style='margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;'>
<div style='max-width:620px;margin:30px auto;background:white;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.1);'>
  <div style='background:linear-gradient(135deg,#1976D2,#0D47A1);padding:28px 32px;'>
    <h1 style='color:white;margin:0;font-size:22px;'>⚠️ Scheduled Expiry Alert</h1>
    <p style='color:rgba(255,255,255,.8);margin:6px 0 0;font-size:14px;'>$shopName &nbsp;·&nbsp; $date &nbsp;·&nbsp; Threshold: $threshold days</p>
  </div>
  <div style='padding:28px 32px;'>
    <p style='color:#374151;font-size:15px;margin:0 0 20px;'>The following batches crossed an alert threshold today:</p>
    <table style='width:100%;border-collapse:collapse;font-size:14px;'>
      <thead><tr style='background:#f8fafc;'>
        <th style='padding:10px 14px;text-align:left;color:#6b7280;font-size:12px;text-transform:uppercase;'>Product</th>
        <th style='padding:10px 14px;text-align:left;color:#6b7280;font-size:12px;text-transform:uppercase;'>Expiry</th>
        <th style='padding:10px 14px;text-align:left;color:#6b7280;font-size:12px;text-transform:uppercase;'>Days Left</th>
        <th style='padding:10px 14px;text-align:left;color:#6b7280;font-size:12px;text-transform:uppercase;'>Qty</th>
        <th style='padding:10px 14px;text-align:left;color:#6b7280;font-size:12px;text-transform:uppercase;'>Suggested Discount</th>
      </tr></thead>
      <tbody>$rows</tbody>
    </table>
    <div style='margin-top:24px;padding:16px;background:#fffbeb;border-left:4px solid #f59e0b;border-radius:4px;'>
      <p style='margin:0;font-size:13px;color:#92400e;'>Apply suggested discounts at POS to move stock. Use Expiry Tracking to pull expired batches.</p>
    </div>
  </div>
  <div style='padding:16px 32px;background:#f8fafc;text-align:center;font-size:12px;color:#9ca3af;'>
    NCC CRM POS &nbsp;·&nbsp; Automated Scheduled Alert &nbsp;·&nbsp; $date
  </div>
</div></body></html>";
}

// ── Build SMS text ─────────────────────────────────────────────────────────
function buildSmsText(array $batches): string {
    $lines = ["NCC POS Expiry Alert — ".date('d M Y').":"];
    foreach (array_slice($batches,0,5) as $b) {
        $lines[] = "• {$b['product_name']}: {$b['days_left']}d left, suggest {$b['disc_label']}";
    }
    if (count($batches) > 5) $lines[] = "...and ".(count($batches)-5)." more.";
    $lines[] = "Check pos_expiry.php";
    return implode("\n",$lines);
}

// ── Log helper ─────────────────────────────────────────────────────────────
function logAlert($conn, string $channel, string $recipient, string $subject,
                   string $body, array $batches, bool $ok, string $err, string $agent): void {
    $ch  = mysqli_real_escape_string($conn, $channel);
    $rec = mysqli_real_escape_string($conn, $recipient);
    $sub = mysqli_real_escape_string($conn, $subject);
    $bod = mysqli_real_escape_string($conn, $body);
    $ids = mysqli_real_escape_string($conn, json_encode(array_column($batches,'batch_id')));
    $st  = $ok ? 'sent' : 'failed';
    $em  = $ok ? 'NULL' : "'".mysqli_real_escape_string($conn,$err)."'";
    $ag  = mysqli_real_escape_string($conn, $agent);
    mysqli_query($conn,"INSERT INTO pos_expiry_alert_log
        (channel,recipient,subject,body,batch_ids,status,error_msg,sent_by)
        VALUES('$ch','$rec','$sub','$bod','$ids','$st',$em,'$ag')");
}

// ── Send emails ────────────────────────────────────────────────────────────
if ($email_enabled) {
    $recipients = array_filter(array_map('trim', explode(',', $cfg['alert_email_to'] ?? '')));
    if (empty($recipients)) {
        logMsg('Email enabled but no recipients configured — skipping.');
    } else {
        $subject  = '⚠️ Expiry Alert — '.count($batches).' batch'.(count($batches)!=1?'es':'').' crossing threshold today';
        $htmlBody = buildEmailHtml($batches, $cfg);
        foreach ($recipients as $email) {
            $result = sendSmtpMail($cfg, $email, $subject, $htmlBody);
            logAlert($conn,'email',$email,$subject,$htmlBody,$batches,$result['ok'],$result['err']??'','cron');
            logMsg($result['ok']
                ? "Email sent → $email"
                : "Email FAILED → $email: ".($result['err']??'unknown'));
        }
    }
}

// ── Send SMS ───────────────────────────────────────────────────────────────
if ($sms_enabled) {
    $phones = array_filter(array_map('trim', explode(',', $cfg['alert_sms_to'] ?? '')));
    if (empty($phones)) {
        logMsg('SMS enabled but no recipients configured — skipping.');
    } else {
        $smsText = buildSmsText($batches);
        foreach ($phones as $phone) {
            $result = sendSms($cfg, $phone, $smsText);
            logAlert($conn,'sms',$phone,'Expiry SMS Alert',$smsText,$batches,$result['ok'],$result['err']??'','cron');
            logMsg($result['ok']
                ? "SMS sent → $phone"
                : "SMS FAILED → $phone: ".($result['err']??'unknown'));
        }
    }
}

logMsg('=== Cron Finished ===');
mysqli_close($conn);
exit(0);
