<?php
// Include database configuration
require_once 'db_config.php';

// Start output buffering
ob_start();

// Check the structure of the budgets table
$query = "SHOW COLUMNS FROM budgets";
try {
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        echo "<h2>Budgets Table Structure:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
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
    } else {
        echo "Error fetching table structure: " . mysqli_error($conn);
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}

// Check the structure of the budget_items table
$query = "SHOW COLUMNS FROM budget_items";
try {
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        echo "<h2>Budget Items Table Structure:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
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
    } else {
        echo "Error fetching budget_items table structure: " . mysqli_error($conn);
    }
} catch (Exception $e) {
    echo "Exception checking budget_items: " . $e->getMessage();
}

// Get the ID column name from the budgets table
$query = "SHOW KEYS FROM budgets WHERE Key_name = 'PRIMARY'";
try {
    $result = mysqli_query($conn, $query);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        echo "<h3>Primary Key Column for budgets: " . $row['Column_name'] . "</h3>";
    } else {
        echo "Error fetching primary key: " . mysqli_error($conn);
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}

// Check if budget_items table exists
$query = "SHOW TABLES LIKE 'budget_items'";
try {
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<p>budget_items table exists</p>";
    } else {
        echo "<p>budget_items table does not exist</p>";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}

// Check foreign keys in budget_items table
$query = "SHOW CREATE TABLE budget_items";
try {
    $result = mysqli_query($conn, $query);
    
    if ($result && $row = mysqli_fetch_array($result)) {
        echo "<h3>Budget Items Table Definition:</h3>";
        echo "<pre>" . htmlspecialchars($row[1]) . "</pre>";
    } else {
        echo "Error fetching table definition: " . mysqli_error($conn);
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}

// Get the output buffer content
$output = ob_get_clean();

// Save to file
file_put_contents('db_check_results.html', $output);

// Also output to screen
echo $output;
?> 