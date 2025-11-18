<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$success_message = '';
$error_message = '';

// Create admin_reset_requests table if it doesn't exist
try {
    $createTableSql = "CREATE TABLE IF NOT EXISTS admin_reset_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        username VARCHAR(100),
        phone VARCHAR(20),
        reason TEXT,
        status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
        admin_notes TEXT,
        processed_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_status (status),
        FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->query($createTableSql);
} catch (Exception $e) {
    error_log("Error creating admin_reset_requests table: " . $e->getMessage());
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    
    // Validate inputs
    if (empty($email) || empty($username) || empty($reason)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if user exists
        $user = fetchOne("SELECT user_id, username, email FROM users WHERE email = ? OR username = ?", [$email, $username]);
        
        if (!$user) {
            $error_message = "No user found with the provided email or username.";
        } else {
            // Check if there's already a pending request for this user
            $existingRequest = fetchOne("SELECT id FROM admin_reset_requests WHERE email = ? AND status = 'pending'", [$email]);
            
            if ($existingRequest) {
                $error_message = "You already have a pending password reset request. Please wait for admin approval.";
            } else {
                // Insert the request
                $insertSql = "INSERT INTO admin_reset_requests (email, username, phone, reason) VALUES (?, ?, ?, ?)";
                $result = insert($insertSql, [$email, $username, $phone, $reason]);
                
                if ($result) {
                    $requestId = $result;
                    $success_message = "Your password reset request has been submitted successfully!<br><br>";
                    $success_message .= "<strong>Request Details:</strong><br>";
                    $success_message .= "Request ID: <code>#" . str_pad($requestId, 6, '0', STR_PAD_LEFT) . "</code><br>";
                    $success_message .= "Email: " . htmlspecialchars($email) . "<br>";
                    $success_message .= "Status: <span class='badge bg-warning'>Pending Admin Approval</span><br><br>";
                    $success_message .= "<strong>Next Steps:</strong><br>";
                    $success_message .= "• An administrator will review your request<br>";
                    $success_message .= "• You will be contacted via the provided information<br>";
                    $success_message .= "• Keep your Request ID for reference<br>";
                    
                    // Log the request
                    error_log("Admin reset request submitted: ID #$requestId, Email: $email, Username: $username");
                } else {
                    $error_message = "Error submitting request. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Admin for Password Reset - SRC Management System</title>
    
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
        
        .contact-admin-card {
            max-width: 600px;
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
        
        .info-box {
            background-color: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        

    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg contact-admin-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-shield text-primary" style="font-size: 3rem;"></i>
                            <h2 class="mt-3 mb-2">Contact Administrator</h2>
                            <p class="text-muted">Request password reset assistance from an administrator</p>
                        </div>
                        
                        <div class="info-box">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-info-circle me-2"></i>How Admin Reset Works:
                            </h6>
                            <ul class="mb-0">
                                <li>Submit your password reset request with details</li>
                                <li>An administrator will verify your identity</li>
                                <li>Admin will reset your password or provide instructions</li>
                                <li>You'll be contacted via the information provided</li>
                            </ul>
                        </div>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success_message; ?>
                            </div>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-primary me-2">
                                    <i class="fas fa-sign-in-alt me-2"></i> Go to Login
                                </a>
                                <a href="forgot_password.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i> Other Reset Options
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label d-flex align-items-center">
                                            <i class="fas fa-envelope me-2"></i> Email Address <span class="text-danger">*</span>
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
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label d-flex align-items-center">
                                            <i class="fas fa-user me-2"></i> Username <span class="text-danger">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="username" 
                                            name="username"
                                            placeholder="Enter your username" 
                                            required
                                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                        >
                                        <div class="invalid-feedback">
                                            Please enter your username.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label d-flex align-items-center">
                                        <i class="fas fa-phone me-2"></i> Phone Number
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="phone" 
                                        name="phone"
                                        placeholder="Enter your phone number (optional)" 
                                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                    >
                                    <div class="form-text">Providing your phone number helps with verification</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="reason" class="form-label d-flex align-items-center">
                                        <i class="fas fa-comment me-2"></i> Reason for Reset Request <span class="text-danger">*</span>
                                    </label>
                                    <textarea 
                                        class="form-control" 
                                        id="reason" 
                                        name="reason"
                                        rows="4"
                                        placeholder="Please explain why you need a password reset (e.g., forgot password, account locked, etc.)"
                                        required
                                    ><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
                                    <div class="invalid-feedback">
                                        Please provide a reason for your reset request.
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i> Submit Reset Request
                                    </button>
                                </div>
                            </form>
                            

                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="forgot_password.php" class="text-primary me-3">
                                <i class="fas fa-arrow-left me-1"></i> Other Reset Options
                            </a>
                            <a href="login.php" class="text-primary">
                                <i class="fas fa-sign-in-alt me-1"></i> Back to Login
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
            
            var forms = document.querySelectorAll('.needs-validation')
            
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
