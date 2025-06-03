<?php
// Include authentication file
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../functions.php'; // Include the functions file directly

// Define the function locally in case it's not found from the include
if (!function_exists('getAllPortfolios')) {
    function getAllPortfolios() {
        global $conn;
        
        $portfolios = [];
        
        // Try to get portfolios from the database
        try {
            $portfoliosSql = "SELECT DISTINCT portfolio FROM reports ORDER BY portfolio";
            $result = mysqli_query($conn, $portfoliosSql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $portfolios[] = $row['portfolio'];
                }
            }
        } catch (Exception $e) {
            // Just handle silently and use default list
        }
        
        // If no portfolios found in database, use a standard list
        if (empty($portfolios)) {
            $portfolios = [
                'President',
                'Secretary General',
                'Treasurer',
                'Academic Affairs',
                'Sports & Culture',
                'Student Welfare',
                'International Students',
                'General'
            ];
        }
        
        // Sort the portfolios
        sort($portfolios);
        
        return $portfolios;
    }
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Set page title
$pageTitle = "Reports - SRC Management System";

// Check for portfolio filter from URL
$portfolioFilter = isset($_GET['portfolio']) ? $_GET['portfolio'] : '';

// Create a directory for report uploads if it doesn't exist
$uploadsDir = '../uploads/reports';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();
$canManageReports = $isAdmin || $isMember; // Allow both admins and members to manage reports

// Check if user is trying to create new reports without permission
if (isset($_GET['action']) && $_GET['action'] === 'new' && !$canManageReports) {
    // Redirect non-privileged users back to the reports page
    header("Location: reports.php");
    exit();
}

// Fetch reports from database
$srcReports = [];
try {
    // Build SQL query based on filter
    $sql = "SELECT * FROM reports";
    $params = [];
    
    if (!empty($portfolioFilter)) {
        $sql .= " WHERE portfolio = ?";
        $params[] = $portfolioFilter;
    }
    
    $sql .= " ORDER BY date DESC";
    
    $reportResults = fetchAll($sql, $params);
    
    // Process each report
    foreach ($reportResults as $report) {
        // Decode categories from JSON
        $categories = json_decode($report['categories'], true) ?: [];
        
        // Add to reports array
        $srcReports[] = [
            'id' => $report['report_id'],
            'title' => $report['title'],
            'author' => $report['author'],
            'date' => $report['date'],
            'type' => $report['type'],
            'summary' => $report['summary'],
            'categories' => $categories,
            'featured' => (bool)$report['featured'],
            'file_path' => '../uploads/reports/' . $report['file_path'],
            'portfolio' => $report['portfolio'],
            'db_record' => $report
        ];
    }
} catch (Exception $e) {
    // Handle exception
    $_SESSION['error'] = "Error fetching reports: " . $e->getMessage();
    $srcReports = [];
}

// If no reports in database, handle gracefully
if (empty($srcReports)) {
    // Optionally add some sample reports to display in the UI
    // This is useful for development or when the table is empty
    if (!isset($_SESSION['error'])) {
        $_SESSION['info'] = "No reports found. Add your first report by clicking 'Upload Report'.";
    }
}

// Filter reports if portfolio filter is set
$filteredReports = $srcReports;
if (!empty($portfolioFilter)) {
    $filteredReports = array_filter($srcReports, function($report) use ($portfolioFilter) {
        return $report['portfolio'] === $portfolioFilter;
    });
}

// Get unique portfolios for the filter dropdown
$portfolios = getAllPortfolios();

