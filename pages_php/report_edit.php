<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/functions.php'; // Include the functions file directly

// Require login for this page
requireLogin();

// Check if user is logged in and has permission
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check permission
if (!hasPermission('update', 'reports')) {
    $_SESSION['error'] = "You don't have permission to edit reports.";
    header("Location: reports.php");
    exit();
}

// Determine if this is create or edit mode
$isCreateMode = isset($_GET['action']) && $_GET['action'] === 'new';
$isEditMode = isset($_GET['id']) && !empty($_GET['id']);

// If neither create nor edit mode is specified, redirect back
if (!$isCreateMode && !$isEditMode) {
    $_SESSION['error'] = "Invalid request. Please specify a report to edit or create a new one.";
    header("Location: reports.php");
    exit();
}

// Initialize variables
$report = null;
$reportId = 0;
$categoriesString = '';

// If in edit mode, fetch the report details
if ($isEditMode) {
    $reportId = intval($_GET['id']);
    
    // Get report information
    $sql = "SELECT * FROM reports WHERE report_id = ?";
    $report = fetchOne($sql, [$reportId]);
    
    // Check if report exists
    if (!$report) {
        $_SESSION['error'] = "Report not found.";
        header("Location: reports.php");
        exit();
    }
    
    // Format categories for display
    if (!empty($report['categories'])) {
        $categories = json_decode($report['categories'], true) ?: [];
        $categoriesString = implode(', ', $categories);
    }
    
    // Set page title for edit mode
    $pageTitle = "Edit Report: " . $report['title'];
} else {
    // Set default values for create mode
    $report = [
        'title' => '',
        'author' => '',
        'date' => date('Y-m-d'),
        'type' => '',
        'portfolio' => '',
        'summary' => '',
        'featured' => 0,
        'file_path' => ''
    ];
    
    // Set page title for create mode
    $pageTitle = "Create New Report";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_report'])) {
    // Get form data
    $title = $_POST['report_title'] ?? '';
    $author = $_POST['report_author'] ?? '';
    $date = $_POST['report_date'] ?? '';
    $type = $_POST['report_type'] ?? '';
    $portfolio = $_POST['report_portfolio'] ?? '';
    $summary = $_POST['report_summary'] ?? '';
    $categories = $_POST['report_categories'] ?? '';
    $featured = isset($_POST['report_featured']) ? 1 : 0;

    // Process categories into an array
    $categoriesArray = !empty($categories) ? array_map('trim', explode(',', $categories)) : [];
    $categoriesJson = json_encode($categoriesArray);

    // Validate input
    if (empty($title) || empty($author) || empty($date) || empty($type) || empty($portfolio) || empty($summary)) {
        $_SESSION['error'] = "All fields except categories are required.";
    } else {
        // Initialize params array for SQL update
        $params = [
            $title,
            $author,
            $date,
            $type,
            $portfolio,
            $summary,
            $categoriesJson,
            $featured,
            $reportId
        ];

        // Check if new file was uploaded
        $fileUploaded = isset($_FILES['report_file']) && $_FILES['report_file']['error'] !== UPLOAD_ERR_NO_FILE;
        
        if ($fileUploaded) {
            $file = $_FILES['report_file'];
            $fileName = $file['name'];
            $fileTmpPath = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            
            // Check for upload errors
            if ($fileError !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = "File upload error (code: $fileError).";
            } else {
                // Get file extension
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Allowed file types (only PDF for reports)
                $allowedExtensions = ['pdf'];
                
                // Check file type
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $_SESSION['error'] = "Invalid file type. Only PDF files are allowed.";
                } else {
                    // Check file size (10MB max)
                    if ($fileSize > 10 * 1024 * 1024) {
                        $_SESSION['error'] = "File is too large. Maximum size is 10MB.";
                    } else {
                        // Create uploads directory if it doesn't exist
                        $uploadsDir = '../uploads/reports';
                        if (!file_exists($uploadsDir)) {
                            mkdir($uploadsDir, 0755, true);
                        }
                        
                        // Create unique filename
                        $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $fileName);
                        $uploadFilePath = $uploadsDir . '/' . $newFileName;
                        
                        // Move uploaded file
                        if (move_uploaded_file($fileTmpPath, $uploadFilePath)) {
                            // Delete old file if it exists
                            $oldFilePath = $uploadsDir . '/' . $report['file_path'];
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                            
                            // Update SQL query to include new file path
                            $sql = "UPDATE reports SET 
                                    title = ?, 
                                    author = ?, 
                                    date = ?, 
                                    type = ?, 
                                    portfolio = ?, 
                                    summary = ?, 
                                    categories = ?, 
                                    featured = ?,
                                    file_path = ?,
                                    description = ?,
                                    content = ?,
                                    report_type = 'general'
                                    WHERE report_id = ?";
                            
                            // Add file path to params
                            array_splice($params, 8, 0, [$newFileName, $summary, $summary]);
                        } else {
                            $_SESSION['error'] = "Failed to upload file.";
                        }
                    }
                }
            }
        } else {
            // No new file uploaded, just update other fields
            $sql = "UPDATE reports SET 
                    title = ?, 
                    author = ?, 
                    date = ?, 
                    type = ?, 
                    portfolio = ?, 
                    summary = ?, 
                    categories = ?, 
                    featured = ?,
                    description = ?,
                    content = ?,
                    report_type = 'general'
                    WHERE report_id = ?";
            
            // Add description and content
            array_splice($params, 8, 0, [$summary, $summary]);
        }
        
        // If no errors occurred during file upload processing
        if (!isset($_SESSION['error'])) {
            // Update database
            $result = update($sql, $params);
            
            if ($result !== false) {
                $_SESSION['success'] = "Report updated successfully.";
                header("Location: report_detail.php?id=$reportId");
                exit();
            } else {
                $_SESSION['error'] = "Failed to update report: " . mysqli_error($conn);
            }
        }
    }
}

