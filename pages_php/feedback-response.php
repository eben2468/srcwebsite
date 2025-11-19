<?php
session_start();
require_once '../includes/simple_auth.php';
require_once '../includes/db_config.php';
require_once '../includes/functions.php';

// Require login for this page
requireLogin();

// Check if user is logged in - redirect to login if not
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Check if user is admin or member (they should use manage-feedback.php instead)
if (isAdmin() || isMember()) {
    header("Location: feedback.php");
    exit();
}

$currentUser = $_SESSION;
$feedback_id = $_GET['feedback_id'] ?? 0;

// If no specific feedback_id is provided, show all user's feedback with responses
if ($feedback_id == 0) {
    // Get all feedback submissions by the current user that have responses
    $sql = "SELECT f.*, a.first_name as assigned_first_name, a.last_name as assigned_last_name
            FROM feedback f
            LEFT JOIN users a ON f.assigned_to = a.user_id
            WHERE f.user_id = ? ORDER BY f.created_at DESC";
    $allFeedback = fetchAll($sql, [$currentUser['user_id']]);
    $showAllFeedback = true;
} else {
    // Get specific feedback details and response
    $sql = "SELECT f.*, a.first_name as assigned_first_name, a.last_name as assigned_last_name
            FROM feedback f
            LEFT JOIN users a ON f.assigned_to = a.user_id
            WHERE f.feedback_id = ? AND f.user_id = ?";
    $feedback = fetchOne($sql, [$feedback_id, $currentUser['user_id']]);

    if (!$feedback) {
        header("Location: feedback.php");
        exit();
    }
    $showAllFeedback = false;
}

