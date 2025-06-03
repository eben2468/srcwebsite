<?php
// Fix document deletion by checking for all possible naming patterns
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 120);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Document Deletion</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #d9534f; }
        h2 { color: #0275d8; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow: auto; }
        ul { list-style-type: none; padding-left: 10px; }
        li { margin-bottom: 8px; }
        .debug-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #ddd; }
        .summary { background-color: #e7f4ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Advanced Document & Gallery Cleanup</h1>
    <p>This script will attempt to find and delete department documents and gallery images using multiple naming patterns.</p>";

// Department codes
$departmentCodes = ['nursa', 'themsa', 'edsa', 'cossa', 'dessa', 'sobsa'];

// Document base names
$documentTypes = ['handbook', 'syllabus', 'guide', 'manual', 'notes', 'curriculum', 'brochure', 'prospectus', 'timetable', 'calendar'];

// Check directory permissions and create if needed
$directories = ['documents/departments', 'images/departments/gallery'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (@mkdir($dir, 0777, true)) {
            echo "<p class='success'>Created directory: $dir</p>";
        } else {
            echo "<p class='error'>Failed to create directory: $dir</p>";
        }
    } else {
        @chmod($dir, 0777); // Set permissions
        echo "<p>Directory exists: $dir</p>";
    }
}

// Counters
$deletedDocs = 0;
$deletedGallery = 0;
$failedDeletions = 0;

// STEP 1: Debug - List all files in directories
echo "<div class='debug-box'>";
echo "<h2>Debug: Current Directory Contents</h2>";

// Show document directory contents
echo "<h3>Documents Directory:</h3>";
$docFiles = glob("documents/departments/*.*");
if (empty($docFiles)) {
    echo "<p>No files found in documents/departments</p>";
} else {
    echo "<ul>";
    foreach ($docFiles as $file) {
        echo "<li>" . basename($file) . "</li>";
    }
    echo "</ul>";
}

// Show gallery directory contents
echo "<h3>Gallery Directory:</h3>";
$galleryFiles = glob("images/departments/gallery/*.*");
if (empty($galleryFiles)) {
    echo "<p>No files found in images/departments/gallery</p>";
} else {
    echo "<ul>";
    foreach ($galleryFiles as $file) {
        echo "<li>" . basename($file) . "</li>";
    }
    echo "</ul>";
}
echo "</div>";

// STEP 2: Advanced document detection & deletion
echo "<h2>Deleting Department Documents</h2>";

// Define different naming patterns to try
$namingPatterns = [
    // Standard patterns
    '{dept}_{type}.pdf',
    '{dept}-{type}.pdf',
    '{dept}{type}.pdf',
    '{type}_{dept}.pdf',
    '{type}-{dept}.pdf',
    // Uppercase variations
    '{DEPT}_{type}.pdf',
    '{dept}_{TYPE}.pdf',
    '{DEPT}_{TYPE}.pdf',
    // With spaces
    '{dept} {type}.pdf',
    '{type} {dept}.pdf',
    // Other document types
    '{dept}_{type}.doc',
    '{dept}_{type}.docx',
    '{dept}_{type}.txt',
    // Other patterns observed
    '{dept}_vvusrc_{type}.pdf',
    'vvu_{dept}_{type}.pdf',
    '{dept}_student_{type}.pdf',
    '{dept}_department_{type}.pdf'
];

echo "<ul>";
// Try to find and delete files with various patterns
foreach ($departmentCodes as $code) {
    echo "<li>Searching documents for <strong>$code</strong>: ";
    
    $found = false;
    
    // Just look for any file with department code in it
    $matchingFiles = glob("documents/departments/*{$code}*.*");
    if (!empty($matchingFiles)) {
        echo "<span class='success'>Found " . count($matchingFiles) . " files</span>";
        echo "<ul>";
        foreach ($matchingFiles as $file) {
            echo "<li>Deleting: " . basename($file) . "... ";
            if (@unlink($file)) {
                echo "<span class='success'>SUCCESS</span>";
                $deletedDocs++;
                $found = true;
            } else {
                echo "<span class='error'>FAILED</span>";
                $failedDeletions++;
            }
            echo "</li>";
        }
        echo "</ul>";
    } else {
        // Try all possible patterns for document types
        foreach ($documentTypes as $type) {
            foreach ($namingPatterns as $pattern) {
                // Replace placeholders with actual values
                $filename = str_replace(
                    ['{dept}', '{type}', '{DEPT}', '{TYPE}'], 
                    [strtolower($code), strtolower($type), strtoupper($code), strtoupper($type)], 
                    $pattern
                );
                
                $filepath = "documents/departments/$filename";
                
                if (file_exists($filepath)) {
                    echo "<span class='success'>Found: $filename</span>";
                    echo "<ul><li>Deleting: $filename... ";
                    
                    if (@unlink($filepath)) {
                        echo "<span class='success'>SUCCESS</span>";
                        $deletedDocs++;
                        $found = true;
                    } else {
                        echo "<span class='error'>FAILED</span>";
                        $failedDeletions++;
                    }
                    
                    echo "</li></ul>";
                }
            }
        }
    }
    
    if (!$found) {
        echo "<span class='warning'>No files found</span>";
    }
    
    echo "</li>";
}
echo "</ul>";

// STEP 3: Delete all remaining files in documents directory
$remainingDocs = glob("documents/departments/*.*");
if (!empty($remainingDocs)) {
    echo "<h3>Cleaning up remaining document files:</h3>";
    echo "<ul>";
    foreach ($remainingDocs as $file) {
        echo "<li>Deleting: " . basename($file) . "... ";
        if (@unlink($file)) {
            echo "<span class='success'>SUCCESS</span>";
            $deletedDocs++;
        } else {
            echo "<span class='error'>FAILED</span>";
            $failedDeletions++;
        }
        echo "</li>";
    }
    echo "</ul>";
}

// STEP 4: Delete gallery images
echo "<h2>Deleting Gallery Images</h2>";

// Delete gallery images with various naming patterns
echo "<ul>";
foreach ($departmentCodes as $code) {
    echo "<li>Searching gallery images for <strong>$code</strong>: ";
    
    // Find files with department code in the name
    $matchingGallery = glob("images/departments/gallery/*{$code}*.*");
    
    if (!empty($matchingGallery)) {
        echo "<span class='success'>Found " . count($matchingGallery) . " files</span>";
        echo "<ul>";
        foreach ($matchingGallery as $file) {
            echo "<li>Deleting: " . basename($file) . "... ";
            if (@unlink($file)) {
                echo "<span class='success'>SUCCESS</span>";
                $deletedGallery++;
            } else {
                echo "<span class='error'>FAILED</span>";
                $failedDeletions++;
            }
            echo "</li>";
        }
        echo "</ul>";
    } else {
        // Try standard gallery patterns (1-4)
        $found = false;
        for ($i = 1; $i <= 4; $i++) {
            $galleryPath = "images/departments/gallery/{$code}{$i}.jpg";
            if (file_exists($galleryPath)) {
                if (!$found) {
                    echo "<span class='success'>Found standard gallery files</span><ul>";
                    $found = true;
                }
                
                echo "<li>Deleting: {$code}{$i}.jpg... ";
                if (@unlink($galleryPath)) {
                    echo "<span class='success'>SUCCESS</span>";
                    $deletedGallery++;
                } else {
                    echo "<span class='error'>FAILED</span>";
                    $failedDeletions++;
                }
                echo "</li>";
            }
        }
        
        if ($found) {
            echo "</ul>";
        } else {
            echo "<span class='warning'>No files found</span>";
        }
    }
    
    echo "</li>";
}
echo "</ul>";

// STEP 5: Delete all remaining gallery files
$remainingGallery = glob("images/departments/gallery/*.*");
if (!empty($remainingGallery)) {
    echo "<h3>Cleaning up remaining gallery files:</h3>";
    echo "<ul>";
    foreach ($remainingGallery as $file) {
        echo "<li>Deleting: " . basename($file) . "... ";
        if (@unlink($file)) {
            echo "<span class='success'>SUCCESS</span>";
            $deletedGallery++;
        } else {
            echo "<span class='error'>FAILED</span>";
            $failedDeletions++;
        }
        echo "</li>";
    }
    echo "</ul>";
}

// Summary
echo "<div class='summary'>";
echo "<h2>Cleanup Summary</h2>";
echo "<p><strong>Documents deleted:</strong> $deletedDocs</p>";
echo "<p><strong>Gallery images deleted:</strong> $deletedGallery</p>";
echo "<p><strong>Total files deleted:</strong> " . ($deletedDocs + $deletedGallery) . "</p>";
if ($failedDeletions > 0) {
    echo "<p><strong>Failed deletions:</strong> <span class='error'>$failedDeletions</span></p>";
    echo "<p>Some files could not be deleted. You may need to check permissions or if the files are in use.</p>";
} else {
    echo "<p class='success'>All operations completed successfully.</p>";
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