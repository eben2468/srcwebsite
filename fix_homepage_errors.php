<?php
/**
 * VVU SRC Homepage Error Fix Script
 *
 * This script automatically detects and fixes common homepage errors:
 * - Database connection issues
 * - Missing tables
 * - Column name mismatches
 * - Missing slider images
 * - Query errors
 */

require_once __DIR__ . '/includes/db_config.php';

// Disable error display for clean output
error_reporting(0);
ini_set('display_errors', 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Homepage Errors - VVU SRC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #e74c3c;
        }
        .header h1 {
            color: #e74c3c;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .fix-item {
            margin: 20px 0;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #6c757d;
            background: #f8f9fa;
        }
        .fix-item.fixed {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .fix-item.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .fix-item.info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
        }
        .fix-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .code {
            background: #2d3748;
            color: #e2e8f0;
            padding: 10px 15px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
            overflow-x: auto;
            font-size: 0.9rem;
        }
        .btn-primary {
            background: #e74c3c;
            border-color: #e74c3c;
        }
        .btn-primary:hover {
            background: #c0392b;
            border-color: #c0392b;
        }
        .summary {
            margin-top: 40px;
            padding: 30px;
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            border-radius: 10px;
            text-align: center;
        }
        .icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tools"></i> Homepage Error Fixer</h1>
            <p>Automatically detecting and fixing homepage issues...</p>
        </div>

        <?php
        $fixes = [];
        $errors = [];
        $warnings = [];

        // FIX 1: Check Database Connection
        echo '<div class="fix-item ';
        if ($conn && mysqli_ping($conn)) {
            echo 'fixed">';
            echo '<div class="fix-title"><i class="icon fas fa-check-circle"></i>Database Connection</div>';
            echo '<p>✓ Database connected successfully</p>';
            $fixes[] = 'Database connection OK';
        } else {
            echo 'error">';
            echo '<div class="fix-title"><i class="icon fas fa-times-circle"></i>Database Connection Failed</div>';
            echo '<p>✗ Cannot connect to database. Check <code>includes/db_config.php</code></p>';
            $errors[] = 'Database connection failed';
            echo '</div></div></body></html>';
            exit;
        }
        echo '</div>';

        // FIX 2: Check and Create Slider Images Table
        echo '<div class="fix-item ';
        $sliderTableExists = false;
        $checkSliderTable = @mysqli_query($conn, "SHOW TABLES LIKE 'slider_images'");

        if ($checkSliderTable && mysqli_num_rows($checkSliderTable) > 0) {
            $sliderTableExists = true;
            echo 'fixed">';
            echo '<div class="fix-title"><i class="icon fas fa-check-circle"></i>Slider Images Table</div>';
            echo '<p>✓ Table exists</p>';
            $fixes[] = 'Slider table exists';
        } else {
            // Create the table
            $createSliderSQL = "CREATE TABLE IF NOT EXISTS slider_images (
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

            if (mysqli_query($conn, $createSliderSQL)) {
                echo 'fixed">';
                echo '<div class="fix-title"><i class="icon fas fa-check-circle"></i>Slider Images Table</div>';
                echo '<p>✓ Created slider_images table</p>';
                $fixes[] = 'Created slider_images table';
                $sliderTableExists = true;
            } else {
                echo 'error">';
                echo '<div class="fix-title"><i class="icon fas fa-times-circle"></i>Slider Images Table</div>';
                echo '<p>✗ Failed to create table: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
                $errors[] = 'Failed to create slider_images table';
            }
        }
        echo '</div>';

        // FIX 3: Insert Default Slider Images if Empty
        if ($sliderTableExists) {
            echo '<div class="fix-item ';
            $countSlides = mysqli_query($conn, "SELECT COUNT(*) as count FROM slider_images");
            $slideCount = mysqli_fetch_assoc($countSlides)['count'];

            if ($slideCount == 0) {
                $insertSlides = "INSERT INTO slider_images (image_path, title, subtitle, button1_text, button1_link, button2_text, button2_link, slide_order, is_active) VALUES
                ('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1920', 'Valley View University', 'Students'' Representative Council', 'Student Login', 'pages_php/login.php', 'Learn More', '#about', 1, 1),
                ('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1920', 'Your Voice Matters', 'Empowering Students Through Representation', 'Latest News', '#news', 'Upcoming Events', '#events', 2, 1),
                ('https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1920', 'Excellence in Leadership', 'Building Tomorrow''s Leaders Today', 'Join Us', 'pages_php/login.php', 'Contact Us', '#contact', 3, 1)";

                if (mysqli_query($conn, $insertSlides)) {
                    echo 'fixed">';
                    echo '<div class="fix-title"><i class="icon fas fa-check-circle"></i>Default Slider Images</div>';
                    echo '<p>✓ Inserted 3 default slider images</p>';
                    $fixes[] = 'Inserted default slider images';
                } else {
                    echo 'error">';
                    echo '<div class="fix-title"><i class="icon fas fa-times-circle"></i>Default Slider Images</div>';
                    echo '<p>✗ Failed to insert: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
                    $errors[] = 'Failed to insert default slides';
                }
            } else {
                echo 'info">';
                echo '<div class="fix-title"><i class="icon fas fa-info-circle"></i>Slider Images</div>';
                echo '<p>ℹ Found ' . $slideCount . ' existing slide(s)</p>';
                $warnings[] = $slideCount . ' slides already exist';
            }
            echo '</div>';
        }

        // FIX 4: Detect News Table Structure
        echo '<div class="fix-item ';
        $newsColumns = [];
        $newsDescribe = @mysqli_query($conn, "DESCRIBE news");

        if ($newsDescribe) {
            while ($col = mysqli_fetch_assoc($newsDescribe)) {
                $newsColumns[] = $col['Field'];
            }
            echo 'fixed">';
            echo '<div class="fix-title"><i class="icon fas fa-check-circle"></i>News Table Structure</div>';
            echo '<p>✓ Detected news table columns:</p>';
            echo '<div class="code">' . implode(', ', $newsColumns) . '</div>';

            // Check if query will work
            $testNewsQuery = "SELECT * FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 1";
            $testNews = @mysqli_query($conn, $testNewsQuery);
            if ($testNews) {
                echo '<p>✓ News query working correctly</p>';
                $fixes[] = 'News table structure OK';
            } else {
                echo '<p>⚠ News query issue: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
                $warnings[] = 'News query needs adjustment';
            }
        } else {
            echo 'error">';
            echo '<div class="fix-title"><i class="icon fas fa-times-circle"></i>News Table</div>';
            echo '<p>✗ News table not found or error: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
            $errors[] = 'News table not accessible';
        }
        echo '</div>';

        // FIX 5: Detect Events Table Structure
        echo '<div class="fix-item ';
        $eventsColumns = [];
        $eventsDescribe = @mysqli_query($conn, "DESCRIBE events");

        if ($eventsDescribe) {
            while ($col = mysqli_fetch_assoc($eventsDescribe)) {
                $eventsColumns[] = $col['Field'];
            }
            echo 'fixed">';
            echo '<div class="fix-title"><i class="icon fas fa-check-circle"></i>Events Table Structure</div>';
            echo '<p>✓ Detected events table columns:</p>';
            echo '<div class="code">' . implode(', ', $eventsColumns) . '</div>';

            // Determine correct column names
            $dateColumn = in_array('event_date', $eventsColumns) ? 'event_date' : (in_array('date', $eventsColumns) ? 'date' : 'created_at');
            $timeColumn = in_array('event_time', $eventsColumns) ? 'event_time' : (in_array('time', $eventsColumns) ? 'time' : null);

            echo '<p>✓ Will use: <code>' . $dateColumn . '</code> for date';
            if ($timeColumn) {
                echo ', <code>' . $timeColumn . '</code> for time';
            }
            echo '</p>';

            // Test query
            $testEventsQuery = "SELECT * FROM events ORDER BY $dateColumn ASC LIMIT 1";
            $testEvents = @mysqli_query($conn, $testEventsQuery);
            if ($testEvents) {
                echo '<p>✓ Events query working correctly</p>';
                $fixes[] = 'Events table structure OK';
            } else {
                echo '<p>⚠ Events query issue: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
                $warnings[] = 'Events query needs adjustment';
            }
        } else {
            echo 'error">';
            echo '<div class="fix-title"><i class="icon fas fa-times-circle"></i>Events Table</div>';
            echo '<p>✗ Events table not found or error: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
            $errors[] = 'Events table not accessible';
        }
        echo '</div>';

        // FIX 6: Create Uploads Directory
        echo '<div class="fix-item ';
        $uploadDir = __DIR__ . '/uploads/slider';
        if (!is_dir($uploadDir)) {
            if (@mkdir($uploadDir, 0755, true)) {
                echo 'fixed">';
                echo '<div class="fix-title"><i class="icon fas fa-check-circle"></i>Uploads Directory</div>';
                echo '<p>✓ Created <code>uploads/slider/</code> directory</p>';
                $fixes[] = 'Created uploads directory';
            } else {
                echo 'error">';
                echo '<div class="fix-title"><i class="icon fas fa-times-circle"></i>Uploads Directory</div>';
                echo '<p>✗ Failed to create directory. Please create manually and set permissions to 755</p>';
                $errors[] = 'Failed to create uploads directory';
            }
        } else {
            echo 'info">';
            echo '<div class="fix-title"><i class="icon fas fa-info-circle"></i>Uploads Directory</div>';
            echo '<p>ℹ Directory already exists</p>';
            $warnings[] = 'Uploads directory exists';
        }
        echo '</div>';

        // FIX 7: Test Complete Homepage Query
        echo '<div class="fix-item ';
        $homePageErrors = [];

        // Test slider query
        $testSliderQuery = @mysqli_query($conn, "SELECT * FROM slider_images WHERE is_active = 1 ORDER BY slide_order ASC");
        if (!$testSliderQuery) {
            $homePageErrors[] = 'Slider query failed';
        }

        // Test news query
        $testNewsQuery = @mysqli_query($conn, "SELECT * FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
        if (!$testNewsQuery) {
            $homePageErrors[] = 'News query failed';
        }

        // Test events query
        if (!empty($eventsColumns)) {
            $dateCol = in_array('event_date', $eventsColumns) ? 'event_date' : (in_array('date', $eventsColumns) ? 'date' : 'created_at');
            $testEventsQuery = @mysqli_query($conn, "SELECT * FROM events ORDER BY $dateCol ASC LIMIT 4");
            if (!$testEventsQuery) {
                $homePageErrors[] = 'Events query failed';
            }
        }

        if (empty($homePageErrors)) {
            echo 'fixed">';
            echo '<div class="fix-title"><i class="icon fas fa-check-circle"></i>Homepage Queries Test</div>';
            echo '<p>✓ All homepage queries executed successfully</p>';
            $fixes[] = 'All queries working';
        } else {
            echo 'error">';
            echo '<div class="fix-title"><i class="icon fas fa-times-circle"></i>Homepage Queries Test</div>';
            echo '<p>✗ Some queries failed:</p>';
            echo '<ul>';
            foreach ($homePageErrors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            $errors[] = 'Some homepage queries failed';
        }
        echo '</div>';

        // Summary
        $totalIssues = count($errors);
        $totalFixes = count($fixes);
        $totalWarnings = count($warnings);

        if ($totalIssues == 0) {
            echo '<div class="summary">';
            echo '<h2><i class="fas fa-check-circle"></i> All Issues Fixed!</h2>';
            echo '<p style="font-size: 1.2rem; margin: 20px 0;">Your homepage should now work correctly.</p>';
            echo '<p><strong>Applied ' . $totalFixes . ' fix(es)</strong></p>';
            if ($totalWarnings > 0) {
                echo '<p><small>' . $totalWarnings . ' informational message(s)</small></p>';
            }
            echo '</div>';
        } else {
            echo '<div class="fix-item error">';
            echo '<div class="fix-title"><i class="icon fas fa-exclamation-triangle"></i>Remaining Issues</div>';
            echo '<p><strong>' . $totalIssues . ' error(s) need manual attention:</strong></p>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }

        mysqli_close($conn);
        ?>

        <!-- Action Buttons -->
        <div class="text-center mt-5">
            <a href="index.php" class="btn btn-primary btn-lg me-2">
                <i class="fas fa-home"></i> View Homepage
            </a>
            <a href="test_homepage_data.php" class="btn btn-secondary btn-lg me-2">
                <i class="fas fa-flask"></i> Run Tests
            </a>
            <a href="pages_php/settings.php" class="btn btn-success btn-lg">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>

        <!-- Additional Info -->
        <div class="mt-5 p-4 bg-light rounded">
            <h4><i class="fas fa-lightbulb"></i> What Was Fixed?</h4>
            <ul>
                <?php foreach ($fixes as $fix): ?>
                    <li><?php echo htmlspecialchars($fix); ?></li>
                <?php endforeach; ?>
            </ul>

            <?php if ($totalWarnings > 0): ?>
                <h4 class="mt-3"><i class="fas fa-info-circle"></i> Informational</h4>
                <ul>
                    <?php foreach ($warnings as $warning): ?>
                        <li><?php echo htmlspecialchars($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="alert alert-info mt-4">
                <strong><i class="fas fa-shield-alt"></i> Security Note:</strong>
                After successful setup, delete this file (<code>fix_homepage_errors.php</code>) for security.
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-5 pt-4 border-top">
            <p class="text-muted">
                <strong>Documentation:</strong>
                <a href="QUICK_START.md">Quick Start</a> |
                <a href="HOMEPAGE_IMPLEMENTATION_GUIDE.md">Full Guide</a>
            </p>
            <p class="text-muted"><small>VVU SRC Homepage v2.0</small></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
