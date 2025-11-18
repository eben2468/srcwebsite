<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and has admin interface access
if (!isLoggedIn() || !shouldUseAdminInterface()) {
    $_SESSION['error'] = "You don't have permission to perform this action.";
    header("Location: portfolio.php");
    exit();
}

// Check if action is provided
if (!isset($_POST['action'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: portfolio.php");
    exit();
}

$action = $_POST['action'];

// Handle Create Portfolio
if ($action === 'create') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $responsibilitiesText = trim($_POST['responsibilities'] ?? '');
    $qualificationsText = trim($_POST['qualifications'] ?? '');
    
    // Validate required fields
    if (empty($title) || empty($name) || empty($email) || empty($description) || empty($responsibilitiesText)) {
        $_SESSION['error'] = "All required fields must be filled.";
        header("Location: portfolio.php");
        exit();
    }
    
    // Process responsibilities and qualifications into arrays
    $responsibilities = array_filter(array_map('trim', explode("\n", $responsibilitiesText)));
    $qualifications = array_filter(array_map('trim', explode("\n", $qualificationsText)));
    
    // Convert arrays to JSON
    $responsibilitiesJson = json_encode($responsibilities);
    $qualificationsJson = json_encode($qualifications);
    
    // Handle photo upload
    $photoFileName = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../images/avatars/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Get file information
        $fileName = basename($_FILES['photo']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Validate file type
        if (in_array($fileExt, $allowedExtensions)) {
            // Generate unique filename
            $newFileName = uniqid() . '.' . $fileExt;
            $targetFilePath = $uploadDir . $newFileName;
            
            // Upload file
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFilePath)) {
                $photoFileName = $newFileName;
            } else {
                $_SESSION['error'] = "Failed to upload photo.";
                header("Location: portfolio.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            header("Location: portfolio.php");
            exit();
        }
    }
    
    // Insert portfolio into database
    $sql = "INSERT INTO portfolios (title, name, email, phone, photo, description, responsibilities, qualifications) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $result = insert($sql, [
        $title,
        $name,
        $email,
        $phone,
        $photoFileName,
        $description,
        $responsibilitiesJson,
        $qualificationsJson
    ]);
    
    if ($result) {
        $_SESSION['success'] = "Portfolio created successfully!";
        header("Location: portfolio.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to create portfolio. Please try again.";
        header("Location: portfolio.php");
        exit();
    }
}

