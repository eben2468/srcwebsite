<?php
/**
 * Public Chat API
 * Handles all public chat-related API requests
 */

// Include required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Enable CORS for AJAX requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Require login for all chat API endpoints
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get current user info
$currentUser = getCurrentUser();

// Update user's last activity
updateUserActivity();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'send_message':
            if ($method === 'POST') {
                sendPublicMessage();
            }
            break;
            
        case 'get_messages':
            if ($method === 'GET') {
                getPublicMessages();
            }
            break;
            
        case 'add_reaction':
            if ($method === 'POST') {
                addReaction();
            }
            break;
            
        case 'get_reactions':
            if ($method === 'GET') {
                getReactions();
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Update user's last activity timestamp
 */
function updateUserActivity() {
    global $conn, $currentUser;
    
    $sql = "UPDATE users SET last_activity = NOW() WHERE user_id = {$currentUser['user_id']}";
    mysqli_query($conn, $sql);
}

/**
 * Send a public message
 */
function sendPublicMessage() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $messageText = mysqli_real_escape_string($conn, $input['message'] ?? '');
    
    if (!$messageText) {
        http_response_code(400);
        echo json_encode(['error' => 'Message required']);
        return;
    }
    
    // Insert message into a special public chat table
    $sql = "INSERT INTO public_chat_messages (sender_id, message_text) 
            VALUES ({$currentUser['user_id']}, '$messageText')";
    
    if (mysqli_query($conn, $sql)) {
        $messageId = mysqli_insert_id($conn);
        
        echo json_encode([
            'success' => true,
            'message_id' => $messageId,
            'message' => 'Message sent successfully'
        ]);
    } else {
        throw new Exception('Failed to send message');
    }
}

/**
 * Get public messages
 */
function getPublicMessages() {
    global $conn, $currentUser;
    
    $lastMessageId = intval($_GET['last_message_id'] ?? 0);
    
    // Get messages with user info
    $sql = "SELECT pcm.*, u.first_name, u.last_name, u.user_id as sender_id, u.profile_picture, u.last_activity
            FROM public_chat_messages pcm
            JOIN users u ON pcm.sender_id = u.user_id";
    
    if ($lastMessageId > 0) {
        $sql .= " WHERE pcm.message_id > $lastMessageId";
    }
    
    $sql .= " ORDER BY pcm.sent_at ASC LIMIT 50";
    
    $result = mysqli_query($conn, $sql);
    $messages = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }
    
    // Get online users (users active in last 5 minutes)
    $onlineSql = "SELECT user_id FROM users WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
    $onlineResult = mysqli_query($conn, $onlineSql);
    $onlineUsers = [];
    
    while ($row = mysqli_fetch_assoc($onlineResult)) {
        $onlineUsers[] = intval($row['user_id']);
    }
    
    echo json_encode([
        'success' => true, 
        'messages' => $messages,
        'online_users' => $onlineUsers
    ]);
}

/**
 * Add a reaction to a message
 */
function addReaction() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $messageId = intval($input['message_id'] ?? 0);
    $reactionType = mysqli_real_escape_string($conn, $input['reaction'] ?? '');
    
    if (!$messageId || !$reactionType) {
        http_response_code(400);
        echo json_encode(['error' => 'Message ID and reaction required']);
        return;
    }
    
    // Check if user already reacted with this emoji
    $checkSql = "SELECT reaction_id FROM public_chat_reactions 
                  WHERE message_id = $messageId AND user_id = {$currentUser['user_id']} AND reaction_type = '$reactionType'";
    $checkResult = mysqli_query($conn, $checkSql);
    
    if (mysqli_num_rows($checkResult) > 0) {
        // Remove reaction (toggle off)
        $deleteSql = "DELETE FROM public_chat_reactions 
                       WHERE message_id = $messageId AND user_id = {$currentUser['user_id']} AND reaction_type = '$reactionType'";
        mysqli_query($conn, $deleteSql);
        
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => 'Reaction removed'
        ]);
    } else {
        // Add reaction
        $insertSql = "INSERT INTO public_chat_reactions (message_id, user_id, reaction_type) 
                       VALUES ($messageId, {$currentUser['user_id']}, '$reactionType')";
        
        if (mysqli_query($conn, $insertSql)) {
            echo json_encode([
                'success' => true,
                'action' => 'added',
                'message' => 'Reaction added'
            ]);
        } else {
            throw new Exception('Failed to add reaction');
        }
    }
}

/**
 * Get reactions for messages
 */
function getReactions() {
    global $conn, $currentUser;
    
    $messageIds = $_GET['message_ids'] ?? '';
    
    if (empty($messageIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message IDs required']);
        return;
    }
    
    $ids = array_map('intval', explode(',', $messageIds));
    $idsList = implode(',', $ids);
    
    // Get reactions for specified messages
    $sql = "SELECT pcr.*, u.first_name, u.last_name 
            FROM public_chat_reactions pcr
            JOIN users u ON pcr.user_id = u.user_id
            WHERE pcr.message_id IN ($idsList)
            ORDER BY pcr.created_at ASC";
    
    $result = mysqli_query($conn, $sql);
    $reactions = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $reactions[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'reactions' => $reactions
    ]);
}
?>