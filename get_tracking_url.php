<?php
// get_tracking_url.php - API endpoint to get current tracking URL
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$urlFile = __DIR__ . '/tracking_url.txt';

if (file_exists($urlFile)) {
    $url = trim(file_get_contents($urlFile));
    if (!empty($url)) {
        echo json_encode([
            'url' => $url, 
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'url' => 'https://8d9285c2f0dc.ngrok-free.app', 
            'success' => false,
            'error' => 'Empty URL file',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
} else {
    echo json_encode([
        'url' => 'https://8d9285c2f0dc.ngrok-free.app', 
        'success' => false,
        'error' => 'URL file not found',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>