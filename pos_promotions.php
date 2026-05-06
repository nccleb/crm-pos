<?php
// ============================================================
// NCC CRM POS — Promotions Manager
// pos_promotions.php
// ============================================================
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);
$is_super   = ($agent_name === 'super');

$conn = mysqli_connect("172.18.208.1","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');
$usd_to_lbp = (float)(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT usd_to_lbp FROM company_settings LIMIT 1"))['usd_to_lbp'] ?? 89700);
$categories_res = mysqli_query($conn, "SELECT name FROM pos_categories ORDER BY name");
$categories = [];
while ($c = mysqli_fetch_assoc($categories_res)) $categories[] = $c['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Promotions — NCC POS</title>
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
.container { max-width:1300px; margin:0 auto; padding:22px 18px; }
.card { background:#fff; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.08);
        overflow:hidden; margin-bottom:20px; }
.card-header { background:#1e3a5f; color:#fff; padding:14px 20px;
               display:flex; align-items:center; justify-content:space-between; }
.card-header h2 { font-size:15px; }
.card-body { padding:20px; }
.btn { padding:.45rem 1.1rem; border-radius:7px; border:none; cursor:pointer;
       font-size:.84rem; font-weight:600; display:inline-flex; align-items:center; gap:5px; }
.btn-primary { background:#1976D2; color:#fff; }
.btn-primary:hover { background:#1565c0; }
.btn-success { background:#16a34a; color:#fff; }
.btn-success:hover { background:#15803d; }
.btn-danger  { background:#dc2626; color:#fff; }
.btn-danger:hover  { background:#b91c1c; }
.btn-sm { padding:.3rem .7rem; font-size:.78rem; }
/* Type cards */
.type-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
.type-card { border:2px solid #e2e8f0; border-radius:10px; padding:14px;
             cursor:pointer; transition:all .15s; text-align:center; }
.type-card:hover { border-color:#1976D2; background:#f0f7ff; }
.type-card.selected { border-color:#1976D2; background:#dbeafe; }
.type-card .icon { font-size:1.8rem; margin-bottom:6px; }
.type-card .label { font-weight:700; font-size:.88rem; color:#1e293b; }
.type-card .sub { font-size:.75rem; color:#64748b; margin-top:2px; }
/* Form fields */
.form-row { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:12px; }
.form-group { display:flex; flex-direction:column; gap:4px; }
.form-group label { font-size:.78rem; font-weight:700; color:#374151; text-transform:uppercase; }
.form-group input, .form-group select { padding:.45rem .8rem; border:1.5px solid #e2e8f0;
    border-radius:7px; font-size:.88rem; }
.form-group input:focus, .form-group select:focus { outline:none; border-color:#1976D2; }
.form-group.full { grid-column:span 3; }
.form-group.half { grid-column:span 1; }
/* Promo table */
table { width:100%; border-collapse:collapse; font-size:.83rem; }
th { padding:10px 14px; background:#1e3a5f; color:#fff; font-weight:700; text-align:left; }
td { padding:10px 14px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
tr:hover td { background:#fafafa; }
.badge { display:inline-block; padding:3px 10px; border-radius:20px;
         font-size:.73rem; font-weight:700; white-space:nowrap; }
.badge-pct    { background:#dbeafe; color:#1e40af; }
.badge-bogo   { background:#dcfce7; color:#166534; }
.badge-bundle { background:#fde68a; color:#92400e; }
.badge-fixed  { background:#ede9fe; color:#5b21b6; }
.badge-active { background:#dcfce7; color:#166534; }
.badge-off    { background:#f3f4f6; color:#6b7280; }
/* Bundle builder */
.bundle-row { display:flex; gap:8px; align-items:center; margin-bottom:8px; }
.bundle-row input { flex:1; padding:.4rem .7rem; border:1.5px solid #e2e8f0;
                    border-radius:7px; font-size:.85rem; }
.bundle-row .qty-in { width:60px; flex:none; }
.remove-bundle { background:#fee2e2; color:#dc2626; border:none; border-radius:6px;
                 padding:.35rem .6rem; cursor:pointer; font-size:.8rem; }
/* Product search dropdown */
.product-search-wrap { position:relative; }
.product-search-results { position:absolute; top:100%; left:0; right:0; background:#fff;
    border:1.5px solid #e2e8f0; border-radius:0 0 8px 8px; max-height:200px;
    overflow-y:auto; z-index:100; display:none; box-shadow:0 4px 12px rgba(0,0,0,.1); }
.ps-item { padding:.5rem .8rem; cursor:pointer; font-size:.84rem; }
.ps-item:hover { background:#f0f7ff; }
/* Toggle switch */
.toggle { position:relative; display:inline-block; width:42px; height:22px; }
.toggle input { opacity:0; width:0; height:0; }
.slider { position:absolute; inset:0; background:#ccc; border-radius:22px;
          cursor:pointer; transition:.3s; }
.slider:before { position:absolute; content:""; height:16px; width:16px; left:3px;
                 bottom:3px; background:#fff; border-radius:50%; transition:.3s; }
input:checked + .slider { background:#16a34a; }
input:checked + .slider:before { transform:translateX(20px); }
/* Promo highlight for active promo */
.promo-tag { background:#fef9c3; border:1px solid #fcd34d; color:#92400e;
             font-size:.72rem; font-weight:700; padding:1px 7px; border-radius:10px;
             display:inline-block; margin-left:4px; }
.empty { text-align:center; padding:3rem; color:#9ca3af; }
.empty i { font-size:2.5rem; display:block; margin-bottom:.5rem; }
</style>
</head>
<body>

<div class="topbar">
  <i class="fas fa-tags fa-lg"></i>
  <h1>Promotions & Offers</h1>
  <div class="ml">
    <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
    <a href="pos_receiving.php"><i class="fas fa-truck-loading"></i> Receiving</a>
    <a href="pos_expiry.php"><i class="fas fa-calendar-times"></i> Expiry</a>
    <a href="pos_sales.php"><i class="fas fa-history"></i> Sales</a>
    <?php if($is_super):?><a href="pos_settings.php"><i class="fas fa-cog"></i> Settings</a><?php endif;?>
    <a href="test204.php?page=<?=urlencode($agent_name)?>&page1=<?=$agent_id?>"><i class="fas fa-arrow-left"></i> CRM</a>
  </div>
</div>

<div class="container">

  <!-- Add / Edit Card -->
  <div class="card">
    <div class="card-header">
      <h2 id="formTitle"><i class="fas fa-plus-circle"></i> Add New Promotion</h2>
    </div>
    <div class="card-body">

      <!-- Type selector -->
      <div style="font-size:.78rem;font-weight:700;color:#374151;text-transform:uppercase;margin-bottom:8px;">
        Promotion Type
      </div>
      <div class="type-grid">
        <div class="type-card selected" onclick="selectType('percentage',this)">
          <div class="icon">%</div>
          <div class="label">Percentage Off</div>
          <div class="sub">10% off dairy this week</div>
        </div>
        <div class="type-card" onclick="selectType('bogo',this)">
          <div class="icon">🎁</div>
          <div class="label">Buy X Get Y Free</div>
          <div class="sub">Buy 2 chips, get 1 free</div>
        </div>
        <div class="type-card" onclick="selectType('bundle',this)">
          <div class="icon">📦</div>
          <div class="label">Bundle Price</div>
          <div class="sub">Water + juice = LL 50,000</div>
        </div>
      </div>

      <input type="hidden" id="promoType" value="percentage">
      <input type="hidden" id="promoId" value="0">

      <!-- Common fields -->
      <div class="form-row">
        <div class="form-group full">
          <label>Promotion Name *</label>
          <input type="text" id="promoName" placeholder="e.g. Summer Dairy Sale, Chips Weekend Offer">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Valid From</label>
          <input type="date" id="promoFrom">
        </div>
        <div class="form-group">
          <label>Valid Until</label>
          <input type="date" id="promoTo">
        </div>
        <div class="form-group" id="applyToGroup">
          <label>Apply To</label>
          <select id="applyTo" onchange="onApplyToChange()">
            <option value="product">Specific Product</option>
            <option value="category">Entire Category</option>
          </select>
        </div>
      </div>

      <!-- Apply to: Product -->
      <div id="productSelector" class="form-row">
        <div class="form-group full product-search-wrap">
          <label>Product</label>
          <input type="text" id="productSearch" placeholder="Type product name to search..."
                 oninput="searchProducts()" autocomplete="off">
          <div class="product-search-results" id="productResults"></div>
          <input type="hidden" id="selectedProductId">
          <input type="hidden" id="selectedProductName">
        </div>
      </div>

      <!-- Apply to: Category -->
      <div id="categorySelector" class="form-row" style="display:none;">
        <div class="form-group full">
          <label>Category</label>
          <select id="categorySelect">
            <option value="">— Select Category —</option>
            <?php foreach($categories as $c): ?>
            <option value="<?=htmlspecialchars($c)?>"><?=htmlspecialchars($c)?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- PERCENTAGE fields -->
      <div id="pctFields" class="form-row">
        <div class="form-group">
          <label>Discount %</label>
          <input type="number" id="discountPct" min="1" max="100" placeholder="e.g. 10">
        </div>
      </div>

      <!-- BOGO fields -->
      <div id="bogoFields" class="form-row" style="display:none;">
        <div class="form-group">
          <label>Customer Buys (qty)</label>
          <input type="number" id="buyQty" min="1" value="2" placeholder="e.g. 2">
        </div>
        <div class="form-group">
          <label>Customer Gets Free (qty)</label>
          <input type="number" id="freeQty" min="1" value="1" placeholder="e.g. 1">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;">
          <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;font-size:.85rem;color:#166534;font-weight:600;width:100%;">
            Buy <span id="bogoSummaryBuy">2</span>, get <span id="bogoSummaryFree">1</span> free
          </div>
        </div>
      </div>

      <!-- BUNDLE fields -->
      <div id="bundleFields" style="display:none;margin-bottom:12px;">
        <div style="font-size:.78rem;font-weight:700;color:#374151;text-transform:uppercase;margin-bottom:8px;">
          Bundle Products (minimum 2)
        </div>
        <div id="bundleItems"></div>
        <button type="button" class="btn btn-sm" style="background:#e8f0fe;color:#1976D2;margin-top:4px;"
                onclick="addBundleRow()">
          <i class="fas fa-plus"></i> Add Product to Bundle
        </button>
        <div class="form-row" style="margin-top:12px;">
          <div class="form-group">
            <label>Bundle Fixed Price (LL)</label>
            <input type="number" id="bundlePrice" min="0" step="1000" placeholder="e.g. 50000"
                   onwheel="this.blur()">
          </div>
        </div>
      </div>

      <div id="formError" class="alert" style="display:none;background:#fee2e2;color:#991b1b;
           border-left:3px solid #dc2626;padding:8px 12px;border-radius:6px;margin-bottom:12px;"></div>

      <div style="display:flex;gap:10px;">
        <button class="btn btn-primary" onclick="savePromo()">
          <i class="fas fa-save"></i> Save Promotion
        </button>
        <button class="btn" style="background:#e2e8f0;color:#475569;" onclick="resetForm()">
          <i class="fas fa-times"></i> Cancel
        </button>
      </div>
    </div>
  </div>

  <!-- Promotions List -->
  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-list"></i> All Promotions</h2>
      <span id="promoCount" style="font-size:.8rem;opacity:.7;"></span>
    </div>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Applies To</th>
            <th>Details</th>
            <th>Valid Period</th>
            <th>Status</th>
            <th style="width:120px">Actions</th>
          </tr>
        </thead>
        <tbody id="promoBody">
          <tr><td colspan="7"><div class="empty"><i class="fas fa-tags"></i>Loading...</div></td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
const AJAX = 'ajax/pos_promotions_ajax.php';
let promoType = 'percentage';
let bundleCount = 0;
let productSearchTimer = null;
let allPromos = [];

// ── Type selection ─────────────────────────────────────────
function selectType(type, el) {
    promoType = type;
    document.getElementById('promoType').value = type;
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');

    document.getElementById('pctFields').style.display    = type === 'percentage' ? '' : 'none';
    document.getElementById('bogoFields').style.display   = type === 'bogo'       ? '' : 'none';
    document.getElementById('bundleFields').style.display = type === 'bundle'     ? '' : 'none';

    // Bundle always uses product selector; for others allow category
    document.getElementById('applyToGroup').style.display = type === 'bundle' ? 'none' : '';
    if (type === 'bundle') {
        document.getElementById('productSelector').style.display = 'none';
        document.getElementById('categorySelector').style.display = 'none';
        if (document.querySelectorAll('.bundle-row').length === 0) {
            addBundleRow(); addBundleRow();
        }
    } else {
        onApplyToChange();
    }
}

function onApplyToChange() {
    const v = document.getElementById('applyTo').value;
    document.getElementById('productSelector').style.display  = v === 'product'  ? '' : 'none';
    document.getElementById('categorySelector').style.display = v === 'category' ? '' : 'none';
}

// ── BOGO summary ───────────────────────────────────────────
document.getElementById('buyQty').addEventListener('input', updateBogoSummary);
document.getElementById('freeQty').addEventListener('input', updateBogoSummary);
function updateBogoSummary() {
    document.getElementById('bogoSummaryBuy').textContent  = document.getElementById('buyQty').value  || '?';
    document.getElementById('bogoSummaryFree').textContent = document.getElementById('freeQty').value || '?';
}

// ── Product search ─────────────────────────────────────────
function searchProducts(inputEl, resultEl, onSelect) {
    // Generic search — used for main form and bundle rows
    const q = inputEl.value.trim();
    if (q.length < 1) { resultEl.style.display = 'none'; return; }
    clearTimeout(productSearchTimer);
    productSearchTimer = setTimeout(async () => {
        const res  = await fetch(`${AJAX}?action=search_products&q=${encodeURIComponent(q)}`);
        const data = await res.json();
        const list = data.products || [];
        if (!list.length) { resultEl.innerHTML = '<div class="ps-item text-muted">No products found</div>'; }
        else {
            resultEl.innerHTML = list.map(p =>
                `<div class="ps-item" data-id="${p.id}" data-name="${esc(p.name)}"
                      data-price="${p.price}" data-cat="${esc(p.category||'')}">
                    <strong>${esc(p.name)}</strong>
                    <span style="color:#94a3b8;font-size:.78rem;"> — ${esc(p.category||'')}</span>
                </div>`).join('');
            resultEl.querySelectorAll('.ps-item').forEach(item => {
                item.addEventListener('click', () => {
                    onSelect(item.dataset.id, item.dataset.name, item.dataset.price);
                    resultEl.style.display = 'none';
                });
            });
        }
        resultEl.style.display = 'block';
    }, 200);
}

// Main product search
document.getElementById('productSearch').addEventListener('input', () => {
    searchProducts(
        document.getElementById('productSearch'),
        document.getElementById('productResults'),
        (id, name) => {
            document.getElementById('selectedProductId').value   = id;
            document.getElementById('selectedProductName').value = name;
            document.getElementById('productSearch').value       = name;
        }
    );
});

document.addEventListener('click', e => {
    if (!e.target.closest('.product-search-wrap')) {
        document.querySelectorAll('.product-search-results').forEach(r => r.style.display = 'none');
    }
});

// ── Bundle rows ────────────────────────────────────────────
function addBundleRow(id='', name='', qty=1) {
    bundleCount++;
    const idx = bundleCount;
    const div = document.createElement('div');
    div.className = 'bundle-row';
    div.id = `bundle-row-${idx}`;
    div.innerHTML = `
        <div class="product-search-wrap" style="flex:1;">
            <input type="text" placeholder="Search product..." value="${esc(name)}"
                   id="bsearch-${idx}" autocomplete="off"
                   oninput="bundleSearch(${idx})">
            <div class="product-search-results" id="bresults-${idx}"></div>
            <input type="hidden" id="bid-${idx}"   value="${id}">
            <input type="hidden" id="bname-${idx}" value="${esc(name)}">
        </div>
        <input type="number" class="qty-in" id="bqty-${idx}" value="${qty}" min="1" placeholder="Qty">
        <button type="button" class="remove-bundle" onclick="removeBundleRow(${idx})">
            <i class="fas fa-times"></i>
        </button>`;
    document.getElementById('bundleItems').appendChild(div);
}

function bundleSearch(idx) {
    searchProducts(
        document.getElementById(`bsearch-${idx}`),
        document.getElementById(`bresults-${idx}`),
        (id, name) => {
            document.getElementById(`bid-${idx}`).value   = id;
            document.getElementById(`bname-${idx}`).value = name;
            document.getElementById(`bsearch-${idx}`).value = name;
        }
    );
}

function removeBundleRow(idx) {
    document.getElementById(`bundle-row-${idx}`)?.remove();
}

// ── Save promotion ─────────────────────────────────────────
async function savePromo() {
    const type = promoType;
    const name = document.getElementById('promoName').value.trim();
    if (!name) { showError('Please enter a promotion name.'); return; }

    const body = new FormData();
    body.append('action', 'save');
    body.append('id',     document.getElementById('promoId').value);
    body.append('name',   name);
    body.append('type',   type);
    body.append('date_from', document.getElementById('promoFrom').value);
    body.append('date_to',   document.getElementById('promoTo').value);

    if (type === 'percentage') {
        const pct = parseFloat(document.getElementById('discountPct').value);
        if (!pct || pct < 1 || pct > 100) { showError('Enter a valid discount % (1–100).'); return; }
        body.append('apply_to',       document.getElementById('applyTo').value);
        body.append('discount_value', pct);
        if (document.getElementById('applyTo').value === 'product') {
            const pid = document.getElementById('selectedProductId').value;
            if (!pid) { showError('Please select a product.'); return; }
            body.append('product_id',   pid);
            body.append('product_name', document.getElementById('selectedProductName').value);
        } else {
            const cat = document.getElementById('categorySelect').value;
            if (!cat) { showError('Please select a category.'); return; }
            body.append('category', cat);
        }

    } else if (type === 'bogo') {
        const pid = document.getElementById('selectedProductId').value;
        if (!pid) { showError('Please select a product for the BOGO offer.'); return; }
        body.append('apply_to',   'product');
        body.append('product_id',   pid);
        body.append('product_name', document.getElementById('selectedProductName').value);
        body.append('buy_qty',  document.getElementById('buyQty').value);
        body.append('free_qty', document.getElementById('freeQty').value);

    } else if (type === 'bundle') {
        // Collect bundle rows
        document.querySelectorAll('.bundle-row').forEach(row => {
            const idx  = row.id.replace('bundle-row-','');
            const bid  = document.getElementById(`bid-${idx}`)?.value;
            const bnm  = document.getElementById(`bname-${idx}`)?.value;
            const bqty = document.getElementById(`bqty-${idx}`)?.value;
            if (bid) {
                body.append('bundle_product_ids[]',   bid);
                body.append('bundle_product_names[]', bnm);
                body.append('bundle_product_qtys[]',  bqty);
            }
        });
        const bp = parseFloat(document.getElementById('bundlePrice').value);
        if (!bp || bp < 1) { showError('Enter a valid bundle price in LBP.'); return; }
        body.append('bundle_price', bp);
    }

    const res  = await fetch(AJAX, { method:'POST', body });
    const data = await res.json();
    if (data.error) { showError(data.error); return; }
    resetForm();
    loadPromos();
}

function showError(msg) {
    const el = document.getElementById('formError');
    el.textContent = msg;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 4000);
}

// ── Reset form ─────────────────────────────────────────────
function resetForm() {
    document.getElementById('promoId').value     = '0';
    document.getElementById('promoName').value   = '';
    document.getElementById('promoFrom').value   = '';
    document.getElementById('promoTo').value     = '';
    document.getElementById('discountPct').value = '';
    document.getElementById('buyQty').value      = '2';
    document.getElementById('freeQty').value     = '1';
    document.getElementById('bundlePrice').value = '';
    document.getElementById('bundleItems').innerHTML = '';
    bundleCount = 0;
    document.getElementById('productSearch').value        = '';
    document.getElementById('selectedProductId').value    = '';
    document.getElementById('selectedProductName').value  = '';
    document.getElementById('formTitle').innerHTML =
        '<i class="fas fa-plus-circle"></i> Add New Promotion';
    document.querySelectorAll('.type-card')[0].click();
    document.getElementById('formError').style.display = 'none';
}

// ── Load promos list ───────────────────────────────────────
async function loadPromos() {
    const res  = await fetch(`${AJAX}?action=list`);
    const data = await res.json();
    allPromos  = data.promotions || [];
    document.getElementById('promoCount').textContent =
        `${allPromos.length} promotion${allPromos.length !== 1 ? 's' : ''}`;
    renderPromos(allPromos);
}

function renderPromos(list) {
    const tbody = document.getElementById('promoBody');
    if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty">
            <i class="fas fa-tags"></i>No promotions yet. Add one above.</div></td></tr>`;
        return;
    }
    const typeLabels = {
        percentage: ['badge-pct',    'Percentage Off'],
        bogo:       ['badge-bogo',   'Buy X Get Y Free'],
        bundle:     ['badge-bundle', 'Bundle Price'],
        fixed_amount:['badge-fixed', 'Fixed Discount'],
    };
    tbody.innerHTML = list.map(p => {
        const [badgeCls, typeLabel] = typeLabels[p.type] || ['badge-pct','Unknown'];
        let appliesTo = '—';
        if (p.apply_to === 'category' && p.category) appliesTo = `Category: <b>${esc(p.category)}</b>`;
        else if (p.product_name) appliesTo = esc(p.product_name);
        else if (p.type === 'bundle') appliesTo = 'Bundle';

        let details = '—';
        if (p.type === 'percentage') details = `<b>${p.discount_value}%</b> off`;
        else if (p.type === 'bogo')  details = `Buy <b>${p.buy_qty}</b>, get <b>${p.free_qty}</b> free`;
        else if (p.type === 'bundle'){
            const items = p.bundle_items ? JSON.parse(p.bundle_items) : [];
            details = items.map(i => `${i.qty}× ${esc(i.product_name)}`).join(' + ') +
                      (p.bundle_price ? ` = LL ${parseFloat(p.bundle_price).toLocaleString()}` : '');
        }
        else if (p.type === 'fixed_amount') details = `LL <b>${parseFloat(p.discount_value).toLocaleString()}</b> off`;

        const period = p.date_from
            ? `${p.date_from} → ${p.date_to || 'open'}`
            : 'Always active';

        const today = new Date().toISOString().split('T')[0];
        const expired = p.date_to && p.date_to < today;

        return `<tr>
            <td><strong>${esc(p.name)}</strong>${expired ? '<span class="promo-tag">EXPIRED</span>' : ''}</td>
            <td><span class="badge ${badgeCls}">${typeLabel}</span></td>
            <td>${appliesTo}</td>
            <td>${details}</td>
            <td style="font-size:.8rem;color:#64748b;">${period}</td>
            <td>
                <label class="toggle">
                    <input type="checkbox" ${p.active==1?'checked':''} onchange="togglePromo(${p.id},this.checked)">
                    <span class="slider"></span>
                </label>
                <span style="font-size:.75rem;color:#64748b;margin-left:4px;">
                    ${p.active==1?'<span style="color:#16a34a;">ON</span>':'OFF'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editPromo(${p.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deletePromo(${p.id},'${esc(p.name)}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

async function togglePromo(id, active) {
    const body = new FormData();
    body.append('action', 'toggle');
    body.append('id',     id);
    body.append('active', active ? 1 : 0);
    await fetch(AJAX, { method:'POST', body });
    loadPromos();
}

async function deletePromo(id, name) {
    if (!confirm(`Delete promotion "${name}"?`)) return;
    const body = new FormData();
    body.append('action', 'delete');
    body.append('id', id);
    await fetch(AJAX, { method:'POST', body });
    loadPromos();
}

function editPromo(id) {
    const p = allPromos.find(x => x.id == id);
    if (!p) return;
    document.getElementById('promoId').value   = p.id;
    document.getElementById('promoName').value = p.name;
    document.getElementById('promoFrom').value = p.date_from || '';
    document.getElementById('promoTo').value   = p.date_to   || '';

    // Select type
    const typeMap = {percentage:0, bogo:1, bundle:2};
    const typeIdx = typeMap[p.type] ?? 0;
    document.querySelectorAll('.type-card')[typeIdx].click();

    if (p.type === 'percentage') {
        document.getElementById('discountPct').value = p.discount_value;
        document.getElementById('applyTo').value = p.apply_to;
        onApplyToChange();
        if (p.apply_to === 'product' && p.product_name) {
            document.getElementById('productSearch').value       = p.product_name;
            document.getElementById('selectedProductId').value   = p.product_id;
            document.getElementById('selectedProductName').value = p.product_name;
        }
        if (p.apply_to === 'category') document.getElementById('categorySelect').value = p.category;
    } else if (p.type === 'bogo') {
        document.getElementById('buyQty').value  = p.buy_qty;
        document.getElementById('freeQty').value = p.free_qty;
        if (p.product_name) {
            document.getElementById('productSearch').value       = p.product_name;
            document.getElementById('selectedProductId').value   = p.product_id;
            document.getElementById('selectedProductName').value = p.product_name;
        }
        updateBogoSummary();
    } else if (p.type === 'bundle') {
        document.getElementById('bundlePrice').value = p.bundle_price || '';
        const items = p.bundle_items ? JSON.parse(p.bundle_items) : [];
        items.forEach(i => addBundleRow(i.product_id, i.product_name, i.qty));
    }

    document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Promotion';
    window.scrollTo({top:0, behavior:'smooth'});
}

function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/'/g,'&#39;');
}

loadPromos();
</script>
</body>
</html>
