<?php
// Diagnose Chat Messages Foreign Key Constraint Error
// Error: Cannot add or update a child row: a foreign key constraint fails 
// (`src_management_system`.`chat_messages`, CONSTRAINT `chat_messages_ibfk_2` 
// FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE)

require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h2>🔍 Chat Messages Foreign Key Constraint Diagnostic</h2>";

// Check database connection
try {
    $conn = getDBConnection();
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

echo "<hr>";

// 1. Check chat_messages table structure
echo "<h3>1. Chat Messages Table Structure</h3>";
try {
    $result = mysqli_query($conn, "DESCRIBE chat_messages");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Could not describe chat_messages table: " . mysqli_error($conn) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking chat_messages structure: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 2. Check users table structure
echo "<h3>2. Users Table Structure</h3>";
try {
    $result = mysqli_query($conn, "DESCRIBE users");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Could not describe users table: " . mysqli_error($conn) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking users structure: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 3. Check foreign key constraints
echo "<h3>3. Foreign Key Constraints</h3>";
try {
    $result = mysqli_query($conn, "
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME,
            DELETE_RULE,
            UPDATE_RULE
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = 'src_management_system' 
        AND TABLE_NAME = 'chat_messages' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Constraint</th><th>Table</th><th>Column</th><th>References Table</th><th>References Column</th><th>Delete Rule</th><th>Update Rule</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['CONSTRAINT_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($row['TABLE_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($row['COLUMN_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($row['REFERENCED_TABLE_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($row['REFERENCED_COLUMN_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($row['DELETE_RULE'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['UPDATE_RULE'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ No foreign key constraints found for chat_messages table</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking foreign key constraints: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 4. Check existing data in users table
echo "<h3>4. Users Table Data Sample</h3>";
try {
    $result = mysqli_query($conn, "SELECT user_id, username, email, role FROM users LIMIT 10");
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>User ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count total users
        $countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
        $count = mysqli_fetch_assoc($countResult);
        echo "<p><strong>Total users:</strong> " . $count['total'] . "</p>";
    } else {
        echo "<p>⚠️ No users found in users table</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking users data: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 5. Check existing data in chat_messages table
echo "<h3>5. Chat Messages Table Data Sample</h3>";
try {
    $result = mysqli_query($conn, "SELECT message_id, sender_id, message, created_at FROM chat_messages LIMIT 10");
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Message ID</th><th>Sender ID</th><th>Message</th><th>Created At</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['message_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['sender_id']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['message'], 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count total messages
        $countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM chat_messages");
        $count = mysqli_fetch_assoc($countResult);
        echo "<p><strong>Total messages:</strong> " . $count['total'] . "</p>";
    } else {
        echo "<p>⚠️ No messages found in chat_messages table</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking chat_messages data: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 6. Check for orphaned sender_ids
echo "<h3>6. Orphaned Sender IDs Check</h3>";
try {
    $result = mysqli_query($conn, "
        SELECT DISTINCT cm.sender_id 
        FROM chat_messages cm 
        LEFT JOIN users u ON cm.sender_id = u.user_id 
        WHERE u.user_id IS NULL
    ");
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<p>❌ <strong>Found orphaned sender_ids:</strong></p>";
        echo "<ul>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<li>Sender ID: " . htmlspecialchars($row['sender_id']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>✅ No orphaned sender_ids found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking for orphaned sender_ids: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 7. Check current session user
echo "<h3>7. Current Session User</h3>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "<p><strong>Session User ID:</strong> " . htmlspecialchars($_SESSION['user_id']) . "</p>";
    
    // Check if this user exists in database
    $userId = $_SESSION['user_id'];
    $result = mysqli_query($conn, "SELECT user_id, username, email FROM users WHERE user_id = '$userId'");
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        echo "<p>✅ Session user exists in database:</p>";
        echo "<ul>";
        echo "<li>User ID: " . htmlspecialchars($user['user_id']) . "</li>";
        echo "<li>Username: " . htmlspecialchars($user['username']) . "</li>";
        echo "<li>Email: " . htmlspecialchars($user['email']) . "</li>";
        echo "</ul>";
    } else {
        echo "<p>❌ Session user does NOT exist in database</p>";
    }
} else {
    echo "<p>⚠️ No user session found</p>";
}

echo "<hr>";

// 8. Recommendations
echo "<h3>8. Diagnostic Summary & Recommendations</h3>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<h4>Possible Causes:</h4>";
echo "<ol>";
echo "<li><strong>Invalid sender_id:</strong> Trying to insert a message with a sender_id that doesn't exist in the users table</li>";
echo "<li><strong>Session user missing:</strong> Current session user_id doesn't exist in the database</li>";
echo "<li><strong>Data type mismatch:</strong> sender_id and user_id have different data types</li>";
echo "<li><strong>Constraint timing:</strong> Foreign key constraint was added after orphaned data existed</li>";
echo "</ol>";

echo "<h4>Next Steps:</h4>";
echo "<ol>";
echo "<li>Check the diagnostic results above</li>";
echo "<li>If orphaned sender_ids found, clean them up</li>";
echo "<li>If session user missing, fix user authentication</li>";
echo "<li>If data types mismatch, fix table structure</li>";
echo "<li>Test message insertion with valid user_id</li>";
echo "</ol>";
echo "</div>";

mysqli_close($conn);
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h3 { color: #555; margin-top: 20px; }
hr { margin: 20px 0; border: 1px solid #eee; }
</style>