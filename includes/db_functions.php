<?php
/**
 * Additional database utility functions for the SRC website
 * 
 * This file contains additional database utility functions not already defined in db_config.php
 */

// Include db_config.php if not already included
require_once __DIR__ . '/db_config.php';

/**
 * Execute a SQL query with parameters
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters (optional)
 * @return bool Success or failure
 */
function execute($sql, $params = [], $types = '') {
    $conn = getDbConnection();

    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        return false;
    }
    
    if (!empty($params)) {
        // If types string is not provided but params exist, create types string
        if (empty($types)) {
            $types = str_repeat('s', count($params));
        }
        
        // Bind parameters
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    // Execute the statement
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * Get the last inserted ID
 *
 * @return int|false Last insert ID or false on failure
 */
function getLastInsertId() {
    $conn = getDbConnection();
    return mysqli_insert_id($conn);
}

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
    $conn = getDbConnection();

    return mysqli_real_escape_string($conn, $string);
}

/**
 * Get SMS configuration from the database
 * 
 * @return array SMS configuration
 */
function getSMSConfig() {
    // Default configuration
    $defaultConfig = [
        'default_provider' => 'none',
        'twilio_account_sid' => '',
        'twilio_auth_token' => '',
        'twilio_phone_number' => '',
        'infobip_base_url' => '',
        'infobip_api_key' => '',
        'infobip_sender' => '',
        'zenoph_api_key' => '',
        'zenoph_sender' => '',
        'zenoph_host' => 'api.smsonlinegh.com'
    ];

    // Get configuration from database using config table
    $sql = "SELECT config_value FROM config WHERE config_name = 'sms_config'";
    $result = fetchOne($sql);

    if ($result === false || empty($result['config_value'])) {
        return $defaultConfig;
    }

    // Decode JSON config
    $config = json_decode($result['config_value'], true);

    if (!is_array($config)) {
        return $defaultConfig;
    }

    // Merge with default config to ensure all keys exist
    return array_merge($defaultConfig, $config);
}

/**
 * Save SMS configuration to the database
 * 
 * @param array $config SMS configuration
 * @return bool True on success, false on failure
 */
function saveSMSConfig($config) {
    if (!is_array($config)) {
        return false;
    }
    
    // Encode config as JSON
    $configJson = json_encode($config);
    
    // Check if config already exists
    $sql = "SELECT * FROM config WHERE config_name = 'sms_config'";
    $result = fetchOne($sql);
    
    if ($result === false) {
        // Insert new config
        $sql = "INSERT INTO config (config_name, config_value) VALUES ('sms_config', ?)";
        return execute($sql, [$configJson]);
    } else {
        // Update existing config
        $sql = "UPDATE config SET config_value = ? WHERE config_name = 'sms_config'";
        return execute($sql, [$configJson]);
    }
}

/**
 * Get WhatsApp configuration from the database
 * 
 * @return array WhatsApp configuration
 */
function getWhatsAppConfig() {
    // Default configuration
    $defaultConfig = [
        'default_provider' => 'none',
        'twilio_account_sid' => '',
        'twilio_auth_token' => '',
        'twilio_phone_number' => '',
        'infobip_base_url' => '',
        'infobip_api_key' => '',
        'infobip_sender' => ''
    ];
    
    // Get configuration from database
    $sql = "SELECT * FROM config WHERE config_name = 'whatsapp_config'";
    $result = fetchOne($sql);
    
    if ($result === false || empty($result['config_value'])) {
        return $defaultConfig;
    }
    
    // Decode JSON config
    $config = json_decode($result['config_value'], true);
    
    if (!is_array($config)) {
        return $defaultConfig;
    }
    
    // Merge with default config to ensure all keys exist
    return array_merge($defaultConfig, $config);
}

/**
 * Save WhatsApp configuration to the database
 * 
 * @param array $config WhatsApp configuration
 * @return bool True on success, false on failure
 */
function saveWhatsAppConfig($config) {
    if (!is_array($config)) {
        return false;
    }
    
    // Encode config as JSON
    $configJson = json_encode($config);
    
    // Check if config already exists
    $sql = "SELECT * FROM config WHERE config_name = 'whatsapp_config'";
    $result = fetchOne($sql);
    
    if ($result === false) {
        // Insert new config
        $sql = "INSERT INTO config (config_name, config_value) VALUES ('whatsapp_config', ?)";
        return execute($sql, [$configJson]);
    } else {
        // Update existing config
        $sql = "UPDATE config SET config_value = ? WHERE config_name = 'whatsapp_config'";
        return execute($sql, [$configJson]);
    }
}

/**
 * Get a PDO connection to the database
 * 
 * @return PDO Returns a PDO connection object
 */
function getConnection() {
    // Use the database credentials from db_config.php
    $dsn = 'mysql:host=' . DB_SERVER . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    try {
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log the error
        logDbError('PDO Connection failed: ' . $e->getMessage());
        throw $e;
    }
}
?> 
