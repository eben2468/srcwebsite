<?php
require_once 'db_config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the minutes table exists
$checkTableSQL = "SHOW TABLES LIKE 'minutes'";
$result = $conn->query($checkTableSQL);

if (!$result || $result->num_rows == 0) {
    die("Minutes table does not exist. Please run create_minutes_table.php first.");
}

// Get a valid user ID from the database (for foreign key constraint)
$userSql = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
$userResult = $conn->query($userSql);

if (!$userResult || $userResult->num_rows == 0) {
    die("No admin user found in the database. Please create an admin user first.");
}

$adminUser = $userResult->fetch_assoc();
$adminId = $adminUser['user_id'];

// Sample data
$sampleMinutes = [
    [
        'title' => 'SRC General Meeting',
        'committee' => 'General SRC',
        'meeting_date' => '2023-10-15',
        'location' => 'Student Union Building, Room 302',
        'attendees' => 'John Dlamini (President), Sarah Molefe (Deputy President), Michael Naidoo (Secretary General), Thabo Motsoaledi (Treasurer), Lerato Phiri (Academic Affairs), James Sithole (Sports & Culture)',
        'apologies' => 'Nomsa Kubeka (Media & Communications)',
        'agenda' => "1. Welcome and apologies\n2. Minutes of previous meeting\n3. Matters arising\n4. Budget approval\n5. Upcoming events\n6. AOB",
        'summary' => 'The meeting primarily focused on budget approval for the upcoming semester and planning for orientation week events.',
        'decisions' => "1. Budget for orientation week approved\n2. Social media campaign for new students to be launched\n3. Sports tournament scheduled for second week of semester",
        'actions' => "1. Treasurer to finalize budget allocation - Due: 20/10/2023\n2. Secretary to book venues for orientation - Due: 25/10/2023\n3. Sports Officer to coordinate with team captains - Due: 30/10/2023",
        'next_meeting_date' => '2023-11-05',
        'status' => 'Approved'
    ],
    [
        'title' => 'Executive Committee Meeting',
        'committee' => 'Executive Committee',
        'meeting_date' => '2023-09-28',
        'location' => 'Online (Zoom)',
        'attendees' => 'John Dlamini (President), Sarah Molefe (Deputy President), Michael Naidoo (Secretary General), Thabo Motsoaledi (Treasurer)',
        'apologies' => 'None',
        'agenda' => "1. Welcome\n2. Strategic planning for the year\n3. Budget review\n4. Upcoming meetings schedule\n5. AOB",
        'summary' => 'The executive committee met to discuss strategic priorities for the academic year and review the initial budget proposal.',
        'decisions' => "1. Strategic plan draft approved\n2. Initial budget adjustments recommended\n3. Monthly meeting schedule confirmed",
        'actions' => "1. President to present strategic plan to full SRC - Due: 15/10/2023\n2. Treasurer to revise budget proposal - Due: 05/10/2023",
        'next_meeting_date' => '2023-10-12',
        'status' => 'Approved'
    ],
    [
        'title' => 'Special Meeting: Residence Issues',
        'committee' => 'Residence Committee',
        'meeting_date' => '2023-09-10',
        'location' => 'Student Union Building, Room 105',
        'attendees' => 'John Dlamini (President), Sarah Molefe (Deputy President), Lungile Ndlovu (Student Welfare), Residence Representatives (5)',
        'apologies' => 'Michael Naidoo (Secretary General)',
        'agenda' => "1. Welcome and apologies\n2. Residence maintenance concerns\n3. Safety and security issues\n4. Meal plan complaints\n5. Way forward",
        'summary' => 'Emergency meeting called to address multiple complaints from residence students regarding maintenance, security, and food quality.',
        'decisions' => "1. Formal complaint to be submitted to university management\n2. Weekly residence inspections to be implemented\n3. Meeting with catering services to be arranged",
        'actions' => "1. President to draft formal complaint - Due: 15/09/2023\n2. Welfare Officer to organize residence inspections - Due: 20/09/2023\n3. Deputy President to arrange meeting with catering - Due: 17/09/2023",
        'next_meeting_date' => '2023-09-24',
        'status' => 'Draft'
    ]
];

// Insert sample data
echo "<h2>Inserting Sample Minutes Data</h2>";

$insertCount = 0;
foreach ($sampleMinutes as $minutes) {
    $sql = "INSERT INTO minutes (
        title, committee, meeting_date, location, attendees, apologies, agenda, 
        summary, decisions, actions, next_meeting_date, status, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error . "<br>";
        continue;
    }
    
    $stmt->bind_param(
        "ssssssssssssi",
        $minutes['title'],
        $minutes['committee'],
        $minutes['meeting_date'],
        $minutes['location'],
        $minutes['attendees'],
        $minutes['apologies'],
        $minutes['agenda'],
        $minutes['summary'],
        $minutes['decisions'],
        $minutes['actions'],
        $minutes['next_meeting_date'],
        $minutes['status'],
        $adminId
    );
    
    if ($stmt->execute()) {
        echo "✓ Added minutes: " . htmlspecialchars($minutes['title']) . "<br>";
        $insertCount++;
    } else {
        echo "✗ Failed to add minutes: " . htmlspecialchars($minutes['title']) . " - Error: " . $stmt->error . "<br>";
    }
    
    $stmt->close();
}

echo "<p>Successfully added $insertCount of " . count($sampleMinutes) . " sample minutes.</p>";

// Show current minutes in the database
$countSql = "SELECT COUNT(*) as total FROM minutes";
$countResult = $conn->query($countSql);
$count = $countResult->fetch_assoc()['total'];

echo "<h3>Total Minutes in Database: $count</h3>";

if ($count > 0) {
    $listSql = "SELECT minutes_id, title, committee, meeting_date, status FROM minutes ORDER BY meeting_date DESC";
    $listResult = $conn->query($listSql);
    
    if ($listResult && $listResult->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>Committee</th><th>Date</th><th>Status</th></tr>";
        
        while ($row = $listResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['minutes_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['committee']) . "</td>";
            echo "<td>" . $row['meeting_date'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
}

// Close the connection
$conn->close();
?> 