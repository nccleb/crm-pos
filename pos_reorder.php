<?php
/**
 * pos_reorder.php
 * Reorder Suggestions + Supplier Performance
 * NCC CRM POS v4.4
 */
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$is_super   = ($_SESSION['oop'] === 'super');
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$conn = mysqli_connect("192.168.1.14","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

// ── Reorder suggestions ────────────────────────────────────────────────────
$reorder_res = mysqli_query($conn,"
    SELECT
        p.codep, p.nomp, p.category, p.unit,
        CAST(p.onhand              AS DECIMAL(10,3)) AS onhand,
        CAST(p.low_stock_threshold AS DECIMAL(10,3)) AS threshold,
        p.cost_price,
        GREATEST(0, p.low_stock_threshold - p.onhand)     AS shortfall,
        GREATEST(0, p.low_stock_threshold * 2 - p.onhand) AS suggested_qty,
        (SELECT s.name
            FROM stock_receivings sr
            JOIN pos_suppliers s  ON sr.supplier_id = s.id
            JOIN stock_receiving_items sri ON sri.receiving_id = sr.id
            WHERE sri.product_id = p.codep
            ORDER BY sr.received_date DESC LIMIT 1) AS last_supplier,
        (SELECT sr.supplier_id
            FROM stock_receivings sr
            JOIN stock_receiving_items sri ON sri.receiving_id = sr.id
            WHERE sri.product_id = p.codep
            ORDER BY sr.received_date DESC LIMIT 1) AS last_supplier_id,
        (SELECT sri.cost_price_lbp
            FROM stock_receiving_items sri
            JOIN stock_receivings sr ON sri.receiving_id = sr.id
            WHERE sri.product_id = p.codep
            ORDER BY sr.received_date DESC LIMIT 1) AS last_cost_lbp,
        (SELECT MAX(sr.received_date)
            FROM stock_receivings sr
            JOIN stock_receiving_items sri ON sri.receiving_id = sr.id
            WHERE sri.product_id = p.codep) AS last_received
    FROM produit p
    WHERE p.active = 1 AND p.low_stock_threshold > 0
      AND p.onhand <= p.low_stock_threshold
    ORDER BY (p.onhand / NULLIF(p.low_stock_threshold,0)) ASC
");
$reorder_items = [];
while ($r = mysqli_fetch_assoc($reorder_res)) $reorder_items[] = $r;

// Group by last supplier
$by_supplier = [];
foreach ($reorder_items as $item) {
    $sid   = $item['last_supplier_id'] ?? 0;
    $sname = $item['last_supplier']    ?? 'No Previous Supplier';
    if (!isset($by_supplier[$sid])) {
        $by_supplier[$sid] = ['name' => $sname, 'id' => $sid, 'items' => []];
    }
    $by_supplier[$sid]['items'][] = $item;
}

// ── Supplier performance ───────────────────────────────────────────────────
$perf_res = mysqli_query($conn,"
    SELECT
        s.id, s.name, s.contact_person, s.phone,
        COUNT(DISTINCT sr.id)           AS delivery_count,
        COALESCE(SUM(sri.qty_received), 0) AS total_qty,
        COALESCE(SUM(sri.subtotal_lbp), 0) AS total_value_lbp,
        COALESCE(AVG(sri.cost_price_lbp / NULLIF(sri.qty_received,0)), 0) AS avg_unit_cost,
        COUNT(DISTINCT sri.product_id)  AS product_variety,
        MAX(sr.received_date)           AS last_delivery,
        MIN(sr.received_date)           AS first_delivery
    FROM pos_suppliers s
    LEFT JOIN stock_receivings sr  ON sr.supplier_id = s.id
    LEFT JOIN stock_receiving_items sri ON sri.receiving_id = sr.id
    WHERE s.active = 1
    GROUP BY s.id, s.name, s.contact_person, s.phone
    ORDER BY total_value_lbp DESC
");
$suppliers = [];
while ($r = mysqli_fetch_assoc($perf_res)) $suppliers[] = $r;

foreach ($suppliers as &$s) {
    $sid = (int)$s['id'];
    $r30 = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS c FROM stock_receivings
         WHERE supplier_id=$sid AND received_date >= DATE_SUB(CURDATE(),INTERVAL 30 DAY)"));
    $s['recent_30d'] = (int)$r30['c'];
}
unset($s);

$max_value      = empty($suppliers) ? 1 : max(1, ...array_column($suppliers,'total_value_lbp'));
$max_deliveries = empty($suppliers) ? 1 : max(1, ...array_column($suppliers,'delivery_count'));

$out_count = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM produit WHERE active=1 AND onhand=0 AND low_stock_threshold>0"))['c'];
$low_count = count($reorder_items);
$total_shortfall_value = (int)array_sum(array_map(
    fn($i) => $i['shortfall'] * ($i['last_cost_lbp'] ?: $i['cost_price']),
    $reorder_items
));
$active_sup = count(array_filter($suppliers, fn($s) => $s['delivery_count'] > 0));

function urgency(float $on, float $th): string {
    if ($on == 0)              return 'critical';
    $r = $on / max(1,$th);
    if ($r <= 0.25)            return 'high';
    if ($r <= 0.5)             return 'medium';
    return 'low';
}
function urgency_label(string $u): string {
    return match($u) {
        'critical' => 'Out of Stock',
        'high'     => 'Critical',
        'medium'   => 'Low',
        default    => 'Below Min',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Reorder &amp; Supplier Performance — POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f0f2f5;font-family:'Segoe UI',sans-serif;font-size:14px;color:#1a1a2e;}
.topbar{background:linear-gradient(135deg,#1976D2,#0D47A1);color:white;padding:14px 24px;display:flex;align-items:center;gap:15px;flex-wrap:wrap;}
.topbar h1{font-size:18px;font-weight:800;}
.topbar a{color:rgba(255,255,255,.85);text-decoration:none;font-size:13px;font-weight:600;background:rgba(255,255,255,.15);padding:7px 14px;border-radius:8px;display:flex;align-items:center;gap:6px;transition:background .2s;}
.topbar a:hover{background:rgba(255,255,255,.25);}
.topbar .ml{margin-left:auto;font-size:13px;opacity:.8;}
.wrap{max-width:1280px;margin:24px auto;padding:0 20px;}
.stats-row{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
.stat-card{background:white;border-radius:12px;padding:16px 20px;box-shadow:0 2px 8px rgba(0,0,0,.06);flex:1;min-width:140px;border-top:4px solid #e5e7eb;}
.stat-card.red{border-top-color:#ef4444;} .stat-card.orange{border-top-color:#f59e0b;}
.stat-card.blue{border-top-color:#1976D2;} .stat-card.green{border-top-color:#10b981;}
.stat-card .val{font-size:22px;font-weight:800;}
.stat-card.red .val{color:#ef4444;} .stat-card.orange .val{color:#f59e0b;}
.stat-card.blue .val{color:#1976D2;} .stat-card.green .val{color:#10b981;}
.stat-card .lbl{font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;margin-top:3px;}
.tabs{display:flex;gap:4px;margin-bottom:20px;background:white;padding:6px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);width:fit-content;}
.tab{padding:9px 22px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:none;background:transparent;color:#6b7280;display:flex;align-items:center;gap:8px;transition:all .2s;}
.tab.active{background:#1976D2;color:white;}
.tab-pane{display:none;} .tab-pane.active{display:block;}
.card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:20px;}
.card-header{padding:16px 20px;border-bottom:2px solid #f0f2f5;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
.card-header h2{font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;}
.supplier-group{margin-bottom:20px;}
.supplier-group-header{background:linear-gradient(135deg,#f8fafc,#eff6ff);border:1px solid #dbeafe;border-radius:12px 12px 0 0;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
.supplier-group-header .sname{font-weight:800;font-size:15px;color:#1e3a5f;display:flex;align-items:center;gap:8px;}
.supplier-group-body{border:1px solid #dbeafe;border-top:none;border-radius:0 0 12px 12px;overflow:hidden;}
table{width:100%;border-collapse:collapse;}
th{background:#f8fafc;padding:11px 14px;text-align:left;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #f0f2f5;}
td{padding:11px 14px;border-bottom:1px solid #f8fafc;vertical-align:middle;}
tr:last-child td{border-bottom:none;} tr:hover td{background:#fafbfc;}
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-critical{background:#fee2e2;color:#dc2626;} .badge-high{background:#fed7aa;color:#c2410c;}
.badge-medium{background:#fef9c3;color:#a16207;} .badge-low{background:#dcfce7;color:#16a34a;}
.stock-bar-wrap{display:flex;align-items:center;gap:8px;min-width:110px;}
.stock-bar{flex:1;height:6px;background:#f0f2f5;border-radius:3px;overflow:hidden;}
.stock-bar-fill{height:100%;border-radius:3px;}
.fill-critical{background:#ef4444;} .fill-high{background:#f97316;}
.fill-medium{background:#eab308;} .fill-low{background:#22c55e;}
.stock-pct{font-size:11px;color:#9ca3af;font-weight:600;white-space:nowrap;}
.qty-input{width:70px;padding:5px 8px;border:2px solid #e5e7eb;border-radius:6px;font-size:13px;font-weight:700;text-align:center;}
.qty-input:focus{border-color:#1976D2;outline:none;}
.btn{padding:7px 16px;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:all .2s;}
.btn-primary{background:#1976D2;color:white;} .btn-primary:hover{background:#1565C0;}
.btn-success{background:#10b981;color:white;} .btn-success:hover{background:#059669;}
.btn-outline{background:white;color:#1976D2;border:2px solid #1976D2;} .btn-outline:hover{background:#eff6ff;}
.btn-print{background:#6366f1;color:white;} .btn-print:hover{background:#4f46e5;}
.btn-sm{padding:5px 12px;font-size:11px;}
.perf-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;padding:20px;}
.perf-card{background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:20px;transition:all .2s;position:relative;}
.perf-card:hover{border-color:#93c5fd;box-shadow:0 4px 16px rgba(25,118,210,.1);}
.perf-rank{position:absolute;top:16px;right:16px;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:900;}
.perf-name{font-size:15px;font-weight:800;color:#1e3a5f;margin-bottom:4px;padding-right:40px;}
.perf-contact{font-size:12px;color:#6b7280;margin-bottom:14px;}
.perf-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;}
.perf-stat{background:white;border-radius:8px;padding:10px 12px;border:1px solid #e5e7eb;}
.perf-stat .ps-val{font-size:15px;font-weight:800;color:#1976D2;}
.perf-stat .ps-lbl{font-size:10px;color:#9ca3af;font-weight:600;text-transform:uppercase;margin-top:2px;}
.bar-wrap{margin-bottom:8px;}
.bar-label{display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px;font-weight:600;color:#6b7280;}
.bar-track{height:7px;background:#e5e7eb;border-radius:4px;overflow:hidden;}
.bar-fill-blue{background:linear-gradient(90deg,#1976D2,#42a5f5);height:100%;border-radius:4px;}
.bar-fill-green{background:linear-gradient(90deg,#10b981,#34d399);height:100%;border-radius:4px;}
.perf-badges{display:flex;flex-wrap:wrap;gap:6px;margin-top:12px;}
.perf-badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#eff6ff;color:#1976D2;}
.perf-badge.green{background:#dcfce7;color:#16a34a;} .perf-badge.amber{background:#fef9c3;color:#a16207;} .perf-badge.red{background:#fee2e2;color:#dc2626;}
.empty-state{text-align:center;padding:60px 20px;color:#9ca3af;}
.empty-state i{font-size:52px;margin-bottom:16px;display:block;opacity:.4;}
.totals-row td{background:#f0f7ff;font-weight:700;color:#1e3a5f;border-top:2px solid #bfdbfe;}
@media print{.topbar,.tabs,.btn,.no-print{display:none!important;}body{background:white;}.wrap{margin:0;padding:0;}}
</style>
</head>
<body>

<div class="topbar">
    <h1><i class="fas fa-truck-loading"></i> Reorder &amp; Suppliers</h1>
    <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="pos_receiving.php"><i class="fas fa-boxes"></i> Receiving</a>
    <a href="pos_suppliers.php"><i class="fas fa-building"></i> Suppliers</a>
    <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
    <span class="ml"><i class="fas fa-user"></i> <?= htmlspecialchars($agent_name) ?></span>
</div>

<div class="wrap">

    <div class="stats-row">
        <div class="stat-card red">
            <div class="val"><?= $out_count ?></div>
            <div class="lbl">Out of Stock</div>
        </div>
        <div class="stat-card orange">
            <div class="val"><?= $low_count ?></div>
            <div class="lbl">Need Reorder</div>
        </div>
        <div class="stat-card blue">
            <div class="val">LL <?= number_format($total_shortfall_value) ?></div>
            <div class="lbl">Est. Order Value</div>
        </div>
        <div class="stat-card green">
            <div class="val"><?= $active_sup ?></div>
            <div class="lbl">Active Suppliers</div>
        </div>
    </div>

    <div class="tabs">
        <button class="tab active" onclick="switchTab('reorder',this)">
            <i class="fas fa-exclamation-triangle"></i> Reorder Suggestions
            <?php if ($low_count > 0): ?>
            <span style="background:#ef4444;color:white;border-radius:20px;padding:1px 8px;font-size:11px;"><?= $low_count ?></span>
            <?php endif; ?>
        </button>
        <button class="tab" onclick="switchTab('perf',this)">
            <i class="fas fa-chart-bar"></i> Supplier Performance
        </button>
    </div>

    <!-- TAB 1: REORDER -->
    <div id="tab-reorder" class="tab-pane active">

        <?php if (empty($reorder_items)): ?>
        <div class="card">
            <div class="empty-state">
                <i class="fas fa-check-circle" style="color:#10b981;opacity:1;font-size:52px;"></i>
                <p style="color:#10b981;font-size:15px;font-weight:700;margin-top:12px;">All products are above minimum stock levels.</p>
                <p style="margin-top:8px;font-size:13px;">Set thresholds per product in the Products page to enable alerts.</p>
            </div>
        </div>
        <?php else: ?>

        <div class="card no-print" style="padding:14px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span style="font-size:13px;color:#6b7280;font-weight:600;">
                <i class="fas fa-info-circle" style="color:#1976D2;"></i>
                &nbsp;<?= $low_count ?> products below minimum — grouped by last supplier.
                Suggested qty = 2× threshold. Adjust before creating receiving.
            </span>
            <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                <button class="btn btn-success" onclick="exportCSV()"><i class="fas fa-file-csv"></i> Export CSV</button>
            </div>
        </div>

        <?php foreach ($by_supplier as $sid => $group):
            $group_est = array_sum(array_map(
                fn($i) => $i['suggested_qty'] * ($i['last_cost_lbp'] ?: $i['cost_price']),
                $group['items']
            ));
        ?>
        <div class="supplier-group">
            <div class="supplier-group-header">
                <div class="sname">
                    <i class="fas fa-building" style="color:#1976D2;"></i>
                    <?= htmlspecialchars($group['name']) ?>
                    <span style="font-size:12px;color:#6b7280;font-weight:600;">(<?= count($group['items']) ?> product<?= count($group['items'])!=1?'s':'' ?>)</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <span style="font-size:13px;font-weight:700;color:#1e3a5f;">Est. LL <?= number_format($group_est) ?></span>
                    <?php if ($sid): ?>
                    <a href="pos_receiving.php?supplier_id=<?= (int)$sid ?>" class="btn btn-primary btn-sm no-print">
                        <i class="fas fa-plus"></i> Create Receiving
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="supplier-group-body">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Stock Level</th>
                            <th>On Hand</th>
                            <th>Min Threshold</th>
                            <th>Shortfall</th>
                            <th>Suggested Qty</th>
                            <th>Last Cost (LL)</th>
                            <th>Est. Cost</th>
                            <th>Urgency</th>
                            <th>Last Received</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $grp_total = 0; foreach ($group['items'] as $item):
                        $u    = urgency((float)$item['onhand'], (float)$item['threshold']);
                        $pct  = $item['threshold'] > 0 ? min(100, round($item['onhand'] / $item['threshold'] * 100)) : 0;
                        $cost = (float)($item['last_cost_lbp'] ?: $item['cost_price']);
                        $est  = (float)$item['suggested_qty'] * $cost;
                        $grp_total += $est;
                    ?>
                    <tr>
                        <td style="font-weight:700;color:#1e3a5f;"><?= htmlspecialchars($item['nomp']) ?></td>
                        <td><span style="font-size:11px;background:#f3f4f6;color:#6b7280;padding:3px 8px;border-radius:20px;font-weight:600;"><?= htmlspecialchars($item['category'] ?: '—') ?></span></td>
                        <td>
                            <div class="stock-bar-wrap">
                                <div class="stock-bar"><div class="stock-bar-fill fill-<?= $u ?>" style="width:<?= $pct ?>%;"></div></div>
                                <span class="stock-pct"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <td style="font-weight:700;"><?= number_format((float)$item['onhand'],1) ?> <?= htmlspecialchars($item['unit']) ?></td>
                        <td style="color:#6b7280;"><?= number_format((float)$item['threshold'],1) ?></td>
                        <td style="color:#ef4444;font-weight:700;"><?= number_format((float)$item['shortfall'],1) ?></td>
                        <td>
                            <input type="number" class="qty-input suggested-qty"
                                   value="<?= (int)$item['suggested_qty'] ?>"
                                   data-cost="<?= $cost ?>"
                                   min="0" step="1">
                        </td>
                        <td><?= $cost ? 'LL '.number_format($cost) : '<span style="color:#9ca3af">—</span>' ?></td>
                        <td style="font-weight:700;color:#1976D2;" class="est-cell">
                            <?= $est ? 'LL '.number_format($est) : '—' ?>
                        </td>
                        <td><span class="badge badge-<?= $u ?>"><?= urgency_label($u) ?></span></td>
                        <td style="color:#9ca3af;font-size:12px;"><?= $item['last_received'] ? date('d M Y',strtotime($item['last_received'])) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="totals-row">
                            <td colspan="8" style="text-align:right;padding-right:20px;">Group Estimate:</td>
                            <td colspan="3">LL <?= number_format($grp_total) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- TAB 2: SUPPLIER PERFORMANCE -->
    <div id="tab-perf" class="tab-pane">

        <?php if (empty($suppliers)): ?>
        <div class="card"><div class="empty-state"><i class="fas fa-building"></i><p>No supplier data yet.</p></div></div>
        <?php else: ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-trophy" style="color:#f59e0b;"></i> Supplier Rankings — by Total Purchase Value</h2>
                <span style="font-size:12px;color:#9ca3af;"><?= count($suppliers) ?> active suppliers</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Supplier</th>
                        <th>Deliveries</th>
                        <th>Products</th>
                        <th>Total Qty</th>
                        <th>Total Value (LL)</th>
                        <th>Value Share</th>
                        <th>Avg Unit Cost</th>
                        <th>Last Delivery</th>
                        <th>Last 30 Days</th>
                        <th class="no-print"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($suppliers as $i => $s):
                    $rank    = $i + 1;
                    $vp      = $max_value > 0      ? round($s['total_value_lbp'] / $max_value * 100)      : 0;
                    $dp      = $max_deliveries > 0 ? round($s['delivery_count']  / $max_deliveries * 100) : 0;
                    $ds      = $s['last_delivery'] ? (int)((time()-strtotime($s['last_delivery']))/86400) : null;
                    $ds_col  = !$ds ? '#9ca3af' : ($ds<=30?'#10b981':($ds<=60?'#f59e0b':'#ef4444'));
                ?>
                <tr>
                    <td>
                        <div style="width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:13px;
                             background:<?= match($rank){1=>'#fef9c3',2=>'#f1f5f9',3=>'#fed7aa',default=>'#f0f2f5'} ?>;
                             color:<?= match($rank){1=>'#a16207',2=>'#475569',3=>'#c2410c',default=>'#9ca3af'} ?>;">
                            <?= match($rank){1=>'🥇',2=>'🥈',3=>'🥉',default=>$rank} ?>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:700;color:#1e3a5f;"><?= htmlspecialchars($s['name']) ?></div>
                        <?php if ($s['contact_person']): ?><div style="font-size:11px;color:#9ca3af;"><?= htmlspecialchars($s['contact_person']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:700;"><?= number_format($s['delivery_count']) ?></div>
                        <div class="bar-track" style="width:80px;margin-top:4px;"><div class="bar-fill-blue" style="width:<?= $dp ?>%;"></div></div>
                    </td>
                    <td style="font-weight:700;"><?= number_format($s['product_variety']) ?></td>
                    <td><?= number_format((float)$s['total_qty'],1) ?></td>
                    <td style="font-weight:700;color:#1976D2;">LL <?= number_format($s['total_value_lbp']) ?></td>
                    <td>
                        <div class="bar-track" style="width:100px;"><div class="bar-fill-green" style="width:<?= $vp ?>%;"></div></div>
                        <div style="font-size:11px;color:#6b7280;margin-top:3px;"><?= $vp ?>%</div>
                    </td>
                    <td><?= $s['avg_unit_cost'] ? 'LL '.number_format($s['avg_unit_cost']) : '—' ?></td>
                    <td>
                        <?php if ($s['last_delivery']): ?>
                        <div style="font-size:12px;"><?= date('d M Y',strtotime($s['last_delivery'])) ?></div>
                        <div style="font-size:11px;color:<?= $ds_col ?>;font-weight:600;"><?= $ds ?> days ago</div>
                        <?php else: ?><span style="color:#9ca3af;font-size:12px;">No deliveries</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['recent_30d'] > 0): ?>
                        <span class="badge badge-low"><?= $s['recent_30d'] ?>× delivery</span>
                        <?php else: ?>
                        <span class="badge" style="background:#f3f4f6;color:#9ca3af;">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="no-print">
                        <a href="pos_receiving.php?supplier_id=<?= (int)$s['id'] ?>" class="btn btn-outline btn-sm">
                            <i class="fas fa-plus"></i> Receiving
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Detail cards -->
        <div style="background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
            <div class="card-header">
                <h2><i class="fas fa-id-card" style="color:#1976D2;"></i> Supplier Detail Cards</h2>
            </div>
            <div class="perf-grid">
            <?php foreach ($suppliers as $i => $s):
                $rank   = $i + 1;
                $vp     = $max_value > 0      ? round($s['total_value_lbp'] / $max_value * 100)      : 0;
                $dp     = $max_deliveries > 0 ? round($s['delivery_count']  / $max_deliveries * 100) : 0;
                $ds     = $s['last_delivery'] ? (int)((time()-strtotime($s['last_delivery']))/86400) : null;
                $fc     = !$ds ? 'red' : ($ds<=30?'green':($ds<=60?'amber':'red'));
                $fl     = !$ds ? 'No deliveries' : ($ds<=30?'Active':($ds<=60?'Slow':'Inactive'));
                $em     = explode(' ',$s['name'])[0];
            ?>
            <div class="perf-card">
                <div class="perf-rank" style="background:<?= match($rank){1=>'#fef9c3',2=>'#f1f5f9',3=>'#fed7aa',default=>'#f0f2f5'} ?>;color:<?= match($rank){1=>'#a16207',2=>'#475569',3=>'#c2410c',default=>'#9ca3af'} ?>;">
                    <?= match($rank){1=>'🥇',2=>'🥈',3=>'🥉',default=>'#'.$rank} ?>
                </div>
                <div class="perf-name"><?= htmlspecialchars($s['name']) ?></div>
                <div class="perf-contact">
                    <?= $s['contact_person'] ? '<i class="fas fa-user fa-xs"></i> '.htmlspecialchars($s['contact_person']) : '' ?>
                    <?= ($s['contact_person'] && $s['phone']) ? ' &nbsp;·&nbsp; ' : '' ?>
                    <?= $s['phone'] ? '<i class="fas fa-phone fa-xs"></i> '.htmlspecialchars($s['phone']) : '' ?>
                    &nbsp;
                </div>
                <div class="perf-stats">
                    <div class="perf-stat">
                        <div class="ps-val"><?= number_format($s['delivery_count']) ?></div>
                        <div class="ps-lbl">Deliveries</div>
                    </div>
                    <div class="perf-stat">
                        <div class="ps-val"><?= number_format($s['product_variety']) ?></div>
                        <div class="ps-lbl">Products</div>
                    </div>
                    <div class="perf-stat" style="grid-column:span 2;">
                        <div class="ps-val" style="font-size:13px;">LL <?= number_format($s['total_value_lbp']) ?></div>
                        <div class="ps-lbl">Total Purchased</div>
                    </div>
                    <div class="perf-stat" style="grid-column:span 2;">
                        <div class="ps-val" style="font-size:13px;"><?= $s['avg_unit_cost'] ? 'LL '.number_format($s['avg_unit_cost']) : '—' ?></div>
                        <div class="ps-lbl">Avg Unit Cost</div>
                    </div>
                </div>
                <div class="bar-wrap">
                    <div class="bar-label"><span>Purchase Value Share</span><span><?= $vp ?>%</span></div>
                    <div class="bar-track"><div class="bar-fill-blue" style="width:<?= $vp ?>%;"></div></div>
                </div>
                <div class="bar-wrap">
                    <div class="bar-label"><span>Delivery Volume Share</span><span><?= $dp ?>%</span></div>
                    <div class="bar-track"><div class="bar-fill-green" style="width:<?= $dp ?>%;"></div></div>
                </div>
                <div class="perf-badges">
                    <span class="perf-badge <?= $fc ?>"><?= $fl ?></span>
                    <?php if ($s['recent_30d'] > 0): ?><span class="perf-badge green"><?= $s['recent_30d'] ?>× last 30d</span><?php endif; ?>
                    <?php if ($s['last_delivery']): ?><span class="perf-badge">Last: <?= date('d M Y',strtotime($s['last_delivery'])) ?></span><?php endif; ?>
                    <?php if ($s['first_delivery']): ?><span class="perf-badge">Since: <?= date('M Y',strtotime($s['first_delivery'])) ?></span><?php endif; ?>
                </div>
                <div style="margin-top:14px;" class="no-print">
                    <a href="pos_receiving.php?supplier_id=<?= (int)$s['id'] ?>" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">
                        <i class="fas fa-plus"></i> New Receiving from <?= htmlspecialchars($em) ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

document.querySelectorAll('.suggested-qty').forEach(input => {
    input.addEventListener('input', function() {
        const cost = parseFloat(this.dataset.cost) || 0;
        const qty  = parseFloat(this.value) || 0;
        const est  = cost * qty;
        const cell = this.closest('tr').querySelector('.est-cell');
        cell.textContent = est > 0 ? 'LL ' + Math.round(est).toLocaleString('en') : '—';
    });
});

function exportCSV() {
    const rows = [['Product','Category','On Hand','Unit','Threshold','Shortfall','Suggested Qty','Last Cost (LL)','Est. Cost (LL)','Urgency','Last Supplier','Last Received']];
    document.querySelectorAll('.supplier-group').forEach(group => {
        const sup = group.querySelector('.sname').childNodes[2]?.textContent?.trim() || '';
        group.querySelectorAll('tbody tr').forEach(tr => {
            const tds = tr.querySelectorAll('td');
            if (!tds.length) return;
            const qtyEl = tr.querySelector('.suggested-qty');
            const qty  = qtyEl ? parseFloat(qtyEl.value) || 0 : 0;
            const cost = qtyEl ? parseFloat(qtyEl.dataset.cost) || 0 : 0;
            rows.push([
                tds[0].textContent.trim(),
                tds[1].textContent.trim(),
                tds[3].textContent.trim(),
                '',
                tds[4].textContent.trim(),
                tds[5].textContent.trim(),
                qty,
                cost ? 'LL '+Math.round(cost).toLocaleString() : '',
                cost*qty ? 'LL '+Math.round(cost*qty).toLocaleString() : '',
                tds[9].textContent.trim(),
                sup,
                tds[10].textContent.trim()
            ]);
        });
    });
    const csv = rows.map(r => r.map(c => '"'+String(c).replace(/"/g,'""')+'"').join(',')).join('\n');
    const blob = new Blob(['\uFEFF'+csv], {type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'reorder_list_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}
</script>
</body>
</html>
