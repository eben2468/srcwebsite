<?php
// Include simple authentication and required files
require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/db_functions.php';
require_once __DIR__ . '/../../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Check if support feature is enabled
if (!hasFeaturePermission('enable_support')) {
    header('Location: ../../dashboard.php?error=feature_disabled');
    exit();
}

// Get current user info
$currentUser = getCurrentUser();
$shouldUseAdminInterface = shouldUseAdminInterface();
$isAdmin = $shouldUseAdminInterface;
$isMember = isMember();
$isStudent = isStudent();

// Define role-based access permissions for support features
$supportAccess = [
    'user_guide' => ['student' => true, 'member' => true, 'admin' => true],
    'help_center' => ['student' => true, 'member' => true, 'admin' => true],
    'contact_support' => ['student' => true, 'member' => true, 'admin' => true],
    'notifications' => ['student' => true, 'member' => true, 'admin' => true],
    'system_stats' => ['student' => false, 'member' => true, 'admin' => true],
    'admin_tools' => ['student' => false, 'member' => false, 'admin' => true],
    'support_tickets' => ['student' => true, 'member' => true, 'admin' => true],
    'knowledge_base' => ['student' => true, 'member' => true, 'admin' => true],
    'system_updates' => ['student' => false, 'member' => true, 'admin' => true],
    'user_management' => ['student' => false, 'member' => false, 'admin' => true]
];

// Get current user role
$userRole = $currentUser['role'] ?? 'student';

// Helper function to check if user has access to a feature
function hasAccess($feature, $role, $accessMatrix) {
    return isset($accessMatrix[$feature][$role]) && $accessMatrix[$feature][$role] === true;
}

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Support Center - " . $siteName;
$bodyClass = "page-support";

// Include header
require_once '../includes/header.php';
?>

<style>
.support-center-container {
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
    padding: 0;
    margin: 0;
    box-sizing: border-box;
    overflow-x: hidden;
}

.support-center-container .container-fluid {
    padding-left: 1rem !important;
    padding-right: 1rem !important;
    max-width: 100%;
    margin: 0 auto;
    box-sizing: border-box;
}

.support-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    margin-top: 60px;
    margin-bottom: 2rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    box-sizing: border-box;
    width: 100%;
    overflow: hidden;
}

.support-hero .container-fluid {
    max-width: 100%;
    margin: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: auto;
    position: relative;
    padding: 0;
}

.support-hero-content {
    text-align: center;
    flex: 0 0 auto;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.support-hero h1 {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    margin-bottom: 1rem;
    font-weight: 700;
    font-size: 2.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    gap: 1rem;
    flex-wrap: wrap;
    width: 100%;
}

.support-hero .lead {
    text-align: center;
    margin-bottom: 1.5rem;
    opacity: 0.95;
    font-size: 1.1rem;
    line-height: 1.5;
    max-width: 800px;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.support-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 2.5rem;
}

.welcome-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.question-item {
    display: block;
    text-decoration: none;
    color: inherit;
    padding: 1rem;
    border-radius: 10px;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
    background: white;
    height: 100%;
}

.question-item:hover {
    background: #f8f9fa;
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
    color: inherit;
    text-decoration: none;
}

.question-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    margin-right: 1rem;
    flex-shrink: 0;
}
    margin-bottom: 2rem;
    transition: all 0.3s ease;
    border: none;
    text-align: center;
    cursor: pointer;
}

.support-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.support-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: white;
    position: relative;
}

.support-icon::before {
    content: '';
    position: absolute;
    top: -10px;
    left: -10px;
    right: -10px;
    bottom: -10px;
    border-radius: 50%;
    background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0.3));
    z-index: -1;
}

