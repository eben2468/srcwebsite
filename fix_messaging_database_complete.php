<?php
/**
 * Complete Database Fix for Messaging System
 * This script fixes all database issues for the messaging service
 */

require_once 'includes/db_config.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Complete Messaging Database Fix</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }";
echo ".container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".warning { color: #ffc107; font-weight: bold; }";
echo ".alert { padding: 15px; margin: 15px 0; border-radius: 4px; }";
echo ".alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }";
echo ".alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }";
echo ".alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }";
echo ".alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }";
echo ".step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }";
echo "</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Complete Messaging Database Fix</h1>";

// Check if this is a POST request to run the fix
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_complete_fix'])) {
    echo "<div class='alert alert-info'>";
    echo "<h3>🚀 Running Complete Database Fix...</h3>";
    echo "</div>";
    
    $fixesApplied = [];
    $errors = [];
    
    try {
        // Step 1: Check and fix notifications table
        echo "<div class='step'>";
        echo "<h4>Step 1: Checking notifications table...</h4>";
        
        $notificationsExists = false;
        $checkNotifications = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
        if (mysqli_num_rows($checkNotifications) > 0) {
            $notificationsExists = true;
            echo "<p class='info'>✓ notifications table exists</p>";
        } else {
            echo "<p class='warning'>⚠ notifications table does not exist</p>";
        }
        
        if ($notificationsExists) {
            // Check columns in notifications table
            $describeResult = mysqli_query($conn, "DESCRIBE notifications");
            $columns = [];
            while ($row = mysqli_fetch_assoc($describeResult)) {
                $columns[] = $row['Field'];
            }
            
            echo "<p class='info'>Current columns: " . implode(', ', $columns) . "</p>";
            
            // Add missing columns
            if (!in_array('created_by', $columns)) {
                $sql = "ALTER TABLE notifications ADD COLUMN created_by INT NULL";
                if (mysqli_query($conn, $sql)) {
                    echo "<p class='success'>✅ Added 'created_by' column</p>";
                    $fixesApplied[] = "Added created_by column to notifications table";
                } else {
                    $error = "Failed to add created_by column: " . mysqli_error($conn);
                    echo "<p class='error'>❌ " . $error . "</p>";
                    $errors[] = $error;
                }
            } else {
                echo "<p class='info'>✓ created_by column exists</p>";
            }
            
            if (!in_array('expiry_date', $columns)) {
                $sql = "ALTER TABLE notifications ADD COLUMN expiry_date DATETIME NULL";
                if (mysqli_query($conn, $sql)) {
                    echo "<p class='success'>✅ Added 'expiry_date' column</p>";
                    $fixesApplied[] = "Added expiry_date column to notifications table";
                } else {
                    $error = "Failed to add expiry_date column: " . mysqli_error($conn);
                    echo "<p class='error'>❌ " . $error . "</p>";
                    $errors[] = $error;
                }
            } else {
                echo "<p class='info'>✓ expiry_date column exists</p>";
            }
        } else {
            // Create notifications table
            $createNotificationsSql = "CREATE TABLE `notifications` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `message` text NOT NULL,
                `type` varchar(50) NOT NULL DEFAULT 'info',
                `created_by` int(11) NULL,
                `expiry_date` datetime NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            if (mysqli_query($conn, $createNotificationsSql)) {
                echo "<p class='success'>✅ Created notifications table</p>";
                $fixesApplied[] = "Created notifications table";
            } else {
                $error = "Failed to create notifications table: " . mysqli_error($conn);
                echo "<p class='error'>❌ " . $error . "</p>";
                $errors[] = $error;
            }
        }
        echo "</div>";
        
        // Step 2: Check and create user_notifications table
        echo "<div class='step'>";
        echo "<h4>Step 2: Checking user_notifications table...</h4>";
        
        $userNotificationsExists = false;
        $checkUserNotifications = mysqli_query($conn, "SHOW TABLES LIKE 'user_notifications'");
        if (mysqli_num_rows($checkUserNotifications) > 0) {
            $userNotificationsExists = true;
            echo "<p class='info'>✓ user_notifications table exists</p>";
        } else {
            echo "<p class='warning'>⚠ user_notifications table does not exist</p>";
            
            // Create user_notifications table
            $createUserNotificationsSql = "CREATE TABLE `user_notifications` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `notification_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `is_read` tinyint(1) NOT NULL DEFAULT '0',
                `read_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `notification_id` (`notification_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            if (mysqli_query($conn, $createUserNotificationsSql)) {
                echo "<p class='success'>✅ Created user_notifications table</p>";
                $fixesApplied[] = "Created user_notifications table";
            } else {
                $error = "Failed to create user_notifications table: " . mysqli_error($conn);
                echo "<p class='error'>❌ " . $error . "</p>";
                $errors[] = $error;
            }
        }
        echo "</div>";
        
        // Step 3: Verify final structure
        echo "<div class='step'>";
        echo "<h4>Step 3: Verifying final database structure...</h4>";
        
        // Check notifications table
        $notificationsCheck = mysqli_query($conn, "DESCRIBE notifications");
        if ($notificationsCheck) {
            echo "<h5>notifications table structure:</h5>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin-bottom: 15px;'>";
            echo "<tr style='background: #f8f9fa;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            while ($row = mysqli_fetch_assoc($notificationsCheck)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Check user_notifications table
        $userNotificationsCheck = mysqli_query($conn, "DESCRIBE user_notifications");
        if ($userNotificationsCheck) {
            echo "<h5>user_notifications table structure:</h5>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
            echo "<tr style='background: #f8f9fa;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            while ($row = mysqli_fetch_assoc($userNotificationsCheck)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "</div>";
        
        // Summary
        if (empty($errors)) {
            echo "<div class='alert alert-success'>";
            echo "<h3>🎉 Database Fix Completed Successfully!</h3>";
            echo "<p>All required database components for the messaging system are now in place.</p>";
            if (!empty($fixesApplied)) {
                echo "<p><strong>Applied fixes:</strong></p>";
                echo "<ul>";
                foreach ($fixesApplied as $fix) {
                    echo "<li>" . htmlspecialchars($fix) . "</li>";
                }
                echo "</ul>";
            }
            echo "<p><strong>The messaging service should now work without errors!</strong></p>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>";
            echo "<h3>⚠️ Fix completed with some issues:</h3>";
            echo "<ul>";
            foreach ($errors as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "<h3>❌ Exception occurred:</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    
} else {
    // Show current state and option to run fix
    echo "<div class='alert alert-warning'>";
    echo "<h3>🚨 Current Issues Detected:</h3>";
    echo "<ul>";
    echo "<li>Unknown column 'created_by' in notifications table</li>";
    echo "<li>Table 'user_notifications' doesn't exist</li>";
    echo "</ul>";
    echo "<p>These issues prevent the messaging service from working properly.</p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>🔍 Current Database State Check</h3>";
    
    try {
        // Check notifications table
        $notificationsExists = false;
        $checkNotifications = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
        if (mysqli_num_rows($checkNotifications) > 0) {
            $notificationsExists = true;
            echo "<p class='info'>✓ notifications table exists</p>";
            
            $describeResult = mysqli_query($conn, "DESCRIBE notifications");
            $columns = [];
            while ($row = mysqli_fetch_assoc($describeResult)) {
                $columns[] = $row['Field'];
            }
            echo "<p>Current columns: " . implode(', ', $columns) . "</p>";
            
            $required = ['created_by', 'expiry_date'];
            $missing = array_diff($required, $columns);
            if (!empty($missing)) {
                echo "<p class='error'>Missing columns: " . implode(', ', $missing) . "</p>";
            }
        } else {
            echo "<p class='error'>❌ notifications table does not exist</p>";
        }
        
        // Check user_notifications table
        $checkUserNotifications = mysqli_query($conn, "SHOW TABLES LIKE 'user_notifications'");
        if (mysqli_num_rows($checkUserNotifications) > 0) {
            echo "<p class='info'>✓ user_notifications table exists</p>";
        } else {
            echo "<p class='error'>❌ user_notifications table does not exist</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>Error checking database: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h3>🔧 What This Fix Will Do:</h3>";
    echo "<ol>";
    echo "<li><strong>Fix notifications table:</strong> Add missing 'created_by' and 'expiry_date' columns</li>";
    echo "<li><strong>Create user_notifications table:</strong> Junction table for user-specific notifications</li>";
    echo "<li><strong>Verify structure:</strong> Ensure all components are properly created</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='text-center' style='text-align: center; margin: 30px 0;'>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='run_complete_fix' style='background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;'>";
    echo "🚀 Run Complete Database Fix";
    echo "</button>";
    echo "</form>";
    echo "<p style='color: #666; margin-top: 10px;'><small>This will fix all database issues for the messaging system</small></p>";
    echo "</div>";
}

echo "<div class='alert alert-info'>";
echo "<h3>📋 After Running the Fix:</h3>";
echo "<ol>";
echo "<li>Try creating a welfare announcement</li>";
echo "<li>Both database errors should be resolved</li>";
echo "<li>In-app notifications should work properly</li>";
echo "<li>Users should receive notifications correctly</li>";
echo "</ol>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>