<?php
// Include authentication and required files
require_once '../includes/simple_auth.php';
require_once '../includes/auth_functions.php';
require_once '../includes/db_config.php';
require_once '../includes/db_functions.php';
require_once '../includes/settings_functions.php';
require_once '../includes/feedback_notifications.php';
require_once 'includes/auto_notifications.php';

// Require login for this page
requireLogin();

// Get current user info
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();
$isStudent = isStudent();
$isSuperAdmin = isSuperAdmin();

// Check if user has permission to manage feedback - use unified admin interface check for super admin users
$canManageFeedback = shouldUseAdminInterface() || $isMember;
if (!$canManageFeedback) {
    header('Location: ../dashboard.php?error=access_denied');
    exit();
}

/**
 * Send notification when feedback receives a response
 */
function notifyFeedbackResponse($feedback_id, $feedback_subject, $response, $responder_name) {
    global $conn;

    // Get feedback details
    $sql = "SELECT user_id, submitter_name, submitter_email FROM feedback WHERE feedback_id = ?";
    $result = fetchAll($sql, [$feedback_id]);

    if (!empty($result)) {
        $feedback = $result[0];

        // If feedback was submitted by a registered user, send notification
        if ($feedback['user_id']) {
            $title = "Feedback Response Received";
            $message = "Your feedback \"" . substr($feedback_subject, 0, 50) . "\" has received a response from " . $responder_name;
            $action_url = "feedback-response.php?feedback_id=" . $feedback_id;

            $notificationSql = "INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?, ?, ?, 'success', ?)";
            $result = insert($notificationSql, [$feedback['user_id'], $title, $message, $action_url]);

            return $result !== false;
        } else {
            // For anonymous feedback, we can't send in-app notifications
            // but we could potentially send email notifications if email is provided
            // This is a placeholder for future email notification implementation
            return true; // Return true to indicate the function executed successfully
        }
    }

    return false;
}

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Process feedback assignment and response
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log all POST data
    error_log("POST request received: " . json_encode($_POST));

    if (isset($_POST['assign_feedback'])) {
        $feedbackId = intval($_POST['feedback_id'] ?? 0);
        $assignedToName = trim($_POST['assigned_to'] ?? '');
        $newStatus = $_POST['new_status'] ?? 'in_progress';

        // Validate required fields
        if ($feedbackId <= 0) {
            $errorMessage = "Invalid feedback ID.";
        } elseif (empty($assignedToName)) {
            $errorMessage = "Please select a member to assign the feedback to.";
        } else {
            // Convert assigned user name to user ID
            $assignedToUserId = null;
            if ($assignedToName !== 'unassigned') {
                $userSql = "SELECT user_id FROM users WHERE CONCAT(first_name, ' ', last_name) = ? LIMIT 1";
                error_log("User lookup - Searching for: '$assignedToName'");

                $userResult = fetchOne($userSql, [$assignedToName]);
                if ($userResult) {
                    $assignedToUserId = $userResult['user_id'];
                    error_log("User found - ID: $assignedToUserId");
                } else {
                    error_log("User not found - Name: '$assignedToName'");

                    // Try to find similar users for debugging
                    $debugSql = "SELECT user_id, first_name, last_name, CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE role IN ('admin', 'member') ORDER BY first_name, last_name";
                    $debugUsers = fetchAll($debugSql);
                    error_log("Available users: " . json_encode($debugUsers));

                    $errorMessage = "Selected user '$assignedToName' not found. Please try again.";
                }
            }
        }

        // If unassigning, set status back to pending and notify previous assignee
        if ($assignedToName === 'unassigned') {
            $newStatus = 'pending';

            // Get current assignment to notify the user being unassigned
            $currentAssignmentSql = "SELECT assigned_to FROM feedback WHERE feedback_id = ?";
            $currentAssignment = fetchOne($currentAssignmentSql, [$feedbackId]);

            if ($currentAssignment && !empty($currentAssignment['assigned_to'])) {
                $previousAssigneeId = $currentAssignment['assigned_to'];

                // Send unassignment notification
                notifyFeedbackUnassignment($feedbackId, $previousAssigneeId, $currentUser['user_id']);
            }
        }

        // Only proceed if we have a valid user ID or it's being unassigned
        if (!isset($errorMessage)) {
            // Store the user ID in assigned_to field (as per database design)
            $assignedToValue = ($assignedToName === 'unassigned') ? null : $assignedToUserId;
            $sql = "UPDATE feedback SET assigned_to = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE feedback_id = ?";

            // Debug logging
            error_log("Assignment Debug - Feedback ID: $feedbackId, Assigned To: " . ($assignedToValue ?? 'NULL') . ", Status: $newStatus");

            $result = update($sql, [$assignedToValue, $newStatus, $feedbackId]);

            if ($result) {
                $successMessage = "Feedback assigned successfully!";
                error_log("Assignment Success - Feedback ID: $feedbackId assigned to user ID: " . ($assignedToUserId ?? 'NULL'));

                // Send notification to assigned user
                if (!empty($assignedToUserId)) {
                    $notificationResult = notifyFeedbackAssignment($feedbackId, $assignedToUserId, $currentUser['user_id']);

                    if ($notificationResult && $notificationResult['notification_sent']) {
                        $successMessage .= " Notification sent to assigned user.";

                        if ($notificationResult['email_sent']) {
                            $successMessage .= " Email notification sent.";
                        }

                        error_log("Feedback assignment notification sent to user ID: $assignedToUserId for feedback ID: $feedbackId");
                    } else {
                        error_log("Failed to send notification to user ID: $assignedToUserId for feedback ID: $feedbackId");
                    }
                }
            } else {
                $errorMessage = "Failed to assign feedback. Please try again.";
                error_log("Assignment Failed - SQL: $sql, Params: " . json_encode([$assignedToValue, $newStatus, $feedbackId]));

                // Check if feedback exists
                $checkSql = "SELECT feedback_id, status, assigned_to FROM feedback WHERE feedback_id = ?";
                $checkResult = fetchOne($checkSql, [$feedbackId]);
                if ($checkResult) {
                    error_log("Feedback exists - Current status: " . $checkResult['status'] . ", Current assigned_to: " . ($checkResult['assigned_to'] ?? 'NULL'));
                } else {
                    error_log("Feedback with ID $feedbackId does not exist");
                    $errorMessage = "Feedback not found. Please refresh the page and try again.";
                }
            }
        }
    }
    
    if (isset($_POST['respond_feedback'])) {
        $feedbackId = $_POST['feedback_id'] ?? 0;
        $response = $_POST['response'] ?? '';
        $newStatus = $_POST['new_status'] ?? 'resolved';
        $sendNotification = isset($_POST['send_notification']);

        // Get feedback details before updating
        $feedbackSql = "SELECT user_id, subject FROM feedback WHERE feedback_id = ?";
        $feedbackResult = fetchAll($feedbackSql, [$feedbackId]);

        $sql = "UPDATE feedback SET resolution = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE feedback_id = ?";
        $result = update($sql, [$response, $newStatus, $feedbackId]);

        if ($result) {
            $successMessage = "Response submitted successfully!";

            // Always send notification to feedback submitter if they are a registered user
            if (!empty($feedbackResult)) {
                $feedback = $feedbackResult[0];
                $responderName = $currentUser['first_name'] . ' ' . $currentUser['last_name'];

                // Check if user wants notification (default to true for registered users)
                if ($sendNotification || !isset($_POST['send_notification'])) {
                    notifyFeedbackResponse($feedbackId, $feedback['subject'], $response, $responderName);
                }
            }
        } else {
            $errorMessage = "Failed to submit response.";
        }
    }
}

// Handle AJAX filter request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'filter_feedback') {
    $statusFilter = $_POST['status'] ?? 'all';
    $typeFilter = $_POST['type'] ?? 'all';
    $assignmentFilter = $_POST['assignment'] ?? 'all';

    // Build the WHERE clause for filtering
    $whereConditions = [];
    $params = [];

    if (!empty($statusFilter) && $statusFilter !== 'all') {
        $whereConditions[] = "f.status = ?";
        $params[] = $statusFilter;
    }

    if (!empty($typeFilter) && $typeFilter !== 'all') {
        $whereConditions[] = "f.type = ?";
        $params[] = $typeFilter;
    }

    if (!empty($assignmentFilter) && $assignmentFilter !== 'all') {
        if ($assignmentFilter === 'assigned') {
            $whereConditions[] = "f.assigned_to IS NOT NULL";
        } elseif ($assignmentFilter === 'unassigned') {
            $whereConditions[] = "f.assigned_to IS NULL";
        }
    }

    // Build the complete SQL query
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    $sql = "SELECT f.*, u.first_name, u.last_name,
                   a.first_name as assigned_first_name, a.last_name as assigned_last_name
            FROM feedback f
            LEFT JOIN users u ON f.user_id = u.user_id
            LEFT JOIN users a ON f.assigned_to = a.user_id
            $whereClause
            ORDER BY f.created_at DESC";

    $filteredFeedback = fetchAll($sql, $params);

    // Calculate filtered statistics
    $filteredStats = [
        'total' => count($filteredFeedback),
        'pending' => count(array_filter($filteredFeedback, fn($f) => $f['status'] === 'pending')),
        'in_progress' => count(array_filter($filteredFeedback, fn($f) => $f['status'] === 'in_progress')),
        'resolved' => count(array_filter($filteredFeedback, fn($f) => $f['status'] === 'resolved'))
    ];

    // Override the original data with filtered data
    $feedback = $filteredFeedback;
    $stats = $filteredStats;
}

// Get feedback data with filters
$statusFilter = $_GET['status'] ?? 'all';
$typeFilter = $_GET['type'] ?? 'all';
$assignedFilter = $_GET['assigned'] ?? 'all';

$whereConditions = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

if ($typeFilter !== 'all') {
    $whereConditions[] = "feedback_type = ?";
    $params[] = $typeFilter;
}

if ($assignedFilter === 'unassigned') {
    $whereConditions[] = "(assigned_to IS NULL OR assigned_to = '')";
} elseif ($assignedFilter === 'assigned') {
    $whereConditions[] = "(assigned_to IS NOT NULL AND assigned_to != '')";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$sql = "SELECT f.*,
               u.first_name, u.last_name, u.email as user_email,
               au.first_name as assigned_first_name, au.last_name as assigned_last_name
        FROM feedback f
        LEFT JOIN users u ON f.user_id = u.user_id
        LEFT JOIN users au ON f.assigned_to = au.user_id
        $whereClause
        ORDER BY f.created_at DESC";

$feedbacks = fetchAll($sql, $params);

// Get all members and admins for assignment dropdown
$membersSql = "SELECT user_id, first_name, last_name, role FROM users WHERE role IN ('admin', 'member') ORDER BY first_name, last_name";
$members = fetchAll($membersSql);

// Debug logging for members
error_log("Members loaded for assignment: " . count($members) . " members found");
if (empty($members)) {
    error_log("No members found - SQL: $membersSql");
} else {
    error_log("Sample members: " . json_encode(array_slice($members, 0, 3)));
}



// Get feedback statistics
$statsSql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN assigned_to IS NULL OR assigned_to = '' THEN 1 ELSE 0 END) as unassigned
    FROM feedback";
$stats = fetchOne($statsSql) ?: ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'unassigned' => 0];

// Get site name from settings
$siteName = getSetting('site_name', 'VVU SRC Management System');
$pageTitle = "Manage Feedback - " . $siteName;

// Include header
require_once 'includes/header.php';
?>

<style>
/* AGGRESSIVE MOBILE FIX - MAXIMUM PRIORITY */
/* Force mobile styles with !important and high specificity */

/* NUCLEAR MOBILE OVERRIDE - HIGHEST PRIORITY */
@media screen and (max-width: 991px) {
    html body .page-wrapper,
    html body div.page-wrapper,
    body .page-wrapper,
    .page-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        position: relative !important;
        left: 0 !important;
        right: 0 !important;
        transform: none !important;
    }

    html body .main-content,
    body .main-content,
    .main-content {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow-x: hidden !important;
    }

    html body .main-content .container-fluid,
    body .main-content .container-fluid,
    .main-content .container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 10px !important;
        overflow-x: hidden !important;
    }
}

