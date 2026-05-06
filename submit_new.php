<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Updated | NCC System</title>
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
        
        .password-display {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border-left: 4px solid var(--primary);
        }
        
        .password-value {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: var(--dark-text);
            word-break: break-all;
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border-radius: 6px;
            border: 1px dashed #ccc;
        }
        
        .btn-login {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.25);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(25, 118, 210, 0.35);
        }
        
        .btn-login:active {
            transform: translateY(1px);
        }
        
        .security-note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
            font-size: 14px;
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
            .reset-body {
                padding: 20px;
            }
            
            .password-value {
                font-size: 16px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-card">
            <div class="reset-header">
                <h3><i class="fas fa-key"></i> Password Updated</h3>
                <p>Your password has been successfully reset</p>
            </div>
            
            <div class="reset-body">
                <?php
                if(isset($_POST['submit_password']) && $_POST['password']) {
                    $email = $_POST['email'];
                    $pass = $_POST['password'];
                    
                    $idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
                    if (mysqli_connect_errno()) {
                        echo '<div class="result-message error">';
                        echo '<i class="fas fa-exclamation-triangle"></i> Failed to connect to MySQL: ' . mysqli_connect_error();
                        echo '</div>';
                        exit();
                    }
                    
                    // Update the password in the database
                    $update = mysqli_query($idr, "UPDATE form_element SET password='$pass' WHERE eemail='$email'");
                    
                    if($update) {
                        echo '<div class="result-message success">';
                        echo '<i class="fas fa-check-circle"></i> Password Updated Successfully!';
                        echo '</div>';
                        
                        echo '<div class="password-display">';
                        echo '<p>The new password for</p>';
                        echo '<p class="password-value">' . htmlspecialchars($email) . '</p>';
                        echo '<p>is:</p>';
                        echo '<p class="password-value">' . htmlspecialchars($pass) . '</p>';
                        echo '</div>';
                        
                        echo '<div class="security-note">';
                        echo '<i class="fas fa-shield-alt"></i> For security reasons, please log in with your new password and consider changing it to something more memorable.';
                        echo '</div>';
                    } else {
                        echo '<div class="result-message error">';
                        echo '<i class="fas fa-exclamation-triangle"></i> Failed to update password. Error: ' . mysqli_error($idr);
                        echo '</div>';
                    }
                    
                    mysqli_close($idr);
                    
                } else {
                    echo '<div class="result-message error">';
                    echo '<i class="fas fa-exclamation-triangle"></i> Missing entry! Please try again.';
                    echo '</div>';
                }
                ?>
                
                <div class="action-buttons">
                    <a href="login200.php" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login Now
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