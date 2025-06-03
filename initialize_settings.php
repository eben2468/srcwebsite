<?php
// Include required files
require_once 'db_config.php';
require_once 'settings_functions.php';

// Default settings
$settings = [
    'general' => [
        'site_name' => 'SRC Management System',
        'welcome_message' => 'Welcome to the SRC Management System',
        'contact_email' => 'src@example.com',
        'support_phone' => '+1234567890',
        'timezone' => 'Africa/Accra'
    ],
    'features' => [
        'enable_elections' => true,
        'enable_documents' => true,
        'enable_news' => true,
        'enable_budget' => true
    ],
    'appearance' => [
        'primary_color' => '#0d6efd',
        'logo_url' => '../images/logo.png',
        'system_icon' => 'university',
        'logo_type' => 'icon',
        'footer_text' => '© 2023 SRC Management System. All rights reserved.',
        'theme_mode' => 'system'
    ],
    'security' => [
        'password_expiry_days' => 90,
        'max_login_attempts' => 5,
        'session_timeout_minutes' => 30,
        'require_2fa' => false
    ]
];

// Create settings table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) NOT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query to create table
mysqli_query($conn, $createTableSQL);

// Initialize settings
foreach ($settings as $group => $groupSettings) {
    foreach ($groupSettings as $key => $value) {
        // Convert boolean values to strings for storage
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        
        // Check if setting exists
        $checkSql = "SELECT COUNT(*) as count FROM settings WHERE setting_key = '$key'";
        $result = mysqli_query($conn, $checkSql);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] > 0) {
            // Update existing setting
            $updateSql = "UPDATE settings SET setting_value = '$value', setting_group = '$group' WHERE setting_key = '$key'";
            mysqli_query($conn, $updateSql);
            echo "Updated setting: $key = $value ($group)\n";
        } else {
            // Insert new setting
            $insertSql = "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES ('$key', '$value', '$group')";
            mysqli_query($conn, $insertSql);
            echo "Inserted setting: $key = $value ($group)\n";
        }
    }
}

echo "\nSettings initialization complete!\n";
?> 