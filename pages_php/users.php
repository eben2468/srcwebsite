<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/profile_picture_helpers.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_functions.php';
/**
 * Helper function to check if a profile picture file exists and is valid
 * @param string $filename The profile picture filename
 * @return string|null Path to the profile picture if it exists, null otherwise
 */
// Legacy function - now using profile_picture_helpers.php
function getValidProfilePicturePath($filename) {
    if (empty($filename)) {
        return null;
    }
    
    // Check in the primary location
    if (file_exists("../images/profiles/" . $filename)) {
        return "../images/profiles/" . $filename;
    }
    
    // Check in the legacy location
    if (file_exists("../../uploads/profile_pictures/" . $filename)) {
        return "../../uploads/profile_pictures/" . $filename;
    }
    
    return null;
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if user is super admin (only super admins can access users page)
if (!isSuperAdmin()) {
    header("Location: dashboard.php");
    exit();
}

// Check if we should redirect to create user page
if (isset($_GET['action']) && $_GET['action'] === 'new') {
    header("Location: create_user.php");
    exit();
}

// Set page title
$pageTitle = "Users - SRC Management System";

// Include header
require_once 'includes/header.php';

// Add mobile optimization CSS for users page
echo '<link rel="stylesheet" href="../css/users-mobile-optimization.css">';

// Track page view
if (function_exists('trackPageView')) {
    trackPageView($pageTitle);
}

// Get current user info
$user = getCurrentUser();
$isSuperAdmin = isSuperAdmin();

// Process actions
$message = '';
$messageType = '';

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    
    // Check if trying to delete self
    if ($user['user_id'] == $userId) {
        $message = "You cannot delete your own account.";
        $messageType = "danger";
    } else {
        // Delete user
        $deleteSql = "DELETE FROM users WHERE user_id = ?";
        
        if (executeQuery($deleteSql, [$userId])) {
            $message = "User deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to delete user. Please try again.";
            $messageType = "danger";
        }
    }
}

