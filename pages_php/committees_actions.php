<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_functions.php';

// Check for AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    // Add general debug logging
    error_log("GET request received. Action: " . $_GET['action']);
    error_log("User logged in: " . (isLoggedIn() ? "Yes" : "No"));
    
    if ($_GET['action'] === 'get_committee') {
        // Add debug logging
        error_log("GET committee request received. User logged in: " . (isLoggedIn() ? "Yes" : "No"));
        error_log("Committee ID: " . ($_GET['id'] ?? 'Not provided'));
        
        // Allow all users to view committee details, even if not logged in
        // This makes the view details button work for everyone
        
        $committeeId = $_GET['id'] ?? 0;
        
        if (empty($committeeId)) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Committee ID is required']);
            exit();
        }
        
        try {
            // Get committee data
            $sql = "SELECT * FROM committees WHERE committee_id = ?";
            $committee = fetchOne($sql, [$committeeId]);
            
            if (!$committee) {
                http_response_code(404); // Not Found
                echo json_encode(['error' => 'Committee not found']);
                exit();
            }
            
            // Get committee members
            $membersSql = "SELECT * FROM committee_members WHERE committee_id = ? ORDER BY position";
            $members = fetchAll($membersSql, [$committeeId]);
            
            // Add members to the committee data
            $committee['members'] = $members;
            
            // Return committee data as JSON
            header('Content-Type: application/json');
            echo json_encode($committee);
            exit();
        } catch (Exception $e) {
            error_log("Error fetching committee data: " . $e->getMessage());
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Error fetching committee data: ' . $e->getMessage()]);
            exit();
        }
    } elseif ($_GET['action'] === 'get_committee_members') {
        // Allow all users to view committee members, even if not logged in
        // This makes the view details button work for everyone
        
        $committeeId = $_GET['id'] ?? 0;
        
        if (empty($committeeId)) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Committee ID is required']);
            exit();
        }
        
        try {
            // Get committee members
            $sql = "SELECT * FROM committee_members WHERE committee_id = ? ORDER BY position";
            $members = fetchAll($sql, [$committeeId]);
            
            // Return members data as JSON
            header('Content-Type: application/json');
            echo json_encode($members);
            exit();
        } catch (Exception $e) {
            error_log("Error fetching committee members: " . $e->getMessage());
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Error fetching committee members: ' . $e->getMessage()]);
            exit();
        }
    }
}

// For POST requests, check if user is logged in and is admin or super admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn() || !shouldUseAdminInterface()) {
        $_SESSION['error'] = "You don't have permission to perform this action.";
        header("Location: committees.php");
        exit();
    }
    
    // Get the action from POST
    $action = $_POST['action'] ?? '';
    
    // Handle different actions
    switch ($action) {
        case 'add_committee':
            addCommittee();
            break;
        case 'edit_committee':
            editCommittee();
            break;
        case 'delete_committee':
            deleteCommittee();
            break;
        case 'edit_static_committee':
            editStaticCommittee();
            break;
        case 'delete_static_committee':
            deleteStaticCommittee();
            break;
        case 'add_member':
            addCommitteeMember();
            break;
        case 'edit_member':
            editCommitteeMember();
            break;
        case 'delete_member':
            deleteCommitteeMember();
            break;
        case 'get_member':
            getCommitteeMember();
            break;
        case 'add_meeting':
            addCommitteeMeeting();
            break;
        case 'add_report':
            addCommitteeReport();
            break;
        default:
            $_SESSION['error'] = "Invalid action.";
            header("Location: committees.php");
            exit();
    }
}

/**
 * Process HTML content to ensure proper formatting and structure
 * 
 * @param string $html The HTML content to process
 * @return string The processed HTML content
 */
function processHtmlContent($html) {
    // If empty, return empty string
    if (empty($html)) {
        return '';
    }
    
    // Remove excess whitespace
    $html = trim($html);
    
    // If the content already has proper list HTML structure, return it as is
    if (preg_match('/<(ul|ol)[^>]*>.*?<\/(ul|ol)>/s', $html)) {
        return $html;
    }
    
    // Check if the content has individual list items but no wrapper
    if (preg_match('/<li[^>]*>/s', $html)) {
        return '<ul>' . $html . '</ul>';
    }
    
    // If no HTML tags found, convert line breaks to list items
    if (!preg_match('/<[^>]+>/', $html)) {
        $lines = explode("\n", $html);
        $listItems = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // Add icon for visual enhancement
                $listItems[] = '<li><i class="fas fa-check-circle me-2 text-success"></i>' . htmlspecialchars($line) . '</li>';
            }
        }
        
        if (!empty($listItems)) {
            return '<ul>' . implode('', $listItems) . '</ul>';
        }
    }
    
    // Fall back to wrapping the entire content in a paragraph if it's not list-like
    return '<p>' . htmlspecialchars($html) . '</p>';
}

