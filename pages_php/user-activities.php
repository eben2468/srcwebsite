<?php
// Include authentication file
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../db_functions.php';
require_once '../activity_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if user is admin
if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

// Set page title
$pageTitle = "User Activities - SRC Management System";

// Process activity creation if requested
$createMessage = '';
$createMessageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_activity'])) {
    // Get data from the form
    $userId = isset($_POST['create_user_id']) ? (int)$_POST['create_user_id'] : 0;
    $activityType = isset($_POST['create_activity_type']) ? $_POST['create_activity_type'] : '';
    $description = isset($_POST['create_activity_description']) ? $_POST['create_activity_description'] : '';
    $pageUrl = isset($_POST['create_page_url']) ? $_POST['create_page_url'] : '';
    $ipAddress = isset($_POST['create_ip_address']) ? $_POST['create_ip_address'] : $_SERVER['REMOTE_ADDR'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($userId)) {
        $errors[] = "User is required";
    }
    
    if (empty($activityType)) {
        $errors[] = "Activity type is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    // If no errors, create the activity
    if (empty($errors)) {
        // Get the user details
        $user = fetchOne("SELECT * FROM users WHERE user_id = ?", [$userId]);
        
        if ($user) {
            // Insert into user_activities table
            $sql = "INSERT INTO user_activities (user_id, activity_type, activity_description, page_url, ip_address, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $result = insert($sql, [$userId, $activityType, $description, $pageUrl, $ipAddress]);
            
            if ($result) {
                $createMessage = "Activity created successfully!";
                $createMessageType = "success";
            } else {
                $createMessage = "Failed to create activity. Please try again.";
                $createMessageType = "danger";
            }
        } else {
            $createMessage = "Invalid user selected.";
            $createMessageType = "danger";
        }
    } else {
        $createMessage = "Please fix the following errors: " . implode(", ", $errors);
        $createMessageType = "danger";
    }
}

// Process activity clearing if requested
$clearMessage = '';
$clearMessageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_activities'])) {
    // Get filters from the form
    $clearFilters = [];
    
    if (!empty($_POST['clear_user_id'])) {
        $clearFilters['user_id'] = (int)$_POST['clear_user_id'];
    }
    
    if (!empty($_POST['clear_activity_type'])) {
        $clearFilters['activity_type'] = $_POST['clear_activity_type'];
    }
    
    if (!empty($_POST['clear_start_date'])) {
        $clearFilters['start_date'] = $_POST['clear_start_date'];
    }
    
    if (!empty($_POST['clear_end_date'])) {
        $clearFilters['end_date'] = $_POST['clear_end_date'];
    }
    
    // Attempt to clear activities
    $clearResult = clearUserActivities($clearFilters);
    
    if ($clearResult) {
        $clearMessage = 'Activities cleared successfully!';
        $clearMessageType = 'success';
    } else {
        $clearMessage = 'Failed to clear activities. Please try again.';
        $clearMessageType = 'danger';
    }
}

// Include header
require_once 'includes/header.php';

// Track page view
if (function_exists('trackPageView')) {
    trackPageView($pageTitle);
}

// Initialize filters
$filters = [];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Process filter form
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['filter'])) {
    if (!empty($_GET['username'])) {
        $filters['username'] = $_GET['username'];
    }
    
    if (!empty($_GET['activity_type'])) {
        $filters['activity_type'] = $_GET['activity_type'];
    }
    
    if (!empty($_GET['start_date'])) {
        $filters['start_date'] = $_GET['start_date'];
    }
    
    if (!empty($_GET['end_date'])) {
        $filters['end_date'] = $_GET['end_date'];
    }
    
    if (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $filters['user_id'] = (int)$_GET['user_id'];
    }
}

// Get activity data
$activities = getUserActivities($filters, $perPage, $offset);
$totalActivities = countUserActivities($filters);
$totalPages = ceil($totalActivities / $perPage);

// Get summary data for charts
$activityTypeSummary = getActivityTypeSummary($filters);

// Get the list of users for filter dropdown
$users = fetchAll("SELECT user_id, username, CONCAT(first_name, ' ', last_name) as full_name FROM users ORDER BY username");

// Get activity types for filter dropdown
$activityTypes = [
    'login' => 'Login',
    'logout' => 'Logout',
    'create' => 'Create',
    'update' => 'Update',
    'delete' => 'Delete',
    'view' => 'View'
];
?>

<!-- Page Content -->
<div class="container-fluid">
    <?php 
    // Define page title, icon, and actions for the enhanced header
    $pageTitle = "User Activities";
    $pageIcon = "fa-history";
    $actions = [];
    
    if ($isAdmin || $isMember || isset($canManageUserActivities)) {
        $actions[] = [
            'url' => '#',
            'icon' => 'fa-plus',
            'text' => 'Add New',
            'class' => 'btn-primary',
            'attributes' => 'data-bs-toggle="modal" data-bs-target="#createModal"'
        ];
    }
    
    // Include the enhanced page header
    include_once 'includes/enhanced_page_header.php';
    ?>

