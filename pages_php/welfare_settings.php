<?php
// Include required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Require login and admin/member access (including super admin)
requireLogin();
if (!shouldUseAdminInterface() && !isMember()) {
    $_SESSION['error'] = "You don't have permission to access welfare settings.";
    header("Location: welfare.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_settings':
            handleUpdateSettings();
            break;
        case 'add_category':
            handleAddCategory();
            break;
        case 'update_category':
            handleUpdateCategory();
            break;
        case 'toggle_category':
            handleToggleCategory();
            break;
        case 'delete_category':
            handleDeleteCategory();
            break;
        default:
            $_SESSION['error'] = "Invalid action.";
            break;
    }
    
    header("Location: welfare_settings.php");
    exit();
}

// Get current settings
$settings = [];
$settingsData = fetchAll("SELECT * FROM welfare_settings ORDER BY setting_key");
foreach ($settingsData as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// Get welfare categories
$categories = fetchAll("SELECT * FROM welfare_categories ORDER BY category_name");

// Get welfare statistics for display
$stats = [
    'total_requests' => fetchOne("SELECT COUNT(*) as count FROM welfare_requests")['count'] ?? 0,
    'pending_requests' => fetchOne("SELECT COUNT(*) as count FROM welfare_requests WHERE status = 'pending'")['count'] ?? 0,
    'active_categories' => fetchOne("SELECT COUNT(*) as count FROM welfare_categories WHERE is_active = 1")['count'] ?? 0,
    'total_announcements' => fetchOne("SELECT COUNT(*) as count FROM welfare_announcements")['count'] ?? 0
];

function handleUpdateSettings() {
    $settingsToUpdate = [
        'max_file_size',
        'allowed_file_types',
        'auto_approval_threshold',
        'notification_email',
        'request_expiry_days',
        'enable_file_uploads',
        'enable_public_announcements',
        'max_requests_per_month'
    ];
    
    $updatedCount = 0;
    foreach ($settingsToUpdate as $key) {
        if (isset($_POST[$key])) {
            $value = $_POST[$key];
            
            // Validate specific settings
            if ($key === 'max_file_size' && !is_numeric($value)) {
                continue;
            }
            if ($key === 'notification_email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            
            $sql = "UPDATE welfare_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?";
            if (execute($sql, [$value, $_SESSION['user_id'], $key])) {
                $updatedCount++;
            }
        }
    }
    
    if ($updatedCount > 0) {
        $_SESSION['success'] = "Settings updated successfully. ($updatedCount settings changed)";
    } else {
        $_SESSION['error'] = "No settings were updated.";
    }
}

function handleAddCategory() {
    $categoryName = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($categoryName)) {
        $_SESSION['error'] = "Category name is required.";
        return;
    }
    
    // Check if category already exists
    $existing = fetchOne("SELECT category_id FROM welfare_categories WHERE category_name = ?", [$categoryName]);
    if ($existing) {
        $_SESSION['error'] = "Category already exists.";
        return;
    }
    
    $sql = "INSERT INTO welfare_categories (category_name, description, is_active, created_at) VALUES (?, ?, 1, NOW())";
    if (execute($sql, [$categoryName, $description])) {
        $_SESSION['success'] = "Category added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add category.";
    }
}

function handleUpdateCategory() {
    $categoryId = $_POST['category_id'] ?? '';
    $categoryName = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($categoryId) || empty($categoryName)) {
        $_SESSION['error'] = "Category ID and name are required.";
        return;
    }
    
    $sql = "UPDATE welfare_categories SET category_name = ?, description = ? WHERE category_id = ?";
    if (execute($sql, [$categoryName, $description, $categoryId])) {
        $_SESSION['success'] = "Category updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update category.";
    }
}

