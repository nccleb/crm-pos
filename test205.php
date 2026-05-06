<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Agent | NCC CRM System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1976D2;
            --primary-dark: #0D47A1;
            --accent: #00C853;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --error: #ff3860;
            --success: #4caf50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            width: 100%;
            max-width: 500px;
        }
        
        .register-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1);
            animation: fadeIn 0.5s ease-out;
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        
        .register-body {
            padding: 30px;
        }
        
        .result-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background-color: rgba(76, 175, 80, 0.15);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .error {
            background-color: rgba(255, 56, 96, 0.15);
            color: var(--error);
            border: 1px solid var(--error);
        }
        
        .warning {
            background-color: rgba(255, 152, 0, 0.15);
            color: #ff9800;
            border: 1px solid #ff9800;
        }
        
        .info {
            background-color: rgba(33, 150, 243, 0.15);
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            font-size: 16px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.15);
            background-color: #fff;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 18px;
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 118, 210, 0.3);
        }
        
        .btn-register:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-login {
            display: inline-block;
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-top: 15px;
        }
        
        .btn-login:hover {
            background-color: rgba(25, 118, 210, 0.1);
            text-decoration: none;
            color: var(--primary-dark);
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            margin-top: 25px;
        }
        
        @media (max-width: 576px) {
            .register-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-card">
            <div class="register-header">
                <h3><i class="fas fa-user-plus"></i> Register New Agent</h3>
                <p>Create a new agent account for NCC CRM</p>
            </div>
            
            <div class="register-body">
                <?php
                session_start();
                
                // Database connection
                $idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
                if (mysqli_connect_errno()) {
                    echo '<div class="result-message error">';
                    echo '<i class="fas fa-exclamation-triangle"></i> Failed to connect to MySQL: ' . mysqli_connect_error();
                    echo '</div>';
                    exit();
                }
                
                // Registration rate limiting functions
                function checkRegistrationRateLimit() {
                    $user_ip = $_SERVER['REMOTE_ADDR'];
                    
                    // Initialize session variables for registration tracking
                    if (!isset($_SESSION['registration_attempts'])) {
                        $_SESSION['registration_attempts'] = [];
                    }
                    
                    // Clean old attempts (older than 1 hour)
                    $current_time = time();
                    $_SESSION['registration_attempts'] = array_filter(
                        $_SESSION['registration_attempts'], 
                        function($attempt) use ($current_time) {
                            return ($current_time - $attempt['timestamp']) < 3600; // 1 hour
                        }
                    );
                    
                    // Check if IP has too many recent registration attempts
                    $ip_attempts = 0;
                    foreach ($_SESSION['registration_attempts'] as $attempt) {
                        if ($attempt['ip'] === $user_ip) {
                            $ip_attempts++;
                        }
                    }
                    
                    if ($ip_attempts >= 3) { // Max 3 registrations per hour per IP
                        return false;
                    }
                    
                    return true;
                }
                
                function recordRegistrationAttempt() {
                    $user_ip = $_SERVER['REMOTE_ADDR'];
                    $_SESSION['registration_attempts'][] = [
                        'ip' => $user_ip,
                        'timestamp' => time()
                    ];
                }
                
                // Function to check license limit
                function checkLicenseLimit($connection) {
                    // Get maximum allowed agents (default to 2 for your setup)
                    $max_agents = 4;
                    
                    // Count currently active agents
                    $result = mysqli_query($connection, "SELECT COUNT(*) as agent_count FROM form_element WHERE active = 1");
                    if ($result) {
                        $row = mysqli_fetch_assoc($result);
                        $current_agents = $row['agent_count'];
                        
                        // Check if we've reached the limit
                        if ($current_agents >= $max_agents) {
                            return false; // Limit reached
                        }
                    }
                    return true; // License available
                }
                
                // Function to sanitize input
                function sanitize_input($data) {
                    $data = trim($data);
                    $data = stripslashes($data);
                    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
                    return $data;
                }
                
                // Check rate limit before processing
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
                    // Check registration rate limit
                    if (!checkRegistrationRateLimit()) {
                        echo '<div class="result-message error">';
                        echo '<i class="fas fa-exclamation-triangle"></i> Registration limit exceeded!';
                        echo '<br>Too many registration attempts from your location. Please try again in 1 hour.';
                        echo '</div>';
                    } else {
                        // Sanitize inputs
                        $name = sanitize_input($_POST['name']);
                        $email = sanitize_input($_POST['email']);
                        $password = sanitize_input($_POST['password']);
                        $confirm_password = sanitize_input($_POST['confirm_password']);
                        
                        // Validate inputs
                        $errors = [];
                        
                        if (empty($name) || strlen($name) < 3) {
                            $errors[] = "Name must be at least 3 characters long";
                        }
                        
                        // Validate name format (letters, numbers, spaces, Arabic characters)
                        if (!preg_match("/^[\p{L}0-9 .,\s\p{Arabic}]*$/u", $name)) {
                            $errors[] = "Name contains invalid characters";
                        }
                        
                        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = "Please enter a valid email address";
                        }
                        
                        if (empty($password) || strlen($password) < 6) {
                            $errors[] = "Password must be at least 6 characters long";
                        }
                        
                        if ($password !== $confirm_password) {
                            $errors[] = "Passwords do not match";
                        }
                        
                        // Check if name already exists
                        $escaped_name = mysqli_real_escape_string($idr, $name);
                        $check_name = mysqli_query($idr, "SELECT idf FROM form_element WHERE name = '$escaped_name'");
                        if (mysqli_num_rows($check_name) > 0) {
                            $errors[] = "Username already exists. Please choose a different name.";
                        }
                        
                        // Check if email already exists
                        $escaped_email = mysqli_real_escape_string($idr, $email);
                        $check_email = mysqli_query($idr, "SELECT idf FROM form_element WHERE eemail = '$escaped_email'");
                        if (mysqli_num_rows($check_email) > 0) {
                            $errors[] = "Email address already registered";
                        }
                        
                        // Check license limit
                        if (!checkLicenseLimit($idr)) {
                            $errors[] = "Maximum number of agents reached. Cannot register new users.";
                        }
                        
                        if (empty($errors)) {
                            // Hash password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $contact_date = date("YmdHis");
                            
                            // Prepare statement for security
                            $stmt = $idr->prepare("INSERT INTO form_element (name, eemail, password, contact, active) VALUES (?, ?, ?, ?, 1)");
                            $stmt->bind_param("ssss", $escaped_name, $escaped_email, $hashed_password, $contact_date);
                            
                            if ($stmt->execute()) {
                                recordRegistrationAttempt(); // Record successful registration
                                
                                echo '<div class="result-message success">';
                                echo '<i class="fas fa-check-circle"></i> Agent registered successfully!';
                                echo '<br>You can now login with your credentials.';
                                echo '</div>';
                                
                                echo '<div class="action-buttons">';
                                echo '<a href="login200.php?registered=1" class="btn-login">';
                                echo '<i class="fas fa-sign-in-alt"></i> Login Now';
                                echo '</a>';
                                echo '</div>';
                                
                                $stmt->close();
                                mysqli_close($idr);
                                exit(); // Stop execution after successful registration
                            } else {
                                recordRegistrationAttempt(); // Record failed attempt
                                echo '<div class="result-message error">';
                                echo '<i class="fas fa-exclamation-triangle"></i> Registration failed. Please try again.';
                                echo '</div>';
                            }
                            $stmt->close();
                        } else {
                            recordRegistrationAttempt(); // Record failed attempt
                            echo '<div class="result-message error">';
                            echo '<i class="fas fa-exclamation-triangle"></i> ';
                            foreach ($errors as $error) {
                                echo $error . '<br>';
                            }
                            echo '</div>';
                        }
                    }
                }
                
                // Check license status
                $license_available = checkLicenseLimit($idr);
                $rate_limit_ok = checkRegistrationRateLimit();
                
                if (!$license_available) {
                    echo '<div class="result-message warning">';
                    echo '<i class="fas fa-exclamation-triangle"></i> Maximum number of agents reached.';
                    echo '<br>Cannot register new users at this time.';
                    echo '</div>';
                }
                
                if (!$rate_limit_ok) {
                    echo '<div class="result-message warning">';
                    echo '<i class="fas fa-clock"></i> Registration temporarily unavailable.';
                    echo '<br>Too many attempts from your location. Please wait 1 hour.';
                    echo '</div>';
                }
                ?>
                
                <?php if ($license_available && $rate_limit_ok): ?>
                <form method="post" action="" id="registrationForm">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="name" class="form-control" id="name" 
                                   placeholder="Enter full name" required minlength="3" maxlength="50">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" class="form-control" id="email" 
                                   placeholder="Enter email address" required maxlength="100">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" class="form-control" id="password" 
                                   placeholder="Enter password" required minlength="6" maxlength="50">
                        </div>
                        <div class="password-requirements">
                            Password must be at least 6 characters long
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="confirm_password" class="form-control" id="confirm_password" 
                                   placeholder="Confirm password" required minlength="6" maxlength="50">
                        </div>
                    </div>
                    
                    <button type="submit" name="register" class="btn-register" id="registerBtn">
                        <i class="fas fa-user-plus"></i> Register Agent
                    </button>
                </form>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <a href="login200.php" class="btn-login">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const registerBtn = document.getElementById('registerBtn');
            
            // Real-time password confirmation validation
            function validatePasswords() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length > 0) {
                    if (password !== confirmPassword) {
                        confirmPasswordInput.style.borderColor = '#ff3860';
                        confirmPasswordInput.style.boxShadow = '0 0 0 3px rgba(255, 56, 96, 0.15)';
                        return false;
                    } else {
                        confirmPasswordInput.style.borderColor = '#4caf50';
                        confirmPasswordInput.style.boxShadow = '0 0 0 3px rgba(76, 175, 80, 0.15)';
                        return true;
                    }
                }
                return true;
            }
            
            // Name validation
            nameInput.addEventListener('input', function() {
                const name = this.value;
                const nameRegex = /^[\p{L}0-9 .,\s\p{Arabic}]*$/u;
                
                if (name.length >= 3 && nameRegex.test(name)) {
                    this.style.borderColor = '#4caf50';
                    this.style.boxShadow = '0 0 0 3px rgba(76, 175, 80, 0.15)';
                } else if (name.length > 0) {
                    this.style.borderColor = '#ff3860';
                    this.style.boxShadow = '0 0 0 3px rgba(255, 56, 96, 0.15)';
                }
            });
            
            // Email validation
            emailInput.addEventListener('input', function() {
                const email = this.value;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (emailRegex.test(email)) {
                    this.style.borderColor = '#4caf50';
                    this.style.boxShadow = '0 0 0 3px rgba(76, 175, 80, 0.15)';
                } else if (email.length > 0) {
                    this.style.borderColor = '#ff3860';
                    this.style.boxShadow = '0 0 0 3px rgba(255, 56, 96, 0.15)';
                }
            });
            
            // Password strength validation
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                
                if (password.length >= 6) {
                    this.style.borderColor = '#4caf50';
                    this.style.boxShadow = '0 0 0 3px rgba(76, 175, 80, 0.15)';
                } else if (password.length > 0) {
                    this.style.borderColor = '#ff3860';
                    this.style.boxShadow = '0 0 0 3px rgba(255, 56, 96, 0.15)';
                }
                
                validatePasswords();
            });
            
            confirmPasswordInput.addEventListener('input', validatePasswords);
            
            // Form submission validation
            if (form) {
                form.addEventListener('submit', function(e) {
                    const name = nameInput.value.trim();
                    const email = emailInput.value.trim();
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    const nameRegex = /^[\p{L}0-9 .,\s\p{Arabic}]*$/u;
                    
                    let isValid = true;
                    let errorMessage = '';
                    
                    if (name.length < 3 || !nameRegex.test(name)) {
                        errorMessage += 'Please enter a valid name (at least 3 characters).\n';
                        isValid = false;
                    }
                    
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        errorMessage += 'Please enter a valid email address.\n';
                        isValid = false;
                    }
                    
                    if (password.length < 6) {
                        errorMessage += 'Password must be at least 6 characters long.\n';
                        isValid = false;
                    }
                    
                    if (password !== confirmPassword) {
                        errorMessage += 'Passwords do not match.\n';
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert(errorMessage);
                    } else {
                        // Show loading state
                        registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
                        registerBtn.disabled = true;
                    }
                });
            }
        });
    </script>
</body>
</html>