<?php
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$conn = mysqli_connect("192.168.1.101","root","1Sys9Admeen72","nccleb_test");
$co_rate    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT usd_to_lbp, vat_rate FROM company_settings LIMIT 1"));
$usd_to_lbp = (float)($co_rate['usd_to_lbp'] ?? 89500);
$vat_rate   = (float)($co_rate['vat_rate']   ?? 0);
mysqli_set_charset($conn,'utf8mb4');

// Selected date — default today
$date = mysqli_real_escape_string($conn, $_GET['date'] ?? date('Y-m-d'));

// ── Main aggregates ────────────────────────────────────────────────────────
$summary = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) as total_sales,
        SUM(total * (1 + $vat_rate/100)) as gross_total,
        SUM(discount * (1 + $vat_rate/100)) as total_discounts,
        SUM(final_total * (1 + $vat_rate/100)) as net_total,
        SUM(CASE WHEN status='refunded' THEN final_total * (1 + $vat_rate/100) ELSE 0 END) as refunded_total,
        COUNT(CASE WHEN status='refunded' THEN 1 END) as refunded_count
     FROM pos_sales
     WHERE DATE(created_at) = '$date' AND status IN ('completed','pending','refunded')"
));

// ── Per payment method ─────────────────────────────────────────────────────
$by_payment_res = mysqli_query($conn,
    "SELECT payment_method,
        COUNT(*) as count,
        SUM(final_total * (1 + $vat_rate/100)) as total,
        SUM(discount) as discounts
     FROM pos_sales
     WHERE DATE(created_at) = '$date' AND status IN ('completed','pending')
     GROUP BY payment_method
     ORDER BY total DESC"
);
$by_payment = [];
while ($r = mysqli_fetch_assoc($by_payment_res)) $by_payment[] = $r;


// ── Cash drawer reconciliation ─────────────────────────────────────────────
$cash_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(paid_usd),0)    as paid_usd,
            COALESCE(SUM(paid_lbp),0)    as paid_lbp,
            COALESCE(SUM(change_usd),0)  as change_usd,
            COALESCE(SUM(change_lbp),0)  as change_lbp
     FROM pos_sales
     WHERE DATE(created_at) = '$date' AND status IN ('completed','pending') AND payment_method='cash'"
));
// Expected in drawer = received minus change returned
$expected_usd = max(0, (float)$cash_row['paid_usd'] - (float)$cash_row['change_usd']);
$expected_lbp = max(0, (float)$cash_row['paid_lbp'] - (float)$cash_row['change_lbp']);

// ── Hourly breakdown ──────────────────────────────────────────────────────
$hourly_res = mysqli_query($conn,
    "SELECT HOUR(created_at) as hr, COUNT(*) as cnt,
            SUM(final_total * (1 + $vat_rate/100)) as total
     FROM pos_sales
     WHERE DATE(created_at) = '$date' AND status IN ('completed','pending')
     GROUP BY HOUR(created_at) ORDER BY hr"
);
$hourly = [];
while ($r = mysqli_fetch_assoc($hourly_res)) $hourly[] = $r;

// ── Top products of the day ───────────────────────────────────────────────
$top_res = mysqli_query($conn,
    "SELECT si.product_name, SUM(si.qty) as qty_sold, SUM(si.subtotal) as revenue
     FROM pos_sale_items si
     JOIN pos_sales s ON s.id = si.sale_id
     WHERE DATE(s.created_at) = '$date' AND s.status IN ('completed','pending')
     GROUP BY si.product_name ORDER BY qty_sold DESC LIMIT 6"
);
$top_products = [];
while ($r = mysqli_fetch_assoc($top_res)) $top_products[] = $r;

