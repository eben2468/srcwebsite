<?php
require_once 'db_config.php';

echo "<h1>Dashboard Card Counts Test</h1>";

// Function to safely count records
function safeCountTable($tableName, $whereClause = '') {
    global $conn;
    $count = 0;
    
    try {
        $sql = "SELECT COUNT(*) as count FROM " . $tableName;
        if (!empty($whereClause)) {
            $sql .= " WHERE " . $whereClause;
        }
        
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $count = $row['count'];
        }
    } catch (Exception $e) {
        echo "<p>Error counting {$tableName}: " . $e->getMessage() . "</p>";
    }
    
    return $count;
}

// Test each table and display the count
$tables = [
    'Events' => ['tableName' => 'events', 'whereClause' => ''],
    'News Articles' => ['tableName' => 'news', 'whereClause' => "status = 'published'"],
    'Documents' => ['tableName' => 'documents', 'whereClause' => "status = 'active'"],
    'Active Elections' => ['tableName' => 'elections', 'whereClause' => "status = 'active'"],
    'Portfolios' => ['tableName' => 'portfolios', 'whereClause' => ''],
    'Minutes' => ['tableName' => 'minutes', 'whereClause' => ''],
    'Reports' => ['tableName' => 'reports', 'whereClause' => ''],
    'Departments' => ['tableName' => 'departments', 'whereClause' => ''],
    'Feedback' => ['tableName' => 'feedback', 'whereClause' => '']
];

echo "<table border='1' style='border-collapse: collapse; width: 50%'>";
echo "<tr><th>Card Name</th><th>Count</th><th>Status</th></tr>";

foreach ($tables as $cardName => $tableInfo) {
    $count = safeCountTable($tableInfo['tableName'], $tableInfo['whereClause']);
    $tableExists = true;
    
    try {
        $checkTableSql = "SHOW TABLES LIKE '{$tableInfo['tableName']}'";
        $tableResult = $conn->query($checkTableSql);
        $tableExists = $tableResult && $tableResult->num_rows > 0;
    } catch (Exception $e) {
        $tableExists = false;
    }
    
    $status = $tableExists ? 'Table Exists' : 'Table Missing';
    $statusColor = $tableExists ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$cardName}</td>";
    echo "<td>{$count}</td>";
    echo "<td style='color: {$statusColor}'>{$status}</td>";
    echo "</tr>";
}

echo "</table>";

// Check specific tables structure
$tablesToCheck = ['events', 'news', 'documents', 'elections', 'minutes'];

foreach ($tablesToCheck as $table) {
    echo "<h2>Table: {$table}</h2>";
    
    try {
        $checkTableSql = "SHOW TABLES LIKE '{$table}'";
        $tableResult = $conn->query($checkTableSql);
        
        if ($tableResult && $tableResult->num_rows > 0) {
            echo "<p style='color: green'>Table exists</p>";
            
            // Show table structure
            $describeTableSql = "DESCRIBE {$table}";
            $describeResult = $conn->query($describeTableSql);
            
            if ($describeResult) {
                echo "<table border='1' style='border-collapse: collapse'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                
                while ($row = $describeResult->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$row['Field']}</td>";
                    echo "<td>{$row['Type']}</td>";
                    echo "<td>{$row['Null']}</td>";
                    echo "<td>{$row['Key']}</td>";
                    echo "<td>{$row['Default']}</td>";
                    echo "<td>{$row['Extra']}</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                // Show sample data
                $sampleDataSql = "SELECT * FROM {$table} LIMIT 3";
                $sampleResult = $conn->query($sampleDataSql);
                
                if ($sampleResult && $sampleResult->num_rows > 0) {
                    echo "<h3>Sample Data:</h3>";
                    echo "<pre>";
                    while ($row = $sampleResult->fetch_assoc()) {
                        print_r($row);
                    }
                    echo "</pre>";
                } else {
                    echo "<p>No data in table</p>";
                }
            } else {
                echo "<p style='color: red'>Error describing table: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: red'>Table does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red'>Error checking table: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

$conn->close();
?> 