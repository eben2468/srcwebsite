<?php
// Include authentication file and database connection
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../settings_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if news feature is enabled
if (!isFeatureEnabled('enable_news', true)) {
    $_SESSION['error'] = "The news & announcements feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();
$canManageNews = $isAdmin || $isMember; // Allow both admins and members to manage news

// Check if the user is trying to create a new article and has permission
if (isset($_GET['action']) && $_GET['action'] === 'new') {
    if (!$canManageNews) {
        // Redirect non-privileged users back to the news page
        header("Location: news.php");
        exit();
    }
}

// Set page title
$pageTitle = "News - SRC Management System";

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

<div class="header">
    <h1 class="page-title">News & Announcements</h1>
    
    <div class="header-actions">
        <?php if ($canManageNews): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNewsModal">
            <i class="fas fa-plus me-2"></i> Create News
        </button>
        <?php endif; ?>
    </div>
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

<h3 class="mb-3">All News</h3>

<!-- News Table -->
<div class="content-card">
    <div class="content-card-body">
        <?php if (empty($news)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> No news found.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>TITLE</th>
                        <th>DATE</th>
                        <th>AUTHOR</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($news as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($item['author_name']); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $item['status'] === 'published' ? 'success' : 'warning'; 
                            ?>">
                                <?php echo strtoupper(htmlspecialchars($item['status'])); ?>
                            </span>
                        </td>
                        <td>
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
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create News Modal -->
<div class="modal fade" id="createNewsModal" tabindex="-1" aria-labelledby="createNewsModalLabel" aria-hidden="true">
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

<?php require_once 'includes/footer.php'; ?> 