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
 *
 * Fixed v4.7 post-release:
 *  - $total_vat_usd was undefined after v4.6 LBP migration — caused PHP warning that
 *    corrupted the ESC/POS binary stream, breaking printing and cash drawer trigger
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
define('CODEPAGE_AR', ESC . "t\x28");      // Codepage 40 = PC720b Arabic (confirmed working on this BIXOLON)
define('CODEPAGE_PC', ESC . "t\x00");      // Codepage 0  = PC437 (reset to Latin)

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
 * hasArabic() — detect Arabic characters in a UTF-8 string
 */
function hasArabic(string $text): bool {
    return (bool)preg_match('/\p{Arabic}/u', $text);
}

// ─────────────────────────────────────────────────────────────────────────────
/**
 * shapeArabicText() — convert base Arabic Unicode → FExx presentation forms
 * so GD/FreeType renders connected glyphs correctly.
 * Handles Lam-Alef ligatures (ل + ا/أ/إ/آ → لا etc.)
 */
function shapeArabicText(string $text): string {
    // Presentation form tables (isolated / final / initial / medial)
    $iso = $fin = $ini = $med = [];

    $letters = [
        0x0621 => [0xFE80, 0xFE80, 0xFE80, 0xFE80], // ء
        0x0622 => [0xFE81, 0xFE82, 0xFE81, 0xFE82], // آ
        0x0623 => [0xFE83, 0xFE84, 0xFE83, 0xFE84], // أ
        0x0624 => [0xFE85, 0xFE86, 0xFE85, 0xFE86], // ؤ
        0x0625 => [0xFE87, 0xFE88, 0xFE87, 0xFE88], // إ
        0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C], // ئ
        0x0627 => [0xFE8D, 0xFE8E, 0xFE8D, 0xFE8E], // ا
        0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92], // ب
        0x0629 => [0xFE93, 0xFE94, 0xFE93, 0xFE94], // ة
        0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98], // ت
        0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C], // ث
        0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0], // ج
        0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4], // ح
        0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8], // خ
        0x062F => [0xFEA9, 0xFEAA, 0xFEA9, 0xFEAA], // د
        0x0630 => [0xFEAB, 0xFEAC, 0xFEAB, 0xFEAC], // ذ
        0x0631 => [0xFEAD, 0xFEAE, 0xFEAD, 0xFEAE], // ر
        0x0632 => [0xFEAF, 0xFEB0, 0xFEAF, 0xFEB0], // ز
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4], // س
        0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8], // ش
        0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC], // ص
        0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0], // ض
        0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4], // ط
        0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8], // ظ
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC], // ع
        0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0], // غ
        0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4], // ف
        0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8], // ق
        0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC], // ك
        0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0], // ل
        0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4], // م
        0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8], // ن
        0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC], // ه
        0x0648 => [0xFEED, 0xFEEE, 0xFEED, 0xFEEE], // و
        0x0649 => [0xFEEF, 0xFEF0, 0xFEEF, 0xFEF0], // ى
        0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4], // ي
    ];

    // Letters that do NOT connect to the left (disconnect after)
    $no_left = [0x0621,0x0622,0x0623,0x0624,0x0625,0x0627,0x0629,0x062F,
                0x0630,0x0631,0x0632,0x0648,0x0649];
    $no_left_set = array_flip($no_left);

    // Convert UTF-8 string to array of codepoints
    $codepoints = [];
    $len = mb_strlen($text, 'UTF-8');
    for ($i = 0; $i < $len; $i++) {
        $codepoints[] = mb_ord(mb_substr($text, $i, 1, 'UTF-8'), 'UTF-8');
    }

    $result = [];
    $n = count($codepoints);
    for ($i = 0; $i < $n; $i++) {
        $cp = $codepoints[$i];
        if (!isset($letters[$cp])) {
            $result[] = $cp;
            continue;
        }
        $has_prev = ($i > 0) && isset($letters[$codepoints[$i-1]])
                    && !isset($no_left_set[$codepoints[$i-1]]);
        $has_next = ($i < $n-1) && isset($letters[$codepoints[$i+1]]);
        if ($has_prev && $has_next)      $form = 3; // medial
        elseif ($has_prev)               $form = 1; // final
        elseif ($has_next)               $form = 2; // initial
        else                             $form = 0; // isolated
        $result[] = $letters[$cp][$form];
    }

    // Reverse for RTL rendering and encode back to UTF-8
    $result = array_reverse($result);
    $out = '';
    foreach ($result as $cp) {
        $out .= mb_chr($cp, 'UTF-8');
    }
    return $out;
}

