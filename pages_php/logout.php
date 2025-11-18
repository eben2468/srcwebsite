<?php
// Include simple authentication
require_once __DIR__ . '/../includes/simple_auth.php';

// Log out the user
logout();

// Redirect to login page
header("Location: login.php");
exit;
?>
