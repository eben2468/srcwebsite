<?php
/**
 * Debug script to check video tutorials data and filtering
 */

require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';
require_once 'includes/db_config.php';
require_once 'includes/db_functions.php';

// Require login for this page
requireLogin();

// Get current user info
$currentUser = getCurrentUser();
$shouldUseAdminInterface = shouldUseAdminInterface();
$isAdmin = $shouldUseAdminInterface;
$isMember = isMember();
$isStudent = isStudent();

// Get current user role
$userRole = $currentUser['role'] ?? 'student';

echo "<h1>Video Tutorials Debug Information</h1>";
echo "<hr>";

echo "<h2>Current User Information</h2>";
echo "<ul>";
echo "<li><strong>User ID:</strong> " . ($currentUser['user_id'] ?? 'Not set') . "</li>";
echo "<li><strong>Username:</strong> " . ($currentUser['username'] ?? 'Not set') . "</li>";
echo "<li><strong>Role:</strong> " . $userRole . "</li>";
echo "<li><strong>Is Admin:</strong> " . ($isAdmin ? 'Yes' : 'No') . "</li>";
echo "<li><strong>Is Member:</strong> " . ($isMember ? 'Yes' : 'No') . "</li>";
echo "<li><strong>Is Student:</strong> " . ($isStudent ? 'Yes' : 'No') . "</li>";
echo "</ul>";

echo "<hr>";

// Check if video_tutorials table exists
echo "<h2>Database Table Check</h2>";
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'video_tutorials'");
if (mysqli_num_rows($tableCheck) > 0) {
    echo "<p style='color: green;'>✓ video_tutorials table exists</p>";
} else {
    echo "<p style='color: red;'>✗ video_tutorials table does NOT exist</p>";
    echo "<p>Please run the setup script first: <a href='run_video_tutorials_setup.html'>Setup Video Tutorials</a></p>";
    exit;
}

echo "<hr>";

// Fetch ALL tutorials from database (no filtering)
echo "<h2>All Video Tutorials in Database</h2>";
try {
    $sql = "SELECT * FROM video_tutorials ORDER BY sort_order ASC, created_at DESC";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<p><strong>Total tutorials found:</strong> " . mysqli_num_rows($result) . "</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Title</th><th>Category</th><th>Target Roles</th><th>Is Active</th><th>Sort Order</th><th>Created At</th>";
        echo "</tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            $targetRoles = json_decode($row['target_roles'], true) ?: [];
            $isActive = $row['is_active'] ? 'Yes' : 'No';
            $activeStyle = $row['is_active'] ? '' : 'background: #ffeeee;';
            
            echo "<tr style='$activeStyle'>";
            echo "<td>" . $row['tutorial_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['category']) . "</td>";
            echo "<td>" . implode(', ', $targetRoles) . "</td>";
            echo "<td>" . $isActive . "</td>";
            echo "<td>" . $row['sort_order'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No tutorials found in database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fetching tutorials: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test the filtering logic used in video-tutorials.php
echo "<h2>Filtered Tutorials (Same Logic as video-tutorials.php)</h2>";
$tutorials = [];
try {
    $sql = "SELECT * FROM video_tutorials WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        echo "<p><strong>Active tutorials found:</strong> " . mysqli_num_rows($result) . "</p>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            // Check if user role is in target roles (same logic as video-tutorials.php)
            $targetRoles = json_decode($row['target_roles'], true) ?: [];
            
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<h4>" . htmlspecialchars($row['title']) . "</h4>";
            echo "<p><strong>Target Roles:</strong> " . implode(', ', $targetRoles) . "</p>";
            echo "<p><strong>Current User Role:</strong> " . $userRole . "</p>";
            
            if (empty($targetRoles)) {
                echo "<p style='color: green;'>✓ No role restriction - tutorial will show</p>";
                $tutorials[] = $row;
            } elseif (in_array($userRole, $targetRoles)) {
                echo "<p style='color: green;'>✓ User role matches - tutorial will show</p>";
                $tutorials[] = $row;
            } else {
                echo "<p style='color: red;'>✗ User role doesn't match - tutorial will NOT show</p>";
            }
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error filtering tutorials: " . $e->getMessage() . "</p>";
}

echo "<hr>";

echo "<h2>Final Result</h2>";
echo "<p><strong>Tutorials that will show for current user:</strong> " . count($tutorials) . "</p>";

if (count($tutorials) > 0) {
    echo "<ul>";
    foreach ($tutorials as $tutorial) {
        echo "<li>" . htmlspecialchars($tutorial['title']) . " (Category: " . htmlspecialchars($tutorial['category']) . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>No tutorials will be displayed for the current user!</p>";
    echo "<p><strong>Possible reasons:</strong></p>";
    echo "<ul>";
    echo "<li>All tutorials are inactive (is_active = 0)</li>";
    echo "<li>No tutorials match the current user's role</li>";
    echo "<li>Target roles are not set correctly</li>";
    echo "</ul>";
}

echo "<hr>";

echo "<h2>Quick Actions</h2>";
echo "<ul>";
echo "<li><a href='pages_php/support/video-tutorials.php'>View Video Tutorials Page</a></li>";
echo "<li><a href='pages_php/support/admin-video-tutorials.php'>Admin Video Tutorials</a></li>";
echo "<li><a href='run_video_tutorials_setup.html'>Setup Video Tutorials</a></li>";
echo "</ul>";

mysqli_close($conn);
?>