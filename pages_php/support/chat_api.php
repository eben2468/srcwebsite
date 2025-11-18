<?php
/**
 * Live Chat API
 * Handles all chat-related API requests
 */

// Include required files
require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/db_functions.php';

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
require_once __DIR__ . '/../../includes/auth_functions.php';
$shouldUseAdminInterface = shouldUseAdminInterface();
$isMember = isMember();
$isAgent = $shouldUseAdminInterface || $isMember;

// Ensure admin and super admin users are treated as agents with full access
if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin') {
    $isAgent = true;
    $shouldUseAdminInterface = true;
}

// Super admins have full access to all sessions
$isSuperAdmin = ($currentUser['role'] === 'super_admin');
$hasFullAccess = $shouldUseAdminInterface || $isSuperAdmin;

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'start_session':
            if ($method === 'POST') {
                startChatSession();
            }
            break;
            
        case 'get_session':
            if ($method === 'GET') {
                getChatSession();
            }
            break;
            
        case 'send_message':
            if ($method === 'POST') {
                sendMessage();
            }
            break;
            
        case 'get_messages':
            if ($method === 'GET') {
                getMessages();
            }
            break;
            
        case 'end_session':
            if ($method === 'POST') {
                endChatSession();
            }
            break;
            
        case 'get_agent_sessions':
            if ($method === 'GET' && $isAgent) {
                getAgentSessions();
            }
            break;
            
        case 'assign_session':
            if ($method === 'POST' && $isAgent) {
                assignSession();
            }
            break;
            
        case 'update_agent_status':
            if ($method === 'POST' && $isAgent) {
                updateAgentStatus();
            }
            break;
            
        case 'get_quick_responses':
            if ($method === 'GET' && $isAgent) {
                getQuickResponses();
            }
            break;
            
        case 'mark_messages_read':
            if ($method === 'POST') {
                markMessagesRead();
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
 * Start a new chat session
 */
function startChatSession() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $subject = mysqli_real_escape_string($conn, $input['subject'] ?? '');
    $department = mysqli_real_escape_string($conn, $input['department'] ?? 'general');
    $priority = mysqli_real_escape_string($conn, $input['priority'] ?? 'medium');
    
    // Generate unique session token
    $sessionToken = bin2hex(random_bytes(32));
    
    // Check if user already has an active session
    $checkSql = "SELECT session_id FROM chat_sessions 
                 WHERE user_id = {$currentUser['user_id']} 
                 AND status IN ('waiting', 'active')";
    $checkResult = mysqli_query($conn, $checkSql);
    
    if (mysqli_num_rows($checkResult) > 0) {
        $existingSession = mysqli_fetch_assoc($checkResult);
        echo json_encode([
            'success' => true,
            'session_id' => $existingSession['session_id'],
            'message' => 'Existing session found'
        ]);
        return;
    }
    
    // Create new session
    $sql = "INSERT INTO chat_sessions (user_id, session_token, subject, department, priority, status) 
            VALUES ({$currentUser['user_id']}, '$sessionToken', '$subject', '$department', '$priority', 'waiting')";
    
    if (mysqli_query($conn, $sql)) {
        $sessionId = mysqli_insert_id($conn);
        
        // Add user as participant
        $participantSql = "INSERT INTO chat_participants (session_id, user_id, role) 
                          VALUES ($sessionId, {$currentUser['user_id']}, 'customer')";
        mysqli_query($conn, $participantSql);
        
        // Send welcome system message
        $welcomeMsg = "Welcome to VVU SRC Support! An agent will be with you shortly.";
        $msgSql = "INSERT INTO chat_messages (session_id, sender_id, message_text, message_type) 
                   VALUES ($sessionId, 0, '$welcomeMsg', 'system')";
        mysqli_query($conn, $msgSql);
        
        // Try to auto-assign to available agent
        autoAssignAgent($sessionId);
        
        echo json_encode([
            'success' => true,
            'session_id' => $sessionId,
            'session_token' => $sessionToken,
            'message' => 'Chat session started successfully'
        ]);
    } else {
        throw new Exception('Failed to create chat session');
    }
}

/**
 * Get chat session details
 */
