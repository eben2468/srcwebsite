<?php
// Simple test script for file uploads
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to debug an uploaded file
function debugUploadedFile($file) {
    echo "<h3>File Upload Debug Information</h3>";
    echo "<pre>";
    print_r($file);
    echo "</pre>";
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        
        echo "<p class='error'>Error: " . ($errorMessages[$file['error']] ?? "Unknown error ({$file['error']})") . "</p>";
    } else {
        echo "<p>No errors detected in the upload.</p>";
        
        // Check temporary file
        echo "<p>Temporary file: {$file['tmp_name']}</p>";
        echo "<p>Temporary file exists: " . (file_exists($file['tmp_name']) ? 'Yes' : 'No') . "</p>";
        echo "<p>Temporary file readable: " . (is_readable($file['tmp_name']) ? 'Yes' : 'No') . "</p>";
    }
}

// Function to ensure a directory exists and is writable
function ensureDirectoryExists($dir) {
    echo "<p>Checking directory: $dir</p>";
    
    if (!file_exists($dir)) {
        echo "<p>Directory does not exist, attempting to create...</p>";
        if (!mkdir($dir, 0777, true)) {
            echo "<p class='error'>Failed to create directory: $dir</p>";
            return false;
        }
        echo "<p>Directory created successfully!</p>";
    } else {
        echo "<p>Directory exists</p>";
    }
    
    if (!is_writable($dir)) {
        echo "<p class='error'>Directory is not writable: $dir</p>";
        return false;
    }
    
    echo "<p>Directory is writable</p>";
    return true;
}

$message = '';
$targetDir = '';
$targetFile = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Target directory selection
    $uploadType = $_POST['upload_type'] ?? 'default';
    
    switch ($uploadType) {
        case 'department':
            $targetDir = './images/departments/';
            break;
        case 'gallery':
            $targetDir = './images/departments/gallery/';
            break;
        case 'document':
            $targetDir = './documents/departments/';
            break;
        default:
            $targetDir = './uploads/';
            break;
    }
    
    // Ensure target directory exists
    if (!ensureDirectoryExists($targetDir)) {
        $message = "Error: Target directory issue!";
    } 
    // Process file upload
    elseif (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Debug the uploaded file
        debugUploadedFile($_FILES['upload_file']);
        
        // Process the upload
        $fileName = basename($_FILES['upload_file']['name']);
        $targetFile = $targetDir . $fileName;
        
        echo "<p>Attempting to move file to: $targetFile</p>";
        
        if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $targetFile)) {
            $message = "File uploaded successfully to: $targetFile";
            echo "<p class='success'>$message</p>";
            echo "<p>File now exists: " . (file_exists($targetFile) ? 'Yes' : 'No') . "</p>";
        } else {
            $message = "Failed to move uploaded file!";
            echo "<p class='error'>$message</p>";
            echo "<p>Error details: " . error_get_last()['message'] . "</p>";
        }
    } else {
        $message = "No file was uploaded or there was an error with the upload.";
    }
}

// Display PHP configuration
$phpConfig = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'temporary_directory' => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
    'loaded_extensions' => implode(', ', get_loaded_extensions())
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP File Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="file"], select { margin-bottom: 10px; }
        .error { color: red; }
        .success { color: green; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background-color: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHP File Upload Test</h1>
        
        <?php if ($message): ?>
        <div class="message">
            <p><?php echo $message; ?></p>
            <?php if ($targetFile && file_exists($targetFile)): ?>
                <img src="<?php echo $targetFile; ?>" style="max-width: 300px;" />
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <h2>Upload Test</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="upload_type">Upload Directory:</label>
                <select name="upload_type" id="upload_type">
                    <option value="default">Test uploads directory</option>
                    <option value="department">Department Images</option>
                    <option value="gallery">Gallery Images</option>
                    <option value="document">Department Documents</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="upload_file">Select File:</label>
                <input type="file" name="upload_file" id="upload_file">
            </div>
            
            <button type="submit">Upload File</button>
        </form>
        
        <h2>PHP Configuration</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
            <?php foreach ($phpConfig as $key => $value): ?>
            <tr>
                <td><?php echo htmlspecialchars($key); ?></td>
                <td><?php echo htmlspecialchars($value); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <h2>Directory Status</h2>
        <?php
        $directories = [
            './images/departments',
            './images/departments/gallery',
            './documents/departments'
        ];
        
        foreach ($directories as $dir) {
            $exists = file_exists($dir);
            $writable = $exists && is_writable($dir);
            echo "<p>";
            echo "<strong>$dir:</strong> ";
            echo $exists ? "Exists" : "<span class='error'>Missing</span>";
            echo $exists ? ($writable ? ", Writable" : ", <span class='error'>Not Writable</span>") : "";
            echo "</p>";
            
            if ($exists) {
                echo "<p>Contents:</p><ul>";
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        echo "<li>$file</li>";
                    }
                }
                echo "</ul>";
            }
        }
        ?>
    </div>
</body>
</html> 