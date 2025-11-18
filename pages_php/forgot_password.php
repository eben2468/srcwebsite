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
                
                // Since email server is not configured, provide alternative reset methods
                $success_message = "Password reset request created successfully!<br><br>";
                $success_message .= "<strong>Reset Options:</strong><br>";
                $success_message .= "1. <strong>Direct Reset Link:</strong><br>";
                $success_message .= "<a href='" . htmlspecialchars($resetLink) . "' class='btn btn-primary btn-sm mt-2'>Reset Password Now</a><br><br>";
                $success_message .= "2. <strong>Contact Administrator:</strong><br>";
                $success_message .= "Reset code: <code>" . substr($token, 0, 8) . "</code> | ";
                $success_message .= "<a href='contact_admin_reset.php' class='btn btn-outline-warning btn-sm'>Submit Admin Request</a><br><br>";
                $success_message .= "3. <strong>Security Questions:</strong><br>";
                $success_message .= "<a href='security_reset.php?email=" . urlencode($email) . "' class='btn btn-outline-primary btn-sm'>Answer Security Questions</a><br><br>";
                $success_message .= "4. <strong>Direct Reset:</strong><br>";
                $success_message .= "<a href='direct_reset.php' class='btn btn-outline-success btn-sm'>Direct Password Reset</a><br><br>";
                $success_message .= "<small class='text-muted'>This link will expire in 1 hour for security.</small>";

                // Log the reset request for admin reference
                error_log("Password reset requested for: $email - Token: " . substr($token, 0, 8));
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
                            <h2 class="fw-bold">Password Reset Options</h2>
                            <p class="text-muted">Choose an alternative method to reset your password</p>
                        </div>
                        
                        <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success persistent-alert mb-4" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger persistent-alert mb-4" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Email reset option removed for security reasons -->

                        <!-- Alternative Reset Options -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-lightbulb me-2"></i>Alternative Reset Options:
                            </h6>
                            <div class="d-grid gap-2">
                                <a href="direct_reset.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-unlock-alt me-2"></i>Direct Reset (No Email Required)
                                </a>
                                <a href="contact_admin_reset.php" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-user-shield me-2"></i>Contact Administrator
                                </a>
                            </div>
                        </div>

                        <div class="back-to-login mt-4">
                            <a href="login.php">
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

    <script>
    // Prevent auto-dismissal of persistent alerts with multiple protection layers
    document.addEventListener('DOMContentLoaded', function() {
        // Protection Layer 1: Override global dismissAllAlerts function
        const originalDismissAllAlerts = window.dismissAllAlerts;
        window.dismissAllAlerts = function() {
            // Only dismiss non-persistent alerts
            const alerts = document.querySelectorAll('.alert:not(.persistent-alert), .alert-dismissible:not(.persistent-alert), .notification:not(.persistent-alert), .toast:not(.persistent-alert)');
            alerts.forEach(alert => {
                try {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                        const bsAlert = bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
                        bsAlert.close();
                    } else {
                        const closeButton = alert.querySelector('.btn-close, .close, [data-dismiss="alert"], [data-bs-dismiss="alert"]');
                        if (closeButton) {
                            closeButton.click();
                        } else {
                            alert.style.display = 'none';
                        }
                    }
                } catch (e) {
                    console.log('Error dismissing alert:', e);
                }
            });
        };

        // Protection Layer 2: Disable Bootstrap Alert functionality on persistent alerts
        document.querySelectorAll('.persistent-alert').forEach(alert => {
            // Remove any existing Bootstrap Alert instances
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getInstance(alert);
                if (bsAlert) {
                    bsAlert.dispose();
                }
            }

            // Prevent new Bootstrap Alert instances from being created
            alert.setAttribute('data-bs-dismiss-disabled', 'true');
        });

        // Protection Layer 3: Monitor and restore persistent alerts
        const persistentAlerts = document.querySelectorAll('.persistent-alert');
        persistentAlerts.forEach(alert => {
            // Store original display and visibility
            const originalDisplay = alert.style.display || 'block';
            const originalVisibility = alert.style.visibility || 'visible';
            const originalOpacity = alert.style.opacity || '1';

            // Create a MutationObserver to watch for changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes') {
                        // Restore visibility if changed
                        if (alert.style.display === 'none') {
                            alert.style.display = originalDisplay;
                        }
                        if (alert.style.visibility === 'hidden') {
                            alert.style.visibility = originalVisibility;
                        }
                        if (alert.style.opacity === '0') {
                            alert.style.opacity = originalOpacity;
                        }
                    }
                });
            });

            // Start observing
            observer.observe(alert, {
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        });

        // Protection Layer 4: Manual close functionality
        document.querySelectorAll('.persistent-alert .btn-close').forEach(button => {
            button.addEventListener('click', function() {
                const alert = this.closest('.alert');
                if (alert) {
                    alert.style.display = 'none';
                }
            });
        });

        // Protection Layer 5: Override setTimeout and setInterval for this page
        const originalSetTimeout = window.setTimeout;
        const originalSetInterval = window.setInterval;

        window.setTimeout = function(callback, delay) {
            if (callback.toString().includes('dismissAllAlerts') || callback.toString().includes('alert')) {
                // Don't execute auto-dismiss timeouts
                return;
            }
            return originalSetTimeout.apply(this, arguments);
        };

        window.setInterval = function(callback, delay) {
            if (callback.toString().includes('dismissAllAlerts') || callback.toString().includes('alert')) {
                // Don't execute auto-dismiss intervals
                return;
            }
            return originalSetInterval.apply(this, arguments);
        };
    });
    </script>

    <style>
        /* Persistent alert styling */
        .persistent-alert {
            position: relative;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
            border: 1px solid transparent;
        }

        .persistent-alert.alert-success {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }

        .persistent-alert.alert-danger {
            background-color: #f8d7da;
            border-color: #f5c2c7;
            color: #842029;
        }

        .persistent-alert .btn-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            z-index: 2;
            padding: 0.375rem;
        }

        /* Ensure alerts are visible and not affected by auto-dismiss */
        .persistent-alert {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
    </style>
</body>
</html>
