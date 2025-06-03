<?php
// Debug script for department image uploads
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$debugInfo = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get department code
    $departmentCode = $_POST['department_code'] ?? '';
    
    if (empty($departmentCode)) {
        $message = "Department code is required";
    } else {
        // Process image upload
        if (isset($_FILES['department_image']) && $_FILES['department_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $debugInfo .= "<h3>Upload Debug Information</h3>";
            $debugInfo .= "<pre>" . print_r($_FILES['department_image'], true) . "</pre>";
            
            // Error messages for file upload
            $errorMessages = [
                UPLOAD_ERR_OK => 'No error',
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            
            if ($_FILES['department_image']['error'] !== UPLOAD_ERR_OK) {
                $errorCode = $_FILES['department_image']['error'];
                $errorMessage = $errorMessages[$errorCode] ?? "Unknown error ($errorCode)";
                $message = "Upload Error: $errorMessage";
            } else {
                // Check file type
                $fileInfo = pathinfo($_FILES['department_image']['name']);
                $fileExt = strtolower($fileInfo['extension'] ?? '');
                
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($fileExt, $allowedExtensions)) {
                    $message = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
                } else {
                    // Check upload directory
                    $uploadDir = 'images/departments/';
                    $debugInfo .= "<p>Upload directory: $uploadDir</p>";
                    
                    if (!file_exists($uploadDir)) {
                        $debugInfo .= "<p>Directory does not exist, creating...</p>";
                        if (!mkdir($uploadDir, 0777, true)) {
                            $error = error_get_last();
                            $message = "Failed to create directory: $uploadDir";
                            $debugInfo .= "<p>Error: " . ($error ? $error['message'] : 'Unknown error') . "</p>";
                        } else {
                            $debugInfo .= "<p>Directory created successfully.</p>";
                        }
                    } else {
                        $debugInfo .= "<p>Directory exists.</p>";
                    }
                    
                    // Check if directory is writable
                    if (!is_writable($uploadDir)) {
                        $debugInfo .= "<p>Directory is not writable, attempting to fix permissions...</p>";
                        if (!chmod($uploadDir, 0777)) {
                            $error = error_get_last();
                            $message = "Directory is not writable: $uploadDir";
                            $debugInfo .= "<p>Error: " . ($error ? $error['message'] : 'Unknown error') . "</p>";
                        } else {
                            $debugInfo .= "<p>Permissions updated successfully.</p>";
                        }
                    } else {
                        $debugInfo .= "<p>Directory is writable.</p>";
                    }
                    
                    // Generate filename from department code
                    $finalExt = ($fileExt === 'jpeg') ? 'jpg' : $fileExt;
                    $newFileName = strtolower($departmentCode) . '.' . $finalExt;
                    $targetFilePath = $uploadDir . $newFileName;
                    
                    $debugInfo .= "<p>Target file path: $targetFilePath</p>";
                    
                    // Try to move the uploaded file
                    if (move_uploaded_file($_FILES['department_image']['tmp_name'], $targetFilePath)) {
                        $message = "Department image uploaded successfully as: $newFileName";
                        $debugInfo .= "<p>File moved successfully.</p>";
                        
                        // Set permissions for the file
                        chmod($targetFilePath, 0666);
                        $debugInfo .= "<p>File permissions set to 0666.</p>";
                    } else {
                        $error = error_get_last();
                        $message = "Failed to move uploaded file.";
                        $debugInfo .= "<p>Error: " . ($error ? $error['message'] : 'Unknown error') . "</p>";
                        
                        // Try a direct copy as fallback
                        $debugInfo .= "<p>Trying direct copy as fallback...</p>";
                        if (copy($_FILES['department_image']['tmp_name'], $targetFilePath)) {
                            $message = "Department image copied successfully (fallback method) as: $newFileName";
                            $debugInfo .= "<p>File copied successfully using fallback method.</p>";
                            
                            // Set permissions for the file
                            chmod($targetFilePath, 0666);
                            $debugInfo .= "<p>File permissions set to 0666.</p>";
                        } else {
                            $error = error_get_last();
                            $debugInfo .= "<p>Fallback error: " . ($error ? $error['message'] : 'Unknown error') . "</p>";
                        }
                    }
                }
            }
        } else {
            $message = "No file was uploaded.";
        }
    }
}

// Get existing images
$departmentImages = [];
$galleryImages = [];

if (file_exists('images/departments/')) {
    $departmentImages = glob('images/departments/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
}

if (file_exists('images/departments/gallery/')) {
    $galleryImages = glob('images/departments/gallery/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Department Image Upload Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="file"] { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        .error { color: red; }
        .success { color: green; }
        .debug { background-color: #f5f5f5; padding: 15px; margin-top: 20px; border-left: 4px solid #2196F3; }
        .image-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 20px; }
        .image-item { border: 1px solid #ddd; padding: 10px; }
        .image-item img { max-width: 100%; height: auto; }
        .image-item p { margin: 5px 0; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Department Image Upload Debug</h1>
        
        <?php if ($message): ?>
        <div class="<?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($debugInfo): ?>
        <div class="debug">
            <?php echo $debugInfo; ?>
        </div>
        <?php endif; ?>
        
        <h2>Test Department Image Upload</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="department_code">Department Code (e.g., NURSA, THEMSA):</label>
                <input type="text" name="department_code" id="department_code" required>
            </div>
            
            <div class="form-group">
                <label for="department_image">Department Image:</label>
                <input type="file" name="department_image" id="department_image" accept="image/*" required>
                <p>Image will be saved as [departmentcode].jpg in the images/departments/ directory</p>
            </div>
            
            <button type="submit">Upload Image</button>
        </form>
        
        <h2>Current Department Images</h2>
        <div class="image-gallery">
            <?php if (empty($departmentImages)): ?>
            <p>No department images found.</p>
            <?php else: ?>
                <?php foreach ($departmentImages as $image): ?>
                <div class="image-item">
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="Department Image">
                    <p><?php echo htmlspecialchars(basename($image)); ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <h2>Current Gallery Images</h2>
        <div class="image-gallery">
            <?php if (empty($galleryImages)): ?>
            <p>No gallery images found.</p>
            <?php else: ?>
                <?php foreach ($galleryImages as $image): ?>
                <div class="image-item">
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="Gallery Image">
                    <p><?php echo htmlspecialchars(basename($image)); ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 