/**
 * Add a new committee
 */
function addCommittee() {
    // Get form data
    $name = $_POST['committee_name'] ?? '';
    $type = $_POST['committee_type'] ?? '';
    $description = $_POST['committee_description'] ?? '';
    $purpose = $_POST['committee_purpose'] ?? '';
    $composition = $_POST['committee_composition'] ?? '';
    $responsibilities = $_POST['committee_responsibilities'] ?? '';
    
    // Process HTML content to ensure proper formatting
    $composition = processHtmlContent($composition);
    $responsibilities = processHtmlContent($responsibilities);
    
    // Validate required fields
    if (empty($name) || empty($type)) {
        $_SESSION['error'] = "Committee name and type are required fields.";
        header("Location: committees.php");
        exit();
    }
    
    try {
        // Check if committees table exists
        $checkTableSql = "SHOW TABLES LIKE 'committees'";
        $tableExists = count(fetchAll($checkTableSql)) > 0;
        
        if (!$tableExists) {
            // Create the table if it doesn't exist
            $createTableSql = "CREATE TABLE committees (
                committee_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                description TEXT,
                purpose TEXT,
                composition TEXT,
                responsibilities TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            execute($createTableSql);
        } else {
            // Check if table has composition and responsibilities columns
            $checkColumnsSql = "SHOW COLUMNS FROM committees LIKE 'composition'";
            $compositionExists = count(fetchAll($checkColumnsSql)) > 0;
            
            if (!$compositionExists) {
                // Add composition column
                $addColumnSql = "ALTER TABLE committees ADD COLUMN composition TEXT AFTER purpose";
                execute($addColumnSql);
                
                // Add responsibilities column
                $addColumnSql = "ALTER TABLE committees ADD COLUMN responsibilities TEXT AFTER composition";
                execute($addColumnSql);
            }
        }
        
        // Check if committee already exists
        $checkSql = "SELECT committee_id FROM committees WHERE name = ?";
        $existingCommittee = fetchOne($checkSql, [$name]);
        
        if ($existingCommittee) {
            $_SESSION['error'] = "A committee with this name already exists.";
            header("Location: committees.php");
            exit();
        }
        
        // Insert new committee
        $sql = "INSERT INTO committees (name, type, description, purpose, composition, responsibilities) VALUES (?, ?, ?, ?, ?, ?)";
        execute($sql, [$name, $type, $description, $purpose, $composition, $responsibilities]);
        
        $_SESSION['success'] = "Committee added successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding committee: " . $e->getMessage();
    }
    
    header("Location: committees.php");
    exit();
}

/**
 * Edit an existing committee
 */
