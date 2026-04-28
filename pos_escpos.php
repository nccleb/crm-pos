<?php
/**
 * pos_escpos.php
 * Generates ESC/POS commands and sends to Windows thermal printer
 * Called internally by pos_ajax.php after complete_sale
 * Can also be called directly: pos_escpos.php?sale_id=X
 */

// ── ESC/POS Constants ────────────────────────────────────────────────────
define('ESC', "\x1B");
define('GS',  "\x1D");
define('LF',  "\x0A");
define('INIT',        ESC . "@");           // Initialize printer
define('BOLD_ON',     ESC . "E\x01");       // Bold on
define('BOLD_OFF',    ESC . "E\x00");       // Bold off
define('ALIGN_LEFT',  ESC . "a\x00");       // Left align
define('ALIGN_CENTER',ESC . "a\x01");       // Center align
define('ALIGN_RIGHT', ESC . "a\x02");       // Right align
define('FONT_NORMAL', ESC . "!\x00");       // Normal size
define('FONT_DOUBLE', ESC . "!\x11");       // Double height+width
define('FONT_WIDE',   ESC . "!\x20");       // Double width only
define('CUT_PAPER',   GS  . "V\x41\x00");  // Full cut
define('FEED_3',      ESC . "d\x03");       // Feed 3 lines

/**
 * Open cash drawer
 * Supports two connection types:
 * - DK/RJ11: connected to printer's DK port — opened via ESC/POS command through printer
 * - USB:     connected directly to PC USB — opened via PowerShell USB HID command
 */
function openCashDrawer($conn) {
    $co = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT printer_name, cash_drawer, drawer_type, drawer_usb_name FROM company_settings LIMIT 1"));
    $printer_name    = trim($co['printer_name']    ?? '');
    $cash_drawer     = $co['cash_drawer']           ?? 'disabled';
    $drawer_type     = $co['drawer_type']           ?? 'dk';
    $drawer_usb_name = trim($co['drawer_usb_name'] ?? '');

    if ($cash_drawer === 'disabled') {
        return ['success'=>false,'error'=>'Cash drawer disabled in Settings'];
    }

    // ── DK/RJ11 drawer — via printer ESC/POS command ──────────────────────
    if ($drawer_type === 'dk') {
        if (empty($printer_name)) {
            return ['success'=>false,'error'=>'No printer name set — required for DK drawer'];
        }
        // ESC p pin on-time off-time
        $data  = "\x1B\x70\x00\x19\xFA"; // Pin 2 (most common)
        $data .= "\x1B\x70\x01\x19\xFA"; // Pin 5 (fallback)
        return sendToPrinter($data, $printer_name);
    }

    // ── USB drawer — via Windows/PowerShell ───────────────────────────────
    if ($drawer_type === 'usb') {
        // Method 1: If USB drawer has a printer port assigned — copy empty file to it
        if (!empty($drawer_usb_name)) {
            $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'drawer_' . time() . '.bin';
            // Some USB drawers respond to any data sent to their port
            file_put_contents($tmp, "\x1B\x70\x00\x19\xFA");
            $esc = escapeshellarg($drawer_usb_name);
            $ret = -1;
            exec("copy /b " . escapeshellarg($tmp) . " {$esc} > NUL 2>&1", $out, $ret);
            @unlink($tmp);
            if ($ret === 0) return ['success'=>true,'method'=>'usb_copy'];
        }

        // Method 2: PowerShell — open USB HID device by VID/PID or friendly name
        // Try common USB cash drawer commands via PowerShell
        $ps_name = !empty($drawer_usb_name) ? $drawer_usb_name : 'Cash Drawer';
        $cmd = 'powershell -Command "' .
               '$drawer = New-Object -ComObject WScript.Shell;' .
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
        if ($ret === 0) return ['success'=>true,'method'=>'usb_serial'];

        // Method 3: Try USB001-USB004 directly
        foreach (['USB001','USB002','USB003','USB004'] as $port) {
            $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'drawer_' . time() . '.bin';
            file_put_contents($tmp, "\x1B\x70\x00\x19\xFA");
            $ret = -1;
            exec("copy /b " . escapeshellarg($tmp) . " {$port} > NUL 2>&1", $out, $ret);
            @unlink($tmp);
            if ($ret === 0) return ['success'=>true,'method'=>'usb_port:'.$port];
        }

        return ['success'=>false,'error'=>'Could not open USB drawer. Check connection and USB name in Settings.'];
    }

    return ['success'=>false,'error'=>'Unknown drawer type'];
}

