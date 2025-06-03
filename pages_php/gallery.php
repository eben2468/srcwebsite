<?php
// Include authentication file and database config
header('Content-Type: text/html; charset=utf-8');
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../auth_bridge.php'; // Add bridge for admin status consistency
require_once '../activity_functions.php'; // Include activity functions
require_once '../settings_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get site name from settings
$siteName = getSetting('site_name', 'SRC Management System');

// Get current user info
$currentUser = getCurrentUser();
$isAdmin = isAdmin() || getBridgedAdminStatus(); // Check both auth system and bridge
$isMember = isMember();
$canManageGallery = $isAdmin || $isMember; // Allow both admins and members to manage gallery

// Process file upload if form submitted
$uploadMsg = '';
$uploadStatus = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload']) && $canManageGallery) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Handle file upload
    if (isset($_FILES['gallery_file']) && $_FILES['gallery_file']['error'] == 0) {
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
        $allowedTypes = array_merge($allowedImageTypes, $allowedVideoTypes);
        
        $fileType = $_FILES['gallery_file']['type'];
        $fileSize = $_FILES['gallery_file']['size'];
        $fileName = $_FILES['gallery_file']['name'];
        
        // Check if file type is allowed
        if (in_array($fileType, $allowedTypes)) {
            // Determine if this is an image or video
            $mediaType = in_array($fileType, $allowedImageTypes) ? 'image' : 'video';
            
            // Create upload directory if it doesn't exist
            $uploadDir = '../uploads/gallery/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $newFileName = time() . '_' . strtolower(str_replace(' ', '_', $fileName));
            $destination = $uploadDir . $newFileName;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['gallery_file']['tmp_name'], $destination)) {
                // Insert into database
                $insertSql = "INSERT INTO gallery (title, description, file_name, file_type, file_size, uploaded_by) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($conn, $insertSql);
                mysqli_stmt_bind_param($stmt, 'ssssii', $title, $description, $newFileName, $mediaType, $fileSize, $currentUser['user_id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    $uploadMsg = "File uploaded successfully!";
                    $uploadStatus = "success";
                    
                    // Track activity
                    if (function_exists('trackActivity')) {
                        trackActivity("create", "Uploaded new " . $mediaType . " to gallery: " . $title);
                    }
                } else {
                    $uploadMsg = "Error saving to database: " . mysqli_error($conn);
                    $uploadStatus = "danger";
                }
                
                mysqli_stmt_close($stmt);
            } else {
                $uploadMsg = "Error uploading file";
                $uploadStatus = "danger";
            }
        } else {
            $uploadMsg = "Invalid file type. Allowed types: JPG, PNG, GIF, WEBP, MP4, WEBM, OGG";
            $uploadStatus = "danger";
        }
    } else {
        $uploadMsg = "Please select a file to upload";
        $uploadStatus = "warning";
    }
}

// Delete gallery item if requested
if (isset($_GET['delete']) && $canManageGallery) {
    $galleryId = (int)$_GET['delete'];
    
    // First get the file name to delete the file
    $getFileSql = "SELECT file_name, title FROM gallery WHERE gallery_id = ?";
    $stmt = mysqli_prepare($conn, $getFileSql);
    mysqli_stmt_bind_param($stmt, 'i', $galleryId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $fileName, $title);
    
    if (mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);
        
        // Delete from database
        $deleteSql = "DELETE FROM gallery WHERE gallery_id = ?";
        $stmt = mysqli_prepare($conn, $deleteSql);
        mysqli_stmt_bind_param($stmt, 'i', $galleryId);
        
        if (mysqli_stmt_execute($stmt)) {
            // Delete the file
            $filePath = '../uploads/gallery/' . $fileName;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $uploadMsg = "Gallery item deleted successfully";
            $uploadStatus = "success";
            
            // Track activity
            if (function_exists('trackActivity')) {
                trackActivity("delete", "Deleted gallery item: " . $title);
            }
        } else {
            $uploadMsg = "Error deleting gallery item: " . mysqli_error($conn);
            $uploadStatus = "danger";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        mysqli_stmt_close($stmt);
        $uploadMsg = "Gallery item not found";
        $uploadStatus = "warning";
    }
}

// Fetch gallery items for display
$galleryItems = [];
$sql = "SELECT g.*, u.first_name, u.last_name, u.username
        FROM gallery g
        LEFT JOIN users u ON g.uploaded_by = u.user_id
        WHERE g.status = 'active'
        ORDER BY g.upload_date DESC";

$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $uploader = trim($row['first_name'] . ' ' . $row['last_name']);
        $uploader = !empty($uploader) ? $uploader : $row['username'];
        
        $galleryItems[] = [
            'id' => $row['gallery_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'file_name' => $row['file_name'],
            'file_type' => $row['file_type'],
            'upload_date' => date('F j, Y', strtotime($row['upload_date'])),
            'uploader' => $uploader
        ];
    }
    mysqli_free_result($result);
}

// Set page title
$pageTitle = "Gallery - " . $siteName;

