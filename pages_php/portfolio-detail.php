<?php
// Include authentication file
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: register.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = isAdmin();

// Check if portfolio ID is provided
if (!isset($_GET['id'])) {
    header("Location: portfolio.php");
    exit();
}

$portfolioId = intval($_GET['id']);

// Get portfolio from database
$sql = "SELECT * FROM portfolios WHERE portfolio_id = ?";
$portfolio = fetchOne($sql, [$portfolioId]);

// Check if portfolio exists
if (!$portfolio) {
    $_SESSION['error'] = "Portfolio not found.";
    header("Location: portfolio.php");
    exit();
}

// Get portfolio initiatives
$sql = "SELECT * FROM portfolio_initiatives WHERE portfolio_id = ? ORDER BY created_at DESC";
$initiatives = fetchAll($sql, [$portfolioId]);

// Decode JSON data
$responsibilities = json_decode($portfolio['responsibilities'], true) ?: [];
$qualifications = json_decode($portfolio['qualifications'], true) ?: [];

// Set page title
$pageTitle = "Portfolio Details: " . $portfolio['title'];

// Include header
require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
        
        <div>
            <?php if ($isAdmin): ?>
            <a href="portfolio_edit.php?id=<?php echo $portfolioId; ?>" class="btn btn-primary me-2">
                <i class="fas fa-edit me-2"></i> Edit Portfolio
            </a>
            <?php endif; ?>
            <a href="portfolio.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Portfolios
                                    </a>
                                </div>
                            </div>
    
    <!-- Notification area -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <div class="row">
        <!-- Portfolio Info Column -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="../images/avatars/<?php echo htmlspecialchars($portfolio['photo']); ?>" 
                         alt="<?php echo htmlspecialchars($portfolio['title']); ?>" 
                         class="img-fluid rounded-circle mb-3" 
                         style="max-width: 150px; max-height: 150px;"
                         onerror="this.src='../images/avatars/default.jpg'">
                    <h2 class="card-title"><?php echo htmlspecialchars($portfolio['name']); ?></h2>
                    <p class="text-muted"><?php echo htmlspecialchars($portfolio['title']); ?></p>
                    <div class="d-grid gap-2 mb-3">
                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo urlencode($portfolio['email']); ?>" target="_blank" class="btn btn-primary">
                            <i class="fas fa-envelope me-2"></i> Email
                        </a>
                        <?php if (!empty($portfolio['phone'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($portfolio['phone']); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-phone me-2"></i> Call
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Qualifications</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                        <?php if (empty($qualifications)): ?>
                        <li class="list-group-item text-muted">No qualifications listed</li>
                        <?php else: ?>
                            <?php foreach ($qualifications as $qualification): ?>
                            <li class="list-group-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <?php echo htmlspecialchars($qualification); ?>
                                </li>
                                <?php endforeach; ?>
                        <?php endif; ?>
                            </ul>
                        </div>
                    </div>
        </div>
        
        <!-- Portfolio Details Column -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">About This Portfolio</h3>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($portfolio['description'])); ?></p>
                        </div>
                    </div>
                    
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Responsibilities</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                        <?php if (empty($responsibilities)): ?>
                        <li class="list-group-item text-muted">No responsibilities listed</li>
                        <?php else: ?>
                            <?php foreach ($responsibilities as $responsibility): ?>
                            <li class="list-group-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <?php echo htmlspecialchars($responsibility); ?>
                                </li>
                                <?php endforeach; ?>
                        <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
            <!-- Initiatives Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Initiatives & Projects</h3>
                    <?php if ($isAdmin): ?>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addInitiativeModal">
                        <i class="fas fa-plus me-1"></i> Add Initiative
                    </button>
                    <?php endif; ?>
                        </div>
                <div class="card-body">
                    <?php if (empty($initiatives)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No initiatives have been added for this portfolio yet.
                    </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($initiatives as $initiative): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($initiative['title']); ?></h5>
                                    <span class="badge bg-<?php 
                                        echo $initiative['status'] === 'Completed' ? 'success' : 
                                            ($initiative['status'] === 'In Progress' ? 'primary' : 
                                            ($initiative['status'] === 'Planning' ? 'warning' : 'secondary')); 
                                    ?>">
                                        <?php echo htmlspecialchars($initiative['status']); ?>
                                        </span>
                                    </div>
                                <p class="mb-1"><?php echo htmlspecialchars($initiative['description']); ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i> Added: <?php echo date('M j, Y', strtotime($initiative['created_at'])); ?>
                                </small>
                                
                                <?php if ($isAdmin): ?>
                                <div class="mt-2">
                                    <form method="POST" action="portfolio_handler.php" class="d-inline">
                                        <input type="hidden" name="action" value="delete_initiative">
                                        <input type="hidden" name="initiative_id" value="<?php echo $initiative['initiative_id']; ?>">
                                        <input type="hidden" name="portfolio_id" value="<?php echo $portfolioId; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Are you sure you want to delete this initiative?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    </div>
                        </div>
                                    </div>
                            </div>
                            </div>

<!-- Add Initiative Modal -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="addInitiativeModal" tabindex="-1" aria-labelledby="addInitiativeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addInitiativeModalLabel">Add New Initiative</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
            <form method="POST" action="portfolio_handler.php">
                <input type="hidden" name="action" value="add_initiative">
                <input type="hidden" name="portfolio_id" value="<?php echo $portfolioId; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="initiative_title" class="form-label">Initiative Title</label>
                        <input type="text" class="form-control" id="initiative_title" name="initiative_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="initiative_status" class="form-label">Status</label>
                        <select class="form-select" id="initiative_status" name="initiative_status" required>
                            <option value="Planning">Planning</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                        </div>
                    <div class="mb-3">
                        <label for="initiative_description" class="form-label">Description</label>
                        <textarea class="form-control" id="initiative_description" name="initiative_description" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Initiative</button>
            </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>