function getChatSession() {
    global $conn, $currentUser, $isAgent, $hasFullAccess, $isSuperAdmin;
    
    $sessionId = intval($_GET['session_id'] ?? 0);
    
    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID required']);
        return;
    }
    
    // Build query based on user role - Super admins and agents have full access
    if ($hasFullAccess || $isAgent || $isSuperAdmin) {
        // Try to get user info, but don't fail if users table structure is different
        $sql = "SELECT cs.* FROM chat_sessions cs WHERE cs.session_id = $sessionId";
        
        // Try to join with users table if it exists and has expected columns
        $checkUsers = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
        if ($checkUsers && mysqli_num_rows($checkUsers) > 0) {
            $checkColumns = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'user_id'");
            if ($checkColumns && mysqli_num_rows($checkColumns) > 0) {
                $sql = "SELECT cs.*, u.user_id as customer_id FROM chat_sessions cs 
                        LEFT JOIN users u ON cs.user_id = u.user_id 
                        WHERE cs.session_id = $sessionId";
            }
        }
    } else {
        $sql = "SELECT cs.* FROM chat_sessions cs 
                WHERE cs.session_id = $sessionId AND cs.user_id = {$currentUser['user_id']}";
    }
    
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'session' => $row]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Session not found']);
    }
}

/**
 * Send a message in chat session
 */
function sendMessage() {
    global $conn, $currentUser, $hasFullAccess, $isSuperAdmin;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = intval($input['session_id'] ?? 0);
    $messageText = mysqli_real_escape_string($conn, $input['message'] ?? '');
    $messageType = mysqli_real_escape_string($conn, $input['type'] ?? 'text');
    
    if (!$sessionId || !$messageText) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID and message required']);
        return;
    }
    
    // Verify user has access to this session - Super admins have full access
    if (!$hasFullAccess && !$isSuperAdmin) {
        $accessSql = "SELECT session_id FROM chat_participants 
                      WHERE session_id = $sessionId AND user_id = {$currentUser['user_id']} AND is_active = 1";
        $accessResult = mysqli_query($conn, $accessSql);
        
        // Also check if user owns the session
        if (mysqli_num_rows($accessResult) === 0) {
            $ownerSql = "SELECT session_id FROM chat_sessions 
                         WHERE session_id = $sessionId AND user_id = {$currentUser['user_id']}";
            $ownerResult = mysqli_query($conn, $ownerSql);
            
            if (mysqli_num_rows($ownerResult) === 0) {
                // For super admins and agents, automatically add them as participants
                if ($hasFullAccess || $currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin') {
                    $addParticipantSql = "INSERT IGNORE INTO chat_participants (session_id, user_id, role, is_active) 
                                         VALUES ($sessionId, {$currentUser['user_id']}, 'agent', 1)";
                    mysqli_query($conn, $addParticipantSql);
                } else {
                    http_response_code(403);
                    echo json_encode(['error' => 'Access denied to this session']);
                    return;
                }
            }
        }
    }
    
    // Insert message
    $sql = "INSERT INTO chat_messages (session_id, sender_id, message_text, message_type) 
            VALUES ($sessionId, {$currentUser['user_id']}, '$messageText', '$messageType')";
    
    if (mysqli_query($conn, $sql)) {
        $messageId = mysqli_insert_id($conn);
        
        // Update session last activity
        $updateSql = "UPDATE chat_sessions SET last_activity = NOW() WHERE session_id = $sessionId";
        mysqli_query($conn, $updateSql);
        
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
 * Get messages for a chat session
 */
function getMessages() {
    global $conn, $currentUser, $isAgent, $hasFullAccess, $isSuperAdmin;
    
    $sessionId = intval($_GET['session_id'] ?? 0);
    $lastMessageId = intval($_GET['last_message_id'] ?? 0);
    
    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID required']);
        return;
    }
    
    // Verify access to session - Super admins and agents have full access
    if (!$hasFullAccess && !$isAgent && !$isSuperAdmin) {
        // Check if user is participant in the session
        $accessSql = "SELECT session_id FROM chat_participants 
                      WHERE session_id = $sessionId AND user_id = {$currentUser['user_id']} AND is_active = 1";
        $accessResult = mysqli_query($conn, $accessSql);
        
        // Also check if user owns the session
        if (mysqli_num_rows($accessResult) === 0) {
            $ownerSql = "SELECT session_id FROM chat_sessions 
                         WHERE session_id = $sessionId AND user_id = {$currentUser['user_id']}";
            $ownerResult = mysqli_query($conn, $ownerSql);
            
            if (mysqli_num_rows($ownerResult) === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied to this session']);
                return;
            }
        }
    }
    
    // Get messages - Try to join with users table safely
    $sql = "SELECT cm.*, cm.sender_id, cm.message_text, cm.message_type, cm.sent_at 
            FROM chat_messages cm 
            WHERE cm.session_id = $sessionId";
    
    if ($lastMessageId > 0) {
        $sql .= " AND cm.message_id > $lastMessageId";
    }
    
    $sql .= " ORDER BY cm.sent_at ASC";
    
    $result = mysqli_query($conn, $sql);
    $messages = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Set default values for missing user info
        $row['first_name'] = $row['first_name'] ?? '';
        $row['last_name'] = $row['last_name'] ?? '';
        $row['role'] = $row['role'] ?? 'user';
        $messages[] = $row;
    }
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

