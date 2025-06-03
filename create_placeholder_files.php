<?php
// Script to create placeholder files for departments
header('Content-Type: text/plain');

echo "Creating placeholder files for departments...\n";

// Directories
$imageDir = 'images/departments/';
$galleryDir = $imageDir . 'gallery/';
$documentDir = 'documents/departments/';

// Ensure directories exist
foreach ([$imageDir, $galleryDir, $documentDir] as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Created directory: $dir\n";
        } else {
            echo "Failed to create directory: $dir\n";
        }
    } else {
        echo "Directory already exists: $dir\n";
    }
}

// Department data
$departments = [
    ['code' => 'NURSA', 'name' => 'School of Nursing and Midwifery'],
    ['code' => 'THEMSA', 'name' => 'School of Theology and Mission'],
    ['code' => 'EDSA', 'name' => 'School of Education'],
    ['code' => 'COSSA', 'name' => 'Faculty of Science'],
    ['code' => 'DESSA', 'name' => 'Development Studies'],
    ['code' => 'SOBSA', 'name' => 'School of Business']
];

// Create placeholder files
echo "\nCreating placeholder image files:\n";

// Create default image placeholder
$defaultImagePath = $imageDir . 'default.jpg';
file_put_contents($defaultImagePath, 'Placeholder for default department image');
echo "Created placeholder: $defaultImagePath\n";

foreach ($departments as $dept) {
    $code = strtolower($dept['code']);
    
    // Main department image
    $imagePath = $imageDir . $code . '.jpg';
    file_put_contents($imagePath, 'Placeholder for ' . $dept['name'] . ' image');
    echo "Created placeholder: $imagePath\n";
    
    // Gallery images
    for ($i = 1; $i <= 4; $i++) {
        $galleryPath = $galleryDir . $code . $i . '.jpg';
        file_put_contents($galleryPath, 'Placeholder for ' . $dept['name'] . ' gallery image ' . $i);
        echo "Created placeholder: $galleryPath\n";
    }
    
    // Document placeholders
    $docTypes = ['handbook', 'syllabus', 'guide'];
    foreach ($docTypes as $type) {
        $docPath = $documentDir . $code . '_' . $type . '.pdf';
        file_put_contents($docPath, 'Placeholder for ' . $dept['name'] . ' ' . $type . ' document');
        echo "Created placeholder: $docPath\n";
    }
}

echo "\nPlaceholder files creation completed successfully!\n";
echo "Note: These are NOT actual image or PDF files, but placeholders for testing.\n";
echo "In a production environment, you would need to upload actual files.\n";
?> 