// Mark notification as read if coming from notification
if (isset($_GET['notification_id'])) {
    $notificationId = $_GET['notification_id'];
    $updateSql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    update($updateSql, [$notificationId, $currentUser['user_id']]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Response - VVUSRC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        /* Basic layout styling */
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Feedback Header Styling - Matching feedback.php */
        .feedback-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 12px;
            margin-top: 60px;
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

        /* Ensure button text and icon are always white */
        .btn-header-action,
        .btn-header-action:visited,
        .btn-header-action:focus,
        .btn-header-action:active,
        .btn-header-action i {
            color: white !important;
        }



        /* Content area styling */
        .content-area {
            padding: 1rem 1.5rem;
            width: 100%;
            margin: 0;
        }

        /* Ensure cards use full width */
        .row {
            margin-left: 0;
            margin-right: 0;
        }

        .col-md-3, .col-md-6, .col-md-12 {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        /* Card styling improvements */
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 2rem;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .card-header {
            border-radius: 15px 15px 0 0 !important;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding: 1.25rem 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Overview cards styling */
        .row .card {
            margin-bottom: 1rem;
        }

        .row .card .card-body {
            padding: 1.25rem;
        }

        .row .card h6 {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }

        .row .card h4 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* Status badge styling */
        .badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }



        /* Responsive design - Matching feedback.php */
        @media (max-width: 768px) {
            .feedback-header {
                padding: 2rem 1.5rem;
            }

            .feedback-header-content {
                flex-direction: column;
                align-items: center;
            }

            .feedback-title {
                font-size: 2rem;
                gap: 0.6rem;
            }

            .feedback-title i {
                font-size: 1.8rem;
            }

            .feedback-description {
                font-size: 1.1rem;
            }

            .feedback-header-actions {
                width: 100%;
                justify-content: center;
            }

            .btn-header-action {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }

            .content-area {
                padding: 1rem;
            }

            .card-body {
                padding: 1rem;
            }
        }

        /* Animation classes - Matching feedback.php */
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
            animation-duration: 1s;
            animation-fill-mode: both;
        }

        .animate__fadeInDown {
            animation-name: fadeInDown;
        }

        /* Mobile Full-Width Optimization for Feedback Response Page */
        @media (max-width: 991px) {
            [class*="col-md-"], [class*="col-lg-"], [class*="col-xl-"] {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            
            .container-fluid, .content-area {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            
            .feedback-header {
                border-radius: 12px !important;
            }
            
            .card {
                margin-left: 0 !important;
                margin-right: 0 !important;
                border-radius: 0 !important;
            }
            
            .row {
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

<!-- Custom Feedback Header -->
<div class="feedback-header animate__animated animate__fadeInDown">
    <div class="feedback-header-content">
        <div class="feedback-header-main">
            <h1 class="feedback-title">
                <i class="fas fa-comment-dots me-3"></i>
                <?php echo $showAllFeedback ? 'My Feedback Responses' : 'Feedback Response'; ?>
            </h1>
            <p class="feedback-description"><?php echo $showAllFeedback ? 'View all responses to your feedback submissions' : 'View the response to your feedback submission'; ?></p>
        </div>
        <div class="feedback-header-actions">
            <a href="feedback.php" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Feedback
            </a>
        </div>
    </div>
</div>

                <!-- Content Area -->
                <div class="content-area">
                    <?php if ($showAllFeedback): ?>
                    <!-- Show all feedback responses -->
                    <?php if (empty($allFeedback)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="text-muted">
                                <div class="mb-4">
                                    <i class="fas fa-comment-slash fa-4x text-muted"></i>
                                </div>
                                <h4 class="mb-3">No Feedback Submitted</h4>
                                <p class="lead">You haven't submitted any feedback yet.</p>
                                <a href="feedback.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Submit Feedback
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-comment-alt fa-2x me-3"></i>
                                        <div>
                                            <h6 class="card-title mb-0">Total</h6>
                                            <h4 class="mb-0"><?php echo count($allFeedback); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-check-circle fa-2x me-3"></i>
                                        <div>
                                            <h6 class="card-title mb-0">Resolved</h6>
                                            <h4 class="mb-0"><?php echo count(array_filter($allFeedback, function($f) { return $f['status'] === 'resolved'; })); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-clock fa-2x me-3"></i>
                                        <div>
                                            <h6 class="card-title mb-0">Pending</h6>
                                            <h4 class="mb-0"><?php echo count(array_filter($allFeedback, function($f) { return $f['status'] === 'pending'; })); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-reply fa-2x me-3"></i>
                                        <div>
                                            <h6 class="card-title mb-0">Responses</h6>
                                            <h4 class="mb-0"><?php echo count(array_filter($allFeedback, function($f) { return !empty($f['resolution']); })); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List of all feedback -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Your Feedback Submissions</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#ID</th>
                                            <th>Category</th>
                                            <th>Message</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Response</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allFeedback as $feedbackItem): ?>
                                        <tr>
                                            <td><?php echo $feedbackItem['feedback_id']; ?></td>
                                            <td><?php echo htmlspecialchars($feedbackItem['subject']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars(substr($feedbackItem['message'], 0, 50)); ?>
                                                <?php echo strlen($feedbackItem['message']) > 50 ? '...' : ''; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $feedbackItem['status'] === 'resolved' ? 'success' : ($feedbackItem['status'] === 'pending' ? 'warning' : 'primary'); ?>">
                                                    <?php echo ucfirst($feedbackItem['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($feedbackItem['created_at'])); ?></td>
                                            <td>
                                                <?php if (!empty($feedbackItem['resolution'])): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Received
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-clock me-1"></i>Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="feedback-response.php?feedback_id=<?php echo $feedbackItem['feedback_id']; ?>"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <!-- Show specific feedback response -->
                    <!-- Feedback Overview Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-comment-alt fa-2x me-3"></i>
                                        <div>
                                            <h6 class="card-title mb-0">Status</h6>
                                            <h4 class="mb-0"><?php echo ucfirst($feedback['status']); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-calendar-plus fa-2x me-3"></i>
                                        <div>
                                            <h6 class="card-title mb-0">Submitted</h6>
                                            <h4 class="mb-0"><?php echo date('M j', strtotime($feedback['created_at'])); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-tag fa-2x me-3"></i>
                                        <div>
                                            <h6 class="card-title mb-0">Category</h6>
                                            <h4 class="mb-0"><?php echo htmlspecialchars(substr($feedback['subject'], 0, 8)); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-<?php echo $feedback['status'] === 'resolved' ? 'success' : ($feedback['status'] === 'pending' ? 'secondary' : 'primary'); ?> text-white">
                                <div class="card-body text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-<?php echo $feedback['status'] === 'resolved' ? 'check-circle' : ($feedback['status'] === 'pending' ? 'clock' : 'cog'); ?> fa-2x me-3"></i>
                                        <div>
                                            <h6 class="card-title mb-0">Response</h6>
                                            <h4 class="mb-0"><?php echo $feedback['resolution'] ? 'Received' : 'Pending'; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feedback Details Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-comment-alt me-2 text-primary"></i>Your Feedback</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong><i class="fas fa-calendar me-2"></i>Submitted:</strong>
                                    <span class="text-muted"><?php echo date('F j, Y \a\t g:i A', strtotime($feedback['created_at'])); ?></span>
                                </div>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-tag me-2"></i>Subject:</strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($feedback['subject']); ?></span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong><i class="fas fa-circle me-2"></i>Status:</strong>
                                    <span class="badge bg-<?php echo $feedback['status'] === 'resolved' ? 'success' : ($feedback['status'] === 'pending' ? 'warning' : 'primary'); ?>">
                                        <?php echo ucfirst($feedback['status']); ?>
                                    </span>
                                </div>
                                <?php if ($feedback['assigned_to']): ?>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-user-tie me-2"></i>Assigned to:</strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($feedback['assigned_first_name'] . ' ' . $feedback['assigned_last_name']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <hr>

                            <div class="mt-3">
                                <h6 class="mb-3"><i class="fas fa-message me-2"></i>Your Message:</h6>
                                <div class="p-3 bg-light rounded border-start border-primary border-4">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Response Section -->
                    <?php if ($feedback['resolution']): ?>
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-reply me-2"></i>Response Received</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong><i class="fas fa-calendar-check me-2"></i>Response Date:</strong>
                                    <span class="text-muted"><?php echo date('F j, Y \a\t g:i A', strtotime($feedback['updated_at'])); ?></span>
                                </div>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-user-check me-2"></i>Responded by:</strong>
                                    <span class="text-muted"><?php echo htmlspecialchars(($feedback['assigned_first_name'] && $feedback['assigned_last_name']) ? $feedback['assigned_first_name'] . ' ' . $feedback['assigned_last_name'] : 'VVUSRC Team'); ?></span>
                                </div>
                            </div>

                            <hr>

                            <div class="mt-3">
                                <h6 class="mb-3"><i class="fas fa-comment-dots me-2"></i>Response:</h6>
                                <div class="p-3 bg-light rounded border-start border-success border-4">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($feedback['resolution'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Response Pending</h5>
                        </div>
                        <div class="card-body text-center py-5">
                            <div class="text-muted">
                                <div class="mb-4">
                                    <i class="fas fa-clock fa-4x text-warning"></i>
                                </div>
                                <h4 class="mb-3">Response Pending</h4>
                                <p class="lead">Your feedback is being reviewed by our team.</p>
                                <p class="mb-0">You will receive a notification when a response is available.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div> <!-- End content-area -->

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
