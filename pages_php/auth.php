<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// This file is a simple wrapper around auth_functions.php
// It's kept for backward compatibility with existing code

// Process logout if requested
if (isset($_GET['logout'])) {
    logout();
}

// For direct inclusion without using functions - check if user is logged in
if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') {
    // Only redirect if not already on login page
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}
?> 
