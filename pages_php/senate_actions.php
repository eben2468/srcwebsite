<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Note: execute() function is already defined in includes/db_functions.php

// Check if user is logged in and has admin interface access
if (!isLoggedIn() || !shouldUseAdminInterface()) {
    $_SESSION['error'] = "You don't have permission to perform this action.";
    header("Location: senate.php");
    exit();
}

// Get the action from POST
$action = $_POST['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'add_member':
        addSenateMember();
        break;
    case 'update_info':
        updateSenateInfo();
        break;
    case 'add_session':
        addSenateSession();
        break;
    case 'add_document':
        addSenateDocument();
        break;
    case 'delete_member':
        deleteSenateMember();
        break;
    case 'edit_member':
        editSenateMember();
        break;
    case 'edit_session':
        editSenateSession();
        break;
    case 'delete_session':
        deleteSenateSession();
        break;
    case 'delete_document':
        deleteDocument();
        break;
    default:
        $_SESSION['error'] = "Invalid action.";
        header("Location: senate.php");
        exit();
}

/**
 * Add a new Senate member
 */
function addSenateMember() {
    // Get form data
    $name = $_POST['name'] ?? '';
    $position = $_POST['position'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $faculty = $_POST['faculty'] ?? '';
    $term = $_POST['term'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $responsibilities = $_POST['responsibilities'] ?? '';
    
    // Validate required fields
    if (empty($name) || empty($position)) {
        $_SESSION['error'] = "Name and position are required fields.";
        header("Location: senate.php");
        exit();
    }
    
    // Process responsibilities (convert from textarea to array)
    $responsibilitiesArray = [];
    if (!empty($responsibilities)) {
        $responsibilitiesArray = array_filter(explode("\n", $responsibilities), 'trim');
    }
    $responsibilitiesJson = json_encode($responsibilitiesArray);
    
    // Handle photo upload
    $photoName = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        // Validate file type and size
        if (!in_array($_FILES['photo']['type'], $allowedTypes)) {
            $_SESSION['error'] = "Only JPG and PNG images are allowed.";
            header("Location: senate.php");
            exit();
        }
        
        if ($_FILES['photo']['size'] > $maxSize) {
            $_SESSION['error'] = "File size exceeds the 2MB limit.";
            header("Location: senate.php");
            exit();
        }
        
        // Generate unique filename
        $photoName = 'senate_' . time() . '_' . $_FILES['photo']['name'];
        $uploadPath = '../uploads/senate/' . $photoName;
        
        // Create directory if it doesn't exist
        if (!is_dir('../uploads/senate/')) {
            mkdir('../uploads/senate/', 0777, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            $_SESSION['error'] = "Failed to upload photo.";
            header("Location: senate.php");
            exit();
        }
    }
    
    try {
        // Check if senate_members table exists
        $checkTableSql = "SHOW TABLES LIKE 'senate_members'";
        $tableExists = count(fetchAll($checkTableSql)) > 0;
        
        if (!$tableExists) {
            // Create the table if it doesn't exist
            $createTableSql = "CREATE TABLE senate_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                position VARCHAR(100) NOT NULL,
                email VARCHAR(255),
                phone VARCHAR(50),
                faculty VARCHAR(100),
                term VARCHAR(100),
                bio TEXT,
                responsibilities JSON,
                photo VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            execute($createTableSql);
        }
        
        // Check if we're adding a Senate President
        if ($position === 'Senate President') {
            // Check if there's already a Senate President in the portfolios table
            $existingSql = "SELECT portfolio_id FROM portfolios WHERE title = 'Senate President'";
            $existingResult = fetchOne($existingSql);
            
            if ($existingResult) {
                // Update existing Senate President in portfolios
                $sql = "UPDATE portfolios SET 
                        name = ?, 
                        email = ?, 
                        phone = ?, 
                        department = ?, 
                        term = ?, 
                        bio = ?, 
                        responsibilities = ?";
                
                $params = [$name, $email, $phone, $faculty, $term, $bio, $responsibilitiesJson];
                
                // Add photo to query if provided
                if (!empty($photoName)) {
                    $sql .= ", photo = ?";
                    $params[] = $photoName;
                }
                
                $sql .= " WHERE title = 'Senate President'";
                
                execute($sql, $params);
                
                $_SESSION['success'] = "Senate President updated successfully.";
            } else {
                // Check if portfolios table exists
                $checkPortfoliosTableSql = "SHOW TABLES LIKE 'portfolios'";
                $portfoliosTableExists = count(fetchAll($checkPortfoliosTableSql)) > 0;
                
                if (!$portfoliosTableExists) {
                    // Create the portfolios table if it doesn't exist
                    $createPortfoliosTableSql = "CREATE TABLE portfolios (
                        portfolio_id INT AUTO_INCREMENT PRIMARY KEY,
                        title VARCHAR(100) NOT NULL,
                        name VARCHAR(255),
                        email VARCHAR(255),
                        phone VARCHAR(50),
                        department VARCHAR(100),
                        term VARCHAR(100),
                        bio TEXT,
                        responsibilities JSON,
                        photo VARCHAR(255),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    execute($createPortfoliosTableSql);
                }
                
                // Insert new Senate President in portfolios
                $sql = "INSERT INTO portfolios (title, name, email, phone, department, term, bio, responsibilities, photo) 
                        VALUES ('Senate President', ?, ?, ?, ?, ?, ?, ?, ?)";
                
                execute($sql, [$name, $email, $phone, $faculty, $term, $bio, $responsibilitiesJson, $photoName]);
                
                $_SESSION['success'] = "Senate President added successfully.";
            }
        } else {
            // Insert into senate_members table
            $sql = "INSERT INTO senate_members (name, position, email, phone, faculty, term, bio, responsibilities, photo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            execute($sql, [$name, $position, $email, $phone, $faculty, $term, $bio, $responsibilitiesJson, $photoName]);
            
            $_SESSION['success'] = "Senate member added successfully.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding Senate member: " . $e->getMessage();
    }
    
    header("Location: senate.php");
    exit();
}

/**
 * Update Senate information
 */
function updateSenateInfo() {
    // Get form data
    $description = $_POST['senate_description'] ?? '';
    $structure = $_POST['senate_structure'] ?? '';
    $meetingSchedule = $_POST['meeting_schedule'] ?? '';
    $functions = $_POST['functions'] ?? [];
    
    // Convert functions array to JSON
    $functionsJson = json_encode(array_filter($functions));
    
    try {
        // Check if senate_info table exists
        $checkTableSql = "SHOW TABLES LIKE 'senate_info'";
        $tableExists = count(fetchAll($checkTableSql)) > 0;
        
        if (!$tableExists) {
            // Create the table if it doesn't exist
            $createTableSql = "CREATE TABLE senate_info (
                id INT AUTO_INCREMENT PRIMARY KEY,
                description TEXT,
                structure TEXT,
                meeting_schedule TEXT,
                functions JSON,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            execute($createTableSql);
        }
        
        // Check if there's existing data
        $checkDataSql = "SELECT id FROM senate_info LIMIT 1";
        $existingData = fetchOne($checkDataSql);
        
        if ($existingData) {
            // Update existing record
            $sql = "UPDATE senate_info SET 
                    description = ?, 
                    structure = ?, 
                    meeting_schedule = ?, 
                    functions = ?";
            
            execute($sql, [$description, $structure, $meetingSchedule, $functionsJson]);
        } else {
            // Insert new record
            $sql = "INSERT INTO senate_info (description, structure, meeting_schedule, functions) 
                    VALUES (?, ?, ?, ?)";
            
            execute($sql, [$description, $structure, $meetingSchedule, $functionsJson]);
        }
        
        $_SESSION['success'] = "Senate information updated successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating Senate information: " . $e->getMessage();
    }
    
    header("Location: senate.php");
    exit();
}

/**
 * Add a new Senate session
 */
function addSenateSession() {
    // Get form data
    $date = $_POST['session_date'] ?? '';
    $type = $_POST['session_type'] ?? '';
    $agenda = $_POST['session_agenda'] ?? '';
    $status = $_POST['session_status'] ?? 'Scheduled';
    
    // Validate required fields
    if (empty($date) || empty($type) || empty($agenda)) {
        $_SESSION['error'] = "Date, type, and agenda are required fields.";
        header("Location: senate.php");
        exit();
    }
    
    try {
        // Check if senate_sessions table exists
        $checkTableSql = "SHOW TABLES LIKE 'senate_sessions'";
        $tableExists = count(fetchAll($checkTableSql)) > 0;
        
        if (!$tableExists) {
            // Create the table if it doesn't exist
            $createTableSql = "CREATE TABLE senate_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_date DATE,
                session_type VARCHAR(50),
                agenda TEXT,
                status VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            execute($createTableSql);
        }
        
        // Insert new session
        $sql = "INSERT INTO senate_sessions (session_date, session_type, agenda, status) 
                VALUES (?, ?, ?, ?)";
        
        execute($sql, [$date, $type, $agenda, $status]);
        
        $_SESSION['success'] = "Senate session added successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding Senate session: " . $e->getMessage();
    }
    
    header("Location: senate.php");
    exit();
}

/**
 * Add a new Senate document
 */
function addSenateDocument() {
    // Get form data
    $title = $_POST['document_title'] ?? '';
    $type = $_POST['document_type'] ?? '';
    $description = $_POST['document_description'] ?? '';
    $status = $_POST['document_status'] ?? 'Active';
    
    // Validate required fields
    if (empty($title) || empty($type)) {
        $_SESSION['error'] = "Title and type are required fields.";
        header("Location: senate.php");
        exit();
    }
    
    // Handle file upload
    $fileName = '';
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        // Validate file type and size
        if (!in_array($_FILES['document_file']['type'], $allowedTypes)) {
            $_SESSION['error'] = "Only PDF and DOC/DOCX files are allowed.";
            header("Location: senate.php");
            exit();
        }
        
        if ($_FILES['document_file']['size'] > $maxSize) {
            $_SESSION['error'] = "File size exceeds the 10MB limit.";
            header("Location: senate.php");
            exit();
        }
        
        // Generate unique filename
        $fileName = 'senate_doc_' . time() . '_' . $_FILES['document_file']['name'];
        $uploadPath = '../uploads/documents/' . $fileName;
        
        // Create directory if it doesn't exist
        if (!is_dir('../uploads/documents/')) {
            mkdir('../uploads/documents/', 0777, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['document_file']['tmp_name'], $uploadPath)) {
            $_SESSION['error'] = "Failed to upload document.";
            header("Location: senate.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Document file is required.";
        header("Location: senate.php");
        exit();
    }
    
    try {
        // Check if senate_documents table exists
        $checkTableSql = "SHOW TABLES LIKE 'senate_documents'";
        $tableExists = count(fetchAll($checkTableSql)) > 0;
        
        if (!$tableExists) {
            // Create the table if it doesn't exist
            $createTableSql = "CREATE TABLE senate_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255),
                type VARCHAR(50),
                description TEXT,
                file_name VARCHAR(255),
                status VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            execute($createTableSql);
        }
        
        // Insert new document
        $sql = "INSERT INTO senate_documents (title, type, description, file_name, status) 
                VALUES (?, ?, ?, ?, ?)";
        
        execute($sql, [$title, $type, $description, $fileName, $status]);
        
        $_SESSION['success'] = "Senate document added successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding Senate document: " . $e->getMessage();
    }
    
    header("Location: senate.php");
    exit();
}

/**
 * Delete a Senate member
 */
function deleteSenateMember() {
    // Get member ID
    $memberId = $_POST['member_id'] ?? 0;
    
    if (!$memberId) {
        $_SESSION['error'] = "Invalid member ID.";
        header("Location: senate.php");
        exit();
    }
    
    try {
        // Check if it's a Senate President (in portfolios table)
        if (isset($_POST['is_president']) && $_POST['is_president'] == 1) {
            // Don't delete from portfolios, just clear the fields
            $sql = "UPDATE portfolios SET 
                    name = '', 
                    email = '', 
                    phone = '', 
                    department = '', 
                    term = '', 
                    bio = '', 
                    responsibilities = '[]', 
                    photo = '' 
                    WHERE portfolio_id = ? AND title = 'Senate President'";
            
            execute($sql, [$memberId]);
        } else {
            // Get photo filename before deletion
            $photoSql = "SELECT photo FROM senate_members WHERE id = ?";
            $photoResult = fetchOne($photoSql, [$memberId]);
            
            // Delete from senate_members table
            $sql = "DELETE FROM senate_members WHERE id = ?";
            execute($sql, [$memberId]);
            
            // Delete photo file if exists
            if ($photoResult && !empty($photoResult['photo'])) {
                $photoPath = '../uploads/senate/' . $photoResult['photo'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
        }
        
        $_SESSION['success'] = "Senate member deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting Senate member: " . $e->getMessage();
    }
    
    header("Location: senate.php");
    exit();
}

/**
 * Edit a Senate member
 */
function editSenateMember() {
    // Get member ID
    $memberId = $_POST['member_id'] ?? 0;
    
    if (!$memberId) {
        $_SESSION['error'] = "Invalid member ID.";
        header("Location: senate.php");
        exit();
    }
    
    // Get form data
    $name = $_POST['name'] ?? '';
    $position = $_POST['position'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $faculty = $_POST['faculty'] ?? '';
    $term = $_POST['term'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $responsibilities = $_POST['responsibilities'] ?? '';
    
    // Validate required fields
    if (empty($name) || empty($position)) {
        $_SESSION['error'] = "Name and position are required fields.";
        header("Location: senate.php");
        exit();
    }
    
    // Process responsibilities (convert from textarea to array)
    $responsibilitiesArray = [];
    if (!empty($responsibilities)) {
        $responsibilitiesArray = array_filter(explode("\n", $responsibilities), 'trim');
    }
    $responsibilitiesJson = json_encode($responsibilitiesArray);
    
    // Handle photo upload
    $photoName = '';
    $updatePhoto = false;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        // Validate file type and size
        if (!in_array($_FILES['photo']['type'], $allowedTypes)) {
            $_SESSION['error'] = "Only JPG and PNG images are allowed.";
            header("Location: senate.php");
            exit();
        }
        
        if ($_FILES['photo']['size'] > $maxSize) {
            $_SESSION['error'] = "File size exceeds the 2MB limit.";
            header("Location: senate.php");
            exit();
        }
        
        // Generate unique filename
        $photoName = 'senate_' . time() . '_' . $_FILES['photo']['name'];
        $uploadPath = '../uploads/senate/' . $photoName;
        
        // Create directory if it doesn't exist
        if (!is_dir('../uploads/senate/')) {
            mkdir('../uploads/senate/', 0777, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            $_SESSION['error'] = "Failed to upload photo.";
            header("Location: senate.php");
            exit();
        }
        
        $updatePhoto = true;
    }
    
    try {
        // Check if it's a Senate President (in portfolios table)
        if (isset($_POST['is_president']) && $_POST['is_president'] == 1) {
            $sql = "UPDATE portfolios SET 
                    name = ?, 
                    email = ?, 
                    phone = ?, 
                    department = ?, 
                    term = ?, 
                    bio = ?, 
                    responsibilities = ?";
            
            $params = [$name, $email, $phone, $faculty, $term, $bio, $responsibilitiesJson];
            
            // Add photo to query if provided
            if ($updatePhoto) {
                $sql .= ", photo = ?";
                $params[] = $photoName;
                
                // Delete old photo if exists
                $photoSql = "SELECT photo FROM portfolios WHERE portfolio_id = ? AND title = 'Senate President'";
                $photoResult = fetchOne($photoSql, [$memberId]);
                
                if ($photoResult && !empty($photoResult['photo'])) {
                    $photoPath = '../uploads/portfolios/' . $photoResult['photo'];
                    if (file_exists($photoPath)) {
                        unlink($photoPath);
                    }
                }
            }
            
            $sql .= " WHERE portfolio_id = ? AND title = 'Senate President'";
            $params[] = $memberId;
            
            execute($sql, $params);
        } else {
            $sql = "UPDATE senate_members SET 
                    name = ?, 
                    position = ?, 
                    email = ?, 
                    phone = ?, 
                    faculty = ?, 
                    term = ?, 
                    bio = ?, 
                    responsibilities = ?";
            
            $params = [$name, $position, $email, $phone, $faculty, $term, $bio, $responsibilitiesJson];
            
            // Add photo to query if provided
            if ($updatePhoto) {
                $sql .= ", photo = ?";
                $params[] = $photoName;
                
                // Delete old photo if exists
                $photoSql = "SELECT photo FROM senate_members WHERE id = ?";
                $photoResult = fetchOne($photoSql, [$memberId]);
                
                if ($photoResult && !empty($photoResult['photo'])) {
                    $photoPath = '../uploads/senate/' . $photoResult['photo'];
                    if (file_exists($photoPath)) {
                        unlink($photoPath);
                    }
                }
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $memberId;
            
            execute($sql, $params);
        }
        
        $_SESSION['success'] = "Senate member updated successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating Senate member: " . $e->getMessage();
    }
    
    header("Location: senate.php");
    exit();
}

/**
 * Edit a Senate session
 */
function editSenateSession() {
    // Get session ID
    $sessionId = $_POST['session_id'] ?? 0;
    
    if (!$sessionId) {
        $_SESSION['error'] = "Invalid session ID.";
        header("Location: senate.php");
        exit();
    }
    
    // Get form data
    $date = $_POST['session_date'] ?? '';
    $type = $_POST['session_type'] ?? '';
    $agenda = $_POST['session_agenda'] ?? '';
    $status = $_POST['session_status'] ?? 'Scheduled';
    
    // Validate required fields
    if (empty($date) || empty($type) || empty($agenda)) {
        $_SESSION['error'] = "Date, type, and agenda are required fields.";
        header("Location: senate.php");
        exit();
    }
    
    try {
        // Update session
        $sql = "UPDATE senate_sessions SET 
                session_date = ?, 
                session_type = ?, 
                agenda = ?, 
                status = ? 
                WHERE id = ?";
        
        execute($sql, [$date, $type, $agenda, $status, $sessionId]);
        
        $_SESSION['notification'] = "Senate session updated successfully.";
        $_SESSION['notification_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['notification'] = "Error updating Senate session: " . $e->getMessage();
        $_SESSION['notification_type'] = "danger";
    }
    
    header("Location: senate.php");
    exit();
}

/**
 * Delete a Senate session
 */
function deleteSenateSession() {
    // Get session ID
    $sessionId = $_POST['session_id'] ?? 0;
    
    if (!$sessionId) {
        $_SESSION['error'] = "Invalid session ID.";
        header("Location: senate.php");
        exit();
    }
    
    try {
        // Delete session
        $sql = "DELETE FROM senate_sessions WHERE id = ?";
        execute($sql, [$sessionId]);
        
        $_SESSION['notification'] = "Senate session deleted successfully.";
        $_SESSION['notification_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['notification'] = "Error deleting Senate session: " . $e->getMessage();
        $_SESSION['notification_type'] = "danger";
    }
    
    header("Location: senate.php");
    exit();
}

/**
 * Delete a Senate document
 */
function deleteDocument() {
    // Get document ID
    $documentId = $_POST['document_id'] ?? 0;
    
    if (!$documentId) {
        $_SESSION['error'] = "Invalid document ID.";
        header("Location: senate.php");
        exit();
    }
    
    try {
        // Get document filename before deletion
        $fileSql = "SELECT file_name FROM senate_documents WHERE id = ?";
        $fileResult = fetchOne($fileSql, [$documentId]);
        
        // Delete from senate_documents table
        $sql = "DELETE FROM senate_documents WHERE id = ?";
        execute($sql, [$documentId]);
        
        // Delete file if exists
        if ($fileResult && !empty($fileResult['file_name'])) {
            $filePath = '../uploads/documents/' . $fileResult['file_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $_SESSION['notification'] = "Senate document deleted successfully.";
        $_SESSION['notification_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['notification'] = "Error deleting Senate document: " . $e->getMessage();
        $_SESSION['notification_type'] = "danger";
    }
    
    header("Location: senate.php");
    exit();
}
?>
