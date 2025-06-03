<?php
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
    
    <!-- Minutes - Read access for admin and members only -->
    <?php if (isAdmin() || isMember()): ?>
    <a href="minutes.php" class="sidebar-link <?php echo isActive('minutes.php'); ?>">
        <i class="fas fa-clipboard me-2"></i> Minutes
    </a>
    <?php endif; ?>
    
    <!-- Reports - Read access for admin and members only -->
    <?php if (hasPermission('read', 'reports')): ?>
    <a href="reports.php" class="sidebar-link <?php echo isActive('reports.php'); ?>">
        <i class="fas fa-chart-bar me-2"></i> Reports
    </a>
    <?php endif; ?>
    
    <!-- Portfolios - Read access for all users -->
    <a href="portfolio.php" class="sidebar-link <?php echo isActive('portfolio.php'); ?>">
        <i class="fas fa-user-tie me-2"></i> Portfolios
    </a>
    
    <!-- Departments - Read access for all users -->
    <a href="departments.php" class="sidebar-link <?php echo isActive('departments.php'); ?>">
        <i class="fas fa-building me-2"></i> Departments
    </a>
    
    <!-- Feedback - Create access for all users -->
    <a href="feedback.php" class="sidebar-link <?php echo isActive('feedback.php'); ?>">
        <i class="fas fa-comment-alt me-2"></i> Feedback
    </a>
    
    <?php if (isAdmin() || isMember()): ?>
    <!-- Admin/Management section -->
    <div class="sidebar-heading">MANAGEMENT</div>
    
    <?php if (isAdmin()): ?>
    <!-- Users Management - Admin only -->
    <a href="users.php" class="sidebar-link <?php echo isActive('users.php'); ?>">
        <i class="fas fa-users me-2"></i> Users
    </a>
    
    <!-- User Activities (new link) - Admin only -->
    <a href="user-activities.php" class="sidebar-link <?php echo isActive('user-activities.php'); ?>">
        <i class="fas fa-history me-2"></i> User Activities
    </a>
    <?php endif; ?>
    
    <!-- Feedback Dashboard -->
    <a href="../admin/feedback_dashboard.php" class="sidebar-link <?php echo isActive('feedback_dashboard.php'); ?>">
        <i class="fas fa-comments me-2"></i> Feedback Dashboard
    </a>
    
    <!-- Budget Management -->
    <a href="budget.php" class="sidebar-link <?php echo isActive('budget.php'); ?>">
        <i class="fas fa-money-bill-wave me-2"></i> Budget
    </a>
    
    <?php if (isAdmin()): ?>
    <!-- Settings - Admin only -->
    <a href="settings.php" class="sidebar-link <?php echo isActive('settings.php'); ?>">
        <i class="fas fa-cog me-2"></i> Settings
    </a>
    <?php endif; ?>
    <?php endif; ?>
</div>