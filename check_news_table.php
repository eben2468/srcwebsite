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

// Check if news table exists
echo "Checking for news table...\n";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'news'");

if (mysqli_num_rows($result) > 0) {
    echo "News table exists. Checking structure:\n";
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM news");
    while ($col = mysqli_fetch_assoc($columns)) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} else {
    echo "News table does not exist.\n";
}

// Check for announcements table
echo "\nChecking for announcements table...\n";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'announcements'");

if (mysqli_num_rows($result) > 0) {
    echo "Announcements table exists. Checking structure:\n";
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM announcements");
    while ($col = mysqli_fetch_assoc($columns)) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} else {
    echo "Announcements table does not exist.\n";
}

mysqli_close($conn);
?> 