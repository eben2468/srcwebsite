<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Include auto notifications system
require_once __DIR__ . '/includes/auto_notifications.php';

// Require login for this page
requireLogin();

// Check if user is logged in - redirect to login if not
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if feedback feature is enabled
if (!hasFeaturePermission('enable_feedback')) {
    $_SESSION['error'] = "Feedback feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Get current user info
$currentUser = getCurrentUser();
$isAdmin = shouldUseAdminInterface();
$isMember = isMember();
$isSuperAdmin = isSuperAdmin();
$canRespondToFeedback = shouldUseAdminInterface(); // Allow super admin, admin, and members to respond to feedback

// Page content
$pageTitle = "Feedback & Suggestions";

// Portfolio categories for the dropdown
$portfolioCategories = [
    'president' => 'President',
    'vice_president' => 'Vice President',
    'senate_president' => 'Senate President',
    'finance' => 'Finance Officer',
    'editor' => 'Editor',
    'secretary' => 'General Secretary',
    'sports' => 'Sports Commissioner',
    'welfare' => 'Welfare Commissioner',
    'women' => 'Women\'s Commissioner',
    'pro' => 'Public Relations Officer',
    'chaplain' => 'Chaplain',
    'general' => 'General Feedback',
    'website' => 'Website Feedback',
    'other' => 'Other'
];

// Process feedback submission
$successMessage = '';
$errorMessage = '';

// Database operations for feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    // Check if user has permission to create feedback
    if (hasPermission('create', 'feedback')) {
        // Validate CAPTCHA
        if (isset($_POST['captcha']) && $_POST['captcha'] === $_POST['captcha_answer']) {
            // Get form data
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $portfolio = $_POST['portfolio'] ?? '';
            $message = $_POST['message'] ?? '';
            $isAnonymous = isset($_POST['is_anonymous']) && $_POST['is_anonymous'] === 'on';
            
            // Validate fields
            $errors = [];
            
            if (empty($portfolio)) {
                $errors[] = "Please select a category for your feedback.";
            }
            
            if (empty($message)) {
                $errors[] = "Please enter your feedback message.";
            }
            
            if (!$isAnonymous) {
                if (empty($name)) {
                    $errors[] = "Please enter your name or check the anonymous option.";
                }
                
                if (empty($email)) {
                    $errors[] = "Please enter your email or check the anonymous option.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Please enter a valid email address.";
                }
            }
            
            // Process if no errors
            if (empty($errors)) {
                // Get user ID with verification
                $userId = null;
                if (!$isAnonymous && isset($currentUser['user_id'])) {
                    // Check if the user exists in the database
                    $userQuery = "SELECT user_id FROM users WHERE user_id = ? LIMIT 1";
                    $userExists = fetchOne($userQuery, [$currentUser['user_id']]);
                    
                    if ($userExists) {
                        $userId = $currentUser['user_id'];
                    }
                }
                
                // Check if the submitter_name and submitter_email columns exist
                $columns = mysqli_query($conn, "SHOW COLUMNS FROM feedback LIKE 'submitter_name'");
                $nameColumnExists = mysqli_num_rows($columns) > 0;
                
                $columns = mysqli_query($conn, "SHOW COLUMNS FROM feedback LIKE 'submitter_email'");
                $emailColumnExists = mysqli_num_rows($columns) > 0;
                
                $columns = mysqli_query($conn, "SHOW COLUMNS FROM feedback LIKE 'submitter_phone'");
                $phoneColumnExists = mysqli_num_rows($columns) > 0;
                
                // Add the columns if they don't exist
                if (!$nameColumnExists || !$emailColumnExists || !$phoneColumnExists) {
                    $alterSQL = "ALTER TABLE feedback ";
                    
                    if (!$nameColumnExists) {
                        $alterSQL .= "ADD COLUMN submitter_name VARCHAR(100) NULL AFTER user_id, ";
                    }
                    
                    if (!$emailColumnExists) {
                        $alterSQL .= "ADD COLUMN submitter_email VARCHAR(100) NULL AFTER " . 
                                      ($nameColumnExists ? "submitter_name" : "user_id") . ", ";
                    }
                    
                    if (!$phoneColumnExists) {
                        $alterSQL .= "ADD COLUMN submitter_phone VARCHAR(50) NULL AFTER submitter_email, ";
                    }
                    
                    // Remove trailing comma and space
                    $alterSQL = rtrim($alterSQL, ", ");
                    
                    mysqli_query($conn, $alterSQL);
                }
                
                // Set submitter name and email based on form or leave NULL if anonymous
                $submitterName = $isAnonymous ? null : $name;
                $submitterEmail = $isAnonymous ? null : $email;
                $submitterPhone = $isAnonymous ? null : $phone;
                
                // Insert feedback into database with submitter information
                $sql = "INSERT INTO feedback (user_id, submitter_name, submitter_email, submitter_phone, subject, message, feedback_type, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
                
                try {
                    $result = insert($sql, [$userId, $submitterName, $submitterEmail, $submitterPhone, $portfolio, $message, 'suggestion']);
                    
                    if ($result) {
                        $successMessage = "Thank you for your feedback! " .
                                        ($isAnonymous ? "Your anonymous feedback" : "Your feedback") .
                                        " has been submitted successfully and will be reviewed by our team.";

                        // Send notification to members and admins about new feedback
                        autoNotifyFeedbackSubmitted($portfolio, 'suggestion', $userId, $result);

                        // Clear form data after successful submission
                        $_POST = [];
                    } else {
                        logDbError("Failed to insert feedback", $sql, [$userId, $submitterName, $submitterEmail, $submitterPhone, $portfolio, $message, 'suggestion']);
                        $errorMessage = "An error occurred while submitting your feedback. Please try again.";
                    }
                } catch (Exception $e) {
                    logDbError("Exception during feedback submission: " . $e->getMessage(), $sql, [$userId, $submitterName, $submitterEmail, $submitterPhone, $portfolio, $message, 'suggestion']);
                    $errorMessage = "An error occurred while submitting your feedback. Please try again later or contact support.";
                }
            } else {
                // Display validation errors
                $errorMessage = implode("<br>", $errors);
            }
        } else {
            $errorMessage = "CAPTCHA verification failed. Please try again.";
        }
    } else {
        $errorMessage = "You do not have permission to submit feedback.";
    }
}

// Process admin actions (update feedback status and response)
if ($canRespondToFeedback && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Check if user has permission to update feedback
    if (hasPermission('update', 'feedback')) {
        $feedbackId = $_POST['feedback_id'] ?? 0;
        $newStatus = $_POST['new_status'] ?? '';
        $assignedToName = $_POST['assigned_to'] ?? '';
        $response = $_POST['response'] ?? '';
        $sendEmail = isset($_POST['email_response']) && $_POST['email_response'] === 'on';

        // Convert assigned user name to user ID
        $assignedToUserId = null;
        if (!empty($assignedToName) && $assignedToName !== 'unassigned') {
            $userSql = "SELECT user_id FROM users WHERE CONCAT(first_name, ' ', last_name) = ? LIMIT 1";
            $userResult = fetchOne($userSql, [$assignedToName]);
            if ($userResult) {
                $assignedToUserId = $userResult['user_id'];
            } else {
                $errorMessage = "Selected user not found. Please try again.";
            }
        }

        // Only proceed if we have a valid user ID or it's being unassigned
        if (!isset($errorMessage)) {
            // Update feedback in database
            $sql = "UPDATE feedback SET
                    status = ?,
                    assigned_to = ?,
                    resolution = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE feedback_id = ?";

            $result = update($sql, [$newStatus, $assignedToUserId, $response, $feedbackId]);

            if ($result) {
            $successMessage = "Feedback #$feedbackId has been updated successfully.";
            
            // Send email notification if requested
            if ($sendEmail && !empty($response)) {
                // Get feedback details including user email
                $feedbackSql = "SELECT f.*, u.first_name, u.last_name, u.email 
                               FROM feedback f 
                               LEFT JOIN users u ON f.user_id = u.user_id 
                               WHERE f.feedback_id = ?";
                $feedbackDetails = fetchOne($feedbackSql, [$feedbackId]);
                
                // Determine the recipient email - either from user record or from submitter_email
                $recipientEmail = null;
                $recipientName = 'User';
                $recipientPhone = null;
                
                if ($feedbackDetails) {
                    if (!empty($feedbackDetails['email'])) {
                        // User from database
                        $recipientEmail = $feedbackDetails['email'];
                        $recipientName = $feedbackDetails['first_name'] . ' ' . $feedbackDetails['last_name'];
                        $recipientPhone = $feedbackDetails['submitter_phone'] ?? null;
                    } elseif (!empty($feedbackDetails['submitter_email'])) {
                        // Direct submission with email
                        $recipientEmail = $feedbackDetails['submitter_email'];
                        $recipientName = !empty($feedbackDetails['submitter_name']) ? $feedbackDetails['submitter_name'] : 'User';
                        $recipientPhone = $feedbackDetails['submitter_phone'] ?? null;
                    }
                    
                    if ($recipientEmail) {
                        // Set email parameters
                        $to = $recipientEmail;
                        $subject = "Response to your SRC Feedback #$feedbackId";
                        
                        // Get category name
                        $category = $portfolioCategories[$feedbackDetails['subject']] ?? 'Other';
                        
                        // Create email body
                        $emailBody = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%); color: white; padding: 20px; border-radius: 5px 5px 0 0; }
                                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
                                .footer { margin-top: 20px; font-size: 12px; color: #666; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h2>Response to Your Feedback</h2>
                                </div>
                                <div class='content'>
                                    <p>Dear " . htmlspecialchars($recipientName) . ",</p>
                                    
                                    <p>Thank you for your feedback regarding <strong>" . htmlspecialchars($category) . "</strong> submitted on " . date('F j, Y', strtotime($feedbackDetails['created_at'])) . ".</p>
                                    
                                    <p>Your feedback is currently marked as <strong>" . ucfirst($newStatus) . "</strong>.</p>
                                    
                                    <div style='background: #fff; padding: 15px; border-left: 4px solid #4b6cb7; margin: 20px 0;'>
                                        <p><strong>Your original message:</strong></p>
                                        <p>" . nl2br(htmlspecialchars($feedbackDetails['message'])) . "</p>
                                    </div>
                                    
                                    <div style='background: #e8f4ff; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>
                                        <p><strong>Our response:</strong></p>
                                        <p>" . nl2br(htmlspecialchars($response)) . "</p>
                                    </div>
                                    
                                    <p>If you have any further questions or feedback, please don't hesitate to reply to this email or submit another feedback through our portal.</p>
                                    
                                    <p>Best regards,<br>
                                    Student Representative Council</p>
                                </div>
                                <div class='footer'>
                                    <p>This is an automated email. Please do not reply directly to this message.</p>
                                    <p>&copy; " . date('Y') . " Student Representative Council. All rights reserved.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                        ";
                        
                        // Headers for HTML email
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                        $headers .= "From: SRC Management System <noreply@srcmanagement.com>" . "\r\n";
                        
                        // Try to send email
                        try {
                            $mailSent = mail($to, $subject, $emailBody, $headers);
                            if ($mailSent) {
                                $successMessage .= " An email notification has been sent to the user.";
                            } else {
                                $errorMessage = "Email notification could not be sent. Please check the mail server configuration.";
                            }
                        } catch (Exception $e) {
                            // Log error but don't show to user
                            error_log("Email sending failed: " . $e->getMessage());
                        }
                    }
                }
            }
            } else {
                $errorMessage = "An error occurred while updating the feedback. Please try again.";
            }
        } else {
            $errorMessage = "User assignment failed. " . ($errorMessage ?? "Please try again.");
        }
    } else {
        $errorMessage = "You do not have permission to update feedback.";
    }
}

