<?php
// Department image uploader
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$successMessage = '';
$errorMessage = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate department selection
    if (!isset($_POST['department']) || empty($_POST['department'])) {
        $errorMessage = "Please select a department.";
    } else {
        $department = $_POST['department'];
        $deptCode = strtolower($department);
        
        // Handle department image upload
        if (isset($_FILES['dept_image']) && $_FILES['dept_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $targetDir = 'images/departments/';
            $fileName = $deptCode . '.jpg'; // Always save as jpg for consistency
            $targetFilePath = $targetDir . $fileName;
            
            // Check file type
            $fileType = strtolower(pathinfo($_FILES['dept_image']['name'], PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $errorMessage = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            } else {
                // Make sure directory exists and is writable
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                // Try to upload the file
                if (move_uploaded_file($_FILES['dept_image']['tmp_name'], $targetFilePath)) {
                    chmod($targetFilePath, 0666); // Make sure it's readable
                    $successMessage = "Department image uploaded successfully.";
                } else {
                    // Try alternate method
                    if (copy($_FILES['dept_image']['tmp_name'], $targetFilePath)) {
                        chmod($targetFilePath, 0666);
                        $successMessage = "Department image uploaded using alternate method.";
                    } else {
                        $errorMessage = "Failed to upload department image.";
                    }
                }
            }
        }
        
        // Handle gallery image upload
        if (isset($_FILES['gallery_images']) && $_FILES['gallery_images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $targetDir = 'images/departments/gallery/';
            
            // Make sure directory exists and is writable
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            // Process each gallery image
            $uploadedCount = 0;
            $failedCount = 0;
            
            for ($i = 0; $i < count($_FILES['gallery_images']['name']); $i++) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                    // Check file type
                    $fileType = strtolower(pathinfo($_FILES['gallery_images']['name'][$i], PATHINFO_EXTENSION));
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (!in_array($fileType, $allowedTypes)) {
                        $failedCount++;
                        continue;
                    }
                    
                    // Generate filename: department code + number
                    $galleryNumber = $i + 1;
                    $fileName = $deptCode . $galleryNumber . '.jpg';
                    $targetFilePath = $targetDir . $fileName;
                    
                    // Try to upload the file
                    if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], $targetFilePath)) {
                        chmod($targetFilePath, 0666);
                        $uploadedCount++;
                    } else {
                        // Try alternate method
                        if (copy($_FILES['gallery_images']['tmp_name'][$i], $targetFilePath)) {
                            chmod($targetFilePath, 0666);
                            $uploadedCount++;
                        } else {
                            $failedCount++;
                        }
                    }
                } else {
                    $failedCount++;
                }
            }
            
            if ($uploadedCount > 0) {
                $successMessage .= " Uploaded $uploadedCount gallery images.";
            }
            if ($failedCount > 0) {
                $errorMessage .= " Failed to upload $failedCount gallery images.";
            }
        }
        
        // Handle document upload
        if (isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
            $targetDir = 'documents/departments/';
            
            // Make sure directory exists and is writable
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            // Process document
            $docType = $_POST['doc_type'] ?? 'handbook';
            $fileName = $deptCode . '_' . $docType . '.pdf';
            $targetFilePath = $targetDir . $fileName;
            
            // Try to upload the file
            if (move_uploaded_file($_FILES['document']['tmp_name'], $targetFilePath)) {
                chmod($targetFilePath, 0666);
                $successMessage .= " Document uploaded successfully.";
            } else {
                // Try alternate method
                if (copy($_FILES['document']['tmp_name'], $targetFilePath)) {
                    chmod($targetFilePath, 0666);
                    $successMessage .= " Document uploaded using alternate method.";
                } else {
                    $errorMessage .= " Failed to upload document.";
                }
            }
        }
    }
    
    // Combine messages
    if ($successMessage) {
        $message .= "<div class='alert alert-success'>{$successMessage}</div>";
    }
    if ($errorMessage) {
        $message .= "<div class='alert alert-danger'>{$errorMessage}</div>";
    }
}

