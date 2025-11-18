<?php
/**
 * Chat Notifications API
 * Handles real-time notifications for chat system
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

// Require login for all notification endpoints
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

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_notifications':
            if ($method === 'GET') {
                getChatNotifications();
            }
            break;
            
        case 'get_unread_count':
            if ($method === 'GET') {
                getUnreadCount();
            }
            break;
            
        case 'mark_notification_read':
            if ($method === 'POST') {
                markNotificationRead();
            }
            break;
            
        case 'get_agent_stats':
            if ($method === 'GET' && $isAgent) {
                getAgentStats();
            }
            break;
            
        case 'get_online_agents':
            if ($method === 'GET') {
                getOnlineAgents();
            }
            break;
            
        case 'heartbeat':
            if ($method === 'POST' && $isAgent) {
                updateAgentHeartbeat();
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
 * Get chat-related notifications for current user
 */
function getChatNotifications() {
    global $conn, $currentUser, $isAgent;
    
    $notifications = [];
    
    if ($isAgent) {
        // Get notifications for agents (new chat requests, messages, etc.)
        
        // New waiting chat sessions
        $waitingSql = "SELECT COUNT(*) as count FROM chat_sessions 
                       WHERE status = 'waiting' AND assigned_agent_id IS NULL";
        $waitingResult = mysqli_query($conn, $waitingSql);
        $waitingCount = mysqli_fetch_assoc($waitingResult)['count'];
        
        if ($waitingCount > 0) {
            $notifications[] = [
                'id' => 'waiting_chats',
                'type' => 'chat_waiting',
                'title' => 'New Chat Requests',
                'message' => "$waitingCount customer(s) waiting for assistance",
                'count' => $waitingCount,
                'action_url' => 'chat-management.php#waiting',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // Unread messages in assigned chats
        $unreadSql = "SELECT cs.session_id, cs.subject, u.first_name, u.last_name,
                             COUNT(cm.message_id) as unread_count
                      FROM chat_sessions cs
                      JOIN users u ON cs.user_id = u.user_id
                      JOIN chat_messages cm ON cs.session_id = cm.session_id
                      WHERE cs.assigned_agent_id = {$currentUser['user_id']}
                      AND cs.status = 'active'
                      AND cm.sender_id != {$currentUser['user_id']}
                      AND cm.is_read = 0
                      GROUP BY cs.session_id
                      HAVING unread_count > 0";
        
        $unreadResult = mysqli_query($conn, $unreadSql);
        
        while ($row = mysqli_fetch_assoc($unreadResult)) {
            $notifications[] = [
                'id' => 'unread_' . $row['session_id'],
                'type' => 'chat_message',
                'title' => 'New Message',
                'message' => "New message from {$row['first_name']} {$row['last_name']}",
                'count' => $row['unread_count'],
                'action_url' => "live-chat.php?session_id={$row['session_id']}",
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
    } else {
        // Get notifications for regular users
        
        // Check for messages in user's active chat sessions
        $userChatSql = "SELECT cs.session_id, cs.subject,
                               COUNT(cm.message_id) as unread_count
                        FROM chat_sessions cs
                        JOIN chat_messages cm ON cs.session_id = cm.session_id
                        WHERE cs.user_id = {$currentUser['user_id']}
                        AND cs.status IN ('waiting', 'active')
                        AND cm.sender_id != {$currentUser['user_id']}
                        AND cm.is_read = 0
                        GROUP BY cs.session_id
                        HAVING unread_count > 0";
        
        $userChatResult = mysqli_query($conn, $userChatSql);
        
        while ($row = mysqli_fetch_assoc($userChatResult)) {
            $notifications[] = [
                'id' => 'user_chat_' . $row['session_id'],
                'type' => 'chat_message',
                'title' => 'New Support Message',
                'message' => "You have new messages in your support chat",
                'count' => $row['unread_count'],
                'action_url' => "live-chat.php?session_id={$row['session_id']}",
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
}

/**
 * Get total unread count for current user
 */
function getUnreadCount() {
    global $conn, $currentUser, $isAgent;
    
    $totalCount = 0;
    
    if ($isAgent) {
        // Count waiting chats
        $waitingSql = "SELECT COUNT(*) as count FROM chat_sessions 
                       WHERE status = 'waiting' AND assigned_agent_id IS NULL";
        $waitingResult = mysqli_query($conn, $waitingSql);
        $waitingCount = mysqli_fetch_assoc($waitingResult)['count'];
        
        // Count unread messages in assigned chats
        $unreadSql = "SELECT COUNT(*) as count
                      FROM chat_sessions cs
                      JOIN chat_messages cm ON cs.session_id = cm.session_id
                      WHERE cs.assigned_agent_id = {$currentUser['user_id']}
                      AND cs.status = 'active'
                      AND cm.sender_id != {$currentUser['user_id']}
                      AND cm.is_read = 0";
        
        $unreadResult = mysqli_query($conn, $unreadSql);
        $unreadCount = mysqli_fetch_assoc($unreadResult)['count'];
        
        $totalCount = $waitingCount + $unreadCount;
    } else {
        // Count unread messages in user's chats
        $userUnreadSql = "SELECT COUNT(*) as count
                          FROM chat_sessions cs
                          JOIN chat_messages cm ON cs.session_id = cm.session_id
                          WHERE cs.user_id = {$currentUser['user_id']}
                          AND cs.status IN ('waiting', 'active')
                          AND cm.sender_id != {$currentUser['user_id']}
                          AND cm.is_read = 0";
        
        $userUnreadResult = mysqli_query($conn, $userUnreadSql);
        $totalCount = mysqli_fetch_assoc($userUnreadResult)['count'];
    }
    
    echo json_encode(['success' => true, 'unread_count' => $totalCount]);
}

/**
 * Mark notification as read (placeholder for future implementation)
 */
function markNotificationRead() {
    global $conn, $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $notificationId = mysqli_real_escape_string($conn, $input['notification_id'] ?? '');
    
    // For now, just return success
    // In a full implementation, you'd track notification read status
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
}

/**
 * Get agent statistics
 */
function getAgentStats() {
    global $conn, $currentUser;
    
    // Get agent's current stats
    $statsSql = "SELECT 
                    (SELECT COUNT(*) FROM chat_sessions WHERE assigned_agent_id = {$currentUser['user_id']} AND status = 'active') as active_chats,
                    (SELECT COUNT(*) FROM chat_sessions WHERE assigned_agent_id = {$currentUser['user_id']} AND DATE(started_at) = CURDATE()) as today_chats,
                    (SELECT AVG(rating) FROM chat_sessions WHERE assigned_agent_id = {$currentUser['user_id']} AND rating IS NOT NULL) as avg_rating,
                    (SELECT status FROM chat_agent_status WHERE agent_id = {$currentUser['user_id']}) as current_status";
    
    $statsResult = mysqli_query($conn, $statsSql);
    $stats = mysqli_fetch_assoc($statsResult);
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}

/**
 * Get list of online agents
 */
function getOnlineAgents() {
    global $conn;
    
    $sql = "SELECT u.user_id, u.first_name, u.last_name, u.role, cas.status, cas.current_chat_count, cas.max_concurrent_chats
            FROM users u
            JOIN chat_agent_status cas ON u.user_id = cas.agent_id
            WHERE u.role IN ('admin', 'member')
            AND cas.status IN ('online', 'busy', 'away')
            AND cas.last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY cas.status, u.first_name";
    
    $result = mysqli_query($conn, $sql);
    $agents = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $agents[] = $row;
    }
    
    echo json_encode(['success' => true, 'agents' => $agents]);
}

/**
 * Update agent heartbeat (to track online status)
 */
function updateAgentHeartbeat() {
    global $conn, $currentUser;
    
    $sql = "UPDATE chat_agent_status 
            SET last_seen = NOW() 
            WHERE agent_id = {$currentUser['user_id']}";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Heartbeat updated']);
    } else {
        throw new Exception('Failed to update heartbeat');
    }
}
?>
