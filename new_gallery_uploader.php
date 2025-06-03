<?php
// Dedicated gallery image uploader for departments
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Department data
$departments = [
    'NURSA' => 'School of Nursing and Midwifery',
    'THEMSA' => 'School of Theology and Mission',
    'EDSA' => 'School of Education',
    'COSSA' => 'Faculty of Science',
    'DESSA' => 'Development Studies',
    'SOBSA' => 'School of Business'
];

$message = '';
$messageType = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_gallery'])) {
    $departmentCode = isset($_POST['department_code']) ? $_POST['department_code'] : '';
    $galleryNumber = isset($_POST['gallery_number']) ? (int)$_POST['gallery_number'] : 0;
    
    if (empty($departmentCode) || !isset($departments[$departmentCode])) {
        $message = "Please select a valid department.";
        $messageType = "danger";
    } else if ($galleryNumber < 1 || $galleryNumber > 4) {
        $message = "Gallery image number must be between 1 and 4.";
        $messageType = "danger";
    } else if (!isset($_FILES['gallery_image']) || $_FILES['gallery_image']['error'] !== UPLOAD_ERR_OK) {
        $errorCodes = [
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
        ];
        
        $errorCode = isset($_FILES['gallery_image']) ? $_FILES['gallery_image']['error'] : UPLOAD_ERR_NO_FILE;
        $errorMessage = isset($errorCodes[$errorCode]) ? $errorCodes[$errorCode] : "Unknown error";
        
        $message = "Upload error: $errorMessage";
        $messageType = "danger";
    } else {
        // Everything looks good, proceed with upload
        $targetDir = 'images/departments/gallery/';
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Set target file path
        $deptCode = strtolower($departmentCode);
        $targetFile = $targetDir . $deptCode . $galleryNumber . '.jpg';
        
        // Delete existing file if it exists
        if (file_exists($targetFile)) {
            unlink($targetFile);
        }
        
        // Try multiple upload methods
        $uploaded = false;
        $methodUsed = '';
        
        // Method 1: move_uploaded_file
        if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $targetFile)) {
            chmod($targetFile, 0666);
            $uploaded = true;
            $methodUsed = 'move_uploaded_file';
        } 
        // Method 2: copy
        else if (copy($_FILES['gallery_image']['tmp_name'], $targetFile)) {
            chmod($targetFile, 0666);
            $uploaded = true;
            $methodUsed = 'copy';
        } 
        // Method 3: file_put_contents
        else {
            $content = @file_get_contents($_FILES['gallery_image']['tmp_name']);
            if ($content !== false && file_put_contents($targetFile, $content)) {
                chmod($targetFile, 0666);
                $uploaded = true;
                $methodUsed = 'file_put_contents';
            }
        }
        
        if ($uploaded) {
            $message = "Gallery image uploaded successfully using $methodUsed method!";
            $messageType = "success";
        } else {
            $message = "Failed to upload gallery image after trying all methods.";
            $messageType = "danger";
        }
    }
}

// Get existing gallery images
$galleryImages = [];
$galleryDir = 'images/departments/gallery/';

if (file_exists($galleryDir)) {
    foreach ($departments as $code => $name) {
        $deptCode = strtolower($code);
        $galleryImages[$code] = [];
        
        for ($i = 1; $i <= 4; $i++) {
            $imagePath = $galleryDir . $deptCode . $i . '.jpg';
            if (file_exists($imagePath)) {
                $galleryImages[$code][$i] = [
                    'path' => $imagePath,
                    'size' => filesize($imagePath),
                    'modified' => date("Y-m-d H:i:s", filemtime($imagePath))
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Gallery Image Uploader</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 30px 0;
        }
        .header {
            background-color: #2e4b8c;
            color: white;
            padding: 15px 0;
            margin-bottom: 30px;
        }
        .container {
            max-width: 900px;
        }
        .card {
            margin-bottom: 30px;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        .gallery-image {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
        }
        .image-container {
            position: relative;
            margin-bottom: 20px;
        }
        .image-info {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .department-section {
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .department-section:last-child {
            border-bottom: none;
        }
        .department-title {
            color: #2e4b8c;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Department Gallery Image Uploader</h1>
            <p class="mb-0">Upload and manage department gallery images</p>
        </div>
    </div>
    
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Upload Gallery Image
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="department_code">Department:</label>
                            <select class="form-control" id="department_code" name="department_code" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $code => $name): ?>
                                    <option value="<?php echo $code; ?>"><?php echo $name; ?> (<?php echo $code; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="gallery_number">Gallery Image Number:</label>
                            <select class="form-control" id="gallery_number" name="gallery_number" required>
                                <option value="1">Gallery Image 1</option>
                                <option value="2">Gallery Image 2</option>
                                <option value="3">Gallery Image 3</option>
                                <option value="4">Gallery Image 4</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="gallery_image">Select Image:</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="gallery_image" name="gallery_image" accept="image/*" required>
                            <label class="custom-file-label" for="gallery_image">Choose file...</label>
                        </div>
                        <small class="form-text text-muted">Image will be saved as [departmentcode][number].jpg</small>
                    </div>
                    <input type="hidden" name="upload_gallery" value="1">
                    <button type="submit" class="btn btn-primary">Upload Gallery Image</button>
                </form>
            </div>
        </div>
        
        <h2 class="mb-4">Existing Gallery Images</h2>
        
        <?php foreach ($departments as $code => $name): ?>
            <div class="department-section">
                <h3 class="department-title"><?php echo $name; ?> (<?php echo $code; ?>)</h3>
                
                <div class="row">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <div class="col-md-3 col-sm-6">
                            <div class="image-container">
                                <h5>Gallery Image <?php echo $i; ?></h5>
                                <?php 
                                    $deptCode = strtolower($code);
                                    $imagePath = "images/departments/gallery/{$deptCode}{$i}.jpg";
                                    
                                    if (file_exists($imagePath)): 
                                ?>
                                    <img src="<?php echo $imagePath; ?>?v=<?php echo time(); ?>" alt="<?php echo $name; ?> Gallery <?php echo $i; ?>" class="gallery-image">
                                    <div class="image-info">
                                        Size: <?php echo round(filesize($imagePath) / 1024, 2); ?> KB<br>
                                        Modified: <?php echo date("Y-m-d H:i:s", filemtime($imagePath)); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="p-4 text-center bg-light">
                                        <p class="mb-0">No image uploaded</p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-primary" onclick="prepareUpload('<?php echo $code; ?>', <?php echo $i; ?>)">Replace</button>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="mt-4">
            <a href="new_departments.php" class="btn btn-secondary">Back to Departments</a>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update file input label with selected filename
        document.querySelector('.custom-file-input').addEventListener('change', function(e) {
            const fileName = e.target.files[0].name;
            const label = e.target.nextElementSibling;
            label.innerHTML = fileName;
        });
        
        // Prepare upload form with department and gallery number
        function prepareUpload(deptCode, galleryNumber) {
            document.getElementById('department_code').value = deptCode;
            document.getElementById('gallery_number').value = galleryNumber;
            
            // Scroll to upload form
            document.querySelector('.card').scrollIntoView({
                behavior: 'smooth'
            });
            
            // Focus on file input
            setTimeout(() => {
                document.getElementById('gallery_image').focus();
            }, 500);
        }
    </script>
</body>
</html> 