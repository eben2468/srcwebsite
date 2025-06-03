<?php
// This file redirects to the departments page while preserving admin status
session_start();

// Check if admin is set in session
$isAdmin = false;

// Check for admin status in auth system
if (file_exists('auth_functions.php')) {
    require_once 'auth_functions.php';
    if (function_exists('isAdmin') && isAdmin()) {
        $isAdmin = true;
    }
}

// Redirect to departments page with proper admin parameter
header("Location: pages_php/departments.php" . ($isAdmin ? "?admin=1" : ""));
exit;
?> 