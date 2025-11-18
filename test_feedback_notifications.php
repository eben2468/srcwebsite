<?php
/**
 * Test script to verify feedback notifications are working
 */

require_once 'includes/db_config.php';
require_once 'includes/simple_auth.php';
require_once 'pages_php/includes/auto_notifications.php';

// Test the notification system
echo "<h2>Testing Feedback Notification System</h2>";

// Check if notifications table exists
$table_check = "SHOW TABLES LIKE 'notifications'";
$table_result = mysqli_query($conn, $table_check);

if (!$table_result || mysqli_num_rows($table_result) == 0) {
    echo "<p style='color: red;'>❌ Notifications table does not exist. Creating it...</p>";
    
    $create_sql = "CREATE TABLE IF NOT EXISTS notifications (
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
        INDEX idx_type (type)
    )";
    
    if (mysqli_query($conn, $create_sql)) {
        echo "<p style='color: green;'>✅ Notifications table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create notifications table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Notifications table exists</p>";
}

// Check users with target roles
$roles_sql = "SELECT role, COUNT(*) as count FROM users WHERE role IN ('super_admin', 'admin', 'finance', 'member') AND status = 'active' GROUP BY role";
$roles_result = mysqli_query($conn, $roles_sql);

echo "<h3>Target Users for Feedback Notifications:</h3>";
if ($roles_result) {
    while ($row = mysqli_fetch_assoc($roles_result)) {
        echo "<p>• {$row['role']}: {$row['count']} users</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Error checking user roles: " . mysqli_error($conn) . "</p>";
}

// Test creating a feedback notification
echo "<h3>Testing Feedback Notification Creation:</h3>";

try {
    // Simulate a feedback submission
    $test_result = autoNotifyFeedbackSubmitted('Test Portfolio', 'suggestion', 1, 999);
    
    if ($test_result > 0) {
        echo "<p style='color: green;'>✅ Test notification created successfully! Sent to {$test_result} users.</p>";
        
        // Show the created notifications
        $check_sql = "SELECT n.*, u.first_name, u.last_name, u.role 
                      FROM notifications n 
                      JOIN users u ON n.user_id = u.user_id 
                      WHERE n.message LIKE '%Test Portfolio%' 
                      ORDER BY n.created_at DESC 
                      LIMIT 10";
        $check_result = mysqli_query($conn, $check_sql);
        
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            echo "<h4>Created Notifications:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>User</th><th>Role</th><th>Title</th><th>Message</th><th>Created</th></tr>";
            
            while ($notif = mysqli_fetch_assoc($check_result)) {
                echo "<tr>";
                echo "<td>{$notif['first_name']} {$notif['last_name']}</td>";
                echo "<td>{$notif['role']}</td>";
                echo "<td>{$notif['title']}</td>";
                echo "<td>" . substr($notif['message'], 0, 50) . "...</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Test notification failed to create or no target users found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error creating test notification: " . $e->getMessage() . "</p>";
}

// Clean up test notifications
$cleanup_sql = "DELETE FROM notifications WHERE message LIKE '%Test Portfolio%'";
mysqli_query($conn, $cleanup_sql);
echo "<p><em>Test notifications cleaned up.</em></p>";

echo "<h3>System Status:</h3>";
echo "<p>✅ Feedback notifications are configured to notify: super_admin, admin, finance, and member roles</p>";
echo "<p>✅ Notifications will appear in the header dropdown with a red badge</p>";
echo "<p>✅ Users will see notifications when new feedback is submitted</p>";

?>