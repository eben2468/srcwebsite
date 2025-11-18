<?php
/**
 * General utility functions for the SRC website
 * 
 * This file contains various utility functions used throughout the site
 */

/**
 * Get all available portfolios from the database or return a default list
 * 
 * @return array Array of portfolios
 */
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
?> 
