<?php
// Very basic image uploader with detailed debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to check upload errors
function getUploadErrorMessage($errorCode) {
    $errorMessages = [
        UPLOAD_ERR_OK => 'No error, upload successful',
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds MAX_FILE_SIZE directive in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
    ];
    
    return $errorMessages[$errorCode] ?? "Unknown error code: $errorCode";
}

$debugInfo = [];
$message = '';
$targetDir = 'images/departments/';
$targetFile = '';

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debugInfo[] = "POST request received at " . date('Y-m-d H:i:s');
    
    // Make sure the directory exists
    if (!file_exists($targetDir)) {
        $debugInfo[] = "Directory does not exist, creating: $targetDir";
        if (!mkdir($targetDir, 0777, true)) {
            $debugInfo[] = "FAILED to create directory";
            $message = "Error: Could not create upload directory";
        } else {
            $debugInfo[] = "Directory created successfully";
        }
    } else {
        $debugInfo[] = "Directory exists: $targetDir";
    }
    
    // Check if directory is writable
    if (is_writable($targetDir)) {
        $debugInfo[] = "Directory is writable: $targetDir";
    } else {
        $debugInfo[] = "Directory is NOT writable: $targetDir";
        
        // Try to make it writable
        if (chmod($targetDir, 0777)) {
            $debugInfo[] = "Changed permissions to 0777";
        } else {
            $debugInfo[] = "FAILED to change permissions";
            $message = "Error: Upload directory is not writable";
        }
    }
    
    // Check if file was uploaded
    if (isset($_FILES['file'])) {
        $debugInfo[] = "File upload attempt detected";
        $debugInfo[] = "Upload details: " . print_r($_FILES['file'], true);
        
        // Check for upload errors
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = getUploadErrorMessage($_FILES['file']['error']);
            $debugInfo[] = "Upload error: $errorMessage";
            $message = "Error: $errorMessage";
        } else {
            $debugInfo[] = "No upload errors detected";
            
            // Check if the temporary file exists and is readable
            if (file_exists($_FILES['file']['tmp_name'])) {
                $debugInfo[] = "Temporary file exists: " . $_FILES['file']['tmp_name'];
                
                if (is_readable($_FILES['file']['tmp_name'])) {
                    $debugInfo[] = "Temporary file is readable";
                } else {
                    $debugInfo[] = "Temporary file is NOT readable";
                    $message = "Error: Cannot read the uploaded file";
                }
            } else {
                $debugInfo[] = "Temporary file does NOT exist: " . $_FILES['file']['tmp_name'];
                $message = "Error: Temporary file not found";
            }
            
            // Proceed with the upload if no errors
            if (empty($message)) {
                // Generate a simple unique filename
                $deptCode = 'nursa'; // Hardcoded for testing
                $targetFile = $targetDir . $deptCode . '.jpg';
                
                $debugInfo[] = "Attempting to move uploaded file to: $targetFile";
                
                // Try to move the uploaded file
                if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
                    $debugInfo[] = "File uploaded successfully via move_uploaded_file";
                    chmod($targetFile, 0666); // Make it readable
                    $message = "Success! File uploaded to: $targetFile";
                } else {
                    $debugInfo[] = "move_uploaded_file FAILED";
                    $error = error_get_last();
                    $debugInfo[] = "Error details: " . ($error ? json_encode($error) : 'No error info');
                    
                    // Try alternative method
                    $debugInfo[] = "Trying alternative upload method (copy)";
                    if (copy($_FILES['file']['tmp_name'], $targetFile)) {
                        $debugInfo[] = "File uploaded successfully via copy";
                        chmod($targetFile, 0666); // Make it readable
                        $message = "Success! File uploaded to: $targetFile (using alternate method)";
                    } else {
                        $debugInfo[] = "copy FAILED";
                        $error = error_get_last();
                        $debugInfo[] = "Error details: " . ($error ? json_encode($error) : 'No error info');
                        $message = "Error: Could not upload file";
                    }
                }
            }
        }
    } else {
        $debugInfo[] = "No file upload detected";
        $message = "Error: No file was uploaded";
    }
    
    // Check the uploaded file
    if (file_exists($targetFile)) {
        $debugInfo[] = "Uploaded file exists at target location";
        $debugInfo[] = "File size: " . filesize($targetFile) . " bytes";
        $debugInfo[] = "File permissions: " . substr(sprintf('%o', fileperms($targetFile)), -4);
    } else if (!empty($targetFile)) {
        $debugInfo[] = "Uploaded file does NOT exist at target location";
    }
}

// Get list of existing files
$existingFiles = [];
if (file_exists($targetDir)) {
    $files = scandir($targetDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $existingFiles[] = [
                'name' => $file,
                'size' => filesize($targetDir . $file),
                'permissions' => substr(sprintf('%o', fileperms($targetDir . $file)), -4),
                'modified' => date("Y-m-d H:i:s", filemtime($targetDir . $file))
            ];
        }
    }
}

// Get PHP configuration information
$phpConfig = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];

// Get server information
$serverInfo = [
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'script_path' => __FILE__,
    'php_version' => PHP_VERSION,
    'os' => PHP_OS,
    'user' => get_current_user(),
    'temp_dir' => sys_get_temp_dir()
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Basic Image Uploader</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .success { background-color: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; }
        .error { background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; }
        .debug { background-color: #f5f5f5; padding: 15px; border-left: 4px solid #2196F3; overflow: auto; }
        pre { white-space: pre-wrap; word-break: break-all; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Basic Image Uploader</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Success') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Upload Form</h2>
            <form method="POST" enctype="multipart/form-data">
                <div>
                    <label for="file">Select Image:</label>
                    <input type="file" name="file" id="file" accept="image/*">
                </div>
                <p>This will upload as nursa.jpg to test the department image issue</p>
                <button type="submit">Upload Image</button>
            </form>
        </div>
        
        <div class="section">
            <h2>Existing Files</h2>
            <?php if (empty($existingFiles)): ?>
                <p>No files found in <?php echo $targetDir; ?></p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Permissions</th>
                        <th>Last Modified</th>
                    </tr>
                    <?php foreach ($existingFiles as $file): ?>
                        <tr>
                            <td><?php echo $file['name']; ?></td>
                            <td><?php echo round($file['size'] / 1024, 2); ?> KB</td>
                            <td><?php echo $file['permissions']; ?></td>
                            <td><?php echo $file['modified']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>PHP Configuration</h2>
            <table>
                <?php foreach ($phpConfig as $key => $value): ?>
                    <tr>
                        <td><?php echo $key; ?></td>
                        <td><?php echo $value; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="section">
            <h2>Server Information</h2>
            <table>
                <?php foreach ($serverInfo as $key => $value): ?>
                    <tr>
                        <td><?php echo $key; ?></td>
                        <td><?php echo $value; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <?php if (!empty($debugInfo)): ?>
            <div class="section">
                <h2>Debug Information</h2>
                <div class="debug">
                    <pre><?php echo implode("\n", $debugInfo); ?></pre>
                </div>
            </div>
        <?php endif; ?>
        
        <p><a href="pages_php/departments.php">Go to Departments Page</a></p>
    </div>
</body>
</html> 