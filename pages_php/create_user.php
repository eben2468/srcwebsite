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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    // Get form data
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    // Basic validation
    $errors = [];
    
    if (empty($firstName)) {
        $errors[] = "First name is required";
    }
    
    if (empty($lastName)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check if username or email already exists
    $checkSql = "SELECT * FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1";
    $existingUser = fetchOne($checkSql, [$username, $email]);
    
    if ($existingUser) {
        if (strtolower($existingUser['username']) === strtolower($username)) {
            $errors[] = "Username already exists";
        }
        if (strtolower($existingUser['email']) === strtolower($email)) {
            $errors[] = "Email already exists";
        }
    }
    
    // If no errors, create the user
    if (empty($errors)) {
        try {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert the user
            $sql = "INSERT INTO users (username, password, first_name, last_name, email, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $result = insert($sql, [$username, $hashedPassword, $firstName, $lastName, $email, $role, $status]);
            
            if ($result) {
                $userId = mysqli_insert_id($conn);
                
                // Create user profile if the table exists
                $checkTableSql = "SHOW TABLES LIKE 'user_profiles'";
                $tableExists = mysqli_query($conn, $checkTableSql);
                
                if ($tableExists && mysqli_num_rows($tableExists) > 0) {
                    $profileSql = "INSERT INTO user_profiles (user_id, full_name, created_at) VALUES (?, ?, NOW())";
                    insert($profileSql, [$userId, $firstName . ' ' . $lastName]);
                }
                
                $roleText = ($role === 'member') ? "Member accounts have access to create events, news, documents, gallery, elections, minutes, reports, budgets, and respond to feedback." : "";
                $successMessage = "User account created successfully! " . $roleText;
            } else {
                $errorMessage = "Error creating user account. Please try again.";
            }
        } catch (Exception $e) {
            // Log the error
            error_log("Error creating user: " . $e->getMessage());
            
            // Check for duplicate entry errors
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'username') !== false) {
                    $errorMessage = "Username already exists. Please choose a different username.";
                } elseif (strpos($e->getMessage(), 'email') !== false) {
                    $errorMessage = "Email already exists. Please use a different email address.";
                } else {
                    $errorMessage = "A user with this information already exists.";
                }
            } else {
                $errorMessage = "Error creating user account. Please try again.";
            }
        }
    } else {
        $errorMessage = implode("<br>", $errors);
    }
}

// Set page title
$pageTitle = "Create New User";

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
                    <li class="breadcrumb-item active" aria-current="page">Create User</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Create New User</h1>
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
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>User Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
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
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <small class="text-muted">At least 8 characters long</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="role" class="form-label">Role</label>
                                        <select class="form-select" id="role" name="role" required>
                                            <option value="admin">Admin</option>
                                            <option value="member" selected>Member</option>
                                            <option value="student">Student</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active" selected>Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="create_user" class="btn btn-primary">
                                        <i class="fas fa-user-plus me-2"></i>Create User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Role Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="role-info">
                                <h5 class="card-title">Admin Role</h5>
                                <p>Administrators have full access to all system features.</p>
                                <hr>
                                
                                <h5 class="card-title">Member Role</h5>
                                <p>Members have access to create and manage:</p>
                                <ul class="small mb-0">
                                    <li>Events</li>
                                    <li>News</li>
                                    <li>Documents</li>
                                    <li>Gallery</li>
                                    <li>Elections</li>
                                    <li>Minutes</li>
                                    <li>Reports</li>
                                    <li>Budgets</li>
                                    <li>Respond to feedback</li>
                                </ul>
                                <hr>
                                
                                <h5 class="card-title">Student Role</h5>
                                <p>Students have limited access to view content and submit feedback.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Quick Tips</h5>
                        </div>
                        <div class="card-body">
                            <ul class="small mb-0">
                                <li>Use strong, unique passwords</li>
                                <li>Usernames should be easy to remember</li>
                                <li>Choose the appropriate role based on user responsibilities</li>
                                <li>Inactive accounts cannot log in</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 