// Handle Update Portfolio
if ($action === 'update') {
    // Get portfolio ID
    $portfolioId = intval($_POST['portfolio_id'] ?? 0);
    
    // Check if portfolio exists
    $portfolio = fetchOne("SELECT * FROM portfolios WHERE portfolio_id = ?", [$portfolioId]);
    
    if (!$portfolio) {
        $_SESSION['error'] = "Portfolio not found.";
        header("Location: portfolio.php");
        exit();
    }
    
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $responsibilitiesText = trim($_POST['responsibilities'] ?? '');
    $qualificationsText = trim($_POST['qualifications'] ?? '');
    
    // Validate required fields
    if (empty($title) || empty($name) || empty($email) || empty($description) || empty($responsibilitiesText)) {
        $_SESSION['error'] = "All required fields must be filled.";
        header("Location: portfolio_edit.php?id=$portfolioId");
        exit();
    }
    
    // Process responsibilities and qualifications into arrays
    $responsibilities = array_filter(array_map('trim', explode("\n", $responsibilitiesText)));
    $qualifications = array_filter(array_map('trim', explode("\n", $qualificationsText)));
    
    // Convert arrays to JSON
    $responsibilitiesJson = json_encode($responsibilities);
    $qualificationsJson = json_encode($qualifications);
    
    // Handle photo upload
    $photoFileName = $portfolio['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../images/avatars/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Get file information
        $fileName = basename($_FILES['photo']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Validate file type
        if (in_array($fileExt, $allowedExtensions)) {
            // Generate unique filename
            $newFileName = uniqid() . '.' . $fileExt;
            $targetFilePath = $uploadDir . $newFileName;
            
            // Upload file
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFilePath)) {
                // Delete old photo if it exists
                if (!empty($photoFileName) && file_exists($uploadDir . $photoFileName)) {
                    unlink($uploadDir . $photoFileName);
                }
                
                $photoFileName = $newFileName;
            } else {
                $_SESSION['error'] = "Failed to upload photo.";
                header("Location: portfolio_edit.php?id=$portfolioId");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            header("Location: portfolio_edit.php?id=$portfolioId");
            exit();
        }
    }
    
    // Update portfolio in database
    $sql = "UPDATE portfolios
            SET title = ?, name = ?, email = ?, phone = ?, photo = ?, description = ?, responsibilities = ?, qualifications = ?
            WHERE portfolio_id = ?";

    try {
        // Debug logging
        error_log("Portfolio Update Debug - Portfolio ID: $portfolioId");
        error_log("Portfolio Update Debug - Title: $title");
        error_log("Portfolio Update Debug - Name: $name");
        error_log("Portfolio Update Debug - Email: $email");
        error_log("Portfolio Update Debug - Phone: $phone");
        error_log("Portfolio Update Debug - Photo: $photoFileName");
        error_log("Portfolio Update Debug - Description length: " . strlen($description));
        error_log("Portfolio Update Debug - Responsibilities JSON: $responsibilitiesJson");
        error_log("Portfolio Update Debug - Qualifications JSON: $qualificationsJson");

        $result = update($sql, [
            $title,
            $name,
            $email,
            $phone,
            $photoFileName,
            $description,
            $responsibilitiesJson,
            $qualificationsJson,
            $portfolioId
        ], 'ssssssssi'); // Specify types: 8 strings and 1 integer

        error_log("Portfolio Update Debug - Update result: " . var_export($result, true));

        if ($result !== false && $result > 0) {
            $_SESSION['success'] = "Portfolio updated successfully!";
            header("Location: portfolio-detail.php?id=$portfolioId");
            exit();
        } else {
            // Check if portfolio exists
            $checkPortfolio = fetchOne("SELECT portfolio_id FROM portfolios WHERE portfolio_id = ?", [$portfolioId]);
            if (!$checkPortfolio) {
                $_SESSION['error'] = "Portfolio not found. It may have been deleted.";
                error_log("Portfolio Update Debug - Portfolio not found: $portfolioId");
            } else {
                $_SESSION['error'] = "No changes were made to the portfolio. Please check your data and try again.";
                error_log("Portfolio Update Debug - No changes made, result: " . var_export($result, true));
            }
            header("Location: portfolio_edit.php?id=$portfolioId");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        error_log("Portfolio Update Debug - Exception: " . $e->getMessage());
        header("Location: portfolio_edit.php?id=$portfolioId");
        exit();
    }
}

// Handle Add Initiative
if ($action === 'add_initiative') {
    // Get portfolio ID
    $portfolioId = intval($_POST['portfolio_id'] ?? 0);
    
    // Check if portfolio exists
    $portfolio = fetchOne("SELECT * FROM portfolios WHERE portfolio_id = ?", [$portfolioId]);
    
    if (!$portfolio) {
        $_SESSION['error'] = "Portfolio not found.";
        header("Location: portfolio.php");
        exit();
    }
    
    // Get form data
    $title = trim($_POST['initiative_title'] ?? '');
    $status = trim($_POST['initiative_status'] ?? '');
    $description = trim($_POST['initiative_description'] ?? '');
    
    // Validate required fields
    if (empty($title) || empty($status) || empty($description)) {
        $_SESSION['error'] = "All initiative fields are required.";
        header("Location: portfolio-detail.php?id=$portfolioId");
        exit();
    }
    
    // Insert initiative into database
    $sql = "INSERT INTO portfolio_initiatives (portfolio_id, title, status, description) 
            VALUES (?, ?, ?, ?)";
    
    $result = insert($sql, [
        $portfolioId,
        $title,
        $status,
        $description
    ]);
    
    if ($result) {
        $_SESSION['success'] = "Initiative added successfully!";
        header("Location: portfolio-detail.php?id=$portfolioId");
        exit();
    } else {
        $_SESSION['error'] = "Failed to add initiative. Please try again.";
        header("Location: portfolio-detail.php?id=$portfolioId");
        exit();
    }
}

// Handle Delete Initiative
if ($action === 'delete_initiative') {
    // Get initiative ID and portfolio ID
    $initiativeId = intval($_POST['initiative_id'] ?? 0);
    $portfolioId = intval($_POST['portfolio_id'] ?? 0);
    
    // Delete initiative from database
    $sql = "DELETE FROM portfolio_initiatives WHERE initiative_id = ? AND portfolio_id = ?";
    
    $result = delete($sql, [$initiativeId, $portfolioId]);
    
    if ($result) {
        $_SESSION['success'] = "Initiative deleted successfully!";
        header("Location: portfolio-detail.php?id=$portfolioId");
        exit();
    } else {
        $_SESSION['error'] = "Failed to delete initiative. Please try again.";
        header("Location: portfolio-detail.php?id=$portfolioId");
        exit();
    }
}

// If none of the actions match
$_SESSION['error'] = "Invalid action.";
header("Location: portfolio.php");
exit();
?> 
