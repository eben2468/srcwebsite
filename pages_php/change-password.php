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
$is_default_password = isset($_SESSION['is_default_password']) && $_SESSION['is_default_password'];

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
                    // If this was a default password change, update the is_default_password flag
                    if ($is_default_password) {
                        $update_flag_sql = "UPDATE users SET is_default_password = 0 WHERE user_id = ?";
                        $flag_stmt = mysqli_prepare($conn, $update_flag_sql);
                        mysqli_stmt_bind_param($flag_stmt, "i", $user_id);
                        mysqli_stmt_execute($flag_stmt);
                    }
                    
                    // Add to password history
                    addPasswordToHistory($user_id, $new_password_hash);
                    
                    // Clean old password history
                    cleanPasswordHistory($user_id);
                    
                    // Log password change
                    $log_message = $is_default_password ? 'Default password changed on first login' : 'Password changed successfully';
                    logSecurityEvent($user_id, 'password_change', $log_message, 'medium');
                    
                    // Clear force change flags
                    unset($_SESSION['force_password_change']);
                    unset($_SESSION['password_expired']);
                    unset($_SESSION['is_default_password']);
                    
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

<style>
/* Modern Password Change Page Styling */
.password-change-wrapper {
    min-height: calc(100vh - 150px);
    background: #ffffff;
    padding: 3rem 0;
    position: relative;
    overflow: hidden;
}

.password-change-wrapper::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: float 20s infinite ease-in-out;
}

.password-change-wrapper::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -5%;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
    animation: float 25s infinite ease-in-out reverse;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(180deg); }
}

.password-card {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 100px rgba(102, 126, 234, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.5);
    overflow: hidden;
    position: relative;
    z-index: 10;
    max-width: 600px;
    margin: 0 auto;
    animation: slideUp 0.6s ease-out;
    transition: transform 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.password-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    padding: 2.5rem 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.password-card-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)" /></svg>');
    opacity: 0.3;
}

.password-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255, 255, 255, 0.3);
    position: relative;
    z-index: 1;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(255, 255, 255, 0); }
}

.password-icon i {
    font-size: 2.5rem;
    color: white;
}

.password-card-header h1 {
    color: white;
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
    position: relative;
    z-index: 1;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.password-card-body {
    padding: 2.5rem;
}

.alert-modern {
    border-radius: 12px;
    border: none;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: start;
    gap: 1rem;
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-modern i {
    font-size: 1.5rem;
    margin-top: 2px;
}

.alert-modern.alert-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
    border-left: 4px solid #dc2626;
}

.alert-modern.alert-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    border-left: 4px solid #f59e0b;
}

.alert-modern.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border-left: 4px solid #10b981;
}

.alert-modern.alert-info {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border-left: 4px solid #3b82f6;
}

.requirements-box {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid #bae6fd;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.requirements-box h6 {
    color: #0c4a6e;
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 1rem;
}

.requirements-box ul {
    margin: 0;
    padding-left: 1.5rem;
}

.requirements-box li {
    color: #075985;
    padding: 0.3rem 0;
    font-size: 0.9rem;
    list-style: none;
}

.requirements-box li::before {
    content: '✓';
    color: #0ea5e9;
    font-weight: bold;
    margin-right: 0.5rem;
}

