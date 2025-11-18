<?php
// Include authentication and security functions
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/security_functions.php';

// Require admin access
requireLogin();
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'unlock_account':
        if (!isset($input['email'])) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }
        
        $email = $input['email'];
        
        // Unlock the account
        $sql = "UPDATE account_lockouts SET is_active = FALSE, unlocked_by = ?, unlock_reason = 'Admin unlock' WHERE email = ? AND is_active = TRUE";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the action
            logSecurityEvent($user_id, 'account_unlocked', "Account unlocked for email: {$email}", 'medium');
            echo json_encode(['success' => true, 'message' => 'Account unlocked successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unlock account']);
        }
        break;
        
    case 'unlock_all_accounts':
        // Unlock all accounts
        $sql = "UPDATE account_lockouts SET is_active = FALSE, unlocked_by = ?, unlock_reason = 'Admin bulk unlock' WHERE is_active = TRUE";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_affected_rows($conn);
            // Log the action
            logSecurityEvent($user_id, 'account_unlocked', "Bulk unlock of {$affected_rows} accounts", 'medium');
            echo json_encode(['success' => true, 'message' => "Unlocked {$affected_rows} accounts"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unlock accounts']);
        }
        break;
        
    case 'add_ip_control':
        if (!isset($input['ip_address']) || !isset($input['access_type'])) {
            echo json_encode(['success' => false, 'message' => 'IP address and access type are required']);
            exit;
        }
        
        $ip_address = $input['ip_address'];
        $access_type = $input['access_type'];
        $reason = $input['reason'] ?? 'Admin action';
        $expires_at = isset($input['expires_at']) ? $input['expires_at'] : null;
        
        // Validate IP address
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            echo json_encode(['success' => false, 'message' => 'Invalid IP address']);
            exit;
        }
        
        // Add IP control
        $sql = "INSERT INTO ip_access_control (ip_address, access_type, reason, created_by, expires_at) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssis", $ip_address, $access_type, $reason, $user_id, $expires_at);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the action
            logSecurityEvent($user_id, 'permission_change', "Added IP {$access_type} for {$ip_address}", 'medium');
            echo json_encode(['success' => true, 'message' => 'IP control added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add IP control']);
        }
        break;
        
    case 'remove_ip_control':
        if (!isset($input['control_id'])) {
            echo json_encode(['success' => false, 'message' => 'Control ID is required']);
            exit;
        }
        
        $control_id = $input['control_id'];
        
        // Get IP info before deletion
        $ip_info = fetchOne("SELECT ip_address, access_type FROM ip_access_control WHERE control_id = ?", [$control_id]);
        
        // Remove IP control
        $sql = "UPDATE ip_access_control SET is_active = FALSE WHERE control_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $control_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the action
            if ($ip_info) {
                logSecurityEvent($user_id, 'permission_change', "Removed IP {$ip_info['access_type']} for {$ip_info['ip_address']}", 'medium');
            }
            echo json_encode(['success' => true, 'message' => 'IP control removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove IP control']);
        }
        break;
        
    case 'force_logout_user':
        if (!isset($input['target_user_id'])) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        $target_user_id = $input['target_user_id'];
        
        // Force logout by deactivating all sessions
        $sql = "UPDATE user_sessions SET is_active = FALSE WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $target_user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Get user info
            $user_info = fetchOne("SELECT first_name, last_name, email FROM users WHERE user_id = ?", [$target_user_id]);
            $user_name = $user_info ? $user_info['first_name'] . ' ' . $user_info['last_name'] : 'Unknown';
            
            // Log the action
            logSecurityEvent($user_id, 'permission_change', "Forced logout for user: {$user_name}", 'medium');
            echo json_encode(['success' => true, 'message' => 'User logged out successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to logout user']);
        }
        break;
        
    case 'reset_failed_attempts':
        if (!isset($input['email'])) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }
        
        $email = $input['email'];
        
        // Reset failed attempts by removing recent failed login attempts
        $sql = "DELETE FROM login_attempts WHERE email = ? AND success = FALSE AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the action
            logSecurityEvent($user_id, 'permission_change', "Reset failed login attempts for: {$email}", 'medium');
            echo json_encode(['success' => true, 'message' => 'Failed attempts reset successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reset attempts']);
        }
        break;
        
    case 'clean_expired_sessions':
        // Clean expired sessions
        if (cleanExpiredSessions()) {
            // Log the action
            logSecurityEvent($user_id, 'permission_change', "Cleaned expired sessions", 'low');
            echo json_encode(['success' => true, 'message' => 'Expired sessions cleaned successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to clean expired sessions']);
        }
        break;
        
    case 'export_security_logs':
        // Export security logs (last 30 days)
        $logs = fetchAll("
            SELECT sl.created_at, sl.event_type, sl.description, sl.ip_address, sl.severity,
                   u.first_name, u.last_name, u.email
            FROM security_logs sl
            LEFT JOIN users u ON sl.user_id = u.user_id
            WHERE sl.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY sl.created_at DESC
        ");
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="security_logs_' . date('Y-m-d') . '.csv"');
        
        // Output CSV
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Time', 'User', 'Event Type', 'Description', 'IP Address', 'Severity']);
        
        foreach ($logs as $log) {
            $user_name = $log['first_name'] ? $log['first_name'] . ' ' . $log['last_name'] : 'System';
            fputcsv($output, [
                date('Y-m-d', strtotime($log['created_at'])),
                date('H:i:s', strtotime($log['created_at'])),
                $user_name,
                $log['event_type'],
                $log['description'],
                $log['ip_address'],
                $log['severity']
            ]);
        }
        
        fclose($output);
        
        // Log the export action
        logSecurityEvent($user_id, 'permission_change', "Exported security logs", 'low');
        exit;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}
?>
