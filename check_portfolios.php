<?php
// Script to check the portfolios in the database
require_once 'db_config.php';

echo "<h1>Portfolio Check Results</h1>";
echo "<pre>";

// Get all portfolios
$sql = "SELECT portfolio_id, title, name, email, photo FROM portfolios ORDER BY portfolio_id";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "Error: " . mysqli_error($conn) . "\n";
    exit;
}

echo "Total portfolios: " . mysqli_num_rows($result) . "\n\n";
echo "ID | Title | Name | Email | Photo\n";
echo str_repeat("-", 80) . "\n";

while ($row = mysqli_fetch_assoc($result)) {
    echo "{$row['portfolio_id']} | {$row['title']} | {$row['name']} | {$row['email']} | {$row['photo']}\n";
}

// Check for the specific portfolios we're looking for
$requiredPortfolios = [
    'Senate President',
    'Women\'s Commissioner',
    'Chaplain',
    'Editor'
];

echo "\nChecking required portfolios:\n";
echo str_repeat("-", 80) . "\n";

foreach ($requiredPortfolios as $title) {
    $sql = "SELECT COUNT(*) as count FROM portfolios WHERE title = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $title);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    echo "{$title}: " . ($row['count'] > 0 ? "FOUND" : "MISSING") . "\n";
    
    mysqli_stmt_close($stmt);
}

echo "</pre>";
echo "<p><a href='run_portfolio_update.html'>Back to Portfolio Management</a></p>";
?> 