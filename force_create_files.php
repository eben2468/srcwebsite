<?php
// Forcibly create all department files with minimal valid structure
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Forcibly Creating Department Files</h1>";
echo "<p>This script will force-create all required files for departments.</p>";

// Department codes
$departmentCodes = ['nursa', 'themsa', 'edsa', 'cossa', 'dessa', 'sobsa'];

// Create required directories with full permissions
$directories = [
    'images',
    'images/departments',
    'images/departments/gallery',
    'documents',
    'documents/departments'
];

// Force create directories
foreach ($directories as $dir) {
    echo "<h3>Creating directory: $dir</h3>";
    
    // Make sure the directory exists with proper permissions
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "<p style='color:green'>Created directory: $dir</p>";
        } else {
            echo "<p style='color:red'>Failed to create directory: $dir</p>";
        }
    } else {
        chmod($dir, 0777);
        echo "<p>Directory already exists: $dir (permissions set to 0777)</p>";
    }
}

// Create a minimal JPEG image
function createMinimalJpeg($text) {
    // Simple JFIF header
    $header = chr(0xFF) . chr(0xD8) . // SOI marker
             chr(0xFF) . chr(0xE0) . // APP0 marker
             chr(0x00) . chr(0x10) . // APP0 header size (16 bytes)
             'JFIF' . chr(0x00) . // JFIF identifier
             chr(0x01) . chr(0x01) . // JFIF version
             chr(0x00) . // Density units
             chr(0x00) . chr(0x01) . // X density
             chr(0x00) . chr(0x01) . // Y density
             chr(0x00) . chr(0x00); // Thumbnail
             
    // Add EOI marker at the end
    $content = $header . str_repeat(chr(0x00), 2048) . $text . chr(0xFF) . chr(0xD9);
    
    return $content;
}

// Create department images
echo "<h2>Creating department images</h2>";
echo "<ul>";

// Default department image
$defaultImage = createMinimalJpeg("default department");
if (file_put_contents("images/departments/default.jpg", $defaultImage)) {
    chmod("images/departments/default.jpg", 0666);
    echo "<li style='color:green'>Created default.jpg</li>";
} else {
    echo "<li style='color:red'>Failed to create default.jpg</li>";
}

// Department images
foreach ($departmentCodes as $dept) {
    $deptImage = createMinimalJpeg("$dept department");
    if (file_put_contents("images/departments/$dept.jpg", $deptImage)) {
        chmod("images/departments/$dept.jpg", 0666);
        echo "<li style='color:green'>Created $dept.jpg</li>";
    } else {
        echo "<li style='color:red'>Failed to create $dept.jpg</li>";
    }
}
echo "</ul>";

// Create gallery images
echo "<h2>Creating gallery images</h2>";
echo "<ul>";

foreach ($departmentCodes as $dept) {
    for ($i = 1; $i <= 4; $i++) {
        $galleryImage = createMinimalJpeg("$dept gallery $i");
        $filename = "images/departments/gallery/{$dept}{$i}.jpg";
        
        if (file_put_contents($filename, $galleryImage)) {
            chmod($filename, 0666);
            echo "<li style='color:green'>Created gallery image: {$dept}{$i}.jpg</li>";
        } else {
            echo "<li style='color:red'>Failed to create gallery image: {$dept}{$i}.jpg</li>";
        }
    }
}
echo "</ul>";

// Create a minimal PDF file
function createMinimalPdf($text) {
    $pdf = "%PDF-1.4\n";
    $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>\nendobj\n";
    $pdf .= "4 0 obj\n<< /Length 55 >>\nstream\nBT /F1 12 Tf 100 700 Td ($text) Tj ET\nendstream\nendobj\n";
    $pdf .= "xref\n0 5\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n";
    $pdf .= "0000000115 00000 n\n0000000196 00000 n\ntrailer\n<< /Size 5 /Root 1 0 R >>\n";
    $pdf .= "startxref\n300\n%%EOF";
    
    return $pdf;
}

// Create department documents
echo "<h2>Creating department documents</h2>";
echo "<ul>";

$documentTypes = ['handbook', 'syllabus', 'guide'];

foreach ($departmentCodes as $dept) {
    foreach ($documentTypes as $type) {
        $docContent = createMinimalPdf("$dept $type document");
        $filename = "documents/departments/{$dept}_{$type}.pdf";
        
        if (file_put_contents($filename, $docContent)) {
            chmod($filename, 0666);
            echo "<li style='color:green'>Created document: {$dept}_{$type}.pdf</li>";
        } else {
            echo "<li style='color:red'>Failed to create document: {$dept}_{$type}.pdf</li>";
        }
    }
}
echo "</ul>";

// Check if the operations were successful
echo "<h2>Verification</h2>";

$deptImageCount = count(glob("images/departments/*.jpg"));
$galleryImageCount = count(glob("images/departments/gallery/*.jpg"));
$documentCount = count(glob("documents/departments/*.pdf"));

echo "<p>Department images created: $deptImageCount (expected: " . (count($departmentCodes) + 1) . ")</p>";
echo "<p>Gallery images created: $galleryImageCount (expected: " . (count($departmentCodes) * 4) . ")</p>";
echo "<p>Documents created: $documentCount (expected: " . (count($departmentCodes) * count($documentTypes)) . ")</p>";

echo "<h2>Next Steps</h2>";
echo "<p>Files have been created. You can now:</p>";
echo "<ol>";
echo "<li><a href='departments.php'>Go to Departments Page</a></li>";
echo "<li><a href='cleanup.html'>Return to Cleanup Tools</a></li>";
echo "</ol>";
?> 