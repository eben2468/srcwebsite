<?php
// Directory structure verification and placeholder creation
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Directory Structure Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #0275d8; }
        h2 { color: #0275d8; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .directory { margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .good { background-color: #d4edda; }
        .bad { background-color: #f8d7da; }
        .action-button {
            display: inline-block;
            padding: 8px 15px;
            background-color: #0275d8;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .action-button:hover {
            background-color: #0056b3;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
        }
        th, td { 
            padding: 8px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }
        th { background-color: #f2f2f2; }
        .placeholder-box {
            width: 200px;
            height: 100px;
            background-color: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>Directory Structure Check</h1>
    <p>This script verifies that all required directories exist with proper permissions.</p>";

// Define required directories
$requiredDirectories = [
    'images',
    'images/departments',
    'images/departments/gallery',
    'documents',
    'documents/departments'
];

// Key directory permissions
$permissionsToCheck = [
    0777, // Full permissions
    0775, // Owner: all, Group: all except write, Others: all except write
    0755  // Owner: all, Group: read+execute, Others: read+execute
];

// Function to convert permissions to human-readable
function permissionsToString($perms) {
    if (($perms & 0xC000) == 0xC000) {
        $info = 's';
    } elseif (($perms & 0xA000) == 0xA000) {
        $info = 'l';
    } elseif (($perms & 0x8000) == 0x8000) {
        $info = '-';
    } elseif (($perms & 0x6000) == 0x6000) {
        $info = 'b';
    } elseif (($perms & 0x4000) == 0x4000) {
        $info = 'd';
    } elseif (($perms & 0x2000) == 0x2000) {
        $info = 'c';
    } elseif (($perms & 0x1000) == 0x1000) {
        $info = 'p';
    } else {
        $info = 'u';
    }

    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));

    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));

    // World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));

    return $info;
}

// Function to format permissions as octal
function octalPerms($perms) {
    return substr(sprintf('%o', $perms), -4);
}

// Check each directory
$dirResults = [];

foreach ($requiredDirectories as $dir) {
    $dirInfo = [
        'path' => $dir,
        'exists' => file_exists($dir),
        'writable' => false,
        'permissions' => null,
        'status' => 'bad'
    ];
    
    if ($dirInfo['exists']) {
        $dirInfo['writable'] = is_writable($dir);
        $dirInfo['permissions'] = fileperms($dir);
        
        // Check if permissions are acceptable
        $permOctal = octalPerms($dirInfo['permissions']);
        $permAcceptable = false;
        
        foreach ($permissionsToCheck as $acceptablePerm) {
            if (($dirInfo['permissions'] & $acceptablePerm) == $acceptablePerm) {
                $permAcceptable = true;
                break;
            }
        }
        
        $dirInfo['status'] = ($dirInfo['writable'] && $permAcceptable) ? 'good' : 'bad';
    }
    
    $dirResults[$dir] = $dirInfo;
}

// Display results and create missing directories
echo "<h2>Directory Check Results</h2>";

$needsRepair = false;
$directoriesCreated = 0;
$permissionsFixed = 0;

foreach ($dirResults as $dir => $info) {
    echo "<div class='directory " . $info['status'] . "'>";
    echo "<h3>$dir</h3>";
    
    if ($info['exists']) {
        echo "<p><span class='success'>✓ Directory exists</span></p>";
        
        if ($info['writable']) {
            echo "<p><span class='success'>✓ Directory is writable</span></p>";
        } else {
            echo "<p><span class='error'>✗ Directory is not writable</span></p>";
            $needsRepair = true;
        }
        
        echo "<p>Permissions: " . octalPerms($info['permissions']) . " (" . permissionsToString($info['permissions']) . ")</p>";
        
        if ($info['status'] == 'bad') {
            echo "<p><span class='warning'>⚠ Permissions should be fixed</span></p>";
            $needsRepair = true;
        }
    } else {
        echo "<p><span class='error'>✗ Directory does not exist</span></p>";
        $needsRepair = true;
    }
    
    echo "</div>";
}

