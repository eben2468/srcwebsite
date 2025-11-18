<?php
session_start();

// Include database connection and auth functions
require_once '../../includes/db_config.php';
require_once '../../includes/simple_auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get current user info
$currentUser = getCurrentUser();
if (!$currentUser) {
    echo json_encode(['success' => false, 'error' => 'User data not available']);
    exit;
}

$user_id = $currentUser['user_id'] ?? 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

// Function to create notifications table if it doesn't exist
function createNotificationsTable($conn, $user_id) {
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error', 'system', 'events') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        action_url VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at),
        INDEX idx_type (type)
    )";
    
    if (mysqli_query($conn, $sql)) {
        // Add sample notifications for the current user
        $sampleNotifications = [
            ['Welcome to the System', 'Welcome to the VVU SRC Management System! Get started by exploring the dashboard.', 'success'],
            ['System Maintenance', 'Scheduled maintenance will occur this weekend from 2 AM to 4 AM.', 'warning'],
            ['New Feature Available', 'Check out the new reporting features in the Finance section.', 'info'],
            ['Event Reminder', 'Don\'t forget about the upcoming SRC meeting tomorrow at 3 PM.', 'events'],
            ['Profile Update Required', 'Please update your profile information to ensure accurate records.', 'info']
        ];
        
        foreach ($sampleNotifications as $notification) {
            $insertSql = "INSERT IGNORE INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insertSql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isss", $user_id, $notification[0], $notification[1], $notification[2]);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        return true;
    }
    return false;
}

// Ensure notifications table exists
$table_check = "SHOW TABLES LIKE 'notifications'";
$table_result = mysqli_query($conn, $table_check);

if (!$table_result || mysqli_num_rows($table_result) == 0) {
    createNotificationsTable($conn, $user_id);
}

try {
    // Get notifications for current user
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    $unread_count = 0;
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = $row;
            if (!$row['is_read']) {
                $unread_count++;
            }
        }
    }

    mysqli_stmt_close($stmt);

    // Add support ticket notifications for admin/member users
    require_once '../../includes/auth_functions.php';
    $shouldUseAdminInterface = shouldUseAdminInterface();
    $isAdmin = $shouldUseAdminInterface;
    $isMember = isMember();
    $support_unread = 0;

    if ($shouldUseAdminInterface || $isMember) {
        // Get new unassigned tickets
        $ticket_sql = "SELECT COUNT(*) as new_tickets FROM support_tickets WHERE assigned_to IS NULL AND status = 'open' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
        $ticket_result = mysqli_query($conn, $ticket_sql);
        if ($ticket_result) {
            $ticket_data = mysqli_fetch_assoc($ticket_result);
            $new_tickets = $ticket_data['new_tickets'] ?? 0;

            if ($new_tickets > 0) {
                $notifications[] = [
                    'id' => 'tickets_' . time(),
                    'user_id' => $user_id,
                    'title' => 'New Support Tickets',
                    'message' => "You have {$new_tickets} new unassigned support ticket" . ($new_tickets > 1 ? 's' : '') . " waiting for assignment.",
                    'type' => 'warning',
                    'is_read' => false,
                    'action_url' => 'support/tickets.php?assigned=unassigned',
                    'created_at' => date('Y-m-d H:i:s'),
                    'read_at' => null
                ];
                $support_unread++;
            }
        }

        // Get urgent tickets
        $urgent_sql = "SELECT COUNT(*) as urgent_tickets FROM support_tickets WHERE priority = 'urgent' AND status IN ('open', 'in_progress')";
        $urgent_result = mysqli_query($conn, $urgent_sql);
        if ($urgent_result) {
            $urgent_data = mysqli_fetch_assoc($urgent_result);
            $urgent_tickets = $urgent_data['urgent_tickets'] ?? 0;

            if ($urgent_tickets > 0) {
                $notifications[] = [
                    'id' => 'urgent_' . time(),
                    'user_id' => $user_id,
                    'title' => 'Urgent Support Tickets',
                    'message' => "You have {$urgent_tickets} urgent support ticket" . ($urgent_tickets > 1 ? 's' : '') . " requiring immediate attention.",
                    'type' => 'error',
                    'is_read' => false,
                    'action_url' => 'support/tickets.php?priority=urgent',
                    'created_at' => date('Y-m-d H:i:s'),
                    'read_at' => null
                ];
                $support_unread++;
            }
        }

        // Get tickets assigned to current user that need response
        $assigned_sql = "SELECT COUNT(*) as my_tickets FROM support_tickets WHERE assigned_to = ? AND status = 'waiting_response'";
        $assigned_stmt = mysqli_prepare($conn, $assigned_sql);
        if ($assigned_stmt) {
            mysqli_stmt_bind_param($assigned_stmt, "i", $user_id);
            mysqli_stmt_execute($assigned_stmt);
            $assigned_result = mysqli_stmt_get_result($assigned_stmt);
            if ($assigned_result) {
                $assigned_data = mysqli_fetch_assoc($assigned_result);
                $my_tickets = $assigned_data['my_tickets'] ?? 0;

                if ($my_tickets > 0) {
                    $notifications[] = [
                        'id' => 'assigned_' . time(),
                        'user_id' => $user_id,
                        'title' => 'Tickets Awaiting Your Response',
                        'message' => "You have {$my_tickets} ticket" . ($my_tickets > 1 ? 's' : '') . " assigned to you that are waiting for your response.",
                        'type' => 'info',
                        'is_read' => false,
                        'action_url' => 'support/tickets.php?assigned=me&status=waiting_response',
                        'created_at' => date('Y-m-d H:i:s'),
                        'read_at' => null
                    ];
                    $support_unread++;
                }
            }
            mysqli_stmt_close($assigned_stmt);
        }
    }

    $total_unread = $unread_count + $support_unread;

    // Return JSON response
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $total_unread,
        'total_count' => count($notifications),
        'support_stats' => [
            'new_tickets' => $new_tickets ?? 0,
            'urgent_tickets' => $urgent_tickets ?? 0,
            'my_tickets' => $my_tickets ?? 0
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Close database connection
mysqli_close($conn);
?>
