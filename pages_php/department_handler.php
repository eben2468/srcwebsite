<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Require login for this page
requireLogin();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for admin status - use unified admin interface check for super admin users
$isAdmin = shouldUseAdminInterface();
$adminParam = $isAdmin ? '?admin=1' : '';

// Ensure only admin users can perform CRUD operations
if (!$isAdmin) {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header("Location: departments.php");
    exit();
}

// Default redirect location
$redirectUrl = 'departments.php' . $adminParam;

// Check if action is provided
if (!isset($_POST['action'])) {
    $_SESSION['error'] = "Invalid request: No action specified.";
    header("Location: $redirectUrl");
    exit();
}

$action = $_POST['action'];

// Handle different actions
switch ($action) {
    case 'add_department':
        handleAddDepartment();
        break;
        
    case 'edit_department':
        handleEditDepartment();
        break;
        
    case 'delete_department':
        handleDeleteDepartment();
        break;
        
    case 'add_event':
        handleAddEvent();
        break;
        
    case 'edit_event':
        handleEditEvent();
        break;
        
    case 'delete_event':
        handleDeleteEvent();
        break;
        
    case 'add_contact':
        handleAddContact();
        break;
        
    case 'edit_contact':
        handleEditContact();
        break;
        
    case 'delete_contact':
        handleDeleteContact();
        break;
        
    case 'add_document':
        handleAddDocument();
        break;
        
    case 'delete_document':
        handleDeleteDocument();
        break;
        
    case 'add_gallery_image':
        handleAddGalleryImage();
        break;
        
    case 'delete_gallery_image':
        handleDeleteGalleryImage();
        break;
        
    case 'delete_gallery_image_file':
        handleDeleteGalleryImageFile();
        break;

    case 'bulk_upload_gallery':
        handleBulkUploadGallery();
        break;

    case 'bulk_upload_documents':
        handleBulkUploadDocuments();
        break;

    default:
        $_SESSION['error'] = "Invalid action specified.";
        header("Location: $redirectUrl");
        exit();
}

// Handler functions

function handleAddDepartment() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['code']));
    $name = trim($_POST['name']);
    $head = trim($_POST['head']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $description = trim($_POST['description']);
    
    // Process programs (convert from textarea to array)
    $programsText = isset($_POST['programs']) ? trim($_POST['programs']) : '';
    $programs = [];
    if (!empty($programsText)) {
        $programs = array_filter(array_map('trim', explode("\n", $programsText)));
    }
    
    // Create the department data structure
    $department = [
        'id' => time(), // Use timestamp as ID
        'code' => $code,
        'name' => $name,
        'head' => $head,
        'email' => $email,
        'phone' => $phone,
        'description' => $description,
        'programs' => $programs,
        'events' => [],
        'contacts' => [],
        'documents' => []
    ];
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Create data directory if it doesn't exist
    if (!file_exists('../data')) {
        mkdir('../data', 0777, true);
    }
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    }
    
    // Add new department
    $departments[$code] = $department;
    
    // Save departments data
    file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
    
    // Upload department image if provided
    if (isset($_FILES['department_image']) && $_FILES['department_image']['error'] === UPLOAD_ERR_OK) {
        $code = strtolower($code);
        $targetDir = '../images/departments/';
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Get file extension
        $fileExt = strtolower(pathinfo($_FILES['department_image']['name'], PATHINFO_EXTENSION));
        $targetFile = $targetDir . $code . '.' . $fileExt;
        
        // Debug log
        $logMessage = date('Y-m-d H:i:s') . " - Uploading image for new department {$code}:\n";
        $logMessage .= "Source: " . $_FILES['department_image']['tmp_name'] . "\n";
        $logMessage .= "Target: " . $targetFile . "\n";
        $logMessage .= "Size: " . $_FILES['department_image']['size'] . " bytes\n";
        $logMessage .= "Type: " . $_FILES['department_image']['type'] . "\n";
        
        // Handle image upload
        if (move_uploaded_file($_FILES['department_image']['tmp_name'], $targetFile)) {
            $logMessage .= "Upload successful\n";
            
            // Resize image if GD is available
            if (extension_loaded('gd')) {
                $resizeResult = resizeImage($targetFile, $targetFile, 800, 400);
                $logMessage .= "Resize result: " . ($resizeResult ? "Success" : "Failed") . "\n";
            } else {
                $logMessage .= "GD extension not available, skipping resize\n";
            }
            
            // Set proper permissions
            chmod($targetFile, 0644);
            $logMessage .= "Permissions set to 0644\n";
        } else {
            $logMessage .= "Upload failed: " . error_get_last()['message'] . "\n";
        }
        
        // Write to log
        file_put_contents('../image_upload.log', $logMessage . "\n", FILE_APPEND);
    }
    
    $_SESSION['success'] = "Department '$name' added successfully!";
    header("Location: departments.php" . $adminParam);
    exit();
}

