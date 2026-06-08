<?php
// pos_products.php — NCC POS Product Manager v4.8
session_start();
$host = '192.168.1.19';
$db   = 'nccleb_test';
$user = 'root';
$pass = '1Sys9Admeen72';
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');
if ($conn->connect_error) die('DB connection failed: ' . $conn->connect_error);

// Fetch categories
$cats = [];
$r = $conn->query("SELECT DISTINCT category FROM produit WHERE category IS NOT NULL AND category != '' ORDER BY category");
while ($row = $r->fetch_assoc()) $cats[] = $row['category'];

// Fetch suppliers
$suppliers = [];
$r2 = $conn->query("SELECT id, name FROM pos_suppliers ORDER BY name");
if ($r2) while ($row = $r2->fetch_assoc()) $suppliers[] = $row;
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NCC POS — Product Manager</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --bg:        #0f1117;
  --surface:   #181c27;
  --surface2:  #1e2333;
  --border:    #2a2f42;
  --accent:    #f5a623;
  --accent2:   #e8834a;
  --green:     #2ecc71;
  --red:       #e74c3c;
  --blue:      #3498db;
  --purple:    #9b59b6;
  --text:      #e8ecf4;
  --text2:     #8892a4;
  --mono:      'IBM Plex Mono', monospace;
  --sans:      'IBM Plex Sans Arabic', sans-serif;
  --radius:    8px;
  --shadow:    0 4px 24px rgba(0,0,0,0.4);
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--sans);
  font-size: 14px;
  min-height: 100vh;
}

/* ── TOP NAV ── */
.topnav {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 0 24px;
  display: flex;
  align-items: center;
  gap: 8px;
  height: 56px;
  position: sticky;
  top: 0;
  z-index: 100;
}
.topnav .brand {
  font-family: var(--mono);
  font-weight: 600;
  font-size: 15px;
  color: var(--accent);
  letter-spacing: 1px;
  margin-right: 16px;
}
.topnav a {
  color: var(--text2);
  text-decoration: none;
  padding: 6px 12px;
  border-radius: var(--radius);
  font-size: 13px;
  transition: all .2s;
}
.topnav a:hover { background: var(--border); color: var(--text); }
.topnav a.active { background: var(--accent); color: #000; font-weight: 600; }
.topnav .spacer { flex: 1; }

/* ── PAGE LAYOUT ── */
.page { padding: 24px; max-width: 1400px; margin: 0 auto; }
.page-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.page-title {
  font-family: var(--mono);
  font-size: 20px;
  font-weight: 600;
  color: var(--accent);
}
.page-header .spacer { flex: 1; }

/* ── BUTTONS ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: var(--radius);
  border: none;
  cursor: pointer;
  font-family: var(--sans);
  font-size: 13px;
  font-weight: 500;
  transition: all .2s;
  text-decoration: none;
}
.btn-primary   { background: var(--accent); color: #000; }
.btn-primary:hover { background: var(--accent2); }
.btn-success   { background: var(--green); color: #000; }
.btn-success:hover { filter: brightness(1.1); }
.btn-danger    { background: var(--red); color: #fff; }
.btn-danger:hover { filter: brightness(1.1); }
.btn-ghost     { background: var(--border); color: var(--text); }
.btn-ghost:hover { background: var(--surface2); }
.btn-sm        { padding: 5px 10px; font-size: 12px; }
.btn-icon      { padding: 7px; border-radius: 6px; background: var(--border); border: none; cursor: pointer; color: var(--text2); transition: all .2s; }
.btn-icon:hover { background: var(--surface2); color: var(--text); }

/* ── SEARCH / FILTER BAR ── */
.filter-bar {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
  align-items: center;
}
.filter-bar input, .filter-bar select {
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--text);
  padding: 8px 12px;
  border-radius: var(--radius);
  font-family: var(--sans);
  font-size: 13px;
  outline: none;
  transition: border .2s;
}
.filter-bar input:focus, .filter-bar select:focus { border-color: var(--accent); }
.filter-bar input { width: 260px; }
.filter-bar select { min-width: 140px; }
.filter-bar select option { background: var(--surface2); }

