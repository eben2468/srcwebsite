<?php
/**
 * Simple verification script for elections access control
 */

// Include required files
require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Elections Access Control Verification</h2>\n";

// Test different user roles
$roles = ['super_admin', 'admin', 'member', 'finance', 'student'];

foreach ($roles as $role) {
    echo "<h3>Testing role: {$role}</h3>\n";
    
    // Simulate user session
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = $role;
    $_SESSION['username'] = 'test_' . $role;
    
    // Test the functions
    $canManage = canManageElections();
    $hasAdminInterface = shouldUseAdminInterface();
    $hasElectionsRead = hasPermission('read', 'elections');
    $hasElectionsUpdate = hasPermission('update', 'elections');
    
    echo "- canManageElections(): " . ($canManage ? 'YES' : 'NO') . "\n";
    echo "- shouldUseAdminInterface(): " . ($hasAdminInterface ? 'YES' : 'NO') . "\n";
    echo "- hasPermission('read', 'elections'): " . ($hasElectionsRead ? 'YES' : 'NO') . "\n";
    echo "- hasPermission('update', 'elections'): " . ($hasElectionsUpdate ? 'YES' : 'NO') . "\n";
    echo "\n";
}

// Clear session
session_unset();

echo "<h3>Expected Results:</h3>\n";
echo "- Super Admin: canManageElections=YES, shouldUseAdminInterface=YES, read=YES, update=YES\n";
echo "- Admin: canManageElections=NO, shouldUseAdminInterface=YES, read=YES, update=NO\n";
echo "- Member: canManageElections=NO, shouldUseAdminInterface=NO, read=YES, update=NO\n";
echo "- Finance: canManageElections=NO, shouldUseAdminInterface=NO, read=YES, update=NO\n";
echo "- Student: canManageElections=NO, shouldUseAdminInterface=NO, read=YES, update=NO\n";
?>