// ─────────────────────────────────────────────────────────────────────────────
/**
 * printArabicLine() — render Arabic text as ESC* bitmap via GD + trado.ttf
 * Returns raw ESC/POS bytes for one line of Arabic text.
 * Falls back to '?' characters if GD or font not available.
 */
function printArabicLine(string $text, int $font_size = 28): string {
    $font = 'C:\Windows\Fonts\trado.ttf';
    if (!function_exists('imagecreatetruecolor') || !file_exists($font)) {
        $fallback = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: '???';
        return $fallback . "\n";
    }

    $shaped  = shapeArabicText($text);
    $ESC     = "\x1B";

    // ── Measure text to build narrow image ────────────────────────────────
    $box    = imagettfbbox($font_size, 0, $font, $shaped);
    $tw     = abs($box[2] - $box[0]);
    $th     = abs($box[5] - $box[3]);

    // CRITICAL: keep image narrow (max 200px).
    // Full-width 576px gets truncated by the Windows print spooler
    // causing Arabic to disappear entirely from the receipt.
    $img_w  = min($tw + 8, 200);
    $img_h  = $th + 10;

    $img   = imagecreatetruecolor($img_w, $img_h);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0,   0,   0  );
    imagefill($img, 0, 0, $white);

    $x = max(0, $img_w - $tw - 4);   // right-align within narrow image
    $y = $img_h - 4;
    imagettftext($img, $font_size, 0, $x, $y, $black, $font, $shaped);

    // ── Posterize — threshold luma < 128 ─────────────────────────────────
    // FreeType anti-aliases to grey (~R=192). Without posterize the
    // grey pixels fall above any reasonable threshold and print blank.
    for ($py = 0; $py < $img_h; $py++) {
        for ($px = 0; $px < $img_w; $px++) {
            $rgb  = imagecolorat($img, $px, $py);
            $luma = 0.299*(($rgb>>16)&0xFF) + 0.587*(($rgb>>8)&0xFF) + 0.114*($rgb&0xFF);
            imagesetpixel($img, $px, $py, $luma < 128 ? $black : $white);
        }
    }

    // ── ESC* 24-dot column raster ─────────────────────────────────────────
    $out     = '';
    $slice_h = 24;

    for ($row = 0; $row < $img_h; $row += $slice_h) {
        $out .= $ESC . '3' . chr($slice_h);
        $out .= $ESC . '*' . chr(33) . chr($img_w & 0xFF) . chr(($img_w >> 8) & 0xFF);
        for ($px = 0; $px < $img_w; $px++) {
            for ($b = 0; $b < 3; $b++) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $py = $row + $b * 8 + $bit;
                    if ($py < $img_h) {
                        $rgb  = imagecolorat($img, $px, $py);
                        $luma = 0.299*(($rgb>>16)&0xFF) + 0.587*(($rgb>>8)&0xFF) + 0.114*($rgb&0xFF);
                        if ($luma < 128) $byte |= (1 << (7 - $bit));
                    }
                }
                $out .= chr($byte);
            }
        }
        $out .= $ESC . 'J' . chr(24);   // advance paper exactly 24 dots per band
    }

    $out .= $ESC . '2';   // reset line spacing to default
    imagedestroy($img);
    return $out;
}

// ─────────────────────────────────────────────────────────────────────────────
/**
 * escposText() — for non-Arabic strings only. Arabic is handled by printArabicLine().
 */
