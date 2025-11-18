<?php
/**
 * Test script to verify finance role admin access
 */

require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';

// Require login for this page
requireLogin();

// Get current user info
$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'unknown';

echo "<h1>Finance Admin Access Test</h1>";
echo "<hr>";

echo "<h2>Current User Information</h2>";
echo "<ul>";
echo "<li><strong>User ID:</strong> " . ($currentUser['user_id'] ?? 'Not set') . "</li>";
echo "<li><strong>Username:</strong> " . ($currentUser['username'] ?? 'Not set') . "</li>";
echo "<li><strong>Role:</strong> " . $userRole . "</li>";
echo "</ul>";

echo "<hr>";

echo "<h2>Role Check Functions</h2>";
echo "<ul>";
echo "<li><strong>isSuperAdmin():</strong> " . (isSuperAdmin() ? 'Yes' : 'No') . "</li>";
echo "<li><strong>isAdmin():</strong> " . (isAdmin() ? 'Yes' : 'No') . "</li>";
echo "<li><strong>isFinance():</strong> " . (isFinance() ? 'Yes' : 'No') . "</li>";
echo "<li><strong>isMember():</strong> " . (isMember() ? 'Yes' : 'No') . "</li>";
echo "<li><strong>isStudent():</strong> " . (isStudent() ? 'Yes' : 'No') . "</li>";
echo "</ul>";

echo "<hr>";

echo "<h2>Admin Interface Access</h2>";
$shouldUseAdmin = shouldUseAdminInterface();
echo "<p><strong>shouldUseAdminInterface():</strong> " . ($shouldUseAdmin ? 'Yes' : 'No') . "</p>";

if ($shouldUseAdmin) {
    echo "<p style='color: green; font-weight: bold;'>✓ User CAN access admin video tutorials page</p>";
    echo "<p>This user should be able to access:</p>";
    echo "<ul>";
    echo "<li><a href='pages_php/support/admin-video-tutorials.php'>Admin Video Tutorials</a></li>";
    echo "<li>Other admin interface pages</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ User CANNOT access admin video tutorials page</p>";
    echo "<p>This user will be redirected to dashboard with access_denied error</p>";
}

echo "<hr>";

echo "<h2>Finance-Specific Access</h2>";
$canManageFinance = canManageFinance();
echo "<p><strong>canManageFinance():</strong> " . ($canManageFinance ? 'Yes' : 'No') . "</p>";

if ($canManageFinance) {
    echo "<p style='color: green;'>✓ User can manage finance-related features</p>";
} else {
    echo "<p style='color: orange;'>- User cannot manage finance-related features</p>";
}

echo "<hr>";

echo "<h2>Video Tutorial Access</h2>";
echo "<p>Based on the current role, this user should:</p>";

if ($userRole === 'super_admin') {
    echo "<p style='color: green;'>✓ See ALL video tutorials (super admin bypass)</p>";
} elseif ($userRole === 'finance') {
    echo "<p style='color: green;'>✓ See ALL video tutorials (finance bypass)</p>";
} else {
    echo "<p style='color: blue;'>- See tutorials based on role targeting</p>";
}

echo "<hr>";

echo "<h2>Test Admin Page Access</h2>";
echo "<p>Click the link below to test if you can access the admin video tutorials page:</p>";
echo "<a href='pages_php/support/admin-video-tutorials.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Admin Video Tutorials Access</a>";

echo "<hr>";

echo "<h2>Expected Behavior</h2>";
echo "<ul>";
echo "<li><strong>Super Admin:</strong> Full access to admin interface and all tutorials</li>";
echo "<li><strong>Admin:</strong> Access to admin interface and role-based tutorials</li>";
echo "<li><strong>Finance:</strong> Access to admin interface and all tutorials (NEW)</li>";
echo "<li><strong>Member:</strong> No admin interface access, role-based tutorials only</li>";
echo "<li><strong>Student:</strong> No admin interface access, role-based tutorials only</li>";
echo "</ul>";

echo "<hr>";

echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='pages_php/support/video-tutorials.php'>User Video Tutorials Page</a></li>";
echo "<li><a href='pages_php/support/admin-video-tutorials.php'>Admin Video Tutorials Page</a></li>";
echo "<li><a href='test_video_tutorials_query.php'>Test Video Query</a></li>";
echo "<li><a href='debug_video_tutorials.php'>Debug Video Tutorials</a></li>";
echo "</ul>";
?>