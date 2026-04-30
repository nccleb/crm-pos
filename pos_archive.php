<?php
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$conn = mysqli_connect("192.168.1.101","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

// Live record counts
$sales_count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM pos_sales"))['c'];
$stock_count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM stock_movements"))['c'];

// Archive list
$archives_res = mysqli_query($conn,"SELECT * FROM archive_registry ORDER BY created_at DESC");
$archives = [];
while ($r = mysqli_fetch_assoc($archives_res)) $archives[] = $r;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Archive Manager — POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.topbar { background:linear-gradient(135deg,#1976D2,#0D47A1); color:white; padding:14px 24px; display:flex; align-items:center; gap:15px; }
.topbar h1 { font-size:18px; font-weight:700; }
.topbar a { color:white; text-decoration:none; background:rgba(255,255,255,.15); padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; }
.topbar a:hover { background:rgba(255,255,255,.25); }
.topbar .ml { margin-left:auto; display:flex; gap:8px; }
.container { max-width:1100px; margin:0 auto; padding:24px 20px; }
.card { background:white; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.08); overflow:hidden; margin-bottom:20px; }
.card-header { padding:16px 22px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.card-header h3 { font-size:15px; font-weight:800; color:#1a1a2e; display:flex; align-items:center; gap:8px; }
.card-body { padding:20px 22px; }
.stats { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:20px; }
.stat { background:white; border-radius:10px; padding:16px 20px; border-left:4px solid #1976D2; box-shadow:0 1px 4px rgba(0,0,0,.07); }
.stat.amber { border-color:#f59e0b; }
.stat.green { border-color:#10b981; }
.stat .val { font-size:22px; font-weight:800; color:#1976D2; }
.stat.amber .val { color:#f59e0b; }
.stat.green .val { color:#10b981; }
.stat .lbl { font-size:12px; color:#6b7280; font-weight:600; text-transform:uppercase; }
.btn { padding:9px 18px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
.btn-blue   { background:#1976D2; color:white; } .btn-blue:hover { background:#0D47A1; }
.btn-amber  { background:#f59e0b; color:white; } .btn-amber:hover { background:#d97706; }
.btn-red    { background:#ef4444; color:white; } .btn-red:hover { background:#dc2626; }
.btn-green  { background:#10b981; color:white; } .btn-green:hover { background:#059669; }
.btn-gray   { background:#f3f4f6; color:#374151; border:2px solid #e5e7eb; }
.btn-sm     { padding:6px 12px; font-size:12px; }
.form-row { display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:10px; align-items:end; }
.form-group label { display:block; font-size:11px; font-weight:700; color:#374151; text-transform:uppercase; margin-bottom:5px; }
.form-group select, .form-group input { width:100%; padding:9px 12px; border:2px solid #e5e7eb; border-radius:8px; font-size:13px; }
.form-group select:focus, .form-group input:focus { outline:none; border-color:#1976D2; }
table { width:100%; border-collapse:collapse; font-size:13px; }
th { padding:10px 14px; background:#f8fafc; color:#374151; font-weight:700; text-align:left; border-bottom:2px solid #e5e7eb; }
td { padding:10px 14px; border-bottom:1px solid #f3f4f6; color:#4b5563; }
tr:hover td { background:#fafafa; }
.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.badge-sales { background:#dbeafe; color:#1e40af; }
.badge-stock { background:#ede9fe; color:#5b21b6; }
.badge-full  { background:#d1fae5; color:#065f46; }
.badge-date  { background:#fef3c7; color:#92400e; }
.modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; padding:20px; }
.modal.show { display:flex; }
.modal-box { background:white; border-radius:14px; width:100%; max-width:500px; overflow:hidden; }
.modal-header { padding:18px 22px; background:linear-gradient(135deg,#1976D2,#0D47A1); color:white; display:flex; justify-content:space-between; align-items:center; }
.modal-header h3 { font-size:16px; font-weight:800; }
.modal-close { background:none; border:none; color:white; font-size:22px; cursor:pointer; }
.modal-body { padding:24px; }
.modal-footer { padding:16px 24px; border-top:1px solid #e5e7eb; display:flex; justify-content:flex-end; gap:10px; }
.alert { padding:12px 16px; border-radius:8px; font-size:13px; font-weight:600; margin-bottom:14px; }
.alert-info { background:#eff6ff; color:#1e40af; border-left:4px solid #1976D2; }
.alert-warn { background:#fef3c7; color:#92400e; border-left:4px solid #f59e0b; }
.no-data { text-align:center; padding:40px; color:#9ca3af; }
.no-data i { font-size:36px; display:block; margin-bottom:10px; }
</style>
</head>
<body>
<div class="topbar">
    <i class="fas fa-archive fa-lg"></i>
    <h1>Archive Manager</h1>
    <div class="ml">
        <a href="pos_import.php"><i class="fas fa-file-import"></i> Import Products</a>
        <a href="pos_sales.php"><i class="fas fa-history"></i> Sales</a>
        <a href="pos_receiving.php"><i class="fas fa-truck-loading"></i> Receiving</a>
    <a href="pos_expiry.php"><i class="fas fa-calendar-times"></i> Expiry</a>
        <a href="pos_suppliers.php"><i class="fas fa-building"></i> Suppliers</a>
        <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
        <a href="test204.php?page=<?= urlencode($agent_name) ?>&page1=<?= $agent_id ?>"><i class="fas fa-arrow-left"></i> CRM</a>
    </div>
</div>

<div class="container">

    <!-- Stats -->
    <div class="stats">
        <div class="stat">
            <div class="val"><?= number_format($sales_count) ?></div>
            <div class="lbl">Live Sales Records</div>
        </div>
        <div class="stat amber">
            <div class="val"><?= number_format($stock_count) ?></div>
            <div class="lbl">Live Stock Movements</div>
        </div>
        <div class="stat green">
            <div class="val"><?= count($archives) ?></div>
            <div class="lbl">Total Archives</div>
        </div>
    </div>

    <!-- Create Archive -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle" style="color:#1976D2;"></i> Create New Archive</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-warn">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong> Archiving moves records OUT of the live table into a separate archive table. This reduces live table size. Records remain accessible in Archive Manager.
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Table to Archive</label>
                    <select id="arc_table">
                        <option value="pos_sales">Sales History (<?= number_format($sales_count) ?> records)</option>
                        <option value="stock_movements">Stock Movements (<?= number_format($stock_count) ?> records)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Archive Type</label>
                    <select id="arc_type" onchange="toggleDatePicker()">
                        <option value="full">Full Archive — All records</option>
                        <option value="date_based">Date Based — Before a date</option>
                    </select>
                </div>
                <div class="form-group" id="date_picker_wrap" style="display:none;">
                    <label>Archive Everything Before</label>
                    <input type="date" id="arc_date" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button class="btn btn-amber" onclick="confirmCreateArchive()">
                        <i class="fas fa-archive"></i> Create Archive
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Live Data -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-download" style="color:#10b981;"></i> Export Live Data</h3>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Table</label>
                    <select id="exp_table">
                        <option value="pos_sales">Sales History</option>
                        <option value="stock_movements">Stock Movements</option>
                        <option value="produit">Products</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>From Date (optional)</label>
                    <input type="date" id="exp_from">
                </div>
                <div class="form-group">
                    <label>To Date (optional)</label>
                    <input type="date" id="exp_to">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button class="btn btn-green" onclick="exportCSV(null)">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Archives List -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-boxes" style="color:#7c3aed;"></i> Existing Archives</h3>
            <button class="btn btn-gray btn-sm" onclick="loadArchives()"><i class="fas fa-sync-alt"></i> Refresh</button>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Archive Name</th><th>Table</th><th>Type</th>
                        <th>Date Range</th><th>Records</th><th>Created</th><th>By</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody id="archiveTableBody">
                <?php if (empty($archives)): ?>
                <tr><td colspan="8" class="no-data"><i class="fas fa-inbox"></i>No archives yet.</td></tr>
                <?php else: foreach ($archives as $a):
                    $tbl_label = $a['table_name'] === 'pos_sales' ? 'Sales' : 'Stock';
                    $tbl_class = $a['table_name'] === 'pos_sales' ? 'badge-sales' : 'badge-stock';
                    $type_class= $a['archive_type'] === 'full' ? 'badge-full' : 'badge-date';
                    $range = $a['date_from'] ? date('d M Y',strtotime($a['date_from'])).' → '.date('d M Y',strtotime($a['date_to'])) : 'All records';
                ?>
                <tr>
                    <td><strong style="color:#1976D2;"><?= htmlspecialchars($a['archive_table']) ?></strong></td>
                    <td><span class="badge <?= $tbl_class ?>"><?= $tbl_label ?></span></td>
                    <td><span class="badge <?= $type_class ?>"><?= ucfirst(str_replace('_',' ',$a['archive_type'])) ?></span></td>
                    <td style="font-size:12px;"><?= $range ?></td>
                    <td><strong><?= number_format($a['records_count']) ?></strong></td>
                    <td style="font-size:12px;"><?= date('d M Y H:i', strtotime($a['created_at'])) ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($a['created_by']) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="pos_archive_detail.php?archive=<?= urlencode($a['archive_table']) ?>&table=<?= urlencode($a['table_name']) ?>"
                               class="btn btn-blue btn-sm"><i class="fas fa-eye"></i> Open</a>
                            <button class="btn btn-green btn-sm" onclick="exportCSV('<?= htmlspecialchars($a['archive_table']) ?>')">
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                            <button class="btn btn-red btn-sm" onclick="confirmDeleteArchive('<?= htmlspecialchars($a['archive_table']) ?>','<?= $a['records_count'] ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Confirm Archive Modal -->
<div class="modal" id="arcConfirmModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Archive</h3>
            <button class="modal-close" onclick="closeModal('arcConfirmModal')">×</button>
        </div>
        <div class="modal-body" id="arcConfirmBody"></div>
        <div class="modal-footer">
            <button class="btn btn-gray" onclick="closeModal('arcConfirmModal')">Cancel</button>
            <button class="btn btn-amber" id="arcConfirmBtn" onclick="doCreateArchive()">
                <i class="fas fa-archive"></i> Yes, Archive
            </button>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal" id="delConfirmModal">
    <div class="modal-box">
        <div class="modal-header" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
            <h3><i class="fas fa-trash"></i> Delete Archive</h3>
            <button class="modal-close" onclick="closeModal('delConfirmModal')">×</button>
        </div>
        <div class="modal-body" id="delConfirmBody"></div>
        <div class="modal-footer">
            <button class="btn btn-gray" onclick="closeModal('delConfirmModal')">Cancel</button>
            <button class="btn btn-red" id="delConfirmBtn"><i class="fas fa-trash"></i> Yes, Delete Forever</button>
        </div>
    </div>
</div>

<!-- Result Modal -->
<div class="modal" id="resultModal">
    <div class="modal-box">
        <div class="modal-header" id="resultHeader">
            <h3 id="resultTitle">Result</h3>
            <button class="modal-close" onclick="closeModal('resultModal')">×</button>
        </div>
        <div class="modal-body" id="resultBody"></div>
        <div class="modal-footer">
            <button class="btn btn-blue" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh Page</button>
        </div>
    </div>
</div>

<script>
var pendingArchiveTable = null, pendingArchiveType = null, pendingArchiveDate = null;
var pendingDeleteTable = null;

function toggleDatePicker() {
    var type = document.getElementById('arc_type').value;
    document.getElementById('date_picker_wrap').style.display = type === 'date_based' ? '' : 'none';
}

function confirmCreateArchive() {
    pendingArchiveTable = document.getElementById('arc_table').value;
    pendingArchiveType  = document.getElementById('arc_type').value;
    pendingArchiveDate  = document.getElementById('arc_date').value;

    if (pendingArchiveType === 'date_based' && !pendingArchiveDate) {
        alert('Please select a date for date-based archive.');
        return;
    }

    var tableLabel = pendingArchiveTable === 'pos_sales' ? 'Sales History' : 'Stock Movements';
    var typeLabel  = pendingArchiveType === 'full' ? 'ALL records' : 'records before ' + pendingArchiveDate;

    document.getElementById('arcConfirmBody').innerHTML =
        '<p style="margin-bottom:12px;">You are about to archive <strong>' + typeLabel + '</strong> from <strong>' + tableLabel + '</strong>.</p>' +
        '<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px;border-radius:6px;font-size:13px;">' +
        '<strong>This will move records out of the live table.</strong><br>They will be accessible in Archive Manager.' +
        '</div>';
    document.getElementById('arcConfirmModal').classList.add('show');
}

function doCreateArchive() {
    var btn = document.getElementById('arcConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';

    var fd = new FormData();
    fd.append('action', 'create_archive');
    fd.append('table_name', pendingArchiveTable);
    fd.append('type', pendingArchiveType);
    if (pendingArchiveDate) fd.append('date_to', pendingArchiveDate);

    fetch('ajax/pos_archive_ajax.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            closeModal('arcConfirmModal');
            if (data.success) {
                showResult('success', '✅ Archive Created',
                    '<p><strong>' + data.count + '</strong> records moved to <code>' + data.archive_table + '</code></p>');
            } else {
                showResult('error', '❌ Archive Failed', '<p>' + data.error + '</p>');
            }
        });
}

function confirmDeleteArchive(archiveTable, count) {
    pendingDeleteTable = archiveTable;
    document.getElementById('delConfirmBody').innerHTML =
        '<p style="margin-bottom:12px;">You are about to permanently delete:</p>' +
        '<div style="background:#fee2e2;border-left:4px solid #ef4444;padding:12px;border-radius:6px;">' +
        '<strong>' + archiveTable + '</strong><br>' +
        '<span style="color:#991b1b;">Contains ' + count + ' records. This CANNOT be undone.</span>' +
        '</div>';
    document.getElementById('delConfirmBtn').onclick = doDeleteArchive;
    document.getElementById('delConfirmModal').classList.add('show');
}

function doDeleteArchive() {
    var btn = document.getElementById('delConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

    var fd = new FormData();
    fd.append('action', 'delete_archive');
    fd.append('archive_table', pendingDeleteTable);

    fetch('ajax/pos_archive_ajax.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            closeModal('delConfirmModal');
            if (data.success) {
                showResult('success', '✅ Archive Deleted', '<p>Archive permanently removed.</p>');
            } else {
                showResult('error', '❌ Delete Failed', '<p>' + data.error + '</p>');
            }
        });
}

function exportCSV(archiveTable) {
    var source = archiveTable || document.getElementById('exp_table').value;
    var from   = archiveTable ? '' : (document.getElementById('exp_from').value || '');
    var to     = archiveTable ? '' : (document.getElementById('exp_to').value || '');

    var url = 'ajax/pos_archive_ajax.php?action=export_csv&source=' + encodeURIComponent(source);
    if (from) url += '&date_from=' + from;
    if (to)   url += '&date_to=' + to;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert('Export failed: ' + data.error); return; }
            var bin = atob(data.content);
            var bytes = new Uint8Array(bin.length);
            for (var i=0;i<bin.length;i++) bytes[i]=bin.charCodeAt(i);
            var blob = new Blob([bytes], {type:'text/csv'});
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = data.filename;
            a.click();
        });
}

function showResult(type, title, body) {
    var colors = { success:'linear-gradient(135deg,#10b981,#059669)', error:'linear-gradient(135deg,#ef4444,#dc2626)' };
    document.getElementById('resultHeader').style.background = colors[type] || colors.success;
    document.getElementById('resultTitle').textContent = title;
    document.getElementById('resultBody').innerHTML = body;
    document.getElementById('resultModal').classList.add('show');
}

function closeModal(id) { document.getElementById(id).classList.remove('show'); }

// Close modal on backdrop click
document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('show'); });
});
</script>
</body>
</html>
