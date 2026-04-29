<?php
/**
 * pos_escpos.php
 * Generates ESC/POS commands and sends to Windows thermal printer
 * Called internally by pos_ajax.php after complete_sale
 * Can also be called directly: pos_escpos.php?sale_id=X
 *
 * Fixed v3.2:
 *  - Item unit_price now converted USD → LBP correctly (was showing raw USD cents)
 *  - Item subtotal column added (qty × unit_price in LBP)
 *  - sendToPrinter() uses dynamic printer_name from settings (not hardcoded 'BIXOLON')
 *  - number_format(..., 0) for all LBP amounts — no decimal places
 */

// ── ESC/POS Constants ─────────────────────────────────────────────────────
define('ESC',         "\x1B");
define('GS',          "\x1D");
define('LF',          "\x0A");
define('INIT',        ESC . "@");          // Initialize printer
define('BOLD_ON',     ESC . "E\x01");      // Bold on
define('BOLD_OFF',    ESC . "E\x00");      // Bold off
define('ALIGN_LEFT',  ESC . "a\x00");      // Left align
define('ALIGN_CENTER',ESC . "a\x01");      // Center align
define('ALIGN_RIGHT', ESC . "a\x02");      // Right align
define('FONT_NORMAL', ESC . "!\x00");      // Normal size
define('FONT_DOUBLE', ESC . "!\x11");      // Double height+width
define('FONT_WIDE',   ESC . "!\x20");      // Double width only
define('CUT_PAPER',   GS  . "V\x41\x00"); // Full cut

// ─────────────────────────────────────────────────────────────────────────────
/**
 * Open cash drawer
 * Supports two connection types:
 *  - DK/RJ11: connected to printer DK port — ESC/POS pulse through printer
 *  - USB:     connected directly to PC — PowerShell / copy to port
 */
function openCashDrawer($conn) {
    $co = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT printer_name, cash_drawer, drawer_type, drawer_usb_name
         FROM company_settings LIMIT 1"));

    $printer_name    = trim($co['printer_name']    ?? '');
    $cash_drawer     = $co['cash_drawer']           ?? 'disabled';
    $drawer_type     = $co['drawer_type']           ?? 'dk';
    $drawer_usb_name = trim($co['drawer_usb_name'] ?? '');

    if ($cash_drawer === 'disabled') {
        return ['success' => false, 'error' => 'Cash drawer disabled in Settings'];
    }

    // ── DK/RJ11 — send ESC/POS pulse through printer ──────────────────────
    if ($drawer_type === 'dk') {
        if (empty($printer_name)) {
            return ['success' => false, 'error' => 'No printer name set — required for DK drawer'];
        }
        $data  = "\x1B\x70\x00\x19\xFA"; // Pin 2 (most common)
        $data .= "\x1B\x70\x01\x19\xFA"; // Pin 5 (fallback)
        return sendToPrinter($data, $printer_name);
    }

    // ── USB drawer — Windows / PowerShell ────────────────────────────────
    if ($drawer_type === 'usb') {

        // Method 1: Named USB device
        if (!empty($drawer_usb_name)) {
            $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'drawer_' . time() . '.bin';
            file_put_contents($tmp, "\x1B\x70\x00\x19\xFA");
            $esc = escapeshellarg($drawer_usb_name);
            $ret = -1;
            exec("copy /b " . escapeshellarg($tmp) . " {$esc} > NUL 2>&1", $out, $ret);
            @unlink($tmp);
            if ($ret === 0) return ['success' => true, 'method' => 'usb_named'];
        }

        // Method 2: PowerShell serial ports scan
        $cmd = 'powershell -Command "' .
               'Add-Type -AssemblyName System.IO.Ports;' .
               '$ports = [System.IO.Ports.SerialPort]::GetPortNames();' .
               'foreach ($p in $ports) {' .
               '  try {' .
               '    $s = New-Object System.IO.Ports.SerialPort $p,9600;' .
               '    $s.Open(); $s.Write([byte[]](0x1B,0x70,0x00,0x19,0xFA),0,5); $s.Close(); break;' .
               '  } catch {}' .
               '}" > NUL 2>&1';
        $ret = -1;
        exec($cmd, $out, $ret);
        if ($ret === 0) return ['success' => true, 'method' => 'usb_serial'];

        // Method 3: Try USB001–USB004 directly
        foreach (['USB001','USB002','USB003','USB004'] as $port) {
            $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'drawer_' . time() . '.bin';
            file_put_contents($tmp, "\x1B\x70\x00\x19\xFA");
            $ret = -1;
            exec("copy /b " . escapeshellarg($tmp) . " {$port} > NUL 2>&1", $out, $ret);
            @unlink($tmp);
            if ($ret === 0) return ['success' => true, 'method' => 'usb_port:' . $port];
        }

        return ['success' => false, 'error' => 'Could not open USB drawer. Check connection and USB device name in Settings.'];
    }

    return ['success' => false, 'error' => 'Unknown drawer type: ' . $drawer_type];
}

