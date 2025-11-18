<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_functions.php';

// Require login for this page
requireLogin();

// Check if user has admin privileges (super admin or admin)
$hasAdminPrivileges = hasAdminPrivileges();

if (!$hasAdminPrivileges) {
    $_SESSION['error'] = "Access denied. Only administrators and super administrators can perform bulk operations.";
    header("Location: users.php");
    exit();
}

// Get the action
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'import':
        handleImport();
        break;
    
    case 'export':
        handleExport();
        break;
    
    default:
        $_SESSION['error'] = "Invalid action specified.";
        header("Location: bulk-users.php");
        exit();
}

function handleImport() {
    global $conn;
    
    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Please select a valid CSV file.";
        header("Location: bulk-users.php");
        exit();
    }
    
    $file = $_FILES['csv_file'];
    
    // Validate file type
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileExtension !== 'csv') {
        $_SESSION['error'] = "Only CSV files are allowed.";
        header("Location: bulk-users.php");
        exit();
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "File size must be less than 5MB.";
        header("Location: bulk-users.php");
        exit();
    }
    
    // Read CSV file
    $csvData = [];
    if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
        $header = fgetcsv($handle); // Skip header row
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 6) { // Ensure we have all required columns
                $csvData[] = $data;
            }
        }
        fclose($handle);
    } else {
        $_SESSION['error'] = "Unable to read the CSV file.";
        header("Location: bulk-users.php");
        exit();
    }
    
    if (empty($csvData)) {
        $_SESSION['error'] = "The CSV file is empty or has invalid format.";
        header("Location: bulk-users.php");
        exit();
    }
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($csvData as $index => $row) {
        $rowNumber = $index + 2; // +2 because we start from row 2 (after header)
        
        $fullname = trim($row[0]);
        $username = trim($row[1]);
        $role = strtolower(trim($row[2]));
        $email = trim($row[3]);
        $phone = trim($row[4]);
        
        // Validate required fields
        if (empty($fullname) || empty($email) || empty($phone)) {
            $errors[] = "Row $rowNumber: Missing required fields (fullname, email, or phone).";
            $errorCount++;
            continue;
        }
        
        // Validate role
        if (!in_array($role, ['admin', 'member', 'finance', 'student'])) {
            $errors[] = "Row $rowNumber: Invalid role '$role'. Must be 'admin', 'member', 'finance', or 'student'.";
            $errorCount++;
            continue;
        }
        
        // Generate username if empty
        if (empty($username)) {
            $nameParts = explode(' ', $fullname);
            $username = strtolower($nameParts[0]);
            
            // Check if username exists and make it unique
            $originalUsername = $username;
            $counter = 1;
            while (usernameExists($username)) {
                $username = $originalUsername . $counter;
                $counter++;
            }
        } else {
            // Check if provided username exists
            if (usernameExists($username)) {
                $errors[] = "Row $rowNumber: Username '$username' already exists.";
                $errorCount++;
                continue;
            }
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row $rowNumber: Invalid email format.";
            $errorCount++;
            continue;
        }
        
        // Check if email exists
        if (emailExists($email)) {
            $errors[] = "Row $rowNumber: Email '$email' already exists.";
            $errorCount++;
            continue;
        }
        
        // Set password based on role
        switch ($role) {
            case 'admin':
                $password = 'admin123';
                break;
            case 'member':
                $password = 'member123';
                break;
            case 'finance':
                $password = 'finance123';
                break;
            case 'student':
                $password = 'student123';
                break;
            default:
                $password = 'user123';
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Split fullname into first and last name
        $nameParts = explode(' ', $fullname, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        
        // Insert user
        $sql = "INSERT INTO users (username, password, email, first_name, last_name, role, phone, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        
        try {
            if (executeQuery($sql, [$username, $hashedPassword, $email, $firstName, $lastName, $role, $phone])) {
                $successCount++;
            } else {
                $errors[] = "Row $rowNumber: Failed to insert user '$fullname'.";
                $errorCount++;
            }
        } catch (Exception $e) {
            $errors[] = "Row $rowNumber: Database error - " . $e->getMessage();
            $errorCount++;
        }
    }
    
    // Set success/error messages
    if ($successCount > 0) {
        $_SESSION['success'] = "Successfully imported $successCount users.";
    }
    
    if ($errorCount > 0) {
        $errorMessage = "Failed to import $errorCount users. Errors:\n" . implode("\n", array_slice($errors, 0, 10));
        if (count($errors) > 10) {
            $errorMessage .= "\n... and " . (count($errors) - 10) . " more errors.";
        }
        $_SESSION['error'] = $errorMessage;
    }
    
    header("Location: bulk-users.php");
    exit();
}

function handleExport() {
    $includeAdmins = isset($_POST['include_admins']) && $_POST['include_admins'] == '1';
    $includeMembers = isset($_POST['include_members']) && $_POST['include_members'] == '1';
    $includeFinance = isset($_POST['include_finance']) && $_POST['include_finance'] == '1';
    $includeStudents = isset($_POST['include_students']) && $_POST['include_students'] == '1';

    if (!$includeAdmins && !$includeMembers && !$includeFinance && !$includeStudents) {
        $_SESSION['error'] = "Please select at least one user type to export.";
        header("Location: bulk-users.php");
        exit();
    }

    $conditions = [];
    $roles = [];

    if ($includeAdmins) $roles[] = 'admin';
    if ($includeMembers) $roles[] = 'member';
    if ($includeFinance) $roles[] = 'finance';
    if ($includeStudents) $roles[] = 'student';

    if (!empty($roles)) {
        $placeholders = str_repeat('?,', count($roles) - 1) . '?';
        $conditions[] = "role IN ($placeholders)";
    }
    
    $whereClause = implode(' OR ', $conditions);

    $sql = "SELECT CONCAT(first_name, ' ', last_name) as fullname, username, role, email, phone,
                   DATE_FORMAT(created_at, '%Y-%m-%d') as created_date
            FROM users
            WHERE $whereClause
            ORDER BY role, first_name, last_name";

    try {
        $users = fetchAll($sql, $roles);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output CSV
        $output = fopen('php://output', 'w');
        
        // Write header
        fputcsv($output, ['Fullname', 'Username', 'Role', 'Email', 'Phone', 'Created Date']);
        
        // Write data
        foreach ($users as $user) {
            $roleDisplay = $user['role'] === 'super_admin' ? 'Super Admin' : ucfirst($user['role']);
            fputcsv($output, [
                $user['fullname'],
                $user['username'],
                $roleDisplay,
                $user['email'],
                $user['phone'],
                $user['created_date']
            ]);
        }
        
        fclose($output);
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to export users: " . $e->getMessage();
        header("Location: bulk-users.php");
        exit();
    }
}

function usernameExists($username) {
    $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
    $result = fetchOne($sql, [$username]);
    return $result && $result['count'] > 0;
}

function emailExists($email) {
    $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
    $result = fetchOne($sql, [$email]);
    return $result && $result['count'] > 0;
}
?>
