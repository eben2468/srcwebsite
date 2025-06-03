<?php
// Include necessary files
require_once 'auth_functions.php';
require_once 'db_config.php';

// Set up error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    echo "Access denied. You must be an admin to use this tool.";
    exit();
}

// Check if username is provided
$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$check_result = '';

if (!empty($username)) {
    // Check if username exists (case-insensitive)
    $checkSql = "SELECT username FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1";
    $existingUser = fetchOne($checkSql, [$username]);
    
    if ($existingUser) {
        $check_result = "<div class='alert alert-danger'>Username '<strong>" . htmlspecialchars($existingUser['username']) . 
                         "</strong>' already exists. The system does case-insensitive username matching.</div>";
    } else {
        $check_result = "<div class='alert alert-success'>Username '<strong>" . htmlspecialchars($username) . 
                         "</strong>' is available.</div>";
    }
}

// Show similar usernames
$similar_usernames = [];
if (!empty($username) && strlen($username) >= 3) {
    $searchSql = "SELECT username FROM users WHERE username LIKE ? LIMIT 10";
    $similar_usernames = fetchAll($searchSql, ['%' . $username . '%']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Username Availability Checker</title>
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
                        <h3 class="mb-0"><i class="fas fa-user-check me-2"></i>Username Availability Checker</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-4">Use this tool to check if a username already exists in the database. The check is case-insensitive.</p>
                        
                        <form method="GET" action="">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" name="username" placeholder="Enter username to check" 
                                       value="<?php echo htmlspecialchars($username); ?>" required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search me-1"></i> Check Availability
                                </button>
                            </div>
                        </form>
                        
                        <?php if ($check_result): ?>
                            <?php echo $check_result; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($similar_usernames)): ?>
                            <div class="mt-4">
                                <h5>Similar Usernames Found:</h5>
                                <ul class="list-group">
                                    <?php foreach ($similar_usernames as $user): ?>
                                        <li class="list-group-item"><?php echo htmlspecialchars($user['username']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="promote_to_member.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user-shield me-1"></i> Promote Users
                            </a>
                            <a href="create_member.php" class="btn btn-outline-success">
                                <i class="fas fa-user-plus me-1"></i> Create Member
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 