<?php
// Include database configuration
require_once 'db_config.php';

// Display header
echo "<!DOCTYPE html>
<html>
<head>
    <title>SRC Management System - Setup Events Table</title>
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
    <h1>SRC Management System - Setup Events Table</h1>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
if ($conn) {
    echo "<p class='success'>Successfully connected to the database!</p>";
} else {
    echo "<p class='error'>Failed to connect to the database. Please check your configuration.</p>";
    exit;
}

// Check if events table exists
echo "<h2>Checking Events Table</h2>";
$tableExists = mysqli_query($conn, "SHOW TABLES LIKE 'events'");

if (mysqli_num_rows($tableExists) > 0) {
    echo "<p class='warning'>Events table already exists. Do you want to drop it and recreate?</p>";
    echo "<form method='post'>
            <input type='submit' name='drop_table' value='Drop and Recreate Table' style='background: #dc3545; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;'>
            <input type='submit' name='keep_table' value='Keep Existing Table' style='background: #28a745; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; margin-left: 10px;'>
          </form>";
    
    if (isset($_POST['drop_table'])) {
        $dropTable = mysqli_query($conn, "DROP TABLE events");
        if ($dropTable) {
            echo "<p class='success'>Events table dropped successfully.</p>";
        } else {
            echo "<p class='error'>Error dropping events table: " . mysqli_error($conn) . "</p>";
            exit;
        }
    } elseif (isset($_POST['keep_table'])) {
        echo "<p class='success'>Keeping existing events table.</p>";
        echo "<p><a href='pages_php/events.php'>Go to Events Page</a></p>";
        echo "</body></html>";
        exit;
    } else {
        echo "</body></html>";
        exit;
    }
}

// Create events table
echo "<h2>Creating Events Table</h2>";
$createTableSQL = "CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    end_date DATE NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('Planning', 'Upcoming', 'Ongoing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Planning',
    description TEXT,
    organizer VARCHAR(255),
    capacity INT DEFAULT 0,
    registrations INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $createTableSQL)) {
    echo "<p class='success'>Events table created successfully!</p>";
} else {
    echo "<p class='error'>Error creating events table: " . mysqli_error($conn) . "</p>";
    exit;
}

// Insert sample data
echo "<h2>Inserting Sample Data</h2>";

$sampleEvents = [
    [
        'event_name' => 'Orientation Week',
        'event_date' => '2023-08-15',
        'end_date' => '2023-08-20',
        'location' => 'Main Campus',
        'status' => 'Upcoming',
        'description' => 'Welcome event for new students',
        'organizer' => 'Student Affairs',
        'capacity' => 500,
        'registrations' => 320
    ],
    [
        'event_name' => 'Leadership Workshop',
        'event_date' => '2023-08-20',
        'end_date' => '2023-08-20',
        'location' => 'Conference Hall',
        'status' => 'Upcoming',
        'description' => 'Workshop to develop leadership skills',
        'organizer' => 'SRC',
        'capacity' => 100,
        'registrations' => 75
    ],
    [
        'event_name' => 'Cultural Festival',
        'event_date' => '2023-09-05',
        'end_date' => '2023-09-07',
        'location' => 'Student Center',
        'status' => 'Upcoming',
        'description' => 'Celebration of diverse cultures',
        'organizer' => 'Cultural Committee',
        'capacity' => 1000,
        'registrations' => 650
    ],
    [
        'event_name' => 'Career Fair',
        'event_date' => '2023-09-15',
        'end_date' => '2023-09-16',
        'location' => 'Exhibition Hall',
        'status' => 'Planning',
        'description' => 'Connect with potential employers',
        'organizer' => 'Career Services',
        'capacity' => 800,
        'registrations' => 0
    ],
    [
        'event_name' => 'Academic Excellence Awards',
        'event_date' => '2023-10-10',
        'end_date' => '2023-10-10',
        'location' => 'Auditorium',
        'status' => 'Planning',
        'description' => 'Recognizing outstanding academic achievements',
        'organizer' => 'Academic Affairs',
        'capacity' => 300,
        'registrations' => 0
    ],
    [
        'event_name' => 'Sports Tournament',
        'event_date' => '2023-07-10',
        'end_date' => '2023-07-15',
        'location' => 'Sports Complex',
        'status' => 'Completed',
        'description' => 'Annual inter-department sports competition',
        'organizer' => 'Sports Department',
        'capacity' => 1200,
        'registrations' => 1100
    ]
];

$insertSuccess = 0;
$insertErrors = 0;

foreach ($sampleEvents as $event) {
    $insertSQL = "INSERT INTO events (event_name, event_date, end_date, location, status, description, organizer, capacity, registrations) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $insertSQL);
    mysqli_stmt_bind_param($stmt, "sssssssii", 
        $event['event_name'], 
        $event['event_date'], 
        $event['end_date'],
        $event['location'], 
        $event['status'], 
        $event['description'], 
        $event['organizer'], 
        $event['capacity'], 
        $event['registrations']
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $insertSuccess++;
    } else {
        $insertErrors++;
        echo "<p class='error'>Error inserting event '" . $event['event_name'] . "': " . mysqli_error($conn) . "</p>";
    }
    
    mysqli_stmt_close($stmt);
}

echo "<p class='success'>Successfully inserted $insertSuccess events.</p>";
if ($insertErrors > 0) {
    echo "<p class='warning'>Failed to insert $insertErrors events.</p>";
}

// Create event_registrations table
echo "<h2>Creating Event Registrations Table</h2>";
$createRegistrationsTableSQL = "CREATE TABLE event_registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT,
    attendee_name VARCHAR(255),
    attendee_email VARCHAR(255),
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Registered', 'Attended', 'Cancelled', 'No-Show') NOT NULL DEFAULT 'Registered',
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $createRegistrationsTableSQL)) {
    echo "<p class='success'>Event Registrations table created successfully!</p>";
} else {
    echo "<p class='error'>Error creating Event Registrations table: " . mysqli_error($conn) . "</p>";
}

// Verify the table structure
echo "<h2>Verification</h2>";
$tableInfo = mysqli_query($conn, "DESCRIBE events");
if (mysqli_num_rows($tableInfo) > 0) {
    echo "<p>Events table structure:</p>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($tableInfo)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<p class='success'>Events database setup is complete!</p>";
echo "<p><a href='pages_php/events.php'>Go to Events Page</a></p>";
echo "</body></html>";
?> 