/* ── STATS CARDS ── */
.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 12px;
  margin-bottom: 24px;
}
.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.stat-card .label { color: var(--text2); font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
.stat-card .value { font-family: var(--mono); font-size: 22px; font-weight: 600; color: var(--accent); }
.stat-card .sub   { font-size: 11px; color: var(--text2); }

/* ── TABLE ── */
.table-wrap {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}
table {
  width: 100%;
  border-collapse: collapse;
}
thead tr {
  background: var(--surface2);
  border-bottom: 1px solid var(--border);
}
thead th {
  padding: 12px 14px;
  text-align: left;
  font-family: var(--mono);
  font-size: 11px;
  color: var(--text2);
  text-transform: uppercase;
  letter-spacing: .5px;
  white-space: nowrap;
  cursor: pointer;
  user-select: none;
}
thead th:hover { color: var(--accent); }
tbody tr {
  border-bottom: 1px solid var(--border);
  transition: background .15s;
}
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: var(--surface2); }
tbody td {
  padding: 11px 14px;
  vertical-align: middle;
  font-size: 13px;
}
.td-name { font-weight: 500; max-width: 200px; }
.td-code { font-family: var(--mono); font-size: 12px; color: var(--text2); }
.td-price { font-family: var(--mono); font-weight: 600; color: var(--green); }
.td-stock { font-family: var(--mono); }
.td-actions { display: flex; gap: 6px; align-items: center; }

/* ── BADGES ── */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}
.badge-active   { background: rgba(46,204,113,.15); color: var(--green); border: 1px solid rgba(46,204,113,.3); }
.badge-inactive { background: rgba(231,76,60,.12); color: var(--red); border: 1px solid rgba(231,76,60,.25); }
.badge-low      { background: rgba(245,166,35,.15); color: var(--accent); border: 1px solid rgba(245,166,35,.3); }
.badge-weight   { background: rgba(52,152,219,.15); color: var(--blue); border: 1px solid rgba(52,152,219,.3); }
.badge-plu      { background: rgba(155,89,182,.15); color: var(--purple); border: 1px solid rgba(155,89,182,.3); font-family: var(--mono); }

/* ── MODAL ── */
.overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.75);
  z-index: 200;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.overlay.open { display: flex; }
.modal {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  width: 100%;
  max-width: 680px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: var(--shadow);
  animation: slideUp .25s ease;
}
@keyframes slideUp {
  from { transform: translateY(30px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}
.modal-header {
  padding: 20px 24px 16px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 12px;
  position: sticky;
  top: 0;
  background: var(--surface);
  z-index: 1;
}
.modal-title { font-family: var(--mono); font-size: 16px; font-weight: 600; color: var(--accent); }
.modal-close { margin-left: auto; background: none; border: none; color: var(--text2); font-size: 22px; cursor: pointer; line-height: 1; }
.modal-close:hover { color: var(--red); }
.modal-body { padding: 24px; }
.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  position: sticky;
  bottom: 0;
  background: var(--surface);
}

/* ── FORM ── */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-grid .full { grid-column: 1 / -1; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label {
  font-size: 11px;
  font-weight: 600;
  color: var(--text2);
  text-transform: uppercase;
  letter-spacing: .5px;
}
.form-group input,
.form-group select,
.form-group textarea {
  background: var(--surface2);
  border: 1px solid var(--border);
  color: var(--text);
  padding: 9px 12px;
  border-radius: var(--radius);
  font-family: var(--sans);
  font-size: 14px;
  outline: none;
  transition: border .2s;
  width: 100%;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: var(--accent); }
.form-group textarea { resize: vertical; min-height: 80px; }
.form-group select option { background: var(--surface2); }
.form-group input[type="checkbox"] { width: auto; }

/* ── SCALE SECTION ── */
.scale-section {
  background: rgba(52,152,219,.07);
  border: 1px solid rgba(52,152,219,.25);
  border-radius: var(--radius);
  padding: 16px;
  margin-top: 4px;
  display: none;
}
.scale-section.visible { display: block; }
.scale-section .section-title {
  font-family: var(--mono);
  font-size: 12px;
  font-weight: 600;
  color: var(--blue);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.weight-toggle-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: var(--surface2);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  cursor: pointer;
  margin-bottom: 4px;
}
.weight-toggle-row:hover { border-color: var(--blue); }
.toggle-switch {
  position: relative;
  width: 40px;
  height: 22px;
  flex-shrink: 0;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
  position: absolute;
  inset: 0;
  background: var(--border);
  border-radius: 22px;
  transition: .3s;
}
.toggle-slider:before {
  content: '';
  position: absolute;
  width: 16px; height: 16px;
  left: 3px; top: 3px;
  background: white;
  border-radius: 50%;
  transition: .3s;
}
.toggle-switch input:checked + .toggle-slider { background: var(--blue); }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(18px); }
.toggle-label { font-size: 13px; font-weight: 500; }
.toggle-sublabel { font-size: 11px; color: var(--text2); margin-left: auto; }

.hint { font-size: 11px; color: var(--text2); margin-top: 3px; }
.hint.warn { color: var(--accent); }

/* ── DELETE CONFIRM ── */
.delete-modal .modal-body { text-align: center; padding: 32px 24px; }
.delete-icon { font-size: 48px; margin-bottom: 16px; }
.delete-name { font-weight: 600; font-size: 16px; color: var(--red); margin-bottom: 8px; }
.delete-text { color: var(--text2); font-size: 13px; }

/* ── TOAST ── */
.toast-wrap {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 999;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.toast {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 18px;
  font-size: 13px;
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: 10px;
  animation: fadeIn .3s ease;
  max-width: 320px;
}
.toast.success { border-left: 3px solid var(--green); }
.toast.error   { border-left: 3px solid var(--red); }
@keyframes fadeIn { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }

/* ── PAGINATION ── */
.pagination {
  display: flex;
  align-items: center;
  gap: 6px;
  justify-content: center;
  margin-top: 20px;
  flex-wrap: wrap;
}
.page-btn {
  padding: 6px 12px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text2);
  cursor: pointer;
  font-family: var(--mono);
  font-size: 13px;
  transition: all .2s;
}
.page-btn:hover, .page-btn.active { background: var(--accent); color: #000; border-color: var(--accent); }
.page-btn:disabled { opacity: .4; cursor: not-allowed; }

/* ── EMPTY STATE ── */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text2);
}
.empty-state .icon { font-size: 48px; margin-bottom: 16px; opacity: .4; }
.empty-state p { font-size: 15px; }

