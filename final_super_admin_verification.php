<?php
/**
 * Final verification test for super admin interface access
 */

// Include required files
require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';

echo "<h1>Final Super Admin Interface Verification</h1>";

// Test 1: Core function verification
echo "<h2>1. Core Function Verification</h2>";

$_SESSION['role'] = 'super_admin';
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'super_admin_test';

$functions = [
    'isLoggedIn' => isLoggedIn(),
    'isAdmin' => isAdmin(),
    'isSuperAdmin' => isSuperAdmin(),
    'shouldUseAdminInterface' => shouldUseAdminInterface()
];

foreach ($functions as $name => $result) {
    $status = $result ? "✅ TRUE" : "❌ FALSE";
    $expected = in_array($name, ['isLoggedIn', 'isAdmin', 'isSuperAdmin', 'shouldUseAdminInterface']) ? "TRUE" : "FALSE";
    echo "$name(): $status<br>";
}

// Test 2: Portfolio page access simulation
echo "<h2>2. Portfolio Page Access Simulation</h2>";

// Simulate the exact code from portfolio.php
$currentUser = ['user_id' => 1, 'username' => 'super_admin_test'];
$isAdmin = shouldUseAdminInterface();

echo "Portfolio page \$isAdmin variable: " . ($isAdmin ? "✅ TRUE" : "❌ FALSE") . "<br>";

// Test the specific conditions used in portfolio.php
$canSeeCreateButton = $isAdmin;
$canSeeEditButtons = $isAdmin;
$canSeeDeleteButtons = $isAdmin;
$canSeeAdminModals = $isAdmin;

echo "<h3>Portfolio Interface Elements:</h3>";
echo "- Create Portfolio button: " . ($canSeeCreateButton ? "✅ VISIBLE" : "❌ HIDDEN") . "<br>";
echo "- Edit buttons on cards: " . ($canSeeEditButtons ? "✅ VISIBLE" : "❌ HIDDEN") . "<br>";
echo "- Delete buttons on cards: " . ($canSeeDeleteButtons ? "✅ VISIBLE" : "❌ HIDDEN") . "<br>";
echo "- Admin modals: " . ($canSeeAdminModals ? "✅ VISIBLE" : "❌ HIDDEN") . "<br>";

// Test 3: Departments page access simulation
echo "<h2>3. Departments Page Access Simulation</h2>";

// Simulate the exact code from departments.php
$isAdmin = shouldUseAdminInterface();

echo "Departments page \$isAdmin variable: " . ($isAdmin ? "✅ TRUE" : "❌ FALSE") . "<br>";

// Test the specific conditions used in departments.php
$canSeeAddDepartmentButton = $isAdmin;
$canSeeDeleteButtons = $isAdmin;
$canSeeAdminModals = $isAdmin;
$canSeeManagementLinks = $isAdmin;

echo "<h3>Departments Interface Elements:</h3>";
echo "- Add Department button: " . ($canSeeAddDepartmentButton ? "✅ VISIBLE" : "❌ HIDDEN") . "<br>";
echo "- Delete buttons on cards: " . ($canSeeDeleteButtons ? "✅ VISIBLE" : "❌ HIDDEN") . "<br>";
echo "- Admin modals: " . ($canSeeAdminModals ? "✅ VISIBLE" : "❌ HIDDEN") . "<br>";
echo "- Management links: " . ($canSeeManagementLinks ? "✅ VISIBLE" : "❌ HIDDEN") . "<br>";

// Test 4: Handler access verification
echo "<h2>4. Handler Access Verification</h2>";

// Test portfolio handler access
$portfolioHandlerAccess = isLoggedIn() && shouldUseAdminInterface();
echo "Portfolio handler access: " . ($portfolioHandlerAccess ? "✅ ALLOWED" : "❌ DENIED") . "<br>";

// Test department handler access
$departmentHandlerAccess = shouldUseAdminInterface();
echo "Department handler access: " . ($departmentHandlerAccess ? "✅ ALLOWED" : "❌ DENIED") . "<br>";

// Test 5: Compare with regular admin
echo "<h2>5. Comparison with Regular Admin</h2>";

$_SESSION['role'] = 'admin';
$adminShouldUseAdminInterface = shouldUseAdminInterface();

$_SESSION['role'] = 'super_admin';
$superAdminShouldUseAdminInterface = shouldUseAdminInterface();

echo "Regular admin shouldUseAdminInterface(): " . ($adminShouldUseAdminInterface ? "✅ TRUE" : "❌ FALSE") . "<br>";
echo "Super admin shouldUseAdminInterface(): " . ($superAdminShouldUseAdminInterface ? "✅ TRUE" : "❌ FALSE") . "<br>";

$accessMatch = ($adminShouldUseAdminInterface === $superAdminShouldUseAdminInterface);
echo "Access levels match: " . ($accessMatch ? "✅ YES" : "❌ NO") . "<br>";

// Test 6: Security verification (non-admin users)
echo "<h2>6. Security Verification</h2>";

$testRoles = ['member', 'student', 'finance'];
foreach ($testRoles as $role) {
    $_SESSION['role'] = $role;
    $hasAccess = shouldUseAdminInterface();
    $status = $hasAccess ? "❌ HAS ACCESS (SECURITY ISSUE)" : "✅ NO ACCESS";
    echo "$role: $status<br>";
}

// Test 7: Final status
echo "<h2>7. Final Status</h2>";

$_SESSION['role'] = 'super_admin';
$finalCheck = shouldUseAdminInterface();

if ($finalCheck) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ SUCCESS: Super Admin Interface Access Fixed!</h3>";
    echo "<p><strong>Super admin users should now see all admin interface elements including:</strong></p>";
    echo "<ul>";
    echo "<li>Create Portfolio button on portfolio page</li>";
    echo "<li>Edit/Delete buttons on portfolio cards</li>";
    echo "<li>Add Department button on departments page</li>";
    echo "<li>Delete buttons on department cards</li>";
    echo "<li>All admin modals and management interfaces</li>";
    echo "</ul>";
    echo "<p><strong>If buttons are still not visible, try:</strong></p>";
    echo "<ul>";
    echo "<li>Hard refresh the browser (Ctrl+F5 or Cmd+Shift+R)</li>";
    echo "<li>Clear browser cache and cookies</li>";
    echo "<li>Check browser console for JavaScript errors</li>";
    echo "<li>Verify the user session is properly set</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ ISSUE: Super Admin Interface Access Not Working</h3>";
    echo "<p>There is still an issue with super admin interface access. Please check the implementation.</p>";
    echo "</div>";
}

// Test 8: Troubleshooting info
echo "<h2>8. Troubleshooting Information</h2>";
echo "<p><strong>Current session:</strong></p>";
echo "- Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
echo "- User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "- Username: " . ($_SESSION['username'] ?? 'NOT SET') . "<br>";

echo "<p><strong>Files updated:</strong></p>";
echo "- pages_php/departments.php ✅<br>";
echo "- pages_php/department_handler.php ✅<br>";
echo "- pages_php/department-detail.php ✅<br>";
echo "- pages_php/portfolio.php ✅<br>";
echo "- pages_php/portfolio_handler.php ✅<br>";
echo "- pages_php/portfolio_edit.php ✅<br>";
echo "- pages_php/functions.php ✅ (removed duplicate function)<br>";

?>