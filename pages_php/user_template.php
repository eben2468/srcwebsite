<?php
// Include authentication file and database config
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../settings_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get current user info
$currentUser = getCurrentUser();

// Page content
$pageTitle = "User Page Title"; // Change this for each page
$resourceType = "resource_name"; // Change this for each page (e.g., events, news, etc.)

// Check if user has read permission for this resource
if (!hasPermission('read', $resourceType)) {
    header("Location: access_denied.php");
    exit();
}

// Add page-specific code here
// ...

?>


// Include header
require_once 'includes/header.php';

// Custom styles for this page
?>
<style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            padding-top: 60px;
            background-color: #343a40;
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .sidebar-link {
            color: #fff;
            display: block;
            padding: 10px 20px;
        }
        .sidebar-link:hover {
            background-color: #495057;
            color: #fff;
            text-decoration: none;
        }
        .sidebar-link.active {
            background-color: #007bff;
        }
    </style>
<?php

?>
<div class="container-fluid">
            <h1 class="mt-4 mb-4"><?php echo $pageTitle; ?></h1>
            
            <!-- Success and Error Messages -->
            <?php if (isset($successMessage) && $successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $successMessage; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage) && $errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $errorMessage; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <!-- Page Content Goes Here -->
            <div class="row">
                <div class="col-md-12">
                    <!-- Example Card -->
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-list mr-2"></i> View <?php echo ucfirst($resourceType); ?></h5>
                        </div>
                        <div class="card-body">
                            <p>This is a template for user pages. Add your content here.</p>
                            
                            <!-- Example Content -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> This is a read-only view for regular users.
                            </div>
                            
                            <!-- Conditional Admin Actions -->
                            <?php if (isAdmin()): ?>
                            <div class="mt-3">
                                <h6>Admin Actions:</h6>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-success">
                                        <i class="fas fa-plus mr-1"></i> Create
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash mr-1"></i> Delete
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->

<?php require_once 'includes/footer.php'; ?>