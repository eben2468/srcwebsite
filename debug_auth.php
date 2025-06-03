<?php
// Show all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include both auth files to test if we still get redeclaration errors
require_once 'auth_functions.php';
require_once 'pages_php/auth.php';

// Test the functions
echo "<h2>Auth Functions Test</h2>";
echo "<p>isLoggedIn(): " . (isLoggedIn() ? "Yes" : "No") . "</p>";
echo "<p>getCurrentUser(): "; print_r(getCurrentUser()); echo "</p>";
echo "<p>isAdmin(): " . (isAdmin() ? "Yes" : "No") . "</p>";

echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p>Test completed without errors.</p>";
?> 