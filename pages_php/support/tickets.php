<?php
// Include simple authentication and required files
require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/notification_helper.php';
require_once __DIR__ . '/../../includes/db_functions.php';
require_once __DIR__ . '/../../includes/settings_functions.php';

// Check if user should use admin interface or is member
$shouldUseAdminInterface = shouldUseAdminInterface();
$isMember = isMember();

// Require admin interface access or member access for this page
if (!$shouldUseAdminInterface && !$isMember) {
    header('Location: ../../access_denied.php');
    exit();
}

// Check if support feature is enabled
if (!hasFeaturePermission('enable_support')) {
    header('Location: ../../dashboard.php?error=feature_disabled');
    exit();
}

// Get current user info
$currentUser = getCurrentUser();
$isAdmin = $shouldUseAdminInterface;
$isMember = isMember();

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Support Tickets - " . $siteName;
$bodyClass = "page-tickets";

// Include header
require_once '../includes/header.php';

// Define page title, icon, and actions for the modern header
$pageTitle = "Support Tickets";
$pageIcon = "fa-ticket-alt";
$pageDescription = "View and manage your support tickets";
$actions = [
    [
        'url' => 'index.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Support',
        'class' => 'btn-outline-light'
    ],
    [
        'url' => 'help-center.php',
        'icon' => 'fa-question-circle',
        'text' => 'Help Center',
        'class' => 'btn-secondary'
    ]
];

// Include the modern page header
include_once '../includes/modern_page_header.php';

// Handle ticket actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    
    if ($action === 'assign_ticket' && $ticketId > 0) {
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);

        // Verify the assigned user is admin or member
        $user_check_sql = "SELECT user_id, first_name, last_name, role FROM users WHERE user_id = ? AND role IN ('admin', 'member')";
        $user_stmt = mysqli_prepare($conn, $user_check_sql);
        mysqli_stmt_bind_param($user_stmt, "i", $assignedTo);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $assigned_user = mysqli_fetch_assoc($user_result);

        if ($assigned_user) {
            // Get ticket details for notification
            $ticket_sql = "SELECT title, priority FROM support_tickets WHERE ticket_id = ?";
            $ticket_stmt = mysqli_prepare($conn, $ticket_sql);
            mysqli_stmt_bind_param($ticket_stmt, "i", $ticketId);
            mysqli_stmt_execute($ticket_stmt);
            $ticket_result = mysqli_stmt_get_result($ticket_stmt);
            $ticket_info = mysqli_fetch_assoc($ticket_result);

            // Update ticket assignment
            $sql = "UPDATE support_tickets SET assigned_to = ?, status = 'in_progress', updated_at = NOW() WHERE ticket_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $assignedTo, $ticketId);

            if (mysqli_stmt_execute($stmt)) {
                // Create notification for assigned user using helper function
                $assigned_by_name = $currentUser['first_name'] . ' ' . $currentUser['last_name'];
                createTicketAssignmentNotification(
                    $assignedTo,
                    $ticketId,
                    $ticket_info['title'] ?? 'Untitled',
                    $ticket_info['priority'] ?? 'medium',
                    $assigned_by_name
                );

                $success_message = "Ticket assigned to " . htmlspecialchars($assigned_user['first_name'] . ' ' . $assigned_user['last_name']) . " successfully! They have been notified.";
            } else {
                $error_message = "Failed to assign ticket.";
            }
        } else {
            $error_message = "Invalid user selected for assignment. Only admin and member users can be assigned tickets.";
        }
    }

    if ($action === 'unassign_ticket' && $ticketId > 0) {
        $sql = "UPDATE support_tickets SET assigned_to = NULL, status = 'open', updated_at = NOW() WHERE ticket_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $ticketId);

        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Ticket unassigned successfully!";
        } else {
            $error_message = "Failed to unassign ticket.";
        }
    }

    if ($action === 'update_status' && $ticketId > 0) {
        $status = $_POST['status'] ?? '';
        $sql = "UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE ticket_id = ?";
        if ($status === 'resolved') {
            $sql = "UPDATE support_tickets SET status = ?, resolved_at = NOW(), updated_at = NOW() WHERE ticket_id = ?";
        }
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $ticketId);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Ticket status updated successfully!";
        } else {
            $error_message = "Failed to update ticket status.";
        }
    }
    
    if ($action === 'add_response' && $ticketId > 0) {
        $message = trim($_POST['message'] ?? '');
        if (!empty($message)) {
            $sql = "INSERT INTO support_ticket_responses (ticket_id, user_id, message, is_staff_response) VALUES (?, ?, ?, 1)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iis", $ticketId, $currentUser['user_id'], $message);
            
            if (mysqli_stmt_execute($stmt)) {
                // Get ticket and customer information for notification
                $ticket_info_sql = "SELECT st.title, st.user_id, u.first_name, u.last_name
                                   FROM support_tickets st
                                   JOIN users u ON st.user_id = u.user_id
                                   WHERE st.ticket_id = ?";
                $ticket_info_stmt = mysqli_prepare($conn, $ticket_info_sql);
                mysqli_stmt_bind_param($ticket_info_stmt, "i", $ticketId);
                mysqli_stmt_execute($ticket_info_stmt);
                $ticket_info_result = mysqli_stmt_get_result($ticket_info_stmt);
                $ticket_info = mysqli_fetch_assoc($ticket_info_result);

                // Update ticket status to waiting_response if it was open
                $updateSql = "UPDATE support_tickets SET status = CASE WHEN status = 'open' THEN 'waiting_response' ELSE status END, updated_at = NOW() WHERE ticket_id = ?";
                $updateStmt = mysqli_prepare($conn, $updateSql);
                mysqli_stmt_bind_param($updateStmt, "i", $ticketId);
                mysqli_stmt_execute($updateStmt);

                // Notify the ticket creator about the response
                if ($ticket_info && $ticket_info['user_id'] != $currentUser['user_id']) {
                    $responder_name = $currentUser['first_name'] . ' ' . $currentUser['last_name'];
                    createTicketResponseNotification(
                        $ticket_info['user_id'],
                        $ticketId,
                        $ticket_info['title'],
                        $responder_name
                    );
                }

                $success_message = "Response added successfully! The customer has been notified.";
            } else {
                $error_message = "Failed to add response.";
            }
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';
$assigned_filter = $_GET['assigned'] ?? 'all';

// Build WHERE clause for filters
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "st.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($priority_filter !== 'all') {
    $where_conditions[] = "st.priority = ?";
    $params[] = $priority_filter;
    $param_types .= 's';
}

