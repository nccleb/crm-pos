<?php
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$conn = mysqli_connect("172.18.208.1","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

// Filters
$from   = mysqli_real_escape_string($conn, $_GET['from'] ?? date('Y-m-d'));
$to     = mysqli_real_escape_string($conn, $_GET['to'] ?? date('Y-m-d'));
$type   = mysqli_real_escape_string($conn, $_GET['type'] ?? '');
$search = mysqli_real_escape_string($conn, $_GET['s'] ?? '');

$where = "WHERE DATE(m.created_at) BETWEEN '$from' AND '$to'";
if ($type)   $where .= " AND m.type = '$type'";
if ($search) $where .= " AND m.product_name LIKE '%$search%'";

$movements = mysqli_query($conn,
    "SELECT m.* FROM stock_movements m $where ORDER BY m.created_at DESC"
);

// Stats
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
     SUM(CASE WHEN type='sale' THEN ABS(qty_change) ELSE 0 END) as sold_qty,
     SUM(CASE WHEN type='restock' THEN qty_change ELSE 0 END) as restocked_qty,
     SUM(CASE WHEN type='adjustment' THEN qty_change ELSE 0 END) as adjusted_qty,
     SUM(CASE WHEN type='return' THEN qty_change ELSE 0 END) as returned_qty,
     COUNT(*) as total_movements
     FROM stock_movements m $where"
));

