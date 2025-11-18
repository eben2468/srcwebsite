<?php
require_once __DIR__ . '/includes/db_config.php';

// Hash the default password 'eben2468'
$hashedPassword = password_hash('eben2468', PASSWORD_DEFAULT);

// Insert or update the admin user
$sql = "INSERT INTO users (email, password, first_name, last_name, role, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
        password = VALUES(password), 
        first_name = VALUES(first_name), 
        last_name = VALUES(last_name), 
        role = VALUES(role), 
        status = VALUES(status)";

try {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", 
        "ebenofficial0@gmail.com", 
        $hashedPassword, 
        "Ebenezer", 
        "Admin", 
        "admin", 
        "active"
    );
    
    if (mysqli_stmt_execute($stmt)) {
        echo "Admin user created/updated successfully!\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Also let's check what users exist
echo "\nExisting users:\n";
$result = mysqli_query($conn, "SELECT user_id, username, email, role FROM users");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- ID: {$row['user_id']}, Username: {$row['username']}, Email: {$row['email']}, Role: {$row['role']}\n";
    }
}
?>