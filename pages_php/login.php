<?php
// Initialize session
session_start();

// Include database configuration and functions
require_once __DIR__ . "/../includes/db_config.php";
require_once __DIR__ . "/../includes/db_functions.php";
require_once __DIR__ . "/../includes/security_functions.php";

// If user is already logged in, redirect to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

// Process login form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!empty($email) && !empty($password)) {
        // Check IP access
        $ip_address = $_SERVER["REMOTE_ADDR"] ?? "unknown";
        if (!isIPAllowed($ip_address)) {
            $error_message = "Access denied from your IP address";
            logSecurityEvent(
                null,
                "suspicious_activity",
                "Login attempt from blocked IP: {$ip_address}",
                "high",
            );
        }
        // Check if account is locked
        elseif (isAccountLocked($email)) {
            $error_message =
                "Account is temporarily locked due to multiple failed login attempts";
            recordLoginAttempt($email, false, "Account locked");
        } else {
            // Check if user exists and password is correct
            $sql =
                "SELECT user_id, username, email, first_name, last_name, role, status, password, profile_picture FROM users WHERE email = ? AND status = 'active' LIMIT 1";
            $user = fetchOne($sql, [$email]);

            if ($user && password_verify($password, $user["password"])) {
                // Login successful
                recordLoginAttempt($email, true);

                // Create user session
                createUserSession($user["user_id"]);

                // Set session variables
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["first_name"] = $user["first_name"];
                $_SESSION["last_name"] = $user["last_name"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["profile_picture"] = $user["profile_picture"];
                $_SESSION["is_logged_in"] = true;

                // Log successful login
                logSecurityEvent(
                    $user["user_id"],
                    "login",
                    "Successful login from IP: {$ip_address}",
                    "low",
                );

                // Check if password needs to be changed
                $force_change = getSecuritySetting(
                    "force_password_change",
                    false,
                );
                $password_expiry = getSecuritySetting(
                    "password_expiry_days",
                    90,
                );

                if ($force_change) {
                    $_SESSION["force_password_change"] = true;
                    header("Location: change-password.php");
                    exit();
                }

                // Check password expiry
                if ($password_expiry > 0) {
                    $password_age_sql =
                        "SELECT DATEDIFF(NOW(), updated_at) as days_old FROM users WHERE user_id = ?";
                    $password_age = fetchOne($password_age_sql, [
                        $user["user_id"],
                    ]);

                    if (
                        $password_age &&
                        $password_age["days_old"] >= $password_expiry
                    ) {
                        $_SESSION["password_expired"] = true;
                        header("Location: change-password.php");
                        exit();
                    }
                }

                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                // Login failed
                recordLoginAttempt($email, false, "Invalid credentials");

                // Check if this should trigger account lockout
                checkFailedAttempts($email);

                // Log failed login attempt
                $user_id = $user ? $user["user_id"] : null;
                logSecurityEvent(
                    $user_id,
                    "login",
                    "Failed login attempt for email: {$email} from IP: {$ip_address}",
                    "medium",
                );

                $error_message = "Invalid email or password";
            }
        }
    } else {
        $error_message = "Please enter both email and password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VVUSRC Management System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">



    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-color: #10b981;
            --danger-color: #ef4444;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background Particles */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }

        .particle:nth-child(1) {
            width: 80px;
            height: 80px;
            left: 10%;
            top: 20%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            width: 60px;
            height: 60px;
            right: 15%;
            top: 40%;
            animation-delay: 2s;
        }

        .particle:nth-child(3) {
            width: 100px;
            height: 100px;
            left: 70%;
            top: 70%;
            animation-delay: 4s;
        }

        .particle:nth-child(4) {
            width: 50px;
            height: 50px;
            left: 30%;
            bottom: 20%;
            animation-delay: 6s;
        }

        .particle:nth-child(5) {
            width: 70px;
            height: 70px;
            right: 25%;
            bottom: 30%;
            animation-delay: 8s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.3;
            }
            50% {
                transform: translateY(-30px) rotate(180deg);
                opacity: 0.6;
            }
        }

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 480px;
        }

        /* Login Card with Glassmorphism */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.25);
        }

        /* Logo and Header */
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-logo {
            width: 80px;
            height: 80px;
            background: var(--primary-gradient);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }

        .login-logo i {
            font-size: 2.5rem;
            color: white;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-subtitle {
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 400;
        }

        /* Alert Messages */
        .alert-modern {
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideInDown 0.5s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-modern i {
            font-size: 1.25rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: #667eea;
        }

        .form-control-modern {
            width: 100%;
            padding: 0.875rem 1.125rem;
            font-size: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: white;
            font-family: 'Poppins', sans-serif;
        }

        .form-control-modern:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-control-modern::placeholder {
            color: #9ca3af;
        }

        /* Password Toggle */
        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 0.5rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        /* Checkbox and Links */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .form-check-modern {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-check-modern input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .form-check-modern label {
            font-size: 0.875rem;
            color: var(--text-light);
            cursor: pointer;
            margin: 0;
        }

        .forgot-link {
            font-size: 0.875rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #764ba2;
        }

        /* Button Styling */
        .btn-login {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Back to Home Link */
        .back-home {
            text-align: center;
            margin-top: 2rem;
        }

        .back-home a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .back-home a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }

        /* Loading State */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-login .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn-login.loading .spinner {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            .login-card {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }

            .login-title {
                font-size: 1.75rem;
            }

            .login-logo {
                width: 70px;
                height: 70px;
            }

            .login-logo i {
                font-size: 2rem;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Validation Feedback */
        .invalid-feedback {
            display: none;
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .form-control-modern.is-invalid {
            border-color: var(--danger-color);
        }

        .form-control-modern.is-invalid ~ .invalid-feedback {
            display: block;
        }

        /* Success Animation */
        @keyframes successPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .success-icon {
            animation: successPulse 0.5s ease;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- Login Container -->
    <div class="login-container" data-aos="fade-up" data-aos-duration="800">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to access your SRC account</p>
            </div>

            <!-- Error Message -->
            <?php if (isset($error_message)): ?>
            <div class="alert-modern alert-danger" data-aos="shake">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="<?php echo htmlspecialchars(
                $_SERVER["PHP_SELF"],
            ); ?>" id="loginForm" novalidate>
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input
                        type="email"
                        class="form-control-modern"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                        value="<?php echo isset($_POST["email"])
                            ? htmlspecialchars($_POST["email"])
                            : ""; ?>"
                    >
                    <div class="invalid-feedback">
                        Please enter a valid email address.
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            class="form-control-modern"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            minlength="6"
                        >
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">
                        Password must be at least 6 characters.
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="form-options">
                    <div class="form-check-modern">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="spinner"></span>
                    <span class="btn-text">Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>

        <!-- Back to Home -->
        <div class="back-home" data-aos="fade-up" data-aos-delay="200">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>

        // Password Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        // Form Validation
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');

        loginForm.addEventListener('submit', function(event) {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            let isValid = true;

            // Reset validation states
            emailInput.classList.remove('is-invalid');
            passwordInput.classList.remove('is-invalid');

            // Validate email
            if (!emailInput.value || !emailInput.validity.valid) {
                emailInput.classList.add('is-invalid');
                isValid = false;
            }

            // Validate password
            if (!passwordInput.value || passwordInput.value.length < 6) {
                passwordInput.classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                // Add loading state
                loginBtn.classList.add('loading');
                loginBtn.querySelector('.btn-text').textContent = 'Signing In...';
            }
        });

        // Real-time validation
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        emailInput.addEventListener('blur', function() {
            if (this.value && this.validity.valid) {
                this.classList.remove('is-invalid');
            }
        });

        passwordInput.addEventListener('blur', function() {
            if (this.value && this.value.length >= 6) {
                this.classList.remove('is-invalid');
            }
        });

        // Input focus effects
        const inputs = document.querySelectorAll('.form-control-modern');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>
