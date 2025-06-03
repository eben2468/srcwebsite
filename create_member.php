<?php
// Include necessary files
require_once 'auth_functions.php';
require_once 'db_config.php';

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_member'])) {
    // Get form data
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    
    // Basic validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
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
            
            // Insert the user with 'member' role
            $sql = "INSERT INTO users (username, password, first_name, last_name, email, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'member', 'active', NOW())";
            
            $result = insert($sql, [$username, $hashedPassword, $firstName, $lastName, $email]);
            
            if ($result) {
                $userId = mysqli_insert_id($conn);
                
                // Create user profile if the table exists
                $checkTableSql = "SHOW TABLES LIKE 'user_profiles'";
                $tableExists = mysqli_query($conn, $checkTableSql);
                
                if ($tableExists && mysqli_num_rows($tableExists) > 0) {
                    $profileSql = "INSERT INTO user_profiles (user_id, full_name, created_at) VALUES (?, ?, NOW())";
                    insert($profileSql, [$userId, $firstName . ' ' . $lastName]);
                }
                
                $successMessage = "Member account created successfully! They now have access to create events, news, documents, gallery, elections, minutes, reports, budgets, and respond to feedback.";
            } else {
                $errorMessage = "Error creating member account. Please try again.";
            }
        } catch (Exception $e) {
            // Log the error (you can add a more sophisticated logging mechanism)
            error_log("Error creating member: " . $e->getMessage());
            
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
                $errorMessage = "Error creating member account. Please try again.";
            }
        }
    } else {
        $errorMessage = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Member Account</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create Member Account</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($successMessage): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($errorMessage): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
                        </div>
                        <?php endif; ?>
                        
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
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="create_member" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Create Member Account
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                                </a>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer bg-light">
                        <p class="mb-0 small text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Members will have access to create and manage events, news, documents, gallery items, elections, minutes, reports, budgets, and respond to feedback.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 