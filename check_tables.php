<?php
// Include database configuration
require_once 'db_config.php';

// Check tables in the database
$result = mysqli_query($conn, 'SHOW TABLES');
echo "<h2>Tables in the database:</h2>\n";
echo "<ul>\n";
$tableFound = false;
while ($row = mysqli_fetch_row($result)) {
    echo "<li>" . $row[0] . "</li>\n";
    if ($row[0] == 'user_activities') {
        $tableFound = true;
    }
}
echo "</ul>\n";

echo "<h2>User Activities Table Check:</h2>\n";
if ($tableFound) {
    echo "<p>user_activities table exists!</p>\n";
    
    // Check structure
    $columnsResult = mysqli_query($conn, 'SHOW COLUMNS FROM user_activities');
    echo "<h3>Columns in user_activities table:</h3>\n";
    echo "<ul>\n";
    while ($column = mysqli_fetch_assoc($columnsResult)) {
        echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>\n";
    }
    echo "</ul>\n";
} else {
    echo "<p>user_activities table does not exist!</p>\n";
}

// Check if users table exists and check its structure
$usersResult = mysqli_query($conn, 'SHOW TABLES LIKE "users"');
if (mysqli_num_rows($usersResult) > 0) {
    $columnsResult = mysqli_query($conn, 'SHOW COLUMNS FROM users');
    echo "<h2>Columns in users table:</h2>\n";
    echo "<ul>\n";
    while ($column = mysqli_fetch_assoc($columnsResult)) {
        echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>\n";
    }
    echo "</ul>\n";
    
    // Check if there are any users
    $usersCountResult = mysqli_query($conn, 'SELECT COUNT(*) as count FROM users');
    $usersCount = mysqli_fetch_assoc($usersCountResult);
    echo "<p>Number of users: " . $usersCount['count'] . "</p>\n";
    
    if ($usersCount['count'] > 0) {
        $usersDataResult = mysqli_query($conn, 'SELECT user_id, username, role FROM users LIMIT 5');
        echo "<h2>Sample users:</h2>\n";
        echo "<ul>\n";
        while ($user = mysqli_fetch_assoc($usersDataResult)) {
            echo "<li>ID: " . $user['user_id'] . ", Username: " . $user['username'] . ", Role: " . $user['role'] . "</li>\n";
        }
        echo "</ul>\n";
    }
} else {
    echo "<p>Users table does not exist!</p>\n";
}

// Check if budgets table exists
$budgetsResult = mysqli_query($conn, 'SHOW TABLES LIKE "budgets"');
if (mysqli_num_rows($budgetsResult) > 0) {
    echo "<p>Budgets table exists.</p>\n";
    
    // Check if there are any budgets
    $budgetsCountResult = mysqli_query($conn, 'SELECT COUNT(*) as count FROM budgets');
    $budgetsCount = mysqli_fetch_assoc($budgetsCountResult);
    echo "<p>Number of budgets: " . $budgetsCount['count'] . "</p>\n";
} else {
    echo "<p>Budgets table does not exist!</p>\n";
}

// Check if budget_items table exists
$budgetItemsResult = mysqli_query($conn, 'SHOW TABLES LIKE "budget_items"');
if (mysqli_num_rows($budgetItemsResult) > 0) {
    echo "<p>Budget items table exists.</p>\n";
} else {
    echo "<p>Budget items table does not exist!</p>\n";
}
?> 