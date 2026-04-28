<?php
session_start();
header('Content-Type: application/json');

// Simple test — send plain text + line feed + cut to USB001
$data  = "================================\n";
$data .= "       TEST RECEIPT\n";
$data .= "================================\n";
$data .= "Hello from NCC POS!\n";
$data .= "If you see this, printing works.\n";
$data .= "\n\n\n";
$data .= "\x1D\x56\x41\x00"; // Cut paper

$tmp = sys_get_temp_dir() . '\pos_test_' . time() . '.txt';
file_put_contents($tmp, $data);

$ret = -1;
exec('copy /b "' . $tmp . '" USB001 > NUL 2>&1', $out, $ret);
@unlink($tmp);

echo json_encode([
    'ret'     => $ret,
    'success' => $ret === 0,
    'tmp'     => $tmp,
    'data_len'=> strlen($data),
]);
?>
