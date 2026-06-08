<?php
/**
 * pos_expiry_alerts.php
 * Expiry Alert Center — NCC CRM POS v4.4
 */
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$is_super   = ($_SESSION['oop'] === 'super');
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$conn = mysqli_connect("192.168.1.19","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

$cfg = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM pos_expiry_alert_settings WHERE id=1 LIMIT 1")) ?: [];
$cfg += [
    'alert_days_1'=>30,'alert_days_2'=>14,'alert_days_3'=>7,
    'email_enabled'=>0,'smtp_host'=>'smtp.gmail.com','smtp_port'=>587,
    'smtp_user'=>'','smtp_pass'=>'','smtp_from'=>'','smtp_from_name'=>'NCC POS',
    'alert_email_to'=>'','sms_enabled'=>0,'sms_provider'=>'http_api',
    'sms_api_url'=>'','sms_api_key'=>'','sms_api_secret'=>'','sms_from'=>'NCCPOS',
    'alert_sms_to'=>'','disc_30d'=>10,'disc_15d'=>20,'disc_7d'=>35,'disc_3d'=>50
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Expiry Alert Center — NCC POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#0f172a;font-family:'Segoe UI',sans-serif;font-size:14px;color:#e2e8f0;min-height:100vh;}

/* ── Topbar ── */
.topbar{background:linear-gradient(135deg,#1e3a5f,#0f2d50);padding:14px 24px;
        display:flex;align-items:center;gap:14px;flex-wrap:wrap;
        border-bottom:1px solid rgba(255,255,255,.07);}
.topbar h1{font-size:17px;font-weight:800;color:#fff;display:flex;align-items:center;gap:9px;}
.topbar h1 .icon{width:32px;height:32px;background:linear-gradient(135deg,#f59e0b,#ef4444);
    border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.topbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:12px;font-weight:600;
          background:rgba(255,255,255,.1);padding:6px 14px;border-radius:7px;
          display:flex;align-items:center;gap:6px;transition:all .2s;}
.topbar a:hover{background:rgba(255,255,255,.18);color:#fff;}
.topbar .ml{margin-left:auto;font-size:12px;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:6px;}

/* ── Layout ── */
.wrap{max-width:1300px;margin:24px auto;padding:0 20px;}

/* ── Stats ── */
.stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:24px;}
.stat{background:linear-gradient(135deg,rgba(255,255,255,.05),rgba(255,255,255,.02));
      border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:18px 20px;position:relative;overflow:hidden;}
.stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.stat.s-expired::before{background:#6b7280;}
.stat.s-critical::before{background:linear-gradient(90deg,#dc2626,#f97316);}
.stat.s-soon::before{background:linear-gradient(90deg,#f59e0b,#eab308);}
.stat.s-watch::before{background:linear-gradient(90deg,#3b82f6,#60a5fa);}
.stat .sv{font-size:28px;font-weight:900;line-height:1;}
.stat.s-expired .sv{color:#9ca3af;}
.stat.s-critical .sv{color:#f87171;}
.stat.s-soon .sv{color:#fbbf24;}
.stat.s-watch .sv{color:#60a5fa;}
.stat .sl{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;
          color:rgba(255,255,255,.4);margin-top:5px;}

/* ── Action bar ── */
.action-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px;}
.within-sel{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);
            color:#e2e8f0;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;}
.within-sel:focus{outline:none;border-color:#f59e0b;}
.within-sel option{background:#1e293b;}

.btn{padding:9px 18px;border:none;border-radius:9px;font-size:13px;font-weight:700;
     cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:all .2s;text-decoration:none;}
.btn-email{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:white;}
.btn-email:hover{background:linear-gradient(135deg,#1d4ed8,#1e40af);transform:translateY(-1px);}
.btn-sms{background:linear-gradient(135deg,#16a34a,#15803d);color:white;}
.btn-sms:hover{background:linear-gradient(135deg,#15803d,#166534);transform:translateY(-1px);}
.btn-settings{background:rgba(255,255,255,.08);color:#e2e8f0;border:1px solid rgba(255,255,255,.12);}
.btn-settings:hover{background:rgba(255,255,255,.14);}
.btn-log{background:rgba(255,255,255,.06);color:#94a3b8;border:1px solid rgba(255,255,255,.08);}
.btn-log:hover{background:rgba(255,255,255,.1);}
.btn-sm{padding:6px 12px;font-size:11px;border-radius:7px;}
.btn-apply{background:linear-gradient(135deg,#f59e0b,#d97706);color:white;}
.btn-apply:hover{background:linear-gradient(135deg,#d97706,#b45309);}
.btn-pull{background:rgba(220,38,38,.2);color:#f87171;border:1px solid rgba(220,38,38,.3);}
.btn-pull:hover{background:rgba(220,38,38,.3);}

/* ── Channel badge ── */
.ch-badge{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;
          border-radius:20px;font-size:11px;font-weight:700;}
.ch-on {background:rgba(16,185,129,.2);color:#6ee7b7;border:1px solid rgba(16,185,129,.3);}
.ch-off{background:rgba(107,114,128,.15);color:#6b7280;border:1px solid rgba(107,114,128,.2);}

/* ── Table ── */
.table-wrap{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);
            border-radius:14px;overflow:hidden;margin-bottom:24px;}
.table-header{padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.06);
              display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
.table-header h2{font-size:14px;font-weight:800;display:flex;align-items:center;gap:8px;}
table{width:100%;border-collapse:collapse;}
th{padding:11px 14px;text-align:left;font-size:10px;font-weight:700;letter-spacing:.7px;
   text-transform:uppercase;color:rgba(255,255,255,.35);border-bottom:1px solid rgba(255,255,255,.06);}
td{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(255,255,255,.03);}

/* ── Days badge ── */
.days-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;
            border-radius:20px;font-size:12px;font-weight:800;}
.db-expired {background:rgba(107,114,128,.2);color:#9ca3af;}
.db-critical{background:rgba(220,38,38,.2); color:#f87171;}
.db-high    {background:rgba(249,115,22,.2); color:#fb923c;}
.db-medium  {background:rgba(234,179,8,.18); color:#fbbf24;}
.db-low     {background:rgba(59,130,246,.2); color:#93c5fd;}
.db-ok      {background:rgba(16,185,129,.15);color:#6ee7b7;}

/* ── Discount pill ── */
.disc-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;
           border-radius:20px;font-size:12px;font-weight:800;border:1px solid;}
.disc-expired {background:rgba(107,114,128,.15);color:#9ca3af;border-color:rgba(107,114,128,.2);}
.disc-critical{background:rgba(220,38,38,.15); color:#f87171;border-color:rgba(220,38,38,.2);}
.disc-high    {background:rgba(249,115,22,.15); color:#fb923c;border-color:rgba(249,115,22,.2);}
.disc-medium  {background:rgba(234,179,8,.15);  color:#fbbf24;border-color:rgba(234,179,8,.2);}
.disc-low     {background:rgba(59,130,246,.15);  color:#93c5fd;border-color:rgba(59,130,246,.2);}
.disc-ok      {background:rgba(16,185,129,.1);   color:#6ee7b7;border-color:rgba(16,185,129,.15);}

/* ── Progress bar ── */
.freshness-bar{height:4px;background:rgba(255,255,255,.08);border-radius:2px;overflow:hidden;min-width:80px;}
.freshness-fill{height:100%;border-radius:2px;}

/* ── Panel (settings / log) ── */
.panel{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);
       border-radius:14px;overflow:hidden;margin-bottom:24px;display:none;}
.panel.open{display:block;}
.panel-header{padding:16px 22px;border-bottom:1px solid rgba(255,255,255,.06);
              display:flex;align-items:center;justify-content:space-between;}
.panel-header h2{font-size:14px;font-weight:800;display:flex;align-items:center;gap:8px;}
.panel-body{padding:24px;}

/* ── Settings form ── */
.settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
@media(max-width:700px){.settings-grid{grid-template-columns:1fr;}}
.settings-section{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);
                  border-radius:10px;padding:20px;}
.settings-section h3{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;
                     color:rgba(255,255,255,.4);margin-bottom:16px;display:flex;align-items:center;gap:7px;}
.form-row{margin-bottom:14px;}
.form-row label{display:block;font-size:11px;font-weight:700;color:rgba(255,255,255,.4);
                text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;}
.form-row input[type=text],.form-row input[type=number],.form-row input[type=email],
.form-row input[type=password],.form-row select,.form-row textarea{
    width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);
    color:#e2e8f0;padding:8px 12px;border-radius:8px;font-size:13px;outline:none;
    transition:border-color .2s;}
.form-row input:focus,.form-row select:focus,.form-row textarea:focus{border-color:#f59e0b;}
.form-row select option{background:#1e293b;}
.form-row textarea{resize:vertical;min-height:60px;}
.toggle-row{display:flex;align-items:center;justify-content:space-between;
            background:rgba(255,255,255,.04);border-radius:8px;padding:12px 14px;margin-bottom:14px;}
.toggle-row span{font-size:13px;font-weight:600;color:#e2e8f0;}
.toggle{position:relative;width:44px;height:24px;display:inline-block;flex-shrink:0;}
.toggle input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;inset:0;background:rgba(255,255,255,.12);border-radius:12px;cursor:pointer;transition:.3s;}
.toggle-slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;
    background:white;border-radius:50%;transition:.3s;}
.toggle input:checked + .toggle-slider{background:linear-gradient(135deg,#16a34a,#15803d);}
.toggle input:checked + .toggle-slider::before{transform:translateX(20px);}
.disc-config{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
.disc-box{background:rgba(255,255,255,.04);border-radius:8px;padding:12px;text-align:center;}
.disc-box label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:8px;}
.disc-box.d-30 label{color:#60a5fa;} .disc-box.d-15 label{color:#fbbf24;}
.disc-box.d-7  label{color:#fb923c;} .disc-box.d-3  label{color:#f87171;}
.disc-box input{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);
                color:#e2e8f0;padding:8px;border-radius:6px;font-size:16px;font-weight:800;
                text-align:center;outline:none;}
.disc-box input:focus{border-color:#f59e0b;}
.disc-box .pct{font-size:11px;color:rgba(255,255,255,.35);margin-top:4px;}
.btn-save-settings{background:linear-gradient(135deg,#f59e0b,#d97706);color:white;width:100%;
                   padding:12px;border-radius:10px;font-size:14px;font-weight:800;
                   border:none;cursor:pointer;margin-top:20px;transition:all .2s;}
.btn-save-settings:hover{background:linear-gradient(135deg,#d97706,#b45309);}
.btn-test-email{background:rgba(37,99,235,.2);color:#93c5fd;border:1px solid rgba(37,99,235,.3);
                padding:7px 14px;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;transition:all .2s;}
.btn-test-email:hover{background:rgba(37,99,235,.35);}

/* ── Toast ── */
.toast-wrap{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
.toast{padding:14px 20px;border-radius:10px;font-size:13px;font-weight:600;
       display:flex;align-items:center;gap:10px;min-width:280px;max-width:380px;
       animation:slideIn .3s ease;box-shadow:0 8px 32px rgba(0,0,0,.4);}
.toast-success{background:linear-gradient(135deg,#166534,#15803d);color:#bbf7d0;border:1px solid rgba(16,185,129,.3);}
.toast-error  {background:linear-gradient(135deg,#7f1d1d,#991b1b);color:#fecaca;border:1px solid rgba(220,38,38,.3);}
.toast-info   {background:linear-gradient(135deg,#1e3a5f,#1e40af);color:#bfdbfe;border:1px solid rgba(59,130,246,.3);}
@keyframes slideIn{from{transform:translateX(100px);opacity:0;}to{transform:translateX(0);opacity:1;}}

/* ── Log table ── */
.log-status-sent  {color:#6ee7b7;font-weight:700;}
.log-status-failed{color:#f87171;font-weight:700;}
.log-ch-email{color:#93c5fd;} .log-ch-sms{color:#6ee7b7;}

/* ── Empty state ── */
.empty{text-align:center;padding:60px 20px;color:rgba(255,255,255,.25);}
.empty i{font-size:48px;display:block;margin-bottom:16px;}
.empty p{font-size:15px;font-weight:600;}

/* ── Loading ── */
.loading-row td{text-align:center;padding:40px;color:rgba(255,255,255,.3);}

/* ── Threshold legend ── */
.legend{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:20px;}
.legend-item{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:rgba(255,255,255,.5);}
.legend-dot{width:10px;height:10px;border-radius:50%;}

@media print{.topbar,.action-bar,.panel,.toast-wrap,.btn{display:none!important;}
body{background:white;color:black;}.table-wrap{border:1px solid #ccc;}.stat{background:#f5f5f5;}}
</style>
</head>
<body>

<div class="topbar">
    <h1>
        <span class="icon"><i class="fas fa-bell"></i></span>
        Expiry Alert Center
    </h1>
    <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="pos_expiry.php"><i class="fas fa-calendar-times"></i> Expiry</a>
    <a href="pos_receiving.php"><i class="fas fa-boxes"></i> Receiving</a>
    <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
    <a href="pos_reorder.php"><i class="fas fa-truck-loading"></i> Reorder</a>
    <span class="ml"><i class="fas fa-user"></i> <?= htmlspecialchars($agent_name) ?></span>
</div>

<div id="toastWrap" class="toast-wrap"></div>

<div class="wrap">

    <!-- Stats -->
    <div class="stats" id="statsRow">
        <div class="stat s-expired"><div class="sv" id="st-expired">—</div><div class="sl">Expired</div></div>
        <div class="stat s-critical"><div class="sv" id="st-critical">—</div><div class="sl">1–3 Days</div></div>
        <div class="stat s-soon"><div class="sv" id="st-soon">—</div><div class="sl">4–15 Days</div></div>
        <div class="stat s-watch"><div class="sv" id="st-watch">—</div><div class="sl">16–30 Days</div></div>
    </div>

    <!-- Action bar -->
    <div class="action-bar">
        <select class="within-sel" id="withinDays" onchange="loadBatches()">
            <option value="7">Expiring in 7 days</option>
            <option value="14">Expiring in 14 days</option>
            <option value="30" selected>Expiring in 30 days</option>
            <option value="60">Expiring in 60 days</option>
            <option value="90">Expiring in 90 days</option>
            <option value="0">Expired only</option>
        </select>

        <!-- Channel status badges -->
        <span class="ch-badge <?= $cfg['email_enabled'] ? 'ch-on' : 'ch-off' ?>" id="emailBadge">
            <i class="fas fa-envelope"></i> Email <?= $cfg['email_enabled'] ? 'ON' : 'OFF' ?>
        </span>
        <span class="ch-badge <?= $cfg['sms_enabled'] ? 'ch-on' : 'ch-off' ?>" id="smsBadge">
            <i class="fas fa-mobile-alt"></i> SMS <?= $cfg['sms_enabled'] ? 'ON' : 'OFF' ?>
        </span>

        <button class="btn btn-email" onclick="sendAlert('email')" id="btnEmail">
            <i class="fas fa-envelope"></i> Send Email Alert
        </button>
        <button class="btn btn-sms" onclick="sendAlert('sms')" id="btnSms">
            <i class="fas fa-mobile-alt"></i> Send SMS Alert
        </button>

        <div style="margin-left:auto;display:flex;gap:8px;">
            <?php if ($is_super): ?>
            <button class="btn btn-settings" onclick="togglePanel('settings')">
                <i class="fas fa-cog"></i> Alert Settings
            </button>
            <?php endif; ?>
            <button class="btn btn-log" onclick="togglePanel('log');loadLog()">
                <i class="fas fa-history"></i> Alert Log
            </button>
            <button class="btn btn-log" onclick="window.print()">
                <i class="fas fa-print"></i>
            </button>
        </div>
    </div>

    <!-- Discount legend -->
    <div class="legend">
        <div class="legend-item"><div class="legend-dot" style="background:#9ca3af;"></div> Expired — Pull</div>
        <div class="legend-item"><div class="legend-dot" style="background:#dc2626;"></div> 1–3 days — <?= $cfg['disc_3d'] ?>% off</div>
        <div class="legend-item"><div class="legend-dot" style="background:#f97316;"></div> 4–7 days — <?= $cfg['disc_7d'] ?>% off</div>
        <div class="legend-item"><div class="legend-dot" style="background:#eab308;"></div> 8–15 days — <?= $cfg['disc_15d'] ?>% off</div>
        <div class="legend-item"><div class="legend-dot" style="background:#3b82f6;"></div> 16–30 days — <?= $cfg['disc_30d'] ?>% off</div>
        <div class="legend-item"><div class="legend-dot" style="background:#10b981;"></div> OK — No discount</div>
    </div>

    <!-- Main table -->
    <div class="table-wrap">
        <div class="table-header">
            <h2><i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i> Expiring Batches</h2>
            <span style="font-size:12px;color:rgba(255,255,255,.3);" id="tableCount">Loading...</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Expiry Date</th>
                    <th>Days Left</th>
                    <th>Qty Remaining</th>
                    <th>Current Price (LL)</th>
                    <th>Suggested Discount</th>
                    <th>Discounted Price (LL)</th>
                    <th>Supplier</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="batchTable">
                <tr class="loading-row"><td colspan="10"><i class="fas fa-spinner fa-spin"></i> Loading batches…</td></tr>
            </tbody>
        </table>
    </div>

    <?php if ($is_super): ?>
    <!-- Settings panel -->
    <div class="panel" id="panel-settings">
        <div class="panel-header">
            <h2><i class="fas fa-cog" style="color:#f59e0b;"></i> Alert Settings</h2>
            <button class="btn btn-log btn-sm" onclick="togglePanel('settings')"><i class="fas fa-times"></i></button>
        </div>
        <div class="panel-body">
            <!-- Discount thresholds -->
            <div style="margin-bottom:24px;">
                <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:rgba(255,255,255,.4);margin-bottom:14px;">
                    <i class="fas fa-percentage"></i> Auto-Discount Thresholds
                </h3>
                <div class="disc-config">
                    <div class="disc-box d-30">
                        <label>≤ 30 Days</label>
                        <input type="number" id="disc_30d" value="<?= $cfg['disc_30d'] ?>" min="0" max="100" step="1">
                        <div class="pct">% discount</div>
                    </div>
                    <div class="disc-box d-15">
                        <label>≤ 15 Days</label>
                        <input type="number" id="disc_15d" value="<?= $cfg['disc_15d'] ?>" min="0" max="100" step="1">
                        <div class="pct">% discount</div>
                    </div>
                    <div class="disc-box d-7">
                        <label>≤ 7 Days</label>
                        <input type="number" id="disc_7d" value="<?= $cfg['disc_7d'] ?>" min="0" max="100" step="1">
                        <div class="pct">% discount</div>
                    </div>
                    <div class="disc-box d-3">
                        <label>≤ 3 Days</label>
                        <input type="number" id="disc_3d" value="<?= $cfg['disc_3d'] ?>" min="0" max="100" step="1">
                        <div class="pct">% discount</div>
                    </div>
                </div>
            </div>

            <div class="settings-grid">
                <!-- Email -->
                <div class="settings-section">
                    <h3><i class="fas fa-envelope"></i> Email Alerts (SMTP)</h3>
                    <div class="toggle-row">
                        <span>Enable Email Alerts</span>
                        <label class="toggle">
                            <input type="checkbox" id="email_enabled" <?= $cfg['email_enabled']?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="form-row">
                        <label>Alert Threshold Days</label>
                        <input type="text" id="alert_days_email" value="<?= $cfg['alert_days_1'] ?>, <?= $cfg['alert_days_2'] ?>, <?= $cfg['alert_days_3'] ?><?= !empty($cfg['alert_days_4']) ? ', '.$cfg['alert_days_4'] : '' ?>" placeholder="30, 18, 15, 5">
                        <small style="color:rgba(255,255,255,.3);font-size:11px;margin-top:4px;display:block;">Comma-separated — alert sent when days-left crosses these values</small>
                    </div>
                    <div class="form-row">
                        <label>SMTP Host</label>
                        <input type="text" id="smtp_host" value="<?= htmlspecialchars($cfg['smtp_host']) ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-row">
                        <label>SMTP Port</label>
                        <select id="smtp_port">
                            <option value="587" <?= $cfg['smtp_port']==587?'selected':'' ?>>587 (TLS — recommended)</option>
                            <option value="465" <?= $cfg['smtp_port']==465?'selected':'' ?>>465 (SSL)</option>
                            <option value="25"  <?= $cfg['smtp_port']==25 ?'selected':'' ?>>25 (Plain)</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label>SMTP Username / Email</label>
                        <input type="text" id="smtp_user" value="<?= htmlspecialchars($cfg['smtp_user']) ?>" placeholder="yourname@gmail.com">
                    </div>
                    <div class="form-row">
                        <label>SMTP Password / App Password</label>
                        <input type="password" id="smtp_pass" value="<?= htmlspecialchars($cfg['smtp_pass']) ?>" placeholder="Gmail: use App Password">
                    </div>
                    <div class="form-row">
                        <label>From Name</label>
                        <input type="text" id="smtp_from_name" value="<?= htmlspecialchars($cfg['smtp_from_name']) ?>" placeholder="NCC POS">
                    </div>
                    <div class="form-row">
                        <label>Recipient Emails (comma-separated)</label>
                        <textarea id="alert_email_to" placeholder="manager@ncc.lb, owner@ncc.lb"><?= htmlspecialchars($cfg['alert_email_to']) ?></textarea>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="text" id="test_email_to" placeholder="test@email.com" style="flex:1;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#e2e8f0;padding:7px 12px;border-radius:7px;font-size:13px;outline:none;">
                        <button class="btn-test-email" onclick="testEmail()"><i class="fas fa-paper-plane"></i> Test</button>
                    </div>
                    <small style="color:rgba(255,255,255,.25);font-size:11px;margin-top:6px;display:block;">
                        For Gmail: enable 2FA → generate App Password at myaccount.google.com/apppasswords
                    </small>
                </div>

                <!-- SMS -->
                <div class="settings-section">
                    <h3><i class="fas fa-mobile-alt"></i> SMS Alerts</h3>
                    <div class="toggle-row">
                        <span>Enable SMS Alerts</span>
                        <label class="toggle">
                            <input type="checkbox" id="sms_enabled" <?= $cfg['sms_enabled']?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="form-row">
                        <label>SMS Provider</label>
                        <select id="sms_provider" onchange="toggleSmsProvider()">
                            <option value="twilio"   <?= $cfg['sms_provider']==='twilio'  ?'selected':'' ?>>Twilio</option>
                            <option value="http_api" <?= $cfg['sms_provider']==='http_api'?'selected':'' ?>>Generic HTTP API</option>
                        </select>
                    </div>
                    <div id="sms-http-fields">
                    <div class="form-row">
                        <label>API URL</label>
                        <input type="text" id="sms_api_url" value="<?= htmlspecialchars($cfg['sms_api_url']) ?>" placeholder="https://sms-provider.com/send">
                        <small style="color:rgba(255,255,255,.25);font-size:11px;margin-top:4px;display:block;">POST receives: to, message, key, from</small>
                    </div>
                    </div>
                    <div class="form-row">
                        <label id="sms_key_label">API Key / Account SID</label>
                        <input type="text" id="sms_api_key" value="<?= htmlspecialchars($cfg['sms_api_key']) ?>" placeholder="API key or Twilio Account SID">
                    </div>
                    <div class="form-row" id="sms-secret-row">
                        <label>Auth Token (Twilio only)</label>
                        <input type="password" id="sms_api_secret" value="<?= htmlspecialchars($cfg['sms_api_secret']) ?>" placeholder="Twilio Auth Token">
                    </div>
                    <div class="form-row">
                        <label>Sender ID / From Number</label>
                        <input type="text" id="sms_from" value="<?= htmlspecialchars($cfg['sms_from']) ?>" placeholder="NCCPOS or +1234567890">
                    </div>
                    <div class="form-row">
                        <label>Recipient Phones (comma-separated, intl format)</label>
                        <textarea id="alert_sms_to" placeholder="+9613000000, +9611234567"><?= htmlspecialchars($cfg['alert_sms_to']) ?></textarea>
                    </div>
                    <div style="background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2);border-radius:8px;padding:12px;font-size:12px;color:#93c5fd;line-height:1.6;">
                        <strong>Lebanese SMS providers:</strong> Infobip, MessageBird, or local gateway.<br>
                        Use international format: +961 + number without leading 0.
                    </div>
                </div>
            </div>

            <button class="btn-save-settings" onclick="saveSettings()">
                <i class="fas fa-save"></i> Save Alert Settings
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alert log panel -->
    <div class="panel" id="panel-log">
        <div class="panel-header">
            <h2><i class="fas fa-history" style="color:#60a5fa;"></i> Alert Log</h2>
            <button class="btn btn-log btn-sm" onclick="togglePanel('log')"><i class="fas fa-times"></i></button>
        </div>
        <div class="panel-body" style="padding:0;">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Channel</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Sent By</th>
                    </tr>
                </thead>
                <tbody id="logTable">
                    <tr class="loading-row"><td colspan="6">Click "Alert Log" to load</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /wrap -->

<script>
let allBatches = [];

// ── Load batches ───────────────────────────────────────────────────────────
async function loadBatches() {
    const within = document.getElementById('withinDays').value;
    document.getElementById('batchTable').innerHTML =
        '<tr class="loading-row"><td colspan="10"><i class="fas fa-spinner fa-spin"></i> Loading…</td></tr>';

    const res  = await fetch(`ajax/pos_expiry_alert_ajax.php?action=get_data&within=${within}`);
    const data = await res.json();
    if (!data.success) { toast('Error loading batches', 'error'); return; }

    allBatches = data.batches || [];
    renderBatches(allBatches);
    updateStats(allBatches);
}

function updateStats(batches) {
    const counts = {expired:0, critical:0, soon:0, watch:0};
    batches.forEach(b => {
        if      (b.urgent === 'expired')  counts.expired++;
        else if (b.urgent === 'critical') counts.critical++;
        else if (b.urgent === 'high' || b.urgent === 'medium') counts.soon++;
        else if (b.urgent === 'low')      counts.watch++;
    });
    document.getElementById('st-expired').textContent  = counts.expired;
    document.getElementById('st-critical').textContent = counts.critical;
    document.getElementById('st-soon').textContent     = counts.soon;
    document.getElementById('st-watch').textContent    = counts.watch;
}

function renderBatches(batches) {
    const tbody = document.getElementById('batchTable');
    document.getElementById('tableCount').textContent =
        batches.length + ' batch' + (batches.length !== 1 ? 'es' : '');

    if (!batches.length) {
        tbody.innerHTML = '<tr><td colspan="10"><div class="empty"><i class="fas fa-check-circle" style="color:#10b981;opacity:1;font-size:40px;"></i><p style="color:#10b981;">No batches expiring in this window.</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = batches.map(b => {
        const daysCls  = 'db-' + b.urgent;
        const discCls  = 'disc-' + b.urgent;
        const daysText = b.days_left <= 0 ? 'EXPIRED' : b.days_left + 'd';
        const barPct   = b.days_left <= 0 ? 0 : Math.min(100, Math.round(b.days_left / 30 * 100));
        const barColor = {expired:'#6b7280', critical:'#dc2626', high:'#f97316', medium:'#eab308', low:'#3b82f6', ok:'#10b981'}[b.urgent] || '#10b981';
        const actionBtn = b.urgent === 'expired'
            ? `<a href="pos_expiry.php" class="btn btn-pull btn-sm"><i class="fas fa-times-circle"></i> Pull Batch</a>`
            : `<a href="pos_expiry.php" class="btn btn-apply btn-sm"><i class="fas fa-tag"></i> Apply</a>`;

        return `<tr>
            <td style="font-weight:700;color:#e2e8f0;">${esc(b.product_name)}</td>
            <td><span style="font-size:11px;background:rgba(255,255,255,.07);color:rgba(255,255,255,.5);padding:3px 9px;border-radius:20px;font-weight:600;">${esc(b.category||'—')}</span></td>
            <td style="font-size:12px;color:rgba(255,255,255,.7);">${b.expiry_date}</td>
            <td>
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <span class="days-badge ${daysCls}">${daysText}</span>
                    <div class="freshness-bar"><div class="freshness-fill" style="width:${barPct}%;background:${barColor};"></div></div>
                </div>
            </td>
            <td style="font-weight:700;">${b.qty_remaining}</td>
            <td>LL ${fmtNum(b.price)}</td>
            <td><span class="disc-pill ${discCls}">${b.disc_pct > 0 ? b.disc_pct + '%' : '—'} &nbsp;${b.disc_label}</span></td>
            <td style="font-weight:700;color:${b.disc_pct > 0 ? '#fbbf24' : 'rgba(255,255,255,.5)'};">
                ${b.disc_pct > 0 ? 'LL ' + fmtNum(b.suggested_price) : '—'}
            </td>
            <td style="font-size:12px;color:rgba(255,255,255,.4);">${esc(b.supplier)}</td>
            <td>${actionBtn}</td>
        </tr>`;
    }).join('');
}

// ── Send alert ─────────────────────────────────────────────────────────────
async function sendAlert(channel) {
    const within = document.getElementById('withinDays').value;
    const btnId  = channel === 'email' ? 'btnEmail' : 'btnSms';
    const btn    = document.getElementById(btnId);
    const orig   = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
    btn.disabled  = true;

    const body = new FormData();
    body.append('action', 'send_' + channel);
    body.append('within', within);

    const res  = await fetch('ajax/pos_expiry_alert_ajax.php', { method:'POST', body });
    const data = await res.json();
    btn.innerHTML = orig; btn.disabled = false;

    if (data.success) {
        toast(`✅ ${channel.toUpperCase()} alert sent to ${data.sent} of ${data.total} recipient(s)`, 'success');
        if (data.errors?.length) {
            data.errors.forEach(e => toast('⚠️ ' + e, 'error'));
        }
    } else {
        toast('❌ ' + (data.error || 'Send failed'), 'error');
    }
}

// ── Settings ───────────────────────────────────────────────────────────────
function togglePanel(name) {
    const p = document.getElementById('panel-' + name);
    if (p) p.classList.toggle('open');
}

function toggleSmsProvider() {
    const p = document.getElementById('sms_provider').value;
    document.getElementById('sms-http-fields').style.display = p === 'http_api' ? '' : 'none';
    document.getElementById('sms-secret-row').style.display  = p === 'twilio'   ? '' : 'none';
    document.getElementById('sms_key_label').textContent = p === 'twilio' ? 'Account SID' : 'API Key';
}

async function saveSettings() {
    const body = new FormData();
    body.append('action', 'save_settings');
    const fields = ['email_enabled','smtp_host','smtp_port','smtp_user','smtp_pass',
        'smtp_from_name','alert_email_to','sms_enabled','sms_provider',
        'sms_api_url','sms_api_key','sms_api_secret','sms_from','alert_sms_to',
        'disc_30d','disc_15d','disc_7d','disc_3d'];

    fields.forEach(f => {
        const el = document.getElementById(f);
        if (!el) return;
        if (el.type === 'checkbox') body.append(f, el.checked ? '1' : '0');
        else body.append(f, el.value);
    });

    // Parse smtp_from from user field
    body.append('smtp_from', document.getElementById('smtp_user')?.value || '');

    // Parse alert days
    const days = (document.getElementById('alert_days_email')?.value || '30,18,15,5').split(',').map(d => parseInt(d.trim())||0);
    body.append('alert_days_1', days[0]||30);
    body.append('alert_days_2', days[1]||18);
    body.append('alert_days_3', days[2]||15);
    body.append('alert_days_4', days[3]||0);

    const res  = await fetch('ajax/pos_expiry_alert_ajax.php', { method:'POST', body });
    const data = await res.json();
    if (data.success) {
        toast('✅ Settings saved', 'success');
        // Update badge labels
        document.getElementById('emailBadge').className = 'ch-badge ' + (document.getElementById('email_enabled').checked ? 'ch-on' : 'ch-off');
        document.getElementById('emailBadge').innerHTML  = '<i class="fas fa-envelope"></i> Email ' + (document.getElementById('email_enabled').checked ? 'ON' : 'OFF');
        document.getElementById('smsBadge').className   = 'ch-badge ' + (document.getElementById('sms_enabled').checked ? 'ch-on' : 'ch-off');
        document.getElementById('smsBadge').innerHTML   = '<i class="fas fa-mobile-alt"></i> SMS ' + (document.getElementById('sms_enabled').checked ? 'ON' : 'OFF');
    } else {
        toast('❌ ' + (data.error || 'Failed'), 'error');
    }
}

async function testEmail() {
    const to = document.getElementById('test_email_to')?.value.trim();
    if (!to) { toast('Enter a test email address', 'error'); return; }
    toast('Sending test email…', 'info');
    const body = new FormData();
    body.append('action', 'test_email');
    body.append('test_to', to);
    const res  = await fetch('ajax/pos_expiry_alert_ajax.php', { method:'POST', body });
    const data = await res.json();
    toast(data.success ? '✅ ' + data.message : '❌ ' + data.error, data.success ? 'success' : 'error');
}

// ── Alert log ──────────────────────────────────────────────────────────────
async function loadLog() {
    const res  = await fetch('ajax/pos_expiry_alert_ajax.php?action=get_log&limit=30');
    const data = await res.json();
    const tbody = document.getElementById('logTable');
    if (!data.log?.length) {
        tbody.innerHTML = '<tr class="loading-row"><td colspan="6">No alert history yet</td></tr>'; return;
    }
    tbody.innerHTML = data.log.map(r => `
        <tr>
            <td style="font-size:12px;color:rgba(255,255,255,.4);white-space:nowrap;">${r.created_at}</td>
            <td><span class="log-ch-${r.channel}"><i class="fas fa-${r.channel==='email'?'envelope':'mobile-alt'}"></i> ${r.channel}</span></td>
            <td style="font-size:12px;">${esc(r.recipient)}</td>
            <td style="font-size:12px;color:rgba(255,255,255,.5);">${esc(r.subject)}</td>
            <td class="log-status-${r.status}">${r.status}</td>
            <td style="font-size:12px;color:rgba(255,255,255,.4);">${esc(r.sent_by)}</td>
        </tr>
    `).join('');
}

// ── Helpers ────────────────────────────────────────────────────────────────
function fmtNum(n) { return Math.round(n).toLocaleString('en'); }
function esc(s)    { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function toast(msg, type = 'info') {
    const w = document.getElementById('toastWrap');
    const t = document.createElement('div');
    t.className = 'toast toast-' + type;
    t.innerHTML = msg;
    w.appendChild(t);
    setTimeout(() => t.remove(), 5000);
}

// ── Init ───────────────────────────────────────────────────────────────────
toggleSmsProvider();
loadBatches();
</script>
</body>
</html>
