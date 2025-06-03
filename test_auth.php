<?php
// This script tests whether our auth system has redeclaration issues

// Start output buffering to capture errors
ob_start();

// Include main authentication file
require_once 'auth_functions.php';

// Include page auth file that should now just be a wrapper
require_once 'pages_php/auth.php';

// Try to use a function from auth_functions.php
$isLogged = isLoggedIn();
echo "Is logged in: " . ($isLogged ? "Yes" : "No") . "\n";

// Get any error messages
$output = ob_get_clean();
echo $output;

echo "Test completed without errors.\n";
?> 