function editCommittee() {
    // Get form data
    $committeeId = $_POST['committee_id'] ?? 0;
    $name = $_POST['committee_name'] ?? '';
    $type = $_POST['committee_type'] ?? '';
    $description = $_POST['committee_description'] ?? '';
    $purpose = $_POST['committee_purpose'] ?? '';
    $composition = $_POST['committee_composition'] ?? '';
    $responsibilities = $_POST['committee_responsibilities'] ?? '';
    
    // Check if this is an AJAX request
    $isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    // Process HTML content to ensure proper formatting
    $composition = processHtmlContent($composition);
    $responsibilities = processHtmlContent($responsibilities);
    
    // Validate required fields
    if (empty($committeeId) || empty($name) || empty($type)) {
        if ($isAjaxRequest) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Committee ID, name, and type are required fields."]);
            exit();
        } else {
            $_SESSION['error'] = "Committee ID, name, and type are required fields.";
            header("Location: committees.php");
            exit();
        }
    }
    
    try {
        // Check if committee exists
        $checkSql = "SELECT committee_id FROM committees WHERE committee_id = ?";
        $existingCommittee = fetchOne($checkSql, [$committeeId]);
        
        if (!$existingCommittee) {
            if ($isAjaxRequest) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Committee not found."]);
                exit();
            } else {
                $_SESSION['error'] = "Committee not found.";
                header("Location: committees.php");
                exit();
            }
        }
        
        // Check if new name conflicts with another committee
        $checkNameSql = "SELECT committee_id FROM committees WHERE name = ? AND committee_id != ?";
        $nameConflict = fetchOne($checkNameSql, [$name, $committeeId]);
        
        if ($nameConflict) {
            if ($isAjaxRequest) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Another committee with this name already exists."]);
                exit();
            } else {
                $_SESSION['error'] = "Another committee with this name already exists.";
                header("Location: committees.php");
                exit();
            }
        }
        
        // Check if table has composition and responsibilities columns
        $checkColumnsSql = "SHOW COLUMNS FROM committees LIKE 'composition'";
        $compositionExists = count(fetchAll($checkColumnsSql)) > 0;
        
        if (!$compositionExists) {
            // Add composition column
            $addColumnSql = "ALTER TABLE committees ADD COLUMN composition TEXT AFTER purpose";
            execute($addColumnSql);
            
            // Add responsibilities column
            $addColumnSql = "ALTER TABLE committees ADD COLUMN responsibilities TEXT AFTER composition";
            execute($addColumnSql);
        }
        
        // Update committee
        $sql = "UPDATE committees SET name = ?, type = ?, description = ?, purpose = ?, composition = ?, responsibilities = ? WHERE committee_id = ?";
        execute($sql, [$name, $type, $description, $purpose, $composition, $responsibilities, $committeeId]);
        
        if ($isAjaxRequest) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => "Committee updated successfully."]);
            exit();
        } else {
            $_SESSION['success'] = "Committee updated successfully.";
            header("Location: committees.php");
            exit();
        }
    } catch (Exception $e) {
        if ($isAjaxRequest) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Error updating committee: " . $e->getMessage()]);
            exit();
        } else {
            $_SESSION['error'] = "Error updating committee: " . $e->getMessage();
            header("Location: committees.php");
            exit();
        }
    }
}

/**
 * Delete a committee
 */
function deleteCommittee() {
    // Get committee ID
    $committeeId = $_POST['committee_id'] ?? 0;
    
    if (empty($committeeId)) {
        $_SESSION['error'] = "Committee ID is required.";
        header("Location: committees.php");
        exit();
    }
    
    try {
        // Check if committee exists
        $checkSql = "SELECT committee_id FROM committees WHERE committee_id = ?";
        $existingCommittee = fetchOne($checkSql, [$committeeId]);
        
        if (!$existingCommittee) {
            $_SESSION['error'] = "Committee not found.";
            header("Location: committees.php");
            exit();
        }
        
        // Delete committee
        $sql = "DELETE FROM committees WHERE committee_id = ?";
        execute($sql, [$committeeId]);
        
        // Also delete committee members
        $deleteMembersSql = "DELETE FROM committee_members WHERE committee_id = ?";
        execute($deleteMembersSql, [$committeeId]);
        
        $_SESSION['success'] = "Committee deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting committee: " . $e->getMessage();
    }
    
    header("Location: committees.php");
    exit();
}

/**
 * Add a committee member
 */
