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
require_once __DIR__ . '/../includes/activity_functions.php'; // Include activity functions

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if gallery feature is enabled
if (!hasFeaturePermission('enable_gallery')) {
    $_SESSION['error'] = "Gallery feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Get site name from settings
$siteName = getSetting('site_name', 'SRC Management System');

// Get current user info
$currentUser = getCurrentUser();
$hasAdminInterface = shouldUseAdminInterface(); // Use unified admin interface check for super admin users
$isMember = isMember();
$canManageGallery = $hasAdminInterface || $isMember; // Allow super admin, admin, and members to manage gallery

// Process file upload if form submitted
$uploadMsg = '';
$uploadStatus = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload']) && $canManageGallery) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Handle single file upload
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

                    // Send notification to all users about new gallery upload
                    $gallery_id = mysqli_insert_id($conn);
                    autoNotifyGalleryUploaded($title, 1, $currentUser['user_id'], $gallery_id);
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

// Process bulk file upload if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_upload']) && $canManageGallery) {
    $bulkTitle = mysqli_real_escape_string($conn, $_POST['bulk_title']);
    $bulkDescription = mysqli_real_escape_string($conn, $_POST['bulk_description']);

    // Handle multiple file upload
    if (isset($_FILES['bulk_gallery_files']) && !empty($_FILES['bulk_gallery_files']['name'][0])) {
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
        $allowedTypes = array_merge($allowedImageTypes, $allowedVideoTypes);

        $uploadDir = '../uploads/gallery/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $uploadedCount = 0;
        $errorCount = 0;
        $errors = [];

        // Process each file
        for ($i = 0; $i < count($_FILES['bulk_gallery_files']['name']); $i++) {
            if ($_FILES['bulk_gallery_files']['error'][$i] == 0) {
                $fileType = $_FILES['bulk_gallery_files']['type'][$i];
                $fileSize = $_FILES['bulk_gallery_files']['size'][$i];
                $fileName = $_FILES['bulk_gallery_files']['name'][$i];

                // Check if file type is allowed
                if (in_array($fileType, $allowedTypes)) {
                    // Determine if this is an image or video
                    $mediaType = in_array($fileType, $allowedImageTypes) ? 'image' : 'video';

                    // Generate unique filename
                    $newFileName = time() . '_' . $i . '_' . strtolower(str_replace(' ', '_', $fileName));
                    $destination = $uploadDir . $newFileName;

                    // Move uploaded file
                    if (move_uploaded_file($_FILES['bulk_gallery_files']['tmp_name'][$i], $destination)) {
                        // Create individual title for each file
                        $individualTitle = !empty($bulkTitle) ? $bulkTitle . ' - ' . pathinfo($fileName, PATHINFO_FILENAME) : pathinfo($fileName, PATHINFO_FILENAME);

                        // Insert into database
                        $insertSql = "INSERT INTO gallery (title, description, file_name, file_type, file_size, uploaded_by)
                                      VALUES (?, ?, ?, ?, ?, ?)";

                        $stmt = mysqli_prepare($conn, $insertSql);
                        mysqli_stmt_bind_param($stmt, 'ssssii', $individualTitle, $bulkDescription, $newFileName, $mediaType, $fileSize, $currentUser['user_id']);

                        if (mysqli_stmt_execute($stmt)) {
                            $uploadedCount++;
                        } else {
                            $errorCount++;
                            $errors[] = "Database error for " . $fileName . ": " . mysqli_error($conn);
                        }

                        mysqli_stmt_close($stmt);
                    } else {
                        $errorCount++;
                        $errors[] = "Failed to upload " . $fileName;
                    }
                } else {
                    $errorCount++;
                    $errors[] = "Invalid file type for " . $fileName;
                }
            } else {
                $errorCount++;
                $errors[] = "Upload error for " . $_FILES['bulk_gallery_files']['name'][$i];
            }
        }

        // Set upload message based on results
        if ($uploadedCount > 0 && $errorCount == 0) {
            $uploadMsg = "Successfully uploaded " . $uploadedCount . " files!";
            $uploadStatus = "success";

            // Track activity
            if (function_exists('trackActivity')) {
                trackActivity("create", "Bulk uploaded " . $uploadedCount . " files to gallery");
            }
        } elseif ($uploadedCount > 0 && $errorCount > 0) {
            $uploadMsg = "Uploaded " . $uploadedCount . " files successfully, but " . $errorCount . " files failed. Errors: " . implode(', ', array_slice($errors, 0, 3));
            if (count($errors) > 3) $uploadMsg .= " and " . (count($errors) - 3) . " more...";
            $uploadStatus = "warning";

            // Track activity
            if (function_exists('trackActivity')) {
                trackActivity("create", "Bulk uploaded " . $uploadedCount . " files to gallery with " . $errorCount . " errors");
            }
        } else {
            $uploadMsg = "Failed to upload any files. Errors: " . implode(', ', array_slice($errors, 0, 3));
            if (count($errors) > 3) $uploadMsg .= " and " . (count($errors) - 3) . " more...";
            $uploadStatus = "danger";
        }
    } else {
        $uploadMsg = "Please select files to upload";
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

// Process bulk delete if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete']) && $canManageGallery) {
    // Check if user has permission to delete gallery items
    if (hasPermission('delete', 'gallery')) {
        $selectedIds = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];
        
        if (!empty($selectedIds) && is_array($selectedIds)) {
            $deletedCount = 0;
            $errorCount = 0;
            $deletedTitles = [];
            
            foreach ($selectedIds as $galleryId) {
                $galleryId = (int)$galleryId;
                
                // Get file information
                $getFileSql = "SELECT file_name, title FROM gallery WHERE gallery_id = ?";
                $stmt = mysqli_prepare($conn, $getFileSql);
                mysqli_stmt_bind_param($stmt, 'i', $galleryId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $fileName, $title);
                
                if (mysqli_stmt_fetch($stmt)) {
                    mysqli_stmt_close($stmt);
                    
                    // Delete from database
                    $deleteSql = "DELETE FROM gallery WHERE gallery_id = ?";
                    $deleteStmt = mysqli_prepare($conn, $deleteSql);
                    mysqli_stmt_bind_param($deleteStmt, 'i', $galleryId);
                    
                    if (mysqli_stmt_execute($deleteStmt)) {
                        // Delete the physical file
                        $filePath = '../uploads/gallery/' . $fileName;
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        
                        $deletedCount++;
                        $deletedTitles[] = $title;
                    } else {
                        $errorCount++;
                    }
                    
                    mysqli_stmt_close($deleteStmt);
                } else {
                    mysqli_stmt_close($stmt);
                    $errorCount++;
                }
            }
            
            // Set appropriate message based on results
            if ($deletedCount > 0 && $errorCount == 0) {
                $uploadMsg = "Successfully deleted " . $deletedCount . " item(s) from gallery.";
                $uploadStatus = "success";
                
                // Track activity
                if (function_exists('trackActivity')) {
                    trackActivity("delete", "Bulk deleted " . $deletedCount . " gallery items: " . implode(', ', array_slice($deletedTitles, 0, 3)) . ($deletedCount > 3 ? '...' : ''));
                }
            } elseif ($deletedCount > 0 && $errorCount > 0) {
                $uploadMsg = "Deleted " . $deletedCount . " item(s), but " . $errorCount . " item(s) failed to delete.";
                $uploadStatus = "warning";
                
                // Track activity
                if (function_exists('trackActivity')) {
                    trackActivity("delete", "Bulk deleted " . $deletedCount . " gallery items with " . $errorCount . " errors");
                }
            } else {
                $uploadMsg = "Failed to delete selected items. Please try again.";
                $uploadStatus = "danger";
            }
        } else {
            $uploadMsg = "No items selected for deletion.";
            $uploadStatus = "warning";
        }
    } else {
        $uploadMsg = "You do not have permission to delete gallery items.";
        $uploadStatus = "danger";
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
$bodyClass = "page-gallery";

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

<script>
    document.body.classList.add('gallery-page');
</script>

<!-- Custom Gallery Header -->
<div class="gallery-header animate__animated animate__fadeInDown">
    <div class="gallery-header-content">
        <div class="gallery-header-main">
            <h1 class="gallery-title">
                <i class="fas fa-images me-3"></i>
                Gallery
            </h1>
            <p class="gallery-description">SRC photo gallery and media</p>
        </div>
        <?php if ($canManageGallery): ?>
        <div class="gallery-header-actions">
            <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="fas fa-upload me-2"></i>Upload Photos
            </button>
            <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                <i class="fas fa-cloud-upload-alt me-2"></i>Bulk Upload
            </button>
            <button type="button" id="bulkDeleteBtn" class="btn btn-header-action btn-danger" style="display: none;" onclick="confirmBulkDelete()">
                <i class="fas fa-trash-alt me-2"></i>Delete Selected (<span id="selectedCount">0</span>)
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.gallery-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.gallery-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.gallery-header-main {
    flex: 1;
    text-align: center;
}

.gallery-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.gallery-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.gallery-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.gallery-header-actions {
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
    .gallery-header {
        padding: 2rem 1.5rem;
    }

    .gallery-header-content {
        flex-direction: column;
        align-items: center;
    }

    .gallery-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .gallery-title i {
        font-size: 1.8rem;
    }

    .gallery-description {
        font-size: 1.1rem;
    }

    .gallery-header-actions {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-header-action {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    /* Bulk delete button mobile adjustments */
    #bulkDeleteBtn {
        width: 100%;
        margin-top: 0.5rem;
        font-size: 0.95rem !important;
    }
    
    /* Mobile gallery button fixes */
    .gallery-card-actions {
        flex-direction: column !important;
        gap: 6px !important;
    }
    
    .gallery-card-actions .btn {
        width: 100% !important;
        min-width: unset !important;
        justify-content: center !important;
        padding: 0.5rem 1rem !important;
        font-size: 0.9rem !important;
    }
    
    .gallery-card-actions .btn-sm {
        padding: 0.4rem 0.8rem !important;
        font-size: 0.85rem !important;
        min-width: unset !important;
    }
}

/* Fix for very small screens */
@media (max-width: 480px) {
    .gallery-card-actions .btn {
        font-size: 0.8rem !important;
        padding: 0.4rem 0.6rem !important;
    }
    
    .gallery-card-actions .btn i {
        margin-right: 0.2rem !important;
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
    <?php if ($canManageGallery): ?>
    <!-- Bulk Selection Controls -->
    <div class="bulk-controls mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleSelectAll()">
            <label class="form-check-label fw-bold" for="selectAll">
                Select All (<span id="totalItems"><?php echo count($galleryItems); ?></span> items)
            </label>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Bulk Delete Form -->
    <form id="bulkDeleteForm" method="POST" action="gallery.php">
        <input type="hidden" name="bulk_delete" value="1">
    <div class="row gallery-grid">
        <?php foreach ($galleryItems as $item): ?>
        <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="gallery-card">
                <?php if ($canManageGallery): ?>
                <!-- Selection Checkbox -->
                <div class="gallery-card-checkbox">
                    <input type="checkbox" class="form-check-input gallery-item-checkbox" 
                           name="selected_items[]" value="<?php echo $item['id']; ?>" 
                           onchange="updateBulkDeleteButton()">
                </div>
                <?php endif; ?>
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
        <div class="modal fade" id="mediaModal<?php echo $item['id']; ?>" tabindex="-1" aria-labelledby="mediaModalLabel<?php echo $item['id']; ?>" aria-hidden="true" data-bs-backdrop="false">
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
    </form>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<?php if ($canManageGallery): ?>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="gallery.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Single Media</h5>
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

<!-- Bulk Upload Modal -->
<div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-labelledby="bulkUploadModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="gallery.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkUploadModalLabel">Bulk Upload Media</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Bulk Upload:</strong> Select multiple files to upload at once. Each file will be given an individual title based on the base title and filename.
                    </div>
                    <div class="mb-3">
                        <label for="bulk_title" class="form-label">Base Title (Optional)</label>
                        <input type="text" class="form-control" id="bulk_title" name="bulk_title" placeholder="e.g., School Event 2025">
                        <div class="form-text">If provided, each file will be titled as "Base Title - Filename"</div>
                    </div>
                    <div class="mb-3">
                        <label for="bulk_description" class="form-label">Description (Applied to all files)</label>
                        <textarea class="form-control" id="bulk_description" name="bulk_description" rows="3" placeholder="Description that will be applied to all uploaded files"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="bulk_gallery_files" class="form-label">Select Multiple Files</label>
                        <input class="form-control" type="file" id="bulk_gallery_files" name="bulk_gallery_files[]" accept="image/*,video/*" multiple required>
                        <div class="form-text">
                            <strong>Allowed file types:</strong> JPG, PNG, GIF, WEBP, MP4, WEBM, OGG<br>
                            <strong>Tip:</strong> Hold Ctrl (Windows) or Cmd (Mac) to select multiple files
                        </div>
                    </div>
                    <div id="file-preview" class="mt-3" style="display: none;">
                        <h6>Selected Files:</h6>
                        <div id="file-list" class="list-group"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="bulk_upload" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i>Upload All Files
                    </button>
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
    position: relative;
}

/* Gallery card checkbox styling */
.gallery-card-checkbox {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 10;
    background: rgba(255, 255, 255, 0.95);
    padding: 8px;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.gallery-card-checkbox .form-check-input {
    width: 20px;
    height: 20px;
    cursor: pointer;
    border: 2px solid #667eea;
}

.gallery-card-checkbox .form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

/* Bulk selection controls */
.bulk-controls {
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    border-left: 4px solid #667eea;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.bulk-controls .form-check-input {
    width: 18px;
    height: 18px;
    cursor: pointer;
    border: 2px solid #667eea;
}

.bulk-controls .form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.bulk-controls .form-check-label {
    font-size: 1.1rem;
    color: #495057;
    cursor: pointer;
    user-select: none;
}

/* Bulk delete button styling */
#bulkDeleteBtn {
    background: rgba(220, 53, 69, 0.9) !important;
    border-color: rgba(220, 53, 69, 1) !important;
    animation: pulse 2s ease-in-out infinite;
}

#bulkDeleteBtn:hover {
    background: rgba(200, 35, 51, 1) !important;
    border-color: rgba(200, 35, 51, 1) !important;
    transform: translateY(-2px) !important;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
    50% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
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
    flex-wrap: wrap;
    margin-top: 10px;
}

/* Fix button sizing to show full text */
.gallery-card-actions .btn {
    white-space: nowrap !important;
    overflow: visible !important;
    text-overflow: clip !important;
    min-width: auto !important;
    padding: 0.375rem 0.75rem !important;
    font-size: 0.875rem !important;
    line-height: 1.5 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

/* Specific fix for small buttons */
.gallery-card-actions .btn-sm {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.8rem !important;
    line-height: 1.4 !important;
    min-width: 70px !important;
}

/* Ensure download button shows full text */
.gallery-card-actions .btn-success,
.gallery-card-actions a[download] {
    min-width: 85px !important;
    text-align: center !important;
}

/* Fix for icon spacing in buttons */
.gallery-card-actions .btn i {
    margin-right: 0.25rem !important;
    flex-shrink: 0 !important;
}

.modal-media-content {
    max-height: 70vh;
    max-width: 100%;
}

/* Bulk upload styles */
.file-preview-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 5px;
    background-color: #f8f9fa;
}

.file-preview-item i {
    margin-right: 8px;
    color: #6c757d;
}

.file-preview-item .file-name {
    flex: 1;
    font-size: 14px;
}

.file-preview-item .file-size {
    font-size: 12px;
    color: #6c757d;
    margin-left: 10px;
}
</style>

<script>
// Bulk upload file preview functionality
document.addEventListener('DOMContentLoaded', function() {
    const bulkFileInput = document.getElementById('bulk_gallery_files');
    const filePreview = document.getElementById('file-preview');
    const fileList = document.getElementById('file-list');

    if (bulkFileInput) {
        bulkFileInput.addEventListener('change', function() {
            const files = this.files;

            if (files.length > 0) {
                filePreview.style.display = 'block';
                fileList.innerHTML = '';

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';

                    // Determine file icon
                    let icon = 'fas fa-file';
                    if (file.type.startsWith('image/')) {
                        icon = 'fas fa-image';
                    } else if (file.type.startsWith('video/')) {
                        icon = 'fas fa-video';
                    }

                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-preview-item';
                    fileItem.innerHTML = `
                        <i class="${icon}"></i>
                        <span class="file-name">${file.name}</span>
                        <span class="file-size">${fileSize}</span>
                    `;

                    fileList.appendChild(fileItem);
                }
            } else {
                filePreview.style.display = 'none';
            }
        });
    }

    // Reset form when modal is closed
    const bulkUploadModal = document.getElementById('bulkUploadModal');
    if (bulkUploadModal) {
        bulkUploadModal.addEventListener('hidden.bs.modal', function() {
            const form = this.querySelector('form');
            if (form) {
                form.reset();
                filePreview.style.display = 'none';
                fileList.innerHTML = '';
            }
        });
    }

    // Reset single upload form when modal is closed
    const uploadModal = document.getElementById('uploadModal');
    if (uploadModal) {
        uploadModal.addEventListener('hidden.bs.modal', function() {
            const form = this.querySelector('form');
            if (form) {
                form.reset();
            }
        });
    }
});

// Bulk delete functionality
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.gallery-item-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkDeleteButton();
}

function updateBulkDeleteButton() {
    const checkboxes = document.querySelectorAll('.gallery-item-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCount = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAll');
    const totalCheckboxes = document.querySelectorAll('.gallery-item-checkbox');
    
    // Update selected count
    if (selectedCount) {
        selectedCount.textContent = checkboxes.length;
    }
    
    // Show/hide bulk delete button
    if (bulkDeleteBtn) {
        if (checkboxes.length > 0) {
            bulkDeleteBtn.style.display = 'inline-block';
        } else {
            bulkDeleteBtn.style.display = 'none';
        }
    }
    
    // Update select all checkbox state
    if (selectAllCheckbox && totalCheckboxes.length > 0) {
        selectAllCheckbox.checked = checkboxes.length === totalCheckboxes.length;
        selectAllCheckbox.indeterminate = checkboxes.length > 0 && checkboxes.length < totalCheckboxes.length;
    }
}

function confirmBulkDelete() {
    const checkboxes = document.querySelectorAll('.gallery-item-checkbox:checked');
    const count = checkboxes.length;
    
    if (count === 0) {
        alert('Please select at least one item to delete.');
        return;
    }
    
    const confirmMessage = `Are you sure you want to delete ${count} selected item${count > 1 ? 's' : ''}? This action cannot be undone.`;
    
    if (confirm(confirmMessage)) {
        document.getElementById('bulkDeleteForm').submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
