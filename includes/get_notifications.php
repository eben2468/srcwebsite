<?php
/**
 * Get Notifications API
 * Returns notifications for the current user in JSON format
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files with proper path handling
$includesDir = __DIR__;

// Try to include files with error handling
$requiredFiles = [
    'db_config.php',
    'db_functions.php', 
    'simple_auth.php',
    'feedback_notifications.php'
];

foreach ($requiredFiles as $file) {
    $filePath = $includesDir . '/' . $file;
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        error_log("Required file not found: $filePath");
    }
}

// Set JSON header
header('Content-Type: application/json');

try {
    // Check if required functions exist
    if (!function_exists('getCurrentUser')) {
        echo json_encode([
            'success' => false,
            'error' => 'Authentication functions not available',
            'notifications' => [],
            'unread_count' => 0
        ]);
        exit;
    }

    // Get current user
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        echo json_encode([
            'success' => false,
            'error' => 'User not authenticated',
            'notifications' => [],
            'unread_count' => 0
        ]);
        exit;
    }

    $userId = $currentUser['id'];
    
    // Ensure notifications table exists
    createNotificationsTable();
    
    // Get notifications for the user (limit to 10 most recent)
    $notifications = getUserNotifications($userId, 10, 0);
    
    // Get unread count
    $unreadCountSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $unreadResult = fetchOne($unreadCountSql, [$userId]);
    $unreadCount = $unreadResult ? (int)$unreadResult['count'] : 0;
    
    // Format notifications for display
    $formattedNotifications = [];
    if ($notifications) {
        foreach ($notifications as $notification) {
            $formattedNotifications[] = [
                'id' => $notification['id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'type' => $notification['type'],
                'action_url' => $notification['action_url'],
                'is_read' => $notification['is_read'],
                'created_at' => $notification['created_at']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $formattedNotifications,
        'unread_count' => $unreadCount
    ]);

} catch (Exception $e) {
    error_log("Error in get_notifications.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load notifications',
        'notifications' => [],
        'unread_count' => 0
    ]);
}
?>