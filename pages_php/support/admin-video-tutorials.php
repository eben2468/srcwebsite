<?php
// Include simple authentication and required files
require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/db_functions.php';
require_once __DIR__ . '/../../includes/settings_functions.php';

// Require login and admin interface access
requireLogin();
$shouldUseAdminInterface = shouldUseAdminInterface();
if (!$shouldUseAdminInterface) {
    header('Location: ../../dashboard.php?error=access_denied');
    exit();
}

// Get current user info
$currentUser = getCurrentUser();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_tutorial':
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $video_type = $_POST['video_type'];
                $video_url = trim($_POST['video_url']);
                $duration = trim($_POST['duration']);
                $difficulty = $_POST['difficulty'];
                $category = trim($_POST['category']);
                $target_roles = json_encode($_POST['target_roles'] ?? []);
                $tags = json_encode(array_map('trim', explode(',', $_POST['tags'])));
                $sort_order = intval($_POST['sort_order']);
                
                // Handle file upload for video
                $video_file = '';
                $thumbnail_image = '';
                
                if ($video_type === 'upload' && isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../../uploads/videos/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileName = time() . '_' . basename($_FILES['video_file']['name']);
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['video_file']['tmp_name'], $targetPath)) {
                        $video_file = $fileName;
                    }
                }
                
                // Handle thumbnail upload
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../../uploads/thumbnails/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileName = time() . '_thumb_' . basename($_FILES['thumbnail']['name']);
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetPath)) {
                        $thumbnail_image = $fileName;
                    }
                }
                
                $sql = "INSERT INTO video_tutorials (title, description, video_type, video_url, video_file, thumbnail_image, duration, difficulty, category, target_roles, tags, sort_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'sssssssssssii', $title, $description, $video_type, $video_url, $video_file, $thumbnail_image, $duration, $difficulty, $category, $target_roles, $tags, $sort_order, $currentUser['user_id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Tutorial added successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error adding tutorial: " . mysqli_error($conn);
                    $messageType = "danger";
                }
                break;
                
            case 'update_tutorial':
                $tutorial_id = intval($_POST['tutorial_id']);
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $video_type = $_POST['video_type'];
                $video_url = trim($_POST['video_url']);
                $duration = trim($_POST['duration']);
                $difficulty = $_POST['difficulty'];
                $category = trim($_POST['category']);
                $target_roles = json_encode($_POST['target_roles'] ?? []);
                $tags = json_encode(array_map('trim', explode(',', $_POST['tags'])));
                $sort_order = intval($_POST['sort_order']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $sql = "UPDATE video_tutorials SET title=?, description=?, video_type=?, video_url=?, duration=?, difficulty=?, category=?, target_roles=?, tags=?, sort_order=?, is_active=? WHERE tutorial_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'sssssssssiis', $title, $description, $video_type, $video_url, $duration, $difficulty, $category, $target_roles, $tags, $sort_order, $is_active, $tutorial_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Tutorial updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error updating tutorial: " . mysqli_error($conn);
                    $messageType = "danger";
                }
                break;
                
            case 'delete_tutorial':
                $tutorial_id = intval($_POST['tutorial_id']);
                $sql = "DELETE FROM video_tutorials WHERE tutorial_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'i', $tutorial_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Tutorial deleted successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error deleting tutorial: " . mysqli_error($conn);
                    $messageType = "danger";
                }
                break;
        }
    }
}

// Fetch all tutorials
$tutorials = [];
$sql = "SELECT * FROM video_tutorials ORDER BY sort_order ASC, created_at DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tutorials[] = $row;
    }
}

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Admin - Video Tutorials - " . $siteName;
$bodyClass = "page-admin-video-tutorials";

// Include header
require_once '../includes/header.php';

// Define page title, icon, and actions for the modern header
$pageTitle = "Video Tutorials Management";
$pageIcon = "fa-video";
$pageDescription = "Manage video tutorials for the VVUSRC system";
$actions = [
    [
        'url' => 'video-tutorials.php',
        'icon' => 'fa-eye',
        'text' => 'View Tutorials',
        'class' => 'btn-secondary'
    ],
    [
        'url' => 'index.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Support',
        'class' => 'btn-secondary'
    ]
];

