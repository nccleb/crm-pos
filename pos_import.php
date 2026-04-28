<?php
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Import Products — POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- SheetJS for Excel parsing -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.topbar { background:linear-gradient(135deg,#1976D2,#0D47A1); color:white; padding:14px 24px; display:flex; align-items:center; gap:15px; }
.topbar h1 { font-size:18px; font-weight:700; }
.topbar a { color:white; text-decoration:none; background:rgba(255,255,255,.15); padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; }
.topbar a:hover { background:rgba(255,255,255,.25); }
.topbar .ml { margin-left:auto; display:flex; gap:8px; }
.container { max-width:1000px; margin:0 auto; padding:24px 20px; }
.card { background:white; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.08); overflow:hidden; margin-bottom:20px; }
.card-header { padding:16px 22px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:10px; }
.card-header h3 { font-size:15px; font-weight:800; color:#1a1a2e; }
.card-header i { color:#1976D2; }
.card-body { padding:22px; }

/* Upload area */
.upload-area {
    border:3px dashed #93c5fd; border-radius:14px; padding:40px 20px;
    text-align:center; cursor:pointer; transition:all .2s;
    background:#f8faff;
}
.upload-area:hover, .upload-area.drag-over { border-color:#1976D2; background:#eff6ff; }
.upload-area i { font-size:48px; color:#93c5fd; margin-bottom:12px; display:block; }
.upload-area h3 { font-size:16px; font-weight:700; color:#374151; margin-bottom:6px; }
.upload-area p  { font-size:13px; color:#9ca3af; }
.upload-area input { display:none; }

/* Column mapper */
.mapper-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:12px; }
.mapper-item label { display:block; font-size:11px; font-weight:700; color:#374151; text-transform:uppercase; margin-bottom:5px; }
.mapper-item select { width:100%; padding:9px 12px; border:2px solid #e5e7eb; border-radius:8px; font-size:13px; }
.mapper-item select:focus { outline:none; border-color:#1976D2; }

/* Preview table */
.preview-wrap { overflow-x:auto; max-height:300px; }
table { width:100%; border-collapse:collapse; font-size:12px; }
th { padding:8px 10px; background:#f8fafc; color:#374151; font-weight:700; text-align:left; border-bottom:2px solid #e5e7eb; position:sticky; top:0; white-space:nowrap; }
td { padding:8px 10px; border-bottom:1px solid #f3f4f6; color:#4b5563; white-space:nowrap; }

.btn { padding:10px 20px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
.btn-blue   { background:#1976D2; color:white; } .btn-blue:hover { background:#0D47A1; }
.btn-green  { background:#10b981; color:white; } .btn-green:hover { background:#059669; }
.btn-gray   { background:#f3f4f6; color:#374151; border:2px solid #e5e7eb; }
.btn-lg     { padding:14px 28px; font-size:15px; }

.alert { padding:13px 16px; border-radius:10px; font-size:13px; font-weight:600; margin-bottom:14px; display:flex; align-items:flex-start; gap:10px; }
.alert-info    { background:#eff6ff; color:#1e40af; border-left:4px solid #1976D2; }
.alert-success { background:#d1fae5; color:#065f46; border-left:4px solid #10b981; }
.alert-error   { background:#fee2e2; color:#991b1b; border-left:4px solid #ef4444; }
.alert-warn    { background:#fef3c7; color:#92400e; border-left:4px solid #f59e0b; }

.step { display:none; }
.step.active { display:block; }
.step-indicator { display:flex; gap:0; margin-bottom:24px; }
.step-item { flex:1; text-align:center; padding:12px; font-size:12px; font-weight:700; background:#f3f4f6; color:#9ca3af; border-bottom:3px solid #e5e7eb; }
.step-item.active { background:#eff6ff; color:#1976D2; border-color:#1976D2; }
.step-item.done   { background:#d1fae5; color:#065f46; border-color:#10b981; }

.template-cols { display:flex; flex-wrap:wrap; gap:8px; }
.col-chip { background:#eff6ff; color:#1976D2; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
.col-chip.required { background:#dbeafe; }
</style>
</head>
<body>

<div class="topbar">
    <i class="fas fa-file-import fa-lg"></i>
    <h1>Import Products</h1>
    <div class="ml">
        <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
        <a href="pos_archive.php"><i class="fas fa-archive"></i> Archive</a>
        <a href="test204.php?page=<?= urlencode($agent_name) ?>&page1=<?= $agent_id ?>"><i class="fas fa-arrow-left"></i> CRM</a>
    </div>
</div>

<div class="container">

    <!-- Step indicator -->
    <div class="step-indicator">
        <div class="step-item active" id="si1">① Upload File</div>
        <div class="step-item" id="si2">② Map Columns</div>
        <div class="step-item" id="si3">③ Preview & Import</div>
        <div class="step-item" id="si4">④ Done</div>
    </div>

    <!-- Step 1: Upload -->
    <div class="step active" id="step1">
        <div class="card">
            <div class="card-header"><i class="fas fa-upload"></i><h3>Upload Your File</h3></div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div>Supported formats: <strong>Excel (.xlsx, .xls)</strong> and <strong>CSV (.csv)</strong><br>
                    Your file should have a header row. Column names will be auto-matched.</div>
                </div>

                <div class="upload-area" id="uploadArea" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>Drop your file here or click to browse</h3>
                    <p>Excel (.xlsx, .xls) · CSV (.csv) · Max 10MB</p>
                    <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" onchange="handleFile(this)">
                </div>

                <div style="margin-top:20px;">
                    <h4 style="font-size:13px;font-weight:700;color:#374151;margin-bottom:10px;">
                        <i class="fas fa-table" style="color:#1976D2;"></i> Expected Column Names
                    </h4>
                    <div class="template-cols">
                        <span class="col-chip required">nomp <small>(product name)*</small></span>
                        <span class="col-chip">category</span>
                        <span class="col-chip">price</span>
                        <span class="col-chip">cost_price</span>
                        <span class="col-chip">onhand <small>(stock qty)</small></span>
                        <span class="col-chip">unit</span>
                        <span class="col-chip">barcode</span>
                        <span class="col-chip">description</span>
                    </div>
                    <p style="font-size:12px;color:#9ca3af;margin-top:8px;">* Required. Other columns are optional. You can also use: <em>name, product_name, selling_price, cost, stock, quantity</em></p>
                </div>

                <div style="margin-top:16px;">
                    <button class="btn btn-gray" onclick="downloadTemplate()">
                        <i class="fas fa-download"></i> Download Sample Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Map Columns -->
    <div class="step" id="step2">
        <div class="card">
            <div class="card-header"><i class="fas fa-columns"></i><h3>Map Your Columns</h3></div>
            <div class="card-body">
                <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">
                    Match your file columns to the POS product fields. Auto-mapping applied where possible.
                </p>
                <div class="mapper-grid" id="mapperGrid"></div>
                <div style="margin-top:20px;display:flex;gap:10px;">
                    <button class="btn btn-gray" onclick="goStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button class="btn btn-blue" onclick="goStep(3)"><i class="fas fa-arrow-right"></i> Preview</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Preview & Import -->
    <div class="step" id="step3">
        <div class="card">
            <div class="card-header"><i class="fas fa-eye"></i><h3>Preview — First 10 Rows</h3></div>
            <div class="card-body">
                <div id="previewWrap" class="preview-wrap"></div>
                <div id="importStats" style="margin-top:12px;font-size:13px;color:#6b7280;"></div>
                <div style="margin-top:20px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <button class="btn btn-gray" onclick="goStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button class="btn btn-green btn-lg" id="importBtn" onclick="doImport()">
                        <i class="fas fa-database"></i> Import All Records
                    </button>
                    <span id="importProgress" style="font-size:13px;color:#1976D2;display:none;">
                        <i class="fas fa-spinner fa-spin"></i> Importing...
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4: Done -->
    <div class="step" id="step4">
        <div class="card">
            <div class="card-body" style="text-align:center;padding:40px;">
                <div style="font-size:60px;margin-bottom:16px;">✅</div>
                <h2 style="font-size:22px;font-weight:800;color:#1a1a2e;margin-bottom:8px;">Import Complete!</h2>
                <div id="doneStats" style="font-size:15px;color:#6b7280;margin-bottom:24px;"></div>
                <div id="doneErrors" style="margin-bottom:20px;text-align:left;"></div>
                <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                    <a href="pos_products.php" class="btn btn-blue btn-lg"><i class="fas fa-box"></i> View Products</a>
                    <button class="btn btn-gray" onclick="location.reload()"><i class="fas fa-plus"></i> Import More</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
var csvData = [];
var csvHeaders = [];
var mapping = {};

// ── DB field definitions ──────────────────────────────────────────────────
var dbFields = [
    { key:'nomp',        label:'Product Name *', required:true },
    { key:'category',    label:'Category',        required:false },
    { key:'price',       label:'Selling Price',   required:false },
    { key:'cost_price',  label:'Cost Price',      required:false },
    { key:'onhand',      label:'Stock Qty',       required:false },
    { key:'unit',        label:'Unit',            required:false },
    { key:'barcode',     label:'Barcode',         required:false },
    { key:'description', label:'Description',     required:false },
];

var autoMap = {
    nomp:['nomp','name','product_name','product','item','nom'],
    category:['category','cat','type'],
    price:['price','selling_price','sell_price','unit_price'],
    cost_price:['cost_price','cost','purchase_price'],
    onhand:['onhand','stock','qty','quantity','on_hand'],
    unit:['unit','uom'],
    barcode:['barcode','ean','sku','code'],
    description:['description','desc','details'],
};

// ── File handling ─────────────────────────────────────────────────────────
function handleFile(input) {
    var file = input.files[0];
    if (!file) return;
    document.querySelector('.upload-area h3').textContent = 'Reading: ' + file.name + '...';

    var reader = new FileReader();
    reader.onload = function(e) {
        var data = e.target.result;
        var wb;
        try {
            if (file.name.endsWith('.csv')) {
                wb = XLSX.read(data, {type:'string'});
            } else {
                wb = XLSX.read(data, {type:'array'});
            }
        } catch(ex) { alert('Error reading file: ' + ex.message); return; }

        var ws = wb.Sheets[wb.SheetNames[0]];
        var json = XLSX.utils.sheet_to_json(ws, {defval:''});

        if (json.length === 0) { alert('No data found in file.'); return; }

        csvHeaders = Object.keys(json[0]);
        csvData    = json;

        buildMapper();
        goStep(2);
    };

    if (file.name.endsWith('.csv')) {
        reader.readAsText(file);
    } else {
        reader.readAsArrayBuffer(file);
    }
}

function buildMapper() {
    var grid = document.getElementById('mapperGrid');
    grid.innerHTML = '';
    mapping = {};

    dbFields.forEach(function(field) {
        // Try auto-map
        var matched = '';
        var hLower = csvHeaders.map(h => h.toLowerCase().trim());
        var aliases = autoMap[field.key] || [];
        for (var i=0;i<aliases.length;i++) {
            var idx = hLower.indexOf(aliases[i]);
            if (idx >= 0) { matched = csvHeaders[idx]; break; }
        }
        mapping[field.key] = matched;

        var div = document.createElement('div');
        div.className = 'mapper-item';
        div.innerHTML =
            '<label>' + field.label + '</label>' +
            '<select id="map_' + field.key + '" onchange="mapping[\'' + field.key + '\']=this.value">' +
            '<option value="">-- Skip --</option>' +
            csvHeaders.map(h => '<option value="' + escHtml(h) + '"' + (h===matched?' selected':'') + '>' + escHtml(h) + '</option>').join('') +
            '</select>';
        grid.appendChild(div);
    });
}

function goStep(n) {
    if (n === 3) {
        // Validate required
        var nomMap = document.getElementById('map_nomp').value;
        if (!nomMap) { alert('Product Name column is required — please map it.'); return; }
        dbFields.forEach(f => { mapping[f.key] = document.getElementById('map_'+f.key).value; });
        buildPreview();
    }

    document.querySelectorAll('.step').forEach((s,i) => s.classList.toggle('active', i+1===n));
    ['si1','si2','si3','si4'].forEach(function(id,i) {
        var el = document.getElementById(id);
        el.className = 'step-item' + (i+1===n?' active':(i+1<n?' done':''));
    });
}

function getMapped(row, key) {
    var col = mapping[key];
    return col ? (row[col] || '') : '';
}

function buildPreview() {
    var preview = csvData.slice(0,10);
    var html = '<table><thead><tr>';
    dbFields.forEach(f => { if (mapping[f.key]) html += '<th>' + escHtml(f.label) + '</th>'; });
    html += '</tr></thead><tbody>';
    preview.forEach(function(row) {
        html += '<tr>';
        dbFields.forEach(f => {
            if (mapping[f.key]) html += '<td>' + escHtml(String(getMapped(row,f.key))) + '</td>';
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    document.getElementById('previewWrap').innerHTML = html;
    document.getElementById('importStats').textContent =
        csvData.length + ' total rows to import (showing first 10 above).';
}

function doImport() {
    var btn = document.getElementById('importBtn');
    btn.style.display = 'none';
    document.getElementById('importProgress').style.display = '';

    // Build rows array
    var rows = csvData.map(function(row) {
        var r = {};
        dbFields.forEach(f => { if (mapping[f.key]) r[f.key] = String(getMapped(row,f.key)).trim(); });
        return r;
    }).filter(r => r.nomp);

    var fd = new FormData();
    fd.append('action','import_products');
    fd.append('rows', JSON.stringify(rows));

    fetch('ajax/pos_archive_ajax.php', {method:'POST',body:fd})
        .then(r=>r.json())
        .then(data => {
            if (!data.success) {
                alert('Import failed: ' + data.error);
                btn.style.display='';
                document.getElementById('importProgress').style.display='none';
                return;
            }
            document.getElementById('doneStats').innerHTML =
                '<strong style="color:#10b981;font-size:18px;">' + data.imported + ' products imported</strong>' +
                (data.skipped>0 ? ' &nbsp;·&nbsp; ' + data.skipped + ' skipped (duplicates or errors)' : '');

            if (data.errors && data.errors.length>0) {
                document.getElementById('doneErrors').innerHTML =
                    '<div class="alert alert-warn"><i class="fas fa-exclamation-triangle"></i><div>' +
                    '<strong>Skipped rows:</strong><br>' +
                    data.errors.slice(0,10).map(e=>'• '+escHtml(e)).join('<br>') +
                    (data.errors.length>10 ? '<br>...and '+(data.errors.length-10)+' more.' : '') +
                    '</div></div>';
            }
            goStep(4);
        });
}

// ── Download template ────────────────────────────────────────────────────
function downloadTemplate() {
    var csv = 'nomp,category,price,cost_price,onhand,unit,barcode,description\n' +
              '"TP-Link Router","Hardware",45.00,30.00,10,"piece","8934567890","Wireless router 300Mbps"\n' +
              '"Ethernet Cable 5m","Accessories",8.50,4.00,50,"piece","","CAT6 flat cable"\n' +
              '"Windows 11 License","Software",120.00,80.00,5,"service","MS-WIN11-OEM","Original license key"\n';
    var blob = new Blob([csv],{type:'text/csv'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'pos_products_template.csv';
    a.click();
}

// ── Drag & drop ───────────────────────────────────────────────────────────
var ua = document.getElementById('uploadArea');
ua.addEventListener('dragover', e => { e.preventDefault(); ua.classList.add('drag-over'); });
ua.addEventListener('dragleave', () => ua.classList.remove('drag-over'));
ua.addEventListener('drop', e => {
    e.preventDefault(); ua.classList.remove('drag-over');
    var f = e.dataTransfer.files[0];
    if (f) { document.getElementById('fileInput').files; handleFile({files:[f]}); }
});

function escHtml(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
</body>
</html>