/* ── IMPORT PANEL ── */
.import-panel {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  margin-bottom: 20px;
  display: none;
}
.import-panel.open { display: block; }
.import-panel h3 { font-family: var(--mono); font-size: 13px; color: var(--accent); margin-bottom: 12px; }

.row-count { font-family: var(--mono); font-size: 12px; color: var(--text2); }
</style>
</head>
<body>

<!-- TOP NAV -->
<nav class="topnav">
  <span class="brand">NCC POS</span>
  <a href="pos.php">🧾 Cashier</a>
  <a href="pos_products.php" class="active">📦 Products</a>
  <a href="pos_sales.php">📊 Sales</a>
  <a href="pos_stock.php">🏭 Stock</a>
  <a href="pos_reports.php">📈 Reports</a>
  <a href="pos_loyalty.php">⭐ Loyalty</a>
  <a href="pos_scale_export.php">⚖️ Scale PLU</a>
  <a href="pos_settings.php">⚙️ Settings</a>
</nav>

<div class="page">
  <!-- HEADER -->
  <div class="page-header">
    <div class="page-title">📦 Product Manager</div>
    <div class="spacer"></div>
    <button class="btn btn-ghost" onclick="toggleImport()">⬆️ Import CSV</button>
    <button class="btn btn-primary" onclick="openAdd()">＋ Add Product</button>
  </div>

  <!-- STATS -->
  <div class="stats-row" id="statsRow">
    <div class="stat-card">
      <span class="label">Total Products</span>
      <span class="value" id="statTotal">—</span>
    </div>
    <div class="stat-card">
      <span class="label">Active</span>
      <span class="value" id="statActive" style="color:var(--green)">—</span>
    </div>
    <div class="stat-card">
      <span class="label">Low Stock</span>
      <span class="value" id="statLow" style="color:var(--accent)">—</span>
    </div>
    <div class="stat-card">
      <span class="label">Weight Items (Scale)</span>
      <span class="value" id="statWeight" style="color:var(--blue)">—</span>
    </div>
    <div class="stat-card">
      <span class="label">Categories</span>
      <span class="value" id="statCats">—</span>
    </div>
  </div>

  <!-- IMPORT PANEL -->
  <div class="import-panel" id="importPanel">
    <h3>⬆️ IMPORT PRODUCTS FROM CSV</h3>
    <p style="color:var(--text2);font-size:12px;margin-bottom:12px;">
      CSV format: <code style="color:var(--accent)">name, category, barcode, price, cost_price, unit, stock</code>
    </p>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <input type="file" id="csvFile" accept=".csv" style="color:var(--text2);">
      <button class="btn btn-success" onclick="doImport()">▶ Start Import</button>
      <span id="importStatus" style="font-size:12px;color:var(--text2);"></span>
    </div>
  </div>

  <!-- FILTER BAR -->
  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="🔍  Search name, barcode, PLU..." oninput="applyFilters()">
    <select id="catFilter" onchange="applyFilters()">
      <option value="">All Categories</option>
      <?php foreach($cats as $c): ?>
      <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
      <?php endforeach; ?>
    </select>
    <select id="statusFilter" onchange="applyFilters()">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
      <option value="low">Low Stock</option>
      <option value="weight">Weight / Scale</option>
    </select>
    <select id="sortField" onchange="applyFilters()">
      <option value="nomp">Sort: Name</option>
      <option value="price">Sort: Price</option>
      <option value="onhand">Sort: Stock</option>
      <option value="category">Sort: Category</option>
    </select>
    <select id="sortDir" onchange="applyFilters()">
      <option value="asc">↑ Asc</option>
      <option value="desc">↓ Desc</option>
    </select>
    <span class="row-count" id="rowCount"></span>
  </div>

  <!-- TABLE -->
  <div class="table-wrap">
    <table id="prodTable">
      <thead>
        <tr>
          <th onclick="sortBy('nomp')">NAME</th>
          <th onclick="sortBy('category')">CATEGORY</th>
          <th onclick="sortBy('barcode')">BARCODE / PLU</th>
          <th onclick="sortBy('price')">PRICE (LL)</th>
          <th onclick="sortBy('onhand')">STOCK</th>
          <th>UNIT</th>
          <th>STATUS</th>
          <th>ACTIONS</th>
        </tr>
      </thead>
      <tbody id="prodTbody">
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text2);">Loading…</td></tr>
      </tbody>
    </table>
  </div>
  <div class="pagination" id="pagination"></div>
