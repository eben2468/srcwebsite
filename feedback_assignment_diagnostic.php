<?php
/**
 * Feedback Assignment Diagnostic Tool (Standalone)
 * This script diagnoses feedback assignment issues without requiring authentication
 */

// Include only essential database files
require_once 'includes/db_config.php';

echo "<h2>Feedback Assignment Diagnostic Tool</h2>";
echo "<p>This tool will diagnose issues with the feedback assignment functionality.</p>";

// Test 1: Database Connection
echo "<h3>Test 1: Database Connection</h3>";
try {
    $conn = getDbConnection();
    if ($conn) {
        echo "<div style='color: green;'>✅ Database connection successful!</div>";
    } else {
        echo "<div style='color: red;'>❌ Database connection failed!</div>";
        exit();
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Database connection error: " . $e->getMessage() . "</div>";
    exit();
}

// Test 2: Check Users Table and Roles
echo "<h3>Test 2: Check Users and Roles</h3>";
try {
    $allUsersSql = "SELECT user_id, first_name, last_name, role FROM users ORDER BY role, first_name, last_name";
    $result = mysqli_query($conn, $allUsersSql);
    
    if ($result) {
        $allUsers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $allUsers[] = $row;
        }
        
        echo "<p><strong>Total users found:</strong> " . count($allUsers) . "</p>";
        
        if (!empty($allUsers)) {
            // Count users by role
            $roleCount = [];
            foreach ($allUsers as $user) {
                $role = $user['role'] ?? 'unknown';
                $roleCount[$role] = ($roleCount[$role] ?? 0) + 1;
            }
            
            echo "<h4>Users by Role:</h4>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Role</th><th>Count</th></tr>";
            foreach ($roleCount as $role => $count) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($role) . "</td>";
                echo "<td>" . $count . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Check for admin/member users
            $adminMemberUsers = array_filter($allUsers, function($user) {
                return in_array($user['role'], ['admin', 'member']);
            });
            
            echo "<p><strong>Admin/Member users:</strong> " . count($adminMemberUsers) . "</p>";
            
            if (empty($adminMemberUsers)) {
                echo "<div style='color: red;'>❌ No admin or member users found! This is why assignment is failing.</div>";
                echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107;'>";
                echo "<strong>Fix:</strong> Update user roles to 'admin' or 'member':<br>";
                echo "<code>UPDATE users SET role = 'admin' WHERE user_id = 1;</code><br>";
                echo "<code>UPDATE users SET role = 'member' WHERE user_id IN (2, 3, 4);</code>";
                echo "</div>";
            } else {
                echo "<div style='color: green;'>✅ Admin/Member users found!</div>";
                echo "<h4>Available for Assignment:</h4>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Name</th><th>Role</th></tr>";
                foreach ($adminMemberUsers as $user) {
                    echo "<tr>";
                    echo "<td>" . $user['user_id'] . "</td>";
                    echo "<td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<div style='color: red;'>❌ No users found in the database!</div>";
        }
    } else {
        echo "<div style='color: red;'>❌ Failed to query users table: " . mysqli_error($conn) . "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking users: " . $e->getMessage() . "</div>";
}

// Test 3: Check Feedback Table Structure
echo "<h3>Test 3: Check Feedback Table Structure</h3>";
try {
    $tableStructureSql = "DESCRIBE feedback";
    $result = mysqli_query($conn, $tableStructureSql);
    
    if ($result) {
        $columns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row;
        }
        
        echo "<h4>Feedback Table Columns:</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if assigned_to column exists
        $assignedToColumn = array_filter($columns, function($col) {
            return $col['Field'] === 'assigned_to';
        });
        
        if (empty($assignedToColumn)) {
            echo "<div style='color: red;'>❌ 'assigned_to' column is missing from feedback table!</div>";
            echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
            echo "<strong>Fix Required:</strong> Add 'assigned_to' column to feedback table:<br>";
            echo "<code>ALTER TABLE feedback ADD COLUMN assigned_to INT NULL;</code><br>";
            echo "<code>ALTER TABLE feedback ADD FOREIGN KEY (assigned_to) REFERENCES users(user_id);</code>";
            echo "</div>";
        } else {
            echo "<div style='color: green;'>✅ 'assigned_to' column exists!</div>";
        }
    } else {
        echo "<div style='color: red;'>❌ Failed to describe feedback table: " . mysqli_error($conn) . "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking feedback table: " . $e->getMessage() . "</div>";
}

// Test 4: Check Notifications Table
echo "<h3>Test 4: Check Notifications Table</h3>";
try {
    $notificationsTableSql = "SHOW TABLES LIKE 'notifications'";
    $result = mysqli_query($conn, $notificationsTableSql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<div style='color: green;'>✅ Notifications table exists!</div>";
    } else {
        echo "<div style='color: orange;'>⚠️ Notifications table doesn't exist.</div>";
        echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107;'>";
        echo "<strong>Fix:</strong> Create notifications table:<br>";
        echo "<textarea rows='10' cols='80' readonly>";
        echo "CREATE TABLE IF NOT EXISTS notifications (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        echo "</textarea>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking notifications table: " . $e->getMessage() . "</div>";
}

// Test 5: Check Sample Feedback
echo "<h3>Test 5: Check Sample Feedback</h3>";
try {
    $feedbackSql = "SELECT feedback_id, subject, status, assigned_to FROM feedback ORDER BY created_at DESC LIMIT 5";
    $result = mysqli_query($conn, $feedbackSql);
    
    if ($result) {
        $feedbacks = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $feedbacks[] = $row;
        }
        
        echo "<p><strong>Recent feedback entries:</strong> " . count($feedbacks) . "</p>";
        
        if (!empty($feedbacks)) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Subject</th><th>Status</th><th>Assigned To</th></tr>";
            foreach ($feedbacks as $feedback) {
                echo "<tr>";
                echo "<td>" . $feedback['feedback_id'] . "</td>";
                echo "<td>" . htmlspecialchars(substr($feedback['subject'], 0, 50)) . "</td>";
                echo "<td>" . htmlspecialchars($feedback['status']) . "</td>";
                echo "<td>" . ($feedback['assigned_to'] ?? 'Unassigned') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div style='color: orange;'>⚠️ No feedback entries found.</div>";
        }
    } else {
        echo "<div style='color: red;'>❌ Failed to query feedback table: " . mysqli_error($conn) . "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking feedback: " . $e->getMessage() . "</div>";
}

// Summary
echo "<h3>Summary and Next Steps</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<h4>Diagnostic Complete</h4>";
echo "<p>Based on the tests above, here are the likely issues and fixes:</p>";
echo "<ol>";
echo "<li><strong>If no admin/member users:</strong> Update user roles in the database</li>";
echo "<li><strong>If assigned_to column missing:</strong> Add the column to feedback table</li>";
echo "<li><strong>If notifications table missing:</strong> Create the notifications table</li>";
echo "<li><strong>After fixes:</strong> Test the assignment functionality in manage-feedback.php</li>";
echo "</ol>";
echo "</div>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li><a href='pages_php/manage-feedback.php'>Go to Manage Feedback</a> to test assignment</li>";
echo "<li><a href='feedback_assignment_fix.php'>Run Assignment Fix Test</a> for isolated testing</li>";
echo "<li><a href='run_feedback_assignment_fix.html'>View Complete Fix Guide</a></li>";
echo "</ul>";

// Close database connection
mysqli_close($conn);
?>