function handleToggleCategory() {
    $categoryId = $_POST['category_id'] ?? '';
    $isActive = $_POST['is_active'] ?? '0';

    if (empty($categoryId)) {
        $_SESSION['error'] = "Category ID is required.";
        return;
    }

    $sql = "UPDATE welfare_categories SET is_active = ? WHERE category_id = ?";
    if (execute($sql, [$isActive, $categoryId])) {
        $status = $isActive ? 'activated' : 'deactivated';
        $_SESSION['success'] = "Category $status successfully.";
    } else {
        $_SESSION['error'] = "Failed to update category status.";
    }
}

function handleDeleteCategory() {
    $categoryId = $_POST['category_id'] ?? '';

    if (empty($categoryId)) {
        $_SESSION['error'] = "Category ID is required.";
        return;
    }

    // Get category name for checking requests
    $category = fetchOne("SELECT category_name FROM welfare_categories WHERE category_id = ?", [$categoryId]);
    if (!$category) {
        $_SESSION['error'] = "Category not found.";
        return;
    }

    // Check if category has any associated requests (welfare_requests uses request_type, not category_id)
    // We'll check if any requests use this category name as request_type
    $categoryName = strtolower(str_replace(' ', '_', $category['category_name']));
    $requestCount = fetchOne("SELECT COUNT(*) as count FROM welfare_requests WHERE request_type = ?", [$categoryName])['count'] ?? 0;

    if ($requestCount > 0) {
        $_SESSION['error'] = "Cannot delete category. It has $requestCount associated requests. Please reassign or delete those requests first.";
        return;
    }

    $sql = "DELETE FROM welfare_categories WHERE category_id = ?";
    if (execute($sql, [$categoryId])) {
        $_SESSION['success'] = "Category deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete category.";
    }
}

// Page header data
$pageTitle = "Welfare Settings";
$pageDescription = "Configure welfare system settings and manage categories";
$actions = [
    [
        'text' => 'View Reports',
        'href' => '#',
        'icon' => 'fas fa-chart-bar',
        'class' => 'btn-info',
        'data-bs-toggle' => 'modal',
        'data-bs-target' => '#reportsModal'
    ]
];
$backButton = [
    'text' => 'Back to Welfare',
    'href' => 'welfare.php',
    'icon' => 'fas fa-arrow-left'
];

include 'includes/header.php';
include 'includes/modern_page_header.php';
?>