function handleEditDepartment() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $name = trim($_POST['name']);
    $head = trim($_POST['head']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $description = trim($_POST['description']);
    
    // Process programs (convert from textarea to array)
    $programsText = isset($_POST['programs']) ? trim($_POST['programs']) : '';
    $programs = [];
    if (!empty($programsText)) {
        $programs = array_filter(array_map('trim', explode("\n", $programsText)));
    }
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Preserve existing data that wasn't in the form
    $department = $departments[$code];
    $department['name'] = $name;
    $department['head'] = $head;
    $department['email'] = $email;
    $department['phone'] = $phone;
    $department['description'] = $description;
    $department['programs'] = $programs;
    
    // Update department
    $departments[$code] = $department;
    
    // Save departments data
    file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
    
    // Upload department image if provided
    if (isset($_FILES['department_image']) && $_FILES['department_image']['error'] === UPLOAD_ERR_OK) {
        $lowerCode = strtolower($code);
        $targetDir = '../images/departments/';
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Remove existing image files for this department
        $existingFiles = glob($targetDir . $lowerCode . '.*');
        foreach ($existingFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Get file extension
        $fileExt = strtolower(pathinfo($_FILES['department_image']['name'], PATHINFO_EXTENSION));
        $targetFile = $targetDir . $lowerCode . '.' . $fileExt;
        
        // Debug log
        $logMessage = date('Y-m-d H:i:s') . " - Uploading image for department {$code}:\n";
        $logMessage .= "Source: " . $_FILES['department_image']['tmp_name'] . "\n";
        $logMessage .= "Target: " . $targetFile . "\n";
        $logMessage .= "Size: " . $_FILES['department_image']['size'] . " bytes\n";
        $logMessage .= "Type: " . $_FILES['department_image']['type'] . "\n";
        
        // Handle image upload
        if (move_uploaded_file($_FILES['department_image']['tmp_name'], $targetFile)) {
            $logMessage .= "Upload successful\n";
            
            // Resize image if GD is available
            if (extension_loaded('gd')) {
                $resizeResult = resizeImage($targetFile, $targetFile, 800, 400);
                $logMessage .= "Resize result: " . ($resizeResult ? "Success" : "Failed") . "\n";
            } else {
                $logMessage .= "GD extension not available, skipping resize\n";
            }
            
            // Set proper permissions
            chmod($targetFile, 0644);
            $logMessage .= "Permissions set to 0644\n";
        } else {
            $logMessage .= "Upload failed: " . error_get_last()['message'] . "\n";
        }
        
        // Write to log
        file_put_contents('../image_upload.log', $logMessage . "\n", FILE_APPEND);
    }
    
    $_SESSION['success'] = "Department '$name' updated successfully!";
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

function handleDeleteDepartment() {
    global $adminParam;
    
    // Get department code from form
    $code = strtoupper(trim($_POST['department_code']));
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Store department name for success message
    $departmentName = $departments[$code]['name'];
    
    // Delete department from data
    unset($departments[$code]);
    
    // Save updated departments data
    file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
    
    // Delete department image
    $lowerCode = strtolower($code);
    $departmentImages = glob('../images/departments/' . $lowerCode . '.*');
    foreach ($departmentImages as $image) {
        if (is_file($image)) {
            unlink($image);
        }
    }
    
    // Delete department gallery folder
    $galleryDir = '../images/departments/gallery/' . $lowerCode;
    if (file_exists($galleryDir) && is_dir($galleryDir)) {
        $galleryFiles = glob($galleryDir . '/*');
        foreach ($galleryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($galleryDir);
    }
    
    // Delete department documents
    $departmentDocs = $departments[$code]['documents'] ?? [];
    foreach ($departmentDocs as $doc) {
        $docPath = '../documents/departments/' . $doc['filename'];
        if (file_exists($docPath) && is_file($docPath)) {
            unlink($docPath);
        }
    }
    
    $_SESSION['success'] = "Department '$departmentName' deleted successfully!";
    header("Location: departments.php" . $adminParam);
    exit();
}

function handleAddEvent() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $title = trim($_POST['title']);
    $date = trim($_POST['date']);
    $description = trim($_POST['description']);
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Create new event
    $event = [
        'id' => time(), // Use timestamp as ID
        'title' => $title,
        'date' => $date,
        'description' => $description
    ];
    
    // Add event to department
    if (!isset($departments[$code]['events'])) {
        $departments[$code]['events'] = [];
    }
    
    $departments[$code]['events'][] = $event;
    
    // Save updated departments data
    file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
    
    $_SESSION['success'] = "Event '$title' added successfully!";
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

function handleEditEvent() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $eventId = intval($_POST['event_id']);
    $title = trim($_POST['title']);
    $date = trim($_POST['date']);
    $description = trim($_POST['description']);
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Find and update the event
    $events = $departments[$code]['events'] ?? [];
    $eventFound = false;
    
    foreach ($events as &$event) {
        if ($event['id'] == $eventId) {
            $event['title'] = $title;
            $event['date'] = $date;
            $event['description'] = $description;
            $eventFound = true;
            break;
        }
    }
    
    if (!$eventFound) {
        $_SESSION['error'] = "Event not found!";
        header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
        exit();
    }
    
    // Update department events
    $departments[$code]['events'] = $events;
    
    // Save updated departments data
    file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
    
    $_SESSION['success'] = "Event '$title' updated successfully!";
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

function handleDeleteEvent() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $eventId = intval($_POST['event_id']);
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Find and remove the event
    $eventTitle = "";
    $events = $departments[$code]['events'] ?? [];
    $updatedEvents = [];
    
    foreach ($events as $event) {
        if ($event['id'] == $eventId) {
            $eventTitle = $event['title'];
            continue; // Skip this event (delete it)
        }
        $updatedEvents[] = $event;
    }
    
    // Update department events
    $departments[$code]['events'] = $updatedEvents;
    
    // Save updated departments data
    file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
    
    $_SESSION['success'] = $eventTitle ? "Event '$eventTitle' deleted successfully!" : "Event deleted successfully!";
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

function handleAddContact() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $name = trim($_POST['name']);
    $position = trim($_POST['position']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Create new contact
    $contact = [
        'id' => time(), // Use timestamp as ID
        'name' => $name,
        'position' => $position,
        'email' => $email,
        'phone' => $phone
    ];
    
    // Add contact to department
    if (!isset($departments[$code]['contacts'])) {
        $departments[$code]['contacts'] = [];
    }
    
    $departments[$code]['contacts'][] = $contact;
    
    // Save updated departments data
    file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
    
    $_SESSION['success'] = "Contact '$name' added successfully!";
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

function handleEditContact() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $contactId = intval($_POST['contact_id']);
    $name = trim($_POST['name']);
    $position = trim($_POST['position']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Find and update the contact
    $contacts = $departments[$code]['contacts'] ?? [];
    $contactFound = false;
    
    foreach ($contacts as &$contact) {
        if ($contact['id'] == $contactId) {
            $contact['name'] = $name;
            $contact['position'] = $position;
            $contact['email'] = $email;
            $contact['phone'] = $phone;
            $contactFound = true;
            break;
        }
    }
    
    if (!$contactFound) {
        $_SESSION['error'] = "Contact not found!";
        header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
        exit();
    }
    
    // Update department contacts
    $departments[$code]['contacts'] = $contacts;
    
    // Save updated departments data
    file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
    
    $_SESSION['success'] = "Contact '$name' updated successfully!";
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

function handleDeleteContact() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $contactId = intval($_POST['contact_id']);
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Find and remove the contact
    $contactName = "";
    $contacts = $departments[$code]['contacts'] ?? [];
    $updatedContacts = [];
    
    foreach ($contacts as $contact) {
        if ($contact['id'] == $contactId) {
            $contactName = $contact['name'];
            continue; // Skip this contact (delete it)
        }
        $updatedContacts[] = $contact;
    }
    
    // Update department contacts
    $departments[$code]['contacts'] = $updatedContacts;
    
    // Save updated departments data
    file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
    
    $_SESSION['success'] = $contactName ? "Contact '$contactName' deleted successfully!" : "Contact deleted successfully!";
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

function handleAddDocument() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $title = trim($_POST['title']);
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Handle document upload
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $targetDir = '../documents/departments/';
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Get original filename and create unique filename to prevent overwriting
        $originalFilename = basename($_FILES['document_file']['name']);
        $fileExt = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $safeFilename = strtolower($code) . '_' . time() . '.' . $fileExt;
        $targetFile = $targetDir . $safeFilename;
        
        // Handle document upload
        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $targetFile)) {
            // Create document record
            $document = [
                'id' => time(), // Use timestamp as ID
                'title' => $title,
                'filename' => $safeFilename,
                'original_filename' => $originalFilename,
                'upload_date' => date('Y-m-d H:i:s')
            ];
            
            // Add document to department
            if (!isset($departments[$code]['documents'])) {
                $departments[$code]['documents'] = [];
            }
            
            $departments[$code]['documents'][] = $document;
            
            // Save updated departments data
            file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
            
            $_SESSION['success'] = "Document '$title' uploaded successfully!";
        } else {
            $_SESSION['error'] = "Failed to upload document. Please try again.";
        }
    } else {
        $_SESSION['error'] = "No document file was uploaded or an error occurred.";
    }
    
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

function handleDeleteDocument() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $documentId = intval($_POST['document_id']);
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Find and remove the document
    $documentTitle = "";
    $documentFilename = "";
    $documents = $departments[$code]['documents'] ?? [];
    $updatedDocuments = [];
    
    foreach ($documents as $document) {
        if ($document['id'] == $documentId) {
            $documentTitle = $document['title'];
            $documentFilename = $document['filename'];
            continue; // Skip this document (delete it)
        }
        $updatedDocuments[] = $document;
    }
    
    // Update department documents
    $departments[$code]['documents'] = $updatedDocuments;
    
    // Save updated departments data
    file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
    
    // Delete document file if exists
    if ($documentFilename) {
        $documentPath = '../documents/departments/' . $documentFilename;
        if (file_exists($documentPath) && is_file($documentPath)) {
            unlink($documentPath);
        }
    }
    
    $_SESSION['success'] = $documentTitle ? "Document '$documentTitle' deleted successfully!" : "Document deleted successfully!";
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

function handleAddGalleryImage() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Handle image upload
    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = '../images/departments/gallery/';
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Create department-specific gallery folder
        $departmentDir = $targetDir . strtolower($code) . '/';
        if (!file_exists($departmentDir)) {
            mkdir($departmentDir, 0777, true);
        }
        
        // Get original filename and create unique filename to prevent overwriting
        $originalFilename = basename($_FILES['gallery_image']['name']);
        $fileExt = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $safeFilename = strtolower($code) . '_' . time() . '.' . $fileExt;
        $targetFile = $departmentDir . $safeFilename;
        
        // Handle image upload
        if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $targetFile)) {
            // Resize image if GD is available
            if (extension_loaded('gd')) {
                resizeImage($targetFile, $targetFile, 1200, 800);
            }
            
            // Create gallery image record
            $galleryImage = [
                'id' => time(), // Use timestamp as ID
                'filename' => $safeFilename,
                'path' => 'images/departments/gallery/' . strtolower($code) . '/' . $safeFilename,
                'caption' => $caption,
                'upload_date' => date('Y-m-d H:i:s')
            ];
            
            // Add gallery image to department
            if (!isset($departments[$code]['gallery'])) {
                $departments[$code]['gallery'] = [];
            }
            
            $departments[$code]['gallery'][] = $galleryImage;
            
            // Save updated departments data
            file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
            
            $_SESSION['success'] = "Gallery image uploaded successfully!";
        } else {
            $_SESSION['error'] = "Failed to upload gallery image. Please try again.";
        }
    } else {
        $_SESSION['error'] = "No gallery image was uploaded or an error occurred.";
    }
    
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

