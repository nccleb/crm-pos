<?php
$host = "172.18.208.1";
$user = "root";
$pass = "1Sys9Admeen72";
$db   = "nccleb_test";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
