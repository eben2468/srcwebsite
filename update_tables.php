<?php
// Include database configuration
require_once 'db_config.php';

// Function to check if a column exists in a table
function columnExists($table, $column) {
    global $conn;
    $sql = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = mysqli_query($conn, $sql);
    return mysqli_num_rows($result) > 0;
}

// Alter news table to add image and document fields
if (!columnExists('news', 'image_path')) {
    $sql = "ALTER TABLE news ADD COLUMN image_path VARCHAR(255) NULL AFTER content";
    if (mysqli_query($conn, $sql)) {
        echo "Added image_path column to news table successfully\n";
    } else {
        echo "Error adding image_path column to news table: " . mysqli_error($conn) . "\n";
    }
}

if (!columnExists('news', 'document_path')) {
    $sql = "ALTER TABLE news ADD COLUMN document_path VARCHAR(255) NULL AFTER image_path";
    if (mysqli_query($conn, $sql)) {
        echo "Added document_path column to news table successfully\n";
    } else {
        echo "Error adding document_path column to news table: " . mysqli_error($conn) . "\n";
    }
}

// Alter events table to add image and document fields
if (!columnExists('events', 'image_path')) {
    $sql = "ALTER TABLE events ADD COLUMN image_path VARCHAR(255) NULL AFTER description";
    if (mysqli_query($conn, $sql)) {
        echo "Added image_path column to events table successfully\n";
    } else {
        echo "Error adding image_path column to events table: " . mysqli_error($conn) . "\n";
    }
}

if (!columnExists('events', 'document_path')) {
    $sql = "ALTER TABLE events ADD COLUMN document_path VARCHAR(255) NULL AFTER image_path";
    if (mysqli_query($conn, $sql)) {
        echo "Added document_path column to events table successfully\n";
    } else {
        echo "Error adding document_path column to events table: " . mysqli_error($conn) . "\n";
    }
}

// Create uploads directory if it doesn't exist
$uploadsDir = __DIR__ . '/uploads';
if (!file_exists($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) {
        echo "Created uploads directory successfully\n";
    } else {
        echo "Error creating uploads directory\n";
    }
}

// Create subdirectories for news and events
$newsUploadsDir = $uploadsDir . '/news';
if (!file_exists($newsUploadsDir)) {
    if (mkdir($newsUploadsDir, 0755, true)) {
        echo "Created news uploads directory successfully\n";
    } else {
        echo "Error creating news uploads directory\n";
    }
}

$eventsUploadsDir = $uploadsDir . '/events';
if (!file_exists($eventsUploadsDir)) {
    if (mkdir($eventsUploadsDir, 0755, true)) {
        echo "Created events uploads directory successfully\n";
    } else {
        echo "Error creating events uploads directory\n";
    }
}

echo "Database update completed!\n";
?> 