/**
 * Auto-assign session to available agent
 */
function autoAssignAgent($sessionId) {
    global $conn;
    
    // Find available agent with lowest current chat count
    $sql = "SELECT cas.agent_id 
            FROM chat_agent_status cas 
            WHERE cas.status = 'online' 
            AND cas.auto_assign = 1 
            AND cas.current_chat_count < cas.max_concurrent_chats 
            ORDER BY cas.current_chat_count ASC, cas.last_seen DESC 
            LIMIT 1";
    
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $agentId = $row['agent_id'];
        
        // Assign session to agent
        $assignSql = "UPDATE chat_sessions 
                      SET assigned_agent_id = $agentId, status = 'active' 
                      WHERE session_id = $sessionId";
        mysqli_query($conn, $assignSql);
        
        // Add agent as participant
        $participantSql = "INSERT INTO chat_participants (session_id, user_id, role) 
                          VALUES ($sessionId, $agentId, 'agent')";
        mysqli_query($conn, $participantSql);
        
        // Update agent's current chat count
        $updateCountSql = "UPDATE chat_agent_status 
                          SET current_chat_count = current_chat_count + 1 
                          WHERE agent_id = $agentId";
        mysqli_query($conn, $updateCountSql);
        
        // Send assignment notification message
        $notifyMsg = "An agent has joined the chat and will assist you shortly.";
        $msgSql = "INSERT INTO chat_messages (session_id, sender_id, message_text, message_type) 
                   VALUES ($sessionId, 0, '$notifyMsg', 'system')";
        mysqli_query($conn, $msgSql);
    }
}

/**
 * End a chat session
 */
function endChatSession() {
    global $conn, $currentUser, $hasFullAccess, $isSuperAdmin;

    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = intval($input['session_id'] ?? 0);
    $rating = intval($input['rating'] ?? 0);
    $feedback = mysqli_real_escape_string($conn, $input['feedback'] ?? '');

    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID required']);
        return;
    }

    // Verify access to session - Super admins have full access
    if (!$hasFullAccess && !$isSuperAdmin) {
        $accessSql = "SELECT session_id FROM chat_sessions
                      WHERE session_id = $sessionId AND user_id = {$currentUser['user_id']}";
        $accessResult = mysqli_query($conn, $accessSql);

        if (mysqli_num_rows($accessResult) === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this session']);
            return;
        }
    }

    // Update session status
    $sql = "UPDATE chat_sessions
            SET status = 'ended', ended_at = NOW()";

    if ($rating > 0) {
        $sql .= ", rating = $rating";
    }

    if (!empty($feedback)) {
        $sql .= ", feedback = '$feedback'";
    }

    $sql .= " WHERE session_id = $sessionId";

    if (mysqli_query($conn, $sql)) {
        // Deactivate participants
        $deactivateSql = "UPDATE chat_participants
                         SET is_active = 0, left_at = NOW()
                         WHERE session_id = $sessionId";
        mysqli_query($conn, $deactivateSql);

        // Update agent's current chat count
        $getAgentSql = "SELECT assigned_agent_id FROM chat_sessions WHERE session_id = $sessionId";
        $agentResult = mysqli_query($conn, $getAgentSql);

        if ($agentRow = mysqli_fetch_assoc($agentResult)) {
            $agentId = $agentRow['assigned_agent_id'];
            if ($agentId) {
                $updateCountSql = "UPDATE chat_agent_status
                                  SET current_chat_count = GREATEST(0, current_chat_count - 1)
                                  WHERE agent_id = $agentId";
                mysqli_query($conn, $updateCountSql);
            }
        }

        // Send session ended message
        $endMsg = "Chat session has been ended. Thank you for contacting VVU SRC Support!";
        $msgSql = "INSERT INTO chat_messages (session_id, sender_id, message_text, message_type)
                   VALUES ($sessionId, 0, '$endMsg', 'system')";
        mysqli_query($conn, $msgSql);

        echo json_encode(['success' => true, 'message' => 'Session ended successfully']);
    } else {
        throw new Exception('Failed to end session');
    }
}

/**
 * Get agent's assigned sessions
 */
