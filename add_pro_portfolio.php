<?php
// Script to add Public Relations Officer portfolio
require_once 'db_config.php';

echo "<h1>Adding Public Relations Officer Portfolio</h1>";

// Check if portfolio already exists
$checkPortfolio = mysqli_query($conn, "SELECT * FROM portfolios WHERE title = 'Public Relations Officer (P.R.O)'");
if (mysqli_num_rows($checkPortfolio) > 0) {
    echo "<p>Public Relations Officer portfolio already exists. No action taken.</p>";
} else {
    // Insert new portfolio
    $title = "Public Relations Officer (P.R.O)";
    $name = "Patricia Mokoena";
    $email = "pro@src.ac.za";
    $phone = "+27 73 111 2222";
    $photo = "default.jpg";
    $description = "The Public Relations Officer is responsible for managing the public image of the SRC. They handle external communications, media relations, and promote SRC activities to the broader community.";
    
    $responsibilities = json_encode([
        'Manage the public image and reputation of the SRC',
        'Coordinate external communications and media relations',
        'Develop and implement PR strategies and campaigns',
        'Prepare press releases and official statements',
        'Organize press conferences and media briefings',
        'Liaise with university communications department',
        'Monitor media coverage of SRC activities',
        'Manage crisis communications when necessary'
    ]);
    
    $qualifications = json_encode([
        'Strong communication and public speaking skills',
        'Media relations experience',
        'Writing and content creation abilities',
        'Understanding of public relations principles',
        'Crisis management capabilities',
        'Social media management skills'
    ]);
    
    $sql = "INSERT INTO portfolios (title, name, email, phone, photo, description, responsibilities, qualifications) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssss", $title, $name, $email, $phone, $photo, $description, $responsibilities, $qualifications);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p>Public Relations Officer portfolio added successfully!</p>";
    } else {
        echo "<p>Error adding portfolio: " . mysqli_error($conn) . "</p>";
    }
    
    mysqli_stmt_close($stmt);
}

echo "<p><a href='pages_php/portfolio.php'>View Portfolios</a></p>";
?> 