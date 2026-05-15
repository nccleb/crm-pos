<?php
/**
 * ajax/pos_expiry_alert_ajax.php
 * Handles expiry alert AJAX: send email, send SMS, save settings, get log
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['oop']) || empty($_SESSION['ooq'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']); exit();
}

$agent_name = $_SESSION['oop'];
$agent_id   = (int)$_SESSION['ooq'];
$is_super   = ($agent_name === 'super');

$conn = mysqli_connect("192.168.1.19","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn, 'utf8mb4');

require_once __DIR__ . '/pos_log.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Load settings ──────────────────────────────────────────────────────────
function getSettings($conn) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pos_expiry_alert_settings WHERE id=1 LIMIT 1"));
    return $r ?: [];
}

// ── Discount suggestion based on days left ─────────────────────────────────
function discountForDays(int $days, array $cfg): array {
    if ($days <= 0)   return ['pct' => 100, 'label' => 'EXPIRED — Pull', 'color' => '#6b7280', 'urgent' => 'expired'];
    if ($days <= 3)   return ['pct' => (float)$cfg['disc_3d'],  'label' => $cfg['disc_3d'].'% off',  'color' => '#dc2626', 'urgent' => 'critical'];
    if ($days <= 7)   return ['pct' => (float)$cfg['disc_7d'],  'label' => $cfg['disc_7d'].'% off',  'color' => '#f97316', 'urgent' => 'high'];
    if ($days <= 15)  return ['pct' => (float)$cfg['disc_15d'], 'label' => $cfg['disc_15d'].'% off', 'color' => '#eab308', 'urgent' => 'medium'];
    if ($days <= 30)  return ['pct' => (float)$cfg['disc_30d'], 'label' => $cfg['disc_30d'].'% off', 'color' => '#3b82f6', 'urgent' => 'low'];
    return ['pct' => 0, 'label' => 'No discount', 'color' => '#10b981', 'urgent' => 'ok'];
}

// ── SMTP email sender (no external libs needed) ────────────────────────────
function sendSmtpMail(array $cfg, string $to, string $subject, string $htmlBody): array {
    $host    = $cfg['smtp_host']      ?? '';
    $port    = (int)($cfg['smtp_port'] ?? 587);
    $user    = $cfg['smtp_user']      ?? '';
    $pass    = $cfg['smtp_pass']      ?? '';
    $from    = $cfg['smtp_from']      ?? $user;
    $fname   = $cfg['smtp_from_name'] ?? 'NCC POS';

    if (!$host || !$user || !$pass) return ['ok' => false, 'err' => 'SMTP not configured'];

    $prefix = ($port === 465) ? 'ssl://' : '';
    $errno  = 0; $errstr = '';
    $sock   = @fsockopen($prefix.$host, $port, $errno, $errstr, 15);
    if (!$sock) return ['ok' => false, 'err' => "Cannot connect: $errstr ($errno)"];

    $recv = function() use ($sock) {
        $r = ''; while (!feof($sock)) { $l = fgets($sock,512); $r .= $l; if (substr($l,3,1)===' ') break; } return $r;
    };
    $send = function($cmd) use ($sock) { fputs($sock, $cmd."\r\n"); };

    $recv(); // 220 greeting
    $send("EHLO ".$host); $ehlo = $recv();

    // STARTTLS for port 587
    if ($port === 587 && strpos($ehlo,'STARTTLS') !== false) {
        $send('STARTTLS'); $recv();
        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $send("EHLO ".$host); $recv();
    }

    $send('AUTH LOGIN'); $recv();
    $send(base64_encode($user)); $recv();
    $send(base64_encode($pass)); $auth = $recv();
    if (strpos($auth,'235') === false) {
        fclose($sock);
        return ['ok' => false, 'err' => 'Auth failed: '.$auth];
    }

    $boundary = md5(uniqid());
    $send("MAIL FROM:<$from>"); $recv();
    $send("RCPT TO:<$to>"); $recv();
    $send("DATA"); $recv();

    $msg  = "From: =?UTF-8?B?".base64_encode($fname)."?= <$from>\r\n";
    $msg .= "To: $to\r\n";
    $msg .= "Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $msg .= chunk_split(base64_encode($htmlBody))."\r\n";
    $msg .= ".";
    $send($msg); $res = $recv();
    $send("QUIT"); fclose($sock);

    if (strpos($res,'250') !== false) return ['ok' => true];
    return ['ok' => false, 'err' => 'Send failed: '.$res];
}

// ── SMS sender (generic HTTP API or Twilio) ────────────────────────────────
function sendSms(array $cfg, string $to, string $msg): array {
    $provider = $cfg['sms_provider'] ?? 'http_api';

    if ($provider === 'twilio') {
        $sid  = $cfg['sms_api_key']    ?? '';
        $tok  = $cfg['sms_api_secret'] ?? '';
        $from = $cfg['sms_from']       ?? '';
        if (!$sid || !$tok || !$from) return ['ok' => false, 'err' => 'Twilio not configured'];

        $url  = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
        $data = http_build_query(['To' => $to, 'From' => $from, 'Body' => $msg]);
        $ctx  = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Authorization: Basic ".base64_encode("$sid:$tok")."\r\nContent-Type: application/x-www-form-urlencoded\r\n",
            'content' => $data,
            'timeout' => 15,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        $json = $resp ? json_decode($resp, true) : null;
        if ($json && isset($json['sid'])) return ['ok' => true];
        return ['ok' => false, 'err' => $json['message'] ?? 'Twilio error'];
    }

    // Generic HTTP API — POST to configured URL with {to}, {message}, {key} placeholders
    $url = $cfg['sms_api_url'] ?? '';
    $key = $cfg['sms_api_key'] ?? '';
    if (!$url) return ['ok' => false, 'err' => 'SMS API URL not configured'];
    $data = http_build_query(['to' => $to, 'message' => $msg, 'key' => $key, 'from' => $cfg['sms_from'] ?? 'NCCPOS']);
    $ctx  = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $data,
        'timeout' => 15,
    ]]);
    $resp = @file_get_contents($url, false, $ctx);
    // Most APIs return 200 or a JSON success — accept any response as ok
    return $resp !== false ? ['ok' => true] : ['ok' => false, 'err' => 'No response from SMS API'];
}

// ── Build alert HTML body ──────────────────────────────────────────────────
function buildEmailBody(array $batches, string $shopName = 'NCC Store'): string {
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
            <td style='padding:10px 14px;border-bottom:1px solid #f0f0f0;font-weight:700;color:$color;'>{$b['days_left']} days</td>
            <td style='padding:10px 14px;border-bottom:1px solid #f0f0f0;'>{$b['qty_remaining']}</td>
            <td style='padding:10px 14px;border-bottom:1px solid #f0f0f0;font-weight:700;color:$color;'>{$b['disc_label']}</td>
        </tr>";
    }
    $date = date('d M Y');
    return "<!DOCTYPE html><html><body style='margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;'>
<div style='max-width:620px;margin:30px auto;background:white;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.1);'>
  <div style='background:linear-gradient(135deg,#1976D2,#0D47A1);padding:28px 32px;'>
    <h1 style='color:white;margin:0;font-size:22px;'>⚠️ Expiry Alert</h1>
    <p style='color:rgba(255,255,255,.8);margin:6px 0 0;font-size:14px;'>$shopName &nbsp;·&nbsp; $date</p>
  </div>
  <div style='padding:28px 32px;'>
    <p style='color:#374151;font-size:15px;margin:0 0 20px;'>The following product batches require your attention:</p>
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
      <p style='margin:0;font-size:13px;color:#92400e;'>Apply suggested discounts at POS to move stock before expiry. Use Expiry Tracking to pull expired batches.</p>
    </div>
  </div>
  <div style='padding:16px 32px;background:#f8fafc;text-align:center;font-size:12px;color:#9ca3af;'>
    NCC CRM POS &nbsp;·&nbsp; Automated Expiry Alert &nbsp;·&nbsp; $date
  </div>
</div></body></html>";
}

function buildSmsText(array $batches): string {
    $lines = ["NCC POS Expiry Alert — ".date('d M Y').":"];
    foreach (array_slice($batches, 0, 5) as $b) {
        $lines[] = "• {$b['product_name']}: {$b['days_left']}d left, suggest {$b['disc_label']}";
    }
    if (count($batches) > 5) $lines[] = '...and '.(count($batches)-5).' more.';
    $lines[] = "Check pos_expiry.php";
    return implode("\n", $lines);
}

// ── Fetch expiring batches ─────────────────────────────────────────────────
function getExpiringBatches($conn, array $cfg, int $within_days = 30): array {
    $res = mysqli_query($conn,"
        SELECT
            sri.id AS batch_id,
            sri.product_id,
            p.nomp AS product_name,
            p.price,
            p.category,
            p.onhand,
            sri.expiry_date,
            sri.qty_received,
            sri.cost_price_lbp,
            DATEDIFF(sri.expiry_date, CURDATE()) AS days_left,
            sup.name AS supplier_name,
            sr.received_date
        FROM stock_receiving_items sri
        JOIN stock_receivings sr ON sri.receiving_id = sr.id
        JOIN produit p ON sri.product_id = p.codep
        LEFT JOIN pos_suppliers sup ON sr.supplier_id = sup.id
        WHERE p.active = 1
          AND sri.expiry_date IS NOT NULL
          AND sri.expiry_date <= DATE_ADD(CURDATE(), INTERVAL $within_days DAY)
        ORDER BY sri.expiry_date ASC, p.nomp ASC
    ");
    $batches = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $days  = (int)$r['days_left'];
        $disc  = discountForDays($days, $cfg);
        $remaining = (float)$r['onhand']; // use actual stock on hand — already accounts for all sales
        $batches[] = [
            'batch_id'     => $r['batch_id'],
            'product_id'   => $r['product_id'],
            'product_name' => $r['product_name'],
            'category'     => $r['category'],
            'price'        => (float)$r['price'],
            'expiry_date'  => $r['expiry_date'],
            'days_left'    => $days,
            'qty_received' => (float)$r['qty_received'],
            'qty_remaining'=> round($remaining, 2),
            'cost_price'   => (float)$r['cost_price_lbp'],
            'supplier'     => $r['supplier_name'] ?? '—',
            'received_date'=> $r['received_date'],
            'disc_pct'     => $disc['pct'],
            'disc_label'   => $disc['label'],
            'disc_color'   => $disc['color'],
            'urgent'       => $disc['urgent'],
            'suggested_price' => $days > 0 && $disc['pct'] > 0
                ? round((float)$r['price'] * (1 - $disc['pct']/100) / 5000) * 5000
                : (float)$r['price'],
        ];
    }
    return $batches;
}

// ══════════════════════════════════════════════════════════════════════════
switch ($action) {

    // ── Get batches + settings ─────────────────────────────────────────
    case 'get_data':
        $cfg     = getSettings($conn);
        $within  = (int)($_GET['within'] ?? 30);
        $batches = getExpiringBatches($conn, $cfg, $within);
        echo json_encode(['success' => true, 'batches' => $batches, 'settings' => $cfg]);
        break;

    // ── Save settings ──────────────────────────────────────────────────
    case 'save_settings':
        if (!$is_super) { echo json_encode(['success'=>false,'error'=>'Super only']); break; }
        $fields = [
            'alert_days_1','alert_days_2','alert_days_3',
            'email_enabled','smtp_host','smtp_port','smtp_user','smtp_pass',
            'smtp_from','smtp_from_name','alert_email_to',
            'sms_enabled','sms_provider','sms_api_url','sms_api_key',
            'sms_api_secret','sms_from','alert_sms_to',
            'disc_30d','disc_15d','disc_7d','disc_3d'
        ];
        $parts = [];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $v = mysqli_real_escape_string($conn, $_POST[$f]);
                $parts[] = "$f='$v'";
            }
        }
        if ($parts) {
            mysqli_query($conn, "UPDATE pos_expiry_alert_settings SET ".implode(',',$parts)." WHERE id=1");
        }
        posLog($conn, $agent_id, $agent_name, 'settings_saved', 'Expiry alert settings updated', 1, 'settings');
        echo json_encode(['success' => true]);
        break;

    // ── Send email alert ───────────────────────────────────────────────
    case 'send_email':
        $cfg     = getSettings($conn);
        $within  = (int)($_POST['within'] ?? 30);
        $batches = getExpiringBatches($conn, $cfg, $within);

        if (empty($batches)) { echo json_encode(['success'=>false,'error'=>'No expiring batches']); break; }
        if (!$cfg['email_enabled']) { echo json_encode(['success'=>false,'error'=>'Email alerts not enabled']); break; }

        $recipients = array_filter(array_map('trim', explode(',', $cfg['alert_email_to'])));
        if (empty($recipients)) { echo json_encode(['success'=>false,'error'=>'No recipient email configured']); break; }

        $subject  = '⚠️ Expiry Alert — '.count($batches).' batch'.( count($batches)!=1?'es':'' ).' expiring within '.$within.' days';
        $htmlBody = buildEmailBody($batches, $cfg['smtp_from_name'] ?? 'NCC Store');
        $batchIds = json_encode(array_column($batches,'batch_id'));

        $errors = []; $sent = 0;
        foreach ($recipients as $email) {
            $result = sendSmtpMail($cfg, $email, $subject, $htmlBody);
            $status = $result['ok'] ? 'sent' : 'failed';
            $errMsg = $result['ok'] ? 'NULL' : "'".mysqli_real_escape_string($conn,$result['err'])."'";
            mysqli_query($conn,"INSERT INTO pos_expiry_alert_log
                (channel,recipient,subject,body,batch_ids,status,error_msg,sent_by)
                VALUES('email','".mysqli_real_escape_string($conn,$email)."',
                '".mysqli_real_escape_string($conn,$subject)."',
                '".mysqli_real_escape_string($conn,$htmlBody)."',
                '".mysqli_real_escape_string($conn,$batchIds)."',
                '$status',$errMsg,'".mysqli_real_escape_string($conn,$agent_name)."')");
            if ($result['ok']) $sent++;
            else $errors[] = $email.': '.$result['err'];
        }
        posLog($conn,$agent_id,$agent_name,'expiry_alert_sent',"Email alert sent to $sent recipient(s) — ".count($batches)." batches",null,'expiry');
        echo json_encode(['success'=>true,'sent'=>$sent,'errors'=>$errors,'total'=>count($recipients)]);
        break;

    // ── Send SMS alert ─────────────────────────────────────────────────
    case 'send_sms':
        $cfg     = getSettings($conn);
        $within  = (int)($_POST['within'] ?? 30);
        $batches = getExpiringBatches($conn, $cfg, $within);

        if (empty($batches)) { echo json_encode(['success'=>false,'error'=>'No expiring batches']); break; }
        if (!$cfg['sms_enabled']) { echo json_encode(['success'=>false,'error'=>'SMS alerts not enabled']); break; }

        $recipients = array_filter(array_map('trim', explode(',', $cfg['alert_sms_to'])));
        if (empty($recipients)) { echo json_encode(['success'=>false,'error'=>'No recipient phone configured']); break; }

        $smsText  = buildSmsText($batches);
        $batchIds = json_encode(array_column($batches,'batch_id'));
        $errors = []; $sent = 0;

        foreach ($recipients as $phone) {
            $result = sendSms($cfg, $phone, $smsText);
            $status = $result['ok'] ? 'sent' : 'failed';
            $errMsg = $result['ok'] ? 'NULL' : "'".mysqli_real_escape_string($conn,$result['err'])."'";
            mysqli_query($conn,"INSERT INTO pos_expiry_alert_log
                (channel,recipient,subject,body,batch_ids,status,error_msg,sent_by)
                VALUES('sms','".mysqli_real_escape_string($conn,$phone)."',
                'Expiry SMS Alert','".mysqli_real_escape_string($conn,$smsText)."',
                '".mysqli_real_escape_string($conn,$batchIds)."',
                '$status',$errMsg,'".mysqli_real_escape_string($conn,$agent_name)."')");
            if ($result['ok']) $sent++;
            else $errors[] = $phone.': '.$result['err'];
        }
        posLog($conn,$agent_id,$agent_name,'expiry_alert_sent',"SMS alert sent to $sent recipient(s) — ".count($batches)." batches",null,'expiry');
        echo json_encode(['success'=>true,'sent'=>$sent,'errors'=>$errors,'total'=>count($recipients)]);
        break;

    // ── Test email ─────────────────────────────────────────────────────
    case 'test_email':
        if (!$is_super) { echo json_encode(['success'=>false,'error'=>'Super only']); break; }
        $cfg = getSettings($conn);
        $to  = trim($_POST['test_to'] ?? $cfg['alert_email_to'] ?? '');
        if (!$to) { echo json_encode(['success'=>false,'error'=>'No recipient']); break; }
        $result = sendSmtpMail($cfg, $to,
            'NCC POS — Test Email from Expiry Alerts',
            '<h2 style="font-family:Arial">✅ Test email from NCC POS Expiry Alerts</h2><p>SMTP is working correctly.</p>'
        );
        echo json_encode($result['ok']
            ? ['success'=>true, 'message'=>'Test email sent to '.$to]
            : ['success'=>false, 'error'=>$result['err']]
        );
        break;

    // ── Get alert log ──────────────────────────────────────────────────
    case 'get_log':
        $limit = min(50,(int)($_GET['limit']??20));
        $res = mysqli_query($conn,"SELECT id,channel,recipient,subject,status,error_msg,sent_by,created_at
            FROM pos_expiry_alert_log ORDER BY created_at DESC LIMIT $limit");
        $log = [];
        while ($r = mysqli_fetch_assoc($res)) $log[] = $r;
        echo json_encode(['success'=>true,'log'=>$log]);
        break;

    default:
        echo json_encode(['success'=>false,'error'=>'Unknown action']);
}
mysqli_close($conn);
