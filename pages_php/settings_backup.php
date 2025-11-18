<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/db_config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Get all settings from the database
$settings = getAllSettings();

// If no settings found, return an empty object
if (empty($settings)) {
    $settings = new stdClass();
}

// Set headers for JSON download
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="settings_backup_' . date('Y-m-d') . '.json"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output settings as JSON
echo json_encode($settings, JSON_PRETTY_PRINT);
exit;
?> 
