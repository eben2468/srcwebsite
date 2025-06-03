<?php
// Initialize session
session_start();

// Include main auth functions
require_once '../auth_functions.php';

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