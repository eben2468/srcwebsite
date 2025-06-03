<?php
// Extremely basic uploader - minimal code
error_reporting(E_ALL);
ini_set('display_errors', 1);

$result = "";
$targetDir = "images/departments/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Make sure directory exists
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $targetFile = $targetDir . "nursa.jpg";
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            chmod($targetFile, 0666);
            $result = "File uploaded successfully";
        } else {
            $result = "Upload failed";
        }
    } else {
        $result = "No file uploaded or upload error";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Minimal Uploader</title>
    <style>
        body { font-family: Arial; margin: 20px; }
    </style>
</head>
<body>
    <h1>Minimal Image Uploader</h1>
    
    <?php if ($result): ?>
        <p><?php echo $result; ?></p>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="file">
        <button type="submit">Upload</button>
    </form>
    
    <p>
        PHP version: <?php echo PHP_VERSION; ?><br>
        Upload max filesize: <?php echo ini_get('upload_max_filesize'); ?><br>
        Post max size: <?php echo ini_get('post_max_size'); ?>
    </p>
</body>
</html> 