@media screen and (max-width: 768px) {
    /* NUCLEAR HEADER FIX - MOBILE HEADER SPACING */
    html body .feedback-header,
    body .feedback-header,
    .feedback-header {
        margin-top: 3px !important; /* Reduced from 10px to match other pages */
        padding: 15px 10px !important;
        border-radius: 8px !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }

    html body .feedback-title,
    body .feedback-title,
    .feedback-title {
        font-size: 1.5rem !important;
        flex-direction: column !important;
        gap: 0.5rem !important;
        text-align: center !important;
    }

    html body .feedback-title i,
    body .feedback-title i,
    .feedback-title i {
        font-size: 1.3rem !important;
    }

    html body .feedback-description,
    body .feedback-description,
    .feedback-description {
        font-size: 0.9rem !important;
        text-align: center !important;
    }

    html body .feedback-header-content,
    body .feedback-header-content,
    .feedback-header-content {
        flex-direction: column !important;
        text-align: center !important;
        gap: 1rem !important;
        width: 100% !important;
    }

    html body .feedback-header-actions,
    body .feedback-header-actions,
    .feedback-header-actions {
        justify-content: center !important;
        width: 100% !important;
        flex-direction: row !important;
    }

    /* NUCLEAR STATS CARDS FIX */
    html body .stat-card,
    body .stat-card,
    .stat-card {
        padding: 12px !important;
        margin-bottom: 10px !important;
        border-radius: 8px !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        gap: 10px !important;
    }

    html body .stat-icon,
    body .stat-icon,
    .stat-icon {
        width: 40px !important;
        height: 40px !important;
        font-size: 1rem !important;
        flex-shrink: 0 !important;
    }

    html body .stat-content h3,
    body .stat-content h3,
    .stat-content h3 {
        font-size: 1.3rem !important;
        margin: 0 !important;
    }

    html body .stat-content p,
    body .stat-content p,
    .stat-content p {
        font-size: 0.8rem !important;
        margin: 0 !important;
    }

    /* NUCLEAR FILTER SECTION FIX */
    html body .filter-section,
    body .filter-section,
    .filter-section {
        padding: 12px !important;
        margin-bottom: 15px !important;
        border-radius: 8px !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }

    html body .filter-section h5,
    body .filter-section h5,
    .filter-section h5 {
        font-size: 1rem !important;
        margin-bottom: 10px !important;
    }

    html body .filter-section .row,
    body .filter-section .row,
    .filter-section .row {
        flex-direction: column !important;
        gap: 10px !important;
        margin: 0 !important;
    }

    html body .filter-section .col-md-3,
    body .filter-section .col-md-3,
    .filter-section .col-md-3 {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin-bottom: 10px !important;
    }

    html body .filter-section .form-select,
    body .filter-section .form-select,
    .filter-section .form-select {
        width: 100% !important;
        padding: 10px !important;
        font-size: 14px !important;
        border-radius: 6px !important;
        min-height: 44px !important;
    }

    html body .filter-section .btn,
    body .filter-section .btn,
    .filter-section .btn {
        width: 100% !important;
        padding: 10px !important;
        font-size: 14px !important;
        min-height: 44px !important;
        border-radius: 6px !important;
    }

    /* NUCLEAR FEEDBACK CARDS MOBILE FIX */
    html body .feedback-card,
    body .feedback-card,
    .feedback-card {
        margin-bottom: 15px !important;
        border-radius: 8px !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    html body .feedback-card .feedback-header,
    body .feedback-card .feedback-header,
    .feedback-card .feedback-header {
        padding: 12px !important;
        margin-top: 0 !important;
        border-radius: 8px 8px 0 0 !important;
    }

    html body .feedback-card .feedback-header h5,
    body .feedback-card .feedback-header h5,
    .feedback-card .feedback-header h5 {
        font-size: 1rem !important;
        margin-bottom: 8px !important;
    }

    html body .feedback-meta,
    body .feedback-meta,
    .feedback-meta {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 8px !important;
        width: 100% !important;
    }

    html body .feedback-meta .text-muted,
    body .feedback-meta .text-muted,
    .feedback-meta .text-muted {
        font-size: 0.8rem !important;
        flex-wrap: wrap !important;
        width: 100% !important;
    }

    html body .feedback-meta .d-flex,
    body .feedback-meta .d-flex,
    .feedback-meta .d-flex {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 4px !important;
        width: 100% !important;
    }

    html body .feedback-card-body,
    body .feedback-card-body,
    .feedback-card-body {
        padding: 12px !important;
    }

    html body .feedback-card-body .row,
    body .feedback-card-body .row,
    .feedback-card-body .row {
        flex-direction: column !important;
        margin: 0 !important;
    }

    html body .feedback-card-body .col-lg-8,
    html body .feedback-card-body .col-lg-4,
    body .feedback-card-body .col-lg-8,
    body .feedback-card-body .col-lg-4,
    .feedback-card-body .col-lg-8,
    .feedback-card-body .col-lg-4 {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin-bottom: 10px !important;
    }

    html body .feedback-content,
    body .feedback-content,
    .feedback-content {
        margin-bottom: 12px !important;
        width: 100% !important;
    }

    html body .feedback-content h6,
    body .feedback-content h6,
    .feedback-content h6 {
        font-size: 0.9rem !important;
        margin-bottom: 8px !important;
    }

    html body .feedback-content p,
    body .feedback-content p,
    .feedback-content p {
        font-size: 0.85rem !important;
        padding: 10px !important;
        line-height: 1.4 !important;
        border-radius: 6px !important;
    }

    html body .feedback-response,
    body .feedback-response,
    .feedback-response {
        padding: 10px !important;
        margin-top: 10px !important;
        border-radius: 0 6px 6px 0 !important;
    }

    html body .feedback-response h6,
    body .feedback-response h6,
    .feedback-response h6 {
        font-size: 0.9rem !important;
        margin-bottom: 8px !important;
    }

    html body .feedback-response p,
    body .feedback-response p,
    .feedback-response p {
        font-size: 0.85rem !important;
        padding: 10px !important;
        border-radius: 6px !important;
    }

    /* NUCLEAR ACTION SECTION MOBILE FIX */
    html body .action-section,
    body .action-section,
    .action-section {
        margin-top: 10px !important;
        padding: 12px !important;
        border-radius: 8px !important;
        width: 100% !important;
    }

    html body .action-section h6,
    body .action-section h6,
    .action-section h6 {
        font-size: 0.9rem !important;
        margin-bottom: 10px !important;
    }

    html body .action-buttons,
    body .action-buttons,
    .action-buttons {
        flex-direction: column !important;
        gap: 8px !important;
        align-items: stretch !important;
        width: 100% !important;
    }

    html body .action-buttons .btn,
    body .action-buttons .btn,
    .action-buttons .btn {
        width: 100% !important;
        min-width: auto !important;
        font-size: 0.8rem !important;
        padding: 10px !important;
        border-radius: 6px !important;
        text-align: center !important;
        min-height: 44px !important;
        margin-bottom: 0 !important;
    }

    html body .status-badge,
    body .status-badge,
    .status-badge {
        padding: 6px 10px !important;
        font-size: 0.7rem !important;
        border-radius: 12px !important;
    }

    html body .assignment-info,
    body .assignment-info,
    .assignment-info {
        padding: 10px !important;
        font-size: 0.8rem !important;
        border-radius: 6px !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 4px !important;
    }
}

/* MOBILE HEADER SPACING FIX - MEDIUM MOBILE */
@media screen and (max-width: 480px) {
    html body .feedback-header,
    body .feedback-header,
    .feedback-header {
        margin-top: 2px !important;
        margin-bottom: 0.6rem !important;
        padding: 1.2rem 0.8rem !important;
    }
}

/* MOBILE HEADER SPACING FIX - SMALL MOBILE */
@media screen and (max-width: 375px) {
    html body .feedback-header,
    body .feedback-header,
    .feedback-header {
        margin-top: 2px !important;
        margin-bottom: 0.5rem !important;
        padding: 1rem 0.6rem !important;
    }
}

/* MOBILE HEADER SPACING FIX - EXTRA SMALL MOBILE */
@media screen and (max-width: 320px) {
    html body .feedback-header,
    body .feedback-header,
    .feedback-header {
        margin-top: 2px !important;
        margin-bottom: 0.5rem !important;
        padding: 0.8rem 0.5rem !important;
    }
}

@media screen and (max-width: 576px) {
    /* ULTRA SMALL MOBILE FIX */
    html body .main-content .container-fluid,
    body .main-content .container-fluid,
    .main-content .container-fluid {
        padding: 0 8px !important;
    }

    html body .feedback-header,
    body .feedback-header,
    .feedback-header {
        margin-top: 2px !important;
        padding: 12px 8px !important;
    }

    html body .feedback-title,
    body .feedback-title,
    .feedback-title {
        font-size: 1.3rem !important;
    }

    html body .stat-card,
    body .stat-card,
    .stat-card {
        padding: 10px !important;
    }

    html body .filter-section,
    body .filter-section,
    .filter-section {
        padding: 10px !important;
    }
}

/* Page Layout Fix for Sidebar */
body {
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

/* Main page wrapper to account for sidebar */
.page-wrapper {
    margin-left: 260px !important; /* Match main system sidebar width */
    padding: 0 !important;
    width: calc(100% - 260px) !important;
    min-height: 100vh;
    transition: margin-left 0.3s ease, width 0.3s ease !important;
    position: relative !important;
}

/* Sidebar toggle responsiveness - match main system behavior */
body .sidebar.collapsed ~ .page-wrapper,
body.sidebar-collapsed .page-wrapper {
    margin-left: 60px !important; /* Collapsed sidebar width */
    width: calc(100% - 60px) !important;
}

/* Force content to fill available space */
.page-wrapper .main-content {
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    padding-top: 45px !important; /* Add proper padding-top for 30px spacing */
}

.page-wrapper .main-content .container-fluid {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 2.5rem !important; /* Increased from 1.5rem to fill more space */
    padding-top: 0 !important; /* Remove any conflicting padding-top */
}

/* Responsive sidebar adjustment */
@media (max-width: 768px) {
    .page-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
    }
}

/* Modern Feedback Header */
.feedback-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    padding: 2.5rem 2rem !important;
    border-radius: 12px !important;
    margin-top: 80px !important; /* Desktop spacing */
    margin-bottom: 1.5rem !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1) !important;
    position: relative !important;
    z-index: 1 !important;
}

/* AGGRESSIVE MOBILE FEEDBACK HEADER FIX - EXACT DASHBOARD SOLUTION */
@media screen and (max-width: 768px) {
    /* Force feedback header positioning for mobile with ID targeting */
    #feedback-header-mobile.feedback-header.animate__animated.animate__fadeInDown,
    #feedback-header-mobile.feedback-header,
    .feedback-header {
        margin-top: 15px !important;
        margin-bottom: 1rem !important;
        padding: 1.5rem 1rem !important;
        position: relative !important;
        z-index: 999 !important;
        top: 0 !important;
        transform: none !important;
    }

    /* Adjust body padding for mobile */
    body {
        padding-top: 70px !important;
    }

    /* Ensure main content starts properly */
    .main-content {
        margin-top: 0 !important;
    }
}

@media screen and (max-width: 480px) {
    #feedback-header-mobile.feedback-header.animate__animated.animate__fadeInDown,
    #feedback-header-mobile.feedback-header,
    .feedback-header {
        margin-top: 12px !important;
        margin-bottom: 0.8rem !important;
        padding: 1.2rem 0.8rem !important;
    }

    body {
        padding-top: 65px !important;
    }
}

@media screen and (max-width: 375px) {
    #feedback-header-mobile.feedback-header.animate__animated.animate__fadeInDown,
    #feedback-header-mobile.feedback-header,
    .feedback-header {
        margin-top: 10px !important;
        margin-bottom: 0.6rem !important;
        padding: 1rem 0.6rem !important;
    }

    body {
        padding-top: 60px !important;
    }
}

@media screen and (max-width: 320px) {
    #feedback-header-mobile.feedback-header.animate__animated.animate__fadeInDown,
    #feedback-header-mobile.feedback-header,
    .feedback-header {
        margin-top: 8px !important;
        margin-bottom: 0.5rem !important;
        padding: 0.8rem 0.5rem !important;
    }

    body {
        padding-top: 55px !important;
    }
}

/* Desktop remains unchanged */
@media screen and (min-width: 769px) {
    .feedback-header {
        margin-top: 80px !important;
    }

    body {
        padding-top: 60px !important;
    }
}

/* ULTRA AGGRESSIVE MOBILE FIX - HIGHEST SPECIFICITY */
@media screen and (max-width: 768px) {
    html body #feedback-header-mobile.feedback-header.animate__animated.animate__fadeInDown,
    html body #feedback-header-mobile.feedback-header,
    html body div.feedback-header {
        margin-top: 15px !important;
        margin-bottom: 1rem !important;
        position: relative !important;
        z-index: 999 !important;
        top: 0 !important;
        transform: none !important;
    }

    /* Force body padding */
    html body {
        padding-top: 70px !important;
    }

    /* Ensure main content starts properly */
    html body .main-content {
        margin-top: 0 !important;
    }
}

/* Force override any conflicting animations */
@media screen and (max-width: 768px) {
    .feedback-header.animate__fadeInDown {
        animation-name: none !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
}

.feedback-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.feedback-header-main {
    flex: 1;
    text-align: center;
}

.feedback-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.feedback-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.feedback-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.feedback-header-actions {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.btn-header-action {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white !important;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
}

.btn-header-action:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-decoration: none;
}

/* Main content area - Aggressive space filling */
.main-content {
    background: #f8f9fa !important;
    min-height: calc(100vh - 60px) !important;
    padding: 0 !important;
    width: 100% !important;
    margin: 0 !important;
    position: relative !important;
}

.main-content .container-fluid {
    padding: 0 1.5rem !important;
    max-width: 100% !important;
    width: 100% !important;
    margin: 0 !important;
    position: relative !important;
}

/* Aggressive content width enforcement */
.feedback-header,
.row,
.filter-section,
.feedback-list,
.feedback-card {
    width: 100% !important;
    max-width: 100% !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

/* Remove any potential left margins or padding */
* {
    box-sizing: border-box !important;
}

.row {
    margin-left: 0 !important;
    margin-right: 0 !important;
}

.col-md-3, .col-sm-6, .col-md-4, .col-lg-8, .col-lg-4 {
    padding-left: 0.5rem !important;
    padding-right: 0.5rem !important;
}

/* AGGRESSIVE LAYOUT FIX - Remove all empty space */
html, body {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    overflow-x: hidden !important;
}

/* Force all containers to use full width */
.container, .container-fluid, .container-sm, .container-md, .container-lg, .container-xl {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding-left: 1.5rem !important;
    padding-right: 1.5rem !important;
}

/* Override Bootstrap grid system margins */
.row {
    --bs-gutter-x: 1rem !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    width: 100% !important;
}

/* Ensure all columns use proper spacing */
[class*="col-"] {
    padding-left: calc(var(--bs-gutter-x) * 0.5) !important;
    padding-right: calc(var(--bs-gutter-x) * 0.5) !important;
}

/* AGGRESSIVE sidebar state handling */
.sidebar.collapsed ~ .page-wrapper,
.sidebar.collapsed + .page-wrapper,
body .sidebar.collapsed ~ .page-wrapper {
    margin-left: 60px !important;
    width: calc(100% - 60px) !important;
    transition: all 0.3s ease !important;
}

/* Force page wrapper positioning */
.page-wrapper {
    margin-left: 260px !important;
    width: calc(100% - 260px) !important;
    transition: margin-left 0.3s ease, width 0.3s ease !important;
    position: relative !important;
}

/* Override any conflicting styles */
.page-wrapper[style*="margin-left"] {
    transition: all 0.3s ease !important;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .page-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
    }

    body .sidebar.collapsed ~ .page-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
    }
}

/* Ensure smooth transitions */
.page-wrapper {
    transition: margin-left 0.3s ease, width 0.3s ease !important;
}

@media (max-width: 768px) {
    .page-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
    }

    .feedback-header {
        margin-top: 70px;
        padding: 1.5rem 1rem;
    }

    .feedback-header-content {
        flex-direction: column;
        text-align: center;
    }

    .feedback-header-actions {
        margin-top: 1rem;
    }

    .main-content .container-fluid {
        padding: 0 1rem !important;
    }
}

/* Enhanced Feedback Cards */
.feedback-card {
    background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 24px;
    box-shadow:
        0 8px 32px rgba(0,0,0,0.08),
        0 2px 8px rgba(0,0,0,0.04),
        inset 0 1px 0 rgba(255,255,255,0.9);
    margin-bottom: 2.5rem;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.8);
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    backdrop-filter: blur(10px);
}

.feedback-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    z-index: 1;
    border-radius: 24px 24px 0 0;
}

.feedback-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);
    z-index: 0;
    pointer-events: none;
    border-radius: 24px;
}

.feedback-card:hover {
    box-shadow:
        0 20px 64px rgba(0,0,0,0.12),
        0 8px 24px rgba(0,0,0,0.08),
        inset 0 1px 0 rgba(255,255,255,0.9);
    transform: translateY(-12px) scale(1.02);
}

.feedback-card .feedback-header {
    padding: 2.5rem 3rem;
    border-bottom: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 70%, #f093fb 100%);
    margin-top: 0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    color: white;
    position: relative;
    z-index: 2;
    overflow: hidden;
}

.feedback-card .feedback-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08) 0%, transparent 50%);
    z-index: -1;
}