</div>

<!-- ══════════════════════════════════════════
     ADD / EDIT MODAL
══════════════════════════════════════════ -->
<div class="overlay" id="editOverlay" onclick="closeIfBg(event,'editOverlay')">
<div class="modal">
  <div class="modal-header">
    <span class="modal-title" id="modalTitle">Add Product</span>
    <button class="modal-close" onclick="closeModal('editOverlay')">×</button>
  </div>
  <div class="modal-body">
    <input type="hidden" id="editId">
    <div class="form-grid">

      <!-- NAME -->
      <div class="form-group full">
        <label>Product Name *</label>
        <input type="text" id="editName" placeholder="e.g. خيار / Cucumber" required>
      </div>

      <!-- CATEGORY -->
      <div class="form-group">
        <label>Category</label>
        <input type="text" id="editCategory" list="catList" placeholder="Select or type new">
        <datalist id="catList">
          <?php foreach($cats as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>">
          <?php endforeach; ?>
        </datalist>
      </div>

      <!-- UNIT -->
      <div class="form-group">
        <label>Unit</label>
        <select id="editUnit">
          <option value="piece">Piece</option>
          <option value="KG">KG (weight)</option>
          <option value="g">Gram</option>
          <option value="L">Litre</option>
          <option value="pack">Pack</option>
          <option value="box">Box</option>
          <option value="can">Can</option>
          <option value="bottle">Bottle</option>
        </select>
      </div>

      <!-- BARCODE -->
      <div class="form-group">
        <label>Barcode (regular items)</label>
        <input type="text" id="editBarcode" placeholder="Scan or type barcode">
        <span class="hint">For scale items, leave empty — use PLU below</span>
      </div>

      <!-- PRICE -->
      <div class="form-group">
        <label>Sale Price (LL) *</label>
        <input type="number" id="editPrice" placeholder="0" min="0">
        <span class="hint">Used for regular (non-weight) items</span>
      </div>

      <!-- COST PRICE -->
      <div class="form-group">
        <label>Cost Price (LL)</label>
        <input type="number" id="editCostPrice" placeholder="0" min="0">
      </div>

      <!-- STOCK -->
      <div class="form-group">
        <label>Stock (QTY)</label>
        <input type="number" id="editStock" placeholder="0.000" step="0.001" min="0">
      </div>

      <!-- LOW STOCK THRESHOLD -->
      <div class="form-group">
        <label>Low Stock Alert Threshold</label>
        <input type="number" id="editLowStock" placeholder="0.000" step="0.001" min="0">
      </div>

      <!-- DESCRIPTION -->
      <div class="form-group full">
        <label>Description</label>
        <textarea id="editDescription" placeholder="Optional product description"></textarea>
      </div>

      <!-- ══ SCALE / WEIGHT SECTION ══ -->
      <div class="form-group full">
        <div class="weight-toggle-row" onclick="toggleWeightMode()">
          <label class="toggle-switch" onclick="event.stopPropagation()">
            <input type="checkbox" id="editSoldByWeight" onchange="onWeightToggle()">
            <span class="toggle-slider"></span>
          </label>
          <span class="toggle-label">⚖️ Scale / Weight-Based Pricing</span>
          <span class="toggle-sublabel">TEG TM-A / EAN-13 prefix-2</span>
        </div>

        <div class="scale-section" id="scaleSection">
          <div class="section-title">⚖️ SCALE CONFIGURATION</div>
          <div class="form-grid">
            <div class="form-group">
              <label>PLU Code * <span style="color:var(--purple)">(links to scale)</span></label>
              <input type="text" id="editPluCode" placeholder="e.g. 70002" maxlength="10">
              <span class="hint warn">⚠️ Must exactly match the PLU programmed in the TEG scale</span>
            </div>
            <div class="form-group">
              <label>Price per KG (LL)</label>
              <input type="number" id="editPricePerKg" placeholder="e.g. 75000" min="0">
              <span class="hint">Used for PLU export file → import into scale</span>
            </div>
          </div>
          <div style="margin-top:12px;padding:10px;background:rgba(52,152,219,.08);border-radius:6px;font-size:12px;color:var(--text2);line-height:1.7;">
            <strong style="color:var(--blue)">How it works:</strong><br>
            Staff weigh item at scale → scale prints EAN-13 label → cashier scans label →
            POS reads PLU from barcode digits 2–6 → looks up product → auto-fills price.<br>
            <strong style="color:var(--accent)">After saving:</strong> go to ⚖️ Scale PLU Export and re-export CSV to update the scale.
          </div>
        </div>
      </div>

      <!-- ACTIVE -->
      <div class="form-group">
        <label>Status</label>
        <select id="editActive">
          <option value="1">✅ Active</option>
          <option value="0">❌ Inactive</option>
        </select>
      </div>

    </div><!-- /form-grid -->
  </div><!-- /modal-body -->
  <div class="modal-footer">
    <button class="btn btn-ghost" onclick="closeModal('editOverlay')">Cancel</button>
    <button class="btn btn-primary" onclick="saveProduct()">💾 Save Product</button>
  </div>
</div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="overlay" id="deleteOverlay" onclick="closeIfBg(event,'deleteOverlay')">
<div class="modal delete-modal" style="max-width:400px;">
  <div class="modal-header">
    <span class="modal-title" style="color:var(--red)">Delete Product</span>
    <button class="modal-close" onclick="closeModal('deleteOverlay')">×</button>
  </div>
  <div class="modal-body">
    <div class="delete-icon">🗑️</div>
    <div class="delete-name" id="deleteProductName"></div>
    <div class="delete-text">This action cannot be undone. All stock and sale records linked to this product will remain, but the product will be removed.</div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-ghost" onclick="closeModal('deleteOverlay')">Cancel</button>
    <button class="btn btn-danger" onclick="confirmDelete()">🗑️ Delete</button>
  </div>
</div>
</div>

<!-- TOAST -->
<div class="toast-wrap" id="toastWrap"></div>

<script>
// ══════════════════════════════════
// STATE
// ══════════════════════════════════
let allProducts = [];
let filtered    = [];
let currentPage = 1;
const perPage   = 30;
let deleteId    = null;
let sortField   = 'nomp';
let sortDir     = 'asc';

// ══════════════════════════════════
// LOAD
// ══════════════════════════════════
async function loadProducts() {
  const res  = await fetch('ajax/pos_ajax.php?action=get_all_products');
  const data = await res.json();
  allProducts = data.products || [];
  updateStats();
  applyFilters();
}

function updateStats() {
  const active = allProducts.filter(p => p.active == 1);
  const low    = allProducts.filter(p => parseFloat(p.onhand) <= parseFloat(p.low_stock_threshold) && parseFloat(p.low_stock_threshold) > 0);
  const weight = allProducts.filter(p => p.sold_by_weight == 1);
  const cats   = new Set(allProducts.map(p => p.category).filter(Boolean));
  document.getElementById('statTotal').textContent  = allProducts.length;
  document.getElementById('statActive').textContent = active.length;
  document.getElementById('statLow').textContent    = low.length;
  document.getElementById('statWeight').textContent = weight.length;
  document.getElementById('statCats').textContent   = cats.size;
}

function applyFilters() {
  const q      = document.getElementById('searchInput').value.toLowerCase().trim();
  const cat    = document.getElementById('catFilter').value;
  const status = document.getElementById('statusFilter').value;
  sortField    = document.getElementById('sortField').value;
  sortDir      = document.getElementById('sortDir').value;

  filtered = allProducts.filter(p => {
    const matchQ = !q
      || (p.nomp||'').toLowerCase().includes(q)
      || (p.barcode||'').toLowerCase().includes(q)
      || (p.plu_code||'').toLowerCase().includes(q)
      || (p.category||'').toLowerCase().includes(q);
    const matchCat = !cat || p.category === cat;
    let matchStatus = true;
    if (status === 'active')   matchStatus = p.active == 1;
    if (status === 'inactive') matchStatus = p.active == 0;
    if (status === 'low')      matchStatus = parseFloat(p.onhand) <= parseFloat(p.low_stock_threshold) && parseFloat(p.low_stock_threshold) > 0;
    if (status === 'weight')   matchStatus = p.sold_by_weight == 1;
    return matchQ && matchCat && matchStatus;
  });

  filtered.sort((a, b) => {
    let va = a[sortField] ?? '';
    let vb = b[sortField] ?? '';
    if (!isNaN(va) && !isNaN(vb)) { va = parseFloat(va); vb = parseFloat(vb); }
    else { va = String(va).toLowerCase(); vb = String(vb).toLowerCase(); }
    if (va < vb) return sortDir === 'asc' ? -1 : 1;
    if (va > vb) return sortDir === 'asc' ? 1 : -1;
    return 0;
  });

  currentPage = 1;
  document.getElementById('rowCount').textContent = filtered.length + ' product' + (filtered.length !== 1 ? 's' : '');
  renderTable();
  renderPagination();
}

function sortBy(field) {
  if (sortField === field) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
  else { sortField = field; sortDir = 'asc'; }
  document.getElementById('sortField').value = sortField;
  document.getElementById('sortDir').value   = sortDir;
  applyFilters();
}

// ══════════════════════════════════
// RENDER TABLE
// ══════════════════════════════════
function renderTable() {
  const tbody = document.getElementById('prodTbody');
  const start = (currentPage - 1) * perPage;
  const slice = filtered.slice(start, start + perPage);

  if (!slice.length) {
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><div class="icon">📦</div><p>No products found</p></div></td></tr>`;
    return;
  }

  tbody.innerHTML = slice.map(p => {
    const isLow    = parseFloat(p.onhand) <= parseFloat(p.low_stock_threshold) && parseFloat(p.low_stock_threshold) > 0;
    const isWeight = p.sold_by_weight == 1;
    const price    = isWeight
      ? `<span style="color:var(--blue)">${fmt(p.price_per_kg)}/kg</span>`
      : `<span class="td-price">${fmt(p.price)}</span>`;

    const barcodeCell = isWeight
      ? `<span class="badge badge-plu">PLU: ${p.plu_code || '—'}</span>`
      : `<span class="td-code">${p.barcode || '—'}</span>`;

    const stockColor = isLow ? 'color:var(--accent)' : '';
    const statusBadge = p.active == 1
      ? `<span class="badge badge-active">● Active</span>`
      : `<span class="badge badge-inactive">● Inactive</span>`;
    const lowBadge   = isLow    ? `<span class="badge badge-low">⚠ Low</span>` : '';
    const weightBadge = isWeight ? `<span class="badge badge-weight">⚖️ Scale</span>` : '';

    return `<tr>
      <td class="td-name">${escHtml(p.nomp)}</td>
      <td style="color:var(--text2)">${escHtml(p.category||'—')}</td>
      <td>${barcodeCell}</td>
      <td>${price}</td>
      <td class="td-stock" style="${stockColor}">${parseFloat(p.onhand||0).toFixed(3)}</td>
      <td style="color:var(--text2)">${escHtml(p.unit||'—')}</td>
      <td>${statusBadge} ${lowBadge} ${weightBadge}</td>
      <td>
        <div class="td-actions">
          <button class="btn btn-ghost btn-sm" onclick="openEdit(${p.codep})">✏️ Edit</button>
          <button class="btn btn-danger btn-sm" onclick="openDelete(${p.codep},'${escHtml(p.nomp)}')">🗑️</button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function renderPagination() {
  const total = Math.ceil(filtered.length / perPage);
  const wrap  = document.getElementById('pagination');
  if (total <= 1) { wrap.innerHTML = ''; return; }
  let html = `<button class="page-btn" onclick="goPage(${currentPage-1})" ${currentPage===1?'disabled':''}>‹</button>`;
  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || Math.abs(i - currentPage) <= 2) {
      html += `<button class="page-btn ${i===currentPage?'active':''}" onclick="goPage(${i})">${i}</button>`;
    } else if (Math.abs(i - currentPage) === 3) {
      html += `<span style="color:var(--text2)">…</span>`;
    }
  }
  html += `<button class="page-btn" onclick="goPage(${currentPage+1})" ${currentPage===total?'disabled':''}>›</button>`;
  wrap.innerHTML = html;
}

function goPage(p) {
  const total = Math.ceil(filtered.length / perPage);
  if (p < 1 || p > total) return;
  currentPage = p;
  renderTable();
  renderPagination();
  window.scrollTo({top:0,behavior:'smooth'});
}

// ══════════════════════════════════
// MODAL OPEN / CLOSE
// ══════════════════════════════════
function openAdd() {
  document.getElementById('modalTitle').textContent = 'Add Product';
  document.getElementById('editId').value           = '';
  document.getElementById('editName').value         = '';
  document.getElementById('editCategory').value     = '';
  document.getElementById('editUnit').value         = 'piece';
  document.getElementById('editBarcode').value      = '';
  document.getElementById('editPrice').value        = '';
  document.getElementById('editCostPrice').value    = '';
  document.getElementById('editStock').value        = '';
  document.getElementById('editLowStock').value     = '';
  document.getElementById('editDescription').value  = '';
  document.getElementById('editActive').value       = '1';
  document.getElementById('editSoldByWeight').checked = false;
  document.getElementById('editPluCode').value      = '';
  document.getElementById('editPricePerKg').value   = '';
  document.getElementById('scaleSection').classList.remove('visible');
  document.getElementById('editOverlay').classList.add('open');
}

function openEdit(id) {
  const p = allProducts.find(x => x.codep == id);
  if (!p) return;
  document.getElementById('modalTitle').textContent    = 'Edit Product';
  document.getElementById('editId').value              = p.codep;
  document.getElementById('editName').value            = p.nomp || '';
  document.getElementById('editCategory').value        = p.category || '';
  document.getElementById('editUnit').value            = p.unit || 'piece';
  document.getElementById('editBarcode').value         = p.barcode || '';
  document.getElementById('editPrice').value           = p.price || '';
  document.getElementById('editCostPrice').value       = p.cost_price || '';
  document.getElementById('editStock').value           = p.onhand || '';
  document.getElementById('editLowStock').value        = p.low_stock_threshold || '';
  document.getElementById('editDescription').value     = p.description || '';
  document.getElementById('editActive').value          = p.active;
  document.getElementById('editSoldByWeight').checked  = p.sold_by_weight == 1;
  document.getElementById('editPluCode').value         = p.plu_code || '';
  document.getElementById('editPricePerKg').value      = p.price_per_kg || '';
  if (p.sold_by_weight == 1) document.getElementById('scaleSection').classList.add('visible');
  else document.getElementById('scaleSection').classList.remove('visible');
  document.getElementById('editOverlay').classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}
function closeIfBg(e, id) {
  if (e.target.id === id) closeModal(id);
}

// ══════════════════════════════════
// WEIGHT TOGGLE
// ══════════════════════════════════
function toggleWeightMode() {
  const cb = document.getElementById('editSoldByWeight');
  cb.checked = !cb.checked;
  onWeightToggle();
}
function onWeightToggle() {
  const on = document.getElementById('editSoldByWeight').checked;
  document.getElementById('scaleSection').classList.toggle('visible', on);
  if (on) {
    document.getElementById('editUnit').value = 'KG';
  }
}

// ══════════════════════════════════
// SAVE PRODUCT
// ══════════════════════════════════
async function saveProduct() {
  const name = document.getElementById('editName').value.trim();
  if (!name) { toast('Product name is required', 'error'); return; }

  const sold_by_weight = document.getElementById('editSoldByWeight').checked ? 1 : 0;
  const plu_code       = document.getElementById('editPluCode').value.trim();

  if (sold_by_weight && !plu_code) {
    toast('PLU Code is required for scale/weight products', 'error');
    document.getElementById('editPluCode').focus();
    return;
  }

  const payload = {
    action:             document.getElementById('editId').value ? 'update_product' : 'add_product',
    codep:              document.getElementById('editId').value,
    nomp:               name,
    category:           document.getElementById('editCategory').value.trim(),
    unit:               document.getElementById('editUnit').value,
    barcode:            document.getElementById('editBarcode').value.trim(),
    price:              document.getElementById('editPrice').value || 0,
    cost_price:         document.getElementById('editCostPrice').value || 0,
    onhand:             document.getElementById('editStock').value || 0,
    low_stock_threshold:document.getElementById('editLowStock').value || 0,
    description:        document.getElementById('editDescription').value.trim(),
    active:             document.getElementById('editActive').value,
    sold_by_weight:     sold_by_weight,
    plu_code:           plu_code,
    price_per_kg:       document.getElementById('editPricePerKg').value || 0,
  };

  try {
    const saveUrl = ['add_product','update_product','delete_product'].includes(payload.action)
      ? 'ajax/pos_product_save.php'
      : 'ajax/pos_ajax.php';
    const res  = await fetch(saveUrl, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      toast(data.message || 'Product saved ✓', 'success');
      closeModal('editOverlay');
      loadProducts();
    } else {
      toast(data.message || 'Save failed', 'error');
    }
  } catch(e) {
    toast('Network error: ' + e.message, 'error');
  }
}

// ══════════════════════════════════
// DELETE
// ══════════════════════════════════
function openDelete(id, name) {
  deleteId = id;
  document.getElementById('deleteProductName').textContent = name;
  document.getElementById('deleteOverlay').classList.add('open');
}
async function confirmDelete() {
  if (!deleteId) return;
  const res  = await fetch('ajax/pos_product_save.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action: 'delete_product', codep: deleteId })
  });
  const data = await res.json();
  if (data.success) {
    toast('Product deleted', 'success');
    closeModal('deleteOverlay');
    loadProducts();
  } else {
    toast(data.message || 'Delete failed', 'error');
  }
}

