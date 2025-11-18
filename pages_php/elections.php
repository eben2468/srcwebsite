<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php'; // Added auth_functions.php for canManageResource()
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

// Check if elections feature is enabled
if (!hasFeaturePermission('enable_elections')) {
    $_SESSION['error'] = "The elections feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Get current user info for permissions check
$user = getCurrentUser();
$isSuperAdmin = isSuperAdmin();
$isAdmin = isAdmin();
$isMember = isMember();
$isFinance = isFinance();

// Both super admins and admins should have admin interface access for viewing
$hasAdminInterface = shouldUseAdminInterface();

// Only super admins should have CRUD functionality - use the function from auth_functions.php
$canManageElections = canManageElections();

// Handle deletion if requested - MUST be before any output
if (isset($_GET['delete']) && $canManageElections) {
    $electionId = intval($_GET['delete']);
    header("Location: election_handler.php?action=delete&id=" . $electionId);
    exit();
}

// Check if user is trying to create new election without permission - MUST be before any output
if (isset($_GET['action']) && $_GET['action'] === 'new' && !$canManageElections) {
    // Redirect non-privileged users back to the elections page
    header("Location: elections.php");
    exit();
}

// Set page title
$pageTitle = "Elections - SRC Management System";

// Include header
require_once 'includes/header.php';

// Add election-actions CSS
echo '<link rel="stylesheet" href="../css/election-actions.css">';
// Add election mobile fix CSS
echo '<link rel="stylesheet" href="../css/election-mobile-fix.css">';
// Add candidate card mobile fix CSS
echo '<link rel="stylesheet" href="../css/candidate-card-mobile-fix.css">';
// Add modal backdrop fix CSS
echo '<link rel="stylesheet" href="../css/modal-backdrop-fix.css">';
// Add election admin tools CSS
echo '<link rel="stylesheet" href="../css/election-admin-tools.css">';

// Immediate backdrop fix script
echo '<script>
    // Fix for any lingering backdrops when the page loads
    document.addEventListener("DOMContentLoaded", function() {
        // Remove any existing modal backdrops
        const removeBackdrops = function() {
            const backdrops = document.querySelectorAll(".modal-backdrop");
            backdrops.forEach(function(backdrop) {
                backdrop.remove();
            });
            
            // Fix body classes
            document.body.classList.remove("modal-open");
            document.body.style.overflow = "";
            document.body.style.paddingRight = "";
        };
        
        // Remove backdrops immediately
        removeBackdrops();
        
        // Also set an interval to continuously check and remove any backdrops
        setInterval(removeBackdrops, 500);
        
        // Add a mutation observer to detect any new backdrops being added
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    for (let i = 0; i < mutation.addedNodes.length; i++) {
                        const node = mutation.addedNodes[i];
                        if (node.classList && node.classList.contains("modal-backdrop")) {
                            node.remove();
                        }
                    }
                }
            });
        });
        
        // Start observing the document body for added nodes
        observer.observe(document.body, { childList: true });
        
        // Override Bootstrap\'s modal method to prevent adding backdrop
        if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
            const originalModalShow = bootstrap.Modal.prototype.show;
            bootstrap.Modal.prototype.show = function() {
                // Call the original method
                originalModalShow.apply(this, arguments);
                
                // Then remove the backdrop
                setTimeout(removeBackdrops, 50);
            };
        }
    });
</script>';

// Add elections-page class to body
echo '<script>document.body.classList.add("elections-page");</script>';

// Disable conflicting header scripts
echo '<script src="../js/disable-conflicting-header-scripts.js"></script>';

// Build query for elections
$sql = "SELECT e.*, 
        (SELECT COUNT(*) FROM election_positions WHERE election_id = e.election_id) AS position_count,
        (SELECT COUNT(*) FROM election_candidates c JOIN election_positions p ON c.position_id = p.position_id WHERE p.election_id = e.election_id) AS candidate_count
        FROM elections e";
$params = [];

// Apply search filter if provided
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $sql .= " WHERE (e.title LIKE ? OR e.description LIKE ?)";
    $search = '%' . $_GET['search'] . '%';
    $params[] = $search;
    $params[] = $search;
    $whereAdded = true;
} else {
    $whereAdded = false;
}

// Apply status filter if provided
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $statusMap = [
        'Nominations Open' => 'nomination',
        'Pending Voting' => 'pending',
        'Upcoming' => 'upcoming',
        'Active' => 'active',
        'Planning' => 'upcoming', // Treat Planning as Upcoming in the database
        'Completed' => 'completed',
        'Cancelled' => 'cancelled'
    ];
    
    $dbStatus = $statusMap[$_GET['status']] ?? strtolower($_GET['status']);
    
    if ($whereAdded) {
        $sql .= " AND e.status = ?";
    } else {
        $sql .= " WHERE e.status = ?";
        $whereAdded = true;
    }
    
    $params[] = $dbStatus;
}

