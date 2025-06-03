<?php
// Simple test script for getAllPortfolios function

// Include database configuration
require_once 'db_config.php';

// Define the function here to make sure it works
function getAllPortfolios() {
    global $conn;
    
    $portfolios = [];
    
    // Try to get portfolios from the database
    try {
        $portfoliosSql = "SELECT DISTINCT portfolio FROM reports ORDER BY portfolio";
        $result = mysqli_query($conn, $portfoliosSql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $portfolios[] = $row['portfolio'];
            }
        }
    } catch (Exception $e) {
        // Just handle silently and use default list
    }
    
    // If no portfolios found in database, use a standard list
    if (empty($portfolios)) {
        $portfolios = [
            'President',
            'Secretary General',
            'Treasurer',
            'Academic Affairs',
            'Sports & Culture',
            'Student Welfare',
            'International Students',
            'General'
        ];
    }
    
    // Sort the portfolios
    sort($portfolios);
    
    return $portfolios;
}

// Output function result
echo "Portfolio function result:\n";
$portfolios = getAllPortfolios();
foreach ($portfolios as $portfolio) {
    echo "- " . $portfolio . "\n";
}

echo "\nDone testing.";
?> 