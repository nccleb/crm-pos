<?php
session_start();
if (empty($_SESSION['oop'])) { header("Location: login200.php"); exit(); }
$is_super   = ($_SESSION['oop'] === 'super');
$agent_name = $_SESSION['oop'];
$agent_id   = (int)($_SESSION['ooq'] ?? 0);

$conn = mysqli_connect("192.168.1.19","root","1Sys9Admeen72","nccleb_test");
mysqli_set_charset($conn,'utf8mb4');

// ── Filters ────────────────────────────────────────────────────────────────
$f_agent   = mysqli_real_escape_string($conn, $_GET['agent']  ?? '');
$f_action  = mysqli_real_escape_string($conn, $_GET['action'] ?? '');
$f_from    = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$f_to      = $_GET['to']   ?? date('Y-m-d');
$page      = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 50;
$offset    = ($page - 1) * $per_page;

// Non-super users see only their own logs
if (!$is_super) { $f_agent = $agent_name; }

$where = "WHERE DATE(created_at) BETWEEN '$f_from' AND '$f_to'";
if ($f_agent)  $where .= " AND agent_name = '$f_agent'";
if ($f_action) $where .= " AND action = '$f_action'";

$total = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM pos_activity_log $where"))['cnt'];
$pages = max(1, ceil($total / $per_page));

