<?php
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$conn = mysqli_connect("192.168.1.101","root","1Sys9Admeen72","nccleb_test");
$co_rate = mysqli_fetch_assoc(mysqli_query($conn, "SELECT usd_to_lbp FROM company_settings LIMIT 1"));
$usd_to_lbp = (float)($co_rate['usd_to_lbp'] ?? 89500);
mysqli_set_charset($conn,'utf8mb4');

$msg = ''; $msg_type = '';

// ── Handle actions ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['add_product'])) {
        $nomp     = mysqli_real_escape_string($conn, trim($_POST['nomp']));
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $price    = (float)$_POST['price'] / $usd_to_lbp; // entered in LBP → save as USD
        $cost     = (float)$_POST['cost_price'] / $usd_to_lbp;
        $onhand   = (int)$_POST['onhand'];
        $unit     = mysqli_real_escape_string($conn, $_POST['unit']);
        $desc     = mysqli_real_escape_string($conn, trim($_POST['description']));
        $barcode  = mysqli_real_escape_string($conn, trim($_POST['barcode']));
        $ond      = $unit;

        // Handle image upload
        $image = '';
        if (!empty($_FILES['product_image']['name'])) {
            $upload_dir = 'uploads/products/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext      = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed) && $_FILES['product_image']['size'] < 2097152) {
                $filename = 'prod_' . time() . '_' . mt_rand(100,999) . '.' . $ext;
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $filename)) {
                    $image = mysqli_real_escape_string($conn, $filename);
                }
            }
        }

        $r = mysqli_query($conn,
            "INSERT INTO produit (nomp, category, price, cost_price, onhand, unit, description, barcode, ond, active, image)
             VALUES ('$nomp','$category',$price,$cost,$onhand,'$unit','$desc','$barcode','$ond',1,'$image')"
        );
        $msg = $r ? '✅ Product added.' : '❌ Error: ' . mysqli_error($conn);
        $msg_type = $r ? 'success' : 'error';
    }

    if (isset($_POST['edit_product'])) {
        $id       = (int)$_POST['codep'];
        $nomp     = mysqli_real_escape_string($conn, trim($_POST['nomp']));
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $price    = (float)$_POST['price'] / $usd_to_lbp; // entered in LBP → save as USD
        $cost     = (float)$_POST['cost_price'] / $usd_to_lbp;
        $onhand   = (int)$_POST['onhand'];
        $unit     = mysqli_real_escape_string($conn, $_POST['unit']);
        $desc     = mysqli_real_escape_string($conn, trim($_POST['description']));
        $barcode  = mysqli_real_escape_string($conn, trim($_POST['barcode']));
        $active   = isset($_POST['active']) ? 1 : 0;

        // Handle image upload for edit
        $image_sql = '';
        if (!empty($_FILES['product_image']['name'])) {
            $upload_dir = 'uploads/products/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext     = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed) && $_FILES['product_image']['size'] < 2097152) {
                $filename = 'prod_' . time() . '_' . mt_rand(100,999) . '.' . $ext;
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], 'uploads/products/' . $filename)) {
                    // Delete old image
                    $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM produit WHERE codep=$id LIMIT 1"));
                    if (!empty($old['image']) && file_exists('uploads/products/' . $old['image'])) {
                        unlink('uploads/products/' . $old['image']);
                    }
                    $img_esc   = mysqli_real_escape_string($conn, $filename);
                    $image_sql = ", image='$img_esc'";
                }
            }
        } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM produit WHERE codep=$id LIMIT 1"));
            if (!empty($old['image']) && file_exists('uploads/products/' . $old['image'])) {
                unlink('uploads/products/' . $old['image']);
            }
            $image_sql = ", image=''";
        }

        mysqli_query($conn,
            "UPDATE produit SET nomp='$nomp', category='$category', price=$price,
             cost_price=$cost, onhand=$onhand, unit='$unit', description='$desc',
             barcode='$barcode', active=$active $image_sql WHERE codep=$id"
        );
        $msg = '✅ Product updated.'; $msg_type = 'success';
    }

    if (isset($_POST['delete_product'])) {
        $id = (int)$_POST['codep'];
        mysqli_query($conn, "UPDATE produit SET active=0 WHERE codep=$id");
        $msg = '✅ Product deactivated.'; $msg_type = 'success';
    }
}

