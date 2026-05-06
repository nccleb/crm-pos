<?php
// ============================================================
// NCC CRM POS — Supplier Management
// pos_suppliers.php
// ============================================================
session_start();
if (!isset($_SESSION["oop"]) || !isset($_SESSION["ooq"])) {
    header("Location: login200.php"); exit;
}
$agent_name  = $_SESSION["oop"];
$is_super    = ($agent_name === "super");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Suppliers — NCC POS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --dark:#1a2b4a; --mid:#2563eb; --light:#dbeafe; }
  body  { background:#f1f5f9; font-size:.9rem; }
  .topbar { background:var(--dark); color:#fff; padding:.6rem 1.2rem;
            display:flex; align-items:center; justify-content:space-between; }
  .topbar a { color:#93c5fd; text-decoration:none; font-size:.85rem; margin-left:1rem; }
  .topbar a:hover { color:#fff; }
  .page-card { background:#fff; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,.08);
               padding:1.5rem; margin:1.2rem auto; max-width:1100px; }
  .section-title { font-size:1.05rem; font-weight:700; color:var(--dark);
                   border-left:4px solid var(--mid); padding-left:.7rem; margin-bottom:1rem; }
  .badge-active   { background:#dcfce7; color:#166534; padding:.25rem .6rem; border-radius:20px; font-size:.78rem; }
  .badge-inactive { background:#fee2e2; color:#991b1b; padding:.25rem .6rem; border-radius:20px; font-size:.78rem; }
  .table th { background:var(--dark); color:#fff; font-size:.82rem; }
  .table td { vertical-align:middle; font-size:.85rem; }
  .btn-primary   { background:var(--mid); border:none; }
  .btn-primary:hover { background:#1d4ed8; }
  .stat-box { background:var(--light); border-radius:8px; padding:.9rem 1.2rem; text-align:center; }
  .stat-box .num { font-size:1.6rem; font-weight:700; color:var(--dark); }
  .stat-box .lbl { font-size:.78rem; color:#64748b; }
</style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
  <div>
    <i class="bi bi-building me-2"></i>
    <strong>NCC POS</strong>
    <span class="ms-3 text-white-50">Suppliers</span>
  </div>
  <div>
    <a href="pos.php"><i class="bi bi-cart3"></i> POS</a>
    <a href="pos_receiving.php"><i class="bi bi-box-arrow-in-down"></i> Receiving</a>
    <a href="pos_products.php"><i class="bi bi-box-seam"></i> Products</a>
    <a href="pos_promotions.php"><i class="bi bi-tags"></i> Promotions</a>
    <?php if($is_super): ?>
    <a href="pos_settings.php"><i class="bi bi-gear"></i> Settings</a>
    <?php endif; ?>
    <span class="ms-3 text-white-50"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($agent_name) ?></span>
  </div>
</div>

<div class="page-card">

  <!-- Stats row -->
  <div class="row g-3 mb-4" id="statsRow">
    <div class="col-md-3">
      <div class="stat-box">
        <div class="num" id="statTotal">—</div>
        <div class="lbl">Total Suppliers</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-box">
        <div class="num" id="statActive">—</div>
        <div class="lbl">Active</div>
      </div>
    </div>
  </div>

  <!-- Header + Add button -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="section-title mb-0">Supplier List</div>
    <button class="btn btn-primary btn-sm" onclick="openModal()">
      <i class="bi bi-plus-circle me-1"></i>Add Supplier
    </button>
  </div>

  <!-- Search -->
  <div class="mb-3">
    <input type="text" id="searchInput" class="form-control form-control-sm" style="max-width:320px"
           placeholder="Search suppliers..." oninput="filterTable()">
  </div>

  <!-- Table -->
  <div class="table-responsive">
    <table class="table table-hover table-bordered" id="supplierTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Supplier Name</th>
          <th>Contact Person</th>
          <th>Phone</th>
          <th>Address</th>
          <th>Notes</th>
          <th>Status</th>
          <th style="width:130px">Actions</th>
        </tr>
      </thead>
      <tbody id="supplierBody">
        <tr><td colspan="8" class="text-center text-muted py-4">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--dark);color:#fff">
        <h5 class="modal-title" id="modalTitle">Add Supplier</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="supplierId">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Supplier Name <span class="text-danger">*</span></label>
            <input type="text" id="suppName" class="form-control" placeholder="e.g. Fattal & Co.">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Contact Person</label>
            <input type="text" id="suppContact" class="form-control" placeholder="e.g. Ahmad Khalil">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Phone</label>
            <input type="text" id="suppPhone" class="form-control" placeholder="03 000 000">
          </div>
          <div class="col-md-8">
            <label class="form-label fw-semibold">Address</label>
            <input type="text" id="suppAddress" class="form-control" placeholder="City, Region">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Notes</label>
            <textarea id="suppNotes" class="form-control" rows="2"
                      placeholder="Payment terms, delivery schedule, etc."></textarea>
          </div>
        </div>
        <div id="modalError" class="alert alert-danger mt-3 d-none"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveSupplier()">
          <i class="bi bi-check-circle me-1"></i>Save Supplier
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('supplierModal'));
let allSuppliers = [];

// ── Load suppliers ─────────────────────────────────────────
async function loadSuppliers() {
  const res  = await fetch('ajax/pos_receiving_ajax.php?action=list_suppliers');
  const data = await res.json();
  allSuppliers = data.suppliers || [];
  renderTable(allSuppliers);
  updateStats(allSuppliers);
}

function updateStats(list) {
  document.getElementById('statTotal').textContent  = list.length;
  document.getElementById('statActive').textContent = list.filter(s => s.active == 1).length;
}

function renderTable(list) {
  const tbody = document.getElementById('supplierBody');
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No suppliers found</td></tr>';
    return;
  }
  tbody.innerHTML = list.map(s => `
    <tr>
      <td>${s.id}</td>
      <td><strong>${esc(s.name)}</strong></td>
      <td>${esc(s.contact_person || '—')}</td>
      <td>${esc(s.phone || '—')}</td>
      <td>${esc(s.address || '—')}</td>
      <td class="text-muted">${esc(s.notes || '')}</td>
      <td><span class="${s.active==1?'badge-active':'badge-inactive'}">${s.active==1?'Active':'Inactive'}</span></td>
      <td>
        <button class="btn btn-outline-primary btn-sm me-1" onclick="editSupplier(${s.id})">
          <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-outline-danger btn-sm" onclick="deactivate(${s.id},'${esc(s.name)}')">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    </tr>
  `).join('');
}

function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  renderTable(allSuppliers.filter(s =>
    s.name.toLowerCase().includes(q) ||
    (s.contact_person||'').toLowerCase().includes(q) ||
    (s.phone||'').includes(q)
  ));
}

// ── Open modal ─────────────────────────────────────────────
function openModal(s = null) {
  document.getElementById('modalTitle').textContent = s ? 'Edit Supplier' : 'Add Supplier';
  document.getElementById('supplierId').value  = s ? s.id   : '';
  document.getElementById('suppName').value    = s ? s.name : '';
  document.getElementById('suppContact').value = s ? (s.contact_person||'') : '';
  document.getElementById('suppPhone').value   = s ? (s.phone||'') : '';
  document.getElementById('suppAddress').value = s ? (s.address||'') : '';
  document.getElementById('suppNotes').value   = s ? (s.notes||'') : '';
  document.getElementById('modalError').classList.add('d-none');
  modal.show();
  setTimeout(() => document.getElementById('suppName').focus(), 300);
}

function editSupplier(id) {
  const s = allSuppliers.find(x => x.id == id);
  if (s) openModal(s);
}

// ── Save ───────────────────────────────────────────────────
async function saveSupplier() {
  const body = new FormData();
  body.append('action',         'save_supplier');
  body.append('id',             document.getElementById('supplierId').value);
  body.append('name',           document.getElementById('suppName').value.trim());
  body.append('contact_person', document.getElementById('suppContact').value.trim());
  body.append('phone',          document.getElementById('suppPhone').value.trim());
  body.append('address',        document.getElementById('suppAddress').value.trim());
  body.append('notes',          document.getElementById('suppNotes').value.trim());

  const res  = await fetch('ajax/pos_receiving_ajax.php', { method:'POST', body });
  const data = await res.json();

  if (data.error) {
    document.getElementById('modalError').textContent = data.error;
    document.getElementById('modalError').classList.remove('d-none');
    return;
  }
  modal.hide();
  loadSuppliers();
}

// ── Deactivate ─────────────────────────────────────────────
async function deactivate(id, name) {
  if (!confirm(`Remove supplier "${name}"?`)) return;
  const body = new FormData();
  body.append('action', 'deactivate_supplier');
  body.append('id', id);
  await fetch('ajax/pos_receiving_ajax.php', { method:'POST', body });
  loadSuppliers();
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

loadSuppliers();
</script>
</body>
</html>
