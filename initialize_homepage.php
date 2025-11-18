<?php
/**
 * VVU SRC Homepage Initialization Script
 *
 * This script will:
 * - Check database connection
 * - Create slider_images table if not exists
 * - Insert default slider images
 * - Verify news and events tables
 * - Create uploads directory structure
 * - Test all queries
 *
 * Run this ONCE after updating the homepage
 */

// Include database configuration
require_once __DIR__ . '/includes/db_config.php';

// Start output buffering for clean display
ob_start();

// Set content type
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VVU SRC Homepage Initialization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 900px;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin: 20px auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .header h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 1.1rem;
        }
        .step {
            margin: 25px 0;
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
            border-left: 5px solid #6c757d;
        }
        .step.success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .step.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .step.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .step.info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
        }
        .step-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .step-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }
        .btn-custom {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: all 0.3s;
        }
        .btn-custom:hover {
            background: #764ba2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .progress-bar-container {
            margin: 30px 0;
        }
        .summary {
            margin-top: 40px;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            text-align: center;
        }
        .summary h2 {
            margin-bottom: 20px;
        }
        ul.checklist {
            list-style: none;
            padding: 0;
        }
        ul.checklist li {
            padding: 8px 0;
            font-size: 1.05rem;
        }
        ul.checklist li i {
            margin-right: 10px;
            width: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-rocket"></i> VVU SRC Homepage Initialization</h1>
            <p>Setting up your new homepage with dynamic slider management</p>
        </div>

        <div class="progress-bar-container">
            <div class="progress" style="height: 30px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                     style="width: 0%" id="progressBar">0%</div>
            </div>
        </div>

        <?php
        $steps = [];
        $totalSteps = 6;
        $currentStep = 0;
        $allSuccess = true;

        // Helper function to update progress
        function updateProgress($current, $total) {
            $percent = round(($current / $total) * 100);
            echo "<script>
                document.getElementById('progressBar').style.width = '{$percent}%';
                document.getElementById('progressBar').textContent = '{$percent}%';
            </script>";
            flush();
            ob_flush();
        }

        // STEP 1: Check Database Connection
        $currentStep++;
        updateProgress($currentStep, $totalSteps);
        echo '<div class="step ';
        if ($conn && mysqli_ping($conn)) {
            echo 'success">';
            echo '<div class="step-title"><span class="step-icon">✅</span>Step 1: Database Connection</div>';
            echo '<p>Successfully connected to database: <strong>' . mysqli_get_host_info($conn) . '</strong></p>';
            $steps[] = ['status' => 'success', 'message' => 'Database connected'];
        } else {
            echo 'error">';
            echo '<div class="step-title"><span class="step-icon">❌</span>Step 1: Database Connection Failed</div>';
            echo '<p class="text-danger">Could not connect to database. Please check your database configuration in <code>includes/db_config.php</code></p>';
            $steps[] = ['status' => 'error', 'message' => 'Database connection failed'];
            $allSuccess = false;
        }
        echo '</div>';

        // STEP 2: Check/Create Uploads Directory
        $currentStep++;
        updateProgress($currentStep, $totalSteps);
        echo '<div class="step ';
        $uploadDir = __DIR__ . '/uploads/slider';
        if (!is_dir($uploadDir)) {
            if (mkdir($uploadDir, 0755, true)) {
                echo 'success">';
                echo '<div class="step-title"><span class="step-icon">✅</span>Step 2: Uploads Directory</div>';
                echo '<p>Created uploads directory: <code>uploads/slider/</code></p>';
                $steps[] = ['status' => 'success', 'message' => 'Uploads directory created'];
            } else {
                echo 'error">';
                echo '<div class="step-title"><span class="step-icon">❌</span>Step 2: Uploads Directory Failed</div>';
                echo '<p class="text-danger">Could not create uploads directory. Please create manually and set permissions to 755</p>';
                $steps[] = ['status' => 'error', 'message' => 'Failed to create uploads directory'];
                $allSuccess = false;
            }
        } else {
            echo 'success">';
            echo '<div class="step-title"><span class="step-icon">✅</span>Step 2: Uploads Directory</div>';
            echo '<p>Uploads directory already exists: <code>uploads/slider/</code></p>';
            $steps[] = ['status' => 'success', 'message' => 'Uploads directory exists'];
        }
        echo '</div>';

        // STEP 3: Create/Verify Slider Images Table
        $currentStep++;
        updateProgress($currentStep, $totalSteps);
        echo '<div class="step ';

        $createTableSQL = "CREATE TABLE IF NOT EXISTS slider_images (
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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active (is_active),
            INDEX idx_order (slide_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (mysqli_query($conn, $createTableSQL)) {
            echo 'success">';
            echo '<div class="step-title"><span class="step-icon">✅</span>Step 3: Slider Images Table</div>';
            echo '<p>Slider images table created/verified successfully</p>';
            $steps[] = ['status' => 'success', 'message' => 'Slider table ready'];
        } else {
            echo 'error">';
            echo '<div class="step-title"><span class="step-icon">❌</span>Step 3: Slider Images Table Failed</div>';
            echo '<p class="text-danger">Error: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
            $steps[] = ['status' => 'error', 'message' => 'Failed to create slider table'];
            $allSuccess = false;
        }
        echo '</div>';

        // STEP 4: Insert Default Slider Images
        $currentStep++;
        updateProgress($currentStep, $totalSteps);
        echo '<div class="step ';

        // Check if we already have slider images
        $checkSlides = mysqli_query($conn, "SELECT COUNT(*) as count FROM slider_images");
        $slideCount = mysqli_fetch_assoc($checkSlides)['count'];

        if ($slideCount == 0) {
            $insertSQL = "INSERT INTO slider_images (image_path, title, subtitle, button1_text, button1_link, button2_text, button2_link, slide_order, is_active) VALUES
            ('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1920', 'Valley View University', 'Students'' Representative Council', 'Student Login', 'pages_php/login.php', 'Learn More', '#about', 1, 1),
            ('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1920', 'Your Voice Matters', 'Empowering Students Through Representation', 'Latest News', '#news', 'Upcoming Events', '#events', 2, 1),
            ('https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1920', 'Excellence in Leadership', 'Building Tomorrow''s Leaders Today', 'Join Us', 'pages_php/login.php', 'Contact Us', '#contact', 3, 1)";

            if (mysqli_query($conn, $insertSQL)) {
                echo 'success">';
                echo '<div class="step-title"><span class="step-icon">✅</span>Step 4: Default Slider Images</div>';
                echo '<p>Inserted 3 default slider images successfully</p>';
                echo '<ul><li>Valley View University - Students\' Representative Council</li>';
                echo '<li>Your Voice Matters - Empowering Students</li>';
                echo '<li>Excellence in Leadership - Building Leaders</li></ul>';
                $steps[] = ['status' => 'success', 'message' => '3 default slides added'];
            } else {
                echo 'error">';
                echo '<div class="step-title"><span class="step-icon">❌</span>Step 4: Default Slider Images Failed</div>';
                echo '<p class="text-danger">Error: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
                $steps[] = ['status' => 'error', 'message' => 'Failed to insert default slides'];
                $allSuccess = false;
            }
        } else {
            echo 'info">';
            echo '<div class="step-title"><span class="step-icon">ℹ️</span>Step 4: Default Slider Images</div>';
            echo '<p>Slider images already exist (' . $slideCount . ' slides found). Skipping default insertion.</p>';
            $steps[] = ['status' => 'info', 'message' => $slideCount . ' slides already exist'];
        }
        echo '</div>';

        // STEP 5: Verify News Table
        $currentStep++;
        updateProgress($currentStep, $totalSteps);
        echo '<div class="step ';

        $newsCheck = @mysqli_query($conn, "SELECT COUNT(*) as count FROM news WHERE status = 'published'");
        if ($newsCheck) {
            $newsCount = mysqli_fetch_assoc($newsCheck)['count'];
            if ($newsCount > 0) {
                echo 'success">';
                echo '<div class="step-title"><span class="step-icon">✅</span>Step 5: News Articles</div>';
                echo '<p>Found <strong>' . $newsCount . '</strong> published news article(s)</p>';
                $steps[] = ['status' => 'success', 'message' => $newsCount . ' news articles found'];
            } else {
                echo 'warning">';
                echo '<div class="step-title"><span class="step-icon">⚠️</span>Step 5: News Articles</div>';
                echo '<p>No published news articles found. Add some via <a href="pages_php/news.php">News Management</a></p>';
                $steps[] = ['status' => 'warning', 'message' => 'No news articles yet'];
            }
        } else {
            echo 'error">';
            echo '<div class="step-title"><span class="step-icon">❌</span>Step 5: News Table Error</div>';
            echo '<p class="text-danger">Could not access news table: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
            $steps[] = ['status' => 'error', 'message' => 'News table error'];
            $allSuccess = false;
        }
        echo '</div>';

        // STEP 6: Verify Events Table
        $currentStep++;
        updateProgress($currentStep, $totalSteps);
        echo '<div class="step ';

        $eventsCheck = @mysqli_query($conn, "SELECT COUNT(*) as count FROM events WHERE event_date >= CURDATE()");
        if ($eventsCheck) {
            $eventsCount = mysqli_fetch_assoc($eventsCheck)['count'];
            if ($eventsCount > 0) {
                echo 'success">';
                echo '<div class="step-title"><span class="step-icon">✅</span>Step 6: Upcoming Events</div>';
                echo '<p>Found <strong>' . $eventsCount . '</strong> upcoming event(s)</p>';
                $steps[] = ['status' => 'success', 'message' => $eventsCount . ' upcoming events found'];
            } else {
                echo 'warning">';
                echo '<div class="step-title"><span class="step-icon">⚠️</span>Step 6: Upcoming Events</div>';
                echo '<p>No upcoming events found. Add some via <a href="pages_php/events.php">Events Management</a></p>';
                $steps[] = ['status' => 'warning', 'message' => 'No upcoming events yet'];
            }
        } else {
            echo 'error">';
            echo '<div class="step-title"><span class="step-icon">❌</span>Step 6: Events Table Error</div>';
            echo '<p class="text-danger">Could not access events table: ' . htmlspecialchars(mysqli_error($conn)) . '</p>';
            $steps[] = ['status' => 'error', 'message' => 'Events table error'];
            $allSuccess = false;
        }
        echo '</div>';

        // Update to 100%
        updateProgress($totalSteps, $totalSteps);

        // Close database connection
        mysqli_close($conn);
        ?>

        <!-- Summary Section -->
        <div class="summary">
            <?php if ($allSuccess): ?>
                <h2><i class="fas fa-check-circle"></i> Initialization Complete!</h2>
                <p style="font-size: 1.2rem; margin: 20px 0;">Your VVU SRC homepage is ready to use.</p>
                <ul class="checklist">
                    <?php foreach ($steps as $step): ?>
                        <li>
                            <?php if ($step['status'] === 'success'): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php elseif ($step['status'] === 'warning'): ?>
                                <i class="fas fa-exclamation-triangle"></i>
                            <?php elseif ($step['status'] === 'info'): ?>
                                <i class="fas fa-info-circle"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($step['message']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <h2><i class="fas fa-exclamation-triangle"></i> Initialization Issues Detected</h2>
                <p>Some steps encountered errors. Please review and fix them before proceeding.</p>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div style="text-align: center; margin-top: 40px;">
            <a href="index.php" class="btn-custom">
                <i class="fas fa-home"></i> View Homepage
            </a>
            <a href="pages_php/settings.php" class="btn-custom">
                <i class="fas fa-cog"></i> Manage Sliders
            </a>
            <a href="test_homepage_data.php" class="btn-custom">
                <i class="fas fa-flask"></i> Run Tests
            </a>
            <a href="check_table_structure.php" class="btn-custom">
                <i class="fas fa-database"></i> Check Tables
            </a>
        </div>

        <!-- Quick Start Info -->
        <div style="margin-top: 40px; padding: 30px; background: #f8f9fa; border-radius: 10px;">
            <h3><i class="fas fa-lightbulb"></i> Next Steps</h3>
            <ol style="font-size: 1.05rem; line-height: 2;">
                <li><strong>View Your Homepage:</strong> Click "View Homepage" above to see the new design</li>
                <li><strong>Upload Custom Images:</strong> Go to Settings → Slider Images tab</li>
                <li><strong>Add Content:</strong> Create news articles and events through the management pages</li>
                <li><strong>Customize:</strong> Edit colors, contact info, and content as needed</li>
            </ol>

            <h4 style="margin-top: 25px;"><i class="fas fa-book"></i> Documentation</h4>
            <p>For detailed instructions, check:</p>
            <ul>
                <li><code>QUICK_START.md</code> - Quick reference guide</li>
                <li><code>HOMEPAGE_IMPLEMENTATION_GUIDE.md</code> - Full documentation</li>
            </ul>

            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin-top: 20px;">
                <strong><i class="fas fa-shield-alt"></i> Security Note:</strong>
                After successful initialization, you can delete or rename this file (<code>initialize_homepage.php</code>) for security.
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd; color: #666;">
            <p>VVU SRC Homepage v2.0 | December 2024</p>
            <p><small>Powered by Bootstrap 5 • Font Awesome 6 • Swiper.js • AOS</small></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom on page load
        window.addEventListener('load', function() {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
?>
