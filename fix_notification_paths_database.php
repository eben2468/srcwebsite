<?php
/**
 * Database Fix Script for Notification Paths
 * This script fixes existing notification action_url paths in the database
 */

require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';
require_once 'includes/db_config.php';
require_once 'includes/db_functions.php';

// Require super admin access for this script
requireLogin();
if (!isSuperAdmin()) {
    die("Access denied. Super admin access required.");
}

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Fix Notification Paths in Database</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }";
echo ".result-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".warning { color: #ffc107; font-weight: bold; }";
echo ".code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }";
echo "</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Fix Notification Paths in Database</h1>";

// Check if this is a POST request to actually run the fix
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_fix'])) {
    echo "<div class='result-card'>";
    echo "<h3>🚀 Running Database Fix...</h3>";
    
    try {
        // Start transaction
        mysqli_autocommit($conn, false);
        
        $totalFixed = 0;
        $errors = [];
        
        // Fix 1: Remove duplicate pages_php/ from support paths
        echo "<h5>1. Fixing duplicate pages_php/ in support paths...</h5>";
        $sql1 = "UPDATE notifications SET action_url = REPLACE(action_url, 'pages_php/pages_php/', 'pages_php/') WHERE action_url LIKE '%pages_php/pages_php/%'";
        $result1 = mysqli_query($conn, $sql1);
        if ($result1) {
            $affected1 = mysqli_affected_rows($conn);
            echo "<p class='success'>✅ Fixed {$affected1} notifications with duplicate pages_php/</p>";
            $totalFixed += $affected1;
        } else {
            $errors[] = "Error fixing duplicate pages_php/: " . mysqli_error($conn);
        }
        
        // Fix 2: Convert pages_php/ prefixed paths to relative paths for support
        echo "<h5>2. Converting pages_php/support/ to support/...</h5>";
        $sql2 = "UPDATE notifications SET action_url = REPLACE(action_url, 'pages_php/support/', 'support/') WHERE action_url LIKE 'pages_php/support/%'";
        $result2 = mysqli_query($conn, $sql2);
        if ($result2) {
            $affected2 = mysqli_affected_rows($conn);
            echo "<p class='success'>✅ Fixed {$affected2} support notifications</p>";
            $totalFixed += $affected2;
        } else {
            $errors[] = "Error fixing support paths: " . mysqli_error($conn);
        }
        
        // Fix 3: Fix absolute paths like /vvusrc/pages_php/
        echo "<h5>3. Fixing absolute paths /vvusrc/pages_php/...</h5>";
        $sql3 = "UPDATE notifications SET action_url = REPLACE(action_url, '/vvusrc/pages_php/', '') WHERE action_url LIKE '/vvusrc/pages_php/%'";
        $result3 = mysqli_query($conn, $sql3);
        if ($result3) {
            $affected3 = mysqli_affected_rows($conn);
            echo "<p class='success'>✅ Fixed {$affected3} absolute path notifications</p>";
            $totalFixed += $affected3;
        } else {
            $errors[] = "Error fixing absolute paths: " . mysqli_error($conn);
        }
        
        // Fix 4: Fix any remaining pages_php/ prefixes for other files
        echo "<h5>4. Fixing other pages_php/ prefixed paths...</h5>";
        $sql4 = "UPDATE notifications SET action_url = REPLACE(action_url, 'pages_php/', '') WHERE action_url LIKE 'pages_php/%' AND action_url NOT LIKE 'support/%'";
        $result4 = mysqli_query($conn, $sql4);
        if ($result4) {
            $affected4 = mysqli_affected_rows($conn);
            echo "<p class='success'>✅ Fixed {$affected4} other notifications</p>";
            $totalFixed += $affected4;
        } else {
            $errors[] = "Error fixing other paths: " . mysqli_error($conn);
        }
        
        // Check for errors
        if (empty($errors)) {
            // Commit transaction
            mysqli_commit($conn);
            echo "<div class='alert alert-success'>";
            echo "<h4>🎉 Database Fix Completed Successfully!</h4>";
            echo "<p><strong>Total notifications fixed:</strong> {$totalFixed}</p>";
            echo "</div>";
        } else {
            // Rollback transaction
            mysqli_rollback($conn);
            echo "<div class='alert alert-danger'>";
            echo "<h4>❌ Database Fix Failed</h4>";
            echo "<ul>";
            foreach ($errors as $error) {
                echo "<li>{$error}</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        // Restore autocommit
        mysqli_autocommit($conn, true);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        echo "<div class='alert alert-danger'>";
        echo "<h4>❌ Exception occurred:</h4>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Show updated notifications
    echo "<div class='result-card'>";
    echo "<h3>📊 Updated Notifications Sample</h3>";
    $sampleSql = "SELECT id, title, message, action_url, created_at FROM notifications WHERE action_url IS NOT NULL ORDER BY created_at DESC LIMIT 10";
    $sampleResult = mysqli_query($conn, $sampleSql);
    
    if ($sampleResult && mysqli_num_rows($sampleResult) > 0) {
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>ID</th><th>Title</th><th>Action URL</th><th>Created</th></tr></thead>";
        echo "<tbody>";
        while ($row = mysqli_fetch_assoc($sampleResult)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['title'], 0, 30)) . "...</td>";
            echo "<td><code>" . htmlspecialchars($row['action_url']) . "</code></td>";
            echo "<td>" . date('M j, H:i', strtotime($row['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>No notifications with action URLs found.</p>";
    }
    echo "</div>";
    
} else {
    // Show current problematic notifications and fix options
    echo "<div class='result-card'>";
    echo "<h3>🔍 Current Problematic Notifications</h3>";
    
    // Check for problematic paths
    $problemSql = "SELECT id, title, message, action_url, created_at FROM notifications WHERE 
                   action_url LIKE '%pages_php/pages_php/%' OR 
                   action_url LIKE 'pages_php/support/%' OR 
                   action_url LIKE '/vvusrc/pages_php/%' OR 
                   (action_url LIKE 'pages_php/%' AND action_url NOT LIKE 'support/%')
                   ORDER BY created_at DESC LIMIT 20";
    
    $problemResult = mysqli_query($conn, $problemSql);
    
    if ($problemResult && mysqli_num_rows($problemResult) > 0) {
        echo "<p class='warning'>Found " . mysqli_num_rows($problemResult) . " notifications with problematic paths:</p>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>ID</th><th>Title</th><th>Problematic Action URL</th><th>Created</th></tr></thead>";
        echo "<tbody>";
        while ($row = mysqli_fetch_assoc($problemResult)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['title'], 0, 30)) . "...</td>";
            echo "<td><code class='error'>" . htmlspecialchars($row['action_url']) . "</code></td>";
            echo "<td>" . date('M j, H:i', strtotime($row['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        
        echo "<div class='alert alert-warning mt-3'>";
        echo "<h5>⚠️ Issues Found:</h5>";
        echo "<ul>";
        echo "<li>Duplicate <code>pages_php/</code> segments</li>";
        echo "<li>Incorrect path prefixes</li>";
        echo "<li>Absolute paths that won't work</li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        echo "<div class='alert alert-success'>";
        echo "<h5>✅ No Problematic Notifications Found</h5>";
        echo "<p>All notification paths appear to be correct!</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Show what the fix will do
    echo "<div class='result-card'>";
    echo "<h3>🔧 What This Fix Will Do</h3>";
    echo "<div class='alert alert-info'>";
    echo "<h5>Database Updates:</h5>";
    echo "<ol>";
    echo "<li><strong>Remove Duplicates:</strong> <code>pages_php/pages_php/</code> → <code>pages_php/</code></li>";
    echo "<li><strong>Fix Support Paths:</strong> <code>pages_php/support/</code> → <code>support/</code></li>";
    echo "<li><strong>Fix Absolute Paths:</strong> <code>/vvusrc/pages_php/</code> → <code></code></li>";
    echo "<li><strong>Fix Other Paths:</strong> <code>pages_php/file.php</code> → <code>file.php</code></li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
    
    // Fix button
    echo "<div class='result-card text-center'>";
    echo "<h3>🚀 Run Database Fix</h3>";
    echo "<p>Click the button below to fix all problematic notification paths in the database:</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='run_fix' class='btn btn-danger btn-lg' onclick='return confirm(\"Are you sure you want to fix all notification paths in the database? This action cannot be undone.\")'>";
    echo "<i class='fas fa-database me-2'></i>Fix Database Paths";
    echo "</button>";
    echo "</form>";
    echo "<p class='text-muted mt-2'><small>This will update all existing notifications with incorrect paths.</small></p>";
    echo "</div>";
}

echo "</div>";

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";
echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js'></script>";
echo "</body></html>";
?>