// Form for repair actions
if ($needsRepair) {
    echo "<form action='" . $_SERVER['PHP_SELF'] . "' method='post'>";
    echo "<input type='hidden' name='repair' value='1'>";
    echo "<button type='submit' class='action-button'>Repair Directory Structure</button>";
    echo "</form>";
}

// Process repair if requested
if (isset($_POST['repair'])) {
    echo "<h2>Repairing Directory Structure</h2>";
    
    foreach ($requiredDirectories as $dir) {
        echo "<p>Processing: $dir... ";
        
        if (!file_exists($dir)) {
            // Create directory
            if (@mkdir($dir, 0777, true)) {
                echo "<span class='success'>Created successfully</span>";
                $directoriesCreated++;
            } else {
                echo "<span class='error'>Failed to create</span>";
            }
        } else {
            // Fix permissions
            if (@chmod($dir, 0777)) {
                echo "<span class='success'>Permissions set to 0777</span>";
                $permissionsFixed++;
            } else {
                echo "<span class='error'>Failed to set permissions</span>";
            }
        }
        
        echo "</p>";
    }
    
    echo "<p>Repair summary: Created $directoriesCreated directories, fixed permissions on $permissionsFixed directories.</p>";
}

// Create placeholder files
if (isset($_POST['create_placeholders'])) {
    echo "<h2>Creating Placeholder Files</h2>";
    
    // Department codes
    $departmentCodes = ['nursa', 'themsa', 'edsa', 'cossa', 'dessa', 'sobsa'];
    
    // Create basic JPEG header
    $jpegHeader = pack("C*", 
        0xFF, 0xD8,                  // SOI marker
        0xFF, 0xE0,                  // APP0 marker
        0x00, 0x10,                  // APP0 header size
        0x4A, 0x46, 0x49, 0x46, 0x00, // JFIF identifier
        0x01, 0x01,                  // JFIF version
        0x00,                        // Density units
        0x00, 0x01,                  // X density
        0x00, 0x01,                  // Y density
        0x00, 0x00                   // Thumbnail
    );
    
    // Create department placeholders
    $createdImages = 0;
    $createdDocs = 0;
    
    echo "<h3>Department Images</h3>";
    echo "<ul>";
    
    // Default department image
    $defaultPath = "images/departments/default.jpg";
    $defaultContent = $jpegHeader . str_repeat('X', 2048) . "Default Department Image";
    
    if (file_put_contents($defaultPath, $defaultContent)) {
        chmod($defaultPath, 0666);
        echo "<li>Created: default.jpg <span class='success'>SUCCESS</span></li>";
        $createdImages++;
    }
    
    // Department images
    foreach ($departmentCodes as $code) {
        $deptPath = "images/departments/{$code}.jpg";
        $deptContent = $jpegHeader . str_repeat('X', 2048) . "{$code} Department Image";
        
        if (file_put_contents($deptPath, $deptContent)) {
            chmod($deptPath, 0666);
            echo "<li>Created: {$code}.jpg <span class='success'>SUCCESS</span></li>";
            $createdImages++;
        }
    }
    echo "</ul>";
    
    // Gallery images
    echo "<h3>Gallery Images</h3>";
    echo "<ul>";
    foreach ($departmentCodes as $code) {
        for ($i = 1; $i <= 4; $i++) {
            $galleryPath = "images/departments/gallery/{$code}{$i}.jpg";
            $galleryContent = $jpegHeader . str_repeat('X', 2048) . "{$code} Gallery Image {$i}";
            
            if (file_put_contents($galleryPath, $galleryContent)) {
                chmod($galleryPath, 0666);
                echo "<li>Created: {$code}{$i}.jpg <span class='success'>SUCCESS</span></li>";
                $createdImages++;
            }
        }
    }
    echo "</ul>";
    
    // Department documents
    echo "<h3>Department Documents</h3>";
    echo "<ul>";
    $documentTypes = ['handbook', 'syllabus', 'guide'];
    
    foreach ($departmentCodes as $code) {
        foreach ($documentTypes as $type) {
            $docPath = "documents/departments/{$code}_{$type}.pdf";
            
            // Create a very basic PDF-like file
            $pdfContent = "%PDF-1.4\n1 0 obj\n<</Type /Catalog /Pages 2 0 R>>\nendobj\n";
            $pdfContent .= "2 0 obj\n<</Type /Pages /Kids [3 0 R] /Count 1>>\nendobj\n";
            $pdfContent .= "3 0 obj\n<</Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 500 800] /Contents 6 0 R>>\nendobj\n";
            $pdfContent .= "4 0 obj\n<</Font <</F1 5 0 R>>>>\nendobj\n";
            $pdfContent .= "5 0 obj\n<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>\nendobj\n";
            $pdfContent .= "6 0 obj\n<</Length 44>>\nstream\nBT /F1 24 Tf 175 720 Td ({$code}_{$type}) Tj ET\nendstream\nendobj\n";
            $pdfContent .= "xref\n0 7\n0000000000 65535 f\n0000000010 00000 n\n0000000056 00000 n\n0000000111 00000 n\n";
            $pdfContent .= "0000000212 00000 n\n0000000250 00000 n\n0000000317 00000 n\ntrailer\n<</Size 7 /Root 1 0 R>>\nstartxref\n406\n%%EOF";
            
            if (file_put_contents($docPath, $pdfContent)) {
                chmod($docPath, 0666);
                echo "<li>Created: {$code}_{$type}.pdf <span class='success'>SUCCESS</span></li>";
                $createdDocs++;
            }
        }
    }
    echo "</ul>";
    
    echo "<p>Created $createdImages images and $createdDocs documents.</p>";
    
    // Show examples
    echo "<h3>Example Placeholders</h3>";
    echo "<div class='placeholder-box'>Department Image Example</div>";
    echo "<div class='placeholder-box'>Gallery Image Example</div>";
}

