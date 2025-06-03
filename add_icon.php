<?php
/**
 * Add Custom Icon
 * This script allows administrators to add new custom icons to the system
 */

// Start session
session_start();

// Include required files
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    die('Database configuration file not found.');
}

if (file_exists('auth_functions.php')) {
    require_once 'auth_functions.php';
} else {
    die('Auth functions file not found.');
}

if (file_exists('icon_functions.php')) {
    require_once 'icon_functions.php';
} else {
    die('Icon functions file not found.');
}

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: index.php');
    exit;
}

// Initialize variables
$message = '';
$messageType = '';
$iconName = '';
$iconValue = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if file was uploaded
    if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK) {
        // Get icon name and value
        $iconName = trim($_POST['icon_name'] ?? '');
        $iconValue = trim($_POST['icon_value'] ?? '');
        
        // Validate inputs
        if (empty($iconName) || empty($iconValue)) {
            $message = 'Icon name and value are required.';
            $messageType = 'danger';
        } else {
            // Validate icon value (alphanumeric and underscores only)
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $iconValue)) {
                $message = 'Icon value can only contain letters, numbers, and underscores.';
                $messageType = 'danger';
            } else {
                // Create icons directory if it doesn't exist
                $iconDir = 'images/icons/';
                if (!is_dir($iconDir)) {
                    mkdir($iconDir, 0755, true);
                }
                
                // Set destination path
                $svgFileName = $iconValue . '.svg';
                $svgFilePath = $iconDir . $svgFileName;
                
                // Check if file already exists
                if (file_exists($svgFilePath)) {
                    $message = 'An icon with this value already exists. Please choose a different value.';
                    $messageType = 'danger';
                } else {
                    // Check file type (only allow SVG files)
                    $fileType = mime_content_type($_FILES['icon_file']['tmp_name']);
                    $fileExt = strtolower(pathinfo($_FILES['icon_file']['name'], PATHINFO_EXTENSION));
                    
                    if ($fileExt !== 'svg' && $fileType !== 'image/svg+xml') {
                        $message = 'Only SVG files are allowed.';
                        $messageType = 'danger';
                    } else {
                        // Move the uploaded file
                        if (move_uploaded_file($_FILES['icon_file']['tmp_name'], $svgFilePath)) {
                            // Create HTML preview file
                            $htmlFilePath = $iconDir . $iconValue . '.html';
                            $htmlContent = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>$iconName Icon</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        .icon {
            width: 100px;
            height: 100px;
        }
    </style>
</head>
<body>
    <img src="$svgFileName" class="icon" alt="$iconName Icon">
</body>
</html>
HTML;
                            file_put_contents($htmlFilePath, $htmlContent);
                            
                            // Create success message
                            $message = "Icon \"$iconName\" added successfully!";
                            $messageType = 'success';
                            
                            // Clear form values
                            $iconName = '';
                            $iconValue = '';
                        } else {
                            $message = 'Failed to upload icon file.';
                            $messageType = 'danger';
                        }
                    }
                }
            }
        }
    } else if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle upload errors
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
        ];
        
        $errorCode = $_FILES['icon_file']['error'];
        $errorMessage = $uploadErrors[$errorCode] ?? 'Unknown upload error.';
        
        $message = "Upload failed: $errorMessage";
        $messageType = 'danger';
    }
}

// Get all existing icons
$existingIcons = getAvailableIcons();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Custom Icon - SRC Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .icon-preview {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
        }
        .icon-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            background-color: white;
        }
        .icon-name {
            margin-top: 5px;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .icon-value {
            font-size: 12px;
            color: #666;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Add Custom Icon</h1>
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="pages_php/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="pages_php/settings.php">Settings</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Custom Icon</li>
            </ol>
        </nav>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Upload New Icon</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="icon_name" class="form-label">Icon Name</label>
                        <input type="text" class="form-control" id="icon_name" name="icon_name" value="<?php echo htmlspecialchars($iconName); ?>" required>
                        <div class="form-text">Display name for the icon (e.g., "Cross", "Bible")</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon_value" class="form-label">Icon Value</label>
                        <input type="text" class="form-control" id="icon_value" name="icon_value" value="<?php echo htmlspecialchars($iconValue); ?>" required>
                        <div class="form-text">Technical name used in code (e.g., "cross", "bible"). Use only letters, numbers, and underscores.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon_file" class="form-label">SVG Icon File</label>
                        <input type="file" class="form-control" id="icon_file" name="icon_file" accept=".svg,image/svg+xml" required>
                        <div class="form-text">Upload an SVG file. Recommended size: 48x48 pixels.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Upload Icon</button>
                        <a href="pages_php/settings.php" class="btn btn-outline-secondary">Back to Settings</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Existing Icons</h5>
            </div>
            <div class="card-body">
                <div class="icon-grid">
                    <?php foreach ($existingIcons as $icon): ?>
                    <div class="icon-card">
                        <img src="<?php echo str_replace('../', '', $icon['path']); ?>" alt="<?php echo htmlspecialchars($icon['name']); ?>" class="icon-preview">
                        <div class="icon-name"><?php echo htmlspecialchars($icon['name']); ?></div>
                        <div class="icon-value"><?php echo htmlspecialchars($icon['value']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-generate icon value from name
        document.getElementById('icon_name').addEventListener('input', function() {
            const iconName = this.value.trim();
            const iconValue = document.getElementById('icon_value');
            
            // Only auto-generate if the user hasn't manually entered a value
            if (iconValue.value === '' || iconValue.value === iconValue.defaultValue) {
                iconValue.value = iconName
                    .toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '')  // Remove special characters
                    .replace(/\s+/g, '_');        // Replace spaces with underscores
            }
        });
    </script>
</body>
</html> 