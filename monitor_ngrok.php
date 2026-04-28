<?php
// monitor_ngrok.php - Continuously monitor ngrok URL changes
echo "Starting ngrok URL monitor...\n";
echo "Press Ctrl+C to stop\n\n";

$lastUrl = '';
$checkInterval = 30; // Check every 30 seconds

while (true) {
    $currentTime = date('Y-m-d H:i:s');
    echo "[$currentTime] Checking ngrok status...\n";
    
    $ngrokApi = "http://127.0.0.1:4040/api/tunnels";
    $response = @file_get_contents($ngrokApi);
    
    if ($response === false) {
        echo "  WARNING: Cannot reach ngrok API. Is ngrok running?\n";
        sleep($checkInterval);
        continue;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['tunnels'][0]['public_url'])) {
        $currentUrl = $data['tunnels'][0]['public_url'];
        
        // Ensure HTTPS
        if (strpos($currentUrl, 'http://') === 0) {
            $currentUrl = str_replace('http://', 'https://', $currentUrl);
        }
        
        if ($currentUrl !== $lastUrl) {
            echo "  URL CHANGED: $currentUrl\n";
            
            $urlFile = __DIR__ . "/tracking_url.txt";
            if (file_put_contents($urlFile, $currentUrl) !== false) {
                echo "  SUCCESS: Updated tracking_url.txt\n";
                $lastUrl = $currentUrl;
            } else {
                echo "  ERROR: Failed to update tracking_url.txt\n";
            }
        } else {
            echo "  URL unchanged: $currentUrl\n";
        }
    } else {
        echo "  ERROR: No tunnels found\n";
    }
    
    echo "  Next check in {$checkInterval} seconds...\n\n";
    sleep($checkInterval);
}
?>