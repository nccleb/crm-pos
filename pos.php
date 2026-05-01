<?php
session_start();
if (empty($_SESSION['oop']) || empty($_SESSION['ooq'])) {
    header("Location: login200.php");
    exit();
}
$agent_name = $_SESSION['oop'];
$agent_id   = (int)$_SESSION['ooq'];

// DB for settings
$conn = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
mysqli_set_charset($conn, 'utf8mb4');

// Load categories
$cats = mysqli_query($conn, "SELECT * FROM pos_categories ORDER BY name");
$categories = [];
while ($c = mysqli_fetch_assoc($cats)) $categories[] = $c;

// Load cash drawer setting
$pos_settings     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM company_settings LIMIT 1")) ?: [];
$cash_drawer_mode = $pos_settings['cash_drawer']       ?? 'disabled';
$usd_to_lbp       = (float)($pos_settings['usd_to_lbp'] ?? 89500);
$usd_denoms       = $pos_settings['usd_denominations']  ?? '1,5,10,20,50,100';
$lbp_denoms       = $pos_settings['lbp_denominations']  ?? '5000,10000,20000,50000,100000';
$vat_rate         = (float)($pos_settings['vat_rate']   ?? 0);

// Pre-compute denomination arrays for JS
$usd_arr = array_values(array_filter(array_map('intval', explode(',', $usd_denoms))));
$lbp_arr = array_values(array_filter(array_map('intval', explode(',', $lbp_denoms))));
if (empty($usd_arr)) $usd_arr = [1,5,10,20,50,100];
if (empty($lbp_arr)) $lbp_arr = [5000,10000,20000,50000,100000];
$js_usd_denoms = json_encode($usd_arr);
$js_lbp_denoms = json_encode($lbp_arr);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS — NCC CRM</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; height:100vh; min-height:100vh; display:flex; flex-direction:column; overflow:hidden; }

