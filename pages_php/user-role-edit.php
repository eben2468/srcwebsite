<?php
// Include required files
require_once '../auth_functions.php';
require_once '../db_config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: access_denied.php");
    exit();
}

// Get current user info
$currentUser = getCurrentUser();

// Initialize variables
$successMessage = '';
$errorMessage = '';
$user = null;

// Check if user ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = (int)$_GET['id'];
    
    // Fetch user data
    $sql = "SELECT * FROM users WHERE user_id = ? LIMIT 1";
    $user = fetchOne($sql, [$userId]);
    
    if (!$user) {
        $errorMessage = "User not found.";
    }
} else {
    header("Location: users.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $newRole = $_POST['role'];
    $userId = (int)$_POST['user_id'];
    
    // Validate role
    $validRoles = ['admin', 'member', 'student'];
    if (!in_array($newRole, $validRoles)) {
        $errorMessage = "Invalid role selected.";
    } else {
        // Update user role
        $updateSql = "UPDATE users SET role = ? WHERE user_id = ?";
        $result = update($updateSql, [$newRole, $userId]);
        
        if ($result) {
            $successMessage = "User role updated successfully to " . ucfirst($newRole) . ".";
            
            // Update user data
            $user['role'] = $newRole;
            
            // Additional message for member role
            if ($newRole === 'member') {
                $successMessage .= " This user now has access to create events, news, documents, gallery, elections, minutes, reports, budgets, and respond to feedback.";
            }
        } else {
            $errorMessage = "Failed to update user role. Please try again.";
        }
    }
}

// Set page title
$pageTitle = "Edit User Role - " . htmlspecialchars($user['username']);

// Include header
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit User Role</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <a href="users.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Users
                </a>
            </div>
            
            <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $successMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $errorMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($user): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit User Role</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="member" <?php echo $user['role'] === 'member' ? 'selected' : ''; ?>>Member</option>
                                        <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                    </select>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="update_role" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Role
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Role Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="role-info">
                                <h5 class="card-title">Admin Role</h5>
                                <p>Administrators have full access to all system features, including:</p>
                                <ul>
                                    <li>User management</li>
                                    <li>System settings</li>
                                    <li>All content creation and management</li>
                                    <li>Access to all reports and analytics</li>
                                </ul>
                                <hr>
                                
                                <h5 class="card-title">Member Role</h5>
                                <p>Members have access to create and manage:</p>
                                <ul>
                                    <li>Events</li>
                                    <li>News and announcements</li>
                                    <li>Documents</li>
                                    <li>Gallery items</li>
                                    <li>Elections</li>
                                    <li>Meeting minutes</li>
                                    <li>Reports</li>
                                    <li>Budget information</li>
                                    <li>Respond to feedback</li>
                                </ul>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Members cannot manage users or change system settings.
                                </div>
                                <hr>
                                
                                <h5 class="card-title">Student Role</h5>
                                <p>Students have limited access:</p>
                                <ul>
                                    <li>View events, news, documents</li>
                                    <li>View gallery and portfolios</li>
                                    <li>Submit feedback</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 