function getAgentSessions() {
    global $conn, $currentUser;

    $status = mysqli_real_escape_string($conn, $_GET['status'] ?? 'active');

    // For waiting status, get unassigned sessions that can be picked up
    if ($status === 'waiting') {
        $sql = "SELECT cs.*, u.first_name, u.last_name, u.email,
                       0 as unread_count
                FROM chat_sessions cs
                JOIN users u ON cs.user_id = u.user_id
                WHERE cs.status = 'waiting' AND cs.assigned_agent_id IS NULL
                ORDER BY cs.started_at ASC";
    } else {
        $sql = "SELECT cs.*, u.first_name, u.last_name, u.email,
                       (SELECT COUNT(*) FROM chat_messages cm
                        WHERE cm.session_id = cs.session_id AND cm.is_read = 0
                        AND cm.sender_id != {$currentUser['user_id']}) as unread_count
                FROM chat_sessions cs
                JOIN users u ON cs.user_id = u.user_id
                WHERE cs.assigned_agent_id = {$currentUser['user_id']}";

        if ($status !== 'all') {
            $sql .= " AND cs.status = '$status'";
        }

        $sql .= " ORDER BY cs.last_activity DESC";
    }

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    $sessions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sessions[] = $row;
    }

    echo json_encode(['success' => true, 'sessions' => $sessions]);
}

/**
 * Assign session to agent
 */
function assignSession() {
    global $conn, $currentUser;

    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = intval($input['session_id'] ?? 0);
    $agentId = intval($input['agent_id'] ?? $currentUser['user_id']);

    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID required']);
        return;
    }

    // Check if agent is available
    $checkSql = "SELECT current_chat_count, max_concurrent_chats
                 FROM chat_agent_status
                 WHERE agent_id = $agentId AND status IN ('online', 'busy')";
    $checkResult = mysqli_query($conn, $checkSql);

    if ($checkRow = mysqli_fetch_assoc($checkResult)) {
        if ($checkRow['current_chat_count'] >= $checkRow['max_concurrent_chats']) {
            http_response_code(400);
            echo json_encode(['error' => 'Agent has reached maximum concurrent chats']);
            return;
        }
    }

    // Update session assignment
    $sql = "UPDATE chat_sessions
            SET assigned_agent_id = $agentId, status = 'active'
            WHERE session_id = $sessionId";

    if (mysqli_query($conn, $sql)) {
        // Add agent as participant if not already added
        $participantSql = "INSERT IGNORE INTO chat_participants (session_id, user_id, role)
                          VALUES ($sessionId, $agentId, 'agent')";
        mysqli_query($conn, $participantSql);

        // Update agent's current chat count
        $updateCountSql = "UPDATE chat_agent_status
                          SET current_chat_count = current_chat_count + 1
                          WHERE agent_id = $agentId";
        mysqli_query($conn, $updateCountSql);

        echo json_encode(['success' => true, 'message' => 'Session assigned successfully']);
    } else {
        throw new Exception('Failed to assign session');
    }
}

/**
 * Update agent status
 */
function updateAgentStatus() {
    global $conn, $currentUser;

    $input = json_decode(file_get_contents('php://input'), true);
    $status = mysqli_real_escape_string($conn, $input['status'] ?? 'offline');
    $maxChats = intval($input['max_chats'] ?? 5);
    $autoAssign = $input['auto_assign'] ? 1 : 0;

    $sql = "INSERT INTO chat_agent_status (agent_id, status, max_concurrent_chats, auto_assign)
            VALUES ({$currentUser['user_id']}, '$status', $maxChats, $autoAssign)
            ON DUPLICATE KEY UPDATE
            status = '$status',
            max_concurrent_chats = $maxChats,
            auto_assign = $autoAssign,
            last_seen = NOW()";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        throw new Exception('Failed to update status');
    }
}

/**
 * Get quick responses for agents
 */
function getQuickResponses() {
    global $conn;

    $category = mysqli_real_escape_string($conn, $_GET['category'] ?? '');

    $sql = "SELECT * FROM chat_quick_responses WHERE is_active = 1";

    if (!empty($category)) {
        $sql .= " AND category = '$category'";
    }

    $sql .= " ORDER BY category, title";

    $result = mysqli_query($conn, $sql);
    $responses = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $responses[] = $row;
    }

    echo json_encode(['success' => true, 'responses' => $responses]);
}

/**
 * Mark messages as read
 */
function markMessagesRead() {
    global $conn, $currentUser;

    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = intval($input['session_id'] ?? 0);

    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID required']);
        return;
    }

    // Mark messages as read (excluding user's own messages)
    $sql = "UPDATE chat_messages
            SET is_read = 1
            WHERE session_id = $sessionId
            AND sender_id != {$currentUser['user_id']}
            AND is_read = 0";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Messages marked as read']);
    } else {
        throw new Exception('Failed to mark messages as read');
    }
}
?>
