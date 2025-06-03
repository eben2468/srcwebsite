<?php
/**
 * Theme Toggle Handler
 * Updates the user's theme preference in the session
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if theme parameter is provided
if (isset($_POST['theme']) && in_array($_POST['theme'], ['light', 'dark', 'system'])) {
    // Update session
    $_SESSION['theme_mode'] = $_POST['theme'];
    
    // If user is logged in, we could also update their preference in the database
    if (isset($_SESSION['user_id'])) {
        require_once '../settings_functions.php';
        require_once '../db_config.php';
        
        // Update the appearance settings for this user
        updateSetting('theme_mode', $_POST['theme'], 'appearance', $_SESSION['user_id']);
    }
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'theme' => $_POST['theme']]);
} else {
    // Return error response
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid theme value']);
}
?> 