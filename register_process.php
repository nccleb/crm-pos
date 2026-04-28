<?php
session_start();
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

// Sanitize input
function test_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect_with_message($message, $redirect_url = 'test205.php', $is_error = true) {
    if ($is_error) {
        $_SESSION['error_message'] = $message;
    } else {
        $_SESSION['success_message'] = $message;
    }
    header("Location: $redirect_url");
    exit();
}

// Validate POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('Invalid request method.');
}

$required_fields = ['username', 'email', 'contact', 'password', 'confirm_password', 'name'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        redirect_with_message("Missing required field: $field");
    }
}

$username = test_input($_POST['username']);
$email = test_input($_POST['email']);
$contact = test_input($_POST['contact']);
$password = test_input($_POST['password']);
$confirm_password = test_input($_POST['confirm_password']);
$name = test_input($_POST['name']);

// Validation
if (!preg_match("/^[a-zA-Z0-9_]{3,50}$/", $username)) {
    redirect_with_message("Invalid username format!");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_message("Invalid email format!");
}
if (!preg_match("/^(03|70|71|76|78|79|81)\d{6}$/", $contact)) {
    redirect_with_message("Invalid phone number format!");
}
if (strlen($password) < 8 || !preg_match("/\d/", $password) || !preg_match("/[!@#$%^&*(),?\":{}|<>]/", $password)) {
    redirect_with_message("Password must be at least 8 characters and contain a number and a special character.");
}
if ($password !== $confirm_password) {
    redirect_with_message("Passwords do not match!");
}

// Check duplicates
$stmt = $idr->prepare("SELECT idf FROM form_element WHERE name = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    redirect_with_message("Username already exists!");
}
$stmt->close();

$stmt = $idr->prepare("SELECT idf FROM form_element WHERE eemail = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    redirect_with_message("Email already registered!");
}
$stmt->close();

// Insert user
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $idr->prepare("INSERT INTO form_element (name, eemail, password, contact, active) VALUES (?, ?, ?, ?, 1)");
$stmt->bind_param("ssss", $username, $email, $hashed_password, $contact);
if ($stmt->execute()) {
    // Optional: also create client record
    $idf = $stmt->insert_id;
    $stmt->close();

    $client_stmt = $idr->prepare("INSERT INTO client (nom, number, email, idf) VALUES (?, ?, ?, ?)");
    $client_stmt->bind_param("sssi", $name, $contact, $email, $idf);
    $client_stmt->execute();
    $client_stmt->close();

    $idr->close();
    redirect_with_message("Registration successful! Please login.", "login200.php", false);
} else {
    redirect_with_message("Registration failed: " . $stmt->error);
}
?>