// Get available portfolios
$portfolios = getAllPortfolios();



// Include header
require_once 'includes/header.php';

// Define page title, icon, and actions for the modern header
$pageTitle = $isCreateMode ? 'Create New Report' : 'Edit Report';
$pageIcon = "fa-edit";
$pageDescription = $isCreateMode ? 'Create a new report for the system' : 'Edit and update report information';
$actions = [];

if (!$isCreateMode) {
    $actions[] = [
        'url' => 'report_detail.php?id=' . $reportId,
        'icon' => 'fa-eye',
        'text' => 'View Report',
        'class' => 'btn-secondary'
    ];
}

$actions[] = [
    'url' => 'reports.php',
    'icon' => 'fa-arrow-left',
    'text' => 'Back to Reports',
    'class' => 'btn-outline-light'
];

// Include the modern page header
include_once 'includes/modern_page_header.php';
?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mt-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
                    <?php if ($isEditMode): ?>
                    <li class="breadcrumb-item"><a href="report_detail.php?id=<?php echo $reportId; ?>"><?php echo htmlspecialchars($report['title']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    <?php else: ?>
                    <li class="breadcrumb-item active" aria-current="page">Create New Report</li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error']; 
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card mb-4" style="margin-top: 1.5rem;">
        <div class="card-body">
            <form method="POST" action="<?php echo $isCreateMode ? 'report_handler.php' : htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $reportId); ?>" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="report_title" class="form-label">Report Title</label>
                    <input type="text" class="form-control" id="report_title" name="report_title" value="<?php echo $isEditMode ? htmlspecialchars($report['title']) : ''; ?>" required>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="report_author" class="form-label">Author</label>
                        <input type="text" class="form-control" id="report_author" name="report_author" value="<?php echo $isEditMode ? htmlspecialchars($report['author'] ?? '') : ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="report_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="report_date" name="report_date" value="<?php echo $isEditMode ? ($report['created_at'] ?? $report['date'] ?? date('Y-m-d')) : date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="">Select Type</option>
                            <option value="Annual" <?php echo $isEditMode && ($report['report_type'] ?? $report['type'] ?? '') === 'Annual' ? 'selected' : ''; ?>>Annual Report</option>
                            <option value="Term" <?php echo $isEditMode && ($report['report_type'] ?? $report['type'] ?? '') === 'Term' ? 'selected' : ''; ?>>Term Report</option>
                            <option value="Financial" <?php echo $isEditMode && ($report['report_type'] ?? $report['type'] ?? '') === 'Financial' ? 'selected' : ''; ?>>Financial Report</option>
                            <option value="Event" <?php echo $isEditMode && ($report['report_type'] ?? $report['type'] ?? '') === 'Event' ? 'selected' : ''; ?>>Event Report</option>
                            <option value="Survey" <?php echo $isEditMode && ($report['report_type'] ?? $report['type'] ?? '') === 'Survey' ? 'selected' : ''; ?>>Survey Results</option>
                            <option value="Academic" <?php echo $isEditMode && ($report['report_type'] ?? $report['type'] ?? '') === 'Academic' ? 'selected' : ''; ?>>Academic Report</option>
                            <option value="Program" <?php echo $isEditMode && ($report['report_type'] ?? $report['type'] ?? '') === 'Program' ? 'selected' : ''; ?>>Program Evaluation</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="report_portfolio" class="form-label">Portfolio</label>
                        <select class="form-select" id="report_portfolio" name="report_portfolio" required>
                            <option value="">Select Portfolio</option>
                            <?php foreach ($portfolios as $portfolio): ?>
                            <option value="<?php echo htmlspecialchars($portfolio); ?>" <?php echo $isEditMode && ($report['portfolio'] ?? '') === $portfolio ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($portfolio); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="report_summary" class="form-label">Summary</label>
                    <textarea class="form-control" id="report_summary" name="report_summary" rows="5" required><?php echo $isEditMode ? htmlspecialchars($report['description'] ?? $report['content'] ?? $report['summary'] ?? '') : ''; ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="report_categories" class="form-label">Categories</label>
                    <input type="text" class="form-control" id="report_categories" name="report_categories" placeholder="Comma-separated categories" value="<?php echo $isEditMode ? htmlspecialchars($categoriesString) : ''; ?>">
                    <div class="form-text">Enter categories separated by commas (e.g. Academic, Events, Financial)</div>
                </div>
                
                <div class="mb-3">
                    <label for="report_file" class="form-label"><?php echo $isEditMode ? 'Replace Report File (PDF)' : 'Report File (PDF)'; ?></label>
                    <input type="file" class="form-control" id="report_file" name="report_file" accept=".pdf" <?php echo !$isEditMode ? 'required' : ''; ?>>
                    <?php if ($isEditMode): ?>
                    <div class="form-text">Leave empty to keep the current file. Current file: <?php echo htmlspecialchars($report['file_path'] ?? 'No file'); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="report_featured" name="report_featured" <?php echo $isEditMode && ($report['featured'] ?? false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="report_featured">
                        Mark as featured report
                    </label>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="reports.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" name="<?php echo $isCreateMode ? 'upload_report' : 'update_report'; ?>">
                        <?php echo $isCreateMode ? 'Upload Report' : 'Update Report'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 
