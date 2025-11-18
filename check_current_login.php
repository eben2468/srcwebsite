<?php
/**
 * Check current login status and role
 */

require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/auth_functions.php';

echo "<h2>Current Login Status Check</h2>\n";

echo "<h3>Session Information:</h3>\n";
echo "- Session ID: " . session_id() . "\n";
echo "- User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "- Username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "- Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "- Is logged in flag: " . ($_SESSION['is_logged_in'] ?? 'NOT SET') . "\n";
echo "- First name: " . ($_SESSION['first_name'] ?? 'NOT SET') . "\n";
echo "- Last name: " . ($_SESSION['last_name'] ?? 'NOT SET') . "\n";

echo "\n<h3>Authentication Status:</h3>\n";
$isLoggedIn = isLoggedIn();
$isAdmin = isAdmin();
$isMember = isMember();
$isSuperAdmin = isSuperAdmin();
$shouldUseAdminInterface = shouldUseAdminInterface();

echo "- isLoggedIn(): " . ($isLoggedIn ? 'YES' : 'NO') . "\n";
echo "- isAdmin(): " . ($isAdmin ? 'YES' : 'NO') . "\n";
echo "- isMember(): " . ($isMember ? 'YES' : 'NO') . "\n";
echo "- isSuperAdmin(): " . ($isSuperAdmin ? 'YES' : 'NO') . "\n";
echo "- shouldUseAdminInterface(): " . ($shouldUseAdminInterface ? 'YES' : 'NO') . "\n";

echo "\n<h3>Minutes Page Access:</h3>\n";
$canAccessMinutes = $shouldUseAdminInterface || $isMember;
echo "- Can access minutes page: " . ($canAccessMinutes ? 'YES' : 'NO') . "\n";

if (!$isLoggedIn) {
    echo "\n❌ You are not logged in. Please log in to access the minutes page.\n";
    echo "- Go to the login page and log in with super admin credentials\n";
} elseif (!$canAccessMinutes) {
    echo "\n❌ Your current role ('" . ($_SESSION['role'] ?? 'unknown') . "') does not have access to minutes.\n";
    echo "- You need to be logged in as super_admin, admin, or member\n";
} else {
    echo "\n✅ You should be able to access the minutes page.\n";
    echo "- If you're still getting an error, try clearing your browser cache and cookies\n";
    echo "- Or try logging out and logging back in\n";
}

echo "\n<h3>Next Steps:</h3>\n";
if (!$isLoggedIn) {
    echo "1. Go to the login page\n";
    echo "2. Log in with super admin credentials\n";
    echo "3. Try accessing the minutes page again\n";
} elseif ($_SESSION['role'] !== 'super_admin') {
    echo "1. Log out from your current account\n";
    echo "2. Log in with super admin credentials\n";
    echo "3. Try accessing the minutes page again\n";
} else {
    echo "1. Clear your browser cache and cookies\n";
    echo "2. Try refreshing the minutes page\n";
    echo "3. If the issue persists, try logging out and back in\n";
}
?>