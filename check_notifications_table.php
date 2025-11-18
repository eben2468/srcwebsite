<?php
/**
 * Quick Check of Notifications Table Structure
 */

require_once 'includes/db_config.php';

echo "<h2>Current Notifications Table Structure</h2>";

try {
    $result = mysqli_query($conn, "DESCRIBE notifications");
    
    if ($result) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $columns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Column Analysis:</h3>";
        echo "<p>Columns found: " . implode(', ', $columns) . "</p>";
        
        $required = ['created_by', 'expiry_date'];
        $missing = array_diff($required, $columns);
        
        if (!empty($missing)) {
            echo "<p style='color: red;'><strong>Missing columns: " . implode(', ', $missing) . "</strong></p>";
            echo "<p>These columns need to be added to fix the messaging service error.</p>";
        } else {
            echo "<p style='color: green;'><strong>All required columns are present!</strong></p>";
        }
        
    } else {
        echo "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
}
?>