<?php
// Simple test script for file uploads

// Function to handle file upload
function handleFileUpload($fileField, $targetDir, $allowedTypes = []) {
    $result = [
        'success' => false,
        'message' => '',
        'debug' => []
    ];
    
    // Check if file was uploaded
    if (!isset($_FILES[$fileField])) {
        $result['message'] = "No file field named '$fileField' was submitted.";
        return $result;
    }
    
    // Get file info
    $file = $_FILES[$fileField];
    $result['debug']['file_data'] = $file;
    
    // Check for errors
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
        
        $errorCode = $file['error'];
        $result['message'] = "Upload error: " . ($errorMessages[$errorCode] ?? "Unknown error code: $errorCode");
        return $result;
    }
    
    // Check file type if allowed types are specified
    if (!empty($allowedTypes)) {
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedTypes)) {
            $result['message'] = "Invalid file type. Allowed types: " . implode(', ', $allowedTypes);
            return $result;
        }
    }
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            $result['message'] = "Failed to create directory: $targetDir";
            return $result;
        }
    }
    
    // Generate unique filename
    $newFilename = uniqid() . '_' . $file['name'];
    $targetPath = $targetDir . '/' . $newFilename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $result['success'] = true;
        $result['message'] = "File uploaded successfully!";
        $result['filename'] = $newFilename;
        $result['path'] = $targetPath;
    } else {
        $result['message'] = "Failed to move uploaded file to $targetPath";
        $result['debug']['error'] = error_get_last();
    }
    
    return $result;
}

// Handle form submission
$uploadResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES)) {
    // Determine target directory based on form selection
    $targetDir = './uploads'; // Default
    
    switch ($_POST['upload_type'] ?? '') {
        case 'department':
            $targetDir = './images/departments';
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            break;
        case 'gallery':
            $targetDir = './images/departments/gallery';
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            break;
        case 'document':
            $targetDir = './documents/departments';
            $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];
            break;
    }
    
    $uploadResult = handleFileUpload('test_file', $targetDir, $allowedTypes);
}

// Get server info
$serverInfo = [
    'max_file_size' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'tmp_dir' => ini_get('upload_tmp_dir') ?: 'System default',
    'system_tmp' => sys_get_temp_dir(),
    'system_tmp_writable' => is_writable(sys_get_temp_dir()) ? 'Yes' : 'No'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>File Upload Test</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test Upload Form</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="upload_type" class="form-label">Upload Type</label>
                                <select class="form-select" id="upload_type" name="upload_type" required>
                                    <option value="department">Department Image</option>
                                    <option value="gallery">Gallery Image</option>
                                    <option value="document">Document</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="test_file" class="form-label">Select File</label>
                                <input type="file" class="form-control" id="test_file" name="test_file" required>
                                <div class="form-text">Maximum file size: <?php echo $serverInfo['max_file_size']; ?></div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Upload File</button>
                        </form>
                    </div>
                </div>
                
                <?php if ($uploadResult): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Upload Result</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-<?php echo $uploadResult['success'] ? 'success' : 'danger'; ?>">
                            <?php echo $uploadResult['message']; ?>
                        </div>
                        
                        <?php if ($uploadResult['success']): ?>
                        <p><strong>Filename:</strong> <?php echo $uploadResult['filename']; ?></p>
                        <p><strong>Path:</strong> <?php echo $uploadResult['path']; ?></p>
                        <?php endif; ?>
                        
                        <h6 class="mt-3">Debug Information:</h6>
                        <pre><?php echo json_encode($uploadResult['debug'], JSON_PRETTY_PRINT); ?></pre>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Server Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($serverInfo as $key => $value): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo ucwords(str_replace('_', ' ', $key)); ?>
                                <span class="badge bg-primary"><?php echo $value; ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Directory Status</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php 
                            $directories = [
                                './images/departments',
                                './images/departments/gallery',
                                './documents/departments'
                            ];
                            
                            foreach ($directories as $dir): 
                                $exists = file_exists($dir);
                                $writable = $exists && is_writable($dir);
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo $dir; ?>
                                <div>
                                    <span class="badge bg-<?php echo $exists ? 'success' : 'danger'; ?> me-2">
                                        <?php echo $exists ? 'Exists' : 'Missing'; ?>
                                    </span>
                                    <?php if ($exists): ?>
                                    <span class="badge bg-<?php echo $writable ? 'success' : 'danger'; ?>">
                                        <?php echo $writable ? 'Writable' : 'Not Writable'; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 