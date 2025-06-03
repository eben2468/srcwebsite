<?php
// Include database configuration
require_once 'db_config.php';

// Get all tables
$tables = fetchAll("SHOW TABLES");

echo "<h2>Tables in Database</h2>";
echo "<ul>";
foreach ($tables as $table) {
    $tableName = reset($table); // Get the first value in the array
    echo "<li><a href='check_table_structure.php?table=" . urlencode($tableName) . "'>" . htmlspecialchars($tableName) . "</a></li>";
}
echo "</ul>";
?> 