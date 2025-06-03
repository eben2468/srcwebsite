<?php
// Complete reset script for department images
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Complete Department Image System Reset</h1>";
echo "<p>This script will completely reset all department images and ensure proper permissions.</p>";

// Department codes
$departments = [
    'nursa' => 'School of Nursing and Midwifery',
    'themsa' => 'School of Theology and Mission',
    'edsa' => 'School of Education',
    'cossa' => 'Faculty of Science',
    'dessa' => 'Development Studies',
    'sobsa' => 'School of Business'
];

// Step 1: Remove all existing department image files
echo "<h2>Step 1: Removing existing files</h2>";

// Main department images
$departmentImagesDir = 'images/departments/';
if (file_exists($departmentImagesDir)) {
    $files = glob($departmentImagesDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    echo "<p>Found " . count($files) . " files in department images directory</p>";
    
    foreach ($files as $file) {
        if (unlink($file)) {
            echo "<p>Deleted: " . basename($file) . "</p>";
        } else {
            echo "<p style='color:red'>Failed to delete: " . basename($file) . "</p>";
        }
    }
} else {
    mkdir($departmentImagesDir, 0777, true);
    echo "<p>Created department images directory</p>";
}

// Gallery images
$galleryDir = 'images/departments/gallery/';
if (file_exists($galleryDir)) {
    $files = glob($galleryDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    echo "<p>Found " . count($files) . " files in gallery directory</p>";
    
    foreach ($files as $file) {
        if (unlink($file)) {
            echo "<p>Deleted: " . basename($file) . "</p>";
        } else {
            echo "<p style='color:red'>Failed to delete: " . basename($file) . "</p>";
        }
    }
} else {
    mkdir($galleryDir, 0777, true);
    echo "<p>Created gallery directory</p>";
}

// Step 2: Reset directory permissions
echo "<h2>Step 2: Resetting directory permissions</h2>";

$directories = [
    'images',
    'images/departments',
    'images/departments/gallery'
];

foreach ($directories as $dir) {
    if (file_exists($dir)) {
        if (chmod($dir, 0777)) {
            echo "<p>Reset permissions for: $dir</p>";
        } else {
            echo "<p style='color:red'>Failed to reset permissions for: $dir</p>";
        }
    } else {
        if (mkdir($dir, 0777, true)) {
            echo "<p>Created directory with proper permissions: $dir</p>";
        } else {
            echo "<p style='color:red'>Failed to create directory: $dir</p>";
        }
    }
}

// Step 3: Create sample images for departments
echo "<h2>Step 3: Creating new department images</h2>";

// Create a default image
$defaultFile = $departmentImagesDir . 'default.jpg';
createSampleImage($defaultFile, 'Default Department', '#3498db');

// Create department images
foreach ($departments as $code => $name) {
    $departmentFile = $departmentImagesDir . $code . '.jpg';
    createSampleImage($departmentFile, $name, getColorForDepartment($code));
    
    // Create gallery images
    for ($i = 1; $i <= 4; $i++) {
        $galleryFile = $galleryDir . $code . $i . '.jpg';
        createSampleImage($galleryFile, $name . ' - Gallery ' . $i, getColorForDepartment($code, $i));
    }
}

// Function to create a sample image
function createSampleImage($filename, $text, $bgColor) {
    // Check if GD is available
    if (extension_loaded('gd')) {
        // Create a sample image with GD
        $width = 800;
        $height = 400;
        
        $img = imagecreatetruecolor($width, $height);
        
        // Convert hex color to RGB
        list($r, $g, $b) = sscanf($bgColor, "#%02x%02x%02x");
        $bgcolor = imagecolorallocate($img, $r, $g, $b);
        $textcolor = imagecolorallocate($img, 255, 255, 255);
        
        // Fill the background
        imagefill($img, 0, 0, $bgcolor);
        
        // Add text
        $font = 5; // Built-in font
        $lines = wordwrap($text, 30, "\n", true);
        $lineheight = imagefontheight($font) + 4;
        
        $text_array = explode("\n", $lines);
        $text_height = count($text_array) * $lineheight;
        
        $y = ($height - $text_height) / 2;
        
        foreach ($text_array as $line) {
            $text_width = imagefontwidth($font) * strlen($line);
            $x = ($width - $text_width) / 2;
            imagestring($img, $font, $x, $y, $line, $textcolor);
            $y += $lineheight;
        }
        
        // Save the image
        imagejpeg($img, $filename, 90);
        imagedestroy($img);
        
        if (file_exists($filename)) {
            chmod($filename, 0666);
            echo "<p style='color:green'>Created image: " . basename($filename) . "</p>";
            return true;
        }
    }
    
    // Fallback method if GD is not available
    $jpegHeader = "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF";
    $content = $jpegHeader . str_repeat('X', 5000) . $text;
    
    if (file_put_contents($filename, $content)) {
        chmod($filename, 0666);
        echo "<p style='color:blue'>Created placeholder file: " . basename($filename) . "</p>";
        return true;
    }
    
    echo "<p style='color:red'>Failed to create image: " . basename($filename) . "</p>";
    return false;
}

// Function to get a color for department
function getColorForDepartment($code, $variant = 0) {
    $colors = [
        'nursa' => ['#e74c3c', '#c0392b', '#f1948a', '#f2d7d5'],
        'themsa' => ['#3498db', '#2980b9', '#85c1e9', '#d4e6f1'],
        'edsa' => ['#f39c12', '#d35400', '#f8c471', '#fdebd0'],
        'cossa' => ['#2ecc71', '#27ae60', '#7dcea0', '#d5f5e3'],
        'dessa' => ['#9b59b6', '#8e44ad', '#bb8fce', '#e8daef'],
        'sobsa' => ['#34495e', '#2c3e50', '#85929e', '#eaecee'],
    ];
    
    if (isset($colors[$code][$variant])) {
        return $colors[$code][$variant];
    }
    
    return isset($colors[$code][0]) ? $colors[$code][0] : '#3498db';
}

// Step 4: Create an uploader specifically for replacing department images
echo "<h2>Step 4: Creating a new department image uploader</h2>";

$uploaderFile = 'replace_department_image.php';
$uploaderContent = <<<'EOD'
<?php
// Special department image replacement tool
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$departments = [
    'nursa' => 'School of Nursing and Midwifery',
    'themsa' => 'School of Theology and Mission',
    'edsa' => 'School of Education',
    'cossa' => 'Faculty of Science', 
    'dessa' => 'Development Studies',
    'sobsa' => 'School of Business'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetDir = 'images/departments/';
    $galleryDir = $targetDir . 'gallery/';
    
    // Make sure directories exist with proper permissions
    foreach ([$targetDir, $galleryDir] as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        } else if (!is_writable($dir)) {
            chmod($dir, 0777);
        }
    }
    
    // Handle main department image upload
    if (isset($_POST['department']) && !empty($_POST['department'])) {
        $deptCode = $_POST['department'];
        
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $targetFile = $targetDir . $deptCode . '.jpg';
            
            // If file exists, remove it first
            if (file_exists($targetFile)) {
                @unlink($targetFile);
            }
            
            // Try multiple upload methods
            $uploadMethods = [
                // Method 1: Standard method
                function($source, $target) {
                    return move_uploaded_file($source, $target);
                },
                // Method 2: Copy method
                function($source, $target) {
                    return copy($source, $target);
                },
                // Method 3: File put contents
                function($source, $target) {
                    $content = @file_get_contents($source);
                    return $content !== false ? file_put_contents($target, $content) !== false : false;
                }
            ];
            
            $uploaded = false;
            $methodUsed = '';
            
            foreach ($uploadMethods as $index => $method) {
                if ($method($_FILES['main_image']['tmp_name'], $targetFile)) {
                    chmod($targetFile, 0666);
                    $uploaded = true;
                    $methodUsed = 'Method ' . ($index + 1);
                    break;
                }
            }
            
            if ($uploaded) {
                $message .= "<div class='alert alert-success'>Department image uploaded successfully using $methodUsed.</div>";
            } else {
                $message .= "<div class='alert alert-danger'>Failed to upload department image after trying all methods.</div>";
            }
        }
        
        // Handle gallery image upload
        if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
            if (isset($_POST['gallery_number']) && $_POST['gallery_number'] >= 1 && $_POST['gallery_number'] <= 4) {
                $galleryNumber = (int)$_POST['gallery_number'];
                $targetFile = $galleryDir . $deptCode . $galleryNumber . '.jpg';
                
                // If file exists, remove it first
                if (file_exists($targetFile)) {
                    @unlink($targetFile);
                }
                
                // Try multiple upload methods
                $uploadMethods = [
                    // Method 1: Standard method
                    function($source, $target) {
                        return move_uploaded_file($source, $target);
                    },
                    // Method 2: Copy method
                    function($source, $target) {
                        return copy($source, $target);
                    },
                    // Method 3: File put contents
                    function($source, $target) {
                        $content = @file_get_contents($source);
                        return $content !== false ? file_put_contents($target, $content) !== false : false;
                    }
                ];
                
                $uploaded = false;
                $methodUsed = '';
                
                foreach ($uploadMethods as $index => $method) {
                    if ($method($_FILES['gallery_image']['tmp_name'], $targetFile)) {
                        chmod($targetFile, 0666);
                        $uploaded = true;
                        $methodUsed = 'Method ' . ($index + 1);
                        break;
                    }
                }
                
                if ($uploaded) {
                    $message .= "<div class='alert alert-success'>Gallery image uploaded successfully using $methodUsed.</div>";
                } else {
                    $message .= "<div class='alert alert-danger'>Failed to upload gallery image after trying all methods.</div>";
                }
            } else {
                $message .= "<div class='alert alert-danger'>Invalid gallery number (must be 1-4).</div>";
            }
        }
    } else {
        $message = "<div class='alert alert-danger'>Please select a department.</div>";
    }
}