function handleDeleteGalleryImage() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $imageId = intval($_POST['image_id']);
    
    // Load existing departments data
    $departmentsFile = '../data/departments.json';
    $departments = [];
    
    // Load existing data if available
    if (file_exists($departmentsFile)) {
        $departmentsData = file_get_contents($departmentsFile);
        $departments = json_decode($departmentsData, true) ?: [];
    } else {
        $_SESSION['error'] = "Department data not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Check if department exists
    if (!isset($departments[$code])) {
        $_SESSION['error'] = "Department not found!";
        header("Location: departments.php" . $adminParam);
        exit();
    }
    
    // Find and remove the gallery image
    $imageFilename = "";
    $galleryImages = $departments[$code]['gallery'] ?? [];
    $updatedGalleryImages = [];
    
    foreach ($galleryImages as $image) {
        if ($image['id'] == $imageId) {
            $imageFilename = $image['filename'];
            continue; // Skip this image (delete it)
        }
        $updatedGalleryImages[] = $image;
    }
    
    // Update department gallery images
    $departments[$code]['gallery'] = $updatedGalleryImages;
    
    // Save updated departments data
    file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
    
    // Delete image file if exists
    if ($imageFilename) {
        $imagePath = '../images/departments/gallery/' . strtolower($code) . '/' . $imageFilename;
        if (file_exists($imagePath) && is_file($imagePath)) {
            unlink($imagePath);
        }
    }
    
    $_SESSION['success'] = "Gallery image deleted successfully!";
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

function handleDeleteGalleryImageFile() {
    global $adminParam;
    
    // Get form data
    $code = strtoupper(trim($_POST['department_code']));
    $imagePath = trim($_POST['image_path']);
    
    // Check if image path is valid and exists
    if (empty($imagePath) || !file_exists($imagePath) || !is_file($imagePath)) {
        $_SESSION['error'] = "Image file not found or invalid path!";
        header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
        exit();
    }
    
    // Delete the image file
    if (unlink($imagePath)) {
        $_SESSION['success'] = "Gallery image deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete the gallery image. Please check file permissions.";
    }
    
    header("Location: department-detail.php?code=$code" . ($adminParam ? '&admin=1' : ''));
    exit();
}

// Helper function to resize images
function resizeImage($sourceFile, $targetFile, $maxWidth, $maxHeight) {
    // Check if GD extension is loaded
    if (!extension_loaded('gd')) {
        // GD extension not available, just copy the file without resizing
        if ($sourceFile !== $targetFile) {
            return copy($sourceFile, $targetFile);
        }
        return true; // File is already in place
    }

    // Check if required GD functions exist
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng') ||
        !function_exists('imagecreatefromgif') || !function_exists('imagecreatetruecolor')) {
        // Required GD functions not available, just copy the file
        if ($sourceFile !== $targetFile) {
            return copy($sourceFile, $targetFile);
        }
        return true;
    }

    // Get image info
    $imageInfo = getimagesize($sourceFile);
    if (!$imageInfo) {
        // Cannot get image info, just copy the file
        if ($sourceFile !== $targetFile) {
            return copy($sourceFile, $targetFile);
        }
        return true;
    }

    list($width, $height, $type) = $imageInfo;

    // Calculate new dimensions while maintaining aspect ratio
    if ($width > $height) {
        $newWidth = $maxWidth;
        $newHeight = intval($height * $maxWidth / $width);
    } else {
        $newHeight = $maxHeight;
        $newWidth = intval($width * $maxHeight / $height);
    }

    // Create a new image with the new dimensions
    $sourceImage = null;

    // Load image based on its type
    switch ($type) {
        case IMAGETYPE_JPEG:
            if (function_exists('imagecreatefromjpeg')) {
                $sourceImage = imagecreatefromjpeg($sourceFile);
            }
            break;
        case IMAGETYPE_PNG:
            if (function_exists('imagecreatefrompng')) {
                $sourceImage = imagecreatefrompng($sourceFile);
            }
            break;
        case IMAGETYPE_GIF:
            if (function_exists('imagecreatefromgif')) {
                $sourceImage = imagecreatefromgif($sourceFile);
            }
            break;
        default:
            // Unsupported image type, just copy the file
            if ($sourceFile !== $targetFile) {
                return copy($sourceFile, $targetFile);
            }
            return true;
    }

    if (!$sourceImage) {
        // Failed to create image resource, just copy the file
        if ($sourceFile !== $targetFile) {
            return copy($sourceFile, $targetFile);
        }
        return true;
    }

    // Create target image
    $targetImage = imagecreatetruecolor($newWidth, $newHeight);
    if (!$targetImage) {
        imagedestroy($sourceImage);
        // Failed to create target image, just copy the file
        if ($sourceFile !== $targetFile) {
            return copy($sourceFile, $targetFile);
        }
        return true;
    }

    // Preserve transparency for PNG images
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Resize the image
    imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Save the resized image
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            if (function_exists('imagejpeg')) {
                $result = imagejpeg($targetImage, $targetFile, 90);
            }
            break;
        case IMAGETYPE_PNG:
            if (function_exists('imagepng')) {
                $result = imagepng($targetImage, $targetFile, 9);
            }
            break;
        case IMAGETYPE_GIF:
            if (function_exists('imagegif')) {
                $result = imagegif($targetImage, $targetFile);
            }
            break;
    }

    // Free up memory
    imagedestroy($sourceImage);
    imagedestroy($targetImage);

    // If image processing failed, try to copy the original file
    if (!$result && $sourceFile !== $targetFile) {
        return copy($sourceFile, $targetFile);
    }

    return $result;
}

