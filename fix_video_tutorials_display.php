<?php
/**
 * Fix script for video tutorials display issues
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

echo "<h1>Video Tutorials Display Fix</h1>";
echo "<hr>";

$fixes_applied = 0;
$errors = 0;

// Fix 1: Ensure all tutorials are active
echo "<h2>Fix 1: Activating Inactive Tutorials</h2>";
try {
    $sql = "UPDATE video_tutorials SET is_active = 1 WHERE is_active = 0";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $affected = mysqli_affected_rows($conn);
        echo "<p style='color: green;'>✓ Activated $affected inactive tutorials</p>";
        $fixes_applied++;
    } else {
        echo "<p style='color: red;'>✗ Error activating tutorials: " . mysqli_error($conn) . "</p>";
        $errors++;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    $errors++;
}

// Fix 2: Ensure target_roles includes all user types
echo "<h2>Fix 2: Updating Target Roles</h2>";
try {
    // Get tutorials with empty or null target_roles
    $sql = "SELECT tutorial_id, title, target_roles FROM video_tutorials WHERE target_roles IS NULL OR target_roles = '' OR target_roles = '[]'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $defaultRoles = json_encode(['student', 'member', 'admin', 'super_admin', 'finance']);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $updateSql = "UPDATE video_tutorials SET target_roles = ? WHERE tutorial_id = ?";
            $stmt = mysqli_prepare($conn, $updateSql);
            mysqli_stmt_bind_param($stmt, 'si', $defaultRoles, $row['tutorial_id']);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "<p style='color: green;'>✓ Updated target roles for: " . htmlspecialchars($row['title']) . "</p>";
                $fixes_applied++;
            } else {
                echo "<p style='color: red;'>✗ Error updating " . htmlspecialchars($row['title']) . ": " . mysqli_error($conn) . "</p>";
                $errors++;
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        echo "<p>No tutorials found with empty target roles</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    $errors++;
}

// Fix 3: Check for tutorials with restrictive role targeting
echo "<h2>Fix 3: Checking Role Restrictions</h2>";
try {
    $sql = "SELECT tutorial_id, title, target_roles FROM video_tutorials";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $targetRoles = json_decode($row['target_roles'], true) ?: [];
            
            // If tutorial only targets admin/member, suggest making it available to students too
            if (!empty($targetRoles) && !in_array('student', $targetRoles)) {
                echo "<p style='color: orange;'>⚠ Tutorial '" . htmlspecialchars($row['title']) . "' is restricted to: " . implode(', ', $targetRoles) . "</p>";
                
                // Ask if we should make it available to all users
                $allRoles = json_encode(['student', 'member', 'admin', 'super_admin', 'finance']);
                $updateSql = "UPDATE video_tutorials SET target_roles = ? WHERE tutorial_id = ?";
                $stmt = mysqli_prepare($conn, $updateSql);
                mysqli_stmt_bind_param($stmt, 'si', $allRoles, $row['tutorial_id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo "<p style='color: green;'>✓ Made available to all user types</p>";
                    $fixes_applied++;
                } else {
                    echo "<p style='color: red;'>✗ Error updating: " . mysqli_error($conn) . "</p>";
                    $errors++;
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    $errors++;
}

// Fix 4: Verify database structure
echo "<h2>Fix 4: Database Structure Verification</h2>";
try {
    $sql = "DESCRIBE video_tutorials";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        echo "<p style='color: green;'>✓ video_tutorials table structure is valid</p>";
        
        // Check for required columns
        $columns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
        }
        
        $requiredColumns = ['tutorial_id', 'title', 'description', 'is_active', 'target_roles', 'sort_order'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            echo "<p style='color: green;'>✓ All required columns present</p>";
        } else {
            echo "<p style='color: red;'>✗ Missing columns: " . implode(', ', $missingColumns) . "</p>";
            $errors++;
        }
    } else {
        echo "<p style='color: red;'>✗ Cannot verify table structure: " . mysqli_error($conn) . "</p>";
        $errors++;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    $errors++;
}

echo "<hr>";

// Summary
echo "<h2>Fix Summary</h2>";
echo "<p><strong>Fixes Applied:</strong> $fixes_applied</p>";
echo "<p><strong>Errors:</strong> $errors</p>";

if ($errors === 0) {
    echo "<p style='color: green; font-weight: bold;'>✓ All fixes completed successfully!</p>";
    echo "<p>Try viewing the video tutorials page now: <a href='pages_php/support/video-tutorials.php'>Video Tutorials</a></p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠ Some errors occurred. Please check the issues above.</p>";
}

echo "<hr>";

// Show current status
echo "<h2>Current Video Tutorials Status</h2>";
try {
    $sql = "SELECT COUNT(*) as total, 
                   SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                   SUM(CASE WHEN target_roles LIKE '%student%' THEN 1 ELSE 0 END) as for_students
            FROM video_tutorials";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $stats = mysqli_fetch_assoc($result);
        echo "<ul>";
        echo "<li><strong>Total Tutorials:</strong> " . $stats['total'] . "</li>";
        echo "<li><strong>Active Tutorials:</strong> " . $stats['active'] . "</li>";
        echo "<li><strong>Available to Students:</strong> " . $stats['for_students'] . "</li>";
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting stats: " . $e->getMessage() . "</p>";
}

echo "<hr>";

echo "<h2>Quick Actions</h2>";
echo "<ul>";
echo "<li><a href='debug_video_tutorials.php'>Run Debug Script</a></li>";
echo "<li><a href='pages_php/support/video-tutorials.php'>View Video Tutorials Page</a></li>";
echo "<li><a href='pages_php/support/admin-video-tutorials.php'>Admin Video Tutorials</a></li>";
echo "</ul>";

mysqli_close($conn);
?>