// ─────────────────────────────────────────────────────────────────────────────
/**
 * Main receipt builder — produces ESC/POS binary and sends to printer
 */
function printEscPos($sale_id, $conn) {

    $sid  = (int)$sale_id;

    // ── Load sale ──────────────────────────────────────────────────────────
    $sale = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM pos_sales WHERE id = $sid LIMIT 1"));
    if (!$sale) return ['success' => false, 'error' => 'Sale not found: #' . $sid];

    // ── Load items ─────────────────────────────────────────────────────────
    $items = [];
    $res   = mysqli_query($conn, "SELECT * FROM pos_sale_items WHERE sale_id = $sid ORDER BY id ASC");
    while ($r = mysqli_fetch_assoc($res)) $items[] = $r;

    // ── Load company settings ──────────────────────────────────────────────
    $co = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM company_settings LIMIT 1"));
    $company_name    = $co['company_name']    ?? 'NCC';
    $company_phone   = $co['company_phone']   ?? '';
    $company_address = $co['company_address'] ?? '';
    $receipt_footer  = $co['receipt_footer']  ?? 'Thank you for your business!';
    $vat_rate        = (float)($co['vat_rate']   ?? 0);     // e.g. 11  (not 0.11)
    $usd_to_lbp      = (float)($co['usd_to_lbp'] ?? 89700);
    $printer_name    = trim($co['printer_name']   ?? '');
    $paper_width     = $co['paper_width']          ?? '80mm';

    if (empty($printer_name)) {
        return ['success' => false, 'error' => 'No printer name configured in Settings.'];
    }

    // ── Character width per paper size ────────────────────────────────────
    // SRP-E300 80mm = 42 chars | 58mm = 32 chars
    $W = ($paper_width === '58mm') ? 32 : 42;

    // ── Payment label map ──────────────────────────────────────────────────
    $pay_labels = [
        'cash'          => 'Cash',
        'card'          => 'Card',
        'omt'           => 'OMT',
        'whish'         => 'Whish',
        'bank_transfer' => 'Bank Transfer',
        'cheque'        => 'Cheque',
        'credit'        => 'Credit',
    ];

    // ── Financial calculations (all in USD first, then → LBP) ─────────────
    $subtotal_usd    = (float)$sale['final_total'];                           // pre-VAT, pre-discount, USD
    $vat_amount_usd  = $vat_rate > 0 ? $subtotal_usd * ($vat_rate / 100) : 0;
    $total_vat_usd   = $subtotal_usd + $vat_amount_usd;                      // incl. VAT, USD

    $lbp_subtotal    = round($subtotal_usd  * $usd_to_lbp);
    $lbp_vat         = round($vat_amount_usd * $usd_to_lbp);
    $lbp_exact       = round($total_vat_usd  * $usd_to_lbp);                 // exact LBP before rounding
    $lbp_due         = round($lbp_exact / 5000) * 5000;                      // nearest LL 5,000
    $lbp_rounding    = $lbp_due - $lbp_exact;                                // negative = store absorbs

    // ── Start building ESC/POS data ────────────────────────────────────────
    $d = INIT;

    // ════════════════════════════════════════════════
    // HEADER
    // ════════════════════════════════════════════════
    $d .= ALIGN_CENTER;
    $d .= BOLD_ON . FONT_DOUBLE . mb_substr($company_name, 0, 20) . LF . FONT_NORMAL . BOLD_OFF;
    if ($company_address) $d .= wordwrap($company_address, $W, LF, true) . LF;
    if ($company_phone)   $d .= 'Tel: ' . $company_phone . LF;
    $d .= BOLD_ON . 'SALE RECEIPT' . LF . BOLD_OFF;
    $d .= date('d M Y H:i:s', strtotime($sale['created_at'])) . LF;
    $d .= str_repeat('-', $W) . LF;

    // ════════════════════════════════════════════════
    // SALE INFO
    // ════════════════════════════════════════════════
    $d .= ALIGN_LEFT;
    $d .= twoCol('Sale #',    '#' . $sale['id'],                                            $W) . LF;
    $d .= twoCol('Customer',  mb_substr($sale['client_name'] ?? 'Walk-in', 0, $W - 10),    $W) . LF;
    $d .= twoCol('Cashier',   $sale['agent_name'] ?? '',                                    $W) . LF;
    $d .= twoCol('Payment',   $pay_labels[$sale['payment_method']] ?? $sale['payment_method'], $W) . LF;

    // Refunded stamp
    if ($sale['status'] === 'refunded') {
        $d .= str_repeat('-', $W) . LF;
        $d .= ALIGN_CENTER . BOLD_ON . '** REFUNDED **' . LF . BOLD_OFF . ALIGN_LEFT;
    }

    // ════════════════════════════════════════════════
    // ITEMS TABLE
    // Column layout (42 chars):
    //   Name      : left,  max 18 chars
    //   Qty       : right, 4 chars
    //   Unit LBP  : right, 10 chars
    //   Sub LBP   : right, 10 chars
    //   Total     : 18+4+10+10 = 42
    // (58mm 32 chars: 10+4+9+9 = 32)
    // ════════════════════════════════════════════════
    $d .= str_repeat('-', $W) . LF;

    // Column widths
    $col_qty  = 4;
    $col_unit = ($W === 42) ? 10 : 9;
    $col_sub  = ($W === 42) ? 10 : 9;
    $col_name = $W - $col_qty - $col_unit - $col_sub; // 42 → 18 | 32 → 10

    // Header row
    $hdr_name = str_pad('PRODUCT', $col_name);
    $hdr_qty  = str_pad('QTY',  $col_qty,  ' ', STR_PAD_LEFT);
    $hdr_unit = str_pad('UNIT', $col_unit, ' ', STR_PAD_LEFT);
    $hdr_sub  = str_pad('SUB',  $col_sub,  ' ', STR_PAD_LEFT);
    $d .= BOLD_ON . $hdr_name . $hdr_qty . $hdr_unit . $hdr_sub . LF . BOLD_OFF;
    $d .= str_repeat('-', $W) . LF;

    // Item rows — FIX: unit_price stored in USD → convert to LBP
    foreach ($items as $item) {
        $unit_lbp = round((float)$item['unit_price'] * $usd_to_lbp);
        $sub_lbp  = round($unit_lbp * (int)$item['qty']);

        $name_cell = mb_substr($item['product_name'], 0, $col_name);
        $name_cell = str_pad($name_cell, $col_name); // pad to column width

        $qty_cell  = str_pad((string)(int)$item['qty'],                $col_qty,  ' ', STR_PAD_LEFT);
        $unit_cell = str_pad(number_format($unit_lbp, 0),             $col_unit, ' ', STR_PAD_LEFT);
        $sub_cell  = str_pad(number_format($sub_lbp,  0),             $col_sub,  ' ', STR_PAD_LEFT);

        $d .= $name_cell . $qty_cell . $unit_cell . $sub_cell . LF;
    }

    // ════════════════════════════════════════════════
    // TOTALS
    // ════════════════════════════════════════════════
    $d .= str_repeat('-', $W) . LF;

    if ($vat_rate > 0) {
        $d .= twoCol('TOTAL excl. VAT', 'LL ' . number_format($lbp_subtotal, 0), $W) . LF;
        $d .= twoCol('VAT (' . rtrim(rtrim(number_format($vat_rate, 2),'0'),'.') . '%)',
                     'LL ' . number_format($lbp_vat, 0), $W) . LF;
        $d .= str_repeat('-', $W) . LF;
        $d .= twoCol('TOTAL exact', 'LL ' . number_format($lbp_exact, 0), $W) . LF;
    } else {
        $d .= twoCol('TOTAL exact', 'LL ' . number_format($lbp_exact, 0), $W) . LF;
    }

    // Show rounding only when store absorbs (negative value)
    if ($lbp_rounding < 0) {
        $d .= twoCol('Rounding', 'LL ' . number_format($lbp_rounding, 0), $W) . LF;
    }

    $d .= str_repeat('-', $W) . LF;
    $d .= BOLD_ON . twoCol('TOTAL DUE', 'LL ' . number_format($lbp_due, 0), $W) . LF . BOLD_OFF;

    // ── Discount line (shown under totals if applicable) ───────────────────
    if ((float)($sale['discount'] ?? 0) > 0) {
        $disc_lbp = round((float)$sale['discount'] * $usd_to_lbp);
        $d .= twoCol('Discount applied', '-LL ' . number_format($disc_lbp, 0), $W) . LF;
    }

    // ── USD equivalent box ─────────────────────────────────────────────────
    if ($usd_to_lbp > 0) {
        $d .= str_repeat('-', $W) . LF;
        $d .= ALIGN_CENTER;
        $d .= '$ ' . number_format($total_vat_usd, 2) . LF;
        $d .= 'USD equivalent' . LF;
        $d .= '1 USD = ' . number_format($usd_to_lbp, 0) . ' LBP' . LF;
        $d .= ALIGN_LEFT;
    }

    // ════════════════════════════════════════════════
    // PAYMENT DETAILS (cash sales only)
    // ════════════════════════════════════════════════
    $paid_lbp = (float)($sale['paid_lbp'] ?? 0);
    $paid_usd = (float)($sale['paid_usd'] ?? 0);
    $chg_usd  = (float)($sale['change_usd'] ?? 0);
    $chg_lbp  = (float)($sale['change_lbp'] ?? 0);

    if ($sale['payment_method'] === 'cash' && ($paid_lbp > 0 || $paid_usd > 0)) {
        $d .= str_repeat('-', $W) . LF;
        $d .= BOLD_ON . 'PAYMENT DETAILS' . LF . BOLD_OFF;

        if ($paid_lbp > 0)
            $d .= twoCol('Paid LBP', 'LL ' . number_format($paid_lbp, 0), $W) . LF;
        if ($paid_usd > 0)
            $d .= twoCol('Paid USD', '$ '  . number_format($paid_usd, 2), $W) . LF;

        $has_change = $chg_usd > 0 || $chg_lbp > 0;
        if ($has_change) {
            if ($chg_usd > 0 && $chg_lbp > 0) {
                // Split change
                $d .= BOLD_ON . twoCol('Change (USD)', '$ '  . number_format($chg_usd, 0), $W) . LF . BOLD_OFF;
                $d .= BOLD_ON . twoCol('Change (LBP)', 'LL ' . number_format($chg_lbp, 0), $W) . LF . BOLD_OFF;
            } elseif ($chg_usd > 0) {
                $d .= BOLD_ON . twoCol('Change', '$ '  . number_format($chg_usd, 0), $W) . LF . BOLD_OFF;
            } else {
                $d .= BOLD_ON . twoCol('Change', 'LL ' . number_format($chg_lbp, 0), $W) . LF . BOLD_OFF;
            }
        } else {
            // Fallback: compute net from paid amounts
            $total_paid_lbp = $paid_lbp + round($paid_usd * $usd_to_lbp);
            $net_lbp        = $total_paid_lbp - $lbp_due;
            if ($net_lbp > 0) {
                $d .= BOLD_ON . twoCol('Change', 'LL ' . number_format($net_lbp, 0), $W) . LF . BOLD_OFF;
            } elseif ($net_lbp < 0) {
                $d .= BOLD_ON . twoCol('Remaining', 'LL ' . number_format(abs($net_lbp), 0), $W) . LF . BOLD_OFF;
            }
        }
    }

    // ════════════════════════════════════════════════
    // FOOTER
    // ════════════════════════════════════════════════
    $d .= str_repeat('-', $W) . LF;
    $d .= ALIGN_CENTER;
    $footer_clean = str_replace(['—', '–', "\xe2\x80\x94", "\xe2\x80\x93"], '-', $receipt_footer);
    $d .= $footer_clean . LF;
    $d .= $company_name . ' - ' . date('Y') . LF;

    // Feed 4 lines so last line clears cutter blade, then cut
    $d .= ESC . "d\x04";
    $d .= CUT_PAPER;

    return sendToPrinter($d, $printer_name);
}