/* ── Top Bar ── */
.topbar {
    background:linear-gradient(135deg,#1976D2,#0D47A1);
    color:white; padding:12px 20px;
    display:flex; align-items:center; gap:15px;
    box-shadow:0 2px 8px rgba(0,0,0,.2);
}
.topbar h1 { font-size:18px; font-weight:700; }
.topbar .agent { margin-left:auto; font-size:13px; opacity:.85; }
.topbar a {
    color:white; text-decoration:none; background:rgba(255,255,255,.15);
    padding:7px 14px; border-radius:6px; font-size:13px; font-weight:600;
    display:flex; align-items:center; gap:6px;
}
.topbar a:hover { background:rgba(255,255,255,.25); }

/* ── Layout ── */
.pos-layout {
    display:grid; grid-template-columns:1fr 380px;
    flex:1; overflow:hidden; gap:0; min-height:0;
}

/* ── Left Panel ── */
.left-panel { display:flex; flex-direction:column; overflow:hidden; padding:15px; gap:12px; }

/* Search bar */
.search-bar {
    display:flex; gap:10px;
}
.search-bar input {
    flex:1; padding:11px 16px; border:2px solid #e5e7eb;
    border-radius:8px; font-size:14px; transition:border-color .2s;
}
.search-bar input:focus { outline:none; border-color:#1976D2; }

/* Category filters */
.cat-filters { display:flex; gap:8px; flex-wrap:wrap; }
.cat-btn {
    padding:6px 14px; border-radius:20px; border:2px solid #e5e7eb;
    background:white; font-size:13px; font-weight:600; cursor:pointer;
    color:#6b7280; transition:all .2s;
}
.cat-btn.active, .cat-btn:hover {
    background:#1976D2; border-color:#1976D2; color:white;
}

/* Product grid */
.products-header { display:flex; align-items:center; justify-content:space-between; font-size:12px; color:#9ca3af; font-weight:600; padding:0 2px; }
.products-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:10px; overflow-y:auto; padding-bottom:10px; }
.product-card { background:white; border-radius:12px; overflow:hidden; cursor:pointer; border:2px solid #f0f2f5; transition:all .2s; box-shadow:0 1px 4px rgba(0,0,0,.06); display:flex; flex-direction:column; }
.product-card:hover { border-color:#1976D2; transform:translateY(-2px); box-shadow:0 6px 16px rgba(25,118,210,.15); }
.product-card.out-of-stock { opacity:.45; cursor:not-allowed; filter:grayscale(.4); }
.product-card.out-of-stock:hover { transform:none; box-shadow:0 1px 4px rgba(0,0,0,.06); border-color:#f0f2f5; }
.product-card .card-top { padding:14px 14px 8px; display:flex; flex-direction:column; align-items:center; flex:1; }
.product-card .icon { width:54px; height:54px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:22px; margin-bottom:10px; background:#eff6ff; color:#1976D2; overflow:hidden; flex-shrink:0; }
.product-card .icon img { width:54px; height:54px; object-fit:cover; border-radius:10px; }
.product-card .name { font-size:12px; font-weight:700; color:#1a1a2e; line-height:1.35; text-align:center; width:100%; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
.product-card .card-bottom { padding:8px 14px 12px; display:flex; align-items:center; justify-content:space-between; border-top:1px solid #f3f4f6; margin-top:8px; }
.product-card .price { font-size:14px; font-weight:800; color:#1976D2; }
.stock-pill { font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; white-space:nowrap; }
.stock-ok  { background:#d1fae5; color:#065f46; }
.stock-low { background:#fef3c7; color:#92400e; }
.stock-out { background:#fee2e2; color:#991b1b; }
.no-products { grid-column:1/-1; text-align:center; padding:60px 20px; color:#9ca3af; }
.no-products i { font-size:48px; margin-bottom:12px; display:block; }

/* ── Right Panel (Cart) ── */
.right-panel {
    background:white; display:flex; flex-direction:column;
    border-left:1px solid #e5e7eb;
    box-shadow:-2px 0 8px rgba(0,0,0,.05);
    overflow-y:auto;
}

.cart-header {
    padding:15px 18px; border-bottom:1px solid #e5e7eb;
    font-size:15px; font-weight:800; color:#1a1a2e;
    display:flex; align-items:center; gap:10px; flex-shrink:0;
}
.cart-header i { color:#1976D2; }
.cart-badge {
    margin-left:auto; background:#1976D2; color:white;
    width:24px; height:24px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:700;
}

/* Customer selector */
.customer-section { padding:12px 18px; border-bottom:1px solid #f3f4f6; flex-shrink:0; }
.customer-section label { font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase; margin-bottom:6px; display:block; }
.customer-search-wrap { position:relative; }
.customer-search-wrap input {
    width:100%; padding:9px 12px; border:2px solid #e5e7eb;
    border-radius:8px; font-size:13px; transition:border-color .2s;
}
.customer-search-wrap input:focus { outline:none; border-color:#1976D2; }
.customer-dropdown {
    position:absolute; top:100%; left:0; right:0; z-index:100;
    background:white; border:2px solid #1976D2; border-top:none;
    border-radius:0 0 8px 8px; max-height:200px; overflow-y:auto;
    display:none;
}
.customer-dropdown.show { display:block; }
.customer-item {
    padding:10px 12px; cursor:pointer; font-size:13px;
    border-bottom:1px solid #f3f4f6;
}
.customer-item:hover { background:#eff6ff; }
.customer-item .cname { font-weight:700; color:#1a1a2e; }
.customer-item .cnum  { color:#6b7280; font-size:12px; }
.selected-customer {
    display:flex; align-items:center; gap:8px;
    background:#eff6ff; padding:8px 10px; border-radius:8px;
    margin-top:8px; display:none;
}
.selected-customer .cname { font-size:13px; font-weight:700; color:#1976D2; flex:1; }
.selected-customer button {
    background:none; border:none; color:#ef4444; cursor:pointer; font-size:14px;
}

/* Cart items */
.cart-items { flex:1; overflow-y:auto; padding:8px 14px; min-height:0; }
.cart-empty { text-align:center; padding:40px 20px; color:#9ca3af; }
.cart-empty i { font-size:40px; margin-bottom:10px; display:block; }
.cart-item { background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; margin-bottom:8px; display:flex; flex-direction:column; gap:8px; }
.cart-item-top { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
.cart-item .item-name { font-size:13px; font-weight:700; color:#1a1a2e; line-height:1.3; flex:1; }
.cart-item .item-unit-price { font-size:11px; color:#9ca3af; white-space:nowrap; }
.cart-item-bottom { display:flex; align-items:center; justify-content:space-between; }
.qty-control { display:flex; align-items:center; gap:5px; }
.qty-btn { width:28px; height:28px; border-radius:7px; border:none; background:#e5e7eb; cursor:pointer; font-size:14px; font-weight:700; display:flex; align-items:center; justify-content:center; transition:all .15s; color:#374151; }
.qty-btn:hover { background:#1976D2; color:white; }
.qty-btn.remove { background:#fee2e2; color:#ef4444; }
.qty-btn.remove:hover { background:#ef4444; color:white; }
.qty-input { width:38px; text-align:center; border:2px solid #e5e7eb; border-radius:7px; padding:4px; font-size:13px; font-weight:700; color:#1a1a2e; }
.cart-item .item-subtotal { font-size:14px; font-weight:800; color:#1976D2; }

/* Cart footer — slim 2-step */
.cart-footer { padding:12px 14px; border-top:2px solid #e5e7eb; flex-shrink:0; background:white; }
.cart-summary { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:10px; }
.cart-summary .lbl { font-size:12px; color:#9ca3af; font-weight:600; text-transform:uppercase; }
.cart-summary .total-big { font-size:22px; font-weight:900; color:#1a1a2e; }
.btn-charge { width:100%; padding:14px; border:none; border-radius:12px; background:linear-gradient(135deg,#10b981,#059669); color:white; font-size:16px; font-weight:800; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; transition:all .2s; }
.btn-charge:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(16,185,129,.35); }
.btn-charge:disabled { background:#d1d5db; color:#9ca3af; cursor:not-allowed; transform:none; box-shadow:none; }
.btn-clear { width:100%; padding:8px; background:transparent; color:#ef4444; border:none; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; margin-top:6px; }
.btn-clear:hover { background:#fee2e2; }

/* Checkout modal */
.checkout-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:900; align-items:flex-end; justify-content:center; }
.checkout-overlay.show { display:flex; }
.checkout-sheet { background:white; width:100%; max-width:520px; border-radius:20px 20px 0 0; padding:20px 20px 24px; animation:slideUp .25s ease; max-height:93vh; overflow-y:auto; }
@keyframes slideUp { from { transform:translateY(40px); opacity:0; } to { transform:translateY(0); opacity:1; } }
.checkout-handle { width:40px; height:4px; background:#e5e7eb; border-radius:4px; margin:0 auto 16px; }
.checkout-title { font-size:16px; font-weight:800; color:#1a1a2e; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
.pay-section { margin-bottom:10px; }
.pay-section-label { font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; display:block; }
.pay-input-row { display:flex; gap:6px; align-items:center; margin-bottom:5px; }
.pay-input-row .sym { font-size:13px; font-weight:800; color:#374151; min-width:28px; }
.pay-input-row input { flex:1; padding:9px 12px; border:2px solid #e5e7eb; border-radius:10px; font-size:15px; font-weight:700; text-align:right; outline:none; }
.pay-input-row input:focus { border-color:#1976D2; }
.denom-row { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:4px; }
.denom-btn { padding:4px 8px; border:2px solid #e5e7eb; border-radius:7px; background:white; font-size:11px; font-weight:700; cursor:pointer; color:#374151; transition:all .15s; }
.denom-btn:hover { border-color:#1976D2; color:#1976D2; background:#eff6ff; }
.denom-btn.lbp:hover { border-color:#059669; color:#059669; background:#f0fdf4; }
.pay-summary { background:#f8fafc; border-radius:10px; padding:10px 12px; margin:8px 0; }
.pay-row { display:flex; justify-content:space-between; font-size:12px; padding:2px 0; color:#6b7280; }
.pay-row.total-row { font-size:14px; font-weight:800; color:#1a1a2e; border-top:2px solid #e5e7eb; margin-top:5px; padding-top:7px; }
.pay-row.due-row { font-size:13px; font-weight:800; color:#ef4444; }
.pay-row.due-row.ok { color:#10b981; }
.change-box { border-radius:10px; padding:10px 12px; margin:8px 0; display:none; }
.change-box.show { display:block; }
.change-box.positive { background:#d1fae5; border:2px solid #6ee7b7; }
.change-box.negative { background:#fee2e2; border:2px solid #fca5a5; }
.change-title { font-size:10px; font-weight:700; text-transform:uppercase; margin-bottom:4px; }
.change-box.positive .change-title { color:#065f46; }
.change-box.negative .change-title { color:#991b1b; }
.change-line { font-size:15px; font-weight:900; }
.change-box.positive .change-line { color:#059669; }
.change-box.negative .change-line { color:#ef4444; }
.change-sub { font-size:11px; color:#374151; margin-top:3px; }
.discount-input-wrap { display:flex; align-items:center; border:2px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:8px; }
.discount-input-wrap span { padding:9px 12px; background:#f8fafc; color:#6b7280; font-size:13px; font-weight:700; border-right:2px solid #e5e7eb; }
.discount-input-wrap input { flex:1; padding:9px 12px; border:none; font-size:14px; font-weight:700; outline:none; }
.currency-row { display:flex; gap:6px; margin-bottom:8px; }
.curr-btn { flex:1; padding:8px 4px; border:2px solid #e5e7eb; border-radius:10px; background:white; font-size:12px; font-weight:700; cursor:pointer; color:#6b7280; transition:all .2s; text-align:center; }
.curr-btn.active { background:#059669; border-color:#059669; color:white; }
.payment-btns { display:grid; grid-template-columns:repeat(3,1fr); gap:7px; margin-bottom:10px; }
.pay-btn { padding:9px 4px; border:2px solid #e5e7eb; border-radius:10px; background:white; font-size:11px; font-weight:700; cursor:pointer; color:#6b7280; transition:all .2s; text-align:center; line-height:1.6; }
.pay-btn i { display:block; font-size:15px; margin-bottom:2px; }
.pay-btn.active { background:#1976D2; border-color:#1976D2; color:white; }
.pay-btn:hover:not(.active) { border-color:#1976D2; color:#1976D2; }
.btn-confirm-charge { width:100%; padding:13px; border:none; border-radius:12px; background:linear-gradient(135deg,#10b981,#059669); color:white; font-size:15px; font-weight:800; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; transition:all .2s; margin-top:6px; }
.btn-confirm-charge:disabled { background:#d1d5db; color:#9ca3af; cursor:not-allowed; }

/* ── Receipt Modal ── */
.modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.6); z-index:1000;
    align-items:center; justify-content:center;
}
.modal-overlay.show { display:flex; }
.receipt-modal {
    background:white; border-radius:16px; width:100%; max-width:480px;
    max-height:90vh; overflow-y:auto;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
    animation:popIn .2s ease;
}
@keyframes popIn {
    from { opacity:0; transform:scale(.95); }
    to   { opacity:1; transform:scale(1); }
}
.receipt-header { padding:20px; text-align:center; border-bottom:2px dashed #e5e7eb; }
.receipt-header h2 { font-size:20px; font-weight:800; color:#1a1a2e; }
.receipt-header p  { font-size:13px; color:#6b7280; margin-top:4px; }
.receipt-body { padding:20px; }
.receipt-row { display:flex; justify-content:space-between; font-size:13px; padding:6px 0; border-bottom:1px solid #f3f4f6; }
.receipt-row .label { color:#6b7280; }
.receipt-row .value { font-weight:700; color:#1a1a2e; }
.receipt-items { margin:15px 0; }
.receipt-item-row { display:flex; justify-content:space-between; font-size:13px; padding:5px 0; }
.receipt-total-row { display:flex; justify-content:space-between; font-size:16px; font-weight:800; padding:12px 0; border-top:2px solid #1a1a2e; margin-top:8px; }
.receipt-actions { padding:15px 20px; display:flex; gap:10px; border-top:1px solid #e5e7eb; }
.btn-print { flex:1; padding:12px; background:#1976D2; color:white; border:none; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; }
.btn-print:hover { background:#0D47A1; }
.btn-new-sale { flex:1; padding:12px; background:#10b981; color:white; border:none; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; }
.btn-new-sale:hover { background:#059669; }
</style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
    <i class="fas fa-cash-register fa-lg"></i>
    <h1>Point of Sale</h1>
    <div class="agent"><i class="fas fa-user"></i> <?= htmlspecialchars($agent_name) ?></div>
    <a href="pos_sales.php"><i class="fas fa-history"></i> Sales History</a>
    <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
    <a href="pos_stock.php"><i class="fas fa-boxes"></i> Stock</a>
    <a href="pos_receiving.php"><i class="fas fa-truck-loading"></i> Receiving</a>
    <a href="pos_suppliers.php"><i class="fas fa-building"></i> Suppliers</a>
    <a href="pos_archive.php"><i class="fas fa-archive"></i> Archive</a>
    <?php if ($agent_name === 'super'): ?>
    <a href="pos_settings.php"><i class="fas fa-cog"></i> Settings</a>
    <?php endif; ?>
    <a href="test204.php?page=<?= urlencode($agent_name) ?>&page1=<?= $agent_id ?>"><i class="fas fa-arrow-left"></i> Back to CRM</a>
</div>

<!-- POS Layout -->
<div class="pos-layout">

    <!-- ── Left: Products ── -->
    <div class="left-panel">

        <!-- Search -->
        <div class="search-bar">
            <input type="text" id="productSearch" placeholder="🔍  Search products by name or barcode..." autocomplete="off">
        </div>

        <!-- Category filters -->
        <div class="cat-filters">
            <button class="cat-btn active" data-cat="all">All</button>
            <?php foreach ($categories as $cat): ?>
            <button class="cat-btn" data-cat="<?= htmlspecialchars($cat['name']) ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Products grid -->
        <div class="products-header"><span id="productsCount"></span></div>
        <div class="products-grid" id="productsGrid"></div>

    </div>

    <!-- ── Right: Cart ── -->
    <div class="right-panel">

        <div class="cart-header">
            <i class="fas fa-shopping-cart"></i> Cart
            <div class="cart-badge" id="cartCount">0</div>
        </div>

        <!-- Customer selector -->
        <div class="customer-section">
            <label><i class="fas fa-user"></i> Customer</label>
            <div class="customer-search-wrap">
                <input type="text" id="customerSearch" placeholder="Search customer or leave for walk-in..." autocomplete="off">
                <div class="customer-dropdown" id="customerDropdown"></div>
            </div>
            <div class="selected-customer" id="selectedCustomer">
                <i class="fas fa-user-check" style="color:#1976D2;"></i>
                <span class="cname" id="selectedCustomerName"></span>
                <button onclick="clearCustomer()" title="Remove"><i class="fas fa-times"></i></button>
            </div>
        </div>

        <!-- Cart items -->
        <div class="cart-items" id="cartItems">
            <div class="cart-empty">
                <i class="fas fa-shopping-cart"></i>
                <p>Cart is empty</p>
                <p style="font-size:12px;margin-top:4px;">Click products to add them</p>
            </div>
        </div>

        <!-- Cart footer — slim -->
        <div class="cart-footer">
            <div class="cart-summary">
                <div><div class="lbl">Total</div><div class="total-big" id="totalDisplay">$0.00</div></div>
                <div style="text-align:right;"><div class="lbl">Items</div><div style="font-size:15px;font-weight:800;color:#1a1a2e;" id="itemCountDisplay">0</div></div>
            </div>
            <button class="btn-charge" id="chargeBtn" onclick="openCheckout()" disabled>
                <i class="fas fa-bolt"></i> Charge <span id="chargeBtnTotal">$0.00</span>
            </button>
            <button class="btn-clear" onclick="clearCart()"><i class="fas fa-trash"></i> Clear Cart</button>
            <?php if ($cash_drawer_mode !== 'disabled'): ?>
            <button class="btn-clear" style="color:#059669;" onclick="openDrawer()">
                <i class="fas fa-cash-register"></i> Open Cash Drawer
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Checkout Modal — Dual Currency Payment -->
<div class="checkout-overlay" id="checkoutOverlay" onclick="closeCheckout(event)">
    <div class="checkout-sheet">
        <div class="checkout-handle"></div>
        <div class="checkout-title"><i class="fas fa-cash-register" style="color:#1976D2;"></i> Payment</div>

        <!-- Summary -->
        <div class="pay-summary">
            <div class="pay-row" id="coSubtotalRow"><span>Subtotal</span><span id="coSubtotal">LL 0</span></div>
            <div class="pay-row" id="coDiscountRow" style="display:none;"><span>Discount</span><span id="coDiscount" style="color:#ef4444;">-LL 0</span></div>
            <div class="pay-row" id="coVatExclRow" style="display:none;"><span id="coVatExclLbl">TOTAL excl. VAT</span><span id="coVatExcl">LL 0</span></div>
            <div class="pay-row" id="coVatRow" style="display:none;color:#1976D2;font-weight:700;"><span id="coVatLbl">VAT</span><span id="coVatAmt">LL 0</span></div>
            <div class="pay-row" id="coExactRow" style="display:none;"><span>TOTAL exact</span><span id="coExact">LL 0</span></div>
            <div class="pay-row" id="coRoundingRow" style="display:none;font-weight:700;"><span>Rounding</span><span id="coRounding">LL 0</span></div>
            <div class="pay-row total-row"><span>TOTAL DUE</span><span id="coTotal">LL 0</span></div>
            <div class="pay-row" id="coLbpRow" style="color:#1976D2;font-size:11px;display:none;"><span>= USD equivalent</span><span id="coLbpEquiv">$ 0.00</span></div>
        </div>

        <!-- Discount -->
        <div class="pay-section">
            <span class="pay-section-label"><i class="fas fa-tag"></i> Discount (LL)</span>
            <div class="discount-input-wrap">
                <span>LL</span>
                <input type="number" id="discountInput" value="0" min="0" step="1000" oninput="updateTotals()" placeholder="0">
            </div>
        </div>

        <!-- Payment Method (non-cash: card/omt/whish/bank/credit) -->
        <div class="pay-section">
            <span class="pay-section-label"><i class="fas fa-wallet"></i> Payment Method</span>
            <div class="payment-btns">
                <button class="pay-btn active" onclick="setPayment('cash',this)"><i class="fas fa-money-bill-wave"></i>Cash</button>
                <button class="pay-btn" onclick="setPayment('card',this)"><i class="fas fa-credit-card"></i>Card</button>
                <button class="pay-btn" onclick="setPayment('omt',this)"><i class="fas fa-mobile-alt"></i>OMT</button>
                <button class="pay-btn" onclick="setPayment('whish',this)"><i class="fas fa-mobile-alt"></i>Whish</button>
                <button class="pay-btn" onclick="setPayment('bank_transfer',this)"><i class="fas fa-university"></i>Bank</button>
                <button class="pay-btn" onclick="setPayment('credit',this)"><i class="fas fa-clock"></i>Credit</button>
            </div>
        </div>

        <!-- Cash payment section — shown only for cash -->
        <div id="cashPaymentSection">
            <!-- LBP paid — primary -->
            <div class="pay-section">
                <span class="pay-section-label">LL LBP received</span>
                <div class="pay-input-row">
                    <span class="sym">LL</span>
                    <input type="number" id="paidLbp" value="" min="0" step="1000" placeholder="0" oninput="calcChange()">
                </div>
                <div class="denom-row" id="lbpDenomBtns"></div>
                <div id="lbpSuggestion" style="font-size:11px;color:#1976D2;font-weight:700;margin-top:4px;display:none;"></div>
            </div>

            <!-- USD paid — secondary -->
            <div class="pay-section">
                <span class="pay-section-label">$ USD received</span>
                <div class="pay-input-row">
                    <span class="sym">$</span>
                    <input type="number" id="paidUsd" value="" min="0" step="1" placeholder="0" oninput="calcChange()">
                </div>
                <div class="denom-row" id="usdDenomBtns"></div>
            </div>

            <!-- Running total paid -->
            <div class="pay-summary">
                <div class="pay-row"><span>Paid LBP</span><span id="summPaidLbp">LL 0</span></div>
                <div class="pay-row"><span>Paid USD (in LBP)</span><span id="summPaidUsd">LL 0</span></div>
                <div class="pay-row"><span>Total Paid</span><span id="summPaidTotal">LL 0</span></div>
                <div class="pay-row total-row" id="summRemaining" style="display:none;"><span></span><span id="summRemainingAmt"></span></div>
            </div>

            <!-- Change due -->
            <div class="change-box" id="changeBox">
                <div class="change-title" id="changeTitle">CHANGE DUE</div>
                <div class="change-line" id="changeAmount"></div>
                <div class="change-sub" id="changeSub"></div>
                <!-- Editable change split — shown when change > 0 -->
                <div id="changeSplitWrap" style="display:none;margin-top:10px;border-top:1px solid rgba(0,0,0,.1);padding-top:10px;">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#065f46;margin-bottom:6px;">Give back:</div>
                    <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px;">
                        <span style="font-size:12px;font-weight:700;min-width:20px;">$</span>
                        <input type="number" id="giveBackUsd" min="0" step="1" placeholder="0"
                            style="flex:1;padding:6px 8px;border:1px solid #6ee7b7;border-radius:6px;font-size:13px;font-weight:700;background:white;"
                            oninput="adjustChangeSplit()">
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <span style="font-size:12px;font-weight:700;min-width:20px;">LL</span>
                        <input type="number" id="giveBackLbp" min="0" step="5000" placeholder="0"
                            style="flex:1;padding:6px 8px;border:1px solid #6ee7b7;border-radius:6px;font-size:13px;font-weight:700;background:#f0fdf4;color:#065f46;" readonly>
                    </div>
                    <div id="changeSplitWarning" style="font-size:11px;color:#dc2626;margin-top:5px;display:none;">
                        ⚠ Give-back exceeds change due
                    </div>
                </div>
            </div>
        </div>

        <!-- Non-cash currency selector -->
        <div id="nonCashSection" style="display:none;">
            <div class="pay-section">
                <span class="pay-section-label"><i class="fas fa-globe"></i> Currency</span>
                <div class="currency-row">
                    <button class="curr-btn" onclick="setCurrency('USD',this)">$ USD</button>
                    <button class="curr-btn active" onclick="setCurrency('LBP',this)">LL LBP</button>
                    <button class="curr-btn" onclick="setCurrency('EUR',this)">&#8364; EUR</button>
                </div>
            </div>
        </div>

        <button class="btn-confirm-charge" id="confirmChargeBtn" onclick="completeSale()" disabled>
            <i class="fas fa-check-circle"></i> Confirm &amp; Charge <span id="confirmTotal">$0.00</span>
        </button>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal-overlay" id="receiptModal">
    <div class="receipt-modal">
        <div class="receipt-header">
            <h2><i class="fas fa-receipt"></i> Sale Receipt</h2>
            <p id="receiptDate"></p>
        </div>
        <div class="receipt-body">
            <div class="receipt-row">
                <span class="label">Sale #</span>
                <span class="value" id="receiptSaleId"></span>
            </div>
            <div class="receipt-row">
                <span class="label">Customer</span>
                <span class="value" id="receiptCustomer"></span>
            </div>
            <div class="receipt-row">
                <span class="label">Cashier</span>
                <span class="value"><?= htmlspecialchars($agent_name) ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Payment</span>
                <span class="value" id="receiptPayment"></span>
            </div>
            <div class="receipt-items" id="receiptItems"></div>
            <div class="receipt-row" id="receiptSubtotalRow">
                <span class="label">Subtotal</span>
                <span class="value" id="receiptSubtotal"></span>
            </div>
            <div class="receipt-row" id="receiptDiscountRow" style="display:none;">
                <span class="label" style="color:#ef4444;">Discount</span>
                <span class="value" style="color:#ef4444;" id="receiptDiscount"></span>
            </div>
            <div class="receipt-row" id="receiptVatExclRow" style="display:none;">
                <span class="label">TOTAL excl. VAT</span>
                <span class="value" id="receiptVatExcl"></span>
            </div>
            <div class="receipt-row" id="receiptVatRow" style="display:none;color:#1976D2;font-weight:700;">
                <span class="label" id="receiptVatLbl">VAT</span>
                <span class="value" id="receiptVatAmt"></span>
            </div>
            <div class="receipt-row" id="receiptExactRow" style="display:none;">
                <span class="label">TOTAL exact</span>
                <span class="value" id="receiptExact"></span>
            </div>
            <div class="receipt-row" id="receiptRoundingRow" style="display:none;font-weight:700;">
                <span class="label" id="receiptRoundingLbl">Rounding</span>
                <span class="value" id="receiptRounding"></span>
            </div>
            <div class="receipt-total-row" id="receiptTotalRow">
                <span>TOTAL DUE</span>
                <span id="receiptTotal"></span>
            </div>
            <div id="receiptPaymentDetails"></div>
        </div>
        <div class="receipt-actions">
            <button class="btn-print" onclick="printReceipt()"><i class="fas fa-print"></i> Print</button>
            <button class="btn-new-sale" onclick="newSale()"><i class="fas fa-plus"></i> New Sale</button>
        </div>
    </div>
</div>

<script>
// ── State ─────────────────────────────────────────────────────────────────
var cart             = [];
var currentSaleId    = null;
var selectedClientId   = null;
var selectedClientName = 'Walk-in Customer';
var paymentMethod = 'cash';
var currency      = 'LBP';
var searchTimeout = null;
var customerTimeout = null;
var currentCategory = 'all';

var catColors = { 'General':'#6b7280','Hardware':'#2563eb','Software':'#7c3aed','Services':'#059669','Accessories':'#d97706','default':'#1976D2' };
var catIcons  = { 'General':'fa-box','Hardware':'fa-microchip','Software':'fa-laptop-code','Services':'fa-concierge-bell','Accessories':'fa-plug','default':'fa-box' };

// ── Load products ─────────────────────────────────────────────────────────
function loadProducts(query, category, autoAdd, showAll) {
    var grid = document.getElementById('productsGrid');

    // Show "start typing" only if no query, no category, and not explicitly loading all
    if (!query && category === 'all' && !showAll) {
        grid.innerHTML = '<div class="no-products"><i class="fas fa-box-open"></i><p>Start typing to search products</p></div>';
        document.getElementById('productsCount').textContent = '';
        return;
    }

    grid.innerHTML = '<div class="no-products"><i class="fas fa-spinner fa-spin"></i><p>Loading...</p></div>';
    fetch('ajax/pos_ajax.php?action=search_products&q=' + encodeURIComponent(query || '') + '&cat=' + encodeURIComponent((category && category !== 'all') ? category : ''))
        .then(r => r.json())
        .then(data => {
            if (!data.success || data.data.length === 0) {
                document.getElementById('productsCount').textContent = '0 products';
                grid.innerHTML = '<div class="no-products"><i class="fas fa-box-open"></i><p>No products found</p></div>';
                return;
            }
            var filtered = data.data; // Already filtered by DB
            document.getElementById('productsCount').textContent = filtered.length + ' product' + (filtered.length !== 1 ? 's' : '');

            // Barcode scanner: if Enter was pressed and exactly 1 result
            if (autoAdd && filtered.length === 1) {
                var p = filtered[0];
                var lbpPriceA = Math.round(parseFloat(p.price));  // price stored as LBP
                if (p.is_weighted == 1) {
                    openWeightModal(p.codep, p.nomp, lbpPriceA, p.unit || 'kg');
                } else if (parseInt(p.onhand) > 0) {
                    addToCart(p.codep, p.nomp, lbpPriceA, false, p.unit || 'pc');
                }
                searchInput.value = '';
                searchInput.style.borderColor = '#10b981';
                setTimeout(function() { searchInput.style.borderColor = ''; }, 600);
                loadProducts('', currentCategory, false, true);
                searchInput.focus();
                return;
            }
            grid.innerHTML = filtered.map(p => {
                var stock = parseFloat(p.onhand);
                var outOfStock = !p.is_weighted && stock <= 0;
                var lowStock   = !p.is_weighted && stock > 0 && stock <= 5;
                var cat = p.category || 'default';
                var dotColor = catColors[cat] || catColors['default'];
                var icon     = catIcons[cat]  || catIcons['default'];
                var lbpPrice = Math.round(parseFloat(p.price));  // price stored as LBP
                var unit = p.unit || (p.is_weighted ? 'kg' : 'pc');

                var stockClass, stockText, clickFn, priceLabel, weightBadge = '';
                if (p.is_weighted == 1) {
                    stockClass  = 'stock-ok';
                    stockText   = stock > 0 ? '⚖ ' + stock + ' ' + unit + ' left' : '⚖ Weigh item';
                    priceLabel  = 'LL ' + lbpPrice.toLocaleString() + ' / ' + unit;
                    clickFn     = 'openWeightModal(' + p.codep + ',' + JSON.stringify(p.nomp) + ',' + lbpPrice + ',' + JSON.stringify(unit) + ')';
                    weightBadge = '<span style=\'position:absolute;top:5px;right:5px;background:#7c3aed;color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:10px;\'>⚖ KG</span>';
                } else {
                    stockClass = outOfStock ? 'stock-out' : (lowStock ? 'stock-low' : 'stock-ok');
                    stockText  = outOfStock ? '✗ Out of stock' : (lowStock ? '⚠ ' + stock + ' left' : stock + ' in stock');
                    priceLabel = 'LL ' + lbpPrice.toLocaleString();
                    clickFn    = outOfStock ? '' : 'addToCart(' + p.codep + ',' + JSON.stringify(p.nomp) + ',' + lbpPrice + ',false,' + JSON.stringify(unit) + ')';
                    weightBadge = '';
                }

                var imgHtml = p.image
                    ? '<img src=\'uploads/products/' + p.image + '\' style=\'width:54px;height:54px;object-fit:cover;border-radius:10px;\' onerror=\'this.style.display="none"\'>'
                    : '<i class=\'fas ' + icon + '\' style=\'font-size:22px;color:' + dotColor + ';\'></i>';

                return '<div class=\'product-card' + (outOfStock ? ' out-of-stock' : '') + '\' style=\'position:relative;\' onclick=\'' + clickFn + '\'>' +
                    weightBadge +
                    '<div class=\'card-top\'>' +
                        '<div class=\'icon\' style=\'background:' + dotColor + '18;\'>' + imgHtml + '</div>' +
                        '<div class=\'name\'>' + escHtml(p.nomp) + '</div>' +
                    '</div>' +
                    '<div class=\'card-bottom\'>' +
                        '<span class=\'price\'>' + priceLabel + '</span>' +
                        '<span class=\'stock-pill ' + stockClass + '\'>' + stockText + '</span>' +
                    '</div>' +
                '</div>';
            }).join('');
        });
}

var searchInput = document.getElementById('productSearch');

// Auto-focus search on page load — barcode scanner ready immediately
searchInput.focus();

// Re-focus search when clicking on products panel (but NOT if clicking customer search area)
document.querySelector('.left-panel').addEventListener('click', function(e) {
    if (!e.target.closest('button') && !e.target.closest('.cat-btn')) {
        searchInput.focus();
    }
});

// Do NOT steal focus from customer search
document.getElementById('customerSearch').addEventListener('focus', function() {
    // When customer search is focused, disable product search auto-refocus temporarily
});

// Handle barcode scanner — fires Enter after typing barcode
searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchTimeout);
        var q = this.value.trim();
        if (!q) return;
        loadProducts(q, currentCategory, true);
    }
});

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadProducts(this.value.trim(), currentCategory, false), 300);
});

document.querySelectorAll('.cat-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentCategory = this.dataset.cat;
        // Use current search value, or load all for the selected category
        var q = document.getElementById('productSearch').value.trim();
        loadProducts(q, currentCategory, false, true);
    });
});

// ── Cart ──────────────────────────────────────────────────────────────────
function addToCart(productId, productName, unitPrice, isWeighted, unit) {
    if (isWeighted) {
        // Weighted items — never merge, always a new line (each weighing is unique)
        return; // should be called via addWeightedToCart instead
    }
    var existing = cart.find(i => i.product_id === productId && !i.is_weighted);
    if (existing) { existing.qty++; } else {
        cart.push({ product_id: productId, product_name: productName,
                    unit_price: parseFloat(unitPrice), qty: 1,
                    is_weighted: false, unit: unit || 'pc' });
    }
    renderCart();
}

function addWeightedToCart(productId, productName, unitPrice, weightKg, unit) {
    // Each weighing is its own cart line
    cart.push({ product_id: productId, product_name: productName,
                unit_price: parseFloat(unitPrice), qty: weightKg,
                is_weighted: true, unit: unit || 'kg' });
    renderCart();
}

function renderCart() {
    var container = document.getElementById('cartItems');
    var count = cart.reduce((s, i) => s + (i.is_weighted ? 1 : i.qty), 0);
    document.getElementById('cartCount').textContent        = cart.length;
    document.getElementById('itemCountDisplay').textContent = cart.length;
    document.getElementById('chargeBtn').disabled = cart.length === 0;

    if (cart.length === 0) {
        container.innerHTML = '<div class="cart-empty"><i class="fas fa-shopping-cart"></i><p>Cart is empty</p><p style="font-size:12px;margin-top:4px;">Click products to add them</p></div>';
        updateTotals(); return;
    }
    container.innerHTML = cart.map((item, idx) => {
        var subtotal = Math.round(item.unit_price * item.qty);
        if (item.is_weighted) {
            // Weight display — no +/- buttons, show weight × price/kg
            var weightDisplay = item.qty >= 1
                ? item.qty.toFixed(3) + ' ' + item.unit
                : (item.qty * 1000).toFixed(0) + 'g';
            return '<div class="cart-item" style="border-left:3px solid #7c3aed;">' +
                '<div class="cart-item-top">' +
                    '<div class="item-name">' + escHtml(item.product_name) +
                        '<span style="font-size:10px;background:#ede9fe;color:#7c3aed;padding:1px 6px;border-radius:8px;margin-left:6px;font-weight:700;">⚖ ' + weightDisplay + '</span>' +
                    '</div>' +
                    '<div class="item-unit-price">LL ' + Math.round(item.unit_price).toLocaleString() + ' / ' + item.unit + '</div>' +
                '</div>' +
                '<div class="cart-item-bottom">' +
                    '<div class="qty-control">' +
                        '<button class="qty-btn" style="background:#ede9fe;color:#7c3aed;" onclick="reweighItem(' + idx + ')" title="Change weight"><i class="fas fa-balance-scale" style="font-size:11px;"></i></button>' +
                        '<span style="padding:0 8px;font-size:12px;font-weight:700;color:#7c3aed;">' + weightDisplay + '</span>' +
                        '<button class="qty-btn remove" onclick="removeItem(' + idx + ')" title="Remove"><i class="fas fa-trash-alt" style="font-size:11px;"></i></button>' +
                    '</div>' +
                    '<div class="item-subtotal">LL ' + subtotal.toLocaleString() + '</div>' +
                '</div>' +
            '</div>';
        } else {
            return '<div class="cart-item">' +
                '<div class="cart-item-top">' +
                    '<div class="item-name">' + escHtml(item.product_name) + '</div>' +
                    '<div class="item-unit-price">LL ' + Math.round(item.unit_price).toLocaleString() + ' / ' + (item.unit || 'pc') + '</div>' +
                '</div>' +
                '<div class="cart-item-bottom">' +
                    '<div class="qty-control">' +
                        '<button class="qty-btn" onclick="changeQty(' + idx + ',-1)">−</button>' +
                        '<input class="qty-input" type="number" value="' + item.qty + '" min="1" onchange="setQty(' + idx + ',this.value)">' +
                        '<button class="qty-btn" onclick="changeQty(' + idx + ',1)">+</button>' +
                        '<button class="qty-btn remove" onclick="removeItem(' + idx + ')" title="Remove"><i class="fas fa-trash-alt" style="font-size:11px;"></i></button>' +
                    '</div>' +
                    '<div class="item-subtotal">LL ' + subtotal.toLocaleString() + '</div>' +
                '</div>' +
            '</div>';
        }
    }).join('');
    updateTotals();
}

function changeQty(idx, delta) { if(!cart[idx].is_weighted) { cart[idx].qty = Math.max(1, cart[idx].qty + delta); renderCart(); } }
function setQty(idx, val) { if(!cart[idx].is_weighted) { cart[idx].qty = Math.max(1, parseInt(val) || 1); renderCart(); } }
function removeItem(idx) { cart.splice(idx, 1); renderCart(); }
function reweighItem(idx) {
    var item = cart[idx];
    // Remove this weighted line then re-open modal
    cart.splice(idx, 1);
    renderCart();
    openWeightModal(item.product_id, item.product_name, item.unit_price, item.unit);
}
function clearCart() { if (cart.length === 0) return; if (!confirm('Clear the cart?')) return; cart = []; renderCart(); }

function updateTotals() {
    var subtotal  = cart.reduce((s, i) => s + i.unit_price * i.qty, 0); // LBP
    var discount  = parseFloat(document.getElementById('discountInput') ?
                    document.getElementById('discountInput').value : 0) || 0;
    var afterDisc = Math.max(0, subtotal - discount);
    var vatAmt    = VAT_RATE > 0 ? Math.round(afterDisc * (VAT_RATE / 100)) : 0;
    var exactTotal = Math.round(afterDisc + vatAmt);
    var total      = Math.round(exactTotal / LBP_ROUND) * LBP_ROUND; // rounded to nearest LL 5,000
    var rounding   = total - exactTotal;

    // Subtotal
    var coSubtotal = document.getElementById('coSubtotal');
    var coSubtotalRow = document.getElementById('coSubtotalRow');
    if (coSubtotal) coSubtotal.textContent = 'LL ' + Math.round(subtotal).toLocaleString();
    if (coSubtotalRow) coSubtotalRow.style.display = discount > 0 ? 'flex' : 'none';

    // Discount
    var discRow = document.getElementById('coDiscountRow');
    if (discRow) {
        discRow.style.display = discount > 0 ? 'flex' : 'none';
        var coDisc = document.getElementById('coDiscount');
        if (coDisc) coDisc.textContent = '-LL ' + Math.round(discount).toLocaleString();
    }

    // VAT breakdown rows
    var vatExclRow = document.getElementById('coVatExclRow');
    var vatRow     = document.getElementById('coVatRow');
    var exactRow   = document.getElementById('coExactRow');
    if (vatExclRow) vatExclRow.style.display = VAT_RATE > 0 ? 'flex' : 'none';
    if (vatRow)     vatRow.style.display     = VAT_RATE > 0 ? 'flex' : 'none';
    if (exactRow)   exactRow.style.display   = VAT_RATE > 0 ? 'flex' : 'none';
    var coVatExcl = document.getElementById('coVatExcl');
    var coVatAmt  = document.getElementById('coVatAmt');
    var coVatLbl  = document.getElementById('coVatLbl');
    var coExact   = document.getElementById('coExact');
    if (coVatExcl) coVatExcl.textContent = 'LL ' + Math.round(afterDisc).toLocaleString();
    if (coVatAmt)  coVatAmt.textContent  = 'LL ' + vatAmt.toLocaleString();
    if (coVatLbl)  coVatLbl.textContent  = 'VAT (' + VAT_RATE + '%)';
    if (coExact)   coExact.textContent   = 'LL ' + exactTotal.toLocaleString();

    // Rounding row
    var roundRow = document.getElementById('coRoundingRow');
    var coRound  = document.getElementById('coRounding');
    if (roundRow && coRound) {
        roundRow.style.display = rounding !== 0 ? 'flex' : 'none';
        var rSign  = rounding > 0 ? '+' : '';
        var rColor = rounding >= 0 ? '#f59e0b' : '#059669';
        roundRow.style.color = rColor;
        coRound.textContent  = rSign + 'LL ' + rounding.toLocaleString();
    }

    // TOTAL DUE
    document.getElementById('totalDisplay').textContent   = 'LL ' + total.toLocaleString();
    document.getElementById('chargeBtnTotal').textContent = 'LL ' + total.toLocaleString();
    var coTotal = document.getElementById('coTotal');
    var confirmBtn = document.getElementById('confirmChargeBtn');
    if (coTotal)    coTotal.textContent       = 'LL ' + total.toLocaleString();
    if (confirmBtn) confirmBtn.dataset.total  = total;

    // USD equivalent
    var usdEquiv = total / USD_TO_LBP;
    var lbpRow   = document.getElementById('coLbpRow');
    if (lbpRow) {
        lbpRow.style.display = total > 0 ? 'flex' : 'none';
        var lbpEquivEl = document.getElementById('coLbpEquiv');
        if (lbpEquivEl) lbpEquivEl.textContent = '$ ' + usdEquiv.toFixed(2);
    }

    if (paymentMethod === 'cash') calcChange();
}

// ── Customer search ───────────────────────────────────────────────────────
document.getElementById('customerSearch').addEventListener('input', function() {
    clearTimeout(customerTimeout);
    var q = this.value.trim();
    if (q.length < 2) { document.getElementById('customerDropdown').classList.remove('show'); return; }
    customerTimeout = setTimeout(() => {
        fetch('ajax/pos_ajax.php?action=search_clients&q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                var dd = document.getElementById('customerDropdown');
                if (!data.success || data.data.length === 0) {
                    dd.innerHTML = '<div class="customer-item"><span class="cname">No clients found</span></div>';
                } else {
                    dd.innerHTML = data.data.map(c =>
                        '<div class="customer-item" onclick="selectCustomer(' + c.id + ',\'' + escJs(c.name) + '\')">' +
                            '<div class="cname">' + escHtml(c.name) + (c.company ? ' — ' + escHtml(c.company) : '') + '</div>' +
                            '<div class="cnum">' + (c.number || '') + '</div>' +
                        '</div>'
                    ).join('');
                }
                dd.classList.add('show');
            });
    }, 300);
});

function selectCustomer(id, name) {
    selectedClientId = id; selectedClientName = name;
    document.getElementById('customerSearch').value = '';
    document.getElementById('customerDropdown').classList.remove('show');
    document.getElementById('selectedCustomerName').textContent = name;
    document.getElementById('selectedCustomer').style.display = 'flex';
}
function clearCustomer() { selectedClientId = null; selectedClientName = 'Walk-in Customer'; document.getElementById('selectedCustomer').style.display = 'none'; }
document.addEventListener('click', e => { if (!e.target.closest('.customer-search-wrap')) document.getElementById('customerDropdown').classList.remove('show'); });

// ── Settings passed from PHP ──────────────────────────────────────────────
var USD_TO_LBP = <?= json_encode((float)($pos_settings['usd_to_lbp'] ?? 89500)) ?>;
var VAT_RATE   = <?= json_encode((float)($pos_settings['vat_rate'] ?? 0)) ?>;
var USD_DENOMS = <?= $js_usd_denoms ?>;
var LBP_DENOMS = <?= $js_lbp_denoms ?>;
var LBP_ROUND  = 5000; // smallest useful denomination in Lebanon

// ── Checkout modal ────────────────────────────────────────────────────────
function openCheckout() {
    if (cart.length === 0) return;
    updateTotals();
    buildDenomButtons();

    // Auto-suggest payment split: whole USD + LBP for cents
    var subtotal  = cart.reduce((s, i) => s + i.unit_price * i.qty, 0); // LBP
    var discount  = parseFloat(document.getElementById('discountInput').value) || 0;
    var afterDisc = Math.max(0, subtotal - discount);
    var vatAmt    = VAT_RATE > 0 ? Math.round(afterDisc * (VAT_RATE / 100)) : 0;
    var total     = Math.round(afterDisc + vatAmt); // total in LBP

    // Pre-fill: suggest exact LBP amount rounded to nearest LBP_ROUND (5,000)
    // < 2,500 mod → round down (customer pays less); ≥ 2,500 mod → round up
    var suggestLbp = Math.round(total / LBP_ROUND) * LBP_ROUND;
    document.getElementById('paidUsd').value = '';
    document.getElementById('paidLbp').value = suggestLbp > 0 ? suggestLbp : '';

    calcChange();
    document.getElementById('checkoutOverlay').classList.add('show');
}
function closeCheckout(e) {
    if (!e || e.target === document.getElementById('checkoutOverlay')) {
        document.getElementById('checkoutOverlay').classList.remove('show');
        searchInput.focus();
    }
}

// Build denomination buttons from PHP settings
function buildDenomButtons() {
    var usdDiv = document.getElementById('usdDenomBtns');
    var lbpDiv = document.getElementById('lbpDenomBtns');
    usdDiv.innerHTML = USD_DENOMS.map(function(d) {
        return '<button class="denom-btn" onclick="addDenom(\'usd\',' + d + ')">$' + d + '</button>';
    }).join('');
    lbpDiv.innerHTML = LBP_DENOMS.map(function(d) {
        return '<button class="denom-btn lbp" onclick="addDenom(\'lbp\',' + d + ')">LL ' + d.toLocaleString() + '</button>';
    }).join('');
}

function addDenom(type, amount) {
    if (type === 'usd') {
        var cur = parseFloat(document.getElementById('paidUsd').value) || 0;
        document.getElementById('paidUsd').value = cur + amount;
    } else {
        var cur = parseFloat(document.getElementById('paidLbp').value) || 0;
        document.getElementById('paidLbp').value = cur + amount;
    }
    calcChange();
}

// ── Payment & Currency ─────────────────────────────────────────────────────
function setPayment(method, btn) {
    paymentMethod = method;
    document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    // Show/hide cash vs non-cash sections
    var isCash = method === 'cash';
    document.getElementById('cashPaymentSection').style.display  = isCash ? '' : 'none';
    document.getElementById('nonCashSection').style.display      = isCash ? 'none' : '';
    // For non-cash always enable confirm button
    if (!isCash) {
        document.getElementById('confirmChargeBtn').disabled = false;
    } else {
        calcChange();
    }
}

function setCurrency(curr, btn) {
    currency = curr;
    document.querySelectorAll('.curr-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    updateTotals();
}

// ── Change calculation (all in LBP) ──────────────────────────────────────
function calcChange() {
    var subtotal   = cart.reduce((s, i) => s + i.unit_price * i.qty, 0);
    var discount   = parseFloat(document.getElementById('discountInput').value) || 0;
    var afterDisc  = Math.max(0, subtotal - discount);
    var vatAmt     = VAT_RATE > 0 ? Math.round(afterDisc * (VAT_RATE / 100)) : 0;
    var rawTotal   = Math.round(afterDisc + vatAmt);                            // exact
    var totalDue   = Math.round(rawTotal / LBP_ROUND) * LBP_ROUND;             // rounded to LL 5,000
    var rounding   = totalDue - rawTotal;                                        // + up, - down

    var paidLbp1   = parseFloat(document.getElementById('paidLbp').value) || 0;
    var paidUsdVal = parseFloat(document.getElementById('paidUsd').value) || 0;
    var paidUsdLbp = Math.round(paidUsdVal * USD_TO_LBP);
    var totalPaid  = paidLbp1 + paidUsdLbp;
    var net        = totalPaid - totalDue;

    document.getElementById('summPaidUsd').textContent   = 'LL ' + paidUsdLbp.toLocaleString() + ' ($ ' + paidUsdVal.toFixed(0) + ')';
    document.getElementById('summPaidLbp').textContent   = 'LL ' + Math.round(paidLbp1).toLocaleString();
    document.getElementById('summPaidTotal').textContent = 'LL ' + Math.round(totalPaid).toLocaleString();

    var remEl  = document.getElementById('summRemaining');
    var remAmt = document.getElementById('summRemainingAmt');
    var changeBox  = document.getElementById('changeBox');
    var confirmBtn = document.getElementById('confirmChargeBtn');

    // Rounding note shown in suggestion area
    var roundSign = rounding > 0 ? '+' : '';
    var roundText = rounding !== 0
        ? 'Rounded ' + roundSign + 'LL ' + Math.abs(rounding).toLocaleString() + (rounding < 0 ? ' (store absorbs)' : ' (customer pays more)')
        : 'Exact amount';
    document.getElementById('lbpSuggestion').style.display = '';
    document.getElementById('lbpSuggestion').textContent   = roundText;

    confirmBtn.disabled = (totalPaid === 0);

    if (totalPaid === 0) {
        remAmt.textContent  = 'LL ' + totalDue.toLocaleString();
        remEl.className     = 'pay-row total-row due-row';
        changeBox.className = 'change-box';
        return;
    }

    if (net >= 0) {
        // Auto-suggest split: USD bills first, LBP remainder
        var suggestUsd = Math.floor(net / USD_TO_LBP);
        var suggestRem = net - (suggestUsd * USD_TO_LBP);
        var suggestLbp = Math.round(suggestRem / LBP_ROUND) * LBP_ROUND;

        remAmt.textContent  = 'LL 0';
        remEl.className     = 'pay-row total-row due-row ok';
        changeBox.className = 'change-box show positive';
        document.getElementById('changeTitle').textContent = 'CHANGE DUE';

        // Show total change amount
        var changeParts = [];
        if (suggestUsd > 0) changeParts.push('$ ' + suggestUsd.toLocaleString());
        if (suggestLbp > 0) changeParts.push('LL ' + suggestLbp.toLocaleString());
        if (changeParts.length === 0) changeParts.push('LL 0');
        document.getElementById('changeAmount').textContent = 'Total: ' + changeParts.join(' + ');
        document.getElementById('changeSub').textContent   = '';

        // Show split inputs
        var splitWrap = document.getElementById('changeSplitWrap');
        splitWrap.style.display = net > 0 ? 'block' : 'none';
        if (net > 0) {
            var gbu = document.getElementById('giveBackUsd');
            var gbl = document.getElementById('giveBackLbp');
            // Always auto-fill — cashier can override giveBackUsd which recalculates gbl
            gbu.value = suggestUsd > 0 ? suggestUsd : 0;
            gbl.value = suggestLbp > 0 ? suggestLbp : 0;
            adjustChangeSplit();
        }
    } else {
        remAmt.textContent  = 'LL ' + Math.round(totalPaid).toLocaleString();
        remEl.className     = 'pay-row total-row due-row';
        changeBox.className = 'change-box show negative';
        document.getElementById('changeTitle').textContent  = 'REMAINING';
        document.getElementById('changeAmount').textContent = '-LL ' + Math.round(Math.abs(net)).toLocaleString();
        document.getElementById('changeSub').textContent   = 'Cashier: collect or round down';
    }
}

// updateTotals defined above

// ── Complete sale ─────────────────────────────────────────────────────────
// ── Cashier adjusts change split ──────────────────────────────────────────
function adjustChangeSplit() {
    var subtotal   = cart.reduce((s, i) => s + i.unit_price * i.qty, 0);
    var discount   = parseFloat(document.getElementById('discountInput').value) || 0;
    var afterDisc  = Math.max(0, subtotal - discount);
    var vatAmt     = VAT_RATE > 0 ? Math.round(afterDisc * (VAT_RATE / 100)) : 0;
    var totalDue   = Math.round((afterDisc + vatAmt) / LBP_ROUND) * LBP_ROUND;
    var paidLbp    = parseFloat(document.getElementById('paidLbp').value) || 0;
    var paidUsdVal = parseFloat(document.getElementById('paidUsd').value) || 0;
    var totalPaid  = paidLbp + Math.round(paidUsdVal * USD_TO_LBP);
    var netChange  = totalPaid - totalDue; // total change due in LBP

    var gbu         = parseFloat(document.getElementById('giveBackUsd').value) || 0;
    var gbuInLbp    = Math.round(gbu * USD_TO_LBP);
    var lbpRemainder = netChange - gbuInLbp;

    // Auto-fill LBP field: remainder rounded to LL 5,000, min 0
    var gblField = document.getElementById('giveBackLbp');
    var autoLbp  = Math.round(Math.max(0, lbpRemainder) / LBP_ROUND) * LBP_ROUND;
    gblField.value = autoLbp > 0 ? autoLbp : 0;

    var warning = document.getElementById('changeSplitWarning');
    warning.style.display = gbuInLbp > netChange + 1000 ? 'block' : 'none';
}

function completeSale() {
    if (cart.length === 0) return;
    var subtotal  = cart.reduce((s, i) => s + i.unit_price * i.qty, 0); // LBP
    var discount  = parseFloat(document.getElementById('discountInput').value) || 0;
    var afterDisc = Math.max(0, subtotal - discount);
    var vatAmt    = VAT_RATE > 0 ? Math.round(afterDisc * (VAT_RATE / 100)) : 0;
    var total     = Math.round((afterDisc + vatAmt) / LBP_ROUND) * LBP_ROUND; // rounded LL 5,000

    var paidUsd = 0, paidLbp = 0, changeUsd = 0, changeLbp = 0;
    if (paymentMethod === 'cash') {
        paidUsd = parseFloat(document.getElementById('paidUsd').value) || 0;
        paidLbp = parseFloat(document.getElementById('paidLbp').value) || 0;
        var totalPaidLbp = paidLbp + Math.round(paidUsd * USD_TO_LBP);
        var netLbp = totalPaidLbp - total;
        if (netLbp > 0) {
            // Auto-calculate split
            changeUsd = Math.floor(netLbp / USD_TO_LBP);
            var rem   = netLbp - (changeUsd * USD_TO_LBP);
            changeLbp = Math.round(rem / LBP_ROUND) * LBP_ROUND;
            // Override with cashier's manual split if give-back USD field was changed
            var gbu = parseFloat(document.getElementById('giveBackUsd').value) || 0;
            var gbl = parseFloat(document.getElementById('giveBackLbp').value) || 0;
            if (gbu !== changeUsd || gbl !== changeLbp) {
                // Cashier customised the split
                changeUsd = gbu;
                changeLbp = gbl;
            }
        }
    }

    var btn = document.getElementById('confirmChargeBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    var fd = new FormData();
    fd.append('action',         'complete_sale');
    fd.append('client_id',      selectedClientId || '');
    fd.append('client_name',    selectedClientName);
    fd.append('payment_method', paymentMethod);
    fd.append('currency',       'LBP');
    fd.append('discount',       discount);      // send in LBP — pos_ajax.php converts to USD
    fd.append('paid_usd',       paidUsd);
    fd.append('paid_lbp',       paidLbp);
    fd.append('change_usd',     changeUsd);
    fd.append('change_lbp',     changeLbp);
    fd.append('exact_lbp',      Math.round(afterDisc + vatAmt)); // exact LBP before LL 5,000 rounding
    fd.append('items',          JSON.stringify(cart));

    fetch('ajax/pos_ajax.php', { method:'POST', body:fd })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('checkoutOverlay').classList.remove('show');
                showReceipt(data.sale_id, total, subtotal, discount, paidUsd, paidLbp, changeUsd, changeLbp);
            } else {
                alert('Sale error: ' + (data.error || 'Unknown error'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm & Charge';
            }
        })
        .catch(err => {
            alert('Connection error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm & Charge';
        });
}

function showReceipt(saleId, total, subtotal, discount, paidUsd, paidLbp, changeUsd, changeLbp) {
    currentSaleId = saleId;
    var payLabels = { cash:'Cash', card:'Card', omt:'OMT', whish:'Whish', bank_transfer:'Bank Transfer', credit:'Credit' };

    document.getElementById('receiptSaleId').textContent   = '#' + saleId;
    document.getElementById('receiptDate').textContent     = new Date().toLocaleString();
    document.getElementById('receiptCustomer').textContent = selectedClientName;
    document.getElementById('receiptPayment').textContent  = payLabels[paymentMethod] || paymentMethod;

    // Items
    document.getElementById('receiptItems').innerHTML =
        '<div style="margin:10px 0;font-weight:700;font-size:12px;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;padding-bottom:6px;">Items</div>' +
        cart.map(item => '<div class="receipt-item-row"><span>' + escHtml(item.product_name) + ' x' + item.qty + '</span><span>LL ' + Math.round(item.unit_price * item.qty).toLocaleString() + '</span></div>').join('');

    // Totals breakdown
    var afterDisc  = Math.max(0, subtotal - discount);
    var vatAmt     = VAT_RATE > 0 ? Math.round(afterDisc * (VAT_RATE / 100)) : 0;
    var exactTotal = Math.round(afterDisc + vatAmt);
    var rounding   = total - exactTotal; // total is already LL-5000 rounded

    document.getElementById('receiptSubtotal').textContent = 'LL ' + Math.round(subtotal).toLocaleString();
    document.getElementById('receiptSubtotalRow').style.display = discount > 0 ? 'flex' : 'none';

    // Discount
    var discRow = document.getElementById('receiptDiscountRow');
    discRow.style.display = discount > 0 ? 'flex' : 'none';
    if (discount > 0) document.getElementById('receiptDiscount').textContent = '-LL ' + Math.round(discount).toLocaleString();

    // VAT breakdown
    var showVat = VAT_RATE > 0;
    document.getElementById('receiptVatExclRow').style.display = showVat ? 'flex' : 'none';
    document.getElementById('receiptVatRow').style.display     = showVat ? 'flex' : 'none';
    document.getElementById('receiptExactRow').style.display   = showVat ? 'flex' : 'none';
    if (showVat) {
        document.getElementById('receiptVatExcl').textContent = 'LL ' + Math.round(afterDisc).toLocaleString();
        document.getElementById('receiptVatLbl').textContent  = 'VAT (' + VAT_RATE + '%)';
        document.getElementById('receiptVatAmt').textContent  = 'LL ' + vatAmt.toLocaleString();
        document.getElementById('receiptExact').textContent   = 'LL ' + exactTotal.toLocaleString();
    }

    // Rounding
    var roundRow = document.getElementById('receiptRoundingRow');
    roundRow.style.display = rounding !== 0 ? 'flex' : 'none';
    if (rounding !== 0) {
        var rSign  = rounding > 0 ? '+' : '';
        var rColor = rounding >= 0 ? '#f59e0b' : '#059669';
        document.getElementById('receiptRoundingLbl').style.color = rColor;
        document.getElementById('receiptRounding').style.color    = rColor;
        document.getElementById('receiptRounding').textContent    = rSign + 'LL ' + rounding.toLocaleString();
    }

    // TOTAL DUE
    document.getElementById('receiptTotal').textContent = 'LL ' + total.toLocaleString();

    // Payment details (cash only)
    var payDetails = document.getElementById('receiptPaymentDetails');
    payDetails.innerHTML = '';
    if (paymentMethod === 'cash' && (paidUsd > 0 || paidLbp > 0)) {
        var totalPaidLbp = paidLbp + Math.round(paidUsd * USD_TO_LBP);
        var netLbp       = totalPaidLbp - total; // total is already rounded to LL 5,000
        var html = '<div style="border-top:1px dashed #e5e7eb;margin-top:10px;padding-top:10px;">';
        if (paidUsd > 0) html += '<div class="receipt-row"><span class="label">Paid USD</span><span class="value">$ ' + paidUsd.toLocaleString() + '</span></div>';
        if (paidLbp > 0) html += '<div class="receipt-row"><span class="label">Paid LBP</span><span class="value">LL ' + Math.round(paidLbp).toLocaleString() + '</span></div>';
        if (changeUsd > 0 || changeLbp > 0) {
            var cParts = [];
            if (changeUsd > 0) cParts.push('$ ' + changeUsd.toLocaleString());
            if (changeLbp > 0) cParts.push('LL ' + changeLbp.toLocaleString());
            html += '<div class="receipt-row" style="font-weight:700;color:#059669;"><span class="label">Change</span><span class="value">' + cParts.join(' + ') + '</span></div>';
        } else if (netLbp < 0) {
            html += '<div class="receipt-row" style="font-weight:700;color:#ef4444;"><span class="label">Remaining</span><span class="value">LL ' + Math.abs(netLbp).toLocaleString() + '</span></div>';
        } else {
            // Exact payment — show the LL 5,000 rounding
            var dispRounding = rounding;
            if (dispRounding !== 0) {
                var dSign  = dispRounding > 0 ? '+' : '';
                var dColor = dispRounding >= 0 ? '#059669' : '#ef4444';
                html += '<div class="receipt-row" style="font-weight:700;color:' + dColor + ';"><span class="label">Rounding</span><span class="value">' + dSign + 'LL ' + dispRounding.toLocaleString() + '</span></div>';
            }
        }
        html += '</div>';
        payDetails.innerHTML = html;
    }

    document.getElementById('receiptModal').classList.add('show');
}

function newSale() {
    document.getElementById('receiptModal').classList.remove('show');
    cart = [];
    currentSaleId = null;
    selectedClientId = null;
    selectedClientName = 'Walk-in Customer';
    paymentMethod = 'cash';

    // Reset inputs safely
    var discInput = document.getElementById('discountInput');
    if (discInput) discInput.value = 0;
    var paidUsdEl = document.getElementById('paidUsd');
    if (paidUsdEl) paidUsdEl.value = '';
    var paidLbpEl = document.getElementById('paidLbp');
    if (paidLbpEl) paidLbpEl.value = '';
    var gbu = document.getElementById('giveBackUsd');
    var gbl = document.getElementById('giveBackLbp');
    if (gbu) gbu.value = '';
    if (gbl) gbl.value = '';

    // Reset change box
    var changeBox = document.getElementById('changeBox');
    if (changeBox) changeBox.className = 'change-box';

    // Reset confirm button
    var confirmBtn = document.getElementById('confirmChargeBtn');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm &amp; Charge $0.00';
    }

    clearCustomer();
    renderCart();
    loadProducts('', currentCategory, false, true);
    searchInput.focus();
}

// ── Print receipt ─────────────────────────────────────────────────────────
function printReceipt() {
    if (!currentSaleId) return;
    window.open('pos_print.php?id=' + currentSaleId, '_blank');
}

// ── Cash drawer ───────────────────────────────────────────────────────────
function openDrawer() {
    fetch('pos_escpos.php?action=open_drawer')
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                console.warn('Drawer:', data.error);
            }
        })
        .catch(err => console.warn('Drawer error:', err));
}

// ── Helpers ───────────────────────────────────────────────────────────────
function escHtml(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function escJs(str) { return String(str).replace(/'/g,"\\'").replace(/"/g,'\\"'); }

// Load all products immediately + again on load for reliability
fetch('ajax/pos_ajax.php?action=search_products&q=')
    .then(r => r.json())
    .then(data => { console.log('Direct test:', data.success, data.data ? data.data.length : 0); })
    .catch(e => { console.error('Fetch failed:', e); });
loadProducts('', 'all', false, true);
window.addEventListener('load', function() {
    loadProducts('', 'all', false, true);
    searchInput.focus();
});
</script>

<!-- ═══════════════════════════════════════════════════
     WEIGHT MODAL
═══════════════════════════════════════════════════ -->
<div id="weightModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:3000;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;width:340px;box-shadow:0 20px 60px rgba(0,0,0,.35);overflow:hidden;">

    <!-- Header -->
    <div style="background:#7c3aed;color:#fff;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <div style="font-weight:800;font-size:1rem;" id="wModalName">Product</div>
        <div style="font-size:.78rem;opacity:.8;" id="wModalPrice">LL 0 / kg</div>
      </div>
      <button onclick="closeWeightModal()" style="background:rgba(255,255,255,.2);border:none;color:#fff;border-radius:50%;width:30px;height:30px;cursor:pointer;font-size:1rem;">✕</button>
    </div>

    <!-- Weight display -->
    <div style="padding:16px 18px 8px;text-align:center;">
      <div style="font-size:.72rem;color:#9ca3af;text-transform:uppercase;font-weight:700;margin-bottom:4px;">Enter Weight</div>

      <!-- Scale hint -->
      <div style="font-size:.72rem;color:#7c3aed;margin-bottom:8px;">
        <i class="fas fa-scale-balanced"></i>
        USB scale: place item on scale — weight appears automatically
      </div>

      <!-- Weight input (auto-focused, scale types here) -->
      <div style="display:flex;gap:6px;margin-bottom:8px;">
        <input id="wInput" type="number" min="0" step="0.001"
               style="flex:1;font-size:1.8rem;font-weight:800;text-align:center;border:2px solid #7c3aed;border-radius:10px;padding:8px;color:#4c1d95;"
               placeholder="0.000"
               oninput="updateWeightCalc()"
               onkeydown="if(event.key==='Enter') confirmWeight()">
        <div style="display:flex;flex-direction:column;gap:4px;">
          <button id="wUnitKg" onclick="setWeightUnit('kg')"
                  style="padding:6px 10px;border-radius:6px;border:none;cursor:pointer;font-weight:700;font-size:.78rem;background:#7c3aed;color:#fff;">kg</button>
          <button id="wUnitG"  onclick="setWeightUnit('g')"
                  style="padding:6px 10px;border-radius:6px;border:none;cursor:pointer;font-weight:700;font-size:.78rem;background:#e2e8f0;color:#475569;">g</button>
        </div>
      </div>

      <!-- Live price calculation -->
      <div id="wCalc" style="background:#f5f3ff;border-radius:10px;padding:10px;margin-bottom:12px;font-size:.85rem;color:#4c1d95;min-height:40px;display:flex;align-items:center;justify-content:center;">
        Enter weight to see price
      </div>

      <!-- Numpad -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:10px;">
        <button class="wKey" onclick="numKey('1')">1</button>
        <button class="wKey" onclick="numKey('2')">2</button>
        <button class="wKey" onclick="numKey('3')">3</button>
        <button class="wKey" onclick="numKey('4')">4</button>
        <button class="wKey" onclick="numKey('5')">5</button>
        <button class="wKey" onclick="numKey('6')">6</button>
        <button class="wKey" onclick="numKey('7')">7</button>
        <button class="wKey" onclick="numKey('8')">8</button>
        <button class="wKey" onclick="numKey('9')">9</button>
        <button class="wKey wKey-del" onclick="numKey('del')">⌫</button>
        <button class="wKey" onclick="numKey('0')">0</button>
        <button class="wKey wKey-dot" onclick="numKey('.')">.</button>
      </div>

      <!-- Confirm button -->
      <button id="wConfirmBtn" onclick="confirmWeight()"
              style="width:100%;padding:12px;background:#7c3aed;color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:800;cursor:pointer;opacity:.4;" disabled>
        <i class="fas fa-check"></i> Add to Cart
      </button>
    </div>

  </div>
</div>

<style>
.wKey { padding:12px;border:none;border-radius:8px;background:#f1f5f9;
        font-size:1.1rem;font-weight:700;cursor:pointer;color:#1e293b;transition:background .1s; }
.wKey:hover     { background:#e2e8f0; }
.wKey:active    { background:#cbd5e1; }
.wKey-del       { background:#fee2e2;color:#dc2626; }
.wKey-del:hover { background:#fecaca; }
.wKey-dot       { background:#ede9fe;color:#7c3aed; }
</style>

<script>
// ── Weight Modal State ────────────────────────────────────
var wProductId   = null;
var wProductName = '';
var wPricePerKg  = 0;
var wUnit        = 'kg';     // product's defined unit (kg, g, lb)
var wInputUnit   = 'kg';     // what the cashier is entering in (kg or g)

function openWeightModal(productId, productName, pricePerKg, unit) {
    wProductId   = productId;
    wProductName = productName;
    wPricePerKg  = parseFloat(pricePerKg);
    wUnit        = unit || 'kg';
    wInputUnit   = 'kg';

    document.getElementById('wModalName').textContent  = productName;
    document.getElementById('wModalPrice').textContent = 'LL ' + Math.round(pricePerKg).toLocaleString() + ' / ' + wUnit;
    document.getElementById('wInput').value = '';
    document.getElementById('wCalc').textContent = 'Enter weight to see price';
    document.getElementById('wConfirmBtn').disabled = true;
    document.getElementById('wConfirmBtn').style.opacity = '.4';
    setWeightUnit('kg');

    var overlay = document.getElementById('weightModalOverlay');
    overlay.style.display = 'flex';
    setTimeout(() => document.getElementById('wInput').focus(), 100);
}

function closeWeightModal() {
    document.getElementById('weightModalOverlay').style.display = 'none';
    wProductId = null;
    if (typeof searchInput !== 'undefined') searchInput.focus();
}

function setWeightUnit(unit) {
    wInputUnit = unit;
    document.getElementById('wUnitKg').style.background = unit === 'kg' ? '#7c3aed' : '#e2e8f0';
    document.getElementById('wUnitKg').style.color      = unit === 'kg' ? '#fff' : '#475569';
    document.getElementById('wUnitG').style.background  = unit === 'g'  ? '#7c3aed' : '#e2e8f0';
    document.getElementById('wUnitG').style.color       = unit === 'g'  ? '#fff' : '#475569';
    updateWeightCalc();
}

function updateWeightCalc() {
    var raw = parseFloat(document.getElementById('wInput').value);
    if (!raw || raw <= 0) {
        document.getElementById('wCalc').textContent = 'Enter weight to see price';
        document.getElementById('wConfirmBtn').disabled = true;
        document.getElementById('wConfirmBtn').style.opacity = '.4';
        return;
    }

    // Convert to kg for calculation
    var weightKg = wInputUnit === 'g' ? raw / 1000 : raw;
    var total    = Math.round(wPricePerKg * weightKg);

    var displayWeight = wInputUnit === 'g'
        ? raw.toFixed(0) + 'g'
        : (weightKg >= 1 ? weightKg.toFixed(3) + ' kg' : (weightKg * 1000).toFixed(0) + 'g');

    document.getElementById('wCalc').innerHTML =
        '<strong>' + displayWeight + '</strong>' +
        ' &times; LL ' + Math.round(wPricePerKg).toLocaleString() + ' / kg' +
        ' = <strong style="font-size:1rem;">LL ' + total.toLocaleString() + '</strong>';

    document.getElementById('wConfirmBtn').disabled = false;
    document.getElementById('wConfirmBtn').style.opacity = '1';
}

function confirmWeight() {
    var raw = parseFloat(document.getElementById('wInput').value);
    if (!raw || raw <= 0 || !wProductId) return;
    var weightKg = wInputUnit === 'g' ? raw / 1000 : raw;
    closeWeightModal();
    addWeightedToCart(wProductId, wProductName, wPricePerKg, parseFloat(weightKg.toFixed(3)), wUnit);
}

// ── Numpad ────────────────────────────────────────────────
function numKey(k) {
    var inp = document.getElementById('wInput');
    if (k === 'del') {
        inp.value = inp.value.slice(0, -1);
    } else if (k === '.') {
        if (!inp.value.includes('.')) inp.value += '.';
    } else {
        inp.value += k;
    }
    updateWeightCalc();
}

// Close on overlay click
document.getElementById('weightModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeWeightModal();
});
</script>

</body>
</html>
