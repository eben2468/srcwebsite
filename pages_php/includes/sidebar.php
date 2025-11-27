<?php
// Include simple authentication
require_once __DIR__ . '/../../includes/simple_auth.php';
// Prevent multiple inclusions
if (isset($GLOBALS['sidebar_included']) && $GLOBALS['sidebar_included']) {
    return; // Skip if already included
}

// Get current page name
$currentPage = basename($_SERVER['PHP_SELF']);

// Get current user role
$userRole = $currentUser['role'] ?? 'guest';

// Define function to check if menu item should be active
function isActive($pageName) {
    global $currentPage;
    return ($currentPage === $pageName) ? 'active' : '';
}
?>

<div class="sidebar">
    <!-- Mobile Close Button -->
    <div class="sidebar-header d-mobile-only">
        <button id="sidebar-close-btn" class="btn btn-link text-white p-2" title="Close Sidebar" type="button">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>

    <a href="dashboard.php" class="sidebar-link <?php echo isActive('dashboard.php'); ?>">
        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
    </a>
    
    <a href="about.php" class="sidebar-link <?php echo isActive('about.php'); ?>">
        <i class="fas fa-info-circle me-2"></i> About SRC
    </a>
    
    <!-- Events - Read access for all users -->
    <a href="events.php" class="sidebar-link <?php echo isActive('events.php'); ?>">
        <i class="fas fa-calendar-alt me-2"></i> Events
    </a>
    
    <!-- News - Read access for all users -->
    <a href="news.php" class="sidebar-link <?php echo isActive('news.php'); ?>">
        <i class="fas fa-newspaper me-2"></i> News
    </a>
    
    <!-- Documents - Read access for all users -->
    <a href="documents.php" class="sidebar-link <?php echo isActive('documents.php'); ?>">
        <i class="fas fa-file-alt me-2"></i> Documents
    </a>
    
    <!-- Gallery - Read access for all users -->
    <a href="gallery.php" class="sidebar-link <?php echo isActive('gallery.php'); ?>">
        <i class="fas fa-images me-2"></i> Gallery
    </a>
    
    <!-- Elections - Read access for all users -->
    <a href="elections.php" class="sidebar-link <?php echo isActive('elections.php'); ?>">
        <i class="fas fa-vote-yea me-2"></i> Elections
    </a>

    <!-- Voting Portal - Quick access to active elections for voting -->
    <a href="voting_portal.php" class="sidebar-link <?php echo isActive('voting_portal.php'); ?>">
        <i class="fas fa-ballot-check me-2"></i> Voting Portal
    </a>
    
    <!-- Minutes - Read access for admin and members only -->
    <?php if (isAdmin() || isMember()): ?>
    <a href="minutes.php" class="sidebar-link <?php echo isActive('minutes.php'); ?>">
        <i class="fas fa-clipboard me-2"></i> Minutes
    </a>
    <?php endif; ?>
    
    <!-- Reports - Read access for admin and members only -->
    <?php if (isAdmin() || isMember()): ?>
    <a href="reports.php" class="sidebar-link <?php echo isActive('reports.php'); ?>">
        <i class="fas fa-chart-bar me-2"></i> Reports
    </a>
    <?php endif; ?>
    
    <!-- Portfolios - Read access for all users -->
    <a href="portfolio.php" class="sidebar-link <?php echo isActive('portfolio.php'); ?>">
        <i class="fas fa-user-tie me-2"></i> Portfolios
    </a>
    
    <!-- Constitution - Read access for all users -->
    <a href="src_constitution.php" class="sidebar-link <?php echo isActive('src_constitution.php'); ?>">
        <i class="fas fa-scroll me-2"></i> Constitution
    </a>
    
    <!-- Departments - Read access for all users -->
    <a href="departments.php" class="sidebar-link <?php echo isActive('departments.php'); ?>">
        <i class="fas fa-building me-2"></i> Departments
    </a>
    
    <!-- Senate - Read access for all users -->
    <a href="senate.php" class="sidebar-link <?php echo isActive('senate.php'); ?>">
        <i class="fas fa-gavel me-2"></i> Senate
    </a>
    
    <!-- Senate Resources - Read access for all users -->
    <a href="senate_resources.php" class="sidebar-link <?php echo isActive('senate_resources.php'); ?>">
        <i class="fas fa-book me-2"></i> Senate Resources
    </a>
    
    <!-- Committees - Read access for all users -->
    <a href="committees.php" class="sidebar-link <?php echo isActive('committees.php'); ?>">
        <i class="fas fa-users me-2"></i> Committees
    </a>
    
    <!-- Feedback - Create access for all users -->
    <a href="feedback.php" class="sidebar-link <?php echo isActive('feedback.php'); ?>">
        <i class="fas fa-comment-alt me-2"></i> Feedback
    </a>

    <!-- Welfare - Access for all users with role-based functionality -->
    <?php if (hasFeaturePermission('enable_welfare')): ?>
    <a href="welfare.php" class="sidebar-link <?php echo isActive('welfare.php'); ?>" style="background-color: #e3f2fd !important; border-left: 4px solid #2196f3 !important;">
        <i class="fas fa-heart me-2"></i> Student Welfare
        <span class="badge bg-primary ms-2">NEW</span>
    </a>
    <?php endif; ?>
    
    <?php if (isAdmin() || isMember()): ?>
    <!-- Admin/Management section -->
    <div class="sidebar-heading">MANAGEMENT</div>

    <!-- Finance - For users with budget permissions -->
    <?php if ((isAdmin() || isMember() || isSuperAdmin() || isFinance()) && hasFeaturePermission('enable_finance')): ?>
    <div class="sidebar-dropdown">
        <a href="finance.php" class="sidebar-link <?php echo isActive('finance.php') || isActive('finance-add-record.php') || isActive('finance-approvals.php') || isActive('finance-reports.php') || isActive('finance-categories.php'); ?>">
            <i class="fas fa-money-bill-wave me-2"></i> Finance
            <i class="fas fa-chevron-down float-end"></i>
        </a>
        <div class="sidebar-submenu">
            <a href="finance.php" class="sidebar-sublink <?php echo isActive('finance.php'); ?>">
                <i class="fas fa-chart-pie me-2"></i> Overview
            </a>
            <a href="finance-approvals.php" class="sidebar-sublink <?php echo isActive('finance-approvals.php'); ?>">
                <i class="fas fa-check-circle me-2"></i> Approvals
            </a>
            <a href="finance-add-record.php" class="sidebar-sublink <?php echo isActive('finance-add-record.php'); ?>">
                <i class="fas fa-plus me-2"></i> Add Record
            </a>
            <a href="finance-reports.php" class="sidebar-sublink <?php echo isActive('finance-reports.php'); ?>">
                <i class="fas fa-file-alt me-2"></i> Reports
            </a>
            <a href="finance-categories.php" class="sidebar-sublink <?php echo isActive('finance-categories.php'); ?>">
                <i class="fas fa-tags me-2"></i> Categories
            </a>
            <?php if (isAdmin() || isSuperAdmin() || isFinance()): ?>
            <a href="finance-approvals.php" class="sidebar-sublink <?php echo isActive('finance-approvals.php'); ?>">
                <i class="fas fa-check-circle me-2"></i> Approvals
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
    <!-- Users Management - Admin only -->
    <a href="users.php" class="sidebar-link <?php echo isActive('users.php'); ?>">
        <i class="fas fa-users me-2"></i> Users
    </a>

    <!-- User Activities (new link) - Admin only -->
    <a href="user-activities.php" class="sidebar-link <?php echo isActive('user-activities.php'); ?>">
        <i class="fas fa-history me-2"></i> User Activities
    </a>

    <!-- Messaging - Admin only -->
    <a href="messaging.php" class="sidebar-link <?php echo isActive('messaging.php'); ?>">
        <i class="fas fa-comment-dots me-2"></i> Messaging
    </a>
    <?php endif; ?>

    <!-- Feedback Dashboard -->
    <a href="../admin/feedback_dashboard.php" class="sidebar-link <?php echo isActive('feedback_dashboard.php'); ?>">
        <i class="fas fa-comments me-2"></i> Feedback Dashboard
    </a>
    
    <!-- Live Election Monitor - Super Admin and Electoral Commission only -->
    <?php if (isSuperAdmin() || isElectoralCommission()): ?>
    <a href="live_election_monitor.php" class="sidebar-link <?php echo isActive('live_election_monitor.php'); ?>">
        <i class="fas fa-chart-line me-2"></i> Live Election Monitor
    </a>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
    <!-- Settings - Admin only -->
    <a href="settings.php" class="sidebar-link <?php echo isActive('settings.php'); ?>">
        <i class="fas fa-cog me-2"></i> Settings
    </a>

    <!-- Security Dashboard - Admin only -->
    <a href="security-dashboard.php" class="sidebar-link <?php echo isActive('security-dashboard.php'); ?>">
        <i class="fas fa-shield-alt me-2"></i> Security Dashboard
    </a>
    <?php endif; ?>
    <?php endif; ?>
    
    <!-- Support Section -->
    <?php if (hasFeaturePermission('enable_support')): ?>
    <div class="sidebar-heading">SUPPORT</div>

    <!-- User Guide -->
    <a href="support/user-guide.php" class="sidebar-link <?php echo isActive('user-guide.php'); ?>">
        <i class="fas fa-book me-2"></i> User Guide
    </a>

    <!-- Help Center -->
    <a href="support/help-center.php" class="sidebar-link <?php echo isActive('help-center.php'); ?>">
        <i class="fas fa-question-circle me-2"></i> Help Center
    </a>

    <!-- Contact Support -->
    <a href="support/contact-support.php" class="sidebar-link <?php echo isActive('contact-support.php'); ?>">
        <i class="fas fa-headset me-2"></i> Contact Support
    </a>
    <?php endif; ?>
    
    <!-- Notifications - Available to all logged-in users -->
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
    ?>
    <?php if (hasFeaturePermission('enable_support')): ?>
    <a href="support/notifications.php" class="sidebar-link <?php echo isActive('notifications.php'); ?>">
        <i class="fas fa-bell me-2"></i> Notifications
        <?php if ($unread_count > 0): ?>
        <span class="badge bg-danger rounded-pill float-end"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </a>
    <?php endif; ?>
</div>