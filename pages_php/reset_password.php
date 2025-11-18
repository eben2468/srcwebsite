<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$token = $_GET['token'] ?? '';
$success_message = '';
$error_message = '';
$token_valid = false;
$user_id = null;

// Validate token
if (!empty($token)) {
    // Check if token exists and is valid
    $sql = "SELECT * FROM password_reset_tokens 
            WHERE token = ? 
            AND expires_at > NOW() 
            AND used = 0 
            LIMIT 1";
    $token_data = fetchOne($sql, [$token]);
    
    if ($token_data) {
        $token_valid = true;
        $user_id = $token_data['user_id'];
    } else {
        $error_message = "Invalid or expired token. Please request a new password reset link.";
    }
} else {
    $error_message = "No reset token provided. Please request a password reset from the forgot password page.";
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($password)) {
        $error_message = "Please enter a password.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user's password
        $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
        $result = update($update_sql, [$hashed_password, $user_id]);
        
        if ($result) {
            // Mark token as used
            $mark_used_sql = "UPDATE password_reset_tokens SET used = 1 WHERE token = ?";
            update($mark_used_sql, [$token]);
            
            $success_message = "Your password has been reset successfully. You can now login with your new password.";
        } else {
            $error_message = "An error occurred while resetting your password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SRC Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom styles -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .reset-password-card {
            max-width: 450px;
            margin: 0 auto;
            border-radius: 10px;
        }
        
        .btn-primary {
            background-color: #4668b3;
            border-color: #4668b3;
        }
        
        .btn-primary:hover {
            background-color: #3a5a96;
            border-color: #3a5a96;
        }
        
        .text-primary {
            color: #4668b3 !important;
        }
        
        .back-to-login {
            margin-top: 20px;
            text-align: center;
        }
        
        .back-to-login a {
            color: #4668b3;
            text-decoration: none;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 5px;
            margin-top: 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-weak {
            background-color: #dc3545;
            width: 25%;
        }
        
        .strength-medium {
            background-color: #ffc107;
            width: 50%;
        }
        
        .strength-strong {
            background-color: #28a745;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="reset-password-card card shadow-sm border-0 my-5">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-lock-open text-primary mb-3" style="font-size: 2.5rem;"></i>
                            <h2 class="fw-bold">Reset Password</h2>
                            <p class="text-muted">Enter your new password</p>
                        </div>
                        
                        <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success mb-4">
                            <?php echo $success_message; ?>
                            <div class="mt-3">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i> Go to Login
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger mb-4">
                            <?php echo $error_message; ?>
                            <?php if (!$token_valid): ?>
                            <div class="mt-3">
                                <a href="forgot_password.php" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i> Request New Reset Link
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($token_valid && empty($success_message)): ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?token=' . urlencode($token)); ?>" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="password" class="form-label d-flex align-items-center">
                                    <i class="fas fa-lock me-2"></i> New Password
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password"
                                    placeholder="Enter your new password" 
                                    required
                                    minlength="8"
                                >
                                <div class="password-strength mt-2" id="passwordStrength"></div>
                                <small class="form-text text-muted">Password must be at least 8 characters long</small>
                                <div class="invalid-feedback">
                                    Please enter a password with at least 8 characters.
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label d-flex align-items-center">
                                    <i class="fas fa-lock me-2"></i> Confirm Password
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="confirm_password" 
                                    name="confirm_password"
                                    placeholder="Confirm your new password" 
                                    required
                                >
                                <div class="invalid-feedback">
                                    Please confirm your password.
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i> Reset Password
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                        
                        <?php if (empty($success_message)): ?>
                        <div class="back-to-login mt-4">
                            <a href="login.php">
                                <i class="fas fa-arrow-left me-2"></i> Back to Login
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all forms to apply validation styles
            var forms = document.querySelectorAll('.needs-validation')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        // Check if passwords match
                        const password = form.querySelector('#password')
                        const confirmPassword = form.querySelector('#confirm_password')
                        
                        if (password && confirmPassword && password.value !== confirmPassword.value) {
                            event.preventDefault()
                            confirmPassword.setCustomValidity('Passwords do not match')
                        } else if (confirmPassword) {
                            confirmPassword.setCustomValidity('')
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
                
            // Password strength indicator
            const passwordInput = document.getElementById('password')
            const strengthIndicator = document.getElementById('passwordStrength')
            
            if (passwordInput && strengthIndicator) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value
                    let strength = 0
                    
                    if (password.length >= 8) strength += 1
                    if (password.match(/[A-Z]/)) strength += 1
                    if (password.match(/[0-9]/)) strength += 1
                    if (password.match(/[^A-Za-z0-9]/)) strength += 1
                    
                    strengthIndicator.className = 'password-strength'
                    
                    if (password.length === 0) {
                        strengthIndicator.style.width = '0%'
                    } else if (strength <= 2) {
                        strengthIndicator.classList.add('strength-weak')
                    } else if (strength === 3) {
                        strengthIndicator.classList.add('strength-medium')
                    } else {
                        strengthIndicator.classList.add('strength-strong')
                    }
                })
            }
        })()
    </script>
</body>
</html> 