.feedback-card .feedback-header h5 {
    color: white !important;
    font-weight: 700;
    margin-bottom: 0.75rem;
    font-size: 1.35rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    letter-spacing: -0.02em;
}

.feedback-card .feedback-header .text-muted {
    color: rgba(255,255,255,0.9) !important;
    font-size: 0.95rem;
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.feedback-card-body {
    padding: 2.5rem 3rem;
    background: white;
    position: relative;
    z-index: 1;
}

.feedback-card-footer {
    padding: 2rem 3rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-top: 1px solid rgba(0,0,0,0.06);
    margin-top: auto;
    position: relative;
    z-index: 1;
}

.assignment-info {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    font-size: 0.95rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    padding: 1rem 1.5rem;
    border-radius: 12px;
    border: 1px solid rgba(102, 126, 234, 0.2);
    font-weight: 500;
}

.assignment-info i {
    font-size: 1.1rem;
    color: #667eea;
}

.assignment-info strong {
    color: #495057;
}

.assignment-info .text-primary {
    color: #667eea !important;
    font-weight: 600;
}

.action-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 20px;
    padding: 2rem;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow:
        0 4px 16px rgba(0,0,0,0.04),
        inset 0 1px 0 rgba(255,255,255,0.8);
    position: relative;
    overflow: hidden;
}

.action-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);
    z-index: 0;
}

.action-section > * {
    position: relative;
    z-index: 1;
}

.action-section h6 {
    color: #495057;
    font-weight: 700;
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.feedback-content {
    margin-bottom: 2rem;
    position: relative;
}

.feedback-content h6 {
    color: #495057;
    font-weight: 700;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.feedback-content p {
    line-height: 1.8;
    color: #495057;
    font-size: 1rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 1.5rem;
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    margin: 0;
}

.feedback-response {
    background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
    border-left: 6px solid #28a745;
    padding: 2rem;
    border-radius: 0 20px 20px 0;
    margin-top: 2rem;
    box-shadow: 0 4px 16px rgba(40, 167, 69, 0.1);
    position: relative;
    overflow: hidden;
}

.feedback-response::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.02) 0%, transparent 100%);
    z-index: 0;
}

.feedback-response > * {
    position: relative;
    z-index: 1;
}

.feedback-response h6 {
    color: #155724 !important;
    margin-bottom: 1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 2px solid rgba(21, 87, 36, 0.2);
    padding-bottom: 0.5rem;
}

.feedback-response p {
    color: #155724;
    margin-bottom: 0;
    background: rgba(255,255,255,0.7);
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid rgba(21, 87, 36, 0.1);
    line-height: 1.8;
}

.feedback-body {
    padding: 1.75rem;
}

.feedback-list {
    display: grid;
    gap: 2.5rem;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.feedback-card {
    animation: slideInCard 0.6s ease-out forwards;
    opacity: 0;
}

.feedback-card:nth-child(1) { animation-delay: 0.1s; }
.feedback-card:nth-child(2) { animation-delay: 0.2s; }
.feedback-card:nth-child(3) { animation-delay: 0.3s; }
.feedback-card:nth-child(4) { animation-delay: 0.4s; }
.feedback-card:nth-child(5) { animation-delay: 0.5s; }

@keyframes slideInCard {
    from {
        opacity: 0;
        transform: translateY(40px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.feedback-card .row {
    margin: 0;
}

.feedback-card .col-lg-8,
.feedback-card .col-lg-4 {
    padding-left: 0;
    padding-right: 1rem;
}

.feedback-card .col-lg-4 {
    padding-right: 0;
}

.status-badge {
    padding: 0.6rem 1.2rem;
    border-radius: 30px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    border: 2px solid transparent;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.status-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 100%);
    z-index: 0;
}

.status-badge i {
    position: relative;
    z-index: 1;
    animation: pulse 2s infinite;
}

.status-badge span {
    position: relative;
    z-index: 1;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.status-pending {
    background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
    color: #2d3436;
    border-color: rgba(253, 203, 110, 0.3);
    box-shadow: 0 4px 16px rgba(253, 203, 110, 0.3);
}

.status-pending:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(253, 203, 110, 0.4);
}

.status-in_progress {
    background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
    color: white;
    border-color: rgba(116, 185, 255, 0.3);
    box-shadow: 0 4px 16px rgba(116, 185, 255, 0.3);
}

.status-in_progress:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(116, 185, 255, 0.4);
}

.status-resolved {
    background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
    color: white;
    border-color: rgba(0, 184, 148, 0.3);
    box-shadow: 0 4px 16px rgba(0, 184, 148, 0.3);
}

.status-resolved:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 184, 148, 0.4);
}

.status-rejected {
    background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
    color: white;
    border-color: rgba(253, 121, 168, 0.3);
    box-shadow: 0 4px 16px rgba(253, 121, 168, 0.3);
}

.status-rejected:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(253, 121, 168, 0.4);
}

.filter-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
    border: 1px solid #e9ecef;
}

.filter-section h5 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.filter-section .form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

.filter-section .form-select {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.filter-section .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    justify-content: flex-start;
}

.action-buttons .btn {
    width: 100%;
    white-space: nowrap;
    border-radius: 16px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
    padding: 1rem 1.5rem;
    border: 2px solid transparent;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    backdrop-filter: blur(10px);
}

.action-buttons .btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.6s ease;
    z-index: 1;
}

.action-buttons .btn:hover::before {
    left: 100%;
}

.action-buttons .btn:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 32px rgba(0,0,0,0.15);
}

.action-buttons .btn:active {
    transform: translateY(-2px) scale(0.98);
    transition: all 0.1s ease;
}

.action-buttons .btn i {
    position: relative;
    z-index: 2;
}

.action-buttons .btn span {
    position: relative;
    z-index: 2;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    color: white;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    border-color: transparent;
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
    border-color: transparent;
    color: white;
    box-shadow: 0 4px 16px rgba(0, 184, 148, 0.3);
}

.btn-success:hover {
    background: linear-gradient(135deg, #00a085 0%, #009073 100%);
    box-shadow: 0 8px 24px rgba(0, 184, 148, 0.4);
    border-color: transparent;
    color: white;
}

.btn-info {
    background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
    border-color: transparent;
    color: white;
    box-shadow: 0 4px 16px rgba(116, 185, 255, 0.3);
}

.btn-info:hover {
    background: linear-gradient(135deg, #5faef7 0%, #0770c4 100%);
    box-shadow: 0 8px 24px rgba(116, 185, 255, 0.4);
    border-color: transparent;
    color: white;
}

.btn-warning {
    background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
    border-color: transparent;
    color: white;
    box-shadow: 0 4px 16px rgba(253, 203, 110, 0.3);
}

.btn-warning:hover {
    background: linear-gradient(135deg, #fcbf49 0%, #d63031 100%);
    box-shadow: 0 8px 24px rgba(253, 203, 110, 0.4);
    border-color: transparent;
    color: white;
}

.feedback-meta {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.5rem;
    margin-bottom: 0;
    flex-wrap: wrap;
}

.feedback-meta .flex-grow-1 {
    flex: 1;
    min-width: 0;
}

.feedback-meta .d-flex {
    align-items: center;
    gap: 1rem;
}

.feedback-meta .text-muted {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    font-size: 0.95rem;
}

.feedback-meta .text-muted .mx-2 {
    color: rgba(255,255,255,0.6);
    font-weight: 300;
}

.feedback-content {
    margin-bottom: 1.5rem;
}

.feedback-content h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.feedback-response {
    background: #f8f9fa;
    border-left: 4px solid #28a745;
    padding: 1rem;
    border-radius: 0 8px 8px 0;
    margin-top: 1rem;
}

.feedback-assignment {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 0.75rem 1rem;
    border-radius: 0 6px 6px 0;
    margin-top: 0.5rem;
    font-size: 0.9rem;
}

.stat-card {
    background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    padding: 2rem;
    box-shadow:
        0 8px 32px rgba(0,0,0,0.08),
        0 2px 8px rgba(0,0,0,0.04),
        inset 0 1px 0 rgba(255,255,255,0.9);
    border: 1px solid rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);
    z-index: 0;
}

.stat-card > * {
    position: relative;
    z-index: 1;
}

.stat-card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow:
        0 16px 48px rgba(0,0,0,0.12),
        0 4px 16px rgba(0,0,0,0.08),
        inset 0 1px 0 rgba(255,255,255,0.9);
}

.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    position: relative;
    overflow: hidden;
}

.stat-icon::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        radial-gradient(circle at 30% 30%, rgba(255,255,255,0.2) 0%, transparent 50%),
        radial-gradient(circle at 70% 70%, rgba(255,255,255,0.1) 0%, transparent 50%);
    z-index: 0;
}

.stat-icon i {
    position: relative;
    z-index: 1;
}

.stat-content h3 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 800;
    color: #495057;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    letter-spacing: -0.02em;
}

.stat-content p {
    margin: 0;
    color: #6c757d;
    font-weight: 600;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Statistics Cards */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: #495057;
}

.stat-content p {
    margin: 0;
    color: #6c757d;
    font-weight: 500;
}

/* COMPREHENSIVE MOBILE RESPONSIVE DESIGN */

/* Tablet and Small Desktop (768px - 991px) */
@media (max-width: 991px) {
    .page-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
    }

    .feedback-header {
        margin-top: 20px !important;
        padding: 1.5rem !important;
    }

    .feedback-header-content {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }

    .feedback-header-actions {
        justify-content: center;
        width: 100%;
    }

    .main-content .container-fluid {
        padding: 0 1rem !important;
    }

    .stat-card {
        margin-bottom: 1rem;
    }

    .filter-section {
        padding: 1.5rem;
    }

    .filter-section .row {
        gap: 1rem;
    }

    .filter-section .col-md-3 {
        margin-bottom: 1rem;
    }
}

/* Mobile Landscape and Portrait (768px and below) */
@media (max-width: 768px) {
    .page-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
    }

    .feedback-header {
        margin-top: 15px !important;
        padding: 1.2rem 1rem !important;
        border-radius: 8px !important;
    }

    .feedback-header-content {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }

    .feedback-header-actions {
        align-items: center;
        flex-direction: row;
        justify-content: center;
        width: 100%;
    }

    .feedback-title {
        font-size: 1.8rem !important;
        flex-direction: column;
        gap: 0.5rem;
    }

    .feedback-title i {
        font-size: 1.5rem !important;
    }

    .feedback-description {
        font-size: 1rem !important;
    }

    .main-content .container-fluid {
        padding: 0 0.8rem !important;
    }

    /* Statistics Cards Mobile */
    .stat-card {
        padding: 1rem !important;
        margin-bottom: 0.8rem;
        border-radius: 12px !important;
    }

    .stat-icon {
        width: 50px !important;
        height: 50px !important;
        font-size: 1.2rem !important;
    }

    .stat-content h3 {
        font-size: 1.5rem !important;
    }

    .stat-content p {
        font-size: 0.85rem !important;
    }

    /* Filter Section Mobile */
    .filter-section {
        padding: 1rem !important;
        margin-bottom: 1.5rem;
        border-radius: 8px !important;
    }

    .filter-section h5 {
        font-size: 1.1rem !important;
        margin-bottom: 1rem !important;
    }

    .filter-section .col-md-3 {
        margin-bottom: 1rem;
        padding: 0 0.5rem;
    }

    .filter-section .form-select {
        padding: 0.6rem 0.8rem !important;
        font-size: 0.9rem !important;
    }

    .filter-section .btn {
        padding: 0.6rem 1rem !important;
        font-size: 0.9rem !important;
    }

    /* Feedback Cards Mobile */
    .feedback-card {
        margin-bottom: 1.5rem !important;
        border-radius: 12px !important;
    }

    .feedback-card .feedback-header {
        padding: 1rem !important;
        border-radius: 12px 12px 0 0 !important;
    }

    .feedback-card .feedback-header h5 {
        font-size: 1.1rem !important;
        margin-bottom: 0.5rem !important;
    }

    .feedback-meta {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.5rem !important;
    }

    .feedback-meta .text-muted {
        font-size: 0.8rem !important;
        flex-wrap: wrap;
    }

    .feedback-meta .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.5rem;
    }

    .feedback-card-body {
        padding: 1rem !important;
    }

    .feedback-card-body .row {
        flex-direction: column;
    }

    .feedback-card-body .col-lg-8,
    .feedback-card-body .col-lg-4 {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
    }

    .feedback-content {
        margin-bottom: 1rem !important;
    }

    .feedback-content h6 {
        font-size: 1rem !important;
        margin-bottom: 0.5rem !important;
    }

    .feedback-content p {
        font-size: 0.9rem !important;
        padding: 1rem !important;
        line-height: 1.5 !important;
    }

    .feedback-response {
        padding: 1rem !important;
        margin-top: 1rem !important;
        border-radius: 0 8px 8px 0 !important;
    }

    .feedback-response h6 {
        font-size: 1rem !important;
    }

    .feedback-response p {
        font-size: 0.9rem !important;
        padding: 1rem !important;
    }

    .feedback-card-footer {
        padding: 1rem !important;
    }

    /* Action Section Mobile */
    .action-section {
        margin-top: 1rem !important;
        padding: 1rem !important;
        border-radius: 12px !important;
    }

    .action-section h6 {
        font-size: 1rem !important;
        margin-bottom: 1rem !important;
    }

    .action-buttons {
        flex-direction: column !important;
        gap: 0.5rem !important;
        align-items: stretch !important;
    }

    .action-buttons .btn {
        width: 100% !important;
        min-width: auto !important;
        font-size: 0.8rem !important;
        padding: 0.7rem 1rem !important;
        border-radius: 8px !important;
        text-align: center !important;
    }

    .status-badge {
        padding: 0.3rem 0.6rem !important;
        font-size: 0.7rem !important;
        border-radius: 12px !important;
    }

    /* Assignment Info Mobile */
    .assignment-info {
        padding: 0.8rem 1rem !important;
        font-size: 0.85rem !important;
        border-radius: 8px !important;
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.3rem !important;
    }
}

