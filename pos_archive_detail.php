<?php
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$archive_table = $_GET['archive'] ?? '';
$source_table  = $_GET['table'] ?? 'pos_sales';

if (empty($archive_table)) { header("Location: pos_archive.php"); exit(); }

$conn = mysqli_connect("172.18.208.1","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

$esc = mysqli_real_escape_string($conn, $archive_table);

// Verify archive exists in registry
$registry = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM archive_registry WHERE archive_table = '$esc' LIMIT 1"));

if (!$registry) {
    mysqli_close($conn);
    echo '<div style="padding:40px;text-align:center;font-family:sans-serif;color:#ef4444;">Archive not found. <a href="pos_archive.php">Back to Archive Manager</a></div>';
    exit();
}

// Count records
$count_res = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM `$esc`"));
$total = (int)$count_res['c'];

$is_sales = ($source_table === 'pos_sales');

$pay_labels = ['cash'=>'Cash','card'=>'Card','omt'=>'OMT','whish'=>'Whish','bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','credit'=>'Credit'];

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Archive: <?= htmlspecialchars($archive_table) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.topbar { background:linear-gradient(135deg,#7c3aed,#5b21b6); color:white; padding:14px 24px; display:flex; align-items:center; gap:15px; flex-wrap:wrap; }
.topbar .archive-name { font-size:14px; font-weight:800; }
.topbar .archive-meta { font-size:12px; opacity:.8; }
.topbar a { color:white; text-decoration:none; background:rgba(255,255,255,.15); padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; }
.topbar a:hover { background:rgba(255,255,255,.25); }
.topbar .ml { margin-left:auto; display:flex; gap:8px; flex-wrap:wrap; }
.live-badge { background:#10b981; color:white; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; }
.archive-badge { background:rgba(255,255,255,.2); padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; }
.container { max-width:1200px; margin:0 auto; padding:24px 20px; }
.info-banner { background:linear-gradient(135deg,#faf5ff,#ede9fe); border:2px solid #c4b5fd; border-radius:12px; padding:16px 22px; margin-bottom:20px; display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
.info-item .lbl { font-size:11px; color:#6b7280; font-weight:700; text-transform:uppercase; }
.info-item .val { font-size:16px; font-weight:800; color:#5b21b6; }
.card { background:white; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.08); overflow:hidden; margin-bottom:20px; }
.card-header { padding:14px 20px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.card-header h3 { font-size:14px; font-weight:800; color:#1a1a2e; }
.btn { padding:8px 16px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
.btn-purple { background:#7c3aed; color:white; } .btn-purple:hover { background:#5b21b6; }
.btn-green  { background:#10b981; color:white; } .btn-green:hover  { background:#059669; }
.btn-gray   { background:#f3f4f6; color:#374151; border:2px solid #e5e7eb; }
.btn-sm     { padding:5px 10px; font-size:12px; }
table { width:100%; border-collapse:collapse; font-size:13px; }
th { padding:10px 12px; background:#faf5ff; color:#374151; font-weight:700; text-align:left; border-bottom:2px solid #e5e7eb; }
td { padding:10px 12px; border-bottom:1px solid #f3f4f6; color:#4b5563; }
tr:hover td { background:#fafafa; }
.badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:700; }
.badge-completed { background:#d1fae5; color:#065f46; }
.badge-refunded  { background:#fee2e2; color:#991b1b; }
.pagination { display:flex; gap:6px; align-items:center; margin-top:14px; flex-wrap:wrap; }
.page-btn { padding:6px 12px; border:2px solid #e5e7eb; background:white; border-radius:6px; cursor:pointer; font-size:12px; font-weight:700; }
.page-btn.active { background:#7c3aed; color:white; border-color:#7c3aed; }
.page-btn:hover:not(.active):not(:disabled) { border-color:#7c3aed; color:#7c3aed; }
.page-btn:disabled { opacity:.4; cursor:not-allowed; }
#recordCount { font-size:12px; color:#6b7280; margin-left:auto; }
.loading { text-align:center; padding:40px; color:#9ca3af; }
</style>
</head>
<body>

<div class="topbar">
    <i class="fas fa-box-open fa-lg"></i>
    <div>
        <div class="archive-name"><span class="archive-badge">ARCHIVE</span> <?= htmlspecialchars($archive_table) ?></div>
        <div class="archive-meta">
            <?= $is_sales ? 'Sales History' : 'Stock Movements' ?> ·
            <?= number_format($total) ?> records ·
            Created <?= date('d M Y', strtotime($registry['created_at'])) ?> by <?= htmlspecialchars($registry['created_by']) ?>
        </div>
    </div>
    <div class="ml">
        <button class="btn btn-green" onclick="exportCSV()"><i class="fas fa-file-csv"></i> Export CSV</button>
        <a href="pos_archive.php"><i class="fas fa-arrow-left"></i> Archive Manager</a>
        <a href="pos.php" class="live-badge"><i class="fas fa-cash-register"></i> Live POS</a>
    </div>
</div>

<div class="container">

    <!-- Info banner -->
    <div class="info-banner">
        <div class="info-item">
            <div class="lbl">Archive Name</div>
            <div class="val"><?= htmlspecialchars($archive_table) ?></div>
        </div>
        <div class="info-item">
            <div class="lbl">Records</div>
            <div class="val"><?= number_format($total) ?></div>
        </div>
        <?php if ($registry['date_from']): ?>
        <div class="info-item">
            <div class="lbl">Date Range</div>
            <div class="val"><?= date('d M Y',strtotime($registry['date_from'])) ?> → <?= date('d M Y',strtotime($registry['date_to'])) ?></div>
        </div>
        <?php endif; ?>
        <div class="info-item">
            <div class="lbl">Type</div>
            <div class="val"><?= ucfirst(str_replace('_',' ',$registry['archive_type'])) ?></div>
        </div>
    </div>

    <!-- Records table -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-table" style="color:#7c3aed;"></i> Archive Records</h3>
            <span id="recordCount">Loading...</span>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead id="tableHead"></thead>
                <tbody id="tableBody"><tr><td colspan="10" class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr></tbody>
            </table>
        </div>
        <div style="padding:14px 20px;border-top:1px solid #f3f4f6;">
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

</div>

<script>
var archiveTable = '<?= htmlspecialchars($archive_table) ?>';
var sourceTable  = '<?= htmlspecialchars($source_table) ?>';
var isSales      = sourceTable === 'pos_sales';
var currentPage  = 1;
var totalPages   = 1;
var payLabels    = {cash:'Cash',card:'Card',omt:'OMT',whish:'Whish',bank_transfer:'Bank Transfer',cheque:'Cheque',credit:'Credit'};

function loadPage(page) {
    currentPage = page;
    var url = 'ajax/pos_archive_ajax.php?action=get_archive_records&archive_table='
              + encodeURIComponent(archiveTable) + '&page=' + page;
    fetch(url).then(r=>r.json()).then(data => {
        if (!data.success) {
            document.getElementById('tableBody').innerHTML = '<tr><td colspan="10" style="color:#ef4444;padding:20px;">Error: ' + data.error + '</td></tr>';
            return;
        }
        totalPages = data.pages;
        document.getElementById('recordCount').textContent = data.total + ' total records · Page ' + data.page + ' of ' + data.pages;
        renderTable(data.rows);
        renderPagination();
    });
}

function renderTable(rows) {
    var head = document.getElementById('tableHead');
    var body = document.getElementById('tableBody');

    if (rows.length === 0) {
        head.innerHTML = '';
        body.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#9ca3af;">No records in this archive.</td></tr>';
        return;
    }

    if (isSales) {
        head.innerHTML = '<tr><th>#</th><th>Date</th><th>Customer</th><th>Items</th><th>Payment</th><th>Currency</th><th>Discount</th><th>Total</th><th>Status</th><th>Cashier</th></tr>';
        body.innerHTML = rows.map(r => {
            var sym = r.currency==='LBP'?'LL ':(r.currency==='EUR'?'€':'$');
            var status = r.status==='refunded'
                ? '<span class="badge badge-refunded">Refunded</span>'
                : '<span class="badge badge-completed">Completed</span>';
            return '<tr>' +
                '<td><strong>#' + r.id + '</strong></td>' +
                '<td>' + r.created_at.substring(0,10) + '</td>' +
                '<td>' + escHtml(r.client_name||'Walk-in') + '</td>' +
                '<td style="font-size:12px;color:#6b7280;">' + (r.item_count||'—') + '</td>' +
                '<td>' + (payLabels[r.payment_method]||r.payment_method) + '</td>' +
                '<td>' + r.currency + '</td>' +
                '<td>' + (parseFloat(r.discount)>0 ? '-'+sym+parseFloat(r.discount).toFixed(2) : '—') + '</td>' +
                '<td><strong>' + sym + parseFloat(r.final_total).toFixed(2) + '</strong></td>' +
                '<td>' + status + '</td>' +
                '<td style="font-size:12px;">' + escHtml(r.agent_name||'') + '</td>' +
                '</tr>';
        }).join('');
    } else {
        head.innerHTML = '<tr><th>#</th><th>Date</th><th>Product</th><th>Type</th><th>Before</th><th>Change</th><th>After</th><th>Note</th><th>Agent</th></tr>';
        body.innerHTML = rows.map(r => {
            var chg = parseInt(r.qty_change);
            var chgHtml = '<span style="font-weight:800;color:' + (chg<0?'#ef4444':'#10b981') + ';">' + (chg>0?'+':'') + chg + '</span>';
            var typeCols = {sale:'#fee2e2|#991b1b',restock:'#d1fae5|#065f46',adjustment:'#fef3c7|#92400e',return:'#ede9fe|#5b21b6'};
            var tc = (typeCols[r.type]||'#f3f4f6|#374151').split('|');
            var typeBadge = '<span class="badge" style="background:' + tc[0] + ';color:' + tc[1] + ';">' + r.type + '</span>';
            return '<tr>' +
                '<td><strong>' + r.id + '</strong></td>' +
                '<td>' + r.created_at.substring(0,10) + '</td>' +
                '<td><strong>' + escHtml(r.product_name) + '</strong></td>' +
                '<td>' + typeBadge + '</td>' +
                '<td>' + r.qty_before + '</td>' +
                '<td>' + chgHtml + '</td>' +
                '<td><strong>' + r.qty_after + '</strong></td>' +
                '<td style="font-size:12px;color:#6b7280;">' + escHtml(r.note||'—') + '</td>' +
                '<td style="font-size:12px;">' + escHtml(r.agent_name||'') + '</td>' +
                '</tr>';
        }).join('');
    }
}

function renderPagination() {
    var pg = document.getElementById('pagination');
    if (totalPages <= 1) { pg.innerHTML = ''; return; }
    var html = '';
    html += '<button class="page-btn" onclick="loadPage(1)" ' + (currentPage===1?'disabled':'') + '>«</button>';
    html += '<button class="page-btn" onclick="loadPage(' + (currentPage-1) + ')" ' + (currentPage===1?'disabled':'') + '>‹</button>';
    var start = Math.max(1, currentPage-2), end = Math.min(totalPages, currentPage+2);
    for (var i=start;i<=end;i++) {
        html += '<button class="page-btn ' + (i===currentPage?'active':'') + '" onclick="loadPage(' + i + ')">' + i + '</button>';
    }
    html += '<button class="page-btn" onclick="loadPage(' + (currentPage+1) + ')" ' + (currentPage===totalPages?'disabled':'') + '>›</button>';
    html += '<button class="page-btn" onclick="loadPage(' + totalPages + ')" ' + (currentPage===totalPages?'disabled':'') + '>»</button>';
    html += '<span id="recordCount" style="margin-left:8px;font-size:12px;color:#6b7280;">Page ' + currentPage + ' of ' + totalPages + '</span>';
    pg.innerHTML = html;
}

function exportCSV() {
    fetch('ajax/pos_archive_ajax.php?action=export_csv&source=' + encodeURIComponent(archiveTable))
        .then(r=>r.json()).then(data => {
            if (!data.success) { alert('Export failed: ' + data.error); return; }
            var bin = atob(data.content);
            var bytes = new Uint8Array(bin.length);
            for (var i=0;i<bin.length;i++) bytes[i]=bin.charCodeAt(i);
            var blob = new Blob([bytes],{type:'text/csv'});
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = data.filename;
            a.click();
        });
}

function escHtml(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

loadPage(1);
</script>
</body>
</html>