function handleBulkUploadGallery() {
    global $isAdmin, $adminParam;

    if (!$isAdmin) {
        $_SESSION['error'] = "Access denied. Only administrators can upload gallery images.";
        header("Location: departments.php$adminParam");
        exit();
    }

    $departmentCode = $_POST['department_code'] ?? '';
    $defaultCaption = $_POST['default_caption'] ?? '';

    if (empty($departmentCode)) {
        $_SESSION['error'] = "Department code is required.";
        header("Location: departments.php$adminParam");
        exit();
    }

    // Check if files were uploaded
    if (!isset($_FILES['gallery_images']) || empty($_FILES['gallery_images']['name'][0])) {
        $_SESSION['error'] = "Please select at least one image to upload.";
        header("Location: department-detail.php?code=$departmentCode$adminParam");
        exit();
    }

    $uploadDir = "../images/departments/gallery/" . strtolower($departmentCode) . "/";

    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $_SESSION['error'] = "Failed to create upload directory.";
            header("Location: department-detail.php?code=$departmentCode$adminParam");
            exit();
        }
    }

    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    $fileCount = count($_FILES['gallery_images']['name']);

    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['gallery_images']['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "File " . ($_i + 1) . ": Upload error.";
            $errorCount++;
            continue;
        }

        $fileName = $_FILES['gallery_images']['name'][$i];
        $fileTmpName = $_FILES['gallery_images']['tmp_name'][$i];
        $fileSize = $_FILES['gallery_images']['size'][$i];

        // Validate file size (5MB max)
        if ($fileSize > 5 * 1024 * 1024) {
            $errors[] = "$fileName: File size exceeds 5MB limit.";
            $errorCount++;
            continue;
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = mime_content_type($fileTmpName);

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "$fileName: Invalid file type. Only JPG, JPEG, PNG are allowed.";
            $errorCount++;
            continue;
        }

        // Generate unique filename
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $newFileName;

        // Move uploaded file
        if (move_uploaded_file($fileTmpName, $targetPath)) {
            // Resize image if needed
            resizeImage($targetPath, $targetPath, 800, 600);

            // Update department JSON with new image
            updateDepartmentGallery($departmentCode, $newFileName, $defaultCaption ?: $fileName);
            $successCount++;
        } else {
            $errors[] = "$fileName: Failed to upload file.";
            $errorCount++;
        }
    }

    // Set success/error messages
    if ($successCount > 0) {
        $_SESSION['success'] = "Successfully uploaded $successCount images.";
    }

    if ($errorCount > 0) {
        $errorMessage = "Failed to upload $errorCount images. Errors: " . implode(", ", array_slice($errors, 0, 5));
        if (count($errors) > 5) {
            $errorMessage .= " and " . (count($errors) - 5) . " more.";
        }
        $_SESSION['error'] = $errorMessage;
    }

    header("Location: department-detail.php?code=$departmentCode$adminParam");
    exit();
}

