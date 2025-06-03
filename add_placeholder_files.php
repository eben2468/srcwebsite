<?php
// Add placeholder files for department images, gallery images, and documents
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 120);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Add Department Placeholder Files</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #28a745; }
        h2 { color: #0275d8; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow: auto; }
        ul { list-style-type: none; padding-left: 10px; }
        li { margin-bottom: 8px; }
        .summary { background-color: #e7f4ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .category { margin-bottom: 30px; padding: 15px; border-radius: 5px; background-color: #f8f9fa; }
        .action-button {
            display: inline-block;
            padding: 8px 15px;
            background-color: #0275d8;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .placeholder-example {
            display: inline-block;
            width: 150px;
            height: 100px;
            background-color: #3498db;
            color: white;
            text-align: center;
            line-height: 100px;
            margin: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Add Department Placeholder Files</h1>
    <p>This script creates placeholder files for department images, gallery images, and documents.</p>";

// Department codes
$departmentCodes = ['nursa', 'themsa', 'edsa', 'cossa', 'dessa', 'sobsa'];

// Document types
$documentTypes = ['handbook', 'syllabus', 'guide', 'curriculum', 'brochure', 'timetable'];

// Check directory existence and create if needed
$directories = [
    'images', 
    'images/departments', 
    'images/departments/gallery', 
    'documents', 
    'documents/departments'
];

// Create directories if they don't exist
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (@mkdir($dir, 0777, true)) {
            echo "<p><span class='success'>Created directory:</span> $dir</p>";
        } else {
            echo "<p><span class='error'>Failed to create directory:</span> $dir</p>";
        }
    } else {
        // Set permissions on existing directory
        @chmod($dir, 0777);
        echo "<p>Directory exists: $dir</p>";
    }
}

// Counters
$createdDeptImages = 0;
$createdGalleryImages = 0;
$createdDocuments = 0;
$failures = 0;

// Function to create a minimal valid JPEG file
function createJpegData($text) {
    // JPEG header with minimal valid structure
    $jpegHeader = pack("C*", 
        0xFF, 0xD8,                  // SOI marker
        0xFF, 0xE0,                  // APP0 marker
        0x00, 0x10,                  // APP0 header size (16 bytes)
        0x4A, 0x46, 0x49, 0x46, 0x00, // JFIF identifier
        0x01, 0x01,                  // JFIF version
        0x00,                        // Density units
        0x00, 0x01,                  // X density
        0x00, 0x01,                  // Y density
        0x00, 0x00                   // Thumbnail
    );
    
    // Create some data for the image
    $imageData = $jpegHeader . str_repeat('X', 2048) . $text;
    
    // JPEG end marker
    $imageData .= pack("C*", 0xFF, 0xD9);
    
    return $imageData;
}

// Function to create a minimal valid PDF file
function createPdfData($text) {
    // Very basic PDF structure
    $pdf = "%PDF-1.4\n";
    $pdf .= "1 0 obj\n<</Type /Catalog /Pages 2 0 R>>\nendobj\n";
    $pdf .= "2 0 obj\n<</Type /Pages /Kids [3 0 R] /Count 1>>\nendobj\n";
    $pdf .= "3 0 obj\n<</Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 500 800] /Contents 6 0 R>>\nendobj\n";
    $pdf .= "4 0 obj\n<</Font <</F1 5 0 R>>>>\nendobj\n";
    $pdf .= "5 0 obj\n<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>\nendobj\n";
    $pdf .= "6 0 obj\n<</Length " . strlen($text) . ">>\nstream\nBT /F1 24 Tf 50 700 Td ($text) Tj ET\nendstream\nendobj\n";
    $pdf .= "xref\n0 7\n0000000000 65535 f\n0000000010 00000 n\n0000000056 00000 n\n0000000111 00000 n\n";
    $pdf .= "0000000212 00000 n\n0000000250 00000 n\n0000000317 00000 n\ntrailer\n<</Size 7 /Root 1 0 R>>\nstartxref\n406\n%%EOF";
    
    return $pdf;
}

// STEP 1: Create department images
echo "<div class='category'>";
echo "<h2>Creating Department Images</h2>";
echo "<ul>";

// Default department image
$defaultPath = "images/departments/default.jpg";
$defaultContent = createJpegData("Default Department Image");

if (file_put_contents($defaultPath, $defaultContent)) {
    chmod($defaultPath, 0666);
    echo "<li>Created: <strong>default.jpg</strong> <span class='success'>SUCCESS</span></li>";
    $createdDeptImages++;
} else {
    echo "<li>Failed to create: <strong>default.jpg</strong> <span class='error'>FAILED</span></li>";
    $failures++;
}

// Department images
foreach ($departmentCodes as $code) {
    $deptPath = "images/departments/{$code}.jpg";
    $deptContent = createJpegData("{$code} Department Image");
    
    if (file_put_contents($deptPath, $deptContent)) {
        chmod($deptPath, 0666);
        echo "<li>Created: <strong>{$code}.jpg</strong> <span class='success'>SUCCESS</span></li>";
        $createdDeptImages++;
    } else {
        echo "<li>Failed to create: <strong>{$code}.jpg</strong> <span class='error'>FAILED</span></li>";
        $failures++;
    }
}
echo "</ul>";
echo "</div>";

// STEP 2: Create gallery images
echo "<div class='category'>";
echo "<h2>Creating Gallery Images</h2>";
echo "<ul>";

foreach ($departmentCodes as $code) {
    echo "<li>Department: <strong>$code</strong>";
    echo "<ul>";
    
    for ($i = 1; $i <= 4; $i++) {
        $galleryPath = "images/departments/gallery/{$code}{$i}.jpg";
        $galleryContent = createJpegData("{$code} Gallery Image {$i}");
        
        if (file_put_contents($galleryPath, $galleryContent)) {
            chmod($galleryPath, 0666);
            echo "<li>Created: <strong>{$code}{$i}.jpg</strong> <span class='success'>SUCCESS</span></li>";
            $createdGalleryImages++;
        } else {
            echo "<li>Failed to create: <strong>{$code}{$i}.jpg</strong> <span class='error'>FAILED</span></li>";
            $failures++;
        }
    }
    
    echo "</ul>";
    echo "</li>";
}
echo "</ul>";
echo "</div>";

// STEP 3: Create department documents
echo "<div class='category'>";
echo "<h2>Creating Department Documents</h2>";
echo "<ul>";

foreach ($departmentCodes as $code) {
    echo "<li>Department: <strong>$code</strong>";
    echo "<ul>";
    
    foreach ($documentTypes as $type) {
        // Create different patterns for document names
        $docPatterns = [
            "{$code}_{$type}.pdf",        // Standard pattern
            "{$code}-{$type}.pdf",        // With dash
            "vvu_{$code}_{$type}.pdf",    // With prefix
            "{$code}_department_{$type}.pdf" // With department word
        ];
        
        // Just create the standard pattern for simplicity
        $docPath = "documents/departments/{$code}_{$type}.pdf";
        $docContent = createPdfData("{$code} Department {$type}");
        
        if (file_put_contents($docPath, $docContent)) {
            chmod($docPath, 0666);
            echo "<li>Created: <strong>{$code}_{$type}.pdf</strong> <span class='success'>SUCCESS</span></li>";
            $createdDocuments++;
        } else {
            echo "<li>Failed to create: <strong>{$code}_{$type}.pdf</strong> <span class='error'>FAILED</span></li>";
            $failures++;
        }
    }
    
    echo "</ul>";
    echo "</li>";
}
echo "</ul>";
echo "</div>";

// Summary
echo "<div class='summary'>";
echo "<h2>Creation Summary</h2>";
echo "<p><strong>Department images created:</strong> $createdDeptImages</p>";
echo "<p><strong>Gallery images created:</strong> $createdGalleryImages</p>";
echo "<p><strong>Documents created:</strong> $createdDocuments</p>";
echo "<p><strong>Total files created:</strong> " . ($createdDeptImages + $createdGalleryImages + $createdDocuments) . "</p>";

if ($failures > 0) {
    echo "<p><strong>Failed creations:</strong> <span class='error'>$failures</span></p>";
    echo "<p>Some files could not be created. Please check directory permissions.</p>";
} else {
    echo "<p class='success'>All files created successfully!</p>";
}
echo "</div>";

// Example placeholders
echo "<h2>Example Placeholders</h2>";
echo "<div>";
echo "<div class='placeholder-example'>Department</div>";
echo "<div class='placeholder-example'>Gallery</div>";
echo "<div class='placeholder-example'>Document</div>";
echo "</div>";

// Next steps
echo "<h2>Next Steps</h2>";
echo "<p>Now that placeholder files have been created, you can:</p>";
echo "<ol>";
echo "<li><a href='departments.php'>Go to Departments Page</a> to view the results</li>";
echo "<li><a href='gallery_uploader.php'>Go to Gallery Uploader</a> to manage gallery images</li>";
echo "<li><a href='cleanup.html'>Return to Cleanup Tools</a> for more options</li>";
echo "</ol>";

echo "</body></html>";
?> 