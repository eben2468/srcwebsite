<?php
// Include database configuration
require_once 'db_config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Events Table</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #4b6cb7; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Fix Events Table Structure</h1>";

// Test database connection
if (!$conn) {
    echo "<p class='error'>Database connection failed!</p>";
    exit;
}

// Check if events table exists
$tableExists = mysqli_query($conn, "SHOW TABLES LIKE 'events'");

if (mysqli_num_rows($tableExists) == 0) {
    echo "<p class='error'>Events table does not exist. Please run setup_events_table.php first.</p>";
    exit;
}

// Check the action to take
if (!isset($_POST['action'])) {
    echo "<p>The events table column names need to be fixed. Please choose an action:</p>";
    echo "<form method='post'>
        <button type='submit' name='action' value='rename_columns' style='background: #4b6cb7; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; margin-right: 10px;'>Rename Columns</button>
        <button type='submit' name='action' value='recreate_table' style='background: #dc3545; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;'>Recreate Table</button>
    </form>";
    exit;
}

// Rename columns action
if ($_POST['action'] === 'rename_columns') {
    // Check current column names
    $result = mysqli_query($conn, "DESCRIBE events");
    $hasEventName = false;
    $hasEventDate = false;
    
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['Field'] === 'event_name') {
            $hasEventName = true;
        }
        if ($row['Field'] === 'event_date') {
            $hasEventDate = true;
        }
    }
    
    echo "<h2>Renaming Columns</h2>";
    
    // Rename event_name to name
    if ($hasEventName) {
        $alterSql = "ALTER TABLE events CHANGE event_name name VARCHAR(255) NOT NULL";
        if (mysqli_query($conn, $alterSql)) {
            echo "<p class='success'>Successfully renamed event_name column to name.</p>";
        } else {
            echo "<p class='error'>Error renaming event_name column: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p class='warning'>Column event_name not found or already renamed.</p>";
    }
    
    // Rename event_date to date
    if ($hasEventDate) {
        $alterSql = "ALTER TABLE events CHANGE event_date date DATE NOT NULL";
        if (mysqli_query($conn, $alterSql)) {
            echo "<p class='success'>Successfully renamed event_date column to date.</p>";
        } else {
            echo "<p class='error'>Error renaming event_date column: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p class='warning'>Column event_date not found or already renamed.</p>";
    }
    
    echo "<h2>Table Structure After Renaming</h2>";
    $tableInfo = mysqli_query($conn, "DESCRIBE events");
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($tableInfo)) {
        print_r($row);
    }
    echo "</pre>";
    
    echo "<p class='success'>Events table columns have been renamed. You can now use the events pages.</p>";
    echo "<p><a href='pages_php/events.php'>Go to Events Page</a></p>";
} 
// Recreate table action
else if ($_POST['action'] === 'recreate_table') {
    echo "<h2>Recreating Events Table</h2>";
    
    // Backup existing data
    $backupTable = "events_backup_" . date('Ymd_His');
    $backupSql = "CREATE TABLE $backupTable LIKE events";
    
    if (mysqli_query($conn, $backupSql)) {
        $insertBackupSql = "INSERT INTO $backupTable SELECT * FROM events";
        if (mysqli_query($conn, $insertBackupSql)) {
            echo "<p class='success'>Successfully created backup table $backupTable with existing data.</p>";
        } else {
            echo "<p class='error'>Error copying data to backup table: " . mysqli_error($conn) . "</p>";
            exit;
        }
    } else {
        echo "<p class='error'>Error creating backup table: " . mysqli_error($conn) . "</p>";
        exit;
    }
    
    // Drop existing table
    if (mysqli_query($conn, "DROP TABLE events")) {
        echo "<p class='success'>Successfully dropped existing events table.</p>";
    } else {
        echo "<p class='error'>Error dropping events table: " . mysqli_error($conn) . "</p>";
        exit;
    }
    
    // Create new table with correct column names
    $createTableSQL = "CREATE TABLE events (
        event_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        date DATE NOT NULL,
        end_date DATE NULL,
        location VARCHAR(255) NOT NULL,
        status ENUM('Planning', 'Upcoming', 'Ongoing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Planning',
        description TEXT,
        organizer VARCHAR(255),
        capacity INT DEFAULT 0,
        registrations INT DEFAULT 0,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
    )";
    
    if (mysqli_query($conn, $createTableSQL)) {
        echo "<p class='success'>Successfully created new events table with correct column names.</p>";
        
        // Copy data from backup with column name adjustments
        $copyDataSql = "INSERT INTO events (
            event_id, name, date, end_date, location, status, description, 
            organizer, capacity, registrations, created_by, created_at, updated_at
        ) 
        SELECT 
            event_id, 
            " . (mysqli_query($conn, "SHOW COLUMNS FROM $backupTable LIKE 'event_name'") && mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM $backupTable LIKE 'event_name'")) > 0 ? 'event_name' : 'name') . ", 
            " . (mysqli_query($conn, "SHOW COLUMNS FROM $backupTable LIKE 'event_date'") && mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM $backupTable LIKE 'event_date'")) > 0 ? 'event_date' : 'date') . ", 
            end_date, location, status, description, 
            organizer, capacity, registrations, created_by, created_at, updated_at
        FROM $backupTable";
        
        if (mysqli_query($conn, $copyDataSql)) {
            echo "<p class='success'>Successfully copied data from backup table to new events table.</p>";
        } else {
            echo "<p class='error'>Error copying data to new table: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p class='error'>Error creating new events table: " . mysqli_error($conn) . "</p>";
    }
    
    echo "<h2>New Table Structure</h2>";
    $tableInfo = mysqli_query($conn, "DESCRIBE events");
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($tableInfo)) {
        print_r($row);
    }
    echo "</pre>";
    
    echo "<p class='success'>Events table has been recreated with correct column names. You can now use the events pages.</p>";
    echo "<p><a href='pages_php/events.php'>Go to Events Page</a></p>";
}

echo "</body></html>";
?> 