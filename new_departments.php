<?php
// New departments page with better image handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'db_config.php';

// Department data (hardcoded for simplicity)
$departments = [
    [
        'id' => 1,
        'code' => 'NURSA',
        'name' => 'School of Nursing and Midwifery',
        'head' => 'Prof. Sarah Johnson',
        'description' => 'The School of Nursing and Midwifery is dedicated to developing competent, compassionate nursing professionals through innovative education, research, and clinical practice.',
        'programs' => ['Bachelor of Nursing', 'Diploma in Midwifery', 'Master of Nursing Science', 'PhD in Nursing']
    ],
    [
        'id' => 2,
        'code' => 'THEMSA',
        'name' => 'School of Theology and Mission',
        'head' => 'Dr. James Matthews',
        'description' => 'The School of Theology and Mission prepares students for ministries in various contexts through theological education, spiritual formation, and practical training.',
        'programs' => ['Bachelor of Theology', 'Master of Divinity', 'Master of Arts in Biblical Studies', 'Diploma in Mission Studies']
    ],
    [
        'id' => 3,
        'code' => 'EDSA',
        'name' => 'School of Education',
        'head' => 'Prof. Michael Brown',
        'description' => 'The School of Education is committed to preparing professional educators who promote excellence in teaching, learning, and development in diverse educational contexts.',
        'programs' => ['Bachelor of Education', 'Post Graduate Certificate in Education', 'Master of Education', 'PhD in Education']
    ],
    [
        'id' => 4,
        'code' => 'COSSA',
        'name' => 'Faculty of Science',
        'head' => 'Prof. Emily Carter',
        'description' => 'The Faculty of Science offers comprehensive programs in natural sciences, mathematics, and technology, focusing on innovation, research, and practical application.',
        'programs' => ['Bachelor of Science', 'Master of Science in Chemistry', 'Bachelor of Computer Science', 'PhD in Physics']
    ],
    [
        'id' => 5,
        'code' => 'DESSA',
        'name' => 'Development Studies',
        'head' => 'Dr. Robert Wilson',
        'description' => 'The Department of Development and Communication Studies offers comprehensive education focusing on social, economic, and political development challenges.',
        'programs' => ['Bachelor of Development Studies', 'Master of International Development', 'Diploma in Community Development', 'Certificate in Project Management']
    ],
    [
        'id' => 6,
        'code' => 'SOBSA',
        'name' => 'School of Business',
        'head' => 'Prof. Jennifer Adams',
        'description' => 'The School of Business provides quality education in business administration, accounting, economics, and management, preparing students for successful careers.',
        'programs' => ['Bachelor of Business Administration', 'Master of Business Administration', 'Diploma in Accounting', 'PhD in Management']
    ]
];

// Function to get department image path
function getDepartmentImagePath($departmentCode) {
    $code = strtolower($departmentCode);
    $mainPath = "images/departments/{$code}.jpg";
    $defaultPath = "images/departments/default.jpg";
    
    if (file_exists($mainPath)) {
        return $mainPath;
    } else {
        // Create directories if they don't exist
        if (!file_exists('images/departments/')) {
            mkdir('images/departments/', 0777, true);
        }
        
        // Create a simple placeholder if default doesn't exist
        if (!file_exists($defaultPath)) {
            createPlaceholder($defaultPath, "Default Department");
        }
        
        return $defaultPath;
    }
}

// Function to create a simple placeholder image
function createPlaceholder($path, $text) {
    if (extension_loaded('gd')) {
        // Create with GD if available
        $width = 800;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        
        // Create colors
        $bgColor = imagecolorallocate($image, 0, 102, 204);
        $textColor = imagecolorallocate($image, 255, 255, 255);
        
        // Fill background
        imagefill($image, 0, 0, $bgColor);
        
        // Add text
        $font = 5;
        $textWidth = strlen($text) * imagefontwidth($font);
        $textHeight = imagefontheight($font);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        imagestring($image, $font, $x, $y, $text, $textColor);
        
        // Save image
        imagejpeg($image, $path, 90);
        chmod($path, 0666);
        imagedestroy($image);
        return true;
    } else {
        // Create a basic file with a JPEG header
        $header = "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46";
        $data = $header . str_repeat('X', 1024) . $text;
        
        file_put_contents($path, $data);
        chmod($path, 0666);
        return true;
    }
}

// Check for add/edit/delete actions
$message = '';
$messageType = '';