// ─────────────────────────────────────────────────────────────────────────────
/**
 * Send raw ESC/POS bytes to Windows printer
 * Tries 5 methods in order, returns first success.
 * Uses dynamic $printer_name from company_settings (not hardcoded).
 */
function sendToPrinter($data, $printer_name) {
    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pos_receipt_' . time() . mt_rand(100,999) . '.bin';

    if (file_put_contents($tmp, $data) === false) {
        return ['success' => false, 'error' => 'Cannot write temp file to: ' . sys_get_temp_dir()];
    }

    $f      = escapeshellarg($tmp);
    $errors = [];

    // ── Method 1: \\localhost\<share> with correct quoting (handles spaces) ──
    $share     = '\\\\localhost\\' . $printer_name;
    $share_esc = '"' . $share . '"';
    $ret = -1;
    exec("copy /b {$f} {$share_esc} > NUL 2>&1", $out, $ret);
    if ($ret === 0) { @unlink($tmp); return ['success' => true, 'method' => 'share:' . $printer_name]; }
    $errors[] = "share({$printer_name}) ret={$ret}";

    // ── Method 2: Printer name directly (works when share = printer name) ────
    $pn_esc = escapeshellarg($printer_name);
    $ret = -1;
    exec("copy /b {$f} {$pn_esc} > NUL 2>&1", $out, $ret);
    if ($ret === 0) { @unlink($tmp); return ['success' => true, 'method' => 'printer_name']; }
    $errors[] = "printer_name ret={$ret}";

    // ── Method 3: Direct USB ports USB001–USB004 ──────────────────────────
    foreach (['USB001','USB002','USB003','USB004'] as $port) {
        $ret = -1;
        exec("copy /b {$f} {$port} > NUL 2>&1", $out, $ret);
        if ($ret === 0) { @unlink($tmp); return ['success' => true, 'method' => 'usb:' . $port]; }
        $errors[] = "{$port} ret={$ret}";
    }

    // ── Method 4: PowerShell Out-Printer ─────────────────────────────────
    $pn_ps = str_replace("'", "''", $printer_name);
    $tp_ps = str_replace("'", "''", $tmp);
    $cmd   = "powershell -Command \"Get-Content -Encoding Byte -Path '{$tp_ps}' | Out-Printer -Name '{$pn_ps}'\" > NUL 2>&1";
    $ret   = -1;
    exec($cmd, $out, $ret);
    if ($ret === 0) { @unlink($tmp); return ['success' => true, 'method' => 'powershell']; }
    $errors[] = "powershell ret={$ret}";

    // ── Method 5: Windows print command ──────────────────────────────────
    $cmd = 'print /D:"' . $printer_name . '" "' . $tmp . '" > NUL 2>&1';
    $ret = -1;
    exec($cmd, $out, $ret);
    if ($ret === 0) { @unlink($tmp); return ['success' => true, 'method' => 'win_print']; }
    $errors[] = "win_print ret={$ret}";

    @unlink($tmp);
    return [
        'success' => false,
        'error'   => 'All print methods failed. Details: ' . implode(' | ', $errors),
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
/**
 * Two-column row: left text flush-left, right text flush-right, total = $width
 */
function twoCol($left, $right, $width) {
    $left = mb_substr($left, 0, $width - mb_strlen($right) - 1);
    $pad  = $width - mb_strlen($left) - mb_strlen($right);
    return $left . str_repeat(' ', max(1, $pad)) . $right;
}

// ─────────────────────────────────────────────────────────────────────────────
// Entry point when called directly via HTTP
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_GET['sale_id']) || isset($_GET['action'])) {

    session_start();
    header('Content-Type: application/json');

    if (empty($_SESSION['oop'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']); exit();
    }

    $conn = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
    if (!$conn) {
        echo json_encode(['success' => false, 'error' => 'DB connection failed: ' . mysqli_connect_error()]); exit();
    }
    mysqli_set_charset($conn, 'utf8mb4');

    // ── Cash drawer manual trigger ─────────────────────────────────────────
    if (($_GET['action'] ?? '') === 'open_drawer') {
        echo json_encode(openCashDrawer($conn));
        mysqli_close($conn);
        exit();
    }

    // ── Check exec() is available ──────────────────────────────────────────
    $disabled = array_map('trim', explode(',', ini_get('disable_functions')));
    if (in_array('exec', $disabled)) {
        echo json_encode([
            'success' => false,
            'error'   => 'exec() is disabled in PHP. Fix: WAMP tray -> PHP -> php.ini -> find disable_functions -> remove "exec" -> restart WAMP.',
        ]); exit();
    }

    // ── Resolve sale ID ────────────────────────────────────────────────────
    $sid_raw = $_GET['sale_id'] ?? '';
    if ($sid_raw === 'latest') {
        $row     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM pos_sales ORDER BY id DESC LIMIT 1"));
        $sale_id = $row ? (int)$row['id'] : 0;
    } else {
        $sale_id = (int)$sid_raw;
    }

    if (!$sale_id) {
        echo json_encode(['success' => false, 'error' => 'No valid sale ID provided.']); exit();
    }

    $result = printEscPos($sale_id, $conn);
    mysqli_close($conn);
    echo json_encode($result);
    exit();
}
?>
