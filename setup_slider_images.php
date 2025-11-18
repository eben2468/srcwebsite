<?php
// Setup slider images table
require_once __DIR__ . '/includes/db_config.php';

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2>Setting up Slider Images Table...</h2>";

// Drop existing table if it exists (optional - comment out if you want to preserve existing data)
// $dropSql = "DROP TABLE IF EXISTS slider_images";
// mysqli_query($conn, $dropSql);

// Create slider_images table
$createTableSql = "CREATE TABLE IF NOT EXISTS slider_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT,
    button1_text VARCHAR(100),
    button1_link VARCHAR(255),
    button2_text VARCHAR(100),
    button2_link VARCHAR(255),
    slide_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $createTableSql)) {
    echo "<p style='color: green;'>✓ Slider images table created successfully!</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating table: " . mysqli_error($conn) . "</p>";
}

// Check if table already has data
$checkSql = "SELECT COUNT(*) as count FROM slider_images";
$result = mysqli_query($conn, $checkSql);
$row = mysqli_fetch_assoc($result);

if ($row['count'] == 0) {
    // Insert default slider images
    $insertSql = "INSERT INTO slider_images (image_path, title, subtitle, button1_text, button1_link, button2_text, button2_link, slide_order, is_active) VALUES
    ('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1920', 'Valley View University', 'Students'' Representative Council', 'Student Login', 'pages_php/login.php', 'Learn More', '#about', 1, 1),
    ('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1920', 'Your Voice Matters', 'Empowering Students Through Representation', 'Latest News', '#news', 'Upcoming Events', '#events', 2, 1),
    ('https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1920', 'Excellence in Leadership', 'Building Tomorrow''s Leaders Today', 'Join Us', 'pages_php/login.php', 'Contact Us', '#contact', 3, 1)";

    if (mysqli_query($conn, $insertSql)) {
        echo "<p style='color: green;'>✓ Default slider images inserted successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Error inserting data: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ Table already contains " . $row['count'] . " slider image(s). Skipping default data insertion.</p>";
}

echo "<h3>Setup Complete!</h3>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='pages_php/settings.php'>Go to Settings</a></p>";

// Close connection
mysqli_close($conn);
?>
