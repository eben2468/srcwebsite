<?php
// Script to update Senate President responsibilities
require_once 'db_config.php';

echo "<h1>Updating Senate President Responsibilities</h1>";
echo "<pre>";

// Check if the portfolios table exists
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'portfolios'");
if (mysqli_num_rows($checkTable) == 0) {
    echo "Error: Table 'portfolios' does not exist.\n";
    exit;
}

// New responsibilities based on the provided text
$newResponsibilities = [
    'Lead the chief legislative authority of the SRC empowered to enact laws within Valley View University regulations',
    'Serve the best interest of the Council and the Institution through legislative action',
    'Exercise power to subpoena Executive members to answer questions before the Senate',
    'Request written reports from Executive members for Senate review and oversight'
];

// Update the Senate President portfolio
$sql = "SELECT portfolio_id, responsibilities FROM portfolios WHERE title = 'Senate President'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "Error: " . mysqli_error($conn) . "\n";
    exit;
}

if (mysqli_num_rows($result) == 0) {
    echo "Error: Senate President portfolio not found in the database.\n";
    exit;
}

$row = mysqli_fetch_assoc($result);
$portfolioId = $row['portfolio_id'];

// Encode the new responsibilities as JSON
$encodedResponsibilities = json_encode($newResponsibilities);

// Update the database
$updateSql = "UPDATE portfolios SET responsibilities = ? WHERE portfolio_id = ?";
$stmt = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($stmt, "si", $encodedResponsibilities, $portfolioId);
$updateResult = mysqli_stmt_execute($stmt);

if ($updateResult) {
    echo "SUCCESS: Senate President responsibilities updated successfully!\n\n";
    echo "New responsibilities:\n";
    foreach ($newResponsibilities as $index => $responsibility) {
        echo ($index + 1) . ". " . $responsibility . "\n";
    }
} else {
    echo "ERROR: Failed to update Senate President responsibilities. " . mysqli_error($conn) . "\n";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo "</pre>";
echo "<p><a href='pages_php/portfolio.php'>View Portfolios</a></p>";
?> 