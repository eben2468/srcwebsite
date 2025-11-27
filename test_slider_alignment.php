<?php
// Test script to check slider alignment database column
// Run this file in your browser to verify the text_alignment column exists

require_once __DIR__ . '/includes/db_config.php';

echo "<h2>Slider Alignment Database Check</h2>";
echo "<hr>";

// Check if text_alignment column exists
$checkColumn = "SHOW COLUMNS FROM slider_images LIKE 'text_alignment'";
$result = mysqli_query($conn, $checkColumn);

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'><strong>✓ Column 'text_alignment' EXISTS in slider_images table</strong></p>";
    
    // Get all sliders and their alignment
    $query = "SELECT id, title, text_alignment FROM slider_images";
    $slidersResult = mysqli_query($conn, $query);
    
    if ($slidersResult && mysqli_num_rows($slidersResult) > 0) {
        echo "<h3>Current Slider Alignments:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Text Alignment</th></tr>";
        
        while ($row = mysqli_fetch_assoc($slidersResult)) {
            $alignment = $row['text_alignment'] ?? 'NULL';
            $alignmentClass = 'align-' . ($alignment !== 'NULL' ? $alignment : 'center');
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($alignment) . "</strong> (CSS: " . $alignmentClass . ")</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><em>No sliders found in database</em></p>";
    }
    
} else {
    echo "<p style='color: red;'><strong>✗ Column 'text_alignment' DOES NOT EXIST</strong></p>";
    echo "<p>You need to run the SQL migration:</p>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd;'>";
    echo "ALTER TABLE slider_images \n";
    echo "ADD COLUMN text_alignment VARCHAR(20) DEFAULT 'center' AFTER button2_link;\n\n";
    echo "UPDATE slider_images \n";
    echo "SET text_alignment = 'center' \n";
    echo "WHERE text_alignment IS NULL OR text_alignment = '';";
    echo "</pre>";
    echo "<p><strong>To fix:</strong> Open phpMyAdmin, select your database, go to SQL tab, and paste the above SQL.</p>";
}

echo "<hr>";
echo "<h3>Alignment Classes Available:</h3>";
$alignments = [
    'top-left' => 'Top Left Corner',
    'top-center' => 'Top Center',
    'top-right' => 'Top Right Corner',
    'center-left' => 'Center Left',
    'center' => 'Center (Default)',
    'center-right' => 'Center Right',
    'bottom-left' => 'Bottom Left Corner',
    'bottom-center' => 'Bottom Center',
    'bottom-right' => 'Bottom Right Corner'
];

echo "<ul>";
foreach ($alignments as $value => $label) {
    echo "<li><code>align-$value</code> - $label</li>";
}
echo "</ul>";

mysqli_close($conn);
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
    table { width: 100%; }
    th { background: #333; color: white; text-align: left; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
</style>
