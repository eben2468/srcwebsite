<?php
/**
 * Database Configuration File
 * This file contains the database connection settings
 */

// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');        // Default XAMPP username
define('DB_PASSWORD', '');            // Default XAMPP password (empty)
define('DB_NAME', 'src_management_system');

// Global connection variable
$conn = null;

// Function to establish database connection
function getDbConnection() {
    global $conn;

    if ($conn === null) {
        // First try to connect to the specific database
        $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

        // If that fails, try to connect without database and create it
        if (!$conn) {
            $tempConn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
            if ($tempConn) {
                // Create database if it doesn't exist
                $createDbSql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                if (mysqli_query($tempConn, $createDbSql)) {
                    mysqli_close($tempConn);
                    // Now try to connect to the newly created database
                    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
                } else {
                    mysqli_close($tempConn);
                }
            }
        }

        // If still no connection, throw an error
        if (!$conn) {
            throw new Exception("ERROR: Could not connect to database. " . mysqli_connect_error());
        }

        // Set charset to ensure proper storage of all characters
        mysqli_set_charset($conn, "utf8mb4");
    }

    return $conn;
}

// Attempt to establish initial connection
try {
    $conn = getDbConnection();
} catch (Exception $e) {
    // Log error but don't die immediately - let individual functions handle it
    error_log("Database connection error: " . $e->getMessage());
}

/**
 * Helper function to run database queries safely
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters (i for integer, s for string, d for double, b for blob)
 * @return mysqli_stmt|false Returns the prepared statement or false on failure
 * @throws mysqli_sql_exception Throws exception on query failure if mysqli report mode is set to throw
 */
function executeQuery($sql, $params = [], $types = '') {
    // Get database connection
    $conn = getDbConnection();

    // Enable exception mode for this function
    $previous_report_mode = mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt && !empty($params)) {
            // If types string is not provided but params exist, create types string
            if (empty($types)) {
                $types = str_repeat('s', count($params));
            }
            
            // Bind parameters
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        // Execute the statement
        if ($stmt) {
            mysqli_stmt_execute($stmt);
        }
        
        // Restore previous report mode
        mysqli_report($previous_report_mode);
        
        return $stmt;
    } catch (mysqli_sql_exception $e) {
        // Restore previous report mode
        mysqli_report($previous_report_mode);
        
        // Log the error
        logDbError($e->getMessage(), $sql, $params);
        
        // Re-throw the exception for handling at a higher level
        throw $e;
    }
}

/**
 * Helper function to fetch all rows from a query
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters
 * @return array Returns an array of rows or empty array on failure
 */
function fetchAll($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    
    if (!$stmt) {
        return [];
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $rows;
}

/**
 * Helper function to fetch a single row from a query
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters
 * @return array|null Returns a row or null on failure
 */
function fetchOne($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    
    if (!$stmt) {
        return null;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    mysqli_stmt_close($stmt);
    return $row;
}

/**
 * Helper function to insert data and return the last inserted id
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters
 * @return int|false Returns the last inserted id or false on failure
 * @throws mysqli_sql_exception Throws exception on query failure
 */
function insert($sql, $params = [], $types = '') {
    try {
        $stmt = executeQuery($sql, $params, $types);

        if (!$stmt) {
            return false;
        }

        $conn = getDbConnection();
        $insertId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        return $insertId;
    } catch (mysqli_sql_exception $e) {
        // Exception already logged by executeQuery
        throw $e; // Re-throw for higher level handling
    }
}

/**
 * Helper function to update data and return affected rows
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters
 * @return int|false Returns the number of affected rows or false on failure
 */
function update($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    
    if (!$stmt) {
        return false;
    }
    
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    return $affectedRows;
}

/**
 * Helper function to delete data and return affected rows
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters
 * @return int|false Returns the number of affected rows or false on failure
 */
function delete($sql, $params = [], $types = '') {
    return update($sql, $params, $types);
}

/**
 * Helper function to log database errors to file
 *
 * @param string $message Error message
 * @param string $sql SQL query (optional)
 * @param array $params Parameters (optional)
 * @return void
 */
function logDbError($message, $sql = '', $params = []) {
    $logFile = __DIR__ . '/db_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] Error: {$message}";
    
    if (!empty($sql)) {
        $logMessage .= "\nSQL: {$sql}";
    }
    
    if (!empty($params)) {
        $logMessage .= "\nParams: " . json_encode($params);
    }
    
    // Add database error if available
    try {
        $conn = getDbConnection();
        if ($conn && mysqli_error($conn)) {
            $logMessage .= "\nMySQL Error: " . mysqli_error($conn);
        }
    } catch (Exception $e) {
        $logMessage .= "\nConnection Error: " . $e->getMessage();
    }
    
    $logMessage .= "\n\n";
    
    // Append to log file
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Also log to PHP error log
    error_log("Database error: {$message}");
}
?> 
