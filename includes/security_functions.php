<?php
/**
 * Enhanced Security Functions for VVUSRC System
 * Comprehensive security features including login attempts, session management, 2FA, etc.
 */

require_once 'db_config.php';

/**
 * Get security setting value
 */
function getSecuritySetting($key, $default = null) {
    global $conn;
    
    $sql = "SELECT setting_value, setting_type FROM security_settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $value = $row['setting_value'];
        $type = $row['setting_type'];
        
        // Convert value based on type
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
    
    return $default;
}

/**
 * Update security setting
 */
function updateSecuritySetting($key, $value, $user_id = null) {
    global $conn;
    
    $sql = "UPDATE security_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sis", $value, $user_id, $key);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Log security event
 */
function logSecurityEvent($user_id, $event_type, $description, $severity = 'medium') {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $sql = "INSERT INTO security_logs (user_id, event_type, description, ip_address, user_agent, severity) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isssss", $user_id, $event_type, $description, $ip_address, $user_agent, $severity);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Record login attempt
 */
function recordLoginAttempt($email, $success = false, $failure_reason = null) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $sql = "INSERT INTO login_attempts (email, ip_address, user_agent, success, failure_reason) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssbs", $email, $ip_address, $user_agent, $success, $failure_reason);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Check if account is locked
 */
function isAccountLocked($email) {
    global $conn;
    
    $sql = "SELECT lockout_id, unlock_at FROM account_lockouts 
            WHERE email = ? AND is_active = TRUE 
            AND (unlock_at IS NULL OR unlock_at > NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) > 0;
}

/**
 * Lock account due to failed attempts
 */
function lockAccount($email, $user_id = null, $reason = 'failed_attempts') {
    global $conn;
    
    $lockout_duration = getSecuritySetting('account_lockout_duration', 30);
    $unlock_at = date('Y-m-d H:i:s', strtotime("+{$lockout_duration} minutes"));
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $sql = "INSERT INTO account_lockouts (user_id, email, ip_address, lockout_reason, unlock_at) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issss", $user_id, $email, $ip_address, $reason, $unlock_at);
    
    if (mysqli_stmt_execute($stmt)) {
        logSecurityEvent($user_id, 'account_locked', "Account locked for email: {$email}. Reason: {$reason}", 'high');
        return true;
    }
    
    return false;
}

/**
 * Check failed login attempts
 */
