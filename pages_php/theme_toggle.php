<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle AJAX theme toggle request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    $theme = $_POST['theme'];
    
    // Validate theme value
    if (in_array($theme, ['light', 'dark'])) {
        // Save to session
        $_SESSION['theme_mode'] = $theme;
        
        // Return success
        http_response_code(200);
        echo json_encode(['status' => 'success', 'theme' => $theme]);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid theme']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
