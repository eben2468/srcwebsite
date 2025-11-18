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
        
        // If no portfolios found in database, use VVUSRC leadership positions
        if (empty($portfolios)) {
            $portfolios = [
                'President',
                'Vice President',
                'Senate President',
                'Executive Secretary',
                'Finance Officer',
                'Editor',
                'Organizing Secretary',
                'Welfare Officer',
                'Women\'s Commissioner',
                'Sports Commissioner',
                'Chaplain',
                'Public Relations Officer',
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

// Check if reports feature is enabled
if (!hasFeaturePermission('enable_reports')) {
    $_SESSION['error'] = "Reports feature is currently disabled.";
    header("Location: dashboard.php");
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
$isAdmin = shouldUseAdminInterface();
$isMember = isMember();
$isSuperAdmin = isSuperAdmin();
$shouldUseAdminInterface = shouldUseAdminInterface(); // Includes super admin
$canManageReports = $shouldUseAdminInterface || $isMember; // Allow super admin, admin, and members to manage reports

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
    
    $sql .= " ORDER BY created_at DESC";
    
    $reportResults = fetchAll($sql, $params);
    
    // Process each report
    foreach ($reportResults as $report) {
        // Decode categories from JSON (with fallback)
        $categories = isset($report['categories']) ? json_decode($report['categories'], true) : [];
        $categories = $categories ?: [];

        // Get author name from user table if author_id exists
        $authorName = 'Unknown Author';
        if (isset($report['author_id']) && $report['author_id']) {
            try {
                $authorUser = fetchOne("SELECT username, full_name FROM users u LEFT JOIN user_profiles up ON u.user_id = up.user_id WHERE u.user_id = ?", [$report['author_id']]);
                if ($authorUser) {
                    $authorName = $authorUser['full_name'] ?: $authorUser['username'];
                }
            } catch (Exception $e) {
                // Fallback to author column if it exists
                $authorName = $report['author'] ?? 'Unknown Author';
            }
        } elseif (isset($report['author'])) {
            $authorName = $report['author'];
        }

        // Add to reports array with proper fallbacks
        $srcReports[] = [
            'id' => $report['report_id'],
            'title' => $report['title'],
            'author' => $authorName,
            'date' => $report['created_at'] ?? $report['date'] ?? date('Y-m-d'),
            'type' => $report['report_type'] ?? $report['type'] ?? 'General',
            'summary' => $report['description'] ?? $report['content'] ?? $report['summary'] ?? 'No description available',
            'categories' => $categories,
            'featured' => isset($report['featured']) ? (bool)$report['featured'] : false,
            'file_path' => isset($report['file_path']) ? '../uploads/reports/' . $report['file_path'] : '',
            'portfolio' => $report['portfolio'] ?? 'General',
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

<style>
/* Modern Report Card Styles */
.modern-report-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
}

.modern-report-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

/* Card Header */
.card-header-modern {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
    color: white;
    min-height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.featured-ribbon {
    position: absolute;
    top: 0;
    right: 0;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-bottom-left-radius: 12px;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.report-type-icon {
    font-size: 2rem;
    opacity: 0.9;
}

/* Card Body */
.card-body-modern {
    padding: 1.5rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.report-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 1rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.report-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
}

.meta-item i {
    width: 16px;
    color: #94a3b8;
}

.badge-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.modern-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.portfolio-badge {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.type-badge {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: #1a202c;
}

.report-summary {
    color: #4a5568;
    line-height: 1.6;
    margin-bottom: 1rem;
    flex-grow: 1;
    font-size: 0.9rem;
}

.categories-section {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
    margin-bottom: 1rem;
}

.category-tag {
    background: #f7fafc;
    color: #4a5568;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    border: 1px solid #e2e8f0;
    font-weight: 500;
}

.more-categories {
    background: #edf2f7;
    color: #718096;
    font-style: italic;
}

/* Card Footer */
.card-footer-modern {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.primary-actions {
    flex: 1;
}

.btn-primary-modern {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    border: none;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.btn-disabled-modern {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #e2e8f0;
    color: #a0aec0;
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.875rem;
}

.secondary-actions {
    display: flex !important;
    gap: 0.5rem !important;
    margin-left: 1rem !important;
    align-items: center !important;
    justify-content: flex-start !important;
    flex-wrap: wrap !important;
    overflow: visible !important;
    z-index: 10 !important;
    position: relative !important;
}

.action-btn {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    min-width: 40px !important;
    min-height: 40px !important;
    border-radius: 10px !important;
    background: white !important;
    color: #64748b !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
    border: 1px solid #e2e8f0 !important;
    opacity: 1 !important;
    visibility: visible !important;
    position: relative !important;
    z-index: 10 !important;
    flex-shrink: 0 !important;
}

.action-btn:hover {
    background: #f1f5f9 !important;
    color: #475569 !important;
    transform: translateY(-2px) !important;
    text-decoration: none !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.action-btn.delete-btn:hover {
    background: #fef2f2 !important;
    color: #dc2626 !important;
    border-color: #fecaca !important;
}

/* Ensure icons are visible */
.action-btn i {
    font-size: 16px !important;
    opacity: 1 !important;
    visibility: visible !important;
    display: block !important;
    color: inherit !important;
}

/* Fix card footer layout */
.card-footer-modern {
    display: flex !important;
    justify-content: space-between !important;
    align-items: flex-start !important;
    padding: 1.5rem !important;
    background: #f8fafc !important;
    border-top: 1px solid #e2e8f0 !important;
    overflow: visible !important;
    position: relative !important;
}

/* Animation */
.animate-fadeIn {
    animation: fadeInUp 0.6s ease-out forwards;
    animation-delay: calc(var(--card-index) * 0.1s);
    opacity: 0;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-footer-modern {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .secondary-actions {
        margin-left: 0 !important;
        justify-content: center !important;
        gap: 0.75rem !important;
        flex-wrap: wrap !important;
    }

    .action-btn {
        width: 45px !important;
        height: 45px !important;
        min-width: 45px !important;
        min-height: 45px !important;
    }

    .action-btn i {
        font-size: 18px !important;
    }

    .btn-primary-modern {
        justify-content: center;
    }
}
</style>



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

    <script>
        document.body.classList.add('reports-page');
    </script>

    <!-- Custom Reports Header -->
    <div class="reports-header animate__animated animate__fadeInDown">
        <div class="reports-header-content">
            <div class="reports-header-main">
                <h1 class="reports-title">
                    <i class="fas fa-chart-line me-3"></i>
                    Reports
                </h1>
                <p class="reports-description">Access and manage SRC reports and analytics</p>
            </div>
            <?php if ($canManageReports): ?>
            <div class="reports-header-actions">
                <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#uploadReportModal">
                    <i class="fas fa-upload me-2"></i>Upload Report
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .reports-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2.5rem 2rem;
        border-radius: 12px;
        margin-top: 60px;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .reports-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .reports-header-main {
        flex: 1;
        text-align: center;
    }

    .reports-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 1rem 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.8rem;
    }

    .reports-title i {
        font-size: 2.2rem;
        opacity: 0.9;
    }

    .reports-description {
        margin: 0;
        opacity: 0.95;
        font-size: 1.2rem;
        font-weight: 400;
        line-height: 1.4;
    }

    .reports-header-actions {
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
        .reports-header {
            padding: 2rem 1.5rem;
        }

        .reports-header-content {
            flex-direction: column;
            align-items: center;
        }

        .reports-title {
            font-size: 2rem;
            gap: 0.6rem;
        }

        .reports-title i {
            font-size: 1.8rem;
        }

        .reports-description {
            font-size: 1.1rem;
        }

        .reports-header-actions {
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
    </style>

</div>


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
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="modern-report-card animate-fadeIn h-100" style="--card-index: <?php echo $cardIndex; ?>" data-type="<?php echo htmlspecialchars($report['type']); ?>">
                <!-- Card Header with Featured Badge -->
                <div class="card-header-modern">
                    <?php if ($report['featured']): ?>
                    <div class="featured-ribbon">
                        <i class="fas fa-star"></i>
                        <span>Featured</span>
                    </div>
                    <?php endif; ?>

                    <!-- Report Type Icon -->
                    <div class="report-type-icon">
                        <?php
                        $typeIcons = [
                            'Annual' => 'fas fa-calendar-year',
                            'Monthly' => 'fas fa-calendar-month',
                            'Event' => 'fas fa-calendar-day',
                            'Budget' => 'fas fa-dollar-sign',
                            'Election' => 'fas fa-vote-yea',
                            'General' => 'fas fa-file-alt'
                        ];
                        $iconClass = $typeIcons[$report['type']] ?? 'fas fa-file-alt';
                        ?>
                        <i class="<?php echo $iconClass; ?>"></i>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card-body-modern">
                    <!-- Title -->
                    <h3 class="report-title">
                        <?php echo htmlspecialchars($report['title']); ?>
                    </h3>

                    <!-- Meta Information -->
                    <div class="report-meta">
                        <div class="meta-item">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($report['author']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo date('M j, Y', strtotime($report['date'])); ?></span>
                        </div>
                    </div>

                    <!-- Portfolio and Type Badges -->
                    <div class="badge-row">
                        <span class="modern-badge portfolio-badge">
                            <i class="fas fa-briefcase"></i>
                            <?php echo htmlspecialchars($report['portfolio']); ?>
                        </span>
                        <span class="modern-badge type-badge">
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($report['type']); ?>
                        </span>
                    </div>

                    <!-- Summary -->
                    <p class="report-summary">
                        <?php echo htmlspecialchars(substr($report['summary'], 0, 120)); ?>
                        <?php echo strlen($report['summary']) > 120 ? '...' : ''; ?>
                    </p>

                    <!-- Categories -->
                    <?php if (!empty($report['categories'])): ?>
                    <div class="categories-section">
                        <?php foreach (array_slice($report['categories'], 0, 3) as $category): ?>
                        <span class="category-tag">
                            <?php echo htmlspecialchars($category); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($report['categories']) > 3): ?>
                        <span class="category-tag more-categories">
                            +<?php echo count($report['categories']) - 3; ?> more
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Card Footer with Actions -->
                <div class="card-footer-modern">
                    <!-- Primary Action -->
                    <div class="primary-actions">
                        <?php if (!empty($report['file_path'])): ?>
                        <a href="<?php echo htmlspecialchars($report['file_path']); ?>" class="btn-primary-modern" target="_blank">
                            <i class="fas fa-eye"></i>
                            <span>View Report</span>
                        </a>
                        <?php else: ?>
                        <span class="btn-disabled-modern">
                            <i class="fas fa-file-slash"></i>
                            <span>No File</span>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Secondary Actions -->
                    <div class="secondary-actions">
                        <a href="report_detail.php?id=<?php echo $report['id']; ?>" class="action-btn" title="View Details">
                            <i class="fas fa-info-circle"></i>
                        </a>

                        <?php if (!empty($report['file_path'])): ?>
                        <a href="report_handler.php?action=download&id=<?php echo $report['id']; ?>" class="action-btn" title="Download">
                            <i class="fas fa-download"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (hasPermission('update', 'reports')): ?>
                        <a href="report_edit.php?id=<?php echo $report['id']; ?>" class="action-btn" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (shouldUseAdminInterface()): ?>
                        <a href="#" class="action-btn delete-btn" data-bs-toggle="modal" data-bs-target="#deleteReportModal<?php echo $report['id']; ?>" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (shouldUseAdminInterface()): ?>
        <!-- Delete Modal for each report -->
        <div class="modal fade" id="deleteReportModal<?php echo $report['id']; ?>" tabindex="-1" aria-labelledby="deleteReportModalLabel<?php echo $report['id']; ?>" aria-hidden="true" data-bs-backdrop="false">
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
<div class="modal fade" id="uploadReportModal" tabindex="-1" aria-labelledby="uploadReportModalLabel" aria-hidden="true" data-bs-backdrop="false">
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
                                    <?php foreach ($portfolios as $portfolio): ?>
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
