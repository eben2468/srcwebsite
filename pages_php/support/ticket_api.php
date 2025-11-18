<?php
/**
 * Support Ticket API
 * Handles ticket operations for admin responses and management
 */

// Include required files
require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/db_config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Require authentication
try {
    requireLogin();
    $currentUser = getCurrentUser();
    require_once __DIR__ . '/../../includes/auth_functions.php';
    $shouldUseAdminInterface = shouldUseAdminInterface();
    $isAdmin = $shouldUseAdminInterface;
    $isMember = isMember();
    
    // Only allow admin interface access and member access
    if (!$shouldUseAdminInterface && !$isMember) {
        throw new Exception('Access denied. Admin or member privileges required.');
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_tickets':
            handleGetTickets();
            break;
            
        case 'get_ticket':
            handleGetTicket();
            break;
            
        case 'assign_ticket':
            handleAssignTicket();
            break;
            
        case 'update_status':
            handleUpdateStatus();
            break;
            
        case 'add_response':
            handleAddResponse();
            break;
            
        case 'get_responses':
            handleGetResponses();
            break;
            
        case 'get_stats':
            handleGetStats();
            break;
            
        case 'start_chat_from_ticket':
            handleStartChatFromTicket();
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetTickets() {
    global $conn;
    
    // Get filter parameters
    $status = $_GET['status'] ?? 'all';
    $priority = $_GET['priority'] ?? 'all';
    $assigned = $_GET['assigned'] ?? 'all';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    $param_types = '';
    
    if ($status !== 'all') {
        $where_conditions[] = "st.status = ?";
        $params[] = $status;
        $param_types .= 's';
    }
    
    if ($priority !== 'all') {
        $where_conditions[] = "st.priority = ?";
        $params[] = $priority;
        $param_types .= 's';
    }
    
    if ($assigned === 'unassigned') {
        $where_conditions[] = "st.assigned_to IS NULL";
    } elseif ($assigned === 'me') {
        global $currentUser;
        $where_conditions[] = "st.assigned_to = ?";
        $params[] = $currentUser['user_id'];
        $param_types .= 'i';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get tickets
    $sql = "SELECT st.*, 
                   u.first_name, u.last_name, u.email, u.role,
                   assigned_user.first_name as assigned_first_name, 
                   assigned_user.last_name as assigned_last_name,
                   (SELECT COUNT(*) FROM support_ticket_responses str WHERE str.ticket_id = st.ticket_id) as response_count
            FROM support_tickets st
            JOIN users u ON st.user_id = u.user_id
            LEFT JOIN users assigned_user ON st.assigned_to = assigned_user.user_id
            $where_clause
            ORDER BY 
                CASE st.priority 
                    WHEN 'urgent' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    WHEN 'low' THEN 4 
                END,
                st.created_at DESC
            LIMIT ? OFFSET ?";
    
    // Add limit and offset to params
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= 'ii';
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $tickets = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tickets[] = $row;
    }
    
    echo json_encode(['success' => true, 'tickets' => $tickets]);
}

function handleGetTicket() {
    global $conn;
    
    $ticketId = (int)($_GET['ticket_id'] ?? 0);
    if ($ticketId <= 0) {
        throw new Exception('Invalid ticket ID');
    }
    
    // Get ticket details
    $sql = "SELECT st.*, 
                   u.first_name, u.last_name, u.email, u.role, u.phone,
                   assigned_user.first_name as assigned_first_name, 
                   assigned_user.last_name as assigned_last_name
            FROM support_tickets st
            JOIN users u ON st.user_id = u.user_id
            LEFT JOIN users assigned_user ON st.assigned_to = assigned_user.user_id
            WHERE st.ticket_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $ticketId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ticket = mysqli_fetch_assoc($result);
    
    if (!$ticket) {
        throw new Exception('Ticket not found');
    }
    
    echo json_encode(['success' => true, 'ticket' => $ticket]);
}

function handleAssignTicket() {
    global $conn, $currentUser;
    
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $assignedTo = (int)($_POST['assigned_to'] ?? 0);
    
    if ($ticketId <= 0) {
        throw new Exception('Invalid ticket ID');
    }
    
    if ($assignedTo <= 0) {
        throw new Exception('Invalid user ID for assignment');
    }
    
    // Verify the assigned user is admin or member
    $user_check_sql = "SELECT role FROM users WHERE user_id = ?";
    $user_stmt = mysqli_prepare($conn, $user_check_sql);
    mysqli_stmt_bind_param($user_stmt, "i", $assignedTo);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($user_result);
    
    if (!$user || !in_array($user['role'], ['admin', 'member'])) {
        throw new Exception('Can only assign tickets to admin or member users');
    }
    
    // Update ticket assignment
    $sql = "UPDATE support_tickets SET assigned_to = ?, status = 'in_progress', updated_at = NOW() WHERE ticket_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $assignedTo, $ticketId);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Ticket assigned successfully']);
    } else {
        throw new Exception('Failed to assign ticket');
    }
}

function handleUpdateStatus() {
    global $conn;
    
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($ticketId <= 0) {
        throw new Exception('Invalid ticket ID');
    }
    
    $validStatuses = ['open', 'in_progress', 'waiting_response', 'resolved', 'closed'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Invalid status');
    }
    
    $sql = "UPDATE support_tickets SET status = ?, updated_at = NOW()";
    if ($status === 'resolved') {
        $sql .= ", resolved_at = NOW()";
    }
    $sql .= " WHERE ticket_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $ticketId);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Ticket status updated successfully']);
    } else {
        throw new Exception('Failed to update ticket status');
    }
}

