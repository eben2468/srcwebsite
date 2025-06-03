<?php
/**
 * Additional database utility functions for the SRC website
 * 
 * This file contains additional database utility functions not already defined in db_config.php
 */

// Include db_config.php if not already included
require_once 'db_config.php';

/**
 * Count rows from a query
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind to the query
 * @return int|false Number of rows or false on failure
 */
function countRows($sql, $params = []) {
    $result = fetchOne($sql, $params);
    
    if ($result === false) {
        return false;
    }
    
    return (int) reset($result);
}

/**
 * Escape a string for use in a query
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeString($string) {
    global $conn;
    
    return mysqli_real_escape_string($conn, $string);
}
?> 