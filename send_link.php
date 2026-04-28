<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset | NCC System</title>
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
        
        .reset-link {
            display: block;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 10px;
            margin: 25px 0;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.25);
            position: relative;
            overflow: hidden;
        }
        
        .reset-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(25, 118, 210, 0.35);
        }
        
        .reset-link:active {
            transform: translateY(1px);
        }
        
        .reset-link i {
            margin-right: 10px;
        }
        
        .reset-link::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            pointer-events: none;
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
            margin-top: 15px;
        }
        
        .btn-login:hover {
            background-color: rgba(25, 118, 210, 0.1);
            text-decoration: none;
            color: var(--primary-dark);
        }
        
        .email-demo {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid var(--accent);
        }
        
        .email-demo h4 {
            color: var(--dark-text);
            margin-bottom: 10px;
        }
        
        .email-demo p {
            margin-bottom: 5px;
            color: #666;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }
        
        .instructions {
            text-align: center;
            margin-bottom: 20px;
            color: #666;
        }
        
        @media (max-width: 576px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-login {
                width: 100%;
                text-align: center;
            }
            
            .reset-link {
                padding: 14px 20px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-card">
            <div class="reset-header">
                <h3><i class="fas fa-key"></i> Password Reset</h3>
                <p>Reset your account password</p>
            </div>
            
            <div class="reset-body">
                <?php
                if(isset($_POST['email'])) {
                    $idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
                    if (mysqli_connect_errno()) {
                        echo '<div class="result-message error">';
                        echo '<i class="fas fa-exclamation-triangle"></i> Failed to connect to MySQL: ' . mysqli_connect_error();
                        echo '</div>';
                        exit();
                    }
                    
                    $email = $_POST['email'];
                    $select = mysqli_query($idr, "SELECT name, eemail, password FROM form_element WHERE eemail='$email'");
                    
                    if(mysqli_num_rows($select) == 1) {
                        while($row = mysqli_fetch_assoc($select)) {
                            $name = $row['name'];
                            $email = $row['eemail'];
                            $pass = md5($row['password']);
                        }
                        
                        echo '<div class="result-message success">';
                        echo '<i class="fas fa-check-circle"></i> Account found! Click the link below to reset your password.';
                        echo '</div>';
                        
                        echo '<div class="instructions">';
                        echo 'Click the button below to reset your password for:';
                        echo '<div style="font-weight: bold; margin: 10px 0; color: var(--primary);">' . $email . '</div>';
                        echo '</div>';
                        
                        // Styled reset link
                        echo '<a href="reset2_pass.php?key=' . $email . '&reset=' . $pass . '" class="reset-link">';
                        echo '<i class="fas fa-lock"></i> Click To Reset Password';
                        echo '</a>';
                        
                       
                        
                    } else {
                        echo '<div class="result-message error">';
                        echo '<i class="fas fa-exclamation-triangle"></i> No account found with that email address.';
                        echo '</div>';
                        
                        echo '<div class="action-buttons">';
                        echo '<a href="reset_pass.php" class="btn-reset">';
                        echo '<i class="fas fa-redo"></i> Try Again';
                        echo '</a>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="result-message error">';
                    echo '<i class="fas fa-exclamation-triangle"></i> Invalid request. Please try again.';
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
        // Function to simulate the login button action
        function subm2() {
            window.location.href = 'login200.php';
        }
    </script>
</body>
</html>