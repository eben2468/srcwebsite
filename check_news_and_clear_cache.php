<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_config.php';

echo "<h1>News Display Diagnostic</h1>";

// Check if the news table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'news'");
if ($tableCheckResult->num_rows == 0) {
    echo "<p style='color: red;'>Error: The news table does not exist!</p>";
} else {
    echo "<p style='color: green;'>Success: The news table exists.</p>";
    
    // Get table structure
    $structureResult = $conn->query("DESCRIBE news");
    if (!$structureResult) {
        echo "<p style='color: red;'>Error getting table structure: " . $conn->error . "</p>";
    } else {
        echo "<h2>News Table Structure:</h2>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $structureResult->fetch_assoc()) {
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
    }
    
    // Count news items
    $countResult = $conn->query("SELECT COUNT(*) as count FROM news");
    $countRow = $countResult->fetch_assoc();
    echo "<p>Total news items: " . $countRow['count'] . "</p>";
    
    // Check recent news
    $recentNewsResult = $conn->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 3");
    if (!$recentNewsResult) {
        echo "<p style='color: red;'>Error fetching recent news: " . $conn->error . "</p>";
    } else {
        echo "<h2>Recent News Items:</h2>";
        
        if ($recentNewsResult->num_rows == 0) {
            echo "<p style='color: orange;'>No news items found in the database.</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Author ID</th><th>Created At</th><th>Status</th></tr>";
            
            while ($row = $recentNewsResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['news_id'] . "</td>";
                echo "<td>" . $row['title'] . "</td>";
                echo "<td>" . $row['author_id'] . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
}

// Clear PHP's internal cache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p>PHP OPcache cleared.</p>";
}

echo "<h2>Actions:</h2>";
echo "<ul>";
echo "<li><a href='pages_php/dashboard.php'>Return to Dashboard</a></li>";
echo "<li><a href='pages_php/news.php'>View News Page</a></li>";
echo "</ul>";

$conn->close();
?> 