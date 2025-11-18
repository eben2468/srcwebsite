<?php
/**
 * Debug Portfolio Admin Access
 * 
 * This script helps debug why admin controls might not be showing for super admin users
 */

// Start session
session_start();

// Include required files
require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';
require_once 'includes/db_functions.php';
require_once 'includes/settings_functions.php';

echo "<h2>Portfolio Admin Access Debug</h2>\n";

// Test different user scenarios
$testScenarios = [
    'super_admin' => 'Super Admin',
    'admin' => 'Admin', 
    'member' => 'Member'
];

foreach ($testScenarios as $role => $roleName) {
    echo "<h3>Testing $roleName Role</h3>\n";
    
    // Set up session for this role
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = $role;
    $_SESSION['username'] = "test_$role";
    $_SESSION['is_logged_in'] = true;
    
    // Test the functions
    $isLoggedIn = isLoggedIn();
    $isSuperAdmin = isSuperAdmin();
    $isAdmin = isAdmin();
    $shouldUseAdminInterface = shouldUseAdminInterface();
    
    echo "isLoggedIn(): " . ($isLoggedIn ? 'true' : 'false') . "<br>\n";
    echo "isSuperAdmin(): " . ($isSuperAdmin ? 'true' : 'false') . "<br>\n";
    echo "isAdmin(): " . ($isAdmin ? 'true' : 'false') . "<br>\n";
    echo "shouldUseAdminInterface(): " . ($shouldUseAdminInterface ? 'true' : 'false') . "<br>\n";
    
    // Test what would happen in portfolio.php
    $portfolioIsAdmin = shouldUseAdminInterface();
    echo "<strong>Portfolio \$isAdmin would be: " . ($portfolioIsAdmin ? 'true' : 'false') . "</strong><br>\n";
    
    if ($portfolioIsAdmin) {
        echo "✅ Admin controls WOULD be visible<br>\n";
        echo "✅ Create Portfolio button WOULD be visible<br>\n";
        echo "✅ Edit/Delete buttons WOULD be visible<br>\n";
    } else {
        echo "❌ Admin controls would NOT be visible<br>\n";
        echo "❌ Create Portfolio button would NOT be visible<br>\n";
        echo "❌ Edit/Delete buttons would NOT be visible<br>\n";
    }
    
    echo "<hr>\n";
}

// Test the actual portfolio page logic simulation
echo "<h3>Portfolio Page Logic Simulation</h3>\n";

// Reset to super admin
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';
$_SESSION['username'] = 'test_super_admin';
$_SESSION['is_logged_in'] = true;

// Simulate the exact logic from portfolio.php
$currentUser = getCurrentUser();
$isAdmin = shouldUseAdminInterface();

echo "Current User: " . print_r($currentUser, true) . "<br>\n";
echo "\$isAdmin variable: " . ($isAdmin ? 'true' : 'false') . "<br>\n";

// Test the conditional checks
echo "<h4>Conditional Checks:</h4>\n";

// Check 1: Create Portfolio Button
if ($isAdmin) {
    echo "✅ Create Portfolio button condition: PASSED<br>\n";
} else {
    echo "❌ Create Portfolio button condition: FAILED<br>\n";
}

// Check 2: Admin controls in cards
if ($isAdmin) {
    echo "✅ Admin controls in cards condition: PASSED<br>\n";
} else {
    echo "❌ Admin controls in cards condition: FAILED<br>\n";
}

// Check 3: Delete functionality
if ($isAdmin && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    echo "✅ Delete functionality condition: WOULD PASS (if GET params present)<br>\n";
} else {
    if ($isAdmin) {
        echo "✅ Delete functionality condition: WOULD PASS (admin check passed, GET params not set)<br>\n";
    } else {
        echo "❌ Delete functionality condition: FAILED (admin check failed)<br>\n";
    }
}

// Test feature permission
echo "<h4>Feature Permission Check:</h4>\n";
if (function_exists('hasFeaturePermission')) {
    $hasPortfolioPermission = hasFeaturePermission('enable_portfolios');
    echo "hasFeaturePermission('enable_portfolios'): " . ($hasPortfolioPermission ? 'true' : 'false') . "<br>\n";
} else {
    echo "hasFeaturePermission function not available<br>\n";
}

// Clean up
unset($_SESSION['user_id']);
unset($_SESSION['role']);
unset($_SESSION['username']);
unset($_SESSION['is_logged_in']);

?>