function handleBulkUploadDocuments() {
    global $isAdmin, $adminParam;

    if (!$isAdmin) {
        $_SESSION['error'] = "Access denied. Only administrators can upload documents.";
        header("Location: departments.php$adminParam");
        exit();
    }

    $departmentCode = $_POST['department_code'] ?? '';

    if (empty($departmentCode)) {
        $_SESSION['error'] = "Department code is required.";
        header("Location: departments.php$adminParam");
        exit();
    }

    // Check if files were uploaded
    if (!isset($_FILES['documents']) || empty($_FILES['documents']['name'][0])) {
        $_SESSION['error'] = "Please select at least one document to upload.";
        header("Location: department-detail.php?code=$departmentCode$adminParam");
        exit();
    }

    $uploadDir = "../documents/departments/";

    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $_SESSION['error'] = "Failed to create upload directory.";
            header("Location: department-detail.php?code=$departmentCode$adminParam");
            exit();
        }
    }

    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    $fileCount = count($_FILES['documents']['name']);

    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['documents']['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "File " . ($i + 1) . ": Upload error.";
            $errorCount++;
            continue;
        }

        $fileName = $_FILES['documents']['name'][$i];
        $fileTmpName = $_FILES['documents']['tmp_name'][$i];
        $fileSize = $_FILES['documents']['size'][$i];

        // Validate file size (10MB max)
        if ($fileSize > 10 * 1024 * 1024) {
            $errors[] = "$fileName: File size exceeds 10MB limit.";
            $errorCount++;
            continue;
        }

        // Validate file type
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = "$fileName: Invalid file type. Only PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT are allowed.";
            $errorCount++;
            continue;
        }

        // Generate unique filename
        $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $newFileName;

        // Move uploaded file
        if (move_uploaded_file($fileTmpName, $targetPath)) {
            // Update department JSON with new document
            $documentTitle = pathinfo($fileName, PATHINFO_FILENAME);
            updateDepartmentDocuments($departmentCode, $newFileName, $documentTitle, $fileName);
            $successCount++;
        } else {
            $errors[] = "$fileName: Failed to upload file.";
            $errorCount++;
        }
    }

    // Set success/error messages
    if ($successCount > 0) {
        $_SESSION['success'] = "Successfully uploaded $successCount documents.";
    }

    if ($errorCount > 0) {
        $errorMessage = "Failed to upload $errorCount documents. Errors: " . implode(", ", array_slice($errors, 0, 5));
        if (count($errors) > 5) {
            $errorMessage .= " and " . (count($errors) - 5) . " more.";
        }
        $_SESSION['error'] = $errorMessage;
    }

    header("Location: department-detail.php?code=$departmentCode$adminParam");
    exit();
}

