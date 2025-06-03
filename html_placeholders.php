<?php
// HTML placeholders for departments.php to avoid GD dependency
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>HTML Placeholders</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #0275d8; }
        h2 { color: #0275d8; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow: auto; max-height: 300px; }
        .summary { background-color: #e7f4ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }
        .placeholder {
            width: 200px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            border-radius: 5px;
        }
        .dept-nursa { background-color: #3498db; }
        .dept-themsa { background-color: #e74c3c; }
        .dept-edsa { background-color: #2ecc71; }
        .dept-cossa { background-color: #f39c12; }
        .dept-dessa { background-color: #9b59b6; }
        .dept-sobsa { background-color: #1abc9c; }
        .gallery {
            width: 150px;
            height: 100px;
            background-color: #34495e;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0275d8;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>HTML Placeholders</h1>
    <p>This script will modify your departments page to use HTML blocks instead of images for placeholders.</p>";

// Backup the original departments.php file
$departmentsFile = 'departments.php';
$backupFile = 'departments.php.bak';

if (!file_exists($departmentsFile)) {
    echo "<p class='error'>Error: departments.php file not found!</p>";
    exit;
}

// Create backup if it doesn't exist
if (!file_exists($backupFile)) {
    if (copy($departmentsFile, $backupFile)) {
        echo "<p class='success'>Created backup of departments.php</p>";
    } else {
        echo "<p class='error'>Failed to create backup file. Aborting.</p>";
        exit;
    }
} else {
    echo "<p class='warning'>Backup file already exists. Continuing without creating a new backup.</p>";
}

// Read the departments.php file
$content = file_get_contents($departmentsFile);

if ($content === false) {
    echo "<p class='error'>Error: Could not read departments.php</p>";
    exit;
}

// Function to generate random color
function randomColor() {
    $colors = [
        '#3498db', '#e74c3c', '#2ecc71', '#f39c12', 
        '#9b59b6', '#1abc9c', '#34495e', '#d35400',
        '#27ae60', '#2980b9', '#8e44ad', '#16a085'
    ];
    return $colors[array_rand($colors)];
}

// Define department colors
$deptColors = [
    'nursa' => '#3498db',   // Blue
    'themsa' => '#e74c3c',  // Red
    'edsa' => '#2ecc71',    // Green
    'cossa' => '#f39c12',   // Orange
    'dessa' => '#9b59b6',   // Purple
    'sobsa' => '#1abc9c'    // Turquoise
];

// Department codes
$departments = array_keys($deptColors);

// 1. Modify the department image display code to use divs instead of <img> tags
echo "<h2>Modifying Department Images</h2>";

// Look for patterns like <img src="images/departments/<?php echo $row['dept_code']; ?>.jpg" alt="Department Image">
$imagePattern = '/<img\s+src="images\/departments\/\<\?php\s+echo\s+\$row\[\'dept_code\'\];\s+\?>.jpg"\s+[^>]*>/i';

// New HTML block replacement
$imageReplacement = '<div class="dept-placeholder" style="width: 200px; height: 150px; background-color: <?php echo getColorForDept($row[\'dept_code\']); ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border-radius: 5px;"><?php echo strtoupper($row[\'dept_code\']); ?></div>';

// First look for matches to see what we're replacing
preg_match_all($imagePattern, $content, $matches);

if (!empty($matches[0])) {
    echo "<p class='success'>Found " . count($matches[0]) . " department image patterns to replace.</p>";
    
    // Show first match
    echo "<pre>" . htmlspecialchars($matches[0][0]) . "</pre>";
    
    // Replace the image tags
    $content = preg_replace($imagePattern, $imageReplacement, $content);
} else {
    echo "<p class='warning'>No department image patterns found. Trying alternative pattern.</p>";
    
    // Try another common pattern
    $altPattern = '/<img\s+src="images\/departments\/([^"]+)\.jpg"\s+[^>]*>/i';
    preg_match_all($altPattern, $content, $matches);
    
    if (!empty($matches[0])) {
        echo "<p class='success'>Found " . count($matches[0]) . " alternative department image patterns.</p>";
        
        // Replace with a more generic solution
        $altReplacement = '<div style="width: 200px; height: 150px; background-color: #3498db; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border-radius: 5px;">DEPARTMENT</div>';
        $content = preg_replace($altPattern, $altReplacement, $content);
    } else {
        echo "<p class='error'>Could not find department image patterns in the file.</p>";
    }
}

// 2. Modify the gallery image display code
echo "<h2>Modifying Gallery Images</h2>";

// Look for patterns like <img src="images/departments/gallery/<?php echo $dept_code; ?>1.jpg">
$galleryPattern = '/<img\s+src="images\/departments\/gallery\/\<\?php\s+echo\s+\$dept_code;\s+\?>\d+\.jpg"\s+[^>]*>/i';

// New gallery placeholder
$galleryReplacement = '<div style="width: 150px; height: 100px; background-color: <?php echo getColorForDept($dept_code); ?>; opacity: 0.8; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border-radius: 5px;"><?php echo $dept_code . "-" . $i; ?></div>';

// Check for gallery matches
preg_match_all($galleryPattern, $content, $matches);

if (!empty($matches[0])) {
    echo "<p class='success'>Found " . count($matches[0]) . " gallery image patterns to replace.</p>";
    
    // Show example
    echo "<pre>" . htmlspecialchars($matches[0][0]) . "</pre>";
    
    // Replace gallery images
    $content = preg_replace($galleryPattern, $galleryReplacement, $content);
} else {
    echo "<p class='warning'>No gallery image patterns found. Trying alternative pattern.</p>";
    
    // Try another pattern for galleries
    $altGalleryPattern = '/<img\s+src="images\/departments\/gallery\/([^"]+)\.jpg"\s+[^>]*>/i';
    preg_match_all($altGalleryPattern, $content, $matches);
    
    if (!empty($matches[0])) {
        echo "<p class='success'>Found " . count($matches[0]) . " alternative gallery patterns.</p>";
        
        $altGalleryReplacement = '<div style="width: 150px; height: 100px; background-color: #34495e; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border-radius: 5px;">GALLERY</div>';
        $content = preg_replace($altGalleryPattern, $altGalleryReplacement, $content);
    } else {
        echo "<p class='error'>Could not find gallery image patterns in the file.</p>";
    }
}

// 3. Add the getColorForDept function to the top of the file
$functionCode = '
<?php
// Function to get color for department
function getColorForDept($dept_code) {
    $colors = [
        "nursa" => "#3498db",   // Blue
        "themsa" => "#e74c3c",  // Red
        "edsa" => "#2ecc71",    // Green
        "cossa" => "#f39c12",   // Orange
        "dessa" => "#9b59b6",   // Purple
        "sobsa" => "#1abc9c"    // Turquoise
    ];
    
    if (isset($colors[$dept_code])) {
        return $colors[$dept_code];
    }
    
    return "#34495e"; // Default dark blue
}
?>';

// Check if the function already exists
if (strpos($content, 'function getColorForDept') === false) {
    // Find opening PHP tag
    $phpOpenTag = '<?php';
    
    if (strpos($content, $phpOpenTag) !== false) {
        // Add after the first PHP tag
        $content = preg_replace('/(<\?php)/', '$1' . PHP_EOL . '// HTML Placeholder functions - Added automatically' . PHP_EOL . 'function getColorForDept($dept_code) {
    $colors = [
        "nursa" => "#3498db",   // Blue
        "themsa" => "#e74c3c",  // Red
        "edsa" => "#2ecc71",    // Green
        "cossa" => "#f39c12",   // Orange
        "dessa" => "#9b59b6",   // Purple
        "sobsa" => "#1abc9c"    // Turquoise
    ];
    
    if (isset($colors[$dept_code])) {
        return $colors[$dept_code];
    }
    
    return "#34495e"; // Default dark blue
}', $content, 1);
        echo "<p class='success'>Added getColorForDept function to the file.</p>";
    } else {
        // Prepend the function code
        $content = $functionCode . $content;
        echo "<p class='warning'>Could not find PHP opening tag. Prepended function code.</p>";
    }
} else {
    echo "<p class='warning'>getColorForDept function already exists. Skipping.</p>";
}

// Write the modified content back to the file
if (file_put_contents($departmentsFile, $content)) {
    echo "<p class='success'>Successfully updated departments.php with HTML placeholders!</p>";
} else {
    echo "<p class='error'>Failed to write to departments.php. Check file permissions.</p>";
}

// Preview of placeholders
echo "<h2>Placeholder Preview</h2>";
echo "<p>Here's what your department placeholders will look like:</p>";

echo "<div class='preview'>";
foreach ($deptColors as $dept => $color) {
    echo "<div class='placeholder dept-$dept' style='background-color: $color;'>" . strtoupper($dept) . "</div>";
}
echo "</div>";

echo "<h2>Gallery Placeholder Preview</h2>";
echo "<div class='preview'>";
for ($i = 1; $i <= 4; $i++) {
    echo "<div class='placeholder gallery'>GALLERY $i</div>";
}
echo "</div>";

// Next steps
echo "<div class='summary'>";
echo "<h2>Summary</h2>";
echo "<p>HTML placeholders have been installed. This method:</p>";
echo "<ul>";
echo "<li>Uses colored blocks instead of actual images</li>";
echo "<li>Doesn't require the GD library</li>";
echo "<li>Assigns each department a unique color</li>";
echo "<li>Shows text labels instead of broken image icons</li>";
echo "</ul>";

echo "<p>If you need to restore the original file:</p>";
echo "<pre>copy departments.php.bak departments.php</pre>";
echo "</div>";

echo "<a href='departments.php' class='btn'>View Departments Page</a> ";
echo "<a href='cleanup.html' class='btn'>Return to Cleanup Tools</a>";

echo "</body></html>";
?> 