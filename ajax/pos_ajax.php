<?php
/**
 * ajax/pos_ajax.php
 * Handles all POS AJAX requests
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['oop']) || empty($_SESSION['ooq'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$agent_name = $_SESSION['oop'];
$agent_id   = (int)$_SESSION['ooq'];

$conn = mysqli_connect("192.168.1.19", "root", "1Sys9Admeen72", "nccleb_test");
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit();
}
mysqli_set_charset($conn, 'utf8mb4');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── Search clients ─────────────────────────────────────────────────────
    case 'search_clients':
        $q = '%' . mysqli_real_escape_string($conn, $_GET['q'] ?? '') . '%';

        // Fetch grade thresholds to compute grade dynamically
        $co_cl = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT grade_gold_threshold, grade_platinum_threshold, grade_premium_threshold
             FROM company_settings LIMIT 1"));
        $tg  = (int)($co_cl['grade_gold_threshold']     ?? 5000000);
        $tp  = (int)($co_cl['grade_platinum_threshold'] ?? 15000000);
        $tpm = (int)($co_cl['grade_premium_threshold']  ?? 30000000);

        $res = mysqli_query($conn,
            "SELECT id, nom, prenom, company, number,
                    loyalty_card, total_spent,
                    CASE
                        WHEN total_spent >= $tpm THEN 'premium'
                        WHEN total_spent >= $tp  THEN 'platinum'
                        WHEN total_spent >= $tg  THEN 'gold'
                        ELSE 'regular'
                    END AS grade
             FROM client
             WHERE nom LIKE '$q' OR prenom LIKE '$q'
             OR company LIKE '$q' OR number LIKE '$q'
             ORDER BY nom, prenom LIMIT 10");
        $clients = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $clients[] = [
                'id'           => $r['id'],
                'name'         => trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? '')),
                'company'      => $r['company']      ?? '',
                'number'       => $r['number']        ?? '',
                'grade'        => $r['grade']         ?? 'regular',
                'loyalty_card' => $r['loyalty_card']  ?? '',
                'total_spent'  => (int)($r['total_spent'] ?? 0),
            ];
        }
        echo json_encode(['success' => true, 'data' => $clients]);
        break;

    // ── Search products ────────────────────────────────────────────────────
    case 'search_products':
        $raw = trim($_GET['q'] ?? '');
        $cat = trim($_GET['cat'] ?? '');
        $cat_sql = $cat ? "AND category = '" . mysqli_real_escape_string($conn, $cat) . "'" : '';

        if ($raw === '') {
            $res = mysqli_query($conn,
                "SELECT codep, nomp, price, onhand, unit, category, image, barcode
                 FROM produit WHERE active = 1 $cat_sql
                 ORDER BY nomp LIMIT 500");
        } else {
            $q = '%' . mysqli_real_escape_string($conn, $raw) . '%';
            $res = mysqli_query($conn,
                "SELECT codep, nomp, price, onhand, unit, category, image, barcode
                 FROM produit
                 WHERE active = 1 $cat_sql
                 AND (nomp LIKE '$q' OR barcode LIKE '$q')
                 ORDER BY nomp LIMIT 500");
        }
        $products = [];
        while ($r = mysqli_fetch_assoc($res)) $products[] = $r;
        echo json_encode(['success' => true, 'data' => $products]);
        break;

    // ── Get product by ID ──────────────────────────────────────────────────
    case 'get_product':
        $id  = (int)($_GET['id'] ?? 0);
        $res = mysqli_query($conn, "SELECT * FROM produit WHERE codep = $id AND active = 1 LIMIT 1");
        if ($row = mysqli_fetch_assoc($res)) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Product not found']);
        }
        break;

    // ── Complete a sale ────────────────────────────────────────────────────
    case 'complete_sale':
        $client_id      = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null;
        $client_name    = mysqli_real_escape_string($conn, $_POST['client_name'] ?? 'Walk-in Customer');
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'cash');
        $currency       = 'LBP';
        $discount       = (float)($_POST['discount'] ?? 0);
        $notes          = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        $items          = json_decode($_POST['items'] ?? '[]', true);
        $paid_usd       = (float)($_POST['paid_usd']   ?? 0);
        $paid_lbp       = (float)($_POST['paid_lbp']   ?? 0);

        // ── Loyalty parameters from frontend ──────────────────────────────
        $loyalty_client_id      = !empty($_POST['loyalty_client_id']) ? (int)$_POST['loyalty_client_id'] : null;
        $loyalty_auth_method    = in_array($_POST['loyalty_auth'] ?? '', ['card','phone_only','supervisor_override'])
                                  ? $_POST['loyalty_auth'] : 'card';
        $loyalty_use_wallet     = (int)($_POST['loyalty_use_wallet']  ?? 0); // LBP amount to redeem
        $loyalty_use_points     = (int)($_POST['loyalty_use_points']  ?? 0); // points to redeem
        $loyalty_supervisor_ovr = !empty($_POST['loyalty_supervisor_override']) ? 1 : 0;
        // Catalogue redemptions — [{cart_idx, product_id, points_cost, qty}]
        $catalogue_redemptions  = json_decode($_POST['catalogue_redemptions'] ?? '[]', true) ?: [];

        // Get exchange rate AND VAT rate AND loyalty settings
        $co_rate    = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT usd_to_lbp, vat_rate, loyalty_mode, loyalty_rate,
                    loyalty_point_value, loyalty_min_redeem, loyalty_min_grade,
                    loyalty_rate_regular, loyalty_rate_gold,
                    loyalty_rate_platinum, loyalty_rate_premium,
                    grade_gold_threshold, grade_platinum_threshold, grade_premium_threshold
             FROM company_settings LIMIT 1"));
        $usd_to_lbp        = (float)($co_rate['usd_to_lbp']              ?? 89500);
        $vat_rate          = (float)($co_rate['vat_rate']                 ?? 0);
        $loyalty_mode      = $co_rate['loyalty_mode']                     ?? 'disabled';
        $loyalty_rate      = (float)($co_rate['loyalty_rate']             ?? 2.00);
        $point_value       = (int)($co_rate['loyalty_point_value']        ?? 1000);
        $min_redeem        = (int)($co_rate['loyalty_min_redeem']         ?? 5000);
        $loyalty_min_grade = $co_rate['loyalty_min_grade']                ?? 'gold';
        $rate_regular      = (float)($co_rate['loyalty_rate_regular']     ?? $loyalty_rate);
        $rate_gold         = (float)($co_rate['loyalty_rate_gold']        ?? $loyalty_rate);
        $rate_platinum     = (float)($co_rate['loyalty_rate_platinum']    ?? $loyalty_rate);
        $rate_premium      = (float)($co_rate['loyalty_rate_premium']     ?? $loyalty_rate);
        $thr_gold          = (int)($co_rate['grade_gold_threshold']       ?? 5000000);
        $thr_platinum      = (int)($co_rate['grade_platinum_threshold']   ?? 15000000);
        $thr_premium       = (int)($co_rate['grade_premium_threshold']    ?? 30000000);

        if (empty($items)) {
            echo json_encode(['success' => false, 'error' => 'No items in sale']);
            break;
        }

        // Items arrive as LBP prices — store natively in LBP (no conversion)
        $total_lbp = 0;
        foreach ($items as $item) {
            $total_lbp += (float)$item['unit_price'] * (float)$item['qty'];
        }

        // ── Loyalty redemption applied as additional discount ─────────────
        $loyalty_discount_lbp = 0;
        if ($loyalty_client_id && $loyalty_mode !== 'disabled') {
            if ($loyalty_mode === 'cashback' && $loyalty_use_wallet > 0) {
                // Verify wallet has sufficient balance
                $cl = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT wallet_balance FROM client WHERE id = $loyalty_client_id LIMIT 1"));
                $available = (int)($cl['wallet_balance'] ?? 0);
                $loyalty_discount_lbp = min($loyalty_use_wallet, $available, $total_lbp);
            } elseif ($loyalty_mode === 'points' && $loyalty_use_points > 0) {
                // Verify points balance and convert to LBP
                $cl = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT loyalty_points FROM client WHERE id = $loyalty_client_id LIMIT 1"));
                $available_pts = (int)($cl['loyalty_points'] ?? 0);
                $pts_to_use    = min($loyalty_use_points, $available_pts);
                $loyalty_discount_lbp = $pts_to_use * $point_value;
                $loyalty_discount_lbp = min($loyalty_discount_lbp, $total_lbp);
                $loyalty_use_points   = $pts_to_use;
            }
        }

        // discount arrives in LBP — add loyalty redemption to it
        $total_discount       = $discount + $loyalty_discount_lbp;
        $final_total_lbp      = max(0, $total_lbp - $total_discount);

        // Change/remaining — computed server-side in LBP, change_usd always 0
        $note           = 5000;
        $total_due_lbp  = round($final_total_lbp * (1 + $vat_rate / 100) / $note) * $note;
        $total_paid_lbp = round($paid_lbp) + round($paid_usd * $usd_to_lbp);
        $net_lbp        = $total_paid_lbp - $total_due_lbp;
        $change_lbp     = $net_lbp >= 0 ? floor($net_lbp / $note) * $note : 0;
        $change_usd     = 0;

        // Insert sale header — all monetary columns in LBP
        $client_id_sql = $client_id ? $client_id : 'NULL';
        $insert = mysqli_query($conn,
            "INSERT INTO pos_sales (client_id, client_name, total, discount, final_total, payment_method, currency, notes, agent_id, agent_name, paid_usd, paid_lbp, change_usd, change_lbp)
             VALUES ($client_id_sql, '$client_name', $total_lbp, $total_discount, $final_total_lbp, '$payment_method', '$currency', '$notes', $agent_id, '$agent_name', $paid_usd, $paid_lbp, $change_usd, $change_lbp)"
        );

        if (!$insert) {
            echo json_encode(['success' => false, 'error' => 'Failed to create sale']);
            break;
        }

        $sale_id = mysqli_insert_id($conn);

        // Insert sale items, update stock and log movements
        foreach ($items as $item) {
            $product_id     = (int)$item['product_id'];
            $product_name   = mysqli_real_escape_string($conn, $item['product_name']);
            $qty            = (float)$item['qty'];
            $unit_price_lbp = (float)$item['unit_price'];
            $subtotal_lbp   = $qty * $unit_price_lbp;

            mysqli_query($conn,
                "INSERT INTO pos_sale_items (sale_id, product_id, product_name, qty, unit_price, subtotal)
                 VALUES ($sale_id, $product_id, '$product_name', $qty, $unit_price_lbp, $subtotal_lbp)"
            );

            $stock_row  = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT onhand FROM produit WHERE codep = $product_id LIMIT 1"));
            $qty_before = (float)($stock_row['onhand'] ?? 0);
            $qty_after  = max(0, $qty_before - $qty);
            $qty_change = $qty_after - $qty_before;

            mysqli_query($conn, "UPDATE produit SET onhand = $qty_after WHERE codep = $product_id");
            mysqli_query($conn,
                "INSERT INTO stock_movements (product_id, product_name, type, qty_change, qty_before, qty_after, reference_id, note, agent_id, agent_name)
                 VALUES ($product_id, '$product_name', 'sale', $qty_change, $qty_before, $qty_after, $sale_id, 'Sale #$sale_id', $agent_id, '$agent_name')"
            );
        }

        // ── Loyalty processing ─────────────────────────────────────────────
        $loyalty_earned  = 0;
        $loyalty_balance_after = 0;
        $loyalty_redeemed_display = 0;

        if ($loyalty_client_id && $loyalty_mode !== 'disabled') {
            require_once dirname(__FILE__) . '/pos_log.php';

            // Fetch fresh client data including grade
            $lc = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT wallet_balance, loyalty_points, total_spent,
                        nom, prenom, company, grade
                 FROM client WHERE id = $loyalty_client_id LIMIT 1"));
            $lc_name = mysqli_real_escape_string($conn,
                trim(($lc['prenom']??'').' '.($lc['nom']??'')) ?: ($lc['company']??''));
            $auth_esc    = mysqli_real_escape_string($conn, $loyalty_auth_method);
            $client_grade = $lc['grade'] ?? 'regular';

            // Pick earn rate for this client's grade
            $grade_rate = match($client_grade) {
                'gold'     => $rate_gold,
                'platinum' => $rate_platinum,
                'premium'  => $rate_premium,
                default    => $rate_regular,
            };

            // In cashback mode — card (or supervisor override) required to earn AND redeem
            // In points mode — phone_only is sufficient for both
            $can_earn = ($loyalty_mode === 'points')
                     || ($loyalty_auth_method === 'card')
                     || ($loyalty_auth_method === 'supervisor_override');

            // ── Grade eligibility order: regular < gold < platinum < premium ──
            $grade_order = ['regular'=>0,'gold'=>1,'platinum'=>2,'premium'=>3];
            $min_grade_order = $grade_order[$loyalty_min_grade] ?? 1;
            $client_grade_order = $grade_order[$client_grade] ?? 0;

            if ($loyalty_mode === 'cashback') {

                // 1. REDEEM wallet if requested
                if ($loyalty_discount_lbp > 0) {
                    $bal_before = (int)$lc['wallet_balance'];
                    $bal_after  = max(0, $bal_before - $loyalty_discount_lbp);
                    mysqli_query($conn,
                        "UPDATE client SET wallet_balance = $bal_after WHERE id = $loyalty_client_id");
                    mysqli_query($conn,
                        "INSERT INTO pos_loyalty_transactions
                         (client_id, client_name, sale_id, type, mode, amount,
                          balance_before, balance_after, auth_method, supervisor_override,
                          agent_id, agent_name, note)
                         VALUES ($loyalty_client_id, '$lc_name', $sale_id, 'redeemed', 'cashback',
                                 $loyalty_discount_lbp, $bal_before, $bal_after,
                                 '$auth_esc', $loyalty_supervisor_ovr,
                                 $agent_id, '$agent_name', 'Wallet used on Sale #$sale_id')");
                    $loyalty_redeemed_display = $loyalty_discount_lbp;
                    $lc['wallet_balance'] = $bal_after; // update for earn calc below
                }

                // 2. EARN cashback on the amount actually paid (not the wallet portion)
                if ($can_earn) {
                    $earn_base   = $final_total_lbp;
                    $earned_lbp  = (int)round($earn_base * $grade_rate / 100);
                    $earned_lbp  = (int)(floor($earned_lbp / 1000) * 1000);
                    if ($earned_lbp > 0) {
                        $bal_before = (int)$lc['wallet_balance'];
                        $bal_after  = $bal_before + $earned_lbp;
                        $new_total_spent = (int)$lc['total_spent'] + $final_total_lbp;

                        // Auto grade upgrade
                        $new_grade = $client_grade;
                        if      ($new_total_spent >= $thr_premium)  $new_grade = 'premium';
                        elseif  ($new_total_spent >= $thr_platinum) $new_grade = 'platinum';
                        elseif  ($new_total_spent >= $thr_gold)     $new_grade = 'gold';
                        else                                         $new_grade = 'regular';

                        $grade_sql = ($new_grade !== $client_grade)
                            ? ", grade = '$new_grade'" : '';

                        mysqli_query($conn,
                            "UPDATE client SET
                             wallet_balance    = $bal_after,
                             total_spent       = $new_total_spent,
                             last_purchase_date = CURDATE()
                             $grade_sql
                             WHERE id = $loyalty_client_id");
                        mysqli_query($conn,
                            "INSERT INTO pos_loyalty_transactions
                             (client_id, client_name, sale_id, type, mode, amount,
                              balance_before, balance_after, auth_method, supervisor_override,
                              agent_id, agent_name, note)
                             VALUES ($loyalty_client_id, '$lc_name', $sale_id, 'earned', 'cashback',
                                     $earned_lbp, $bal_before, $bal_after,
                                     '$auth_esc', $loyalty_supervisor_ovr,
                                     $agent_id, '$agent_name', 'Cashback on Sale #$sale_id ($client_grade rate: {$grade_rate}%)')");
                        $loyalty_earned        = $earned_lbp;
                        $loyalty_balance_after = $bal_after;

                        if ($new_grade !== $client_grade) {
                            posLog($conn, $agent_id, $agent_name, 'grade_upgraded',
                                "$lc_name upgraded from $client_grade to $new_grade (total spent: LL " . number_format($new_total_spent) . ")",
                                $loyalty_client_id, 'client');
                            $grade_upgraded = $new_grade;
                        }
                    }
                }

            } elseif ($loyalty_mode === 'points') {

                // 1. CATALOGUE REDEMPTIONS — per-item points deduction
                $total_catalogue_pts = 0;
                if (!empty($catalogue_redemptions)) {
                    $pts_before_cat = (int)$lc['loyalty_points'];
                    foreach ($catalogue_redemptions as $cr) {
                        $cr_pts = (int)($cr['points_cost'] ?? 0);
                        $cr_qty = (int)($cr['qty']         ?? 1);
                        $total_catalogue_pts += $cr_pts * $cr_qty;
                    }
                    $total_catalogue_pts = min($total_catalogue_pts, $pts_before_cat);
                    if ($total_catalogue_pts > 0) {
                        $pts_after_cat = max(0, $pts_before_cat - $total_catalogue_pts);
                        mysqli_query($conn,
                            "UPDATE client SET loyalty_points = $pts_after_cat WHERE id = $loyalty_client_id");
                        // Build readable note
                        $cat_items = implode(', ', array_map(fn($cr) =>
                            "{$cr['product_name']} x{$cr['qty']} ({$cr['points_cost']} pts each)",
                            $catalogue_redemptions));
                        mysqli_query($conn,
                            "INSERT INTO pos_loyalty_transactions
                             (client_id, client_name, sale_id, type, mode, amount,
                              balance_before, balance_after, auth_method, supervisor_override,
                              agent_id, agent_name, note)
                             VALUES ($loyalty_client_id, '$lc_name', $sale_id, 'redeemed', 'points',
                                     $total_catalogue_pts, $pts_before_cat, $pts_after_cat,
                                     '$auth_esc', $loyalty_supervisor_ovr,
                                     $agent_id, '$agent_name',
                                     'Catalogue: $cat_items — Sale #$sale_id')");
                        $loyalty_redeemed_display = $total_catalogue_pts;
                        $lc['loyalty_points']     = $pts_after_cat;
                    }
                }

                // 2. EARN points on the paid amount (catalogue items excluded — they were free)
                $earn_base    = $final_total_lbp;
                $earned_pts   = (int)floor(($earn_base / 1000) * $grade_rate);
                if ($earned_pts > 0) {
                    $pts_before      = (int)$lc['loyalty_points'];
                    $pts_after       = $pts_before + $earned_pts;
                    $new_total_spent = (int)$lc['total_spent'] + $final_total_lbp;

                    // Auto grade upgrade
                    $new_grade = $client_grade;
                    if      ($new_total_spent >= $thr_premium)  $new_grade = 'premium';
                    elseif  ($new_total_spent >= $thr_platinum) $new_grade = 'platinum';
                    elseif  ($new_total_spent >= $thr_gold)     $new_grade = 'gold';
                    else                                         $new_grade = 'regular';

                    $grade_sql = ($new_grade !== $client_grade)
                        ? ", grade = '$new_grade'" : '';

                    mysqli_query($conn,
                        "UPDATE client SET
                         loyalty_points     = $pts_after,
                         total_spent        = $new_total_spent,
                         last_purchase_date = CURDATE()
                         $grade_sql
                         WHERE id = $loyalty_client_id");
                    mysqli_query($conn,
                        "INSERT INTO pos_loyalty_transactions
                         (client_id, client_name, sale_id, type, mode, amount,
                          balance_before, balance_after, auth_method, supervisor_override,
                          agent_id, agent_name, note)
                         VALUES ($loyalty_client_id, '$lc_name', $sale_id, 'earned', 'points',
                                 $earned_pts, $pts_before, $pts_after,
                                 '$auth_esc', $loyalty_supervisor_ovr,
                                 $agent_id, '$agent_name', 'Points earned on Sale #$sale_id ($client_grade rate: {$grade_rate})')");
                    $loyalty_earned        = $earned_pts;
                    $loyalty_balance_after = $pts_after;

                    if ($new_grade !== $client_grade) {
                        posLog($conn, $agent_id, $agent_name, 'grade_upgraded',
                            "$lc_name upgraded from $client_grade to $new_grade (total spent: LL " . number_format($new_total_spent) . ")",
                            $loyalty_client_id, 'client');
                        $grade_upgraded = $new_grade;
                    }
                }
            }
        }

        // ── Auto thermal print ─────────────────────────────────────────────
        $print_result = null;
        $co = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT print_mode, printer_name, cash_drawer FROM company_settings LIMIT 1"));
        if ($co && ($co['print_mode'] ?? 'manual') === 'automatic' && !empty($co['printer_name'])) {
            require_once dirname(__FILE__) . '/../pos_escpos.php';
            $print_result = printEscPos($sale_id, $conn);
        }

        // ── Auto open cash drawer ──────────────────────────────────────────
        $drawer_result = null;
        if ($co && ($co['cash_drawer'] ?? 'disabled') === 'automatic' && !empty($co['printer_name'])) {
            if (!function_exists('openCashDrawer')) require_once dirname(__FILE__) . '/../pos_escpos.php';
            $drawer_result = openCashDrawer($conn);
        }

        // ── Log activity ───────────────────────────────────────────────────
        require_once dirname(__FILE__) . '/pos_log.php';
        $item_count = count($items);
        posLog($conn, $agent_id, $agent_name, 'sale_completed',
            "Sale #$sale_id - $client_name - LL " . number_format($final_total_lbp, 0) . " - $item_count item(s)",
            $sale_id, 'sale');

        echo json_encode([
            'success'                  => true,
            'sale_id'                  => $sale_id,
            'final_total'              => $final_total_lbp,
            'loyalty_earned'           => $loyalty_earned,
            'loyalty_redeemed'         => $loyalty_redeemed_display,
            'loyalty_balance_after'    => $loyalty_balance_after,
            'loyalty_mode'             => $loyalty_mode,
            'loyalty_client_id'        => $loyalty_client_id,
            'grade_upgraded'           => $grade_upgraded ?? null,
            'print_result'             => $print_result,
            'drawer_result'            => $drawer_result,
        ]);
        break;

    // ── Get sale for receipt ───────────────────────────────────────────────
    case 'get_sale':
        $sale_id = (int)($_GET['id'] ?? 0);
        $sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pos_sales WHERE id = $sale_id LIMIT 1"));
        if (!$sale) {
            echo json_encode(['success' => false, 'error' => 'Sale not found']);
            break;
        }
        $items_res = mysqli_query($conn, "SELECT * FROM pos_sale_items WHERE sale_id = $sale_id");
        $items = [];
        while ($i = mysqli_fetch_assoc($items_res)) $items[] = $i;
        echo json_encode(['success' => true, 'sale' => $sale, 'items' => $items]);
        break;

    // ── Get client purchase history ────────────────────────────────────────
    case 'get_client_history':
        $client_id = (int)($_GET['client_id'] ?? 0);
        if (!$client_id) { echo json_encode(['success' => false, 'error' => 'No client']); break; }

        $co_h = mysqli_fetch_assoc(mysqli_query($conn, "SELECT vat_rate FROM company_settings LIMIT 1"));
        $vat_h = (float)($co_h['vat_rate'] ?? 0);
        $vat_mult_h = 1 + $vat_h / 100;

        // Last 10 completed sales for this client
        $sales_res = mysqli_query($conn,
            "SELECT id, created_at, status,
                    ROUND(ROUND(final_total * $vat_mult_h / 5000) * 5000) AS total_lbp,
                    payment_method
             FROM pos_sales
             WHERE client_id = $client_id AND status IN ('completed','pending','refunded')
             ORDER BY created_at DESC LIMIT 10");

        $sales = [];
        while ($s = mysqli_fetch_assoc($sales_res)) {
            // Fetch items for this sale
            $items_r = mysqli_query($conn,
                "SELECT product_name, qty FROM pos_sale_items WHERE sale_id = {$s['id']}");
            $items_h = [];
            while ($it = mysqli_fetch_assoc($items_r)) $items_h[] = $it;
            $sales[] = [
                'id'             => $s['id'],
                'date'           => $s['created_at'],
                'total'          => (int)$s['total_lbp'],
                'payment_method' => $s['payment_method'],
                'status'         => $s['status'],
                'items'          => $items_h,
            ];
        }

        // Summary
        $sum = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) AS count,
                    SUM(ROUND(ROUND(final_total * $vat_mult_h / 5000) * 5000)) AS total
             FROM pos_sales
             WHERE client_id = $client_id AND status IN ('completed','pending')"));

        echo json_encode([
            'success' => true,
            'sales'   => $sales,
            'summary' => ['count' => (int)$sum['count'], 'total' => (int)$sum['total']],
        ]);
        break;

    // ── Manual restock / adjustment ────────────────────────────────────────
    case 'adjust_stock':
        $product_id   = (int)$_POST['product_id'];
        $type         = in_array($_POST['type'], ['restock','adjustment','return']) ? $_POST['type'] : 'adjustment';
        $qty_change   = (int)$_POST['qty_change'];
        $note         = mysqli_real_escape_string($conn, $_POST['note'] ?? '');

        // Get current stock
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nomp, onhand FROM produit WHERE codep = $product_id LIMIT 1"));
        if (!$row) { echo json_encode(['success'=>false,'error'=>'Product not found']); break; }

        $qty_before = (int)$row['onhand'];
        $qty_after  = max(0, $qty_before + $qty_change);
        $product_name = mysqli_real_escape_string($conn, $row['nomp']);

        mysqli_query($conn, "UPDATE produit SET onhand = $qty_after WHERE codep = $product_id");
        mysqli_query($conn,
            "INSERT INTO stock_movements (product_id, product_name, type, qty_change, qty_before, qty_after, note, agent_id, agent_name)
             VALUES ($product_id, '$product_name', '$type', $qty_change, $qty_before, $qty_after, '$note', $agent_id, '$agent_name')"
        );

        require_once dirname(__FILE__) . '/pos_log.php';
        posLog($conn, $agent_id, $agent_name, 'stock_adjusted',
            "$product_name - $type - qty change: $qty_change (was $qty_before, now $qty_after)" . ($note ? " - $note" : ''),
            $product_id, 'product');

        echo json_encode(['success'=>true, 'qty_before'=>$qty_before, 'qty_after'=>$qty_after]);
        break;


    // ── Process refund ─────────────────────────────────────────────────────
    case 'process_refund':
        $sale_id = (int)($_POST['sale_id'] ?? 0);
        if (!$sale_id) { echo json_encode(['success'=>false,'error'=>'No sale ID']); break; }

        // Get sale — must be completed
        $sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pos_sales WHERE id=$sale_id LIMIT 1"));
        if (!$sale) { echo json_encode(['success'=>false,'error'=>'Sale not found']); break; }
        if ($sale['status'] === 'refunded') { echo json_encode(['success'=>false,'error'=>'Already refunded']); break; }

        // Restore stock for each item
        $items_res = mysqli_query($conn, "SELECT * FROM pos_sale_items WHERE sale_id=$sale_id");
        while ($item = mysqli_fetch_assoc($items_res)) {
            $product_id   = (int)$item['product_id'];
            $qty          = (float)$item['qty'];
            $product_name = mysqli_real_escape_string($conn, $item['product_name']);

            $stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT onhand FROM produit WHERE codep=$product_id LIMIT 1"));
            $qty_before = (float)($stock['onhand'] ?? 0);
            $qty_after  = $qty_before + $qty;

            mysqli_query($conn, "UPDATE produit SET onhand=$qty_after WHERE codep=$product_id");
            mysqli_query($conn, "INSERT INTO stock_movements
                (product_id, product_name, type, qty_change, qty_before, qty_after, reference_id, note, agent_id, agent_name)
                VALUES ($product_id, '$product_name', 'return', $qty, $qty_before, $qty_after, $sale_id, 'Refund of Sale #$sale_id', $agent_id, '$agent_name')");
        }

        // Flip sale status to refunded
        mysqli_query($conn, "UPDATE pos_sales SET status='refunded' WHERE id=$sale_id");

        // Log activity
        require_once dirname(__FILE__) . '/pos_log.php';
        posLog($conn, $agent_id, $agent_name, 'sale_refunded', "Refund of Sale #$sale_id", $sale_id, 'sale');

        echo json_encode(['success'=>true, 'sale_id'=>$sale_id]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

mysqli_close($conn);
?>
