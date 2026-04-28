<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }

// Super admin only
$is_super = ($_SESSION['oop'] === 'super');
if (!$is_super) { header("Location: pos.php"); exit(); }

$conn = mysqli_connect("192.168.1.101","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

$msg = '';
$msg_type = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name    = mysqli_real_escape_string($conn, trim($_POST['company_name'] ?? ''));
    $company_phone   = mysqli_real_escape_string($conn, trim($_POST['company_phone'] ?? ''));
    $company_address = mysqli_real_escape_string($conn, trim($_POST['company_address'] ?? ''));
    $receipt_footer  = mysqli_real_escape_string($conn, trim($_POST['receipt_footer'] ?? ''));
    $vat_rate        = (float)($_POST['vat_rate'] ?? 0);
    $usd_to_lbp      = (float)($_POST['usd_to_lbp'] ?? 89500);
    $printer_name    = mysqli_real_escape_string($conn, trim($_POST['printer_name'] ?? ''));
    $print_mode      = in_array($_POST['print_mode'] ?? '', ['manual','automatic']) ? $_POST['print_mode'] : 'manual';
    $paper_width     = in_array($_POST['paper_width'] ?? '', ['58mm','80mm']) ? $_POST['paper_width'] : '80mm';
    $cash_drawer     = in_array($_POST['cash_drawer'] ?? '', ['disabled','manual','automatic']) ? $_POST['cash_drawer'] : 'disabled';
    $drawer_type     = in_array($_POST['drawer_type'] ?? '', ['dk','usb']) ? $_POST['drawer_type'] : 'dk';
    $drawer_usb_name = mysqli_real_escape_string($conn, trim($_POST['drawer_usb_name'] ?? ''));
    $usd_denoms      = mysqli_real_escape_string($conn, trim($_POST['usd_denominations'] ?? '1,5,10,20,50,100'));
    $lbp_denoms      = mysqli_real_escape_string($conn, trim($_POST['lbp_denominations'] ?? '5000,10000,20000,50000,100000'));

    if (empty($company_name)) {
        $msg = 'Company name cannot be empty.';
        $msg_type = 'error';
    } else {
        // Check if row exists
        $exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM company_settings LIMIT 1"));
        if ($exists) {
            mysqli_query($conn, "UPDATE company_settings SET
                company_name    = '$company_name',
                company_phone   = '$company_phone',
                company_address = '$company_address',
                receipt_footer  = '$receipt_footer',
                vat_rate        = $vat_rate,
                usd_to_lbp      = $usd_to_lbp,
                printer_name    = '$printer_name',
                print_mode      = '$print_mode',
                paper_width     = '$paper_width',
                cash_drawer     = '$cash_drawer',
                drawer_type     = '$drawer_type',
                drawer_usb_name = '$drawer_usb_name',
                usd_denominations = '$usd_denoms',
                lbp_denominations = '$lbp_denoms'
                WHERE id = {$exists['id']}");
        } else {
            mysqli_query($conn, "INSERT INTO company_settings
                (company_name, company_phone, company_address, receipt_footer, vat_rate, usd_to_lbp, printer_name, print_mode, paper_width, cash_drawer, drawer_type, drawer_usb_name)
                VALUES ('$company_name','$company_phone','$company_address','$receipt_footer',$vat_rate,$usd_to_lbp,'$printer_name','$print_mode','$paper_width','$cash_drawer','$drawer_type','$drawer_usb_name')");
        }
        $msg = 'Settings saved successfully.';
        $msg_type = 'success';
    }
}

// Load current settings
$settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM company_settings LIMIT 1"));
if (!$settings) {
    $settings = ['company_name'=>'','company_phone'=>'','company_address'=>'','receipt_footer'=>'Thank you for your business!',
                 'vat_rate'=>0,'usd_to_lbp'=>89500,'printer_name'=>'','print_mode'=>'manual','paper_width'=>'80mm',
                 'cash_drawer'=>'disabled','drawer_type'=>'dk','drawer_usb_name'=>'',
                 'usd_denominations'=>'1,5,10,20,50,100','lbp_denominations'=>'5000,10000,20000,50000,100000'];
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>POS Settings</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.topbar {
    background:linear-gradient(135deg,#1976D2,#0D47A1);
    color:white; padding:14px 24px;
    display:flex; align-items:center; gap:15px;
}
.topbar h1 { font-size:18px; font-weight:700; }
.topbar a {
    color:white; text-decoration:none;
    background:rgba(255,255,255,.15);
    padding:8px 16px; border-radius:6px;
    font-size:13px; font-weight:600;
    display:flex; align-items:center; gap:6px;
    margin-left:auto;
}
.topbar a:hover { background:rgba(255,255,255,.25); }

.container { max-width:640px; margin:40px auto; padding:0 20px; }

.card { background:white; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.08); overflow:hidden; }
.card-header {
    padding:20px 28px; border-bottom:1px solid #e5e7eb;
    display:flex; align-items:center; gap:12px;
}
.card-header i { color:#1976D2; font-size:20px; }
.card-header h2 { font-size:17px; font-weight:800; color:#1a1a2e; }
.card-header p  { font-size:13px; color:#9ca3af; margin-top:2px; }

.card-body { padding:28px; }

.alert { padding:13px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:10px; }
.alert-success { background:#d1fae5; color:#065f46; border-left:4px solid #10b981; }
.alert-error   { background:#fee2e2; color:#991b1b; border-left:4px solid #ef4444; }

.form-group { margin-bottom:20px; }
.form-group label {
    display:block; font-size:12px; font-weight:700;
    color:#374151; text-transform:uppercase;
    letter-spacing:.5px; margin-bottom:8px;
}
.form-group input,
.form-group textarea {
    width:100%; padding:12px 14px;
    border:2px solid #e5e7eb; border-radius:10px;
    font-size:14px; font-family:inherit;
    transition:border-color .2s; color:#1a1a2e;
}
.form-group input:focus,
.form-group textarea:focus { outline:none; border-color:#1976D2; }
.form-group textarea { resize:vertical; min-height:80px; }
.form-group .hint { font-size:12px; color:#9ca3af; margin-top:6px; }

.divider { border:none; border-top:1px solid #f3f4f6; margin:24px 0; }

.btn-save {
    width:100%; padding:14px; background:linear-gradient(135deg,#1976D2,#0D47A1);
    color:white; border:none; border-radius:10px;
    font-size:15px; font-weight:800; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:10px;
    transition:all .2s;
}
.btn-save:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(25,118,210,.3); }

/* Live preview */
.preview-box {
    background:#f8fafc; border:2px dashed #e5e7eb;
    border-radius:10px; padding:20px;
    text-align:center; margin-top:24px;
}
.preview-box .preview-label { font-size:11px; font-weight:700; text-transform:uppercase; color:#9ca3af; letter-spacing:.5px; margin-bottom:14px; }
.preview-company { font-size:16px; font-weight:800; color:#1976D2; }
.preview-title   { font-size:18px; font-weight:800; margin:4px 0; }
.preview-phone   { font-size:12px; color:#6b7280; }
.preview-address { font-size:12px; color:#6b7280; }
.preview-footer  { font-size:11px; color:#aaa; margin-top:10px; border-top:1px dashed #e5e7eb; padding-top:10px; }
</style>
</head>
<body>

<div class="topbar">
    <i class="fas fa-cog fa-lg"></i>
    <h1>POS Settings</h1>
    <a href="pos.php"><i class="fas fa-arrow-left"></i> Back to POS</a>
</div>

<div class="container">

    <?php
ini_set("display_errors", 1);
error_reporting(E_ALL); if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>" style="margin-bottom:20px;">
        <i class="fas fa-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php
ini_set("display_errors", 1);
error_reporting(E_ALL); endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-receipt"></i>
            <div>
                <h2>Receipt & Company Settings</h2>
                <p>This information appears on every printed receipt</p>
            </div>
        </div>
        <div class="card-body">
            <form method="POST">

                <div class="form-group">
                    <label><i class="fas fa-building"></i> Company Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="company_name" id="prev_company"
                        value="<?= htmlspecialchars($settings['company_name']) ?>"
                        placeholder="e.g. NCC Trading" oninput="updatePreview()" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="text" name="company_phone" id="prev_phone"
                        value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>"
                        placeholder="e.g. +961 1 234 567" oninput="updatePreview()">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Address</label>
                    <input type="text" name="company_address" id="prev_address"
                        value="<?= htmlspecialchars($settings['company_address'] ?? '') ?>"
                        placeholder="e.g. Baabda, Lebanon" oninput="updatePreview()">
                </div>

                <hr class="divider">

                <div class="form-group">
                    <label><i class="fas fa-percent"></i> VAT Rate (%)</label>
                    <input type="number" name="vat_rate" id="prev_vat"
                        value="<?= htmlspecialchars($settings['vat_rate'] ?? 0) ?>"
                        placeholder="e.g. 11 for 11% — enter 0 to disable VAT"
                        min="0" max="100" step="0.01" oninput="updatePreview()">
                    <div class="hint">Set to 0 to hide VAT from receipts. VAT is calculated on top of the sale total.</div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-exchange-alt"></i> Exchange Rate (1 USD = ? LBP)</label>
                    <input type="number" name="usd_to_lbp" id="prev_rate"
                        value="<?= htmlspecialchars($settings['usd_to_lbp'] ?? 89500) ?>"
                        placeholder="e.g. 89500" min="1" step="1" oninput="updatePreview()">
                    <div class="hint">Used to show the LBP equivalent on every USD receipt. Update whenever the rate changes.</div>
                </div>

                <hr class="divider">
                    <textarea name="receipt_footer" id="prev_footer"
                        placeholder="e.g. Thank you for your business!" oninput="updatePreview()"><?= htmlspecialchars($settings['receipt_footer'] ?? '') ?></textarea>
                    <div class="hint">Shown at the bottom of every receipt</div>
                </div>

                <!-- Live preview -->
                <div class="preview-box">
                    <div class="preview-label"><i class="fas fa-eye"></i> Receipt Preview</div>
                    <div class="preview-company" id="disp_company"><?= htmlspecialchars($settings['company_name']) ?></div>
                    <div class="preview-title">🧾 Sale Receipt</div>
                    <div class="preview-phone"   id="disp_phone"><?= htmlspecialchars($settings['company_phone'] ?? '') ?></div>
                    <div class="preview-address" id="disp_address"><?= htmlspecialchars($settings['company_address'] ?? '') ?></div>
                    <div style="margin-top:8px;font-size:12px;color:#374151;border-top:1px dashed #ccc;padding-top:8px;">
                        <div id="disp_vat" style="color:#1976D2;font-weight:700;"><?= ($settings['vat_rate']??0) > 0 ? 'VAT '.htmlspecialchars($settings['vat_rate']).'% applied' : 'No VAT' ?></div>
                        <div id="disp_rate" style="color:#059669;font-weight:700;">1 USD = <?= number_format($settings['usd_to_lbp']??89500,0) ?> LBP</div>
                    </div>
                    <div class="preview-footer"  id="disp_footer"><?= htmlspecialchars($settings['receipt_footer'] ?? '') ?></div>
                </div>

                <hr class="divider">

                <!-- Thermal Printer -->
                <div style="grid-column:1/-1;">
                    <div style="background:#f8fafc;border:2px solid #e5e7eb;border-radius:10px;padding:18px 20px;margin-bottom:4px;">
                        <div style="font-size:14px;font-weight:800;color:#1a1a2e;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-print" style="color:#1976D2;"></i> Thermal Printer Settings
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                            <div class="form-group" style="margin:0;">
                                <label>Printer Name (Windows)</label>
                                <input type="text" name="printer_name"
                                    value="<?= htmlspecialchars($settings['printer_name'] ?? '') ?>"
                                    placeholder="e.g. BIXOLON SRP-350">
                                <div class="hint">Exact name from Devices &amp; Printers</div>
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label>Print Mode</label>
                                <select name="print_mode">
                                    <option value="manual"    <?= ($settings['print_mode']??'manual')==='manual'    ?'selected':'' ?>>Manual — cashier clicks Print</option>
                                    <option value="automatic" <?= ($settings['print_mode']??'manual')==='automatic' ?'selected':'' ?>>Automatic — prints on sale complete</option>
                                </select>
                                <div class="hint">Automatic: prints immediately, no dialog</div>
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label>Paper Width</label>
                                <select name="paper_width">
                                    <option value="80mm" <?= ($settings['paper_width']??'80mm')==='80mm'?'selected':'' ?>>80mm (standard)</option>
                                    <option value="58mm" <?= ($settings['paper_width']??'80mm')==='58mm'?'selected':'' ?>>58mm (narrow)</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label>Cash Drawer Mode</label>
                                <select name="cash_drawer">
                                    <option value="disabled"  <?= ($settings['cash_drawer']??'disabled')==='disabled'  ?'selected':'' ?>>Disabled</option>
                                    <option value="manual"    <?= ($settings['cash_drawer']??'disabled')==='manual'    ?'selected':'' ?>>Manual — button in POS</option>
                                    <option value="automatic" <?= ($settings['cash_drawer']??'disabled')==='automatic' ?'selected':'' ?>>Automatic — opens on every sale</option>
                                </select>
                            </div>
                        </div>
                        <!-- Cash drawer connection type — shown when not disabled -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:12px;" id="drawerTypeSection"
                             <?= ($settings['cash_drawer']??'disabled')==='disabled' ? 'style="display:none!important;"' : '' ?>>
                            <div class="form-group" style="margin:0;">
                                <label>Drawer Connection Type</label>
                                <select name="drawer_type" id="drawer_type" onchange="toggleDrawerUsb()">
                                    <option value="dk"  <?= ($settings['drawer_type']??'dk')==='dk'  ?'selected':'' ?>>DK / RJ11 — connected to printer port</option>
                                    <option value="usb" <?= ($settings['drawer_type']??'dk')==='usb' ?'selected':'' ?>>USB — connected directly to PC</option>
                                </select>
                                <div class="hint">DK: cable from drawer to Bixolon DK port. USB: drawer has its own USB cable.</div>
                            </div>
                            <div class="form-group" style="margin:0;" id="drawerUsbNameField"
                                 <?= ($settings['drawer_type']??'dk')!=='usb' ? 'style="display:none;"' : '' ?>>
                                <label>USB Drawer Device Name (optional)</label>
                                <input type="text" name="drawer_usb_name"
                                    value="<?= htmlspecialchars($settings['drawer_usb_name'] ?? '') ?>"
                                    placeholder="e.g. APG Cash Drawer">
                                <div class="hint">Windows device name — leave blank to auto-detect</div>
                            </div>
                        </div>
                        <?php
ini_set("display_errors", 1);
error_reporting(E_ALL); if (!empty($settings['printer_name'])): ?>
                        <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="button" class="btn-save" style="background:#059669;padding:8px 18px;font-size:13px;" onclick="testPrint()">
                                <i class="fas fa-print"></i> Test Print
                            </button>
                            <?php
ini_set("display_errors", 1);
error_reporting(E_ALL); if (($settings['cash_drawer']??'disabled') !== 'disabled'): ?>
                            <button type="button" class="btn-save" style="background:#f59e0b;padding:8px 18px;font-size:13px;" onclick="testDrawer()">
                                <i class="fas fa-cash-register"></i> Test Drawer
                            </button>
                            <?php
ini_set("display_errors", 1);
error_reporting(E_ALL); endif; ?>
                            <span id="testPrintMsg" style="font-size:13px;line-height:36px;"></span>
                        </div>
                        <?php
ini_set("display_errors", 1);
error_reporting(E_ALL); endif; ?>
                    </div>
                </div>

                <hr class="divider">

                <!-- Cash Denomination Settings -->
                <div style="grid-column:1/-1;">
                    <div style="background:#f8fafc;border:2px solid #e5e7eb;border-radius:10px;padding:18px 20px;margin-bottom:4px;">
                        <div style="font-size:14px;font-weight:800;color:#1a1a2e;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-money-bill-wave" style="color:#10b981;"></i> Cash Denomination Buttons
                            <span style="font-size:11px;font-weight:400;color:#6b7280;margin-left:4px;">— shown as quick-pay buttons in POS checkout</span>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                            <div class="form-group" style="margin:0;">
                                <label>USD Denominations (comma separated)</label>
                                <input type="text" name="usd_denominations"
                                    value="<?= htmlspecialchars($settings['usd_denominations'] ?? '1,5,10,20,50,100') ?>"
                                    placeholder="1,5,10,20,50,100">
                                <div class="hint">Example: 1,5,10,20,50,100 — add new bills anytime</div>
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label>LBP Denominations (comma separated)</label>
                                <input type="text" name="lbp_denominations"
                                    value="<?= htmlspecialchars($settings['lbp_denominations'] ?? '5000,10000,20000,50000,100000') ?>"
                                    placeholder="5000,10000,20000,50000,100000">
                                <div class="hint">Example: 5000,10000,50000,100000,500000,1000000</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:24px;">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

<script>
function toggleDrawerUsb() {
    var type = document.getElementById('drawer_type').value;
    var usbField = document.getElementById('drawerUsbNameField');
    if (usbField) usbField.style.display = type === 'usb' ? '' : 'none';
}

function testDrawer() {
    var msg = document.getElementById('testPrintMsg');
    msg.textContent = '⏳ Opening drawer...';
    msg.style.color = '#6b7280';
    fetch('pos_escpos.php?action=open_drawer')
        .then(r => r.text())
        .then(text => {
            try {
                var data = JSON.parse(text);
                if (data.success) {
                    msg.textContent = '✅ Drawer opened! Method: ' + data.method;
                    msg.style.color = '#059669';
                } else {
                    msg.textContent = '❌ ' + data.error;
                    msg.style.color = '#ef4444';
                }
            } catch(e) {
                msg.textContent = '❌ Error: ' + text.substring(0,100);
                msg.style.color = '#ef4444';
            }
        });
}

// Show/hide drawer type section based on cash_drawer select
document.querySelector('select[name="cash_drawer"]').addEventListener('change', function() {
    var section = document.getElementById('drawerTypeSection');
    if (section) section.style.display = this.value === 'disabled' ? 'none' : 'grid';
});

function testPrint() {
    var msg = document.getElementById('testPrintMsg');
    msg.textContent = '⏳ Sending...';
    msg.style.color = '#6b7280';
    fetch('pos_escpos.php?sale_id=latest')
        .then(r => r.text())
        .then(text => {
            try {
                var data = JSON.parse(text);
                if (data.success) {
                    msg.textContent = '✅ Sent! Method: ' + data.method;
                    msg.style.color = '#059669';
                } else {
                    msg.textContent = '❌ ' + data.error;
                    msg.style.color = '#ef4444';
                }
            } catch(e) {
                msg.textContent = '❌ PHP error: ' + text.substring(0, 200);
                msg.style.color = '#ef4444';
            }
        })
        .catch(err => {
            msg.textContent = '❌ Request failed: ' + err;
            msg.style.color = '#ef4444';
        });
}

function updatePreview() {
    var company = document.getElementById('prev_company').value || '—';
    var phone   = document.getElementById('prev_phone').value;
    var address = document.getElementById('prev_address').value;
    var footer  = document.getElementById('prev_footer').value;
    var vat     = parseFloat(document.getElementById('prev_vat').value) || 0;
    var rate    = parseFloat(document.getElementById('prev_rate').value) || 89500;

    document.getElementById('disp_company').textContent = company;
    document.getElementById('disp_phone').textContent   = phone;
    document.getElementById('disp_address').textContent = address;
    document.getElementById('disp_footer').textContent  = footer;
    document.getElementById('disp_vat').textContent     = vat > 0 ? 'VAT ' + vat + '% applied' : 'No VAT';
    document.getElementById('disp_rate').textContent    = '1 USD = ' + rate.toLocaleString() + ' LBP';
}
</script>
</body>
</html>
