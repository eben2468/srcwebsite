<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and has admin interface access
if (!isLoggedIn() || !shouldUseAdminInterface()) {
    $_SESSION['error'] = "You don't have permission to access this page.";
    header("Location: portfolio.php");
    exit();
}

// Check if portfolio ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Portfolio ID not provided.";
    header("Location: portfolio.php");
    exit();
}

$portfolioId = intval($_GET['id']);

// Get portfolio information
$sql = "SELECT * FROM portfolios WHERE portfolio_id = ?";
$portfolio = fetchOne($sql, [$portfolioId]);

// Check if portfolio exists
if (!$portfolio) {
    $_SESSION['error'] = "Portfolio not found.";
    header("Location: portfolio.php");
    exit();
}

// Decode JSON data
$responsibilities = json_decode($portfolio['responsibilities'], true) ?: [];
$qualifications = json_decode($portfolio['qualifications'], true) ?: [];

// Convert arrays to text for textarea
$responsibilitiesText = implode("\n", $responsibilities);
$qualificationsText = implode("\n", $qualifications);

// Set page title
$pageTitle = "Edit Portfolio: " . $portfolio['title'];

// Include header
require_once 'includes/header.php';
?>

<!-- Page Content -->
<div class="container-fluid" style="margin-top: 60px;">
    <?php
    // Set up modern page header variables
    $pageTitle = "Edit Portfolio";
    $pageIcon = "fa-edit";
    $pageDescription = "Modify portfolio details and information";
    $actions = [
        [
            'url' => 'portfolio-detail.php?id=' . $portfolioId,
            'icon' => 'fa-eye',
            'text' => 'View Portfolio',
            'class' => 'btn-outline-light'
        ],
        [
            'url' => 'portfolio.php',
            'icon' => 'fa-arrow-left',
            'text' => 'Back to Portfolios',
            'class' => 'btn-outline-light'
        ]
    ];

    // Include modern page header
    include 'includes/modern_page_header.php';
    ?>

    <!-- Notification area -->
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="mb-0">Edit Portfolio</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="portfolio_handler.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="portfolio_id" value="<?php echo $portfolioId; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Portfolio Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($portfolio['title']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="name" class="form-label">Holder's Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($portfolio['name']); ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($portfolio['email']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($portfolio['phone']); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($portfolio['description']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="responsibilities" class="form-label">Responsibilities</label>
                    <textarea class="form-control" id="responsibilities" name="responsibilities" rows="4" placeholder="Enter each responsibility on a new line" required><?php echo htmlspecialchars($responsibilitiesText); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="qualifications" class="form-label">Qualifications</label>
                    <textarea class="form-control" id="qualifications" name="qualifications" rows="4" placeholder="Enter each qualification on a new line"><?php echo htmlspecialchars($qualificationsText); ?></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="photo" class="form-label">Photo</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        <small class="text-muted">Leave empty to keep the current photo.</small>
                    </div>
                    <div class="col-md-6">
                        <?php if (!empty($portfolio['photo'])): ?>
                        <label class="form-label">Current Photo</label>
                        <div>
                            <img src="../images/avatars/<?php echo htmlspecialchars($portfolio['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($portfolio['title']); ?>" 
                                 class="img-thumbnail" style="max-height: 100px;"
                                 onerror="this.src='../images/avatars/default.jpg'">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Portfolio Information</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Created On</h6>
                                    <p class="card-text"><?php echo date('M j, Y', strtotime($portfolio['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Last Updated</h6>
                                    <p class="card-text"><?php echo date('M j, Y', strtotime($portfolio['updated_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="portfolio-detail.php?id=<?php echo $portfolioId; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-eye me-2"></i> View Portfolio
                    </a>
                    <div>
                        <a href="portfolio.php" class="btn btn-outline-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Portfolio</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Mobile Full-Width Optimization for Portfolio Edit Page */
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
    
    /* Ensure page header has border-radius on mobile */
    .header, .page-hero, .modern-page-header {
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 
