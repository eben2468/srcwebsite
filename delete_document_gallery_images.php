<?php
// Delete all documents and gallery images from departments
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 120);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Delete Department Documents and Gallery Images</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #d9534f; }
        h2 { color: #0275d8; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .document-icon { font-size: 24px; color: #2e86de; margin-right: 10px; }
        .gallery-icon { font-size: 24px; color: #e74c3c; margin-right: 10px; }
        ul { list-style-type: none; padding-left: 10px; }
        li { margin-bottom: 8px; }
        .target-list { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .summary { background-color: #e7f4ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Delete Department Documents and Gallery Images</h1>
    <p>This script will delete all documents and gallery images for all departments.</p>";

// Department codes
$departmentCodes = ['nursa', 'themsa', 'edsa', 'cossa', 'dessa', 'sobsa'];

// Document types from the screenshot
$documentTypes = ['handbook', 'syllabus', 'guide'];

// Check directories exist
$galleriesExist = file_exists('images/departments/gallery');
$documentsExist = file_exists('documents/departments');

// Count deleted files
$deletedGalleryImages = 0;
$deletedDocuments = 0;
$failedDeletions = 0;

// List of files to delete
echo "<div class='target-list'>";
echo "<h2>Files Targeted for Deletion:</h2>";

// 1. Gallery Images
echo "<h3><span class='gallery-icon'>🖼️</span> Gallery Images:</h3>";
echo "<ul>";
foreach ($departmentCodes as $code) {
    echo "<li><strong>$code</strong>: ";
    $galleryFiles = [];
    for ($i = 1; $i <= 4; $i++) {
        $galleryFiles[] = strtolower($code) . $i . ".jpg";
    }
    echo implode(', ', $galleryFiles);
    echo "</li>";
}
echo "</ul>";

// 2. Documents
echo "<h3><span class='document-icon'>📄</span> Department Documents:</h3>";
echo "<ul>";
foreach ($departmentCodes as $code) {
    echo "<li><strong>$code</strong>: ";
    $docFiles = [];
    foreach ($documentTypes as $type) {
        $docFiles[] = strtolower($code) . "_" . $type . ".pdf";
    }
    echo implode(', ', $docFiles);
    echo "</li>";
}
echo "</ul>";
echo "</div>";

// PROCESS 1: Delete Gallery Images
echo "<h2>Deleting Gallery Images</h2>";

if ($galleriesExist) {
    echo "<ul>";
    foreach ($departmentCodes as $code) {
        $codeLower = strtolower($code);
        for ($i = 1; $i <= 4; $i++) {
            $galleryPath = "images/departments/gallery/{$codeLower}{$i}.jpg";
            
            echo "<li>Deleting {$code} gallery image {$i}: ";
            if (file_exists($galleryPath)) {
                if (@unlink($galleryPath)) {
                    echo "<span class='success'>SUCCESS</span>";
                    $deletedGalleryImages++;
                } else {
                    echo "<span class='error'>FAILED</span>";
                    $failedDeletions++;
                }
            } else {
                echo "<span class='warning'>NOT FOUND</span>";
            }
            echo "</li>";
        }
    }
    echo "</ul>";
    
    // Also try to remove any other gallery files
    $otherGalleryFiles = glob('images/departments/gallery/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    if (!empty($otherGalleryFiles)) {
        echo "<h3>Cleaning up additional gallery files:</h3>";
        echo "<ul>";
        foreach ($otherGalleryFiles as $file) {
            echo "<li>Deleting: " . basename($file) . "... ";
            if (@unlink($file)) {
                echo "<span class='success'>SUCCESS</span>";
                $deletedGalleryImages++;
            } else {
                echo "<span class='error'>FAILED</span>";
                $failedDeletions++;
            }
            echo "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='warning'>Gallery directory not found.</p>";
}

// PROCESS 2: Delete Documents
echo "<h2>Deleting Department Documents</h2>";

if ($documentsExist) {
    echo "<ul>";
    foreach ($departmentCodes as $code) {
        $codeLower = strtolower($code);
        foreach ($documentTypes as $type) {
            $docPath = "documents/departments/{$codeLower}_{$type}.pdf";
            
            echo "<li>Deleting {$code} {$type}: ";
            if (file_exists($docPath)) {
                if (@unlink($docPath)) {
                    echo "<span class='success'>SUCCESS</span>";
                    $deletedDocuments++;
                } else {
                    echo "<span class='error'>FAILED</span>";
                    $failedDeletions++;
                }
            } else {
                echo "<span class='warning'>NOT FOUND</span>";
            }
            echo "</li>";
        }
    }
    echo "</ul>";
    
    // Also try to remove any other document files
    $otherDocFiles = glob('documents/departments/*.{pdf,doc,docx}', GLOB_BRACE);
    if (!empty($otherDocFiles)) {
        echo "<h3>Cleaning up additional document files:</h3>";
        echo "<ul>";
        foreach ($otherDocFiles as $file) {
            echo "<li>Deleting: " . basename($file) . "... ";
            if (@unlink($file)) {
                echo "<span class='success'>SUCCESS</span>";
                $deletedDocuments++;
            } else {
                echo "<span class='error'>FAILED</span>";
                $failedDeletions++;
            }
            echo "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='warning'>Documents directory not found.</p>";
}

// Create empty directories if they don't exist
$directories = [
    'images/departments/gallery',
    'documents/departments'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (@mkdir($dir, 0777, true)) {
            echo "<p class='success'>Created directory: $dir</p>";
        } else {
            echo "<p class='error'>Failed to create directory: $dir</p>";
        }
    }
    @chmod($dir, 0777);
}

// Summary
echo "<div class='summary'>";
echo "<h2>Deletion Summary</h2>";
echo "<p><strong>Gallery images deleted:</strong> $deletedGalleryImages</p>";
echo "<p><strong>Documents deleted:</strong> $deletedDocuments</p>";
if ($failedDeletions > 0) {
    echo "<p><strong>Failed deletions:</strong> <span class='error'>$failedDeletions</span></p>";
    echo "<p>Some files could not be deleted. You may need to use the <a href='force_cleanup.php'>Force Cleanup</a> tool.</p>";
} else {
    echo "<p class='success'>All target files were successfully deleted!</p>";
}
echo "</div>";

// Next steps
echo "<h2>Next Steps</h2>";
echo "<p>You can now:</p>";
echo "<ol>";
echo "<li><a href='list_remaining_files.php'>Check remaining files</a> to verify deletion</li>";
echo "<li><a href='departments.php'>Go to Departments Page</a> to view the changes</li>";
echo "<li><a href='cleanup.html'>Return to Cleanup Tools</a> for more options</li>";
echo "</ol>";

echo "</body></html>";
?> 