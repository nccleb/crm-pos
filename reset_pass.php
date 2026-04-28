
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | NCC System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* Same styles as login200.php */
        :root {
            --primary: #1976D2;
            --primary-dark: #0D47A1;
            --accent: #00C853;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --error: #ff3860;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .reset-container {
            width: 100%;
            max-width: 420px;
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
        
        /* Include all the form styling from login200.php */
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
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 18px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <h3><i class="fas fa-key"></i> Reset Password</h3>
                <p>Enter your email to reset your password</p>
            </div>
            
            <div class="reset-body">
                <form method="post" action="send_link.php">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" class="form-control" id="email" 
                                   placeholder="Enter your registered email" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-reset">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="login200.php" style="color: var(--primary); text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
            