/**
 * Main function — build and send receipt
 */
function printEscPos($sale_id, $conn) {
    // Load sale
    $sid  = (int)$sale_id;
    $sale = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM pos_sales WHERE id = $sid LIMIT 1"));
    if (!$sale) return ['success'=>false,'error'=>'Sale not found'];

    // Load items
    $items_res = mysqli_query($conn,
        "SELECT * FROM pos_sale_items WHERE sale_id = $sid");
    $items = [];
    while ($r = mysqli_fetch_assoc($items_res)) $items[] = $r;

    // Load company settings
    $co = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM company_settings LIMIT 1"));
    $company_name   = $co['company_name']   ?? 'NCC CRM';
    $company_phone  = $co['company_phone']  ?? '';
    $company_address= $co['company_address']?? '';
    $receipt_footer = $co['receipt_footer'] ?? 'Thank you for your business!';
    $vat_rate       = (float)($co['vat_rate']    ?? 0);
    $usd_to_lbp     = (float)($co['usd_to_lbp']  ?? 89500);
    $printer_name   = trim($co['printer_name']    ?? '');
    $paper_width    = $co['paper_width']           ?? '80mm';

    if (empty($printer_name)) {
        return ['success'=>false,'error'=>'No printer name set in Settings'];
    }

    // Paper width → character width
    // SRP-E300 80mm = 42 chars, 58mm = 32 chars
    $char_width = $paper_width === '58mm' ? 32 : 42;

    // Calculations — VAT = simply multiply by (1 + vat_rate/100)
    $sym            = 'LL ';
    $subtotal_usd   = (float)$sale['final_total'];                          // pre-VAT in USD
    $vat_amount     = $vat_rate > 0 ? $subtotal_usd * ($vat_rate / 100) : 0; // × 0.11
    $total_with_vat = $subtotal_usd + $vat_amount;                          // × 1.11
    $pay_labels     = ['cash'=>'Cash','card'=>'Card','omt'=>'OMT','whish'=>'Whish',
                       'bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','credit'=>'Credit'];

    // ── Build ESC/POS data ────────────────────────────────────────────────
    $data = INIT;

    // ── Header ─────────────────────────────────────────────────────────
    $data .= ALIGN_CENTER;
    $data .= BOLD_ON . FONT_DOUBLE . substr($company_name, 0, 20) . LF . FONT_NORMAL . BOLD_OFF;
    $data .= 'Sale Receipt' . LF;
    $data .= date('d M Y, H:i', strtotime($sale['created_at'])) . LF;
    if ($company_phone)   $data .= $company_phone . LF;
    if ($company_address) $data .= wordwrap($company_address, $char_width, LF, true) . LF;
    $data .= str_repeat('-', $char_width) . LF;

    // ── Sale info ──────────────────────────────────────────────────────
    $data .= ALIGN_LEFT;
    $data .= twoCol('Sale #',    '#' . $sale['id'], $char_width) . LF;
    $data .= twoCol('Customer',  substr($sale['client_name'], 0, $char_width - 10), $char_width) . LF;
    $data .= twoCol('Cashier',   $sale['agent_name'], $char_width) . LF;
    $data .= twoCol('Payment',   $pay_labels[$sale['payment_method']] ?? $sale['payment_method'], $char_width) . LF;
    $data .= twoCol('Currency',  $sale['currency'], $char_width) . LF;

    if ($sale['status'] === 'refunded') {
        $data .= str_repeat('-', $char_width) . LF;
        $data .= ALIGN_CENTER . BOLD_ON . '** REFUNDED **' . LF . BOLD_OFF . ALIGN_LEFT;
    }

    // ── Items ──────────────────────────────────────────────────────────
    $data .= str_repeat('-', $char_width) . LF;
    $data .= BOLD_ON . 'ITEMS' . LF . BOLD_OFF;
    $data .= str_repeat('-', $char_width) . LF;

    foreach ($items as $item) {
        $price_str = $sym . number_format($item['unit_price'], 2);
        $qty_str   = 'x' . $item['qty'];
        // Fixed width: qty (4 chars) + price (8 chars) = 12 chars right block
        $right = sprintf('%3s %8s', $qty_str, $price_str);
        $name  = mb_substr($item['product_name'], 0, $char_width - 13);
        $pad   = $char_width - mb_strlen($name) - mb_strlen($right);
        $data .= $name . str_repeat(' ', max(1, $pad)) . $right . LF;
    }

    // ── Totals ─────────────────────────────────────────────────────────
    $note         = 5000;
    $lbp_subtotal = round($subtotal_usd   * $usd_to_lbp);
    $lbp_taxable  = round($subtotal_usd   * $usd_to_lbp);
    $lbp_vat      = round($vat_amount     * $usd_to_lbp);
    $lbp_exact    = round($total_with_vat * $usd_to_lbp);  // exact before rounding
    $lbp_due      = round($lbp_exact / $note) * $note;      // rounded total due
    $lbp_rounding = $lbp_due - $lbp_exact;                  // + = rounded up, - = rounded down

    $data .= str_repeat('-', $char_width) . LF;
    $data .= twoCol('Subtotal', 'LL ' . number_format($lbp_subtotal, 0), $char_width) . LF;

    if ($sale['discount'] > 0) {
        $data .= twoCol('Discount', '-LL ' . number_format(round($sale['discount'] * $usd_to_lbp), 0), $char_width) . LF;
    }

    $data .= str_repeat('-', $char_width) . LF;

    if ($vat_rate > 0) {
        $data .= BOLD_ON . twoCol('TOTAL excl.VAT', 'LL ' . number_format($lbp_taxable, 0), $char_width) . LF . BOLD_OFF;
        $data .= twoCol('VAT (' . $vat_rate . '%)', 'LL ' . number_format($lbp_vat, 0), $char_width) . LF;
        $data .= str_repeat('-', $char_width) . LF;
        $data .= BOLD_ON . twoCol('TOTAL exact', 'LL ' . number_format($lbp_exact, 0), $char_width) . LF . BOLD_OFF;
    } else {
        $data .= BOLD_ON . twoCol('TOTAL exact', 'LL ' . number_format($lbp_exact, 0), $char_width) . LF . BOLD_OFF;
    }
    if ($lbp_rounding < 0) {
        $data .= twoCol('Rounding', 'LL ' . number_format($lbp_rounding, 0), $char_width) . LF;
    }
    $data .= str_repeat('-', $char_width) . LF;
    $data .= BOLD_ON . twoCol('TOTAL DUE', 'LL ' . number_format($lbp_due, 0), $char_width) . LF . BOLD_OFF;

    // ── USD equivalent ─────────────────────────────────────────────────
    if ($usd_to_lbp > 0) {
        $data .= str_repeat('-', $char_width) . LF;
        $data .= ALIGN_CENTER;
        $data .= 'Exchange rate: 1 USD = ' . number_format($usd_to_lbp, 0) . ' LBP' . LF;
        $data .= '$ ' . number_format($total_with_vat, 2) . ' USD equivalent' . LF;
        $data .= ALIGN_LEFT;
    }

    // ── Payment details (cash only) ────────────────────────────────────────
    if ($sale['payment_method'] === 'cash' &&
        ((float)($sale['paid_usd']??0) > 0 || (float)($sale['paid_lbp']??0) > 0)) {
        $total_paid_lbp = (float)($sale['paid_lbp']??0) + round((float)($sale['paid_usd']??0) * $usd_to_lbp);
        $net_lbp        = $total_paid_lbp - $lbp_due;
        $chg_usd        = (float)($sale['change_usd']??0);
        $chg_lbp        = (float)($sale['change_lbp']??0);
        $has_change     = $chg_usd > 0 || $chg_lbp > 0;
        $data .= str_repeat('-', $char_width) . LF;
        if ((float)($sale['paid_lbp']??0) > 0)
            $data .= twoCol('Paid LBP:', 'LL ' . number_format($sale['paid_lbp'], 0), $char_width) . LF;
        if ((float)($sale['paid_usd']??0) > 0)
            $data .= twoCol('Paid USD:', '$ ' . number_format($sale['paid_usd'], 0), $char_width) . LF;
        if ($has_change) {
            // Change: USD bills + LBP remainder
            if ($chg_usd > 0 && $chg_lbp > 0) {
                $data .= BOLD_ON . twoCol('Change (USD):', '$ ' . number_format($chg_usd, 0), $char_width) . LF . BOLD_OFF;
                $data .= BOLD_ON . twoCol('Change (LBP):', 'LL ' . number_format($chg_lbp, 0), $char_width) . LF . BOLD_OFF;
            } elseif ($chg_usd > 0) {
                $data .= BOLD_ON . twoCol('Change:', '$ ' . number_format($chg_usd, 0), $char_width) . LF . BOLD_OFF;
            } else {
                $data .= BOLD_ON . twoCol('Change:', 'LL ' . number_format($chg_lbp, 0), $char_width) . LF . BOLD_OFF;
            }
        } elseif ($net_lbp < 0) {
            $data .= BOLD_ON . twoCol('Remaining:', 'LL ' . number_format(abs($net_lbp), 0), $char_width) . LF . BOLD_OFF;
        } else {
            // Show rounding only when store absorbs (negative)
            $disp = ($net_lbp != 0) ? $net_lbp : $lbp_rounding;
            if ($disp < 0) {
                $data .= twoCol('Rounding:', 'LL ' . number_format($disp, 0), $char_width) . LF;
            }
        }
    }

    // ── Footer ─────────────────────────────────────────────────────────
    $data .= str_repeat('-', $char_width) . LF;
    $data .= ALIGN_CENTER;
    $data .= $receipt_footer . LF;
    $data .= $company_name . ' - ' . date('Y') . LF;

    // Feed 4 lines before cut so last line clears the cutter
    $data .= ESC . "d\x04";
    $data .= CUT_PAPER;

    // ── Send to printer ───────────────────────────────────────────────────
    return sendToPrinter($data, $printer_name);
}