// Include header
require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
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

    <?php if (isset($_SESSION['info'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['info']; 
        unset($_SESSION['info']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php 
    // Define page title, icon, and actions for the enhanced header
    $pageTitle = "Reports";
    $pageIcon = "fa-chart-bar";
    $actions = [];
    
    if ($canManageReports) {
        $actions[] = [
            'url' => 'report_edit.php?action=new',
            'icon' => 'fa-plus',
            'text' => 'Upload Report',
            'class' => 'btn-primary'
        ];
    }
    
    // Include the enhanced page header
    include_once 'includes/enhanced_page_header.php';
    ?>
            
<!-- Filter Reports -->
<div class="row mb-4 filter-section">
    <div class="col-md-12">
        <div class="content-card animate-fadeIn">
            <div class="content-card-header d-flex align-items-center">
                <h3 class="content-card-title mb-0"><i class="fas fa-filter me-2"></i> Filter Reports</h3>
            </div>
            <div class="content-card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="d-flex align-items-center">
                    <div class="flex-grow-1 me-3">
                        <select class="form-select" name="portfolio" id="portfolioFilter">
                            <option value="">All Portfolios</option>
                            <?php foreach ($portfolios as $portfolio): ?>
                            <option value="<?php echo htmlspecialchars($portfolio); ?>" <?php echo $portfolioFilter === $portfolio ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($portfolio); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <?php if (!empty($portfolioFilter)): ?>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-times me-1"></i> Clear
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reports Grid -->
<div class="row">
    <?php if (empty($filteredReports)): ?>
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> No reports found for the selected portfolio.
            <?php if (!empty($portfolioFilter)): ?>
            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="alert-link">View all reports</a>.
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
        <?php 
        $cardIndex = 0;
        foreach ($filteredReports as $report): 
            $cardIndex++;
        ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="content-card report-card animate-fadeIn h-100" style="--card-index: <?php echo $cardIndex; ?>" data-type="<?php echo htmlspecialchars($report['type']); ?>">
                <div class="content-card-header" style="padding: 1.75rem !important;">
                    <h3 class="content-card-title">
                        <i class="fas fa-file-alt me-2"></i> 
                        <?php echo htmlspecialchars($report['title']); ?>
                    </h3>
                    <?php if ($report['featured']): ?>
                    <span class="badge bg-warning text-dark featured-badge">
                        <i class="fas fa-star"></i> Featured
                    </span>
                    <?php endif; ?>
                </div>
                <div class="content-card-body" style="padding: 1.75rem !important;">
                    <h6 class="card-subtitle mb-2 text-muted">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($report['author']); ?></span>
                    </h6>
                    <h6 class="card-subtitle mb-2 text-muted">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo date('F j, Y', strtotime($report['date'])); ?></span>
                    </h6>
                    <div class="badge-container">
                        <span class="badge bg-secondary">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($report['type']); ?>
                        </span>
                        <span class="badge bg-success">
                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($report['portfolio']); ?>
                        </span>
                    </div>
                    <p class="card-text"><?php echo htmlspecialchars(substr($report['summary'], 0, 150)); ?>
                    <?php echo strlen($report['summary']) > 150 ? '...' : ''; ?></p>
                    <div class="badge-container">
                        <?php foreach ($report['categories'] as $category): ?>
                        <span class="badge bg-info">
                            <?php echo htmlspecialchars($category); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="button-container d-grid gap-2">
                        <a href="<?php echo htmlspecialchars($report['file_path']); ?>" class="btn btn-primary" target="_blank">
                            <i class="fas fa-file-pdf"></i> View Report
                        </a>
                        <a href="report_handler.php?action=download&id=<?php echo $report['id']; ?>" class="btn btn-success">
                            <i class="fas fa-download"></i> Download Report
                        </a>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="report_detail.php?id=<?php echo $report['id']; ?>" class="details-btn">
                            <i class="fas fa-info-circle"></i> Details
                        </a>
                        
                        <?php if (hasPermission('update', 'reports')): ?>
                        <a href="report_edit.php?id=<?php echo $report['id']; ?>" class="details-btn">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('delete', 'reports')): ?>
                        <a href="#" class="details-btn text-danger" data-bs-toggle="modal" data-bs-target="#deleteReportModal<?php echo $report['id']; ?>">
                            <i class="fas fa-trash-alt"></i> Delete
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (hasPermission('delete', 'reports')): ?>
        <!-- Delete Modal for each report -->
        <div class="modal fade" id="deleteReportModal<?php echo $report['id']; ?>" tabindex="-1" aria-labelledby="deleteReportModalLabel<?php echo $report['id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteReportModalLabel<?php echo $report['id']; ?>">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this report? This action cannot be undone.</p>
                        <p class="fw-bold"><?php echo htmlspecialchars($report['title']); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="report_handler.php?action=delete&id=<?php echo $report['id']; ?>" class="btn btn-danger">Delete</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Upload Report Modal -->
    <?php if (hasPermission('create', 'reports')): ?>
<div class="modal fade" id="uploadReportModal" tabindex="-1" aria-labelledby="uploadReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadReportModalLabel">Upload New Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
                <form method="POST" action="report_handler.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="report_title" class="form-label">Report Title</label>
                        <input type="text" class="form-control" id="report_title" name="report_title" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="report_author" class="form-label">Author</label>
                            <input type="text" class="form-control" id="report_author" name="report_author" required>
                        </div>
                        <div class="col-md-6">
                            <label for="report_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="report_date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="report_type" required>
                                <option value="">Select Type</option>
                                <option value="Annual">Annual Report</option>
                                <option value="Term">Term Report</option>
                                <option value="Financial">Financial Report</option>
                                <option value="Event">Event Report</option>
                                <option value="Survey">Survey Results</option>
                                <option value="Academic">Academic Report</option>
                                <option value="Program">Program Evaluation</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="report_portfolio" class="form-label">Portfolio</label>
                            <select class="form-select" id="report_portfolio" name="report_portfolio" required>
                                <option value="">Select Portfolio</option>
                                    <?php 
                                    $portfolioList = getAllPortfolios();
                                    foreach ($portfolioList as $portfolio): ?>
                                <option value="<?php echo htmlspecialchars($portfolio); ?>">
                                    <?php echo htmlspecialchars($portfolio); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="report_summary" class="form-label">Summary</label>
                        <textarea class="form-control" id="report_summary" name="report_summary" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="report_categories" class="form-label">Categories</label>
                        <input type="text" class="form-control" id="report_categories" name="report_categories" placeholder="Comma-separated categories">
                        <div class="form-text">Enter categories separated by commas (e.g. Academic, Events, Financial)</div>
                    </div>
                    <div class="mb-3">
                        <label for="report_file" class="form-label">Report File (PDF)</label>
                        <input type="file" class="form-control" id="report_file" name="report_file" accept=".pdf" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="report_featured" name="report_featured">
                        <label class="form-check-label" for="report_featured">
                            Mark as featured report
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="upload_report">Upload Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 