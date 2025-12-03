<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';

// Check if user is logged in and is super admin (only super admins can create users)
if (!isLoggedIn() || !isSuperAdmin()) {
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
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
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
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check if email already exists
    $checkSql = "SELECT * FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1";
    $existingUser = fetchOne($checkSql, [$email]);
    
    if ($existingUser) {
        if (strtolower($existingUser['email']) === strtolower($email)) {
            $errors[] = "Email already exists";
        }
    }
    
    // If no errors, create the user
    if (empty($errors)) {
        try {
            // Hash the password
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

            // Insert the user
            $sql = "INSERT INTO users (username, email, password, first_name, last_name, phone, role, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $result = insert($sql, [$username, $email, $hashedPassword, $firstName, $lastName, $phone, $role, $status]);
            
            if ($result) {
                $userId = mysqli_insert_id($conn);
                
                // Create user profile if the table exists
                $checkTableSql = "SHOW TABLES LIKE 'user_profiles'";
                $tableExists = mysqli_query($conn, $checkTableSql);
                
                if ($tableExists && mysqli_num_rows($tableExists) > 0) {
                    $profileSql = "INSERT INTO user_profiles (user_id, full_name, phone, created_at) VALUES (?, ?, ?, NOW())";
                    insert($profileSql, [$userId, $firstName . ' ' . $lastName, $phone]);
                }
                
                $roleText = "";
                switch ($role) {
                    case 'super_admin':
                        $roleText = "Super Admin accounts have complete system control including user management and settings.";
                        break;
                    case 'admin':
                        $roleText = "Admin accounts have access to user activities, password reset, messaging, and content management.";
                        break;
                    case 'member':
                        $roleText = "Member accounts have access to create events, news, documents, gallery, minutes, reports, budgets, and respond to feedback.";
                        break;
                    case 'finance':
                        $roleText = "Finance accounts have member access plus financial management features and budget oversight.";
                        break;
                    case 'electoral_commission':
                        $roleText = "Electoral Commission accounts have student-level access to all pages, but with full super admin privileges for election management (creating elections, managing candidates, and publishing results).";
                        break;
                    case 'student':
                        $roleText = "Student accounts have limited access to view content and submit feedback.";
                        break;
                }
                $successMessage = "User account created successfully! " . $roleText;
            } else {
                $errorMessage = "Error creating user account. Please try again.";
            }
        } catch (Exception $e) {
            // Log the error
            error_log("Error creating user: " . $e->getMessage());
            
            // Check for duplicate entry errors
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'email') !== false) {
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
            <!-- Custom Create User Header -->
            <div class="create-user-header animate__animated animate__fadeInDown">
                <div class="create-user-header-content">
                    <div class="create-user-header-main">
                        <h1 class="create-user-title">
                            <i class="fas fa-user-plus me-3"></i>
                            Create New User
                        </h1>
                        <p class="create-user-description">Add a new user to the system</p>
                    </div>
                    <div class="create-user-header-actions">
                        <a href="users.php" class="btn btn-header-action">
                            <i class="fas fa-arrow-left me-2"></i>Back to Users
                        </a>
                    </div>
                </div>
            </div>

            <style>
            .create-user-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 2.5rem 2rem;
                border-radius: 12px;
                margin-top: 60px;
                margin-bottom: 2rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            }

            .create-user-header-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }

            .create-user-header-main {
                flex: 1;
                text-align: center;
            }

            .create-user-title {
                font-size: 2.5rem;
                font-weight: 700;
                margin: 0 0 1rem 0;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.8rem;
            }

            .create-user-title i {
                font-size: 2.2rem;
                opacity: 0.9;
            }

            .create-user-description {
                margin: 0;
                opacity: 0.95;
                font-size: 1.2rem;
                font-weight: 400;
                line-height: 1.4;
            }

            .create-user-header-actions {
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
                text-decoration: none;
            }

            .btn-header-action:hover {
                background: rgba(255, 255, 255, 0.3);
                border-color: rgba(255, 255, 255, 0.5);
                color: white;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                text-decoration: none;
            }

            @media (max-width: 768px) {
                .create-user-header {
                    padding: 2rem 1.5rem;
                }

                .create-user-header-content {
                    flex-direction: column;
                    align-items: center;
                }

                .create-user-title {
                    font-size: 2rem;
                    gap: 0.6rem;
                }

                .create-user-title i {
                    font-size: 1.8rem;
                }

                .create-user-description {
                    font-size: 1.1rem;
                }

                .create-user-header-actions {
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
            
            /* Mobile Full-Width Optimization for Create User Page */
            @media (max-width: 991px) {
                [class*="col-md-"] {
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                }
                
                /* Remove container padding on mobile for full width */
                .container-fluid {
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                }
                
                /* Ensure page header extends full width */
                .create-user-header {
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                    border-radius: 12px !important;
                }
                
                /* Ensure content cards extend full width */
                .card {
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                    border-radius: 0 !important;
                }
            }
            </style>
            
            <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-top: 1.5rem;">
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
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                    <small class="text-muted">Format: 0241234567 or +233241234567</small>
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
                                            <option value="super_admin">Super Admin</option>
                                            <option value="admin">Admin</option>
                                            <option value="member" selected>Member</option>
                                            <option value="finance">Finance</option>
                                            <option value="electoral_commission">Electoral Commission</option>
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
                                <h5 class="card-title">Super Admin Role</h5>
                                <p>Super Administrators have complete system control including user management, settings, and all features.</p>
                                <hr>

                                <h5 class="card-title">Admin Role</h5>
                                <p>Administrators have access to user activities, password reset, messaging, and content management.</p>
                                <hr>

                                <h5 class="card-title">Member Role</h5>
                                <p>Members have access to create and manage:</p>
                                <ul class="small mb-0">
                                    <li>Events</li>
                                    <li>News</li>
                                    <li>Documents</li>
                                    <li>Gallery</li>
                                    <li>Minutes</li>
                                    <li>Reports</li>
                                    <li>Budgets</li>
                                    <li>Respond to feedback</li>
                                </ul>
                                <hr>

                                <h5 class="card-title">Finance Role</h5>
                                <p>Finance users have member access plus financial management features and budget oversight.</p>
                                <hr>

                                <h5 class="card-title">Electoral Commission Role</h5>
                                <p>Electoral Commission users have <strong>student-level access</strong> to all non-election pages, but <strong>full super admin privileges</strong> for election management:</p>
                                <ul class="small mb-0">
                                    <li><strong>Election Management:</strong> Full CRUD access</li>
                                    <li>Create and manage elections</li>
                                    <li>Approve/reject candidates</li>
                                    <li>Manage voting process</li>
                                    <li>Publish election results</li>
                                    <li><strong>Other Pages:</strong> Student-level (read-only)</li>
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