// Check existing images
$existingImages = [];
$departmentImagesDir = 'images/departments/';
$galleryDir = $departmentImagesDir . 'gallery/';

foreach ($departments as $code => $name) {
    $mainImagePath = $departmentImagesDir . $code . '.jpg';
    $existingImages[$code] = [
        'main' => file_exists($mainImagePath) ? [
            'path' => $mainImagePath,
            'size' => filesize($mainImagePath),
            'modified' => date("Y-m-d H:i:s", filemtime($mainImagePath))
        ] : null,
        'gallery' => []
    ];
    
    for ($i = 1; $i <= 4; $i++) {
        $galleryPath = $galleryDir . $code . $i . '.jpg';
        if (file_exists($galleryPath)) {
            $existingImages[$code]['gallery'][$i] = [
                'path' => $galleryPath,
                'size' => filesize($galleryPath),
                'modified' => date("Y-m-d H:i:s", filemtime($galleryPath))
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Replace Department Images</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; }
        .alert-danger { background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; }
        select, input[type="file"], input[type="number"] { width: 100%; padding: 8px; margin-top: 5px; margin-bottom: 10px; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        .upload-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .existing-files { margin-top: 30px; }
        .department-images { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .image-container { border: 1px solid #ddd; padding: 10px; border-radius: 4px; text-align: center; width: 200px; }
        .image-container img { max-width: 100%; height: auto; margin-bottom: 10px; }
        .image-info { font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Replace Department Images</h1>
        
        <?php echo $message; ?>
        
        <div class="upload-card">
            <h2>Upload Department Image</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="department">Select Department:</label>
                    <select name="department" id="department" required>
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $code => $name): ?>
                            <option value="<?php echo $code; ?>"><?php echo $name; ?> (<?php echo $code; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="main_image">Main Department Image:</label>
                    <input type="file" name="main_image" id="main_image" accept="image/*">
                    <p>This will be saved as [departmentcode].jpg</p>
                </div>
                
                <button type="submit">Upload Main Image</button>
            </form>
        </div>
        
        <div class="upload-card">
            <h2>Upload Gallery Image</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="department">Select Department:</label>
                    <select name="department" id="department" required>
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $code => $name): ?>
                            <option value="<?php echo $code; ?>"><?php echo $name; ?> (<?php echo $code; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="gallery_number">Gallery Image Number (1-4):</label>
                    <input type="number" name="gallery_number" id="gallery_number" min="1" max="4" value="1" required>
                </div>
                
                <div class="form-group">
                    <label for="gallery_image">Gallery Image:</label>
                    <input type="file" name="gallery_image" id="gallery_image" accept="image/*">
                    <p>This will be saved as [departmentcode][number].jpg</p>
                </div>
                
                <button type="submit">Upload Gallery Image</button>
            </form>
        </div>
        
        <div class="existing-files">
            <h2>Existing Department Images</h2>
            
            <?php foreach ($departments as $code => $name): ?>
                <h3><?php echo $name; ?> (<?php echo $code; ?>)</h3>
                
                <div class="department-images">
                    <?php if (isset($existingImages[$code]['main'])): ?>
                        <div class="image-container">
                            <h4>Main Image</h4>
                            <img src="<?php echo $existingImages[$code]['main']['path']; ?>" alt="<?php echo $name; ?>">
                            <div class="image-info">
                                <?php echo round($existingImages[$code]['main']['size'] / 1024, 2); ?> KB<br>
                                <?php echo $existingImages[$code]['main']['modified']; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="image-container">
                            <h4>Main Image</h4>
                            <p>No image found</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <?php if (isset($existingImages[$code]['gallery'][$i])): ?>
                            <div class="image-container">
                                <h4>Gallery <?php echo $i; ?></h4>
                                <img src="<?php echo $existingImages[$code]['gallery'][$i]['path']; ?>" alt="<?php echo $name; ?> Gallery <?php echo $i; ?>">
                                <div class="image-info">
                                    <?php echo round($existingImages[$code]['gallery'][$i]['size'] / 1024, 2); ?> KB<br>
                                    <?php echo $existingImages[$code]['gallery'][$i]['modified']; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="image-container">
                                <h4>Gallery <?php echo $i; ?></h4>
                                <p>No image found</p>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <p><a href="pages_php/departments.php">Go to Departments Page</a></p>
    </div>
</body>
</html>
EOD;

if (file_put_contents($uploaderFile, $uploaderContent)) {
    echo "<p style='color:green'>Created new department image uploader: $uploaderFile</p>";
} else {
    echo "<p style='color:red'>Failed to create department image uploader</p>";
}

// Step 5: Check if department images are working
echo "<h2>Step 5: Verifying department images</h2>";

echo "<p>Checking department images:</p>";
echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";

foreach ($departments as $code => $name) {
    $imagePath = "images/departments/$code.jpg";
    
    echo "<div style='border: 1px solid #ccc; padding: 10px; text-align: center; width: 200px;'>";
    echo "<h3>$name</h3>";
    if (file_exists($imagePath)) {
        echo "<img src='$imagePath' style='max-width: 100%; height: auto;' alt='$name'>";
        echo "<p style='color:green'>Image exists: " . filesize($imagePath) . " bytes</p>";
    } else {
        echo "<p style='color:red'>Image not found!</p>";
    }
    echo "</div>";
}

echo "</div>";

// Final instructions
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Go to <a href='replace_department_image.php'>Replace Department Images</a> to upload new department images</li>";
echo "<li>Check the <a href='pages_php/departments.php'>Departments Page</a> to see if images are displayed correctly</li>";
echo "<li>If you're still having issues, check for GD library availability: <a href='check_gd.php'>Check GD Library</a></li>";
echo "</ol>";

echo "<h2>Reset Complete!</h2>";
echo "<p>The department image system has been completely reset with fresh images and proper permissions.</p>";
?> 