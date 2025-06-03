<?php
// Include authentication file
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../db_functions.php';
require_once '../settings_functions.php';

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

// Check if we should redirect to create user page
if (isset($_GET['action']) && $_GET['action'] === 'new') {
    header("Location: create_user.php");
    exit();
}

// Set page title
$pageTitle = "Users - SRC Management System";

// Include header
require_once 'includes/header.php';

// Track page view
if (function_exists('trackPageView')) {
    trackPageView($pageTitle);
}

// Get current user info
$user = getCurrentUser();
$isAdmin = isAdmin();

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
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
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
        // Check if username already exists
        $checkUsernameSql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
        $result = fetchOne($checkUsernameSql, [$username]);
        
        if ($result && $result['count'] > 0) {
            $message = "Username already exists. Please choose a different username.";
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
                
                // Insert new user
                $insertSql = "INSERT INTO users (username, password, email, first_name, last_name, role, status, created_at, updated_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $params = [$username, $hashedPassword, $email, $firstName, $lastName, $role, $status];
                
                if (executeQuery($insertSql, $params)) {
                    $message = "User created successfully.";
                    $messageType = "success";
                } else {
                    $message = "Failed to create user. Please try again.";
                    $messageType = "danger";
                }
            }
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($roleFilter)) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}

if (!empty($statusFilter)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY created_at DESC";

// Get users from database
$users = fetchAll($sql, $params);
?>

<!-- Page Content -->
<div class="container-fluid">
<?php if (!empty($message)): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

    <?php 
    // Define page title, icon, and actions for the enhanced header
    $pageTitle = "Users";
    $pageIcon = "fa-users";
    $actions = [];
    
    if ($isAdmin || $isMember || isset($canManageUsers)) {
        $actions[] = [
            'url' => '#',
            'icon' => 'fa-plus',
            'text' => 'Add New',
            'class' => 'btn-primary',
            'attributes' => 'data-bs-toggle="modal" data-bs-target="#createUserModal"'
        ];
    }
    
    // Include the enhanced page header
    include_once 'includes/enhanced_page_header.php';
    ?>

<!-- Filters and Search -->
<div class="content-card animate-fadeIn mb-4">
    <div class="content-card-body">
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        <option value="member" <?php echo $roleFilter === 'member' ? 'selected' : ''; ?>>Member</option>
                        <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Student</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $statusFilter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="Suspended" <?php echo $statusFilter === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="content-card animate-fadeIn">
    <div class="content-card-header">
        <h5 class="mb-0">All Users</h5>
    </div>
    <div class="content-card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users && count($users) > 0): ?>
                        <?php foreach ($users as $user_item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($user_item['profile_picture']) && file_exists('../images/profiles/' . $user_item['profile_picture'])): ?>
                                        <img src="../images/profiles/<?php echo htmlspecialchars($user_item['profile_picture']); ?>" 
                                             class="rounded-circle me-2" 
                                             style="width: 32px; height: 32px; object-fit: cover;" 
                                             alt="Profile Picture">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle me-2 text-secondary" style="font-size: 1.2rem;"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($user_item['first_name'] . ' ' . $user_item['last_name']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user_item['username']); ?></td>
                            <td><?php echo htmlspecialchars($user_item['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $user_item['role'] === 'admin' ? 'danger' : 
                                        ($user_item['role'] === 'member' ? 'primary' : 'info'); 
                                ?>">
                                    <?php echo $user_item['role'] === 'admin' ? 'Administrator' : 
                                        ($user_item['role'] === 'member' ? 'Member' : 'Student'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $user_item['status'] === 'Active' ? 'success' : 
                                        ($user_item['status'] === 'Inactive' ? 'secondary' : 'warning'); 
                                ?>">
                                    <?php echo htmlspecialchars($user_item['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($user_item['created_at'])); ?></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $user_item['user_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $user_item['user_id']; ?>">
                                        <li><a class="dropdown-item" href="user-edit.php?id=<?php echo $user_item['user_id']; ?>"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#resetPasswordModal" data-user-id="<?php echo $user_item['user_id']; ?>" data-user-name="<?php echo htmlspecialchars($user_item['first_name'] . ' ' . $user_item['last_name']); ?>"><i class="fas fa-key me-2"></i>Reset Password</a></li>
                                        
                                        <?php if ($user_item['status'] === 'Active'): ?>
                                            <li><a class="dropdown-item" href="users.php?id=<?php echo $user_item['user_id']; ?>&status=Inactive" onclick="return confirm('Are you sure you want to deactivate this user?')"><i class="fas fa-user-slash me-2"></i>Deactivate</a></li>
                                        <?php elseif ($user_item['status'] === 'Inactive'): ?>
                                            <li><a class="dropdown-item" href="users.php?id=<?php echo $user_item['user_id']; ?>&status=Active" onclick="return confirm('Are you sure you want to activate this user?')"><i class="fas fa-user-check me-2"></i>Activate</a></li>
                                        <?php endif; ?>
                                        
                                        <?php if ($user_item['status'] !== 'Suspended'): ?>
                                            <li><a class="dropdown-item" href="users.php?id=<?php echo $user_item['user_id']; ?>&status=Suspended" onclick="return confirm('Are you sure you want to suspend this user?')"><i class="fas fa-ban me-2"></i>Suspend</a></li>
                                        <?php else: ?>
                                            <li><a class="dropdown-item" href="users.php?id=<?php echo $user_item['user_id']; ?>&status=Active" onclick="return confirm('Are you sure you want to unsuspend this user?')"><i class="fas fa-user-check me-2"></i>Unsuspend</a></li>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['user_id'] != $user_item['user_id']): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="users.php?delete=<?php echo $user_item['user_id']; ?>" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <div class="form-text">At least 8 characters</div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="student">Student</option>
                                <option value="member">Member</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="user_handler.php">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="modal-body">
                    <p>You are about to reset the password for <strong id="reset_user_name"></strong>.</p>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_password" required minlength="8">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

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