<?php
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$conn = mysqli_connect("172.18.208.1","root","1Sys9Admeen72","nccleb_test");
$co = mysqli_fetch_assoc(mysqli_query($conn, "SELECT usd_to_lbp, vat_rate FROM company_settings LIMIT 1"));
$usd_to_lbp = (float)($co['usd_to_lbp'] ?? 89500);
$vat_rate   = (float)($co['vat_rate']   ?? 0);
mysqli_set_charset($conn,'utf8mb4');

// Filters
$from    = mysqli_real_escape_string($conn, $_GET['from'] ?? date('Y-m-d'));
$to      = mysqli_real_escape_string($conn, $_GET['to'] ?? date('Y-m-d'));
$pay     = mysqli_real_escape_string($conn, $_GET['pay'] ?? '');
$search  = mysqli_real_escape_string($conn, $_GET['s'] ?? '');

$where = "WHERE DATE(s.created_at) BETWEEN '$from' AND '$to'";
if ($pay)    $where .= " AND s.payment_method = '$pay'";
if ($search) $where .= " AND (s.client_name LIKE '%$search%' OR s.id LIKE '%$search%')";

$sales = mysqli_query($conn,
    "SELECT s.*, 
     (SELECT COUNT(*) FROM pos_sale_items WHERE sale_id = s.id) as item_count
     FROM pos_sales s $where ORDER BY s.created_at DESC"
);

