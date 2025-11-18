<?php
// Include authentication and security functions
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/security_functions.php';

// Require login
requireLogin();

$user_id = $_SESSION['user_id'];
$force_change = isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'];
$password_expired = isset($_SESSION['password_expired']) && $_SESSION['password_expired'];

// Process password change
$success_message = '';
$error_messages = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($current_password)) {
        $error_messages[] = "Current password is required";
    }
    
    if (empty($new_password)) {
        $error_messages[] = "New password is required";
    }
    
    if ($new_password !== $confirm_password) {
        $error_messages[] = "New passwords do not match";
    }
    
    if (empty($error_messages)) {
        // Get current user data
        $user = fetchOne("SELECT password FROM users WHERE user_id = ?", [$user_id]);
        
        if (!$user || !password_verify($current_password, $user['password'])) {
            $error_messages[] = "Current password is incorrect";
        } else {
            // Validate new password strength
            $strength_errors = validatePasswordStrength($new_password);
            if (!empty($strength_errors)) {
                $error_messages = array_merge($error_messages, $strength_errors);
            }
            
            // Check password history
            if (isPasswordInHistory($user_id, $new_password)) {
                $history_count = getSecuritySetting('password_history_count', 5);
                $error_messages[] = "Password has been used recently. Please choose a different password (last {$history_count} passwords are remembered)";
            }
            
            if (empty($error_messages)) {
                // Hash new password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($stmt, "si", $new_password_hash, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Add to password history
                    addPasswordToHistory($user_id, $new_password_hash);
                    
                    // Clean old password history
                    cleanPasswordHistory($user_id);
                    
                    // Log password change
                    logSecurityEvent($user_id, 'password_change', 'Password changed successfully', 'medium');
                    
                    // Clear force change flags
                    unset($_SESSION['force_password_change']);
                    unset($_SESSION['password_expired']);
                    
                    $success_message = "Password changed successfully!";
                    
                    // Redirect after 2 seconds
                    header("refresh:2;url=dashboard.php");
                } else {
                    $error_messages[] = "Failed to update password. Please try again.";
                }
            }
        }
    }
}

// Get password requirements for display
$min_length = getSecuritySetting('password_min_length', 8);
$require_uppercase = getSecuritySetting('password_require_uppercase', true);
$require_lowercase = getSecuritySetting('password_require_lowercase', true);
$require_numbers = getSecuritySetting('password_require_numbers', true);
$require_symbols = getSecuritySetting('password_require_symbols', false);

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-key me-2"></i>
                        <?php if ($force_change): ?>
                            Password Change Required
                        <?php elseif ($password_expired): ?>
                            Password Expired
                        <?php else: ?>
                            Change Password
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($force_change): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            You are required to change your password before continuing.
                        </div>
                    <?php elseif ($password_expired): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-clock me-2"></i>
                            Your password has expired and must be changed.
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                            <br><small>Redirecting to dashboard...</small>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_messages)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <ul class="mb-0">
                                <?php foreach ($error_messages as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Password Requirements -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Password Requirements:</h6>
                        <ul class="mb-0 small">
                            <li>At least <?php echo $min_length; ?> characters long</li>
                            <?php if ($require_uppercase): ?>
                                <li>At least one uppercase letter (A-Z)</li>
                            <?php endif; ?>
                            <?php if ($require_lowercase): ?>
                                <li>At least one lowercase letter (a-z)</li>
                            <?php endif; ?>
                            <?php if ($require_numbers): ?>
                                <li>At least one number (0-9)</li>
                            <?php endif; ?>
                            <?php if ($require_symbols): ?>
                                <li>At least one special character (!@#$%^&*)</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="strength-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="strength-text" class="text-muted">Password strength will appear here</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Change Password
                            </button>
                            <?php if (!$force_change && !$password_expired): ?>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    
    let score = 0;
    let feedback = [];
    
    // Length check
    if (password.length >= <?php echo $min_length; ?>) {
        score += 20;
    } else {
        feedback.push('At least <?php echo $min_length; ?> characters');
    }
    
    // Uppercase check
    <?php if ($require_uppercase): ?>
    if (/[A-Z]/.test(password)) {
        score += 20;
    } else {
        feedback.push('Uppercase letter');
    }
    <?php endif; ?>
    
    // Lowercase check
    <?php if ($require_lowercase): ?>
    if (/[a-z]/.test(password)) {
        score += 20;
    } else {
        feedback.push('Lowercase letter');
    }
    <?php endif; ?>
    
    // Number check
    <?php if ($require_numbers): ?>
    if (/[0-9]/.test(password)) {
        score += 20;
    } else {
        feedback.push('Number');
    }
    <?php endif; ?>
    
    // Symbol check
    <?php if ($require_symbols): ?>
    if (/[^A-Za-z0-9]/.test(password)) {
        score += 20;
    } else {
        feedback.push('Special character');
    }
    <?php endif; ?>
    
    // Update progress bar
    strengthBar.style.width = score + '%';
    
    if (score < 40) {
        strengthBar.className = 'progress-bar bg-danger';
        strengthText.textContent = 'Weak - Missing: ' + feedback.join(', ');
        strengthText.className = 'text-danger small';
    } else if (score < 80) {
        strengthBar.className = 'progress-bar bg-warning';
        strengthText.textContent = 'Fair - Missing: ' + feedback.join(', ');
        strengthText.className = 'text-warning small';
    } else if (score < 100) {
        strengthBar.className = 'progress-bar bg-info';
        strengthText.textContent = 'Good - Missing: ' + feedback.join(', ');
        strengthText.className = 'text-info small';
    } else {
        strengthBar.className = 'progress-bar bg-success';
        strengthText.textContent = 'Strong password';
        strengthText.className = 'text-success small';
    }
});

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
