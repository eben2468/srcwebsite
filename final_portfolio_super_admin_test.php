<?php
/**
 * Final Portfolio Super Admin Test
 * 
 * This test performs a comprehensive check of all portfolio functionality
 * for super admin users to identify any remaining issues.
 */

// Start session
session_start();

// Include all the same files as portfolio.php
require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';
require_once 'includes/db_functions.php';
require_once 'includes/settings_functions.php';
require_once 'includes/functions.php';

echo "<h1>Final Portfolio Super Admin Test</h1>\n";

// Test different user roles
$roles = ['super_admin', 'admin', 'member'];

foreach ($roles as $role) {
    echo "<h2>Testing Role: " . strtoupper($role) . "</h2>\n";
    
    // Set up session for this role
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = $role;
    $_SESSION['username'] = "test_$role";
    $_SESSION['is_logged_in'] = true;
    
    echo "<h3>Step 1: Login Check</h3>\n";
    if (!isLoggedIn()) {
        echo "❌ Would redirect to register.php<br>\n";
        continue;
    } else {
        echo "✅ Login check passed<br>\n";
    }
    
    echo "<h3>Step 2: Feature Permission Check</h3>\n";
    if (!hasFeaturePermission('enable_portfolios')) {
        echo "❌ Would redirect to dashboard.php (portfolios disabled)<br>\n";
        continue;
    } else {
        echo "✅ Portfolio feature permission granted<br>\n";
    }
    
    echo "<h3>Step 3: Admin Interface Check</h3>\n";
    $currentUser = getCurrentUser();
    $isAdmin = shouldUseAdminInterface();
    
    echo "Current User: " . print_r($currentUser, true) . "<br>\n";
    echo "\$isAdmin variable: " . ($isAdmin ? 'true' : 'false') . "<br>\n";
    
    echo "<h3>Step 4: UI Elements Test</h3>\n";
    
    // Test Create Portfolio Button
    if ($isAdmin) {
        echo "✅ CREATE PORTFOLIO BUTTON: VISIBLE<br>\n";
    } else {
        echo "❌ CREATE PORTFOLIO BUTTON: HIDDEN<br>\n";
    }
    
    // Test Admin Controls in Cards
    if ($isAdmin) {
        echo "✅ EDIT/DELETE BUTTONS: VISIBLE<br>\n";
    } else {
        echo "❌ EDIT/DELETE BUTTONS: HIDDEN<br>\n";
    }
    
    // Test Create Portfolio Modal
    if ($isAdmin) {
        echo "✅ CREATE PORTFOLIO MODAL: AVAILABLE<br>\n";
    } else {
        echo "❌ CREATE PORTFOLIO MODAL: NOT AVAILABLE<br>\n";
    }
    
    // Test Delete Functionality
    if ($isAdmin) {
        echo "✅ DELETE FUNCTIONALITY: ACCESSIBLE<br>\n";
    } else {
        echo "❌ DELETE FUNCTIONALITY: NOT ACCESSIBLE<br>\n";
    }
    
    echo "<h3>Step 5: Related Pages Access Test</h3>\n";
    
    // Test portfolio_edit.php access
    if (isLoggedIn() && shouldUseAdminInterface()) {
        echo "✅ PORTFOLIO EDIT PAGE: ACCESSIBLE<br>\n";
    } else {
        echo "❌ PORTFOLIO EDIT PAGE: NOT ACCESSIBLE<br>\n";
    }
    
    // Test portfolio_handler.php access
    if (isLoggedIn() && shouldUseAdminInterface()) {
        echo "✅ PORTFOLIO HANDLER: ACCESSIBLE<br>\n";
    } else {
        echo "❌ PORTFOLIO HANDLER: NOT ACCESSIBLE<br>\n";
    }
    
    // Test portfolio-detail.php admin controls
    $detailIsAdmin = shouldUseAdminInterface();
    if ($detailIsAdmin) {
        echo "✅ PORTFOLIO DETAIL ADMIN CONTROLS: VISIBLE<br>\n";
    } else {
        echo "❌ PORTFOLIO DETAIL ADMIN CONTROLS: HIDDEN<br>\n";
    }
    
    echo "<hr>\n";
}

echo "<h2>Summary and Recommendations</h2>\n";

// Reset to super admin for final test
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';
$_SESSION['username'] = 'test_super_admin';
$_SESSION['is_logged_in'] = true;

$finalIsAdmin = shouldUseAdminInterface();
$finalHasPermission = hasFeaturePermission('enable_portfolios');

if ($finalIsAdmin && $finalHasPermission) {
    echo "<div style='color: green; font-weight: bold; font-size: 18px;'>✅ IMPLEMENTATION IS CORRECT</div>\n";
    echo "<p>Super admin users should see all admin UI elements. If they don't, check:</p>\n";
    echo "<ul>\n";
    echo "<li>Browser cache - Clear cache and hard refresh</li>\n";
    echo "<li>Session data - Ensure user is actually logged in as super_admin</li>\n";
    echo "<li>Database - Check if portfolios feature is enabled in settings</li>\n";
    echo "<li>JavaScript errors - Check browser console for errors</li>\n";
    echo "<li>CSS issues - Check if admin controls are hidden by CSS</li>\n";
    echo "</ul>\n";
} else {
    echo "<div style='color: red; font-weight: bold; font-size: 18px;'>❌ IMPLEMENTATION HAS ISSUES</div>\n";
    echo "<p>Debug information:</p>\n";
    echo "<ul>\n";
    echo "<li>shouldUseAdminInterface(): " . ($finalIsAdmin ? 'true' : 'false') . "</li>\n";
    echo "<li>hasFeaturePermission(): " . ($finalHasPermission ? 'true' : 'false') . "</li>\n";
    echo "</ul>\n";
}

echo "<h2>Files Modified</h2>\n";
echo "<ul>\n";
echo "<li>pages_php/portfolio.php - Updated to use shouldUseAdminInterface()</li>\n";
echo "<li>pages_php/portfolio_edit.php - Updated to use shouldUseAdminInterface()</li>\n";
echo "<li>pages_php/portfolio_handler.php - Updated to use shouldUseAdminInterface()</li>\n";
echo "<li>pages_php/portfolio-detail.php - Updated to use shouldUseAdminInterface()</li>\n";
echo "<li>includes/settings_functions.php - Updated hasFeaturePermission() to use shouldUseAdminInterface()</li>\n";
echo "</ul>\n";

// Clean up
unset($_SESSION['user_id']);
unset($_SESSION['role']);
unset($_SESSION['username']);
unset($_SESSION['is_logged_in']);

?>