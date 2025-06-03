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

// Handle Delete Portfolio (if admin)
if ($isAdmin && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $portfolioId = intval($_GET['id']);
    
    // Check if portfolio exists
    $checkPortfolio = fetchOne("SELECT portfolio_id FROM portfolios WHERE portfolio_id = ?", [$portfolioId]);
    
    if ($checkPortfolio) {
        $result = delete("DELETE FROM portfolios WHERE portfolio_id = ?", [$portfolioId]);
        
        if ($result) {
            $_SESSION['success'] = "Portfolio deleted successfully!";
            header("Location: portfolio.php");
            exit();
        } else {
            $_SESSION['error'] = "Error deleting portfolio. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Portfolio not found.";
    }
}

// Set page title
$pageTitle = "Portfolios - SRC Management System";

// Fetch portfolios from database
$sql = "SELECT * FROM portfolios ORDER BY portfolio_id";
$portfolios = fetchAll($sql);

// Group portfolios by category (Executive Committee and Portfolio Officers)
$executivePortfolios = [];
$otherPortfolios = [];

// Simple categorization based on title
foreach ($portfolios as $portfolio) {
    if (in_array($portfolio['title'], ['President', 'Vice President', 'Senate President', 'Secretary General'])) {
        $executivePortfolios[] = $portfolio;
    } else {
        $otherPortfolios[] = $portfolio;
    }
}

// Include header
require_once 'includes/header.php';

// Include the enhanced portfolio card styles
?>
<link rel="stylesheet" href="../css/portfolio-cards.css">
<link rel="stylesheet" href="../css/profile-image-enhancement.css">
<script src="../js/portfolio-cards.js" defer></script>

<style>
    /* Legacy styles kept for backward compatibility */
    .portfolio-card {
        transition: transform 0.3s;
        height: 100%;
    }
    .portfolio-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .member-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 50%;
    }
    .admin-controls {
        position: absolute;
        top: 10px;
        right: 10px;
    }
    h5{
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        padding-left: 20px;
        padding-top: 15px;
    }
</style>

<div class="container-fluid px-4">
    
    <?php 
    // Define page title, icon, and actions for the enhanced header
    $pageTitle = "Portfolios";
    $pageIcon = "fa-user-tie";
    $actions = [];
    
    if ($isAdmin || $isMember || isset($canManagePortfolio)) {
        $actions[] = [
            'url' => '#',
            'icon' => 'fa-plus',
            'text' => 'Add New',
            'class' => 'btn-primary',
            'attributes' => 'data-bs-toggle="modal" data-bs-target="#createPortfolioModal"'
        ];
    }
    
    // Include the enhanced page header
    include_once 'includes/enhanced_page_header.php';
    ?></div>
            
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
    <div class="col-12 mb-4">
        <div class="content-card animate-fadeIn">
            <div class="content-card-body">
                <h5 class="content-card-title">SRC Members and Their Portfolios</h5>
                <p class="card-text">This page displays all current SRC members, their portfolios, and their responsibilities.</p>
            </div>
        </div>
    </div>
</div>

