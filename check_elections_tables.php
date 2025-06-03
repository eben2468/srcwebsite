<?php
require_once 'db_config.php';

// Just display the structure of the election_candidates table
echo "ELECTION_CANDIDATES TABLE STRUCTURE:\n";
$result = $conn->query("SHOW COLUMNS FROM election_candidates");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Field: " . $row['Field'] . ", Type: " . $row['Type'] . ", Null: " . $row['Null'] . ", Key: " . $row['Key'] . "\n";
    }
} else {
    echo "Error showing columns: " . $conn->error . "\n";
}
?> 