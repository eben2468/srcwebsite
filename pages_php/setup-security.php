<?php
// Include authentication
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';

// Require admin access
requireLogin();
if (!isAdmin()) {
    header("Location: dashboard.php");
    exit;
}

$setup_complete = false;
$messages = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['setup_security'])) {
    // SQL statements to create security tables
    $sql_statements = [
        // Login Attempts Table
        "CREATE TABLE IF NOT EXISTS login_attempts (
            attempt_id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success BOOLEAN DEFAULT FALSE,
            failure_reason VARCHAR(255) DEFAULT NULL,
            INDEX idx_email (email),
            INDEX idx_ip_address (ip_address),
            INDEX idx_attempt_time (attempt_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Security Logs Table
        "CREATE TABLE IF NOT EXISTS security_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            event_type ENUM('login', 'logout', 'password_change', 'account_locked', 'account_unlocked', 'permission_change', 'suspicious_activity', '2fa_enabled', '2fa_disabled', 'password_reset') NOT NULL,
            description TEXT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_event_type (event_type),
            INDEX idx_severity (severity),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // User Sessions Table
        "CREATE TABLE IF NOT EXISTS user_sessions (
            session_id VARCHAR(255) PRIMARY KEY,
            user_id INT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Password History Table
        "CREATE TABLE IF NOT EXISTS password_history (
            history_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Two Factor Authentication Table
        "CREATE TABLE IF NOT EXISTS two_factor_auth (
            tfa_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            secret_key VARCHAR(255) NOT NULL,
            backup_codes JSON,
            is_enabled BOOLEAN DEFAULT FALSE,
            last_used TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Account Lockouts Table
        "CREATE TABLE IF NOT EXISTS account_lockouts (
            lockout_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            email VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45),
            lockout_reason ENUM('failed_attempts', 'suspicious_activity', 'admin_action') NOT NULL,
            locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            unlock_at DATETIME NULL,
            is_active BOOLEAN DEFAULT TRUE,
            unlocked_by INT NULL,
            unlock_reason VARCHAR(255) NULL,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
            FOREIGN KEY (unlocked_by) REFERENCES users(user_id) ON DELETE SET NULL,
            INDEX idx_email (email),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Security Settings Table (Enhanced)
        "CREATE TABLE IF NOT EXISTS security_settings (
            setting_id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            setting_type ENUM('boolean', 'integer', 'string', 'json') DEFAULT 'string',
            description TEXT,
            is_system BOOLEAN DEFAULT FALSE,
            updated_by INT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // IP Whitelist/Blacklist Table
        "CREATE TABLE IF NOT EXISTS ip_access_control (
            control_id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            ip_range VARCHAR(50),
            access_type ENUM('whitelist', 'blacklist') NOT NULL,
            reason VARCHAR(255),
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NULL,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_ip_address (ip_address),
            INDEX idx_access_type (access_type),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    // Execute each SQL statement
    $success_count = 0;
    foreach ($sql_statements as $index => $sql) {
        if (mysqli_query($conn, $sql)) {
            $messages[] = "✅ Table " . ($index + 1) . " created successfully";
            $success_count++;
        } else {
            $messages[] = "❌ Error creating table " . ($index + 1) . ": " . mysqli_error($conn);
        }
    }

    // Insert default security settings
    $default_security_settings = [
        ['password_expiry_days', '90', 'integer', 'Number of days before password expires (0 = never)'],
        ['max_login_attempts', '5', 'integer', 'Maximum failed login attempts before account lockout'],
        ['session_timeout_minutes', '30', 'integer', 'Session timeout in minutes'],
        ['require_2fa', 'false', 'boolean', 'Require two-factor authentication for all users'],
        ['password_min_length', '8', 'integer', 'Minimum password length'],
        ['password_require_uppercase', 'true', 'boolean', 'Require uppercase letters in password'],
        ['password_require_lowercase', 'true', 'boolean', 'Require lowercase letters in password'],
        ['password_require_numbers', 'true', 'boolean', 'Require numbers in password'],
        ['password_require_symbols', 'false', 'boolean', 'Require special characters in password'],
        ['password_history_count', '5', 'integer', 'Number of previous passwords to remember'],
        ['account_lockout_duration', '30', 'integer', 'Account lockout duration in minutes'],
        ['force_password_change', 'false', 'boolean', 'Force users to change password on next login'],
        ['enable_ip_whitelist', 'false', 'boolean', 'Enable IP address whitelist'],
        ['enable_login_notifications', 'true', 'boolean', 'Send email notifications for new logins'],
        ['max_concurrent_sessions', '3', 'integer', 'Maximum concurrent sessions per user'],
        ['enable_captcha', 'false', 'boolean', 'Enable CAPTCHA for login attempts'],
        ['suspicious_activity_threshold', '10', 'integer', 'Number of failed attempts to trigger suspicious activity alert']
    ];

    foreach ($default_security_settings as $setting) {
        $insert_sql = "INSERT IGNORE INTO security_settings (setting_key, setting_value, setting_type, description, is_system) 
                       VALUES (?, ?, ?, ?, TRUE)";
        
        $stmt = mysqli_prepare($conn, $insert_sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssss", $setting[0], $setting[1], $setting[2], $setting[3]);
            
            if (mysqli_stmt_execute($stmt)) {
                $messages[] = "✅ Security setting '{$setting[0]}' inserted";
            } else {
                $messages[] = "⚠️ Security setting '{$setting[0]}' already exists or error: " . mysqli_error($conn);
            }
            
            mysqli_stmt_close($stmt);
        }
    }

    if ($success_count == count($sql_statements)) {
        $setup_complete = true;
        $messages[] = "🎉 Security system setup completed successfully!";
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security System Setup</h4>
                </div>
                <div class="card-body">
                    <?php if (!$setup_complete): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Setup Security System</h5>
                            <p>This will create all necessary security tables and configure default security settings for the VVUSRC system.</p>
                            <p><strong>Features to be installed:</strong></p>
                            <ul>
                                <li>Login attempt tracking and account lockout</li>
                                <li>Security event logging</li>
                                <li>Session management</li>
                                <li>Password history and strength requirements</li>
                                <li>Two-factor authentication support</li>
                                <li>IP access control (whitelist/blacklist)</li>
                                <li>Enhanced security settings</li>
                            </ul>
                        </div>

                        <form method="POST">
                            <div class="d-grid">
                                <button type="submit" name="setup_security" class="btn btn-primary btn-lg">
                                    <i class="fas fa-play me-2"></i>Setup Security System
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle me-2"></i>Setup Complete!</h5>
                            <p>The security system has been successfully installed and configured.</p>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="security-dashboard.php" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt me-2"></i>Go to Security Dashboard
                            </a>
                            <a href="settings.php" class="btn btn-outline-primary">
                                <i class="fas fa-cog me-2"></i>Configure Security Settings
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($messages)): ?>
                        <div class="mt-4">
                            <h6>Setup Log:</h6>
                            <div class="bg-light p-3 rounded">
                                <?php foreach ($messages as $message): ?>
                                    <div><?php echo htmlspecialchars($message); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
