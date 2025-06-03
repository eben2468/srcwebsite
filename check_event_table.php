<?php
// Include database configuration
require_once 'db_config.php';

// Buffer the output
ob_start();

echo "<h1>Events Table Structure</h1>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

// Check event table structure
$result = mysqli_query($conn, 'DESCRIBE events');
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

// Check if there's a portfolio_id column
$portfolioColumn = mysqli_query($conn, "SHOW COLUMNS FROM events LIKE 'portfolio_id'");
if (mysqli_num_rows($portfolioColumn) > 0) {
    echo "<p>The events table has a portfolio_id column.</p>";
} else {
    echo "<p>The events table does not have a portfolio_id column.</p>";
}

// Check for department_id column
$departmentColumn = mysqli_query($conn, "SHOW COLUMNS FROM events LIKE 'department_id'");
if (mysqli_num_rows($departmentColumn) > 0) {
    echo "<p>The events table has a department_id column.</p>";
} else {
    echo "<p>The events table does not have a department_id column.</p>";
}

echo "<h2>Available Portfolios from reports table:</h2>";
echo "<ul>";

// Get available portfolios from reports table
$portfoliosQuery = mysqli_query($conn, "SELECT DISTINCT portfolio FROM reports ORDER BY portfolio");
if ($portfoliosQuery) {
    while ($portfolio = mysqli_fetch_assoc($portfoliosQuery)) {
        echo "<li>" . $portfolio['portfolio'] . "</li>";
    }
} else {
    echo "<li>Error fetching portfolios: " . mysqli_error($conn) . "</li>";
}
echo "</ul>";

// Get the buffered content
$content = ob_get_clean();

// Write to file
file_put_contents('event_table_info.html', $content);

// Display in browser
echo $content;

// Close connection
mysqli_close($conn);
?> 