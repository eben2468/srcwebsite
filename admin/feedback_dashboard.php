<?php
// Include authentication file and database config
require_once '../auth_functions.php';
require_once '../db_config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../pages_php/login.php");
    exit();
}

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

// Build the query for feedback list
$sql = "SELECT f.*, u.first_name, u.last_name, u.email 
        FROM feedback f 
        LEFT JOIN users u ON f.user_id = u.user_id";

// Add where clause if there are filter conditions
if (!empty($filterConditions)) {
    $sql .= " WHERE " . implode(" AND ", $filterConditions);
}

// Add order by
$sql .= " ORDER BY f.created_at DESC";

// Fetch feedback submissions
$feedbackSubmissions = fetchAll($sql, $filterParams, $filterTypes);

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

// Fetch feedback counts
$totalFeedbackQuery = "SELECT COUNT(*) as total FROM feedback";
$totalFeedback = fetchOne($totalFeedbackQuery)['total'] ?? 0;

$pendingFeedbackQuery = "SELECT COUNT(*) as count FROM feedback WHERE status = 'pending'";
$pendingCount = fetchOne($pendingFeedbackQuery)['count'] ?? 0;

$inProgressFeedbackQuery = "SELECT COUNT(*) as count FROM feedback WHERE status = 'in_progress'";
$inProgressCount = fetchOne($inProgressFeedbackQuery)['count'] ?? 0;

$resolvedFeedbackQuery = "SELECT COUNT(*) as count FROM feedback WHERE status = 'resolved'";
$resolvedCount = fetchOne($resolvedFeedbackQuery)['count'] ?? 0;

$rejectedFeedbackQuery = "SELECT COUNT(*) as count FROM feedback WHERE status = 'rejected'";
$rejectedCount = fetchOne($rejectedFeedbackQuery)['count'] ?? 0;

// Fetch category counts
$categoryCountsQuery = "SELECT subject, COUNT(*) as count FROM feedback GROUP BY subject ORDER BY count DESC";
$categoryCounts = fetchAll($categoryCountsQuery);

// Initialize messages
$successMessage = '';
$errorMessage = '';

// Define path prefix for sidebar links
$GLOBALS['path_prefix'] = '../pages_php/';

// Include header
require_once '../pages_php/includes/header.php';

