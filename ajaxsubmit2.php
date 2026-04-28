
<?php
session_start();
ob_start();








// Database connection
$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

// Rate limiting
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

// Check if user is rate limited
if ($_SESSION['login_attempts'] >= 5) {
    $time_elapsed = time() - $_SESSION['last_attempt'];
    if ($time_elapsed < 300) { // 5 minute lockout
        $remaining = 300 - $time_elapsed;
        die("<div style='text-align:center; padding:50px;'>
                <p style='color:red;font-size:24px;'>Too many failed attempts!</p>
                <p>Please wait " . ceil($remaining/60) . " more minutes before trying again.</p>
                <button type='button' onclick='history.back()' style='padding:10px 20px; background:#1976D2; color:white; border:none; border-radius:5px; cursor:pointer;'>Go Back</button>
             </div>");
    } else {
        // Reset attempts after lockout period
        $_SESSION['login_attempts'] = 0;
    }
}

// Function to sanitize input
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Function to redirect with message
function redirect_with_message($message, $redirect_url = 'login200.php') {
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt'] = time();
    echo "<script>alert('$message'); location.replace('$redirect_url');</script>";
    exit();
}

// Function to validate name format
function validate_name($name) {
    return preg_match("/^[\p{L}0-9 .,\s\p{Arabic}]*$/u", $name);
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('Invalid request method.');
}

// Validate required fields
if (!isset($_POST['name1']) || empty($_POST['name1']) || 
    !isset($_POST['password1']) || empty($_POST['password1'])) {
    redirect_with_message('Missing required fields!');
}

// Sanitize inputs
$name = test_input($_POST['name1']);
$password = test_input($_POST['password1']);

// Validate input formats
if (!validate_name($name)) {
    die("<div style='text-align:center; padding:50px;'>
            <p style='color:red;font-size:24px;'>Invalid Name format!</p>
            <button type='button' onclick='history.back()' style='padding:10px 20px; background:#1976D2; color:white; border:none; border-radius:5px; cursor:pointer;'>Go Back</button>
         </div>");
}

// Check for admin credentials first
if ($name === "admin" && $password === "admin") {
    $_SESSION["name"] = $name;
    $_SESSION["idf"] = 0;
    $_SESSION["id"] = session_id();
    $_SESSION['login_attempts'] = 0; // Reset attempts on successful login
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    header("Location: test204.php?pag=$name&pag1=0");
    exit();
}

// Prepare statement for regular user authentication
$stmt = $idr->prepare("SELECT idf, name, password FROM form_element WHERE name = ? AND active = 1");
if (!$stmt) {
    die("Database error: " . $idr->error);
}

$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    redirect_with_message('Invalid username or password!');
}

$user = $result->fetch_assoc();
$stmt->close();

// Verify password (supports both hashed and plaintext for backward compatibility)
$password_valid = false;

if (password_get_info($user['password'])['algo'] !== null) {
    // Password is hashed - use password_verify
    $password_valid = password_verify($password, $user['password']);
} else {
    // Password is plaintext (legacy) - direct comparison
    $password_valid = ($password === $user['password']);
    
    // Optional: Upgrade to hashed password
    if ($password_valid) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_stmt = $idr->prepare("UPDATE form_element SET password = ? WHERE idf = ?");
        $update_stmt->bind_param("si", $hashed_password, $user['idf']);
        $update_stmt->execute();
        $update_stmt->close();
    }
}

if ($password_valid) {
    $_SESSION["name"] = $user['name'];
    $_SESSION["idf"] = $user['idf'];
    $_SESSION["id"] = session_id();
    $_SESSION['login_attempts'] = 0; // Reset attempts on successful login
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Update last login
    $update_stmt = $idr->prepare("UPDATE form_element SET contact = 'Last login: ' + NOW() WHERE idf = ?");
    $update_stmt->bind_param("i", $user['idf']);
    $update_stmt->execute();
    $update_stmt->close();
    
    header("Location: test204.php?page=" . urlencode($user['name']) . "&page1=" . $user['idf']);
    exit();
} else {
    redirect_with_message('Invalid username or password!');
}

$idr->close();
ob_end_flush();
?>
            