<!-- Activity Summary -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="content-card">
            <div class="content-card-header">
                <h3 class="content-card-title">Activity Summary</h3>
            </div>
            <div class="content-card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Activity Type</th>
                                <th class="text-center">Count</th>
                                <th class="text-end">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalCount = 0;
                            foreach ($activityTypeSummary as $summary) {
                                $totalCount += $summary['count'];
                            }
                            
                            foreach ($activityTypeSummary as $summary): 
                                $percentage = $totalCount > 0 ? round(($summary['count'] / $totalCount) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <?php 
                                        $icon = '';
                                        switch ($summary['activity_type']) {
                                            case 'login':
                                                $icon = '<i class="fas fa-sign-in-alt text-success me-2"></i>';
                                                break;
                                            case 'logout':
                                                $icon = '<i class="fas fa-sign-out-alt text-danger me-2"></i>';
                                                break;
                                            case 'create':
                                                $icon = '<i class="fas fa-plus text-primary me-2"></i>';
                                                break;
                                            case 'update':
                                                $icon = '<i class="fas fa-edit text-info me-2"></i>';
                                                break;
                                            case 'delete':
                                                $icon = '<i class="fas fa-trash text-danger me-2"></i>';
                                                break;
                                            case 'view':
                                                $icon = '<i class="fas fa-eye text-secondary me-2"></i>';
                                                break;
                                            default:
                                                $icon = '<i class="fas fa-dot-circle me-2"></i>';
                                        }
                                        echo $icon . ucfirst($summary['activity_type']);
                                    ?>
                                </td>
                                <td class="text-center"><?php echo $summary['count']; ?></td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <div class="progress me-2" style="width: 100px; height: 10px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                                 aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span><?php echo $percentage; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($activityTypeSummary)): ?>
                            <tr>
                                <td colspan="3" class="text-center">No activity data available</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="content-card">
            <div class="content-card-header">
                <h3 class="content-card-title">Filter Activities</h3>
            </div>
            <div class="content-card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo isset($filters['username']) ? htmlspecialchars($filters['username']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="user_id" class="form-label">User</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>" 
                                <?php echo (isset($filters['user_id']) && $filters['user_id'] == $user['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username'] . ' (' . $user['full_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="activity_type" class="form-label">Activity Type</label>
                        <select class="form-select" id="activity_type" name="activity_type">
                            <option value="">All Activities</option>
                            <?php foreach ($activityTypes as $type => $label): ?>
                            <option value="<?php echo $type; ?>" 
                                <?php echo (isset($filters['activity_type']) && $filters['activity_type'] == $type) ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo isset($filters['start_date']) ? htmlspecialchars($filters['start_date']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo isset($filters['end_date']) ? htmlspecialchars($filters['end_date']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-12 text-end">
                        <button type="submit" name="filter" value="1" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i> Apply Filters
                        </button>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-sync-alt me-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Activities List -->
<div class="content-card mb-4">
    <div class="content-card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="content-card-title">User Activities</h3>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary me-2"><?php echo number_format($totalActivities); ?> activities found</span>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#clearActivitiesModal">
                    <i class="fas fa-trash-alt me-1"></i> Clear Activities
                </button>
            </div>
        </div>
    </div>
    <div class="content-card-body">
        <?php if (!empty($createMessage)): ?>
        <div class="alert alert-<?php echo $createMessageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $createMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($clearMessage)): ?>
        <div class="alert alert-<?php echo $clearMessageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $clearMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Activity Type</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Page</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($activity['created_at'])); ?></td>
                        <td>
                            <?php 
                                $fullName = trim($activity['first_name'] . ' ' . $activity['last_name']);
                                echo !empty($fullName) ? htmlspecialchars($fullName) : htmlspecialchars($activity['username']); 
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars(ucfirst($activity['role'] ?? 'Unknown')); ?></td>
                        <td>
                            <?php 
                                $activityTypeLabel = '';
                                $badgeClass = 'bg-secondary';
                                
                                switch ($activity['activity_type']) {
                                    case 'login':
                                        $activityTypeLabel = '<i class="fas fa-sign-in-alt me-1"></i> Login';
                                        $badgeClass = 'bg-success';
                                        break;
                                    case 'logout':
                                        $activityTypeLabel = '<i class="fas fa-sign-out-alt me-1"></i> Logout';
                                        $badgeClass = 'bg-danger';
                                        break;
                                    case 'create':
                                        $activityTypeLabel = '<i class="fas fa-plus me-1"></i> Create';
                                        $badgeClass = 'bg-primary';
                                        break;
                                    case 'update':
                                        $activityTypeLabel = '<i class="fas fa-edit me-1"></i> Update';
                                        $badgeClass = 'bg-info';
                                        break;
                                    case 'delete':
                                        $activityTypeLabel = '<i class="fas fa-trash me-1"></i> Delete';
                                        $badgeClass = 'bg-danger';
                                        break;
                                    case 'view':
                                        $activityTypeLabel = '<i class="fas fa-eye me-1"></i> View';
                                        $badgeClass = 'bg-secondary';
                                        break;
                                    default:
                                        $activityTypeLabel = ucfirst($activity['activity_type']);
                                }
                                
                                echo '<span class="badge ' . $badgeClass . '">' . $activityTypeLabel . '</span>';
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($activity['activity_description']); ?></td>
                        <td><?php echo htmlspecialchars($activity['ip_address'] ?? 'Unknown'); ?></td>
                        <td>
                            <?php 
                                $pageUrl = $activity['page_url'];
                                $pageName = basename($pageUrl);
                                echo htmlspecialchars($pageName); 
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No activities found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Activity pagination">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($filters) ? '&' . http_build_query(array_merge($filters, ['filter' => 1])) : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php
                    // Calculate range of pages to show
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Always show first page
                    if ($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($filters) ? '&' . http_build_query(array_merge($filters, ['filter' => 1])) : '') . '">1</a></li>';
                        if ($startPage > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Show page links
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="?page=' . $i . (!empty($filters) ? '&' . http_build_query(array_merge($filters, ['filter' => 1])) : '') . '">' . $i . '</a></li>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . (!empty($filters) ? '&' . http_build_query(array_merge($filters, ['filter' => 1])) : '') . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($filters) ? '&' . http_build_query(array_merge($filters, ['filter' => 1])) : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Clear Activities Modal -->
<div class="modal fade" id="clearActivitiesModal" tabindex="-1" aria-labelledby="clearActivitiesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearActivitiesModalLabel">Clear User Activities</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. Activities will be permanently deleted.
                    </div>
                    
                    <p>You can optionally filter which activities to clear:</p>
                    
                    <div class="mb-3">
                        <label for="clear_user_id" class="form-label">User</label>
                        <select class="form-select" id="clear_user_id" name="clear_user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo htmlspecialchars($user['username'] . ' (' . $user['full_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="clear_activity_type" class="form-label">Activity Type</label>
                        <select class="form-select" id="clear_activity_type" name="clear_activity_type">
                            <option value="">All Activities</option>
                            <?php foreach ($activityTypes as $type => $label): ?>
                            <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="clear_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="clear_start_date" name="clear_start_date">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="clear_end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="clear_end_date" name="clear_end_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="clear_activities" value="1" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Clear Activities
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Activity Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Create New Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_user_id" class="form-label">User</label>
                        <select class="form-select" id="create_user_id" name="create_user_id" required>
                            <option value="">Select User</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo htmlspecialchars($user['username'] . ' (' . $user['full_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_activity_type" class="form-label">Activity Type</label>
                        <select class="form-select" id="create_activity_type" name="create_activity_type" required>
                            <option value="">Select Activity Type</option>
                            <?php foreach ($activityTypes as $type => $label): ?>
                            <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_activity_description" class="form-label">Description</label>
                        <textarea class="form-control" id="create_activity_description" name="create_activity_description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_page_url" class="form-label">Page URL</label>
                        <input type="text" class="form-control" id="create_page_url" name="create_page_url" placeholder="e.g., dashboard.php">
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="create_ip_address" name="create_ip_address" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_activity" value="1" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create Activity
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>

<script>
// Add confirmation for clearing activities
document.addEventListener('DOMContentLoaded', function() {
    const clearActivitiesForm = document.querySelector('#clearActivitiesModal form');
    if (clearActivitiesForm) {
        clearActivitiesForm.addEventListener('submit', function(event) {
            // Get selected filters
            const userId = document.getElementById('clear_user_id').value;
            const activityType = document.getElementById('clear_activity_type').value;
            const startDate = document.getElementById('clear_start_date').value;
            const endDate = document.getElementById('clear_end_date').value;
            
            // Build confirmation message
            let confirmMsg = 'Are you sure you want to clear';
            
            if (userId) {
                const userOption = document.querySelector(`#clear_user_id option[value="${userId}"]`);
                confirmMsg += ' activities for ' + userOption.textContent;
            } else {
                confirmMsg += ' ALL user activities';
            }
            
            if (activityType) {
                const typeOption = document.querySelector(`#clear_activity_type option[value="${activityType}"]`);
                confirmMsg += ' of type "' + typeOption.textContent + '"';
            }
            
            if (startDate || endDate) {
                confirmMsg += ' from';
                if (startDate) confirmMsg += ' ' + startDate;
                if (startDate && endDate) confirmMsg += ' to';
                if (endDate) confirmMsg += ' ' + endDate;
            }
            
            confirmMsg += '? This action cannot be undone.';
            
            // Show confirmation dialog
            if (!confirm(confirmMsg)) {
                event.preventDefault();
            }
        });
    }
});
</script>
?> 