/* Small Mobile (576px and below) */
@media (max-width: 576px) {
    .feedback-header {
        margin-top: 10px !important;
        padding: 1rem 0.8rem !important;
    }

    .feedback-title {
        font-size: 1.5rem !important;
    }

    .feedback-title i {
        font-size: 1.3rem !important;
    }

    .feedback-description {
        font-size: 0.9rem !important;
    }

    .btn-header-action {
        padding: 0.5rem 1rem !important;
        font-size: 0.8rem !important;
    }

    .main-content .container-fluid {
        padding: 0 0.5rem !important;
    }

    .stat-card {
        padding: 0.8rem !important;
        gap: 0.8rem !important;
    }

    .stat-icon {
        width: 40px !important;
        height: 40px !important;
        font-size: 1rem !important;
    }

    .stat-content h3 {
        font-size: 1.3rem !important;
    }

    .stat-content p {
        font-size: 0.8rem !important;
    }

    .filter-section {
        padding: 0.8rem !important;
    }

    .filter-section h5 {
        font-size: 1rem !important;
    }

    .filter-section .col-md-3 {
        padding: 0 0.3rem;
    }

    .filter-section .form-select {
        padding: 0.5rem 0.6rem !important;
        font-size: 0.85rem !important;
    }

    .filter-section .btn {
        padding: 0.5rem 0.8rem !important;
        font-size: 0.85rem !important;
    }

    .feedback-card .feedback-header {
        padding: 0.8rem !important;
    }

    .feedback-card .feedback-header h5 {
        font-size: 1rem !important;
    }

    .feedback-meta .text-muted {
        font-size: 0.75rem !important;
    }

    .feedback-card-body {
        padding: 0.8rem !important;
    }

    .feedback-content h6 {
        font-size: 0.9rem !important;
    }

    .feedback-content p {
        font-size: 0.85rem !important;
        padding: 0.8rem !important;
    }

    .feedback-response {
        padding: 0.8rem !important;
    }

    .feedback-response h6 {
        font-size: 0.9rem !important;
    }

    .feedback-response p {
        font-size: 0.85rem !important;
        padding: 0.8rem !important;
    }

    .action-section {
        padding: 0.8rem !important;
    }

    .action-section h6 {
        font-size: 0.9rem !important;
    }

    .action-buttons .btn {
        font-size: 0.75rem !important;
        padding: 0.6rem 0.8rem !important;
    }

    .status-badge {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.65rem !important;
    }

    .assignment-info {
        padding: 0.6rem 0.8rem !important;
        font-size: 0.8rem !important;
    }
}

/* Extra Small Mobile (320px and below) */
@media (max-width: 320px) {
    .main-content .container-fluid {
        padding: 0 0.3rem !important;
    }

    .feedback-header {
        padding: 0.8rem 0.5rem !important;
    }

    .feedback-title {
        font-size: 1.3rem !important;
    }

    .feedback-title i {
        font-size: 1.1rem !important;
    }

    .stat-card {
        padding: 0.6rem !important;
    }

    .stat-icon {
        width: 35px !important;
        height: 35px !important;
        font-size: 0.9rem !important;
    }

    .stat-content h3 {
        font-size: 1.1rem !important;
    }

    .stat-content p {
        font-size: 0.75rem !important;
    }

    .filter-section {
        padding: 0.6rem !important;
    }

    .feedback-card .feedback-header {
        padding: 0.6rem !important;
    }

    .feedback-card-body {
        padding: 0.6rem !important;
    }

    .action-section {
        padding: 0.6rem !important;
    }

    .action-buttons .btn {
        font-size: 0.7rem !important;
        padding: 0.5rem 0.6rem !important;
    }
}

/* MOBILE TOUCH IMPROVEMENTS */
@media (max-width: 768px) {
    /* Improve touch targets */
    .btn, .form-select, .form-control {
        min-height: 44px !important;
        touch-action: manipulation;
    }

    /* Better spacing for touch */
    .action-buttons .btn {
        margin-bottom: 0.5rem;
        min-height: 44px !important;
    }

    /* Improve text readability */
    body {
        -webkit-text-size-adjust: 100%;
        -ms-text-size-adjust: 100%;
    }

    /* Prevent horizontal scroll */
    .feedback-card,
    .filter-section,
    .stat-card {
        max-width: 100% !important;
        overflow-x: hidden !important;
    }

    /* ULTRA AGGRESSIVE MODAL MOBILE FIX */
    .modal {
        padding: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100vw !important;
        max-width: 100vw !important;
    }

    .modal-dialog {
        margin: 0.125rem !important;
        max-width: calc(100vw - 0.25rem) !important;
        width: calc(100vw - 0.25rem) !important;
        transform: none !important;
        position: relative !important;
        left: 0 !important;
        right: 0 !important;
    }

    .modal-dialog.modal-lg {
        margin: 0.125rem !important;
        max-width: calc(100vw - 0.25rem) !important;
        width: calc(100vw - 0.25rem) !important;
    }

    .modal-content {
        border-radius: 8px !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
        margin: 0 !important;
        position: relative !important;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 0.5rem !important;
        overflow-x: hidden !important;
    }

    /* AGGRESSIVE FORM ELEMENTS MOBILE FIX */
    .form-select,
    .form-control {
        font-size: 16px !important; /* Prevents zoom on iOS */
        border-radius: 8px !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow-x: hidden !important;
    }

    .form-select {
        background-size: 16px 12px !important;
        padding-right: 2rem !important;
    }

    .btn {
        font-size: 14px !important;
        padding: 0.5rem 1rem !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* AGGRESSIVE MODAL BUTTON FIX */
    .modal-footer .btn {
        flex: 1 !important;
        margin: 0 0.25rem !important;
        min-width: 0 !important;
    }

    /* Feedback cards mobile optimization */
    .feedback-card {
        margin-bottom: 1rem !important;
        border-radius: 8px !important;
        overflow-x: hidden !important;
    }

    .feedback-card-header {
        padding: 1rem !important;
        overflow-x: hidden !important;
    }

    .feedback-card-body {
        padding: 1rem !important;
        overflow-x: hidden !important;
    }

    .action-buttons .btn {
        margin-bottom: 0.5rem !important;
        font-size: 13px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* Better alert display */
    .alert {
        margin-bottom: 1rem !important;
        border-radius: 8px !important;
        padding: 1rem !important;
    }

    /* Improve focus states for accessibility */
    .btn:focus,
    .form-select:focus,
    .form-control:focus {
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25) !important;
        outline: none !important;
    }
}

/* LANDSCAPE MOBILE SPECIFIC */
@media (max-width: 768px) and (orientation: landscape) {
    .feedback-header {
        padding: 1rem !important;
    }

    .feedback-title {
        font-size: 1.6rem !important;
    }

    .stat-card {
        padding: 0.8rem !important;
    }

    .feedback-card .feedback-header {
        padding: 1rem !important;
    }

    .feedback-card-body {
        padding: 1rem !important;
    }
}

/* Filter Section */
.filter-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
    border: 1px solid #e9ecef;
}

.filter-section h5 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.filter-section .form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

.filter-section .form-select {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.filter-section .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-start;
}

