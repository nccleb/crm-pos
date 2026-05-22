<?php
/**
 * pos_loyalty.php
 * Loyalty Program Management — Points & Cashback Wallet
 * Super: full access — settings, card generation, adjustments, all clients
 * Cashier: view only — no settings, no adjustments
 */
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }

$agent_name = $_SESSION['oop'];
$agent_id   = (int)$_SESSION['ooq'];
$is_super   = ($agent_name === 'super');

$conn = mysqli_connect("192.168.1.19","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

$co = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT loyalty_mode, loyalty_rate, loyalty_point_value,
            loyalty_min_redeem, universal_key_card, usd_to_lbp
     FROM company_settings LIMIT 1"));
$loyalty_mode  = $co['loyalty_mode']        ?? 'disabled';
$loyalty_rate  = (float)($co['loyalty_rate'] ?? 2.00);
$point_value   = (int)($co['loyalty_point_value'] ?? 1000);
$min_redeem    = (int)($co['loyalty_min_redeem']  ?? 5000);
$ukey_card     = $co['universal_key_card']  ?? '';
$usd_to_lbp    = (float)($co['usd_to_lbp'] ?? 89500);

// Summary stats
$totals = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as enrolled,
            SUM(wallet_balance) as total_wallet,
            SUM(loyalty_points) as total_points,
            SUM(total_spent)    as total_spent
     FROM client WHERE loyalty_card IS NOT NULL"));

$today_txns = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt,
            SUM(CASE WHEN type='earned'   THEN amount ELSE 0 END) as earned,
            SUM(CASE WHEN type='redeemed' THEN amount ELSE 0 END) as redeemed
     FROM pos_loyalty_transactions WHERE DATE(created_at) = CURDATE()"));

mysqli_close($conn);

// ── Abbreviate large numbers for stat cards ───────────────────────────────────
function abbr($n, $prefix='') {
    $n = (float)$n;
    if ($n >= 1_000_000_000) return $prefix . number_format($n / 1_000_000_000, 1) . 'B';
    if ($n >= 1_000_000)     return $prefix . number_format($n / 1_000_000, 1)     . 'M';
    if ($n >= 10_000)        return $prefix . number_format($n / 1_000, 1)          . 'K';
    return $prefix . number_format((int)$n);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Loyalty Program — NCC POS</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;font-size:14px;background:#F0F2F5;color:#212121}

/* Topbar */
.topbar{background:#1565C0;color:#fff;padding:0 20px;display:flex;align-items:center;gap:12px;height:52px;flex-wrap:wrap}
.topbar .brand{font-weight:700;font-size:16px;margin-right:8px}
.topbar a{color:rgba(255,255,255,.85);text-decoration:none;padding:6px 12px;border-radius:6px;font-size:13px;white-space:nowrap}
.topbar a:hover{background:rgba(255,255,255,.15)}
.topbar a.active{background:rgba(255,255,255,.2);color:#fff;font-weight:600}
.topbar .spacer{flex:1}
.topbar .user{font-size:13px;opacity:.8}

.container{max-width:1300px;margin:24px auto;padding:0 20px}

/* Stats strip */
.stats{display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap}
.stat-card{background:#fff;border-radius:10px;padding:18px 22px;flex:1;min-width:160px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.stat-card .val{font-size:22px;font-weight:700;color:#1565C0}
.stat-card .lbl{font-size:12px;color:#757575;margin-top:2px;text-transform:uppercase;letter-spacing:.5px}
.stat-card.green .val{color:#2E7D32}
.stat-card.amber .val{color:#E65100}
.stat-card.red   .val{color:#C62828}

/* Mode badge */
.mode-badge{display:inline-block;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.mode-badge.points  {background:#E3F2FD;color:#1565C0}
.mode-badge.cashback{background:#E8F5E9;color:#2E7D32}
.mode-badge.disabled{background:#F5F5F5;color:#757575}

/* Cards / panels */
.panel{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:24px;overflow:hidden}
.panel-header{padding:16px 20px;border-bottom:1px solid #E0E0E0;display:flex;align-items:center;justify-content:space-between;gap:12px}
.panel-header h2{font-size:15px;font-weight:700;color:#1565C0}
.panel-body{padding:20px}

/* Settings form */
.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:16px}
.form-group label{display:block;font-size:12px;font-weight:600;color:#616161;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px}
.form-group input,.form-group select{width:100%;padding:9px 12px;border:1px solid #CCC;border-radius:7px;font-size:14px;font-family:inherit}
.form-group input:focus,.form-group select:focus{outline:none;border-color:#1976D2;box-shadow:0 0 0 3px rgba(25,118,210,.12)}
.form-group .hint{font-size:11px;color:#9E9E9E;margin-top:4px}

/* Buttons */
.btn{padding:9px 18px;border:none;border-radius:7px;cursor:pointer;font-size:13px;font-weight:600;font-family:inherit;display:inline-flex;align-items:center;gap:6px;transition:.15s}
.btn-primary{background:#1565C0;color:#fff}.btn-primary:hover{background:#0D47A1}
.btn-success{background:#2E7D32;color:#fff}.btn-success:hover{background:#1B5E20}
.btn-danger {background:#C62828;color:#fff}.btn-danger:hover{background:#B71C1C}
.btn-outline{background:#fff;color:#1565C0;border:1px solid #1565C0}.btn-outline:hover{background:#E3F2FD}
.btn-sm{padding:6px 12px;font-size:12px}

/* Search bar */
.search-bar{display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
.search-bar input{flex:1;min-width:200px;padding:9px 14px;border:1px solid #CCC;border-radius:7px;font-size:14px}
.search-bar input:focus{outline:none;border-color:#1976D2}

/* Table */
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{background:#1565C0;color:#fff;padding:10px 12px;text-align:left;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap}
tbody tr{border-bottom:1px solid #F0F2F5;transition:.1s}
tbody tr:hover{background:#F8F9FF}
tbody td{padding:10px 12px;font-size:13px;vertical-align:middle}
.badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700}
.badge-green{background:#E8F5E9;color:#2E7D32}
.badge-blue {background:#E3F2FD;color:#1565C0}
.badge-red  {background:#FFEBEE;color:#C62828}
.badge-grey {background:#F5F5F5;color:#757575}
.badge-amber{background:#FFF3E0;color:#E65100}

/* Auth state pill */
.auth-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600}
.auth-card{background:#E8F5E9;color:#2E7D32}
.auth-phone{background:#FFF3E0;color:#E65100}
.auth-override{background:#E3F2FD;color:#1565C0}

/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:#fff;border-radius:12px;padding:28px;width:520px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,.2)}
.modal h3{font-size:16px;font-weight:700;color:#1565C0;margin-bottom:18px}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}

/* Card preview */
.card-preview{background:linear-gradient(135deg,#1565C0,#0D47A1);border-radius:12px;padding:20px 24px;color:#fff;width:320px;font-family:'Segoe UI',sans-serif;margin:16px auto;box-shadow:0 4px 16px rgba(0,0,0,.2)}
.card-preview .store-name{font-size:13px;opacity:.8;margin-bottom:14px}
.card-preview .card-num{font-size:15px;font-weight:700;letter-spacing:2px;margin-bottom:16px;font-family:'Courier New',monospace}
.card-preview .card-holder{font-size:13px;opacity:.9}
.card-preview .card-tag{font-size:11px;opacity:.6;margin-top:4px}

/* Toast */
#toast{position:fixed;bottom:24px;right:24px;background:#323232;color:#fff;padding:12px 20px;border-radius:8px;font-size:13px;z-index:9999;display:none;max-width:340px}
#toast.show{display:block;animation:fadeIn .2s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* Tabs */
.tabs{display:flex;gap:0;border-bottom:2px solid #E0E0E0;margin-bottom:20px}
.tab-btn{padding:10px 20px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:600;color:#757575;border-bottom:3px solid transparent;margin-bottom:-2px;font-family:inherit;transition:.15s}
.tab-btn.active{color:#1565C0;border-bottom-color:#1565C0}

.tab-panel{display:none}.tab-panel.active{display:block}

/* Loyalty icon on client row */
.lcard{font-size:11px;color:#9E9E9E;font-family:'Courier New',monospace}
.lcard.has-card{color:#1565C0;font-weight:600}

/* Info box */
.info-box{background:#E3F2FD;border-left:4px solid #1976D2;padding:12px 16px;border-radius:0 8px 8px 0;font-size:13px;color:#1565C0;margin-bottom:16px}
.warn-box{background:#FFF3E0;border-left:4px solid #E65100;padding:12px 16px;border-radius:0 8px 8px 0;font-size:13px;color:#E65100;margin-bottom:16px}

@media print{.topbar,.btn,.search-bar,.modal-overlay,.tabs{display:none!important}.panel{box-shadow:none}}
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <span class="brand">&#127919; Loyalty</span>
    <a href="pos.php">POS</a>
    <a href="pos_sales.php">Sales</a>
    <?php if($is_super):?>
    <a href="pos_products.php">Products</a>
    <a href="pos_closing.php">Closing</a>
    <a href="pos_reports.php">Reports</a>
    <a href="pos_settings.php">Settings</a>
    <?php endif;?>
    <a href="pos_loyalty.php" class="active">Loyalty</a>
    <div class="spacer"></div>
    <span class="user">&#128100; <?= htmlspecialchars($agent_name) ?></span>
</div>

<div class="container">

<!-- Stats strip -->
<div class="stats">
    <div class="stat-card">
        <div class="val"><?= number_format((int)$totals['enrolled']) ?></div>
        <div class="lbl">Enrolled Customers</div>
    </div>
    <?php if($loyalty_mode === 'cashback'):?>
    <div class="stat-card green">
        <div class="val"><?= abbr((int)$totals['total_wallet'], 'LL ') ?></div>
        <div class="lbl">Total Wallet Balance</div>
    </div>
    <?php elseif($loyalty_mode === 'points'):?>
    <div class="stat-card green">
        <div class="val"><?= abbr((int)$totals['total_points']) ?> pts</div>
        <div class="lbl">Total Points in System</div>
    </div>
    <?php endif;?>
    <div class="stat-card">
        <div class="val"><?= abbr((int)$totals['total_spent'], 'LL ') ?></div>
        <div class="lbl">Total Lifetime Spending</div>
    </div>
    <div class="stat-card amber">
        <div class="val"><?= number_format((int)$today_txns['cnt']) ?></div>
        <div class="lbl">Today's Transactions</div>
    </div>
    <div class="stat-card green">
        <div class="val"><?= abbr((int)$today_txns['earned']) ?></div>
        <div class="lbl">Today's Earned
            <?= $loyalty_mode==='points' ? 'pts' : 'LL' ?></div>
    </div>
    <div class="stat-card red">
        <div class="val"><?= abbr((int)$today_txns['redeemed']) ?></div>
        <div class="lbl">Today's Redeemed</div>
    </div>
    <div class="stat-card">
        <div class="val"><span class="mode-badge <?= $loyalty_mode ?>">
            <?= ucfirst($loyalty_mode) ?>
        </span></div>
        <div class="lbl">Active Mode</div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <button class="tab-btn active" onclick="switchTab('clients')">&#128101; Enrolled Clients</button>
    <button class="tab-btn" onclick="switchTab('transactions')">&#128203; Transaction Log</button>
    <button class="tab-btn" onclick="switchTab('inactive')">&#9201; Inactive Clients</button>
    <button class="tab-btn" onclick="switchTab('catalogue')">&#11088; Redemption Catalogue</button>
    <?php if($is_super):?>
    <button class="tab-btn" onclick="switchTab('settings')">&#9881; Settings</button>
    <?php endif;?>
</div>

<!-- ── TAB: Enrolled Clients ──────────────────────────────────────────────── -->
<div class="tab-panel active" id="tab-clients">
    <div class="panel">
        <div class="panel-header">
            <h2>Enrolled Clients</h2>
            <div style="display:flex;gap:8px">
                <?php if($is_super && $loyalty_mode!=='disabled'):?>
                <button class="btn btn-success btn-sm" onclick="openEnrollModal()">+ Enroll New Client</button>
                <?php endif;?>
            </div>
        </div>
        <div class="panel-body">
            <div class="search-bar">
                <input type="text" id="client-search" placeholder="Search by name, phone, or card number..."
                    oninput="searchClients()">
            </div>
            <?php if($loyalty_mode==='disabled'):?>
            <div class="warn-box">&#9888; Loyalty program is currently disabled. Enable it in Settings to enroll clients and start earning.</div>
            <?php endif;?>
            <div class="tbl-wrap">
                <table id="clients-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Card #</th>
                            <th>Grade</th>
                            <th>Redeem</th>
                            <th><?= $loyalty_mode==='points' ? 'Points' : 'Wallet Balance' ?></th>
                            <th>Total Spent</th>
                            <th>Enrolled</th>
                            <?php if($is_super):?><th>Actions</th><?php endif;?>
                        </tr>
                    </thead>
                    <tbody id="clients-tbody">
                        <tr><td colspan="7" style="text-align:center;color:#9E9E9E;padding:24px">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ── TAB: Transactions ──────────────────────────────────────────────────── -->
<div class="tab-panel" id="tab-transactions">
    <div class="panel">
        <div class="panel-header">
            <h2>Transaction Log</h2>
        </div>
        <div class="panel-body">
            <div class="search-bar">
                <input type="date" id="txn-from" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                <input type="date" id="txn-to"   value="<?= date('Y-m-d') ?>">
                <select id="txn-type">
                    <option value="">All Types</option>
                    <option value="earned">Earned</option>
                    <option value="redeemed">Redeemed</option>
                    <option value="adjusted">Adjusted</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="loadTransactions()">Filter</button>
            </div>
            <div class="tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>Auth</th>
                            <th>Sale #</th>
                            <th>Agent</th>
                        </tr>
                    </thead>
                    <tbody id="txn-tbody">
                        <tr><td colspan="9" style="text-align:center;color:#9E9E9E;padding:24px">Select a date range and click Filter</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ── TAB: Settings ─────────────────────────────────────────────────────── -->
<?php if($is_super):?>
<div class="tab-panel" id="tab-settings">
    <div class="panel">
        <div class="panel-header"><h2>Loyalty Program Settings</h2></div>
        <div class="panel-body">
            <div class="info-box">
                &#8505; Choose one mode. Only one can be active at a time. Changing mode does not erase
                existing balances — customers keep their earned wallet or points.
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Loyalty Mode</label>
                    <select id="s-mode" onchange="onModeChange()">
                        <option value="disabled" <?=$loyalty_mode==='disabled'?'selected':''?>>Disabled</option>
                        <option value="points"   <?=$loyalty_mode==='points'  ?'selected':''?>>Points</option>
                        <option value="cashback" <?=$loyalty_mode==='cashback'?'selected':''?>>Cashback Wallet</option>
                    </select>
                </div>
            </div>

            <!-- Points settings -->
            <div id="points-settings" style="display:<?=$loyalty_mode==='points'?'block':'none'?>">
                <hr style="margin:16px 0;border:none;border-top:1px solid #E0E0E0">
                <div class="form-row">
                    <div class="form-group">
                        <label>Points per LL 1,000 Spent</label>
                        <input type="number" id="s-rate-points" min="0.1" step="0.1"
                            value="<?= $loyalty_mode==='points' ? $loyalty_rate : 1 ?>">
                        <div class="hint">e.g. 1 = customer earns 1 point per LL 1,000 spent</div>
                    </div>
                    <div class="form-group">
                        <label>1 Point Value (LL)</label>
                        <input type="number" id="s-point-value" min="100" step="100"
                            value="<?= $point_value ?>">
                        <div class="hint">e.g. 1000 = 1 point = LL 1,000 when redeeming</div>
                    </div>
                    <div class="form-group">
                        <label>Minimum Points to Redeem</label>
                        <input type="number" id="s-min-points" min="1" step="1"
                            value="<?= $loyalty_mode==='points' ? $min_redeem : 100 ?>">
                        <div class="hint">Customer must have at least this many points to redeem</div>
                    </div>
                </div>
            </div>

            <!-- Cashback settings -->
            <div id="cashback-settings" style="display:<?=$loyalty_mode==='cashback'?'block':'none'?>">
                <hr style="margin:16px 0;border:none;border-top:1px solid #E0E0E0">
                <div class="form-row">
                    <div class="form-group">
                        <label>Cashback Rate (%)</label>
                        <input type="number" id="s-rate-cashback" min="0.1" max="20" step="0.1"
                            value="<?= $loyalty_mode==='cashback' ? $loyalty_rate : 2 ?>">
                        <div class="hint">e.g. 2 = customer earns 2% of every purchase as wallet credit</div>
                    </div>
                    <div class="form-group">
                        <label>Minimum Wallet to Redeem (LL)</label>
                        <input type="number" id="s-min-wallet" min="1000" step="1000"
                            value="<?= $loyalty_mode==='cashback' ? $min_redeem : 5000 ?>">
                        <div class="hint">Minimum wallet balance required before customer can spend it</div>
                    </div>
                </div>
                <div class="warn-box">
                    &#9888; Cashback mode: physical key card required for both earning AND redeeming.
                    Phone-only lookup allows sale to proceed but no cashback is credited.
                </div>
            </div>

            <!-- Universal key card -->
            <div id="ukey-section" style="display:<?=$loyalty_mode==='cashback'?'block':'none'?>">
                <hr style="margin:16px 0;border:none;border-top:1px solid #E0E0E0">
                <div class="form-row">
                    <div class="form-group">
                        <label>Universal Key Card Barcode</label>
                        <input type="text" id="s-ukey" placeholder="Scan or type the master key card barcode"
                            value="<?= htmlspecialchars($ukey_card) ?>">
                        <div class="hint">
                            This card allows supervisor to authorize cashback when customer forgets their card.
                            Scan the physical card into this field, then save.
                        </div>
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end">
                        <div>
                        <label>Print Universal Key Card Label</label>
                        <button class="btn btn-outline btn-sm" onclick="printUkeyCard()"
                            <?= $ukey_card ? '' : 'disabled' ?>>
                            &#128424; Print Key Card Label
                        </button>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top:20px">
                <button class="btn btn-primary" onclick="saveSettings()">&#10003; Save Settings</button>
            </div>
        </div>
    </div>
</div>
<?php endif;?>

<!-- ── TAB: Inactive Clients ──────────────────────────────────────────────── -->
<div class="tab-panel" id="tab-inactive">
    <div class="panel">
        <div class="panel-header">
            <h2>&#9201; Inactive Clients</h2>
            <span id="inactive-threshold-label" style="font-size:12px;color:#9E9E9E;"></span>
        </div>
        <div class="panel-body">
            <div id="inactive-note" class="warn-box" style="display:none;"></div>
            <div class="tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Grade</th>
                            <th>Last Purchase</th>
                            <th>Inactive For</th>
                            <th><?= $loyalty_mode==='points' ? 'Points' : 'Wallet' ?></th>
                            <th>Total Spent</th>
                            <?php if($is_super):?><th>Action</th><?php endif;?>
                        </tr>
                    </thead>
                    <tbody id="inactive-tbody">
                        <tr><td colspan="8" style="text-align:center;color:#9E9E9E;padding:24px">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ── TAB: Redemption Catalogue ─────────────────────────────────────────── -->
<div class="tab-panel" id="tab-catalogue">
    <div class="panel">
        <div class="panel-header">
            <h2>&#11088; Redemption Catalogue</h2>
            <div style="font-size:12px;color:#9E9E9E;">Products customers can claim free using points (Points mode only)</div>
        </div>
        <div class="panel-body">
            <?php if($loyalty_mode !== 'points'): ?>
            <div class="warn-box">&#9888; Redemption Catalogue is only available in Points mode. Switch loyalty mode in Settings.</div>
            <?php else: ?>
            <div class="info-box">&#8505; Set a points price on any product to add it to the catalogue. Customers with enough points see a "Redeem" button on that item in their cart.</div>
            <div class="search-bar">
                <input type="text" id="cat-search" placeholder="Search products..." oninput="searchCatalogue()">
                <select id="cat-filter">
                    <option value="all">All Products</option>
                    <option value="redeemable">In Catalogue Only</option>
                </select>
                <button class="btn btn-outline btn-sm" onclick="loadCatalogue()">&#128269; Refresh</button>
            </div>
            <div class="tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price (LL)</th>
                            <th>In Catalogue</th>
                            <th>Points Required</th>
                            <?php if($is_super):?><th>Actions</th><?php endif;?>
                        </tr>
                    </thead>
                    <tbody id="cat-tbody">
                        <tr><td colspan="6" style="text-align:center;color:#9E9E9E;padding:24px">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</div><!-- /container -->

<!-- ── Enroll Modal ─────────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="enroll-modal">
    <div class="modal">
        <h3>&#127919; Enroll New Client</h3>
        <div class="search-bar" style="margin-bottom:12px">
            <input type="text" id="enroll-search" placeholder="Search client by name or phone..."
                oninput="searchEnroll()">
        </div>
        <div id="enroll-results" style="max-height:220px;overflow-y:auto;border:1px solid #E0E0E0;border-radius:8px;margin-bottom:16px">
            <div style="padding:16px;text-align:center;color:#9E9E9E">Type to search...</div>
        </div>
        <div id="enroll-selected" style="display:none">
            <div class="card-preview" id="card-preview-box">
                <div class="store-name">NCC LOYALTY CARD</div>
                <div class="card-num" id="preview-barcode">NCC-L-______</div>
                <div class="card-holder" id="preview-name">Customer Name</div>
                <div class="card-tag">Loyalty Member</div>
            </div>
            <div id="barcode-svg" style="text-align:center;margin:8px 0"></div>
        </div>
        <div class="modal-actions">
            <button class="btn btn-outline" onclick="closeModal('enroll-modal')">Cancel</button>
            <button class="btn btn-success" id="btn-generate" onclick="generateCard()" disabled>
                &#127915; Generate Card
            </button>
            <button class="btn btn-primary" id="btn-print-card" onclick="printCard()" style="display:none">
                &#128424; Print Card
            </button>
        </div>
    </div>
</div>

<!-- ── Adjust Modal ─────────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="adjust-modal">
    <div class="modal">
        <h3>&#9881; Manual Balance Adjustment</h3>
        <input type="hidden" id="adj-client-id">
        <div id="adj-client-info" style="margin-bottom:16px;padding:12px;background:#F5F5F5;border-radius:8px;font-size:13px"></div>
        <div class="form-row">
            <div class="form-group">
                <label>Amount (positive = add, negative = subtract)</label>
                <input type="number" id="adj-amount" placeholder="e.g. 5000 or -2000">
                <div class="hint" id="adj-hint"></div>
            </div>
        </div>
        <div class="form-group" style="margin-bottom:16px">
            <label>Reason (required)</label>
            <input type="text" id="adj-note" placeholder="e.g. Correction for sale #49">
        </div>
        <div class="modal-actions">
            <button class="btn btn-outline" onclick="closeModal('adjust-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="doAdjust()">Apply Adjustment</button>
        </div>
    </div>
</div>

<!-- ── Catalogue Edit Modal ─────────────────────────────────────────────────── -->
<div class="modal-overlay" id="cat-edit-modal">
    <div class="modal">
        <h3>&#11088; Catalogue Settings</h3>
        <input type="hidden" id="cat-edit-id">
        <div style="font-size:14px;font-weight:700;color:#1565C0;margin-bottom:16px;" id="cat-edit-name"></div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <input type="checkbox" id="cat-edit-redeemable"
                onchange="document.getElementById('cat-pts-row').style.display=this.checked?'':'none'">
            <label for="cat-edit-redeemable" style="font-size:13px;font-weight:600;cursor:pointer;">
                Include in Redemption Catalogue
            </label>
        </div>
        <div id="cat-pts-row" class="form-group" style="display:none;">
            <label>Points Required to Redeem (per unit)</label>
            <input type="number" id="cat-edit-pts" min="1" step="1" placeholder="e.g. 350">
            <div class="hint">Customer needs this many points to get one unit free</div>
        </div>
        <div class="modal-actions">
            <button class="btn btn-outline" onclick="closeModal('cat-edit-modal')">Cancel</button>
            <button class="btn btn-success" onclick="saveCatalogueItem()">&#10003; Save</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast"></div>

<!-- JsBarcode -->
<script src="jsbarcode.min.js"></script>

<script>
const LOYALTY_MODE = '<?= $loyalty_mode ?>';
const IS_SUPER     = <?= $is_super ? 'true' : 'false' ?>;
let enrollClientId   = null;
let enrollClientName = '';
let generatedCard    = '';

function switchTab(name) {
    document.querySelectorAll('.tab-btn').forEach((b,i) => {
        const panels = ['clients','transactions','inactive','catalogue','settings'];
        b.classList.toggle('active', panels[i] === name);
    });
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    const panel = document.getElementById('tab-' + name);
    if (panel) panel.classList.add('active');
    if (name === 'clients')      loadClients();
    if (name === 'transactions') loadTransactions();
    if (name === 'inactive')     loadInactive();
    if (name === 'catalogue')    loadCatalogue();
}

// ── Catalogue ──────────────────────────────────────────────────────────────────
var _catalogueAll = [];

function loadCatalogue(q) {
    var query = q !== undefined ? q : (document.getElementById('cat-search') ? document.getElementById('cat-search').value : '');
    var filter = document.getElementById('cat-filter') ? document.getElementById('cat-filter').value : 'all';
    var url = 'ajax/pos_loyalty_ajax.php?action=get_catalogue&q=' + encodeURIComponent(query);
    if (filter === 'redeemable') url += '&redeemable=1';
    fetch(url)
        .then(r => r.json()).then(data => {
        if (!data.success) return;
        _catalogueAll = data.products;
        renderCatalogue(_catalogueAll);
    });
}

function searchCatalogue() {
    clearTimeout(window._catTimer);
    window._catTimer = setTimeout(() => loadCatalogue(), 300);
}

function renderCatalogue(products) {
    var tbody = document.getElementById('cat-tbody');
    if (!products.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#9E9E9E;padding:24px">No products found</td></tr>';
        return;
    }
    tbody.innerHTML = products.map(p => {
        var inCat = p.redeemable == 1;
        var catBadge = inCat
            ? '<span class="badge badge-green">&#11088; In Catalogue</span>'
            : '<span class="badge badge-grey">Not in catalogue</span>';
        var ptsCell = inCat && p.points_price
            ? '<b>' + Number(p.points_price).toLocaleString() + ' pts</b>'
            : '<span style="color:#9E9E9E;">—</span>';
        var actions = IS_SUPER
            ? `<button class="btn btn-outline btn-sm" onclick="openCatalogueEdit(${p.codep},'${(p.nomp||'').replace(/'/g,"\\'")}',${p.redeemable||0},${p.points_price||0})">Edit</button>`
            : '';
        return `<tr>
            <td><b>${p.nomp}</b></td>
            <td>${p.category || '—'}</td>
            <td>LL ${Number(p.price).toLocaleString()}</td>
            <td>${catBadge}</td>
            <td>${ptsCell}</td>
            ${IS_SUPER ? `<td>${actions}</td>` : ''}
        </tr>`;
    }).join('');
}

// ── Catalogue Edit Modal ────────────────────────────────────────────────────────
function openCatalogueEdit(id, name, redeemable, pointsPrice) {
    document.getElementById('cat-edit-id').value    = id;
    document.getElementById('cat-edit-name').textContent = name;
    document.getElementById('cat-edit-redeemable').checked = redeemable == 1;
    document.getElementById('cat-edit-pts').value   = pointsPrice || '';
    document.getElementById('cat-pts-row').style.display = redeemable == 1 ? '' : 'none';
    openModal('cat-edit-modal');
}

function saveCatalogueItem() {
    var id         = document.getElementById('cat-edit-id').value;
    var redeemable = document.getElementById('cat-edit-redeemable').checked ? 1 : 0;
    var pts        = document.getElementById('cat-edit-pts').value;
    fetch('ajax/pos_loyalty_ajax.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=save_catalogue&product_id=${id}&redeemable=${redeemable}&points_price=${pts}`
    }).then(r => r.json()).then(data => {
        if (data.success) { toast('Catalogue updated'); closeModal('cat-edit-modal'); loadCatalogue(); }
        else toast(data.error, false);
    });
}

function loadInactive() {
    fetch('ajax/pos_loyalty_ajax.php?action=get_inactive')
        .then(r => r.json()).then(data => {
        const tbody = document.getElementById('inactive-tbody');
        const label = document.getElementById('inactive-threshold-label');
        const note  = document.getElementById('inactive-note');
        if (label) label.textContent = `Threshold: ${data.threshold_days} days since last purchase`;
        if (!data.clients || !data.clients.length) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:#2E7D32;padding:24px">
                &#10003; No inactive loyalty clients found</td></tr>`;
            if (note) note.style.display = 'none';
            return;
        }
        if (note) {
            note.style.display = '';
            note.textContent = `${data.clients.length} loyalty client(s) have not purchased in over ${data.threshold_days} days. Review for demotion or re-engagement.`;
        }
        const gradeColors = {regular:'#9E9E9E', gold:'#F59E0B', platinum:'#6366F1', premium:'#EC4899'};
        tbody.innerHTML = data.clients.map(c => {
            const name  = [c.prenom, c.nom].filter(Boolean).join(' ') || c.company || '—';
            const grade = c.grade || 'regular';
            const gcol  = gradeColors[grade] || '#9E9E9E';
            const gradeBadge = `<span class="badge" style="background:${gcol}22;color:${gcol};">${grade.charAt(0).toUpperCase()+grade.slice(1)}</span>`;
            const bal   = LOYALTY_MODE === 'points'
                        ? Number(c.loyalty_points).toLocaleString() + ' pts'
                        : 'LL ' + Number(c.wallet_balance).toLocaleString();
            const demoteBtn = IS_SUPER && grade !== 'regular'
                ? `<button class="btn btn-danger btn-sm" onclick="demoteClient(${c.id},'${name.replace(/'/g,"\\'")}','${grade}')">Demote</button>`
                : '—';
            return `<tr>
                <td><b>${name}</b></td>
                <td>${c.number || '—'}</td>
                <td>${gradeBadge}</td>
                <td>${c.last_purchase_date || '—'}</td>
                <td><b style="color:#C62828;">${c.days_inactive} days</b></td>
                <td>${bal}</td>
                <td>LL ${Number(c.total_spent).toLocaleString()}</td>
                ${IS_SUPER ? `<td>${demoteBtn}</td>` : ''}
            </tr>`;
        }).join('');
    });
}

function demoteClient(id, name, currentGrade) {
    const grades = ['regular','gold','platinum','premium'];
    const idx    = grades.indexOf(currentGrade);
    const target = idx > 0 ? grades[idx-1] : 'regular';
    if (!confirm(`Demote ${name} from ${currentGrade} to ${target}?`)) return;
    fetch('ajax/pos_loyalty_ajax.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=set_grade&client_id=${id}&grade=${target}`
    }).then(r => r.json()).then(data => {
        if (data.success) { toast(`${name} demoted to ${target}`); loadInactive(); loadClients(); }
        else toast(data.error, false);
    });
}

// ── Toast ──────────────────────────────────────────────────────────────────────
function toast(msg, ok=true) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = ok ? '#2E7D32' : '#C62828';
    t.className = 'show';
    setTimeout(() => t.className = '', 3000);
}

// ── Modal helpers ──────────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// ── Load enrolled clients ──────────────────────────────────────────────────────
function loadClients(q='') {
    fetch(`ajax/pos_loyalty_ajax.php?action=get_enrolled&q=${encodeURIComponent(q)}`)
        .then(r => r.json()).then(data => {
        if (!data.success) return;
        const tbody = document.getElementById('clients-tbody');
        if (!data.clients.length) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;color:#9E9E9E;padding:24px">
                No enrolled clients found</td></tr>`;
            return;
        }
        const minGrade = data.settings ? (data.settings.loyalty_min_grade || 'gold') : 'gold';
        const gradeOrder = {regular:0, gold:1, platinum:2, premium:3};
        const gradeColors = {regular:'#9E9E9E', gold:'#F59E0B', platinum:'#6366F1', premium:'#EC4899'};
        tbody.innerHTML = data.clients.map(c => {
            const name   = [c.prenom, c.nom].filter(Boolean).join(' ') || c.company || '—';
            const bal    = LOYALTY_MODE === 'points'
                         ? Number(c.loyalty_points).toLocaleString() + ' pts'
                         : 'LL ' + Number(c.wallet_balance).toLocaleString();
            const enroll = c.loyalty_enrolled ? c.loyalty_enrolled.substring(0,10) : '—';
            const grade  = c.grade || 'regular';
            const gcol   = gradeColors[grade] || '#9E9E9E';
            const gradeBadge = `<span class="badge" style="background:${gcol}22;color:${gcol};">${grade.charAt(0).toUpperCase()+grade.slice(1)}</span>`;
            const canRedeem  = (gradeOrder[grade]||0) >= (gradeOrder[minGrade]||1);
            const redeemBadge = canRedeem
                ? '<span class="badge badge-green">&#10003; Yes</span>'
                : `<span class="badge badge-grey">&#128274; ${minGrade.charAt(0).toUpperCase()+minGrade.slice(1)}+</span>`;
            const actions = IS_SUPER ? `
                <button class="btn btn-outline btn-sm" onclick='openAdjust(${c.id},"${name.replace(/'/g,"\\'")}",${c.wallet_balance},${c.loyalty_points})'>Adjust</button>
                <button class="btn btn-danger btn-sm" onclick="revokeCard(${c.id},'${c.loyalty_card}')">Revoke</button>
            ` : '';
            return `<tr>
                <td><b>${name}</b></td>
                <td>${c.number || '—'}</td>
                <td><span class="lcard has-card">${c.loyalty_card}</span></td>
                <td>${gradeBadge}</td>
                <td>${redeemBadge}</td>
                <td><b>${bal}</b></td>
                <td>LL ${Number(c.total_spent).toLocaleString()}</td>
                <td>${enroll}</td>
                ${IS_SUPER ? `<td style="white-space:nowrap">${actions}</td>` : ''}
            </tr>`;
        }).join('');
    });
}

function searchClients() {
    clearTimeout(window._csTimer);
    window._csTimer = setTimeout(() => loadClients(document.getElementById('client-search').value), 300);
}

// ── Load transactions ──────────────────────────────────────────────────────────
function loadTransactions() {
    const from  = document.getElementById('txn-from').value;
    const to    = document.getElementById('txn-to').value;
    const type  = document.getElementById('txn-type').value;
    fetch(`ajax/pos_loyalty_ajax.php?action=get_transactions&from=${from}&to=${to}&type=${type}&limit=100`)
        .then(r => r.json()).then(data => {
        if (!data.success) return;
        const tbody = document.getElementById('txn-tbody');
        if (!data.transactions.length) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;color:#9E9E9E;padding:24px">No transactions found</td></tr>`;
            return;
        }
        const modeLabel = LOYALTY_MODE === 'points' ? 'pts' : 'LL';
        tbody.innerHTML = data.transactions.map(t => {
            const typeBadge = t.type === 'earned'   ? '<span class="badge badge-green">Earned</span>'
                            : t.type === 'redeemed' ? '<span class="badge badge-red">Redeemed</span>'
                            : '<span class="badge badge-grey">Adjusted</span>';
            const authBadge = t.auth_method === 'card'               ? '<span class="auth-pill auth-card">Card</span>'
                            : t.auth_method === 'supervisor_override' ? '<span class="auth-pill auth-override">Supervisor</span>'
                            : '<span class="auth-pill auth-phone">Phone</span>';
            const amt    = (t.mode === 'cashback' ? 'LL ' : '') + Number(t.amount).toLocaleString() + (t.mode==='points'?' pts':'');
            const before = (t.mode === 'cashback' ? 'LL ' : '') + Number(t.balance_before).toLocaleString();
            const after  = (t.mode === 'cashback' ? 'LL ' : '') + Number(t.balance_after).toLocaleString();
            return `<tr>
                <td>${t.created_at.substring(0,16)}</td>
                <td>${t.client_name}</td>
                <td>${typeBadge}</td>
                <td><b>${amt}</b></td>
                <td>${before}</td>
                <td>${after}</td>
                <td>${authBadge}</td>
                <td>${t.sale_id ? '#'+t.sale_id : '—'}</td>
                <td>${t.agent_name}</td>
            </tr>`;
        }).join('');
    });
}

// ── Enroll modal ──────────────────────────────────────────────────────────────
function openEnrollModal() {
    enrollClientId = null; generatedCard = '';
    document.getElementById('enroll-search').value = '';
    document.getElementById('enroll-results').innerHTML = '<div style="padding:16px;text-align:center;color:#9E9E9E">Type to search...</div>';
    document.getElementById('enroll-selected').style.display = 'none';
    document.getElementById('btn-generate').disabled = true;
    document.getElementById('btn-print-card').style.display = 'none';
    openModal('enroll-modal');
}

function searchEnroll() {
    const q = document.getElementById('enroll-search').value;
    if (q.length < 2) return;
    fetch(`ajax/pos_loyalty_ajax.php?action=get_enrolled&q=${encodeURIComponent(q)}`)
        .then(r => r.json()).then(data => {
        // Search ALL clients not just enrolled — use CRM search
        fetch(`ajax/pos_ajax.php?action=search_clients&q=${encodeURIComponent(q)}`)
            .then(r2 => r2.json()).then(data2 => {
            if (!data2.success) return;
            const box = document.getElementById('enroll-results');
            if (!data2.data.length) {
                box.innerHTML = '<div style="padding:16px;text-align:center;color:#9E9E9E">No clients found</div>';
                return;
            }
            box.innerHTML = data2.data.map(c =>
                `<div style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #F0F2F5;font-size:13px"
                      onmouseover="this.style.background='#F8F9FF'" onmouseout="this.style.background=''"
                      onclick="selectEnroll(${c.id},'${(c.name||'').replace(/'/g,"\\'")}')">
                    <b>${c.name || '—'}</b>
                    ${c.company ? `<span style="color:#9E9E9E"> · ${c.company}</span>` : ''}
                    ${c.number  ? `<span style="color:#9E9E9E"> · ${c.number}</span>`  : ''}
                 </div>`
            ).join('');
        });
    });
}

function selectEnroll(id, name) {
    enrollClientId   = id;
    enrollClientName = name;
    document.getElementById('enroll-results').innerHTML =
        `<div style="padding:10px 14px;background:#E8F5E9;color:#2E7D32;font-size:13px;font-weight:600">
            &#10003; Selected: ${name} (ID #${id})
         </div>`;
    document.getElementById('btn-generate').disabled = false;
}

function generateCard() {
    if (!enrollClientId) return;
    fetch('ajax/pos_loyalty_ajax.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=generate_card&client_id=${enrollClientId}`
    }).then(r => r.json()).then(data => {
        if (!data.success) { toast(data.error, false); return; }
        generatedCard = data.barcode;
        document.getElementById('preview-barcode').textContent = data.barcode;
        document.getElementById('preview-name').textContent    = data.client_name;

        // Render barcode SVG
        const svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
        svg.setAttribute('id','barcode-render');
        document.getElementById('barcode-svg').innerHTML = '';
        document.getElementById('barcode-svg').appendChild(svg);
        JsBarcode('#barcode-render', data.barcode, {
            format:'CODE128', width:2, height:50, displayValue:true,
            font:'Courier New', fontSize:13, textMargin:4, background:'#fff'
        });

        document.getElementById('enroll-selected').style.display = 'block';
        document.getElementById('btn-generate').style.display    = 'none';
        document.getElementById('btn-print-card').style.display  = 'inline-flex';
        toast('Card generated: ' + data.barcode);
        loadClients();
    });
}

function printCard() {
    const win = window.open('','_blank','width=400,height=300');
    const svg = document.getElementById('barcode-render');
    const svgStr = svg ? svg.outerHTML : '';
    win.document.write(`<!DOCTYPE html><html><head>
        <style>*{margin:0;padding:0;font-family:'Segoe UI',sans-serif}
        body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f0f0f0}
        .card{background:linear-gradient(135deg,#1565C0,#0D47A1);border-radius:12px;padding:20px 24px;color:#fff;width:320px}
        .store{font-size:12px;opacity:.7;margin-bottom:12px}
        .num{font-size:14px;font-weight:700;letter-spacing:2px;margin-bottom:12px;font-family:monospace}
        .holder{font-size:13px;opacity:.9}
        .tag{font-size:10px;opacity:.6;margin-top:4px}
        .barcode{background:#fff;border-radius:8px;padding:10px;margin-top:12px;text-align:center}
        </style></head><body>
        <div>
          <div class="card">
            <div class="store">NCC LOYALTY CARD</div>
            <div class="num">${generatedCard}</div>
            <div class="holder">${enrollClientName}</div>
            <div class="tag">Loyalty Member</div>
            <div class="barcode">${svgStr}</div>
          </div>
        </div>
        <script>window.onload=()=>{setTimeout(()=>window.print(),300)}<\/script>
        </body></html>`);
    win.document.close();
}

// ── Revoke card ────────────────────────────────────────────────────────────────
function revokeCard(id, card) {
    if (!confirm(`Revoke card ${card} from client #${id}?\nBalance is preserved.`)) return;
    fetch('ajax/pos_loyalty_ajax.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=revoke_card&client_id=${id}`
    }).then(r => r.json()).then(data => {
        if (data.success) { toast('Card revoked — balance preserved'); loadClients(); }
        else toast(data.error, false);
    });
}

// ── Adjust balance ─────────────────────────────────────────────────────────────
function openAdjust(id, name, wallet, points) {
    document.getElementById('adj-client-id').value = id;
    const mode   = LOYALTY_MODE;
    const balance = mode === 'cashback' ? 'LL ' + Number(wallet).toLocaleString()
                                        : Number(points).toLocaleString() + ' pts';
    document.getElementById('adj-client-info').innerHTML =
        `<b>${name}</b> — Current ${mode === 'cashback' ? 'wallet' : 'points'}: <b>${balance}</b>`;
    document.getElementById('adj-hint').textContent =
        mode === 'cashback' ? 'Enter LBP amount (e.g. 10000 to add LL 10,000 or -5000 to subtract)'
                            : 'Enter number of points (e.g. 50 to add or -20 to subtract)';
    document.getElementById('adj-amount').value = '';
    document.getElementById('adj-note').value   = '';
    openModal('adjust-modal');
}

function doAdjust() {
    const id     = document.getElementById('adj-client-id').value;
    const amount = document.getElementById('adj-amount').value;
    const note   = document.getElementById('adj-note').value.trim();
    if (!amount || !note) { toast('Amount and reason are required', false); return; }
    fetch('ajax/pos_loyalty_ajax.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=adjust_balance&client_id=${id}&amount=${amount}&note=${encodeURIComponent(note)}`
    }).then(r => r.json()).then(data => {
        if (data.success) {
            toast('Balance adjusted — new balance: ' + Number(data.balance_after).toLocaleString());
            closeModal('adjust-modal');
            loadClients();
        } else toast(data.error, false);
    });
}

// ── Settings ───────────────────────────────────────────────────────────────────
function onModeChange() {
    const mode = document.getElementById('s-mode').value;
    document.getElementById('points-settings').style.display   = mode==='points'   ? 'block' : 'none';
    document.getElementById('cashback-settings').style.display = mode==='cashback' ? 'block' : 'none';
    document.getElementById('ukey-section').style.display      = mode==='cashback' ? 'block' : 'none';
}

function saveSettings() {
    const mode   = document.getElementById('s-mode').value;
    const rate   = mode==='points'   ? document.getElementById('s-rate-points').value
                 : mode==='cashback' ? document.getElementById('s-rate-cashback').value
                 : 0;
    const pv     = document.getElementById('s-point-value')?.value || 1000;
    const minR   = mode==='points'   ? document.getElementById('s-min-points').value
                 : mode==='cashback' ? document.getElementById('s-min-wallet').value
                 : 5000;
    const ukey   = document.getElementById('s-ukey')?.value || '';

    fetch('ajax/pos_loyalty_ajax.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=save_settings&loyalty_mode=${mode}&loyalty_rate=${rate}` +
              `&loyalty_point_value=${pv}&loyalty_min_redeem=${minR}` +
              `&universal_key_card=${encodeURIComponent(ukey)}`
    }).then(r => r.json()).then(data => {
        if (data.success) { toast('Settings saved — reload to apply mode change'); }
        else toast(data.error, false);
    });
}

function printUkeyCard() {
    const code = document.getElementById('s-ukey').value.trim();
    if (!code) return;
    const win = window.open('','_blank','width=400,height:250');
    win.document.write(`<!DOCTYPE html><html><head>
        <style>*{margin:0;padding:0;font-family:'Segoe UI',sans-serif}
        body{display:flex;align-items:center;justify-content:center;min-height:100vh}
        .card{background:linear-gradient(135deg,#B71C1C,#7F0000);border-radius:12px;padding:20px 24px;color:#fff;width:320px}
        .title{font-size:13px;opacity:.7;margin-bottom:10px;font-weight:700;letter-spacing:1px}
        .num{font-size:14px;font-weight:700;letter-spacing:2px;font-family:monospace;margin-bottom:8px}
        .tag{font-size:10px;opacity:.6}
        </style></head><body>
        <div class="card">
            <div class="title">NCC POS — SUPERVISOR KEY CARD</div>
            <div class="num">${code}</div>
            <div class="tag">Universal Loyalty Override — Keep Secure</div>
        </div>
        <script>window.onload=()=>{setTimeout(()=>window.print(),300)}<\/script>
        </body></html>`);
    win.document.close();
}

// ── Init ───────────────────────────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => loadClients());
</script>
</body>
</html>
