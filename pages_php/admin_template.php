<?php
// Include authentication file and database config
require_once '../auth_functions.php';
require_once '../db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if user has admin privileges
// Modified to allow members to access specific admin pages
$allowedForMembers = [
    'events.php', 'news.php', 'documents.php', 'gallery.php', 
    'elections.php', 'minutes.php', 'reports.php', 'budget.php', 'feedback.php',
    'event-edit.php', 'news-edit.php', 'minutes_edit.php', 'election_edit.php', 
    'report_edit.php', 'budget-edit.php'
];

$currentPage = basename($_SERVER['PHP_SELF']);
$isMemberAllowedPage = in_array($currentPage, $allowedForMembers);

if (!isAdmin() && !(isMember() && $isMemberAllowedPage)) {
    header("Location: access_denied.php");
    exit();
}

// Get current user info
$currentUser = getCurrentUser();

// Page content
$pageTitle = "Admin Page Title"; // Change this for each page

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
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cog mr-2"></i> Admin Functions</h5>
                        </div>
                        <div class="card-body">
                            <p>This is a template for admin pages. Add your content here.</p>
                            
                            <!-- Example Admin Action Buttons -->
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-success">
                                    <i class="fas fa-plus mr-1"></i> Create
                                </button>
                                <button type="button" class="btn btn-info">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                                <button type="button" class="btn btn-danger">
                                    <i class="fas fa-trash mr-1"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->

<?php require_once 'includes/footer.php'; ?>