.action-buttons .btn {
    min-width: 120px;
    white-space: nowrap;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-sm:hover {
    transform: translateY(-1px);
}

/* Feedback Meta and Content */
.feedback-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.feedback-content {
    margin-bottom: 1.5rem;
}

.feedback-content h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.feedback-response {
    background: #f8f9fa;
    border-left: 4px solid #28a745;
    padding: 1rem;
    border-radius: 0 8px 8px 0;
    margin-top: 1rem;
}

.feedback-assignment {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 0.75rem 1rem;
    border-radius: 0 6px 6px 0;
    margin-top: 0.5rem;
    font-size: 0.9rem;
}

/* Status Badges */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-in_progress { background: #cce5ff; color: #004085; }
.status-resolved { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }

/* Modal improvements */
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px 12px 0 0;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 0 0 12px 12px;
}

/* NUCLEAR MOBILE MODAL FIX - MAXIMUM CONTAINMENT */
@media screen and (max-width: 768px) {
    /* Force all modals to be contained within viewport */
    html body .modal,
    body .modal,
    .modal {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        max-width: 100vw !important;
        max-height: 100vh !important;
        padding: 0 !important;
        margin: 0 !important;
        z-index: 9999 !important;
        overflow: hidden !important;
        display: none !important;
    }

    /* When modal is shown */
    html body .modal.show,
    body .modal.show,
    .modal.show {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 10px !important;
    }

    /* Modal dialog positioning */
    html body .modal-dialog,
    body .modal-dialog,
    .modal-dialog {
        position: relative !important;
        margin: 0 !important;
        width: calc(100vw - 20px) !important;
        max-width: calc(100vw - 20px) !important;
        height: auto !important;
        max-height: calc(100vh - 20px) !important;
        transform: none !important;
        left: auto !important;
        right: auto !important;
        top: auto !important;
        bottom: auto !important;
        display: flex !important;
        flex-direction: column !important;
    }

    /* Large modal dialogs */
    html body .modal-dialog.modal-lg,
    body .modal-dialog.modal-lg,
    .modal-dialog.modal-lg {
        width: calc(100vw - 20px) !important;
        max-width: calc(100vw - 20px) !important;
    }

    /* Modal content */
    html body .modal-content,
    body .modal-content,
    .modal-content {
        position: relative !important;
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        max-height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border-radius: 8px !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        box-sizing: border-box !important;
    }

    /* Modal header */
    html body .modal-header,
    body .modal-header,
    .modal-header {
        position: relative !important;
        flex-shrink: 0 !important;
        padding: 12px 16px !important;
        overflow: hidden !important;
        border-bottom: 1px solid #dee2e6 !important;
        box-sizing: border-box !important;
    }

    /* Modal body */
    html body .modal-body,
    body .modal-body,
    .modal-body {
        position: relative !important;
        flex: 1 !important;
        padding: 16px !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        max-height: calc(100vh - 140px) !important;
        box-sizing: border-box !important;
    }

    /* Modal footer */
    html body .modal-footer,
    body .modal-footer,
    .modal-footer {
        position: relative !important;
        flex-shrink: 0 !important;
        padding: 12px 16px !important;
        overflow: hidden !important;
        border-top: 1px solid #dee2e6 !important;
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 8px !important;
        box-sizing: border-box !important;
    }

    /* Modal footer buttons */
    html body .modal-footer .btn,
    body .modal-footer .btn,
    .modal-footer .btn {
        flex: 1 !important;
        margin: 0 !important;
        min-width: 0 !important;
        font-size: 14px !important;
        padding: 8px 12px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        box-sizing: border-box !important;
    }

    /* Form elements in modals */
    html body .modal .form-select,
    html body .modal .form-control,
    body .modal .form-select,
    body .modal .form-control,
    .modal .form-select,
    .modal .form-control {
        width: 100% !important;
        max-width: 100% !important;
        font-size: 16px !important; /* Prevent zoom on iOS */
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    /* Modal backdrop */
    html body .modal-backdrop,
    body .modal-backdrop,
    .modal-backdrop {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 9998 !important;
    }

    /* Prevent body scroll when modal is open */
    html body.modal-open,
    body.modal-open {
        overflow: hidden !important;
        position: fixed !important;
        width: 100% !important;
        height: 100% !important;
    }
}

/* FORCE MODAL VIEWPORT CONTAINMENT */
@media (max-width: 768px) {
    body.modal-open {
        overflow-x: hidden !important;
        position: fixed !important;
        width: 100% !important;
    }

    .modal {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        max-width: 100vw !important;
        max-height: 100vh !important;
        padding: 0 !important;
        margin: 0 !important;
        z-index: 1055 !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
    }

    .modal.show {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0.5rem !important;
        left: 0 !important;
        right: 0 !important;
        width: 100vw !important;
        max-width: 100vw !important;
    }

    .modal.show .modal-dialog {
        margin: 0 !important;
        max-width: calc(100vw - 1rem) !important;
        width: calc(100vw - 1rem) !important;
        max-height: calc(100vh - 1rem) !important;
        transform: none !important;
        position: relative !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .modal.show .modal-dialog.modal-lg {
        max-width: calc(100vw - 1rem) !important;
        width: calc(100vw - 1rem) !important;
    }

    .modal.show .modal-content {
        width: 100% !important;
        max-width: 100% !important;
        max-height: 100% !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        box-sizing: border-box !important;
        border-radius: 8px !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .modal-body {
        overflow-x: hidden !important;
        overflow-y: auto !important;
        flex: 1 !important;
        padding: 1rem !important;
    }

    .modal-header {
        flex-shrink: 0 !important;
        padding: 0.75rem 1rem !important;
        overflow-x: hidden !important;
    }

    .modal-footer {
        flex-shrink: 0 !important;
        padding: 0.75rem 1rem !important;
        overflow-x: hidden !important;
        flex-wrap: nowrap !important;
    }

    /* Force form elements to stay contained */
    .modal .form-select,
    .modal .form-control,
    .modal .btn {
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    .modal .form-select {
        background-size: 16px 12px !important;
        padding-right: 2rem !important;
        font-size: 16px !important; /* Prevent zoom on iOS */
    }

    .modal .form-control {
        font-size: 16px !important; /* Prevent zoom on iOS */
    }

    .modal-footer .btn {
        flex: 1 !important;
        margin: 0 0.25rem !important;
        min-width: 0 !important;
        font-size: 0.875rem !important;
        padding: 0.5rem 0.75rem !important;
    }

    /* Ensure modal backdrop doesn't interfere */
    .modal-backdrop {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 1050 !important;
    }
}

/* Extra small mobile devices */
@media (max-width: 576px) {
    .modal.show {
        padding: 0.25rem !important;
    }

    .modal.show .modal-dialog {
        max-width: calc(100vw - 0.5rem) !important;
        width: calc(100vw - 0.5rem) !important;
        max-height: calc(100vh - 0.5rem) !important;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 0.5rem !important;
    }

    .modal-footer .btn {
        font-size: 0.8rem !important;
        padding: 0.375rem 0.5rem !important;
    }
}

/* Very small mobile devices */
@media (max-width: 375px) {
    .modal.show {
        padding: 0.125rem !important;
    }

    .modal.show .modal-dialog {
        max-width: calc(100vw - 0.25rem) !important;
        width: calc(100vw - 0.25rem) !important;
        max-height: calc(100vh - 0.25rem) !important;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 0.375rem !important;
    }

    .modal-title {
        font-size: 1rem !important;
    }

    .modal-footer .btn {
        font-size: 0.75rem !important;
        padding: 0.25rem 0.375rem !important;
    }
}

/* ULTRA COMPACT MOBILE MODAL FIX - MAKE MODALS MUCH SMALLER */
@media screen and (max-width: 768px) {
    /* Force modals to be much smaller and more compact */
    html body .modal.show,
    body .modal.show,
    .modal.show {
        padding: 5px !important;
        align-items: flex-start !important;
        padding-top: 20px !important;
    }

    html body .modal.show .modal-dialog,
    body .modal.show .modal-dialog,
    .modal.show .modal-dialog {
        width: calc(100vw - 10px) !important;
        max-width: calc(100vw - 10px) !important;
        max-height: calc(100vh - 40px) !important;
        margin: 0 !important;
        transform: none !important;
    }

    html body .modal-content,
    body .modal-content,
    .modal-content {
        border-radius: 6px !important;
        max-height: calc(100vh - 40px) !important;
        overflow: hidden !important;
    }

    html body .modal-header,
    body .modal-header,
    .modal-header {
        padding: 8px 12px !important;
        min-height: auto !important;
        border-bottom: 1px solid #dee2e6 !important;
    }

    html body .modal-title,
    body .modal-title,
    .modal-title {
        font-size: 0.9rem !important;
        margin: 0 !important;
        font-weight: 600 !important;
    }

    html body .modal-body,
    body .modal-body,
    .modal-body {
        padding: 12px !important;
        max-height: calc(100vh - 120px) !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
    }

    html body .modal-footer,
    body .modal-footer,
    .modal-footer {
        padding: 8px 12px !important;
        border-top: 1px solid #dee2e6 !important;
        gap: 6px !important;
    }

    html body .modal-footer .btn,
    body .modal-footer .btn,
    .modal-footer .btn {
        font-size: 0.8rem !important;
        padding: 6px 10px !important;
        border-radius: 4px !important;
        flex: 1 !important;
        margin: 0 !important;
        min-width: 0 !important;
    }

    /* Make form elements more compact */
    html body .modal .form-label,
    body .modal .form-label,
    .modal .form-label {
        font-size: 0.85rem !important;
        margin-bottom: 4px !important;
        font-weight: 600 !important;
    }

    html body .modal .form-select,
    html body .modal .form-control,
    html body .modal textarea,
    body .modal .form-select,
    body .modal .form-control,
    body .modal textarea,
    .modal .form-select,
    .modal .form-control,
    .modal textarea {
        font-size: 0.85rem !important;
        padding: 6px 8px !important;
        border-radius: 4px !important;
        min-height: auto !important;
    }

    html body .modal .mb-3,
    body .modal .mb-3,
    .modal .mb-3 {
        margin-bottom: 0.75rem !important;
    }

    /* Compact alert boxes in modals */
    html body .modal .alert,
    body .modal .alert,
    .modal .alert {
        padding: 8px 10px !important;
        font-size: 0.8rem !important;
        margin-bottom: 0.5rem !important;
        border-radius: 4px !important;
    }

    /* Compact tables in modals */
    html body .modal .table td,
    body .modal .table td,
    .modal .table td {
        padding: 4px 6px !important;
        font-size: 0.8rem !important;
        border: none !important;
    }

    /* Close button */
    html body .btn-close,
    body .btn-close,
    .btn-close {
        font-size: 0.8rem !important;
        padding: 4px !important;
        margin: 0 !important;
    }
}

/* Extra compact for very small screens */
@media screen and (max-width: 480px) {
    html body .modal.show,
    body .modal.show,
    .modal.show {
        padding: 3px !important;
        padding-top: 15px !important;
    }

    html body .modal.show .modal-dialog,
    body .modal.show .modal-dialog,
    .modal.show .modal-dialog {
        width: calc(100vw - 6px) !important;
        max-width: calc(100vw - 6px) !important;
        max-height: calc(100vh - 30px) !important;
    }

    html body .modal-header,
    body .modal-header,
    .modal-header {
        padding: 6px 10px !important;
    }

    html body .modal-title,
    body .modal-title,
    .modal-title {
        font-size: 0.85rem !important;
    }

    html body .modal-body,
    body .modal-body,
    .modal-body {
        padding: 10px !important;
        max-height: calc(100vh - 100px) !important;
    }

    html body .modal-footer,
    body .modal-footer,
    .modal-footer {
        padding: 6px 10px !important;
    }

    html body .modal-footer .btn,
    body .modal-footer .btn,
    .modal-footer .btn {
        font-size: 0.75rem !important;
        padding: 5px 8px !important;
    }
}

/* Ultra compact for tiny screens */
@media screen and (max-width: 320px) {
    html body .modal.show,
    body .modal.show,
    .modal.show {
        padding: 2px !important;
        padding-top: 10px !important;
    }

    html body .modal.show .modal-dialog,
    body .modal.show .modal-dialog,
    .modal.show .modal-dialog {
        width: calc(100vw - 4px) !important;
        max-width: calc(100vw - 4px) !important;
        max-height: calc(100vh - 20px) !important;
    }

    html body .modal-header,
    body .modal-header,
    .modal-header {
        padding: 5px 8px !important;
    }

    html body .modal-title,
    body .modal-title,
    .modal-title {
        font-size: 0.8rem !important;
    }

    html body .modal-body,
    body .modal-body,
    .modal-body {
        padding: 8px !important;
        max-height: calc(100vh - 80px) !important;
    }

    html body .modal-footer,
    body .modal-footer,
    .modal-footer {
        padding: 5px 8px !important;
    }

    html body .modal-footer .btn,
    body .modal-footer .btn,
    .modal-footer .btn {
        font-size: 0.7rem !important;
        padding: 4px 6px !important;
    }
}
/* FINAL NUCLEAR OVERRIDE - MAXIMUM PRIORITY */
@media screen and (max-width: 768px) {
    /* Force all modals to be contained - FINAL OVERRIDE */
    .modal[style*="display: block"],
    .modal.show,
    .modal.fade.show {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        max-width: 100vw !important;
        max-height: 100vh !important;
        margin: 0 !important;
        padding: 5px !important;
        padding-top: 20px !important;
        z-index: 9999 !important;
        display: flex !important;
        align-items: flex-start !important;
        justify-content: center !important;
        overflow: hidden !important;
        transform: none !important;
    }

    /* Force modal dialog - FINAL OVERRIDE - COMPACT */
    .modal.show .modal-dialog,
    .modal[style*="display: block"] .modal-dialog {
        position: relative !important;
        margin: 0 !important;
        width: calc(100vw - 10px) !important;
        max-width: calc(100vw - 10px) !important;
        height: auto !important;
        max-height: calc(100vh - 40px) !important;
        transform: none !important;
        left: auto !important;
        right: auto !important;
        top: auto !important;
        bottom: auto !important;
    }

    /* Force modal content - FINAL OVERRIDE - COMPACT */
    .modal.show .modal-content,
    .modal[style*="display: block"] .modal-content {
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        max-height: calc(100vh - 40px) !important;
        overflow: hidden !important;
        border-radius: 6px !important;
        display: flex !important;
        flex-direction: column !important;
    }

    /* Force modal body - FINAL OVERRIDE - COMPACT */
    .modal.show .modal-body,
    .modal[style*="display: block"] .modal-body {
        overflow-x: hidden !important;
        overflow-y: auto !important;
        max-height: calc(100vh - 120px) !important;
        flex: 1 !important;
        padding: 12px !important;
    }

    /* FINAL COMPACT MODAL OVERRIDE - ABSOLUTE PRIORITY */
    html body .modal-header,
    body .modal-header,
    .modal-header {
        padding: 8px 12px !important;
        min-height: auto !important;
    }

    html body .modal-footer,
    body .modal-footer,
    .modal-footer {
        padding: 8px 12px !important;
        gap: 6px !important;
    }

    html body .modal-footer .btn,
    body .modal-footer .btn,
    .modal-footer .btn {
        font-size: 0.8rem !important;
        padding: 6px 10px !important;
        border-radius: 4px !important;
    }

    html body .modal-title,
    body .modal-title,
    .modal-title {
        font-size: 0.9rem !important;
        font-weight: 600 !important;
        margin: 0 !important;
    }

    html body .modal .form-label,
    body .modal .form-label,
    .modal .form-label {
        font-size: 0.85rem !important;
        margin-bottom: 4px !important;
    }

    html body .modal .form-select,
    html body .modal .form-control,
    html body .modal textarea,
    body .modal .form-select,
    body .modal .form-control,
    body .modal textarea,
    .modal .form-select,
    .modal .form-control,
    .modal textarea {
        font-size: 0.85rem !important;
        padding: 6px 8px !important;
        border-radius: 4px !important;
        min-height: auto !important;
        height: auto !important;
    }

    /* COMPACT DROPDOWN STYLES FOR MOBILE */
    html body .modal .form-select,
    body .modal .form-select,
    .modal .form-select {
        background-size: 12px 8px !important;
        padding-right: 24px !important;
        line-height: 1.2 !important;
        max-height: 200px !important;
    }

    /* Dropdown options styling */
    html body .modal .form-select option,
    body .modal .form-select option,
    .modal .form-select option {
        font-size: 0.8rem !important;
        padding: 4px 6px !important;
        line-height: 1.3 !important;
    }

    /* Compact margin for form groups */
    html body .modal .mb-3,
    body .modal .mb-3,
    .modal .mb-3 {
        margin-bottom: 0.5rem !important;
    }

    /* Compact alert boxes */
    html body .modal .alert,
    body .modal .alert,
    .modal .alert {
        padding: 6px 8px !important;
        font-size: 0.8rem !important;
        margin-bottom: 0.5rem !important;
        border-radius: 4px !important;
        line-height: 1.3 !important;
    }
}

/* EXTRA COMPACT DROPDOWN FOR VERY SMALL SCREENS */
@media screen and (max-width: 480px) {
    html body .modal .form-select,
    body .modal .form-select,
    .modal .form-select {
        font-size: 0.8rem !important;
        padding: 5px 6px !important;
        padding-right: 20px !important;
        background-size: 10px 6px !important;
        max-height: 150px !important;
    }

    html body .modal .form-select option,
    body .modal .form-select option,
    .modal .form-select option {
        font-size: 0.75rem !important;
        padding: 3px 5px !important;
    }

    html body .modal .form-label,
    body .modal .form-label,
    .modal .form-label {
        font-size: 0.8rem !important;
        margin-bottom: 3px !important;
    }
}

/* ULTRA COMPACT DROPDOWN FOR TINY SCREENS */
@media screen and (max-width: 320px) {
    html body .modal .form-select,
    body .modal .form-select,
    .modal .form-select {
        font-size: 0.75rem !important;
        padding: 4px 5px !important;
        padding-right: 18px !important;
        background-size: 8px 5px !important;
        max-height: 120px !important;
    }

    html body .modal .form-select option,
    body .modal .form-select option,
    .modal .form-select option {
        font-size: 0.7rem !important;
        padding: 2px 4px !important;
    }

    html body .modal .form-label,
    body .modal .form-label,
    .modal .form-label {
        font-size: 0.75rem !important;
        margin-bottom: 2px !important;
    }
}

/* AGGRESSIVE DROPDOWN OPTIONS LIST WIDTH REDUCTION - 50% SMALLER */
@media screen and (max-width: 768px) {
    /* Target the actual dropdown options container that appears when opened */

    /* Native HTML select dropdown options container - AGGRESSIVE */
    html body select:focus,
    html body select[size],
    html body select[multiple],
    html body select:active,
    body select:focus,
    body select[size],
    body select[multiple],
    body select:active,
    select:focus,
    select[size],
    select[multiple],
    select:active {
        width: 50% !important;
        max-width: 50% !important;
        min-width: 50% !important;
    }

    /* Form select elements when opened */
    html body .form-select:focus,
    html body .form-select:active,
    html body .form-select[size],
    body .form-select:focus,
    body .form-select:active,
    body .form-select[size],
    .form-select:focus,
    .form-select:active,
    .form-select[size] {
        width: 50% !important;
        max-width: 50% !important;
        min-width: 50% !important;
    }

    /* Bootstrap select dropdown options */
    html body .form-select option,
    body .form-select option,
    .form-select option {
        width: 50% !important;
        max-width: 50% !important;
    }

    /* Custom dropdown containers and option lists */
    html body .dropdown-menu,
    body .dropdown-menu,
    .dropdown-menu {
        width: 50% !important;
        max-width: 50% !important;
        min-width: auto !important;
    }

    /* Select2 dropdown containers if used */
    html body .select2-container,
    html body .select2-dropdown,
    body .select2-container,
    body .select2-dropdown,
    .select2-container,
    .select2-dropdown {
        width: 50% !important;
        max-width: 50% !important;
    }

    /* Modal dropdown input fields - FULL WIDTH BY DEFAULT */
    html body .modal select,
    html body .modal .form-select,
    body .modal select,
    body .modal .form-select,
    .modal select,
    .modal .form-select {
        width: 100% !important;
        max-width: 100% !important;
        min-width: auto !important;
    }

    /* Modal specific dropdown targeting - ONLY WHEN FOCUSED/OPENED */
    html body .modal select:focus,
    html body .modal .form-select:focus,
    body .modal select:focus,
    body .modal .form-select:focus,
    .modal select:focus,
    .modal .form-select:focus {
        width: 50% !important;
        max-width: 50% !important;
        min-width: 50% !important;
    }

    /* Filter section specific dropdown targeting */
    html body .filter-section select:focus,
    html body .filter-section .form-select:focus,
    body .filter-section select:focus,
    body .filter-section .form-select:focus,
    .filter-section select:focus,
    .filter-section .form-select:focus {
        width: 50% !important;
        max-width: 50% !important;
        min-width: 50% !important;
    }

    /* Datalist dropdown containers */
    html body datalist,
    body datalist,
    datalist {
        width: 50% !important;
        max-width: 50% !important;
    }

    /* FILTER SECTION INPUT FIELDS - FULL WIDTH */
    html body .filter-section select,
    html body .filter-section .form-select,
    html body .filter-section .form-control,
    body .filter-section select,
    body .filter-section .form-select,
    body .filter-section .form-control,
    .filter-section select,
    .filter-section .form-select,
    .filter-section .form-control {
        width: 100% !important;
        max-width: 100% !important;
        min-width: auto !important;
    }

    /* Filter section dropdowns when opened/focused */
    html body .filter-section select:focus,
    html body .filter-section select:active,
    html body .filter-section select[size],
    html body .filter-section .form-select:focus,
    html body .filter-section .form-select:active,
    html body .filter-section .form-select[size],
    body .filter-section select:focus,
    body .filter-section select:active,
    body .filter-section select[size],
    body .filter-section .form-select:focus,
    body .filter-section .form-select:active,
    body .filter-section .form-select[size],
    .filter-section select:focus,
    .filter-section select:active,
    .filter-section select[size],
    .filter-section .form-select:focus,
    .filter-section .form-select:active,
    .filter-section .form-select[size] {
        width: 50% !important;
        max-width: 50% !important;
        min-width: 50% !important;
    }

    /* Filter section column targeting - FULL WIDTH INPUT FIELDS */
    html body .filter-section .col-md-3 select,
    html body .filter-section .col-md-3 .form-select,
    body .filter-section .col-md-3 select,
    body .filter-section .col-md-3 .form-select,
    .filter-section .col-md-3 select,
    .filter-section .col-md-3 .form-select {
        width: 100% !important;
        max-width: 100% !important;
        min-width: auto !important;
    }
}

/* NUCLEAR FILTER SECTION DROPDOWN WIDTH REDUCTION */
@media screen and (max-width: 768px) {
    /* Ultra high specificity targeting for filter section - FULL WIDTH INPUT FIELDS */
    html body div.filter-section select,
    html body div.filter-section .form-select,
    html body .container .filter-section select,
    html body .container .filter-section .form-select,
    html body .row .filter-section select,
    html body .row .filter-section .form-select,
    body div.filter-section select,
    body div.filter-section .form-select,
    body .container .filter-section select,
    body .container .filter-section .form-select,
    body .row .filter-section select,
    body .row .filter-section .form-select,
    div.filter-section select,
    div.filter-section .form-select,
    .container .filter-section select,
    .container .filter-section .form-select,
    .row .filter-section select,
    .row .filter-section .form-select {
        width: 100% !important;
        max-width: 100% !important;
        min-width: auto !important;
        flex: auto !important;
    }

    /* Target filter section by class combinations - FULL WIDTH INPUT FIELDS */
    html body .filter-section.mb-4 select,
    html body .filter-section.mb-4 .form-select,
    body .filter-section.mb-4 select,
    body .filter-section.mb-4 .form-select,
    .filter-section.mb-4 select,
    .filter-section.mb-4 .form-select {
        width: 100% !important;
        max-width: 100% !important;
        min-width: auto !important;
    }
}

/* EXTRA COMPACT DROPDOWN OPTIONS WIDTH FOR SMALLER SCREENS */
@media screen and (max-width: 480px) {
    html body select:focus,
    html body select[size],
    html body .form-select:focus,
    html body .dropdown-menu,
    html body .form-select option,
    body select:focus,
    body select[size],
    body .form-select:focus,
    body .dropdown-menu,
    body .form-select option,
    select:focus,
    select[size],
    .form-select:focus,
    .dropdown-menu,
    .form-select option {
        width: 45% !important;
        max-width: 45% !important;
        min-width: 45% !important;
    }
}

/* ULTRA COMPACT DROPDOWN OPTIONS WIDTH FOR TINY SCREENS */
@media screen and (max-width: 320px) {
    html body select:focus,
    html body select[size],
    html body .form-select:focus,
    html body .dropdown-menu,
    html body .form-select option,
    body select:focus,
    body select[size],
    body .form-select:focus,
    body .dropdown-menu,
    body .form-select option,
    select:focus,
    select[size],
    .form-select:focus,
    .dropdown-menu,
    .form-select option {
        width: 40% !important;
        max-width: 40% !important;
        min-width: 40% !important;
    }
}
</style>

<!-- Force cache refresh for mobile styles -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<!-- Additional viewport meta for mobile modal fix -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<!-- AGGRESSIVE MOBILE HEADER FIX - INLINE STYLES (EXACT DASHBOARD SOLUTION) -->
<style>
@media screen and (max-width: 768px) {
    #feedback-header-mobile {
        margin-top: 15px !important;
        margin-bottom: 1rem !important;
        position: relative !important;
        z-index: 999 !important;
        top: 0 !important;
        transform: none !important;
    }
    body { padding-top: 70px !important; }
}
@media screen and (max-width: 480px) {
    #feedback-header-mobile { margin-top: 12px !important; }
    body { padding-top: 65px !important; }
}
@media screen and (max-width: 375px) {
    #feedback-header-mobile { margin-top: 10px !important; }
    body { padding-top: 60px !important; }
}
@media screen and (max-width: 320px) {
    #feedback-header-mobile { margin-top: 8px !important; }
    body { padding-top: 55px !important; }
}
</style>

<script>
// AGGRESSIVE MOBILE LAYOUT ENFORCEMENT
document.addEventListener('DOMContentLoaded', function() {
    function forceMobileLayout() {
        if (window.innerWidth <= 991) {
            // Force page wrapper mobile styles
            const pageWrapper = document.querySelector('.page-wrapper');
            if (pageWrapper) {
                pageWrapper.style.cssText = `
                    margin-left: 0 !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    padding: 0 !important;
                    position: relative !important;
                    left: 0 !important;
                    right: 0 !important;
                    transform: none !important;
                `;
            }

            // Force main content mobile styles
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.style.cssText = `
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    overflow-x: hidden !important;
                `;
            }

            // Force container fluid mobile styles
            const containerFluid = document.querySelector('.main-content .container-fluid');
            if (containerFluid) {
                containerFluid.style.cssText = `
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 ${window.innerWidth <= 576 ? '8px' : '10px'} !important;
                    overflow-x: hidden !important;
                `;
            }
        }

        if (window.innerWidth <= 768) {
            // Force feedback header mobile styles with proper spacing
            const feedbackHeader = document.querySelector('.feedback-header');
            if (feedbackHeader) {
                let marginTop = '3px';
                let marginBottom = '0.8rem';
                let padding = '15px 10px';

                if (window.innerWidth <= 576) {
                    marginTop = '2px';
                    marginBottom = '0.5rem';
                    padding = '12px 8px';
                } else if (window.innerWidth <= 480) {
                    marginTop = '2px';
                    marginBottom = '0.6rem';
                    padding = '1.2rem 0.8rem';
                } else if (window.innerWidth <= 375) {
                    marginTop = '2px';
                    marginBottom = '0.5rem';
                    padding = '1rem 0.6rem';
                } else if (window.innerWidth <= 320) {
                    marginTop = '2px';
                    marginBottom = '0.5rem';
                    padding = '0.8rem 0.5rem';
                }

                feedbackHeader.style.cssText = `
                    margin-top: ${marginTop} !important;
                    margin-bottom: ${marginBottom} !important;
                    padding: ${padding} !important;
                    border-radius: 8px !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    box-sizing: border-box !important;
                `;
            }

            // Force feedback title mobile styles
            const feedbackTitle = document.querySelector('.feedback-title');
            if (feedbackTitle) {
                feedbackTitle.style.cssText = `
                    font-size: ${window.innerWidth <= 576 ? '1.3rem' : '1.5rem'} !important;
                    flex-direction: column !important;
                    gap: 0.5rem !important;
                    text-align: center !important;
                `;
            }

            // Force stat cards mobile styles
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.style.cssText = `
                    padding: ${window.innerWidth <= 576 ? '10px' : '12px'} !important;
                    margin-bottom: 10px !important;
                    border-radius: 8px !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    box-sizing: border-box !important;
                    gap: 10px !important;
                `;
            });

            // Force filter section mobile styles
            const filterSection = document.querySelector('.filter-section');
            if (filterSection) {
                filterSection.style.cssText = `
                    padding: ${window.innerWidth <= 576 ? '10px' : '12px'} !important;
                    margin-bottom: 15px !important;
                    border-radius: 8px !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    box-sizing: border-box !important;
                `;

                const filterRow = filterSection.querySelector('.row');
                if (filterRow) {
                    filterRow.style.cssText = `
                        flex-direction: column !important;
                        gap: 10px !important;
                        margin: 0 !important;
                    `;
                }

                const filterCols = filterSection.querySelectorAll('.col-md-3');
                filterCols.forEach(col => {
                    col.style.cssText = `
                        width: 100% !important;
                        max-width: 100% !important;
                        padding: 0 !important;
                        margin-bottom: 10px !important;
                    `;
                });

                const formSelects = filterSection.querySelectorAll('.form-select');
                formSelects.forEach(select => {
                    select.style.cssText = `
                        width: 100% !important;
                        padding: 10px !important;
                        font-size: 14px !important;
                        border-radius: 6px !important;
                        min-height: 44px !important;
                    `;
                });

                const filterBtn = filterSection.querySelector('.btn');
                if (filterBtn) {
                    filterBtn.style.cssText = `
                        width: 100% !important;
                        padding: 10px !important;
                        font-size: 14px !important;
                        min-height: 44px !important;
                        border-radius: 6px !important;
                    `;
                }
            }

            // Force feedback cards mobile styles
            const feedbackCards = document.querySelectorAll('.feedback-card');
            feedbackCards.forEach(card => {
                card.style.cssText = `
                    margin-bottom: 15px !important;
                    border-radius: 8px !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    box-sizing: border-box !important;
                    overflow: hidden !important;
                `;

                const cardHeader = card.querySelector('.feedback-header');
                if (cardHeader) {
                    cardHeader.style.cssText = `
                        padding: 12px !important;
                        margin-top: 0 !important;
                        border-radius: 8px 8px 0 0 !important;
                    `;
                }

                const cardBody = card.querySelector('.feedback-card-body');
                if (cardBody) {
                    cardBody.style.cssText = `
                        padding: 12px !important;
                    `;

                    const cardRow = cardBody.querySelector('.row');
                    if (cardRow) {
                        cardRow.style.cssText = `
                            flex-direction: column !important;
                            margin: 0 !important;
                        `;
                    }

                    const cardCols = cardBody.querySelectorAll('.col-lg-8, .col-lg-4');
                    cardCols.forEach(col => {
                        col.style.cssText = `
                            width: 100% !important;
                            max-width: 100% !important;
                            padding: 0 !important;
                            margin-bottom: 10px !important;
                        `;
                    });
                }

                const actionSection = card.querySelector('.action-section');
                if (actionSection) {
                    actionSection.style.cssText = `
                        margin-top: 10px !important;
                        padding: 12px !important;
                        border-radius: 8px !important;
                        width: 100% !important;
                    `;

                    const actionButtons = actionSection.querySelector('.action-buttons');
                    if (actionButtons) {
                        actionButtons.style.cssText = `
                            flex-direction: column !important;
                            gap: 8px !important;
                            align-items: stretch !important;
                            width: 100% !important;
                        `;

                        const buttons = actionButtons.querySelectorAll('.btn');
                        buttons.forEach(btn => {
                            btn.style.cssText = `
                                width: 100% !important;
                                min-width: auto !important;
                                font-size: 0.8rem !important;
                                padding: 10px !important;
                                border-radius: 6px !important;
                                text-align: center !important;
                                min-height: 44px !important;
                                margin-bottom: 0 !important;
                            `;
                        });
                    }
                }
            });
        }
    }

    // Apply mobile layout immediately
    forceMobileLayout();

    // Reapply on window resize
    window.addEventListener('resize', forceMobileLayout);

    // Reapply after any dynamic content changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                setTimeout(forceMobileLayout, 100);
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // NUCLEAR MODAL MOBILE FIX - MAXIMUM FORCE
    function fixModalMobile() {
        if (window.innerWidth <= 768) {
            console.log('Applying nuclear modal mobile fix...');

            // Fix all modals on mobile with nuclear force
            const modals = document.querySelectorAll('.modal');
            modals.forEach((modal, index) => {
                console.log(`Fixing modal ${index + 1}...`);

                // NUCLEAR MODAL POSITIONING - COMPACT VERSION
                const modalStyles = {
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'right': '0',
                    'bottom': '0',
                    'width': '100vw',
                    'height': '100vh',
                    'max-width': '100vw',
                    'max-height': '100vh',
                    'padding': '5px',
                    'padding-top': '20px',
                    'margin': '0',
                    'z-index': '9999',
                    'overflow': 'hidden',
                    'transform': 'none'
                };

                Object.keys(modalStyles).forEach(prop => {
                    modal.style.setProperty(prop, modalStyles[prop], 'important');
                });

                // Force display when shown - COMPACT VERSION
                if (modal.classList.contains('show')) {
                    modal.style.setProperty('display', 'flex', 'important');
                    modal.style.setProperty('align-items', 'flex-start', 'important');
                    modal.style.setProperty('justify-content', 'center', 'important');
                    modal.style.setProperty('padding', '5px', 'important');
                    modal.style.setProperty('padding-top', '20px', 'important');
                }

                // NUCLEAR MODAL DIALOG POSITIONING - COMPACT VERSION
                const modalDialog = modal.querySelector('.modal-dialog');
                if (modalDialog) {
                    const dialogStyles = {
                        'position': 'relative',
                        'margin': '0',
                        'width': 'calc(100vw - 10px)',
                        'max-width': 'calc(100vw - 10px)',
                        'height': 'auto',
                        'max-height': 'calc(100vh - 40px)',
                        'transform': 'none',
                        'left': 'auto',
                        'right': 'auto',
                        'top': 'auto',
                        'bottom': 'auto',
                        'display': 'flex',
                        'flex-direction': 'column'
                    };

                    Object.keys(dialogStyles).forEach(prop => {
                        modalDialog.style.setProperty(prop, dialogStyles[prop], 'important');
                    });
                }

                // NUCLEAR MODAL CONTENT POSITIONING - COMPACT VERSION
                const modalContent = modal.querySelector('.modal-content');
                if (modalContent) {
                    const contentStyles = {
                        'position': 'relative',
                        'width': '100%',
                        'max-width': '100%',
                        'height': 'auto',
                        'max-height': 'calc(100vh - 40px)',
                        'margin': '0',
                        'padding': '0',
                        'border-radius': '6px',
                        'overflow': 'hidden',
                        'display': 'flex',
                        'flex-direction': 'column',
                        'box-sizing': 'border-box'
                    };

                    Object.keys(contentStyles).forEach(prop => {
                        modalContent.style.setProperty(prop, contentStyles[prop], 'important');
                    });
                }

                // NUCLEAR MODAL HEADER - COMPACT VERSION
                const modalHeader = modal.querySelector('.modal-header');
                if (modalHeader) {
                    const headerStyles = {
                        'position': 'relative',
                        'flex-shrink': '0',
                        'padding': '8px 12px',
                        'overflow': 'hidden',
                        'box-sizing': 'border-box',
                        'min-height': 'auto'
                    };

                    Object.keys(headerStyles).forEach(prop => {
                        modalHeader.style.setProperty(prop, headerStyles[prop], 'important');
                    });
                }

                // NUCLEAR MODAL BODY - COMPACT VERSION
                const modalBody = modal.querySelector('.modal-body');
                if (modalBody) {
                    const bodyStyles = {
                        'position': 'relative',
                        'flex': '1',
                        'padding': '12px',
                        'overflow-x': 'hidden',
                        'overflow-y': 'auto',
                        'max-height': 'calc(100vh - 120px)',
                        'box-sizing': 'border-box'
                    };

                    Object.keys(bodyStyles).forEach(prop => {
                        modalBody.style.setProperty(prop, bodyStyles[prop], 'important');
                    });
                }

                // NUCLEAR MODAL FOOTER - COMPACT VERSION
                const modalFooter = modal.querySelector('.modal-footer');
                if (modalFooter) {
                    const footerStyles = {
                        'position': 'relative',
                        'flex-shrink': '0',
                        'padding': '8px 12px',
                        'overflow': 'hidden',
                        'display': 'flex',
                        'flex-wrap': 'nowrap',
                        'gap': '6px',
                        'box-sizing': 'border-box'
                    };

                    Object.keys(footerStyles).forEach(prop => {
                        modalFooter.style.setProperty(prop, footerStyles[prop], 'important');
                    });
                }

                // NUCLEAR FORM ELEMENTS - COMPACT VERSION
                const formElements = modal.querySelectorAll('.form-select, .form-control, input, textarea, select');
                formElements.forEach(element => {
                    const formStyles = {
                        'width': '100%', // Keep input field full width
                        'max-width': '100%', // Keep input field full width
                        'font-size': '0.85rem', // Compact font size
                        'padding': '6px 8px',
                        'border-radius': '4px',
                        'min-height': 'auto',
                        'height': 'auto',
                        'box-sizing': 'border-box',
                        'overflow': 'hidden'
                    };

                    Object.keys(formStyles).forEach(prop => {
                        element.style.setProperty(prop, formStyles[prop], 'important');
                    });

                    // Special handling for select dropdowns - only affect dropdown options, not input
                    if (element.tagName.toLowerCase() === 'select' || element.classList.contains('form-select')) {
                        element.style.setProperty('background-size', '12px 8px', 'important');
                        element.style.setProperty('padding-right', '24px', 'important');
                        element.style.setProperty('max-height', '200px', 'important');
                        element.style.setProperty('line-height', '1.2', 'important');

                        // Apply width reduction only to dropdown options when focused/opened
                        element.addEventListener('focus', function() {
                            if (window.innerWidth <= 768) {
                                this.style.setProperty('width', '50%', 'important');
                                this.style.setProperty('max-width', '50%', 'important');
                                this.style.setProperty('min-width', '50%', 'important');
                            }
                        });

                        element.addEventListener('click', function() {
                            if (window.innerWidth <= 768) {
                                this.style.setProperty('width', '50%', 'important');
                                this.style.setProperty('max-width', '50%', 'important');
                                this.style.setProperty('min-width', '50%', 'important');
                            }
                        });

                        element.addEventListener('blur', function() {
                            this.style.setProperty('width', '100%', 'important');
                            this.style.setProperty('max-width', '100%', 'important');
                            this.style.removeProperty('min-width');
                        });
                    }
                });

                // NUCLEAR BUTTONS - COMPACT VERSION
                const buttons = modal.querySelectorAll('.modal-footer .btn');
                buttons.forEach(btn => {
                    const buttonStyles = {
                        'flex': '1',
                        'margin': '0',
                        'min-width': '0',
                        'font-size': '0.8rem',
                        'padding': '6px 10px',
                        'white-space': 'nowrap',
                        'overflow': 'hidden',
                        'text-overflow': 'ellipsis',
                        'box-sizing': 'border-box',
                        'border-radius': '4px'
                    };

                    Object.keys(buttonStyles).forEach(prop => {
                        btn.style.setProperty(prop, buttonStyles[prop], 'important');
                    });
                });

                console.log(`Modal ${index + 1} fixed successfully`);
            });

            // NUCLEAR BODY FIX
            if (document.body.classList.contains('modal-open')) {
                const bodyStyles = {
                    'overflow': 'hidden',
                    'position': 'fixed',
                    'width': '100%',
                    'height': '100%'
                };

                Object.keys(bodyStyles).forEach(prop => {
                    document.body.style.setProperty(prop, bodyStyles[prop], 'important');
                });
            }

            console.log('Nuclear modal mobile fix completed');
        }
    }

    // NUCLEAR MODAL EVENT HANDLERS
    function applyNuclearModalFix(modal) {
        if (window.innerWidth <= 768 && modal) {
            console.log('Applying nuclear fix to specific modal...');

            // Force immediate positioning - COMPACT VERSION
            setTimeout(() => {
                modal.style.setProperty('position', 'fixed', 'important');
                modal.style.setProperty('top', '0', 'important');
                modal.style.setProperty('left', '0', 'important');
                modal.style.setProperty('right', '0', 'important');
                modal.style.setProperty('bottom', '0', 'important');
                modal.style.setProperty('width', '100vw', 'important');
                modal.style.setProperty('height', '100vh', 'important');
                modal.style.setProperty('max-width', '100vw', 'important');
                modal.style.setProperty('max-height', '100vh', 'important');
                modal.style.setProperty('z-index', '9999', 'important');
                modal.style.setProperty('display', 'flex', 'important');
                modal.style.setProperty('align-items', 'flex-start', 'important');
                modal.style.setProperty('justify-content', 'center', 'important');
                modal.style.setProperty('padding', '5px', 'important');
                modal.style.setProperty('padding-top', '20px', 'important');

                const modalDialog = modal.querySelector('.modal-dialog');
                if (modalDialog) {
                    modalDialog.style.setProperty('width', 'calc(100vw - 10px)', 'important');
                    modalDialog.style.setProperty('max-width', 'calc(100vw - 10px)', 'important');
                    modalDialog.style.setProperty('max-height', 'calc(100vh - 40px)', 'important');
                    modalDialog.style.setProperty('margin', '0', 'important');
                    modalDialog.style.setProperty('transform', 'none', 'important');
                }

                console.log('Nuclear compact fix applied to modal');
            }, 1);
        }
    }

    // NUCLEAR MODAL FIX APPLICATION
    console.log('Initializing nuclear modal fixes...');

    // Apply nuclear fixes on load
    fixModalMobile();

    // Apply nuclear fixes on resize
    window.addEventListener('resize', () => {
        console.log('Window resized, reapplying nuclear modal fixes...');
        fixModalMobile();
    });

    // NUCLEAR EVENT LISTENERS FOR MODALS

    // Before modal shows
    document.addEventListener('show.bs.modal', function(event) {
        console.log('Modal about to show, applying nuclear fix...');
        const modal = event.target;
        applyNuclearModalFix(modal);

        if (window.innerWidth <= 768) {
            // Force body to be fixed immediately
            document.body.style.setProperty('overflow', 'hidden', 'important');
            document.body.style.setProperty('position', 'fixed', 'important');
            document.body.style.setProperty('width', '100%', 'important');
            document.body.style.setProperty('height', '100%', 'important');
        }
    });

    // After modal is shown
    document.addEventListener('shown.bs.modal', function(event) {
        console.log('Modal shown, applying nuclear fix...');
        const modal = event.target;

        // Apply nuclear fix multiple times to ensure it sticks
        applyNuclearModalFix(modal);
        setTimeout(() => applyNuclearModalFix(modal), 10);
        setTimeout(() => applyNuclearModalFix(modal), 50);
        setTimeout(() => applyNuclearModalFix(modal), 100);

        // Force global modal fix
        setTimeout(fixModalMobile, 1);
        setTimeout(fixModalMobile, 25);
        setTimeout(fixModalMobile, 75);

        if (window.innerWidth <= 768) {
            // Force backdrop positioning
            setTimeout(() => {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.style.setProperty('position', 'fixed', 'important');
                    backdrop.style.setProperty('top', '0', 'important');
                    backdrop.style.setProperty('left', '0', 'important');
                    backdrop.style.setProperty('width', '100vw', 'important');
                    backdrop.style.setProperty('height', '100vh', 'important');
                    backdrop.style.setProperty('z-index', '9998', 'important');
                }
            }, 10);
        }
    });

    // When modal is hidden
    document.addEventListener('hidden.bs.modal', function(event) {
        console.log('Modal hidden, restoring body...');

        if (window.innerWidth <= 768) {
            // Restore body properties
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('position');
            document.body.style.removeProperty('width');
            document.body.style.removeProperty('height');
        }
    });

    // NUCLEAR CLICK HANDLERS FOR MODAL TRIGGERS
    document.addEventListener('click', function(event) {
        const trigger = event.target.closest('[data-bs-toggle="modal"]');
        if (trigger && window.innerWidth <= 768) {
            console.log('Modal trigger clicked, preparing nuclear fix...');

            const targetSelector = trigger.getAttribute('data-bs-target');
            if (targetSelector) {
                const targetModal = document.querySelector(targetSelector);
                if (targetModal) {
                    // Pre-apply nuclear fix
                    setTimeout(() => applyNuclearModalFix(targetModal), 1);
                }
            }
        }
    });

    // NUCLEAR MUTATION OBSERVER FOR DYNAMIC MODALS
    const nuclearObserver = new MutationObserver(function(mutations) {
        if (window.innerWidth <= 768) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target.classList.contains('modal') && target.classList.contains('show')) {
                        console.log('Modal class changed to show, applying nuclear fix...');
                        applyNuclearModalFix(target);
                    }
                }

                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            if (node.classList && node.classList.contains('modal')) {
                                console.log('New modal added, applying nuclear fix...');
                                applyNuclearModalFix(node);
                            }

                            const modals = node.querySelectorAll && node.querySelectorAll('.modal');
                            if (modals) {
                                modals.forEach(modal => applyNuclearModalFix(modal));
                            }
                        }
                    });
                }
            });
        }
    });

    // Start observing
    nuclearObserver.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class', 'style']
    });

    console.log('Nuclear modal fixes initialized successfully');

    // GLOBAL DROPDOWN OPTIONS WIDTH REDUCTION FUNCTION
    function applyDropdownOptionsWidthReduction() {
        if (window.innerWidth <= 768) {
            console.log('Applying dropdown options width reduction...');

            // Target all dropdown select elements and add event listeners
            const allDropdowns = document.querySelectorAll('.form-select, select');
            allDropdowns.forEach(dropdown => {
                // Add focus event to reduce width when dropdown opens
                dropdown.addEventListener('focus', function() {
                    if (window.innerWidth <= 768) {
                        this.style.setProperty('width', '50%', 'important');
                        this.style.setProperty('max-width', '50%', 'important');
                        this.style.setProperty('min-width', '50%', 'important');
                    }
                });

                // Add blur event to restore width when dropdown closes
                dropdown.addEventListener('blur', function() {
                    this.style.setProperty('width', '100%', 'important');
                    this.style.setProperty('max-width', '100%', 'important');
                    this.style.removeProperty('min-width');
                });

                // Add click event for additional support
                dropdown.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        this.style.setProperty('width', '50%', 'important');
                        this.style.setProperty('max-width', '50%', 'important');
                        this.style.setProperty('min-width', '50%', 'important');
                    }
                });

                // Add mousedown event for better support
                dropdown.addEventListener('mousedown', function() {
                    if (window.innerWidth <= 768) {
                        this.style.setProperty('width', '50%', 'important');
                        this.style.setProperty('max-width', '50%', 'important');
                        this.style.setProperty('min-width', '50%', 'important');
                    }
                });
            });

            // Target dropdown menus specifically
            const dropdownMenus = document.querySelectorAll('.dropdown-menu');
            dropdownMenus.forEach(menu => {
                menu.style.setProperty('width', '50%', 'important');
                menu.style.setProperty('max-width', '50%', 'important');
            });

            // SPECIFIC FILTER SECTION DROPDOWN TARGETING - FULL WIDTH INPUT FIELDS
            const filterDropdowns = document.querySelectorAll('.filter-section select, .filter-section .form-select');
            filterDropdowns.forEach(dropdown => {
                // Ensure filter dropdown input fields are full width
                dropdown.style.setProperty('width', '100%', 'important');
                dropdown.style.setProperty('max-width', '100%', 'important');
                dropdown.style.removeProperty('min-width');

                // Add event listeners to reduce width only when dropdown options are shown
                dropdown.addEventListener('focus', function() {
                    if (window.innerWidth <= 768) {
                        // Only reduce width when dropdown is opened/focused
                        this.style.setProperty('width', '50%', 'important');
                        this.style.setProperty('max-width', '50%', 'important');
                        this.style.setProperty('min-width', '50%', 'important');
                    }
                });

                dropdown.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        this.style.setProperty('width', '50%', 'important');
                        this.style.setProperty('max-width', '50%', 'important');
                        this.style.setProperty('min-width', '50%', 'important');
                    }
                });

                dropdown.addEventListener('mousedown', function() {
                    if (window.innerWidth <= 768) {
                        this.style.setProperty('width', '50%', 'important');
                        this.style.setProperty('max-width', '50%', 'important');
                        this.style.setProperty('min-width', '50%', 'important');
                    }
                });

                // Restore full width when dropdown closes
                dropdown.addEventListener('blur', function() {
                    this.style.setProperty('width', '100%', 'important');
                    this.style.setProperty('max-width', '100%', 'important');
                    this.style.removeProperty('min-width');
                });
            });

            console.log('Dropdown options width reduction applied successfully');
        }
    }

    // Apply dropdown options width reduction on page load
    applyDropdownOptionsWidthReduction();

    // FORCE FILTER SECTION DROPDOWN INPUT FIELDS TO FULL WIDTH
    function forceFilterDropdownFullWidth() {
        console.log('Forcing filter section dropdown input fields to full width...');

        const filterSelects = document.querySelectorAll('.filter-section select, .filter-section .form-select, .filter-section .form-control');
        filterSelects.forEach(select => {
            // Ensure input fields are full width when not focused
            select.style.setProperty('width', '100%', 'important');
            select.style.setProperty('max-width', '100%', 'important');
            select.style.removeProperty('min-width');
            select.style.setProperty('box-sizing', 'border-box', 'important');
        });

        // Also target by ID if they have specific IDs
        const statusSelect = document.querySelector('#status-filter, [name="status"], .filter-section [name="status"]');
        if (statusSelect) {
            statusSelect.style.setProperty('width', '100%', 'important');
            statusSelect.style.setProperty('max-width', '100%', 'important');
            statusSelect.style.removeProperty('min-width');
        }

        console.log('Filter section dropdown input fields set to full width successfully');
    }

    // Apply filter dropdown full width immediately
    forceFilterDropdownFullWidth();

    // Apply filter dropdown full width after a short delay
    setTimeout(forceFilterDropdownFullWidth, 100);
    setTimeout(forceFilterDropdownFullWidth, 500);
    setTimeout(forceFilterDropdownFullWidth, 1000);

    // Apply dropdown options width reduction on window resize
    window.addEventListener('resize', applyDropdownOptionsWidthReduction);

    // Apply dropdown width reduction when DOM changes
    const dropdownObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Check if the added node contains dropdowns
                        const dropdowns = node.querySelectorAll && node.querySelectorAll('.form-select, select');
                        if (dropdowns && dropdowns.length > 0) {
                            setTimeout(applyDropdownOptionsWidthReduction, 10);
                        }

                        // Check if the node itself is a dropdown
                        if (node.classList && (node.classList.contains('form-select') || node.tagName.toLowerCase() === 'select')) {
                            setTimeout(applyDropdownOptionsWidthReduction, 10);
                        }
                    }
                });
            }
        });
    });

    // Start observing for dropdown changes
    dropdownObserver.observe(document.body, {
        childList: true,
        subtree: true
    });

    console.log('Dropdown options width reduction system initialized');
});
</script>