// Summary stats for the period
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total_sales,
     SUM(final_total) as revenue,
     SUM(discount) as total_discount,
     SUM(CASE WHEN payment_method='cash' THEN final_total ELSE 0 END) as cash_total,
     SUM(CASE WHEN payment_method='credit' THEN final_total ELSE 0 END) as credit_total
     FROM pos_sales s $where"
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Sales History — POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.topbar { background:linear-gradient(135deg,#1976D2,#0D47A1); color:white; padding:14px 24px; display:flex; align-items:center; gap:15px; }
.topbar h1 { font-size:18px; font-weight:700; }
.topbar a { color:white; text-decoration:none; background:rgba(255,255,255,.15); padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; }
.topbar a:hover { background:rgba(255,255,255,.25); }
.topbar a + a { margin-left:8px; }
.topbar .ml { margin-left:auto; }
.container { max-width:1200px; margin:0 auto; padding:24px 20px; }
.stats { display:grid; grid-template-columns:repeat(5,1fr); gap:15px; margin-bottom:24px; }
@media(max-width:800px){ .stats { grid-template-columns:repeat(2,1fr); } }
.stat { background:white; border-radius:10px; padding:18px; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,.06); border-left:4px solid #1976D2; }
.stat.green { border-color:#10b981; }
.stat.orange { border-color:#f59e0b; }
.stat.red { border-color:#ef4444; }
.stat .val { font-size:22px; font-weight:800; color:#1976D2; }
.stat.green .val { color:#10b981; }
.stat.orange .val { color:#f59e0b; }
.stat.red .val { color:#ef4444; }
.stat .lbl { font-size:12px; color:#6b7280; margin-top:4px; text-transform:uppercase; font-weight:600; }
.card { background:white; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.08); overflow:hidden; }
.card-header { padding:16px 22px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:10px; font-size:15px; font-weight:700; color:#1a1a2e; flex-wrap:wrap; }
.card-header i { color:#1976D2; }
.filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; padding:15px 22px; border-bottom:1px solid #e5e7eb; background:#fafbfc; }
.filters input, .filters select { padding:8px 12px; border:2px solid #e5e7eb; border-radius:8px; font-size:13px; }
.filters input:focus, .filters select:focus { outline:none; border-color:#1976D2; }
.btn { padding:9px 18px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
.btn-blue { background:#1976D2; color:white; }
table { width:100%; border-collapse:collapse; font-size:14px; }
th { padding:12px; background:#f8fafc; color:#374151; font-weight:700; text-align:left; border-bottom:2px solid #e5e7eb; }
td { padding:12px; border-bottom:1px solid #f3f4f6; color:#4b5563; }
tr:hover td { background:#fafafa; }
.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.pay-cash  { background:#d1fae5; color:#065f46; }
.pay-card  { background:#dbeafe; color:#1e40af; }
.pay-omt   { background:#ede9fe; color:#5b21b6; }
.pay-whish { background:#fce7f3; color:#9d174d; }
.pay-bank_transfer { background:#e0f2fe; color:#0369a1; }
.pay-cheque { background:#fef3c7; color:#92400e; }
.pay-credit { background:#fee2e2; color:#991b1b; }

/* Sale detail modal */
.modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; padding:20px; }
.modal.show { display:flex; }
.modal-box { background:white; border-radius:12px; width:100%; max-width:500px; max-height:90vh; overflow-y:auto; }
.modal-header { padding:18px 22px; border-bottom:1px solid #e5e7eb; font-size:16px; font-weight:700; display:flex; justify-content:space-between; align-items:center; }
.modal-close { background:none; border:none; font-size:20px; cursor:pointer; color:#6b7280; }
.modal-body { padding:22px; }
.detail-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f3f4f6; font-size:14px; }
.detail-row .lbl { color:#6b7280; }
.detail-row .val { font-weight:700; }
.items-table { width:100%; border-collapse:collapse; margin-top:12px; font-size:13px; }
.items-table th { padding:8px; background:#f8fafc; border-bottom:2px solid #e5e7eb; text-align:left; }
.items-table td { padding:8px; border-bottom:1px solid #f3f4f6; }
.total-line { display:flex; justify-content:space-between; font-size:16px; font-weight:800; padding:12px 0; border-top:2px solid #1a1a2e; margin-top:8px; }
@media print {
    body > *:not(.modal) { display:none !important; }
    .modal { display:flex !important; background:white !important; position:relative !important; }
    .modal-close, .modal-header button { display:none !important; }
}
</style>
</head>
<body>
<div class="topbar">
    <i class="fas fa-history fa-lg"></i>
    <h1>Sales History</h1>
    <a class="ml" href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
    <a href="pos_closing.php"><i class="fas fa-cash-register"></i> Closing</a>
    <a href="pos_archive.php"><i class="fas fa-archive"></i> Archive</a>
    <a href="test204.php?page=<?= urlencode($agent_name) ?>&page1=<?= $agent_id ?>"><i class="fas fa-arrow-left"></i> CRM</a>
</div>

<div class="container">

<!-- Stats -->
<div class="stats">
    <div class="stat"><div class="val"><?= $stats['total_sales'] ?></div><div class="lbl">Total Sales</div></div>
    <div class="stat green"><div class="val">LL <?= number_format(round(($stats['revenue'] ?? 0) * (1 + $vat_rate/100)), 0) ?></div><div class="lbl">Revenue (incl. VAT)</div></div>
    <div class="stat orange"><div class="val">LL <?= number_format(round($stats['total_discount'] ?? 0), 0) ?></div><div class="lbl">Discounts Given</div></div>
    <div class="stat"><div class="val">LL <?= number_format(round(($stats['cash_total'] ?? 0) * (1 + $vat_rate/100)), 0) ?></div><div class="lbl">Cash Collected</div></div>
    <div class="stat red"><div class="val">LL <?= number_format(round(($stats['credit_total'] ?? 0) * (1 + $vat_rate/100)), 0) ?></div><div class="lbl">On Credit</div></div>
</div>

<div class="card">
    <!-- Filters -->
    <form method="GET" class="filters">
        <input type="date" name="from" value="<?= $from ?>">
        <input type="date" name="to" value="<?= $to ?>">
        <select name="pay">
            <option value="">All Payments</option>
            <option value="cash" <?= $pay==='cash'?'selected':'' ?>>Cash</option>
            <option value="card" <?= $pay==='card'?'selected':'' ?>>Card</option>
            <option value="omt" <?= $pay==='omt'?'selected':'' ?>>OMT</option>
            <option value="whish" <?= $pay==='whish'?'selected':'' ?>>Whish</option>
            <option value="bank_transfer" <?= $pay==='bank_transfer'?'selected':'' ?>>Bank Transfer</option>
            <option value="credit" <?= $pay==='credit'?'selected':'' ?>>Credit</option>
        </select>
        <input type="text" name="s" value="<?= htmlspecialchars($_GET['s'] ?? '') ?>" placeholder="Search customer or sale #">
        <button type="submit" class="btn btn-blue"><i class="fas fa-search"></i> Filter</button>
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Sale #</th><th>Date</th><th>Customer</th>
                    <th>Items</th><th>Payment</th><th>Currency</th>
                    <th>Discount</th><th>Total</th><th>Cashier</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($sales) === 0): ?>
            <tr><td colspan="10" style="text-align:center;padding:40px;color:#9ca3af;">No sales found for this period.</td></tr>
            <?php else: while ($s = mysqli_fetch_assoc($sales)): ?>
            <tr>
                <td><strong>#<?= $s['id'] ?></strong></td>
                <td><?= date('d M Y H:i', strtotime($s['created_at'])) ?></td>
                <td><?= htmlspecialchars($s['client_name']) ?></td>
                <td><?= $s['item_count'] ?> item(s)</td>
                <td><span class="badge pay-<?= $s['payment_method'] ?>"><?= ucfirst(str_replace('_',' ',$s['payment_method'])) ?></span></td>
                <td><?= $s['currency'] ?></td>
                <td><?= $s['discount'] > 0 ? '-LL '.number_format(round($s['discount']),0) : '—' ?></td>
                <td><strong>LL <?= number_format(round(round((float)$s['final_total'] * (1 + $vat_rate/100) / 5000) * 5000), 0) ?></strong></td>
                <td><?= htmlspecialchars($s['agent_name']) ?></td>
                <td>
                    <button class="btn btn-blue" style="padding:6px 12px;font-size:12px;" onclick="viewSale(<?= $s['id'] ?>)">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<!-- Sale Detail Modal -->
<div class="modal" id="saleModal">
    <div class="modal-box">
        <div class="modal-header">
            <span><i class="fas fa-receipt"></i> Sale Details</span>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body" id="saleModalBody">
            <p style="text-align:center;color:#9ca3af;padding:30px;">Loading...</p>
        </div>
    </div>
</div>

<script>
var USD_TO_LBP = <?= $usd_to_lbp ?>;
var VAT_RATE   = <?= $vat_rate ?>;

function viewSale(id) {
    document.getElementById('saleModal').classList.add('show');
    document.getElementById('saleModalBody').innerHTML = '<p style="text-align:center;padding:30px;color:#9ca3af;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    fetch('ajax/pos_ajax.php?action=get_sale&id=' + id)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            var s = data.sale;
            var items = data.items;
            var payLabels = { cash:'Cash', card:'Card', omt:'OMT', whish:'Whish', bank_transfer:'Bank Transfer', cheque:'Cheque', credit:'Credit' };

            var html = '<div class="detail-row"><span class="lbl">Sale #</span><span class="val">#' + s.id + '</span></div>';
            html += '<div class="detail-row"><span class="lbl">Date</span><span class="val">' + s.created_at + '</span></div>';
            html += '<div class="detail-row"><span class="lbl">Customer</span><span class="val">' + escHtml(s.client_name) + '</span></div>';
            html += '<div class="detail-row"><span class="lbl">Payment</span><span class="val">' + (payLabels[s.payment_method] || s.payment_method) + '</span></div>';
            html += '<div class="detail-row"><span class="lbl">Currency</span><span class="val">' + s.currency + '</span></div>';
            html += '<div class="detail-row"><span class="lbl">Cashier</span><span class="val">' + escHtml(s.agent_name) + '</span></div>';

            html += '<table class="items-table"><thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead><tbody>';
            items.forEach(function(item) {
                var unitLbp    = Math.round(parseFloat(item.unit_price));
                var subtotalLbp = Math.round(parseFloat(item.subtotal));
                html += '<tr><td>' + escHtml(item.product_name) + '</td><td>' + item.qty + '</td>'
                      + '<td>LL ' + unitLbp.toLocaleString() + '</td>'
                      + '<td>LL ' + subtotalLbp.toLocaleString() + '</td></tr>';
            });
            html += '</tbody></table>';

            var grossLbp    = Math.round(parseFloat(s.total));
            var discountLbp = Math.round(parseFloat(s.discount));
            var taxBaseLbp  = Math.round(parseFloat(s.final_total));  // post-discount pre-VAT, LBPe-VAT
            var vatAmt      = Math.round(parseFloat(s.final_total) * (VAT_RATE/100));
            var exactTotal  = taxBaseLbp + vatAmt;                                  // exact — no auto-rounding
            var dueLbp      = Math.round(exactTotal / 5000) * 5000;                 // rounded to LL 5,000
            var rounding    = dueLbp - exactTotal;                                   // +/- diff

            html += '<div class="detail-row"><span class="lbl">Subtotal</span><span class="val">LL ' + grossLbp.toLocaleString() + '</span></div>';
            if (discountLbp > 0) {
                html += '<div class="detail-row"><span class="lbl">Discount</span><span class="val" style="color:#ef4444;">-LL ' + discountLbp.toLocaleString() + '</span></div>';
            }
            if (VAT_RATE > 0) {
                html += '<div class="detail-row"><span class="lbl">TOTAL excl. VAT</span><span class="val">LL ' + taxBaseLbp.toLocaleString() + '</span></div>';
                html += '<div class="detail-row"><span class="lbl" style="color:#1976D2;">VAT (' + VAT_RATE + '%)</span><span class="val" style="color:#1976D2;">LL ' + vatAmt.toLocaleString() + '</span></div>';
            }
            html += '<div class="detail-row" style="font-weight:700;"><span class="lbl">TOTAL exact</span><span class="val">LL ' + exactTotal.toLocaleString() + '</span></div>';
            if (rounding !== 0) {
                var rSign  = rounding > 0 ? '+' : '';
                var rColor = rounding >= 0 ? '#f59e0b' : '#059669';
                html += '<div class="detail-row"><span class="lbl" style="color:' + rColor + ';font-weight:700;">Rounding</span><span class="val" style="color:' + rColor + ';font-weight:700;">' + rSign + 'LL ' + rounding.toLocaleString() + '</span></div>';
            }
            html += '<div class="total-line"><span>TOTAL DUE</span><span>LL ' + dueLbp.toLocaleString() + '</span></div>';

            // Payment details (cash only)
            if (s.payment_method === 'cash' && (parseFloat(s.paid_lbp) > 0 || parseFloat(s.paid_usd) > 0)) {
                var paidTotal    = parseFloat(s.paid_lbp) + Math.round(parseFloat(s.paid_usd) * USD_TO_LBP);
                var payRounding  = paidTotal - dueLbp;
                var dispRounding = (payRounding !== 0) ? payRounding : rounding;
                html += '<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;margin:14px 0 6px;">Payment Details</div>';
                if (parseFloat(s.paid_usd) > 0)
                    html += '<div class="detail-row"><span class="lbl">Paid USD</span><span class="val">$ ' + parseFloat(s.paid_usd).toLocaleString() + '</span></div>';
                if (parseFloat(s.paid_lbp) > 0)
                    html += '<div class="detail-row"><span class="lbl">Paid LBP</span><span class="val">LL ' + Math.round(parseFloat(s.paid_lbp)).toLocaleString() + '</span></div>';
                if (dispRounding !== 0) {
                    var dSign  = dispRounding > 0 ? '+' : '';
                    var dColor = dispRounding >= 0 ? '#059669' : '#ef4444';
                    html += '<div class="detail-row"><span class="lbl" style="color:' + dColor + ';font-weight:700;">Rounding</span><span class="val" style="color:' + dColor + ';font-weight:700;">' + dSign + 'LL ' + dispRounding.toLocaleString() + '</span></div>';
                }
            }

            html += '<div style="margin-top:16px;text-align:center;"><a href="pos_print.php?id=' + s.id + '" target="_blank" class="btn btn-blue"><i class="fas fa-print"></i> Print Receipt</a></div>';

            document.getElementById('saleModalBody').innerHTML = html;
        });
}

function closeModal() {
    document.getElementById('saleModal').classList.remove('show');
}
document.getElementById('saleModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
