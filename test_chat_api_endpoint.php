<?php
// Test the public chat API endpoint directly
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Require login
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Test sending a message
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'] ?? '';
    
    if (!empty($message)) {
        $currentUser = getCurrentUser();
        $message = mysqli_real_escape_string($conn, $message);
        
        $sql = "INSERT INTO public_chat_messages (sender_id, message_text) 
                VALUES ({$currentUser['user_id']}, '$message')";
        
        if (mysqli_query($conn, $sql)) {
            $messageId = mysqli_insert_id($conn);
            echo json_encode([
                'success' => true,
                'message_id' => $messageId,
                'message' => 'Message sent successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . mysqli_error($conn)
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Message is required'
        ]);
    }
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'API endpoint is working'
]);
?>