function escposText(string $text): string {
    // Strip any remaining Arabic codepoints (should not happen — caller should use printArabicLine)
    return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
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

    // ── Financial calculations — all amounts now native LBP ───────────────
    $lbp_subtotal    = (float)$sale['final_total'];
    $lbp_vat         = $vat_rate > 0 ? round($lbp_subtotal * ($vat_rate / 100)) : 0;
    $lbp_exact       = round($lbp_subtotal + $lbp_vat);
    $lbp_due         = round($lbp_exact / 5000) * 5000;                      // nearest LL 5,000
    $lbp_rounding    = $lbp_due - $lbp_exact;                                // negative = store absorbs

    // ── Start building ESC/POS data ────────────────────────────────────────
    $d = INIT;

    // ════════════════════════════════════════════════
    // HEADER
    // ════════════════════════════════════════════════
    $d .= ALIGN_CENTER;
    $d .= BOLD_ON . FONT_DOUBLE . escposText(mb_substr($company_name, 0, 20)) . LF . FONT_NORMAL . BOLD_OFF;
    if ($company_address) $d .= escposText(wordwrap($company_address, $W, LF, true)) . LF;
    if ($company_phone)   $d .= 'Tel: ' . $company_phone . LF;
    $d .= BOLD_ON . 'SALE RECEIPT' . LF . BOLD_OFF;
    $d .= date('d M Y H:i:s', strtotime($sale['created_at'])) . LF;
    $d .= str_repeat('-', $W) . LF;

    // ════════════════════════════════════════════════
    // SALE INFO
    // ════════════════════════════════════════════════
    $d .= ALIGN_LEFT;
    $d .= twoCol('Sale #',    '#' . $sale['id'],                                            $W) . LF;
    $d .= twoCol('Customer',  escposText(mb_substr($sale['client_name'] ?? 'Walk-in', 0, $W - 10)),    $W) . LF;
    $d .= twoCol('Cashier',   escposText($sale['agent_name'] ?? ''),                                    $W) . LF;
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

    // Column widths — unit/sub need 12 chars for large LBP prices (e.g. 110,120,000)
    $col_qty  = 4;
    $col_unit = ($W === 42) ? 12 : 10;
    $col_sub  = ($W === 42) ? 12 : 10;
    $col_name = $W - $col_qty - $col_unit - $col_sub; // 42 → 14 | 32 → 8

    // Header row
    $hdr_name = str_pad('PRODUCT', $col_name);
    $hdr_qty  = str_pad('QTY',  $col_qty,  ' ', STR_PAD_LEFT);
    $hdr_unit = str_pad('UNIT', $col_unit, ' ', STR_PAD_LEFT);
    $hdr_sub  = str_pad('SUB',  $col_sub,  ' ', STR_PAD_LEFT);
    $d .= BOLD_ON . $hdr_name . $hdr_qty . $hdr_unit . $hdr_sub . LF . BOLD_OFF;
    $d .= str_repeat('-', $W) . LF;

    // Item rows — unit_price and subtotal now stored in LBP
    foreach ($items as $item) {
        $unit_lbp = round((float)$item['unit_price']);
        $qty_val  = (float)$item['qty'];
        $sub_lbp  = round((float)$item['subtotal']);
        $qty_disp = ($qty_val != intval($qty_val)) ? number_format($qty_val,3).'kg' : (string)(int)$qty_val;

        // Truncate numbers if they overflow column width
        $unit_str  = number_format($unit_lbp, 0);
        $sub_str   = number_format($sub_lbp,  0);
        if (strlen($unit_str) > $col_unit) $unit_str = substr($unit_str, -$col_unit);
        if (strlen($sub_str)  > $col_sub)  $sub_str  = substr($sub_str,  -$col_sub);

        $qty_cell  = str_pad($qty_disp, $col_qty,  ' ', STR_PAD_LEFT);
        $unit_cell = str_pad($unit_str, $col_unit, ' ', STR_PAD_LEFT);
        $sub_cell  = str_pad($sub_str,  $col_sub,  ' ', STR_PAD_LEFT);
        $numbers_row = $qty_cell . $unit_cell . $sub_cell . LF;

        $name_raw = $item['product_name'];

        if (hasArabic($name_raw)) {
            // Arabic name: bitmap line first, then numbers indented to right
            $d .= printArabicLine($name_raw);
            $d .= str_repeat(' ', $W - strlen($numbers_row) + 1) . $numbers_row;
        } else {
            // Latin name: truncate + mb-safe padding then inline
            $name_truncated = mb_substr($name_raw, 0, $col_name, 'UTF-8');
            $name_vis       = mb_strlen($name_truncated, 'UTF-8');
            $name_cell      = $name_truncated . str_repeat(' ', max(0, $col_name - $name_vis));
            $d .= $name_cell . $numbers_row;
        }
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
        $disc_lbp = round((float)$sale['discount']);
        $d .= twoCol('Discount applied', '-LL ' . number_format($disc_lbp, 0), $W) . LF;
    }

    // ── USD equivalent box ─────────────────────────────────────────────────
    if ($usd_to_lbp > 0) { // compute USD equivalent from LBP total
        $total_vat_usd = $lbp_exact / $usd_to_lbp;   // Fixed v4.7: was undefined after LBP migration
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
            // Use a LL 5,000 tolerance to suppress rounding noise from USD conversion
            $total_paid_lbp = $paid_lbp + round($paid_usd * $usd_to_lbp);
            $net_lbp        = $total_paid_lbp - $lbp_due;
            if ($net_lbp > 5000) {
                $d .= BOLD_ON . twoCol('Change', 'LL ' . number_format($net_lbp, 0), $W) . LF . BOLD_OFF;
            } elseif ($net_lbp < -5000) {
                $d .= BOLD_ON . twoCol('Remaining', 'LL ' . number_format(abs($net_lbp), 0), $W) . LF . BOLD_OFF;
            }
            // amounts within LL 5,000 of due = exact payment, show nothing
        }
    }

    // ════════════════════════════════════════════════
    // LOYALTY SECTION
    // ════════════════════════════════════════════════
    $loyalty_mode_disp = $co['loyalty_mode'] ?? 'disabled';
    $loyalty_earned    = 0;
    $loyalty_redeemed  = 0;
    $loyalty_bal_after = null;

    if (!empty($sale['client_id']) && $loyalty_mode_disp !== 'disabled') {
        $lt_res = mysqli_query($conn,
            "SELECT type, amount, balance_after FROM pos_loyalty_transactions
             WHERE sale_id = $sid ORDER BY id ASC");
        while ($lt = mysqli_fetch_assoc($lt_res)) {
            if ($lt['type'] === 'earned')   { $loyalty_earned   = (int)$lt['amount']; $loyalty_bal_after = (int)$lt['balance_after']; }
            if ($lt['type'] === 'redeemed') { $loyalty_redeemed = (int)$lt['amount']; }
        }
    }

    if ($loyalty_earned > 0 || $loyalty_redeemed > 0) {
        $mode_label = strtoupper($loyalty_mode_disp); // POINTS or CASHBACK
        $d .= str_repeat('-', $W) . LF;
        $d .= ALIGN_LEFT;
        $d .= BOLD_ON . 'LOYALTY ' . $mode_label . LF . BOLD_OFF;

        if ($loyalty_redeemed > 0) {
            $redeem_label = ($loyalty_mode_disp === 'points') ? 'Points Redeemed' : 'Wallet Used';
            $redeem_val   = ($loyalty_mode_disp === 'points')
                ? '-' . number_format($loyalty_redeemed) . ' pts'
                : '-LL ' . number_format($loyalty_redeemed);
            $d .= twoCol($redeem_label, $redeem_val, $W) . LF;
        }

        if ($loyalty_earned > 0) {
            $earn_label = ($loyalty_mode_disp === 'points') ? 'Points Earned' : 'Cashback Earned';
            $earn_val   = ($loyalty_mode_disp === 'points')
                ? '+' . number_format($loyalty_earned) . ' pts'
                : '+LL ' . number_format($loyalty_earned);
            $d .= twoCol($earn_label, $earn_val, $W) . LF;
        }

        if ($loyalty_bal_after !== null) {
            $bal_val = ($loyalty_mode_disp === 'points')
                ? number_format($loyalty_bal_after) . ' pts'
                : 'LL ' . number_format($loyalty_bal_after);
            $d .= twoCol('New Balance', $bal_val, $W) . LF;
        }
    }

    // ════════════════════════════════════════════════
    // FOOTER
    // ════════════════════════════════════════════════
    $d .= str_repeat('-', $W) . LF;
    $d .= ALIGN_CENTER;
    $footer_clean = str_replace(['—', '–', "\xe2\x80\x94", "\xe2\x80\x93"], '-', $receipt_footer);
    $d .= escposText($footer_clean) . LF;
    $d .= escposText($company_name) . ' - ' . date('Y') . LF;

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
    // Strip ESC/POS control bytes before measuring printable length
    $printable = function($s) {
        return preg_replace('/[\x00-\x1F\x7F](?:.{0,2})?/', '', $s);
    };
    $left_vis  = mb_strlen($printable($left),  'UTF-8');
    $right_vis = mb_strlen($printable($right), 'UTF-8');
    $pad = max(1, $width - $left_vis - $right_vis);
    return $left . str_repeat(' ', $pad) . $right;
}