/**
 * Send raw ESC/POS data to Windows printer
 */
function sendToPrinter($data, $printer_name) {
    // Write to a temp file
    $tmp_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pos_receipt_' . time() . '.bin';

    if (file_put_contents($tmp_file, $data) === false) {
        return ['success'=>false,'error'=>'Could not write temp file: ' . sys_get_temp_dir()];
    }

    $file_escaped = escapeshellarg($tmp_file);
    $errors = [];

    // Method 1: Network share — most reliable when Apache runs as SYSTEM
    $share = '\\\\localhost\\BIXOLON';
    $share_escaped = escapeshellarg($share);
    $cmd = "copy /b {$file_escaped} {$share_escaped} > NUL 2>&1";
    $ret = -1;
    exec($cmd, $out, $ret);
    if ($ret === 0) {
        @unlink($tmp_file);
        return ['success'=>true,'method'=>'share:BIXOLON'];
    }
    $errors[] = "Share BIXOLON failed (ret=$ret)";

    // Method 2: Printer name via copy /b
    $printer_escaped = escapeshellarg($printer_name);
    $cmd = "copy /b {$file_escaped} {$printer_escaped} > NUL 2>&1";
    $ret = -1;
    exec($cmd, $out, $ret);
    if ($ret === 0) {
        @unlink($tmp_file);
        return ['success'=>true,'method'=>'printer_name'];
    }
    $errors[] = "Printer name failed (ret=$ret)";

    // Method 3: Direct USB ports
    foreach (['USB001','USB002','USB003','USB004'] as $port) {
        $cmd = "copy /b {$file_escaped} {$port} > NUL 2>&1";
        $ret = -1;
        exec($cmd, $out, $ret);
        if ($ret === 0) {
            @unlink($tmp_file);
            return ['success'=>true,'method'=>'usb_port:'.$port];
        }
        $errors[] = "USB port $port failed (ret=$ret)";
    }

    // Method 4: PowerShell via shared queue
    $cmd = 'powershell -Command "Get-Content -Encoding Byte -Path \'' . $tmp_file . '\' | Out-Printer -Name \'' . $printer_name . '\'" > NUL 2>&1';
    $ret = -1;
    exec($cmd, $out, $ret);
    if ($ret === 0) {
        @unlink($tmp_file);
        return ['success'=>true,'method'=>'powershell_queue'];
    }
    $errors[] = "PowerShell queue failed (ret=$ret)";

    // Method 5: Windows print command
    $cmd = 'print /D:"' . $printer_name . '" "' . $tmp_file . '" > NUL 2>&1';
    $ret = -1;
    exec($cmd, $out, $ret);
    if ($ret === 0) {
        @unlink($tmp_file);
        return ['success'=>true,'method'=>'windows_print'];
    }
    $errors[] = "Windows print failed (ret=$ret)";

    @unlink($tmp_file);

    return [
        'success' => false,
        'error'   => 'Could not reach printer. Tried: USB001-USB004, printer name, network share. Details: ' . implode(' | ', $errors),
    ];
}

