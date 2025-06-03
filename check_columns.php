<?php
// Script to check if the database has the correct columns
require_once 'db_config.php';

echo "Checking database columns...\n";

// Check the current structure of the users table
$columnsSql = "DESCRIBE users";
$columnsResult = mysqli_query($conn, $columnsSql);

if ($columnsResult) {
    echo "Current structure of users table:\n";
    $columns = [];
    while ($column = mysqli_fetch_assoc($columnsResult)) {
        $columns[] = $column['Field'];
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check for required columns
    $requiredColumns = ['profile_picture', 'bio', 'phone'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            $missingColumns[] = $column;
        }
    }
    
    if (empty($missingColumns)) {
        echo "\nAll required columns exist!\n";
    } else {
        echo "\nMissing columns: " . implode(', ', $missingColumns) . "\n";
    }
} else {
    echo "Error fetching table structure: " . mysqli_error($conn) . "\n";
}

echo "\nCheck completed.\n";

// Close connection
mysqli_close($conn);
?> 