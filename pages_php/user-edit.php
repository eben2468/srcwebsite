<?php
// Include authentication file
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../db_functions.php';
require_once '../settings_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if user is admin
if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$userId = (int)$_GET['id'];

// Get user details
$userSql = "SELECT * FROM users WHERE user_id = ?";
$userDetails = fetchOne($userSql, [$userId]);

if (!$userDetails) {
    header("Location: users.php");
    exit();
}

// Set page title
$pageTitle = "Edit User - SRC Management System";

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $bio = trim($_POST['bio']);
    $phone = trim($_POST['phone']);
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $message = "All fields are required.";
        $messageType = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $messageType = "danger";
    } else {
        // Check if email already exists (excluding current user)
        $checkEmailSql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND user_id != ?";
        $result = fetchOne($checkEmailSql, [$email, $userId]);
        
        if ($result && $result['count'] > 0) {
            $message = "Email already exists. Please use a different email address.";
            $messageType = "danger";
        } else {
            // Handle profile picture upload
            $profilePicture = $userDetails['profile_picture']; // Default to current picture
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
                    $message = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
                    $messageType = "danger";
                } elseif ($_FILES['profile_picture']['size'] > $maxSize) {
                    $message = "File is too large. Maximum size is 5MB.";
                    $messageType = "danger";
                } else {
                    // Create profiles directory if it doesn't exist
                    $profilesDir = '../images/profiles';
                    if (!file_exists($profilesDir)) {
                        mkdir($profilesDir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    $newFilename = 'user_' . $userId . '_' . time() . '.' . $fileExtension;
                    $targetPath = $profilesDir . '/' . $newFilename;
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
                        // Delete old profile picture if it exists and is not the default
                        if (!empty($profilePicture) && $profilePicture != 'default.jpg' && file_exists($profilesDir . '/' . $profilePicture)) {
                            unlink($profilesDir . '/' . $profilePicture);
                        }
                        
                        $profilePicture = $newFilename;
                    } else {
                        $message = "Failed to upload profile picture.";
                        $messageType = "danger";
                    }
                }
            }
            
            if (empty($message)) {
                // Update user
                $updateSql = "UPDATE users SET 
                              first_name = ?, 
                              last_name = ?, 
                              email = ?, 
                              role = ?, 
                              status = ?, 
                              bio = ?, 
                              phone = ?, 
                              profile_picture = ?, 
                              updated_at = NOW() 
                              WHERE user_id = ?";
                
                $params = [
                    $firstName, 
                    $lastName, 
                    $email, 
                    $role, 
                    $status, 
                    $bio, 
                    $phone, 
                    $profilePicture, 
                    $userId
                ];
                
                if (executeQuery($updateSql, $params)) {
                    $message = "User updated successfully.";
                    $messageType = "success";
                    
                    // Refresh user details
                    $userDetails = fetchOne($userSql, [$userId]);
                } else {
                    $message = "Failed to update user. Please try again.";
                    $messageType = "danger";
                }
            }
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

<!-- Page Content -->
<?php if (!empty($message)): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Edit User</h1>
    <a href="users.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i> Back to Users
    </a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Profile Picture</h5>
            </div>
            <div class="card-body text-center">
                <?php if (!empty($userDetails['profile_picture']) && file_exists('../images/profiles/' . $userDetails['profile_picture'])): ?>
                    <img src="../images/profiles/<?php echo htmlspecialchars($userDetails['profile_picture']); ?>" 
                         class="rounded-circle img-fluid mb-3" 
                         style="width: 150px; height: 150px; object-fit: cover;" 
                         alt="Profile Picture">
                <?php else: ?>
                    <div class="avatar-circle mx-auto mb-3" style="width: 150px; height: 150px;">
                        <span class="avatar-text" style="font-size: 64px;">
                            <?php echo strtoupper(substr($userDetails['first_name'], 0, 1)); ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <p class="mb-2"><strong><?php echo htmlspecialchars($userDetails['username']); ?></strong></p>
                <p class="text-muted mb-3">
                    <span class="badge bg-<?php echo $userDetails['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                        <?php echo ucfirst(htmlspecialchars($userDetails['role'])); ?>
                    </span>
                </p>
                
                <p class="mb-0">
                    <small class="text-muted">
                        Created: <?php echo date('M d, Y', strtotime($userDetails['created_at'])); ?><br>
                        Last Updated: <?php echo date('M d, Y', strtotime($userDetails['updated_at'])); ?>
                    </small>
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">User Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $userId); ?>" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($userDetails['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($userDetails['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userDetails['email']); ?>" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?php echo $userDetails['role'] === 'user' ? 'selected' : ''; ?>>Regular User</option>
                                <option value="admin" <?php echo $userDetails['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Active" <?php echo $userDetails['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $userDetails['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Suspended" <?php echo $userDetails['status'] === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($userDetails['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($userDetails['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                        <div class="form-text">Upload a new profile picture (JPG, PNG, or GIF, max 5MB)</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 