function handleAddResponse() {
    global $conn, $currentUser;
    
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $isStaffResponse = true; // Always true for admin/member responses
    
    if ($ticketId <= 0) {
        throw new Exception('Invalid ticket ID');
    }
    
    if (empty($message)) {
        throw new Exception('Response message cannot be empty');
    }
    
    // Add response
    $sql = "INSERT INTO support_ticket_responses (ticket_id, user_id, message, is_staff_response) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisi", $ticketId, $currentUser['user_id'], $message, $isStaffResponse);
    
    if (mysqli_stmt_execute($stmt)) {
        // Update ticket status to waiting_response if it was open
        $updateSql = "UPDATE support_tickets SET status = CASE WHEN status = 'open' THEN 'waiting_response' ELSE status END, updated_at = NOW() WHERE ticket_id = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param($updateStmt, "i", $ticketId);
        mysqli_stmt_execute($updateStmt);
        
        echo json_encode(['success' => true, 'message' => 'Response added successfully']);
    } else {
        throw new Exception('Failed to add response');
    }
}

function handleGetResponses() {
    global $conn;
    
    $ticketId = (int)($_GET['ticket_id'] ?? 0);
    if ($ticketId <= 0) {
        throw new Exception('Invalid ticket ID');
    }
    
    $sql = "SELECT str.*, u.first_name, u.last_name, u.role
            FROM support_ticket_responses str
            JOIN users u ON str.user_id = u.user_id
            WHERE str.ticket_id = ?
            ORDER BY str.created_at ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $ticketId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $responses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $responses[] = $row;
    }
    
    echo json_encode(['success' => true, 'responses' => $responses]);
}

function handleGetStats() {
    global $conn;
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'waiting_response' THEN 1 ELSE 0 END) as waiting_response_count,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
                SUM(CASE WHEN assigned_to IS NULL THEN 1 ELSE 0 END) as unassigned_count,
                SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_count
            FROM support_tickets";
    
    $result = mysqli_query($conn, $sql);
    $stats = mysqli_fetch_assoc($result);
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}

function handleStartChatFromTicket() {
    global $conn, $currentUser;
    
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    if ($ticketId <= 0) {
        throw new Exception('Invalid ticket ID');
    }
    
    // Get ticket details
    $sql = "SELECT user_id, title FROM support_tickets WHERE ticket_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $ticketId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ticket = mysqli_fetch_assoc($result);
    
    if (!$ticket) {
        throw new Exception('Ticket not found');
    }
    
    // Create a new chat session
    $sessionToken = bin2hex(random_bytes(16));
    $subject = "Support for Ticket #" . $ticketId . ": " . $ticket['title'];
    
    $chat_sql = "INSERT INTO chat_sessions (user_id, assigned_agent_id, session_token, status, subject, department, priority) 
                 VALUES (?, ?, ?, 'active', ?, 'general', 'medium')";
    $chat_stmt = mysqli_prepare($conn, $chat_sql);
    mysqli_stmt_bind_param($chat_stmt, "iiss", $ticket['user_id'], $currentUser['user_id'], $sessionToken, $subject);
    
    if (mysqli_stmt_execute($chat_stmt)) {
        $sessionId = mysqli_insert_id($conn);
        
        // Add participants
        $participant_sql = "INSERT INTO chat_participants (session_id, user_id, role) VALUES (?, ?, 'customer'), (?, ?, 'agent')";
        $participant_stmt = mysqli_prepare($conn, $participant_sql);
        mysqli_stmt_bind_param($participant_stmt, "iiii", $sessionId, $ticket['user_id'], $sessionId, $currentUser['user_id']);
        mysqli_stmt_execute($participant_stmt);
        
        // Send initial message
        $initialMessage = "Hello! I'm here to help you with your support ticket #" . $ticketId . ". How can I assist you today?";
        $message_sql = "INSERT INTO chat_messages (session_id, sender_id, message_text) VALUES (?, ?, ?)";
        $message_stmt = mysqli_prepare($conn, $message_sql);
        mysqli_stmt_bind_param($message_stmt, "iis", $sessionId, $currentUser['user_id'], $initialMessage);
        mysqli_stmt_execute($message_stmt);
        
        echo json_encode([
            'success' => true, 
            'session_id' => $sessionId,
            'session_token' => $sessionToken,
            'message' => 'Chat session created successfully'
        ]);
    } else {
        throw new Exception('Failed to create chat session');
    }
}
?>
