<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | NCC System</title>
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
            max-width: 450px;
        }
        
        .reset-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1);
            animation: fadeIn 0.5s ease-out;
        }
        
        .reset-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        
        .reset-body {
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
        
        .btn-reset {
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
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 118, 210, 0.3);
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
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-login {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-card">
            <div class="reset-header">
                <h3><i class="fas fa-key"></i> Set New Password</h3>
                <p>Create a new password for your account</p>
            </div>
            
            <div class="reset-body">
                <?php
                if(isset($_GET['key']) && isset($_GET['reset'])) {
                    $email = $_GET['key'];
                    $pass = $_GET['reset'];
                    
                    $idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
                    if (mysqli_connect_errno()) {
                        echo '<div class="result-message error">';
                        echo '<i class="fas fa-exclamation-triangle"></i> Failed to connect to MySQL: ' . mysqli_connect_error();
                        echo '</div>';
                        exit();
                    }
                    
                    $select = mysqli_query($idr, "SELECT eemail, password FROM form_element WHERE eemail='$email' AND md5(password)='$pass'");
                    
                    if(mysqli_num_rows($select) == 1) {
                        echo '<div class="result-message info">';
                        echo '<i class="fas fa-check-circle"></i> Please enter your new password below.';
                        echo '</div>';
                        ?>
                        
                        <form method="post" action="submit_new.php">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" name="password" class="form-control" id="password" 
                                           placeholder="Enter new password" required minlength="6">
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
                                           placeholder="Confirm new password" required minlength="6">
                                </div>
                            </div>
                            
                            <button type="submit" name="submit_password" class="btn-reset">
                                <i class="fas fa-save"></i> Update Password
                            </button>
                        </form>
                        
                        <?php
                    } else {
                        echo '<div class="result-message error">';
                        echo '<i class="fas fa-exclamation-triangle"></i> Invalid or expired reset link.';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="result-message error">';
                    echo '<i class="fas fa-exclamation-triangle"></i> Invalid request. Please use the link from your email.';
                    echo '</div>';
                }
                ?>
                
                <div class="action-buttons">
                    <a href="login200.php" class="btn-login">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.style.borderColor = '#ff3860';
            } else {
                this.style.borderColor = '#4caf50';
            }
        });
    </script>
</body>
</html>