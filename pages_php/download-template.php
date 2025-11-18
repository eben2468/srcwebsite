<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';

// Require login for this page
requireLogin();

// Check if user has admin privileges (super admin, admin, or finance can access)
$hasAdminPrivileges = hasAdminPrivileges();
$isFinance = isFinance();

if (!$hasAdminPrivileges && !$isFinance) {
    $_SESSION['error'] = "Access denied. Only administrators, finance staff, and super administrators can download the template.";
    header("Location: users.php");
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="user_import_template.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create CSV content
$csvContent = [
    ['Fullname', 'Username', 'Role', 'Email', 'Phone', 'Password'],
    ['John Doe', 'john', 'member', 'john.doe@example.com', '0241234567', 'member123'],
    ['Jane Smith', 'jane', 'student', 'jane.smith@example.com', '0241234568', 'student123'],
    ['Michael Johnson', '', 'member', 'michael.johnson@example.com', '0241234569', 'member123'],
    ['Sarah Wilson', '', 'student', 'sarah.wilson@example.com', '0241234570', 'student123']
];

// Output CSV
$output = fopen('php://output', 'w');

foreach ($csvContent as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
