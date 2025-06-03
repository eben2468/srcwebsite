<?php
// Profile page - View and edit user profile information
require_once '../db_config.php';
require_once '../functions.php';
require_once '../auth_functions.php';
require_once '../auth_bridge.php';

// Create user_profiles table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query to create table
mysqli_query($conn, $createTableSQL);

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page
    header("Location: ../login.php");
    exit();
}

// Get current user information
$currentUser = getCurrentUser();

// Check if user data is available
if (!$currentUser || !isset($currentUser['user_id'])) {
    // Redirect to login page if user data is incomplete
    header("Location: ../login.php");
    exit();
}

$userId = $currentUser['user_id'];

// Get user profile data including full name
try {
    $userProfile = fetchOne("SELECT * FROM user_profiles WHERE user_id = ?", [$userId]);

    // If no profile exists yet, create one with default values
    if (!$userProfile) {
        // Use username as default full name if no profile exists
        $fullName = $currentUser['username'];
        $sql = "INSERT INTO user_profiles (user_id, full_name) VALUES (?, ?)";
        $profileId = insert($sql, [$userId, $fullName]);
        
        // Fetch the newly created profile
        $userProfile = fetchOne("SELECT * FROM user_profiles WHERE user_id = ?", [$userId]);
        
        // If still can't fetch profile, create a default one in memory
        if (!$userProfile) {
            $userProfile = [
                'full_name' => $fullName,
                'bio' => '',
                'phone' => '',
                'address' => '',
                'profile_picture' => 'default.jpg'
            ];
        }
    }
} catch (Exception $e) {
    // If there's an error, create a default profile in memory
    $userProfile = [
        'full_name' => $currentUser['username'],
        'bio' => '',
        'phone' => '',
        'address' => '',
        'profile_picture' => 'default.jpg'
    ];
    error_log("Error fetching/creating user profile: " . $e->getMessage());
}

// Check for admin status
$isAdmin = getBridgedAdminStatus();

// Handle profile update
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Validate required fields
    if (empty($fullName) || empty($email)) {
        $errorMessage = "Full name and email are required fields.";
    } else {
        // Update user email in users table
        try {
            $userSql = "UPDATE users SET email = ? WHERE user_id = ?";
            $userParams = [$email, $userId];
            $userUpdated = executeQuery($userSql, $userParams);
            
            // Update profile data in user_profiles table
            $profileSql = "UPDATE user_profiles SET full_name = ?, bio = ?, phone = ? WHERE user_id = ?";
            $profileParams = [$fullName, $bio, $phone, $userId];
            
            // Check if profile exists
            $checkProfile = fetchOne("SELECT profile_id FROM user_profiles WHERE user_id = ?", [$userId]);
            
            // If profile doesn't exist, create it
            if (!$checkProfile) {
                $profileSql = "INSERT INTO user_profiles (user_id, full_name, bio, phone) VALUES (?, ?, ?, ?)";
            }
            
            $profileUpdated = executeQuery($profileSql, $profileParams);
            
            if ($userUpdated) {
                // Update session data
                $_SESSION['user']['email'] = $email;
                
                $successMessage = "Profile updated successfully!";
                
                // Refresh user data
                $currentUser = getCurrentUser();
                try {
                    $userProfile = fetchOne("SELECT * FROM user_profiles WHERE user_id = ?", [$userId]);
                    
                    // If still can't fetch profile, use default values
                    if (!$userProfile) {
                        $userProfile = [
                            'full_name' => $fullName,
                            'bio' => $bio,
                            'phone' => $phone,
                            'address' => '',
                            'profile_picture' => 'default.jpg'
                        ];
                    }
                } catch (Exception $e) {
                    // Use form values if there's an error
                    $userProfile = [
                        'full_name' => $fullName,
                        'bio' => $bio,
                        'phone' => $phone,
                        'address' => '',
                        'profile_picture' => 'default.jpg'
                    ];
                }
            } else {
                $errorMessage = "Failed to update profile. Please try again.";
            }
        } catch (Exception $e) {
            $errorMessage = "An error occurred while updating your profile. Please try again.";
            error_log("Error updating profile: " . $e->getMessage());
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate password fields
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = "All password fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = "New password and confirmation do not match.";
    } elseif (strlen($newPassword) < 8) {
        $errorMessage = "New password must be at least 8 characters long.";
    } else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE user_id = ? LIMIT 1";
        $user = fetchOne($sql, [$userId]);
        
        if ($user && password_verify($currentPassword, $user['password'])) {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            
            if (executeQuery($sql, [$hashedPassword, $userId])) {
                $successMessage = "Password changed successfully!";
            } else {
                $errorMessage = "Failed to update password. Please try again.";
            }
        } else {
            $errorMessage = "Current password is incorrect.";
        }
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
            $errorMessage = "Only JPG, JPEG, and PNG files are allowed.";
        } elseif ($_FILES['profile_picture']['size'] > $maxSize) {
            $errorMessage = "File size must be less than 5MB.";
        } else {
            $uploadDir = '../images/profiles/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $filename = 'user_' . $userId . '_' . time() . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $targetFile = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                // Update profile picture in database
                $sql = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
                
                if (executeQuery($sql, [$filename, $userId])) {
                    // Update session data
                    $_SESSION['user']['profile_picture'] = $filename;
                    $successMessage = "Profile picture updated successfully!";
                    
                    // Refresh user data
                    $currentUser = getCurrentUser();
                } else {
                    $errorMessage = "Failed to update profile picture in database.";
                }
            } else {
                $errorMessage = "Failed to upload profile picture. Please try again.";
            }
        }
    } else {
        $errorMessage = "Please select a profile picture to upload.";
    }
}

