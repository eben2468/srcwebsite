<?php
// Test the public chat API
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Require login
requireLogin();

// Test sending a message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                'error' => 'Failed to send message'
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

echo "<h2>Public Chat API Test</h2>";
echo "<p>Testing the public chat API functionality</p>";

// Test if tables exist
$tables = ['public_chat_messages', 'public_chat_reactions'];
foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
    }
}
?>

<script>
async function testSendMessage() {
    const message = "Test message from API test";
    
    try {
        const response = await fetch('test_chat_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({message: message})
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('result').innerHTML = 
                "<p style='color: green;'>✓ Message sent successfully! ID: " + result.message_id + "</p>";
        } else {
            document.getElementById('result').innerHTML = 
                "<p style='color: red;'>✗ Failed to send message: " + result.error + "</p>";
        }
    } catch (error) {
        document.getElementById('result').innerHTML = 
            "<p style='color: red;'>✗ Error: " + error.message + "</p>";
    }
}
</script>

<button onclick="testSendMessage()">Test Send Message</button>
<div id="result"></div>

<p><a href="pages_php/public_chat.php">Go to Public Chat</a></p>