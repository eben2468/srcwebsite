<?php
// Diagnostic script to check table structures
require_once __DIR__ . '/includes/db_config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Table Structure Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        h2 { color: #0066cc; border-bottom: 2px solid #0066cc; padding-bottom: 10px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #0066cc;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 15px 0; }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>📊 Database Table Structure Diagnostic</h1>

    <?php
    // Check NEWS table
    echo '<div class="section">';
    echo '<h2>NEWS Table Structure</h2>';

    $newsDescribe = mysqli_query($conn, "DESCRIBE news");
    if ($newsDescribe) {
        echo '<p class="success">✓ News table exists</p>';
        echo '<table>';
        echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        while ($row = mysqli_fetch_assoc($newsDescribe)) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($row['Field']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Default'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($row['Extra']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        // Check for sample data
        $newsCount = mysqli_query($conn, "SELECT COUNT(*) as count FROM news");
        $countRow = mysqli_fetch_assoc($newsCount);
        echo '<p><strong>Total Records:</strong> ' . $countRow['count'] . '</p>';

        // Show sample record
        $newsSample = mysqli_query($conn, "SELECT * FROM news LIMIT 1");
        if ($newsSample && mysqli_num_rows($newsSample) > 0) {
            echo '<h3>Sample Record:</h3>';
            echo '<pre>' . print_r(mysqli_fetch_assoc($newsSample), true) . '</pre>';
        }
    } else {
        echo '<p class="error">✗ News table does not exist or error: ' . mysqli_error($conn) . '</p>';
    }
    echo '</div>';

    // Check EVENTS table
    echo '<div class="section">';
    echo '<h2>EVENTS Table Structure</h2>';

    $eventsDescribe = mysqli_query($conn, "DESCRIBE events");
    if ($eventsDescribe) {
        echo '<p class="success">✓ Events table exists</p>';
        echo '<table>';
        echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        while ($row = mysqli_fetch_assoc($eventsDescribe)) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($row['Field']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Default'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($row['Extra']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        // Check for sample data
        $eventsCount = mysqli_query($conn, "SELECT COUNT(*) as count FROM events");
        $countRow = mysqli_fetch_assoc($eventsCount);
        echo '<p><strong>Total Records:</strong> ' . $countRow['count'] . '</p>';

        // Show sample record
        $eventsSample = mysqli_query($conn, "SELECT * FROM events LIMIT 1");
        if ($eventsSample && mysqli_num_rows($eventsSample) > 0) {
            echo '<h3>Sample Record:</h3>';
            echo '<pre>' . print_r(mysqli_fetch_assoc($eventsSample), true) . '</pre>';
        }
    } else {
        echo '<p class="error">✗ Events table does not exist or error: ' . mysqli_error($conn) . '</p>';
    }
    echo '</div>';

    // Check SLIDER_IMAGES table
    echo '<div class="section">';
    echo '<h2>SLIDER_IMAGES Table Structure</h2>';

    $sliderDescribe = mysqli_query($conn, "DESCRIBE slider_images");
    if ($sliderDescribe) {
        echo '<p class="success">✓ Slider_images table exists</p>';
        echo '<table>';
        echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        while ($row = mysqli_fetch_assoc($sliderDescribe)) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($row['Field']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Default'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($row['Extra']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        // Check for sample data
        $sliderCount = mysqli_query($conn, "SELECT COUNT(*) as count FROM slider_images");
        $countRow = mysqli_fetch_assoc($sliderCount);
        echo '<p><strong>Total Records:</strong> ' . $countRow['count'] . '</p>';

        if ($countRow['count'] == 0) {
            echo '<div class="info">';
            echo '<strong>ℹ️ No slider images found.</strong><br>';
            echo 'Run <a href="setup_slider_images.php">setup_slider_images.php</a> to initialize default slider images.';
            echo '</div>';
        }
    } else {
        echo '<p class="error">✗ Slider_images table does not exist</p>';
        echo '<div class="info">';
        echo '<strong>ℹ️ Slider images table not found.</strong><br>';
        echo 'Run <a href="setup_slider_images.php">setup_slider_images.php</a> to create the table.';
        echo '</div>';
    }
    echo '</div>';

    // Suggested Fix for index.php
    echo '<div class="section">';
    echo '<h2>🔧 Recommended Query Fixes</h2>';

    // Get actual news columns
    $newsDescribe = mysqli_query($conn, "DESCRIBE news");
    $newsColumns = [];
    if ($newsDescribe) {
        while ($row = mysqli_fetch_assoc($newsDescribe)) {
            $newsColumns[] = $row['Field'];
        }
    }

    echo '<h3>News Query:</h3>';
    echo '<p>Based on your table structure, use this query:</p>';
    echo '<pre>';
    if (in_array('created_by', $newsColumns)) {
        echo 'SELECT n.*, u.first_name, u.last_name
FROM news n
LEFT JOIN users u ON n.created_by = u.user_id
WHERE n.status = \'published\'
ORDER BY n.created_at DESC
LIMIT 3';
    } else {
        echo 'SELECT * FROM news
WHERE status = \'published\'
ORDER BY created_at DESC
LIMIT 3';
    }
    echo '</pre>';

    // Get actual events columns
    $eventsDescribe = mysqli_query($conn, "DESCRIBE events");
    $eventsColumns = [];
    if ($eventsDescribe) {
        while ($row = mysqli_fetch_assoc($eventsDescribe)) {
            $eventsColumns[] = $row['Field'];
        }
    }

    echo '<h3>Events Query:</h3>';
    echo '<p>Based on your table structure, use this query:</p>';
    echo '<pre>';
    echo 'SELECT * FROM events
WHERE event_date >= CURDATE()
ORDER BY event_date ASC, event_time ASC
LIMIT 4';
    echo '</pre>';

    echo '<h3>Available News Columns:</h3>';
    echo '<ul>';
    foreach ($newsColumns as $col) {
        echo '<li><code>' . htmlspecialchars($col) . '</code></li>';
    }
    echo '</ul>';

    echo '<h3>Available Events Columns:</h3>';
    echo '<ul>';
    foreach ($eventsColumns as $col) {
        echo '<li><code>' . htmlspecialchars($col) . '</code></li>';
    }
    echo '</ul>';

    echo '</div>';

    mysqli_close($conn);
    ?>

    <div class="section">
        <h2>Next Steps</h2>
        <ol>
            <li>Review the table structures above</li>
            <li>Update <code>index.php</code> with the correct queries shown above</li>
            <li>If slider_images table doesn't exist, run <a href="setup_slider_images.php">setup_slider_images.php</a></li>
            <li>Test the homepage at <a href="index.php">index.php</a></li>
        </ol>
    </div>

    <p style="text-align: center; color: #666; margin-top: 40px;">
        <a href="index.php">← Back to Homepage</a> |
        <a href="pages_php/settings.php">Go to Settings</a>
    </p>
</body>
</html>
