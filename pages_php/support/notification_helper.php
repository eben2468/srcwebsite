<?php
/**
 * Notification Helper Functions
 * Handles creation and management of notifications for support system
 */

require_once __DIR__ . '/../../includes/db_config.php';

/**
 * Create a notification for a user
 */
function createNotification($user_id, $title, $message, $type = 'info', $action_url = null) {
    global $conn;
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issss", $user_id, $title, $message, $type, $action_url);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Create ticket assignment notification
 */
function createTicketAssignmentNotification($assigned_user_id, $ticket_id, $ticket_title, $priority = 'medium', $assigned_by_name = 'System') {
    $title = "New Support Ticket Assigned";
    $message = "You have been assigned support ticket #{$ticket_id}: " . substr($ticket_title, 0, 50) . (strlen($ticket_title) > 50 ? '...' : '');
    $message .= " (Assigned by: {$assigned_by_name})";
    
    $type = ($priority === 'urgent') ? 'error' : (($priority === 'high') ? 'warning' : 'info');
    $action_url = "support/tickets.php?ticket_id={$ticket_id}";
    
    return createNotification($assigned_user_id, $title, $message, $type, $action_url);
}

/**
 * Create ticket response notification
 */
function createTicketResponseNotification($user_id, $ticket_id, $ticket_title, $responder_name) {
    $title = "New Response to Your Support Ticket";
    $message = "You have received a new response to ticket #{$ticket_id}: " . substr($ticket_title, 0, 50) . (strlen($ticket_title) > 50 ? '...' : '');
    $message .= " (Response from: {$responder_name})";
    
    $action_url = "support/tickets.php?ticket_id={$ticket_id}";
    
    return createNotification($user_id, $title, $message, 'info', $action_url);
}

/**
 * Create urgent ticket notification for all admin/member users
 */
function createUrgentTicketNotification($ticket_id, $ticket_title, $customer_name) {
    global $conn;
    
    // Get all admin and member users
    $sql = "SELECT user_id FROM users WHERE role IN ('admin', 'member')";
    $result = mysqli_query($conn, $sql);
    
    $title = "Urgent Support Ticket Created";
    $message = "An urgent support ticket has been created: #{$ticket_id} - " . substr($ticket_title, 0, 50) . (strlen($ticket_title) > 50 ? '...' : '');
    $message .= " (Customer: {$customer_name})";
    $action_url = "support/tickets.php?ticket_id={$ticket_id}";
    
    $success_count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        if (createNotification($row['user_id'], $title, $message, 'error', $action_url)) {
            $success_count++;
        }
    }
    
    return $success_count;
}

/**
 * Create chat session notification
 */