// Process admin actions if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Check if admin has permission to update feedback
    if (hasPermission('update', 'feedback')) {
        $feedbackId = $_POST['feedback_id'] ?? 0;
        $newStatus = $_POST['new_status'] ?? '';
        $assignedTo = $_POST['assigned_to'] ?? '';
        $response = $_POST['response'] ?? '';
        $sendEmail = isset($_POST['email_response']) && $_POST['email_response'] === 'on';
        
        // Update feedback in database
        $sql = "UPDATE feedback SET 
                status = ?, 
                assigned_to = ?, 
                resolution = ?,
                updated_at = CURRENT_TIMESTAMP 
                WHERE feedback_id = ?";
        
        $result = update($sql, [$newStatus, $assignedTo, $response, $feedbackId]);
        
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
                
                if ($feedbackDetails) {
                    if (!empty($feedbackDetails['email'])) {
                        // User from database
                        $recipientEmail = $feedbackDetails['email'];
                        $recipientName = $feedbackDetails['first_name'] . ' ' . $feedbackDetails['last_name'];
                    } elseif (!empty($feedbackDetails['submitter_email'])) {
                        // Direct submission with email
                        $recipientEmail = $feedbackDetails['submitter_email'];
                        $recipientName = !empty($feedbackDetails['submitter_name']) ? $feedbackDetails['submitter_name'] : 'User';
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
        $errorMessage = "You do not have permission to update feedback.";
    }
}

// Page title
$pageTitle = "Admin Feedback Dashboard";

?>

<style>
    /* Status badge colors */
    .status-badge-pending {
        background-color: #dc3545 !important; /* Red */
    }
    .status-badge-in_progress {
        background-color: #fd7e14 !important; /* Orange */
    }
    .status-badge-resolved {
        background-color: #28a745 !important; /* Green */
    }
    .status-badge-rejected {
        background-color: #6c757d !important; /* Gray */
    }
    
    /* Feedback card borders based on status */
    .feedback-item-pending {
        border-left: 4px solid #dc3545;
    }
    .feedback-item-in_progress {
        border-left: 4px solid #fd7e14;
    }
    .feedback-item-resolved {
        border-left: 4px solid #28a745;
    }
    .feedback-item-rejected {
        border-left: 4px solid #6c757d;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Admin Feedback Dashboard</h1>
        <a href="<?php echo $GLOBALS['path_prefix']; ?>feedback.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i> Back to Feedback Page
        </a>
    </div>

    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $successMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $errorMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-light h-100">
                <div class="card-body text-center">
                    <h3 class="display-4"><?php echo $totalFeedback; ?></h3>
                    <p class="text-muted mb-0">Total Feedback</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body text-center">
                    <h3 class="display-4"><?php echo $pendingCount; ?></h3>
                    <p class="text-white-50 mb-0">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body text-center">
                    <h3 class="display-4"><?php echo $inProgressCount; ?></h3>
                    <p class="text-dark-50 mb-0">In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <h3 class="display-4"><?php echo $resolvedCount; ?></h3>
                    <p class="text-white-50 mb-0">Resolved</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="row">
        <!-- Category Statistics -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Feedback by Category</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($categoryCounts)): ?>
                    <p class="text-center text-muted">No category data available.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($categoryCounts as $category): 
                                    $percentage = ($category['count'] / $totalFeedback) * 100;
                                ?>
                                <tr>
                                    <td><?php echo $portfolioCategories[$category['subject']] ?? $category['subject']; ?></td>
                                    <td><?php echo $category['count']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%;" 
                                                 aria-valuenow="<?php echo $percentage; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo round($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
            
        <!-- Recent Activity -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Feedback Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <h2 class="text-primary"><?php echo $totalFeedback; ?></h2>
                            <p class="text-muted">Total Submissions</p>
                        </div>
                        <div class="col-6">
                            <h2 class="text-success"><?php echo $resolvedCount; ?></h2>
                            <p class="text-muted">Resolved</p>
                        </div>
                    </div>
                    
                    <h6 class="text-muted mb-2">Resolution Rate</h6>
                    <?php $resolutionRate = $totalFeedback > 0 ? ($resolvedCount / $totalFeedback) * 100 : 0; ?>
                    <div class="progress mb-4" style="height: 25px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo $resolutionRate; ?>%;" 
                             aria-valuenow="<?php echo $resolutionRate; ?>" 
                             aria-valuemin="0" aria-valuemax="100">
                            <?php echo round($resolutionRate, 1); ?>%
                        </div>
                    </div>
                    
                    <h6 class="text-muted mb-2">Status Breakdown</h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Pending</span>
                            <span class="badge bg-danger"><?php echo $pendingCount; ?></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-danger" role="progressbar" 
                                 style="width: <?php echo $totalFeedback > 0 ? ($pendingCount / $totalFeedback) * 100 : 0; ?>%;" 
                                 aria-valuenow="<?php echo $pendingCount; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo $totalFeedback; ?>"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span>In Progress</span>
                            <span class="badge bg-warning text-dark"><?php echo $inProgressCount; ?></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: <?php echo $totalFeedback > 0 ? ($inProgressCount / $totalFeedback) * 100 : 0; ?>%;" 
                                 aria-valuenow="<?php echo $inProgressCount; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo $totalFeedback; ?>"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Resolved</span>
                            <span class="badge bg-success"><?php echo $resolvedCount; ?></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $totalFeedback > 0 ? ($resolvedCount / $totalFeedback) * 100 : 0; ?>%;" 
                                 aria-valuenow="<?php echo $resolvedCount; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo $totalFeedback; ?>"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Rejected</span>
                            <span class="badge bg-secondary"><?php echo $rejectedCount; ?></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-secondary" role="progressbar" 
                                 style="width: <?php echo $totalFeedback > 0 ? ($rejectedCount / $totalFeedback) * 100 : 0; ?>%;" 
                                 aria-valuenow="<?php echo $rejectedCount; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo $totalFeedback; ?>"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Card -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Feedback</h5>
                </div>
                <div class="card-body">
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
                            <a href="feedback_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback List Card -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Feedback Management</h5>
            <div>
                <button class="btn btn-sm btn-outline-light" id="toggleView">
                    <i class="fas fa-th-list me-1"></i> <span id="viewText">Switch to Grid View</span>
                </button>
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
                                <small class="text-muted"><?php echo htmlspecialchars($feedback['email'] ?? 'No email'); ?></small>
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
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewFeedbackModal<?php echo $feedback['feedback_id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary ms-1" data-bs-toggle="modal" data-bs-target="#updateFeedbackModal<?php echo $feedback['feedback_id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
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
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Feedback #<?php echo $feedback['feedback_id']; ?></span>
                            <span class="badge status-badge-<?php echo $feedback['status']; ?> text-white">
                                <?php echo $feedback['status_display']; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">
                                <?php echo $portfolioCategories[$feedback['subject']] ?? 'Other'; ?>
                            </h6>
                            <p class="card-text">
                                <?php echo htmlspecialchars(substr($feedback['message'], 0, 150)); ?>
                                <?php echo strlen($feedback['message']) > 150 ? '...' : ''; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt me-1"></i> <?php echo date('M j, Y', strtotime($feedback['date_submitted'])); ?>
                                </small>
                                <div>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewFeedbackModal<?php echo $feedback['feedback_id']; ?>">
                                        <i class="fas fa-eye me-1"></i> View
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-muted">
                            From: <?php echo !empty($feedback['name']) ? htmlspecialchars($feedback['name']) : 'Anonymous'; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add JavaScript for toggling between List and Grid views -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleViewBtn = document.getElementById('toggleView');
        const viewText = document.getElementById('viewText');
        const listView = document.getElementById('listView');
        const gridView = document.getElementById('gridView');
        
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
    });