if ($assigned_filter === 'unassigned') {
    $where_conditions[] = "st.assigned_to IS NULL";
} elseif ($assigned_filter === 'me') {
    $where_conditions[] = "st.assigned_to = ?";
    $params[] = $currentUser['user_id'];
    $param_types .= 'i';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get tickets with user information
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
            st.created_at DESC";

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $sql);
}

$tickets = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tickets[] = $row;
}

// Get staff members for assignment dropdown
$staff_sql = "SELECT user_id, first_name, last_name FROM users WHERE role IN ('admin', 'member') ORDER BY first_name, last_name";
$staff_result = mysqli_query($conn, $staff_sql);
$staff_members = [];
while ($row = mysqli_fetch_assoc($staff_result)) {
    $staff_members[] = $row;
}
?>

<style>
.tickets-container {
    padding: 2rem 0;
}



.filter-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.ticket-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border-left: 4px solid #e9ecef;
    transition: all 0.3s ease;
}

.ticket-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.ticket-card.priority-urgent {
    border-left-color: #dc3545;
}

.ticket-card.priority-high {
    border-left-color: #fd7e14;
}

.ticket-card.priority-medium {
    border-left-color: #ffc107;
}

.ticket-card.priority-low {
    border-left-color: #28a745;
}

.ticket-card.status-open {
    background: #fff8e1;
}

.ticket-card.status-in_progress {
    background: #e3f2fd;
}

.ticket-card.status-waiting_response {
    background: #f3e5f5;
}

.ticket-card.status-resolved {
    background: #e8f5e8;
}

.ticket-card.status-closed {
    background: #f5f5f5;
}

.priority-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 500;
}

.priority-urgent {
    background: #f5c6cb;
    color: #721c24;
    animation: pulse 2s infinite;
}

.priority-high {
    background: #ffeaa7;
    color: #856404;
}

.priority-medium {
    background: #fff3cd;
    color: #856404;
}

