<?php
/**
 * pos_scale_export.php
 * =====================
 * Exports PLU product list to formats compatible with:
 * - CAS scales (CSV)
 * - DIGI/Teraoka scales (CSV)
 * - Generic scales (TXT)
 *
 * Place at: C:\wamp64\www\pos_scale_export.php
 * Access at: http://localhost/pos_scale_export.php
 */
require_once 'db.php';

$format = $_GET['format'] ?? 'cas';
$download = isset($_GET['download']);

// Fetch all weight products with PLU codes
$res = mysqli_query($conn, "
    SELECT codep, nomp, price_per_kg, plu_code, category
    FROM produit
    WHERE sold_by_weight = 1 AND plu_code IS NOT NULL AND plu_code != '' AND active = 1
    ORDER BY CAST(plu_code AS UNSIGNED) ASC
");

$products = [];
while ($r = mysqli_fetch_assoc($res)) {
    $products[] = $r;
}

// ── CAS Scale CSV format ──────────────────────────────────────────────────
function safeScaleName(string $name, int $maxLen = 28): string {
    // If pure ASCII already, just uppercase and truncate
    if (mb_detect_encoding($name, 'ASCII', true)) {
        return substr(strtoupper($name), 0, $maxLen);
    }
    // Try to convert Arabic to Windows-1256 compatible — keep as UTF-8 for display
    // Strip characters the scale cannot handle, keep alphanumeric + spaces
    $clean = preg_replace('/[^\p{Arabic}\p{L}\p{N}\s\-\/]/u', '', $name);
    return mb_substr($clean, 0, $maxLen, 'UTF-8');
}

function exportCAS(array $products): string {
    $lines = [];
    // UTF-8 BOM so Excel opens it correctly
    $bom   = "\xEF\xBB\xBF";
    $lines[] = $bom . "PLU,Name,Price_Per_KG,Tare,Validity_Days";
    foreach ($products as $p) {
        $plu   = str_pad((int)$p['plu_code'], 5, '0', STR_PAD_LEFT);
        $name  = safeScaleName($p['nomp'], 28);
        $price = number_format((float)$p['price_per_kg'], 2, '.', '');
        $lines[] = "{$plu},{$name},{$price},0,0";
    }
    return implode("\r\n", $lines);
}

// ── DIGI Scale CSV format ─────────────────────────────────────────────────
function exportDIGI(array $products): string {
    $lines = [];
    $bom   = "\xEF\xBB\xBF";
    $lines[] = $bom . "PLU_No,Commodity_Name,Unit_Price,Valid_Date,Tare_Weight";
    foreach ($products as $p) {
        $plu   = (int)$p['plu_code'];
        $name  = safeScaleName($p['nomp'], 32);
        $price = number_format((float)$p['price_per_kg'], 2, '.', '');
        $lines[] = "{$plu},{$name},{$price},0,0.000";
    }
    return implode("\r\n", $lines);
}

// ── Generic TXT format ────────────────────────────────────────────────────
function exportGeneric(array $products): string {
    $lines = [];
    foreach ($products as $p) {
        $plu   = str_pad((int)$p['plu_code'], 5, '0', STR_PAD_LEFT);
        $name  = str_pad(substr($p['nomp'], 0, 24), 24);
        $price = number_format((float)$p['price_per_kg'], 2, '.', '');
        $lines[] = "{$plu} {$name} {$price}";
    }
    return implode("\r\n", $lines);
}

if ($download) {
    $content  = match($format) {
        'digi'    => exportDIGI($products),
        'generic' => exportGeneric($products),
        default   => exportCAS($products),
    };
    $filename = "scale_plu_export_{$format}_" . date('Ymd_His') . ".csv";
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    echo $content;
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NCC POS — Scale PLU Export</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --bg:      #0f1117;
  --surface: #181c27;
  --surface2:#1e2333;
  --border:  #2a2f42;
  --accent:  #f5a623;
  --green:   #2ecc71;
  --blue:    #3498db;
  --purple:  #9b59b6;
  --text:    #e8ecf4;
  --text2:   #8892a4;
  --mono:    'IBM Plex Mono', monospace;
  --sans:    'IBM Plex Sans Arabic', sans-serif;
  --radius:  8px;
  --shadow:  0 4px 24px rgba(0,0,0,.4);
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg); color: var(--text); font-family: var(--sans); font-size: 14px; min-height: 100vh; }

/* NAV */
.topnav { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 24px; display: flex; align-items: center; gap: 8px; height: 56px; position: sticky; top: 0; z-index: 100; }
.topnav .brand { font-family: var(--mono); font-weight: 600; font-size: 15px; color: var(--accent); letter-spacing: 1px; margin-right: 16px; }
.topnav a { color: var(--text2); text-decoration: none; padding: 6px 12px; border-radius: var(--radius); font-size: 13px; transition: all .2s; }
.topnav a:hover { background: var(--border); color: var(--text); }
.topnav a.active { background: var(--accent); color: #000; font-weight: 600; }

/* PAGE */
.page { padding: 24px; max-width: 1100px; margin: 0 auto; }
.page-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
.page-title { font-family: var(--mono); font-size: 20px; font-weight: 600; color: var(--accent); }

/* CARD */
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 24px; }
.card-header { background: var(--surface2); border-bottom: 1px solid var(--border); padding: 16px 20px; display: flex; align-items: center; gap: 10px; }
.card-header h5 { font-family: var(--mono); font-size: 14px; font-weight: 600; color: var(--accent); margin: 0; }
.card-header .sub { font-size: 12px; color: var(--text2); margin-top: 2px; }
.card-body { padding: 20px; }
.card-footer { background: var(--surface2); border-top: 1px solid var(--border); padding: 14px 20px; font-size: 12px; color: var(--text2); }