function checkFailedAttempts($email) {
    global $conn;
    
    $max_attempts = getSecuritySetting('max_login_attempts', 5);
    $time_window = 15; // minutes
    
    $sql = "SELECT COUNT(*) as attempt_count FROM login_attempts 
            WHERE email = ? AND success = FALSE 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $email, $time_window);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    $attempt_count = $row['attempt_count'];
    
    if ($attempt_count >= $max_attempts) {
        // Get user_id if exists
        $user_sql = "SELECT user_id FROM users WHERE email = ?";
        $user_stmt = mysqli_prepare($conn, $user_sql);
        mysqli_stmt_bind_param($user_stmt, "s", $email);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $user_row = mysqli_fetch_assoc($user_result);
        $user_id = $user_row ? $user_row['user_id'] : null;
        
        lockAccount($email, $user_id);
        return true;
    }
    
    return false;
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    $min_length = getSecuritySetting('password_min_length', 8);
    $require_uppercase = getSecuritySetting('password_require_uppercase', true);
    $require_lowercase = getSecuritySetting('password_require_lowercase', true);
    $require_numbers = getSecuritySetting('password_require_numbers', true);
    $require_symbols = getSecuritySetting('password_require_symbols', false);
    
    if (strlen($password) < $min_length) {
        $errors[] = "Password must be at least {$min_length} characters long";
    }
    
    if ($require_uppercase && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if ($require_lowercase && !preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if ($require_numbers && !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if ($require_symbols && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

/**
 * Check password history
 */
function isPasswordInHistory($user_id, $password) {
    global $conn;
    
    $history_count = getSecuritySetting('password_history_count', 5);
    
    $sql = "SELECT password_hash FROM password_history 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $history_count);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password_hash'])) {
            return true;
        }
    }
    
    return false;
}

/**
 * Add password to history
 */
function addPasswordToHistory($user_id, $password_hash) {
    global $conn;
    
    $sql = "INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $password_hash);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Clean old password history
 */
function cleanPasswordHistory($user_id) {
    global $conn;
    
    $history_count = getSecuritySetting('password_history_count', 5);
    
    $sql = "DELETE FROM password_history 
            WHERE user_id = ? 
            AND history_id NOT IN (
                SELECT history_id FROM (
                    SELECT history_id FROM password_history 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ) as recent_passwords
            )";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $history_count);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Create user session
 */
function createUserSession($user_id) {
    global $conn;

    $session_id = session_id();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $timeout_minutes = getSecuritySetting('session_timeout_minutes', 30);
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$timeout_minutes} minutes"));
    
    // Check for maximum concurrent sessions
    $max_sessions = getSecuritySetting('max_concurrent_sessions', 3);
    $count_sql = "SELECT COUNT(*) as session_count FROM user_sessions WHERE user_id = ? AND is_active = TRUE";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "i", $user_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    
    if ($count_row['session_count'] >= $max_sessions) {
        // Remove oldest session
        $remove_sql = "UPDATE user_sessions SET is_active = FALSE 
                       WHERE user_id = ? AND is_active = TRUE 
                       ORDER BY created_at ASC LIMIT 1";
        $remove_stmt = mysqli_prepare($conn, $remove_sql);
        mysqli_stmt_bind_param($remove_stmt, "i", $user_id);
        mysqli_stmt_execute($remove_stmt);
    }
    
    $sql = "INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            last_activity = NOW(), expires_at = ?, is_active = TRUE";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sissss", $session_id, $user_id, $ip_address, $user_agent, $expires_at, $expires_at);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Validate session
 */
function validateSession($session_id) {
    global $conn;
    
    $sql = "SELECT user_id FROM user_sessions
            WHERE session_id = ? AND is_active = TRUE AND expires_at > NOW()";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $session_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Update last activity
        $update_sql = "UPDATE user_sessions SET last_activity = NOW() WHERE session_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "s", $session_id);
        mysqli_stmt_execute($update_stmt);
        
        return $row['user_id'];
    }
    
    return false;
}

/**
 * Destroy user session
 */
function destroyUserSession($session_id) {
    global $conn;
    
    $sql = "UPDATE user_sessions SET is_active = FALSE WHERE session_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $session_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Clean expired sessions
 */
function cleanExpiredSessions() {
    global $conn;
    
    $sql = "UPDATE user_sessions SET is_active = FALSE WHERE expires_at <= NOW()";
    return mysqli_query($conn, $sql);
}

/**
 * Check if IP is allowed
 */
function isIPAllowed($ip_address) {
    global $conn;
    
    $enable_whitelist = getSecuritySetting('enable_ip_whitelist', false);
    
    if (!$enable_whitelist) {
        // Check blacklist
        $sql = "SELECT control_id FROM ip_access_control 
                WHERE access_type = 'blacklist' AND is_active = TRUE 
                AND (expires_at IS NULL OR expires_at > NOW())
                AND (ip_address = ? OR ? LIKE CONCAT(ip_range, '%'))";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $ip_address, $ip_address);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_num_rows($result) == 0; // Allow if not in blacklist
    } else {
        // Check whitelist
        $sql = "SELECT control_id FROM ip_access_control 
                WHERE access_type = 'whitelist' AND is_active = TRUE 
                AND (expires_at IS NULL OR expires_at > NOW())
                AND (ip_address = ? OR ? LIKE CONCAT(ip_range, '%'))";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $ip_address, $ip_address);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_num_rows($result) > 0; // Allow only if in whitelist
    }
}

/**
 * Generate 2FA secret
 */
function generate2FASecret() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < 32; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $secret;
}

/**
 * Generate backup codes for 2FA
 */
function generate2FABackupCodes($count = 10) {
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $codes[] = strtoupper(bin2hex(random_bytes(4)));
    }
    return $codes;
}
?>
