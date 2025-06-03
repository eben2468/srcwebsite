<?php
// Script to create profiles for existing users
require_once 'db_config.php';

echo "<h1>Creating Profiles for Existing Users</h1>";

// Create user_profiles table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query to create table
if (mysqli_query($conn, $createTableSQL)) {
    echo "<p>User profiles table created or already exists.</p>";
} else {
    echo "<p>Error creating user profiles table: " . mysqli_error($conn) . "</p>";
    exit;
}

// Get all users who don't have profiles yet
$sql = "SELECT u.* FROM users u 
        LEFT JOIN user_profiles p ON u.user_id = p.user_id 
        WHERE p.profile_id IS NULL";

$users = fetchAll($sql);

if (empty($users)) {
    echo "<p>No users without profiles found.</p>";
} else {
    echo "<p>Found " . count($users) . " users without profiles.</p>";
    
    $createdCount = 0;
    $errorCount = 0;
    
    foreach ($users as $user) {
        // Use username as the full name if no first/last name available
        $fullName = $user['username'];
        
        // Try to create a profile for this user
        try {
            $insertSql = "INSERT INTO user_profiles (user_id, full_name) VALUES (?, ?)";
            $profileId = insert($insertSql, [$user['user_id'], $fullName]);
            
            if ($profileId) {
                $createdCount++;
                echo "<p>Created profile for user: " . htmlspecialchars($fullName) . "</p>";
            } else {
                $errorCount++;
                echo "<p>Failed to create profile for user: " . htmlspecialchars($fullName) . "</p>";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "<p>Error creating profile for user: " . htmlspecialchars($fullName) . " - " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>Summary</h2>";
    echo "<p>Created profiles for $createdCount users.</p>";
    if ($errorCount > 0) {
        echo "<p>Failed to create profiles for $errorCount users.</p>";
    }
}

echo "<p><a href='list_tables.php'>View All Tables</a> | <a href='pages_php/dashboard.php'>Go to Dashboard</a></p>";
?> 