<?php
// Include database configuration
require_once 'db_config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>SRC Management System - Setup News Table</title>
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
    <h1>SRC Management System - Setup News Table</h1>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
if ($conn) {
    echo "<p class='success'>Successfully connected to the database!</p>";
} else {
    echo "<p class='error'>Failed to connect to the database. Please check your configuration.</p>";
    exit;
}

// Check if sample data already exists
$checkData = mysqli_query($conn, "SELECT COUNT(*) as count FROM news");
$hasData = false;
if ($checkData) {
    $row = mysqli_fetch_assoc($checkData);
    if ($row['count'] > 0) {
        $hasData = true;
        echo "<p class='warning'>News table already has data. There are " . $row['count'] . " news items.</p>";
        echo "<form method='post'>
                <input type='submit' name='add_more' value='Add More Sample Data' style='background: #28a745; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;'>
                <input type='submit' name='delete_all' value='Delete All and Recreate' style='background: #dc3545; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; margin-left: 10px;'>
                <input type='submit' name='keep_data' value='Keep Existing Data' style='background: #007bff; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; margin-left: 10px;'>
              </form>";
        
        if (isset($_POST['keep_data'])) {
            echo "<p class='success'>Keeping existing data.</p>";
            echo "<p><a href='pages_php/news.php'>Go to News Page</a></p>";
            echo "</body></html>";
            exit;
        } else if (isset($_POST['delete_all'])) {
            $truncateTable = mysqli_query($conn, "TRUNCATE TABLE news");
            if ($truncateTable) {
                echo "<p class='success'>Deleted all news items.</p>";
                $hasData = false;
            } else {
                echo "<p class='error'>Error deleting news items: " . mysqli_error($conn) . "</p>";
                exit;
            }
        } else if (!isset($_POST['add_more'])) {
            echo "</body></html>";
            exit;
        }
    }
}

// Proceed with adding sample data
echo "<h2>Adding Sample News Items</h2>";

// Get admin user ID for author_id
$adminUser = mysqli_query($conn, "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1");
$adminId = 1; // Default if no admin is found
if ($adminUser && mysqli_num_rows($adminUser) > 0) {
    $admin = mysqli_fetch_assoc($adminUser);
    $adminId = $admin['user_id'];
}

// Sample news items
$sampleNews = [
    [
        'title' => 'SRC Elections Announced',
        'content' => 'The SRC elections for the upcoming academic year have been announced. Students interested in running for positions should submit their applications by the end of this month. More details will be provided during the information session next week.',
        'author_id' => $adminId,
        'image' => '',
        'status' => 'published'
    ],
    [
        'title' => 'New Campus Facilities Opening',
        'content' => 'We are excited to announce the opening of new campus facilities including a modernized library, student lounge, and computer lab. These facilities will be available for use starting next month. A formal opening ceremony will be held on campus.',
        'author_id' => $adminId,
        'image' => '',
        'status' => 'published'
    ],
    [
        'title' => 'Student Achievements 2023',
        'content' => 'Congratulations to all students who received academic and extracurricular achievement awards this year. The university is proud of your accomplishments and continues to support excellence in all areas of student life.',
        'author_id' => $adminId,
        'image' => '',
        'status' => 'published'
    ],
    [
        'title' => 'Upcoming Cultural Festival',
        'content' => 'The annual cultural festival will be held next month. Students are encouraged to participate and showcase their cultural heritage through performances, food, and exhibitions. Registration for booths and performances is now open.',
        'author_id' => $adminId,
        'image' => '',
        'status' => 'published'
    ],
    [
        'title' => 'Library Hours Extended',
        'content' => 'In response to student feedback, the library will now be open for extended hours during exam periods. The new hours will be from 7 AM to midnight on weekdays and 9 AM to 9 PM on weekends.',
        'author_id' => $adminId,
        'image' => '',
        'status' => 'published'
    ],
    [
        'title' => 'Academic Calendar Update',
        'content' => 'Please note the updates to the academic calendar for the upcoming semester. Midterm examinations will begin on October 15th, and final examinations will start on December 5th. The updated calendar is available on the university website.',
        'author_id' => $adminId,
        'image' => '',
        'status' => 'draft'
    ]
];

$insertCount = 0;
$errorCount = 0;

foreach ($sampleNews as $news) {
    $sql = "INSERT INTO news (title, content, author_id, image, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssiss", 
            $news['title'], 
            $news['content'],
            $news['author_id'],
            $news['image'],
            $news['status']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $insertCount++;
        } else {
            $errorCount++;
            echo "<p class='error'>Error inserting news item '" . $news['title'] . "': " . mysqli_error($conn) . "</p>";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $errorCount++;
        echo "<p class='error'>Error preparing statement: " . mysqli_error($conn) . "</p>";
    }
}

echo "<p class='success'>Successfully inserted $insertCount news items.</p>";
if ($errorCount > 0) {
    echo "<p class='warning'>Failed to insert $errorCount news items.</p>";
}

echo "<h2>News Table Setup Complete</h2>";
echo "<p><a href='pages_php/news.php'>Go to News Page</a></p>";

echo "</body></html>";
?> 