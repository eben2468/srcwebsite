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

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Set page title
$pageTitle = "News Details - SRC Management System";

// Get current user info
$currentUser = getCurrentUser();
$hasAdminInterface = shouldUseAdminInterface();
$isMember = isMember();
$canManageNews = $hasAdminInterface || $isMember; // Allow super admin, admin, and members to manage news

// Get news ID from URL
$newsId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize messages
$successMessage = '';
$errorMessage = '';

// Fetch news from database
$news = fetchOne("SELECT n.*, u.username as author_name 
                  FROM news n 
                  LEFT JOIN users u ON n.author_id = u.user_id 
                  WHERE n.news_id = ?", [$newsId]);

// Check if user is the author or an admin
$isAuthor = false;
if ($news) {
    $isAuthor = $news['author_id'] == $currentUser['user_id'];
}
$canEditNews = $hasAdminInterface || ($isMember && $isAuthor);

// Handle delete action
if (isset($_GET['delete']) && $_GET['delete'] == 'confirm' && $canEditNews) {
    if ($news) {
        // Delete associated files if they exist
        if (!empty($news['image_path'])) {
            $imagePath = '../' . $news['image_path'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        if (!empty($news['document_path'])) {
            $documentPath = '../' . $news['document_path'];
            if (file_exists($documentPath)) {
                unlink($documentPath);
            }
        }
        
        $result = delete("DELETE FROM news WHERE news_id = ?", [$newsId]);
        
        if ($result) {
            header("Location: news.php?deleted=true");
            exit;
        } else {
            $errorMessage = "Error deleting news item. Please try again.";
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    <?php if (!$news): ?>
    <div class="alert alert-danger">
        <h4 class="alert-heading">News Not Found</h4>
        <p>The news item you are looking for does not exist or has been removed.</p>
        <a href="news.php" class="btn btn-outline-danger">
            <i class="fas fa-arrow-left me-2"></i> Back to News
        </a>
    </div>
    <?php else: ?>

    <!-- Custom News Detail Header -->
    <div class="news-detail-header animate__animated animate__fadeInDown">
        <div class="news-detail-header-content">
            <div class="news-detail-header-main">
                <h1 class="news-detail-title">
                    <i class="fas fa-newspaper me-3"></i>
                    <?php echo htmlspecialchars($news['title']); ?>
                </h1>
                <p class="news-detail-description">News Article Details and Information</p>
            </div>
            <div class="news-detail-header-actions">
                <a href="news.php" class="btn btn-header-action">
                    <i class="fas fa-arrow-left me-2"></i>Back to News
                </a>
                <?php if ($canEditNews): ?>
                <a href="news-edit.php?id=<?php echo $news['news_id']; ?>" class="btn btn-header-action">
                    <i class="fas fa-edit me-2"></i>Edit News
                </a>
                <button type="button" class="btn btn-header-action btn-header-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
                    <i class="fas fa-trash me-2"></i>Delete News
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
    .news-detail-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2.5rem 2rem;
        border-radius: 12px;
        margin-top: 60px;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .news-detail-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .news-detail-header-main {
        flex: 1;
        text-align: center;
    }

    .news-detail-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 1rem 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.8rem;
    }

    .news-detail-title i {
        font-size: 2.2rem;
        opacity: 0.9;
    }

    .news-detail-description {
        margin: 0;
        opacity: 0.95;
        font-size: 1.2rem;
        font-weight: 400;
        line-height: 1.4;
    }

    .news-detail-header-actions {
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
        text-decoration: none;
    }

    .btn-header-action:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        text-decoration: none;
    }

    .btn-header-danger {
        background: rgba(220, 53, 69, 0.3);
        border-color: rgba(220, 53, 69, 0.5);
    }

    .btn-header-danger:hover {
        background: rgba(220, 53, 69, 0.5);
        border-color: rgba(220, 53, 69, 0.7);
    }

    @media (max-width: 768px) {
        .news-detail-header {
            padding: 2rem 1.5rem;
        }

        .news-detail-header-content {
            flex-direction: column;
            align-items: center;
        }

        .news-detail-title {
            font-size: 2rem;
            gap: 0.6rem;
        }

        .news-detail-title i {
            font-size: 1.8rem;
        }

        .news-detail-description {
            font-size: 1.1rem;
        }

        .news-detail-header-actions {
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
    </style>
    </div>
    
    <!-- Notification area -->
    <?php if (!empty($successMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $successMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $errorMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- News Details -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h2><?php echo htmlspecialchars($news['title']); ?></h2>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <div class="d-flex align-items-center text-muted">
                            <i class="fas fa-calendar-alt me-2"></i> <?php echo date('M j, Y', strtotime($news['created_at'])); ?>
                        </div>
                        <div class="d-flex align-items-center text-muted">
                            <i class="fas fa-user me-2"></i> <?php echo htmlspecialchars($news['author_name']); ?>
                        </div>
                        <span class="badge bg-<?php 
                            echo $news['status'] === 'published' ? 'success' : 'warning'; 
                        ?>">
                            <?php echo ucfirst(htmlspecialchars($news['status'])); ?>
                        </span>
                    </div>
                    <div class="news-content mb-4">
                        <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                    </div>
                    
                    <?php if (!empty($news['image_path'])): ?>
                    <div class="news-image mb-4">
                        <h5>Attached Image</h5>
                        <div class="card">
                            <div class="card-body text-center">
                                <img src="<?php echo '../' . htmlspecialchars($news['image_path']); ?>" alt="News Image" class="img-fluid rounded" style="max-height: 400px;">
                                <div class="mt-3 file-actions">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#imageModal">
                                        <i class="fas fa-search-plus me-2"></i> View Larger
                                    </button>
                                    <a href="<?php echo '../' . htmlspecialchars($news['image_path']); ?>" class="btn btn-info ms-2" download>
                                        <i class="fas fa-download me-2"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light h-100">
                        <div class="card-body">
                            <h5 class="mb-3">News Information</h5>
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Created:</td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($news['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Last Updated:</td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($news['updated_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Status:</td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $news['status'] === 'published' ? 'success' : 'warning'; 
                                            ?>">
                                                <?php echo ucfirst(htmlspecialchars($news['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <?php if (!empty($news['document_path'])): ?>
                            <div class="mt-4">
                                <h5 class="mb-3">Attached Document</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-alt fa-3x me-3"></i>
                                            <div>
                                                <p class="mb-1"><?php echo basename($news['document_path']); ?></p>
                                                <div class="file-actions">
                                                    <a href="<?php echo '../' . htmlspecialchars($news['document_path']); ?>" class="btn btn-sm btn-primary me-2" target="_blank">
                                                        <i class="fas fa-eye me-1"></i> View
                                                    </a>
                                                    <a href="<?php echo '../' . htmlspecialchars($news['document_path']); ?>" class="btn btn-sm btn-info" download>
                                                        <i class="fas fa-download me-1"></i> Download
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<?php if ($news && $canEditNews): ?>
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the news item "<strong><?php echo htmlspecialchars($news['title']); ?></strong>"?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="news-detail.php?id=<?php echo $news['news_id']; ?>&delete=confirm" class="btn btn-danger">Delete News</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Image Modal -->
<?php if (!empty($news['image_path'])): ?>
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">News Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="<?php echo '../' . htmlspecialchars($news['image_path']); ?>" alt="News Image" class="img-fluid" style="max-height: 80vh;">
            </div>
            <div class="modal-footer">
                <a href="<?php echo '../' . htmlspecialchars($news['image_path']); ?>" class="btn btn-info" download>
                    <i class="fas fa-download me-1"></i> Download Image
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 
