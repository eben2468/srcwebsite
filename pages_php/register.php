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

// Process registration form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    $errors = [];
    
    if (empty($fullname)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $sql = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";
        $existingUser = fetchOne($sql, [$username, $email]);
        
        if ($existingUser) {
            if ($existingUser['username'] === $username) {
                $errors[] = "Username already exists";
            }
            if ($existingUser['email'] === $email) {
                $errors[] = "Email already exists";
            }
        }
    }
    
    // If no errors, create the user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student'; // Default role for new users
        $status = 'Active';
        
        // Split the full name into first name and last name
        $nameParts = explode(' ', $fullname, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        
        // Store the username for login and the full name as the display name
        // We'll use the username field for the login ID and store the full name in a separate field or table later
        $sql = "INSERT INTO users (username, email, password, role, status, first_name, last_name, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $userId = insert($sql, [$username, $email, $hashedPassword, $role, $status, $firstName, $lastName]);
        
        if ($userId) {
            // Store the full name in the session for later use
            $_SESSION['fullname'] = $fullname;
            $_SESSION['new_user_id'] = $userId;
            
            // Create the user profile immediately
            try {
                $profileSql = "INSERT INTO user_profiles (user_id, full_name) VALUES (?, ?)";
                $profileId = insert($profileSql, [$userId, $fullname]);
            } catch (Exception $e) {
                // If there's an error, log it but continue
                error_log("Error creating user profile: " . $e->getMessage());
            }
            
            // Set success message and redirect to login
            $_SESSION['success'] = "Registration successful! You can now login.";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Registration failed. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SRC Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-hover: #375aca;
            --secondary-color: #6c757d;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --light-bg: #f8f9fc;
            --dark-bg: #5a5c69;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            --input-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.05);
            --transition-speed: 0.3s;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fc, #e8eaf6);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .container {
            padding: 2rem 1rem;
        }
        
        .form-container {
            max-width: 100%;
            transition: all var(--transition-speed) ease;
            position: relative;
        }
        
        .card {
            border: none;
            box-shadow: var(--card-shadow);
            border-radius: 1rem;
            overflow: hidden;
            background-color: white;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.2);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 2rem 1.5rem 1rem;
            text-align: center;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid #e3e6f0;
            font-size: 0.9rem;
            transition: all 0.2s;
            box-shadow: var(--input-shadow);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .form-control.is-invalid {
            border-color: var(--danger-color);
            box-shadow: 0 0 0 0.25rem rgba(231, 74, 59, 0.25);
        }
        
        .form-control.is-valid {
            border-color: var(--success-color);
            box-shadow: 0 0 0 0.25rem rgba(28, 200, 138, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: #4e4e4e;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.2s;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
        }
        
        .btn-primary:active {
            transform: translateY(1px);
        }
        
        .form-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1.5rem;
        }
        
        .form-toggle-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            text-decoration: underline;
            cursor: pointer;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .form-toggle-btn:hover {
            color: var(--primary-hover);
        }
        
        /* Password strength indicator */
        .progress {
            height: 6px;
            border-radius: 3px;
            background-color: #e9ecef;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .progress-bar {
            transition: width 0.3s ease;
        }
        
        /* Animation classes */
        .slide-in {
            animation: slideIn 0.3s forwards;
        }
        
        .slide-out {
            animation: slideOut 0.3s forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(-100%); opacity: 0; }
        }
        
        /* Form icons styling */
        .text-center i {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
            width: 60px;
            height: 60px;
            line-height: 60px;
            border-radius: 50%;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .text-center:hover i {
            transform: scale(1.1);
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Form field icons */
        .form-label i {
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }
        
        /* Alert styling */
        .alert {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.05);
        }
        
        .alert-success {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }
        
        /* Form feedback */
        .form-text {
            font-size: 0.8rem;
        }
        
        .text-danger {
            color: var(--danger-color) !important;
        }
        
        .text-success {
            color: var(--success-color) !important;
        }
        
        /* Responsive adjustments */
        @media (min-width: 768px) {
            .container {
                padding: 3rem;
            }
            
            .card {
                border-radius: 1.5rem;
            }
        }
        
        /* Custom checkbox styling */
        .form-check-input {
            width: 1.1em;
            height: 1.1em;
            margin-top: 0.2em;
            cursor: pointer;
            border: 1px solid #cbd3e1;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-label {
            cursor: pointer;
        }
        
        /* Links styling */
        a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        /* Brand logo styling */
        .brand-logo {
            margin-bottom: 1rem;
            animation: fadeInDown 1s;
        }
        
        .brand-logo i {
            font-size: 3rem;
            color: var(--primary-color);
            background: rgba(78, 115, 223, 0.1);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(78, 115, 223, 0.15);
            transition: all 0.3s;
        }
        
        .brand-logo:hover i {
            transform: scale(1.05) rotate(5deg);
            box-shadow: 0 6px 25px rgba(78, 115, 223, 0.25);
        }
        
        .brand-logo h4 {
            font-weight: 700;
            color: #333;
            letter-spacing: 0.5px;
        }
        
        /* Input group styling */
        .input-group-text {
            border-radius: 0.5rem 0 0 0.5rem;
            border-color: #e3e6f0;
        }
        
        .input-group .form-control {
            border-radius: 0 0.5rem 0.5rem 0;
        }
        
        .input-group {
            position: relative;
        }
        
        /* Password visibility toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
            background: transparent;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            width: 30px;
            height: 30px;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        /* Additional animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .btn-primary:hover {
            animation: pulse 1s infinite;
        }
        
        /* Improve form feedback */
        .form-text.text-success {
            display: flex;
            align-items: center;
        }
        
        .form-text.text-success::before {
            content: "\f00c";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-right: 5px;
            color: var(--success-color);
        }
        
        /* Make form fields more interactive */
        .form-control:focus + .input-group-text,
        .input-group-text + .form-control:focus {
            border-color: var(--primary-color);
        }
        
        /* Improve button styles */
        .btn {
            font-weight: 600;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <!-- Brand logo -->
                <div class="text-center mb-4">
                    <div class="brand-logo">
                        <i class="fas fa-graduation-cap text-primary"></i>
                        <h4 class="mt-2 mb-0">Valley View University</h4>
                        <p class="text-muted small">Student Representative Council</p>
                    </div>
                </div>
                
                <div class="card shadow-lg border-0 my-4">
                    <div class="card-body p-0">
                        <div class="position-relative overflow-hidden">
                            <!-- Login Form -->
                            <div id="loginForm" class="form-container p-5">
                                <div class="text-center mb-4">
                                    <i class="fas fa-sign-in-alt text-primary mb-3" style="font-size: 2.5rem;"></i>
                                    <h2 class="fw-bold">Login</h2>
                                    <p class="text-muted">Access your SRC account</p>
                                </div>
                                
                                <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success mb-4">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Login Form -->
                                <form id="login-form" action="login.php" method="post">
                                    <div class="mb-4">
                                        <label for="login-email" class="form-label d-flex align-items-center">
                                            <i class="fas fa-envelope me-2"></i> Email
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-envelope text-muted"></i>
                                            </span>
                                            <input type="email" class="form-control border-start-0" id="login-email" name="email" placeholder="Enter your email" required>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="login-password" class="form-label d-flex align-items-center">
                                            <i class="fas fa-lock me-2"></i> Password
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-lock text-muted"></i>
                                            </span>
                                            <input type="password" class="form-control border-start-0" id="login-password" name="password" placeholder="Enter your password" required>
                                            <span class="password-toggle" onclick="togglePasswordVisibility('login-password', this)">
                                                <i class="fas fa-eye"></i>
                                            </span>
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
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-sign-in-alt me-2"></i> Login
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="form-toggle mt-4 text-center">
                                    <p class="mb-0">Don't have an account? <button type="button" class="form-toggle-btn" id="showRegisterForm">Sign up</button></p>
                                </div>
                            </div>
                            
                            <!-- Registration Form -->
                            <div id="registerForm" class="form-container p-5 d-none">
                                <div class="text-center mb-4">
                                    <i class="fas fa-user-plus text-primary mb-3" style="font-size: 2.5rem;"></i>
                                    <h2 class="fw-bold">Create Account</h2>
                                    <p class="text-muted">Join the SRC community</p>
                                </div>
                                
                                <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger mb-4">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                                
                                <form id="register-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="mb-3">
                                        <label for="fullname" class="form-label d-flex align-items-center">
                                            <i class="fas fa-user me-2"></i> Full Name
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-user text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0" id="fullname" name="fullname" placeholder="Enter your full name" value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="username" class="form-label d-flex align-items-center">
                                            <i class="fas fa-user-tag me-2"></i> Username
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-user-tag text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0" id="username" name="username" placeholder="Choose a username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label d-flex align-items-center">
                                            <i class="fas fa-envelope me-2"></i> Email
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-envelope text-muted"></i>
                                            </span>
                                            <input type="email" class="form-control border-start-0" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label d-flex align-items-center">
                                            <i class="fas fa-lock me-2"></i> Password
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-lock text-muted"></i>
                                            </span>
                                            <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="Create a password" required>
                                            <span class="password-toggle" onclick="togglePasswordVisibility('password', this)">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                        <div id="passwordFeedback" class="form-text text-danger"></div>
                                        <div class="form-text">Password must be at least 6 characters long.</div>
                                        <div class="mt-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>Password strength:</small>
                                                <small id="passwordStrengthText">Not entered</small>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div id="passwordStrengthBar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="confirm_password" class="form-label d-flex align-items-center">
                                            <i class="fas fa-lock me-2"></i> Confirm Password
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-lock text-muted"></i>
                                            </span>
                                            <input type="password" class="form-control border-start-0" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                                            <span class="password-toggle" onclick="togglePasswordVisibility('confirm_password', this)">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                        <div id="confirmPasswordFeedback" class="form-text text-danger"></div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-user-plus me-2"></i> Register
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="form-toggle mt-4 text-center">
                                    <p class="mb-0">Already have an account? <button type="button" class="form-toggle-btn" id="showLoginForm">Login</button></p> 
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mb-5">
                    <p class="mb-0">
                        <a href="../index.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-home me-1"></i> Back to Home
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Password visibility toggle function
    function togglePasswordVisibility(inputId, icon) {
        const passwordInput = document.getElementById(inputId);
        const iconElement = icon.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            iconElement.classList.remove('fa-eye');
            iconElement.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            iconElement.classList.remove('fa-eye-slash');
            iconElement.classList.add('fa-eye');
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Form toggle functionality
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const showRegisterFormBtn = document.getElementById('showRegisterForm');
        const showLoginFormBtn = document.getElementById('showLoginForm');
        
        // Check URL parameters to determine which form to show initially
        const urlParams = new URLSearchParams(window.location.search);
        const showRegister = urlParams.get('register');
        
        // Show registration form if register parameter is present
        if (showRegister === 'true') {
            loginForm.classList.add('d-none');
            registerForm.classList.remove('d-none');
            registerForm.classList.add('slide-in');
        }
        
        if (showRegisterFormBtn) {
            showRegisterFormBtn.addEventListener('click', function(e) {
                e.preventDefault();
                loginForm.classList.add('slide-out');
                setTimeout(() => {
                    loginForm.classList.add('d-none');
                    loginForm.classList.remove('slide-out', 'slide-in');
                    registerForm.classList.remove('d-none');
                    registerForm.classList.add('slide-in');
                    registerForm.classList.remove('slide-out');
                }, 300);
            });
        }
        
        if (showLoginFormBtn) {
            showLoginFormBtn.addEventListener('click', function(e) {
                e.preventDefault();
                registerForm.classList.add('slide-out');
                setTimeout(() => {
                    registerForm.classList.add('d-none');
                    registerForm.classList.remove('slide-out', 'slide-in');
                    loginForm.classList.remove('d-none');
                    loginForm.classList.add('slide-in');
                    loginForm.classList.remove('slide-out');
                }, 300);
            });
        }
        
        // Get password elements
        const passwordInput = document.getElementById('password');
        const passwordFeedback = document.getElementById('passwordFeedback');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const confirmPasswordFeedback = document.getElementById('confirmPasswordFeedback');
        const passwordStrengthBar = document.getElementById('passwordStrengthBar');
        const passwordStrengthText = document.getElementById('passwordStrengthText');
        const registerFormElement = document.getElementById('register-form');
        
        // Simple direct event listeners for password strength
        if (passwordInput) {
            // Function to update password strength
            function updatePasswordStrength() {
                const password = passwordInput.value;
                
                if (password.length > 0 && password.length < 6) {
                    // Weak password
                    passwordFeedback.textContent = "Password must be at least 6 characters long";
                    passwordInput.classList.add('is-invalid');
                    passwordInput.classList.remove('is-valid');
                    
                    // Update strength bar
                    const percentage = Math.min((password.length / 6) * 100, 100);
                    passwordStrengthBar.style.width = percentage + '%';
                    passwordStrengthBar.className = 'progress-bar bg-danger';
                    passwordStrengthText.textContent = 'Weak';
                    
                } else if (password.length >= 6) {
                    // Valid password
                    passwordFeedback.textContent = "";
                    passwordInput.classList.remove('is-invalid');
                    passwordInput.classList.add('is-valid');
                    
                    // Update strength based on length
                    let strengthClass = 'bg-warning';
                    let strengthText = 'Medium';
                    
                    if (password.length >= 10) {
                        strengthClass = 'bg-success';
                        strengthText = 'Strong';
                    }
                    
                    passwordStrengthBar.style.width = '100%';
                    passwordStrengthBar.className = 'progress-bar ' + strengthClass;
                    passwordStrengthText.textContent = strengthText;
                    
                } else {
                    // Empty password
                    passwordFeedback.textContent = "";
                    passwordInput.classList.remove('is-invalid');
                    passwordInput.classList.remove('is-valid');
                    
                    passwordStrengthBar.style.width = '0%';
                    passwordStrengthText.textContent = 'Not entered';
                }
                
                // Check confirm password match if it has a value
                if (confirmPasswordInput.value) {
                    checkPasswordMatch();
                }
            }
            
            // Add multiple event listeners for better responsiveness
            ['input', 'keyup', 'keydown', 'change', 'paste'].forEach(function(event) {
                passwordInput.addEventListener(event, updatePasswordStrength);
            });
            
            // Run initial check
            updatePasswordStrength();
        }
        
        // Confirm password validation
        if (confirmPasswordInput) {
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length > 0) {
                    if (password !== confirmPassword) {
                        confirmPasswordFeedback.textContent = "Passwords do not match";
                        confirmPasswordFeedback.className = "form-text text-danger";
                        confirmPasswordInput.classList.add('is-invalid');
                        confirmPasswordInput.classList.remove('is-valid');
                    } else {
                        confirmPasswordFeedback.textContent = "Passwords match";
                        confirmPasswordFeedback.className = "form-text text-success";
                        confirmPasswordInput.classList.remove('is-invalid');
                        confirmPasswordInput.classList.add('is-valid');
                    }
                } else {
                    confirmPasswordFeedback.textContent = "";
                    confirmPasswordInput.classList.remove('is-invalid');
                    confirmPasswordInput.classList.remove('is-valid');
                }
            }
            
            // Add event listeners for confirm password
            ['input', 'keyup', 'change', 'paste'].forEach(function(event) {
                confirmPasswordInput.addEventListener(event, checkPasswordMatch);
            });
        }
        
        // Form validation
        if (registerFormElement) {
            registerFormElement.addEventListener('submit', function(event) {
                const password = passwordInput.value;
                
                // Check password length
                if (password.length < 6) {
                    event.preventDefault();
                    passwordFeedback.textContent = "Password must be at least 6 characters long";
                    passwordInput.classList.add('is-invalid');
                    
                    // Scroll to password field and focus it
                    passwordInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    passwordInput.focus();
                    
                    // Show alert
                    alert("Please enter a password with at least 6 characters.");
                    return false;
                }
                
                // Check if passwords match
                if (password !== confirmPasswordInput.value) {
                    event.preventDefault();
                    confirmPasswordFeedback.textContent = "Passwords do not match";
                    confirmPasswordFeedback.className = "form-text text-danger";
                    confirmPasswordInput.classList.add('is-invalid');
                    
                    // Scroll to confirm password field and focus it
                    confirmPasswordInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    confirmPasswordInput.focus();
                    
                    // Show alert
                    alert("Passwords do not match. Please make sure both passwords are the same.");
                    return false;
                }
                
                return true;
            });
        }
    });
    </script>
</body>
</html> 