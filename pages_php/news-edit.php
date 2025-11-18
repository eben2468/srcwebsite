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

// Get current user
$currentUser = getCurrentUser();
$hasAdminInterface = shouldUseAdminInterface();
$isMember = isMember();
$canManageNews = $hasAdminInterface || $isMember; // Allow super admin, admin, and members to manage news

// Check if user has permission to edit news
if (!$canManageNews) {
    header("Location: news.php?error=unauthorized");
    exit();
}

// Set page title
$pageTitle = "Edit News - SRC Management System";

// Get news ID from URL
$newsId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize messages
$successMessage = '';
$errorMessage = '';

// Fetch news details
$news = fetchOne("SELECT * FROM news WHERE news_id = ?", [$newsId]);

// Check if news exists
if (!$news) {
    header("Location: news.php?error=not_found");
    exit();
}

// Check if user is the author or has admin interface access
$isAuthor = $news['author_id'] == $currentUser['user_id'];
if (!$hasAdminInterface && !$isAuthor) {
    header("Location: news.php?error=unauthorized");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_news'])) {
    // Get form data
    $title = trim($_POST['news_title']);
    $content = trim($_POST['news_content']);
    $status = $_POST['news_status'];
    
    // Validate required fields
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    // Handle image upload if provided
    $imagePath = $news['image_path']; // Keep existing image by default
    
    // Check if delete image was requested
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == 1) {
        // If there was an existing image, delete the file
        if (!empty($news['image_path'])) {
            $fullPath = '../' . $news['image_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        $imagePath = null; // Clear the image path
    }
    // Check if new image was uploaded
    elseif (isset($_FILES['news_image']) && $_FILES['news_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['news_image']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Invalid image format. Allowed formats: JPG, PNG, GIF, WEBP";
        } else {
            // If there was an existing image, delete it
            if (!empty($news['image_path'])) {
                $fullPath = '../' . $news['image_path'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            
            $uploadDir = '../uploads/news/';
            // Ensure directory exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = 'news_' . time() . '_' . basename($_FILES['news_image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['news_image']['tmp_name'], $targetFile)) {
                $imagePath = 'uploads/news/' . $fileName;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    }
    
    // Handle document upload if provided
    $documentPath = $news['document_path']; // Keep existing document by default
    
    // Check if delete document was requested
    if (isset($_POST['delete_document']) && $_POST['delete_document'] == 1) {
        // If there was an existing document, delete the file
        if (!empty($news['document_path'])) {
            $fullPath = '../' . $news['document_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        $documentPath = null; // Clear the document path
    }
    // Check if new document was uploaded
    elseif (isset($_FILES['news_document']) && $_FILES['news_document']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $fileType = $_FILES['news_document']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Invalid document format. Allowed formats: PDF, DOC, DOCX";
        } else {
            // If there was an existing document, delete it
            if (!empty($news['document_path'])) {
                $fullPath = '../' . $news['document_path'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            
            $uploadDir = '../uploads/news/';
            // Ensure directory exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = 'doc_' . time() . '_' . basename($_FILES['news_document']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['news_document']['tmp_name'], $targetFile)) {
                $documentPath = 'uploads/news/' . $fileName;
            } else {
                $errors[] = "Failed to upload document. Please try again.";
            }
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        $sql = "UPDATE news SET 
                title = ?, 
                content = ?, 
                status = ?, 
                image_path = ?,
                document_path = ?,
                updated_at = NOW() 
                WHERE news_id = ?";
        
        $result = update($sql, [
            $title, 
            $content, 
            $status,
            $imagePath,
            $documentPath,
            $newsId
        ]);
        
        if ($result) {
            $successMessage = "News item updated successfully!";
            // Refresh news data
            $news = fetchOne("SELECT * FROM news WHERE news_id = ?", [$newsId]);
        } else {
            $errorMessage = "Error updating news item. Please try again.";
        }
    } else {
        $errorMessage = implode("<br>", $errors);
    }
}

// Include header
require_once 'includes/header.php';
?>


<!-- Custom News Edit Header -->
<div class="news-edit-header animate__animated animate__fadeInDown">
    <div class="news-edit-header-content">
        <div class="news-edit-header-main">
            <h1 class="news-edit-title">
                <i class="fas fa-edit me-3"></i>
                Edit News
            </h1>
            <p class="news-edit-description">Modify news article details and content</p>
        </div>
        <div class="news-edit-header-actions">
            <a href="news-detail.php?id=<?php echo $newsId; ?>" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to News
            </a>
            <a href="news.php" class="btn btn-header-action">
                <i class="fas fa-newspaper me-2"></i>All News
            </a>
        </div>
    </div>
</div>

<style>
.news-edit-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.news-edit-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.news-edit-header-main {
    flex: 1;
    text-align: center;
}

.news-edit-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.news-edit-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.news-edit-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.news-edit-header-actions {
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

@media (max-width: 768px) {
    .news-edit-header {
        padding: 2rem 1.5rem;
    }

    .news-edit-header-content {
        flex-direction: column;
        align-items: center;
    }

    .news-edit-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .news-edit-title i {
        font-size: 1.8rem;
    }

    .news-edit-description {
        font-size: 1.1rem;
    }

    .news-edit-header-actions {
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

<!-- Edit News Form -->
<div class="content-card">
    <div class="content-card-header">
        <h3 class="content-card-title">Edit News: <?php echo htmlspecialchars($news['title']); ?></h3>
    </div>
    <div class="content-card-body">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $newsId; ?>" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="news_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="news_title" name="news_title" required
                               value="<?php echo htmlspecialchars($news['title']); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="news_status" class="form-label">Status</label>
                        <select class="form-select" id="news_status" name="news_status" required>
                            <option value="draft" <?php echo $news['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $news['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="news_content" class="form-label">Content</label>
                <textarea class="form-control" id="news_content" name="news_content" rows="10" required><?php echo htmlspecialchars($news['content']); ?></textarea>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Image</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($news['image_path'])): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-center mb-2">
                                        <img src="<?php echo '../' . htmlspecialchars($news['image_path']); ?>" alt="News Image" class="img-fluid rounded" style="max-height: 200px;">
                                    </div>
                                    <div class="d-flex justify-content-center file-actions">
                                        <button type="button" class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#imageModal">
                                            <i class="fas fa-search-plus me-1"></i> View Larger
                                        </button>
                                        <a href="<?php echo '../' . htmlspecialchars($news['image_path']); ?>" class="btn btn-info btn-sm me-2" download>
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                        <?php if ($canManageNews): ?>
                                        <div class="form-check form-switch ms-2 d-flex align-items-center">
                                            <input class="form-check-input" type="checkbox" id="delete_image" name="delete_image" value="1">
                                            <label class="form-check-label ms-2" for="delete_image">Delete</label>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="news_image" class="form-label"><?php echo !empty($news['image_path']) ? 'Replace Image' : 'Upload Image'; ?></label>
                                <input type="file" class="form-control" id="news_image" name="news_image">
                                <div class="form-text">Allowed formats: JPG, PNG, GIF, WEBP</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Document</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($news['document_path'])): ?>
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-file-alt fa-3x me-3"></i>
                                        <div>
                                            <p class="mb-1"><?php echo basename($news['document_path']); ?></p>
                                            <div class="d-flex align-items-center file-actions">
                                                <a href="<?php echo '../' . htmlspecialchars($news['document_path']); ?>" class="btn btn-sm btn-primary me-2" target="_blank">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                                <a href="<?php echo '../' . htmlspecialchars($news['document_path']); ?>" class="btn btn-sm btn-info me-2" download>
                                                    <i class="fas fa-download me-1"></i> Download
                                                </a>
                                                <?php if ($canManageNews): ?>
                                                <div class="form-check form-switch ms-2 d-flex align-items-center">
                                                    <input class="form-check-input" type="checkbox" id="delete_document" name="delete_document" value="1">
                                                    <label class="form-check-label ms-2" for="delete_document">Delete</label>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="news_document" class="form-label"><?php echo !empty($news['document_path']) ? 'Replace Document' : 'Upload Document'; ?></label>
                                <input type="file" class="form-control" id="news_document" name="news_document">
                                <div class="form-text">Allowed formats: PDF, DOC, DOCX</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label text-muted">News Information</label>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Author</h6>
                                <p class="card-text"><?php 
                                    $author = fetchOne("SELECT username FROM users WHERE user_id = ?", [$news['author_id']]);
                                    echo $author ? htmlspecialchars($author['username']) : 'Unknown';
                                ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Created On</h6>
                                <p class="card-text"><?php echo date('M j, Y g:i A', strtotime($news['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Last Updated</h6>
                                <p class="card-text"><?php echo date('M j, Y g:i A', strtotime($news['updated_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="news-detail.php?id=<?php echo $newsId; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-eye me-2"></i> View News
                </a>
                <div>
                    <a href="news.php" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" name="update_news" class="btn btn-primary">Update News</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Image Modal -->
<?php if (!empty($news['image_path'])): ?>
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true" data-bs-backdrop="false">
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
