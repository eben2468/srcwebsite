<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/functions.php';
// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: register.php");
    exit();
}

// Check if portfolios feature is enabled
if (!hasFeaturePermission('enable_portfolios')) {
    $_SESSION['error'] = "Portfolios feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = shouldUseAdminInterface();

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
$sql = "SELECT * FROM portfolios ORDER BY
    CASE
        WHEN title = 'President' THEN 1
        WHEN title = 'Vice President' THEN 2
        WHEN title = 'Senate President' THEN 3
        WHEN title = 'Executive Secretary' THEN 4
        WHEN title = 'Finance Officer' THEN 5
        WHEN title = 'Editor' THEN 6
        WHEN title = 'Organizing Secretary' THEN 7
        WHEN title = 'Welfare Officer' THEN 8
        WHEN title = 'Women\'s Commissioner' THEN 9
        WHEN title = 'Sports Commissioner' THEN 10
        WHEN title = 'Chaplain' THEN 11
        WHEN title = 'Public Relations Officer' THEN 12
        ELSE 13
    END";
$portfolios = fetchAll($sql);

// Group portfolios by category (Executive Committee and Portfolio Officers)
$executivePortfolios = [];
$otherPortfolios = [];

// Simple categorization based on title
foreach ($portfolios as $portfolio) {
    if (in_array($portfolio['title'], ['President', 'Vice President', 'Senate President', 'Executive Secretary', 'Finance Officer'])) {
        $executivePortfolios[] = $portfolio;
    } else {
        $otherPortfolios[] = $portfolio;
    }
}

// Include header
require_once 'includes/header.php';

// Include the enhanced portfolio card styles
?>
<link rel="stylesheet" href="../css/portfolio-cards.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/profile-image-enhancement.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/enhanced-portfolio-cards.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/portfolio-role-fixes.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/portfolio-button-fix.css?v=<?php echo time(); ?>">
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
    /* Direct inline styles for problematic cards */
    .portfolio-card .card-header:contains("Senate") {
        background: linear-gradient(135deg, #ff9800, #e65100) !important;
        color: white !important;
    }
    
    .portfolio-card .card-header:contains("Editor") {
        background: linear-gradient(135deg, #009688, #00695c) !important;
        color: white !important;
    }
    
    .portfolio-card .card-header:contains("Public Relations"),
    .portfolio-card .card-header:contains("PRO") {
        background: linear-gradient(135deg, #9c27b0, #6a0080) !important;
        color: white !important;
    }
    
    /* Force specific colors for each role */
    [data-role*="Senate"] {
        --card-color: #ff9800 !important;
    }
    
    [data-role*="Editor"] {
        --card-color: #009688 !important;
    }
    
    [data-role*="PRO"],
    [data-role*="Public Relations"] {
        --card-color: #9c27b0 !important;
    }

    /* Organizing Secretary specific styling */
    [data-role*="Organizing Secretary"],
    [data-role="Organizing Secretary"] {
        --card-color: #8d6e63 !important;
    }

    .portfolio-card[data-role*="Organizing Secretary"] .card-header,
    .portfolio-card[data-role="Organizing Secretary"] .card-header {
        background: linear-gradient(135deg, #8d6e63, #5d4037) !important;
        color: white !important;
    }

    .portfolio-card[data-role*="Organizing Secretary"],
    .portfolio-card[data-role="Organizing Secretary"] {
        border-left-color: #8d6e63 !important;
        background: linear-gradient(135deg, rgba(141, 110, 99, 0.08), rgba(93, 64, 55, 0.05)) !important;
    }
    
    /* Footer Styles */
    body {
        margin: 0 !important;
        padding: 0 !important;
        min-height: 100vh !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .main-content {
        flex: 1 !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }

    .src-footer {
        margin-top: auto !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
    
    .src-footer .container-fluid,
    .footer-container {
        padding-left: 300px !important;
        padding-right: 50px !important;
        margin: 0 !important;
        max-width: none !important;
        width: 100% !important;
    }
    
    .footer-bottom {
        background: transparent !important;
    }
    
    .copyright-and-links {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        gap: 25px !important;
    }
</style>

<script>
    // Immediate script to fix specific cards
    document.addEventListener('DOMContentLoaded', function() {
        // Fix Organizing Secretary card first (highest priority)
        document.querySelectorAll('.card-header').forEach(header => {
            if (header.textContent.includes('Organizing Secretary')) {
                const card = header.closest('.portfolio-card');
                if (card) {
                    card.style.setProperty('border-left-color', '#8d6e63', 'important');
                    header.style.setProperty('background', 'linear-gradient(135deg, #8d6e63, #5d4037)', 'important');
                    header.style.setProperty('color', 'white', 'important');
                    console.log('Applied brown theme to Organizing Secretary');
                }
            }
        });

        // Fix other cards
        document.querySelectorAll('.card-header').forEach(header => {
            if (header.textContent.includes('Senate')) {
                const card = header.closest('.portfolio-card');
                if (card) {
                    card.style.borderLeftColor = '#ff9800';
                    header.style.background = 'linear-gradient(135deg, #ff9800, #e65100)';
                    header.style.color = 'white';
                }
            }
            
            if (header.textContent.includes('Editor')) {
                const card = header.closest('.portfolio-card');
                if (card) {
                    card.style.borderLeftColor = '#009688';
                    header.style.background = 'linear-gradient(135deg, #009688, #00695c)';
                    header.style.color = 'white';
                }
            }
            
            if (header.textContent.includes('Public Relations') || header.textContent.includes('PRO')) {
                const card = header.closest('.portfolio-card');
                if (card) {
                    card.style.borderLeftColor = '#9c27b0';
                    header.style.background = 'linear-gradient(135deg, #9c27b0, #6a0080)';
                    header.style.color = 'white';
                }
            }

            if (header.textContent.includes('Executive Secretary')) {
                const card = header.closest('.portfolio-card');
                if (card) {
                    card.style.borderLeftColor = '#17a2b8';
                    header.style.background = 'linear-gradient(135deg, #17a2b8, #117a8b)';
                    header.style.color = 'white';
                }
            }

            if (header.textContent.includes('Organizing Secretary')) {
                const card = header.closest('.portfolio-card');
                if (card) {
                    card.style.borderLeftColor = '#8d6e63';
                    header.style.background = 'linear-gradient(135deg, #8d6e63, #5d4037)';
                    header.style.color = 'white';
                }
            }
        });
    });
</script>

<div class="container-fluid px-4">
    
    <?php
    // Define page title, icon, and actions for the enhanced header
    ?>

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

<script>
    document.body.classList.add('portfolio-page');
</script>

<?php
// Set up modern page header variables
$pageTitle = "Portfolios";
$pageIcon = "fa-users";
$pageDescription = "Meet the SRC leadership team and their responsibilities";
$actions = [];

// DEBUG: Add detailed role checking for super admin accessibility issue
$currentUserRole = $_SESSION['role'] ?? 'unknown';
$userIsSuperAdmin = isSuperAdmin();
$userIsAdmin = isAdmin();
$userShouldUseAdminInterface = shouldUseAdminInterface();

// Force super admin access - this is the fix for the accessibility issue
if ($currentUserRole === 'super_admin' || $userIsSuperAdmin || $userShouldUseAdminInterface) {
    $actions[] = [
        'data-bs-toggle' => 'modal',
        'data-bs-target' => '#createPortfolioModal',
        'icon' => 'fa-plus',
        'text' => 'Create Portfolio',
        'class' => 'btn-primary'
    ];
}

// Include the modern page header
include 'includes/modern_page_header.php';
?>

<style>
.portfolio-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}
/* Mobile responsive portfolio-header */
@media (max-width: 768px) {
    .portfolio-header {
        margin-top: 3px !important;
        margin-bottom: 0.8rem !important;
        padding: 1.5rem 1rem !important;
    }
}

@media (max-width: 480px) {
    .portfolio-header {
        margin-top: 2px !important;
        margin-bottom: 0.6rem !important;
        padding: 1.2rem 0.8rem !important;
    }
}

@media (max-width: 375px) {
    .portfolio-header {
        margin-top: 2px !important;
        margin-bottom: 0.5rem !important;
        padding: 1rem 0.6rem !important;
    }
}

@media (max-width: 320px) {
    .portfolio-header {
        margin-top: 2px !important;
        margin-bottom: 0.5rem !important;
        padding: 0.8rem 0.5rem !important;
    }
}

/* Mobile responsive portfolio header */
@media (max-width: 768px) {
    .portfolio-header {
        margin-top: 3px !important;
        margin-bottom: 0.8rem !important;
        padding: 1.5rem 1rem !important;
    }
}

@media (max-width: 480px) {
    .portfolio-header {
        margin-top: 2px !important;
        margin-bottom: 0.6rem !important;
        padding: 1.2rem 0.8rem !important;
    }
}

@media (max-width: 375px) {
    .portfolio-header {
        margin-top: 2px !important;
        margin-bottom: 0.5rem !important;
        padding: 1rem 0.6rem !important;
    }
}

@media (max-width: 320px) {
    .portfolio-header {
        margin-top: 2px !important;
        margin-bottom: 0.5rem !important;
        padding: 0.8rem 0.5rem !important;
    }
}

.portfolio-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.portfolio-header-main {
    flex: 1;
    text-align: center;
}

.portfolio-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.portfolio-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.portfolio-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.portfolio-header-actions {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.btn-header-action {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
}

.btn-header-action:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
    .portfolio-header {
        padding: 2rem 1.5rem;
    }

    .portfolio-header-content {
        flex-direction: column;
        align-items: center;
    }

    .portfolio-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .portfolio-title i {
        font-size: 1.8rem;
    }

    .portfolio-description {
        font-size: 1.1rem;
    }

    .portfolio-header-actions {
        width: 100%;
        justify-content: center;
    }

    .btn-header-action {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
}

/* Animation classes */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translate3d(0, -100%, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

.animate__animated {
    animation-duration: 0.6s;
    animation-fill-mode: both;
}

.animate__fadeInDown {
    animation-name: fadeInDown;
}

/* Fix for Create Portfolio button clickability */
.header-actions .btn,
.header-actions button {
    pointer-events: auto !important;
    cursor: pointer !important;
    z-index: 1060 !important;
    position: relative !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

/* Ensure modal trigger buttons work properly */
.header-actions .btn[data-bs-toggle="modal"],
.header-actions button[data-bs-toggle="modal"] {
    pointer-events: auto !important;
    cursor: pointer !important;
    z-index: 1060 !important;
    position: relative !important;
    touch-action: manipulation !important;
}

/* Override any conflicting styles that might prevent clicking */
.header-actions .btn:not(:disabled),
.header-actions button:not(:disabled) {
    pointer-events: auto !important;
    cursor: pointer !important;
}
</style>

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
                    <?php if ($currentUserRole === 'super_admin' || $userIsSuperAdmin || $userShouldUseAdminInterface || shouldUseAdminInterface()): ?>
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
                            
                            // Show indicator if there are more responsibilities
                            if (count($responsibilities) > 4):
                            ?>
                            <li class="more-items"><i class="fas fa-ellipsis-h"></i> <a href="portfolio-detail.php?id=<?php echo $portfolio['portfolio_id']; ?>">See <?php echo count($responsibilities) - 4; ?> more...</a></li>
                            <?php endif; ?>
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
                    <?php if (shouldUseAdminInterface()): ?>
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
                            
                            // Show indicator if there are more responsibilities
                            if (count($responsibilities) > 4):
                            ?>
                            <li class="more-items"><i class="fas fa-ellipsis-h"></i> <a href="portfolio-detail.php?id=<?php echo $portfolio['portfolio_id']; ?>">See <?php echo count($responsibilities) - 4; ?> more...</a></li>
                            <?php endif; ?>
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
<?php if ($currentUserRole === 'super_admin' || $userIsSuperAdmin || $userShouldUseAdminInterface || $isAdmin): ?>
<div class="modal fade" id="createPortfolioModal" tabindex="-1" aria-labelledby="createPortfolioModalLabel" aria-hidden="true" data-bs-backdrop="false">
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

<script>
// FIX CREATE PORTFOLIO BUTTON - Enhanced for super admin accessibility
document.addEventListener('DOMContentLoaded', function() {
    // Debug current user info for accessibility troubleshooting
    console.log('🔍 PORTFOLIO ADMIN ACCESS DEBUG:');
    console.log('- Current PHP role: <?php echo $currentUserRole; ?>');
    console.log('- isSuperAdmin(): <?php echo $userIsSuperAdmin ? "true" : "false"; ?>');
    console.log('- isAdmin(): <?php echo $userIsAdmin ? "true" : "false"; ?>');
    console.log('- shouldUseAdminInterface(): <?php echo $userShouldUseAdminInterface ? "true" : "false"; ?>');
    console.log('- Button should appear: <?php echo (!empty($actions)) ? "true" : "false"; ?>');
    
    console.log('🔧 Portfolio page loaded - waiting for Bootstrap...');
    
    // Function to check if Bootstrap is available and setup button
    function setupCreatePortfolioButton() {
        if (typeof bootstrap === 'undefined') {
            console.log('⏳ Bootstrap not yet available, retrying...');
            setTimeout(setupCreatePortfolioButton, 100);
            return;
        }
        
        console.log('✅ Bootstrap is available, setting up Create Portfolio button...');
        
        // Check if modal exists
        const modal = document.querySelector('#createPortfolioModal');
        console.log('Modal exists:', modal ? '✅ Yes' : '❌ No', modal);
        
        // Check if button exists
        const button = document.querySelector('[data-bs-target="#createPortfolioModal"]');
        console.log('Button exists:', button ? '✅ Yes' : '❌ No', button);
        
        if (button && modal) {
            console.log('🔧 Both button and modal found - setting up functionality...');
            
            // Remove any existing event listeners
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Force button properties
            newButton.style.cssText = `
                pointer-events: auto !important;
                cursor: pointer !important;
                z-index: 1070 !important;
                position: relative !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                visibility: visible !important;
                opacity: 1 !important;
            `;
            
            // Add click event listener
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('🚀 Create Portfolio button clicked!');
                console.log('Bootstrap version:', bootstrap.Tooltip.VERSION || 'unknown');
                
                try {
                    // Create and show modal using Bootstrap
                    const modalInstance = new bootstrap.Modal(modal, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                    modalInstance.show();
                    console.log('✅ Modal opened successfully!');
                } catch (error) {
                    console.error('❌ Error opening modal:', error);
                    // Fallback: try to show modal directly
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
                    document.body.classList.add('modal-open');
                    
                    // Add a backdrop
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                    
                    console.log('✅ Modal opened using fallback method!');
                }
            }, { passive: false, capture: true });
            
            console.log('✅ Create Portfolio button setup complete!');
        } else {
            console.error('❌ Missing elements:', { button: !!button, modal: !!modal });
            if (!button) {
                console.log('Searching for any modal buttons...');
                const allModalButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
                console.log('Found modal buttons:', allModalButtons.length, allModalButtons);
            }
        }
    }
    
    // Start checking for Bootstrap availability
    setupCreatePortfolioButton();
    
    // Also try after a longer delay in case Bootstrap loads very late
    setTimeout(setupCreatePortfolioButton, 2000);
    
    // Senate card styling fix below
        document.querySelectorAll('.card-header').forEach(function(header) {
            const text = header.textContent.trim();
            const card = header.closest('.portfolio-card');
            
            if (!card) return;
            
            if (text.includes('Senate')) {
                console.log('Direct fix: Senate card found');
                card.style.borderLeftColor = '#ff9800';
                header.style.background = 'linear-gradient(135deg, #ff9800, #e65100)';
                header.style.color = 'white';
                
                const icons = card.querySelectorAll('.responsibilities-list li i');
                icons.forEach(icon => icon.style.color = '#ff9800');
                
                const moreLink = card.querySelector('.responsibilities-list li.more-items a');
                if (moreLink) moreLink.style.color = '#ff9800';
                
                const btn = card.querySelector('.card-actions .btn-primary');
                if (btn) {
                    btn.style.background = 'linear-gradient(135deg, #ff9800, #e65100)';
                    btn.style.borderColor = '#ff9800';
                }
            }
            
            if (text.includes('Editor')) {
                console.log('Direct fix: Editor card found');
                card.style.borderLeftColor = '#009688';
                header.style.background = 'linear-gradient(135deg, #009688, #00695c)';
                header.style.color = 'white';
                
                const icons = card.querySelectorAll('.responsibilities-list li i');
                icons.forEach(icon => icon.style.color = '#009688');
                
                const moreLink = card.querySelector('.responsibilities-list li.more-items a');
                if (moreLink) moreLink.style.color = '#009688';
                
                const btn = card.querySelector('.card-actions .btn-primary');
                if (btn) {
                    btn.style.background = 'linear-gradient(135deg, #009688, #00695c)';
                    btn.style.borderColor = '#009688';
                }
            }
            
            if (text.includes('Public Relations') || text.includes('PRO')) {
                console.log('Direct fix: PRO card found');
                card.style.borderLeftColor = '#9c27b0';
                header.style.background = 'linear-gradient(135deg, #9c27b0, #6a0080)';
                header.style.color = 'white';
                
                const icons = card.querySelectorAll('.responsibilities-list li i');
                icons.forEach(icon => icon.style.color = '#9c27b0');
                
                const moreLink = card.querySelector('.responsibilities-list li.more-items a');
                if (moreLink) moreLink.style.color = '#9c27b0';
                
                const btn = card.querySelector('.card-actions .btn-primary');
                if (btn) {
                    btn.style.background = 'linear-gradient(135deg, #9c27b0, #6a0080)';
                    btn.style.borderColor = '#9c27b0';
                }
            }
        });
    }, 500); // Delay to ensure DOM is fully loaded and other scripts have run
    
// Add modal close functionality
const modal = document.querySelector('#createPortfolioModal');
if (modal) {
    // Close button
    const closeBtn = modal.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            if (typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            } else {
                modal.style.display = 'none';
                modal.classList.remove('show');
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) backdrop.remove();
            }
        });
    }
        
    // Cancel button
    const cancelBtn = modal.querySelector('[data-bs-dismiss="modal"]');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            if (typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            } else {
                modal.style.display = 'none';
                modal.classList.remove('show');
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) backdrop.remove();
            }
        });
    }
});
</script>

</div> <!-- Close container-fluid -->
</div> <!-- Close main-content -->

<?php require_once 'includes/footer.php'; ?>
