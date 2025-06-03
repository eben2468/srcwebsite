<?php
// Include necessary files
require_once 'auth_functions.php';

// Get the current user, if logged in
$currentUser = getCurrentUser();
$role = $currentUser ? $currentUser['role'] : 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - SRC Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .access-denied-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .access-denied-icon {
            font-size: 64px;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'pages_php/includes/header.php'; ?>
    
    <div class="container">
        <div class="access-denied-container">
            <div class="access-denied-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="text-danger">Access Denied</h1>
            <p class="lead">You do not have permission to access this page or perform this action.</p>
            
            <?php if ($role === 'student' || $role === 'member'): ?>
            <p>Your current role (<?php echo ucfirst($role); ?>) does not have the necessary permissions.</p>
            <p>If you believe this is an error, please contact an administrator.</p>
            <?php elseif ($role === 'guest'): ?>
            <p>Please log in to access this feature.</p>
            <a href="login.php" class="btn btn-primary mt-3">Go to Login</a>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="index.php" class="btn btn-secondary">Return to Home</a>
            </div>
        </div>
    </div>
    
    <?php include 'pages_php/includes/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html> 