</script>

<!-- Add modals for each feedback submission -->
<?php foreach ($feedbackSubmissions as $feedback): ?>
<!-- View Feedback Modal -->
<div class="modal fade" id="viewFeedbackModal<?php echo $feedback['feedback_id']; ?>" tabindex="-1" aria-labelledby="viewFeedbackModalLabel<?php echo $feedback['feedback_id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
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
                            <strong>Type:</strong> <span class="badge bg-info">Registered User</span>
                            <?php elseif (!empty($feedback['submitter_name'])): ?>
                            <strong>Name:</strong> <?php echo htmlspecialchars($feedback['submitter_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($feedback['submitter_email'] ?? 'No email'); ?><br>
                            <strong>Type:</strong> <span class="badge bg-warning text-dark">Direct Submission</span>
                            <?php else: ?>
                            <strong>Name:</strong> Anonymous<br>
                            <strong>Email:</strong> Not provided<br>
                            <strong>Type:</strong> <span class="badge bg-secondary">Anonymous</span>
                            <?php endif; ?>
                            <br>
                            <strong>Date Submitted:</strong> <?php echo date('F j, Y g:i a', strtotime($feedback['date_submitted'])); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Status Information</h6>
                        <p>
                            <strong>Current Status:</strong> 
                            <span class="badge status-badge-<?php echo $feedback['status']; ?> text-white">
                                <?php echo $feedback['status_display']; ?>
                            </span><br>
                            <strong>Category:</strong> <?php echo $portfolioCategories[$feedback['subject']] ?? 'Other'; ?><br>
                            <strong>Assigned To:</strong> <?php echo !empty($feedback['assigned_to']) ? htmlspecialchars($feedback['assigned_to']) : 'Not assigned'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Feedback Message</h6>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></p>
                    </div>
                </div>
                
                <?php if (!empty($feedback['resolution'])): ?>
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">Admin Response</h6>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($feedback['resolution'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateFeedbackModal<?php echo $feedback['feedback_id']; ?>" onclick="$('#viewFeedbackModal<?php echo $feedback['feedback_id']; ?>').modal('hide');">
                        <i class="fas fa-edit me-1"></i> Update & Respond
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Feedback Modal -->
<div class="modal fade" id="updateFeedbackModal<?php echo $feedback['feedback_id']; ?>" tabindex="-1" aria-labelledby="updateFeedbackModalLabel<?php echo $feedback['feedback_id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateFeedbackModalLabel<?php echo $feedback['feedback_id']; ?>">Update Feedback #<?php echo $feedback['feedback_id']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                    
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
                        <input type="text" class="form-control" id="assignedTo<?php echo $feedback['feedback_id']; ?>" name="assigned_to" value="<?php echo htmlspecialchars($feedback['assigned_to'] ?? ''); ?>" placeholder="Enter name of person to handle this feedback">
                    </div>
                    
                    <div class="mb-3">
                        <label for="response<?php echo $feedback['feedback_id']; ?>" class="form-label">Response</label>
                        <textarea class="form-control" id="response<?php echo $feedback['feedback_id']; ?>" name="response" rows="5" placeholder="Enter your response to this feedback"><?php echo htmlspecialchars($feedback['resolution'] ?? ''); ?></textarea>
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
<?php endforeach; ?>

<?php require_once '../pages_php/includes/footer.php'; ?> 