.support-icon.user-guide {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.support-icon.help-center {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.support-icon.video-tutorials {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
}

.support-icon.contact {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.support-icon.notifications {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.support-card h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
    font-weight: 600;
}

.support-card p {
    color: #6c757d;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.btn-support {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 25px;
    padding: 0.75rem 2rem;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-support:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.quick-stats {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: fit-content;
}

.stat-item {
    text-align: center;
    padding: 1rem;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #667eea;
    display: block;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.recent-activity {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    padding: 2rem;
}

.activity-item {
    padding: 1rem;
    border-left: 3px solid #667eea;
    margin-bottom: 1rem;
    background: #f8f9fa;
    border-radius: 0 10px 10px 0;
}

.activity-time {
    font-size: 0.875rem;
    color: #6c757d;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}

.list-group-item {
    border: none;
    padding: 1rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.list-group-item:last-child {
    border-bottom: none;
}

.list-group-item:hover {
    background-color: #f8f9fa;
    text-decoration: none;
}

/* Remove empty spaces and improve layout */
.row {
    margin-bottom: 0;
}

.col-lg-6, .col-md-6, .col-12 {
    padding-bottom: 0;
}

/* Only apply padding to main content containers, not navbar */
.support-center-container .container-fluid {
    padding-bottom: 1rem;
}

/* Ensure cards fill available space */
.support-card {
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.support-center-container .row:last-child {
    margin-bottom: 0;
}

.quick-stats:last-child {
    margin-bottom: 0;
}

/* Video Tutorials Card Styling */
.video-tutorial-icon:hover {
    transform: scale(1.05);
    box-shadow: 0 15px 40px rgba(255, 107, 157, 0.4) !important;
}

.video-tutorial-icon {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6) !important;
}

/* Button container in hero section */
.support-hero-content .d-flex {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    width: 100%;
    margin-bottom: 1rem;
    box-sizing: border-box;
    padding: 0 1rem;
}

.support-hero-content .btn {
    white-space: nowrap;
    padding: 0.6rem 1.2rem;
    font-size: 0.95rem;
    flex-shrink: 0;
    border-radius: 25px;
    transition: all 0.3s ease;
}

.support-hero-content .btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

.support-hero-content .btn-lg {
    padding: 0.8rem 1.5rem;
    font-size: 1rem;
}

.btn-outline-light {
    color: white;
    border-color: rgba(255, 255, 255, 0.5);
    background: transparent;
}

.btn-outline-light:hover {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.8);
}

.btn-light {
    color: #667eea;
    background: white;
}

.btn-light:hover {
    background: #f8f9fa;
    color: #667eea;
}
    .support-center-container {
        padding: 0;
    }
    
    .support-hero {
        margin-top: 60px !important;
        margin-bottom: 1.5rem !important;
        padding: 1.5rem 1rem !important;
        border-radius: 12px;
    }

    .support-hero h1 {
        font-size: 1.8rem;
        margin-bottom: 0.8rem;
        gap: 0.5rem;
    }

    .support-hero .lead {
        font-size: 1rem;
        margin-bottom: 1rem;
    }

    .support-hero .d-flex {
        flex-direction: column !important;
        gap: 1rem !important;
        width: 100%;
        padding: 0 0.5rem;
    }

    .support-hero .btn {
        width: 100%;
        white-space: normal;
        word-wrap: break-word;
    }

    .support-card {
        padding: 1.5rem;
        min-height: 150px;
        margin-bottom: 1rem;
    }

    .support-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }

    .stat-number {
        font-size: 1.8rem;
    }

    .quick-stats {
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .col-lg-6 {
        margin-bottom: 1rem;
    }

    .question-item {
        padding: 1rem;
    }

    .list-group-item {
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .support-hero {
        margin-top: 55px !important;
        margin-bottom: 1rem !important;
        padding: 1.2rem 0.8rem !important;
        border-radius: 10px;
    }

    .support-hero h1 {
        font-size: 1.4rem;
        margin-bottom: 0.6rem;
    }

    .support-hero .lead {
        font-size: 0.9rem;
        margin-bottom: 0.8rem;
    }

    .support-hero-content .d-flex {
        gap: 0.75rem;
        padding: 0 0.5rem;
    }

    .support-hero-content .btn {
        padding: 0.5rem 0.8rem;
        font-size: 0.8rem;
        white-space: normal;
    }

    .support-hero-content .btn-lg {
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }
}

@media (max-width: 375px) {
    .support-hero {
        margin-top: 55px !important;
        margin-bottom: 0.8rem !important;
        padding: 1rem 0.6rem !important;
        border-radius: 8px;
    }

    .support-hero h1 {
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
    }

    .support-hero .lead {
        font-size: 0.85rem;
        margin-bottom: 0.7rem;
    }

    .support-hero-content .d-flex {
        gap: 0.5rem;
        padding: 0 0.3rem;
    }

    .support-hero-content .btn {
        padding: 0.4rem 0.7rem;
        font-size: 0.75rem;
    }
}

@media (max-width: 320px) {
    .support-hero {
        margin-top: 55px !important;
        margin-bottom: 0.6rem !important;
        padding: 0.8rem 0.5rem !important;
        border-radius: 8px;
    }

    .support-hero h1 {
        font-size: 1.1rem;
        margin-bottom: 0.4rem;
    }

    .support-hero .lead {
        font-size: 0.8rem;
        margin-bottom: 0.6rem;
    }

    .support-hero-content .d-flex {
        gap: 0.4rem;
        padding: 0;
    }

    .support-hero-content .btn {
        padding: 0.35rem 0.6rem;
        font-size: 0.7rem;
        border-radius: 20px;
    }
}

/* Mobile Full-Width Optimization for Support Index Page */
@media (max-width: 991px) {
    [class*="col-md-"], [class*="col-lg-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .support-hero, .page-hero, .modern-page-header {
        border-radius: 12px !important;
    }
    .card, .support-card, .knowledge-card, .question-item {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="container-fluid">
<!-- Hero Section -->
<div class="support-hero">
    <div class="container-fluid">
        <div class="support-hero-content">
            <h1 class="mb-4">
                <i class="fas fa-life-ring me-3"></i>Support Center
            </h1>
            <p class="lead mb-4">Get help, find answers, and stay informed with our comprehensive support system</p>

            <!-- Quick Access Navigation -->
            <div class="d-flex justify-content-center gap-2 mb-4 flex-wrap">
                <?php if (hasAccess('notifications', $userRole, $supportAccess)): ?>
                <a href="notifications.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-bell me-2"></i>Notifications
                </a>
                <?php endif; ?>

                <?php if ($shouldUseAdminInterface || $isMember): ?>
                <a href="tickets.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-ticket-alt me-2"></i>Support Tickets
                </a>
                <a href="chat-management.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-headset me-2"></i>Chat Management
                </a>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="user-guide.php" class="btn btn-light btn-lg">
                        <i class="fas fa-book me-2"></i>User Guide
                    </a>
                    <a href="video-tutorials.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-play-circle me-2"></i>Video Tutorials
                    </a>
                    <a href="help-center.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-question-circle me-2"></i>Help Center
                    </a>
                    <a href="contact-support.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-headset me-2"></i>Contact Us
                    </a>
                    <?php if (hasAccess('notifications', $userRole, $supportAccess)): ?>
                    <a href="notifications.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-bell me-2"></i>Notifications
                        <?php
                        // Get unread notification count for current user
                        $unread_count = 0;
                        if (isset($currentUser['user_id'])) {
                            $notif_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE";
                            $notif_stmt = mysqli_prepare($conn, $notif_sql);
                            if ($notif_stmt) {
                                mysqli_stmt_bind_param($notif_stmt, "i", $currentUser['user_id']);
                                mysqli_stmt_execute($notif_stmt);
                                $notif_result = mysqli_stmt_get_result($notif_stmt);
                                $notif_row = mysqli_fetch_assoc($notif_result);
                                $unread_count = $notif_row['count'] ?? 0;
                            }
                        }
                        if ($unread_count > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container-fluid">
    <!-- Support Options -->
    <div class="row mb-5">
            <!-- User Guide - Available to all users -->
            <?php if (hasAccess('user_guide', $userRole, $supportAccess)): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="support-card" onclick="location.href='user-guide.php'">
                    <div class="support-icon user-guide">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>User Guide</h3>
                    <p><?php echo $isStudent ? 'Learn the basics of using the SRC system and accessing student features.' : 'Comprehensive documentation and tutorials to help you master the system features.'; ?></p>
                    <a href="user-guide.php" class="btn-support">
                        <i class="fas fa-arrow-right me-2"></i>Explore Guide
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Help Center - Available to all users -->
            <?php if (hasAccess('help_center', $userRole, $supportAccess)): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="support-card" onclick="location.href='help-center.php'">
                    <div class="support-icon help-center">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h3>Help Center</h3>
                    <p><?php echo $isStudent ? 'Find answers to common questions about student features and account management.' : 'Browse our knowledge base with frequently asked questions and detailed articles.'; ?></p>
                    <a href="help-center.php" class="btn-support">
                        <i class="fas fa-search me-2"></i>Find Answers
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Contact Support - Available to all users -->
            <?php if (hasAccess('contact_support', $userRole, $supportAccess)): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="support-card" onclick="location.href='contact-support.php'">
                    <div class="support-icon contact">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Contact Support</h3>
                    <p><?php echo $isStudent ? 'Submit support tickets for technical issues or account problems.' : 'Get personalized help from our support team through tickets and direct contact.'; ?></p>
                    <a href="contact-support.php" class="btn-support">
                        <i class="fas fa-envelope me-2"></i>Get Help
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Notifications - Available to all logged-in users -->
            <?php if (hasAccess('notifications', $userRole, $supportAccess)): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="support-card" onclick="location.href='notifications.php'">
                    <div class="support-icon notifications">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>System Notifications</h3>
                    <p>Stay updated with system announcements, important updates, and administrative notifications.</p>
                    <a href="notifications.php" class="btn-support">
                        <i class="fas fa-bell me-2"></i>View Updates
                    </a>
                </div>
            </div>
            <?php endif; ?>
    </div>

    <!-- Welcome and Main Content Section -->
    <div class="row">
        <!-- Welcome Section -->
        <div class="col-lg-6 mb-4">
            <div class="quick-stats">
                <div class="d-flex align-items-center mb-4">
                    <div class="me-3">
                        <div class="welcome-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-1">
                            Welcome, <?php echo htmlspecialchars($currentUser['first_name'] ?? 'User'); ?>!
                        </h3>
                        <p class="text-muted mb-0">How can we help you today? Our support team is here to assist you.</p>
                    </div>
                </div>

                <!-- Common Questions Section -->
                <h4 class="mb-3">
                    <i class="fas fa-question-circle me-2"></i>Common Questions
                </h4>
                <div class="row">
                    <div class="col-12 mb-3">
                        <a href="user-guide.php#getting-started" class="question-item">
                            <div class="d-flex align-items-start">
                                <div class="question-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Getting Started</h6>
                                    <p class="small text-muted mb-0">How to use your student account</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 mb-3">
                        <a href="user-guide.php#notifications" class="question-item">
                            <div class="d-flex align-items-start">
                                <div class="question-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Notifications</h6>
                                    <p class="small text-muted mb-0">Understanding system notifications</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 mb-3">
                        <a href="help-center.php#password-reset" class="question-item">
                            <div class="d-flex align-items-start">
                                <div class="question-icon">
                                    <i class="fas fa-key"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Password Reset</h6>
                                    <p class="small text-muted mb-0">How to reset your password</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 mb-3">
                        <a href="help-center.php#account-security" class="question-item">
                            <div class="d-flex align-items-start">
                                <div class="question-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Account Security</h6>
                                    <p class="small text-muted mb-0">Keeping your account secure</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Tutorials Card -->
        <div class="col-lg-6 mb-4">
            <div class="quick-stats h-100">
                <div class="text-center">
                    <div class="video-tutorial-icon mx-auto mb-4" style="width: 120px; height: 120px; background: linear-gradient(135deg, #ff6b9d 0%, #c44569 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; box-shadow: 0 10px 30px rgba(255, 107, 157, 0.3);">
                        <i class="fas fa-play"></i>
                    </div>
                    <h3 class="mb-3" style="color: #2c3e50; font-weight: 600;">Video Tutorials</h3>
                    <p class="text-muted mb-4" style="font-size: 1.1rem; line-height: 1.6;">
                        Interactive video tutorials covering all system features with detailed explanations.
                    </p>
                    <a href="video-tutorials.php" class="btn btn-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 50px; padding: 12px 30px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);">
                        <i class="fas fa-play me-2"></i>Watch Videos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information Section -->
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="quick-stats">
                <h4 class="mb-3">
                    <i class="fas fa-phone me-2"></i>Contact Information
                </h4>

                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <div class="me-3">
                                <i class="fas fa-envelope text-primary" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 text-primary">Email Support</h6>
                                <p class="mb-0">support@vvusrc.edu.gh</p>
                                <small class="text-muted">Response within 24 hours</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-3">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <div class="me-3">
                                <i class="fas fa-phone text-success" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 text-success">Phone Support</h6>
                                <p class="mb-0">+233 54 881 1774</p>
                                <small class="text-muted">Mon-Fri, 8AM-6PM</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-3">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <div class="me-3">
                                <i class="fas fa-comments text-info" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 text-info">Live Chat</h6>
                                <p class="mb-0">Available In-app</p>
                                <small class="text-muted">Real-time assistance</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-3">
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <div class="me-3">
                                <i class="fas fa-clock text-warning" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 text-warning">Office Hours</h6>
                                <p class="mb-0">Mon-Fri: 8AM-6PM</p>
                                <p class="mb-0">Sunday: 9AM-2PM</p>
                                <small class="text-muted">Ghana Standard Time</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-3">
                        <div class="d-flex align-items-center p-3 bg-danger text-white rounded">
                            <div class="me-3">
                                <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 text-white">Emergency Contact</h6>
                                <p class="mb-0 text-white">+233 54 881 1774L</p>
                                <small class="text-white-50">For urgent matters only</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links and System Status Section -->
    <div class="row">
        <!-- Quick Links -->
        <div class="col-lg-6 mb-4">
            <div class="quick-stats">
                <h4 class="mb-3">
                    <i class="fas fa-external-link-alt me-2"></i>Quick Links
                </h4>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="user-guide.php" class="support-card h-100 text-decoration-none" style="padding: 1.5rem; cursor: pointer;">
                            <div class="text-center">
                                <div class="support-icon user-guide mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                    <i class="fas fa-book"></i>
                                </div>
                                <h6 class="mb-2">User Guide</h6>
                                <p class="small text-muted mb-0">Complete system documentation</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6 mb-3">
                        <a href="help-center.php" class="support-card h-100 text-decoration-none" style="padding: 1.5rem; cursor: pointer;">
                            <div class="text-center">
                                <div class="support-icon help-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                    <i class="fas fa-question-circle"></i>
                                </div>
                                <h6 class="mb-2">FAQ</h6>
                                <p class="small text-muted mb-0">Frequently asked questions</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6 mb-3">
                        <a href="contact-support.php" class="support-card h-100 text-decoration-none" style="padding: 1.5rem; cursor: pointer;">
                            <div class="text-center">
                                <div class="support-icon contact mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <h6 class="mb-2">Submit Ticket</h6>
                                <p class="small text-muted mb-0">Get personalized help</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="col-lg-6 mb-4">
            <div class="quick-stats">
                <h4 class="mb-3">
                    <i class="fas fa-server me-2"></i>System Status
                </h4>

                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="status-indicator bg-success mx-auto mb-2"></div>
                            <h6 class="mb-1">System</h6>
                            <strong class="text-success">Online</strong>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="status-indicator bg-success mx-auto mb-2"></div>
                            <h6 class="mb-1">Database</h6>
                            <strong class="text-success">Active</strong>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="status-indicator bg-warning mx-auto mb-2"></div>
                            <h6 class="mb-1">Maintenance</h6>
                            <strong class="text-warning">Scheduled</strong>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="status-indicator bg-success mx-auto mb-2"></div>
                            <h6 class="mb-1">Support</h6>
                            <strong class="text-success">Available</strong>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-3 bg-primary text-white rounded text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Last updated:</strong> <?php echo date('M j, Y g:i A'); ?>
                </div>

                <!-- Additional Help Resources -->
                <div class="mt-4">
                    <h5 class="mb-3">
                        <i class="fas fa-lightbulb me-2"></i>Need More Help?
                    </h5>
                    <div class="list-group">
                        <a href="user-guide.php#troubleshooting" class="list-group-item list-group-item-action">
                            <i class="fas fa-tools me-2 text-primary"></i>
                            Troubleshooting Guide
                        </a>
                        <a href="contact-support.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-headset me-2 text-success"></i>
                            Contact Technical Support
                        </a>
                        <a href="help-center.php#system-requirements" class="list-group-item list-group-item-action">
                            <i class="fas fa-desktop me-2 text-info"></i>
                            System Requirements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Tools Section -->
    <?php if ($isAdmin): ?>
    <div class="row mt-4">
        <div class="col-12">
                <div class="quick-stats">
                    <h4 class="mb-4">
                        <i class="fas fa-tools me-2"></i>Admin Tools
                    </h4>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="support-card h-100" onclick="location.href='admin-video-tutorials.php'">
                                <div class="support-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="fas fa-video"></i>
                                </div>
                                <h5>Manage Video Tutorials</h5>
                                <p class="small">Add, edit, and manage video tutorials for users. Upload videos or add YouTube links.</p>
                                <a href="admin-video-tutorials.php" class="btn-support">
                                    <i class="fas fa-cog me-2"></i>Manage Videos
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="support-card h-100" onclick="location.href='tickets.php'">
                                <div class="support-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <h5>Support Tickets</h5>
                                <p class="small">View and manage support tickets from users. Respond to issues and track resolution.</p>
                                <a href="tickets.php" class="btn-support">
                                    <i class="fas fa-headset me-2"></i>View Tickets
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="support-card h-100" onclick="location.href='chat-management.php'">
                                <div class="support-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h5>Chat Management</h5>
                                <p class="small">Manage live chat sessions and respond to user inquiries in real-time.</p>
                                <a href="chat-management.php" class="btn-support">
                                    <i class="fas fa-comments me-2"></i>Manage Chat
                                </a>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Statistics for Admins -->
    <?php if (hasAccess('system_stats', $userRole, $supportAccess) && $isAdmin): ?>
    <div class="row mt-4">
        <div class="col-12">
                <div class="quick-stats">
                    <h4 class="mb-4">
                        <i class="fas fa-chart-bar me-2"></i>System Statistics
                    </h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-item">
                                <span class="stat-number">
                                    <?php
                                    // Get total users count
                                    try {
                                        $userCount = fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
                                        echo $userCount['count'] ?? '0';
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                    ?>
                                </span>
                                <span class="stat-label">Active Users</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <span class="stat-number">
                                    <?php
                                    // Get support tickets count
                                    try {
                                        $ticketCount = fetchOne("SELECT COUNT(*) as count FROM support_tickets WHERE status != 'closed'");
                                        echo $ticketCount['count'] ?? '0';
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                    ?>
                                </span>
                                <span class="stat-label">Open Tickets</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <span class="stat-number">< 2hrs</span>
                                <span class="stat-label">Response Time</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <span class="stat-number">98%</span>
                                <span class="stat-label">Satisfaction Rate</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>

<script src="js/support-index.js"></script>

<?php require_once '../includes/footer.php'; ?>