// ── Sales list for the day ────────────────────────────────────────────────
$sales_res = mysqli_query($conn,
    "SELECT s.*, (SELECT COUNT(*) FROM pos_sale_items WHERE sale_id=s.id) as item_count
     FROM pos_sales s
     WHERE DATE(s.created_at)='$date' AND s.status IN ('completed','pending','refunded')
     ORDER BY s.created_at DESC"
);
$sales = [];
while ($r = mysqli_fetch_assoc($sales_res)) $sales[] = $r;

mysqli_close($conn);

// ── Helpers ───────────────────────────────────────────────────────────────
$pay_labels = [
    'cash'=>'Cash','card'=>'Card','omt'=>'OMT','whish'=>'Whish',
    'bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','credit'=>'Credit'
];
$pay_icons = [
    'cash'=>'fa-money-bill-wave','card'=>'fa-credit-card','omt'=>'fa-mobile-alt',
    'whish'=>'fa-mobile-alt','bank_transfer'=>'fa-university',
    'cheque'=>'fa-file-invoice','credit'=>'fa-clock'
];
$pay_colors = [
    'cash'=>'#10b981','card'=>'#1976D2','omt'=>'#7c3aed',
    'whish'=>'#db2777','bank_transfer'=>'#0369a1',
    'cheque'=>'#92400e','credit'=>'#ef4444'
];
function sym($c) { return $c==='LBP'?'LL ':($c==='EUR'?'€':'$'); }
function fmt($v, $c='LBP') {
    global $usd_to_lbp;
    $lbp = round((float)$v * $usd_to_lbp);
    return 'LL '.number_format($lbp, 0);
}
function fmtUsd($v) { return '$'.number_format((float)$v, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Cash Closing — <?= date('d M Y', strtotime($date)) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; min-height:100vh; }

/* ── Topbar ── */
.topbar {
    background:linear-gradient(135deg,#1976D2,#0D47A1);
    color:white; padding:14px 24px;
    display:flex; align-items:center; gap:15px;
    box-shadow:0 2px 8px rgba(0,0,0,.2);
}
.topbar h1 { font-size:18px; font-weight:700; }
.topbar a {
    color:white; text-decoration:none;
    background:rgba(255,255,255,.15);
    padding:8px 16px; border-radius:6px;
    font-size:13px; font-weight:600;
    display:flex; align-items:center; gap:6px;
}
.topbar a:hover { background:rgba(255,255,255,.25); }
.topbar .ml { margin-left:auto; display:flex; gap:8px; }

/* ── Container ── */
.container { max-width:1100px; margin:0 auto; padding:24px 20px; }

/* ── Date selector ── */
.date-bar {
    background:white; border-radius:12px;
    padding:16px 20px; margin-bottom:20px;
    display:flex; align-items:center; gap:12px;
    box-shadow:0 1px 6px rgba(0,0,0,.07);
    flex-wrap:wrap;
}
.date-bar label { font-size:13px; font-weight:700; color:#374151; }
.date-bar input[type=date] {
    padding:9px 14px; border:2px solid #e5e7eb;
    border-radius:8px; font-size:14px; font-weight:600;
    color:#1a1a2e; cursor:pointer;
}
.date-bar input[type=date]:focus { outline:none; border-color:#1976D2; }
.btn { padding:9px 18px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
.btn-blue  { background:#1976D2; color:white; }
.btn-blue:hover  { background:#0D47A1; }
.btn-green { background:#10b981; color:white; }
.btn-green:hover { background:#059669; }
.btn-gray  { background:#f3f4f6; color:#374151; border:2px solid #e5e7eb; }
.btn-gray:hover  { background:#e5e7eb; }

/* ── Day banner ── */
.day-banner {
    background:linear-gradient(135deg,#0D47A1,#1565C0);
    border-radius:14px; padding:20px 24px;
    margin-bottom:20px; color:white;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:12px;
}
.day-banner .day-title { font-size:22px; font-weight:900; }
.day-banner .day-sub   { font-size:13px; opacity:.8; margin-top:2px; }
.day-banner .day-meta  { display:flex; gap:24px; flex-wrap:wrap; }
.day-banner .meta-item { text-align:center; }
.day-banner .meta-val  { font-size:20px; font-weight:800; }
.day-banner .meta-lbl  { font-size:11px; opacity:.75; text-transform:uppercase; letter-spacing:.5px; }

/* ── Stats grid ── */
.stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
@media(max-width:800px) { .stats { grid-template-columns:repeat(2,1fr); } }
.stat-card {
    background:white; border-radius:12px; padding:18px 20px;
    box-shadow:0 1px 6px rgba(0,0,0,.07);
    border-left:4px solid #1976D2;
    display:flex; flex-direction:column; gap:4px;
}
.stat-card.green  { border-color:#10b981; }
.stat-card.orange { border-color:#f59e0b; }
.stat-card.red    { border-color:#ef4444; }
.stat-card.purple { border-color:#7c3aed; }
.stat-card .s-val { font-size:22px; font-weight:800; color:#1976D2; }
.stat-card.green  .s-val { color:#10b981; }
.stat-card.orange .s-val { color:#f59e0b; }
.stat-card.red    .s-val { color:#ef4444; }
.stat-card.purple .s-val { color:#7c3aed; }
.stat-card .s-lbl { font-size:12px; color:#6b7280; font-weight:600; text-transform:uppercase; }

/* ── Two-column grid ── */
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
@media(max-width:750px) { .two-col { grid-template-columns:1fr; } }

/* ── Card ── */
.card { background:white; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.07); overflow:hidden; }
.card-header { padding:16px 20px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; gap:10px; }
.card-header i { color:#1976D2; }
.card-header h3 { font-size:14px; font-weight:800; color:#1a1a2e; }
.card-body { padding:16px 20px; }

/* ── Payment method rows ── */
.pay-row {
    display:flex; align-items:center; gap:12px;
    padding:11px 0; border-bottom:1px solid #f3f4f6;
}
.pay-row:last-child { border-bottom:none; }
.pay-icon {
    width:36px; height:36px; border-radius:9px;
    display:flex; align-items:center; justify-content:center;
    font-size:15px; flex-shrink:0; color:white;
}
.pay-info { flex:1; }
.pay-name  { font-size:13px; font-weight:700; color:#1a1a2e; }
.pay-meta  { font-size:11px; color:#9ca3af; }
.pay-total { font-size:15px; font-weight:800; color:#1a1a2e; }
.pay-badge { font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; background:#eff6ff; color:#1976D2; }

/* ── Currency pills ── */
    flex:1; min-width:80px; padding:14px 12px;
    border-radius:10px; text-align:center;
    border:2px solid #f3f4f6;
}

/* ── Reconciliation box ── */
.reconcile-box {
    background:#f8fafc; border-radius:12px;
    border:2px dashed #e5e7eb; padding:18px;
    margin-bottom:20px;
}
.reconcile-box h3 { font-size:14px; font-weight:800; color:#1a1a2e; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.rec-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f3f4f6; font-size:14px; }
.rec-row:last-child { border:none; }
.rec-row .rec-lbl { color:#6b7280; font-weight:600; }
.rec-row .rec-val { font-weight:800; color:#1a1a2e; }
.rec-input-wrap { display:flex; align-items:center; gap:8px; }
.rec-input-wrap span { font-size:14px; font-weight:700; color:#6b7280; }
.rec-input {
    width:140px; padding:8px 12px; border:2px solid #e5e7eb;
    border-radius:8px; font-size:14px; font-weight:700;
    text-align:right; transition:border-color .2s;
}
.rec-input:focus { outline:none; border-color:#1976D2; }
.discrepancy { margin-top:12px; padding:12px 16px; border-radius:10px; font-size:14px; font-weight:700; display:none; }
.disc-ok  { background:#d1fae5; color:#065f46; }
.disc-err { background:#fee2e2; color:#991b1b; }

/* ── Hourly chart bars ── */
.hour-chart { display:flex; align-items:flex-end; gap:4px; height:80px; padding:0 2px; }
.hour-bar-wrap { flex:1; display:flex; flex-direction:column; align-items:center; gap:3px; }
.hour-bar { width:100%; border-radius:4px 4px 0 0; background:#bfdbfe; min-height:2px; transition:height .3s; cursor:default; position:relative; }
.hour-bar.has-sales { background:#1976D2; }
.hour-bar:hover::after { content:attr(data-tip); position:absolute; bottom:calc(100% + 4px); left:50%; transform:translateX(-50%); background:#1a1a2e; color:white; font-size:10px; padding:3px 7px; border-radius:5px; white-space:nowrap; pointer-events:none; }
.hour-lbl { font-size:9px; color:#9ca3af; }

/* ── Top products ── */
.product-bar-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #f9fafb; }
.product-bar-row:last-child { border:none; }
.product-bar-name { font-size:12px; font-weight:700; color:#1a1a2e; width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.product-bar-track { flex:1; height:8px; background:#f3f4f6; border-radius:4px; overflow:hidden; }
.product-bar-fill  { height:100%; border-radius:4px; background:linear-gradient(90deg,#1976D2,#42a5f5); }
.product-bar-qty   { font-size:12px; font-weight:700; color:#1976D2; width:40px; text-align:right; }

/* ── Sales table ── */
table { width:100%; border-collapse:collapse; font-size:13px; }
th { padding:10px 12px; background:#f8fafc; color:#374151; font-weight:700; text-align:left; border-bottom:2px solid #e5e7eb; }
td { padding:10px 12px; border-bottom:1px solid #f3f4f6; color:#4b5563; }
tr:hover td { background:#fafafa; }
.badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:700; }
.badge-completed { background:#d1fae5; color:#065f46; }
.badge-refunded  { background:#fee2e2; color:#991b1b; }

/* ── No data ── */
.no-data { text-align:center; padding:40px 20px; color:#9ca3af; }
.no-data i { font-size:40px; display:block; margin-bottom:10px; }

/* ── Print styles ── */
@media print {
    .topbar, .date-bar, .no-print { display:none !important; }
    body { background:white; }
    .container { padding:10px; max-width:100%; }
    .stats { grid-template-columns:repeat(4,1fr); }
    .two-col { grid-template-columns:1fr 1fr; }
    .card, .reconcile-box, .day-banner { box-shadow:none; border:1px solid #e5e7eb; }
}
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar no-print">
    <i class="fas fa-cash-register fa-lg"></i>
    <h1>Cash Closing Report</h1>
    <div class="ml">
        <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
        <a href="pos_sales.php"><i class="fas fa-history"></i> Sales</a>
        <a href="pos_receiving.php"><i class="fas fa-truck-loading"></i> Receiving</a>
    <a href="pos_expiry.php"><i class="fas fa-calendar-times"></i> Expiry</a>
        <a href="pos_suppliers.php"><i class="fas fa-building"></i> Suppliers</a>
        <a href="test204.php?page=<?= urlencode($agent_name) ?>&page1=<?= $agent_id ?>"><i class="fas fa-arrow-left"></i> CRM</a>
    </div>
</div>

<div class="container">

<!-- Date selector -->
<form method="GET" class="date-bar no-print">
    <i class="fas fa-calendar-day" style="color:#1976D2;font-size:18px;"></i>
    <label>Closing Report for:</label>
    <input type="date" name="date" value="<?= $date ?>" max="<?= date('Y-m-d') ?>">
    <button type="submit" class="btn btn-blue"><i class="fas fa-sync-alt"></i> Load</button>
    <button type="button" class="btn btn-gray" onclick="window.location='pos_closing.php?date=<?= date('Y-m-d') ?>'">
        <i class="fas fa-calendar-check"></i> Today
    </button>
    <button type="button" class="btn btn-green" onclick="window.print()" style="margin-left:auto;">
        <i class="fas fa-print"></i> Print Report
    </button>
</form>

<!-- Day banner -->
<div class="day-banner">
    <div>
        <div class="day-title"><?= date('l, d F Y', strtotime($date)) ?></div>
        <div class="day-sub">
            Generated: <?= date('d M Y H:i') ?> &nbsp;·&nbsp; Cashier: <?= htmlspecialchars($agent_name) ?>
        </div>
    </div>
    <div class="day-meta">
        <div class="meta-item">
            <div class="meta-val"><?= $summary['total_sales'] ?? 0 ?></div>
            <div class="meta-lbl">Transactions</div>
        </div>
        <div class="meta-item">
            <div class="meta-val">LL <?= number_format(round(($summary['net_total'] ?? 0) * (1 + $vat_rate/100) * $usd_to_lbp), 0) ?></div>
            <div class="meta-lbl">Net Revenue</div>
        </div>
        <div class="meta-item">
            <div class="meta-val">LL <?= number_format(round(($summary['total_discounts'] ?? 0) * $usd_to_lbp), 0) ?></div>
            <div class="meta-lbl">Discounts</div>
        </div>
        <?php if (($summary['refunded_count'] ?? 0) > 0): ?>
        <div class="meta-item">
            <div class="meta-val" style="color:#fca5a5;"><?= $summary['refunded_count'] ?></div>
            <div class="meta-lbl">Refunds</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stats row -->
<div class="stats">
    <div class="stat-card green">
        <div class="s-val">LL <?= number_format(round(($summary['net_total'] ?? 0) * (1 + $vat_rate/100) * $usd_to_lbp), 0) ?></div>
        <div class="s-lbl">Net Revenue</div>
    </div>
    <div class="stat-card">
        <div class="s-val">LL <?= number_format(round(($summary['gross_total'] ?? 0) * (1 + $vat_rate/100) * $usd_to_lbp), 0) ?></div>
        <div class="s-lbl">Gross (before discount)</div>
    </div>
    <div class="stat-card orange">
        <div class="s-val">LL <?= number_format(round(($summary['total_discounts'] ?? 0) * $usd_to_lbp), 0) ?></div>
        <div class="s-lbl">Total Discounts Given</div>
    </div>
    <div class="stat-card red">
        <div class="s-val">LL <?= number_format(round(($summary['refunded_total'] ?? 0) * (1 + $vat_rate/100) * $usd_to_lbp), 0) ?></div>
        <div class="s-lbl">Refunded Amount</div>
    </div>
</div>

<!-- Cash Reconciliation -->
<div class="reconcile-box no-print">
    <h3><i class="fas fa-balance-scale" style="color:#1976D2;"></i> Cash Drawer Reconciliation</h3>
    <div class="rec-row">
        <span class="rec-lbl">Expected USD in drawer</span>
        <span class="rec-val" id="expectedCash" style="color:#1976D2;">
            $<?= number_format($expected_usd, 2) ?>
        </span>
    </div>
    <div class="rec-row">
        <span class="rec-lbl">Expected LBP in drawer</span>
        <span class="rec-val" style="color:#059669;">
            LL <?= number_format($expected_lbp, 0) ?>
        </span>
    </div>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:10px 0;">
    <div class="rec-row">
        <span class="rec-lbl">Actual USD counted</span>
        <div class="rec-input-wrap">
            <span>$</span>
            <input type="number" class="rec-input" id="actualCashUsd"
                placeholder="0.00" step="0.01" min="0"
                oninput="checkDiscrepancy()">
        </div>
    </div>
    <div class="rec-row">
        <span class="rec-lbl">Actual LBP counted</span>
        <div class="rec-input-wrap">
            <span>LL</span>
            <input type="number" class="rec-input" id="actualCashLbp"
                placeholder="0" step="1000" min="0"
                oninput="checkDiscrepancy()">
        </div>
    </div>
    <div class="discrepancy" id="discrepancyBox"></div>
</div>

<!-- Reconciliation print version (shows filled value) -->
<div class="reconcile-box" id="reconcilePrint" style="display:none;">
    <h3><i class="fas fa-balance-scale" style="color:#1976D2;"></i> Cash Drawer Reconciliation</h3>
    <div class="rec-row">
        <span class="rec-lbl">Expected cash in drawer</span>
        <span class="rec-val" id="printExpected"></span>
    </div>
    <div class="rec-row">
        <span class="rec-lbl">Actual cash counted</span>
        <span class="rec-val" id="printActual"></span>
    </div>
    <div class="rec-row">
        <span class="rec-lbl">Discrepancy</span>
        <span class="rec-val" id="printDiscrepancy"></span>
    </div>
    <div class="rec-row">
        <span class="rec-lbl">Cashier signature</span>
        <span class="rec-val" style="border-bottom:1px solid #ccc;min-width:200px;display:inline-block;">&nbsp;</span>
    </div>
</div>

<!-- Payment methods + Currency side by side -->
<div class="two-col">

    <!-- Payment methods -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-wallet"></i>
            <h3>By Payment Method</h3>
        </div>
        <div class="card-body">
            <?php if (empty($by_payment)): ?>
            <div class="no-data"><i class="fas fa-inbox"></i><p>No sales on this day</p></div>
            <?php else:
                foreach ($by_payment as $row):
                    $method = $row['payment_method'];
                    $col = $pay_colors[$method] ?? '#6b7280';
                    $ico = $pay_icons[$method]  ?? 'fa-money-bill';
                    $total_lbp = round($row['total'] * $usd_to_lbp);
                    $pct = ($summary['net_total'] > 0) ? round($row['total'] / $summary['net_total'] * 100) : 0;
            ?>
            <div class="pay-row">
                <div class="pay-icon" style="background:<?= $col ?>;">
                    <i class="fas <?= $ico ?>"></i>
                </div>
                <div class="pay-info">
                    <div class="pay-name"><?= $pay_labels[$method] ?? ucfirst($method) ?></div>
                    <div class="pay-meta"><?= $row['count'] ?> transaction<?= $row['count']!==1?'s':'' ?></div>
                </div>
                <div>
                    <div class="pay-total">LL <?= number_format($total_lbp, 0) ?></div>
                    <div style="font-size:10px;color:#9ca3af;text-align:right;"><?= $pct ?>%</div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>


    <!-- Hourly activity -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-chart-bar"></i>
            <h3>Hourly Activity</h3>
        </div>
        <div class="card-body">
            <?php
            $hours_map = [];
            foreach ($hourly as $h) $hours_map[(int)$h['hr']] = $h;
            $max_total = max(1, max(array_map(fn($h) => (float)$h['total'], $hourly ?: [['total'=>1]])));
            ?>
                <div class="hour-chart">
                    <?php for ($h = 0; $h < 24; $h++):
                        $hd = $hours_map[$h] ?? null;
                        $pct = $hd ? round((float)$hd['total'] / $max_total * 100) : 0;
                        $tip = $hd ? sprintf('%02d:00 — %d sale%s — LL %s', $h, $hd['cnt'], $hd['cnt']!=1?'s':'', number_format(round((float)$hd['total']*$usd_to_lbp),0)) : sprintf('%02d:00 — no sales', $h);
                    ?>
                    <div class="hour-bar-wrap">
                        <div class="hour-bar <?= $hd ? 'has-sales' : '' ?>"
                             style="height:<?= max(2,$pct) ?>%"
                             data-tip="<?= $tip ?>"></div>
                        <?php if ($h % 4 === 0): ?>
                        <div class="hour-lbl"><?= sprintf('%02d', $h) ?></div>
                        <?php else: ?>
                        <div class="hour-lbl"></div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Top Products + Sales Table -->
<div class="two-col">

    <!-- Top products -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-fire" style="color:#ef4444;"></i>
            <h3>Top Products Today</h3>
        </div>
        <div class="card-body">
            <?php if (empty($top_products)): ?>
            <div class="no-data"><i class="fas fa-box-open"></i><p>No products sold</p></div>
            <?php else:
                $max_qty = max(array_column($top_products,'qty_sold'));
                foreach ($top_products as $p):
                    $pct = round($p['qty_sold'] / $max_qty * 100);
            ?>
            <div class="product-bar-row">
                <div class="product-bar-name" title="<?= htmlspecialchars($p['product_name']) ?>">
                    <?= htmlspecialchars($p['product_name']) ?>
                </div>
                <div class="product-bar-track">
                    <div class="product-bar-fill" style="width:<?= $pct ?>%"></div>
                </div>
                <div class="product-bar-qty"><?= $p['qty_sold'] ?> <small style="color:#9ca3af;font-weight:400;">sold</small></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Transaction summary -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-receipt"></i>
            <h3>Transaction Summary</h3>
        </div>
        <div class="card-body">
            <div class="rec-row">
                <span class="rec-lbl">Completed sales</span>
                <span class="rec-val"><?= ($summary['total_sales'] ?? 0) - ($summary['refunded_count'] ?? 0) ?></span>
            </div>
            <div class="rec-row">
                <span class="rec-lbl">Refunded sales</span>
                <span class="rec-val" style="color:#ef4444;"><?= $summary['refunded_count'] ?? 0 ?></span>
            </div>
            <div class="rec-row">
                <span class="rec-lbl">Total transactions</span>
                <span class="rec-val"><?= $summary['total_sales'] ?? 0 ?></span>
            </div>
            <div class="rec-row">
                <span class="rec-lbl">Gross revenue</span>
                <span class="rec-val">LL <?= number_format(round(($summary['gross_total'] ?? 0) * (1 + $vat_rate/100) * $usd_to_lbp), 0) ?></span>
            </div>
            <div class="rec-row">
                <span class="rec-lbl">Discounts given</span>
                <span class="rec-val" style="color:#f59e0b;">-LL <?= number_format(round(($summary['total_discounts'] ?? 0) * $usd_to_lbp), 0) ?></span>
            </div>
            <div class="rec-row">
                <span class="rec-lbl">Refunded amount</span>
                <span class="rec-val" style="color:#ef4444;">-LL <?= number_format(round(($summary['refunded_total'] ?? 0) * (1 + $vat_rate/100) * $usd_to_lbp), 0) ?></span>
            </div>
            <div class="rec-row" style="border-top:2px solid #1a1a2e;margin-top:4px;padding-top:12px;">
                <span style="font-weight:800;font-size:15px;color:#1a1a2e;">NET REVENUE</span>
                <span style="font-weight:900;font-size:17px;color:#10b981;">LL <?= number_format(round(($summary['net_total'] ?? 0) * (1 + $vat_rate/100) * $usd_to_lbp), 0) ?></span>
            </div>
        </div>
    </div>

</div>

<!-- All transactions -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <i class="fas fa-list"></i>
        <h3>All Transactions — <?= date('d M Y', strtotime($date)) ?></h3>
        <span style="margin-left:auto;font-size:12px;color:#9ca3af;"><?= count($sales) ?> records</span>
    </div>
    <?php if (empty($sales)): ?>
    <div class="no-data"><i class="fas fa-inbox"></i><p>No transactions on this day</p></div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Sale #</th><th>Time</th><th>Customer</th>
                    <th>Items</th><th>Payment</th><th>Currency</th>
                    <th>Discount</th><th>Total</th><th>Status</th><th>Cashier</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sales as $s): ?>
            <tr>
                <td><strong>#<?= $s['id'] ?></strong></td>
                <td><?= date('H:i', strtotime($s['created_at'])) ?></td>
                <td><?= htmlspecialchars($s['client_name']) ?></td>
                <td><?= $s['item_count'] ?></td>
                <td><?= $pay_labels[$s['payment_method']] ?? $s['payment_method'] ?></td>
                <td><?= $s['currency'] ?></td>
                <td><?= $s['discount'] > 0 ? '-LL '.number_format(round($s['discount']*$usd_to_lbp),0) : '—' ?></td>
                <td><strong>LL <?= number_format(round(round((float)$s['final_total'] * (1 + $vat_rate/100) * $usd_to_lbp / 5000) * 5000), 0) ?></strong></td>
                <td>
                    <span class="badge badge-<?= $s['status'] ?>">
                        <?= ucfirst($s['status']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($s['agent_name']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

</div><!-- /container -->

<script>
var expectedUsd  = <?= $expected_usd ?>;
var expectedLbp  = <?= $expected_lbp ?>;
var usdToLbp     = <?= $usd_to_lbp ?>;

function checkDiscrepancy() {
    var actualUsd = parseFloat(document.getElementById('actualCashUsd').value) || 0;
    var actualLbp = parseFloat(document.getElementById('actualCashLbp').value) || 0;
    var box       = document.getElementById('discrepancyBox');

    // Convert everything to LBP for comparison
    var expectedTotalLbp = Math.round(expectedUsd * usdToLbp) + expectedLbp;
    var actualTotalLbp   = Math.round(actualUsd   * usdToLbp) + actualLbp;
    var diffLbp          = actualTotalLbp - expectedTotalLbp;

    box.style.display = 'block';

    if (Math.abs(diffLbp) < 1000) {
        box.className = 'discrepancy disc-ok';
        box.innerHTML = '<i class="fas fa-check-circle"></i> Drawer balances. No discrepancy.';
    } else if (diffLbp > 0) {
        box.className = 'discrepancy disc-err';
        box.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Overage: <strong>LL ' + Math.round(diffLbp).toLocaleString() + '</strong> — drawer has more than expected.';
    } else {
        box.className = 'discrepancy disc-err';
        box.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Shortage: <strong>LL ' + Math.round(Math.abs(diffLbp)).toLocaleString() + '</strong> — drawer has less than expected.';
    }
}

// Print handler
window.addEventListener('beforeprint', function() {
    var noprint = document.querySelector('.reconcile-box.no-print');
    if (noprint) noprint.style.display = 'none';
    var printBox = document.getElementById('reconcilePrint');
    if (!printBox) return;
    var actualUsd = parseFloat(document.getElementById('actualCashUsd').value) || 0;
    var actualLbp = parseFloat(document.getElementById('actualCashLbp').value) || 0;
    document.getElementById('printExpected').textContent =
        '$ ' + expectedUsd.toFixed(2) + ' + LL ' + Math.round(expectedLbp).toLocaleString();
    document.getElementById('printActual').textContent =
        '$ ' + actualUsd.toFixed(2) + ' + LL ' + Math.round(actualLbp).toLocaleString();
    var diffLbp = (Math.round(actualUsd * usdToLbp) + actualLbp) - (Math.round(expectedUsd * usdToLbp) + expectedLbp);
    document.getElementById('printDiscrepancy').textContent =
        Math.abs(diffLbp) < 1000 ? '✓ Balanced' : 'LL ' + (diffLbp > 0 ? '+' : '') + Math.round(diffLbp).toLocaleString();
    printBox.style.display = 'block';
});
window.addEventListener('afterprint', function() {
    var noprint = document.querySelector('.reconcile-box.no-print');
    if (noprint) noprint.style.display = 'block';
    var printBox = document.getElementById('reconcilePrint');
    if (printBox) printBox.style.display = 'none';
});
</script>
</body>
</html>
