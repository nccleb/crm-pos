<?php
// ============================================================
// NCC CRM POS — Expiry Date Tracking (v3 — dark theme, per batch)
// pos_expiry.php
// ============================================================
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);
$is_super   = ($agent_name === 'super');

$conn = mysqli_connect("192.168.1.19","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

$today = date('Y-m-d');

// ── All batches — one row per receiving item with expiry date ──
$all_batches = [];

$res = mysqli_query($conn,
    "SELECT
        i.id             AS batch_id,
        i.product_id,
        i.product_name,
        i.qty_received,
        i.expiry_date,
        i.notes          AS batch_notes,
        r.received_date,
        r.supplier_name,
        r.invoice_number,
        r.id             AS receiving_id,
        p.onhand,
        p.category,
        p.unit,
        DATEDIFF(i.expiry_date, '$today') AS days_left
     FROM stock_receiving_items i
     JOIN stock_receivings r ON r.id = i.receiving_id
     JOIN produit p ON p.codep = i.product_id
     WHERE i.expiry_date IS NOT NULL
     AND p.active = 1
     ORDER BY i.expiry_date ASC, i.product_name ASC");

while ($r = mysqli_fetch_assoc($res)) {
    $r['source'] = 'batch';
    $all_batches[] = $r;
}

// Products with expiry set directly on produit but no batch records
$res2 = mysqli_query($conn,
    "SELECT
        NULL            AS batch_id,
        p.codep         AS product_id,
        p.nomp          AS product_name,
        p.onhand        AS qty_received,
        p.expiry_date,
        NULL            AS batch_notes,
        NULL            AS received_date,
        NULL            AS supplier_name,
        NULL            AS invoice_number,
        NULL            AS receiving_id,
        p.onhand,
        p.category,
        p.unit,
        DATEDIFF(p.expiry_date, '$today') AS days_left
     FROM produit p
     WHERE p.active = 1
     AND p.expiry_date IS NOT NULL
     AND p.codep NOT IN (
         SELECT DISTINCT product_id FROM stock_receiving_items WHERE expiry_date IS NOT NULL
     )
     ORDER BY p.expiry_date ASC");

while ($r = mysqli_fetch_assoc($res2)) {
    $r['source'] = 'direct';
    $all_batches[] = $r;
}

usort($all_batches, fn($a,$b) => strcmp($a['expiry_date'], $b['expiry_date']));

// ── Stats — count batches ──────────────────────────────────
$stat_expired = $stat_critical = $stat_warning = $stat_ok = 0;
foreach ($all_batches as $b) {
    $d = (int)$b['days_left'];
    if      ($d < 0)   $stat_expired++;
    elseif  ($d <= 7)  $stat_critical++;
    elseif  ($d <= 30) $stat_warning++;
    else               $stat_ok++;
}
$total_alerts = $stat_expired + $stat_critical;

// Strip trailing zeros from decimal quantities (13.000 -> 13, 0.450 -> 0.45)
function fmtQty($n) {
    $n = (float)$n;
    if ($n == intval($n)) return (string)intval($n);
    return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Expiry Tracking — NCC POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#0f172a;font-family:'Segoe UI',sans-serif;font-size:14px;color:#e2e8f0;min-height:100vh;}

/* ── Topbar ── */
.topbar{background:linear-gradient(135deg,#1e3a5f,#0f2d50);padding:14px 24px;
        display:flex;align-items:center;gap:14px;flex-wrap:wrap;
        border-bottom:1px solid rgba(255,255,255,.07);}
.topbar h1{font-size:17px;font-weight:800;color:#fff;display:flex;align-items:center;gap:9px;}
.topbar h1 .icon{width:32px;height:32px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);
    border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.topbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:12px;font-weight:600;
          background:rgba(255,255,255,.1);padding:6px 14px;border-radius:7px;
          display:flex;align-items:center;gap:6px;transition:all .2s;}
.topbar a:hover{background:rgba(255,255,255,.18);color:#fff;}
.topbar .ml{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center;}

/* ── Layout ── */
.wrap{max-width:1380px;margin:24px auto;padding:0 20px;}

/* ── Alert banner ── */
.alert-banner{background:rgba(220,38,38,.12);border:1px solid rgba(220,38,38,.3);border-radius:12px;
              padding:14px 18px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px;
              font-size:13px;color:#fca5a5;font-weight:600;}
.alert-banner i{font-size:18px;margin-top:1px;color:#f87171;}

/* ── Stats ── */
.stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:24px;}
.stat{background:linear-gradient(135deg,rgba(255,255,255,.05),rgba(255,255,255,.02));
      border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:18px 20px;position:relative;
      overflow:hidden;cursor:pointer;transition:transform .15s,border-color .15s;}
.stat:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.18);}
.stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.stat.s-expired::before {background:linear-gradient(90deg,#dc2626,#991b1b);}
.stat.s-critical::before{background:linear-gradient(90deg,#f97316,#ea580c);}
.stat.s-warning::before {background:linear-gradient(90deg,#eab308,#ca8a04);}
.stat.s-ok::before       {background:linear-gradient(90deg,#16a34a,#15803d);}
.stat .sv{font-size:28px;font-weight:900;line-height:1;}
.stat.s-expired .sv {color:#f87171;}
.stat.s-critical .sv{color:#fb923c;}
.stat.s-warning .sv {color:#fbbf24;}
.stat.s-ok .sv      {color:#6ee7b7;}
.stat .sl{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;
          color:rgba(255,255,255,.4);margin-top:5px;display:flex;align-items:center;gap:5px;}
.stat .sub{font-size:11px;color:rgba(255,255,255,.3);margin-top:3px;}

/* ── Toolbar ── */
.toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px;}
.tabs{display:flex;gap:7px;flex-wrap:wrap;}
.tab{padding:8px 16px;border-radius:20px;border:1px solid rgba(255,255,255,.1);cursor:pointer;
     font-size:12px;font-weight:700;background:rgba(255,255,255,.06);color:#94a3b8;
     display:inline-flex;align-items:center;gap:6px;transition:all .2s;}
.tab:hover{background:rgba(255,255,255,.1);color:#e2e8f0;}
.tab.t-all.active     {background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border-color:transparent;}
.tab.t-expired.active {background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;border-color:transparent;}
.tab.t-critical.active{background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;border-color:transparent;}
.tab.t-warning.active {background:linear-gradient(135deg,#eab308,#ca8a04);color:#fff;border-color:transparent;}
.tab.t-ok.active       {background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border-color:transparent;}

.search-box{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);
            color:#e2e8f0;padding:8px 14px;border-radius:8px;font-size:13px;outline:none;
            min-width:230px;transition:border-color .2s;}
.search-box:focus{border-color:#3b82f6;}
.search-box::placeholder{color:rgba(255,255,255,.3);}
.result-count{font-size:12px;color:rgba(255,255,255,.35);}

.btn{padding:9px 18px;border:none;border-radius:9px;font-size:13px;font-weight:700;
     cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:all .2s;text-decoration:none;}
.btn-log{background:rgba(255,255,255,.06);color:#94a3b8;border:1px solid rgba(255,255,255,.08);}
.btn-log:hover{background:rgba(255,255,255,.1);color:#e2e8f0;}

/* ── Table ── */
.table-wrap{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);
            border-radius:14px;overflow:hidden;margin-bottom:24px;}
table{width:100%;border-collapse:collapse;}
th{padding:11px 14px;text-align:left;font-size:10px;font-weight:700;letter-spacing:.7px;
   text-transform:uppercase;color:rgba(255,255,255,.35);border-bottom:1px solid rgba(255,255,255,.06);
   white-space:nowrap;}
td{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(255,255,255,.03);}

tr.row-expired  td{background:rgba(220,38,38,.05);}
tr.row-critical td{background:rgba(249,115,22,.05);}
tr.row-warning  td{background:rgba(234,179,8,.04);}

/* indent second+ batch of same product */
tr.same-product td:first-child{padding-left:2rem;border-left:3px solid rgba(255,255,255,.1);}

.product-name{font-weight:700;color:#e2e8f0;}
.batch-sub{font-size:11px;color:rgba(255,255,255,.35);margin-top:3px;display:flex;align-items:center;gap:5px;}
.second-badge{font-size:10px;background:rgba(255,255,255,.08);color:rgba(255,255,255,.4);
              border-radius:10px;padding:2px 8px;font-weight:600;margin-left:6px;}

/* ── Status badge ── */
.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;
       font-size:11px;font-weight:800;white-space:nowrap;}
.badge-expired {background:rgba(220,38,38,.18);color:#f87171;}
.badge-critical{background:rgba(249,115,22,.18);color:#fb923c;}
.badge-warning {background:rgba(234,179,8,.16);color:#fbbf24;}
.badge-ok      {background:rgba(16,185,129,.15);color:#6ee7b7;}

/* ── Days badge ── */
.days-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;
            border-radius:20px;font-size:12px;font-weight:800;}
.db-expired {background:rgba(107,114,128,.2);color:#9ca3af;}
.db-critical{background:rgba(220,38,38,.2);color:#f87171;}
.db-warning {background:rgba(234,179,8,.18);color:#fbbf24;}
.db-ok      {background:rgba(16,185,129,.15);color:#6ee7b7;}

.btn-act{padding:7px 14px;border-radius:8px;border:none;cursor:pointer;
         font-size:12px;font-weight:700;display:inline-flex;align-items:center;
         gap:6px;margin-right:6px;transition:all .2s;}
.btn-act:hover{transform:translateY(-1px);}
.btn-discount{background:rgba(37,99,235,.18);color:#93c5fd;border:1px solid rgba(37,99,235,.25);}
.btn-discount:hover{background:rgba(37,99,235,.3);}
.btn-pulled{background:rgba(220,38,38,.18);color:#f87171;border:1px solid rgba(220,38,38,.25);}
.btn-pulled:hover{background:rgba(220,38,38,.3);}

/* ── Empty state ── */
.empty{text-align:center;padding:60px 20px;color:rgba(255,255,255,.25);}
.empty i{font-size:48px;display:block;margin-bottom:16px;}
.empty p{font-size:15px;font-weight:600;}

@media(max-width:700px){.stats{grid-template-columns:1fr 1fr;}}
@media print{.topbar,.toolbar,.btn{display:none!important;}
body{background:white;color:black;}.table-wrap{border:1px solid #ccc;}.stat{background:#f5f5f5;}}
</style>
</head>
<body>

<div class="topbar">
    <h1>
        <span class="icon"><i class="fas fa-calendar-times"></i></span>
        Expiry Tracking
    </h1>
    <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <!a href="pos_expiry_alerts.php"><i class="fas fa-bell"></i> Alert Center</a>
    <a href="pos_receiving.php"><i class="fas fa-truck-loading"></i> Receiving</a>
    <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
    <a href="pos_stock.php"><i class="fas fa-boxes"></i> Stock</a>
    <div class="ml">
        <?php if ($is_super): ?><a href="pos_settings.php"><i class="fas fa-cog"></i> Settings</a><?php endif; ?>
        <a href="test204.php?page=<?= urlencode($agent_name) ?>&page1=<?= $agent_id ?>"><i class="fas fa-arrow-left"></i> CRM</a>
        <span style="font-size:12px;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:6px;">
            <i class="fas fa-user"></i> <?= htmlspecialchars($agent_name) ?>
        </span>
    </div>
</div>

<div class="wrap">

    <?php if ($total_alerts > 0): ?>
    <div class="alert-banner">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <?php if ($stat_expired > 0): ?>
                <strong><?= $stat_expired ?> batch<?= $stat_expired > 1 ? 'es' : '' ?> EXPIRED</strong>
                — pull these from shelves immediately.<?= $stat_critical > 0 ? '<br>' : '' ?>
            <?php endif; ?>
            <?php if ($stat_critical > 0): ?>
                <strong><?= $stat_critical ?> batch<?= $stat_critical > 1 ? 'es' : '' ?> expire within 7 days</strong>
                — consider discounting or promoting now.
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats">
        <div class="stat s-expired" onclick="filterTab('expired')">
            <div class="sv"><?= $stat_expired ?></div>
            <div class="sl"><i class="fas fa-ban"></i> Expired</div>
            <div class="sub">Past expiry date — pull now</div>
        </div>
        <div class="stat s-critical" onclick="filterTab('critical')">
            <div class="sv"><?= $stat_critical ?></div>
            <div class="sl"><i class="fas fa-fire"></i> Critical</div>
            <div class="sub">Expire within 7 days</div>
        </div>
        <div class="stat s-warning" onclick="filterTab('warning')">
            <div class="sv"><?= $stat_warning ?></div>
            <div class="sl"><i class="fas fa-exclamation-circle"></i> Warning</div>
            <div class="sub">Expire in 8–30 days</div>
        </div>
        <div class="stat s-ok" onclick="filterTab('ok')">
            <div class="sv"><?= $stat_ok ?></div>
            <div class="sl"><i class="fas fa-check-circle"></i> OK</div>
            <div class="sub">More than 30 days left</div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
        <div class="tabs">
            <button class="tab t-all active" onclick="filterTab('all')">
                <i class="fas fa-layer-group"></i> All (<?= count($all_batches) ?>)
            </button>
            <button class="tab t-expired" onclick="filterTab('expired')">
                <i class="fas fa-ban"></i> Expired (<?= $stat_expired ?>)
            </button>
            <button class="tab t-critical" onclick="filterTab('critical')">
                <i class="fas fa-fire"></i> Critical (<?= $stat_critical ?>)
            </button>
            <button class="tab t-warning" onclick="filterTab('warning')">
                <i class="fas fa-exclamation-circle"></i> Warning (<?= $stat_warning ?>)
            </button>
            <button class="tab t-ok" onclick="filterTab('ok')">
                <i class="fas fa-check-circle"></i> OK (<?= $stat_ok ?>)
            </button>
        </div>
        <input type="text" class="search-box" id="searchBox"
               placeholder="🔍 Search product name..." oninput="applyFilters()">
        <span class="result-count" id="resultCount"></span>
        <div style="margin-left:auto;">
            <button class="btn btn-log" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- Main table -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Batch Qty</th>
                    <th>Total In Stock</th>
                    <th>Supplier</th>
                    <th>Received On</th>
                    <th>Invoice</th>
                    <th>Expiry Date</th>
                    <th>Days Left</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="tableBody">
            <?php
            $prev_pid = null;
            foreach ($all_batches as $b):
                $days = (int)$b['days_left'];
                $same = ($b['product_id'] === $prev_pid);

                if      ($days < 0)   { $status='expired';  $badge='badge-expired';  $label='EXPIRED';  $dcls='db-expired'; }
                elseif  ($days <= 7)  { $status='critical'; $badge='badge-critical'; $label='CRITICAL'; $dcls='db-critical'; }
                elseif  ($days <= 30) { $status='warning';  $badge='badge-warning';  $label='WARNING';  $dcls='db-warning'; }
                else                  { $status='ok';       $badge='badge-ok';       $label='OK';       $dcls='db-ok'; }

                $days_text = $days < 0
                  ? abs($days) . (abs($days) === 1 ? ' day ago' : ' days ago')
                  : ($days === 0 ? 'Today!' : $days . ($days === 1 ? ' day' : ' days'));

                $prev_pid = $b['product_id'];
            ?>
            <tr class="exp-row <?= $same ? 'same-product row-'.$status : 'row-'.$status ?>"
                data-status="<?= $status ?>"
                data-name="<?= htmlspecialchars(strtolower($b['product_name'])) ?>"
                data-product-id="<?= $b['product_id'] ?>"
                data-batch-id="<?= $b['batch_id'] ?? '' ?>"
                data-source="<?= $b['source'] ?>">
                <td>
                    <div class="product-name">
                        <?= htmlspecialchars($b['product_name']) ?>
                        <?php if ($same): ?><span class="second-badge">batch 2+</span><?php endif; ?>
                    </div>
                    <?php if (!empty($b['batch_notes'])): ?>
                        <div class="batch-sub"><i class="fas fa-comment-alt"></i> <?= htmlspecialchars($b['batch_notes']) ?></div>
                    <?php endif; ?>
                </td>
                <td><span style="font-size:11px;background:rgba(255,255,255,.07);color:rgba(255,255,255,.5);padding:3px 9px;border-radius:20px;font-weight:600;"><?= htmlspecialchars($b['category'] ?? '—') ?></span></td>
                <td><?= fmtQty($b['qty_received']) ?> <?= htmlspecialchars($b['unit'] ?? '') ?></td>
                <td style="font-weight:700;"><?= fmtQty($b['onhand']) ?> <?= htmlspecialchars($b['unit'] ?? '') ?></td>
                <td style="color:rgba(255,255,255,.5);"><?= htmlspecialchars($b['supplier_name'] ?: '—') ?></td>
                <td style="white-space:nowrap;color:rgba(255,255,255,.5);"><?= $b['received_date'] ? date('d M Y', strtotime($b['received_date'])) : '—' ?></td>
                <td style="color:rgba(255,255,255,.5);"><?= htmlspecialchars($b['invoice_number'] ?: '—') ?></td>
                <td style="white-space:nowrap;font-weight:700;"><?= date('d M Y', strtotime($b['expiry_date'])) ?></td>
                <td><span class="days-badge <?= $dcls ?>"><?= $days_text ?></span></td>
                <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                <td style="white-space:nowrap;">
                    <?php if ($days <= 30): ?>
                    <button class="btn-act btn-discount" onclick="goDiscount(<?= $b['product_id'] ?>)">
                        <i class="fas fa-tag"></i> Discount
                    </button>
                    <?php endif; ?>
                    <?php if ($days < 0): ?>
                    <button class="btn-act btn-pulled" onclick="pullProduct(<?= $b['product_id'] ?>, '<?= htmlspecialchars(addslashes($b['product_name'])) ?>', '<?= $b['batch_id'] ?? '' ?>', '<?= $b['source'] ?>')">
                        <i class="fas fa-times-circle"></i> Pull
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($all_batches)): ?>
            <tr><td colspan="11">
                <div class="empty">
                    <i class="fas fa-calendar-check"></i>
                    <p>No expiry dates tracked yet.</p>
                    <span style="font-size:13px;">Add expiry dates when receiving stock.</span>
                </div>
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
let activeFilter = 'all';

function filterTab(filter) {
    activeFilter = filter;
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelector('.tab.t-' + filter)?.classList.add('active');
    applyFilters();
}

function applyFilters() {
    const q    = document.getElementById('searchBox').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.exp-row');
    let visible = 0;
    rows.forEach(row => {
        const ok = (activeFilter === 'all' || row.dataset.status === activeFilter)
                && (!q || row.dataset.name.includes(q));
        row.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });
    const rc = document.getElementById('resultCount');
    rc.textContent = (visible === rows.length) ? '' : `Showing ${visible} of ${rows.length} batches`;
}

function goDiscount(productId) {
    window.open('pos_products.php?edit=' + productId, '_blank');
}

function pullProduct(productId, name, batchId, source) {
    const allRows    = document.querySelectorAll('[data-product-id="' + productId + '"]');
    const nonExpired = Array.from(allRows).filter(r => r.dataset.status !== 'expired');

    if (nonExpired.length > 0) {
        // Other valid batches exist — only pull this expired batch, keep product active
        if (!confirm(
            'Pull expired batch of "' + name + '" from shelf?\n\n' +
            'This product has ' + nonExpired.length + ' other valid batch(es) — ' +
            'the product will STAY ACTIVE at the POS.\n\n' +
            'Only this expired batch will be removed from tracking.'
        )) return;

        fetch('ajax/pos_receiving_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=pull_batch&batch_id=' + batchId
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Array.from(allRows)
                    .filter(r => r.dataset.status === 'expired')
                    .forEach(r => r.remove());
                applyFilters();
                alert('Expired batch removed. Product remains active at POS.');
            } else {
                alert('Error: ' + (data.error || 'Could not pull batch'));
            }
        });

    } else {
        // All batches expired — deactivate the whole product
        if (!confirm(
            'Pull "' + name + '" from shelf?\n\n' +
            'All batches of this product are expired.\n' +
            'This will DEACTIVATE the product so it no longer appears at the POS.'
        )) return;

        fetch('ajax/pos_receiving_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=deactivate_product&product_id=' + productId
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('[data-product-id="' + productId + '"]').forEach(r => r.remove());
                applyFilters();
                alert('"' + name + '" has been deactivated and removed from POS.');
            } else {
                alert('Error: ' + (data.error || 'Could not deactivate'));
            }
        });
    }
}
</script>
</body>
</html>
