<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if documents feature is enabled
if (!hasFeaturePermission('enable_documents')) {
    $_SESSION['error'] = "The document repository feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = shouldUseAdminInterface();
$isMember = isMember();
$shouldUseAdminInterface = shouldUseAdminInterface();
$canManageDocuments = $shouldUseAdminInterface || $isMember; // Allow admin interface users and members to manage documents

// Check if user is trying to access upload document form and doesn't have admin interface access
if (isset($_GET['action']) && $_GET['action'] === 'new' && !$shouldUseAdminInterface) {
    // Redirect non-admin interface users back to the documents page
    header("Location: documents.php");
    exit();
}

// Set page title and body class
$pageTitle = "Documents - SRC Management System";
$bodyClass = "page-documents"; // Add body class for CSS targeting

// Build query for documents
$sql = "SELECT d.*, u.first_name, u.last_name 
        FROM documents d 
        LEFT JOIN users u ON d.uploaded_by = u.user_id 
        WHERE d.status = 'active'";
$params = [];

// Apply search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $sql .= " AND (d.title LIKE ? OR d.description LIKE ?)";
    $params[] = $search;
    $params[] = $search;
}

// Apply category filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $sql .= " AND d.category = ?";
    $params[] = $_GET['category'];
}

// Apply file type filter
if (isset($_GET['file_type']) && !empty($_GET['file_type'])) {
    $sql .= " AND d.document_type = ?";
    $params[] = strtolower($_GET['file_type']);
}

// Order by created_at desc (newest first)
$sql .= " ORDER BY d.created_at DESC";

// Fetch documents
$documents = fetchAll($sql, $params);

// Get unique categories for dropdown
$categoriesSql = "SELECT DISTINCT category FROM documents WHERE status = 'active' ORDER BY category";
$categoriesResult = fetchAll($categoriesSql);
$categories = array_column($categoriesResult, 'category');

// Get unique file types for dropdown
$fileTypesSql = "SELECT DISTINCT document_type FROM documents WHERE status = 'active' ORDER BY document_type";
$fileTypesResult = fetchAll($fileTypesSql);
$fileTypes = array_column($fileTypesResult, 'document_type');

/**
 * Format file size for display
 *
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, 2) . ' ' . $units[$pow];
}

// Include header
require_once 'includes/header.php';
?>

<link rel="stylesheet" href="../css/documents-responsive.css">

<style>
/* Mobile Full-Width Optimization for Documents Page */
@media (max-width: 991px) {
    [class*="col-md-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Ensure documents header has border-radius on mobile */
    .documents-header {
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .card, .content-card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<script>
    document.body.classList.add('documents-page');
</script>

<!-- Custom Documents Header -->
<div class="documents-header animate__animated animate__fadeInDown">
    <div class="documents-header-content">
        <div class="documents-header-main">
            <h1 class="documents-title">
                <i class="fas fa-file-alt me-3"></i>
                Documents Management
            </h1>
            <p class="documents-description">Access and manage official SRC documents and resources</p>
        </div>
        <?php if ($shouldUseAdminInterface || $isMember): ?>
        <div class="documents-header-actions">
            <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                <i class="fas fa-upload me-2"></i>Upload Document
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Display success/error messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success']; 
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error']) && $_SESSION['error'] !== "The document repository feature is currently disabled."): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error']; 
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php else: ?>
<?php endif; ?>

<!-- Main Documents Content Wrapper -->
<div class="documents-content">
    <!-- Display success/error messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && $_SESSION['error'] !== "The document repository feature is currently disabled."): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php else: ?>
        <?php unset($_SESSION['error']); ?>

    <h3 class="documents-section-title">All Documents</h3>

    <!-- Documents Table -->
    <div class="content-card">
        <div class="content-card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>TITLE</th>
                            <th>CATEGORY</th>
                            <th>UPLOADED BY</th>
                            <th>DATE</th>
                            <th>FILE TYPE</th>
                            <th>SIZE</th>
                            <th>ACTIONS</th>
                    </tr>
                </thead>
                    <tbody>
                        <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="no-documents-placeholder">
                                        <i class="fas fa-file-alt"></i>
                                        <p>No documents found. Check back soon for updates!</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                        <?php foreach ($documents as $document): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($document['title']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($document['category'])); ?></td>
                                <td>
                                    <?php 
                                    if ($document['first_name'] && $document['last_name']) {
                                        echo htmlspecialchars($document['first_name'] . ' ' . $document['last_name']);
                                    } else {
                                        echo 'Unknown';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($document['created_at'])); ?></td>
                                <td><?php echo strtoupper(htmlspecialchars($document['document_type'])); ?></td>
                                <td><?php echo formatFileSize($document['file_size']); ?></td>
                                <td style="white-space: nowrap;">
                                    <a href="document_handler.php?action=download&id=<?php echo $document['document_id']; ?>" class="btn btn-sm btn-primary" title="Download">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <?php if (shouldUseAdminInterface() || ($document['uploaded_by'] == getCurrentUser()['user_id'])): ?>
                                        <a href="document_handler.php?action=delete&id=<?php echo $document['document_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this document?');" title="Delete">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div><!-- Close documents-content -->

<!-- Upload Document Modal -->
<?php if (hasPermission('create', 'documents')): ?>
    <div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-labelledby="uploadDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadDocumentModalLabel">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="document_handler.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        <div class="mb-3">
                            <label for="document_title" class="form-label">Document Title</label>
                            <input type="text" class="form-control" id="document_title" name="document_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="document_category" class="form-label">Category</label>
                            <select class="form-select" id="document_category" name="document_category" required>
                                <option value="">Select Category</option>
                                <option value="legal">Legal</option>
                                <option value="general">General</option>
                                <option value="financial">Financial</option>
                                <option value="elections">Elections</option>
                                <option value="events">Events</option>
                                <option value="reports">Reports</option>
                                <option value="minutes">Minutes</option>
                                <option value="bylaws">Bylaws</option>
                                <option value="legislation">Legislation</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="document_file" class="form-label">File</label>
                            <input type="file" class="form-control" id="document_file" name="document_file" required>
                            <div class="form-text">Accepted file types: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT (Max size: 5MB)</div>
                        </div>
                        <div class="mb-3">
                            <label for="document_description" class="form-label">Description</label>
                            <textarea class="form-control" id="document_description" name="document_description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

</div> <!-- Close main content container -->

<?php require_once 'includes/footer.php'; ?>
