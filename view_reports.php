<?php
// Include database configuration
require_once 'db_config.php';

echo "<h1>Reports in Database</h1>";

// Query to get all reports
$sql = "SELECT * FROM reports ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Title</th>";
    echo "<th>Author</th>";
    echo "<th>Date</th>";
    echo "<th>Type</th>";
    echo "<th>Portfolio</th>";
    echo "<th>Summary</th>";
    echo "<th>File Path</th>";
    echo "<th>Featured</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['report_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['author']) . "</td>";
        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['portfolio']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['summary'], 0, 50)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['file_path']) . "</td>";
        echo "<td>" . ($row['featured'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No reports found in the database.</p>";
}

// Close connection
mysqli_close($conn);
?> 