// Apply month filter if provided
if (isset($_GET['month']) && !empty($_GET['month'])) {
    $monthParts = explode('-', $_GET['month']);
    if (count($monthParts) === 2) {
        $year = $monthParts[0];
        $month = $monthParts[1];
        
        if ($whereAdded) {
            $sql .= " AND (MONTH(e.start_date) = ? OR MONTH(e.end_date) = ?) AND (YEAR(e.start_date) = ? OR YEAR(e.end_date) = ?)";
        } else {
            $sql .= " WHERE (MONTH(e.start_date) = ? OR MONTH(e.end_date) = ?) AND (YEAR(e.start_date) = ? OR YEAR(e.end_date) = ?)";
            $whereAdded = true;
        }
        
        $params[] = $month;
        $params[] = $month;
        $params[] = $year;
        $params[] = $year;
    }
}

// Order by start date (most recent first)
$sql .= " ORDER BY e.start_date DESC";

// Fetch elections
$elections = fetchAll($sql, $params);

// Map database status to display format
$statusDisplayMap = [
    'nomination' => 'Nominations Open',
    'pending' => 'Pending Voting',
    'upcoming' => 'Upcoming',
    'active' => 'Active',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];
?>

<?php
// Set up modern page header variables
$pageTitle = "Elections";
$pageIcon = "fa-vote-yea";
$pageDescription = "Manage and participate in SRC elections and voting processes";
$actions = [];

if ($canManageElections) {
    $actions[] = [
        'data-bs-toggle' => 'modal',
        'data-bs-target' => '#createElectionModal',
        'icon' => 'fa-plus',
        'text' => 'Create Election',
        'class' => 'btn-primary'
    ];
}

