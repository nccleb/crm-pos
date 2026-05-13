<?php
/**
 * pos_bundles.php
 * Bundle SKU Manager — NCC CRM POS v4.4
 */
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$is_super   = ($_SESSION['oop'] === 'super');
$agent_name = $_SESSION['oop'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Bundle Manager — NCC POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#f0f2f5;font-family:'Segoe UI',sans-serif;font-size:14px;color:#1a1a2e;}
.topbar{background:linear-gradient(135deg,#1976D2,#0D47A1);color:white;padding:14px 24px;
        display:flex;align-items:center;gap:14px;flex-wrap:wrap;}
.topbar h1{font-size:18px;font-weight:800;}
.topbar a{color:rgba(255,255,255,.85);text-decoration:none;font-size:13px;font-weight:600;
          background:rgba(255,255,255,.15);padding:7px 14px;border-radius:8px;
          display:flex;align-items:center;gap:6px;transition:background .2s;}
.topbar a:hover{background:rgba(255,255,255,.25);}
.topbar .ml{margin-left:auto;font-size:13px;opacity:.8;}
.wrap{max-width:1200px;margin:24px auto;padding:0 20px;}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;}
.page-header h2{font-size:20px;font-weight:800;color:#1e3a5f;display:flex;align-items:center;gap:10px;}
.btn{padding:9px 18px;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;
     display:inline-flex;align-items:center;gap:7px;transition:all .2s;text-decoration:none;}
.btn-primary{background:#1976D2;color:white;} .btn-primary:hover{background:#1565C0;}
.btn-success{background:#10b981;color:white;} .btn-success:hover{background:#059669;}
.btn-danger {background:#ef4444;color:white;} .btn-danger:hover{background:#dc2626;}
.btn-warning{background:#f59e0b;color:white;} .btn-warning:hover{background:#d97706;}
.btn-outline{background:white;color:#1976D2;border:2px solid #1976D2;}
.btn-sm{padding:5px 12px;font-size:12px;border-radius:7px;}
/* Bundle cards */
.bundles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:16px;}
.bundle-card{background:white;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.07);
             overflow:hidden;border:2px solid transparent;transition:all .2s;}
.bundle-card:hover{border-color:#bfdbfe;box-shadow:0 4px 20px rgba(25,118,210,.1);}
.bundle-card.inactive{opacity:.6;}
.bc-header{padding:16px 18px;background:linear-gradient(135deg,#1976D2,#1565C0);color:white;
           display:flex;align-items:flex-start;justify-content:space-between;gap:10px;}
.bc-header .bc-name{font-size:15px;font-weight:800;line-height:1.3;}
.bc-header .bc-price{font-size:18px;font-weight:900;white-space:nowrap;}
.bc-barcode{display:flex;align-items:center;gap:6px;font-size:11px;
            background:rgba(255,255,255,.15);padding:4px 10px;border-radius:20px;margin-top:6px;
            font-family:monospace;font-weight:700;letter-spacing:1px;}
.bc-body{padding:16px 18px;}
.bc-items{display:flex;flex-direction:column;gap:8px;margin-bottom:14px;}
.bc-item{display:flex;align-items:center;gap:10px;background:#f8fafc;border-radius:8px;padding:9px 12px;}
.bc-item-qty{background:#1976D2;color:white;font-size:11px;font-weight:800;
             width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.bc-item-name{flex:1;font-weight:700;font-size:13px;color:#1e3a5f;}
.bc-item-price{font-size:12px;color:#9ca3af;font-weight:600;}
.bc-savings{display:flex;justify-content:space-between;align-items:center;
            background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 12px;margin-bottom:14px;}
.bc-savings .label{font-size:12px;color:#166534;font-weight:600;}
.bc-savings .amount{font-size:14px;font-weight:800;color:#16a34a;}
.bc-actions{display:flex;gap:8px;}
.bc-badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.bc-badge-on {background:#dcfce7;color:#16a34a;}
.bc-badge-off{background:#fee2e2;color:#dc2626;}
/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;
               align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal{background:white;border-radius:16px;width:100%;max-width:680px;max-height:90vh;
       overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);}
.modal-header{padding:20px 24px;border-bottom:2px solid #f0f2f5;display:flex;align-items:center;justify-content:space-between;
              background:linear-gradient(135deg,#1976D2,#1565C0);color:white;border-radius:16px 16px 0 0;}
.modal-header h2{font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px;}
.modal-body{padding:24px;}
.form-row{margin-bottom:16px;}
.form-row label{display:block;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;
                letter-spacing:.5px;margin-bottom:6px;}
.form-row input,.form-row textarea,.form-row select{width:100%;padding:10px 14px;border:2px solid #e5e7eb;
    border-radius:9px;font-size:14px;outline:none;transition:border-color .2s;font-family:inherit;}
.form-row input:focus,.form-row textarea:focus{border-color:#1976D2;}
.form-row textarea{resize:vertical;min-height:60px;}
.form-row .hint{font-size:11px;color:#9ca3af;margin-top:5px;}
.form-row.two-col{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
/* Component builder */
.component-section{background:#f8fafc;border-radius:10px;padding:16px;margin-bottom:16px;}
.component-section h3{font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;
                      letter-spacing:.5px;margin-bottom:12px;display:flex;align-items:center;gap:7px;}
.component-search{display:flex;gap:8px;margin-bottom:12px;}
.component-search input{flex:1;padding:9px 14px;border:2px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;}
.component-search input:focus{border-color:#1976D2;}
.search-results{background:white;border:2px solid #e5e7eb;border-radius:8px;
                max-height:200px;overflow-y:auto;display:none;}
.search-result-item{padding:10px 14px;cursor:pointer;border-bottom:1px solid #f0f2f5;
                    display:flex;align-items:center;justify-content:space-between;transition:background .15s;}
.search-result-item:hover{background:#eff6ff;}
.search-result-item:last-child{border-bottom:none;}
.sri-name{font-weight:700;font-size:13px;color:#1e3a5f;}
.sri-price{font-size:12px;color:#9ca3af;}
.components-list{display:flex;flex-direction:column;gap:8px;}
.component-row{display:flex;align-items:center;gap:10px;background:white;border-radius:8px;
               padding:10px 12px;border:1px solid #e5e7eb;}
.cr-name{flex:1;font-weight:700;font-size:13px;color:#1e3a5f;}
.cr-price{font-size:12px;color:#9ca3af;white-space:nowrap;}
.cr-qty{width:60px;padding:6px 8px;border:2px solid #e5e7eb;border-radius:7px;font-size:13px;
        font-weight:700;text-align:center;outline:none;}
.cr-qty:focus{border-color:#1976D2;}
.cr-remove{width:28px;height:28px;border:none;background:#fee2e2;color:#ef4444;border-radius:7px;
           cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;}
/* Price summary */
.price-summary{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;}
.ps-row{display:flex;justify-content:space-between;align-items:center;font-size:13px;margin-bottom:6px;}
.ps-row:last-child{margin-bottom:0;font-weight:800;font-size:14px;color:#1976D2;
                   border-top:1px solid #bfdbfe;padding-top:8px;margin-top:6px;}
.ps-savings{color:#16a34a;font-weight:700;}
/* Empty state */
.empty-state{text-align:center;padding:60px 20px;color:#9ca3af;}
.empty-state i{font-size:52px;margin-bottom:16px;display:block;opacity:.4;}
/* Toast */
.toast-wrap{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;}
.toast{padding:12px 18px;border-radius:10px;font-size:13px;font-weight:600;min-width:260px;
       display:flex;align-items:center;gap:8px;animation:toastIn .3s ease;box-shadow:0 8px 24px rgba(0,0,0,.15);}
.toast-s{background:#065f46;color:#6ee7b7;}
.toast-e{background:#7f1d1d;color:#fca5a5;}
@keyframes toastIn{from{opacity:0;transform:translateX(60px);}to{opacity:1;transform:translateX(0);}}
</style>
</head>
<body>
<div class="topbar">
    <h1><i class="fas fa-layer-group"></i> Bundle Manager</h1>
    <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="pos_promotions.php"><i class="fas fa-tags"></i> Promotions</a>
    <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
    <a href="pos_reorder.php"><i class="fas fa-truck-loading"></i> Reorder</a>
    <a href="pos_expiry_alerts.php"><i class="fas fa-bell"></i> Expiry Alerts</a>
    <span class="ml"><i class="fas fa-user"></i> <?= htmlspecialchars($agent_name) ?></span>
</div>

<div id="toastWrap" class="toast-wrap"></div>

<div class="wrap">
    <div class="page-header">
        <h2><i class="fas fa-layer-group" style="color:#1976D2;"></i> Bundle Offers</h2>
        <?php if ($is_super): ?>
        <button class="btn btn-primary" onclick="openModal()">
            <i class="fas fa-plus"></i> New Bundle
        </button>
        <?php endif; ?>
    </div>

    <div class="bundles-grid" id="bundlesGrid">
        <div style="text-align:center;padding:40px;color:#9ca3af;grid-column:1/-1;">
            <i class="fas fa-spinner fa-spin" style="font-size:24px;"></i>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)closeModal()">
<div class="modal">
    <div class="modal-header">
        <h2><i class="fas fa-layer-group"></i> <span id="modalTitle">New Bundle</span></h2>
        <button onclick="closeModal()" style="background:rgba(255,255,255,.2);border:none;color:white;width:32px;height:32px;border-radius:8px;font-size:16px;cursor:pointer;">×</button>
    </div>
    <div class="modal-body">
        <input type="hidden" id="editId">

        <div class="form-row two-col">
            <div>
                <label>Bundle Name *</label>
                <input type="text" id="bName" placeholder="e.g. Pepsi Family Pack Offer">
            </div>
            <div>
                <label>Wrapper Barcode *</label>
                <input type="text" id="bBarcode" placeholder="e.g. NCC-B001 or scan the wrapper">
                <div class="hint">Scan or type the barcode printed on the physical wrapper</div>
            </div>
        </div>

        <div class="form-row">
            <label>Description (optional)</label>
            <textarea id="bDescription" placeholder="e.g. 3 × Pepsi 1.5L + 1 × Chips 100g"></textarea>
        </div>

        <!-- Component builder -->
        <div class="component-section">
            <h3><i class="fas fa-boxes"></i> Bundle Components</h3>
            <div class="component-search">
                <input type="text" id="productSearchInput" placeholder="Search product to add..." oninput="searchProducts(this.value)">
            </div>
            <div class="search-results" id="searchResults"></div>
            <div class="components-list" id="componentsList">
                <div style="text-align:center;color:#9ca3af;padding:16px;font-size:13px;">
                    Search and add products above
                </div>
            </div>
        </div>

        <!-- Price summary + bundle price input -->
        <div class="price-summary" id="priceSummary">
            <div class="ps-row"><span>Regular Total:</span><span id="psRegular">LL 0</span></div>
            <div class="ps-row"><span style="color:#16a34a;">You Save:</span><span class="ps-savings" id="psSavings">LL 0</span></div>
            <div class="ps-row">
                <span>Bundle Price (LL):</span>
                <input type="number" id="bPrice" placeholder="0" min="0" step="5000"
                       style="width:160px;padding:6px 10px;border:2px solid #1976D2;border-radius:7px;font-size:14px;font-weight:800;text-align:right;outline:none;"
                       oninput="updatePriceSummary()">
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:20px;">
            <button class="btn btn-success" style="flex:1;" onclick="saveBundle()">
                <i class="fas fa-save"></i> Save Bundle
            </button>
            <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
        </div>
        <div id="modalError" style="color:#ef4444;font-size:13px;font-weight:600;margin-top:10px;display:none;"></div>
    </div>
</div>
</div>

<script>
let bundles     = [];
let components  = []; // [{product_id, product_name, price, unit, qty}]
let searchTimer = null;

// ── Load bundles ───────────────────────────────────────────────────────────
async function loadBundles() {
    const res  = await fetch('ajax/pos_bundle_ajax.php?action=list');
    const data = await res.json();
    bundles = data.bundles || [];
    renderBundles();
}

function renderBundles() {
    const grid = document.getElementById('bundlesGrid');
    if (!bundles.length) {
        grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><i class="fas fa-layer-group"></i><p>No bundles yet. Create your first bundle offer.</p></div>';
        return;
    }
    grid.innerHTML = bundles.map(b => {
        const regularTotal = b.items.reduce((s,i) => s + parseFloat(i.price)*parseFloat(i.qty), 0);
        const savings      = Math.max(0, regularTotal - parseFloat(b.bundle_price));
        return `
        <div class="bundle-card ${b.active==1?'':'inactive'}">
            <div class="bc-header">
                <div>
                    <div class="bc-name">${esc(b.name)}</div>
                    <div class="bc-barcode"><i class="fas fa-barcode"></i> ${esc(b.barcode)}</div>
                    ${b.description ? `<div style="font-size:11px;opacity:.75;margin-top:6px;">${esc(b.description)}</div>` : ''}
                </div>
                <div style="text-align:right;">
                    <div class="bc-price">LL ${fmtNum(b.bundle_price)}</div>
                    <span class="bc-badge ${b.active==1?'bc-badge-on':'bc-badge-off'}">${b.active==1?'Active':'Inactive'}</span>
                </div>
            </div>
            <div class="bc-body">
                <div class="bc-items">
                    ${b.items.map(i => `
                    <div class="bc-item">
                        <div class="bc-item-qty">${parseFloat(i.qty)%1===0?parseInt(i.qty):parseFloat(i.qty)}×</div>
                        <div class="bc-item-name">${esc(i.product_name)}</div>
                        <div class="bc-item-price">LL ${fmtNum(i.price)}/${esc(i.unit||'pc')}</div>
                    </div>`).join('')}
                </div>
                ${savings > 0 ? `
                <div class="bc-savings">
                    <span class="label"><i class="fas fa-tag"></i> Customer Saves</span>
                    <span class="amount">LL ${fmtNum(savings)}</span>
                </div>` : ''}
                <div class="bc-actions">
                    <button class="btn btn-sm" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;"
                        onclick="openPrintModal('${esc(b.barcode).replace(/'/g,"\\'")}','${esc(b.name).replace(/'/g,"\\'")}',${b.bundle_price})">
                        <i class="fas fa-barcode"></i> Print Label
                    </button>
                    <?php if ($is_super): ?>
                    <button class="btn btn-primary btn-sm" onclick="editBundle(${b.id})">
                        <i class="fas fa-pencil-alt"></i> Edit
                    </button>
                    <button class="btn btn-sm" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;"
                        onclick="toggleBundle(${b.id})">
                        <i class="fas fa-${b.active==1?'pause':'play'}"></i> ${b.active==1?'Disable':'Enable'}
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteBundle(${b.id},'${esc(b.name).replace(/'/g,"\\'")}')">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>`;
    }).join('');
}

// ── Modal ──────────────────────────────────────────────────────────────────
function openModal(b = null) {
    components = [];
    document.getElementById('editId').value        = b ? b.id : '';
    document.getElementById('modalTitle').textContent = b ? 'Edit Bundle' : 'New Bundle';
    document.getElementById('bName').value         = b ? b.name : '';
    document.getElementById('bBarcode').value      = b ? b.barcode : '';
    document.getElementById('bDescription').value  = b ? (b.description||'') : '';
    document.getElementById('bPrice').value        = b ? b.bundle_price : '';
    document.getElementById('modalError').style.display = 'none';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('productSearchInput').value = '';

    if (b && b.items) {
        components = b.items.map(i => ({
            product_id: i.product_id, product_name: i.product_name,
            price: parseFloat(i.price), unit: i.unit||'pc', qty: parseFloat(i.qty)
        }));
    }
    renderComponents();
    updatePriceSummary();
    document.getElementById('modalOverlay').classList.add('open');
    setTimeout(() => document.getElementById('bName').focus(), 200);
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
}

function editBundle(id) {
    const b = bundles.find(x => x.id == id);
    if (b) openModal(b);
}

// ── Product search ─────────────────────────────────────────────────────────
function searchProducts(q) {
    clearTimeout(searchTimer);
    if (!q.trim()) { document.getElementById('searchResults').style.display='none'; return; }
    searchTimer = setTimeout(async () => {
        const res  = await fetch('ajax/pos_bundle_ajax.php?action=search_products&q='+encodeURIComponent(q));
        const data = await res.json();
        const box  = document.getElementById('searchResults');
        if (!data.products?.length) { box.style.display='none'; return; }
        box.innerHTML = data.products.map(p => `
            <div class="search-result-item" onclick="addComponent(${p.codep},'${esc(p.nomp).replace(/'/g,"\\'")}',${p.price},'${p.unit||'pc'}')">
                <div>
                    <div class="sri-name">${esc(p.nomp)}</div>
                    <div class="sri-price">${esc(p.category||'')} · ${esc(p.unit||'pc')} · Stock: ${p.onhand}</div>
                </div>
                <div class="sri-price" style="font-weight:700;color:#1976D2;">LL ${fmtNum(p.price)}</div>
            </div>
        `).join('');
        box.style.display = 'block';
    }, 250);
}

function addComponent(productId, productName, price, unit) {
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('productSearchInput').value = '';
    const existing = components.find(c => c.product_id == productId);
    if (existing) { existing.qty++; }
    else { components.push({product_id:productId, product_name:productName, price:parseFloat(price), unit, qty:1}); }
    renderComponents();
    updatePriceSummary();
}

function removeComponent(idx) {
    components.splice(idx, 1);
    renderComponents();
    updatePriceSummary();
}

function updateQty(idx, val) {
    components[idx].qty = Math.max(0.001, parseFloat(val)||1);
    updatePriceSummary();
}

function renderComponents() {
    const list = document.getElementById('componentsList');
    if (!components.length) {
        list.innerHTML = '<div style="text-align:center;color:#9ca3af;padding:16px;font-size:13px;">Search and add products above</div>';
        return;
    }
    list.innerHTML = components.map((c,i) => `
        <div class="component-row">
            <div class="bc-item-qty" style="background:#1976D2;color:white;font-size:11px;font-weight:800;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">${i+1}</div>
            <div class="cr-name">${esc(c.product_name)}</div>
            <div class="cr-price">LL ${fmtNum(c.price)}/${esc(c.unit)}</div>
            <input type="number" class="cr-qty" value="${c.qty}" min="0.001" step="0.001"
                   onchange="updateQty(${i},this.value)" title="Quantity">
            <button class="cr-remove" onclick="removeComponent(${i})"><i class="fas fa-times"></i></button>
        </div>
    `).join('');
}

function updatePriceSummary() {
    const regular = components.reduce((s,c) => s + c.price * c.qty, 0);
    const bundle  = parseFloat(document.getElementById('bPrice').value) || 0;
    const savings = Math.max(0, regular - bundle);
    document.getElementById('psRegular').textContent = 'LL ' + fmtNum(regular);
    document.getElementById('psSavings').textContent = 'LL ' + fmtNum(savings);
    document.getElementById('psSavings').style.color = savings > 0 ? '#16a34a' : '#9ca3af';
}

// ── Save bundle ────────────────────────────────────────────────────────────
async function saveBundle() {
    const err = document.getElementById('modalError');
    err.style.display = 'none';

    const id          = document.getElementById('editId').value;
    const name        = document.getElementById('bName').value.trim();
    const barcode     = document.getElementById('bBarcode').value.trim();
    const price       = parseFloat(document.getElementById('bPrice').value);
    const description = document.getElementById('bDescription').value.trim();

    if (!name)           { showErr('Bundle name is required'); return; }
    if (!barcode)        { showErr('Barcode is required'); return; }
    if (!price || price <= 0) { showErr('Enter the bundle price'); return; }
    if (!components.length)  { showErr('Add at least one product component'); return; }

    const body = new FormData();
    body.append('action',       'save');
    body.append('id',           id);
    body.append('name',         name);
    body.append('barcode',      barcode);
    body.append('bundle_price', price);
    body.append('description',  description);
    body.append('active',       '1');
    body.append('items',        JSON.stringify(components.map(c => ({product_id:c.product_id, qty:c.qty}))));

    const res  = await fetch('ajax/pos_bundle_ajax.php', {method:'POST', body});
    const data = await res.json();

    if (data.success) {
        toast('Bundle saved ✓', 's');
        closeModal();
        loadBundles();
    } else {
        showErr(data.error || 'Failed to save');
    }
}

function showErr(msg) {
    const el = document.getElementById('modalError');
    el.textContent = '⚠ ' + msg;
    el.style.display = 'block';
}

// ── Toggle / Delete ────────────────────────────────────────────────────────
async function toggleBundle(id) {
    const body = new FormData(); body.append('action','toggle'); body.append('id',id);
    const res  = await fetch('ajax/pos_bundle_ajax.php',{method:'POST',body});
    const data = await res.json();
    if (data.success) { loadBundles(); toast(data.active ? 'Bundle enabled' : 'Bundle disabled', 's'); }
}

async function deleteBundle(id, name) {
    if (!confirm(`Delete bundle "${name}"? This cannot be undone.`)) return;
    const body = new FormData(); body.append('action','delete'); body.append('id',id);
    const res  = await fetch('ajax/pos_bundle_ajax.php',{method:'POST',body});
    const data = await res.json();
    if (data.success) { loadBundles(); toast('Bundle deleted', 's'); }
    else toast(data.error || 'Error', 'e');
}

// ── Helpers ────────────────────────────────────────────────────────────────
function fmtNum(n) { return Math.round(n).toLocaleString('en'); }
function esc(s)    { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function toast(msg, type) {
    const w = document.getElementById('toastWrap');
    const t = document.createElement('div');
    t.className = 'toast toast-' + type;
    t.textContent = msg;
    w.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// Close search results when clicking outside
document.addEventListener('click', e => {
    if (!e.target.closest('.component-search') && !e.target.closest('#searchResults')) {
        document.getElementById('searchResults').style.display = 'none';
    }
});

loadBundles();
</script>
<!-- Print Label Modal -->
<div class="modal-overlay" id="printOverlay" onclick="if(event.target===this)closePrint()">
<div class="modal" style="max-width:440px;">
    <div class="modal-header" style="background:linear-gradient(135deg,#166534,#15803d);">
        <h2><i class="fas fa-barcode"></i> Print Bundle Label</h2>
        <button onclick="closePrint()" style="background:rgba(255,255,255,.2);border:none;color:white;width:32px;height:32px;border-radius:8px;font-size:16px;cursor:pointer;">×</button>
    </div>
    <div class="modal-body">
        <!-- Preview -->
        <div style="text-align:center;background:#f8fafc;border-radius:10px;padding:20px;margin-bottom:18px;border:1px solid #e5e7eb;">
            <svg id="bundleBarcodesvg"></svg>
            <div id="printBundleName" style="font-size:13px;font-weight:700;color:#1e3a5f;margin-top:8px;"></div>
            <div id="printBundlePrice" style="font-size:12px;color:#1976D2;font-weight:800;margin-top:3px;"></div>
        </div>
        <!-- Controls -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Size</label>
                <select id="printSize" onchange="updateBundleBarcode()" style="width:100%;padding:9px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                    <option value="small">Small (50×25mm)</option>
                    <option value="medium" selected>Medium (80×40mm)</option>
                    <option value="large">Large (100×50mm)</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Copies</label>
                <input type="number" id="printCopies" value="1" min="1" max="100"
                       style="width:100%;padding:9px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:13px;font-weight:700;outline:none;">
            </div>
        </div>
        <button onclick="doPrintBundle()" style="width:100%;padding:13px;background:linear-gradient(135deg,#166534,#15803d);color:white;border:none;border-radius:10px;font-size:14px;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
            <i class="fas fa-print"></i> Print Labels
        </button>
    </div>
</div>
</div>

<!-- Hidden print frame -->
<div id="printFrame" style="display:none;"></div>

<!-- JsBarcode -->
<script src="jsbarcode.min.js"></script>

<script>
var _printBarcode = '', _printName = '', _printPrice = 0;

function openPrintModal(barcode, name, price) {
    _printBarcode = barcode;
    _printName    = name;
    _printPrice   = price;
    document.getElementById('printBundleName').textContent  = name;
    document.getElementById('printBundlePrice').textContent = 'LL ' + Math.round(price).toLocaleString();
    document.getElementById('printOverlay').classList.add('open');
    updateBundleBarcode();
}

function closePrint() {
    document.getElementById('printOverlay').classList.remove('open');
}

function updateBundleBarcode() {
    if (!_printBarcode) return;
    var size   = document.getElementById('printSize').value;
    var widths = {small:1.2, medium:2, large:2.8};
    var heights = {small:40, medium:70, large:90};
    try {
        JsBarcode('#bundleBarcodesvg', _printBarcode, {
            format:       'CODE128',
            width:        widths[size],
            height:       heights[size],
            displayValue: true,
            fontSize:     13,
            margin:       8,
            background:   '#ffffff',
            lineColor:    '#000000'
        });
    } catch(e) { console.warn('Barcode error:', e); }
}

function doPrintBundle() {
    var copies = parseInt(document.getElementById('printCopies').value) || 1;
    var size   = document.getElementById('printSize').value;
    var dims   = {small:'50mm 25mm', medium:'80mm 40mm', large:'100mm 50mm'};
    var widths = {small:1.2, medium:2, large:2.8};
    var heights= {small:40, medium:70, large:90};

    // Generate SVG barcode as string
    var svgEl = document.createElementNS('http://www.w3.org/2000/svg','svg');
    svgEl.setAttribute('id','_tmpsvg');
    document.body.appendChild(svgEl);
    try {
        JsBarcode('#_tmpsvg', _printBarcode, {
            format:'CODE128', width:widths[size], height:heights[size],
            displayValue:true, fontSize:13, margin:8,
            background:'#ffffff', lineColor:'#000000'
        });
    } catch(e) {}
    var svgStr = svgEl.outerHTML;
    svgEl.remove();

    var label = '<div style="text-align:center;page-break-inside:avoid;margin:4px;">'
        + svgStr
        + '<div style="font-size:11px;font-weight:700;font-family:Arial;">' + _printName + '</div>'
        + '<div style="font-size:12px;font-weight:900;font-family:Arial;color:#1976D2;">LL ' + Math.round(_printPrice).toLocaleString() + '</div>'
        + '</div>';

    var labels = '';
    for (var i = 0; i < copies; i++) labels += label;

    var win = window.open('','_blank','width=600,height=400');
    win.document.write('<html><head><title>Bundle Label</title>'
        + '<style>@page{size:' + dims[size] + ';margin:2mm;}body{margin:0;padding:0;}</style>'
        + '</head><body>' + labels + '</body></html>');
    win.document.close();
    win.focus();
    setTimeout(function(){ win.print(); win.close(); }, 400);
}
</script>
</body>
</html>
