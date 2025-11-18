<?php
/**
 * Debug script to check admin interface visibility for super admin users
 */

// Include required files
require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';

echo "<h2>Admin Interface Visibility Debug</h2>";

// Test different user roles
$testRoles = ['super_admin', 'admin', 'member', 'student'];

foreach ($testRoles as $role) {
    echo "<h3>Testing Role: $role</h3>";
    
    // Set up session for this role
    $_SESSION['role'] = $role;
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = "test_$role";
    
    // Test auth functions
    $isLoggedIn = isLoggedIn();
    $isAdmin = isAdmin();
    $isSuperAdmin = isSuperAdmin();
    $shouldUseAdminInterface = shouldUseAdminInterface();
    
    echo "- isLoggedIn(): " . ($isLoggedIn ? "✅ TRUE" : "❌ FALSE") . "<br>";
    echo "- isAdmin(): " . ($isAdmin ? "✅ TRUE" : "❌ FALSE") . "<br>";
    echo "- isSuperAdmin(): " . ($isSuperAdmin ? "✅ TRUE" : "❌ FALSE") . "<br>";
    echo "- shouldUseAdminInterface(): " . ($shouldUseAdminInterface ? "✅ TRUE" : "❌ FALSE") . "<br>";
    
    // Test what would happen in portfolio page
    $portfolioAdminAccess = $shouldUseAdminInterface;
    echo "- Portfolio admin access: " . ($portfolioAdminAccess ? "✅ YES" : "❌ NO") . "<br>";
    
    // Test what would happen in departments page
    $departmentsAdminAccess = $shouldUseAdminInterface;
    echo "- Departments admin access: " . ($departmentsAdminAccess ? "✅ YES" : "❌ NO") . "<br>";
    
    echo "<br>";
}

// Test specific scenarios
echo "<h3>Specific Test Scenarios</h3>";

// Test super admin scenario
$_SESSION['role'] = 'super_admin';
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'super_admin_user';

echo "<h4>Super Admin Test</h4>";
$shouldSeeButtons = shouldUseAdminInterface();
echo "Super admin should see admin buttons: " . ($shouldSeeButtons ? "✅ YES" : "❌ NO") . "<br>";

if ($shouldSeeButtons) {
    echo "✅ Super admin should see:<br>";
    echo "- Add Department button<br>";
    echo "- Edit/Delete buttons on departments<br>";
    echo "- Create Portfolio button<br>";
    echo "- Edit/Delete buttons on portfolios<br>";
} else {
    echo "❌ Super admin will NOT see admin interface elements<br>";
}

// Test admin scenario
$_SESSION['role'] = 'admin';
echo "<h4>Admin Test</h4>";
$shouldSeeButtons = shouldUseAdminInterface();
echo "Admin should see admin buttons: " . ($shouldSeeButtons ? "✅ YES" : "❌ NO") . "<br>";

// Check if there are any session issues
echo "<h3>Session Debug</h3>";
echo "Current session data:<br>";
echo "- Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
echo "- User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "- Username: " . ($_SESSION['username'] ?? 'NOT SET') . "<br>";

// Test the actual function implementations
echo "<h3>Function Implementation Test</h3>";

if (function_exists('shouldUseAdminInterface')) {
    echo "✅ shouldUseAdminInterface() function exists<br>";
    
    // Test the function directly
    $_SESSION['role'] = 'super_admin';
    $result1 = shouldUseAdminInterface();
    
    $_SESSION['role'] = 'admin';
    $result2 = shouldUseAdminInterface();
    
    $_SESSION['role'] = 'member';
    $result3 = shouldUseAdminInterface();
    
    echo "- super_admin: " . ($result1 ? "TRUE" : "FALSE") . "<br>";
    echo "- admin: " . ($result2 ? "TRUE" : "FALSE") . "<br>";
    echo "- member: " . ($result3 ? "FALSE" : "TRUE") . "<br>";
    
    if ($result1 && $result2 && !$result3) {
        echo "✅ Function is working correctly<br>";
    } else {
        echo "❌ Function has issues<br>";
    }
} else {
    echo "❌ shouldUseAdminInterface() function does not exist<br>";
}

// Test if there are any CSS or JavaScript issues hiding the buttons
echo "<h3>Potential Issues</h3>";
echo "<p>If super admin users are not seeing buttons, check:</p>";
echo "<ul>";
echo "<li>CSS rules that might hide admin elements</li>";
echo "<li>JavaScript that might remove admin buttons</li>";
echo "<li>Template caching issues</li>";
echo "<li>Session persistence problems</li>";
echo "</ul>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
</style>