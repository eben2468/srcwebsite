<?php
// User handler file - Processes form submissions for user actions
require_once '../db_config.php';
require_once '../functions.php';
require_once '../auth_functions.php';
require_once '../settings_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error'] = "You do not have permission to perform this action.";
    header("Location: login.php");
    exit();
}

// Get the action
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Process based on action
switch ($action) {
    case 'create':
        handleCreateUser();
        break;
    
    case 'update':
        handleUpdateUser();
        break;
    
    case 'delete':
        handleDeleteUser();
        break;
    
    case 'reset_password':
        handleResetPassword();
        break;
    
    case 'change_status':
        handleChangeStatus();
        break;
    
    default:
        $_SESSION['error'] = "Invalid action specified.";
        header("Location: users.php");
        exit();
}

/**
 * Handle user creation
 */
function handleCreateUser() {
    // Get form data
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: users.php");
        exit();
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: users.php");
        exit();
    }
    
    // Validate password match
    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: users.php");
        exit();
    }
    
    // Validate password length
    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
        header("Location: users.php");
        exit();
    }
    
    // Check if username already exists
    $checkUsernameSql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
    $result = fetchOne($checkUsernameSql, [$username]);
    
    if ($result && $result['count'] > 0) {
        $_SESSION['error'] = "Username already exists. Please choose a different username.";
        header("Location: users.php");
        exit();
    }
    
    // Check if email already exists
    $checkEmailSql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
    $result = fetchOne($checkEmailSql, [$email]);
    
    if ($result && $result['count'] > 0) {
        $_SESSION['error'] = "Email already exists. Please use a different email address.";
        header("Location: users.php");
        exit();
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $insertSql = "INSERT INTO users (username, password, email, first_name, last_name, role, status, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $params = [$username, $hashedPassword, $email, $firstName, $lastName, $role, $status];
    
    if (executeQuery($insertSql, $params)) {
        $_SESSION['success'] = "User created successfully.";
    } else {
        $_SESSION['error'] = "Failed to create user. Please try again.";
    }
    
    header("Location: users.php");
    exit();
}

/**
 * Handle user update
 */
function handleUpdateUser() {
    // Get form data
    $userId = $_POST['user_id'];
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    // Validate required fields
    if (empty($userId) || empty($firstName) || empty($lastName) || empty($email)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: users.php");
        exit();
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: users.php");
        exit();
    }
    
    // Check if email already exists (excluding current user)
    $checkEmailSql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND user_id != ?";
    $result = fetchOne($checkEmailSql, [$email, $userId]);
    
    if ($result && $result['count'] > 0) {
        $_SESSION['error'] = "Email already exists. Please use a different email address.";
        header("Location: users.php");
        exit();
    }
    
    // Update user
    $updateSql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, status = ?, updated_at = NOW() WHERE user_id = ?";
    $params = [$firstName, $lastName, $email, $role, $status, $userId];
    
    if (executeQuery($updateSql, $params)) {
        $_SESSION['success'] = "User updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update user. Please try again.";
    }
    
    header("Location: users.php");
    exit();
}

/**
 * Handle user deletion
 */
function handleDeleteUser() {
    $userId = isset($_POST['user_id']) ? $_POST['user_id'] : (isset($_GET['id']) ? $_GET['id'] : 0);
    
    if (empty($userId)) {
        $_SESSION['error'] = "Invalid user ID.";
        header("Location: users.php");
        exit();
    }
    
    // Check if user exists
    $checkUserSql = "SELECT * FROM users WHERE user_id = ?";
    $user = fetchOne($checkUserSql, [$userId]);
    
    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: users.php");
        exit();
    }
    
    // Check if trying to delete self
    $currentUser = getCurrentUser();
    if ($currentUser['user_id'] == $userId) {
        $_SESSION['error'] = "You cannot delete your own account.";
        header("Location: users.php");
        exit();
    }
    
    // Delete user
    $deleteSql = "DELETE FROM users WHERE user_id = ?";
    
    if (executeQuery($deleteSql, [$userId])) {
        $_SESSION['success'] = "User deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete user. Please try again.";
    }
    
    header("Location: users.php");
    exit();
}

/**
 * Handle password reset
 */
function handleResetPassword() {
    // Get form data
    $userId = $_POST['user_id'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate required fields
    if (empty($userId) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: users.php");
        exit();
    }
    
    // Validate password match
    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: users.php");
        exit();
    }
    
    // Validate password length
    if (strlen($newPassword) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
        header("Location: users.php");
        exit();
    }
    
    // Hash password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $updateSql = "UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?";
    
    if (executeQuery($updateSql, [$hashedPassword, $userId])) {
        $_SESSION['success'] = "Password reset successfully.";
    } else {
        $_SESSION['error'] = "Failed to reset password. Please try again.";
    }
    
    header("Location: users.php");
    exit();
}

/**
 * Handle status change
 */
function handleChangeStatus() {
    // Get form data
    $userId = isset($_POST['user_id']) ? $_POST['user_id'] : (isset($_GET['id']) ? $_GET['id'] : 0);
    $status = isset($_POST['status']) ? $_POST['status'] : (isset($_GET['status']) ? $_GET['status'] : '');
    
    if (empty($userId) || empty($status)) {
        $_SESSION['error'] = "Invalid request.";
        header("Location: users.php");
        exit();
    }
    
    // Check if user exists
    $checkUserSql = "SELECT * FROM users WHERE user_id = ?";
    $user = fetchOne($checkUserSql, [$userId]);
    
    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: users.php");
        exit();
    }
    
    // Check if trying to change own status
    $currentUser = getCurrentUser();
    if ($currentUser['user_id'] == $userId) {
        $_SESSION['error'] = "You cannot change your own status.";
        header("Location: users.php");
        exit();
    }
    
    // Update status
    $updateSql = "UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?";
    
    if (executeQuery($updateSql, [$status, $userId])) {
        $_SESSION['success'] = "User status updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update user status. Please try again.";
    }
    
    header("Location: users.php");
    exit();
}
?> 