/* FORM ROW */
.form-row { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 24px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 11px; font-weight: 600; color: var(--text2); text-transform: uppercase; letter-spacing: .5px; }
.form-group select { background: var(--surface2); border: 1px solid var(--border); color: var(--text); padding: 9px 12px; border-radius: var(--radius); font-family: var(--sans); font-size: 14px; outline: none; min-width: 180px; }
.form-group select:focus { border-color: var(--accent); }
.form-group select option { background: var(--surface2); }

/* BUTTON */
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; border-radius: var(--radius); border: none; cursor: pointer; font-family: var(--sans); font-size: 14px; font-weight: 600; transition: all .2s; text-decoration: none; }
.btn-success { background: var(--green); color: #000; }
.btn-success:hover { filter: brightness(1.1); }

/* INFO BOX */
.info-box { background: rgba(52,152,219,.08); border: 1px solid rgba(52,152,219,.25); border-radius: var(--radius); padding: 12px 16px; font-size: 12px; color: var(--text2); line-height: 1.7; }

/* TABLE */
.table-wrap { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
table { width: 100%; border-collapse: collapse; }
thead tr { background: var(--surface2); border-bottom: 1px solid var(--border); }
thead th { padding: 11px 14px; text-align: left; font-family: var(--mono); font-size: 11px; color: var(--text2); text-transform: uppercase; letter-spacing: .5px; }
tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: var(--surface2); }
tbody td { padding: 11px 14px; font-size: 13px; }

/* BADGES */
.badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; font-family: var(--mono); }
.badge-plu { background: rgba(52,152,219,.15); color: var(--blue); border: 1px solid rgba(52,152,219,.3); }

/* ALERT */
.alert { background: rgba(245,166,35,.08); border: 1px solid rgba(245,166,35,.3); border-radius: var(--radius); padding: 16px; font-size: 13px; color: var(--accent); }

/* HOW IT WORKS */
.howto { display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap; }
.howto-step { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.howto-step .num { width: 28px; height: 28px; border-radius: 50%; background: var(--accent); color: #000; font-weight: 700; font-size: 12px; display: flex; align-items: center; justify-content: center; font-family: var(--mono); }
.howto-step .lbl { font-size: 11px; color: var(--text2); text-align: center; max-width: 90px; line-height: 1.4; }
.howto-arrow { color: var(--text2); font-size: 18px; padding-top: 6px; }
</style>
</head>
<body>

<nav class="topnav">
  <span class="brand">NCC POS</span>
  <a href="pos.php">🧾 Cashier</a>
  <a href="pos_products.php">📦 Products</a>
  <a href="pos_sales.php">📊 Sales</a>
  <a href="pos_stock.php">🏭 Stock</a>
  <a href="pos_reports.php">📈 Reports</a>
  <a href="pos_loyalty.php">⭐ Loyalty</a>
  <a href="pos_scale_export.php" class="active">⚖️ Scale PLU</a>
  <a href="pos_settings.php">⚙️ Settings</a>
</nav>

<div class="page">
    <div class="card">
        <div class="card-header">
            <h5>⚖️ Scale PLU Export</h5>
            <div class="sub">Export product list to your TEG TM-A LAN scale</div>
        </div>
        <div class="card-body">

            <!-- Format selector + download -->
            <div class="form-row">
                <div class="form-group">
                    <label>Scale Brand</label>
                    <select id="format-select">
                        <option value="cas">CAS Scale</option>
                        <option value="digi">DIGI / Teraoka</option>
                        <option value="generic">Generic / Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn btn-success" onclick="downloadExport()">⬇️ Download PLU File</button>
                </div>
                <div class="form-group">
                    <div class="info-box">📋 Copy this file to your scale's USB or network folder, then import via the scale management software.</div>
                </div>
            </div>

            <!-- Product table -->
            <h6 style="font-family:var(--mono);font-size:13px;color:var(--text2);margin-bottom:12px;">WEIGHT PRODUCTS (<?= count($products) ?> items)</h6>
            <?php if (empty($products)): ?>
                <div class="alert">⚠️ No weight products found. Go to <strong>Products</strong>, enable <strong>"Sold by Weight"</strong> and set a <strong>PLU code</strong> and <strong>Price per KG</strong> for each item.</div>
            <?php else: ?>
            <div class="table-wrap"><table>
                <thead>
                    <tr>
                        <th>PLU</th>
                        <th>Product Name</th>
                        <th>Price / KG (LBP)</th>
                        <th>Category</th>
                        <th>Last Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><span class="badge badge-plu"><?= str_pad((int)$p['plu_code'], 5, '0', STR_PAD_LEFT) ?></span></td>
                        <td><?= htmlspecialchars($p['nomp']) ?></td>
                        <td class="fw-bold">LL <?= number_format((float)$p['price_per_kg']) ?></td>
                        <td><?= htmlspecialchars($p['category'] ?? '') ?></td>
                        <td><span class="text-muted small"><?= date('d M Y') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>

        </div>
        <div class="card-footer">
            <strong style="color:var(--accent)">⚖️ How it works:</strong>
            Scale operator selects PLU → weighs item → prints EAN-13 label → cashier scans →
            POS reads PLU from barcode → auto-fills price. Update price only in POS then re-export here.
        </div>
    </div>
</div>
</div>

<script>
function downloadExport() {
    const fmt = document.getElementById('format-select').value;
    window.location = `pos_scale_export.php?download=1&format=${fmt}`;
}
</script>
</body>
</html>
