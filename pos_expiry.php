<?php
// ============================================================
// NCC CRM POS — Expiry Date Tracking (v2 — per batch)
// pos_expiry.php
// ============================================================
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);
$is_super   = ($agent_name === 'super');

$conn = mysqli_connect("192.168.1.101","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

$today = date('Y-m-d');
$in7   = date('Y-m-d', strtotime('+7 days'));
$in30  = date('Y-m-d', strtotime('+30 days'));

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Expiry Tracking — NCC POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; font-size:.9rem; }

.topbar { background:linear-gradient(135deg,#1976D2,#0D47A1); color:white;
          padding:14px 24px; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.topbar h1 { font-size:17px; font-weight:700; }
.topbar a  { color:white; text-decoration:none; background:rgba(255,255,255,.15);
             padding:7px 14px; border-radius:6px; font-size:12.5px; font-weight:600;
             display:inline-flex; align-items:center; gap:5px; }
.topbar a:hover { background:rgba(255,255,255,.28); }
.topbar .ml { margin-left:auto; display:flex; gap:8px; flex-wrap:wrap; }
.container { max-width:1380px; margin:0 auto; padding:22px 18px; }

.alert-banner { background:#fef2f2; border:1.5px solid #fca5a5; border-radius:10px;
                padding:.9rem 1.2rem; margin-bottom:18px;
                display:flex; align-items:flex-start; gap:.8rem;
                font-size:.88rem; color:#991b1b; font-weight:600; }
.alert-banner i { font-size:1.3rem; margin-top:1px; }

.stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px; }
.stat  { background:#fff; border-radius:10px; padding:18px 20px;
         box-shadow:0 1px 5px rgba(0,0,0,.07); border-left:5px solid #ccc;
         cursor:pointer; transition:transform .15s, box-shadow .15s; }
.stat:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.1); }
.stat.expired  { border-color:#dc2626; }
.stat.critical { border-color:#f97316; }
.stat.warning  { border-color:#eab308; }
.stat.ok       { border-color:#16a34a; }
.stat .num { font-size:2.2rem; font-weight:800; }
.stat.expired  .num { color:#dc2626; }
.stat.critical .num { color:#f97316; }
.stat.warning  .num { color:#eab308; }
.stat.ok       .num { color:#16a34a; }
.stat .lbl { font-size:.74rem; color:#6b7280; text-transform:uppercase; font-weight:700; margin-top:3px; }
.stat .sub { font-size:.73rem; color:#9ca3af; margin-top:2px; }

.toolbar { display:flex; gap:10px; align-items:center; margin-bottom:14px; flex-wrap:wrap; }
.tabs { display:flex; gap:7px; flex-wrap:wrap; }
.tab  { padding:.4rem 1rem; border-radius:20px; border:none; cursor:pointer;
        font-size:.81rem; font-weight:700; background:#e2e8f0; color:#475569;
        display:inline-flex; align-items:center; gap:5px; transition:all .15s; }
.tab:hover { filter:brightness(.93); }
.tab.t-all.active      { background:#1976D2; color:#fff; }
.tab.t-expired.active  { background:#dc2626; color:#fff; }
.tab.t-critical.active { background:#f97316; color:#fff; }
.tab.t-warning.active  { background:#eab308; color:#fff; }
.tab.t-ok.active       { background:#16a34a; color:#fff; }

.search-box { padding:.42rem .9rem; border:2px solid #e2e8f0; border-radius:8px;
              font-size:.85rem; min-width:230px; }
.search-box:focus { outline:none; border-color:#1976D2; }
.result-count { font-size:.78rem; color:#9ca3af; }

.card { background:#fff; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.07); overflow:hidden; }
table { width:100%; border-collapse:collapse; font-size:.83rem; }
th    { padding:10px 13px; background:#1e3a5f; color:#fff; font-weight:700;
        text-align:left; white-space:nowrap; font-size:.8rem; }
td    { padding:9px 13px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }

tr.row-expired  > td { background:#fff5f5; }
tr.row-critical > td { background:#fff8f1; }
tr.row-warning  > td { background:#fffef0; }
tr:hover > td { filter:brightness(.97); }

/* indent second+ batch of same product */
tr.same-product > td:first-child { padding-left:2rem; border-left:3px solid #e2e8f0; }

.product-name { font-weight:700; color:#1e293b; }
.batch-sub    { font-size:.74rem; color:#64748b; margin-top:2px; }
.second-badge { font-size:.7rem; background:#f1f5f9; color:#94a3b8;
                border-radius:10px; padding:1px 7px; font-weight:600; margin-left:5px; }

.badge { display:inline-block; padding:3px 10px; border-radius:20px;
         font-size:.74rem; font-weight:700; white-space:nowrap; }
.badge-expired  { background:#fee2e2; color:#991b1b; }
.badge-critical { background:#ffedd5; color:#9a3412; }
.badge-warning  { background:#fef9c3; color:#854d0e; }
.badge-ok       { background:#dcfce7; color:#166534; }

.days-pill { font-weight:800; font-size:.88rem; }
.days-expired  { color:#dc2626; }
.days-critical { color:#f97316; }
.days-warning  { color:#ca8a04; }
.days-ok       { color:#16a34a; }

.btn-act { padding:.28rem .7rem; border-radius:6px; border:none; cursor:pointer;
           font-size:.76rem; font-weight:600; display:inline-flex; align-items:center;
           gap:4px; margin-right:4px; }
.btn-act:hover { filter:brightness(.9); }
.btn-discount { background:#dbeafe; color:#1e40af; }
.btn-pulled   { background:#fee2e2; color:#991b1b; }

.empty { text-align:center; padding:3rem; color:#9ca3af; }
.empty i { font-size:2.5rem; display:block; margin-bottom:.5rem; }

@media(max-width:700px){ .stats { grid-template-columns:1fr 1fr; } }
</style>
</head>
<body>

<div class="topbar">
  <i class="fas fa-calendar-times fa-lg"></i>
  <h1>Expiry Tracking</h1>
  <div class="ml">
    <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="pos_receiving.php"><i class="fas fa-truck-loading"></i> Receiving</a>
    <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
    <a href="pos_stock.php"><i class="fas fa-boxes"></i> Stock</a>
    <?php if($is_super): ?><a href="pos_settings.php"><i class="fas fa-cog"></i> Settings</a><?php endif; ?>
    <a href="test204.php?page=<?= urlencode($agent_name) ?>&page1=<?= $agent_id ?>"><i class="fas fa-arrow-left"></i> CRM</a>
  </div>
</div>

<div class="container">

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

  <div class="stats">
    <div class="stat expired"  onclick="filterTab('expired')">
      <div class="num"><?= $stat_expired ?></div>
      <div class="lbl"><i class="fas fa-ban"></i> Expired Batches</div>
      <div class="sub">Past expiry date — pull now</div>
    </div>
    <div class="stat critical" onclick="filterTab('critical')">
      <div class="num"><?= $stat_critical ?></div>
      <div class="lbl"><i class="fas fa-fire"></i> Critical Batches</div>
      <div class="sub">Expire within 7 days</div>
    </div>
    <div class="stat warning"  onclick="filterTab('warning')">
      <div class="num"><?= $stat_warning ?></div>
      <div class="lbl"><i class="fas fa-exclamation-circle"></i> Warning Batches</div>
      <div class="sub">Expire in 8–30 days</div>
    </div>
    <div class="stat ok"       onclick="filterTab('ok')">
      <div class="num"><?= $stat_ok ?></div>
      <div class="lbl"><i class="fas fa-check-circle"></i> OK Batches</div>
      <div class="sub">More than 30 days left</div>
    </div>
  </div>

  <div class="toolbar">
    <div class="tabs">
      <button class="tab t-all active" onclick="filterTab('all')">All (<?= count($all_batches) ?>)</button>
      <button class="tab t-expired"    onclick="filterTab('expired')">
        <i class="fas fa-ban"></i> Expired (<?= $stat_expired ?>)
      </button>
      <button class="tab t-critical"   onclick="filterTab('critical')">
        <i class="fas fa-fire"></i> Critical (<?= $stat_critical ?>)
      </button>
      <button class="tab t-warning"    onclick="filterTab('warning')">
        <i class="fas fa-exclamation-circle"></i> Warning (<?= $stat_warning ?>)
      </button>
      <button class="tab t-ok"         onclick="filterTab('ok')">
        <i class="fas fa-check-circle"></i> OK (<?= $stat_ok ?>)
      </button>
    </div>
    <input type="text" class="search-box" id="searchBox"
           placeholder="&#128269; Search product name..." oninput="applyFilters()">
    <span class="result-count" id="resultCount"></span>
  </div>

  <div class="card">
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

        if      ($days < 0)   { $status='expired';  $badge='badge-expired';  $label='EXPIRED';  $dcls='days-expired'; }
        elseif  ($days <= 7)  { $status='critical'; $badge='badge-critical'; $label='CRITICAL'; $dcls='days-critical'; }
        elseif  ($days <= 30) { $status='warning';  $badge='badge-warning';  $label='WARNING';  $dcls='days-warning'; }
        else                  { $status='ok';       $badge='badge-ok';       $label='OK';       $dcls='days-ok'; }

        $days_text = $days < 0
          ? abs($days) . (abs($days) === 1 ? ' day ago' : ' days ago')
          : ($days === 0 ? 'Today!' : $days . ($days === 1 ? ' day' : ' days'));

        $prev_pid = $b['product_id'];
      ?>
      <tr class="exp-row <?= $same ? 'same-product row-'.$status : 'row-'.$status ?>"
          data-status="<?= $status ?>"
          data-name="<?= htmlspecialchars(strtolower($b['product_name'])) ?>"
          data-product-id="<?= $b['product_id'] ?>">
        <td>
          <div class="product-name">
            <?= htmlspecialchars($b['product_name']) ?>
            <?php if ($same): ?><span class="second-badge">batch 2+</span><?php endif; ?>
          </div>
          <?php if (!empty($b['batch_notes'])): ?>
            <div class="batch-sub"><i class="fas fa-comment-alt"></i> <?= htmlspecialchars($b['batch_notes']) ?></div>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($b['category'] ?? '—') ?></td>
        <td><?= (float)$b['qty_received'] ?> <?= htmlspecialchars($b['unit'] ?? '') ?></td>
        <td><?= $b['onhand'] ?> <?= htmlspecialchars($b['unit'] ?? '') ?></td>
        <td><?= htmlspecialchars($b['supplier_name'] ?: '—') ?></td>
        <td style="white-space:nowrap"><?= $b['received_date'] ? date('d M Y', strtotime($b['received_date'])) : '—' ?></td>
        <td><?= htmlspecialchars($b['invoice_number'] ?: '—') ?></td>
        <td style="white-space:nowrap; font-weight:600;"><?= date('d M Y', strtotime($b['expiry_date'])) ?></td>
        <td><span class="days-pill <?= $dcls ?>"><?= $days_text ?></span></td>
        <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
        <td style="white-space:nowrap;">
          <?php if ($days <= 30): ?>
          <button class="btn-act btn-discount" onclick="goDiscount(<?= $b['product_id'] ?>)">
            <i class="fas fa-tag"></i> Discount
          </button>
          <?php endif; ?>
          <?php if ($days < 0): ?>
          <button class="btn-act btn-pulled" onclick="pullProduct(<?= $b['product_id'] ?>, '<?= htmlspecialchars(addslashes($b['product_name'])) ?>')">
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
          No expiry dates tracked yet.<br>
          Add expiry dates when receiving stock.
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

function pullProduct(productId, name) {
  if (!confirm(`Pull "${name}" from shelf?\n\nThis will DEACTIVATE the product so it no longer appears at the POS.\n\nMake sure ALL batches of this product are expired before pulling.`)) return;
  fetch('ajax/pos_receiving_ajax.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=deactivate_product&product_id=' + productId
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.querySelectorAll('[data-product-id="' + productId + '"]').forEach(r => r.remove());
      alert('"' + name + '" has been deactivated and removed from POS.');
    } else {
      alert('Error: ' + (data.error || 'Could not deactivate'));
    }
  });
}
</script>
</body>
</html>
