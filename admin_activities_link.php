<?php
/**
 * Direct link to User Activities page
 * This file provides a direct link to access the user activities page
 */

// Check if user is logged in and is admin
require_once 'auth_functions.php';
if (!isLoggedIn() || !isAdmin()) {
    header("Location: pages_php/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Activities - Direct Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; line-height: 1.6; }
        h1 { color: #4b6cb7; margin-bottom: 20px; }
        .card { border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: linear-gradient(to right, #4b6cb7, #182848); color: white; border-radius: 8px 8px 0 0; }
        .btn-primary { background-color: #4b6cb7; border-color: #4b6cb7; }
        .btn-primary:hover { background-color: #3a5a9e; border-color: #3a5a9e; }
        .alert { border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-history"></i> User Activities Access</h1>
            </div>
            <div class="card-body">
                <p class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    This page provides direct access to the User Activities page. If you can't see the User Activities link in the sidebar, 
                    you can use this link instead.
                </p>
                
                <p>The User Activities page allows administrators to:</p>
                <ul>
                    <li>View user login/logout records</li>
                    <li>Track page views and system actions</li>
                    <li>Filter activities by user, date, and action type</li>
                    <li>Monitor system usage patterns</li>
                </ul>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                    <a href="pages_php/user-activities.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-history me-2"></i> Access User Activities
                    </a>
                    
                    <a href="pages_php/dashboard.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-tachometer-alt me-2"></i> Return to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-wrench"></i> Fix Sidebar Issues</h4>
            </div>
            <div class="card-body">
                <p>If you don't see the User Activities link in your sidebar, try these steps:</p>
                <ol>
                    <li>Clear your browser cache (Ctrl+F5 or Cmd+Shift+R)</li>
                    <li>Run the <a href="clear_cache.php">Cache Clearing Script</a></li>
                    <li>Check if you're logged in with admin privileges</li>
                    <li>Try using a different browser</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html> 