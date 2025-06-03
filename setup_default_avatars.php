<?php
// Script to check and set up default avatars
echo "<h1>Setting Up Default Avatars</h1>";
echo "<pre>";

// Update database to use default.jpg for new portfolios
require_once 'db_config.php';

$portfolioAvatars = [
    'Women\'s Commissioner' => 'default.jpg',
    'Chaplain' => 'default.jpg'
];

foreach ($portfolioAvatars as $title => $avatar) {
    $sql = "UPDATE portfolios SET photo = ? WHERE title = ? AND (photo IS NULL OR photo = '')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $avatar, $title);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        if ($affected > 0) {
            echo "Updated avatar for {$title} to {$avatar}.\n";
        } else {
            echo "No update needed for {$title}.\n";
        }
    } else {
        echo "Error updating avatar for {$title}: " . mysqli_error($conn) . "\n";
    }
    
    mysqli_stmt_close($stmt);
}

echo "\nAvatar setup completed successfully!";
echo "</pre>";
?> 