// Check if we need to show the upload modal
$showUploadModal = false;
if (isset($_GET['action']) && $_GET['action'] === 'upload') {
    // Allow admins and members to upload
    if ($canManageGallery) {
        $showUploadModal = true;
    } else {
        // Redirect non-privileged users back to the gallery page
        header("Location: gallery.php");
        exit();
    }
}

// Include header
require_once 'includes/header.php';

// Track page view
if (function_exists('trackPageView')) {
    trackPageView($pageTitle);
}
?>

<div class="header">
    <h1 class="page-title">School Activities Gallery</h1>
    
    <div class="header-actions">
        <?php if ($canManageGallery): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-2"></i>Upload Media
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Alert message if upload succeeded or failed -->
<?php if (!empty($uploadMsg)): ?>
<div class="alert alert-<?php echo $uploadStatus; ?> alert-dismissible fade show" role="alert">
    <?php echo $uploadMsg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Show upload modal automatically if requested -->
<?php if ($showUploadModal): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));
    uploadModal.show();
});
</script>
<?php endif; ?>

<!-- Gallery Items -->
<div class="gallery-container">
    <?php if (empty($galleryItems)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>No gallery items found. 
        <?php if ($canManageGallery): ?>
        Please upload some images or videos.
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="row gallery-grid">
        <?php foreach ($galleryItems as $item): ?>
        <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="gallery-card">
                <div class="gallery-card-media">
                    <?php if ($item['file_type'] === 'image'): ?>
                    <img src="../uploads/gallery/<?php echo $item['file_name']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" 
                         class="gallery-image" data-bs-toggle="modal" data-bs-target="#mediaModal<?php echo $item['id']; ?>">
                    <?php else: ?>
                    <video class="gallery-video" controls data-bs-toggle="modal" data-bs-target="#mediaModal<?php echo $item['id']; ?>">
                        <source src="../uploads/gallery/<?php echo $item['file_name']; ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <?php endif; ?>
                </div>
                <div class="gallery-card-body">
                    <h3 class="gallery-card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                    <p class="gallery-card-text"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                    <div class="gallery-card-meta">
                        <div><i class="fas fa-calendar-alt me-1"></i> <?php echo $item['upload_date']; ?></div>
                        <div><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($item['uploader']); ?></div>
                    </div>
                    <div class="gallery-card-actions">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mediaModal<?php echo $item['id']; ?>">
                            <i class="fas fa-eye me-1"></i>View
                        </button>
                        <a href="../uploads/gallery/<?php echo $item['file_name']; ?>" class="btn btn-success btn-sm" download="<?php echo htmlspecialchars($item['title']); ?>">
                            <i class="fas fa-download me-1"></i>Download
                        </a>
                        <?php if ($canManageGallery): ?>
                        <a href="gallery.php?delete=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm" 
                           onclick="return confirm('Are you sure you want to delete this item?');">
                            <i class="fas fa-trash-alt me-1"></i>Delete
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal for fullscreen viewing -->
        <div class="modal fade" id="mediaModal<?php echo $item['id']; ?>" tabindex="-1" aria-labelledby="mediaModalLabel<?php echo $item['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="mediaModalLabel<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['title']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <?php if ($item['file_type'] === 'image'): ?>
                        <img src="../uploads/gallery/<?php echo $item['file_name']; ?>" class="img-fluid modal-media-content" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <?php else: ?>
                        <video class="modal-media-content" controls>
                            <source src="../uploads/gallery/<?php echo $item['file_name']; ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <?php endif; ?>
                        <div class="mt-3">
                            <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                            <small>Uploaded on <?php echo $item['upload_date']; ?> by <?php echo htmlspecialchars($item['uploader']); ?></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="../uploads/gallery/<?php echo $item['file_name']; ?>" class="btn btn-success" download="<?php echo htmlspecialchars($item['title']); ?>">
                            <i class="fas fa-download me-1"></i>Download
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<?php if ($canManageGallery): ?>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="gallery.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Media</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="gallery_file" class="form-label">Select File</label>
                        <input class="form-control" type="file" id="gallery_file" name="gallery_file" accept="image/*,video/*" required>
                        <div class="form-text">Allowed file types: JPG, PNG, GIF, WEBP, MP4, WEBM, OGG</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Gallery specific styles */
.gallery-container {
    margin-top: 20px;
}

.gallery-grid {
    display: flex;
    flex-wrap: wrap;
}

.gallery-card {
    height: 100%;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
}

.gallery-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
}

.gallery-card-media {
    position: relative;
    overflow: hidden;
    height: 200px;
}

.gallery-image, .gallery-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.gallery-card-media:hover .gallery-image,
.gallery-card-media:hover .gallery-video {
    transform: scale(1.05);
}

.gallery-card-body {
    padding: 15px;
}

.gallery-card-title {
    font-size: 18px;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--text-color);
}

.gallery-card-text {
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 10px;
    max-height: 60px;
    overflow: hidden;
}

.gallery-card-meta {
    display: flex;
    justify-content: space-between;
    color: var(--text-muted);
    font-size: 12px;
    margin-bottom: 10px;
}

.gallery-card-actions {
    display: flex;
    justify-content: flex-start;
    gap: 8px;
}

.modal-media-content {
    max-height: 70vh;
    max-width: 100%;
}
</style>

<?php require_once 'includes/footer.php'; ?> 