<?php
// Direct fix for department images - creates raw image files with correct permissions
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Direct Image Fix</h1>";
echo "<p>This script will directly create placeholder image files with the correct names and permissions.</p>";

// Function to create a simple placeholder image
function createRawImageFile($path) {
    // Create a very basic image-like file with some data
    $header = "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46"; // JPEG-like header
    $data = $header . str_repeat('X', 1024); // Add some data
    
    if (file_put_contents($path, $data)) {
        chmod($path, 0666); // Make sure it's readable by all
        echo "<p style='color:green'>✓ Created: $path</p>";
        return true;
    }
    echo "<p style='color:red'>Failed to create: $path</p>";
    return false;
}

// Function to ensure directory exists with proper permissions
function ensureDirectory($dir) {
    echo "<p>Checking directory: $dir</p>";
    
    if (!file_exists($dir)) {
        echo "<p>Creating directory: $dir</p>";
        if (!mkdir($dir, 0777, true)) {
            echo "<p style='color:red'>Failed to create directory: $dir</p>";
            return false;
        }
    }
    
    if (!is_writable($dir)) {
        echo "<p>Setting permissions for: $dir</p>";
        if (!chmod($dir, 0777)) {
            echo "<p style='color:red'>Failed to set permissions for: $dir</p>";
            return false;
        }
    }
    
    echo "<p style='color:green'>✓ Directory ready: $dir</p>";
    return true;
}

// Create necessary directories
$directories = [
    'images',
    'images/departments',
    'images/departments/gallery'
];

foreach ($directories as $dir) {
    ensureDirectory($dir);
}

// Create department images
$departments = [
    'nursa',
    'themsa',
    'edsa',
    'cossa',
    'dessa',
    'sobsa'
];

// Create default department image
echo "<h2>Creating default.jpg</h2>";
createRawImageFile('images/departments/default.jpg');

echo "<h2>Creating department images</h2>";
foreach ($departments as $dept) {
    $imagePath = "images/departments/$dept.jpg";
    createRawImageFile($imagePath);
    
    // Create gallery images
    for ($i = 1; $i <= 4; $i++) {
        $galleryPath = "images/departments/gallery/$dept$i.jpg";
        createRawImageFile($galleryPath);
    }
}

// Check if we can display the created images
echo "<h2>Testing Image Display</h2>";
echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
foreach ($departments as $dept) {
    $imagePath = "images/departments/$dept.jpg";
    echo "<div style='border: 1px solid #ccc; padding: 5px; text-align: center;'>";
    echo "<img src='$imagePath' style='max-width: 150px; max-height: 100px;'><br>";
    echo $imagePath;
    echo "</div>";
}
echo "</div>";

// Check file permissions
echo "<h2>Checking File Permissions</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>File</th><th>Exists</th><th>Size</th><th>Permissions</th></tr>";

foreach ($departments as $dept) {
    $imagePath = "images/departments/$dept.jpg";
    echo "<tr>";
    echo "<td>$imagePath</td>";
    echo "<td>" . (file_exists($imagePath) ? "Yes" : "No") . "</td>";
    echo "<td>" . (file_exists($imagePath) ? filesize($imagePath) . " bytes" : "N/A") . "</td>";
    echo "<td>" . (file_exists($imagePath) ? substr(sprintf('%o', fileperms($imagePath)), -4) : "N/A") . "</td>";
    echo "</tr>";
}

echo "</table>";

// Create a test image uploader right on this page
echo "<h2>Test Uploader</h2>";
echo "<form action='" . $_SERVER['PHP_SELF'] . "' method='post' enctype='multipart/form-data'>";
echo "<select name='department'>";
foreach ($departments as $dept) {
    echo "<option value='$dept'>$dept</option>";
}
echo "</select>";
echo "<input type='file' name='image'>";
echo "<button type='submit'>Upload</button>";
echo "</form>";

// Process the upload if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && isset($_POST['department'])) {
    $dept = $_POST['department'];
    $targetFile = "images/departments/$dept.jpg";
    
    echo "<h3>Upload Results</h3>";
    echo "<p>Department: $dept</p>";
    echo "<p>Target file: $targetFile</p>";
    
    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            chmod($targetFile, 0666);
            echo "<p style='color:green'>✓ Upload successful!</p>";
        } else {
            echo "<p style='color:red'>Upload failed with move_uploaded_file()</p>";
            
            // Try copy as fallback
            if (copy($_FILES['image']['tmp_name'], $targetFile)) {
                chmod($targetFile, 0666);
                echo "<p style='color:green'>✓ Upload successful with copy() fallback!</p>";
            } else {
                echo "<p style='color:red'>Upload also failed with copy()</p>";
                
                // Last resort: try to update the file directly
                if (file_put_contents($targetFile, file_get_contents($_FILES['image']['tmp_name']))) {
                    chmod($targetFile, 0666);
                    echo "<p style='color:green'>✓ Upload successful with file_put_contents() last resort!</p>";
                } else {
                    echo "<p style='color:red'>All upload methods failed</p>";
                }
            }
        }
    } else {
        echo "<p style='color:red'>Upload error: " . $_FILES['image']['error'] . "</p>";
    }
}

echo "<p><a href='pages_php/departments.php'>Go to Departments Page</a></p>";
?> 