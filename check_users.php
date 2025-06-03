<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database directly
$conn = mysqli_connect("localhost", "root", "", "src_management_system");

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

// Get users
echo "USERS IN DATABASE:\n";
$result = mysqli_query($conn, "SELECT user_id, username, email, role FROM users LIMIT 5");

// Check if query was successful
if (!$result) {
    echo "Error: " . mysqli_error($conn);
    exit();
}

// Print users
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "User ID: " . $row['user_id'] . ", Username: " . $row['username'] . ", Role: " . ($row['role'] ?? 'N/A') . "\n";
    }
} else {
    echo "No users found.\n";
}

echo "\nChecking departments table:\n";
$deptResult = mysqli_query($conn, "SHOW TABLES LIKE 'departments'");
if (mysqli_num_rows($deptResult) > 0) {
    $deptsQuery = mysqli_query($conn, "SELECT * FROM departments LIMIT 5");
    if ($deptsQuery) {
        if (mysqli_num_rows($deptsQuery) > 0) {
            while ($dept = mysqli_fetch_assoc($deptsQuery)) {
                echo "Department ID: " . $dept['department_id'] . ", Name: " . $dept['name'] . "\n";
            }
        } else {
            echo "No departments found.\n";
        }
    } else {
        echo "Error querying departments: " . mysqli_error($conn);
    }
} else {
    echo "Departments table does not exist.\n";
}

mysqli_close($conn);
?> 