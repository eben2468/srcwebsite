<?php
/**
 * Debug script to check actual session state and minutes access
 */

require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/auth_functions.php';

echo "<h2>Minutes Access Debug</h2>\n";

// Check if user is logged in
echo "<h3>Session Information:</h3>\n";
echo "- Session started: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "\n";
echo "- User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "- Username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "- Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "- Is logged in flag: " . ($_SESSION['is_logged_in'] ?? 'NOT SET') . "\n";

echo "\n<h3>Authentication Functions:</h3>\n";
echo "- isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "\n";
echo "- isAdmin(): " . (isAdmin() ? 'true' : 'false') . "\n";
echo "- isMember(): " . (isMember() ? 'true' : 'false') . "\n";
echo "- isSuperAdmin(): " . (isSuperAdmin() ? 'true' : 'false') . "\n";
echo "- shouldUseAdminInterface(): " . (shouldUseAdminInterface() ? 'true' : 'false') . "\n";

echo "\n<h3>Minutes Access Logic:</h3>\n";
$shouldUseAdminInterface = shouldUseAdminInterface();
$isMember = isMember();
$canAccess = $shouldUseAdminInterface || $isMember;

echo "- shouldUseAdminInterface: " . ($shouldUseAdminInterface ? 'true' : 'false') . "\n";
echo "- isMember: " . ($isMember ? 'true' : 'false') . "\n";
echo "- Access condition (\$shouldUseAdminInterface || \$isMember): " . ($canAccess ? 'true' : 'false') . "\n";
echo "- Negated condition (!\$shouldUseAdminInterface && !\$isMember): " . ((!$shouldUseAdminInterface && !$isMember) ? 'true' : 'false') . "\n";

if (!$shouldUseAdminInterface && !$isMember) {
    echo "\n❌ ACCESS DENIED: User would see 'You do not have permission to access meeting minutes.'\n";
    echo "- Would be redirected to: senate.php\n";
} else {
    echo "\n✅ ACCESS GRANTED: User can access minutes page\n";
}

echo "\n<h3>Recommendations:</h3>\n";
if (!isLoggedIn()) {
    echo "- User is not logged in. Please log in first.\n";
} elseif ($_SESSION['role'] === 'super_admin' && !$shouldUseAdminInterface) {
    echo "- Super admin role detected but shouldUseAdminInterface() returns false. Check auth_functions.php\n";
} elseif (!isset($_SESSION['role'])) {
    echo "- No role set in session. Check user login process.\n";
} else {
    echo "- All checks passed. Access should be granted.\n";
}
?>