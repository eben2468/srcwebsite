<?php
// Simple GD Library and System Check
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if GD is available
$gdAvailable = extension_loaded('gd');

// Test image creation
$imageCreationSuccess = false;
$imageCreationMethod = "None";
$testImagePath = "";

// Test directory
$testDir = 'test_images';
if (!file_exists($testDir)) {
    mkdir($testDir, 0777, true);
}

$testImagePath = $testDir . '/test_image_' . time() . '.jpg';

// Try creating image with GD
if ($gdAvailable) {
    $width = 200;
    $height = 100;
    $image = @imagecreatetruecolor($width, $height);
    
    if ($image) {
        // Create colors
        $bgColor = imagecolorallocate($image, 0, 102, 204);
        $textColor = imagecolorallocate($image, 255, 255, 255);
        
        // Fill background
        imagefill($image, 0, 0, $bgColor);
        
        // Add text
        imagestring($image, 4, 50, 40, 'GD Test', $textColor);
        
        // Save image
        if (imagejpeg($image, $testImagePath, 90)) {
            chmod($testImagePath, 0666);
            $imageCreationSuccess = true;
            $imageCreationMethod = "GD Library";
        }
        
        imagedestroy($image);
    }
}

// Fallback if GD failed
if (!$imageCreationSuccess) {
    // Create a simple JPEG-like file with a header
    $header = "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF";
    $content = $header . str_repeat('X', 1024) . " Fallback Test Image";
    
    if (file_put_contents($testImagePath, $content)) {
        chmod($testImagePath, 0666);
        $imageCreationSuccess = true;
        $imageCreationMethod = "Fallback Method";
    }
}

// Check key directories
$directories = [
    'images',
    'images/departments',
    'images/departments/gallery'
];

$directoryStatus = [];

foreach ($directories as $dir) {
    $directoryStatus[$dir] = [
        'exists' => file_exists($dir),
        'writable' => is_writable($dir)
    ];
    
    // Try to create if doesn't exist
    if (!$directoryStatus[$dir]['exists']) {
        mkdir($dir, 0777, true);
        $directoryStatus[$dir]['exists'] = file_exists($dir);
        $directoryStatus[$dir]['writable'] = is_writable($dir);
    }
}

// Get PHP info
$phpInfo = [
    'version' => PHP_VERSION,
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Check for Department Images</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #2c3e50;
        }
        .status-box {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .test-image {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
        }
        .button:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>System Check for Department Images</h1>
        
        <h2>GD Library Status</h2>
        <?php if ($gdAvailable): ?>
            <div class="status-box success">
                <strong>GD Library is available ✓</strong>
                <p>Your system has the required image processing capabilities.</p>
            </div>
        <?php else: ?>
            <div class="status-box danger">
                <strong>GD Library is not available ✗</strong>
                <p>Your PHP installation is missing the GD library, which may limit image functionality.</p>
                <p>To enable it:</p>
                <ol>
                    <li>Edit your php.ini file</li>
                    <li>Uncomment the line with 'extension=gd'</li>
                    <li>Restart your web server</li>
                </ol>
            </div>
        <?php endif; ?>
        
        <h2>Image Creation Test</h2>
        <?php if ($imageCreationSuccess): ?>
            <div class="status-box success">
                <strong>Image creation successful ✓</strong>
                <p>Method: <?php echo $imageCreationMethod; ?></p>
                <?php if (file_exists($testImagePath)): ?>
                    <img src="<?php echo $testImagePath; ?>?v=<?php echo time(); ?>" alt="Test Image" class="test-image">
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="status-box danger">
                <strong>Image creation failed ✗</strong>
                <p>Your system cannot create images. Please check file permissions and PHP configuration.</p>
            </div>
        <?php endif; ?>
        
        <h2>Directory Status</h2>
        <table>
            <tr>
                <th>Directory</th>
                <th>Exists</th>
                <th>Writable</th>
            </tr>
            <?php foreach ($directoryStatus as $dir => $status): ?>
                <tr>
                    <td><?php echo $dir; ?></td>
                    <td><?php echo $status['exists'] ? '✓' : '✗'; ?></td>
                    <td><?php echo $status['writable'] ? '✓' : '✗'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <?php if (array_filter($directoryStatus, function($status) { return !$status['exists'] || !$status['writable']; })): ?>
            <div class="status-box warning">
                <strong>Directory issues detected ⚠</strong>
                <p>Some directories do not exist or are not writable. This may prevent image uploads.</p>
                <p>Solution: Set proper permissions for the web server user on these directories.</p>
            </div>
        <?php endif; ?>
        
        <h2>PHP Configuration</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
            <?php foreach ($phpInfo as $key => $value): ?>
                <tr>
                    <td><?php echo $key; ?></td>
                    <td><?php echo $value; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <h2>Next Steps</h2>
        <p>Based on the results above, you can:</p>
        
        <a href="setup_departments.php" class="button">Run Setup Script</a>
        <a href="reset_images.php" class="button">Reset Department Images</a>
        
        <?php if (!$gdAvailable): ?>
            <div class="status-box warning" style="margin-top: 20px;">
                <strong>Note:</strong> While the system can operate without GD library using fallback methods, 
                it is strongly recommended to enable the GD library for full image functionality.
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 