// Get department data
$departments = [
    ['code' => 'NURSA', 'name' => 'School of Nursing and Midwifery'],
    ['code' => 'THEMSA', 'name' => 'School of Theology and Mission'],
    ['code' => 'EDSA', 'name' => 'School of Education'],
    ['code' => 'COSSA', 'name' => 'Faculty of Science'],
    ['code' => 'DESSA', 'name' => 'Development Studies'],
    ['code' => 'SOBSA', 'name' => 'School of Business']
];

// Get current images
$departmentImages = [];
if (file_exists('images/departments/')) {
    foreach ($departments as $dept) {
        $deptCode = strtolower($dept['code']);
        $imagePath = "images/departments/{$deptCode}.jpg";
        if (file_exists($imagePath)) {
            $departmentImages[$deptCode] = [
                'path' => $imagePath,
                'size' => filesize($imagePath),
                'modified' => date("Y-m-d H:i:s", filemtime($imagePath))
            ];
        }
    }
}

// Get current documents
$departmentDocs = [];
if (file_exists('documents/departments/')) {
    foreach (glob("documents/departments/*.pdf") as $docPath) {
        $docName = basename($docPath);
        $departmentDocs[] = [
            'name' => $docName,
            'path' => $docPath,
            'size' => filesize($docPath),
            'modified' => date("Y-m-d H:i:s", filemtime($docPath))
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Department Image Uploader</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
        .alert-danger { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input[type="file"] { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #337ab7; color: white; border: none; cursor: pointer; }
        .card { border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 20px; }
        .card-header { font-weight: bold; margin-bottom: 10px; }
        .file-info { color: #666; font-size: 0.9em; }
        .existing-files { margin-top: 30px; }
        .file-list { list-style-type: none; padding: 0; }
        .file-list li { border-bottom: 1px solid #eee; padding: 8px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Department Image Uploader</h1>
        
        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="department">Select Department:</label>
                    <select name="department" id="department" required>
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['code']; ?>"><?php echo $dept['name']; ?> (<?php echo $dept['code']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <h3>Department Main Image</h3>
                <div class="form-group">
                    <label for="dept_image">Upload Department Image:</label>
                    <input type="file" name="dept_image" id="dept_image" accept="image/*">
                    <p class="file-info">This will be saved as [departmentcode].jpg in images/departments/</p>
                </div>
                
                <h3>Department Gallery Images</h3>
                <div class="form-group">
                    <label for="gallery_images">Upload Gallery Images (up to 4):</label>
                    <input type="file" name="gallery_images[]" id="gallery_images" accept="image/*" multiple>
                    <p class="file-info">These will be saved as [departmentcode]1.jpg, [departmentcode]2.jpg, etc. in images/departments/gallery/</p>
                </div>
                
                <h3>Department Document</h3>
                <div class="form-group">
                    <label for="document">Upload Document:</label>
                    <input type="file" name="document" id="document" accept=".pdf,.doc,.docx">
                    
                    <label for="doc_type">Document Type:</label>
                    <select name="doc_type" id="doc_type">
                        <option value="handbook">Handbook</option>
                        <option value="syllabus">Syllabus</option>
                        <option value="guide">Guide</option>
                    </select>
                    <p class="file-info">This will be saved as [departmentcode]_[type].pdf in documents/departments/</p>
                </div>
                
                <button type="submit">Upload Files</button>
            </form>
        </div>
        
        <div class="existing-files">
            <h2>Existing Department Images</h2>
            <?php if (empty($departmentImages)): ?>
                <p>No department images found.</p>
            <?php else: ?>
                <ul class="file-list">
                    <?php foreach ($departmentImages as $code => $image): ?>
                        <li>
                            <strong><?php echo $code; ?>.jpg</strong> - 
                            <?php echo round($image['size'] / 1024, 2); ?> KB - 
                            Last modified: <?php echo $image['modified']; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <h2>Existing Department Documents</h2>
            <?php if (empty($departmentDocs)): ?>
                <p>No department documents found.</p>
            <?php else: ?>
                <ul class="file-list">
                    <?php foreach ($departmentDocs as $doc): ?>
                        <li>
                            <strong><?php echo $doc['name']; ?></strong> - 
                            <?php echo round($doc['size'] / 1024, 2); ?> KB - 
                            Last modified: <?php echo $doc['modified']; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <p><a href="pages_php/departments.php">Go to Departments Page</a></p>
    </div>
</body>
</html> 