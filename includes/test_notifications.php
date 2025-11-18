<?php
/**
 * Test Notifications API
 * Simple test to check if notification system is working
 */

// Set JSON header
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Simple test response
    echo json_encode([
        'success' => true,
        'message' => 'Notification API is working',
        'notifications' => [
            [
                'id' => 1,
                'title' => 'Test Notification',
                'message' => 'This is a test notification to verify the system is working.',
                'type' => 'info',
                'action_url' => null,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ],
        'unread_count' => 1
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'notifications' => [],
        'unread_count' => 0
    ]);
}
?>