<main class="main-content" style="margin-left: 0 !important; padding-left: 0 !important;">
    <div class="container-fluid" style="margin-left: 0 !important; padding-left: 0 !important; max-width: 100% !important;">

        <!-- Statistics Cards -->
        <div class="row mb-4" style="margin-left: 0 !important; padding-left: 15px; padding-right: 15px;">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Requests</h6>
                                <h3 class="mb-0"><?php echo $stats['total_requests']; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clipboard-list fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Pending Requests</h6>
                                <h3 class="mb-0"><?php echo $stats['pending_requests']; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Active Categories</h6>
                                <h3 class="mb-0"><?php echo $stats['active_categories']; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-tags fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Announcements</h6>
                                <h3 class="mb-0"><?php echo $stats['total_announcements']; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-bullhorn fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" style="margin-left: 0 !important; padding-left: 15px; padding-right: 15px;">
            <!-- Settings Configuration -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog me-2"></i>System Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_file_size" class="form-label">Max File Size (bytes)</label>
                                        <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                               value="<?php echo htmlspecialchars($settings['max_file_size'] ?? '5242880'); ?>">
                                        <div class="form-text">Current: <?php echo number_format(($settings['max_file_size'] ?? 5242880) / 1024 / 1024, 1); ?> MB</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="allowed_file_types" class="form-label">Allowed File Types</label>
                                        <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types" 
                                               value="<?php echo htmlspecialchars($settings['allowed_file_types'] ?? 'pdf,doc,docx,jpg,jpeg,png'); ?>">
                                        <div class="form-text">Comma-separated list (e.g., pdf,doc,jpg)</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="notification_email" class="form-label">Notification Email</label>
                                        <input type="email" class="form-control" id="notification_email" name="notification_email" 
                                               value="<?php echo htmlspecialchars($settings['notification_email'] ?? 'welfare@vvusrc.edu'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="request_expiry_days" class="form-label">Request Expiry (days)</label>
                                        <input type="number" class="form-control" id="request_expiry_days" name="request_expiry_days" 
                                               value="<?php echo htmlspecialchars($settings['request_expiry_days'] ?? '30'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="auto_approval_threshold" class="form-label">Auto Approval Threshold (₵)</label>
                                        <input type="number" class="form-control" id="auto_approval_threshold" name="auto_approval_threshold" 
                                               value="<?php echo htmlspecialchars($settings['auto_approval_threshold'] ?? '1000'); ?>">
                                        <div class="form-text">Requests below this amount may be auto-approved</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_requests_per_month" class="form-label">Max Requests per Month</label>
                                        <input type="number" class="form-control" id="max_requests_per_month" name="max_requests_per_month" 
                                               value="<?php echo htmlspecialchars($settings['max_requests_per_month'] ?? '3'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_file_uploads" name="enable_file_uploads" value="1"
                                                   <?php echo ($settings['enable_file_uploads'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_file_uploads">
                                                Enable File Uploads
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_public_announcements" name="enable_public_announcements" value="1"
                                                   <?php echo ($settings['enable_public_announcements'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_public_announcements">
                                                Enable Public Announcements
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus me-2"></i>Add Category
                            </button>
                            <button class="btn btn-outline-info" onclick="exportSettings()">
                                <i class="fas fa-download me-2"></i>Export Settings
                            </button>
                            <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#backupModal">
                                <i class="fas fa-database me-2"></i>Backup Data
                            </button>
                            <button class="btn btn-outline-danger" onclick="clearOldRequests()">
                                <i class="fas fa-trash me-2"></i>Clear Old Requests
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>System Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <p><strong>Upload Directory:</strong> uploads/welfare/</p>
                            <p><strong>Max Upload Size:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
                            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                            <p><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Categories Management -->
        <div class="row mt-4" style="margin-left: 0 !important; padding-left: 15px; padding-right: 15px;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tags me-2"></i>Welfare Categories
                        </h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-1"></i>Add Category
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($category['description'] ?? 'No description'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $category['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>', '<?php echo htmlspecialchars($category['description']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-<?php echo $category['is_active'] ? 'warning' : 'success'; ?>" onclick="toggleCategory(<?php echo $category['category_id']; ?>, <?php echo $category['is_active'] ? '0' : '1'; ?>)">
                                                <i class="fas fa-<?php echo $category['is_active'] ? 'pause' : 'play'; ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</main>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_category">

                    <div class="mb-3">
                        <label for="new_category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="new_category_name" name="category_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_category_description" class="form-label">Description</label>
                        <textarea class="form-control" id="new_category_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="category_id" id="edit_category_id">

                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_category_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_category_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reports Modal -->
<div class="modal fade" id="reportsModal" tabindex="-1" aria-labelledby="reportsModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportsModalLabel">Welfare System Reports</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                                <h6>Usage Statistics</h6>
                                <p class="text-muted">Generate detailed usage statistics</p>
                                <button class="btn btn-primary" onclick="generateReport('usage')">Generate</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-pie fa-3x text-success mb-3"></i>
                                <h6>Category Analysis</h6>
                                <p class="text-muted">Analyze requests by category</p>
                                <button class="btn btn-success" onclick="generateReport('categories')">Generate</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-info mb-3"></i>
                                <h6>Monthly Trends</h6>
                                <p class="text-muted">View monthly request trends</p>
                                <button class="btn btn-info" onclick="generateReport('trends')">Generate</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-file-export fa-3x text-warning mb-3"></i>
                                <h6>Export All Data</h6>
                                <p class="text-muted">Export complete welfare data</p>
                                <button class="btn btn-warning" onclick="exportAllData()">Export</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backup Modal -->
<div class="modal fade" id="backupModal" tabindex="-1" aria-labelledby="backupModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="backupModalLabel">Backup Welfare Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>This will create a backup of all welfare system data including:</p>
                <ul>
                    <li>Welfare requests and responses</li>
                    <li>Categories and settings</li>
                    <li>Announcements</li>
                    <li>User comments</li>
                </ul>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    The backup will be saved as a SQL file that can be imported later.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="createBackup()">Create Backup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Edit category function
function editCategory(categoryId, categoryName, description) {
    document.getElementById('edit_category_id').value = categoryId;
    document.getElementById('edit_category_name').value = categoryName;
    document.getElementById('edit_category_description').value = description;

    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

// Toggle category status
function toggleCategory(categoryId, newStatus) {
    const action = newStatus ? 'activate' : 'deactivate';
    if (confirm(`Are you sure you want to ${action} this category?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'toggle_category';

        const categoryIdInput = document.createElement('input');
        categoryIdInput.type = 'hidden';
        categoryIdInput.name = 'category_id';
        categoryIdInput.value = categoryId;

        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'is_active';
        statusInput.value = newStatus;

        form.appendChild(actionInput);
        form.appendChild(categoryIdInput);
        form.appendChild(statusInput);

        document.body.appendChild(form);
        form.submit();
    }
}

// Delete category function
function deleteCategory(categoryId, categoryName) {
    if (confirm(`Are you sure you want to permanently delete the category "${categoryName}"? This action cannot be undone and will fail if there are associated requests.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_category';

        const categoryIdInput = document.createElement('input');
        categoryIdInput.type = 'hidden';
        categoryIdInput.name = 'category_id';
        categoryIdInput.value = categoryId;

        form.appendChild(actionInput);
        form.appendChild(categoryIdInput);

        document.body.appendChild(form);
        form.submit();
    }
}

// Export settings
function exportSettings() {
    window.open('welfare_handler.php?action=export_settings', '_blank');
}

// Generate reports
function generateReport(type) {
    window.open(`welfare_handler.php?action=generate_report&type=${type}`, '_blank');
}

// Export all data
function exportAllData() {
    window.open('welfare_handler.php?action=export_all_data', '_blank');
}

// Create backup
function createBackup() {
    if (confirm('This will create a complete backup of the welfare system. Continue?')) {
        window.open('welfare_handler.php?action=create_backup', '_blank');
    }
}

// Clear old requests
function clearOldRequests() {
    const days = prompt('Delete requests older than how many days? (Enter number)', '90');
    if (days && !isNaN(days) && days > 0) {
        if (confirm(`This will permanently delete all requests older than ${days} days. This action cannot be undone. Continue?`)) {
            fetch('welfare_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=clear_old_requests&days=${days}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully deleted ${data.deleted_count} old requests.`);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error clearing old requests');
            });
        }
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    // Validate file size input
    const fileSizeInput = document.getElementById('max_file_size');
    if (fileSizeInput) {
        fileSizeInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            const mbValue = value / 1024 / 1024;
            const helpText = this.parentNode.querySelector('.form-text');
            if (helpText) {
                helpText.textContent = `Current: ${mbValue.toFixed(1)} MB`;
            }
        });
    }

    // Auto-save settings on change (optional)
    const settingsForm = document.querySelector('form[action=""]');
    if (settingsForm) {
        const inputs = settingsForm.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.type === 'checkbox') {
                input.addEventListener('change', function() {
                    // Auto-save checkbox changes
                    const formData = new FormData();
                    formData.append('action', 'update_settings');
                    formData.append(this.name, this.checked ? '1' : '0');

                    fetch('welfare_settings.php', {
                        method: 'POST',
                        body: formData
                    }).then(() => {
                        // Show brief success indicator
                        const indicator = document.createElement('span');
                        indicator.className = 'text-success ms-2';
                        indicator.innerHTML = '<i class="fas fa-check"></i>';
                        this.parentNode.appendChild(indicator);
                        setTimeout(() => indicator.remove(), 2000);
                    });
                });
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