<!-- Executive Members -->
<h2 class="mb-3">Executive Portfolios</h2>
<div class="row">
        <?php if (empty($executivePortfolios) && empty($otherPortfolios)): ?>
        <div class="col-12">
            <div class="alert alert-info">No portfolios found.</div>
        </div>
        <?php else: ?>
            <?php 
            // Display executive portfolios first
            foreach ($executivePortfolios as $portfolio): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card portfolio-card" data-role="<?php echo htmlspecialchars($portfolio['title']); ?>">
                    <?php if ($isAdmin): ?>
                    <div class="admin-controls">
                        <a href="portfolio_edit.php?id=<?php echo $portfolio['portfolio_id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="portfolio.php?action=delete&id=<?php echo $portfolio['portfolio_id']; ?>" class="btn btn-sm btn-outline-danger" 
                           onclick="return confirm('Are you sure you want to delete this portfolio?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-header">
                        <?php echo htmlspecialchars($portfolio['title']); ?>
                    </div>
                    
                    <div class="card-body">
                        <div class="member-image-container">
                            <img src="../images/avatars/<?php echo htmlspecialchars($portfolio['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($portfolio['title']); ?>" 
                                 class="member-image fill-image"
                                 onerror="this.src='../images/avatars/default.jpg'">
                        </div>
                        
                        <h5 class="card-title"><?php echo htmlspecialchars($portfolio['name']); ?></h5>
                        <p class="card-subtitle"><?php echo htmlspecialchars($portfolio['title']); ?></p>
                        
                        <h6 class="responsibilities-title">Responsibilities</h6>
                        <ul class="responsibilities-list">
                            <?php 
                            $responsibilities = json_decode($portfolio['responsibilities'], true) ?: [];
                            // Show up to 4 responsibilities
                            $count = 0;
                            foreach ($responsibilities as $responsibility): 
                                if ($count < 4):
                            ?>
                            <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($responsibility); ?></li>
                            <?php 
                                endif;
                                $count++;
                            endforeach; 
                            ?>
                        </ul>
                        
                        <div class="card-actions">
                            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo urlencode($portfolio['email']); ?>" target="_blank" class="btn btn-primary">
                                <i class="fas fa-envelope"></i> Contact
                            </a>
                            <a href="portfolio-detail.php?id=<?php echo $portfolio['portfolio_id']; ?>" class="btn btn-info">
                                <i class="fas fa-info-circle"></i> Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php 
            // Then display other portfolios
            foreach ($otherPortfolios as $portfolio): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card portfolio-card" data-role="<?php echo htmlspecialchars($portfolio['title']); ?>">
                    <?php if ($isAdmin): ?>
                    <div class="admin-controls">
                        <a href="portfolio_edit.php?id=<?php echo $portfolio['portfolio_id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="portfolio.php?action=delete&id=<?php echo $portfolio['portfolio_id']; ?>" class="btn btn-sm btn-outline-danger" 
                           onclick="return confirm('Are you sure you want to delete this portfolio?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-header">
                        <?php echo htmlspecialchars($portfolio['title']); ?>
                    </div>
                    
                    <div class="card-body">
                        <div class="member-image-container">
                            <img src="../images/avatars/<?php echo htmlspecialchars($portfolio['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($portfolio['title']); ?>" 
                                 class="member-image fill-image"
                                 onerror="this.src='../images/avatars/default.jpg'">
                        </div>
                        
                        <h5 class="card-title"><?php echo htmlspecialchars($portfolio['name']); ?></h5>
                        <p class="card-subtitle"><?php echo htmlspecialchars($portfolio['title']); ?></p>
                        
                        <h6 class="responsibilities-title">Responsibilities</h6>
                        <ul class="responsibilities-list">
                            <?php 
                            $responsibilities = json_decode($portfolio['responsibilities'], true) ?: [];
                            // Show up to 4 responsibilities
                            $count = 0;
                            foreach ($responsibilities as $responsibility): 
                                if ($count < 4):
                            ?>
                            <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($responsibility); ?></li>
                            <?php 
                                endif;
                                $count++;
                            endforeach; 
                            ?>
                        </ul>
                        
                        <div class="card-actions">
                            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo urlencode($portfolio['email']); ?>" target="_blank" class="btn btn-primary">
                                <i class="fas fa-envelope"></i> Contact
                            </a>
                            <a href="portfolio-detail.php?id=<?php echo $portfolio['portfolio_id']; ?>" class="btn btn-info">
                                <i class="fas fa-info-circle"></i> Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
</div>

<!-- Create Portfolio Modal -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="createPortfolioModal" tabindex="-1" aria-labelledby="createPortfolioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPortfolioModalLabel">Create New Portfolio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="portfolio_handler.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Portfolio Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">Holder's Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone">
        </div>
    </div>
    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="responsibilities" class="form-label">Responsibilities</label>
                        <textarea class="form-control" id="responsibilities" name="responsibilities" rows="4" placeholder="Enter each responsibility on a new line" required></textarea>
                </div>
                    
                    <div class="mb-3">
                        <label for="qualifications" class="form-label">Qualifications</label>
                        <textarea class="form-control" id="qualifications" name="qualifications" rows="4" placeholder="Enter each qualification on a new line"></textarea>
    </div>
    
                    <div class="mb-3">
                        <label for="photo" class="form-label">Photo</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        <small class="text-muted">Upload an image file for the portfolio holder.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Portfolio</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 