.form-label-modern {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-label-modern i {
    color: #667eea;
    font-size: 0.9rem;
}

.input-group-modern {
    position: relative;
    margin-bottom: 1.5rem;
}

.input-group-modern input {
    width: 100%;
    padding: 0.875rem 3rem 0.875rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.input-group-modern input::placeholder {
    color: #9ca3af;
    font-style: italic;
}

.input-group-modern input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.input-group-modern .toggle-password-btn {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #6b7280;
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 8px;
    z-index: 10;
}

.input-group-modern .toggle-password-btn:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

.password-strength-indicator {
    margin-top: 0.75rem;
}

.strength-progress {
    height: 6px;
    background: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.strength-progress-bar {
    height: 100%;
    transition: all 0.3s ease;
    border-radius: 10px;
}

.strength-text {
    font-size: 0.85rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-modern-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    border: none;
    border-radius: 12px;
    padding: 1rem 2rem;
    font-weight: 600;
    font-size: 1.05rem;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    cursor: pointer;
    width: 100%;
    margin-bottom: 0.75rem;
    position: relative;
    overflow: hidden;
}

.btn-modern-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.btn-modern-primary:hover::before {
    left: 100%;
}

.btn-modern-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
}

.btn-modern-primary:active {
    transform: translateY(0);
}

.btn-modern-secondary {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 0.875rem;
    font-weight: 600;
    font-size: 1rem;
    color: #6b7280;
    transition: all 0.3s ease;
    cursor: pointer;
    width: 100%;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-modern-secondary:hover {
    background: #f9fafb;
    border-color: #667eea;
    color: #667eea;
    transform: translateY(-1px);
}

.lock-notice {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1rem;
    text-align: center;
    border: 1px solid #fbbf24;
}

.lock-notice i {
    color: #92400e;
    margin-right: 0.5rem;
}

.lock-notice small {
    color: #92400e;
    font-weight: 500;
}

/* Smooth transitions for input focus states */
.input-group-modern input:valid {
    border-color: #10b981;
}

.input-group-modern input.is-invalid {
    border-color: #ef4444 !important;
    animation: shake 0.5s;
}

/* Enhanced alert styling */
.alert-modern ul {
    margin: 0;
    padding-left: 1.25rem;
}

.alert-modern ul li {
    margin: 0.25rem 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .password-change-wrapper {
        padding: 1.5rem 1rem;
        min-height: calc(100vh - 100px);
    }
    
    .password-card {
        border-radius: 16px;
        max-width: 100%;
    }
    
    .password-card-header {
        padding: 2rem 1.5rem;
    }
    
    .password-card-body {
        padding: 1.5rem;
    }
    
    .password-icon {
        width: 70px;
        height: 70px;
    }
    
    .password-icon i {
        font-size: 2rem;
    }
    
    .password-card-header h1 {
        font-size: 1.4rem;
    }
    
    .requirements-box {
        padding: 1.25rem;
    }
    
    .requirements-box h6 {
        font-size: 0.95rem;
    }
    
    .requirements-box li {
        font-size: 0.85rem;
    }
    
    .alert-modern {
        padding: 1rem;
        font-size: 0.9rem;
    }
    
    .btn-modern-primary {
        padding: 0.875rem 1.5rem;
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .password-change-wrapper {
        padding: 1rem 0.5rem;
    }
    
    .password-card-body {
        padding: 1.25rem;
    }
    
    .password-card-header h1 {
        font-size: 1.25rem;
    }
    
    .input-group-modern input {
        padding: 0.75rem 2.5rem 0.75rem 0.875rem;
        font-size: 0.95rem;
    }
}

/* Animation for form submission */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.shake {
    animation: shake 0.5s;
}
</style>

<div class="password-change-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="password-card">
                    <div class="password-card-header">
                        <div class="password-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <h1>
                            <?php if ($is_default_password): ?>
                                Change Default Password
                            <?php elseif ($force_change): ?>
                                Password Change Required
                            <?php elseif ($password_expired): ?>
                                Password Expired
                            <?php else: ?>
                                Change Your Password
                            <?php endif; ?>
                        </h1>
                    </div>
                    <div class="password-card-body">
                        <?php if ($is_default_password): ?>
                            <div class="alert-modern alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>
                                    <strong>Security Notice:</strong> You are currently using an automatically generated default password. 
                                    For your account security, you <strong>must change it</strong> before you can access the system.
                                </div>
                            </div>
                        <?php elseif ($force_change): ?>
                            <div class="alert-modern alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>You are required to change your password before continuing.</div>
                            </div>
                        <?php elseif ($password_expired): ?>
                            <div class="alert-modern alert-danger">
                                <i class="fas fa-clock"></i>
                                <div>Your password has expired and must be changed.</div>
                            </div>
                        <?php endif; ?>

                        <?php if ($success_message): ?>
                            <div class="alert-modern alert-success">
                                <i class="fas fa-check-circle"></i>
                                <div>
                                    <?php echo htmlspecialchars($success_message); ?>
                                    <br><small>Redirecting to dashboard...</small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error_messages)): ?>
                            <div class="alert-modern alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>
                                    <ul class="mb-0">
                                        <?php foreach ($error_messages as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Password Requirements -->
                        <div class="requirements-box">
                            <h6><i class="fas fa-shield-alt me-2"></i>Password Requirements</h6>
                            <ul>
                                <li>✓ At least <?php echo $min_length; ?> characters long</li>
                                <?php if ($require_uppercase): ?>
                                    <li>✓ At least one uppercase letter (A-Z)</li>
                                <?php endif; ?>
                                <?php if ($require_lowercase): ?>
                                    <li>✓ At least one lowercase letter (a-z)</li>
                                <?php endif; ?>
                                <?php if ($require_numbers): ?>
                                    <li>✓ At least one number (0-9)</li>
                                <?php endif; ?>
                                <?php if ($require_symbols): ?>
                                    <li>✓ At least one special character (!@#$%^&*)</li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="passwordChangeForm">
                            <div class="input-group-modern">
                                <label for="current_password" class="form-label-modern">
                                    <i class="fas fa-lock"></i>
                                    Current Password
                                </label>
                                <input type="password" id="current_password" name="current_password" required placeholder="Enter your current password">
                                <button class="toggle-password-btn" type="button" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>

                            <div class="input-group-modern">
                                <label for="new_password" class="form-label-modern">
                                    <i class="fas fa-key"></i>
                                    New Password
                                </label>
                                <input type="password" id="new_password" name="new_password" required placeholder="Enter your new password">
                                <button class="toggle-password-btn" type="button" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="password-strength-indicator">
                                    <div class="strength-progress">
                                        <div class="strength-progress-bar" id="strength-bar" style="width: 0%"></div>
                                    </div>
                                    <div class="strength-text" id="strength-text">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Password strength will appear here</span>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group-modern">
                                <label for="confirm_password" class="form-label-modern">
                                    <i class="fas fa-check-circle"></i>
                                    Confirm New Password
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter your new password">
                                <button class="toggle-password-btn" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn-modern-primary">
                                    <i class="fas fa-save me-2"></i>Change Password
                                </button>
                                <?php if (!$force_change && !$password_expired && !$is_default_password): ?>
                                    <a href="dashboard.php" class="btn-modern-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Cancel
                                    </a>
                                <?php else: ?>
                                    <div class="lock-notice">
                                        <i class="fas fa-lock"></i>
                                        <small>You must change your password to continue using the system.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
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
        strengthBar.style.background = 'linear-gradient(to right, #ef4444, #dc2626)';
        strengthText.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span style="color: #dc2626;">Weak - Missing: ' + feedback.join(', ') + '</span>';
    } else if (score < 80) {
        strengthBar.style.background = 'linear-gradient(to right, #f59e0b, #d97706)';
        strengthText.innerHTML = '<i class="fas fa-info-circle"></i><span style="color: #d97706;">Fair - Missing: ' + feedback.join(', ') + '</span>';
    } else if (score < 100) {
        strengthBar.style.background = 'linear-gradient(to right, #3b82f6, #2563eb)';
        strengthText.innerHTML = '<i class="fas fa-check-circle"></i><span style="color: #2563eb;">Good - Missing: ' + feedback.join(', ') + '</span>';
    } else {
        strengthBar.style.background = 'linear-gradient(to right, #10b981, #059669)';
        strengthText.innerHTML = '<i class="fas fa-shield-alt"></i><span style="color: #059669;">Strong password ✓</span>';
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
