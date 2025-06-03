<?php
// Include database configuration and authentication functions
require_once 'db_config.php';
require_once 'auth_functions.php';

// Check if user is logged in and is an admin
session_start();
if (!isLoggedIn() || !isAdmin()) {
    echo "<div style='color: red; padding: 20px; text-align: center;'>
            <h2>Access Denied</h2>
            <p>You must be logged in as an administrator to access this page.</p>
            <p><a href='pages_php/login.php'>Login</a></p>
          </div>";
    exit;
}

$message = '';
$email = '';
$resetLink = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    
    // Validate email
    if (empty($email)) {
        $message = "<div style='color: red;'>Please enter an email address.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div style='color: red;'>Please enter a valid email address.</div>";
    } else {
        // Check if email exists in the database
        $sql = "SELECT user_id, username FROM users WHERE email = ? LIMIT 1";
        $user = fetchOne($sql, [$email]);
        
        if ($user) {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // Token expires in 1 hour
            
            // Store the token in the database
            $sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
            $result = insert($sql, [$user['user_id'], $token, $expires]);
            
            if ($result) {
                // Create reset link
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/srcwebsite/pages_php/reset_password.php?token=" . $token;
                $message = "<div style='color: green;'>Reset link generated successfully for user: " . htmlspecialchars($user['username']) . "</div>";
            } else {
                $message = "<div style='color: red;'>An error occurred. Please try again later.</div>";
            }
        } else {
            $message = "<div style='color: red;'>No user found with this email address.</div>";
        }
    }
}

// Create password_reset_tokens table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query to create table
mysqli_query($conn, $createTableSQL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Generate Password Reset Link</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #4668b3;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        .btn-primary {
            background-color: #4668b3;
            border-color: #4668b3;
        }
        .btn-primary:hover {
            background-color: #3a5a96;
            border-color: #3a5a96;
        }
        .reset-link {
            word-break: break-all;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-key me-2"></i> Generate Password Reset Link</h2>
                <p class="mb-0">Use this tool to generate password reset links for users</p>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($message)): ?>
                <div class="mb-4">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">User Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email"
                                placeholder="Enter user's email address" 
                                required
                                value="<?php echo htmlspecialchars($email); ?>"
                            >
                        </div>
                        <div class="form-text">Enter the email address of the user who needs to reset their password.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i> Generate Reset Link
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($resetLink)): ?>
                <div class="mt-4">
                    <h4>Password Reset Link</h4>
                    <p>Share this link with the user:</p>
                    <div class="reset-link">
                        <a href="<?php echo htmlspecialchars($resetLink); ?>" target="_blank"><?php echo htmlspecialchars($resetLink); ?></a>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-outline-primary" onclick="copyToClipboard('<?php echo htmlspecialchars(addslashes($resetLink)); ?>')">
                            <i class="fas fa-copy me-2"></i> Copy Link
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="pages_php/dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text) {
            // Create a temporary input element
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            
            // Select and copy the text
            input.select();
            document.execCommand('copy');
            
            // Remove the temporary element
            document.body.removeChild(input);
            
            // Show feedback
            alert('Reset link copied to clipboard!');
        }
    </script>
</body>
</html> 