// Include the modern page header
include 'includes/modern_page_header.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    
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

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Access Control Notice for Non-Super Admin Users -->
    <?php if (!$canManageElections): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Elections Management:</strong> Full elections management (create, edit, delete) is restricted to Super Admin users only. You can view elections and their details, but cannot modify them.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
            
    <?php
    // Define page title, icon, and actions for the enhanced header
    ?>

    <style>
    /* Enhanced styling for the elections page */
    .elections-container {
        max-width: 100%;
        padding: 0 20px;
    }
    
    .page-header-container {
        background-color: #1a73e8;
        color: white;
        border-radius: 12px;
        padding: 15px 25px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-header-left {
        display: flex;
        align-items: center;
    }
    
    .page-header-left i {
        font-size: 1.5rem;
        margin-right: 15px;
    }
    
    .page-header-left h1 {
        font-size: 1.8rem;
        margin: 0;
        font-weight: 500;
    }
    
    .page-header-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .current-date {
        background-color: rgba(255, 255, 255, 0.2);
        padding: 8px 15px;
        border-radius: 6px;
        font-weight: 500;
    }
    
    .section-header {
        background-color: white;
        color: #333;
        padding: 0.8rem 1.2rem;
        border-radius: 8px 8px 0 0;
        margin-bottom: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        border: 1px solid #eaeaea;
        border-bottom: none;
    }
    
    .section-header i {
        margin-right: 0.8rem;
        font-size: 1.1em;
        color: #1a73e8;
    }
    
    .content-panel {
        background: white;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 25px;
        border: 1px solid #eaeaea;
        border-top: none;
    }
    
    .content-panel-body {
        padding: 1.5rem;
    }
    
    .form-control, .form-select, .input-group-text {
        border-radius: 6px;
        border-color: #d1d1d1;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #1a73e8;
        box-shadow: 0 0 0 0.25rem rgba(26, 115, 232, 0.25);
    }
    
    .btn-filter {
        background-color: #1a73e8;
        color: white;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .btn-filter:hover {
        background-color: #1967d2;
        color: white;
    }
    
    .btn-create {
        background-color: #1a73e8;
        color: white;
        border-radius: 50px;
        padding: 8px 20px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        border: none;
    }
    
    .btn-create:hover {
        background-color: #1967d2;
        color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .admin-tools-container {
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .admin-tool {
        flex: 1;
        background-color: #fff;
        border-radius: 8px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .admin-tool-icon {
        background-color: #f0f4ff;
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
    }
    
    .admin-tool-icon i {
        font-size: 1.8rem;
        color: #1a73e8;
    }
    
    .admin-tool h4 {
        margin-bottom: 10px;
        font-weight: 500;
        color: #333;
    }
    
    .admin-tool p {
        color: #666;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }
    
    .admin-tool .btn {
        border-radius: 50px;
    }
    
    .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        padding: 12px 15px;
        font-weight: 600;
    }
    
    .table tbody td {
        padding: 12px 15px;
        vertical-align: middle;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(26, 115, 232, 0.05);
    }
    
    .badge {
        padding: 0.5em 0.8em;
        font-weight: 500;
        border-radius: 6px;
    }
    
    .actions-cell {
        white-space: nowrap;
    }
    
    .btn-action {
        width: 36px;
        height: 36px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 5px;
        transition: all 0.2s ease;
    }
    
    .btn-view {
        background-color: #6c757d;
        color: white;
    }
    
    .btn-edit {
        background-color: #1a73e8;
        color: white;
    }
    
    .btn-delete {
        background-color: #dc3545;
        color: white;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 5px rgba(0,0,0,0.1);
    }
    
    .pagination {
        margin-top: 1rem;
        justify-content: center;
    }
    
    .footer {
        background-color: white;
        color: #333;
        padding: 15px 0;
        text-align: center;
        border-radius: 0;
        margin-top: 40px;
        border-top: 1px solid #eaeaea;
    }
    
    .footer a {
        color: #1a73e8;
        text-decoration: underline;
    }
    </style>

    <div class="elections-container">

        <!-- Filters and Search -->
        <h3 class="section-header">
            <i class="fas fa-filter"></i> Filter Elections
        </h3>

        <!-- Filter Form -->
        <div class="content-panel">
            <div class="content-panel-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Search elections..." 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="Nominations Open" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Nominations Open') ? 'selected' : ''; ?>>Nominations Open</option>
                            <option value="Pending Voting" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Pending Voting') ? 'selected' : ''; ?>>Pending Voting</option>
                            <option value="Upcoming" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="Active" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Planning" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Planning') ? 'selected' : ''; ?>>Planning</option>
                            <option value="Completed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="month" class="form-control" name="month" placeholder="Filter by month" 
                               value="<?php echo isset($_GET['month']) ? htmlspecialchars($_GET['month']) : ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-filter w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

        <?php if ($isSuperAdmin): ?>
        <!-- Election Administration Tools Section (Super Admin Only) -->
        <h3 class="section-header">
            <i class="fas fa-cog"></i> Election Administration Tools
        </h3>
        
        <div class="content-panel">
            <div class="content-panel-body">
                <div class="admin-tools-container">
                    <div class="admin-tool">
                        <div class="admin-tool-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h4>Diagnostics</h4>
                        <p>Run system checks on election configuration and setup</p>
                        <?php if ($canManageElections): ?>
                        <a href="election_diagnostic.php" class="btn btn-outline-primary">
                            <i class="fas fa-tools me-2"></i> Run Diagnostics
                        </a>
                        <?php else: ?>
                        <button class="btn btn-outline-secondary" disabled>
                            <i class="fas fa-tools me-2"></i> Run Diagnostics (Super Admin Only)
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="admin-tool">
                        <div class="admin-tool-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h4>Vote Privacy</h4>
                        <p>Verify vote privacy and security settings are correctly configured</p>
                        <?php if ($canManageElections): ?>
                        <a href="vote_privacy.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-shield me-2"></i> Check Privacy Settings
                        </a>
                        <?php else: ?>
                        <button class="btn btn-outline-secondary" disabled>
                            <i class="fas fa-user-shield me-2"></i> Check Privacy Settings (Super Admin Only)
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Elections Table Section -->
        <h3 class="section-header">
            <i class="fas fa-list"></i> All Elections
        </h3>

        <!-- Elections Table -->
        <div class="content-panel">
            <div class="content-panel-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Positions</th>
                            <th>Candidates</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($elections)): ?>
                            <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-info-circle me-2 text-muted"></i>
                                        No elections found. Try adjusting your filters or creating a new election.
                                    </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($elections as $election): ?>
                                <?php
                                // Determine if it's in Planning status (upcoming but future start date)
                                $displayStatus = $statusDisplayMap[$election['status']] ?? ucfirst($election['status']);
                                if ($displayStatus === 'Upcoming' && strtotime($election['start_date']) > time()) {
                                    $displayStatus = 'Planning';
                                }
                                ?>
                                <tr>
                                        <td class="fw-medium"><?php echo htmlspecialchars($election['title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($election['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($election['end_date'])); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $election['position_count']; ?></span></td>
                                        <td><span class="badge bg-secondary"><?php echo $election['candidate_count']; ?></span></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $displayStatus === 'Upcoming' ? 'success' : 
                                                ($displayStatus === 'Active' ? 'primary' : 
                                                    ($displayStatus === 'Planning' ? 'warning' : 
                                                        ($displayStatus === 'Nominations Open' ? 'info' :
                                                            ($displayStatus === 'Pending Voting' ? 'secondary' :
                                                                ($displayStatus === 'Completed' ? 'success' : 'secondary')))));
                                        ?> <?php echo str_replace(' ', '-', strtolower($displayStatus)); ?>">
                                            <?php echo htmlspecialchars($displayStatus); ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <a href="election_detail.php?id=<?php echo $election['election_id']; ?>" class="btn btn-action btn-view" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($canManageElections): ?>
                                            <a href="election_edit.php?id=<?php echo $election['election_id']; ?>" class="btn btn-action btn-edit" title="Edit Election">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="elections.php?delete=<?php echo $election['election_id']; ?>" class="btn btn-action btn-delete" title="Delete Election" onclick="return confirm('Are you sure you want to delete this election?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                
                <!-- Pagination placeholder - can be implemented later -->
                <!--
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#">Next</a></li>
                    </ul>
                </nav>
                -->
            </div>
        </div>
    </div>
</div>

<!-- Create Election Modal -->
<?php if ($canManageElections): ?>
<div class="modal fade" id="createElectionModal" tabindex="-1" aria-labelledby="createElectionModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createElectionModalLabel">Create New Election</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="election_handler.php" id="createElectionForm">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="election-title" class="form-label">Election Title</label>
                            <input type="text" class="form-control" id="election-title" name="election_title" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="election-start-date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="election-start-date" name="election_start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="election-end-date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="election-end-date" name="election_end_date" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="election-status" class="form-label">Status</label>
                            <select class="form-select" id="election-status" name="election_status" required>
                                <option value="Planning">Planning</option>
                                <option value="Upcoming">Upcoming</option>
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="election-description" class="form-label">Description</label>
                        <textarea class="form-control" id="election-description" name="election_description" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Positions</label>
                        <div class="position-inputs">
                            <div class="row mb-2 position-input">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="positions[]" placeholder="Position Title" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="seats[]" placeholder="Number of Seats" min="1" value="1" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-secondary w-100 add-position">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Election</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Check for "new" action in URL to show create election modal
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
    const createElectionModal = new bootstrap.Modal(document.getElementById('createElectionModal'), {
        backdrop: false,  // Set to false to prevent the backdrop
        keyboard: true,
        focus: true
    });
    createElectionModal.show();
    <?php endif; ?>
    
    // Add position functionality
    const addPositionBtn = document.querySelector('.add-position');
    const positionInputs = document.querySelector('.position-inputs');
    
    if(addPositionBtn) {
        addPositionBtn.addEventListener('click', function() {
            const newRow = document.createElement('div');
            newRow.className = 'row mb-2 position-input';
            newRow.innerHTML = `
                <div class="col-md-6">
                    <input type="text" class="form-control" name="positions[]" placeholder="Position Title" required>
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control" name="seats[]" placeholder="Number of Seats" min="1" value="1" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger w-100 remove-position">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            `;
            positionInputs.appendChild(newRow);
            
            // Add event listener to the new remove button
            const removeBtn = newRow.querySelector('.remove-position');
            removeBtn.addEventListener('click', function() {
                newRow.remove();
            });
        });
    }
    
    // Event delegation for remove position buttons
    if(positionInputs) {
        positionInputs.addEventListener('click', function(e) {
            if(e.target.closest('.remove-position')) {
                e.target.closest('.position-input').remove();
            }
        });
    }
    
    // Form validation
    const form = document.getElementById('createElectionForm');
    if(form) {
        form.addEventListener('submit', function(event) {
            const startDate = new Date(document.getElementById('election-start-date').value);
            const endDate = new Date(document.getElementById('election-end-date').value);
            
            if (endDate <= startDate) {
                event.preventDefault();
                alert('End date must be after start date.');
                return false;
            }
            
            return true;
        });
    }
    
    // Fix for modal backdrop issues
    const createElectionModalElement = document.getElementById('createElectionModal');
    if (createElectionModalElement) {
        // Configure the modal to not use a backdrop
        const modalOptions = {
            backdrop: false,
            keyboard: true,
            focus: true
        };
        
        // Try to retrieve the modal instance and reconfigure it
        let modalInstance = bootstrap.Modal.getInstance(createElectionModalElement);
        if (!modalInstance) {
            modalInstance = new bootstrap.Modal(createElectionModalElement, modalOptions);
        }
        
        // Add event listeners for the Create Election modal
        createElectionModalElement.addEventListener('hidden.bs.modal', function() {
            // Aggressively remove backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.remove();
            });
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            // Clear any inline styles Bootstrap might have added
            document.body.removeAttribute('style');
        });
    }
    
    // Add hover effects to action buttons
    const actionButtons = document.querySelectorAll('.btn-action');
    actionButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 3px 5px rgba(0,0,0,0.1)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
