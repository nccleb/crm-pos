<?php
// ============================================================
// NCC CRM POS — Stock Receiving
// pos_receiving.php
// ============================================================
session_start();
if (!isset($_SESSION["oop"]) || !isset($_SESSION["ooq"])) {
    header("Location: login200.php"); exit;
}
$agent_name = $_SESSION["oop"];
$is_super   = ($agent_name === "super");

// Get exchange rate + company settings
$db = new mysqli("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
$db->set_charset("utf8mb4");
$settings   = $db->query("SELECT usd_to_lbp FROM company_settings LIMIT 1")->fetch_assoc();
$usd_to_lbp = $settings ? (float)$settings["usd_to_lbp"] : 89700;
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Stock Receiving — NCC POS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root { --dark:#1a2b4a; --mid:#2563eb; --light:#dbeafe; --green:#16a34a; --red:#dc2626; }
body  { background:#f1f5f9; font-size:.9rem; }

/* Topbar */
.topbar { background:var(--dark); color:#fff; padding:.6rem 1.2rem;
          display:flex; align-items:center; justify-content:space-between; }
.topbar a { color:#93c5fd; text-decoration:none; font-size:.85rem; margin-left:1rem; }
.topbar a:hover { color:#fff; }

/* Layout: left panel = form, right panel = history */
.main-wrap { display:flex; gap:1rem; padding:1rem; min-height:calc(100vh - 50px); }
.panel-left  { width:62%; }
.panel-right { width:38%; }

/* Cards */
.card-block { background:#fff; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,.07);
              padding:1.2rem; margin-bottom:1rem; }
.section-title { font-size:.95rem; font-weight:700; color:var(--dark);
                 border-left:4px solid var(--mid); padding-left:.7rem; margin-bottom:1rem; }

/* Search box */
.search-wrap { position:relative; }
.search-wrap input { padding-right:2.5rem; }
.search-results { position:absolute; top:100%; left:0; right:0; background:#fff;
                  border:1px solid #e2e8f0; border-radius:0 0 8px 8px;
                  max-height:280px; overflow-y:auto; z-index:200; display:none;
                  box-shadow:0 4px 16px rgba(0,0,0,.12); }
.search-item { padding:.55rem .9rem; cursor:pointer; display:flex;
               justify-content:space-between; align-items:center; font-size:.85rem; }
.search-item:hover { background:var(--light); }
.search-item .stock-badge { font-size:.75rem; color:#64748b; }

/* Items table */
.items-table th { background:var(--dark); color:#fff; font-size:.78rem; padding:.45rem .5rem; }
.items-table td { font-size:.83rem; padding:.4rem .5rem; vertical-align:middle; }
.items-table input { font-size:.82rem; padding:.28rem .4rem; min-width:0; }
.items-table .remove-btn { color:var(--red); cursor:pointer; background:none; border:none; font-size:1rem; }
.items-table .remove-btn:hover { color:#991b1b; }
.subtotal-cell { font-weight:700; color:var(--dark); white-space:nowrap; }

/* Totals bar */
.totals-bar { background:var(--dark); color:#fff; border-radius:8px;
              padding:.9rem 1.2rem; display:flex; justify-content:space-between; align-items:center; }
.totals-bar .amt { font-size:1.2rem; font-weight:700; }
.totals-bar .lbl { font-size:.75rem; opacity:.7; }

/* Buttons */
.btn-save { background:var(--green); color:#fff; border:none; padding:.55rem 1.5rem;
            font-weight:600; border-radius:6px; font-size:.92rem; }
.btn-save:hover { background:#15803d; color:#fff; }
.btn-clear { background:#e2e8f0; color:#475569; border:none; padding:.55rem 1rem; border-radius:6px; }
.btn-clear:hover { background:#cbd5e1; }

/* History panel */
.history-item { padding:.7rem .9rem; border-bottom:1px solid #f1f5f9; cursor:pointer;
                transition:background .15s; }
.history-item:hover { background:var(--light); }
.history-item .ref  { font-weight:700; color:var(--dark); font-size:.85rem; }
.history-item .meta { font-size:.77rem; color:#64748b; }
.history-item .amt  { font-size:.85rem; font-weight:600; color:var(--green); white-space:nowrap; }

/* Cost hint */
.cost-hint { font-size:.72rem; color:#94a3b8; margin-top:1px; }
.cost-changed { color:var(--red) !important; font-weight:600; }

/* Status badges */
.badge-new  { background:#dcfce7; color:#166534; border-radius:20px; padding:.2rem .6rem; font-size:.75rem; }
.badge-done { background:#dbeafe; color:#1e40af; border-radius:20px; padding:.2rem .6rem; font-size:.75rem; }

/* Empty state */
.empty-items { text-align:center; padding:2rem; color:#94a3b8; }
.empty-items i { font-size:2.5rem; display:block; margin-bottom:.5rem; }
</style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
  <div>
    <i class="bi bi-box-arrow-in-down me-2"></i>
    <strong>NCC POS</strong>
    <span class="ms-3 text-white-50">Stock Receiving</span>
  </div>
  <div>
    <a href="pos.php"><i class="bi bi-cart3"></i> POS</a>
    <a href="pos_suppliers.php"><i class="bi bi-building"></i> Suppliers</a>
    <a href="pos_expiry.php"><i class="bi bi-calendar-x"></i> Expiry</a>
    <a href="pos_products.php"><i class="bi bi-box-seam"></i> Products</a>
    <a href="pos_stock.php"><i class="bi bi-graph-up"></i> Stock</a>
    <?php if($is_super): ?>
    <a href="pos_settings.php"><i class="bi bi-gear"></i> Settings</a>
    <?php endif; ?>
    <span class="ms-3 text-white-50"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($agent_name) ?></span>
  </div>
</div>

<div class="main-wrap">

  <!-- ═══════════════════════════════════════════════════════
       LEFT — New Receiving Form
  ════════════════════════════════════════════════════════════ -->
  <div class="panel-left">

    <!-- Header info -->
    <div class="card-block">
      <div class="section-title">New Receiving</div>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Supplier</label>
          <select id="supplierId" class="form-select form-select-sm" onchange="onSupplierChange()">
            <option value="">— Walk-in / Unknown —</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Invoice / Ref #</label>
          <input type="text" id="invoiceNo" class="form-control form-control-sm" placeholder="Optional">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Date Received</label>
          <input type="date" id="receivedDate" class="form-control form-control-sm"
                 value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-8">
          <label class="form-label fw-semibold">Notes</label>
          <input type="text" id="receivingNotes" class="form-control form-control-sm"
                 placeholder="e.g. Partial delivery, waiting for cold items...">
        </div>

      </div>
    </div>

    <!-- Product search -->
    <div class="card-block">
      <div class="section-title">Add Products</div>
      <div class="search-wrap">
        <input type="text" id="productSearch" class="form-control"
               placeholder="&#128269;  Scan barcode or type product name..."
               oninput="searchProducts()" onkeydown="handleSearchKey(event)"
               autocomplete="off">
        <div class="search-results" id="searchResults"></div>
      </div>
      <div class="cost-hint mt-2">
        <i class="bi bi-info-circle"></i>
        Scan the barcode directly into the search box — products with barcodes will be added instantly.
      </div>
    </div>

    <!-- Items table -->
    <div class="card-block p-0 overflow-hidden">
      <div id="emptyState" class="empty-items">
        <i class="bi bi-inbox"></i>
        No products added yet.<br>Search above or scan a barcode.
      </div>
      <div id="tableWrap" class="d-none">
        <table class="table table-bordered items-table mb-0" id="itemsTable">
          <thead>
            <tr>
              <th style="width:26%">Product</th>
              <th style="width:10%">Qty</th>
              <th style="width:32%">Cost per unit (LL)</th>
              <th style="width:14%">Expiry</th>
              <th style="width:14%">Subtotal</th>
              <th style="width:4%"></th>
            </tr>
          </thead>
          <tbody id="itemsBody"></tbody>
        </table>
      </div>
    </div>

    <!-- Totals + Save -->
    <div class="card-block">
      <div class="totals-bar mb-3">
        <div>
          <div class="lbl">TOTAL COST (LL)</div>
          <div class="amt" id="totalUsd">LL 0</div>
        </div>
        <div id="totalLbp" style="display:none"></div>
        <div>
          <div class="lbl">PRODUCTS</div>
          <div class="amt" id="totalItems">0</div>
        </div>
        <div>
          <div class="lbl">TOTAL UNITS</div>
          <div class="amt" id="totalUnits">0</div>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn-save" onclick="saveReceiving()">
          <i class="bi bi-check-circle me-1"></i>Save & Update Stock
        </button>
        <button class="btn-clear" onclick="clearAll()">
          <i class="bi bi-x-circle me-1"></i>Clear
        </button>
      </div>
      <div id="saveMsg" class="mt-2"></div>
    </div>

  </div>

  <!-- ═══════════════════════════════════════════════════════
       RIGHT — Receiving History
  ════════════════════════════════════════════════════════════ -->
  <div class="panel-right">
    <div class="card-block p-0 overflow-hidden" style="max-height:calc(100vh - 80px); display:flex; flex-direction:column;">

      <!-- Filter bar -->
      <div style="padding:1rem 1rem .7rem; border-bottom:1px solid #f1f5f9;">
        <div class="section-title mb-2">Receiving History</div>
        <div class="row g-2">
          <div class="col-6">
            <input type="date" id="hDateFrom" class="form-control form-control-sm"
                   value="<?= date('Y-m-d', strtotime('-30 days')) ?>" onchange="loadHistory()">
          </div>
          <div class="col-6">
            <input type="date" id="hDateTo" class="form-control form-control-sm"
                   value="<?= date('Y-m-d') ?>" onchange="loadHistory()">
          </div>
          <div class="col-12">
            <select id="hSupplier" class="form-select form-select-sm" onchange="loadHistory()">
              <option value="">All suppliers</option>
            </select>
          </div>
        </div>
      </div>

      <!-- History list -->
      <div id="historyList" style="overflow-y:auto; flex:1; min-height:0;">
        <div class="text-center text-muted py-4">Loading...</div>
      </div>

    </div>
  </div>

</div><!-- /main-wrap -->

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--dark);color:#fff">
        <h5 class="modal-title" id="detailTitle">Receiving Detail</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailBody">Loading...</div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── State ──────────────────────────────────────────────────
let items       = [];   // [{product_id, product_name, qty, cost_price_usd, cost_price_lbp, expiry_date, notes, last_cost_usd}]
let suppliers   = [];
let searchTimer = null;
const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
const RATE = () => <?= $usd_to_lbp ?>; // rate kept for reference, not used in LBP storage

// ── Init ───────────────────────────────────────────────────
(async function init() {
  await loadSuppliers();
  await loadHistory();
  document.getElementById('productSearch').focus();
})();

// ── Suppliers ──────────────────────────────────────────────
async function loadSuppliers() {
  const res  = await fetch('ajax/pos_receiving_ajax.php?action=list_suppliers');
  const data = await res.json();
  suppliers  = data.suppliers || [];

  const sel  = document.getElementById('supplierId');
  const hSel = document.getElementById('hSupplier');
  suppliers.forEach(s => {
    sel.innerHTML  += `<option value="${s.id}">${esc(s.name)}</option>`;
    hSel.innerHTML += `<option value="${s.id}">${esc(s.name)}</option>`;
  });
}

function onSupplierChange() { /* can be extended */ }

// ── Product Search ─────────────────────────────────────────
function searchProducts() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(doSearch, 180);
}

async function doSearch() {
  const q   = document.getElementById('productSearch').value.trim();
  if (q.length < 1) { closeResults(); return; }

  const res  = await fetch(`ajax/pos_receiving_ajax.php?action=search_products&q=${encodeURIComponent(q)}`);
  const data = await res.json();
  const list = data.products || [];

  // Barcode exact match → auto-add
  if (list.length === 1 && list[0].barcode && list[0].barcode === q) {
    addItem(list[0]);
    document.getElementById('productSearch').value = '';
    closeResults();
    return;
  }

  const box = document.getElementById('searchResults');
  if (!list.length) {
    box.innerHTML = '<div class="search-item text-muted">No products found</div>';
    box.style.display = 'block';
    return;
  }
  box.innerHTML = list.map(p => `
    <div class="search-item" onclick="addItem(${JSON.stringify(p).replace(/"/g,'&quot;')})">
      <div>
        <strong>${esc(p.nomp)}</strong>
        ${p.barcode ? `<span class="text-muted ms-2 small">${esc(p.barcode)}</span>` : ''}
        <br><span class="stock-badge">Stock: ${p.onhand} ${esc(p.unit||'pc')}
        &nbsp;|&nbsp; Category: ${esc(p.category||'—')}</span>
      </div>
      <div class="text-end">
        <div class="stock-badge">Cost: $${parseFloat(p.cost_price||0).toFixed(2)}</div>
      </div>
    </div>
  `).join('');
  box.style.display = 'block';
}

function closeResults() {
  document.getElementById('searchResults').style.display = 'none';
}

function handleSearchKey(e) {
  if (e.key === 'Escape') closeResults();
}

document.addEventListener('click', e => {
  if (!e.target.closest('.search-wrap')) closeResults();
});

// ── Add Item ───────────────────────────────────────────────
async function addItem(p) {
  closeResults();
  document.getElementById('productSearch').value = '';

  // Check if already in list
  const existing = items.find(i => i.product_id == p.id);
  if (existing) {
    existing.qty += 1;
    renderItems();
    recalcAll();
    document.getElementById('productSearch').focus();
    return;
  }

  // Fetch last cost price from previous receivings
  let lastCost = null;
  try {
    const res  = await fetch(`ajax/pos_receiving_ajax.php?action=get_last_cost&product_id=${p.id}`);
    const data = await res.json();
    lastCost   = data.last_cost;
  } catch(e) {}

  // All costs in LBP now
  const cost_lbp = lastCost
    ? parseFloat(lastCost.cost_price_lbp || 0)
    : parseFloat(p.cost_price || 0);

  items.push({
    product_id:     p.id,
    product_name:   p.nomp,
    unit:           p.unit || 'pc',
    qty:            1,
    cost_price_lbp: Math.round(cost_lbp),
    last_cost_lbp:  lastCost ? parseFloat(lastCost.cost_price_lbp || 0) : parseFloat(p.cost_price || 0),
    expiry_date:    '',
    notes:          '',
    last_cost:      lastCost,
    current_cost:   parseFloat(p.cost_price || 0),
  });

  renderItems();
  recalcAll();
  document.getElementById('productSearch').focus();
}

// ── Render Items Table ─────────────────────────────────────
function renderItems() {
  const tbody = document.getElementById('itemsBody');
  const empty = document.getElementById('emptyState');
  const wrap  = document.getElementById('tableWrap');

  if (!items.length) {
    empty.classList.remove('d-none');
    wrap.classList.add('d-none');
    return;
  }
  empty.classList.add('d-none');
  wrap.classList.remove('d-none');

  tbody.innerHTML = items.map((item, idx) => {
    const sub_lbp_total = Math.round(item.qty * item.cost_price_lbp);
  
    const costChanged = item.current_cost && Math.abs(item.cost_price_lbp - item.current_cost) > 100;
    const lastInfo = item.last_cost
      ? `Last: LL ${Math.round(item.last_cost_lbp || 0).toLocaleString()} on ${item.last_cost ? item.last_cost.received_date : ''}`
      : `Current: LL ${Math.round(item.current_cost).toLocaleString()}`;

    return `
    <tr>
      <td>
        <div class="fw-semibold">${esc(item.product_name)}</div>
        <div class="cost-hint">${esc(lastInfo)}</div>
      </td>
      <td>
        <input type="number" class="form-control form-control-sm" min="0.001" step="0.001"
               value="${item.qty}"
               onchange="updateField(${idx},'qty',this.value)">
        <div class="cost-hint text-center">${esc(item.unit)}</div>
      </td>
      <td>
        <input type="number" class="form-control form-control-sm ${costChanged?'border-danger':''}"
               min="0" step="1" onwheel="this.blur()"
               value="${Math.round(item.cost_price_lbp)}"
               onchange="updateCostLbp(${idx},this.value)">
        ${costChanged ? `<div class="cost-hint cost-changed">⚠ was LL ${Math.round(item.last_cost_lbp).toLocaleString()}</div>` : ''}
      </td>
      <td>
        <input type="date" class="form-control form-control-sm"
               value="${item.expiry_date||''}"
               onchange="updateField(${idx},'expiry_date',this.value)">
      </td>
      <td class="subtotal-cell">
        <div>LL ${sub_lbp_total.toLocaleString()}</div>
      </td>
      <td class="text-center">
        <button class="remove-btn" onclick="removeItem(${idx})"><i class="bi bi-x-circle"></i></button>
      </td>
    </tr>`;
  }).join('');
}

// ── Update fields ──────────────────────────────────────────
function updateField(idx, field, val) {
  items[idx][field] = field === 'qty' ? parseFloat(val)||0 : val;
  renderItems();
  recalcAll();
}

function updateCostLbp(idx, val) {
  items[idx].cost_price_lbp = parseFloat(val) || 0;
  renderItems();
  recalcAll();
}

function removeItem(idx) {
  items.splice(idx, 1);
  renderItems();
  recalcAll();
}

function recalcAll() {
  let totalUsd = 0, totalLbp = 0, totalUnits = 0;
  items.forEach(i => {
    totalLbp   += i.qty * i.cost_price_lbp;
    totalUnits += i.qty;
  });
  document.getElementById('totalUsd').textContent   = 'LL ' + Math.round(totalLbp).toLocaleString();
  document.getElementById('totalLbp').textContent   = '';
  document.getElementById('totalItems').textContent = items.length;
  document.getElementById('totalUnits').textContent = parseFloat(totalUnits.toFixed(3));
}

// ── Save Receiving ─────────────────────────────────────────
async function saveReceiving() {
  if (!items.length) {
    showMsg('error', 'Add at least one product before saving.');
    return;
  }

  const suppSel = document.getElementById('supplierId');
  const payload = {
    supplier_id:    suppSel.value,
    supplier_name:  suppSel.options[suppSel.selectedIndex].text,
    invoice_number: document.getElementById('invoiceNo').value.trim(),
    received_date:  document.getElementById('receivedDate').value,
    notes:          document.getElementById('receivingNotes').value.trim(),
    exchange_rate:  <?= $usd_to_lbp ?>,
    items: items.map(i => ({
      product_id:     i.product_id,
      product_name:   i.product_name,
      qty:            i.qty,
      cost_price_lbp: i.cost_price_lbp,
      cost_price_usd: 0,  // no longer used
      expiry_date:    i.expiry_date || '',
      notes:          i.notes || '',
    }))
  };

  const btn = document.querySelector('.btn-save');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Saving...';

  const res  = await fetch('ajax/pos_receiving_ajax.php?action=save_receiving', {
    method:  'POST',
    headers: {'Content-Type':'application/json'},
    body:    JSON.stringify(payload)
  });
  const data = await res.json();

  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save & Update Stock';

  if (data.error) {
    showMsg('error', data.error);
    return;
  }

  showMsg('success',
    `✓ Receiving #${data.receiving_id} saved. ` +
    `Stock updated for ${items.length} product(s). ` +
    `Total cost: $${data.total_usd} / LL ${parseInt(data.total_lbp).toLocaleString()}`
  );
  clearAll();
  loadHistory();
}

function clearAll() {
  items = [];
  renderItems();
  recalcAll();
  document.getElementById('invoiceNo').value       = '';
  document.getElementById('receivingNotes').value  = '';
  document.getElementById('supplierId').value      = '';
  document.getElementById('receivedDate').value    = '<?= date('Y-m-d') ?>';
  document.getElementById('productSearch').focus();
}

function showMsg(type, text) {
  const el = document.getElementById('saveMsg');
  el.className = type === 'success' ? 'alert alert-success py-2' : 'alert alert-danger py-2';
  el.textContent = text;
  if (type === 'success') setTimeout(() => el.className = '', 6000);
}

// ── History ────────────────────────────────────────────────
async function loadHistory() {
  const from = document.getElementById('hDateFrom').value;
  const to   = document.getElementById('hDateTo').value;
  const sup  = document.getElementById('hSupplier').value;
  const url  = `ajax/pos_receiving_ajax.php?action=list_receivings&date_from=${from}&date_to=${to}&supplier_id=${sup}`;

  const res  = await fetch(url);
  const data = await res.json();
  const list = data.receivings || [];
  const el   = document.getElementById('historyList');

  if (!list.length) {
    const msg = data.warning
      ? `<div class="alert alert-warning m-3 py-2" style="font-size:.83rem;">
           <i class="bi bi-exclamation-triangle me-1"></i>${data.warning}
         </div>`
      : '<div class="text-center text-muted py-4">No receivings in this period</div>';
    el.innerHTML = msg;
    return;
  }

  el.innerHTML = list.map(r => `
    <div class="history-item" onclick="showDetail(${r.id})">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="ref">#${r.id} — ${esc(r.supplier_name||'Walk-in')}</div>
          <div class="meta">
            ${r.received_date}
            ${r.invoice_number ? ` &nbsp;·&nbsp; Inv: ${esc(r.invoice_number)}` : ''}
            &nbsp;·&nbsp; ${r.item_count} item(s)
          </div>
          ${r.notes ? `<div class="meta text-muted">${esc(r.notes)}</div>` : ''}
        </div>
        <div class="text-end">
          <div class="amt">LL ${Math.round(r.total_cost_lbp).toLocaleString()}</div>
        </div>
      </div>
    </div>
  `).join('');
}

// ── Detail Modal ───────────────────────────────────────────
async function showDetail(id) {
  document.getElementById('detailTitle').textContent = `Receiving #${id}`;
  document.getElementById('detailBody').innerHTML    = '<div class="text-center py-4">Loading...</div>';
  detailModal.show();

  const res  = await fetch(`ajax/pos_receiving_ajax.php?action=get_receiving&id=${id}`);
  const data = await res.json();
  if (data.error) {
    document.getElementById('detailBody').innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
    return;
  }

  const r = data.receiving;
  const it = data.items || [];

  const rows = it.map(i => `
    <tr>
      <td>${esc(i.product_name)}</td>
      <td class="text-end">${parseFloat(i.qty_received)}</td>
      <td class="text-end">LL ${Math.round(i.cost_price_lbp).toLocaleString()}</td>
      <td class="text-end">$${parseFloat(i.subtotal_usd).toFixed(2)}</td>
      <td class="text-center">${i.expiry_date || '—'}</td>
      <td>${esc(i.notes||'')}</td>
    </tr>
  `).join('');

  document.getElementById('detailBody').innerHTML = `
    <div class="row g-3 mb-3">
      <div class="col-md-3"><strong>Supplier</strong><br>${esc(r.supplier_name||'Walk-in')}</div>
      <div class="col-md-3"><strong>Invoice #</strong><br>${esc(r.invoice_number||'—')}</div>
      <div class="col-md-2"><strong>Date</strong><br>${r.received_date}</div>
      <div class="col-md-2"><strong>Rate Used</strong><br>LL ${parseFloat(r.exchange_rate).toLocaleString()}</div>
      <div class="col-md-2"><strong>By</strong><br>${esc(r.agent_name||'—')}</div>
    </div>
    ${r.notes ? `<div class="alert alert-light py-2 mb-3"><i class="bi bi-chat-left-text me-1"></i>${esc(r.notes)}</div>` : ''}
    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <thead style="background:#1a2b4a;color:#fff">
          <tr>
            <th>Product</th><th class="text-end">Qty</th>
            <th class="text-end">Cost (LL)</th>
            <th class="text-end">Subtotal</th><th class="text-center">Expiry</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
        <tfoot style="background:#f8fafc;font-weight:700">
          <tr>
            <td colspan="4">TOTAL</td>
            <td colspan="3" class="text-end">LL ${Math.round(r.total_cost_lbp).toLocaleString()}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  `;
}

function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
