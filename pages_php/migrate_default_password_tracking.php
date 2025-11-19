<?php
/**
 * Database Migration: Add Default Password Tracking
 * This script adds the is_default_password column to the users table
 * Run this ONCE to enable forced password change for imported users
 */

require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/simple_auth.php';

// Require super admin access
requireLogin();
if (!isSuperAdmin()) {
    die('Error: Only super administrators can run database migrations.');
}

$errors = [];
$success = [];

// Add is_default_password column
$sql1 = "ALTER TABLE users 
         ADD COLUMN IF NOT EXISTS is_default_password TINYINT(1) DEFAULT 0 AFTER password";

if (mysqli_query($conn, $sql1)) {
    $success[] = "✓ Successfully added 'is_default_password' column to users table";
} else {
    // Check if column already exists
    $checkSql = "SHOW COLUMNS FROM users LIKE 'is_default_password'";
    $result = mysqli_query($conn, $checkSql);
    if (mysqli_num_rows($result) > 0) {
        $success[] = "✓ Column 'is_default_password' already exists";
    } else {
        $errors[] = "✗ Failed to add 'is_default_password' column: " . mysqli_error($conn);
    }
}

// Add index for better performance
$sql2 = "ALTER TABLE users ADD INDEX IF NOT EXISTS idx_is_default_password (is_default_password)";
if (mysqli_query($conn, $sql2)) {
    $success[] = "✓ Successfully added index on 'is_default_password' column";
} else {
    // Index might already exist
    if (strpos(mysqli_error($conn), 'Duplicate key name') !== false) {
        $success[] = "✓ Index on 'is_default_password' already exists";
    } else {
        $errors[] = "✗ Failed to add index: " . mysqli_error($conn);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Default Password Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .migration-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 2rem;
        }
        .success-item {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            border-left: 4px solid #28a745;
        }
        .error-item {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="migration-container">
        <div class="text-center mb-4">
            <i class="fas fa-database display-1 text-primary mb-3"></i>
            <h2>Database Migration</h2>
            <p class="text-muted">Add Default Password Tracking Feature</p>
        </div>

        <div class="mb-4">
            <h5><i class="fas fa-list-check me-2"></i>Migration Results</h5>
            
            <?php if (!empty($success)): ?>
                <div class="mt-3">
                    <?php foreach ($success as $msg): ?>
                        <div class="success-item">
                            <i class="fas fa-check-circle me-2"></i><?php echo $msg; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="mt-3">
                    <?php foreach ($errors as $msg): ?>
                        <div class="error-item">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $msg; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle me-2"></i>What This Migration Does:</h6>
            <ul class="mb-0 small">
                <li>Adds an <code>is_default_password</code> column to track users with default passwords</li>
                <li>Users imported via CSV will be marked with default passwords</li>
                <li>These users will be forced to change their password on first login</li>
                <li>Once changed, the password becomes permanent (no automatic expiry)</li>
                <li>Users can voluntarily change passwords or request admin reset</li>
            </ul>
        </div>

        <div class="alert alert-success">
            <h6><i class="fas fa-shield-alt me-2"></i>Security Enhancement:</h6>
            <p class="mb-0 small">
                This feature improves security by ensuring all users with automatically generated passwords
                (student123, member123, etc.) must create their own unique password before accessing the system.
            </p>
        </div>

        <div class="text-center mt-4">
            <a href="bulk-users.php" class="btn btn-primary me-2">
                <i class="fas fa-users me-2"></i>Go to Bulk Users
            </a>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