function addCommitteeMember() {
    // Get form data
    $committeeId = $_POST['committee_id'] ?? 0;
    $name = $_POST['member_name'] ?? '';
    $position = $_POST['member_position'] ?? '';
    $email = $_POST['member_email'] ?? '';
    $phone = $_POST['member_phone'] ?? '';
    $department = $_POST['member_department'] ?? '';
    
    // Validate required fields
    if (empty($committeeId) || empty($name) || empty($position)) {
        $_SESSION['error'] = "Committee ID, member name, and position are required fields.";
        header("Location: committees.php");
        exit();
    }
    
    try {
        // Check if committee_members table exists
        $checkTableSql = "SHOW TABLES LIKE 'committee_members'";
        $tableExists = count(fetchAll($checkTableSql)) > 0;
        
        if (!$tableExists) {
            // Create the table if it doesn't exist
            $createTableSql = "CREATE TABLE committee_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                committee_id INT NOT NULL,
                user_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                position VARCHAR(100) NOT NULL,
                email VARCHAR(255),
                phone VARCHAR(50),
                department VARCHAR(255),
                photo VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (committee_id) REFERENCES committees(committee_id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
            )";
            execute($createTableSql);
        } else {
            // Check if user_id column exists, if not add it
            $checkUserIdColumn = "SHOW COLUMNS FROM committee_members LIKE 'user_id'";
            $userIdExists = count(fetchAll($checkUserIdColumn)) > 0;

            if (!$userIdExists) {
                $addUserIdColumn = "ALTER TABLE committee_members ADD COLUMN user_id INT DEFAULT NULL AFTER committee_id";
                execute($addUserIdColumn);

                // Add foreign key constraint
                $addForeignKey = "ALTER TABLE committee_members ADD FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL";
                try {
                    execute($addForeignKey);
                } catch (Exception $e) {
                    // Foreign key might already exist or users table might not exist, continue
                    error_log("Could not add foreign key for user_id: " . $e->getMessage());
                }
            }
        }
        
        // Handle photo upload
        $photoName = '';
        if (isset($_FILES['member_photo']) && $_FILES['member_photo']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            // Validate file type and size
            if (!in_array($_FILES['member_photo']['type'], $allowedTypes)) {
                $_SESSION['error'] = "Only JPG and PNG images are allowed.";
                header("Location: committees.php");
                exit();
            }
            
            if ($_FILES['member_photo']['size'] > $maxSize) {
                $_SESSION['error'] = "File size exceeds the 2MB limit.";
                header("Location: committees.php");
                exit();
            }
            
            // Generate unique filename
            $photoName = 'committee_' . time() . '_' . $_FILES['member_photo']['name'];
            $uploadPath = '../uploads/committees/' . $photoName;
            
            // Create directory if it doesn't exist
            if (!is_dir('../uploads/committees/')) {
                mkdir('../uploads/committees/', 0777, true);
            }
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['member_photo']['tmp_name'], $uploadPath)) {
                $_SESSION['error'] = "Failed to upload photo.";
                header("Location: committees.php");
                exit();
            }
        }
        
        // Insert committee member (using user_id = 1 as default for non-user members)
        $sql = "INSERT INTO committee_members (committee_id, user_id, name, position, email, phone, department, photo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        execute($sql, [$committeeId, 1, $name, $position, $email, $phone, $department, $photoName]);
        
        $_SESSION['success'] = "Committee member added successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding committee member: " . $e->getMessage();
    }

    // Redirect back to the committee edit page if committee_id is available
    if (!empty($committeeId)) {
        header("Location: committees_edit.php?id=$committeeId");
    } else {
        header("Location: committees.php");
    }
    exit();
}

/**
 * Get a committee member for editing
 */
function getCommitteeMember() {
    $memberId = $_POST['member_id'] ?? 0;

    if (empty($memberId)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Member ID is required']);
        exit();
    }

    try {
        // Get member data
        $sql = "SELECT * FROM committee_members WHERE id = ?";
        $member = fetchOne($sql, [$memberId]);

        if (!$member) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Member not found']);
            exit();
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'member' => $member]);
        exit();

    } catch (Exception $e) {
        error_log("Error fetching member: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error fetching member data']);
        exit();
    }
}

/**
 * Edit a committee member
 */
