<?php
// Test script to verify homepage data loading
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/simple_auth.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage Data Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        h2 {
            color: #667eea;
            margin-top: 30px;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        .card {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .counter {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Homepage Data Test</h1>
        <p>Testing data loading for the new VVU SRC homepage...</p>

        <?php
        $allTestsPassed = true;

        // Test 1: Database Connection
        echo '<h2>1️⃣ Database Connection</h2>';
        if ($conn && mysqli_ping($conn)) {
            echo '<div class="status success">✓ Database connected successfully!</div>';
        } else {
            echo '<div class="status error">✗ Database connection failed!</div>';
            $allTestsPassed = false;
        }

        // Test 2: Slider Images
        echo '<h2>2️⃣ Slider Images</h2>';
        $sliderQuery = "SELECT * FROM slider_images WHERE is_active = 1 ORDER BY slide_order ASC";
        $sliderResult = @mysqli_query($conn, $sliderQuery);

        if (!$sliderResult) {
            echo '<div class="status error">✗ Slider images table not found or error: ' . mysqli_error($conn) . '</div>';
            echo '<div class="info">💡 <strong>Fix:</strong> Run <a href="setup_slider_images.php" class="btn">setup_slider_images.php</a> to create the table.</div>';
            $allTestsPassed = false;
        } else {
            $sliderCount = mysqli_num_rows($sliderResult);
            if ($sliderCount > 0) {
                echo '<div class="status success">✓ Found <span class="counter">' . $sliderCount . '</span> active slider image(s)</div>';
                echo '<div class="grid">';
                $sliderImages = [];
                while ($row = mysqli_fetch_assoc($sliderResult)) {
                    $sliderImages[] = $row;
                    echo '<div class="card">';
                    echo '<h4>' . htmlspecialchars($row['title']) . '</h4>';
                    echo '<p><small>' . htmlspecialchars($row['subtitle']) . '</small></p>';
                    echo '<img src="' . htmlspecialchars($row['image_path']) . '" style="width:100%; height:150px; object-fit:cover; border-radius:5px;" alt="Slider">';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<div class="status warning">⚠ Slider images table exists but no active slides found</div>';
                echo '<div class="info">💡 <strong>Add slides:</strong> Go to Settings → Slider Images tab</div>';
            }
        }

        // Test 3: News Articles
        echo '<h2>3️⃣ News Articles</h2>';
        $newsQuery = "SELECT * FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 3";
        $newsResult = @mysqli_query($conn, $newsQuery);

        if (!$newsResult) {
            echo '<div class="status error">✗ News table error: ' . mysqli_error($conn) . '</div>';
            $allTestsPassed = false;
        } else {
            $newsCount = mysqli_num_rows($newsResult);
            if ($newsCount > 0) {
                echo '<div class="status success">✓ Found <span class="counter">' . $newsCount . '</span> published news article(s)</div>';
                echo '<table>';
                echo '<tr><th>Title</th><th>Category</th><th>Date</th></tr>';
                while ($row = mysqli_fetch_assoc($newsResult)) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['title']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['category'] ?? 'Uncategorized') . '</td>';
                    echo '<td>' . date('M d, Y', strtotime($row['created_at'])) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="status warning">⚠ News table exists but no published articles found</div>';
                echo '<div class="info">💡 <strong>Add news:</strong> Go to <a href="pages_php/news.php" class="btn">News Management</a></div>';
            }
        }

        // Test 4: Events
        echo '<h2>4️⃣ Upcoming Events</h2>';
        $eventsQuery = "SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC, event_time ASC LIMIT 4";
        $eventsResult = @mysqli_query($conn, $eventsQuery);

        if (!$eventsResult) {
            echo '<div class="status error">✗ Events table error: ' . mysqli_error($conn) . '</div>';
            $allTestsPassed = false;
        } else {
            $eventsCount = mysqli_num_rows($eventsResult);
            if ($eventsCount > 0) {
                echo '<div class="status success">✓ Found <span class="counter">' . $eventsCount . '</span> upcoming event(s)</div>';
                echo '<table>';
                echo '<tr><th>Event Name</th><th>Date</th><th>Time</th><th>Location</th></tr>';
                while ($row = mysqli_fetch_assoc($eventsResult)) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['event_name']) . '</td>';
                    echo '<td>' . date('M d, Y', strtotime($row['event_date'])) . '</td>';
                    echo '<td>' . date('g:i A', strtotime($row['event_time'])) . '</td>';
                    echo '<td>' . htmlspecialchars($row['location']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="status warning">⚠ Events table exists but no upcoming events found</div>';
                echo '<div class="info">💡 <strong>Add events:</strong> Go to <a href="pages_php/events.php" class="btn">Events Management</a></div>';
            }
        }

        // Test 5: Authentication Status
        echo '<h2>5️⃣ Authentication Status</h2>';
        $isLoggedIn = isLoggedIn();
        if ($isLoggedIn) {
            $currentUser = getCurrentUser();
            echo '<div class="status success">✓ User is logged in</div>';
            echo '<div class="card">';
            echo '<strong>Current User:</strong> ' . htmlspecialchars($currentUser['first_name'] ?? 'Unknown') . ' ' . htmlspecialchars($currentUser['last_name'] ?? '') . '<br>';
            echo '<strong>Role:</strong> ' . htmlspecialchars($currentUser['role'] ?? 'N/A');
            echo '</div>';
        } else {
            echo '<div class="status info">ℹ Not logged in (viewing as public user)</div>';
        }

        // Final Summary
        echo '<h2>📊 Test Summary</h2>';
        if ($allTestsPassed) {
            echo '<div class="status success">';
            echo '<h3 style="margin:0;">🎉 All Critical Tests Passed!</h3>';
            echo '<p style="margin:10px 0 0 0;">Your homepage should display correctly.</p>';
            echo '</div>';
        } else {
            echo '<div class="status warning">';
            echo '<h3 style="margin:0;">⚠ Some Issues Found</h3>';
            echo '<p style="margin:10px 0 0 0;">Please fix the issues above before viewing the homepage.</p>';
            echo '</div>';
        }

        // Quick Actions
        echo '<h2>🚀 Quick Actions</h2>';
        echo '<div style="margin:20px 0;">';
        echo '<a href="index.php" class="btn">View Homepage</a>';
        echo '<a href="setup_slider_images.php" class="btn">Setup Slider Images</a>';
        echo '<a href="check_table_structure.php" class="btn">Check Table Structure</a>';
        echo '<a href="pages_php/settings.php" class="btn">Settings</a>';
        echo '<a href="pages_php/news.php" class="btn">Manage News</a>';
        echo '<a href="pages_php/events.php" class="btn">Manage Events</a>';
        echo '</div>';

        // Debug Information
        echo '<h2>🔧 Debug Information</h2>';
        echo '<div class="card">';
        echo '<strong>PHP Version:</strong> ' . phpversion() . '<br>';
        echo '<strong>MySQL Version:</strong> ' . mysqli_get_server_info($conn) . '<br>';
        echo '<strong>Current Time:</strong> ' . date('Y-m-d H:i:s') . '<br>';
        echo '<strong>Timezone:</strong> ' . date_default_timezone_get() . '<br>';
        echo '</div>';

        mysqli_close($conn);
        ?>

        <div style="text-align:center; margin-top:40px; padding:20px; border-top:2px solid #ddd;">
            <p style="color:#666;">
                <strong>Need help?</strong> Check the
                <a href="QUICK_START.md" style="color:#667eea;">Quick Start Guide</a> or
                <a href="HOMEPAGE_IMPLEMENTATION_GUIDE.md" style="color:#667eea;">Full Documentation</a>
            </p>
        </div>
    </div>
</body>
</html>
