<?php
$servername = "192.168.1.101";
$username = "root";
$password = "1Sys9Admeen72";
$dbname = "nccleb_test";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$log_file = "C:\\Mdr\\CallerID2025-10.txt";
if (!file_exists($log_file)) {
    die("Log file not found");
}

$lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (preg_match('/<([\d\- :]+)>\s+(\d+)\s+(\d+)/', $line, $matches)) {
        $call_time = $matches[1];
        $line_number = $matches[2];
        $phone_number = $matches[3];

        // Insert if not already in DB
        $stmt = $conn->prepare("SELECT COUNT(*) FROM call_history WHERE call_time=? AND phone_number=?");
        $stmt->bind_param("ss", $call_time, $phone_number);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0) {
            $insert = $conn->prepare("INSERT INTO call_history (call_time, line_number, phone_number) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $call_time, $line_number, $phone_number);
            $insert->execute();
            $insert->close();
        }
    }
}

echo "✅ Call history updated successfully.";
$conn->close();
?>