// ── Load data ──────────────────────────────────────────────────────────────
$search = mysqli_real_escape_string($conn, $_GET['s'] ?? '');
$filter_cat = mysqli_real_escape_string($conn, $_GET['cat'] ?? '');
$show_inactive = isset($_GET['inactive']);

$where = $show_inactive ? '' : "WHERE p.active = 1";
if ($search) $where .= ($where ? ' AND' : 'WHERE') . " (p.nomp LIKE '%$search%' OR p.barcode LIKE '%$search%')";
if ($filter_cat) $where .= ($where ? ' AND' : 'WHERE') . " p.category = '$filter_cat'";

$products = mysqli_query($conn, "SELECT * FROM produit p $where ORDER BY p.nomp");
$cats = mysqli_query($conn, "SELECT * FROM pos_categories ORDER BY name");
$categories = [];
while ($c = mysqli_fetch_assoc($cats)) $categories[] = $c;

// Stats
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN active=1 THEN 1 ELSE 0 END) as active_count,
     SUM(CASE WHEN onhand <= 0 AND active=1 THEN 1 ELSE 0 END) as out_of_stock,
     SUM(CASE WHEN onhand > 0 AND onhand <= 5 AND active=1 THEN 1 ELSE 0 END) as low_stock,
     SUM(onhand * cost_price) as stock_value
     FROM produit"
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Products — POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.topbar { background:linear-gradient(135deg,#1976D2,#0D47A1); color:white; padding:14px 24px; display:flex; align-items:center; gap:15px; }
.topbar h1 { font-size:18px; font-weight:700; }
.topbar a { margin-left:auto; color:white; text-decoration:none; background:rgba(255,255,255,.15); padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; }
.topbar a:hover { background:rgba(255,255,255,.25); }
.topbar a + a { margin-left:8px; }
.container { max-width:1200px; margin:0 auto; padding:24px 20px; }
.stats { display:grid; grid-template-columns:repeat(5,1fr); gap:15px; margin-bottom:24px; }
@media(max-width:800px){ .stats { grid-template-columns:repeat(2,1fr); } }
.stat { background:white; border-radius:10px; padding:18px; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,.06); border-left:4px solid #1976D2; }
.stat.red { border-color:#ef4444; }
.stat.orange { border-color:#f59e0b; }
.stat.green { border-color:#10b981; }
.stat .val { font-size:26px; font-weight:800; color:#1976D2; }
.stat.red .val { color:#ef4444; }
.stat.orange .val { color:#f59e0b; }
.stat.green .val { color:#10b981; }
.stat .lbl { font-size:12px; color:#6b7280; margin-top:4px; text-transform:uppercase; font-weight:600; }
.card { background:white; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.08); overflow:hidden; }
.card-header { padding:16px 22px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; gap:10px; font-size:15px; font-weight:700; color:#1a1a2e; }
.card-header i { color:#1976D2; }
.card-header .actions { margin-left:auto; display:flex; gap:8px; align-items:center; }
.card-body { padding:22px; }
.alert { padding:13px 18px; border-radius:8px; margin-bottom:20px; font-weight:600; font-size:14px; }
.alert-success { background:#d1fae5; color:#065f46; border-left:4px solid #10b981; }
.alert-error   { background:#fee2e2; color:#991b1b; border-left:4px solid #ef4444; }
.form-row { display:grid; grid-template-columns:repeat(3,1fr); gap:15px; margin-bottom:15px; }
@media(max-width:700px){ .form-row { grid-template-columns:1fr; } }
.form-group label { display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px; text-transform:uppercase; }
.form-group input, .form-group select, .form-group textarea {
    width:100%; padding:10px 13px; border:2px solid #e5e7eb; border-radius:8px;
    font-size:14px; transition:border-color .2s; font-family:inherit;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:#1976D2; }
.btn { padding:10px 20px; border:none; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:7px; transition:all .2s; }
.btn-blue  { background:#1976D2; color:white; }
.btn-blue:hover { background:#0D47A1; }
.btn-green { background:#10b981; color:white; }
.btn-green:hover { background:#059669; }
.btn-red   { background:#ef4444; color:white; }
.btn-red:hover { background:#dc2626; }
.btn-sm    { padding:6px 12px; font-size:12px; }
.filters { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
.filters input, .filters select { padding:9px 13px; border:2px solid #e5e7eb; border-radius:8px; font-size:13px; }
.filters input:focus, .filters select:focus { outline:none; border-color:#1976D2; }
table { width:100%; border-collapse:collapse; font-size:14px; }
th { padding:12px; background:#f8fafc; color:#374151; font-weight:700; text-align:left; border-bottom:2px solid #e5e7eb; }
td { padding:12px; border-bottom:1px solid #f3f4f6; color:#4b5563; vertical-align:middle; }
tr:hover td { background:#fafafa; }
.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.badge-active   { background:#d1fae5; color:#065f46; }
.badge-inactive { background:#fee2e2; color:#991b1b; }
.badge-low      { background:#fef3c7; color:#92400e; }
.badge-out      { background:#fee2e2; color:#991b1b; }
.action-btns { display:flex; gap:6px; }

/* Edit modal */
.modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; padding:20px; }
.modal.show { display:flex; }
.modal-box { background:white; border-radius:12px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; }
.modal-header { padding:18px 22px; border-bottom:1px solid #e5e7eb; font-size:16px; font-weight:700; color:#1a1a2e; display:flex; align-items:center; justify-content:space-between; }
.modal-close { background:none; border:none; font-size:20px; cursor:pointer; color:#6b7280; }
.modal-body { padding:22px; }
</style>
</head>
<body>
<div class="topbar">
    <i class="fas fa-box fa-lg"></i>
    <h1>Product Manager</h1>
    <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="pos_sales.php"><i class="fas fa-history"></i> Sales</a>
    <a href="pos_archive.php"><i class="fas fa-archive"></i> Archive</a>
    <a href="test204.php?page=<?= urlencode($agent_name) ?>&page1=<?= $agent_id ?>"><i class="fas fa-arrow-left"></i> CRM</a>
</div>

<div class="container">

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="stats">
    <div class="stat"><div class="val"><?= $stats['active_count'] ?></div><div class="lbl">Active Products</div></div>
    <div class="stat red"><div class="val"><?= $stats['out_of_stock'] ?></div><div class="lbl">Out of Stock</div></div>
    <div class="stat orange"><div class="val"><?= $stats['low_stock'] ?></div><div class="lbl">Low Stock (≤5)</div></div>
    <div class="stat green"><div class="val">LL <?= number_format(round(($stats['stock_value'] ?? 0) * $usd_to_lbp), 0) ?></div><div class="lbl">Stock Value (Cost)</div></div>
    <div class="stat"><div class="val"><?= $stats['total'] ?></div><div class="lbl">Total Products</div></div>
</div>

<!-- Add Product -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><i class="fas fa-plus-circle"></i> Add New Product</div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="nomp" required maxlength="30" placeholder="e.g. TP-Link Router">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unit</label>
                    <select name="unit">
                        <option value="piece">Piece</option>
                        <option value="box">Box</option>
                        <option value="meter">Meter</option>
                        <option value="roll">Roll</option>
                        <option value="kg">KG</option>
                        <option value="service">Service</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Selling Price (LL) *</label>
                    <input type="number" name="price" step="1000" min="0" required placeholder="e.g. 895000">
                </div>
                <div class="form-group">
                    <label>Cost Price (LL)</label>
                    <input type="number" name="cost_price" step="1000" min="0" placeholder="e.g. 700000" value="0">
                </div>
                <div class="form-group">
                    <label>Initial Stock (qty)</label>
                    <input type="number" name="onhand" min="0" value="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Barcode (optional)</label>
                    <input type="text" name="barcode" placeholder="Scan or type barcode">
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="Short product description">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="grid-column:span 3;">
                    <label><i class="fas fa-image"></i> Product Image (optional — JPG/PNG/WEBP, max 2MB)</label>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <label style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;background:#eff6ff;border:2px dashed #93c5fd;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;color:#1976D2;">
                            <i class="fas fa-upload"></i> Choose Image
                            <input type="file" name="product_image" accept="image/*" style="display:none;" onchange="previewAddImage(this)">
                        </label>
                        <img id="add_img_preview" src="" alt="" style="display:none;width:60px;height:60px;object-fit:cover;border-radius:8px;border:2px solid #e5e7eb;">
                        <span id="add_img_name" style="font-size:12px;color:#6b7280;"></span>
                    </div>
                </div>
            </div>
            <button type="submit" name="add_product" class="btn btn-green">
                <i class="fas fa-plus"></i> Add Product
            </button>
        </form>
    </div>
</div>

<!-- Product List -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Products
        <div class="actions">
            <form method="GET" class="filters">
                <input type="text" name="s" value="<?= htmlspecialchars($_GET['s'] ?? '') ?>" placeholder="Search...">
                <select name="cat">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= htmlspecialchars($c['name']) ?>" <?= ($_GET['cat'] ?? '') === $c['name'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <label style="font-size:13px;color:#6b7280;display:flex;align-items:center;gap:5px;">
                    <input type="checkbox" name="inactive" <?= isset($_GET['inactive']) ? 'checked' : '' ?>> Show inactive
                </label>
                <button type="submit" class="btn btn-blue btn-sm"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Image</th><th>Name</th><th>Category</th>
                    <th>Price</th><th>Cost</th><th>Stock</th>
                    <th>Unit</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($p = mysqli_fetch_assoc($products)): ?>
            <tr>
                <td><?= $p['codep'] ?></td>
                <td>
                    <?php if (!empty($p['image']) && file_exists('uploads/products/' . $p['image'])): ?>
                    <img src="uploads/products/<?= htmlspecialchars($p['image']) ?>"
                         style="width:44px;height:44px;object-fit:cover;border-radius:8px;border:2px solid #e5e7eb;">
                    <?php else: ?>
                    <div style="width:44px;height:44px;border-radius:8px;background:#f3f4f6;border:2px solid #e5e7eb;display:flex;align-items:center;justify-content:center;color:#d1d5db;font-size:18px;">
                        <i class="fas fa-box"></i>
                    </div>
                    <?php endif; ?>
                </td>
                <td><strong><?= htmlspecialchars($p['nomp']) ?></strong>
                    <?php if ($p['barcode']): ?><br><small style="color:#9ca3af;"><?= htmlspecialchars($p['barcode']) ?></small><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                <td><strong>LL <?= number_format(round($p['price'] * $usd_to_lbp), 0) ?></strong></td>
                <td>LL <?= number_format(round(($p['cost_price'] ?? 0) * $usd_to_lbp), 0) ?></td>
                <td>
                    <?php if ($p['onhand'] <= 0): ?>
                        <span class="badge badge-out">Out of stock</span>
                    <?php elseif ($p['onhand'] <= 5): ?>
                        <span class="badge badge-low">⚠ <?= $p['onhand'] ?></span>
                    <?php else: ?>
                        <?= $p['onhand'] ?>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['unit'] ?? '—') ?></td>
                <td><span class="badge badge-<?= $p['active'] ? 'active' : 'inactive' ?>"><?= $p['active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                    <div class="action-btns">
                        <button class="btn btn-blue btn-sm" onclick="openEdit(<?= htmlspecialchars(json_encode($p)) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($p['active']): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this product?')">
                            <input type="hidden" name="codep" value="<?= $p['codep'] ?>">
                            <button type="submit" name="delete_product" class="btn btn-red btn-sm">
                                <i class="fas fa-ban"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-box">
        <div class="modal-header">
            <span><i class="fas fa-edit"></i> Edit Product</span>
            <button class="modal-close" onclick="closeEdit()">×</button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="codep" id="edit_codep">
                <input type="hidden" name="remove_image" id="edit_remove_image" value="0">
                <div class="form-row">
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="nomp" id="edit_nomp" required maxlength="30">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="edit_category">
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <select name="unit" id="edit_unit">
                            <option value="piece">Piece</option>
                            <option value="box">Box</option>
                            <option value="meter">Meter</option>
                            <option value="roll">Roll</option>
                            <option value="kg">KG</option>
                            <option value="service">Service</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Selling Price (LL)</label>
                        <input type="number" name="price" id="edit_price" step="1000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Cost Price (LL)</label>
                        <input type="number" name="cost_price" id="edit_cost" step="1000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Stock (qty)</label>
                        <input type="number" name="onhand" id="edit_onhand" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Barcode</label>
                        <input type="text" name="barcode" id="edit_barcode">
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label>Description</label>
                        <input type="text" name="description" id="edit_desc">
                    </div>
                </div>
                <!-- Image upload -->
                <div class="form-group" style="margin-bottom:16px;">
                    <label><i class="fas fa-image"></i> Product Image</label>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
                        <img id="edit_img_preview" src="" alt=""
                             style="width:64px;height:64px;object-fit:cover;border-radius:10px;border:2px solid #e5e7eb;display:none;">
                        <div id="edit_img_placeholder" style="width:64px;height:64px;border-radius:10px;background:#f3f4f6;border:2px solid #e5e7eb;display:flex;align-items:center;justify-content:center;color:#d1d5db;font-size:24px;">
                            <i class="fas fa-box"></i>
                        </div>
                        <div>
                            <label style="display:inline-flex;align-items:center;gap:8px;padding:7px 14px;background:#eff6ff;border:2px dashed #93c5fd;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;color:#1976D2;margin-bottom:6px;">
                                <i class="fas fa-upload"></i> Change Image
                                <input type="file" name="product_image" accept="image/*" style="display:none;" onchange="previewEditImage(this)">
                            </label><br>
                            <button type="button" id="edit_remove_btn" onclick="removeEditImage()" style="display:none;padding:5px 10px;background:#fee2e2;color:#ef4444;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">
                                <i class="fas fa-trash"></i> Remove Image
                            </button>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="active" id="edit_active"> Active
                    </label>
                </div>
                <button type="submit" name="edit_product" class="btn btn-blue">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<script>
var USD_TO_LBP = <?= $usd_to_lbp ?>;
function openEdit(p) {
    document.getElementById('edit_codep').value    = p.codep;
    document.getElementById('edit_nomp').value     = p.nomp;
    document.getElementById('edit_price').value    = Math.round(p.price * USD_TO_LBP);
    document.getElementById('edit_cost').value     = Math.round((p.cost_price || 0) * USD_TO_LBP);
    document.getElementById('edit_onhand').value   = p.onhand;
    document.getElementById('edit_unit').value     = p.unit || 'piece';
    document.getElementById('edit_barcode').value  = p.barcode || '';
    document.getElementById('edit_desc').value     = p.description || '';
    document.getElementById('edit_active').checked = p.active == 1;
    document.getElementById('edit_category').value = p.category || 'General';
    document.getElementById('edit_remove_image').value = '0';

    // Handle image preview
    var preview = document.getElementById('edit_img_preview');
    var placeholder = document.getElementById('edit_img_placeholder');
    var removeBtn = document.getElementById('edit_remove_btn');

    if (p.image) {
        preview.src = 'uploads/products/' + p.image;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
        removeBtn.style.display = 'inline-block';
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'flex';
        removeBtn.style.display = 'none';
    }

    document.getElementById('editModal').classList.add('show');
}

function closeEdit() {
    document.getElementById('editModal').classList.remove('show');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});

// Add form image preview
function previewAddImage(input) {
    var preview = document.getElementById('add_img_preview');
    var nameEl  = document.getElementById('add_img_name');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
        nameEl.textContent = input.files[0].name;
    }
}

// Edit form image preview
function previewEditImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('edit_img_preview');
            preview.src = e.target.result;
            preview.style.display = 'block';
            document.getElementById('edit_img_placeholder').style.display = 'none';
            document.getElementById('edit_remove_btn').style.display = 'inline-block';
            document.getElementById('edit_remove_image').value = '0';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Remove image
function removeEditImage() {
    document.getElementById('edit_img_preview').style.display = 'none';
    document.getElementById('edit_img_placeholder').style.display = 'flex';
    document.getElementById('edit_remove_btn').style.display = 'none';
    document.getElementById('edit_remove_image').value = '1';
}
</script>
</body>
</html>
