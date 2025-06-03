<?php
// Script to list all users in the database
require_once 'db_config.php';

echo "<h2>Users in Database</h2>";

// Get all users
$sql = "SELECT user_id, username, email, role, status, created_at FROM users ORDER BY user_id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th>";
    echo "<th>Username</th>";
    echo "<th>Email</th>";
    echo "<th>Role</th>";
    echo "<th>Status</th>";
    echo "<th>Created</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No users found in the database.</p>";
}

// Check if there's a specific user with admin role
$adminSql = "SELECT * FROM users WHERE role = 'admin' LIMIT 1";
$adminResult = mysqli_query($conn, $adminSql);

echo "<h3>Admin User Check</h3>";

if (mysqli_num_rows($adminResult) > 0) {
    $adminUser = mysqli_fetch_assoc($adminResult);
    echo "<p style='color: green;'>Admin user found!</p>";
    echo "<p>Username: " . htmlspecialchars($adminUser['username']) . "</p>";
    echo "<p>Email: " . htmlspecialchars($adminUser['email']) . "</p>";
    echo "<p>Status: " . htmlspecialchars($adminUser['status']) . "</p>";
    
    // Provide login link with this admin's credentials
    echo "<p><strong>You can log in with:</strong></p>";
    echo "<p>Email: " . htmlspecialchars($adminUser['email']) . "</p>";
    echo "<p>Password: admin123 (if you've reset it using reset_admin_password.php)</p>";
} else {
    echo "<p style='color: red;'>No admin user found!</p>";
}

mysqli_close($conn);

echo "<p><a href='pages_php/login.php'>Go to Login Page</a></p>";
echo "<p><a href='reset_admin_password.php'>Reset Admin Password</a></p>";

// Get users
$users = fetchAll("SELECT * FROM users LIMIT 5");

// Get column names
$columns = fetchAll("SHOW COLUMNS FROM users");
$columnNames = array_column($columns, 'Field');

echo "<h2>Users Table Structure</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr>";
foreach ($columnNames as $columnName) {
    echo "<th>" . htmlspecialchars($columnName) . "</th>";
}
echo "</tr>";

// Output user data
foreach ($users as $user) {
    echo "<tr>";
    foreach ($columnNames as $columnName) {
        if ($columnName === 'password') {
            echo "<td>[HIDDEN]</td>";
        } else {
            echo "<td>" . htmlspecialchars($user[$columnName] ?? '') . "</td>";
        }
    }
    echo "</tr>";
}

echo "</table>";
?> 