function editCommitteeMember() {
    // Get form data
    $memberId = $_POST['member_id'] ?? 0;
    $committeeId = $_POST['committee_id'] ?? 0;
    $name = $_POST['member_name'] ?? '';
    $position = $_POST['member_position'] ?? '';
    $email = $_POST['member_email'] ?? '';
    $phone = $_POST['member_phone'] ?? '';
    $department = $_POST['member_department'] ?? '';
    
    // Validate required fields
    if (empty($memberId) || empty($committeeId) || empty($name) || empty($position)) {
        $_SESSION['error'] = "Member ID, committee ID, name, and position are required fields.";
        header("Location: committees.php");
        exit();
    }
    
    try {
        // Check if member exists
        $checkSql = "SELECT id, photo FROM committee_members WHERE id = ?";
        $existingMember = fetchOne($checkSql, [$memberId]);
        
        if (!$existingMember) {
            $_SESSION['error'] = "Committee member not found.";
            header("Location: committees.php");
            exit();
        }
        
        // Handle photo upload
        $photoName = $existingMember['photo'] ?? '';
        $updatePhoto = false;
        
        if (isset($_FILES['member_photo']) && $_FILES['member_photo']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            // Validate file type and size
            if (!in_array($_FILES['member_photo']['type'], $allowedTypes)) {
                $_SESSION['error'] = "Only JPG and PNG images are allowed.";
                header("Location: committees.php");
                exit();
            }
            
            if ($_FILES['member_photo']['size'] > $maxSize) {
                $_SESSION['error'] = "File size exceeds the 2MB limit.";
                header("Location: committees.php");
                exit();
            }
            
            // Generate unique filename
            $photoName = 'committee_' . time() . '_' . $_FILES['member_photo']['name'];
            $uploadPath = '../uploads/committees/' . $photoName;
            
            // Create directory if it doesn't exist
            if (!is_dir('../uploads/committees/')) {
                mkdir('../uploads/committees/', 0777, true);
            }
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['member_photo']['tmp_name'], $uploadPath)) {
                $_SESSION['error'] = "Failed to upload photo.";
                header("Location: committees.php");
                exit();
            }
            
            // Delete old photo if exists
            if (!empty($existingMember['photo'])) {
                $oldPhotoPath = '../uploads/committees/' . $existingMember['photo'];
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }
            
            $updatePhoto = true;
        }
        
        // Update member
        $sql = "UPDATE committee_members SET 
                committee_id = ?, 
                name = ?, 
                position = ?, 
                email = ?, 
                phone = ?, 
                department = ?";
        
        $params = [$committeeId, $name, $position, $email, $phone, $department];
        
        if ($updatePhoto) {
            $sql .= ", photo = ?";
            $params[] = $photoName;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $memberId;
        
        execute($sql, $params);
        
        $_SESSION['success'] = "Committee member updated successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating committee member: " . $e->getMessage();
    }

    // Redirect back to the committee edit page if committee_id is available
    if (!empty($committeeId)) {
        header("Location: committees_edit.php?id=$committeeId");
    } else {
        header("Location: committees.php");
    }
    exit();
}

/**
 * Delete a committee member
 */
function deleteCommitteeMember() {
    // Get member ID and committee ID
    $memberId = $_POST['member_id'] ?? 0;
    $committeeId = $_POST['committee_id'] ?? 0;

    if (empty($memberId)) {
        $_SESSION['error'] = "Member ID is required.";
        header("Location: committees.php");
        exit();
    }

    try {
        // Get photo filename and committee_id before deletion
        $photoSql = "SELECT photo, committee_id FROM committee_members WHERE id = ?";
        $photoResult = fetchOne($photoSql, [$memberId]);

        // If committee_id wasn't passed in POST, get it from the database
        if (empty($committeeId) && $photoResult) {
            $committeeId = $photoResult['committee_id'];
        }

        // Delete member
        $sql = "DELETE FROM committee_members WHERE id = ?";
        execute($sql, [$memberId]);

        // Delete photo file if exists
        if ($photoResult && !empty($photoResult['photo'])) {
            $photoPath = '../uploads/committees/' . $photoResult['photo'];
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }

        $_SESSION['success'] = "Committee member deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting committee member: " . $e->getMessage();
    }

    // Redirect back to the committee edit page if committee_id is available
    if (!empty($committeeId)) {
        header("Location: committees_edit.php?id=$committeeId");
    } else {
        header("Location: committees.php");
    }
    exit();
}

/**
 * Add a committee meeting
 */
function addCommitteeMeeting() {
    // Get form data
    $committeeId = $_POST['committee_id'] ?? 0;
    $date = $_POST['meeting_date'] ?? '';
    $agenda = $_POST['meeting_agenda'] ?? '';
    $venue = $_POST['meeting_venue'] ?? '';
    $status = $_POST['meeting_status'] ?? 'Scheduled';
    
    // Validate required fields
    if (empty($committeeId) || empty($date) || empty($agenda)) {
        $_SESSION['error'] = "Committee ID, meeting date, and agenda are required fields.";
        header("Location: committees.php");
        exit();
    }
    
    try {
        // Check if committee_meetings table exists
        $checkTableSql = "SHOW TABLES LIKE 'committee_meetings'";
        $tableExists = count(fetchAll($checkTableSql)) > 0;
        
        if (!$tableExists) {
            // Create the table if it doesn't exist
            $createTableSql = "CREATE TABLE committee_meetings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                committee_id INT NOT NULL,
                meeting_date DATE NOT NULL,
                agenda TEXT NOT NULL,
                venue VARCHAR(255),
                status VARCHAR(50) DEFAULT 'Scheduled',
                minutes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (committee_id) REFERENCES committees(id) ON DELETE CASCADE
            )";
            execute($createTableSql);
        }
        
        // Insert meeting
        $sql = "INSERT INTO committee_meetings (committee_id, meeting_date, agenda, venue, status) 
                VALUES (?, ?, ?, ?, ?)";
        execute($sql, [$committeeId, $date, $agenda, $venue, $status]);
        
        $_SESSION['success'] = "Committee meeting added successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding committee meeting: " . $e->getMessage();
    }
    
    header("Location: committees.php");
    exit();
}

