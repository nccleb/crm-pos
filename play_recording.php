<?php
/**
 * play_recording.php
 * Streams a recording file to the browser.
 * Always reads the recording path from call_history_settings.json
 * so changing the folder in Settings takes effect immediately.
 */

// ── Load settings ────────────────────────────────────────────────
$settingsFile = __DIR__ . '/call_history_settings.json';

$defaultRecordingPath = 'c:/rec/';

if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
    $recordingPath = $settings['recording_path'] ?? $defaultRecordingPath;
} else {
    $recordingPath = $defaultRecordingPath;
}

// Normalize to forward slashes, ensure trailing slash
$recordingPath = rtrim(str_replace('\\', '/', $recordingPath), '/') . '/';

// ── Validate request ─────────────────────────────────────────────
if (empty($_GET['file'])) {
    http_response_code(400);
    exit('No file specified.');
}

$requestedFile = $_GET['file'];

// Security: block directory traversal
$requestedFile = str_replace(['../', '..\\', '\\'], ['', '', '/'], $requestedFile);
$requestedFile = ltrim($requestedFile, '/');

// Build full path
$fullPath = $recordingPath . $requestedFile;

// Try Windows-style path as fallback
if (!file_exists($fullPath)) {
    $fullPath = str_replace('/', '\\', $fullPath);
}

if (!file_exists($fullPath)) {
    http_response_code(404);
    exit('Recording not found: ' . htmlspecialchars($requestedFile));
}

// Security: ensure file is actually inside the recording directory
$realFile      = realpath($fullPath);
$realBase      = realpath(str_replace('/', DIRECTORY_SEPARATOR, rtrim($recordingPath, '/\\')));

if (!$realFile || !$realBase || strpos($realFile, $realBase) !== 0) {
    http_response_code(403);
    exit('Access denied.');
}

// ── Determine MIME type ──────────────────────────────────────────
$ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));

$mimeTypes = [
    'wav'  => 'audio/wav',
    'mp3'  => 'audio/mpeg',
    'gsm'  => 'audio/x-gsm',
    'ogg'  => 'audio/ogg',
    'opus' => 'audio/opus',
    'm4a'  => 'audio/mp4',
];

$mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';

// ── Stream the file ──────────────────────────────────────────────
$fileSize = filesize($realFile);

// Support HTTP Range requests (allows seeking in the audio player)
if (isset($_SERVER['HTTP_RANGE'])) {
    preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches);
    $start = intval($matches[1]);
    $end   = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;
    $length = $end - $start + 1;

    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$fileSize");
    header("Content-Length: $length");
    header("Content-Type: $mimeType");
    header('Accept-Ranges: bytes');
    header('Cache-Control: no-cache');

    $fp = fopen($realFile, 'rb');
    fseek($fp, $start);
    $remaining = $length;
    while (!feof($fp) && $remaining > 0) {
        $chunk = min(8192, $remaining);
        echo fread($fp, $chunk);
        $remaining -= $chunk;
        flush();
    }
    fclose($fp);
} else {
    header("Content-Type: $mimeType");
    header("Content-Length: $fileSize");
    header('Accept-Ranges: bytes');
    header('Cache-Control: no-cache');
    header('Content-Disposition: inline; filename="' . basename($realFile) . '"');

    readfile($realFile);
}
exit;