// Page title
$pageTitle = "My Profile";

// Include standard header instead of department_header
require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    
    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $successMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $errorMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Profile Information Card -->
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-circle me-1"></i>
                    Profile Information
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php
                        // Display profile picture if available, otherwise show initial
                        if (!empty($currentUser['profile_picture']) && file_exists('../images/profiles/' . $currentUser['profile_picture'])) {
                            echo '<img src="../images/profiles/' . htmlspecialchars($currentUser['profile_picture']) . '" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;" alt="Profile Picture">';
                        } else {
                            // Use the first letter of the full name for the avatar
                            $initial = strtoupper(substr($userProfile['full_name'] ?? $currentUser['username'], 0, 1));
                            echo '<div class="avatar-circle mx-auto" style="width: 150px; height: 150px;">';
                            echo '<span class="avatar-text">' . $initial . '</span>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <h4><?php echo htmlspecialchars($userProfile['full_name'] ?? $currentUser['username']); ?></h4>
                    <p class="text-muted"><?php echo ucfirst(htmlspecialchars($currentUser['role'] ?? 'user')); ?></p>
                    <p><?php echo htmlspecialchars($userProfile['bio'] ?? ''); ?></p>
                    
                    <form action="profile.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST" enctype="multipart/form-data" class="mt-3">
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Update Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/jpg" required>
                        </div>
                        <button type="submit" name="upload_picture" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i> Upload Picture
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Edit Profile Card -->
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-edit me-1"></i>
                    Edit Profile
                </div>
                <div class="card-body">
                    <form action="profile.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($currentUser['username'] ?? ''); ?>" disabled>
                                <div class="form-text">Username cannot be changed.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control" id="role" value="<?php echo ucfirst(htmlspecialchars($currentUser['role'] ?? 'user')); ?>" disabled>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($userProfile['full_name'] ?? $currentUser['username']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($userProfile['phone'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($userProfile['bio'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-key me-1"></i>
                    Change Password
                </div>
                <div class="card-body">
                    <form action="profile.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key me-2"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include standard footer instead of department_footer
require_once 'includes/footer.php';
?> 