function createChatSessionNotification($agent_id, $session_id, $customer_name, $subject = null) {
    $title = "New Live Chat Session";
    $message = "You have been assigned a new live chat session with {$customer_name}";
    if ($subject) {
        $message .= " - Subject: " . substr($subject, 0, 50) . (strlen($subject) > 50 ? '...' : '');
    }
    
    $action_url = "support/chat-management.php?session_id={$session_id}";
    
    return createNotification($agent_id, $title, $message, 'info', $action_url);
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notification_id, $user_id) {
    global $conn;
    
    $sql = "UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Get unread notification count for user
 */
function getUnreadNotificationCount($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    return $row['count'] ?? 0;
}

/**
 * Get recent notifications for user
 */
function getRecentNotifications($user_id, $limit = 10) {
    global $conn;
    
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

/**
 * Delete old notifications (older than 30 days)
 */
function cleanupOldNotifications() {
    global $conn;
    
    $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    return mysqli_query($conn, $sql);
}

/**
 * Create bulk notifications for multiple users
 */
function createBulkNotifications($user_ids, $title, $message, $type = 'info', $action_url = null) {
    global $conn;
    
    if (empty($user_ids)) {
        return 0;
    }
    
    $success_count = 0;
    $sql = "INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    foreach ($user_ids as $user_id) {
        mysqli_stmt_bind_param($stmt, "issss", $user_id, $title, $message, $type, $action_url);
        if (mysqli_stmt_execute($stmt)) {
            $success_count++;
        }
    }
    
    return $success_count;
}

/**
 * Get notification statistics
 */
function getNotificationStats($user_id) {
    global $conn;

    $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN type = 'error' THEN 1 ELSE 0 END) as urgent,
                SUM(CASE WHEN type = 'warning' THEN 1 ELSE 0 END) as warnings,
                SUM(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as recent
            FROM notifications
            WHERE user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($result);
}

/**
 * Generate appropriate action URL for notifications
 */
function generateActionUrl($content_type, $config, $item_id = null) {
    // Determine the correct path prefix based on where the notification is being viewed
    // Check if we're in the support directory or main pages_php directory
    $current_script = $_SERVER['PHP_SELF'];
    $is_in_support_dir = strpos($current_script, '/support/') !== false;

    // Set path prefix based on current location
    $path_prefix = $is_in_support_dir ? '../' : '';

    // If no item ID, return the main page with correct path
    if (!$item_id) {
        return $path_prefix . basename($config['action_url']);
    }

    // Generate specific URLs based on content type with correct paths
    switch ($content_type) {
        case 'news':
            // Check if news-detail.php exists, otherwise use news.php with ID
            if (file_exists(__DIR__ . '/../news-detail.php')) {
                return $path_prefix . "news-detail.php?id=" . $item_id;
            } else {
                return $path_prefix . "news.php?id=" . $item_id;
            }

        case 'events':
            // Check if event-detail.php exists, otherwise use events.php with ID
            if (file_exists(__DIR__ . '/../event-detail.php')) {
                return $path_prefix . "event-detail.php?id=" . $item_id;
            } else {
                return $path_prefix . "events.php?id=" . $item_id;
            }

        case 'elections':
            // Check if election-detail.php exists, otherwise use elections.php with ID
            if (file_exists(__DIR__ . '/../election-detail.php')) {
                return $path_prefix . "election-detail.php?id=" . $item_id;
            } else {
                return $path_prefix . "elections.php?id=" . $item_id;
            }

        case 'gallery':
            // Gallery typically shows all images, but we can highlight the specific one
            return $path_prefix . "gallery.php?highlight=" . $item_id;

        case 'minutes':
            // Check if minutes-detail.php exists, otherwise use minutes.php with ID
            if (file_exists(__DIR__ . '/../minutes-detail.php')) {
                if ($is_in_support_dir) {
                    return "../minutes-detail.php?id=" . $item_id;
                } else {
                    return "minutes-detail.php?id=" . $item_id;
                }
            } else {
                if ($is_in_support_dir) {
                    return "../minutes.php?id=" . $item_id;
                } else {
                    return "minutes.php?id=" . $item_id;
                }
            }

        case 'reports':
            // Check if report-detail.php exists, otherwise use reports.php with ID
            if (file_exists(__DIR__ . '/../report-detail.php')) {
                return $path_prefix . "report-detail.php?id=" . $item_id;
            } else {
                return $path_prefix . "reports.php?id=" . $item_id;
            }

        case 'documents':
            // For documents, link to the documents page with full path
            if ($is_in_support_dir) {
                return "../documents.php";
            } else {
                return "documents.php";
            }

        case 'finance':
            // Check if finance-detail.php exists, otherwise use finance.php with ID
            if (file_exists(__DIR__ . '/../finance-detail.php')) {
                return $path_prefix . "finance-detail.php?id=" . $item_id;
            } else {
                return $path_prefix . "finance.php?transaction_id=" . $item_id;
            }

        case 'welfare':
            // Welfare requests typically show in the main welfare page
            return $path_prefix . "welfare.php?request_id=" . $item_id;

        case 'feedback':
            // Feedback shows in the main feedback page with full path
            if ($is_in_support_dir) {
                return "../feedback.php?feedback_id=" . $item_id;
            } else {
                return "feedback.php?feedback_id=" . $item_id;
            }

        case 'departments':
            // Department detail page
            if (file_exists(__DIR__ . '/../department-detail.php')) {
                return $path_prefix . "department-detail.php?id=" . $item_id;
            } else {
                return $path_prefix . "departments.php?dept_id=" . $item_id;
            }

        case 'senate':
            // Senate main page
            return $path_prefix . "senate.php";

        default:
            // Fallback to main page with ID parameter
            $base_url = $path_prefix . basename($config['action_url']);
            return $base_url . (strpos($base_url, '?') !== false ? '&' : '?') . 'id=' . $item_id;
    }
}

/**
 * Create system-wide notifications for content creation/updates
 */
function createSystemNotification($content_type, $action, $title, $description, $created_by_id, $item_id = null) {
    global $conn;

    // Define notification types and target audiences based on content type
    // Use simple filenames - the generateActionUrl function will handle the correct paths
    $notification_config = [
        'news' => [
            'icon' => 'newspaper',
            'type' => 'info',
            'audience' => 'all', // All users can see news
            'action_url' => 'news.php',
            'detail_url' => 'news-detail.php' // For specific news item
        ],
        'events' => [
            'icon' => 'calendar-alt',
            'type' => 'events',
            'audience' => 'all',
            'action_url' => 'events.php',
            'detail_url' => 'event-detail.php' // For specific event
        ],
        'elections' => [
            'icon' => 'vote-yea',
            'type' => 'info',
            'audience' => 'all',
            'action_url' => 'elections.php',
            'detail_url' => 'election-detail.php' // For specific election
        ],
        'gallery' => [
            'icon' => 'images',
            'type' => 'info',
            'audience' => 'all',
            'action_url' => 'gallery.php',
            'detail_url' => 'gallery.php' // Gallery shows all images
        ],
        'minutes' => [
            'icon' => 'clipboard',
            'type' => 'info',
            'audience' => 'members_admin', // Only members and admins
            'action_url' => 'minutes.php',
            'detail_url' => 'minutes-detail.php' // For specific minutes
        ],
        'reports' => [
            'icon' => 'chart-bar',
            'type' => 'info',
            'audience' => 'members_admin',
            'action_url' => 'reports.php',
            'detail_url' => 'report-detail.php' // For specific report
        ],
        'feedback' => [
            'icon' => 'comment-alt',
            'type' => 'warning', // Changed to warning to make it more noticeable
            'audience' => 'members_admin',
            'action_url' => 'feedback.php',
            'detail_url' => 'feedback.php' // Feedback shows all submissions
        ],
        'documents' => [
            'icon' => 'file-alt',
            'type' => 'info',
            'audience' => 'all',
            'action_url' => 'documents.php',
            'detail_url' => 'documents.php' // For document viewing
        ],
        'welfare' => [
            'icon' => 'heart',
            'type' => 'info',
            'audience' => 'all',
            'action_url' => 'welfare.php',
            'detail_url' => 'welfare.php' // Welfare shows all requests
        ],
        'departments' => [
            'icon' => 'building',
            'type' => 'info',
            'audience' => 'all',
            'action_url' => 'departments.php',
            'detail_url' => 'department-detail.php' // For specific department
        ],
        'senate' => [
            'icon' => 'gavel',
            'type' => 'info',
            'audience' => 'all',
            'action_url' => 'senate.php',
            'detail_url' => 'senate.php' // Senate main page
        ],
        'finance' => [
            'icon' => 'money-bill-wave',
            'type' => 'info',
            'audience' => 'members_admin',
            'action_url' => 'finance.php',
            'detail_url' => 'finance-detail.php' // For specific transaction
        ]
    ];

    if (!isset($notification_config[$content_type])) {
        return false; // Unknown content type
    }

    $config = $notification_config[$content_type];

    // Get target users based on audience
    $target_users = getTargetUsers($config['audience'], $created_by_id);

    if (empty($target_users)) {
        return false;
    }

    // Create notification message
    $action_text = [
        'created' => 'created',
        'updated' => 'updated',
        'deleted' => 'deleted',
        'published' => 'published'
    ];

    $message = sprintf(
        "New %s has been %s: %s",
        ucfirst($content_type),
        $action_text[$action] ?? $action,
        $description
    );

    // Generate appropriate action URL based on content type and item ID
    $action_url = generateActionUrl($content_type, $config, $item_id);

    // Create notifications for all target users
    return createBulkNotifications($target_users, $title, $message, $config['type'], $action_url);
}

/**
 * Get target users based on audience type
 */
function getTargetUsers($audience, $exclude_user_id = null) {
    global $conn;

    $where_conditions = [];
    $params = [];
    $param_types = '';

    // Exclude the user who created the content
    if ($exclude_user_id) {
        $where_conditions[] = "user_id != ?";
        $params[] = $exclude_user_id;
        $param_types .= 'i';
    }

    // Add role-based filtering
    switch ($audience) {
        case 'all':
            // No additional filtering needed
            break;
        case 'members_admin':
            $where_conditions[] = "role IN ('super_admin', 'admin', 'finance', 'member')";
            break;
        case 'admin_only':
            $where_conditions[] = "role = 'admin'";
            break;
        case 'students_only':
            $where_conditions[] = "role = 'student'";
            break;
    }

    // Add active user filter
    $where_conditions[] = "status = 'active'";

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $sql = "SELECT user_id FROM users $where_clause";

    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, $sql);
    }

    $user_ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $user_ids[] = $row['user_id'];
    }

    return $user_ids;
}

/**
 * Create notification for new content creation
 */
function notifyContentCreated($content_type, $title, $description, $created_by_id, $item_id = null) {
    return createSystemNotification($content_type, 'created', $title, $description, $created_by_id, $item_id);
}

/**
 * Create notification for content updates
 */
function notifyContentUpdated($content_type, $title, $description, $updated_by_id, $item_id = null) {
    return createSystemNotification($content_type, 'updated', $title, $description, $updated_by_id, $item_id);
}

/**
 * Create notification for content publishing
 */
function notifyContentPublished($content_type, $title, $description, $published_by_id, $item_id = null) {
    return createSystemNotification($content_type, 'published', $title, $description, $published_by_id, $item_id);
}
?>
