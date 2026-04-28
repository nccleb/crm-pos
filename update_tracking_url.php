<?php
// update_tracking_url.php
$ngrokApi = "http://127.0.0.1:4040/api/tunnels";
$response = file_get_contents($ngrokApi);
$data = json_decode($response, true);

if (isset($data['tunnels'][0]['public_url'])) {
    $publicUrl = $data['tunnels'][0]['public_url'];
    file_put_contents(__DIR__ . "/tracking_url.txt", $publicUrl);
    echo "Updated tracking URL: $publicUrl\n";
} else {
    echo "Could not fetch ngrok URL\n";
}