// ══════════════════════════════════
// IMPORT CSV
// ══════════════════════════════════
function toggleImport() {
  document.getElementById('importPanel').classList.toggle('open');
}
async function doImport() {
  const file = document.getElementById('csvFile').files[0];
  if (!file) { toast('Select a CSV file first', 'error'); return; }
  const text  = await file.text();
  const lines = text.split('\n').filter(l => l.trim());
  const status = document.getElementById('importStatus');
  let done = 0, errors = 0;
  const batchSize = 300;

  for (let i = 1; i < lines.length; i += batchSize) {
    const batch = lines.slice(i, i + batchSize).map(l => {
      const cols = l.split(',');
      return {
        nomp:       (cols[0]||'').trim(),
        category:   (cols[1]||'').trim(),
        barcode:    (cols[2]||'').trim(),
        price:      parseFloat(cols[3]||0),
        cost_price: parseFloat(cols[4]||0),
        unit:       (cols[5]||'piece').trim(),
        onhand:     parseFloat(cols[6]||0),
      };
    }).filter(r => r.nomp);

    const res  = await fetch('ajax/pos_ajax.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ action: 'import_products', rows: batch })
    });
    const data = await res.json();
    done  += data.imported || 0;
    errors += data.errors  || 0;
    status.textContent = `Imported ${done}, Errors: ${errors}…`;
  }
  toast(`Import complete: ${done} products added`, 'success');
  loadProducts();
}

// ══════════════════════════════════
// UTILITIES
// ══════════════════════════════════
function fmt(n) {
  return Number(n||0).toLocaleString('en-LB') + ' LL';
}
function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function toast(msg, type='success') {
  const wrap = document.getElementById('toastWrap');
  const el   = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// ══════════════════════════════════
// INIT
// ══════════════════════════════════
loadProducts();
</script>
</body>
</html>