/**
 * Two-column row helper — left text + right text aligned
 */
function twoCol($left, $right, $width) {
    $left  = mb_substr($left, 0, $width - mb_strlen($right) - 1);
    $pad   = $width - mb_strlen($left) - mb_strlen($right);
    return $left . str_repeat(' ', max(1, $pad)) . $right;
}

/**
 * Center a string within width
 */
function mbStr($str, $width) {
    $len = mb_strlen($str);
    if ($len >= $width) return $str;
    $pad = intval(($width - $len) / 2);
    return str_repeat(' ', $pad) . $str;
}

// ── If called directly ────────────────────────────────────────────────────
if (isset($_GET['sale_id']) || isset($_GET['action'])) {
    session_start();
    header('Content-Type: application/json');
    if (empty($_SESSION['oop'])) {
        echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit();
    }
    $conn = mysqli_connect("192.168.1.101","root","1Sys9Admeen72","nccleb_test");
    mysqli_set_charset($conn,'utf8mb4');

    // Open cash drawer manually
    if (($_GET['action'] ?? '') === 'open_drawer') {
        echo json_encode(openCashDrawer($conn));
        mysqli_close($conn);
        exit();
    }

    // Check exec() is available
    $disabled = explode(',', ini_get('disable_functions'));
    $disabled = array_map('trim', $disabled);
    if (in_array('exec', $disabled)) {
        echo json_encode([
            'success' => false,
            'error'   => 'exec() is disabled in PHP. Go to WAMP → PHP → php.ini → find disable_functions → remove "exec" → restart WAMP.'
        ]);
        exit();
    }

    // Resolve sale ID
    $sid_raw = $_GET['sale_id'] ?? '';
    if ($sid_raw === 'latest') {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM pos_sales ORDER BY id DESC LIMIT 1"));
        $sale_id = $row ? (int)$row['id'] : 0;
    } else {
        $sale_id = (int)$sid_raw;
    }

    if (!$sale_id) {
        echo json_encode(['success'=>false,'error'=>'No sales found in database.']);
        exit();
    }

    $result = printEscPos($sale_id, $conn);
    mysqli_close($conn);
    echo json_encode($result);
    exit();
}
?>
