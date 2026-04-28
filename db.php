<?php
$host = "192.168.1.101";
$user = "root";
$pass = "1Sys9Admeen72";
$db   = "nccleb_test";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