// Handle department image upload
if (isset($_POST['upload_image']) && isset($_POST['department_code'])) {
    $departmentCode = strtolower($_POST['department_code']);
    $targetDir = 'images/departments/';
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (isset($_FILES['department_image']) && $_FILES['department_image']['error'] === UPLOAD_ERR_OK) {
        $targetFile = $targetDir . $departmentCode . '.jpg';
        
        // Remove existing file
        if (file_exists($targetFile)) {
            unlink($targetFile);
        }
        
        // Try multiple upload methods
        $uploaded = false;
        
        // Method 1: move_uploaded_file
        if (move_uploaded_file($_FILES['department_image']['tmp_name'], $targetFile)) {
            chmod($targetFile, 0666);
            $uploaded = true;
        } 
        // Method 2: copy
        else if (copy($_FILES['department_image']['tmp_name'], $targetFile)) {
            chmod($targetFile, 0666);
            $uploaded = true;
        } 
        // Method 3: file_put_contents
        else {
            $content = file_get_contents($_FILES['department_image']['tmp_name']);
            if ($content !== false && file_put_contents($targetFile, $content)) {
                chmod($targetFile, 0666);
                $uploaded = true;
            }
        }
        
        if ($uploaded) {
            $message = "Department image uploaded successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to upload department image.";
            $messageType = "danger";
        }
    } else if (isset($_FILES['department_image'])) {
        $errorCodes = [
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
        ];
        
        $errorCode = $_FILES['department_image']['error'];
        $errorMessage = isset($errorCodes[$errorCode]) ? $errorCodes[$errorCode] : "Unknown error";
        
        $message = "Upload error: $errorMessage";
        $messageType = "danger";
    }
}

// Get the current admin status (mock implementation)
$isAdmin = true; // For testing, set to true

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRC Management System - Departments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-bottom: 30px;
        }
        .header {
            background-color: #2e4b8c;
            color: white;
            padding: 15px 0;
            margin-bottom: 30px;
        }
        .department-card {
            margin-bottom: 30px;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            transition: transform 0.3s;
        }
        .department-card:hover {
            transform: translateY(-5px);
        }
        .department-image {
            height: 200px;
            background-size: cover;
            background-position: center;
        }
        .card-header {
            font-weight: bold;
            font-size: 1.2rem;
        }
        .department-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
        }
        .program-list {
            list-style-type: none;
            padding-left: 0;
        }
        .program-list li {
            padding: 5px 0;
            border-bottom: 1px dashed #eee;
        }
        .program-list li:last-child {
            border-bottom: none;
        }
        .department-head {
            font-style: italic;
            color: #6c757d;
        }
        .upload-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .image-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>School Departments</h1>
                </div>
                <div class="col-md-6 text-right">
                    <?php if ($isAdmin): ?>
                    <a href="#" class="btn btn-light" data-toggle="modal" data-target="#addDepartmentModal">
                        <i class="fas fa-plus"></i> Add Department
                    </a>
                    <?php endif; ?>
                </div>
            </div>
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
        
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Academic Departments
                    </div>
                    <div class="card-body">
                        <p>Explore our various schools and faculties, each offering unique programs and opportunities for academic growth.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($departments as $department): ?>
                <?php 
                    $imagePath = getDepartmentImagePath($department['code']);
                    
                    // Generate random color for department tag
                    $colors = ['#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6', '#34495e'];
                    $colorIndex = $department['id'] % count($colors);
                    $tagColor = $colors[$colorIndex];
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card department-card">
                        <div class="department-image" style="background-image: url('<?php echo $imagePath; ?>');">
                            <div class="department-tag" style="background-color: <?php echo $tagColor; ?>">
                                <?php echo $department['code']; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $department['name']; ?></h5>
                            <p class="department-head">Head: <?php echo $department['head']; ?></p>
                            <p class="card-text"><?php echo substr($department['description'], 0, 100); ?>...</p>
                            
                            <div class="programs mb-3">
                                <h6>Programs Offered:</h6>
                                <ul class="program-list">
                                    <?php foreach (array_slice($department['programs'], 0, 3) as $program): ?>
                                        <li><?php echo $program; ?></li>
                                    <?php endforeach; ?>
                                    <?php if (count($department['programs']) > 3): ?>
                                        <li>And more...</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="#" class="btn btn-primary btn-sm">View Department</a>
                                <?php if ($isAdmin): ?>
                                    <div>
                                        <a href="#" class="btn btn-info btn-sm">Edit</a>
                                        <a href="#" class="btn btn-danger btn-sm">Delete</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($isAdmin): ?>
                                <div class="upload-form">
                                    <div class="image-controls">
                                        <span>Department Image:</span>
                                        <button class="btn btn-outline-primary btn-sm" type="button" 
                                                onclick="toggleUploadForm('<?php echo $department['code']; ?>')">
                                            Change Image
                                        </button>
                                    </div>
                                    <form id="uploadForm<?php echo $department['code']; ?>" 
                                          action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
                                          method="post" 
                                          enctype="multipart/form-data" 
                                          style="display: none; margin-top: 10px;">
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" name="department_image" id="departmentImage<?php echo $department['code']; ?>" accept="image/*" required>
                                                <label class="custom-file-label" for="departmentImage<?php echo $department['code']; ?>">Choose file</label>
                                            </div>
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">Upload</button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="department_code" value="<?php echo $department['code']; ?>">
                                        <input type="hidden" name="upload_image" value="1">
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script>
        function toggleUploadForm(code) {
            const formId = 'uploadForm' + code;
            const form = document.getElementById(formId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        // Update custom file input label with selected filename
        document.querySelectorAll('.custom-file-input').forEach(input => {
            input.addEventListener('change', function() {
                const fileName = this.value.split('\\').pop();
                this.nextElementSibling.innerHTML = fileName || 'Choose file';
            });
        });
    </script>
</body>
</html> 