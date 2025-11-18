<?php
// Set page title
$pageTitle = "Page Not Found - SRC Management System";

// Include functions first
try {
    require_once 'functions.php';
} catch (Error $e) {
    // If functions.php fails, start session at least
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Include header
try {
    require_once 'includes/header.php';
} catch (Error $e) {
    // If header.php fails, display a simple header
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Page Not Found - SRC Management System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body>
    <div class="container py-5">';
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-1 fw-bold text-danger">404</h1>
            <h2 class="mb-4">Page Not Found</h2>
            <p class="lead mb-5">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i> Go to Dashboard
                </a>
                <a href="javascript:history.back()" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Go Back
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
try {
    require_once 'includes/footer.php';
} catch (Error $e) {
    // If footer.php fails, display a simple footer
    echo '</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';
}
?> 