/**
 * Add a committee report
 */
function addCommitteeReport() {
    // Get form data
    $committeeId = $_POST['committee_id'] ?? 0;
    $title = $_POST['report_title'] ?? '';
    $date = $_POST['report_date'] ?? '';
    $content = $_POST['report_content'] ?? '';
    
    // Validate required fields
    if (empty($committeeId) || empty($title) || empty($date)) {
        $_SESSION['error'] = "Committee ID, report title, and date are required fields.";
        header("Location: committees.php");
        exit();
    }
    
    try {
        // Check if committee_reports table exists
        $checkTableSql = "SHOW TABLES LIKE 'committee_reports'";
        $tableExists = count(fetchAll($checkTableSql)) > 0;
        
        if (!$tableExists) {
            // Create the table if it doesn't exist
            $createTableSql = "CREATE TABLE committee_reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                committee_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                report_date DATE NOT NULL,
                content TEXT,
                file_name VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (committee_id) REFERENCES committees(id) ON DELETE CASCADE
            )";
            execute($createTableSql);
        }
        
        // Handle file upload
        $fileName = '';
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $maxSize = 10 * 1024 * 1024; // 10MB
            
            // Validate file type and size
            if (!in_array($_FILES['report_file']['type'], $allowedTypes)) {
                $_SESSION['error'] = "Only PDF and DOC/DOCX files are allowed.";
                header("Location: committees.php");
                exit();
            }
            
            if ($_FILES['report_file']['size'] > $maxSize) {
                $_SESSION['error'] = "File size exceeds the 10MB limit.";
                header("Location: committees.php");
                exit();
            }
            
            // Generate unique filename
            $fileName = 'committee_report_' . time() . '_' . $_FILES['report_file']['name'];
            $uploadPath = '../uploads/reports/' . $fileName;
            
            // Create directory if it doesn't exist
            if (!is_dir('../uploads/reports/')) {
                mkdir('../uploads/reports/', 0777, true);
            }
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['report_file']['tmp_name'], $uploadPath)) {
                $_SESSION['error'] = "Failed to upload report file.";
                header("Location: committees.php");
                exit();
            }
        }
        
        // Insert report
        $sql = "INSERT INTO committee_reports (committee_id, title, report_date, content, file_name) 
                VALUES (?, ?, ?, ?, ?)";
        execute($sql, [$committeeId, $title, $date, $content, $fileName]);
        
        $_SESSION['success'] = "Committee report added successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding committee report: " . $e->getMessage();
    }
    
    header("Location: committees.php");
    exit();
}

/**
 * Edit a static committee (hardcoded in the template)
 * This creates a new committee in the database with the same name as the static committee
 */
