<?php
// Initialize session
session_start();

// Include authentication functions
require_once '../auth_functions.php';
require_once '../db_config.php';

// Create user_profiles table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query to create table
mysqli_query($conn, $createTableSQL);

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

// Redirect to register.php which now handles both login and registration
if (!isset($_POST['email']) && !isset($_GET['provider'])) {
    header("Location: register.php");
    exit;
}

// Process login form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Debug information
    $debug_mode = isset($_GET['debug']) && $_GET['debug'] === 'true';
    
    // Check if email exists
    $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $user = fetchOne($sql, [$email]);
    
    if ($debug_mode) {
        echo "<div style='background: #f8f9fa; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd;'>";
        echo "<h4>Debug Information</h4>";
        echo "<p>Email: " . htmlspecialchars($email) . "</p>";
        
        if ($user) {
            echo "<p>User found: Yes</p>";
            echo "<p>User ID: " . $user['user_id'] . "</p>";
            echo "<p>Username: " . $user['username'] . "</p>";
            echo "<p>Status: " . $user['status'] . "</p>";
            echo "<p>Role: " . $user['role'] . "</p>";
            echo "<p>Password verification: " . (password_verify($password, $user['password']) ? "Success" : "Failed") . "</p>";
        } else {
            echo "<p>User found: No</p>";
            
            // Check if user exists with username instead
            $usernameSql = "SELECT * FROM users WHERE username = ? LIMIT 1";
            $usernameUser = fetchOne($usernameSql, [$email]);
            
            if ($usernameUser) {
                echo "<p>Note: A user was found with this as username instead of email.</p>";
            }
        }
        
        echo "</div>";
    }
    
    if ($user && password_verify($password, $user['password'])) {
        // Check if user is active
        if ($user['status'] !== 'Active' && $user['status'] !== 'active') {
            $error_message = "Your account is not active. Please contact an administrator.";
        } else {
            // Remove password from user data before storing in session
            unset($user['password']);
            
            // Set user in session
            $_SESSION['user'] = $user;
            $_SESSION['is_logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            // Check if this is a newly registered user with a full name stored in session
            if (isset($_SESSION['fullname']) && isset($_SESSION['new_user_id']) && $_SESSION['new_user_id'] == $user['user_id']) {
                $fullname = $_SESSION['fullname'];
                
                // Check if profile already exists
                $checkProfile = fetchOne("SELECT profile_id FROM user_profiles WHERE user_id = ?", [$user['user_id']]);
                
                if (!$checkProfile) {
                    try {
                        // Create a profile record for the user with their full name
                        $profileSql = "INSERT INTO user_profiles (user_id, full_name) VALUES (?, ?)";
                        $profileId = insert($profileSql, [$user['user_id'], $fullname]);
                        
                        // Also update the users table with first_name and last_name if they are empty
                        if (empty($user['first_name']) && empty($user['last_name'])) {
                            $nameParts = explode(' ', $fullname, 2);
                            $firstName = $nameParts[0];
                            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
                            
                            $updateUserSql = "UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ?";
                            executeQuery($updateUserSql, [$firstName, $lastName, $user['user_id']]);
                        }
                    } catch (Exception $e) {
                        // If there's an error, log it but continue with login
                        error_log("Error creating user profile: " . $e->getMessage());
                    }
                }
                
                // Clear the session variables
                unset($_SESSION['fullname']);
                unset($_SESSION['new_user_id']);
            }
            
            // Redirect to dashboard or to the page the user was trying to access
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        }
    } else {
        $error_message = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SRC Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card card shadow-sm border-0 my-5">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-sign-in-alt text-primary mb-3" style="font-size: 2.5rem;"></i>
                            <h2 class="fw-bold">Login</h2>
                            <p class="text-muted">Access your SRC account</p>
                        </div>
                        
                        <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger mb-4">
                            <?php echo $error_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Email Login Form -->
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . (isset($_GET['debug']) ? '?debug=true' : '')); ?>" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label d-flex align-items-center">
                                    <i class="fas fa-user me-2"></i> Email Address
                                </label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email"
                                    placeholder="Enter your email" 
                                    required
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                >
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label d-flex align-items-center">
                                    <i class="fas fa-lock me-2"></i> Password
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password"
                                    placeholder="Enter your password" 
                                    required
                                    minlength="6"
                                >
                                <div class="invalid-feedback">
                                    Password must be at least 6 characters.
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">Remember me</label>
                                </div>
                                <a href="forgot_password.php" class="text-primary">Forgot password?</a>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Login
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0 text-muted">
                                Don't have an account? <a href="register.php" class="text-primary">Sign up</a>
                            </p>
                        </div>
                        
                        <div class="text-center mt-4">
                            <!--<p class="mb-0 text-muted">
                                Admin credentials:<br>
                                Email: admin@src.com<br>
                                Password: admin123
                            </p>-->
                        </div>
                    </div>
                </div>
                
                <div class="text-center mb-5">
                    <p class="mb-0">
                        <a href="../index.php">Back to Home</a> | 
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?debug=true'); ?>">Debug Mode</a> | 
                        <a href="../admin_reset_link.php">Admin Reset Tool</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
    });
    </script>
</body>
</html> 