// Products for restock form
$products = mysqli_query($conn, "SELECT codep, nomp, onhand FROM produit WHERE active=1 ORDER BY nomp");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Stock Movements — POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.topbar { background:linear-gradient(135deg,#1976D2,#0D47A1); color:white; padding:14px 24px; display:flex; align-items:center; gap:15px; }
.topbar h1 { font-size:18px; font-weight:700; }
.topbar a { color:white; text-decoration:none; background:rgba(255,255,255,.15); padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; }
.topbar a:hover { background:rgba(255,255,255,.25); }
.topbar a + a { margin-left:8px; }
.topbar .ml { margin-left:auto; }
.container { max-width:1200px; margin:0 auto; padding:24px 20px; }

.stats { display:grid; grid-template-columns:repeat(5,1fr); gap:15px; margin-bottom:24px; }
@media(max-width:800px){ .stats { grid-template-columns:repeat(2,1fr); } }
.stat { background:white; border-radius:10px; padding:18px; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,.06); border-left:4px solid #1976D2; }
.stat.red    { border-color:#ef4444; }
.stat.green  { border-color:#10b981; }
.stat.orange { border-color:#f59e0b; }
.stat.purple { border-color:#8b5cf6; }
.stat .val { font-size:26px; font-weight:800; color:#1976D2; }
.stat.red .val    { color:#ef4444; }
.stat.green .val  { color:#10b981; }
.stat.orange .val { color:#f59e0b; }
.stat.purple .val { color:#8b5cf6; }
.stat .lbl { font-size:12px; color:#6b7280; margin-top:4px; text-transform:uppercase; font-weight:600; }

.grid { display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start; }
@media(max-width:900px){ .grid { grid-template-columns:1fr; } }

.card { background:white; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.08); overflow:hidden; }
.card-header { padding:16px 22px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:10px; font-size:15px; font-weight:700; color:#1a1a2e; flex-wrap:wrap; }
.card-header i { color:#1976D2; }
.filters { display:flex; gap:8px; flex-wrap:wrap; padding:14px 22px; border-bottom:1px solid #e5e7eb; background:#fafbfc; }
.filters input, .filters select { padding:8px 12px; border:2px solid #e5e7eb; border-radius:8px; font-size:13px; }
.filters input:focus, .filters select:focus { outline:none; border-color:#1976D2; }
.btn { padding:9px 18px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
.btn-blue  { background:#1976D2; color:white; }
.btn-blue:hover { background:#0D47A1; }
.btn-green { background:#10b981; color:white; }
.btn-green:hover { background:#059669; }

table { width:100%; border-collapse:collapse; font-size:13px; }
th { padding:11px 14px; background:#f8fafc; color:#374151; font-weight:700; text-align:left; border-bottom:2px solid #e5e7eb; }
td { padding:11px 14px; border-bottom:1px solid #f3f4f6; color:#4b5563; vertical-align:middle; }
tr:hover td { background:#fafafa; }

.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.type-sale       { background:#fee2e2; color:#991b1b; }
.type-restock    { background:#d1fae5; color:#065f46; }
.type-adjustment { background:#fef3c7; color:#92400e; }
.type-return     { background:#ede9fe; color:#5b21b6; }

.qty-change { font-weight:800; font-size:14px; }
.qty-neg { color:#ef4444; }
.qty-pos { color:#10b981; }

/* Adjust stock form */
.adjust-card .card-body { padding:20px; }
.form-group { margin-bottom:14px; }
.form-group label { display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px; text-transform:uppercase; }
.form-group input, .form-group select, .form-group textarea {
    width:100%; padding:10px 13px; border:2px solid #e5e7eb;
    border-radius:8px; font-size:13px; transition:border-color .2s; font-family:inherit;
}
.form-group input:focus, .form-group select:focus { outline:none; border-color:#1976D2; }
.alert { padding:12px 16px; border-radius:8px; margin-bottom:15px; font-size:13px; font-weight:600; }
.alert-success { background:#d1fae5; color:#065f46; border-left:4px solid #10b981; }
.alert-error   { background:#fee2e2; color:#991b1b; border-left:4px solid #ef4444; }
</style>
</head>
<body>

<div class="topbar">
    <i class="fas fa-boxes fa-lg"></i>
    <h1>Stock Movements</h1>
    <a class="ml" href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
    <a href="pos_sales.php"><i class="fas fa-history"></i> Sales</a>
    <a href="pos_receiving.php"><i class="fas fa-truck-loading"></i> Receiving</a>
    <a href="pos_expiry.php"><i class="fas fa-calendar-times"></i> Expiry</a>
    <a href="pos_promotions.php"><i class="fas fa-tags"></i> Promotions</a>
    <a href="pos_suppliers.php"><i class="fas fa-building"></i> Suppliers</a>
    <a href="pos_archive.php"><i class="fas fa-archive"></i> Archive</a>
    <a href="test204.php?page=<?= urlencode($agent_name) ?>&page1=<?= $agent_id ?>"><i class="fas fa-arrow-left"></i> CRM</a>
</div>

<div class="container">

<!-- Stats -->
<div class="stats">
    <div class="stat"><div class="val"><?= $stats['total_movements'] ?></div><div class="lbl">Total Movements</div></div>
    <div class="stat red"><div class="val"><?= $stats['sold_qty'] ?? 0 ?></div><div class="lbl">Units Sold</div></div>
    <div class="stat green"><div class="val"><?= $stats['restocked_qty'] ?? 0 ?></div><div class="lbl">Units Restocked</div></div>
    <div class="stat orange"><div class="val"><?= $stats['adjusted_qty'] ?? 0 ?></div><div class="lbl">Adjusted</div></div>
    <div class="stat purple"><div class="val"><?= $stats['returned_qty'] ?? 0 ?></div><div class="lbl">Returned</div></div>
</div>

<div class="grid">

    <!-- Movements Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-exchange-alt"></i> Movement Log
        </div>
        <form method="GET" class="filters">
            <input type="date" name="from" value="<?= $from ?>">
            <input type="date" name="to"   value="<?= $to ?>">
            <select name="type">
                <option value="">All Types</option>
                <option value="sale"       <?= $type==='sale'?'selected':'' ?>>Sale</option>
                <option value="restock"    <?= $type==='restock'?'selected':'' ?>>Restock</option>
                <option value="adjustment" <?= $type==='adjustment'?'selected':'' ?>>Adjustment</option>
                <option value="return"     <?= $type==='return'?'selected':'' ?>>Return</option>
            </select>
            <input type="text" name="s" value="<?= htmlspecialchars($_GET['s'] ?? '') ?>" placeholder="Search product...">
            <button type="submit" class="btn btn-blue"><i class="fas fa-search"></i></button>
        </form>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Date</th><th>Product</th><th>Type</th>
                        <th>Before</th><th>Change</th><th>After</th>
                        <th>Note</th><th>Agent</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($movements) === 0): ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;">No movements found for this period.</td></tr>
                <?php else: while ($m = mysqli_fetch_assoc($movements)): ?>
                <tr>
                    <td style="white-space:nowrap;"><?= date('d M H:i', strtotime($m['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($m['product_name']) ?></strong></td>
                    <td><span class="badge type-<?= $m['type'] ?>"><?= ucfirst($m['type']) ?></span></td>
                    <td><?= $m['qty_before'] ?></td>
                    <td>
                        <span class="qty-change <?= $m['qty_change'] < 0 ? 'qty-neg' : 'qty-pos' ?>">
                            <?= $m['qty_change'] > 0 ? '+' : '' ?><?= $m['qty_change'] ?>
                        </span>
                    </td>
                    <td><strong><?= $m['qty_after'] ?></strong></td>
                    <td style="font-size:12px;color:#6b7280;"><?= htmlspecialchars($m['note'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($m['agent_name']) ?></td>
                </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Manual Adjustment Form -->
    <div class="card adjust-card">
        <div class="card-header"><i class="fas fa-sliders-h"></i> Manual Adjustment</div>
        <div class="card-body">
            <div id="adjustMsg"></div>
            <div class="form-group">
                <label>Product</label>
                <select id="adj_product">
                    <option value="">Select product...</option>
                    <?php while ($p = mysqli_fetch_assoc($products)): ?>
                    <option value="<?= $p['codep'] ?>" data-stock="<?= $p['onhand'] ?>">
                        <?= htmlspecialchars($p['nomp']) ?> (<?= $p['onhand'] ?> in stock)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select id="adj_type">
                    <option value="restock">Restock (add stock)</option>
                    <option value="adjustment">Adjustment (fix count)</option>
                    <option value="return">Return (customer returned)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity Change</label>
                <input type="number" id="adj_qty" placeholder="e.g. +10 or -3" value="1">
                <small style="color:#6b7280;font-size:12px;margin-top:4px;display:block;">
                    Use positive to add stock, negative to remove.
                </small>
            </div>
            <div class="form-group">
                <label>Note (optional)</label>
                <input type="text" id="adj_note" placeholder="e.g. Received from supplier">
            </div>
            <button class="btn btn-green" style="width:100%;justify-content:center;" onclick="submitAdjustment()">
                <i class="fas fa-check"></i> Apply Adjustment
            </button>
        </div>
    </div>

</div>
</div>

<script>
function submitAdjustment() {
    var product_id = document.getElementById('adj_product').value;
    var type       = document.getElementById('adj_type').value;
    var qty_change = parseInt(document.getElementById('adj_qty').value);
    var note       = document.getElementById('adj_note').value;
    var msgDiv     = document.getElementById('adjustMsg');

    if (!product_id) { msgDiv.innerHTML = '<div class="alert alert-error">Please select a product.</div>'; return; }
    if (!qty_change) { msgDiv.innerHTML = '<div class="alert alert-error">Please enter a quantity.</div>'; return; }

    var fd = new FormData();
    fd.append('action', 'adjust_stock');
    fd.append('product_id', product_id);
    fd.append('type', type);
    fd.append('qty_change', qty_change);
    fd.append('note', note);

    fetch('ajax/pos_ajax.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                msgDiv.innerHTML = '<div class="alert alert-success">✅ Stock updated: ' + data.qty_before + ' → ' + data.qty_after + '</div>';
                document.getElementById('adj_product').value = '';
                document.getElementById('adj_qty').value = '1';
                document.getElementById('adj_note').value = '';
                setTimeout(() => location.reload(), 1500);
            } else {
                msgDiv.innerHTML = '<div class="alert alert-error">❌ ' + data.error + '</div>';
            }
        });
}
</script>
</body>
</html>
