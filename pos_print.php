<?php
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }

$sale_id = (int)($_GET['id'] ?? 0);
if (!$sale_id) { echo "Invalid sale ID."; exit(); }

$conn = mysqli_connect("192.168.1.101","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

$sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pos_sales WHERE id = $sale_id LIMIT 1"));
if (!$sale) { echo "Sale not found."; exit(); }

$items_res = mysqli_query($conn, "SELECT * FROM pos_sale_items WHERE sale_id = $sale_id");
$items = [];
while ($r = mysqli_fetch_assoc($items_res)) $items[] = $r;

$co = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM company_settings LIMIT 1"));
$company_name    = $co['company_name']    ?? 'NCC CRM';
$company_phone   = $co['company_phone']   ?? '';
$company_address = $co['company_address'] ?? '';
$receipt_footer  = $co['receipt_footer']  ?? 'Thank you for your business!';
mysqli_close($conn);

$vat_rate   = isset($co['vat_rate'])   && $co['vat_rate']   !== null ? (float)$co['vat_rate']   : 0;
$usd_to_lbp = isset($co['usd_to_lbp']) && $co['usd_to_lbp'] !== null ? (float)$co['usd_to_lbp'] : 89500;

$gross_usd      = (float)$sale['total'];
$subtotal_usd   = (float)$sale['final_total'];
$vat_amount_usd = $vat_rate > 0 ? $subtotal_usd * ($vat_rate / 100) : 0;
$total_with_vat = $subtotal_usd + $vat_amount_usd;

$note        = 5000;
$gross_lbp   = round($gross_usd      * $usd_to_lbp);
$sub_lbp     = round($subtotal_usd   * $usd_to_lbp);
$vat_lbp     = round($vat_amount_usd * $usd_to_lbp);
$exact_lbp   = round($total_with_vat * $usd_to_lbp);
$due_lbp     = round($exact_lbp / $note) * $note;
$rounding    = $due_lbp - $exact_lbp;
$disc_lbp    = round((float)$sale['discount'] * $usd_to_lbp);
$has_discount = $sale['discount'] > 0;

$pay_labels = [
    'cash'=>'Cash','card'=>'Card','omt'=>'OMT','whish'=>'Whish',
    'bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','credit'=>'Credit'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt #<?= $sale_id ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family:'Courier New', Courier, monospace;
    font-size:12px;
    max-width:360px;
    margin:0 auto;
    padding:20px 16px;
    color:#111;
    background:#fff;
}

/* ── Header ── */
.header { text-align:center; margin-bottom:14px; }
.header .company { font-size:15px; font-weight:700; letter-spacing:.5px; }
.header .title   { font-size:13px; font-weight:700; margin:4px 0 2px; text-transform:uppercase; letter-spacing:1px; }
.header .sub     { font-size:11px; color:#555; line-height:1.7; }

/* ── Dividers ── */
.div-solid  { border:none; border-top:2px solid #111; margin:10px 0; }
.div-dashed { border:none; border-top:1px dashed #aaa; margin:8px 0; }

/* ── Info rows ── */
.info { display:flex; justify-content:space-between; padding:2px 0; font-size:12px; }
.info .lbl { color:#555; }
.info .val { font-weight:700; text-align:right; }

/* ── REFUNDED stamp ── */
.refunded {
    text-align:center; border:2px solid #111; padding:5px;
    font-weight:900; font-size:14px; letter-spacing:3px;
    margin:10px 0;
}

/* ── Items table ── */
.items-tbl { width:100%; border-collapse:collapse; margin:10px 0; font-size:11.5px; }
.items-tbl thead th {
    border-top:1px solid #111; border-bottom:1px solid #111;
    padding:4px 3px; text-align:left; font-weight:700;
    text-transform:uppercase; font-size:10px; letter-spacing:.3px;
}
.items-tbl thead th.r { text-align:right; }
.items-tbl thead th.c { text-align:center; }
.items-tbl tbody td { padding:5px 3px; border-bottom:1px dotted #ccc; vertical-align:top; }
.items-tbl tbody td.c { text-align:center; }
.items-tbl tbody td.r { text-align:right; font-weight:700; }
.items-tbl tbody tr:last-child td { border-bottom:none; }

/* ── Totals ── */
.t-row  { display:flex; justify-content:space-between; padding:3px 0; font-size:12px; color:#555; }
.t-row.vat    { color:#1a1a2e; font-weight:700; }
.t-row.disc   { color:#111; }
.t-row.rnd-dn { color:#111; font-weight:700; }
.t-row.rnd-up { color:#111; font-weight:700; }
.t-total { display:flex; justify-content:space-between; font-size:15px; font-weight:900; padding:6px 0 4px; }
.usd-box { text-align:center; border:1px dashed #aaa; padding:6px; margin:8px 0; font-size:11px; color:#555; }
.usd-box strong { font-size:14px; font-weight:900; color:#111; display:block; }

/* ── Payment details ── */
.pay-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#555; margin:10px 0 4px; }

/* ── Footer ── */
.footer { text-align:center; margin-top:14px; font-size:11px; color:#777; line-height:1.8; }

/* ── Print button (screen only) ── */
.print-btn {
    display:block; width:100%; padding:11px;
    background:#1976D2; color:white; border:none;
    border-radius:8px; font-size:13px; font-weight:700;
    cursor:pointer; margin-top:16px;
    font-family:'Segoe UI', Arial, sans-serif;
}
@media print {
    .print-btn { display:none !important; }
    body { padding:4px; }
}
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="company"><?= htmlspecialchars($company_name) ?></div>
    <?php if ($company_address): ?><div class="sub"><?= htmlspecialchars($company_address) ?></div><?php endif; ?>
    <?php if ($company_phone): ?><div class="sub">Tel: <?= htmlspecialchars($company_phone) ?></div><?php endif; ?>
    <div class="title">Sale Receipt</div>
    <div class="sub"><?= date('d M Y  H:i:s', strtotime($sale['created_at'])) ?></div>
</div>

<hr class="div-solid">

<!-- Sale info -->
<div class="info"><span class="lbl">Sale #</span><span class="val">#<?= $sale['id'] ?></span></div>
<div class="info"><span class="lbl">Customer</span><span class="val"><?= htmlspecialchars($sale['client_name']) ?></span></div>
<div class="info"><span class="lbl">Cashier</span><span class="val"><?= htmlspecialchars($sale['agent_name']) ?></span></div>
<div class="info"><span class="lbl">Payment</span><span class="val"><?= $pay_labels[$sale['payment_method']] ?? $sale['payment_method'] ?></span></div>

<?php if ($sale['status'] === 'refunded'): ?>
<div class="refunded">*** REFUNDED ***</div>
<?php endif; ?>

<hr class="div-dashed">

<!-- Items table -->
<table class="items-tbl">
    <thead>
        <tr>
            <th style="width:42%">Product</th>
            <th class="c" style="width:10%">Qty</th>
            <th class="r" style="width:24%">Unit Price</th>
            <th class="r" style="width:24%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item):
        $unit_lbp = round($item['unit_price'] * $usd_to_lbp);
        $item_lbp = round($item['subtotal']   * $usd_to_lbp);
    ?>
    <tr>
        <td><?= htmlspecialchars($item['product_name']) ?></td>
        <td class="c"><?= $item['qty'] ?></td>
        <td class="r">LL <?= number_format($unit_lbp, 0) ?></td>
        <td class="r">LL <?= number_format($item_lbp, 0) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<hr class="div-solid">

<!-- Totals -->
<?php if ($has_discount): ?>
<div class="t-row"><span>Subtotal</span><span>LL <?= number_format($gross_lbp, 0) ?></span></div>
<div class="t-row disc"><span>Discount</span><span>-LL <?= number_format($disc_lbp, 0) ?></span></div>
<hr class="div-dashed">
<?php endif; ?>

<?php if ($vat_rate > 0): ?>
<div class="t-row"><span>TOTAL excl. VAT</span><span>LL <?= number_format($sub_lbp, 0) ?></span></div>
<div class="t-row vat"><span>VAT (<?= $vat_rate ?>%)</span><span>LL <?= number_format($vat_lbp, 0) ?></span></div>
<hr class="div-dashed">
<div class="t-row"><span>TOTAL exact</span><span>LL <?= number_format($exact_lbp, 0) ?></span></div>
<?php else: ?>
<div class="t-row"><span>TOTAL exact</span><span>LL <?= number_format($exact_lbp, 0) ?></span></div>
<?php endif; ?>

<?php if ($rounding < 0): ?>
<div class="t-row rnd-dn">
    <span>Rounding</span>
    <span>LL <?= number_format($rounding, 0) ?></span>
</div>
<?php endif; ?>

<hr class="div-solid">
<div class="t-total"><span>TOTAL DUE</span><span>LL <?= number_format($due_lbp, 0) ?></span></div>

<div class="usd-box">
    <strong>$ <?= number_format($total_with_vat, 2) ?></strong>
    USD equivalent &nbsp;&middot;&nbsp; 1 USD = <?= number_format($usd_to_lbp, 0) ?> LBP
</div>

<!-- Payment details (cash only) -->
<?php
$show_pay = $sale['payment_method'] === 'cash' && ($sale['paid_usd'] > 0 || $sale['paid_lbp'] > 0);
if ($show_pay):
    $paid_total   = (float)$sale['paid_lbp'] + round((float)$sale['paid_usd'] * $usd_to_lbp);
    $net_lbp      = $paid_total - $due_lbp;
    $chg_usd      = (float)$sale['change_usd'];
    $chg_lbp      = (float)$sale['change_lbp'];
    $has_change   = $chg_usd > 0 || $chg_lbp > 0;
    $disp_rounding = ($net_lbp != 0) ? $net_lbp : $rounding;
    $dr_sign       = $disp_rounding > 0 ? '+' : '';
?>
<hr class="div-dashed">
<div class="pay-title">Payment Details</div>
<?php if ($sale['paid_lbp'] > 0): ?>
<div class="info"><span class="lbl">Paid LBP</span><span class="val">LL <?= number_format($sale['paid_lbp'], 0) ?></span></div>
<?php endif; ?>
<?php if ($sale['paid_usd'] > 0): ?>
<div class="info"><span class="lbl">Paid USD</span><span class="val">$ <?= number_format((float)$sale['paid_usd'], 2) ?></span></div>
<?php endif; ?>
<?php if ($has_change): ?>
<div class="info" style="font-weight:700;">
    <span class="lbl">Change</span>
    <span class="val"><?php
        $parts = [];
        if ($chg_usd > 0) $parts[] = '$ ' . number_format($chg_usd, 0);
        if ($chg_lbp > 0) $parts[] = 'LL ' . number_format($chg_lbp, 0);
        echo implode(' + ', $parts);
    ?></span>
</div>
<?php elseif ($net_lbp < 0): ?>
<div class="info" style="font-weight:700;color:#c00;">
    <span class="lbl">Remaining</span>
    <span class="val">LL <?= number_format(abs($net_lbp), 0) ?></span>
</div>
<?php elseif ($disp_rounding < 0): ?>
<div class="info" style="font-weight:700;color:#059669;">
    <span class="lbl">Rounding</span>
    <span class="val">LL <?= number_format($disp_rounding, 0) ?></span>
</div>
<?php endif; // has_change ?>
<?php endif; // show_pay ?>

<!-- Footer -->
<hr class="div-solid">
<div class="footer">
    <?= htmlspecialchars($receipt_footer) ?><br>
    <?= htmlspecialchars($company_name) ?> &mdash; <?= date('Y') ?>
</div>

<button class="print-btn" onclick="window.print()">🖨 Print Receipt</button>

<script>
window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 300);
});
</script>
</body>
</html>
