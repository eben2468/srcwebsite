<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Include auto notifications system
require_once __DIR__ . '/includes/auto_notifications.php';

// Require login for this page
requireLogin();

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if news feature is enabled
if (!hasFeaturePermission('enable_news')) {
    $_SESSION['error'] = "The news & announcements feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$hasAdminInterface = shouldUseAdminInterface();
$isMember = isMember();
$canManageNews = $hasAdminInterface || $isMember; // Allow super admin, admin, and members to manage news

// Check if the user is trying to create a new article and has permission
if (isset($_GET['action']) && $_GET['action'] === 'new') {
    if (!$canManageNews) {
        // Redirect non-privileged users back to the news page
        header("Location: news.php");
        exit();
    }
}

// Set page title and body class
$pageTitle = "News - SRC Management System";
$bodyClass = "page-news"; // Add body class for CSS targeting

// Initialize messages
$successMessage = '';
$errorMessage = '';

// Handle Create News
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['news_title'])) {
    // Check if admin has permission to create news
    if ($canManageNews) {
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
        $imagePath = null;
        if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = $_FILES['news_image']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Invalid image format. Allowed formats: JPG, PNG, GIF, WEBP";
            } else {
                $uploadDir = '../uploads/news/';
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
        $documentPath = null;
        if (isset($_FILES['news_document']) && $_FILES['news_document']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $fileType = $_FILES['news_document']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Invalid document format. Allowed formats: PDF, DOC, DOCX";
            } else {
                $uploadDir = '../uploads/news/';
                $fileName = 'doc_' . time() . '_' . basename($_FILES['news_document']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['news_document']['tmp_name'], $targetFile)) {
                    $documentPath = 'uploads/news/' . $fileName;
                } else {
                    $errors[] = "Failed to upload document. Please try again.";
                }
            }
        }
        
        // If no errors, insert into database
        if (empty($errors)) {
            $sql = "INSERT INTO news (title, content, image_path, document_path, author_id, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $result = insert($sql, [
                $title, 
                $content,
                $imagePath,
                $documentPath,
                $currentUser['user_id'], 
                $status
            ]);
            
            if ($result) {
                $successMessage = "News item created successfully!";

                // Send notification to all users about new news
                $news_id = $result; // The insert function returns the new ID
                autoNotifyNewsCreated($title, $content, $currentUser['user_id'], $news_id);
            } else {
                $errorMessage = "Error creating news item. Please try again.";
            }
        } else {
            $errorMessage = implode("<br>", $errors);
        }
    } else {
        $errorMessage = "You don't have permission to create news.";
    }
}

// Handle Delete News
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $newsId = intval($_GET['id']);
    
    if ($canManageNews) {
        // Check if news exists
        $checkNews = fetchOne("SELECT news_id FROM news WHERE news_id = ?", [$newsId]);
        
        if ($checkNews) {
            $result = delete("DELETE FROM news WHERE news_id = ?", [$newsId]);
            
            if ($result) {
                $successMessage = "News item deleted successfully!";
            } else {
                $errorMessage = "Error deleting news item. Please try again.";
            }
        } else {
            $errorMessage = "News item not found.";
        }
    } else {
        $errorMessage = "You don't have permission to delete news.";
    }
}

// Build query with filters
$whereConditions = [];
$params = [];
$types = '';

// Search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $whereConditions[] = "(title LIKE ? OR content LIKE ?)";
    $params = array_merge($params, [$search, $search]);
    $types .= 'ss';
}

// Status filter
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $whereConditions[] = "status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

// Month filter
if (isset($_GET['month']) && !empty($_GET['month'])) {
    // Format: YYYY-MM
    $month = $_GET['month'];
    $whereConditions[] = "DATE_FORMAT(created_at, '%Y-%m') = ?";
    $params = array_merge($params, [$month]);
    $types .= 's';
}

// Build the complete query
$sql = "SELECT n.*, u.username as author_name 
        FROM news n 
        LEFT JOIN users u ON n.author_id = u.user_id";

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY created_at DESC";

// Fetch news from database
$news = fetchAll($sql, $params, $types);

// Include header
require_once 'includes/header.php';
?>

<script>
    document.body.classList.add('news-page');
</script>

<!-- Custom News Header -->
<div class="news-header animate__animated animate__fadeInDown">
    <div class="news-header-content">
        <div class="news-header-main">
            <h1 class="news-title">
                <i class="fas fa-newspaper me-3"></i>
                News
            </h1>
            <p class="news-description">Stay informed with the latest news and announcements</p>
        </div>
        <?php if ($canManageNews): ?>
        <div class="news-header-actions">
            <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#createNewsModal">
                <i class="fas fa-plus me-2"></i>Create News
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.news-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.news-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.news-header-main {
    flex: 1;
    text-align: center;
}

.news-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    color: white !important;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.news-title i {
    font-size: 2.2rem;
    opacity: 0.9;
    color: white !important;
}

.news-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
    color: white !important;
}

