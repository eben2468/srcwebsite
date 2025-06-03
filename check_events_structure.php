<?php
// Include database connection
require_once 'db_config.php';

echo "<h1>Events Table Structure Check</h1>";

// Check if the events table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'events'");
if ($tableCheckResult->num_rows == 0) {
    echo "<p style='color: red;'>The events table does not exist!</p>";
    exit;
}

// Get table structure
$structureResult = $conn->query("DESCRIBE events");
if (!$structureResult) {
    echo "<p style='color: red;'>Error getting table structure: " . $conn->error . "</p>";
    exit;
}

echo "<h2>Table Structure:</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $structureResult->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Check for sample data
$dataResult = $conn->query("SELECT * FROM events LIMIT 5");
if (!$dataResult) {
    echo "<p style='color: red;'>Error getting table data: " . $conn->error . "</p>";
    exit;
}

echo "<h2>Sample Data:</h2>";
if ($dataResult->num_rows == 0) {
    echo "<p>No data found in events table.</p>";
} else {
    // Get field names
    $fields = [];
    $finfo = $dataResult->fetch_fields();
    foreach ($finfo as $field) {
        $fields[] = $field->name;
    }
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr>";
    foreach ($fields as $field) {
        echo "<th>" . $field . "</th>";
    }
    echo "</tr>";
    
    // Reset data pointer
    $dataResult->data_seek(0);
    
    // Display data
    while ($row = $dataResult->fetch_assoc()) {
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<td>" . (isset($row[$field]) ? htmlspecialchars($row[$field]) : "NULL") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// Check for date columns
echo "<h2>Date Columns:</h2>";
$dateColumns = [];
$structureResult->data_seek(0);
while ($row = $structureResult->fetch_assoc()) {
    if (strpos(strtolower($row['Type']), 'date') !== false) {
        $dateColumns[] = $row['Field'];
    }
}

if (empty($dateColumns)) {
    echo "<p>No date columns found in the events table.</p>";
} else {
    echo "<p>Date columns found: " . implode(", ", $dateColumns) . "</p>";
}

$conn->close();
?> 