// Include the modern page header
include_once '../includes/modern_page_header.php';
?>

<style>
.admin-container {
    padding: 2rem 0;
}

.tutorial-form {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
}

.tutorial-list {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 2rem;
}

.tutorial-item {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.tutorial-item:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.tutorial-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.meta-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.difficulty-beginner {
    background: #d4edda;
    color: #155724;
}

.difficulty-intermediate {
    background: #fff3cd;
    color: #856404;
}

.difficulty-advanced {
    background: #f8d7da;
    color: #721c24;
}

.type-upload {
    background: #d1ecf1;
    color: #0c5460;
}

.type-youtube {
    background: #f8d7da;
    color: #721c24;
}

.type-external {
    background: #e2e3e5;
    color: #383d41;
}

.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    background: #f8f9fa;
}

.form-section h5 {
    color: #495057;
    margin-bottom: 1rem;
    font-weight: 600;
}

.btn-admin {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 25px;
    padding: 0.75rem 2rem;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-admin:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

@media (max-width: 768px) {
    .tutorial-form, .tutorial-list {
        padding: 1.5rem;
    }
    
    .tutorial-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<!-- Main Content -->
<div class="container-fluid px-4" style="margin-top: 2rem;">
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Add New Tutorial Form -->
    <div class="tutorial-form">
        <h3 class="mb-4">
            <i class="fas fa-plus-circle me-2"></i>Add New Video Tutorial
        </h3>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_tutorial">
            
            <div class="form-section">
                <h5><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Tutorial Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category" placeholder="e.g., system-overview" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="duration" class="form-label">Duration</label>
                        <input type="text" class="form-control" id="duration" name="duration" placeholder="5:30" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="difficulty" class="form-label">Difficulty</label>
                        <select class="form-select" id="difficulty" name="difficulty" required>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h5><i class="fas fa-video me-2"></i>Video Content</h5>
                <div class="mb-3">
                    <label for="video_type" class="form-label">Video Type</label>
                    <select class="form-select" id="video_type" name="video_type" required onchange="toggleVideoFields()">
                        <option value="youtube">YouTube Link</option>
                        <option value="vimeo">Vimeo Link</option>
                        <option value="upload">Upload Video File</option>
                        <option value="external">External Link</option>
                    </select>
                </div>

                <div id="url_field" class="mb-3">
                    <label for="video_url" class="form-label">Video URL</label>
                    <input type="url" class="form-control" id="video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=...">
                    <div class="form-text">For YouTube, use the full watch URL or embed URL</div>
                </div>

                <div id="upload_field" class="mb-3" style="display: none;">
                    <label for="video_file" class="form-label">Upload Video File</label>
                    <input type="file" class="form-control" id="video_file" name="video_file" accept="video/*">
                    <div class="form-text">Supported formats: MP4, WebM, AVI (Max size: 100MB)</div>
                </div>

                <div class="mb-3">
                    <label for="thumbnail" class="form-label">Thumbnail Image (Optional)</label>
                    <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                    <div class="form-text">Upload a custom thumbnail image for the video</div>
                </div>
            </div>

            <div class="form-section">
                <h5><i class="fas fa-users me-2"></i>Access & Targeting</h5>
                <div class="mb-3">
                    <label class="form-label">Target Roles</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="target_roles[]" value="student" id="role_student">
                        <label class="form-check-label" for="role_student">Students</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="target_roles[]" value="member" id="role_member">
                        <label class="form-check-label" for="role_member">Members</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="target_roles[]" value="admin" id="role_admin">
                        <label class="form-check-label" for="role_admin">Admins</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="target_roles[]" value="super_admin" id="role_super_admin">
                        <label class="form-check-label" for="role_super_admin">Super Admins</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="target_roles[]" value="finance" id="role_finance">
                        <label class="form-check-label" for="role_finance">Finance</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="tags" class="form-label">Tags</label>
                    <input type="text" class="form-control" id="tags" name="tags" placeholder="dashboard, navigation, setup">
                    <div class="form-text">Separate tags with commas</div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-admin">
                    <i class="fas fa-save me-2"></i>Add Tutorial
                </button>
            </div>
        </form>
    </div>

    <!-- Existing Tutorials List -->
    <div class="tutorial-list">
        <h3 class="mb-4">
            <i class="fas fa-list me-2"></i>Existing Video Tutorials
        </h3>

        <?php if (empty($tutorials)): ?>
        <div class="text-center py-5">
            <i class="fas fa-video fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No video tutorials found</h5>
            <p class="text-muted">Add your first tutorial using the form above.</p>
        </div>
        <?php else: ?>
        <?php foreach ($tutorials as $tutorial): ?>
        <div class="tutorial-item">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="mb-0"><?php echo htmlspecialchars($tutorial['title']); ?></h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="editTutorial(<?php echo $tutorial['tutorial_id']; ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteTutorial(<?php echo $tutorial['tutorial_id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            <p class="text-muted mb-2"><?php echo htmlspecialchars($tutorial['description']); ?></p>

            <div class="tutorial-meta">
                <span class="meta-badge difficulty-<?php echo $tutorial['difficulty']; ?>">
                    <?php echo ucfirst($tutorial['difficulty']); ?>
                </span>
                <span class="meta-badge type-<?php echo $tutorial['video_type']; ?>">
                    <?php echo ucfirst($tutorial['video_type']); ?>
                </span>
                <span class="meta-badge bg-light text-dark">
                    <i class="fas fa-clock me-1"></i><?php echo $tutorial['duration']; ?>
                </span>
                <span class="meta-badge bg-light text-dark">
                    Order: <?php echo $tutorial['sort_order']; ?>
                </span>
                <?php if (!$tutorial['is_active']): ?>
                <span class="meta-badge bg-warning text-dark">Inactive</span>
                <?php endif; ?>
            </div>

            <?php if ($tutorial['video_type'] !== 'upload'): ?>
            <div class="mb-2">
                <strong>URL:</strong>
                <a href="<?php echo htmlspecialchars($tutorial['video_url']); ?>" target="_blank" class="text-decoration-none">
                    <?php echo htmlspecialchars($tutorial['video_url']); ?>
                </a>
            </div>
            <?php endif; ?>

            <?php
            $roles = json_decode($tutorial['target_roles'], true) ?: [];
            $tags = json_decode($tutorial['tags'], true) ?: [];
            ?>

            <div class="mb-2">
                <strong>Target Roles:</strong>
                <?php foreach ($roles as $role): ?>
                <span class="badge bg-secondary me-1"><?php echo ucfirst($role); ?></span>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($tags)): ?>
            <div>
                <strong>Tags:</strong>
                <?php foreach ($tags as $tag): ?>
                <span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars($tag); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleVideoFields() {
    const videoType = document.getElementById('video_type').value;
    const urlField = document.getElementById('url_field');
    const uploadField = document.getElementById('upload_field');
    const videoUrlInput = document.getElementById('video_url');
    const videoFileInput = document.getElementById('video_file');

    if (videoType === 'upload') {
        urlField.style.display = 'none';
        uploadField.style.display = 'block';
        videoUrlInput.required = false;
        videoFileInput.required = true;
    } else {
        urlField.style.display = 'block';
        uploadField.style.display = 'none';
        videoUrlInput.required = true;
        videoFileInput.required = false;
    }
}

function editTutorial(tutorialId) {
    // This would open an edit modal or redirect to edit page
    alert('Edit functionality would be implemented here for tutorial ID: ' + tutorialId);
}

function deleteTutorial(tutorialId) {
    if (confirm('Are you sure you want to delete this tutorial? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_tutorial">
            <input type="hidden" name="tutorial_id" value="${tutorialId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize form on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleVideoFields();
});
</script>

<?php require_once '../includes/footer.php'; ?>
