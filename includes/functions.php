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
    // For now, always return the VVUSRC leadership positions to fix the dropdown
    $portfolios = [
        'President',
        'Vice President',
        'Senate President',
        'Executive Secretary',
        'Finance Officer',
        'Editor',
        'Organizing Secretary',
        'Welfare Officer',
        'Women\'s Commissioner',
        'Sports Commissioner',
        'Chaplain',
        'Public Relations Officer',
        'General'
    ];

    // Sort the portfolios
    sort($portfolios);

    return $portfolios;
}
?> 
