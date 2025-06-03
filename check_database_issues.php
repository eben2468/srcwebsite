<?php
// Include database connection
require_once 'db_config.php';

echo "<h1>Dashboard Data Issues Diagnosis</h1>";

// Function to safely check if a table exists
function tableExists($tableName) {
    global $conn;
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Function to get table structure
function getTableStructure($tableName) {
    global $conn;
    $structure = [];
    $result = $conn->query("DESCRIBE $tableName");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $structure[] = $row;
        }
    }
    return $structure;
}

// Function to count records with optional condition
function countRecords($tableName, $condition = '') {
    global $conn;
    $count = 0;
    $sql = "SELECT COUNT(*) as count FROM $tableName";
    if (!empty($condition)) {
        $sql .= " WHERE $condition";
    }
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $count = $row['count'];
    }
    return $count;
}

// Function to sample data from a table
function sampleData($tableName, $limit = 5) {
    global $conn;
    $data = [];
    $sql = "SELECT * FROM $tableName LIMIT $limit";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Check events table
echo "<h2>Events Table</h2>";
if (tableExists('events')) {
    echo "<p style='color: green'>Table exists</p>";
    
    // Check structure
    echo "<h3>Structure:</h3>";
    $structure = getTableStructure('events');
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
    
    // Count records
    $totalEvents = countRecords('events');
    echo "<p>Total events: $totalEvents</p>";
    
    // Count upcoming events
    $upcomingEvents = countRecords('events', "date >= CURDATE()");
    echo "<p>Upcoming events: $upcomingEvents</p>";
    
    // Sample data
    echo "<h3>Sample Data:</h3>";
    $sampleEvents = sampleData('events');
    echo "<pre>";
    print_r($sampleEvents);
    echo "</pre>";
} else {
    echo "<p style='color: red'>Table does not exist</p>";
}

// Check news table
echo "<h2>News Table</h2>";
if (tableExists('news')) {
    echo "<p style='color: green'>Table exists</p>";
    
    // Check structure
    echo "<h3>Structure:</h3>";
    $structure = getTableStructure('news');
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
    
    // Count records
    $totalNews = countRecords('news');
    echo "<p>Total news: $totalNews</p>";
    
    // Count published news
    $publishedNews = countRecords('news', "status = 'published'");
    echo "<p>Published news: $publishedNews</p>";
    
    // Sample data
    echo "<h3>Sample Data:</h3>";
    $sampleNews = sampleData('news');
    echo "<pre>";
    print_r($sampleNews);
    echo "</pre>";
} else {
    echo "<p style='color: red'>Table does not exist</p>";
}

// Check elections table
echo "<h2>Elections Table</h2>";
if (tableExists('elections')) {
    echo "<p style='color: green'>Table exists</p>";
    
    // Check structure
    echo "<h3>Structure:</h3>";
    $structure = getTableStructure('elections');
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
    
    // Count records
    $totalElections = countRecords('elections');
    echo "<p>Total elections: $totalElections</p>";
    
    // Count active elections
    $activeElections = countRecords('elections', "status = 'active'");
    echo "<p>Active elections: $activeElections</p>";
    
    // Sample data
    echo "<h3>Sample Data:</h3>";
    $sampleElections = sampleData('elections');
    echo "<pre>";
    print_r($sampleElections);
    echo "</pre>";
} else {
    echo "<p style='color: red'>Table does not exist</p>";
}

// Check dashboard queries
echo "<h2>Dashboard Queries Test</h2>";

// Test events query
echo "<h3>Events Count Query:</h3>";
try {
    $eventCountSql = "SELECT COUNT(*) as count FROM events";
    $eventCountResult = $conn->query($eventCountSql);
    if ($eventCountResult) {
        $row = $eventCountResult->fetch_assoc();
        echo "<p>Query result: " . $row['count'] . "</p>";
    } else {
        echo "<p style='color: red'>Query failed: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>Exception: " . $e->getMessage() . "</p>";
}

// Test news query
echo "<h3>News Count Query:</h3>";
try {
    $newsCountSql = "SELECT COUNT(*) as count FROM news WHERE status = 'published'";
    $newsCountResult = $conn->query($newsCountSql);
    if ($newsCountResult) {
        $row = $newsCountResult->fetch_assoc();
        echo "<p>Query result: " . $row['count'] . "</p>";
    } else {
        echo "<p style='color: red'>Query failed: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>Exception: " . $e->getMessage() . "</p>";
}

// Test elections query
echo "<h3>Elections Count Query:</h3>";
try {
    $electionCountSql = "SELECT COUNT(*) as count FROM elections WHERE status = 'active'";
    $electionCountResult = $conn->query($electionCountSql);
    if ($electionCountResult) {
        $row = $electionCountResult->fetch_assoc();
        echo "<p>Query result: " . $row['count'] . "</p>";
    } else {
        echo "<p style='color: red'>Query failed: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>Exception: " . $e->getMessage() . "</p>";
}

// Test upcoming events query
echo "<h3>Upcoming Events Query:</h3>";
try {
    $upcomingEventsSql = "SELECT event_id as id, title, date as date, location, 
                     CASE
                         WHEN date > CURDATE() THEN 'Upcoming'
                         WHEN date = CURDATE() THEN 'Today'
                         ELSE 'Ongoing'
                     END as status
                     FROM events 
                     WHERE date >= CURDATE() 
                     ORDER BY date ASC 
                     LIMIT 5";
    $upcomingEventsResult = $conn->query($upcomingEventsSql);
    if ($upcomingEventsResult) {
        $count = $upcomingEventsResult->num_rows;
        echo "<p>Query result count: $count</p>";
        if ($count > 0) {
            echo "<h4>Results:</h4>";
            echo "<pre>";
            while ($row = $upcomingEventsResult->fetch_assoc()) {
                print_r($row);
            }
            echo "</pre>";
        }
    } else {
        echo "<p style='color: red'>Query failed: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>Exception: " . $e->getMessage() . "</p>";
}

// Test recent news query
echo "<h3>Recent News Query:</h3>";
try {
    $recentNewsSql = "SELECT news_id as id, title, DATE_FORMAT(created_at, '%Y-%m-%d') as date, 
                 'System' as author, status
                 FROM news 
                 ORDER BY created_at DESC 
                 LIMIT 3";
    $recentNewsResult = $conn->query($recentNewsSql);
    if ($recentNewsResult) {
        $count = $recentNewsResult->num_rows;
        echo "<p>Query result count: $count</p>";
        if ($count > 0) {
            echo "<h4>Results:</h4>";
            echo "<pre>";
            while ($row = $recentNewsResult->fetch_assoc()) {
                print_r($row);
            }
            echo "</pre>";
        }
    } else {
        echo "<p style='color: red'>Query failed: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>Exception: " . $e->getMessage() . "</p>";
}

$conn->close();
?> 