// Verify server configuration
echo "<h2>Server Configuration</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>max_execution_time</td><td>" . ini_get('max_execution_time') . " seconds</td></tr>";
echo "<tr><td>memory_limit</td><td>" . ini_get('memory_limit') . "</td></tr>";
echo "<tr><td>display_errors</td><td>" . ini_get('display_errors') . "</td></tr>";
echo "<tr><td>GD Library</td><td>" . (extension_loaded('gd') ? "<span class='success'>Available</span>" : "<span class='warning'>Not available</span>") . "</td></tr>";
echo "<tr><td>Server Software</td><td>" . $_SERVER['SERVER_SOFTWARE'] . "</td></tr>";

// Get document root and script path
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$scriptPath = __FILE__;
$relativePath = str_replace($docRoot, '', $scriptPath);

echo "<tr><td>Document Root</td><td>" . $docRoot . "</td></tr>";
echo "<tr><td>Script Path</td><td>" . $scriptPath . "</td></tr>";
echo "<tr><td>Relative Path</td><td>" . $relativePath . "</td></tr>";

echo "</table>";

// Placeholder creation form
echo "<h2>Create Placeholder Files</h2>";
echo "<p>If needed, you can create basic placeholder files for all departments:</p>";
echo "<form action='" . $_SERVER['PHP_SELF'] . "' method='post'>";
echo "<input type='hidden' name='create_placeholders' value='1'>";
echo "<button type='submit' class='action-button'>Create Placeholder Files</button>";
echo "</form>";

// Next steps
echo "<h2>Next Steps</h2>";
echo "<p>After ensuring all directories are properly set up:</p>";
echo "<ol>";
echo "<li><a href='cleanup.html'>Return to Cleanup Tools</a></li>";
echo "<li><a href='list_remaining_files.php'>Check remaining files</a></li>";
echo "<li><a href='departments.php'>View departments page</a></li>";
echo "</ol>";

echo "</body></html>";
?> 