<?php
// Initialize session
session_start();

// Include authentication functions
require_once '../auth_functions.php';
require_once '../db_config.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    
    // Validate email
    if (empty($email)) {
        $error_message = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if email exists in the database
        $sql = "SELECT user_id, username FROM users WHERE email = ? LIMIT 1";
        $user = fetchOne($sql, [$email]);
        
        if ($user) {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // Token expires in 1 hour
            
            // Store the token in the database
            $sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
            $result = insert($sql, [$user['user_id'], $token, $expires]);
            
            if ($result) {
                // Create reset link
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                
                // Send email using PHP mail function
                $to = $email;
                $subject = "Password Reset Request - SRC Management System";
                
                // Create HTML message
                $htmlMessage = "
                <html>
                <head>
                    <title>Password Reset Request</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #4668b3; color: white; padding: 10px 20px; text-align: center; }
                        .content { padding: 20px; border: 1px solid #ddd; }
                        .button { display: inline-block; background-color: #4668b3; color: white; padding: 10px 20px; 
                                  text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        .footer { font-size: 12px; color: #777; margin-top: 20px; text-align: center; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Password Reset Request</h2>
                        </div>
                        <div class='content'>
                            <p>Hello " . htmlspecialchars($user['username']) . ",</p>
                            <p>You have requested to reset your password for your SRC Management System account.</p>
                            <p>Please click the button below to reset your password:</p>
                            <p style='text-align: center;'>
                                <a href='" . $resetLink . "' class='button'>Reset Password</a>
                            </p>
                            <p>Alternatively, you can copy and paste the following link into your browser:</p>
                            <p>" . $resetLink . "</p>
                            <p>This link will expire in 1 hour.</p>
                            <p>If you did not request this password reset, please ignore this email.</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated email. Please do not reply to this message.</p>
                            <p>&copy; " . date('Y') . " SRC Management System</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Plain text version for email clients that don't support HTML
                $textMessage = "Hello " . $user['username'] . ",\n\n" .
                              "You have requested to reset your password for your SRC Management System account.\n\n" .
                              "Please click the link below or copy and paste it into your browser to reset your password:\n\n" .
                              $resetLink . "\n\n" .
                              "This link will expire in 1 hour.\n\n" .
                              "If you did not request this password reset, please ignore this email.\n\n" .
                              "SRC Management System";
                
                // Email headers
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: SRC Management System <noreply@srcmanagementsystem.com>\r\n";
                $headers .= "Reply-To: noreply@srcmanagementsystem.com\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                // Try to send the email
                $mailSent = mail($to, $subject, $htmlMessage, $headers);
                
                if ($mailSent) {
                    $success_message = "A password reset link has been sent to your email address. The link will expire in 1 hour.";
                } else {
                    // If mail sending fails, still show success but also show the link in debug mode
                    $success_message = "A password reset link has been generated. Please check your email.";
                    error_log("Failed to send password reset email to: $email");
                }
                
                // For development/testing, always display the link in debug mode
                if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
                    $success_message .= "<br><br>Debug: <a href='" . htmlspecialchars($resetLink) . "'>Reset Link</a>";
                    $success_message .= "<br><br>Note: If you didn't receive an email, you can use this link directly to reset your password.";
                    $success_message .= "<br>Alternatively, ask an administrator to generate a reset link for you.";
                }
            } else {
                $error_message = "An error occurred. Please try again later.";
            }
        } else {
            // Don't reveal that the email doesn't exist for security reasons
            $success_message = "If your email is registered, you will receive a password reset link shortly.";
        }
    }
}

// Create password_reset_tokens table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query to create table
mysqli_query($conn, $createTableSQL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SRC Management System</title>
    
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
        
        .forgot-password-card {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="forgot-password-card card shadow-sm border-0 my-5">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-key text-primary mb-3" style="font-size: 2.5rem;"></i>
                            <h2 class="fw-bold">Forgot Password</h2>
                            <p class="text-muted">Enter your email to receive a password reset link</p>
                        </div>
                        
                        <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success mb-4">
                            <?php echo $success_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger mb-4">
                            <?php echo $error_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . (isset($_GET['debug']) ? '?debug=true' : '')); ?>" novalidate>
                            <div class="mb-4">
                                <label for="email" class="form-label d-flex align-items-center">
                                    <i class="fas fa-envelope me-2"></i> Email Address
                                </label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email"
                                    placeholder="Enter your registered email" 
                                    required
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                >
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i> Send Reset Link
                                </button>
                            </div>
                        </form>
                        
                        <div class="back-to-login mt-4">
                            <a href="register.php">
                                <i class="fas fa-arrow-left me-2"></i> Back to Login
                            </a>
                        </div>
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
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html> 