// Handle status change
if (isset($_GET['status']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $status = $_GET['status'];
    
    // Validate status
    if (in_array($status, ['Active', 'Inactive', 'Suspended'])) {
        // Check if trying to change own status
        if ($user['user_id'] == $userId) {
            $message = "You cannot change your own status.";
            $messageType = "danger";
        } else {
            // Update status
            $updateSql = "UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?";
            
            if (executeQuery($updateSql, [$status, $userId])) {
                $message = "User status updated successfully.";
                $messageType = "success";
            } else {
                $message = "Failed to update user status. Please try again.";
                $messageType = "danger";
            }
        }
    }
}

// Handle form submission to add a new user
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword)) {
        $message = "All fields are required.";
        $messageType = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $messageType = "danger";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "danger";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = "danger";
    } else {
        // Check if email already exists
        $checkEmailSql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $result = fetchOne($checkEmailSql, [$email]);
        
        if ($result && $result['count'] > 0) {
            $message = "Email already exists. Please use a different email address.";
            $messageType = "danger";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Generate username from email (part before @)
            $username = strtolower(explode('@', $email)[0]);

            // Check if username already exists and make it unique if needed
            $originalUsername = $username;
            $counter = 1;
            while (true) {
                $checkUsernameSql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
                $result = fetchOne($checkUsernameSql, [$username]);

                if (!$result || $result['count'] == 0) {
                    break; // Username is available
                }

                // Username exists, try with a number suffix
                $username = $originalUsername . $counter;
                $counter++;
            }

            // Insert new user
            $insertSql = "INSERT INTO users (username, email, password, first_name, last_name, phone, role, status, created_at, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $params = [$username, $email, $hashedPassword, $firstName, $lastName, $phone, $role, $status];
            
            if (executeQuery($insertSql, $params)) {
                // Get the new user ID
                $userId = mysqli_insert_id($conn);
                
                // Create user profile
                $profileSql = "INSERT INTO user_profiles (user_id, full_name, phone, created_at) VALUES (?, ?, ?, NOW())";
                insert($profileSql, [$userId, $firstName . ' ' . $lastName, $phone]);
                
                $message = "User created successfully.";
                $messageType = "success";
            } else {
                $message = "Failed to create user. Please try again.";
                $messageType = "danger";
            }
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build query
$sql = "SELECT u.*, p.phone FROM users u LEFT JOIN user_profiles p ON u.user_id = p.user_id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($roleFilter)) {
    $sql .= " AND u.role = ?";
    $params[] = $roleFilter;
}

if (!empty($statusFilter)) {
    $sql .= " AND u.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY u.created_at DESC";

// Get users from database
$users = fetchAll($sql, $params);
?>

<!-- Page Content -->
<div class="container-fluid">
    <script>
        document.body.classList.add('users-page');
    </script>

    <!-- Custom Users Header -->
    <div class="users-header animate__animated animate__fadeInDown">
        <div class="users-header-content">
            <div class="users-header-main">
                <h1 class="users-title">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </h1>
                <p class="users-description">Manage system users, roles, and permissions</p>
            </div>
            <!-- User Management Actions - Only for Super Admin -->
            <?php if ($isSuperAdmin): ?>
            <div class="users-header-actions">
                <a href="create_user.php" class="btn btn-header-action" title="Add New User">
                    <i class="fas fa-user-plus"></i>
                    <span>Add User</span>
                </a>
                <a href="bulk-users.php" class="btn btn-header-action" title="Bulk User Actions">
                    <i class="fas fa-tasks"></i>
                    <span>Bulk Actions</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>



    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>


<!-- Filters and Search -->
<div class="content-card filter-card animate-fadeIn mb-4">
    <div class="content-card-header">
        <h5 class="mb-0">
            <i class="fas fa-search me-2"></i>Search & Filter Users
        </h5>
    </div>
    <div class="content-card-body">
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="users-filter-form">
            <div class="row g-3 align-items-end">
                <!-- Search Input -->
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="search" class="form-label d-none d-md-block">Search</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="search"
                            name="search" 
                            placeholder="Search users by name, email, or phone..." 
                            value="<?php echo htmlspecialchars($search); ?>"
                            aria-label="Search users"
                        >
                    </div>
                </div>

                <!-- Role Filter -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="role" class="form-label d-none d-md-block">Role</label>
                    <select class="form-select" id="role" name="role" aria-label="Filter by role">
                        <option value="">All Roles</option>
                        <option value="super_admin" <?php echo $roleFilter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="member" <?php echo $roleFilter === 'member' ? 'selected' : ''; ?>>Member</option>
                        <option value="finance" <?php echo $roleFilter === 'finance' ? 'selected' : ''; ?>>Finance</option>
                        <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Student</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="status" class="form-label d-none d-md-block">Status</label>
                    <select class="form-select" id="status" name="status" aria-label="Filter by status">
                        <option value="">All Statuses</option>
                        <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $statusFilter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="Suspended" <?php echo $statusFilter === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>

                <!-- Filter Button -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <button type="submit" class="btn btn-outline-secondary w-100" aria-label="Apply filters">
                        <i class="fas fa-filter me-1"></i>
                        <span class="d-none d-md-inline">Filter</span>
                        <span class="d-inline d-md-none">Apply</span>
                    </button>
                </div>

                <!-- Clear Button -->
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-outline-secondary w-100" aria-label="Clear all filters">
                        <i class="fas fa-times me-1"></i>
                        <span class="d-none d-md-inline">Clear</span>
                        <span class="d-inline d-md-none">Reset</span>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="content-card animate-fadeIn" role="region" aria-label="Users list">
    <div class="content-card-header">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>All Users
            </h5>
            <span class="badge bg-secondary">
                <?php echo count($users); ?> Users
            </span>
        </div>
    </div>
    <div class="content-card-body p-0">
        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-striped table-hover align-middle mb-0" id="usersTable" role="table">
                <thead>
                    <tr>
                        <th scope="col" class="ps-4">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col" class="d-none d-lg-table-cell">Phone</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="d-none d-xl-table-cell">Created</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <?php
                                // Display profile picture using helper function
                                echo displayProfilePicture($user, 'pages_php', [
                                    'width' => 40,
                                    'height' => 40,
                                    'class' => 'rounded-circle'
                                ]);
                                ?>
                                <div>
                                    <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">ID: <?php echo $user['user_id']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </a>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <small><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?php
                                echo $user['role'] === 'super_admin' ? 'dark' :
                                    ($user['role'] === 'admin' ? 'danger' :
                                    ($user['role'] === 'member' ? 'primary' :
                                    ($user['role'] === 'finance' ? 'success' : 'secondary')));
                            ?>">
                                <?php echo $user['role'] === 'super_admin' ? 'ADMIN' : strtoupper($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $user['status'] === 'Active' || $user['status'] === 'active' ? 'success' : 
                                    ($user['status'] === 'Inactive' || $user['status'] === 'inactive' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td class="d-none d-xl-table-cell">
                            <small class="text-muted"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                        </td>
                        <td class="text-center">
                            <!-- User Actions - Only for Super Admin -->
                            <?php if ($isSuperAdmin): ?>
                            <div class="btn-group" role="group" aria-label="User actions">
                                <a href="user-edit.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit User" aria-label="Edit <?php echo htmlspecialchars($user['first_name']); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <?php if ($user['status'] === 'Active' || $user['status'] === 'active'): ?>
                                <a href="?status=Inactive&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-warning" title="Deactivate User" aria-label="Deactivate user" onclick="return confirm('Are you sure you want to deactivate this user?')">
                                    <i class="fas fa-ban"></i>
                                </a>
                                <?php else: ?>
                                <a href="?status=Active&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-success" title="Activate User" aria-label="Activate user" onclick="return confirm('Are you sure you want to activate this user?')">
                                    <i class="fas fa-check"></i>
                                </a>
                                <?php endif; ?>

                                <?php if ($user['user_id'] != $currentUser['user_id']): ?>
                                <a href="?delete=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete User" aria-label="Delete user" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-muted">
                                <small><i class="fas fa-lock me-1"></i>Super Admin Only</small>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="d-block d-md-none">
            <?php if (empty($users)): ?>
            <div class="text-center py-5 px-3">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem; display: block;"></i>
                <h5 class="text-muted">No Users Found</h5>
                <p class="text-muted">Try adjusting your search or filter criteria.</p>
            </div>
            <?php else: ?>
            <?php foreach ($users as $user): ?>
            <div class="user-card">
                <div class="user-card-header">
                    <div class="d-flex align-items-center gap-3" style="flex: 1; min-width: 0;">
                        <!-- Profile Picture -->
                        <div style="flex-shrink: 0; width: 50px; height: 50px;">
                            <?php
                            // Display profile picture using helper function
                            echo displayProfilePicture($user, 'pages_php', [
                                'width' => 50,
                                'height' => 50,
                                'class' => 'rounded-circle'
                            ]);
                            ?>
                        </div>
                        <!-- User Info -->
                        <div style="flex: 1; min-width: 0;">
                            <h6 class="mb-1" style="font-size: 1.1rem; font-weight: 600; color: #2c3e50; margin: 0; word-break: break-word;">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </h6>
                            <small class="text-muted" style="display: block; margin: 0;">
                                <i class="fas fa-id-card me-1"></i>ID: <?php echo $user['user_id']; ?>
                            </small>
                        </div>
                    </div>
                    <!-- Badges -->
                    <div class="user-badges" style="flex-shrink: 0; text-align: right;">
                        <span class="badge bg-<?php
                            echo $user['role'] === 'super_admin' ? 'dark' :
                                ($user['role'] === 'admin' ? 'danger' :
                                ($user['role'] === 'member' ? 'primary' :
                                ($user['role'] === 'finance' ? 'success' : 'secondary')));
                        ?>" style="white-space: nowrap; display: inline-block;">
                            <?php echo $user['role'] === 'super_admin' ? 'ADMIN' : strtoupper($user['role']); ?>
                        </span>
                        <br>
                        <span class="badge bg-<?php 
                            echo $user['status'] === 'Active' || $user['status'] === 'active' ? 'success' : 
                                ($user['status'] === 'Inactive' || $user['status'] === 'inactive' ? 'warning' : 'danger'); 
                        ?>" style="white-space: nowrap; display: inline-block;">
                            <?php echo strtoupper($user['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="user-card-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <strong>
                                <i class="fas fa-envelope me-2" style="color: #667eea;"></i>Email
                            </strong>
                            <br>
                            <small>
                                <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </a>
                            </small>
                        </div>
                        <div class="col-6">
                            <strong>
                                <i class="fas fa-phone me-2" style="color: #667eea;"></i>Phone
                            </strong>
                            <br>
                            <small><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></small>
                        </div>
                        <div class="col-6">
                            <strong>
                                <i class="fas fa-calendar me-2" style="color: #667eea;"></i>Created
                            </strong>
                            <br>
                            <small><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                        </div>
                    </div>
                </div>
                <?php if ($isSuperAdmin): ?>
                <div class="user-card-actions">
                    <a href="user-edit.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit User" aria-label="Edit user">
                        <i class="fas fa-edit"></i>
                        <span class="d-none d-sm-inline ms-1">Edit</span>
                    </a>
                    <?php if ($user['status'] === 'Active' || $user['status'] === 'active'): ?>
                    <a href="?status=Inactive&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-warning" aria-label="Deactivate user" onclick="return confirm('Are you sure you want to deactivate this user?')">
                        <i class="fas fa-ban"></i>
                        <span class="d-none d-sm-inline ms-1">Deactivate</span>
                    </a>
                    <?php else: ?>
                    <a href="?status=Active&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-success" aria-label="Activate user" onclick="return confirm('Are you sure you want to activate this user?')">
                        <i class="fas fa-check"></i>
                        <span class="d-none d-sm-inline ms-1">Activate</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($user['user_id'] != $currentUser['user_id']): ?>
                    <a href="?delete=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-danger" aria-label="Delete user" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                        <i class="fas fa-trash"></i>
                        <span class="d-none d-sm-inline ms-1">Delete</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createUserModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Create New User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="createUserForm" class="needs-validation">
                    <!-- First & Last Name Row -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">
                                <i class="fas fa-user me-1" style="color: #667eea;"></i>First Name
                            </label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required aria-required="true">
                            <div class="invalid-feedback">First name is required.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">
                                <i class="fas fa-user me-1" style="color: #667eea;"></i>Last Name
                            </label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required aria-required="true">
                            <div class="invalid-feedback">Last name is required.</div>
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1" style="color: #667eea;"></i>Email Address
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required aria-required="true">
                        <div class="invalid-feedback">Please provide a valid email address.</div>
                    </div>
                    
                    <!-- Phone -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone me-1" style="color: #667eea;"></i>Phone Number
                        </label>
                        <input type="tel" class="form-control" id="phone" name="phone" required aria-required="true" placeholder="0241234567 or +233241234567">
                        <small class="text-muted">Format: 0241234567 or +233241234567</small>
                        <div class="invalid-feedback">Phone number is required.</div>
                    </div>
                    
                    <!-- Password Row -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1" style="color: #667eea;"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required aria-required="true" minlength="8">
                            <div class="form-text">Must be at least 8 characters long.</div>
                            <div class="invalid-feedback">Password is required.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-1" style="color: #667eea;"></i>Confirm Password
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required aria-required="true" minlength="8">
                            <div class="invalid-feedback">Please confirm your password.</div>
                        </div>
                    </div>
                    
                    <!-- Role & Status Row -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-shield me-1" style="color: #667eea;"></i>Role
                            </label>
                            <select class="form-select" id="role" name="role" required aria-required="true">
                                <option value="">Select a role</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="admin">Admin</option>
                                <option value="member">Member</option>
                                <option value="finance">Finance</option>
                                <option value="student" selected>Student</option>
                            </select>
                            <div class="invalid-feedback">Please select a role.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">
                                <i class="fas fa-toggle-on me-1" style="color: #667eea;"></i>Status
                            </label>
                            <select class="form-select" id="status" name="status" required aria-required="true">
                                <option value="">Select a status</option>
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                            <div class="invalid-feedback">Please select a status.</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="createUserForm" class="btn btn-primary">
                    <i class="fas fa-check me-2"></i>Create User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">
                    <i class="fas fa-key me-2" style="color: #667eea;"></i>Reset Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="user_handler.php" id="resetPasswordForm" class="needs-validation">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="modal-body">
                    <p class="mb-3">
                        You are about to reset the password for <strong id="reset_user_name" style="color: #667eea;"></strong>.
                    </p>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        The new password must be at least 8 characters long.
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">
                            <i class="fas fa-lock me-1" style="color: #667eea;"></i>New Password
                        </label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required aria-required="true" minlength="8">
                        <div class="invalid-feedback">Password must be at least 8 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_new_password" class="form-label">
                            <i class="fas fa-lock me-1" style="color: #667eea;"></i>Confirm New Password
                        </label>
                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_password" required aria-required="true" minlength="8">
                        <div class="invalid-feedback">Please confirm your password.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mobile optimization script -->
<script src="../js/users-mobile-test.js"></script>

<!-- Add this before the closing body tag -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle reset password modal
    const resetPasswordModal = document.getElementById('resetPasswordModal');
    if (resetPasswordModal) {
        resetPasswordModal.addEventListener('show.bs.modal', function(event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract info from data-* attributes
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            
            // Update the modal's content
            const userIdInput = document.getElementById('reset_user_id');
            const userNameSpan = document.getElementById('reset_user_name');
            
            userIdInput.value = userId;
            userNameSpan.textContent = userName;
        });
    }
    
    // Password confirmation validation
    const passwordForm = document.querySelector('#createUserModal form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(event) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password.value !== confirmPassword.value) {
                event.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    }
    
    // Reset password validation
    const resetPasswordForm = document.querySelector('#resetPasswordModal form');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(event) {
            const newPassword = document.getElementById('new_password');
            const confirmNewPassword = document.getElementById('confirm_new_password');
            
            if (newPassword.value !== confirmNewPassword.value) {
                event.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    }
});
</script>

</div> <!-- Close container-fluid -->

<?php require_once 'includes/footer.php'; ?>
