<?php
/**
 * Fix Feedback Assignment Functionality
 * This script fixes the feedback assignment issue in manage-feedback.php
 */

// Include required files
require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';
require_once 'includes/db_config.php';
require_once 'includes/db_functions.php';
require_once 'includes/settings_functions.php';
require_once 'includes/feedback_notifications.php';

// Require login for this page
requireLogin();

// Get current user info
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();
$isSuperAdmin = isSuperAdmin();

// Check if user has permission to manage feedback
$canManageFeedback = shouldUseAdminInterface() || $isMember;
if (!$canManageFeedback) {
    header('Location: pages_php/dashboard.php?error=access_denied');
    exit();
}

echo "<h2>Feedback Assignment Fix</h2>";
echo "<p>This script will test and fix the feedback assignment functionality.</p>";

// Test 1: Check if members are loaded correctly
echo "<h3>Test 1: Check Members Loading</h3>";
$membersSql = "SELECT user_id, first_name, last_name, role FROM users WHERE role IN ('admin', 'member') ORDER BY first_name, last_name";
$members = fetchAll($membersSql);

echo "<p><strong>Members found:</strong> " . count($members) . "</p>";
if (empty($members)) {
    echo "<div style='color: red;'>❌ No members found! This is the main issue.</div>";
    
    // Check if there are any users at all
    $allUsersSql = "SELECT user_id, first_name, last_name, role FROM users ORDER BY first_name, last_name";
    $allUsers = fetchAll($allUsersSql);
    echo "<p><strong>All users found:</strong> " . count($allUsers) . "</p>";
    
    if (!empty($allUsers)) {
        echo "<h4>Available Users and Their Roles:</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Role</th></tr>";
        foreach ($allUsers as $user) {
            echo "<tr>";
            echo "<td>" . $user['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Suggest fix
        echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107;'>";
        echo "<strong>Fix Suggestion:</strong> Update user roles to 'admin' or 'member' for users who should be able to receive feedback assignments.";
        echo "</div>";
    }
} else {
    echo "<div style='color: green;'>✅ Members loaded successfully!</div>";
    echo "<h4>Available Members:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Role</th></tr>";
    foreach ($members as $member) {
        echo "<tr>";
        echo "<td>" . $member['user_id'] . "</td>";
        echo "<td>" . htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($member['role']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 2: Check feedback table structure
echo "<h3>Test 2: Check Feedback Table Structure</h3>";
$tableStructureSql = "DESCRIBE feedback";
$tableStructure = fetchAll($tableStructureSql);

echo "<h4>Feedback Table Columns:</h4>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($tableStructure as $column) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check if assigned_to column exists and its type
$assignedToColumn = array_filter($tableStructure, function($col) {
    return $col['Field'] === 'assigned_to';
});

if (empty($assignedToColumn)) {
    echo "<div style='color: red;'>❌ 'assigned_to' column is missing from feedback table!</div>";
    echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
    echo "<strong>Fix Required:</strong> Add 'assigned_to' column to feedback table:<br>";
    echo "<code>ALTER TABLE feedback ADD COLUMN assigned_to INT NULL, ADD FOREIGN KEY (assigned_to) REFERENCES users(user_id);</code>";
    echo "</div>";
} else {
    $assignedToColumn = reset($assignedToColumn);
    echo "<div style='color: green;'>✅ 'assigned_to' column exists!</div>";
    echo "<p><strong>Column Type:</strong> " . $assignedToColumn['Type'] . "</p>";
}

// Test 3: Check notifications table
echo "<h3>Test 3: Check Notifications Table</h3>";
$notificationsTableSql = "SHOW TABLES LIKE 'notifications'";
$notificationsTableExists = fetchAll($notificationsTableSql);

if (empty($notificationsTableExists)) {
    echo "<div style='color: orange;'>⚠️ Notifications table doesn't exist. Creating it...</div>";
    
    // Create notifications table
    $createNotificationsTableSql = "CREATE TABLE IF NOT EXISTS notifications (
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
    
    $result = mysqli_query(getDbConnection(), $createNotificationsTableSql);
    if ($result) {
        echo "<div style='color: green;'>✅ Notifications table created successfully!</div>";
    } else {
        echo "<div style='color: red;'>❌ Failed to create notifications table: " . mysqli_error(getDbConnection()) . "</div>";
    }
} else {
    echo "<div style='color: green;'>✅ Notifications table exists!</div>";
}

// Test 4: Test assignment functionality
echo "<h3>Test 4: Test Assignment Functionality</h3>";

if (!empty($members)) {
    // Get a sample feedback to test with
    $sampleFeedbackSql = "SELECT feedback_id, subject, status, assigned_to FROM feedback ORDER BY created_at DESC LIMIT 1";
    $sampleFeedback = fetchOne($sampleFeedbackSql);
    
    if ($sampleFeedback) {
        echo "<p><strong>Testing with Feedback ID:</strong> " . $sampleFeedback['feedback_id'] . "</p>";
        echo "<p><strong>Current Status:</strong> " . $sampleFeedback['status'] . "</p>";
        echo "<p><strong>Currently Assigned To:</strong> " . ($sampleFeedback['assigned_to'] ?? 'Unassigned') . "</p>";
        
        // Test assignment to first member
        $testMember = $members[0];
        $testMemberName = $testMember['first_name'] . ' ' . $testMember['last_name'];
        $testMemberId = $testMember['user_id'];
        
        echo "<p><strong>Testing assignment to:</strong> " . htmlspecialchars($testMemberName) . " (ID: $testMemberId)</p>";
        
        // Perform test assignment
        $assignmentSql = "UPDATE feedback SET assigned_to = ?, status = 'in_progress', updated_at = CURRENT_TIMESTAMP WHERE feedback_id = ?";
        $assignmentResult = update($assignmentSql, [$testMemberId, $sampleFeedback['feedback_id']]);
        
        if ($assignmentResult) {
            echo "<div style='color: green;'>✅ Assignment successful!</div>";
            
            // Test notification
            echo "<p>Testing notification...</p>";
            $notificationResult = notifyFeedbackAssignment($sampleFeedback['feedback_id'], $testMemberId, $currentUser['user_id']);
            
            if ($notificationResult && $notificationResult['notification_sent']) {
                echo "<div style='color: green;'>✅ Notification sent successfully!</div>";
                if ($notificationResult['email_sent']) {
                    echo "<div style='color: green;'>✅ Email notification sent!</div>";
                } else {
                    echo "<div style='color: orange;'>⚠️ In-app notification sent, but email failed.</div>";
                }
            } else {
                echo "<div style='color: red;'>❌ Failed to send notification.</div>";
            }
            
            // Verify assignment
            $verificationSql = "SELECT assigned_to, status FROM feedback WHERE feedback_id = ?";
            $verificationResult = fetchOne($verificationSql, [$sampleFeedback['feedback_id']]);
            
            if ($verificationResult && $verificationResult['assigned_to'] == $testMemberId) {
                echo "<div style='color: green;'>✅ Assignment verified in database!</div>";
                echo "<p><strong>New Status:</strong> " . $verificationResult['status'] . "</p>";
                echo "<p><strong>Assigned To ID:</strong> " . $verificationResult['assigned_to'] . "</p>";
            } else {
                echo "<div style='color: red;'>❌ Assignment verification failed!</div>";
            }
        } else {
            echo "<div style='color: red;'>❌ Assignment failed!</div>";
            echo "<p><strong>SQL:</strong> " . $assignmentSql . "</p>";
            echo "<p><strong>Parameters:</strong> [" . $testMemberId . ", " . $sampleFeedback['feedback_id'] . "]</p>";
        }
    } else {
        echo "<div style='color: orange;'>⚠️ No feedback found to test with.</div>";
    }
} else {
    echo "<div style='color: red;'>❌ Cannot test assignment - no members available.</div>";
}

// Test 5: Check current user permissions
echo "<h3>Test 5: Current User Permissions</h3>";
echo "<p><strong>Current User:</strong> " . htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) . "</p>";
echo "<p><strong>Role:</strong> " . htmlspecialchars($currentUser['role']) . "</p>";
echo "<p><strong>Is Admin:</strong> " . ($isAdmin ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Is Member:</strong> " . ($isMember ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Is Super Admin:</strong> " . ($isSuperAdmin ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Can Manage Feedback:</strong> " . ($canManageFeedback ? 'Yes' : 'No') . "</p>";

// Summary and recommendations
echo "<h3>Summary and Recommendations</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<h4>Issues Found:</h4>";
echo "<ul>";
if (empty($members)) {
    echo "<li>❌ No admin/member users found for assignment dropdown</li>";
}
if (empty($assignedToColumn)) {
    echo "<li>❌ Missing 'assigned_to' column in feedback table</li>";
}
if (empty($notificationsTableExists)) {
    echo "<li>⚠️ Notifications table was missing (now created)</li>";
}
echo "</ul>";

echo "<h4>Recommended Fixes:</h4>";
echo "<ol>";
if (empty($members)) {
    echo "<li>Update user roles in the database to include 'admin' or 'member' roles</li>";
}
if (empty($assignedToColumn)) {
    echo "<li>Add 'assigned_to' column to feedback table</li>";
}
echo "<li>Test the assignment functionality after applying fixes</li>";
echo "<li>Ensure email configuration is set up for email notifications</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='pages_php/manage-feedback.php'>← Back to Manage Feedback</a></p>";
?>