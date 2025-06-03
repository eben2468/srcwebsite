<?php
/**
 * Admin Menu - SRC Management System
 * Hard-coded admin menu for direct access to admin features
 */

// Check if user is admin
if (!isset($isAdmin) || !$isAdmin) {
    return; // Only display for admin users
}

// Get current page name
$currentPage = basename($_SERVER['PHP_SELF']);

// Define function to check if menu item should be active
function isAdminMenuActive($pageName) {
    global $currentPage;
    return ($currentPage === $pageName) ? 'active' : '';
}
?>

<!-- Admin Menu -->
<div class="card mt-4 mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-lock me-2"></i> Admin Menu</h5>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <a href="users.php" class="list-group-item list-group-item-action <?php echo isAdminMenuActive('users.php'); ?>">
                <i class="fas fa-users me-2"></i> Users Management
            </a>
            
            <a href="user-activities.php" class="list-group-item list-group-item-action <?php echo isAdminMenuActive('user-activities.php'); ?>">
                <i class="fas fa-history me-2"></i> User Activities
            </a>
            
            <a href="../admin/feedback_dashboard.php" class="list-group-item list-group-item-action <?php echo isAdminMenuActive('feedback_dashboard.php'); ?>">
                <i class="fas fa-comments me-2"></i> Feedback Dashboard
            </a>
            
            <a href="budget.php" class="list-group-item list-group-item-action <?php echo isAdminMenuActive('budget.php'); ?>">
                <i class="fas fa-money-bill-wave me-2"></i> Budget Management
            </a>
            
            <a href="settings.php" class="list-group-item list-group-item-action <?php echo isAdminMenuActive('settings.php'); ?>">
                <i class="fas fa-cog me-2"></i> System Settings
            </a>
        </div>
    </div>
</div> 