function updateDepartmentGallery($departmentCode, $fileName, $caption) {
    $departmentsFile = '../data/departments.json';

    if (!file_exists($departmentsFile)) {
        return false;
    }

    $departmentsData = file_get_contents($departmentsFile);
    $departments = json_decode($departmentsData, true) ?: [];

    if (!isset($departments[$departmentCode])) {
        return false;
    }

    if (!isset($departments[$departmentCode]['gallery'])) {
        $departments[$departmentCode]['gallery'] = [];
    }

    $newImage = [
        'id' => uniqid(),
        'path' => "images/departments/gallery/" . strtolower($departmentCode) . "/" . $fileName,
        'caption' => $caption,
        'upload_date' => date('Y-m-d H:i:s')
    ];

    $departments[$departmentCode]['gallery'][] = $newImage;

    return file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
}

function updateDepartmentDocuments($departmentCode, $fileName, $title, $originalFileName) {
    $departmentsFile = '../data/departments.json';

    if (!file_exists($departmentsFile)) {
        return false;
    }

    $departmentsData = file_get_contents($departmentsFile);
    $departments = json_decode($departmentsData, true) ?: [];

    if (!isset($departments[$departmentCode])) {
        return false;
    }

    if (!isset($departments[$departmentCode]['documents'])) {
        $departments[$departmentCode]['documents'] = [];
    }

    $newDocument = [
        'id' => uniqid(),
        'title' => $title,
        'filename' => $fileName,
        'original_filename' => $originalFileName,
        'upload_date' => date('Y-m-d H:i:s')
    ];

    $departments[$departmentCode]['documents'][] = $newDocument;

    return file_put_contents($departmentsFile, json_encode($departments, JSON_PRETTY_PRINT));
}
?>
