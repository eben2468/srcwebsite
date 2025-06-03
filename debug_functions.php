<?php
// Debug script to check function availability

// Show all include paths
echo "Include path: " . get_include_path() . "\n";

// Include files
require_once 'functions.php';

// Check if function exists
if (function_exists('getAllPortfolios')) {
    echo "getAllPortfolios() function exists\n";
} else {
    echo "getAllPortfolios() function does not exist\n";
}

// Try calling the function
echo "Calling getAllPortfolios():\n";
var_dump(getAllPortfolios());
?> 