// Process delete feedback action
if ($canRespondToFeedback && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_feedback'])) {
    // Check if user has permission to delete feedback
    if (hasPermission('delete', 'feedback')) {
        $feedbackId = $_POST['feedback_id'] ?? 0;
        
        // Delete feedback from database
        $sql = "DELETE FROM feedback WHERE feedback_id = ?";
        
        $result = delete($sql, [$feedbackId]);
        
        if ($result) {
            $successMessage = "Feedback #$feedbackId has been deleted successfully.";
        } else {
            $errorMessage = "An error occurred while deleting the feedback. Please try again.";
        }
    } else {
        $errorMessage = "You do not have permission to delete feedback.";
    }
}

// Fetch feedback submissions from database with filtering
$feedbackSubmissions = [];

// Set up filter conditions
$filterConditions = [];
$filterParams = [];
$filterTypes = '';

// Status filter
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filterConditions[] = "f.status = ?";
    $filterParams[] = $_GET['status'];
    $filterTypes .= 's';
}

// Category/Subject filter
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $filterConditions[] = "f.subject = ?";
    $filterParams[] = $_GET['type'];
    $filterTypes .= 's';
}

// Date filter
if (isset($_GET['date']) && !empty($_GET['date'])) {
    switch ($_GET['date']) {
        case 'today':
            $filterConditions[] = "DATE(f.created_at) = CURDATE()";
            break;
        case 'week':
            $filterConditions[] = "YEARWEEK(f.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $filterConditions[] = "MONTH(f.created_at) = MONTH(CURDATE()) AND YEAR(f.created_at) = YEAR(CURDATE())";
            break;
    }
}

// Search term filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $filterConditions[] = "(f.message LIKE ? OR f.subject LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR f.submitter_name LIKE ? OR f.submitter_email LIKE ?)";
    $filterParams = array_merge($filterParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $filterTypes .= 'sssssss';
}

// Build the query
if ($canRespondToFeedback) {
    // Super Admin, Admin and Members can see all feedback
    $sql = "SELECT f.*, u.first_name, u.last_name, u.email,
                   a.first_name as assigned_first_name, a.last_name as assigned_last_name
            FROM feedback f
            LEFT JOIN users u ON f.user_id = u.user_id
            LEFT JOIN users a ON f.assigned_to = a.user_id";
    
    // Add where clause if there are filter conditions
    if (!empty($filterConditions)) {
        $sql .= " WHERE " . implode(" AND ", $filterConditions);
    }
    
    // Add order by
    $sql .= " ORDER BY f.created_at DESC";
    
    $feedbackSubmissions = fetchAll($sql, $filterParams, $filterTypes);
} else {
    // Regular users (students) can only see their own feedback
    $sql = "SELECT f.*, u.first_name, u.last_name, u.email,
                   a.first_name as assigned_first_name, a.last_name as assigned_last_name
            FROM feedback f
            LEFT JOIN users u ON f.user_id = u.user_id
            LEFT JOIN users a ON f.assigned_to = a.user_id
            WHERE f.user_id = ?";
    
    // Add additional filter conditions if there are any
    if (!empty($filterConditions)) {
        $sql .= " AND " . implode(" AND ", $filterConditions);
    }
    
    // Add order by
    $sql .= " ORDER BY f.created_at DESC";
    
    // Add user_id as the first parameter
    array_unshift($filterParams, $currentUser['user_id']);
    $filterTypes = 'i' . $filterTypes;
    
    $feedbackSubmissions = fetchAll($sql, $filterParams, $filterTypes);
}

// Get all members and admins for assignment dropdown
$membersSql = "SELECT user_id, first_name, last_name FROM users WHERE role IN ('admin', 'member') ORDER BY first_name, last_name";
$members = fetchAll($membersSql);

// Format feedback data for display
foreach ($feedbackSubmissions as &$feedback) {
    // Format name
    if (!empty($feedback['first_name'])) {
        // User exists in the users table
        $feedback['name'] = $feedback['first_name'] . ' ' . $feedback['last_name'];
        $feedback['email'] = $feedback['email'] ?? 'No email';
    } elseif (!empty($feedback['submitter_name'])) {
        // Anonymous submission with name provided
        $feedback['name'] = $feedback['submitter_name'] . ' (Direct Submission)';
        $feedback['email'] = $feedback['submitter_email'] ?? 'No email';
    } else {
        // Completely anonymous
        $feedback['name'] = 'Anonymous';
        $feedback['email'] = 'No email';
    }
    
    // Format status for display
    $feedback['status_display'] = ucfirst($feedback['status']);
    
    // Convert database timestamp to date string
    $feedback['date_submitted'] = $feedback['created_at'];
}

// Generate a simple CAPTCHA
$num1 = rand(1, 10);
$num2 = rand(1, 10);
$captchaAnswer = $num1 + $num2;
$captchaQuestion = "$num1 + $num2 = ?";

// Include header
require_once 'includes/header.php';
?>


<!-- Custom Feedback Header -->
<div class="feedback-header animate__animated animate__fadeInDown">
    <div class="feedback-header-content">
        <div class="feedback-header-main">
            <h1 class="feedback-title">
                <i class="fas fa-comments me-3"></i>
                Feedback & Suggestions
            </h1>
            <p class="feedback-description">Share your thoughts and help us improve the SRC experience</p>
        </div>
        <div class="feedback-header-actions">
            <?php if (!shouldUseAdminInterface()): ?>
            <!-- View Responses button for students only -->
            <a href="feedback-response.php" class="btn btn-header-action">
                <i class="fas fa-reply me-2"></i>View Responses
            </a>
            <?php endif; ?>
            <?php if ($canRespondToFeedback): ?>
            <!-- Manage Feedback button for admins/members only -->
            <a href="manage-feedback.php" class="btn btn-header-action">
                <i class="fas fa-cogs me-2"></i>Manage Feedback
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.feedback-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 80px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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
    color: white;
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
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-decoration: none;
}

/* Tablet and Below (< 992px) */
@media (max-width: 991px) {
    .feedback-header {
        margin-top: 60px;
        padding: 2rem 1.75rem;
        border-radius: 14px;
    }
}

