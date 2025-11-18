<?php
/**
 * Feedback Notification Helper Functions
 * Handles notifications for feedback assignments and responses
 */

// Only include dependencies if not already included
if (!function_exists('getDbConnection')) {
    require_once __DIR__ . '/db_config.php';
}
if (!function_exists('fetchOne')) {
    require_once __DIR__ . '/db_functions.php';
}

/**
 * Send notification when feedback is assigned to a user
 */
if (!function_exists('notifyFeedbackAssignment')) {
function notifyFeedbackAssignment($feedbackId, $assignedUserId, $assignerUserId) {
    global $conn;
    
    try {
        // Get feedback details
        $feedbackSql = "SELECT subject, submitter_name, feedback_type FROM feedback WHERE feedback_id = ?";
        $feedback = fetchOne($feedbackSql, [$feedbackId]);
        
        // Get assigner details
        $assignerSql = "SELECT first_name, last_name, role FROM users WHERE user_id = ?";
        $assigner = fetchOne($assignerSql, [$assignerUserId]);
        
        // Get assigned user details
        $assignedUserSql = "SELECT email, first_name, last_name FROM users WHERE user_id = ?";
        $assignedUser = fetchOne($assignedUserSql, [$assignedUserId]);
        
        if (!$feedback || !$assigner || !$assignedUser) {
            return false;
        }
        
        $feedbackSubject = $feedback['subject'] ?: 'Feedback #' . $feedbackId;
        $submitterName = $feedback['submitter_name'] ?: 'Anonymous';
        $feedbackType = ucfirst($feedback['feedback_type'] ?: 'general');
        $assignerName = $assigner['first_name'] . ' ' . $assigner['last_name'];
        $assignerRole = ucfirst($assigner['role']);
        $assignedUserName = $assignedUser['first_name'] . ' ' . $assignedUser['last_name'];
        
        // Create in-app notification
        $title = "New Feedback Assignment";
        $message = "You have been assigned to handle a {$feedbackType} feedback: \"{$feedbackSubject}\" submitted by {$submitterName}. Assignment made by {$assignerName} ({$assignerRole}).";
        $action_url = "/vvusrc/pages_php/manage-feedback.php?feedback_id=" . $feedbackId;
        
        // Ensure notifications table exists
        createNotificationsTable();
        
        // Insert notification
        $notificationSql = "INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?, ?, ?, 'info', ?)";
        $notificationResult = insert($notificationSql, [$assignedUserId, $title, $message, $action_url]);
        
        // Send email notification
        $emailSent = sendFeedbackAssignmentEmail($assignedUser['email'], $assignedUserName, $feedbackSubject, $feedbackType, $submitterName, $assignerName, $assignerRole, $feedbackId);
        
        return [
            'notification_sent' => (bool)$notificationResult,
            'email_sent' => $emailSent,
            'message' => $notificationResult ? 'Notification sent successfully' : 'Failed to send notification'
        ];
        
    } catch (Exception $e) {
        error_log("Error sending feedback assignment notification: " . $e->getMessage());
        return false;
    }
}
}

/**
 * Send notification when feedback is unassigned
 */
if (!function_exists('notifyFeedbackUnassignment')) {
function notifyFeedbackUnassignment($feedbackId, $previousAssigneeId, $unassignerUserId) {
    global $conn;
    
    try {
        // Get feedback details
        $feedbackSql = "SELECT subject FROM feedback WHERE feedback_id = ?";
        $feedback = fetchOne($feedbackSql, [$feedbackId]);
        
        // Get unassigner details
        $unassignerSql = "SELECT first_name, last_name, role FROM users WHERE user_id = ?";
        $unassigner = fetchOne($unassignerSql, [$unassignerUserId]);
        
        if (!$feedback || !$unassigner) {
            return false;
        }
        
        $feedbackSubject = $feedback['subject'] ?: 'Feedback #' . $feedbackId;
        $unassignerName = $unassigner['first_name'] . ' ' . $unassigner['last_name'];
        $unassignerRole = ucfirst($unassigner['role']);
        
        // Create notification
        $title = "Feedback Unassigned";
        $message = "The feedback \"{$feedbackSubject}\" has been unassigned from you by {$unassignerName} ({$unassignerRole}).";
        $action_url = "/vvusrc/pages_php/manage-feedback.php";
        
        // Insert notification
        $notificationSql = "INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?, ?, ?, 'warning', ?)";
        $result = insert($notificationSql, [$previousAssigneeId, $title, $message, $action_url]);
        
        return (bool)$result;
        
    } catch (Exception $e) {
        error_log("Error sending feedback unassignment notification: " . $e->getMessage());
        return false;
    }
}
}

/**
 * Send email notification for feedback assignment
 */
if (!function_exists('sendFeedbackAssignmentEmail')) {
function sendFeedbackAssignmentEmail($email, $userName, $feedbackSubject, $feedbackType, $submitterName, $assignerName, $assignerRole, $feedbackId) {
    $emailSubject = "Feedback Assignment - Action Required";
    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .feedback-details { background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0; }
            .action-button { background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 15px 0; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>VVUSRC - Feedback Assignment</h2>
        </div>
        <div class='content'>
            <p>Dear {$userName},</p>
            
            <p>You have been assigned to handle a new feedback submission in the VVUSRC system.</p>
            
            <div class='feedback-details'>
                <h4>Feedback Details:</h4>
                <p><strong>Type:</strong> {$feedbackType}</p>
                <p><strong>Subject:</strong> {$feedbackSubject}</p>
                <p><strong>Submitted by:</strong> {$submitterName}</p>
                <p><strong>Assigned by:</strong> {$assignerName} ({$assignerRole})</p>
            </div>
            
            <p>Please log in to the system to review and respond to this feedback.</p>
            
            <a href='http://localhost/vvusrc/pages_php/manage-feedback.php?feedback_id={$feedbackId}' class='action-button'>View Feedback</a>
            
            <p>Thank you for your prompt attention to this matter.</p>
            
            <p>Best regards,<br>VVUSRC Management System</p>
        </div>
        <div class='footer'>
            <p>This is an automated message from the VVUSRC Management System. Please do not reply to this email.</p>
        </div>
    </body>
    </html>";
    
    // Send email using PHP's mail function
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: VVUSRC System <noreply@vvusrc.edu.gh>" . "\r\n";
    
    return mail($email, $emailSubject, $emailBody, $headers);
}
}

/**
 * Create notifications table if it doesn't exist
 */
if (!function_exists('createNotificationsTable')) {
function createNotificationsTable() {
    global $conn;
    
    $createTableSql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error', 'system', 'events') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        action_url VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at),
        INDEX idx_type (type),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    return mysqli_query($conn, $createTableSql);
}
}

/**
 * Get notifications for a user
 */
if (!function_exists('getUserNotifications')) {
function getUserNotifications($userId, $limit = 10, $offset = 0) {
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
    return fetchAll($sql, [$userId, $limit, $offset]);
}
}
?>
