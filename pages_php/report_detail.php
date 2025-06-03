<?php
// Include authentication file
require_once '../auth_functions.php';
require_once '../db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if report ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Report ID not provided.";
    header("Location: reports.php");
    exit();
}

$reportId = intval($_GET['id']);

// Get report information
$sql = "SELECT r.*, u.first_name, u.last_name 
        FROM reports r 
        LEFT JOIN users u ON r.uploaded_by = u.user_id 
        WHERE r.report_id = ?";
$report = fetchOne($sql, [$reportId]);

// Check if report exists
if (!$report) {
    $_SESSION['error'] = "Report not found.";
    header("Location: reports.php");
    exit();
}

// Set page title
$pageTitle = "Report Details: " . $report['title'];

// Decode categories JSON
$categories = json_decode($report['categories'], true) ?: [];

// Get uploader's name
$uploaderName = $report['first_name'] && $report['last_name'] 
                ? $report['first_name'] . ' ' . $report['last_name']
                : 'Unknown';

// Include header
require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mt-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($report['title']); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success']; 
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error']; 
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="report-detail-header">
                <h2 class="mb-0"><?php echo htmlspecialchars($report['title']); ?></h2>
                <?php if ($report['featured']): ?>
                <span class="badge bg-warning ms-2">
                    <i class="fas fa-star me-1"></i> Featured
                </span>
                <?php endif; ?>
            </div>
            <div>
                <?php if (hasPermission('update', 'reports')): ?>
                <a href="report_edit.php?id=<?php echo $report['report_id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i> Edit Report
                </a>
                <?php endif; ?>

                <?php if (hasPermission('delete', 'reports')): ?>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteReportModal">
                    <i class="fas fa-trash-alt me-2"></i> Delete
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-4">
                        <h3>Report Information</h3>
                        <table class="table">
                            <tr>
                                <th style="width: 150px;">Author:</th>
                                <td><?php echo htmlspecialchars($report['author']); ?></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td><?php echo date('F j, Y', strtotime($report['date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Type:</th>
                                <td><?php echo htmlspecialchars($report['type']); ?></td>
                            </tr>
                            <tr>
                                <th>Portfolio:</th>
                                <td><?php echo htmlspecialchars($report['portfolio']); ?></td>
                            </tr>
                            <tr>
                                <th>Categories:</th>
                                <td>
                                    <?php foreach ($categories as $category): ?>
                                    <span class="badge bg-info me-1"><?php echo htmlspecialchars($category); ?></span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Uploaded By:</th>
                                <td><?php echo htmlspecialchars($uploaderName); ?></td>
                            </tr>
                            <tr>
                                <th>Upload Date:</th>
                                <td><?php echo date('F j, Y, g:i a', strtotime($report['created_at'])); ?></td>
                            </tr>
                            <?php if ($report['updated_at'] !== $report['created_at']): ?>
                            <tr>
                                <th>Last Updated:</th>
                                <td><?php echo date('F j, Y, g:i a', strtotime($report['updated_at'])); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="mb-4">
                        <h3>Summary</h3>
                        <div class="report-summary">
                            <p><?php echo nl2br(htmlspecialchars($report['summary'])); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4 report-document-card">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-file-pdf document-icon"></i>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="<?php echo '../uploads/reports/' . $report['file_path']; ?>" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-eye me-2"></i> View Report
                                </a>
                                <a href="report_handler.php?action=download&id=<?php echo $report['report_id']; ?>" class="btn btn-success">
                                    <i class="fas fa-download me-2"></i> Download Report
                                </a>
                                <?php if (isAdmin() && $report['featured'] == 0): ?>
                                <a href="report_handler.php?action=toggle_featured&id=<?php echo $report['report_id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-star me-2"></i> Mark as Featured
                                </a>
                                <?php elseif (isAdmin() && $report['featured'] == 1): ?>
                                <a href="report_handler.php?action=toggle_featured&id=<?php echo $report['report_id']; ?>" class="btn btn-outline-warning">
                                    <i class="fas fa-star me-2"></i> Remove from Featured
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (hasPermission('delete', 'reports')): ?>
<!-- Delete Report Modal -->
<div class="modal fade" id="deleteReportModal" tabindex="-1" aria-labelledby="deleteReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteReportModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this report? This action cannot be undone.</p>
                <p class="fw-bold"><?php echo htmlspecialchars($report['title']); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="report_handler.php?action=delete&id=<?php echo $report['report_id']; ?>" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 