$logs_res = mysqli_query($conn,
    "SELECT * FROM pos_activity_log $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$logs = [];
while ($r = mysqli_fetch_assoc($logs_res)) $logs[] = $r;

// Distinct agents and actions for filter dropdowns
$agents_res  = mysqli_query($conn, "SELECT DISTINCT agent_name FROM pos_activity_log ORDER BY agent_name");
$actions_res = mysqli_query($conn, "SELECT DISTINCT action FROM pos_activity_log ORDER BY action");
$agents  = []; while ($r = mysqli_fetch_assoc($agents_res))  $agents[]  = $r['agent_name'];
$actions = []; while ($r = mysqli_fetch_assoc($actions_res)) $actions[] = $r['action'];

// Action labels and icons
$action_meta = [
    'sale_completed'    => ['icon'=>'fa-receipt',           'color'=>'#10b981', 'label'=>'Sale'],
    'sale_refunded'     => ['icon'=>'fa-undo',              'color'=>'#ef4444', 'label'=>'Refund'],
    'stock_adjusted'    => ['icon'=>'fa-boxes',             'color'=>'#f59e0b', 'label'=>'Stock Adj.'],
    'product_added'     => ['icon'=>'fa-plus-circle',       'color'=>'#2563eb', 'label'=>'Product Added'],
    'product_edited'    => ['icon'=>'fa-edit',              'color'=>'#6366f1', 'label'=>'Product Edited'],
    'product_deactivated'=>['icon'=>'fa-ban',               'color'=>'#6b7280', 'label'=>'Deactivated'],
    'product_pulled'    => ['icon'=>'fa-times-circle',      'color'=>'#dc2626', 'label'=>'Pulled'],
    'batch_pulled'      => ['icon'=>'fa-calendar-times',    'color'=>'#f97316', 'label'=>'Batch Pulled'],
    'receiving_saved'   => ['icon'=>'fa-truck',             'color'=>'#0891b2', 'label'=>'Receiving'],
    'supplier_added'    => ['icon'=>'fa-building',          'color'=>'#7c3aed', 'label'=>'Supplier Added'],
    'supplier_updated'  => ['icon'=>'fa-building',          'color'=>'#7c3aed', 'label'=>'Supplier Updated'],
    'promotion_added'   => ['icon'=>'fa-tag',               'color'=>'#d97706', 'label'=>'Promo Added'],
    'promotion_updated' => ['icon'=>'fa-tag',               'color'=>'#d97706', 'label'=>'Promo Updated'],
    'promotion_toggled' => ['icon'=>'fa-toggle-on',         'color'=>'#d97706', 'label'=>'Promo Toggled'],
    'promotion_deleted' => ['icon'=>'fa-tag',               'color'=>'#ef4444', 'label'=>'Promo Deleted'],
    'settings_saved'    => ['icon'=>'fa-cog',               'color'=>'#059669', 'label'=>'Settings'],
    'backup_downloaded' => ['icon'=>'fa-download',          'color'=>'#0284c7', 'label'=>'Backup'],
    'backup_restored'   => ['icon'=>'fa-upload',            'color'=>'#dc2626', 'label'=>'Restore'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Activity Log — POS</title>
<style>
/* ── Inline SVG icon system — no CDN required ── */
.fas { display:inline-block; width:1em; height:1em; vertical-align:-0.125em;
       background-repeat:no-repeat; background-size:contain; background-position:center; }
.fa-history        { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M504 255.5c.3 136.6-111.2 248.4-247.8 248.5-60.7.0-116.3-21.8-159.3-57.8-10.9-9.2-11.5-25.8-1.2-35.9l19.8-19.7c9.1-9.1 23.6-9.7 33.4-1.4C181.1 413.2 216.9 428 256 428c94.4.0 171-76.3 171-171 0-94.4-76.3-171-171-171-41.4.0-79.4 14.9-108.9 39.5l41.4 41.4c14.1 14.1 4.1 38.3-15.9 38.3H32c-12.4.0-22.4-10-22.4-22.4V42c0-20 24.2-30 38.3-15.9l40.9 40.9C131.1 25.4 190.8 0 256 0 392.4 0 503.7 114.4 504 255.5z'/%3E%3C/svg%3E"); }
.fa-cash-register  { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M0 192v272a48 48 0 0 0 48 48h416a48 48 0 0 0 48-48V192zm148 228a12 12 0 0 1-12 12H92a12 12 0 0 1-12-12v-44a12 12 0 0 1 12-12h44a12 12 0 0 1 12 12zm0-128a12 12 0 0 1-12 12H92a12 12 0 0 1-12-12v-44a12 12 0 0 1 12-12h44a12 12 0 0 1 12 12zm128 128a12 12 0 0 1-12 12h-44a12 12 0 0 1-12-12v-44a12 12 0 0 1 12-12h44a12 12 0 0 1 12 12zm0-128a12 12 0 0 1-12 12h-44a12 12 0 0 1-12-12v-44a12 12 0 0 1 12-12h44a12 12 0 0 1 12 12zm128 128a12 12 0 0 1-12 12h-44a12 12 0 0 1-12-12V292a12 12 0 0 1 12-12h44a12 12 0 0 1 12 12zm64-320H448V32a32 32 0 0 0-32-32H96a32 32 0 0 0-32 32v160H44a44 44 0 0 0-44 44v4h512v-4a44 44 0 0 0-44-44zm-96 0H144V64h228z'/%3E%3C/svg%3E"); }
.fa-box            { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='currentColor' d='M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5.0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm-6 400H54a6 6 0 0 1-6-6V86a6 6 0 0 1 6-6h340a6 6 0 0 1 6 6v340a6 6 0 0 1-6 6z'/%3E%3C/svg%3E"); }
.fa-truck-loading  { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 512'%3E%3Cpath fill='currentColor' d='M50.2 375.6c2.3 8.5 11.1 13.6 19.6 11.3l216.4-58c8.5-2.3 13.6-11.1 11.3-19.6L239.1 117c-2.3-8.5-11.1-13.6-19.6-11.3L2.9 164c-8.5 2.3-13.6 11.1-11.3 19.6l58.6 192zm92.8-204.1l89.7 293.4-216.4 58L-42 183l216.4-58zM640 0H224v400h96.9c9.3 0 17.4 6.4 19.4 15.5L360 512h80l16.5-99.5c1.9-9 9.7-15.5 19.4-15.5H640V0zm-96 320h-64V192h64v128z'/%3E%3C/svg%3E"); }
.fa-bell           { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='currentColor' d='M224 512c35.3.0 64-28.7 64-64H160c0 35.3 28.7 64 64 64zm215.4-164.9C425.6 329.3 416 313.1 416 295.6V208c0-77.4-55-142-128-156.8V32c0-17.7-14.3-32-32-32s-32 14.3-32 32v19.2C151 65.8 96 130.4 96 208v87.6c0 17.5-9.6 33.7-23.4 51.5L48 384h352l-24.6-36.9z'/%3E%3C/svg%3E"); }
.fa-layer-group    { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M12.4 148.3L256 288l243.6-139.7L256 8 12.4 148.3zM256 320L0 172.8V216l256 147.2L512 216v-43.2L256 320zm0 96L0 268.8V312l256 147.2L512 312v-43.2L256 416z'/%3E%3C/svg%3E"); }
.fa-cog            { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M487.4 315.7l-42.6-24.6c4.3-23.2 4.3-47 0-70.2l42.6-24.6c4.9-2.8 7.1-8.6 5.5-14-11.1-35.6-30-67.8-54.7-94.6-3.8-4.1-10-5.1-14.8-2.3L380.8 110c-17.9-15.4-38.5-27.3-60.8-35.1V25.8c0-5.6-3.9-10.5-9.4-11.7-36.7-8.2-74.3-7.8-109.2 0-5.5 1.2-9.4 6.1-9.4 11.7V75c-22.2 7.9-42.8 19.8-60.8 35.1L88.7 85.5c-4.9-2.8-11-1.9-14.8 2.3-24.7 26.7-43.6 58.9-54.7 94.6-1.7 5.4.6 11.2 5.5 14L67.3 221c-4.3 23.2-4.3 47 0 70.2l-42.6 24.6c-4.9 2.8-7.1 8.6-5.5 14 11.1 35.6 30 67.8 54.7 94.6 3.8 4.1 10 5.1 14.8 2.3l42.6-24.6c17.9 15.4 38.5 27.3 60.8 35.1v49.2c0 5.6 3.9 10.5 9.4 11.7 36.7 8.2 74.3 7.8 109.2 0 5.5-1.2 9.4-6.1 9.4-11.7v-49.2c22.2-7.9 42.8-19.8 60.8-35.1l42.6 24.6c4.9 2.8 11 1.9 14.8-2.3 24.7-26.7 43.6-58.9 54.7-94.6 1.5-5.5-.7-11.3-5.6-14.1zM256 336c-44.1.0-80-35.9-80-80s35.9-80 80-80 80 35.9 80 80-35.9 80-80 80z'/%3E%3C/svg%3E"); }
.fa-user           { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='currentColor' d='M224 256c70.7.0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5.0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z'/%3E%3C/svg%3E"); }
.fa-receipt        { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 384 512'%3E%3Cpath fill='currentColor' d='M358.4 3.2L320 48 265.6 3.2a15.9 15.9 0 0 0-19.2.0L192 48 137.6 3.2a15.9 15.9 0 0 0-19.2.0L64 48 25.6 3.2C15-4.7 0 2.8 0 16v480c0 13.2 15 20.7 25.6 12.8L64 464l54.4 44.8a15.9 15.9 0 0 0 19.2.0L192 464l54.4 44.8a15.9 15.9 0 0 0 19.2.0L320 464l38.4 44.8c10.5 7.9 25.6.4 25.6-12.8V16c0-13.2-15-20.7-25.6-12.8zM96 256H64v-32h32v32zm0-64H64v-32h32v32zm0-64H64V96h32v32zm224 128H128v-32h192v32zm0-64H128v-32h192v32zm0-64H128V96h192v32z'/%3E%3C/svg%3E"); }
.fa-undo           { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M212.3 224.3H12c-6.6.0-12-5.4-12-12V12C0 5.4 5.4 0 12 0h48c6.6.0 12 5.4 12 12v78.1C117.7 39 181.2 7.6 253.8 8c136.4.7 246.6 111.7 246.2 248.1-.4 135.6-110.8 245.9-246.5 245.9-64.1.0-122.5-24.2-166.3-63.9-4.7-4.3-4.8-11.6-.2-16.2l33.8-33.8c4.3-4.3 11.3-4.4 15.7-.3C161.6 415.1 205.6 432 253.5 432c105.9.0 192-86.1 192-192s-86.1-192-192-192c-49.2.0-94.2 19.4-127.4 51H212c6.6.0 12 5.4 12 12v48c.3 6.7-5.1 12.3-11.7 12.3z'/%3E%3C/svg%3E"); }
.fa-boxes          { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 576 512'%3E%3Cpath fill='currentColor' d='M560 288h-80v96l-32-21.3-32 21.3v-96h-80c-8.8.0-16 7.2-16 16v192c0 8.8 7.2 16 16 16h224c8.8.0 16-7.2 16-16V304c0-8.8-7.2-16-16-16zm-384-64h224c8.8.0 16-7.2 16-16V16c0-8.8-7.2-16-16-16h-80v96l-32-21.3L256 96V0h-80c-8.8.0-16 7.2-16 16v192c0 8.8 7.2 16 16 16zm64 64h-80v96l-32-21.3L96 384v-96H16c-8.8.0-16 7.2-16 16v192c0 8.8 7.2 16 16 16h224c8.8.0 16-7.2 16-16V304c0-8.8-7.2-16-16-16z'/%3E%3C/svg%3E"); }
.fa-plus-circle    { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm144 276c0 6.6-5.4 12-12 12h-92v92c0 6.6-5.4 12-12 12h-56c-6.6.0-12-5.4-12-12v-92h-92c-6.6.0-12-5.4-12-12v-56c0-6.6 5.4-12 12-12h92v-92c0-6.6 5.4-12 12-12h56c6.6.0 12 5.4 12 12v92h92c6.6.0 12 5.4 12 12v56z'/%3E%3C/svg%3E"); }
.fa-edit           { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 576 512'%3E%3Cpath fill='currentColor' d='M402.6 83.2l90.2 90.2c3.8 3.8 3.8 10 0 13.8L274.4 405.1l-92.8 10.3c-12.4 1.4-22.9-9.1-21.5-21.5l10.3-92.8L388.8 83.2c3.8-3.8 10-3.8 13.8.0zm162-22.9l-48.8-48.8c-15.2-15.2-39.9-15.2-55.2.0l-35.4 35.4c-3.8 3.8-3.8 10 0 13.8l90.2 90.2c3.8 3.8 10 3.8 13.8.0l35.4-35.4c15.3-15.2 15.3-40 0-55.2zM384 346.2V448H64V128h229.8c3.2.0 6.2-1.3 8.5-3.5l40-40c7.6-7.6 2.2-20.5-8.5-20.5H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h352c26.5.0 48-21.5 48-48V306.2c0-10.7-12.9-16-20.5-8.5l-40 40c-2.2 2.3-3.5 5.3-3.5 8.5z'/%3E%3C/svg%3E"); }
.fa-ban            { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm141.421 106.579c73.176 73.175 77.05 187.301 15.964 264.865L132.556 98.615c77.588-61.105 191.709-57.193 264.865 15.964zM114.579 397.421c-73.176-73.175-77.05-187.301-15.964-264.865l280.829 280.829c-77.588 61.105-191.709 57.193-264.865-15.964z'/%3E%3C/svg%3E"); }
.fa-times-circle   { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm121.6 313.1c4.7 4.7 4.7 12.3.0 17L338 377.6c-4.7 4.7-12.3 4.7-17 0L256 312l-65.1 65.6c-4.7 4.7-12.3 4.7-17 0L134.4 338c-4.7-4.7-4.7-12.3.0-17l65.6-65-65.6-65.1c-4.7-4.7-4.7-12.3.0-17l39.6-39.6c4.7-4.7 12.3-4.7 17 0l65 65.7 65.1-65.6c4.7-4.7 12.3-4.7 17 0l39.6 39.6c4.7 4.7 4.7 12.3.0 17L312 256l65.6 65.1z'/%3E%3C/svg%3E"); }
.fa-calendar-times { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='currentColor' d='M436 160H12c-6.6.0-12-5.4-12-12v-36c0-26.5 21.5-48 48-48h48V12c0-6.6 5.4-12 12-12h40c6.6.0 12 5.4 12 12v52h128V12c0-6.6 5.4-12 12-12h40c6.6.0 12 5.4 12 12v52h48c26.5.0 48 21.5 48 48v36c0 6.6-5.4 12-12 12zM12 192h424c6.6.0 12 5.4 12 12v260c0 26.5-21.5 48-48 48H48c-26.5.0-48-21.5-48-48V204c0-6.6 5.4-12 12-12zm286.5 142.5l-55.98-56 55.98-56c4.69-4.69 4.69-12.29.0-16.97l-28.27-28.28c-4.69-4.69-12.3-4.69-16.98.0l-55.97 56-55.98-56c-4.69-4.69-12.3-4.69-16.97.0l-28.28 28.28c-4.69 4.69-4.69 12.28.0 16.97L192.53 334.5l-55.98 56c-4.69 4.69-4.69 12.29.0 16.97l28.28 28.28c4.69 4.69 12.28 4.69 16.97.0l55.98-56 55.97 56c4.69 4.69 12.29 4.69 16.98.0l28.27-28.28c4.69-4.68 4.69-12.28.0-16.97z'/%3E%3C/svg%3E"); }
.fa-truck          { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 512'%3E%3Cpath fill='currentColor' d='M624 352h-16V243.9c0-12.7-5.1-24.9-14.1-33.9L494 110.1c-9-9-21.2-14.1-33.9-14.1H416V48c0-26.5-21.5-48-48-48H48C21.5 0 0 21.5 0 48v320c0 26.5 21.5 48 48 48h16c0 53 43 96 96 96s96-43 96-96h128c0 53 43 96 96 96s96-43 96-96h48c8.8.0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zM160 464c-26.5.0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48zm320 0c-26.5.0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48zm80-208H416V144h44.1l99.9 99.9V256z'/%3E%3C/svg%3E"); }
.fa-building       { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='currentColor' d='M436 480h-20V24c0-13.3-10.7-24-24-24H56C42.7 0 32 10.7 32 24v456H12c-6.6.0-12 5.4-12 12v20h448v-20c0-6.6-5.4-12-12-12zM128 76c0-6.6 5.4-12 12-12h40c6.6.0 12 5.4 12 12v40c0 6.6-5.4 12-12 12h-40c-6.6.0-12-5.4-12-12V76zm0 96c0-6.6 5.4-12 12-12h40c6.6.0 12 5.4 12 12v40c0 6.6-5.4 12-12 12h-40c-6.6.0-12-5.4-12-12v-40zm52 248h-40v-76c0-6.6 5.4-12 12-12h16c6.6.0 12 5.4 12 12v76zm76-152c0 6.6-5.4 12-12 12h-40c-6.6.0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h40c6.6.0 12 5.4 12 12v40zm0-96c0 6.6-5.4 12-12 12h-40c-6.6.0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h40c6.6.0 12 5.4 12 12v40zm0-96c0 6.6-5.4 12-12 12h-40c-6.6.0-12-5.4-12-12V76c0-6.6 5.4-12 12-12h40c6.6.0 12 5.4 12 12v40zm76 248h-40v-76c0-6.6 5.4-12 12-12h16c6.6.0 12 5.4 12 12v76zm0-152c0 6.6-5.4 12-12 12h-40c-6.6.0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h40c6.6.0 12 5.4 12 12v40zm0-96c0 6.6-5.4 12-12 12h-40c-6.6.0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h40c6.6.0 12 5.4 12 12v40zm0-96c0 6.6-5.4 12-12 12h-40c-6.6.0-12-5.4-12-12V76c0-6.6 5.4-12 12-12h40c6.6.0 12 5.4 12 12v40z'/%3E%3C/svg%3E"); }
.fa-tag            { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M0 252.1V48C0 21.5 21.5 0 48 0h204.1a48 48 0 0 1 33.9 14.1l211.9 211.9c18.7 18.7 18.7 49.1.0 67.9L293.8 497.9c-18.7 18.7-49.1 18.7-67.9.0L14.1 286.1A48 48 0 0 1 0 252.1zM112 64c-26.5.0-48 21.5-48 48s21.5 48 48 48 48-21.5 48-48-21.5-48-48-48z'/%3E%3C/svg%3E"); }
.fa-toggle-on      { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 576 512'%3E%3Cpath fill='currentColor' d='M384 64H192C86 64 0 150 0 256s86 192 192 192h192c106 0 192-86 192-192S490 64 384 64zm0 320c-70.8.0-128-57.2-128-128s57.2-128 128-128 128 57.2 128 128-57.2 128-128 128z'/%3E%3C/svg%3E"); }
.fa-download       { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M216 0h80c13.3.0 24 10.7 24 24v168h87.7c17.8.0 26.7 21.5 14.1 34.1L269.7 378.3c-7.5 7.5-19.8 7.5-27.3.0L90.1 226.1c-12.6-12.6-3.7-34.1 14.1-34.1H192V24c0-13.3 10.7-24 24-24zm296 376v112c0 13.3-10.7 24-24 24H24c-13.3.0-24-10.7-24-24V376c0-13.3 10.7-24 24-24h146.7l49 49c20.1 20.1 52.5 20.1 72.6.0l49-49H488c13.3.0 24 10.7 24 24zm-124 88c0-11-9-20-20-20s-20 9-20 20 9 20 20 20 20-9 20-20zm64 0c0-11-9-20-20-20s-20 9-20 20 9 20 20 20 20-9 20-20z'/%3E%3C/svg%3E"); }
.fa-upload         { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M296 384h-80c-13.3.0-24-10.7-24-24V192h-87.7c-17.8.0-26.7-21.5-14.1-34.1L242.3 5.7c7.5-7.5 19.8-7.5 27.3.0l152.2 152.2c12.6 12.6 3.7 34.1-14.1 34.1H320v168c0 13.3-10.7 24-24 24zm216-8v112c0 13.3-10.7 24-24 24H24c-13.3.0-24-10.7-24-24V376c0-13.3 10.7-24 24-24h146.7l49 49c20.1 20.1 52.5 20.1 72.6.0l49-49H488c13.3.2 24 10.9 24 24.2zm-124 88c0-11-9-20-20-20s-20 9-20 20 9 20 20 20 20-9 20-20zm64 0c0-11-9-20-20-20s-20 9-20 20 9 20 20 20 20-9 20-20z'/%3E%3C/svg%3E"); }
.fa-trash-alt      { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='currentColor' d='M268 416h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12zM432 80h-82.4l-34-56.7A48 48 0 0 0 274.4 0h-100a48 48 0 0 0-41.2 23.3L99.4 80H16A16 16 0 0 0 0 96v16a16 16 0 0 0 16 16h16v336a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48V128h16a16 16 0 0 0 16-16V96a16 16 0 0 0-16-16zM171.8 50.9A6 6 0 0 1 177 48h94a6 6 0 0 1 5.2 2.9l22.1 36.9H149.7zm204.2 365.1a16 16 0 0 1-16 16H88a16 16 0 0 1-16-16V128h304zm-152-208h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12z'/%3E%3C/svg%3E"); }
.fa-search         { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M505 442.7L405.3 343c-4.5-4.5-10.6-7-17-7H372c27.6-35.3 44-79.7 44-128C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c48.3.0 92.7-16.4 128-44v16.3c0 6.4 2.5 12.5 7 17l99.7 99.7c9.4 9.4 24.6 9.4 33.9.0l28.3-28.3c9.4-9.4 9.4-24.6.1-34zM208 336c-70.7.0-128-57.2-128-128s57.2-128 128-128 128 57.2 128 128-57.3 128-128 128z'/%3E%3C/svg%3E"); }
.fa-file-csv       { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 384 512'%3E%3Cpath fill='currentColor' d='M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3.0 24-10.7 24-24V160H248c-13.2.0-24-10.8-24-24zm-96 144c0 4.4-3.6 8-8 8h-8c-8.8.0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h8c4.4.0 8 3.6 8 8v16c0 4.4-3.6 8-8 8h-8c-26.5.0-48-21.5-48-48v-32c0-26.5 21.5-48 48-48h8c4.4.0 8 3.6 8 8v16zm44.2 104h-8.2c-4.4.0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h8.2c5.4.0 9.8-5.4 9.8-12 0-6.8-4.4-12-9.8-12h-8.2c-4.4.0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h8.2c26.5.0 45.8 21.5 45.8 44 0 10.4-3.8 20-10.2 27.2C218.2 373.4 224 384.1 224 396c0 26.5-19.3 48-45.8 48h-8.2c-4.4.0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h8.2c5.4.0 9.8-5.4 9.8-12-.1-6.8-4.5-12-9.8-12zm96-140v16c0 4.4-3.6 8-8 8h-8c-4.4.0-8 3.6-8 8v96c0 4.4-3.6 8-8 8h-16c-4.4.0-8-3.6-8-8v-96c0-4.4-3.6-8-8-8h-8c-4.4.0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h56c4.4.0 8 3.6 8 8zM272 0l112 112H272V0zM160 404v4c0 6.6-5.4 12-12 12h-16c-6.6.0-12-5.4-12-12v-4c-17.6 0-32-14.4-32-32v-8c0-4.4 3.6-8 8-8h16c4.4.0 8 3.6 8 8v8h24v-20l-33.2-8.3A28 28 0 0 1 96 316v-4c0-15.5 12.6-28 28-28v-4c0-6.6 5.4-12 12-12h16c6.6.0 12 5.4 12 12v4c17.6.0 32 14.4 32 32v4c0 4.4-3.6 8-8 8h-16c-4.4.0-8-3.6-8-8v-4h-24v20l33.2 8.3A28 28 0 0 1 192 372v4c0 15.5-12.6 28-32 28z'/%3E%3C/svg%3E"); }
.fa-check          { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M173.9 439.4l-166.4-166.4c-10-10-10-26.2.0-36.2l36.2-36.2c10-10 26.2-10 36.2.0L192 312.7 432.1 72.6c10-10 26.2-10 36.2.0l36.2 36.2c10 10 10 26.2.0 36.2l-294.4 294.4c-10 10-26.2 10-36.2.0z'/%3E%3C/svg%3E"); }
.fa-spinner        { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath fill='currentColor' d='M304 48c0 26.5-21.5 48-48 48s-48-21.5-48-48 21.5-48 48-48 48 21.5 48 48zm-48 368c-26.5.0-48 21.5-48 48s21.5 48 48 48 48-21.5 48-48-21.5-48-48-48zM48 256c0 26.5-21.5 48-48 48S-48 282.5-48 256s21.5-48 48-48 48 21.5 48 48zm416 0c0-26.5 21.5-48 48-48s48 21.5 48 48-21.5 48-48 48-48-21.5-48-48z'/%3E%3C/svg%3E"); }
.fa-circle         { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Ccircle fill='currentColor' cx='256' cy='256' r='248'/%3E%3C/svg%3E"); }
@keyframes fa-spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
.fa-spin { animation:fa-spin 1s linear infinite; }
</style>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; font-size:14px; }
.topbar { background:linear-gradient(135deg,#1976D2,#0D47A1); color:white; padding:14px 24px;
          display:flex; align-items:center; gap:15px; flex-wrap:wrap; }
.topbar h1 { font-size:18px; font-weight:800; }
.topbar a { color:rgba(255,255,255,.85); text-decoration:none; font-size:13px; font-weight:600;
            background:rgba(255,255,255,.15); padding:7px 14px; border-radius:8px; display:flex;
            align-items:center; gap:6px; transition:background .2s; }
.topbar a:hover { background:rgba(255,255,255,.25); }
.topbar .ml { margin-left:auto; }
.wrap { max-width:1200px; margin:24px auto; padding:0 20px; }
.filters { background:white; border-radius:12px; padding:20px 24px; margin-bottom:20px;
           box-shadow:0 2px 8px rgba(0,0,0,.06); display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end; }
.filters label { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase;
                 display:block; margin-bottom:4px; letter-spacing:.5px; }
.filters select, .filters input[type=date], .filters input[type=text] {
    padding:8px 12px; border:2px solid #e5e7eb; border-radius:8px; font-size:13px;
    color:#1a1a2e; outline:none; background:white; min-width:140px; }
.filters select:focus, .filters input:focus { border-color:#1976D2; }
.btn-filter { background:#1976D2; color:white; border:none; padding:9px 20px; border-radius:8px;
              font-weight:700; font-size:13px; cursor:pointer; }
.btn-reset { background:#f3f4f6; color:#6b7280; border:none; padding:9px 16px; border-radius:8px;
             font-weight:600; font-size:13px; cursor:pointer; text-decoration:none; }
.stats-row { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.stat-card { background:white; border-radius:10px; padding:14px 20px; box-shadow:0 2px 8px rgba(0,0,0,.06);
             flex:1; min-width:140px; }
.stat-card .val { font-size:22px; font-weight:800; color:#1976D2; }
.stat-card .lbl { font-size:11px; color:#9ca3af; font-weight:600; text-transform:uppercase; margin-top:2px; }
.card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden; }
table { width:100%; border-collapse:collapse; }
th { background:#f8fafc; padding:12px 16px; text-align:left; font-size:11px; font-weight:700;
     color:#6b7280; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #f0f2f5; }
td { padding:12px 16px; border-bottom:1px solid #f8fafc; vertical-align:middle; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#fafafa; }
.badge-action { display:inline-flex; align-items:center; gap:6px; padding:4px 10px;
                border-radius:20px; font-size:11px; font-weight:700; }
.agent-pill { display:inline-block; background:#eff6ff; color:#1976D2; padding:3px 10px;
              border-radius:20px; font-size:12px; font-weight:700; }
.details-cell { font-size:12px; color:#6b7280; max-width:380px; line-height:1.5; }
.time-cell { font-size:12px; color:#9ca3af; white-space:nowrap; }
.pagination { display:flex; gap:6px; justify-content:center; padding:20px; flex-wrap:wrap; }
.pagination a, .pagination span { padding:7px 13px; border-radius:8px; font-size:13px; font-weight:600;
    text-decoration:none; border:2px solid #e5e7eb; color:#6b7280; background:white; }
.pagination a:hover { border-color:#1976D2; color:#1976D2; }
.pagination .active { background:#1976D2; color:white; border-color:#1976D2; }
.empty { text-align:center; padding:60px 20px; color:#9ca3af; }
.empty i { font-size:48px; margin-bottom:16px; display:block; }
</style>
</head>
<body>

<div class="topbar">
    <h1><i class="fas fa-history"></i> Activity Log</h1>
    <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="pos_products.php"><i class="fas fa-box"></i> Products</a>
    <a href="pos_reorder.php"><i class="fas fa-truck-loading"></i> Reorder</a>
    <a href="pos_expiry_alerts.php"><i class="fas fa-bell"></i> Expiry Alerts</a>
    <a href="pos_bundles.php"><i class="fas fa-layer-group"></i> Bundles</a>
    <?php if ($is_super): ?>
    <a href="pos_settings.php"><i class="fas fa-cog"></i> Settings</a>
    <?php endif; ?>
    <span class="ml" style="font-size:13px;opacity:.8;">
        <i class="fas fa-user"></i> <?= htmlspecialchars($agent_name) ?>
    </span>
</div>

<div class="wrap">

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="val"><?= number_format($total) ?></div>
            <div class="lbl">Total Events</div>
        </div>
        <?php
        $total_all   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM pos_activity_log"))['c'];
        $sale_count  = (int)mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) AS c FROM pos_activity_log WHERE action='sale_completed' AND DATE(created_at) = CURDATE()"))['c'];
        $agent_count = (int)mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(DISTINCT agent_name) AS c FROM pos_activity_log $where"))['c'];
        $oldest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(created_at) AS d FROM pos_activity_log"))['d'];
        ?>
        <div class="stat-card">
            <div class="val"><?= $sale_count ?></div>
            <div class="lbl">Sales Today</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= $agent_count ?></div>
            <div class="lbl">Active Users</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= number_format($total_all) ?></div>
            <div class="lbl">All-time Records</div>
        </div>
        <?php if ($is_super): ?>
        <div style="display:flex;flex-direction:column;gap:8px;justify-content:center;">
            <button onclick="exportLog()"
                style="background:#0891b2;color:white;border:none;padding:10px 18px;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:7px;white-space:nowrap;">
                <i class="fas fa-file-csv"></i> Export Full Log (CSV)
            </button>
            <button onclick="confirmClear()"
                style="background:#fee2e2;color:#dc2626;border:2px solid #fecaca;padding:10px 18px;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:7px;white-space:nowrap;">
                <i class="fas fa-trash-alt"></i> Clear Log After Export
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" class="filters">
        <div>
            <label>From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($f_from) ?>">
        </div>
        <div>
            <label>To</label>
            <input type="date" name="to" value="<?= htmlspecialchars($f_to) ?>">
        </div>
        <?php if ($is_super): ?>
        <div>
            <label>User</label>
            <select name="agent">
                <option value="">All Users</option>
                <?php foreach ($agents as $a): ?>
                <option value="<?= htmlspecialchars($a) ?>" <?= $f_agent === $a ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div>
            <label>Action Type</label>
            <select name="action">
                <option value="">All Actions</option>
                <?php foreach ($actions as $ac): ?>
                <option value="<?= htmlspecialchars($ac) ?>" <?= $f_action === $ac ? 'selected' : '' ?>>
                    <?= htmlspecialchars($action_meta[$ac]['label'] ?? $ac) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
            <a href="pos_activity.php" class="btn-reset" style="margin-left:6px;">Reset</a>
        </div>
    </form>

    <!-- Table -->
    <div class="card">
        <?php if (empty($logs)): ?>
        <div class="empty">
            <i class="fas fa-history"></i>
            <p>No activity found for the selected filters.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log):
                $meta  = $action_meta[$log['action']] ?? ['icon'=>'fa-circle','color'=>'#9ca3af','label'=>$log['action']];
                $dt    = new DateTime($log['created_at']);
            ?>
            <tr>
                <td class="time-cell">
                    <div style="font-weight:700;color:#1a1a2e;"><?= $dt->format('d M Y') ?></div>
                    <div><?= $dt->format('H:i:s') ?></div>
                </td>
                <td>
                    <span class="agent-pill"><?= htmlspecialchars($log['agent_name']) ?></span>
                </td>
                <td>
                    <span class="badge-action" style="background:<?= $meta['color'] ?>18;color:<?= $meta['color'] ?>;">
                        <i class="fas <?= $meta['icon'] ?>"></i>
                        <?= htmlspecialchars($meta['label']) ?>
                    </span>
                </td>
                <td class="details-cell">
                    <?= htmlspecialchars($log['details']) ?>
                    <?php if ($log['reference_id']): ?>
                        <span style="color:#d1d5db;font-size:11px;margin-left:4px;">
                            #<?= $log['reference_id'] ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td class="time-cell"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="pagination">
            <?php
            $base = '?' . http_build_query(array_filter(['from'=>$f_from,'to'=>$f_to,'agent'=>$f_agent,'action'=>$f_action]));
            for ($p = 1; $p <= $pages; $p++):
                if ($p == $page): ?>
                    <span class="active"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= $base ?>&page=<?= $p ?>"><?= $p ?></a>
                <?php endif;
            endfor; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>

</div>
</body>

<script>
var exported = false;

function exportLog() {
    var btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';

    fetch('ajax/pos_backup_ajax.php?action=export_activity')
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert('Export failed: ' + data.error); return; }
            var bytes = Uint8Array.from(atob(data.content), c => c.charCodeAt(0));
            var blob  = new Blob([bytes], {type:'text/csv'});
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = data.filename;
            a.click();
            exported = true;
            btn.innerHTML = '<i class="fas fa-check"></i> Exported (' + data.count + ' records)';
            btn.style.background = '#10b981';
        })
        .catch(err => { alert('Error: ' + err.message); })
        .finally(() => { btn.disabled = false; });
}

function confirmClear() {
    if (!exported) {
        alert('⚠ Please export the log first before clearing.\n\nClick "Export Full Log (CSV)" to download a backup copy, then you can safely clear.');
        return;
    }

    var total = <?= $total_all ?>;
    if (!confirm(
        '🗑 CLEAR ACTIVITY LOG?\n\n' +
        'This will permanently delete ALL ' + total.toLocaleString() + ' activity records.\n\n' +
        '✅ You have already exported a CSV backup.\n\n' +
        'Are you sure you want to continue?'
    )) return;

    // Second confirmation
    var typed = prompt('Type DELETE to confirm:');
    if (typed !== 'DELETE') { alert('Cancelled — log was not cleared.'); return; }

    fetch('ajax/pos_backup_ajax.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=clear_activity'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ Activity log cleared — ' + data.deleted + ' records deleted.');
            location.reload();
        } else {
            alert('❌ ' + data.error);
        }
    });
}
</script>
</html>
