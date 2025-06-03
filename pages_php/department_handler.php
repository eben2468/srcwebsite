<?php
// Department handler file - Processes form submissions for department actions
require_once '../db_config.php';
require_once '../functions.php';
require_once '../auth_functions.php'; // Add auth_functions for standard auth
require_once '../auth_bridge.php'; // Add auth bridge for admin status

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for admin status using both systems
$isAdmin = isAdmin() || getBridgedAdminStatus();
$adminParam = $isAdmin ? '?admin=1' : '';

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
        
    case 'delete_event':
        handleDeleteEvent();
        break;
        
    case 'add_contact':
        handleAddContact();
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
        
        // Handle image upload
        if (move_uploaded_file($_FILES['department_image']['tmp_name'], $targetFile)) {
            // Resize image if GD is available
            if (extension_loaded('gd')) {
                resizeImage($targetFile, $targetFile, 800, 400);
            }
        }
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
        
        // Handle image upload
        if (move_uploaded_file($_FILES['department_image']['tmp_name'], $targetFile)) {
            // Resize image if GD is available
            if (extension_loaded('gd')) {
                resizeImage($targetFile, $targetFile, 800, 400);
            }
        }
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
    list($width, $height) = getimagesize($sourceFile);
    
    // Calculate new dimensions while maintaining aspect ratio
    if ($width > $height) {
        $newWidth = $maxWidth;
        $newHeight = intval($height * $maxWidth / $width);
    } else {
        $newHeight = $maxHeight;
        $newWidth = intval($width * $maxHeight / $height);
    }
    
    // Create a new image with the new dimensions
    $sourceImage = imagecreatefromjpeg($sourceFile);
    $targetImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Resize the image
    imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save the resized image
    imagejpeg($targetImage, $targetFile, 90);
    
    // Free up memory
    imagedestroy($sourceImage);
    imagedestroy($targetImage);
}
?> 