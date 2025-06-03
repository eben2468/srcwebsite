<?php
// Script to add new portfolios
require_once 'db_config.php';

echo "<h1>Adding New Portfolios</h1>";
echo "<pre>";

// Check if the tables exist
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'portfolios'");
if (mysqli_num_rows($checkTable) == 0) {
    echo "Table 'portfolios' does not exist. Please run create_portfolios_table.php first.\n";
    exit;
}

// New portfolios to add
$portfolios = [
    [
        'title' => 'Senate President',
        'name' => 'Michael Naidoo',
        'email' => 'senate@src.ac.za',
        'phone' => '+27 73 456 7890',
        'photo' => 'secretary.jpg',
        'description' => 'The Senate President represents student interests in the university Senate. They ensure student perspectives are incorporated into academic policies and decisions.',
        'responsibilities' => json_encode([
            'Represent students in the university Senate',
            'Communicate academic policy changes to students',
            'Advocate for student academic interests',
            'Coordinate with faculty representatives',
            'Report senate proceedings to the SRC',
            'Participate in academic governance structures',
            'Provide student input on curriculum development',
            'Address academic concerns raised by students'
        ]),
        'qualifications' => json_encode([
            'Strong academic background',
            'Knowledge of university governance',
            'Excellent communication skills',
            'Understanding of academic policies',
            'Ability to articulate student concerns effectively'
        ])
    ],
    [
        'title' => 'Women\'s Commissioner',
        'name' => 'Thandi Ndlovu',
        'email' => 'women@src.ac.za',
        'phone' => '+27 81 234 5678',
        'photo' => 'default.jpg',
        'description' => 'The Women\'s Commissioner advocates for gender equality and women\'s rights on campus. They address issues affecting female students and create awareness programs to promote gender equality.',
        'responsibilities' => json_encode([
            'Advocate for women\'s rights and gender equality',
            'Organize awareness campaigns on gender-based issues',
            'Provide support for female students facing discrimination',
            'Coordinate women\'s empowerment programs',
            'Liaise with university gender offices',
            'Represent women\'s interests in SRC decision-making',
            'Address harassment and safety concerns'
        ]),
        'qualifications' => json_encode([
            'Understanding of gender issues and equality principles',
            'Strong advocacy skills',
            'Good communication and interpersonal abilities',
            'Experience in women\'s rights activities',
            'Leadership capabilities'
        ])
    ],
    [
        'title' => 'Chaplain',
        'name' => 'Reverend Simon Mokoena',
        'email' => 'chaplain@src.ac.za',
        'phone' => '+27 82 345 6789',
        'photo' => 'default.jpg',
        'description' => 'The Chaplain provides spiritual guidance and pastoral care to students. They organize religious activities, interfaith dialogues, and offer counseling services to support the spiritual wellbeing of the student community.',
        'responsibilities' => json_encode([
            'Provide spiritual guidance and pastoral care',
            'Organize religious services and events',
            'Facilitate interfaith dialogues and activities',
            'Offer counseling services for students',
            'Support students during personal crises',
            'Promote spiritual wellbeing on campus',
            'Coordinate with religious student organizations'
        ]),
        'qualifications' => json_encode([
            'Religious or theological training',
            'Counseling experience',
            'Interfaith understanding and respect',
            'Strong communication and listening skills',
            'Empathy and compassion'
        ])
    ],
    [
        'title' => 'Editor',
        'name' => 'Nomsa Kubeka',
        'email' => 'editor@src.ac.za',
        'phone' => '+27 75 678 9012',
        'photo' => 'media.jpg',
        'description' => 'The Editor manages all SRC media and publications. They oversee the creation and distribution of newsletters, social media content, and other information resources to keep students informed about SRC activities and initiatives.',
        'responsibilities' => json_encode([
            'Managing SRC media',
            'Publications and newsletters',
            'Social media coordination',
            'Public relations',
            'Content creation and editing',
            'Promotion of SRC events and initiatives',
            'Brand management',
            'Media monitoring and response'
        ]),
        'qualifications' => json_encode([
            'Strong writing and editing skills',
            'Knowledge of media production',
            'Creativity and design sense',
            'Social media expertise',
            'Communication abilities'
        ])
    ]
];

// Insert each portfolio into the database
foreach ($portfolios as $portfolio) {
    // Check if the portfolio already exists
    $checkPortfolio = mysqli_query($conn, "SELECT portfolio_id FROM portfolios WHERE title = '" . mysqli_real_escape_string($conn, $portfolio['title']) . "'");
    
    if (mysqli_num_rows($checkPortfolio) > 0) {
        echo "Portfolio '{$portfolio['title']}' already exists. Skipping.\n";
        continue;
    }
    
    $sql = "INSERT INTO portfolios (title, name, email, phone, photo, description, responsibilities, qualifications) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssss", 
        $portfolio['title'], 
        $portfolio['name'], 
        $portfolio['email'], 
        $portfolio['phone'], 
        $portfolio['photo'], 
        $portfolio['description'], 
        $portfolio['responsibilities'], 
        $portfolio['qualifications']
    );
    
    if (mysqli_stmt_execute($stmt)) {
        echo "Inserted portfolio: {$portfolio['title']} - {$portfolio['name']}\n";
    } else {
        echo "Error inserting portfolio {$portfolio['title']}: " . mysqli_error($conn) . "\n";
    }
    
    mysqli_stmt_close($stmt);
}

echo "\nNew portfolios added successfully!";
echo "</pre>";
?> 