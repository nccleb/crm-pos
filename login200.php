<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | NCC System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary: #1976D2;
            --primary-dark: #0D47A1;
            --accent: #00C853;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --error: #ff3860;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            color: #333;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 40px rgba(50, 50, 93, 0.15), 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        
        .login-header h3 {
            font-weight: 600;
            margin: 0;
            font-size: 24px;
        }
        
        .login-header p {
            opacity: 0.9;
            margin: 5px 0 0;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-text);
            font-size: 14px;
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
        
        .btn-login {
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
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .system-info {
            text-align: center;
            margin-top: 30px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
        }
        
        .ncc-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            color: white;
        }
        
        .ncc-brand i {
            font-size: 32px;
            margin-right: 12px;
        }
        
        .ncc-brand h1 {
            font-weight: 700;
            font-size: 28px;
            margin: 0;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-card {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .login-card {
                border-radius: 12px;
            }
            
            .login-header {
                padding: 20px;
            }
            
            .login-body {
                padding: 25px 20px;
            }
            
            .login-footer {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
        
        /* Error message styling */
        .error-message {
            color: var(--error);
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="ncc-brand">
        <i class="fas fa-headset"></i>
        <h1>NCC System</h1>
    </div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h3><i class="fas fa-lock"></i> Secure Login</h3>
                <p>Access your NCC account</p>
            </div>
            
            <div class="login-body">
                <form role="form" method="post" action="ajaxsubmit2.php" id="loginForm">
                    <div class="form-group">
                        <label for="name">Username</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="name1" class="form-control" id="name" placeholder="Enter your username" required>
                        </div>
                        <div class="error-message" id="usernameError">Please enter a valid username</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-key input-icon"></i>
                            <input type="password" name="password1" class="form-control" id="password" placeholder="Enter your password" required>
                        </div>
                        <div class="error-message" id="passwordError">Please enter your password</div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                    
                    <div class="login-footer">
                        <a href="reset_pass.php" class="forgot-password">
                            <i class="fas fa-question-circle"></i> Forgot Password?
                        </a>
                        <div>
                            <span class="text-muted">Not a member?</span>
                            <a href="test205.php" style="color: var(--primary); margin-left: 5px;">Sign Up</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="system-info">
            <p>NCC Customer Management System v1.7.0</p>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <script>
    $(document).ready(function() {
        // Form validation
        $('#loginForm').on('submit', function(e) {
            let isValid = true;
            
            // Validate username
            if ($('#name').val().trim() === '') {
                $('#usernameError').show();
                isValid = false;
            } else {
                $('#usernameError').hide();
            }
            
            // Validate password
            if ($('#password').val().trim() === '') {
                $('#passwordError').show();
                isValid = false;
            } else {
                $('#passwordError').hide();
            }
            
            if (!isValid) {
                e.preventDefault();
                
                // Add shake animation to form
                $('.login-card').css('animation', 'shake 0.5s');
                setTimeout(function() {
                    $('.login-card').css('animation', '');
                }, 500);
            }
        });
        
        // Input focus effects
        $('.form-control').focus(function() {
            $(this).parent().parent().addClass('focused');
        }).blur(function() {
            $(this).parent().parent().removeClass('focused');
        });
        
        // Add shake animation for errors
        const shakeKeyframes = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
        `;
        $('<style>').html(shakeKeyframes).appendTo('head');
    });
    </script>

    
<!-- Add this JavaScript to your existing login200.php, before closing </body> tag -->

<script>
$(document).ready(function() {
    // Check for registration success
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('registered') === '1') {
        showSuccessMessage('Registration successful! Please login with your new credentials.');
    }
    
    // Check for session expiry
    if (urlParams.get('expired') === '1') {
        showErrorMessage('Your session has expired. Please login again.');
    }
    
    // Show success message
    function showSuccessMessage(message) {
        const successAlert = `
            <div class="alert alert-success" style="
                position: fixed; 
                top: 20px; 
                right: 20px; 
                z-index: 9999;
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
                border-radius: 8px;
                padding: 15px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            ">
                <i class="fas fa-check-circle"></i> ${message}
                <button type="button" class="close" style="
                    float: right; 
                    margin-left: 10px; 
                    background: none; 
                    border: none; 
                    font-size: 18px;
                    cursor: pointer;
                " onclick="this.parentElement.remove()">×</button>
            </div>
        `;
        $('body').append(successAlert);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            $('.alert').fadeOut(300, function() { $(this).remove(); });
        }, 5000);
    }
    
    // Show error message
    function showErrorMessage(message) {
        const errorAlert = `
            <div class="alert alert-danger" style="
                position: fixed; 
                top: 20px; 
                right: 20px; 
                z-index: 9999;
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
                border-radius: 8px;
                padding: 15px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            ">
                <i class="fas fa-exclamation-triangle"></i> ${message}
                <button type="button" class="close" style="
                    float: right; 
                    margin-left: 10px; 
                    background: none; 
                    border: none; 
                    font-size: 18px;
                    cursor: pointer;
                " onclick="this.parentElement.remove()">×</button>
            </div>
        `;
        $('body').append(errorAlert);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            $('.alert').fadeOut(300, function() { $(this).remove(); });
        }, 5000);
    }
    
    // Add slide-in animation
    const slideInKeyframes = `
        @keyframes slideIn {
            from { 
                opacity: 0; 
                transform: translateX(100%); 
            }
            to { 
                opacity: 1; 
                transform: translateX(0); 
            }
        }
    `;
    $('<style>').html(slideInKeyframes).appendTo('head');
    
    // Enhanced form validation with better UX
    $('#loginForm').on('submit', function(e) {
        let isValid = true;
        
        // Clear previous errors
        $('.error-message').hide();
        $('.form-control').removeClass('error');
        
        // Validate username
        const username = $('#name').val().trim();
        if (username === '') {
            $('#usernameError').text('Please enter your username').show();
            $('#name').addClass('error');
            isValid = false;
        } else if (username.length < 3) {
            $('#usernameError').text('Username must be at least 3 characters').show();
            $('#name').addClass('error');
            isValid = false;
        }
        
        // Validate password
        const password = $('#password').val();
        if (password === '') {
            $('#passwordError').text('Please enter your password').show();
            $('#password').addClass('error');
            isValid = false;
        } else if (password.length < 4) {
            $('#passwordError').text('Password must be at least 4 characters').show();
            $('#password').addClass('error');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            
            // Shake animation for errors
            $('.login-card').addClass('shake-animation');
            setTimeout(() => {
                $('.login-card').removeClass('shake-animation');
            }, 600);
        } else {
            // Show loading state
            const $submitBtn = $(this).find('button[type="submit"]');
            const originalText = $submitBtn.html();
            $submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Signing In...').prop('disabled', true);
            
            // Re-enable if form submission fails
            setTimeout(() => {
                $submitBtn.html(originalText).prop('disabled', false);
            }, 5000);
        }
    });
    
    // Add shake animation CSS
    const shakeKeyframes = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }
        .shake-animation {
            animation: shake 0.6s ease-in-out;
        }
    `;
    $('<style>').html(shakeKeyframes).appendTo('head');
    
    // Real-time validation feedback
    $('#name').on('input', function() {
        const $this = $(this);
        const username = $this.val().trim();
        
        if (username.length >= 3) {
            $this.removeClass('error').addClass('success');
            $('#usernameError').hide();
        } else {
            $this.removeClass('success');
        }
    });
    
    $('#password').on('input', function() {
        const $this = $(this);
        const password = $this.val();
        
        if (password.length >= 4) {
            $this.removeClass('error').addClass('success');
            $('#passwordError').hide();
        } else {
            $this.removeClass('success');
        }
    });
});
</script>

<!-- Also add these CSS classes to your login200.php styles -->
<style>
.form-control.error {
    border-color: #ff3860 !important;
    box-shadow: 0 0 0 3px rgba(255, 56, 96, 0.15) !important;
}

.form-control.success {
    border-color: #00C853 !important;
    box-shadow: 0 0 0 3px rgba(0, 200, 83, 0.15) !important;
}
</style>
            
</body>
</html>