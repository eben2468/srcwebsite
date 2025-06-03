<?php
// Script to create and populate the portfolios table
require_once 'db_config.php';

echo "<h1>Creating Portfolios Table</h1>";
echo "<pre>";

// Check if the table already exists
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'portfolios'");
if (mysqli_num_rows($checkTable) > 0) {
    echo "Table 'portfolios' already exists. Skipping creation.\n";
} else {
    // Create portfolios table
    $sql = "CREATE TABLE portfolios (
        portfolio_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        photo VARCHAR(255),
        description TEXT,
        responsibilities TEXT,
        qualifications TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    if (mysqli_query($conn, $sql)) {
        echo "Table 'portfolios' created successfully.\n";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "\n";
        exit;
    }
}

// Check if there are already records in the table
$checkRecords = mysqli_query($conn, "SELECT COUNT(*) as count FROM portfolios");
$row = mysqli_fetch_assoc($checkRecords);
if ($row['count'] > 0) {
    echo "Table 'portfolios' already has {$row['count']} records. Skipping data insertion.\n";
} else {
    // Insert initial portfolio data
    $portfolios = [
        [
            'title' => 'President',
            'name' => 'John Dlamini',
            'email' => 'president@src.ac.za',
            'phone' => '+27 71 234 5678',
            'photo' => 'president.jpg',
            'description' => 'The President is the chief executive officer of the SRC and the official spokesperson for the student body. They chair SRC meetings, represent students in university governance structures, and lead the development and implementation of SRC policies and programs.',
            'responsibilities' => json_encode([
                'Provide leadership and direction to the SRC',
                'Represent the student body at official functions and meetings',
                'Chair Executive Committee meetings and SRC general meetings',
                'Ensure effective communication between the SRC and university management',
                'Coordinate activities of all SRC portfolios',
                'Present the annual report on SRC activities',
                'Serve on the University Council and Senate',
                'Advocate for student interests and welfare'
            ]),
            'qualifications' => json_encode([
                'Strong leadership skills',
                'Excellent communication and public speaking abilities',
                'Good understanding of university governance',
                'Problem-solving and conflict resolution skills',
                'Previous experience in student leadership'
            ])
        ],
        [
            'title' => 'Vice President',
            'name' => 'Sarah Molefe',
            'email' => 'vicepresident@src.ac.za',
            'phone' => '+27 72 345 6789',
            'photo' => 'deputy.jpg',
            'description' => 'The Vice President assists the President in executing their duties and assumes the responsibilities of the President in their absence. They also oversee specific projects and initiatives as delegated by the President.',
            'responsibilities' => json_encode([
                'Assist the President in all duties',
                'Assume presidential duties when the President is absent',
                'Coordinate special projects as assigned by the President',
                'Supervise and support the work of subcommittees',
                'Serve as the liaison between the Executive Committee and other SRC members',
                'Attend university governance meetings as required',
                'Support portfolio holders in executing their mandates'
            ]),
            'qualifications' => json_encode([
                'Leadership and organizational skills',
                'Ability to work well in a team',
                'Good communication and interpersonal skills',
                'Problem-solving abilities',
                'Knowledge of university policies and procedures'
            ])
        ],
        [
            'title' => 'Senate President',
            'name' => 'Michael Naidoo',
            'email' => 'senate@src.ac.za',
            'phone' => '+27 73 456 7890',
            'photo' => 'secretary.jpg',
            'description' => 'The Secretary General is responsible for the administrative functions of the SRC, including record-keeping, correspondence, and communication. They ensure proper documentation of all SRC activities and decisions.',
            'responsibilities' => json_encode([
                'Maintain accurate records of all SRC meetings and activities',
                'Handle official SRC correspondence',
                'Prepare and distribute meeting agendas and minutes',
                'Manage the SRC filing system and archives',
                'Coordinate internal and external communications',
                'Ensure compliance with constitutional requirements',
                'Oversee the implementation of SRC resolutions',
                'Maintain the SRC calendar of events and activities'
            ]),
            'qualifications' => json_encode([
                'Excellent organizational and administrative skills',
                'Strong written and verbal communication abilities',
                'Attention to detail and record-keeping aptitude',
                'Time management and punctuality',
                'Knowledge of meeting procedures and documentation'
            ])
        ],
        [
            'title' => 'Finance Officer',
            'name' => 'Thabo Motsoaledi',
            'email' => 'finance@src.ac.za',
            'phone' => '+27 74 567 8901',
            'photo' => 'treasurer.jpg',
            'description' => 'The Finance Officer is responsible for managing the SRC budget and finances. They oversee the allocation of funds to various SRC activities and ensure financial accountability and transparency.',
            'responsibilities' => json_encode([
                'Financial management',
                'Budget preparation and oversight',
                'Financial reporting',
                'Fundraising initiatives',
                'Process funding requests from student organizations',
                'Ensure compliance with financial regulations',
                'Present financial reports to the SRC and university',
                'Maintain accurate financial records'
            ]),
            'qualifications' => json_encode([
                'Knowledge of financial management principles',
                'Budgeting and accounting skills',
                'Attention to detail',
                'Integrity and trustworthiness',
                'Understanding of financial reporting'
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
        ],
        [
            'title' => 'Academic Officer',
            'name' => 'Lerato Phiri',
            'email' => 'academic@src.ac.za',
            'phone' => '+27 76 789 0123',
            'photo' => 'academic.jpg',
            'description' => 'The Academic Officer represents student interests in academic matters. They advocate for quality education, fair assessment, and appropriate academic support for all students.',
            'responsibilities' => json_encode([
                'Academic issues and concerns',
                'Coordination with faculty representatives',
                'Academic support programs',
                'Student academic advocacy',
                'Liaison with academic departments',
                'Representation on academic committees',
                'Academic policy review and input',
                'Study resources and support coordination'
            ]),
            'qualifications' => json_encode([
                'Strong academic background',
                'Understanding of academic policies',
                'Advocacy skills',
                'Ability to analyze educational issues',
                'Good relationships with faculty members'
            ])
        ],
        [
            'title' => 'Sports Officer',
            'name' => 'James Sithole',
            'email' => 'sports@src.ac.za',
            'phone' => '+27 77 890 1234',
            'photo' => 'sports.jpg',
            'description' => 'The Sports Officer coordinates and promotes sports activities on campus. They work with sports teams and clubs to organize competitions, secure facilities, and advocate for sports development.',
            'responsibilities' => json_encode([
                'Sports programs and events',
                'Coordination with sports teams',
                'Sports facilities management',
                'Interuniversity sports relations',
                'Sports budget management',
                'Promotion of student participation in sports',
                'Organization of sports tournaments',
                'Liaison with university sports department'
            ]),
            'qualifications' => json_encode([
                'Interest and experience in sports',
                'Organizational skills',
                'Knowledge of sports administration',
                'Team management abilities',
                'Event planning experience'
            ])
        ],
        [
            'title' => 'Welfare Officer',
            'name' => 'Lungile Ndlovu',
            'email' => 'welfare@src.ac.za',
            'phone' => '+27 78 901 2345',
            'photo' => 'welfare.jpg',
            'description' => 'The Welfare Officer is responsible for student welfare matters, including health, accommodation, safety, and general wellbeing. They work to ensure a supportive and inclusive campus environment.',
            'responsibilities' => json_encode([
                'Student welfare and wellbeing',
                'Residence life coordination',
                'Health and safety initiatives',
                'Student support services liaison',
                'Addressing accommodation issues',
                'Mental health awareness programs',
                'Diversity and inclusion initiatives',
                'Support for students in crisis'
            ]),
            'qualifications' => json_encode([
                'Empathy and interpersonal skills',
                'Knowledge of welfare services',
                'Advocacy abilities',
                'Crisis management capabilities',
                'Understanding of diverse student needs'
            ])
        ],
        [
            'title' => 'International Students Officer',
            'name' => 'Fatima Hassan',
            'email' => 'international@src.ac.za',
            'phone' => '+27 79 012 3456',
            'photo' => 'international.jpg',
            'description' => 'The International Students Officer represents the interests of international students. They address specific challenges faced by international students and promote cultural exchange and integration.',
            'responsibilities' => json_encode([
                'International student representation',
                'Integration programs',
                'Cultural awareness initiatives',
                'International affairs coordination',
                'Support for visa and registration issues',
                'Organization of cultural exchange events',
                'Orientation for new international students',
                'Liaison with international office'
            ]),
            'qualifications' => json_encode([
                'Cultural sensitivity',
                'Understanding of international student issues',
                'Communication skills',
                'Event organization abilities',
                'Knowledge of immigration policies'
            ])
        ]
    ];

    // Insert each portfolio into the database
    foreach ($portfolios as $portfolio) {
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
    
    echo "All portfolio data has been inserted successfully.\n";
}

// Create portfolios_initiatives table for portfolio initiatives
$checkInitiativesTable = mysqli_query($conn, "SHOW TABLES LIKE 'portfolio_initiatives'");
if (mysqli_num_rows($checkInitiativesTable) > 0) {
    echo "Table 'portfolio_initiatives' already exists. Skipping creation.\n";
} else {
    $sql = "CREATE TABLE portfolio_initiatives (
        initiative_id INT AUTO_INCREMENT PRIMARY KEY,
        portfolio_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        status VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (portfolio_id) REFERENCES portfolios(portfolio_id) ON DELETE CASCADE
    )";

    if (mysqli_query($conn, $sql)) {
        echo "Table 'portfolio_initiatives' created successfully.\n";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "\n";
    }
}

echo "\nSetup completed successfully!";
echo "</pre>";
?> 