function editStaticCommittee() {
    // Get form data
    $originalName = $_POST['original_committee_name'] ?? '';
    $name = $_POST['committee_name'] ?? '';
    $type = $_POST['committee_type'] ?? '';
    $description = $_POST['committee_description'] ?? '';
    $purpose = $_POST['committee_purpose'] ?? '';
    $composition = $_POST['committee_composition'] ?? '';
    $responsibilities = $_POST['committee_responsibilities'] ?? '';
    
    // Check if this is an AJAX request
    $isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    // Also get backup hidden HTML fields if the textareas weren't properly filled
    $compositionHtml = $_POST['committee_composition_html'] ?? '';
    $responsibilitiesHtml = $_POST['committee_responsibilities_html'] ?? '';
    
    // Use backup HTML if textarea is empty
    if (empty($composition) && !empty($compositionHtml)) {
        $composition = $compositionHtml;
    }
    
    if (empty($responsibilities) && !empty($responsibilitiesHtml)) {
        $responsibilities = $responsibilitiesHtml;
    }
    
    // Process HTML content to ensure proper formatting
    $composition = processHtmlContent($composition);
    $responsibilities = processHtmlContent($responsibilities);
    
    // Debug information
    error_log("Editing static committee: " . $originalName);
    error_log("New name: " . $name);
    error_log("Composition: " . substr($composition, 0, 100) . "...");
    error_log("Responsibilities: " . substr($responsibilities, 0, 100) . "...");
    
    // Validate required fields
    if (empty($originalName) || empty($name) || empty($type)) {
        if ($isAjaxRequest) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Committee name and type are required fields."]);
            exit();
        } else {
            $_SESSION['error'] = "Committee name and type are required fields.";
            header("Location: committees.php");
            exit();
        }
    }
    
    try {
        // Check if committees table exists
        $checkTableSql = "SHOW TABLES LIKE 'committees'";
        $tableExists = count(fetchAll($checkTableSql)) > 0;
        
        if (!$tableExists) {
            // Create the table if it doesn't exist
            $createTableSql = "CREATE TABLE committees (
                committee_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                description TEXT,
                purpose TEXT,
                composition TEXT,
                responsibilities TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            execute($createTableSql);
        } else {
            // Check if composition and responsibilities columns exist
            $checkColumnsSql = "SHOW COLUMNS FROM committees LIKE 'composition'";
            $compositionExists = count(fetchAll($checkColumnsSql)) > 0;
            
            if (!$compositionExists) {
                // Add composition column
                $addColumnSql = "ALTER TABLE committees ADD COLUMN composition TEXT AFTER purpose";
                execute($addColumnSql);
                
                // Add responsibilities column
                $addColumnSql = "ALTER TABLE committees ADD COLUMN responsibilities TEXT AFTER composition";
                execute($addColumnSql);
            }
        }
        
        // Check if committee already exists in database
        $checkSql = "SELECT committee_id FROM committees WHERE name = ?";
        $existingCommittee = fetchOne($checkSql, [$originalName]);

        if ($existingCommittee) {
            // Update existing committee
            $sql = "UPDATE committees SET name = ?, type = ?, description = ?, purpose = ?, composition = ?, responsibilities = ? WHERE committee_id = ?";
            execute($sql, [$name, $type, $description, $purpose, $composition, $responsibilities, $existingCommittee['committee_id']]);
            
            if ($isAjaxRequest) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => "Committee updated successfully."]);
                exit();
            } else {
                $_SESSION['success'] = "Committee updated successfully.";
                header("Location: committees.php?updated=" . time());
                exit();
            }
        } else {
            // Insert new committee
            $sql = "INSERT INTO committees (name, type, description, purpose, composition, responsibilities) VALUES (?, ?, ?, ?, ?, ?)";
            execute($sql, [$name, $type, $description, $purpose, $composition, $responsibilities]);
            
            if ($isAjaxRequest) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => "Committee added to database successfully."]);
                exit();
            } else {
                $_SESSION['success'] = "Committee added to database successfully.";
                header("Location: committees.php?updated=" . time());
                exit();
            }
        }
    } catch (Exception $e) {
        error_log("Error updating committee: " . $e->getMessage());
        if ($isAjaxRequest) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Error updating committee: " . $e->getMessage()]);
            exit();
        } else {
            $_SESSION['error'] = "Error updating committee: " . $e->getMessage();
            header("Location: committees.php");
            exit();
        }
    }
}

/**
 * Delete a static committee (hardcoded in the template)
 * This adds a flag to the committee in the database to mark it as deleted
 */
function deleteStaticCommittee() {
    // Get committee name
    $committeeName = $_POST['committee_name'] ?? '';
    
    if (empty($committeeName)) {
        $_SESSION['error'] = "Committee name is required.";
        header("Location: committees.php");
        exit();
    }
    
    try {
        // Check if committee exists in database
        $checkSql = "SELECT committee_id FROM committees WHERE name = ?";
        $existingCommittee = fetchOne($checkSql, [$committeeName]);

        if ($existingCommittee) {
            // Delete committee from database
            $sql = "DELETE FROM committees WHERE committee_id = ?";
            execute($sql, [$existingCommittee['committee_id']]);

            // Also delete committee members
            $deleteMembersSql = "DELETE FROM committee_members WHERE committee_id = ?";
            execute($deleteMembersSql, [$existingCommittee['committee_id']]);
            
            $_SESSION['success'] = "Committee deleted successfully.";
        } else {
            // Static committee doesn't exist in database, so it's already "deleted" from display
            $_SESSION['success'] = "Static committee removed from display successfully.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting committee: " . $e->getMessage();
    }
    
    header("Location: committees.php");
    exit();
}
?>
