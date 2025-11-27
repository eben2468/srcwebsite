<?php
/**
 * Electoral Commission Role Setup Script
 * This script sets up the electoral_commission role in the database
 * Run this ONCE to enable the electoral commission role
 */

require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/simple_auth.php';

// Require super admin access
requireLogin();
if (!isSuperAdmin()) {
    die('Error: Only super administrators can run this setup script.');
}

$errors = [];
$success = [];

// Step 1: Add index for better query performance on the new role
$sql1 = "ALTER TABLE users ADD INDEX IF NOT EXISTS idx_electoral_role (role)";
if (mysqli_query($conn, $sql1)) {
    $success[] = "✓ Added index for role column (if not exists)";
} else {
    // Check if index already exists
    $checkSql = "SHOW INDEX FROM users WHERE Key_name = 'idx_electoral_role'";
    $result = mysqli_query($conn, $checkSql);
    if (mysqli_num_rows($result) > 0) {
        $success[] = "✓ Index 'idx_electoral_role' already exists";
    } else {
        $errors[] = "✗ Failed to add index: " . mysqli_error($conn);
    }
}

// Step 2: Verify the role column can accept the new value
$sql2 = "SHOW COLUMNS FROM users LIKE 'role'";
$result = mysqli_query($conn, $sql2);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $success[] = "✓ Role column type: " . $row['Type'];
    if (strpos($row['Type'], 'varchar') !== false || strpos($row['Type'], 'text') !== false) {
        $success[] = "✓ Role column supports varchar/text - can store 'electoral_commission'";
    } elseif (strpos($row['Type'], 'enum') !== false) {
        // If it's ENUM, we need to alter it
        $sql3 = "ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'student'";
        if (mysqli_query($conn, $sql3)) {
            $success[] = "✓ Converted role column from ENUM to VARCHAR to support new roles";
        } else {
            $errors[] = "✗ Failed to convert role column: " . mysqli_error($conn);
        }
    }
} else {
    $errors[] = "✗ Could not check role column type";
}

// Step 3: Check if there are any existing electoral commission users
$sql4 = "SELECT COUNT(*) as count FROM users WHERE role = 'electoral_commission'";
$result = mysqli_query($conn, $sql4);
if ($result && $row = mysqli_fetch_assoc($result)) {
    if ($row['count'] > 0) {
        $success[] = "✓ Found " . $row['count'] . " existing electoral commission user(s)";
    } else {
        $success[] = "ℹ No electoral commission users found yet - you can create them via the Users page";
    }
}

// Step 4: Show all current roles in the system
$sql5 = "SELECT DISTINCT role, COUNT(*) as count FROM users GROUP BY role ORDER BY role";
$result = mysqli_query($conn, $sql5);
if ($result) {
    $success[] = "✓ Current roles in the system:";
    while ($row = mysqli_fetch_assoc($result)) {
        $success[] = "  - {$row['role']}: {$row['count']} user(s)";
    }
}

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electoral Commission Setup - SRC Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-vote-yea me-2"></i>Electoral Commission Role Setup
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Errors</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle me-2"></i>Setup Status</h5>
                                <ul class="mb-0">
                                    <?php foreach ($success as $message): ?>
                                        <li><?php echo htmlspecialchars($message); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($errors)): ?>
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle me-2"></i>Electoral Commission Role</h5>
                                <p class="mb-2">The electoral commission role has been successfully set up! This role provides:</p>
                                <ul class="mb-2">
                                    <li><strong>Full election management access</strong> - Create, edit, delete elections</li>
                                    <li><strong>Candidate management</strong> - Approve, reject candidates</li>
                                    <li><strong>Vote management</strong> - View and manage votes</li>
                                    <li><strong>Election results</strong> - Access and publish results</li>
                                    <li><strong>Read-only access</strong> to other system features (events, news, documents, etc.)</li>
                                    <li><strong>No access</strong> to user management, finance, or system settings</li>
                                </ul>
                                <p class="mb-0"><strong>Default password for electoral commission users:</strong> electoral123 (users must change on first login)</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="users.php" class="btn btn-primary">
                                <i class="fas fa-users me-2"></i>Create Electoral Commission User
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-home me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-question-circle me-2"></i>How to Create Electoral Commission Users
                        </h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>Go to the <a href="users.php">Users Management</a> page</li>
                            <li>Click on "Create New User"</li>
                            <li>Fill in the user details (name, email, username, etc.)</li>
                            <li>Select <strong>"Electoral Commission"</strong> from the Role dropdown</li>
                            <li>Set the status to <strong>"Active"</strong></li>
                            <li>Click "Create User"</li>
                        </ol>
                        <p class="mb-0 text-muted">
                            <i class="fas fa-info-circle me-2"></i>
                            The user will be required to change their password on first login for security.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