// ─────────────────────────────────────────────────────────────────────────────
// Arabic codepage test — prints sample Arabic text with every possible codepage
// Usage: pos_escpos.php?action=test_arabic
// ─────────────────────────────────────────────────────────────────────────────
function testArabicCodepages($conn) {
    $co = mysqli_fetch_assoc(mysqli_query($conn, "SELECT printer_name FROM company_settings LIMIT 1"));
    $printer = trim($co['printer_name'] ?? '');
    if (!$printer) return ['success'=>false,'error'=>'No printer name in settings'];

    // Arabic sample in raw Windows-1256 bytes (avoids PHP source encoding issues)
    // These are the Windows-1256 byte values for: خيار (kheyyar)
    $sample_1256 = "\xd1\xed\xc7\xd1"; // simplified: just 4 Arabic letters
    $sample_rev  = strrev($sample_1256);

    $d  = "\x1B@"; // INIT
    $d .= "\x1B\x61\x01"; // center
    $d .= "Arabic Codepage Test\x0A";
    $d .= str_repeat('-', 32) . "\x0A";

    $codepages = [
        0x00 => 'CP00 PC437  ',
        0x05 => 'CP05 PC858  ',
        0x11 => 'CP17 PC866  ',
        0x15 => 'CP21 Win1256',
        0x16 => 'CP22 PC720  ',
        0x28 => 'CP40 PC720b ',
        0x2D => 'CP45 W1256b ',
    ];

    foreach ($codepages as $cp => $label) {
        $d .= "\x1B\x74\x00";           // reset to PC437
        $d .= "\x1B\x61\x00";           // left align
        $d .= sprintf("%s: ", $label);
        $d .= "\x1B\x74" . chr($cp);     // switch to candidate codepage
        $d .= $sample_rev . "\x0A";
        $d .= "\x1B\x74\x00";           // reset
    }

    $d .= str_repeat('-', 32) . "\x0A";
    $d .= "\x1B\x64\x04";               // feed 4 lines
    $d .= "\x1D\x56\x41\x00";          // cut

    return sendToPrinter($d, $printer);
}

// ─────────────────────────────────────────────────────────────────────────────
// Entry point when called directly via HTTP
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_GET['sale_id']) || isset($_GET['action'])) {

    // test_arabic bypasses login for local troubleshooting
    if (($_GET['action'] ?? '') === 'test_arabic') {
        header('Content-Type: application/json');
        $tc = mysqli_connect("192.168.1.19", "root", "1Sys9Admeen72", "nccleb_test");
        mysqli_set_charset($tc, 'utf8mb4');
        echo json_encode(testArabicCodepages($tc));
        mysqli_close($tc);
        exit();
    }

    session_start();
    header('Content-Type: application/json');

    if (empty($_SESSION['oop'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']); exit();
    }

    $conn = mysqli_connect("192.168.1.19", "root", "1Sys9Admeen72", "nccleb_test");
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
