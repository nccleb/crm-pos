<?php
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$is_super   = ($_SESSION['oop'] === 'super');
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$conn = mysqli_connect("192.168.1.19","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

// ── Filters ────────────────────────────────────────────────────────────────
$f_agent   = mysqli_real_escape_string($conn, $_GET['agent']  ?? '');
$f_action  = mysqli_real_escape_string($conn, $_GET['action'] ?? '');
$f_from    = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$f_to      = $_GET['to']   ?? date('Y-m-d');
$page      = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 50;
$offset    = ($page - 1) * $per_page;

// Non-super users see only their own logs
if (!$is_super) { $f_agent = $agent_name; }

$where = "WHERE DATE(created_at) BETWEEN '$f_from' AND '$f_to'";
if ($f_agent)  $where .= " AND agent_name = '$f_agent'";
if ($f_action) $where .= " AND action = '$f_action'";

$total = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM pos_activity_log $where"))['cnt'];
$pages = max(1, ceil($total / $per_page));

$logs_res = mysqli_query($conn,
    "SELECT * FROM pos_activity_log $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$logs = [];
while ($r = mysqli_fetch_assoc($logs_res)) $logs[] = $r;

// Distinct agents and actions for filter dropdowns
$agents_res  = mysqli_query($conn, "SELECT DISTINCT agent_name FROM pos_activity_log ORDER BY agent_name");
$actions_res = mysqli_query($conn, "SELECT DISTINCT action FROM pos_activity_log ORDER BY action");
$agents  = []; while ($r = mysqli_fetch_assoc($agents_res))  $agents[]  = $r['agent_name'];
$actions = []; while ($r = mysqli_fetch_assoc($actions_res)) $actions[] = $r['action'];

// Action labels and icons
$action_meta = [
    'sale_completed'    => ['icon'=>'fa-receipt',           'color'=>'#10b981', 'label'=>'Sale'],
    'sale_refunded'     => ['icon'=>'fa-undo',              'color'=>'#ef4444', 'label'=>'Refund'],
    'stock_adjusted'    => ['icon'=>'fa-boxes',             'color'=>'#f59e0b', 'label'=>'Stock Adj.'],
    'product_added'     => ['icon'=>'fa-plus-circle',       'color'=>'#2563eb', 'label'=>'Product Added'],
    'product_edited'    => ['icon'=>'fa-edit',              'color'=>'#6366f1', 'label'=>'Product Edited'],
    'product_deactivated'=>['icon'=>'fa-ban',               'color'=>'#6b7280', 'label'=>'Deactivated'],
    'product_pulled'    => ['icon'=>'fa-times-circle',      'color'=>'#dc2626', 'label'=>'Pulled'],
    'batch_pulled'      => ['icon'=>'fa-calendar-times',    'color'=>'#f97316', 'label'=>'Batch Pulled'],
    'receiving_saved'   => ['icon'=>'fa-truck',             'color'=>'#0891b2', 'label'=>'Receiving'],
    'supplier_added'    => ['icon'=>'fa-building',          'color'=>'#7c3aed', 'label'=>'Supplier Added'],
    'supplier_updated'  => ['icon'=>'fa-building',          'color'=>'#7c3aed', 'label'=>'Supplier Updated'],
    'promotion_added'   => ['icon'=>'fa-tag',               'color'=>'#d97706', 'label'=>'Promo Added'],
    'promotion_updated' => ['icon'=>'fa-tag',               'color'=>'#d97706', 'label'=>'Promo Updated'],
    'promotion_toggled' => ['icon'=>'fa-toggle-on',         'color'=>'#d97706', 'label'=>'Promo Toggled'],
    'promotion_deleted' => ['icon'=>'fa-tag',               'color'=>'#ef4444', 'label'=>'Promo Deleted'],
    'settings_saved'    => ['icon'=>'fa-cog',               'color'=>'#059669', 'label'=>'Settings'],
    'backup_downloaded' => ['icon'=>'fa-download',          'color'=>'#0284c7', 'label'=>'Backup'],
    'backup_restored'   => ['icon'=>'fa-upload',            'color'=>'#dc2626', 'label'=>'Restore'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Activity Log — POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; font-size:14px; }
.topbar { background:linear-gradient(135deg,#1976D2,#0D47A1); color:white; padding:14px 24px;
          display:flex; align-items:center; gap:15px; flex-wrap:wrap; }
.topbar h1 { font-size:18px; font-weight:800; }
.topbar a { color:rgba(255,255,255,.85); text-decoration:none; font-size:13px; font-weight:600;
            background:rgba(255,255,255,.15); padding:7px 14px; border-radius:8px; display:flex;
            align-items:center; gap:6px; transition:background .2s; }
.topbar a:hover { background:rgba(255,255,255,.25); }
.topbar .ml { margin-left:auto; }
.wrap { max-width:1200px; margin:24px auto; padding:0 20px; }
.filters { background:white; border-radius:12px; padding:20px 24px; margin-bottom:20px;
           box-shadow:0 2px 8px rgba(0,0,0,.06); display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end; }
.filters label { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase;
                 display:block; margin-bottom:4px; letter-spacing:.5px; }
.filters select, .filters input[type=date], .filters input[type=text] {
    padding:8px 12px; border:2px solid #e5e7eb; border-radius:8px; font-size:13px;
    color:#1a1a2e; outline:none; background:white; min-width:140px; }
.filters select:focus, .filters input:focus { border-color:#1976D2; }
.btn-filter { background:#1976D2; color:white; border:none; padding:9px 20px; border-radius:8px;
              font-weight:700; font-size:13px; cursor:pointer; }
.btn-reset { background:#f3f4f6; color:#6b7280; border:none; padding:9px 16px; border-radius:8px;
             font-weight:600; font-size:13px; cursor:pointer; text-decoration:none; }
.stats-row { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.stat-card { background:white; border-radius:10px; padding:14px 20px; box-shadow:0 2px 8px rgba(0,0,0,.06);
             flex:1; min-width:140px; }
.stat-card .val { font-size:22px; font-weight:800; color:#1976D2; }
.stat-card .lbl { font-size:11px; color:#9ca3af; font-weight:600; text-transform:uppercase; margin-top:2px; }
.card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden; }
table { width:100%; border-collapse:collapse; }
th { background:#f8fafc; padding:12px 16px; text-align:left; font-size:11px; font-weight:700;
     color:#6b7280; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #f0f2f5; }
td { padding:12px 16px; border-bottom:1px solid #f8fafc; vertical-align:middle; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#fafafa; }
.badge-action { display:inline-flex; align-items:center; gap:6px; padding:4px 10px;
                border-radius:20px; font-size:11px; font-weight:700; }
.agent-pill { display:inline-block; background:#eff6ff; color:#1976D2; padding:3px 10px;
              border-radius:20px; font-size:12px; font-weight:700; }
.details-cell { font-size:12px; color:#6b7280; max-width:380px; line-height:1.5; }
.time-cell { font-size:12px; color:#9ca3af; white-space:nowrap; }
.pagination { display:flex; gap:6px; justify-content:center; padding:20px; flex-wrap:wrap; }
.pagination a, .pagination span { padding:7px 13px; border-radius:8px; font-size:13px; font-weight:600;
    text-decoration:none; border:2px solid #e5e7eb; color:#6b7280; background:white; }
.pagination a:hover { border-color:#1976D2; color:#1976D2; }
.pagination .active { background:#1976D2; color:white; border-color:#1976D2; }
.empty { text-align:center; padding:60px 20px; color:#9ca3af; }
.empty i { font-size:48px; margin-bottom:16px; display:block; }
</style>
</head>
<body>

<div class="topbar">
    <h1><i class="fas fa-history"></i> Activity Log</h1>
    <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
    <a href="pos_reorder.php"><i class="fas fa-truck-loading"></i> Reorder</a>
    <a href="pos_expiry_alerts.php"><i class="fas fa-bell"></i> Expiry Alerts</a>
    <a href="pos_bundles.php"><i class="fas fa-layer-group"></i> Bundles</a>
    <?php if ($is_super): ?>
    <a href="pos_settings.php"><i class="fas fa-cog"></i> Settings</a>
    <?php endif; ?>
    <span class="ml" style="font-size:13px;opacity:.8;">
        <i class="fas fa-user"></i> <?= htmlspecialchars($agent_name) ?>
    </span>
</div>

<div class="wrap">

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="val"><?= number_format($total) ?></div>
            <div class="lbl">Total Events</div>
        </div>
        <?php
        $total_all   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM pos_activity_log"))['c'];
        $sale_count  = (int)mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) AS c FROM pos_activity_log WHERE action='sale_completed' AND DATE(created_at) = CURDATE()"))['c'];
        $agent_count = (int)mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(DISTINCT agent_name) AS c FROM pos_activity_log $where"))['c'];
        $oldest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(created_at) AS d FROM pos_activity_log"))['d'];
        ?>
        <div class="stat-card">
            <div class="val"><?= $sale_count ?></div>
            <div class="lbl">Sales Today</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= $agent_count ?></div>
            <div class="lbl">Active Users</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= number_format($total_all) ?></div>
            <div class="lbl">All-time Records</div>
        </div>
        <?php if ($is_super): ?>
        <div style="display:flex;flex-direction:column;gap:8px;justify-content:center;">
            <button onclick="exportLog()"
                style="background:#0891b2;color:white;border:none;padding:10px 18px;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:7px;white-space:nowrap;">
                <i class="fas fa-file-csv"></i> Export Full Log (CSV)
            </button>
            <button onclick="confirmClear()"
                style="background:#fee2e2;color:#dc2626;border:2px solid #fecaca;padding:10px 18px;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:7px;white-space:nowrap;">
                <i class="fas fa-trash-alt"></i> Clear Log After Export
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" class="filters">
        <div>
            <label>From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($f_from) ?>">
        </div>
        <div>
            <label>To</label>
            <input type="date" name="to" value="<?= htmlspecialchars($f_to) ?>">
        </div>
        <?php if ($is_super): ?>
        <div>
            <label>User</label>
            <select name="agent">
                <option value="">All Users</option>
                <?php foreach ($agents as $a): ?>
                <option value="<?= htmlspecialchars($a) ?>" <?= $f_agent === $a ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div>
            <label>Action Type</label>
            <select name="action">
                <option value="">All Actions</option>
                <?php foreach ($actions as $ac): ?>
                <option value="<?= htmlspecialchars($ac) ?>" <?= $f_action === $ac ? 'selected' : '' ?>>
                    <?= htmlspecialchars($action_meta[$ac]['label'] ?? $ac) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
            <a href="pos_activity.php" class="btn-reset" style="margin-left:6px;">Reset</a>
        </div>
    </form>

    <!-- Table -->
    <div class="card">
        <?php if (empty($logs)): ?>
        <div class="empty">
            <i class="fas fa-history"></i>
            <p>No activity found for the selected filters.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log):
                $meta  = $action_meta[$log['action']] ?? ['icon'=>'fa-circle','color'=>'#9ca3af','label'=>$log['action']];
                $dt    = new DateTime($log['created_at']);
            ?>
            <tr>
                <td class="time-cell">
                    <div style="font-weight:700;color:#1a1a2e;"><?= $dt->format('d M Y') ?></div>
                    <div><?= $dt->format('H:i:s') ?></div>
                </td>
                <td>
                    <span class="agent-pill"><?= htmlspecialchars($log['agent_name']) ?></span>
                </td>
                <td>
                    <span class="badge-action" style="background:<?= $meta['color'] ?>18;color:<?= $meta['color'] ?>;">
                        <i class="fas <?= $meta['icon'] ?>"></i>
                        <?= htmlspecialchars($meta['label']) ?>
                    </span>
                </td>
                <td class="details-cell">
                    <?= htmlspecialchars($log['details']) ?>
                    <?php if ($log['reference_id']): ?>
                        <span style="color:#d1d5db;font-size:11px;margin-left:4px;">
                            #<?= $log['reference_id'] ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td class="time-cell"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="pagination">
            <?php
            $base = '?' . http_build_query(array_filter(['from'=>$f_from,'to'=>$f_to,'agent'=>$f_agent,'action'=>$f_action]));
            for ($p = 1; $p <= $pages; $p++):
                if ($p == $page): ?>
                    <span class="active"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= $base ?>&page=<?= $p ?>"><?= $p ?></a>
                <?php endif;
            endfor; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>

</div>
</body>

<script>
var exported = false;

function exportLog() {
    var btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';

    fetch('ajax/pos_backup_ajax.php?action=export_activity')
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert('Export failed: ' + data.error); return; }
            var bytes = Uint8Array.from(atob(data.content), c => c.charCodeAt(0));
            var blob  = new Blob([bytes], {type:'text/csv'});
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = data.filename;
            a.click();
            exported = true;
            btn.innerHTML = '<i class="fas fa-check"></i> Exported (' + data.count + ' records)';
            btn.style.background = '#10b981';
        })
        .catch(err => { alert('Error: ' + err.message); })
        .finally(() => { btn.disabled = false; });
}

function confirmClear() {
    if (!exported) {
        alert('⚠ Please export the log first before clearing.\n\nClick "Export Full Log (CSV)" to download a backup copy, then you can safely clear.');
        return;
    }

    var total = <?= $total_all ?>;
    if (!confirm(
        '🗑 CLEAR ACTIVITY LOG?\n\n' +
        'This will permanently delete ALL ' + total.toLocaleString() + ' activity records.\n\n' +
        '✅ You have already exported a CSV backup.\n\n' +
        'Are you sure you want to continue?'
    )) return;

    // Second confirmation
    var typed = prompt('Type DELETE to confirm:');
    if (typed !== 'DELETE') { alert('Cancelled — log was not cleared.'); return; }

    fetch('ajax/pos_backup_ajax.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=clear_activity'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ Activity log cleared — ' + data.deleted + ' records deleted.');
            location.reload();
        } else {
            alert('❌ ' + data.error);
        }
    });
}
</script>
</html>
