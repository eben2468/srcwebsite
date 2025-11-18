<?php
/**
 * Simple test to replicate the exact query logic from video-tutorials.php
 */

// Include required files (same as video-tutorials.php)
require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';
require_once 'includes/db_config.php';
require_once 'includes/db_functions.php';
require_once 'includes/settings_functions.php';

// Require login for this page
requireLogin();

// Get current user info (same as video-tutorials.php)
$currentUser = getCurrentUser();
$shouldUseAdminInterface = shouldUseAdminInterface();
$isAdmin = $shouldUseAdminInterface;
$isMember = isMember();
$isStudent = isStudent();

// Get current user role (same as video-tutorials.php)
$userRole = $currentUser['role'] ?? 'student';

echo "<h1>Video Tutorials Query Test</h1>";
echo "<p><strong>Current User Role:</strong> $userRole</p>";
echo "<hr>";

// Fetch video tutorials from database (EXACT same code as video-tutorials.php)
$tutorials = [];
try {
    $sql = "SELECT * FROM video_tutorials WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC";
    echo "<p><strong>SQL Query:</strong> $sql</p>";
    
    $result = mysqli_query($conn, $sql);
    if ($result) {
        echo "<p><strong>Query executed successfully</strong></p>";
        echo "<p><strong>Number of rows returned:</strong> " . mysqli_num_rows($result) . "</p>";
        
        $rowCount = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $rowCount++;
            echo "<h3>Tutorial #$rowCount: " . htmlspecialchars($row['title']) . "</h3>";
            
            // Check if user role is in target roles (EXACT same logic as video-tutorials.php)
            $targetRoles = json_decode($row['target_roles'], true) ?: [];
            
            echo "<ul>";
            echo "<li><strong>Tutorial ID:</strong> " . $row['tutorial_id'] . "</li>";
            echo "<li><strong>Title:</strong> " . htmlspecialchars($row['title']) . "</li>";
            echo "<li><strong>Category:</strong> " . htmlspecialchars($row['category']) . "</li>";
            echo "<li><strong>Is Active:</strong> " . ($row['is_active'] ? 'Yes' : 'No') . "</li>";
            echo "<li><strong>Target Roles (raw):</strong> " . htmlspecialchars($row['target_roles']) . "</li>";
            echo "<li><strong>Target Roles (decoded):</strong> " . implode(', ', $targetRoles) . "</li>";
            echo "<li><strong>Current User Role:</strong> $userRole</li>";
            echo "</ul>";
            
            // Apply the filtering logic
            if (empty($targetRoles)) {
                echo "<p style='color: green; font-weight: bold;'>✓ WILL SHOW: No role restriction (empty target roles)</p>";
                $tutorials[] = $row;
            } elseif (in_array($userRole, $targetRoles)) {
                echo "<p style='color: green; font-weight: bold;'>✓ WILL SHOW: User role '$userRole' is in target roles</p>";
                $tutorials[] = $row;
            } else {
                echo "<p style='color: red; font-weight: bold;'>✗ WILL NOT SHOW: User role '$userRole' is NOT in target roles</p>";
            }
            
            echo "<hr>";
        }
    } else {
        echo "<p style='color: red;'><strong>Query failed:</strong> " . mysqli_error($conn) . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Exception:</strong> " . $e->getMessage() . "</p>";
    // If table doesn't exist, use default tutorials (same as video-tutorials.php)
    $tutorials = [];
    echo "<p>Using empty tutorials array due to exception</p>";
}

echo "<h2>Final Result</h2>";
echo "<p><strong>Total tutorials that will be displayed:</strong> " . count($tutorials) . "</p>";

if (count($tutorials) > 0) {
    echo "<h3>Tutorials that will show:</h3>";
    echo "<ol>";
    foreach ($tutorials as $tutorial) {
        echo "<li>" . htmlspecialchars($tutorial['title']) . " (Category: " . htmlspecialchars($tutorial['category']) . ")</li>";
    }
    echo "</ol>";
} else {
    echo "<p style='color: red; font-weight: bold;'>NO TUTORIALS WILL BE DISPLAYED!</p>";
    echo "<p>This explains why the video-tutorials.php page shows 'No video tutorials available'</p>";
}

echo "<hr>";
echo "<h2>Recommendations</h2>";

if (count($tutorials) === 0) {
    echo "<ul>";
    echo "<li>Run the <a href='debug_video_tutorials.php'>debug script</a> for detailed analysis</li>";
    echo "<li>Run the <a href='fix_video_tutorials_display.php'>fix script</a> to resolve common issues</li>";
    echo "<li>Check the <a href='pages_php/support/admin-video-tutorials.php'>admin panel</a> to verify tutorial settings</li>";
    echo "</ul>";
} else {
    echo "<p style='color: green;'>The query logic is working correctly. If tutorials still don't show on the main page, there might be a different issue.</p>";
}

mysqli_close($conn);
?>