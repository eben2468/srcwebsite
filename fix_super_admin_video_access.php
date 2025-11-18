<?php
/**
 * Fix script to add super_admin and finance roles to video tutorials
 * and ensure super_admin has full access
 */

require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';
require_once 'includes/db_config.php';

// Require login and admin access
requireLogin();
$shouldUseAdminInterface = shouldUseAdminInterface();
if (!$shouldUseAdminInterface) {
    die("Access denied. Admin privileges required.");
}

echo "<h1>Super Admin Video Tutorial Access Fix</h1>";
echo "<hr>";

$fixes_applied = 0;
$errors = 0;

// Fix 1: Add super_admin and finance to all existing video tutorials
echo "<h2>Fix 1: Adding super_admin and finance to all video tutorials</h2>";
try {
    $sql = "SELECT tutorial_id, title, target_roles FROM video_tutorials";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $targetRoles = json_decode($row['target_roles'], true) ?: [];
            
            // Add super_admin and finance if not already present
            $updated = false;
            if (!in_array('super_admin', $targetRoles)) {
                $targetRoles[] = 'super_admin';
                $updated = true;
            }
            if (!in_array('finance', $targetRoles)) {
                $targetRoles[] = 'finance';
                $updated = true;
            }
            
            if ($updated) {
                $newTargetRoles = json_encode($targetRoles);
                $updateSql = "UPDATE video_tutorials SET target_roles = ? WHERE tutorial_id = ?";
                $stmt = mysqli_prepare($conn, $updateSql);
                mysqli_stmt_bind_param($stmt, 'si', $newTargetRoles, $row['tutorial_id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo "<p style='color: green;'>✓ Updated: " . htmlspecialchars($row['title']) . "</p>";
                    echo "<p style='margin-left: 20px; color: #666;'>New roles: " . implode(', ', $targetRoles) . "</p>";
                    $fixes_applied++;
                } else {
                    echo "<p style='color: red;'>✗ Error updating " . htmlspecialchars($row['title']) . ": " . mysqli_error($conn) . "</p>";
                    $errors++;
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "<p style='color: blue;'>- Already includes super_admin and finance: " . htmlspecialchars($row['title']) . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>✗ Error fetching tutorials: " . mysqli_error($conn) . "</p>";
        $errors++;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    $errors++;
}

echo "<hr>";

// Fix 2: Ensure all tutorials are active
echo "<h2>Fix 2: Activating all tutorials</h2>";
try {
    $sql = "UPDATE video_tutorials SET is_active = 1 WHERE is_active = 0";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $affected = mysqli_affected_rows($conn);
        if ($affected > 0) {
            echo "<p style='color: green;'>✓ Activated $affected inactive tutorials</p>";
            $fixes_applied++;
        } else {
            echo "<p style='color: blue;'>- All tutorials were already active</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Error activating tutorials: " . mysqli_error($conn) . "</p>";
        $errors++;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    $errors++;
}

echo "<hr>";

// Fix 3: Show current status
echo "<h2>Current Status After Fixes</h2>";
try {
    $sql = "SELECT tutorial_id, title, target_roles, is_active FROM video_tutorials ORDER BY sort_order ASC, created_at DESC";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Title</th><th>Target Roles</th><th>Active</th>";
        echo "</tr>";
        
        $superAdminCount = 0;
        $financeCount = 0;
        $activeCount = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $targetRoles = json_decode($row['target_roles'], true) ?: [];
            $isActive = $row['is_active'] ? 'Yes' : 'No';
            $activeStyle = $row['is_active'] ? '' : 'background: #ffeeee;';
            
            if (in_array('super_admin', $targetRoles)) $superAdminCount++;
            if (in_array('finance', $targetRoles)) $financeCount++;
            if ($row['is_active']) $activeCount++;
            
            echo "<tr style='$activeStyle'>";
            echo "<td>" . $row['tutorial_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . implode(', ', $targetRoles) . "</td>";
            echo "<td>" . $isActive . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Summary Statistics</h3>";
        echo "<ul>";
        echo "<li><strong>Total Tutorials:</strong> " . mysqli_num_rows($result) . "</li>";
        echo "<li><strong>Active Tutorials:</strong> $activeCount</li>";
        echo "<li><strong>Available to Super Admin:</strong> $superAdminCount</li>";
        echo "<li><strong>Available to Finance:</strong> $financeCount</li>";
        echo "</ul>";
        
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting status: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Summary
echo "<h2>Fix Summary</h2>";
echo "<p><strong>Fixes Applied:</strong> $fixes_applied</p>";
echo "<p><strong>Errors:</strong> $errors</p>";

if ($errors === 0) {
    echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ All fixes completed successfully!</p>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Super Admin Access Enabled!</h3>";
    echo "<p>Super admin users now have full access to all video tutorials. The system will:</p>";
    echo "<ul>";
    echo "<li>Show all active tutorials to super_admin users</li>";
    echo "<li>Include super_admin and finance in all tutorial target roles</li>";
    echo "<li>Bypass role restrictions for super_admin users</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠ Some errors occurred. Please check the issues above.</p>";
}

echo "<hr>";

echo "<h2>Test the Fix</h2>";
echo "<p>Now test the video tutorials pages:</p>";
echo "<ul>";
echo "<li><a href='pages_php/support/video-tutorials.php' style='color: #007bff; text-decoration: none;'>📺 View Video Tutorials (User Page)</a></li>";
echo "<li><a href='pages_php/support/admin-video-tutorials.php' style='color: #007bff; text-decoration: none;'>⚙️ Admin Video Tutorials</a></li>";
echo "<li><a href='test_video_tutorials_query.php' style='color: #007bff; text-decoration: none;'>🔍 Run Query Test Again</a></li>";
echo "</ul>";

mysqli_close($conn);
?>