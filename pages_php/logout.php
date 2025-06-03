<?php
// Include authentication functions
require_once '../auth_functions.php';

// Log out the user
logout();

// Redirect to login page
header("Location: login.php");
exit;
?> 