<?php
// Include authentication file
require_once '../auth_functions.php';
require_once '../settings_functions.php';

// Force enable documents feature in session
if (!isset($_SESSION['features'])) {
    $_SESSION['features'] = [];
}
$_SESSION['features']['enable_documents'] = true;

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();
$canManageDocuments = $isAdmin || $isMember; // Allow both admins and members to manage documents

// Check if documents feature is enabled
if (!isFeatureEnabled('enable_documents', true)) {
    $_SESSION['error'] = "The document repository feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Check if user is trying to access upload document form and is not an admin
if (isset($_GET['action']) && $_GET['action'] === 'new' && !$isAdmin) {
    // Redirect non-admin users back to the documents page
    header("Location: documents.php");
    exit();
}

// Set page title
$pageTitle = "Documents - SRC Management System";

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

// Include header
require_once 'includes/header.php';
?>

<script>
    document.body.classList.add('documents-page');
</script>

<div class="header">
    <h1 class="page-title">Documents</h1>
    
    <div class="header-actions">
        <?php if (hasPermission('create', 'documents')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                    <i class="fas fa-file-upload me-2"></i> Upload Document
                </button>
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
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<h3 class="mb-3">All Documents</h3>

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
                            <td colspan="7" class="text-center">No documents found.</td>
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
                                <td>
                                    <a href="document_handler.php?action=download&id=<?php echo $document['document_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <?php if (isAdmin() || ($document['uploaded_by'] == getCurrentUser()['user_id'])): ?>
                                        <a href="document_handler.php?action=delete&id=<?php echo $document['document_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this document?');">
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
</div>

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

<?php
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
?>

<?php require_once 'includes/footer.php'; ?> 