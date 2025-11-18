<?php
// Debug file to check why super admin buttons are not showing
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Get current user role
$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'unknown';

// Check various admin functions
$isSuperAdmin = isSuperAdmin();
$isAdmin = isAdmin();
$shouldUseAdminInterface = shouldUseAdminInterface();

// Include header
require_once 'includes/header.php';
?>

<div class="container-fluid" style="margin-top: 60px;">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Admin Button Visibility Debug</h5>
                </div>
                <div class="card-body">
                    <h6>User Information:</h6>
                    <ul>
                        <li><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></li>
                        <li><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'Not set'; ?></li>
                        <li><strong>Role:</strong> <?php echo $userRole; ?></li>
                    </ul>
                    
                    <h6>Admin Status Checks:</h6>
                    <ul>
                        <li><strong>isSuperAdmin():</strong> <?php echo $isSuperAdmin ? 'true' : 'false'; ?></li>
                        <li><strong>isAdmin():</strong> <?php echo $isAdmin ? 'true' : 'false'; ?></li>
                        <li><strong>shouldUseAdminInterface():</strong> <?php echo $shouldUseAdminInterface ? 'true' : 'false'; ?></li>
                    </ul>
                    
                    <h6>Session Variables:</h6>
                    <pre><?php print_r($_SESSION); ?></pre>
                    
                    <h6>Test Admin Buttons:</h6>
                    <div class="mb-4">
                        <p>The buttons below should be visible if you have admin privileges:</p>
                        
                        <!-- Test using isAdmin() -->
                        <?php if (isAdmin()): ?>
                        <button class="btn btn-primary mb-2">Button using isAdmin()</button><br>
                        <?php endif; ?>
                        
                        <!-- Test using isSuperAdmin() -->
                        <?php if (isSuperAdmin()): ?>
                        <button class="btn btn-success mb-2">Button using isSuperAdmin()</button><br>
                        <?php endif; ?>
                        
                        <!-- Test using shouldUseAdminInterface() -->
                        <?php if (shouldUseAdminInterface()): ?>
                        <button class="btn btn-info mb-2">Button using shouldUseAdminInterface()</button><br>
                        <?php endif; ?>
                        
                        <!-- Test using direct role check -->
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                        <button class="btn btn-warning mb-2">Button using direct role check for super_admin</button><br>
                        <?php endif; ?>
                        
                        <!-- Test using direct role check for admin -->
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <button class="btn btn-danger mb-2">Button using direct role check for admin</button><br>
                        <?php endif; ?>
                    </div>
                    
                    <h6>Test Admin Controls:</h6>
                    <div class="card portfolio-card" style="position: relative; max-width: 400px;">
                        <?php if (isAdmin()): ?>
                        <div class="admin-controls">
                            <a href="#" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-header">
                            Test Portfolio (using isAdmin)
                        </div>
                        <div class="card-body">
                            <p>This is a test portfolio card to check admin controls visibility.</p>
                        </div>
                    </div>
                    
                    <div class="card portfolio-card mt-4" style="position: relative; max-width: 400px;">
                        <?php if (shouldUseAdminInterface()): ?>
                        <div class="admin-controls">
                            <a href="#" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-header">
                            Test Portfolio (using shouldUseAdminInterface)
                        </div>
                        <div class="card-body">
                            <p>This is a test portfolio card to check admin controls visibility.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>