@media (max-width: 768px) {
    .feedback-header {
        padding: 1.5rem 1rem;
        margin-top: 45px;
        margin-bottom: 1rem;
    }

    .feedback-header-content {
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .feedback-title {
        font-size: 1.8rem;
        gap: 0.5rem;
        text-align: center;
    }

    .feedback-title i {
        font-size: 1.6rem;
    }

    .feedback-description {
        font-size: 1rem;
        text-align: center;
        margin-bottom: 0.5rem;
    }

    .feedback-header-actions {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-header-action {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
        margin: 0.2rem;
        min-width: 120px;
    }
}

/* Mobile (< 576px) */
@media (max-width: 575px) {
    .feedback-header {
        padding: 1.25rem 1rem;
        margin-top: 32px;
        border-radius: 12px;
    }
}

@media (max-width: 480px) {
    .feedback-header {
        padding: 1rem 0.8rem;
        margin-top: 35px;
    }

    .feedback-title {
        font-size: 1.5rem;
        gap: 0.4rem;
    }

    .feedback-title i {
        font-size: 1.4rem;
    }

    .feedback-description {
        font-size: 0.9rem;
    }

    .btn-header-action {
        font-size: 0.8rem;
        padding: 0.35rem 0.7rem;
        min-width: 110px;
    }
}

@media (max-width: 375px) {
    .feedback-header {
        padding: 0.8rem 0.6rem;
        margin-top: 28px;
    }

    .feedback-title {
        font-size: 1.3rem;
        flex-direction: column;
        gap: 0.3rem;
    }

    .feedback-title i {
        font-size: 1.2rem;
    }

    .feedback-description {
        font-size: 0.85rem;
        line-height: 1.3;
    }

    .btn-header-action {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
        min-width: 100px;
    }
}

/* Extra Small Mobile (< 320px) */
@media (max-width: 319px) {
    .feedback-header {
        padding: 0.75rem 0.5rem;
        margin-top: 24px;
        border-radius: 8px;
    }
}

/* Animation classes */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translate3d(0, -100%, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

.animate__animated {
    animation-duration: 0.6s;
    animation-fill-mode: both;
}

.animate__fadeInDown {
    animation-name: fadeInDown;
}
</style>

<style>
/* Critical Mobile Viewport Fix */
html, body {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    position: relative;
}

* {
    box-sizing: border-box;
}

    .status-badge-pending {
        background-color: #dc3545;
    }
    .status-badge-in_progress {
        background-color: #ffc107;
    }
    .status-badge-resolved {
        background-color: #28a745;
    }
    .status-badge-rejected {
        background-color: #6c757d;
    }
    
    .feedback-item-pending {
        border-left: 4px solid #dc3545;
    }
    .feedback-item-in_progress {
        border-left: 4px solid #ffc107;
    }
    .feedback-item-resolved {
        border-left: 4px solid #28a745;
    }
    .feedback-item-rejected {
        border-left: 4px solid #6c757d;
    }
    
    .captcha-container {
        background-color: #f8f9fa;
        padding: 10px 15px;
        border-radius: 5px;
        font-size: 1.2rem;
        font-weight: bold;
        letter-spacing: 2px;
        margin-bottom: 10px;
        display: inline-block;
    }

    /* Enhanced Anonymous Submission Button Styling */
    .form-group.p-3.border.rounded {
        background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%) !important;
        border: 2px solid #2196f3 !important;
        border-radius: 12px !important;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .form-group.p-3.border.rounded::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        transition: left 0.5s;
    }

    .form-group.p-3.border.rounded:hover::before {
        left: 100%;
    }

    .form-group.p-3.border.rounded:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(33, 150, 243, 0.3);
        border-color: #1976d2 !important;
    }

    .form-check-label.fw-bold {
        color: #1565c0 !important;
        font-size: 1.1rem !important;
        font-weight: 600 !important;
        transition: color 0.3s ease;
    }

    .form-check-label.fw-bold:hover {
        color: #0d47a1 !important;
    }

    .form-check-input {
        width: 1.2rem !important;
        height: 1.2rem !important;
        border: 2px solid #2196f3 !important;
        border-radius: 4px !important;
        transition: all 0.3s ease;
    }

    .form-check-input:checked {
        background-color: #2196f3 !important;
        border-color: #2196f3 !important;
        box-shadow: 0 0 10px rgba(33, 150, 243, 0.5) !important;
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(33, 150, 243, 0.25) !important;
        border-color: #1976d2 !important;
    }

    .text-info {
        color: #1976d2 !important;
    }
    
    .why-feedback-matters li {
        margin-bottom: 8px;
    }
    
    /* Contact buttons styling */
    .contact-btn-group {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }

    .contact-btn {
        transition: all 0.2s ease;
    }

    .contact-btn:hover {
        transform: translateY(-2px);
    }

    .email-btn {
        background-color: #007bff;
        border-color: #007bff;
    }

    .call-btn {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
        font-weight: 500;
        padding: 8px 16px;
        text-align: center;
        border-radius: 4px;
        display: block;
        margin-top: 8px;
    }

    /* Make contact info cards more distinctive */
    .contact-info-card {
        border-left: 4px solid #007bff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

/* ===== MOBILE RESPONSIVE STYLES ===== */

/* Mobile Layout Optimizations */
@media (max-width: 991px) {
    html, body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden !important;
        position: relative;
    }

    .container-fluid {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
        max-width: 100%;
        width: 100%;
        overflow-x: hidden;
        position: relative;
    }

    /* Two-column layout becomes single column on mobile */
    .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .row .col-md-5,
    .row .col-md-7,
    .row .col-md-12 {
        margin-bottom: 1.5rem;
        width: 100%;
        max-width: 100%;
        padding-left: 0 !important;
        padding-right: 0 !important;
        overflow-x: hidden;
    }

    /* Card spacing optimization */
    .card {
        margin-bottom: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .card-body {
        padding: 1rem;
        overflow-x: hidden;
    }

    .card-header {
        padding: 0.75rem 1rem;
    }

    /* Form optimizations */
    .form-control,
    .form-select,
    textarea {
        padding: 0.75rem;
        font-size: 1rem;
        border-radius: 6px;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }

    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-text {
        font-size: 0.85rem;
        margin-top: 0.25rem;
    }

    /* Button optimizations */
    .btn {
        padding: 0.75rem 1.5rem;
        font-size: 0.95rem;
        border-radius: 6px;
        font-weight: 500;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }

    /* Statistics cards mobile layout */
    .row .col-md-3 {
        margin-bottom: 1rem;
        width: 100%;
        max-width: 100%;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .row .col-md-3 .card {
        margin-bottom: 0;
    }
}

/* Tablet Optimizations (768px - 991px) */
@media (min-width: 768px) and (max-width: 991px) {
    .container-fluid {
        padding-left: 1.5rem !important;
        padding-right: 1.5rem !important;
    }

    .feedback-header {
        padding: 2rem 1.5rem;
    }

    .card-body {
        padding: 1.25rem;
    }

    .btn {
        padding: 0.8rem 1.6rem;
        font-size: 1rem;
    }

    /* Statistics cards in 2x2 grid on tablets */
    .row .col-md-3:nth-child(1),
    .row .col-md-3:nth-child(2) {
        margin-bottom: 1rem;
    }
}

/* Mobile Phone Optimizations (max-width: 767px) */
@media (max-width: 767px) {
    html, body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden !important;
        position: relative;
    }

    .container-fluid {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
        max-width: 100%;
        width: 100%;
        overflow-x: hidden;
        position: relative;
    }

    .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    /* Form specific optimizations */
    .feedback-form-card {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .feedback-form-card .card-body {
        padding: 0.7rem;
        overflow-x: hidden;
    }

    .feedback-form-card form {
        width: 100%;
        overflow-x: hidden;
    }

    .form-group.p-3.border.rounded {
        padding: 0.5rem !important;
        margin-bottom: 0.8rem !important;
        overflow-x: hidden;
    }

    .form-check-label {
        font-size: 0.9rem !important;
        word-break: break-word;
        overflow-wrap: break-word;
    }

    .form-check-input {
        width: 1rem !important;
        height: 1rem !important;
        flex-shrink: 0;
    }

    .form-control,
    .form-select,
    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="number"],
    textarea {
        font-size: 16px !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0.6rem !important;
        box-sizing: border-box !important;
        overflow-x: hidden;
    }

    .form-label {
        font-size: 0.85rem !important;
        word-break: break-word;
        margin-bottom: 0.4rem;
    }

    .mb-3 {
        margin-bottom: 0.8rem !important;
        width: 100%;
        overflow-x: hidden;
    }

    /* Anonymous submission section mobile styling */
    .form-group.p-3.border.rounded {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 6px !important;
        width: 100%;
        box-sizing: border-box;
    }

    /* CAPTCHA mobile styling */
    .captcha-container {
        padding: 0.6rem 0.8rem;
        font-size: 1rem;
        text-align: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 1px solid #dee2e6;
        border-radius: 6px;
        width: 100%;
        box-sizing: border-box;
        word-break: break-word;
        overflow-x: hidden;
    }

    /* Information cards mobile styling */
    .why-feedback-matters li {
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        line-height: 1.4;
    }

    .card {
        width: 100%;
        box-sizing: border-box;
        overflow-x: hidden;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .card-body {
        overflow-x: hidden;
        word-break: break-word;
    }

    /* Statistics cards stack vertically */
    .row .col-md-3 {
        margin-bottom: 0.8rem;
        width: 100%;
        max-width: 100%;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .row .col-md-3:last-child {
        margin-bottom: 0;
    }

    .row .col-md-3 .card .card-body {
        padding: 0.8rem;
        text-align: center;
        overflow-x: hidden;
    }

    .row .col-md-3 .card h6 {
        font-size: 0.8rem;
        margin-bottom: 0.4rem;
        word-break: break-word;
    }

    .row .col-md-3 .card h3 {
        font-size: 1.3rem;
        margin-bottom: 0;
    }

    /* Button mobile styling */
    .btn {
        width: auto;
        font-size: 0.9rem;
        padding: 0.6rem 1rem;
        overflow-x: hidden;
        white-space: normal;
        word-break: break-word;
    }

    .btn.w-100 {
        width: 100% !important;
        box-sizing: border-box;
    }

    /* Input group and prepend/append */
    .input-group {
        width: 100%;
        overflow-x: hidden;
    }

    .input-group > * {
        max-width: 100%;
        overflow-x: hidden;
    }
}

/* Small Phone Optimizations (max-width: 480px) */
@media (max-width: 480px) {
    html, body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden !important;
        position: relative;
    }

    .container-fluid {
        padding-left: 0.4rem !important;
        padding-right: 0.4rem !important;
        max-width: 100%;
        width: 100%;
        overflow-x: hidden;
        position: relative;
    }

    .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .card {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        margin-left: 0 !important;
        margin-right: 0 !important;
        overflow-x: hidden;
    }

    .card-body {
        padding: 0.6rem;
        overflow-x: hidden;
        word-break: break-word;
    }

    .card-header {
        padding: 0.4rem 0.6rem;
    }

    .card-header h5 {
        font-size: 0.9rem;
        margin-bottom: 0;
    }

    .form-control,
    .form-select {
        padding: 0.5rem;
        font-size: 16px !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="number"],
    textarea {
        font-size: 16px !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0.5rem !important;
        box-sizing: border-box !important;
        overflow-x: hidden !important;
    }

    .btn {
        padding: 0.5rem 0.8rem;
        font-size: 0.75rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        word-break: break-word;
    }

    .btn.w-100 {
        width: 100% !important;
        padding: 0.6rem;
        font-size: 0.9rem;
        box-sizing: border-box;
    }

    /* Form labels and text smaller */
    .form-label {
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
    }

    .form-text {
        font-size: 0.7rem;
    }

    /* Anonymous submission section */
    .form-group.p-3.border.rounded {
        padding: 0.4rem !important;
        overflow-x: hidden;
    }

    .form-check-label {
        font-size: 0.8rem !important;
        word-break: break-word;
    }

    .form-check-input {
        width: 0.95rem !important;
        height: 0.95rem !important;
        flex-shrink: 0;
    }

    .form-group.p-3.border.rounded .form-check {
        margin: 0;
    }

    .form-group.p-3.border.rounded small {
        display: block;
        margin-top: 0.2rem;
        word-break: break-word;
        font-size: 0.7rem;
    }

    .mb-3 {
        margin-bottom: 0.6rem !important;
    }

    .row .col-md-3 {
        width: 100%;
        max-width: 100%;
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin-bottom: 0.6rem;
    }

    /* Statistics cards smaller on small phones */
    .row .col-md-3 .card h6 {
        font-size: 0.7rem;
    }

    .row .col-md-3 .card h3 {
        font-size: 1.1rem;
    }
}

/* Very Small Phone Optimizations (max-width: 375px) */
@media (max-width: 375px) {
    html, body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden !important;
        position: relative;
    }

    .container-fluid {
        padding-left: 0.3rem !important;
        padding-right: 0.3rem !important;
        max-width: 100%;
        width: 100%;
        overflow-x: hidden;
        position: relative;
    }

    .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .card {
        width: 100%;
        max-width: 100%;
        margin: 0 !important;
        border-radius: 6px;
        overflow-x: hidden;
    }

    .card-body {
        padding: 0.5rem;
        overflow-x: hidden;
        word-break: break-word;
        hyphens: auto;
    }

    .card-header {
        padding: 0.4rem 0.5rem;
    }

    .card-header h5 {
        font-size: 0.9rem;
        word-break: break-word;
    }

    .form-control,
    .form-select {
        padding: 0.45rem;
        font-size: 16px !important;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="number"],
    textarea {
        font-size: 16px !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0.45rem !important;
        box-sizing: border-box !important;
        overflow-x: hidden !important;
    }

    .btn {
        padding: 0.45rem 0.8rem;
        font-size: 0.75rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        word-break: break-word;
        hyphens: auto;
    }

    .btn.w-100 {
        width: 100% !important;
        padding: 0.6rem;
        font-size: 0.9rem;
        box-sizing: border-box;
    }

    .form-label {
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
        word-break: break-word;
    }

    .form-text {
        font-size: 0.7rem;
        word-break: break-word;
    }

    .form-group.p-3.border.rounded {
        padding: 0.4rem !important;
        margin-bottom: 0.6rem !important;
        overflow-x: hidden;
    }

    .form-check-label {
        font-size: 0.8rem !important;
        word-break: break-word;
        margin-left: 0.3rem;
    }

    .form-check-input {
        width: 0.95rem !important;
        height: 0.95rem !important;
        margin-right: 0.2rem;
        flex-shrink: 0;
    }

    .captcha-container {
        padding: 0.5rem 0.6rem;
        font-size: 0.95rem;
        word-break: break-word;
        overflow-x: hidden;
    }

    .row {
        margin-left: 0;
        margin-right: 0;
        overflow-x: hidden;
    }

    .row > [class*="col-"] {
        padding-left: 0;
        padding-right: 0;
        width: 100%;
        max-width: 100%;
        margin-bottom: 0.6rem;
        overflow-x: hidden;
    }

    .mb-3 {
        margin-bottom: 0.6rem !important;
        width: 100%;
        overflow-x: hidden;
    }

    /* Statistics cards extremely compact */
    .row .col-md-3 {
        width: 100%;
        max-width: 100%;
        margin-bottom: 0.6rem;
    }

    .row .col-md-3 .card .card-body {
        padding: 0.5rem;
    }

    .row .col-md-3 .card h6 {
        font-size: 0.7rem;
        margin-bottom: 0.2rem;
    }

    .row .col-md-3 .card h3 {
        font-size: 1.1rem;
    }

    .feedback-form-card {
        margin: 0;
        overflow-x: hidden;
    }

    /* Filter buttons stack */
    .row .col-12.text-end {
        text-align: center !important;
    }

    .row .col-12.text-end .btn {
        display: block;
        width: 100%;
        margin: 0.15rem 0;
        min-width: auto;
        box-sizing: border-box;
    }

    /* Feedback header responsive */
    .feedback-header {
        padding: 0.7rem 0.5rem;
    }

    .feedback-title {
        font-size: 1.2rem;
    }

    .feedback-description {
        font-size: 0.8rem;
    }
}

/* Table Mobile Optimizations */
@media (max-width: 991px) {
    .table-responsive {
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table {
        margin-bottom: 0;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .table th,
    .table td {
        padding: 0.6rem 0.4rem;
        vertical-align: middle;
    }

    .table th {
        font-size: 0.85rem;
        font-weight: 600;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    /* Action buttons in table */
    .table .btn {
        padding: 0.3rem 0.5rem;
        font-size: 0.8rem;
        margin: 0.1rem;
    }

    .table .d-flex {
        flex-wrap: wrap;
        gap: 0.2rem;
    }

    /* Status badges */
    .badge {
        font-size: 0.75rem;
        padding: 0.3rem 0.5rem;
    }

    /* Truncated text in table */
    .text-truncate {
        max-width: 150px !important;
    }
}

/* Card-Style Tables for Small Screens */
@media (max-width: 576px) {
    .table-responsive {
        overflow-x: visible;
    }

    .table,
    .table thead,
    .table tbody,
    .table th,
    .table td,
    .table tr {
        display: block;
    }

    .table thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }

    .table tr {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 1rem;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .table td {
        border: none;
        padding: 0.5rem 0;
        position: relative;
        padding-left: 35%;
        white-space: normal;
    }

    .table td:before {
        content: attr(data-label);
        position: absolute;
        left: 0;
        width: 30%;
        padding-right: 10px;
        white-space: nowrap;
        font-weight: 600;
        color: #495057;
        font-size: 0.85rem;
    }

    /* Add data labels for mobile table */
    .table td:nth-child(1):before { content: "ID:"; }
    .table td:nth-child(2):before { content: "Submitter:"; }
    .table td:nth-child(3):before { content: "Category:"; }
    .table td:nth-child(4):before { content: "Message:"; }
    .table td:nth-child(5):before { content: "Status:"; }
    .table td:nth-child(6):before { content: "Date:"; }
    .table td:nth-child(7):before { content: "Actions:"; }

    /* Action buttons mobile layout - Keep horizontal */
    .table td:nth-child(7) .d-flex {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 0.3rem;
        align-items: center;
        justify-content: flex-start;
    }

    .table td:nth-child(7) .btn {
        width: auto;
        min-width: 36px;
        height: 36px;
        justify-content: center;
        padding: 0.4rem;
        font-size: 0.8rem;
        margin: 0.1rem;
    }

    .text-truncate {
        max-width: none !important;
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: clip !important;
    }
}

/* Modal Mobile Optimizations */
@media (max-width: 991px) {
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }

    .modal-dialog.modal-lg,
    .modal-dialog.modal-xl {
        max-width: calc(100% - 1rem);
    }

    .modal-content {
        border-radius: 8px;
    }

    .modal-header {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid #dee2e6;
    }

    .modal-title {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .modal-body {
        padding: 1rem;
        max-height: 70vh;
        overflow-y: auto;
    }

    .modal-footer {
        padding: 0.8rem 1rem;
        border-top: 1px solid #dee2e6;
    }

    /* Modal form elements */
    .modal .form-control,
    .modal .form-select {
        padding: 0.6rem;
        font-size: 0.95rem;
    }

    .modal .btn {
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
    }

    /* Modal cards */
    .modal .card {
        margin-bottom: 1rem;
    }

    .modal .card-body {
        padding: 0.8rem;
    }

    .modal .card-header {
        padding: 0.6rem 0.8rem;
        font-size: 0.95rem;
    }

    /* Contact buttons in modals */
    .modal .contact-btn-group .btn {
        flex: 1;
        margin: 0.2rem;
        padding: 0.6rem;
        font-size: 0.85rem;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.25rem;
        max-width: calc(100% - 0.5rem);
    }

    .modal-header {
        padding: 0.6rem 0.8rem;
    }

    .modal-title {
        font-size: 1rem;
    }

    .modal-body {
        padding: 0.8rem;
        max-height: 75vh;
    }

    .modal-footer {
        padding: 0.6rem 0.8rem;
    }

    .modal .btn {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }

    /* Modal two-column layout becomes single column */
    .modal .row .col-md-6 {
        margin-bottom: 1rem;
    }

    .modal .contact-btn-group {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 0.3rem;
    }

    .modal .contact-btn-group .btn {
        flex: 1;
        min-width: 100px;
        margin: 0.2rem;
    }
}

/* Grid View Mobile Optimizations */
@media (max-width: 991px) {
    #gridView .col-md-6,
    #gridView .col-lg-4 {
        margin-bottom: 1rem;
    }

    #gridView .feedback-card {
        height: auto;
    }

    #gridView .card-body {
        padding: 1rem;
    }

    #gridView .action-buttons .btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        margin: 0.1rem;
    }
}

@media (max-width: 576px) {
    #gridView .col-md-6,
    #gridView .col-lg-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }

    #gridView .action-buttons .d-flex {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 0.3rem;
        justify-content: flex-start;
    }

    #gridView .action-buttons .btn {
        width: auto;
        min-width: 36px;
        height: 36px;
        justify-content: center;
        padding: 0.4rem;
        font-size: 0.8rem;
        margin: 0.1rem;
    }
}