.priority-low {
    background: #d4edda;
    color: #155724;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-open {
    background: #fff3cd;
    color: #856404;
}

.status-in_progress {
    background: #cce5ff;
    color: #004085;
}

.status-waiting_response {
    background: #e2e3e5;
    color: #383d41;
}

.status-resolved {
    background: #d4edda;
    color: #155724;
}

.status-closed {
    background: #f8d7da;
    color: #721c24;
}

.btn-action {
    border-radius: 20px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
}

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.stats-card {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 1rem;
}

.stats-card h3 {
    margin-bottom: 0.5rem;
}

.stats-card .display-6 {
    font-weight: 700;
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .ticket-card {
        padding: 1rem;
    }

    .filter-card {
        padding: 1rem;
    }
}
</style>

<div class="container-fluid px-4" style="margin-top: 2rem;">

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row mb-4">
        <?php
        $stats_sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                        SUM(CASE WHEN assigned_to IS NULL THEN 1 ELSE 0 END) as unassigned_count
                      FROM support_tickets";
        $stats_result = mysqli_query($conn, $stats_sql);
        $stats = mysqli_fetch_assoc($stats_result);
        ?>
        
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <h6>Total Tickets</h6>
                <div class="display-6"><?php echo $stats['total']; ?></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333;">
                <h6>Open Tickets</h6>
                <div class="display-6"><?php echo $stats['open_count']; ?></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333;">
                <h6>In Progress</h6>
                <div class="display-6"><?php echo $stats['in_progress_count']; ?></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: #333;">
                <h6>Unassigned</h6>
                <div class="display-6"><?php echo $stats['unassigned_count']; ?></div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Tickets</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="waiting_response" <?php echo $status_filter === 'waiting_response' ? 'selected' : ''; ?>>Waiting Response</option>
                    <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="priority" class="form-label">Priority</label>
                <select class="form-select" id="priority" name="priority">
                    <option value="all" <?php echo $priority_filter === 'all' ? 'selected' : ''; ?>>All Priorities</option>
                    <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="assigned" class="form-label">Assignment</label>
                <select class="form-select" id="assigned" name="assigned">
                    <option value="all" <?php echo $assigned_filter === 'all' ? 'selected' : ''; ?>>All Tickets</option>
                    <option value="unassigned" <?php echo $assigned_filter === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                    <option value="me" <?php echo $assigned_filter === 'me' ? 'selected' : ''; ?>>Assigned to Me</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-action me-2">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                <a href="tickets.php" class="btn btn-secondary btn-action">
                    <i class="fas fa-undo me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Tickets List -->
    <div class="row">
        <div class="col-12">
            <?php if (empty($tickets)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No tickets found</h4>
                    <p class="text-muted">No support tickets match your current filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card priority-<?php echo $ticket['priority']; ?> status-<?php echo $ticket['status']; ?>" 
                         data-ticket-id="<?php echo $ticket['ticket_id']; ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex align-items-start mb-2">
                                    <div class="me-3">
                                        <h5 class="mb-1">
                                            <a href="#" class="text-decoration-none" onclick="viewTicket(<?php echo $ticket['ticket_id']; ?>)">
                                                #<?php echo $ticket['ticket_id']; ?> - <?php echo htmlspecialchars($ticket['title']); ?>
                                            </a>
                                        </h5>
                                        <div class="d-flex gap-2 mb-2">
                                            <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                                                <?php echo ucfirst($ticket['priority']); ?>
                                            </span>
                                            <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <p class="text-muted mb-2"><?php echo htmlspecialchars(substr($ticket['description'], 0, 150)) . (strlen($ticket['description']) > 150 ? '...' : ''); ?></p>
                                
                                <div class="row text-muted small">
                                    <div class="col-md-6">
                                        <i class="fas fa-user me-1"></i>
                                        <strong><?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?></strong>
                                        (<?php echo htmlspecialchars($ticket['role']); ?>)
                                    </div>
                                    <div class="col-md-6">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <?php if ($ticket['assigned_first_name']): ?>
                                    <div class="mt-2 text-muted small">
                                        <i class="fas fa-user-check me-1"></i>
                                        Assigned to: <strong><?php echo htmlspecialchars($ticket['assigned_first_name'] . ' ' . $ticket['assigned_last_name']); ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4 text-end">
                                <div class="d-flex flex-column gap-2">
                                    <button class="btn btn-primary btn-action btn-sm" onclick="viewTicket(<?php echo $ticket['ticket_id']; ?>)">
                                        <i class="fas fa-eye me-1"></i>View Details
                                        <?php if ($ticket['response_count'] > 0): ?>
                                            <span class="badge bg-light text-dark ms-1"><?php echo $ticket['response_count']; ?></span>
                                        <?php endif; ?>
                                    </button>
                                    
                                    <?php if (!$ticket['assigned_to']): ?>
                                        <div class="btn-group">
                                            <button class="btn btn-success btn-action btn-sm" onclick="assignToMe(<?php echo $ticket['ticket_id']; ?>)">
                                                <i class="fas fa-user-plus me-1"></i>Assign to Me
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="visually-hidden">Toggle Dropdown</span>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><h6 class="dropdown-header">Assign to Staff Member</h6></li>
                                                <?php foreach ($staff_members as $staff): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="assignToStaff(<?php echo $ticket['ticket_id']; ?>, <?php echo $staff['user_id']; ?>, '<?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>')">
                                                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <div class="btn-group">
                                            <button class="btn btn-outline-secondary btn-sm" disabled>
                                                <i class="fas fa-user-check me-1"></i>Assigned
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="visually-hidden">Reassign</span>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><h6 class="dropdown-header">Reassign to</h6></li>
                                                <?php foreach ($staff_members as $staff): ?>
                                                    <?php if ($staff['user_id'] != $ticket['assigned_to']): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="assignToStaff(<?php echo $ticket['ticket_id']; ?>, <?php echo $staff['user_id']; ?>, '<?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>')">
                                                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-warning" href="#" onclick="unassignTicket(<?php echo $ticket['ticket_id']; ?>)">
                                                        <i class="fas fa-user-times me-2"></i>Unassign Ticket
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-info btn-action btn-sm" onclick="startChat(<?php echo $ticket['user_id']; ?>)">
                                        <i class="fas fa-comments me-1"></i>Start Chat
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Ticket Detail Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketModalLabel">Ticket Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ticketModalBody">
                <!-- Ticket details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewTicket(ticketId) {
    // Show loading state
    document.getElementById('ticketModalBody').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading ticket details...</p></div>';

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('ticketModal'));
    modal.show();

    // Load ticket details via AJAX
    fetch(`ticket_details.php?ticket_id=${ticketId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('ticketModalBody').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading ticket details:', error);
            document.getElementById('ticketModalBody').innerHTML = '<div class="alert alert-danger">Error loading ticket details. Please try again.</div>';
        });
}

function assignToMe(ticketId) {
    if (confirm('Are you sure you want to assign this ticket to yourself?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="assign_ticket">
            <input type="hidden" name="ticket_id" value="${ticketId}">
            <input type="hidden" name="assigned_to" value="<?php echo $currentUser['user_id']; ?>">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function assignToStaff(ticketId, staffId, staffName) {
    if (confirm(`Are you sure you want to assign this ticket to ${staffName}?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="assign_ticket">
            <input type="hidden" name="ticket_id" value="${ticketId}">
            <input type="hidden" name="assigned_to" value="${staffId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function unassignTicket(ticketId) {
    if (confirm('Are you sure you want to unassign this ticket?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="unassign_ticket">
            <input type="hidden" name="ticket_id" value="${ticketId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function updateTicketStatus(ticketId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="ticket_id" value="${ticketId}">
        <input type="hidden" name="status" value="${status}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function addResponse(ticketId) {
    const message = document.getElementById(`response_${ticketId}`).value.trim();
    if (!message) {
        alert('Please enter a response message.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="add_response">
        <input type="hidden" name="ticket_id" value="${ticketId}">
        <input type="hidden" name="message" value="${message}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function startChat(userId) {
    // Open live chat with specific user
    window.open(`live-chat.php?user_id=${userId}`, 'livechat', 'width=800,height=600,scrollbars=yes,resizable=yes');
}

// Auto-refresh tickets every 30 seconds
setInterval(() => {
    // Only refresh if no modal is open
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 30000);
</script>

<?php require_once '../includes/footer.php'; ?>
