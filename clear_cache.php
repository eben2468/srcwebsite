<?php
/**
 * Cache Clearing Script
 * This script clears any PHP opcache and sets cache control headers
 * to instruct browsers to reload all assets
 */

// Clear PHP opcache if enabled
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Regenerate session ID to force new session
session_start();
session_regenerate_id(true);

// Set cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Set a version parameter for forcing asset reload
$cacheVersion = time();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cache Cleared</title>
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
    <script>
        // Force reload of cached resources
        window.onload = function() {
            // Add cache-busting parameter to all link tags
            const links = document.getElementsByTagName('link');
            for (let i = 0; i < links.length; i++) {
                if (links[i].rel === 'stylesheet') {
                    links[i].href = links[i].href + '?v=<?php echo $cacheVersion; ?>';
                }
            }
            
            // Add cache-busting parameter to all script tags
            const scripts = document.getElementsByTagName('script');
            for (let i = 0; i < scripts.length; i++) {
                if (scripts[i].src) {
                    scripts[i].src = scripts[i].src + '?v=<?php echo $cacheVersion; ?>';
                }
            }
            
            // Add a message after a delay
            setTimeout(function() {
                document.getElementById('status').innerHTML = 'Cache cleared successfully!';
            }, 1000);
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-sync-alt"></i> Cache Clearing</h1>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Clearing cache and reloading resources...
                </div>
                
                <div id="status" class="alert alert-success">
                    <i class="fas fa-cog fa-spin"></i> Working...
                </div>
                
                <p>This tool helps resolve issues related to cached files:</p>
                <ul>
                    <li>Clears PHP opcache if enabled</li>
                    <li>Forces reload of CSS and JavaScript files</li>
                    <li>Resets browser cache for this website</li>
                    <li>Regenerates session ID</li>
                </ul>
                
                <h4>Next Steps:</h4>
                <p>After cache clearing, try the following:</p>
                <ol>
                    <li>Log out and log back in</li>
                    <li>Hard refresh your browser (Ctrl+F5 or Cmd+Shift+R)</li>
                    <li>Try accessing the User Activities page again</li>
                </ol>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                    <a href="pages_php/dashboard.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                    </a>
                    
                    <a href="pages_php/user-activities.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-history me-2"></i> User Activities
                    </a>
                    
                    <a href="admin_activities_link.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-link me-2"></i> Alternative Access
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Create a cache-busting function for images
        function loadImageWithCacheBusting(imagePath) {
            return imagePath + '?v=<?php echo $cacheVersion; ?>';
        }
    </script>
</body>
</html> 