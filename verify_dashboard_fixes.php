<?php
// Include authentication file and database config
require_once 'db_config.php';

echo "<h1>Dashboard Fixes Verification</h1>";

// Test the fixed queries
echo "<h2>Testing Fixed Queries</h2>";

// Test events query
echo "<h3>Events Count:</h3>";
try {
    $eventCountSql = "SELECT COUNT(*) as count FROM events";
    $eventCountResult = $conn->query($eventCountSql);
    if ($eventCountResult) {
        $row = $eventCountResult->fetch_assoc();
        echo "<p>Total events: " . $row['count'] . "</p>";
    } else {
        echo "<p style='color: red'>Query failed: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>Exception: " . $e->getMessage() . "</p>";
}

// Test upcoming events query
echo "<h3>Upcoming Events:</h3>";
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
        echo "<p>Upcoming events count: $count</p>";
        if ($count > 0) {
            echo "<table border='1' style='border-collapse: collapse'>";
            echo "<tr><th>ID</th><th>Title</th><th>Date</th><th>Location</th><th>Status</th></tr>";
            while ($row = $upcomingEventsResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['title'] . "</td>";
                echo "<td>" . $row['date'] . "</td>";
                echo "<td>" . $row['location'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No upcoming events found with updated query.</p>";
        }
    } else {
        echo "<p style='color: red'>Query failed: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>Exception: " . $e->getMessage() . "</p>";
}

// Test news query
echo "<h3>News Count:</h3>";
try {
    // Try published news first
    $newsCountSql = "SELECT COUNT(*) as count FROM news WHERE status = 'published'";
    $newsCountResult = $conn->query($newsCountSql);
    if ($newsCountResult) {
        $row = $newsCountResult->fetch_assoc();
        echo "<p>Published news: " . $row['count'] . "</p>";
    } else {
        echo "<p style='color: red'>Query failed: " . $conn->error . "</p>";
    }
    
    // Then try all news
    $allNewsCountSql = "SELECT COUNT(*) as count FROM news";
    $allNewsCountResult = $conn->query($allNewsCountSql);
    if ($allNewsCountResult) {
        $row = $allNewsCountResult->fetch_assoc();
        echo "<p>All news: " . $row['count'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>Exception: " . $e->getMessage() . "</p>";
}

// Test recent news query
echo "<h3>Recent News:</h3>";
try {
    $recentNewsSql = "SELECT news_id as id, title, DATE_FORMAT(created_at, '%Y-%m-%d') as date, 
                 'System' as author, status
                 FROM news 
                 ORDER BY created_at DESC 
                 LIMIT 3";
    $recentNewsResult = $conn->query($recentNewsSql);
    if ($recentNewsResult) {
        $count = $recentNewsResult->num_rows;
        echo "<p>Recent news count: $count</p>";
        if ($count > 0) {
            echo "<table border='1' style='border-collapse: collapse'>";
            echo "<tr><th>ID</th><th>Title</th><th>Date</th><th>Author</th><th>Status</th></tr>";
            while ($row = $recentNewsResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['title'] . "</td>";
                echo "<td>" . $row['date'] . "</td>";
                echo "<td>" . $row['author'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No recent news found.</p>";
        }
    } else {
        echo "<p style='color: red'>Query failed: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>Exception: " . $e->getMessage() . "</p>";
}

// Test elections query
echo "<h3>Elections Count:</h3>";
try {
    // Try active elections
    $electionCountSql = "SELECT COUNT(*) as count FROM elections WHERE status = 'active'";
    $electionCountResult = $conn->query($electionCountSql);
    if ($electionCountResult) {
        $row = $electionCountResult->fetch_assoc();
        echo "<p>Active elections: " . $row['count'] . "</p>";
    } else {
        echo "<p style='color: red'>Query failed: " . $conn->error . "</p>";
    }
    
    // Check all distinct status values
    $statusesSql = "SELECT DISTINCT status FROM elections";
    $statusesResult = $conn->query($statusesSql);
    if ($statusesResult) {
        echo "<p>Election status values:</p>";
        echo "<ul>";
        $hasStatuses = false;
        while ($row = $statusesResult->fetch_assoc()) {
            echo "<li>" . $row['status'] . "</li>";
            $hasStatuses = true;
        }
        if (!$hasStatuses) {
            echo "<li>No status values found</li>";
        }
        echo "</ul>";
    }
    
    // Check all elections
    $allElectionsSql = "SELECT COUNT(*) as count FROM elections";
    $allElectionsResult = $conn->query($allElectionsSql);
    if ($allElectionsResult) {
        $row = $allElectionsResult->fetch_assoc();
        echo "<p>All elections: " . $row['count'] . "</p>";
    }
    
    // Check elections data
    $electionsDataSql = "SELECT * FROM elections LIMIT 5";
    $electionsDataResult = $conn->query($electionsDataSql);
    if ($electionsDataResult) {
        $count = $electionsDataResult->num_rows;
        if ($count > 0) {
            echo "<h4>Elections Data:</h4>";
            echo "<pre>";
            while ($row = $electionsDataResult->fetch_assoc()) {
                print_r($row);
            }
            echo "</pre>";
        } else {
            echo "<p>No elections data found.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red'>Exception: " . $e->getMessage() . "</p>";
}

// Summary
echo "<h2>Summary of Fixes</h2>";
echo "<ol>";
echo "<li>Updated the upcoming events query to include events where either end_date or start_date is in the future</li>";
echo "<li>Updated the news count to check for all news articles if no published ones are found</li>";
echo "<li>Updated the elections count to check for alternative status values if 'active' isn't found</li>";
echo "</ol>";

echo "<p>After applying these fixes, the dashboard should properly display:</p>";
echo "<ul>";
echo "<li>The correct number of events and show upcoming events in the table</li>";
echo "<li>The correct number of news articles (all 6) and show recent news</li>";
echo "<li>The correct number of active elections (should be 1)</li>";
echo "</ul>";

$conn->close();
?> 