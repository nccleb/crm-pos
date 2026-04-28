<?php
session_start();

// ============================================
// DISPLAY ONLY - No database logging
// CDR Listener handles all database operations
// ============================================

$inc = "";
$lineNum = "";

// Get from session (set by test449.php)
if (isset($_SESSION["userinc"]) && !empty($_SESSION["userinc"])) {
    $inc = trim($_SESSION["userinc"]);
}

// Fallback to CallerID file
$opic = "c:" . "\\" . "Mdr" . "\\" . "CallerID" . date("Y") . "-" . date("m") . "." . "txt";
if (empty($inc) && file_exists($opic)) {
    $f = fopen($opic, 'r');
    if ($f) {
        $cursor = -1;
        fseek($f, $cursor, SEEK_END);
        $char = fgetc($f);
        
        while ($char === "\n" || $char === "\r") {
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }
        
        while ($char !== false && $char !== "\n" && $char !== "\r") {
            $line = $char . $line;
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }
        fclose($f);
        
        $inc = trim(substr($line, 49, 8));
    }
}

// Fallback to XML
$fichier = "CaCallStatus.dat";
if (empty($inc) && file_exists($fichier)) {
    $xml = simplexml_load_file($fichier);
    if ($xml) {
        foreach ($xml as $CallRecord) {
            if (isset($CallRecord->CallerID)) {
                $inc = trim((string)$CallRecord->CallerID);
            }
        }
    }
}

// Clean and validate
$inc = preg_replace('/[^0-9+]/', '', $inc);
$isValidPhone = !empty($inc) && strlen($inc) >= 3 && !preg_match('/DAHDI|SIP|IAX/i', $inc);

// DISPLAY ONLY - No database logging!
if ($isValidPhone) {
    echo $inc;
} else {
    echo "No valid caller ID";
}

// Note: Database logging is now handled by cdr_listener_STANDALONE.php
// This keeps the popup fast and prevents duplicates
?>