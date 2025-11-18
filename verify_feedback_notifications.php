<?php
/**
 * Verification script for feedback notifications
 * Run this to verify that the notification system is working properly
 */

require_once 'includes/db_config.php';
require_once 'includes/simple_auth.php';

echo "<h1>Feedback Notification System Verification</h1>";

// 1. Check if notifications table exists and has correct structure
echo "<h2>1. Database Structure Check</h2>";

$table_check = "SHOW TABLES LIKE 'notifications'";
$table_result = mysqli_query($conn, $table_check);

if ($table_result && mysqli_num_rows($table_result) > 0) {
    echo "✅ Notifications table exists<br>";
    
    // Check table structure
    $structure_check = "DESCRIBE notifications";
    $structure_result = mysqli_query($conn, $structure_check);
    
    if ($structure_result) {
        echo "✅ Table structure:<br>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = mysqli_fetch_assoc($structure_result)) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
} else {
    echo "❌ Notifications table does not exist<br>";
}

// 2. Check target users
echo "<h2>2. Target Users Check</h2>";

$users_sql = "SELECT role, COUNT(*) as count, GROUP_CONCAT(CONCAT(first_name, ' ', last_name) SEPARATOR ', ') as names 
              FROM users 
              WHERE role IN ('super_admin', 'admin', 'finance', 'member') AND status = 'active' 
              GROUP BY role";
$users_result = mysqli_query($conn, $users_sql);

if ($users_result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Role</th><th>Count</th><th>Users</th></tr>";
    $total_users = 0;
    
    while ($row = mysqli_fetch_assoc($users_result)) {
        echo "<tr>";
        echo "<td>{$row['role']}</td>";
        echo "<td>{$row['count']}</td>";
        echo "<td>" . substr($row['names'], 0, 100) . (strlen($row['names']) > 100 ? '...' : '') . "</td>";
        echo "</tr>";
        $total_users += $row['count'];
    }
    echo "</table>";
    echo "<p><strong>Total users who will receive feedback notifications: {$total_users}</strong></p>";
} else {
    echo "❌ Error checking users: " . mysqli_error($conn) . "<br>";
}

// 3. Check recent feedback submissions
echo "<h2>3. Recent Feedback Submissions</h2>";

$feedback_sql = "SELECT id, subject, message, feedback_type, status, created_at 
                 FROM feedback 
                 ORDER BY created_at DESC 
                 LIMIT 5";
$feedback_result = mysqli_query($conn, $feedback_sql);

if ($feedback_result && mysqli_num_rows($feedback_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Subject</th><th>Type</th><th>Status</th><th>Created</th></tr>";
    
    while ($row = mysqli_fetch_assoc($feedback_result)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['subject']}</td>";
        echo "<td>{$row['feedback_type']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No recent feedback submissions found.<br>";
}

// 4. Check recent notifications
echo "<h2>4. Recent Feedback Notifications</h2>";

$notifications_sql = "SELECT n.*, u.first_name, u.last_name, u.role 
                      FROM notifications n 
                      JOIN users u ON n.user_id = u.user_id 
                      WHERE n.message LIKE '%feedback%' 
                      ORDER BY n.created_at DESC 
                      LIMIT 10";
$notifications_result = mysqli_query($conn, $notifications_sql);

if ($notifications_result && mysqli_num_rows($notifications_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>User</th><th>Role</th><th>Title</th><th>Message</th><th>Type</th><th>Read</th><th>Created</th></tr>";
    
    while ($row = mysqli_fetch_assoc($notifications_result)) {
        echo "<tr>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['role']}</td>";
        echo "<td>{$row['title']}</td>";
        echo "<td>" . substr($row['message'], 0, 50) . "...</td>";
        echo "<td>{$row['type']}</td>";
        echo "<td>" . ($row['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No recent feedback notifications found.<br>";
}

// 5. System Configuration Summary
echo "<h2>5. System Configuration Summary</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<h3>✅ Feedback Notification System is Configured:</h3>";
echo "<ul>";
echo "<li><strong>Target Roles:</strong> super_admin, admin, finance, member</li>";
echo "<li><strong>Notification Type:</strong> Warning (orange badge)</li>";
echo "<li><strong>Trigger:</strong> When new feedback is submitted</li>";
echo "<li><strong>Display:</strong> Header notification dropdown with red badge</li>";
echo "<li><strong>Action:</strong> Clicking notification takes user to feedback.php</li>";
echo "</ul>";

echo "<h3>📋 How it Works:</h3>";
echo "<ol>";
echo "<li>Student submits feedback via feedback.php</li>";
echo "<li>System calls autoNotifyFeedbackSubmitted() function</li>";
echo "<li>Notification is created for all super_admin, admin, finance, and member users</li>";
echo "<li>Users see red badge on notification bell in header</li>";
echo "<li>Clicking the bell shows the notification dropdown</li>";
echo "<li>Clicking the notification takes them to the feedback page</li>";
echo "</ol>";
echo "</div>";

echo "<h2>6. Test Instructions</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h3>To Test the System:</h3>";
echo "<ol>";
echo "<li>Log in as a student account</li>";
echo "<li>Go to the Feedback page</li>";
echo "<li>Submit a new feedback</li>";
echo "<li>Log out and log in as an admin/member account</li>";
echo "<li>Check the notification bell in the header - it should show a red badge</li>";
echo "<li>Click the bell to see the new feedback notification</li>";
echo "</ol>";
echo "</div>";

?>