<div class="page-wrapper" style="margin-left: 0 !important; width: 100% !important; max-width: 100% !important;" id="pageWrapper">
<main class="main-content" style="width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; overflow-x: hidden !important;" id="mainContent">
    <div class="container-fluid px-4" style="width: 100% !important; max-width: 100% !important; margin: 0 !important; overflow-x: hidden !important;" id="containerFluid">
        <!-- Modern Feedback Management Header -->
        <div class="feedback-header animate__animated animate__fadeInDown" id="feedback-header-mobile">
            <div class="feedback-header-content">
                <div class="feedback-header-main">
                    <h1 class="feedback-title">
                        <i class="fas fa-cogs me-3"></i>
                        Manage Feedback
                    </h1>
                    <p class="feedback-description">Review, assign, and respond to user feedback</p>
                </div>
                <div class="feedback-header-actions">
                    <a href="feedback.php" class="btn btn-header-action">
                        <i class="fas fa-arrow-left me-2"></i>Back to Feedback
                    </a>
                </div>
            </div>
        </div>

<!-- Aggressive Mobile Fix Script (EXACT DASHBOARD SOLUTION) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aggressive mobile feedback header positioning fix
    function fixFeedbackHeaderMobile() {
        const feedbackHeader = document.getElementById('feedback-header-mobile');
        const isMobile = window.innerWidth <= 768;

        if (feedbackHeader && isMobile) {
            // Force mobile positioning with increased spacing
            feedbackHeader.style.setProperty('margin-top', '15px', 'important');
            feedbackHeader.style.setProperty('margin-bottom', '1rem', 'important');
            feedbackHeader.style.setProperty('position', 'relative', 'important');
            feedbackHeader.style.setProperty('z-index', '999', 'important');
            feedbackHeader.style.setProperty('top', '0', 'important');
            feedbackHeader.style.setProperty('transform', 'none', 'important');

            // Adjust body padding for mobile with significantly increased spacing
            if (window.innerWidth <= 320) {
                document.body.style.setProperty('padding-top', '55px', 'important');
                feedbackHeader.style.setProperty('margin-top', '8px', 'important');
            } else if (window.innerWidth <= 375) {
                document.body.style.setProperty('padding-top', '60px', 'important');
                feedbackHeader.style.setProperty('margin-top', '10px', 'important');
            } else if (window.innerWidth <= 480) {
                document.body.style.setProperty('padding-top', '65px', 'important');
                feedbackHeader.style.setProperty('margin-top', '12px', 'important');
            } else {
                document.body.style.setProperty('padding-top', '70px', 'important');
                feedbackHeader.style.setProperty('margin-top', '15px', 'important');
            }
        }
    }

    // Apply fix immediately and on resize
    fixFeedbackHeaderMobile();
    window.addEventListener('resize', fixFeedbackHeaderMobile);
});
</script>

        <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Section -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Feedback</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['in_progress']; ?></h3>
                        <p>In Progress</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['resolved']; ?></h3>
                        <p>Resolved</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Feedback</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select class="form-select" id="statusFilter" name="status">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="typeFilter" class="form-label">Type</label>
                    <select class="form-select" id="typeFilter" name="type">
                        <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="suggestion" <?php echo $typeFilter === 'suggestion' ? 'selected' : ''; ?>>Suggestion</option>
                        <option value="complaint" <?php echo $typeFilter === 'complaint' ? 'selected' : ''; ?>>Complaint</option>
                        <option value="question" <?php echo $typeFilter === 'question' ? 'selected' : ''; ?>>Question</option>
                        <option value="praise" <?php echo $typeFilter === 'praise' ? 'selected' : ''; ?>>Praise</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="assignmentFilter" class="form-label">Assignment</label>
                    <select class="form-select" id="assignmentFilter" name="assigned">
                        <option value="all" <?php echo $assignedFilter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="unassigned" <?php echo $assignedFilter === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                        <option value="assigned" <?php echo $assignedFilter === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-primary d-block w-100" onclick="applyFilters()">
                        <i class="fas fa-search me-2"></i>Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Feedback List -->
        <div class="feedback-list">
            <?php if (empty($feedbacks)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No feedback found</h5>
                <p class="text-muted">No feedback matches your current filters.</p>
            </div>
            <?php else: ?>
            <?php foreach ($feedbacks as $feedback): ?>
            <div class="feedback-card">
                <div class="feedback-header">
                    <div class="feedback-meta">
                        <div class="flex-grow-1">
                            <h5 class="mb-2">
                                <i class="fas fa-comment me-2"></i>
                                <?php echo htmlspecialchars($feedback['subject']); ?>
                            </h5>
                            <div class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                <?php if ($feedback['user_id']): ?>
                                    <?php echo htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']); ?>
                                <?php elseif ($feedback['submitter_name']): ?>
                                    <?php echo htmlspecialchars($feedback['submitter_name']); ?>
                                <?php else: ?>
                                    Anonymous submission
                                <?php endif; ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date('M j, Y g:i A', strtotime($feedback['created_at'])); ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-tag me-1"></i>
                                <?php echo ucfirst($feedback['feedback_type']); ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="status-badge status-<?php echo $feedback['status']; ?>">
                                <i class="fas fa-circle me-1" style="font-size: 0.6rem;"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $feedback['status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="feedback-card-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="feedback-content">
                                <h6><i class="fas fa-message me-2"></i>Message</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></p>
                            </div>

                            <?php if ($feedback['resolution']): ?>
                            <div class="feedback-response">
                                <h6 class="mb-2"><i class="fas fa-reply me-2"></i>Response</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($feedback['resolution'])); ?></p>
                            </div>
                            <?php endif; ?>

                        </div>
                        <div class="col-lg-4">
                            <div class="action-section">
                                <h6 class="mb-3"><i class="fas fa-tools me-2"></i>Quick Actions</h6>
                                <div class="action-buttons">
                                    <?php if ($feedback['status'] === 'pending'): ?>
                                    <button class="btn btn-primary btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $feedback['feedback_id']; ?>">
                                        <i class="fas fa-user-plus me-2"></i>Assign
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($feedback['status'] !== 'resolved' && $feedback['status'] !== 'rejected'): ?>
                                    <button class="btn btn-success btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#respondModal<?php echo $feedback['feedback_id']; ?>">
                                        <i class="fas fa-reply me-2"></i>Respond
                                    </button>
                                    <?php endif; ?>

                                    <button class="btn btn-info btn-sm w-100" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $feedback['feedback_id']; ?>">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($feedback['assigned_to'] || $feedback['resolution']): ?>
                <div class="feedback-card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <?php if ($feedback['assigned_to']): ?>
                            <div class="assignment-info">
                                <i class="fas fa-user-check me-2 text-primary"></i>
                                <strong>Assigned to:</strong>
                                <span class="text-primary"><?php echo htmlspecialchars($feedback['assigned_first_name'] . ' ' . $feedback['assigned_last_name']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 text-end">
                            <?php if ($feedback['resolution']): ?>
                            <small class="text-muted">
                                <i class="fas fa-check-circle me-1 text-success"></i>
                                Response provided
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Assignment Modal -->
            <div class="modal fade" id="assignModal<?php echo $feedback['feedback_id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Assign Feedback</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">

                                <div class="mb-3">
                                    <label for="assigned_to<?php echo $feedback['feedback_id']; ?>" class="form-label">Assign to:</label>
                                    <select class="form-select" id="assigned_to<?php echo $feedback['feedback_id']; ?>" name="assigned_to" required>
                                        <option value="">Select a member...</option>
                                        <option value="unassigned">Unassign</option>
                                        <?php if (!empty($members)): ?>
                                            <?php foreach ($members as $member): ?>
                                            <option value="<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>">
                                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?> (<?php echo ucfirst($member['role']); ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="" disabled>No members available - Check user roles</option>
                                        <?php endif; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        Available members: <?php echo count($members); ?>
                                        <?php if (empty($members)): ?>
                                            <br><span class="text-warning">No admin/member users found. Please check user roles in the database.</span>
                                        <?php endif; ?>
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <label for="new_status<?php echo $feedback['feedback_id']; ?>" class="form-label">Status:</label>
                                    <select class="form-select" id="new_status<?php echo $feedback['feedback_id']; ?>" name="new_status" required>
                                        <option value="">Select Status...</option>
                                        <option value="pending">Pending</option>
                                        <option value="in_progress" selected>In Progress</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="assign_feedback" class="btn btn-primary">Assign</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Response Modal -->
            <div class="modal fade" id="respondModal<?php echo $feedback['feedback_id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Respond to Feedback</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">

                                <div class="mb-3">
                                    <label class="form-label"><strong>Original Message:</strong></label>
                                    <div class="alert alert-light">
                                        <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="response<?php echo $feedback['feedback_id']; ?>" class="form-label">Your Response:</label>
                                    <textarea class="form-control" id="response<?php echo $feedback['feedback_id']; ?>" name="response" rows="5" placeholder="Enter your response to this feedback..." required><?php echo htmlspecialchars($feedback['resolution'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="new_status_respond<?php echo $feedback['feedback_id']; ?>" class="form-label">Status:</label>
                                    <select class="form-select" id="new_status_respond<?php echo $feedback['feedback_id']; ?>" name="new_status">
                                        <option value="resolved" <?php echo $feedback['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="in_progress" <?php echo $feedback['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="rejected" <?php echo $feedback['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>

                                <?php if ($feedback['user_id']): ?>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="send_notification<?php echo $feedback['feedback_id']; ?>" name="send_notification" checked>
                                        <label class="form-check-label" for="send_notification<?php echo $feedback['feedback_id']; ?>">
                                            Send notification to user about this response
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="respond_feedback" class="btn btn-success">Submit Response</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- View Details Modal -->
            <div class="modal fade" id="viewModal<?php echo $feedback['feedback_id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Feedback Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Feedback Information</h6>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>ID:</strong></td>
                                            <td>#<?php echo $feedback['feedback_id']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Subject:</strong></td>
                                            <td><?php echo htmlspecialchars($feedback['subject']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Type:</strong></td>
                                            <td><?php echo ucfirst($feedback['feedback_type']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td><span class="status-badge status-<?php echo $feedback['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $feedback['status'])); ?></span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Submitted:</strong></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($feedback['created_at'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Updated:</strong></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($feedback['updated_at'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Submitter Information</h6>
                                    <table class="table table-borderless">
                                        <?php if ($feedback['user_id']): ?>
                                        <tr>
                                            <td><strong>Name:</strong></td>
                                            <td><?php echo htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td><?php echo htmlspecialchars($feedback['user_email']); ?></td>
                                        </tr>
                                        <?php elseif ($feedback['submitter_name']): ?>
                                        <tr>
                                            <td><strong>Name:</strong></td>
                                            <td><?php echo htmlspecialchars($feedback['submitter_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td><?php echo htmlspecialchars($feedback['submitter_email'] ?? 'Not provided'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phone:</strong></td>
                                            <td><?php echo htmlspecialchars($feedback['submitter_phone'] ?? 'Not provided'); ?></td>
                                        </tr>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="2"><em>Anonymous submission</em></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($feedback['assigned_to']): ?>
                                        <tr>
                                            <td><strong>Assigned to:</strong></td>
                                            <td><?php echo htmlspecialchars($feedback['assigned_first_name'] . ' ' . $feedback['assigned_last_name']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h6>Message</h6>
                                <div class="alert alert-light">
                                    <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
                                </div>
                            </div>

                            <?php if ($feedback['resolution']): ?>
                            <div class="mt-3">
                                <h6>Response</h6>
                                <div class="alert alert-info">
                                    <?php echo nl2br(htmlspecialchars($feedback['resolution'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
</div> <!-- End page-wrapper -->

<script>
// Sidebar toggle functionality - Match main system behavior
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const pageWrapper = document.querySelector('.page-wrapper');
    const navbarToggleBtn = document.getElementById('sidebar-toggle-navbar');

    // Function to update page wrapper based on sidebar state
    function updatePageLayout() {
        if (pageWrapper) {
            if (sidebar && sidebar.classList.contains('collapsed')) {
                // Sidebar is collapsed
                pageWrapper.style.setProperty('margin-left', '60px', 'important');
                pageWrapper.style.setProperty('width', 'calc(100% - 60px)', 'important');
            } else {
                // Sidebar is expanded
                pageWrapper.style.setProperty('margin-left', '260px', 'important');
                pageWrapper.style.setProperty('width', 'calc(100% - 260px)', 'important');
            }
        }
    }

    // Initial layout update
    updatePageLayout();

    // Listen for the main system's sidebar toggle button
    if (navbarToggleBtn) {
        navbarToggleBtn.addEventListener('click', function() {
            // Wait for the main sidebar toggle to complete
            setTimeout(updatePageLayout, 100);
            setTimeout(updatePageLayout, 300);
        });
    }

    // Listen for ANY button that might toggle sidebar
    document.addEventListener('click', function(e) {
        if (e.target.id === 'sidebar-toggle-navbar' ||
            e.target.closest('#sidebar-toggle-navbar') ||
            e.target.classList.contains('sidebar-toggle') ||
            e.target.closest('.sidebar-toggle')) {
            setTimeout(updatePageLayout, 100);
        }
    });

    // Listen for sidebar state changes using MutationObserver
    if (sidebar) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    updatePageLayout();
                }
            });
        });

        // Start observing sidebar for class changes
        observer.observe(sidebar, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    // Check localStorage for initial sidebar state
    const sidebarCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    if (sidebarCollapsed && sidebar) {
        setTimeout(updatePageLayout, 100);
    }

    // Listen for keyboard shortcut (Ctrl+B) that toggles sidebar
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'b') {
            setTimeout(updatePageLayout, 100);
        }
    });

    // Responsive handling
    function handleResize() {
        if (window.innerWidth <= 992) {
            pageWrapper.style.setProperty('margin-left', '0', 'important');
            pageWrapper.style.setProperty('width', '100%', 'important');
        } else {
            updatePageLayout();
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize(); // Initial call
});

// Apply filters function with AJAX
function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const type = document.getElementById('typeFilter').value;
    const assignment = document.getElementById('assignmentFilter').value;

    console.log('Applying filters:', { status, type, assignment });

    // Show loading state
    const applyBtn = document.querySelector('.btn-primary');
    const originalText = applyBtn.innerHTML;
    applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Applying...';
    applyBtn.disabled = true;

    // Create form data
    const formData = new FormData();
    formData.append('action', 'filter_feedback');
    formData.append('status', status);
    formData.append('type', type);
    formData.append('assignment', assignment);

    // Make AJAX request
    fetch('manage-feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Parse the response to extract the feedback list
        const parser = new DOMParser();
        const doc = parser.parseFromString(data, 'text/html');
        const newFeedbackList = doc.querySelector('.feedback-list');
        const newStatsCards = doc.querySelectorAll('.stats-card');

        if (newFeedbackList) {
            // Update the feedback list
            document.querySelector('.feedback-list').innerHTML = newFeedbackList.innerHTML;

            // Update statistics cards
            if (newStatsCards.length > 0) {
                const currentStatsCards = document.querySelectorAll('.stats-card');
                newStatsCards.forEach((newCard, index) => {
                    if (currentStatsCards[index]) {
                        currentStatsCards[index].innerHTML = newCard.innerHTML;
                    }
                });
            }

            // Show success message
            showNotification('Filters applied successfully!', 'success');
        } else {
            showNotification('Error applying filters. Please try again.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error applying filters. Please try again.', 'error');
    })
    .finally(() => {
        // Restore button state
        applyBtn.innerHTML = originalText;
        applyBtn.disabled = false;
    });
}

// Show notification function
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.filter-notification');
    existingNotifications.forEach(notification => notification.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show filter-notification`;
    notification.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Add to page
    document.body.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
</script>

<?php require_once 'includes/footer.php'; ?>