.news-header-actions {
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
    .news-header {
        padding: 2rem 1.5rem;
    }

    .news-header-content {
        flex-direction: column;
        align-items: center;
    }

    .news-title {
        font-size: 2rem;
        gap: 0.6rem;
        color: white !important;
    }

    .news-title i {
        font-size: 1.8rem;
        color: white !important;
    }

    .news-description {
        font-size: 1.1rem;
        color: white !important;
    }

    .news-header-actions {
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

/* Mobile Full-Width Optimization for News Page */
@media (max-width: 991px) {
    [class*="col-md-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid, .container {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Ensure news header has border-radius on mobile */
    .header, .news-header {
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .card, .news-card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
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

<h3 class="mb-3">All News</h3>

<!-- News Cards Layout -->
<div class="news-grid">
    <?php if (empty($news)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> No news found.
    </div>
    <?php else: ?>
    <?php foreach ($news as $item): ?>
    <div class="card news-card">
        <?php if (!empty($item['image_path'])): ?>
        <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>">
        <?php endif; ?>
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
            <p class="card-text"><small class="text-muted"><?php echo date('M j, Y', strtotime($item['created_at'])); ?> by <?php echo htmlspecialchars($item['author_name']); ?></small></p>
            <p class="card-text"><?php echo substr(htmlspecialchars($item['content']), 0, 100); ?>...</p>
            <span class="badge bg-<?php echo $item['status'] === 'published' ? 'success' : 'warning'; ?>">
                <?php echo strtoupper(htmlspecialchars($item['status'])); ?>
            </span>
        </div>
        <div class="card-footer">
            <a href="news-detail.php?id=<?php echo $item['news_id']; ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-eye"></i> View
            </a>
            <?php if ($canManageNews): ?>
            <a href="news-edit.php?id=<?php echo $item['news_id']; ?>" class="btn btn-sm btn-info">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="news.php?action=delete&id=<?php echo $item['news_id']; ?>" class="btn btn-sm btn-danger" 
               onclick="return confirm('Are you sure you want to delete this news item?');">
                <i class="fas fa-trash"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.news-card {
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.news-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.news-card .card-img-top {
    height: 200px;
    object-fit: cover;
}

.news-card .card-body {
    padding: 1.25rem;
}

.news-card .card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.news-card .card-text {
    color: #6c757d;
}

.news-card .card-footer {
    background-color: #f8f9fa;
    padding: 0.75rem 1.25rem;
    display: flex;
    gap: 0.5rem;
}

/* Mobile navbar size increase and spacing adjustments for news page */
@media (max-width: 768px) {
    .news-page .navbar {
        height: 70px !important;
        padding: 0.75rem 1rem !important;
    }
    
    .news-page .navbar .navbar-brand {
        font-size: 1.3rem !important;
    }
    
    .news-page .navbar .system-icon {
        width: 35px !important;
        height: 35px !important;
    }
    
    .news-page .navbar .btn {
        font-size: 1.1rem !important;
        padding: 0.5rem 0.75rem !important;
    }
    
    .news-page .navbar .site-name {
        font-size: 1.1rem !important;
    }
    
    /* Remove main-content padding-top to prevent double spacing */
    .news-page .main-content {
        padding-top: 0 !important;
    }
    
    /* Adjust margin between navbar and page header to 30px */
    .news-page .header {
        margin-top: 100px !important; /* 70px navbar + 30px spacing */
    }
}

@media (max-width: 480px) {
    .news-page .navbar {
        height: 65px !important;
        padding: 0.6rem 0.8rem !important;
    }
    
    .news-page .navbar .navbar-brand {
        font-size: 1.2rem !important;
    }
    
    .news-page .navbar .system-icon {
        width: 32px !important;
        height: 32px !important;
    }
    
    .news-page .navbar .btn {
        font-size: 1rem !important;
        padding: 0.4rem 0.6rem !important;
    }
    
    .news-page .navbar .site-name {
        font-size: 1rem !important;
    }
    
    /* Remove main-content padding-top to prevent double spacing */
    .news-page .main-content {
        padding-top: 0 !important;
    }
    
    /* Adjust margin between navbar and page header to 30px */
    .news-page .header {
        margin-top: 95px !important; /* 65px navbar + 30px spacing */
    }
}

@media (max-width: 375px) {
    .news-page .navbar {
        height: 60px !important;
        padding: 0.5rem 0.7rem !important;
    }
    
    .news-page .navbar .navbar-brand {
        font-size: 1.1rem !important;
    }
    
    .news-page .navbar .system-icon {
        width: 30px !important;
        height: 30px !important;
    }
    
    .news-page .navbar .btn {
        font-size: 0.95rem !important;
        padding: 0.35rem 0.5rem !important;
    }
    
    .news-page .navbar .site-name {
        font-size: 0.95rem !important;
    }
    
    /* Remove main-content padding-top to prevent double spacing */
    .news-page .main-content {
        padding-top: 0 !important;
    }
    
    /* Adjust margin between navbar and page header to 30px */
    .news-page .header {
        margin-top: 90px !important; /* 60px navbar + 30px spacing */
    }
}
</style>


<!-- Create News Modal -->
<div class="modal fade" id="createNewsModal" tabindex="-1" aria-labelledby="createNewsModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createNewsModalLabel">Create News</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="news_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="news_title" name="news_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="news_content" class="form-label">Content</label>
                        <textarea class="form-control" id="news_content" name="news_content" rows="6" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="news_status" class="form-label">Status</label>
                        <select class="form-select" id="news_status" name="news_status" required>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="news_image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="news_image" name="news_image">
                    </div>
                    <div class="mb-3">
                        <label for="news_document" class="form-label">Document</label>
                        <input type="file" class="form-control" id="news_document" name="news_document">
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create News</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</div> <!-- Close main content container -->

<?php require_once 'includes/footer.php'; ?> 