/* Alert Mobile Optimizations */
@media (max-width: 576px) {
    .alert {
        padding: 0.8rem;
        font-size: 0.9rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    .alert .btn-close {
        padding: 0.5rem;
    }
}

/* Utility Classes for Mobile */
@media (max-width: 576px) {
    .d-mobile-block {
        display: block !important;
    }

    .d-mobile-none {
        display: none !important;
    }

    .text-mobile-center {
        text-align: center !important;
    }

    .w-mobile-100 {
        width: 100% !important;
    }

    .mb-mobile-3 {
        margin-bottom: 1rem !important;
    }

    .p-mobile-2 {
        padding: 0.5rem !important;
    }
}

/* Force Action Buttons to Display Horizontally on All Devices */
@media (max-width: 991px) {
    .d-flex.align-items-center {
        flex-direction: row !important;
        flex-wrap: wrap;
        gap: 0.3rem;
        justify-content: flex-start;
    }

    .action-buttons .d-flex,
    .table .d-flex {
        flex-direction: row !important;
        flex-wrap: wrap;
        gap: 0.3rem;
    }

    .action-buttons .btn,
    .table .btn {
        width: auto !important;
        min-width: 36px;
        height: 36px;
        margin: 0.1rem;
        padding: 0.4rem;
        font-size: 0.8rem;
    }
}

/* Override Global Mobile Header Spacing Fix for Feedback Header */
@media (max-width: 768px) {
    /* High specificity override for feedback header */
    .feedback-header.animate__animated.animate__fadeInDown {
        margin-top: 18px !important;
        margin-bottom: 1rem !important;
    }

    /* Additional specificity for feedback header */
    div.feedback-header {
        margin-top: 18px !important;
        margin-bottom: 1rem !important;
    }
}

@media (max-width: 480px) {
    .feedback-header.animate__animated.animate__fadeInDown {
        margin-top: 15px !important;
        margin-bottom: 1rem !important;
    }

    div.feedback-header {
        margin-top: 15px !important;
        margin-bottom: 1rem !important;
    }
}

@media (max-width: 375px) {
    .feedback-header.animate__animated.animate__fadeInDown {
        margin-top: 12px !important;
        margin-bottom: 0.8rem !important;
    }

    div.feedback-header {
        margin-top: 12px !important;
        margin-bottom: 0.8rem !important;
    }
}

@media (max-width: 320px) {
    .feedback-header.animate__animated.animate__fadeInDown {
        margin-top: 10px !important;
        margin-bottom: 0.8rem !important;
    }

    div.feedback-header {
        margin-top: 10px !important;
        margin-bottom: 0.8rem !important;
    }
}

/* Desktop override to ensure proper spacing */
@media (min-width: 769px) {
    .feedback-header.animate__animated.animate__fadeInDown,
    div.feedback-header {
        margin-top: 75px !important;
        margin-bottom: 1.5rem !important;
    }
}

/* Mobile Full-Width Optimization for Feedback Page */
@media (max-width: 991px) {
    [class*="col-md-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Ensure feedback header has border-radius on mobile */
    .feedback-header {
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .card, .feedback-form-card, .feedback-card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="container-fluid">
    
    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $successMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $errorMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            // Handle success alert
            const successAlert = document.getElementById('successAlert');
            if (successAlert) {
                setTimeout(function() {
                    const closeButton = successAlert.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.click();
                    } else {
                        successAlert.classList.remove('show');
                        setTimeout(function() {
                            successAlert.remove();
                        }, 150);
                    }
                }, 5000); // 5 seconds
            }
            
            // Handle error alert
            const errorAlert = document.getElementById('errorAlert');
            if (errorAlert) {
                setTimeout(function() {
                    const closeButton = errorAlert.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.click();
                    } else {
                        errorAlert.classList.remove('show');
                        setTimeout(function() {
                            errorAlert.remove();
                        }, 150);
                    }
                }, 5000); // 5 seconds
            }
        });
    </script>
    
    <div class="row">
        <!-- Show feedback form only if user has permission to create feedback and should not use admin interface -->
        <?php if (hasPermission('create', 'feedback') && !shouldUseAdminInterface()): ?>
        <div class="col-md-5">
            <!-- Feedback Form -->
            <div class="card feedback-form-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-comment me-2"></i> Submit Your Feedback</h5>
                </div>
                <div class="card-body">
                    <p>We value your feedback! Please use this form to share your suggestions, concerns, or comments with the SRC.</p>
                    
                    <form method="post" action="">
                        <!-- Anonymous toggle - enhanced visibility -->
                        <div class="form-group mb-4 p-3 border rounded" style="background-color: #f8f9fa; border-color: #dee2e6 !important;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_anonymous" name="is_anonymous"
                                       style="cursor: pointer;">
                                <label class="form-check-label fw-bold" for="is_anonymous" style="cursor: pointer;">
                                    <i class="fas fa-user-secret me-2 text-info"></i> Submit anonymously
                                </label>
                                <small class="form-text text-muted d-block mt-1">
                                    <i class="fas fa-info-circle me-1"></i>
                                    If checked, your name and email won't be linked to this feedback.
                                </small>
                            </div>
                        </div>
                        
                        <div id="contact-info-section">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo $currentUser['first_name'] . ' ' . $currentUser['last_name']; ?>">
                                <small class="form-text text-muted">Your name will help us address your feedback properly.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo $currentUser['email']; ?>">
                                <small class="form-text text-muted">We'll use this to follow up on your feedback if necessary.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="e.g., +233 20 123 4567">
                                <small class="form-text text-muted">Optional: Provide your phone number for faster communication.</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="portfolio" class="form-label">Select Category</label>
                            <select class="form-select" id="portfolio" name="portfolio" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($portfolioCategories as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" placeholder="Please provide your feedback, suggestion, or concern here..." required></textarea>
                        </div>
                        
                        <!-- CAPTCHA -->
                        <div class="mb-3">
                            <label class="form-label">Verification</label>
                            <div class="captcha-container">
                                <?php echo $captchaQuestion; ?>
                            </div>
                            <input type="hidden" name="captcha_answer" value="<?php echo $captchaAnswer; ?>">
                            <input type="number" class="form-control" name="captcha" placeholder="Enter the answer" required>
                            <small class="form-text text-muted">This helps us prevent spam submissions.</small>
                        </div>
                        
                        <button type="submit" name="submit_feedback" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-2"></i> Submit Feedback
                        </button>
                    </form>
                    
                    <!-- Add JavaScript to toggle contact info fields with mobile enhancements -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const anonymousCheckbox = document.getElementById('is_anonymous');
                            const contactInfoSection = document.getElementById('contact-info-section');
                            const nameField = document.getElementById('name');
                            const emailField = document.getElementById('email');
                            const phoneField = document.getElementById('phone');

                            // Function to toggle visibility of contact info with mobile-friendly animations
                            function toggleContactInfo() {
                                if (anonymousCheckbox && contactInfoSection) {
                                    if (anonymousCheckbox.checked) {
                                        // Smooth hide animation for mobile
                                        contactInfoSection.style.transition = 'all 0.3s ease';
                                        contactInfoSection.style.opacity = '0';
                                        contactInfoSection.style.transform = 'translateY(-10px)';

                                        setTimeout(function() {
                                            contactInfoSection.style.display = 'none';
                                            // Clear the fields when anonymous is selected
                                            if (nameField) nameField.value = '';
                                            if (emailField) emailField.value = '';
                                            if (phoneField) phoneField.value = '';
                                        }, 300);
                                    } else {
                                        // Smooth show animation for mobile
                                        contactInfoSection.style.display = 'block';
                                        contactInfoSection.style.opacity = '0';
                                        contactInfoSection.style.transform = 'translateY(-10px)';

                                        // Restore default values
                                        if (nameField) nameField.value = '<?php echo addslashes($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>';
                                        if (emailField) emailField.value = '<?php echo addslashes($currentUser['email']); ?>';

                                        setTimeout(function() {
                                            contactInfoSection.style.transition = 'all 0.3s ease';
                                            contactInfoSection.style.opacity = '1';
                                            contactInfoSection.style.transform = 'translateY(0)';
                                        }, 50);
                                    }
                                }
                            }

                            // Initial state check
                            if (anonymousCheckbox) {
                                toggleContactInfo();

                                // Add event listener for changes with mobile touch support
                                anonymousCheckbox.addEventListener('change', toggleContactInfo);

                                // Add touch-friendly styling for mobile
                                if (window.innerWidth <= 768) {
                                    anonymousCheckbox.style.transform = 'scale(1.2)';
                                    anonymousCheckbox.style.margin = '0 8px 0 0';

                                    const label = document.querySelector('label[for="is_anonymous"]');
                                    if (label) {
                                        label.style.cursor = 'pointer';
                                        label.style.userSelect = 'none';
                                        label.style.padding = '8px';
                                        label.style.borderRadius = '4px';
                                        label.style.transition = 'background-color 0.2s ease';

                                        label.addEventListener('touchstart', function() {
                                            label.style.backgroundColor = 'rgba(0, 123, 255, 0.1)';
                                        });

                                        label.addEventListener('touchend', function() {
                                            setTimeout(function() {
                                                label.style.backgroundColor = 'transparent';
                                            }, 150);
                                        });
                                    }
                                }
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="<?php echo (hasPermission('create', 'feedback') && !shouldUseAdminInterface()) ? 'col-md-7' : 'col-md-12'; ?>">
            <!-- Information Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle text-info me-2"></i> Why Your Feedback Matters</h5>
                    <p>The SRC is committed to continuously improving student experiences and services. Your feedback helps us:</p>
                    <ul class="why-feedback-matters">
                        <li>Identify areas that need improvement</li>
                        <li>Understand student concerns and priorities</li>
                        <li>Develop new initiatives that address real student needs</li>
                        <li>Measure the effectiveness of our current programs</li>
                    </ul>
                    <p>All feedback is reviewed by the relevant portfolio holders and discussed in SRC meetings.</p>
                </div>
            </div>
            
            <?php if ($canRespondToFeedback): ?>
            <!-- Admin/Member View: Feedback Management Dashboard -->
            <div class="card mb-4">
                <?php ?>
<div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-light mb-0">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Total Feedback</h6>
                                    <h3 class="mb-0"><?php echo count($feedbackSubmissions); ?></h3>
                                </div>
                            </div>
                        </div>
                        <?php
                            $pending = 0;
                            $inProgress = 0;
                            $resolved = 0;
                            $rejected = 0;
                            
                            foreach ($feedbackSubmissions as $item) {
                                if ($item['status'] === 'pending') $pending++;
                                if ($item['status'] === 'in_progress') $inProgress++;
                                if ($item['status'] === 'resolved') $resolved++;
                                if ($item['status'] === 'rejected') $rejected++;
                            }
                        ?>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white mb-0">
                                <div class="card-body text-center">
                                    <h6 class="text-white-50">Pending</h6>
                                    <h3 class="mb-0"><?php echo $pending; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark mb-0">
                                <div class="card-body text-center">
                                    <h6 class="text-dark-50">In Progress</h6>
                                    <h3 class="mb-0"><?php echo $inProgress; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white mb-0">
                                <div class="card-body text-center">
                                    <h6 class="text-white-50">Resolved</h6>
                                    <h3 class="mb-0"><?php echo $resolved; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin/Member View: Feedback Management -->
            <div class="card">
                <?php ?>
</div>
                
                <!-- Filter Options -->
                <div id="filterOptions" class="collapse">
                    <div class="card-body bg-light">
                        <form method="get" class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="filterStatus" class="form-label">Status</label>
                                    <select class="form-select" id="filterStatus" name="status">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="in_progress" <?php echo isset($_GET['status']) && $_GET['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo isset($_GET['status']) && $_GET['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="rejected" <?php echo isset($_GET['status']) && $_GET['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="filterType" class="form-label">Category</label>
                                    <select class="form-select" id="filterType" name="type">
                                        <option value="">All Categories</option>
                                        <?php foreach ($portfolioCategories as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo isset($_GET['type']) && $_GET['type'] === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="filterDate" class="form-label">Date Range</label>
                                    <select class="form-select" id="filterDate" name="date">
                                        <option value="">All Time</option>
                                        <option value="today" <?php echo isset($_GET['date']) && $_GET['date'] === 'today' ? 'selected' : ''; ?>>Today</option>
                                        <option value="week" <?php echo isset($_GET['date']) && $_GET['date'] === 'week' ? 'selected' : ''; ?>>This Week</option>
                                        <option value="month" <?php echo isset($_GET['date']) && $_GET['date'] === 'month' ? 'selected' : ''; ?>>This Month</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="searchTerm" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="searchTerm" name="search" placeholder="Search by keyword" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i> Apply Filters
                                </button>
                                <a href="feedback.php" class="btn btn-secondary">
                                    <i class="fas fa-redo me-1"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- List View (Default) -->
                <div class="card-body p-0" id="listView">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#ID</th>
                                    <th>Submitter</th>
                                    <th>Category</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($feedbackSubmissions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">No feedback submissions found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($feedbackSubmissions as $feedback): ?>
                                <tr>
                                    <td><?php echo $feedback['feedback_id']; ?></td>
                                    <td>
                                        <?php echo !empty($feedback['name']) ? htmlspecialchars($feedback['name']) : 'Anonymous'; ?><br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($feedback['email'] ?? 'No email'); ?>
                                            <?php if (!empty($feedback['submitter_phone'])): ?>
                                            <br><i class="fas fa-phone-alt me-1"></i><?php echo htmlspecialchars($feedback['submitter_phone']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td><?php echo $portfolioCategories[$feedback['subject']] ?? 'Other'; ?></td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;">
                                            <?php echo htmlspecialchars(substr($feedback['message'], 0, 100)); ?>
                                            <?php echo strlen($feedback['message']) > 100 ? '...' : ''; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge status-badge-<?php echo $feedback['status']; ?> text-white">
                                            <?php echo $feedback['status_display']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($feedback['date_submitted'])); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a class="btn btn-primary me-1" data-bs-toggle="modal" data-bs-target="#viewFeedbackModal<?php echo $feedback['feedback_id']; ?>" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-eye"></i>
                                            </a>
                                            <a class="btn btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#updateFeedbackModal<?php echo $feedback['feedback_id']; ?>" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-edit"></i>
                                            </a>
                                            <a class="btn btn-outline-danger me-1" data-bs-toggle="modal" data-bs-target="#deleteFeedbackModal<?php echo $feedback['feedback_id']; ?>" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 1px solid #dc3545;">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                    
                                            <?php 
                                            $contactEmail = !empty($feedback['email']) ? $feedback['email'] : (!empty($feedback['submitter_email']) ? $feedback['submitter_email'] : '');
                                            $contactPhone = !empty($feedback['submitter_phone']) ? $feedback['submitter_phone'] : '';
                                            $feedbackCategory = $portfolioCategories[$feedback['subject']] ?? 'Other';
                                            $emailSubject = urlencode("Response to Feedback #{$feedback['feedback_id']} - $feedbackCategory");
                                            ?>
                                            
                                            <?php if (!empty($contactEmail)): ?>
                                            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo htmlspecialchars($contactEmail); ?>&su=<?php echo $emailSubject; ?>" class="btn btn-primary me-1" target="_blank" style="width: auto; min-width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($contactPhone)): ?>
                                            <a href="tel:<?php echo htmlspecialchars(str_replace(' ', '', $contactPhone)); ?>" class="btn btn-info text-white me-1" style="width: auto; min-width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background-color: #4ecdf9; border-color: #4ecdf9;">
                                                <i class="fas fa-phone-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Grid View (Hidden by default) -->
                <div class="card-body d-none" id="gridView">
                    <div class="row">
                        <?php if (empty($feedbackSubmissions)): ?>
                        <div class="col-12 text-center py-4">
                            <p class="text-muted mb-0">No feedback submissions found.</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($feedbackSubmissions as $feedback): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 feedback-card feedback-item-<?php echo $feedback['status']; ?>">
                                <?php ?>
<div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <?php echo $portfolioCategories[$feedback['subject']] ?? 'Other'; ?>
                                    </h6>
                                    <p class="card-text">
                                        <?php echo htmlspecialchars(substr($feedback['message'], 0, 150)); ?>
                                        <?php echo strlen($feedback['message']) > 150 ? '...' : ''; ?>
                                    </p>
                                    <?php ?>
<?php 
                                    $contactEmail = !empty($feedback['email']) ? $feedback['email'] : (!empty($feedback['submitter_email']) ? $feedback['submitter_email'] : '');
                                    $contactPhone = !empty($feedback['submitter_phone']) ? $feedback['submitter_phone'] : '';
                                    $feedbackCategory = $portfolioCategories[$feedback['subject']] ?? 'Other';
                                    $emailSubject = urlencode("Response to Feedback #{$feedback['feedback_id']} - $feedbackCategory");
                                    ?>
                                    
                                    <div class="action-buttons mt-3">
                                        <div class="d-flex align-items-center">
                                            <a class="btn btn-primary me-1" data-bs-toggle="modal" data-bs-target="#viewFeedbackModal<?php echo $feedback['feedback_id']; ?>" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a class="btn btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#updateFeedbackModal<?php echo $feedback['feedback_id']; ?>" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a class="btn btn-outline-danger me-1" data-bs-toggle="modal" data-bs-target="#deleteFeedbackModal<?php echo $feedback['feedback_id']; ?>" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 1px solid #dc3545;">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                            
                                            <?php if (!empty($contactEmail) || !empty($contactPhone)): ?>
                                            <div class="mt-3">
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($contactEmail)): ?>
                                                    <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo htmlspecialchars($contactEmail); ?>&su=<?php echo $emailSubject; ?>" class="btn btn-primary me-2" target="_blank" style="height: 40px; display: flex; align-items: center; justify-content: center; flex-grow: 1;">
                                                        <i class="fas fa-envelope me-1"></i> Email
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($contactPhone)): ?>
                                                    <a href="tel:<?php echo htmlspecialchars(str_replace(' ', '', $contactPhone)); ?>" class="btn btn-info text-white" style="height: 40px; display: flex; align-items: center; justify-content: center; background-color: #4ecdf9; border-color: #4ecdf9; flex-grow: 1;">
                                                        <i class="fas fa-phone-alt me-1"></i> Call
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-muted">
                                    <div>From: <?php echo !empty($feedback['name']) ? htmlspecialchars($feedback['name']) : 'Anonymous'; ?></div>
                                    <?php if (!empty($feedback['submitter_phone'])): ?>
                                    <div><small><i class="fas fa-phone-alt me-1"></i><?php echo htmlspecialchars($feedback['submitter_phone']); ?></small></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- View/Response Modals for Each Feedback Item -->
            <?php foreach ($feedbackSubmissions as $feedback): ?>
            <!-- View Feedback Modal -->
            <div class="modal fade" id="viewFeedbackModal<?php echo $feedback['feedback_id']; ?>" tabindex="-1" aria-labelledby="viewFeedbackModalLabel<?php echo $feedback['feedback_id']; ?>" aria-hidden="true">
                <a id="feedback-<?php echo $feedback['feedback_id']; ?>"></a>
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewFeedbackModalLabel<?php echo $feedback['feedback_id']; ?>">
                                Feedback #<?php echo $feedback['feedback_id']; ?> - <?php echo $portfolioCategories[$feedback['subject']] ?? 'Other'; ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Submitter Information</h6>
                                    <p>
                                        <?php if (!empty($feedback['first_name'])): ?>
                                        <strong>Name:</strong> <?php echo htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']); ?><br>
                                        <strong>Email:</strong> <?php echo htmlspecialchars($feedback['email'] ?? 'No email'); ?><br>
                                        <?php if (!empty($feedback['submitter_phone'])): ?>
                                        <strong>Phone:</strong> <?php echo htmlspecialchars($feedback['submitter_phone']); ?><br>
                                        <?php endif; ?>
                                        <strong>Type:</strong> <span class="badge bg-info">Registered User</span>
                                        <?php elseif (!empty($feedback['submitter_name'])): ?>
                                        <strong>Name:</strong> <?php echo htmlspecialchars($feedback['submitter_name']); ?><br>
                                        <strong>Email:</strong> <?php echo htmlspecialchars($feedback['submitter_email'] ?? 'No email'); ?><br>
                                        <?php if (!empty($feedback['submitter_phone'])): ?>
                                        <strong>Phone:</strong> <?php echo htmlspecialchars($feedback['submitter_phone']); ?><br>
                                        <?php endif; ?>
                                        <strong>Type:</strong> <span class="badge bg-warning text-dark">Direct Submission</span>
                                        <?php else: ?>
                                        <strong>Name:</strong> Anonymous<br>
                                        <strong>Email:</strong> Not provided<br>
                                        <strong>Type:</strong> <span class="badge bg-secondary">Anonymous</span>
                                        <?php endif; ?>
                                        <br>
                                        <strong>Date Submitted:</strong> <?php echo date('F j, Y g:i a', strtotime($feedback['date_submitted'])); ?>
                                    </p>
                                    
                                    <!-- Contact action buttons -->
                                    <?php 
                                    $contactEmail = !empty($feedback['email']) ? $feedback['email'] : (!empty($feedback['submitter_email']) ? $feedback['submitter_email'] : '');
                                    $contactPhone = !empty($feedback['submitter_phone']) ? $feedback['submitter_phone'] : '';
                                    $feedbackCategory = $portfolioCategories[$feedback['subject']] ?? 'Other';
                                    $emailSubject = urlencode("Response to Feedback #{$feedback['feedback_id']} - $feedbackCategory");
                                    
                                    if (!empty($contactEmail) || !empty($contactPhone)): 
                                    ?>
                                    <div class="mt-3">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($contactEmail)): ?>
                                            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo htmlspecialchars($contactEmail); ?>&su=<?php echo $emailSubject; ?>" class="btn btn-primary me-2" target="_blank" style="height: 40px; display: flex; align-items: center; justify-content: center; flex-grow: 1;">
                                                <i class="fas fa-envelope me-1"></i> Email
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($contactPhone)): ?>
                                            <a href="tel:<?php echo htmlspecialchars(str_replace(' ', '', $contactPhone)); ?>" class="btn btn-info text-white" style="height: 40px; display: flex; align-items: center; justify-content: center; background-color: #4ecdf9; border-color: #4ecdf9; flex-grow: 1;">
                                                <i class="fas fa-phone-alt me-1"></i> Call
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Status Information</h6>
                                    <p>
                                        <strong>Current Status:</strong> 
                                        <span class="badge status-badge-<?php echo $feedback['status']; ?> text-white">
                                            <?php echo $feedback['status_display']; ?>
                                        </span><br>
                                        <strong>Category:</strong> <?php echo $portfolioCategories[$feedback['subject']] ?? 'Other'; ?><br>
                                        <strong>Assigned To:</strong> <?php echo !empty($feedback['assigned_to']) ? htmlspecialchars($feedback['assigned_first_name'] . ' ' . $feedback['assigned_last_name']) : 'Not assigned'; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Feedback Message</h6>
                                </div>
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    <p class="lead"><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($feedback['resolution'])): ?>
                            <div class="card mb-4 border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Admin Response</h6>
                                </div>
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    <p><?php echo nl2br(htmlspecialchars($feedback['resolution'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php ?>
</div>
                    </div>
                </div>
            </div>
            
            <!-- Update Feedback Modal -->
            <div class="modal fade" id="updateFeedbackModal<?php echo $feedback['feedback_id']; ?>" tabindex="-1" aria-labelledby="updateFeedbackModalLabel<?php echo $feedback['feedback_id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="updateFeedbackModalLabel<?php echo $feedback['feedback_id']; ?>">Update Feedback #<?php echo $feedback['feedback_id']; ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post" action="">
                            <div class="modal-body">
                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="card contact-info-card">
                                            <div class="card-header">Contact Information</div>
                                            <div class="card-body">
                                                <p>
                                                    <strong>Name:</strong> <?php echo !empty($feedback['name']) ? htmlspecialchars($feedback['name']) : 'Anonymous'; ?><br>
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($feedback['email'] ?? 'No email'); ?><br>
                                                    <?php if (!empty($feedback['submitter_phone'])): ?>
                                                    <strong>Phone:</strong> <?php echo htmlspecialchars($feedback['submitter_phone']); ?><br>
                                                    <?php endif; ?>
                                                </p>
                                                
                                                <!-- Contact action buttons -->
                                                <?php 
                                                $contactEmail = !empty($feedback['email']) ? $feedback['email'] : (!empty($feedback['submitter_email']) ? $feedback['submitter_email'] : '');
                                                $contactPhone = !empty($feedback['submitter_phone']) ? $feedback['submitter_phone'] : '';
                                                $feedbackCategory = $portfolioCategories[$feedback['subject']] ?? 'Other';
                                                $emailSubject = urlencode("Response to Feedback #{$feedback['feedback_id']} - $feedbackCategory");
                                                ?>
                                                
                                                <div class="mt-3">
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($contactEmail)): ?>
                                                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo htmlspecialchars($contactEmail); ?>&su=<?php echo $emailSubject; ?>" class="btn btn-primary me-2" target="_blank" style="height: 40px; display: flex; align-items: center; justify-content: center; flex-grow: 1;">
                                                            <i class="fas fa-envelope me-1"></i> Email
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($contactPhone)): ?>
                                                        <a href="tel:<?php echo htmlspecialchars(str_replace(' ', '', $contactPhone)); ?>" class="btn btn-info text-white" style="height: 40px; display: flex; align-items: center; justify-content: center; background-color: #4ecdf9; border-color: #4ecdf9; flex-grow: 1;">
                                                            <i class="fas fa-phone-alt me-1"></i> Call
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="newStatus<?php echo $feedback['feedback_id']; ?>" class="form-label">Status</label>
                                    <select class="form-select" id="newStatus<?php echo $feedback['feedback_id']; ?>" name="new_status">
                                        <option value="pending" <?php echo $feedback['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="in_progress" <?php echo $feedback['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $feedback['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="rejected" <?php echo $feedback['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="assignedTo<?php echo $feedback['feedback_id']; ?>" class="form-label">Assign To</label>
                                    <select class="form-select" id="assignedTo<?php echo $feedback['feedback_id']; ?>" name="assigned_to">
                                        <option value="">Select a member...</option>
                                        <option value="unassigned">Unassign</option>
                                        <?php foreach ($members as $member): ?>
                                        <option value="<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>"
                                                <?php echo ($feedback['assigned_first_name'] . ' ' . $feedback['assigned_last_name']) === ($member['first_name'] . ' ' . $member['last_name']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="response<?php echo $feedback['feedback_id']; ?>" class="form-label">Response</label>
                                    <textarea class="form-control" id="response<?php echo $feedback['feedback_id']; ?>" name="response" rows="6" placeholder="Enter your response to this feedback"><?php echo htmlspecialchars($feedback['resolution'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="emailResponse<?php echo $feedback['feedback_id']; ?>" name="email_response">
                                    <label class="form-check-label" for="emailResponse<?php echo $feedback['feedback_id']; ?>">
                                        Send response via email
                                    </label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="update_status" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Delete Feedback Modal -->
            <div class="modal fade" id="deleteFeedbackModal<?php echo $feedback['feedback_id']; ?>" tabindex="-1" aria-labelledby="deleteFeedbackModalLabel<?php echo $feedback['feedback_id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="deleteFeedbackModalLabel<?php echo $feedback['feedback_id']; ?>">Confirm Delete</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post" action="">
                            <div class="modal-body">
                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i> Are you sure you want to delete Feedback #<?php echo $feedback['feedback_id']; ?>?
                                </div>
                                
                                <p><strong>Category:</strong> <?php echo $portfolioCategories[$feedback['subject']] ?? 'Other'; ?></p>
                                <p><strong>From:</strong> <?php echo !empty($feedback['name']) ? htmlspecialchars($feedback['name']) : 'Anonymous'; ?></p>
                                
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <p class="card-text text-truncate"><?php echo htmlspecialchars(substr($feedback['message'], 0, 100)); ?><?php echo strlen($feedback['message']) > 100 ? '...' : ''; ?></p>
                                    </div>
                                </div>
                                
                                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. All feedback data will be permanently deleted.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="delete_feedback" class="btn btn-danger">
                                    <i class="fas fa-trash-alt me-1"></i> Delete Permanently
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Add JavaScript for toggling between List and Grid views and Mobile Responsiveness -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const toggleViewBtn = document.getElementById('toggleView');
                    const viewText = document.getElementById('viewText');
                    const listView = document.getElementById('listView');
                    const gridView = document.getElementById('gridView');

                    if (toggleViewBtn) {
                        toggleViewBtn.addEventListener('click', function() {
                            if (listView.classList.contains('d-none')) {
                                // Switch to List View
                                listView.classList.remove('d-none');
                                gridView.classList.add('d-none');
                                viewText.textContent = 'Switch to Grid View';
                            } else {
                                // Switch to Grid View
                                listView.classList.add('d-none');
                                gridView.classList.remove('d-none');
                                viewText.textContent = 'Switch to List View';
                            }
                        });
                    }

                    // Mobile Table Responsiveness
                    function initMobileTable() {
                        const tables = document.querySelectorAll('.table');

                        tables.forEach(function(table) {
                            // Add mobile-responsive class
                            if (window.innerWidth <= 576) {
                                table.classList.add('table-mobile-cards');
                            } else {
                                table.classList.remove('table-mobile-cards');
                            }
                        });
                    }

                    // Initialize mobile table on load
                    initMobileTable();

                    // Re-initialize on window resize
                    window.addEventListener('resize', function() {
                        initMobileTable();
                    });

                    // Mobile Modal Optimizations
                    const modals = document.querySelectorAll('.modal');
                    modals.forEach(function(modal) {
                        modal.addEventListener('shown.bs.modal', function() {
                            // Adjust modal height for mobile
                            if (window.innerWidth <= 576) {
                                const modalBody = modal.querySelector('.modal-body');
                                if (modalBody) {
                                    modalBody.style.maxHeight = '70vh';
                                    modalBody.style.overflowY = 'auto';
                                }
                            }
                        });
                    });

                    // Mobile-specific UI enhancements
                    if (window.innerWidth <= 768) {
                        // Add touch-friendly classes to buttons
                        const buttons = document.querySelectorAll('.btn');
                        buttons.forEach(function(btn) {
                            btn.style.minHeight = '44px'; // Touch-friendly minimum height
                        });

                        // Improve form field spacing on mobile
                        const formGroups = document.querySelectorAll('.mb-3');
                        formGroups.forEach(function(group) {
                            if (window.innerWidth <= 375) {
                                group.style.marginBottom = '0.8rem';
                            }
                        });
                    }
                });
            </script>
            <?php else: ?>
            <!-- Regular User: Feedback Guidelines -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i> Feedback Guidelines</h5>
                </div>
                <div class="card-body">
                    <h6>For best results, please:</h6>
                    <ul class="why-feedback-matters">
                        <li>Be specific about your concern or suggestion</li>
                        <li>Provide relevant details and context</li>
                        <li>Suggest solutions if possible</li>
                        <li>Use respectful and constructive language</li>
                        <li>Include your contact information if you'd like a direct response</li>
                    </ul>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> All feedback is reviewed by our team and kept confidential. If you've provided your email, we'll follow up with you regarding your submission.
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Mobile Optimization Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile viewport optimization
    function optimizeForMobile() {
        const isMobile = window.innerWidth <= 768;
        const isSmallMobile = window.innerWidth <= 480;
        const isVerySmallMobile = window.innerWidth <= 375;

        // Add mobile classes to body for CSS targeting
        document.body.classList.toggle('mobile-device', isMobile);
        document.body.classList.toggle('small-mobile', isSmallMobile);
        document.body.classList.toggle('very-small-mobile', isVerySmallMobile);

        // Optimize touch targets for mobile
        if (isMobile) {
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(function(btn) {
                if (!btn.style.minHeight) {
                    btn.style.minHeight = '44px';
                }
                btn.style.touchAction = 'manipulation';
            });

            // Optimize form inputs for mobile
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(function(input) {
                input.style.fontSize = Math.max(16, parseInt(getComputedStyle(input).fontSize)) + 'px';
            });

            // Optimize table scrolling for mobile
            const tableContainers = document.querySelectorAll('.table-responsive');
            tableContainers.forEach(function(container) {
                container.style.webkitOverflowScrolling = 'touch';
                container.style.overflowX = 'auto';
            });
        }

        // Optimize modal positioning for mobile
        const modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            if (isMobile) {
                modal.addEventListener('shown.bs.modal', function() {
                    const modalDialog = modal.querySelector('.modal-dialog');
                    if (modalDialog) {
                        modalDialog.style.margin = '0.5rem';
                        modalDialog.style.maxWidth = 'calc(100% - 1rem)';
                    }
                });
            }
        });
    }

    // Initial optimization
    optimizeForMobile();

    // Re-optimize on window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(optimizeForMobile, 250);
    });

    // Prevent zoom on input focus for iOS
    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            input.addEventListener('focus', function() {
                input.style.fontSize = '16px';
            });
        });
    }

    // Smooth scrolling for mobile anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target && window.innerWidth <= 768) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
