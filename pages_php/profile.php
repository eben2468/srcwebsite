<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/profile_picture_helpers.php';

// Require login for this page
requireLogin();

// Create user_profiles table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    student_id VARCHAR(50) NULL,
    level VARCHAR(20) NULL,
    department VARCHAR(255) NULL,
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
$isAdmin = isAdmin();

// Handle profile update
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $level = trim($_POST['level'] ?? '');
    $department = trim($_POST['department'] ?? '');
    
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
            $profileSql = "UPDATE user_profiles SET full_name = ?, bio = ?, phone = ?, student_id = ?, level = ?, department = ? WHERE user_id = ?";
            $profileParams = [$fullName, $bio, $phone, $studentId, $level, $department, $userId];
            
            // Check if profile exists
            $checkProfile = fetchOne("SELECT profile_id FROM user_profiles WHERE user_id = ?", [$userId]);
            
            // If profile doesn't exist, create it
            if (!$checkProfile) {
                $profileSql = "INSERT INTO user_profiles (user_id, full_name, bio, phone, student_id, level, department) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $profileParams = [$userId, $fullName, $bio, $phone, $studentId, $level, $department]; // Correct params for INSERT
            } else {
                // Parameters for UPDATE
                $profileParams = [$fullName, $bio, $phone, $studentId, $level, $department, $userId];
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
            $errorMessage = "An error occurred while updating your profile: " . $e->getMessage();
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
                // Update profile picture in both users and user_profiles tables
                $userSql = "UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE user_id = ?";
                $profileSql = "UPDATE user_profiles SET profile_picture = ?, updated_at = NOW() WHERE user_id = ?";
                
                $userUpdated = executeQuery($userSql, [$filename, $userId]);
                $profileUpdated = executeQuery($profileSql, [$filename, $userId]);
                
                // If user_profiles record doesn't exist yet, create it
                if (!$profileUpdated) {
                    $checkProfileSql = "SELECT COUNT(*) as count FROM user_profiles WHERE user_id = ?";
                    $profileExists = fetchOne($checkProfileSql, [$userId]);

                    if (!$profileExists || $profileExists['count'] == 0) {
                        $createProfileSql = "INSERT INTO user_profiles (user_id, full_name, profile_picture) VALUES (?, ?, ?)";
                        // Use the full name from the profile if available, otherwise construct it
                        $fullName = $userProfile['full_name'] ?? ($currentUser['first_name'] . ' ' . $currentUser['last_name']);
                        executeQuery($createProfileSql, [$userId, $fullName, $filename]);
                    }
                }
                
                if ($userUpdated) {
                    // Update session data
                    $_SESSION['profile_picture'] = $filename;
                    $successMessage = "Profile picture updated successfully!";

                    // Refresh user data
                    $currentUser = getCurrentUser();

                    // Also update userProfile variable for this page
                    if (isset($userProfile) && is_array($userProfile)) {
                        $userProfile['profile_picture'] = $filename;
                    }
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

<!-- Custom Profile Header -->
<div class="profile-header animate__animated animate__fadeInDown">
    <div class="profile-header-content">
        <div class="profile-header-main">
            <h1 class="profile-title">
                <i class="fas fa-user me-3"></i>
                My Profile
            </h1>
            <p class="profile-description">Manage your personal information and account settings</p>
        </div>
        <div class="profile-header-actions">
            <a href="dashboard.php" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.profile-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.profile-header-main {
    flex: 1;
    text-align: center;
}

.profile-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.profile-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.profile-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.profile-header-actions {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.btn-header-action {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
}

.btn-header-action:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-decoration: none;
}

@media (max-width: 768px) {
    .profile-header {
        padding: 2rem 1.5rem;
    }

    .profile-header-content {
        flex-direction: column;
        align-items: center;
        padding: 0 1.5rem;
    }

    .profile-header-main {
        width: 100%;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .profile-title {
        font-size: 2rem;
        gap: 0.6rem;
        text-align: center;
        width: 100%;
    }

    .profile-title i {
        font-size: 1.8rem;
    }

    .profile-description {
        font-size: 1.1rem;
        text-align: center;
        width: 100%;
    }

    .profile-header-actions {
        width: 100%;
        justify-content: center;
    }

    .btn-header-action {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        min-width: 200px;
    }
}

/* Animation classes */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translate3d(0, -100%, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

.animate__animated {
    animation-duration: 0.6s;
    animation-fill-mode: both;
}

.animate__fadeInDown {
    animation-name: fadeInDown;
}

/* Mobile Full-Width Optimization for Profile Page */
@media (max-width: 991px) {
    [class*="col-md-"], [class*="col-xl-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Ensure profile header has border-radius on mobile */
    .profile-header {
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="container-fluid">
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
                        // Display profile picture using helper function
                        echo displayProfilePicture($currentUser, 'pages_php', [
                            'width' => 150, 
                            'height' => 150, 
                            'class' => 'img-fluid rounded-circle',
                            'style' => 'object-fit: cover;'
                        ]);
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
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="student_id" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($userProfile['student_id'] ?? ''); ?>" placeholder="Enter your student ID">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($userProfile['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="level" class="form-label">Level</label>
                                <select class="form-control" id="level" name="level">
                                    <option value="">Select Level</option>
                                    <option value="Level 100" <?php echo (isset($userProfile['level']) && $userProfile['level'] == 'Level 100') ? 'selected' : ''; ?>>Level 100</option>
                                    <option value="Level 200" <?php echo (isset($userProfile['level']) && $userProfile['level'] == 'Level 200') ? 'selected' : ''; ?>>Level 200</option>
                                    <option value="Level 300" <?php echo (isset($userProfile['level']) && $userProfile['level'] == 'Level 300') ? 'selected' : ''; ?>>Level 300</option>
                                    <option value="Level 400" <?php echo (isset($userProfile['level']) && $userProfile['level'] == 'Level 400') ? 'selected' : ''; ?>>Level 400</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-control" id="department" name="department">
                                    <option value="">Select Department</option>
                                    <option value="School of Nursing and Midwifery" <?php echo (isset($userProfile['department']) && $userProfile['department'] == 'School of Nursing and Midwifery') ? 'selected' : ''; ?>>School of Nursing and Midwifery</option>
                                    <option value="School of Theology and Mission" <?php echo (isset($userProfile['department']) && $userProfile['department'] == 'School of Theology and Mission') ? 'selected' : ''; ?>>School of Theology and Mission</option>
                                    <option value="School of Education" <?php echo (isset($userProfile['department']) && $userProfile['department'] == 'School of Education') ? 'selected' : ''; ?>>School of Education</option>
                                    <option value="School of Business" <?php echo (isset($userProfile['department']) && $userProfile['department'] == 'School of Business') ? 'selected' : ''; ?>>School of Business</option>
                                    <option value="Faculty of Science" <?php echo (isset($userProfile['department']) && $userProfile['department'] == 'Faculty of Science') ? 'selected' : ''; ?>>Faculty of Science</option>
                                    <option value="Development and Communication Studies" <?php echo (isset($userProfile['department']) && $userProfile['department'] == 'Development and Communication Studies') ? 'selected' : ''; ?>>Development and Communication Studies</option>
                                    <option value="Biomedical Engineering" <?php echo (isset($userProfile['department']) && $userProfile['department'] == 'Biomedical Engineering') ? 'selected' : ''; ?>>Biomedical Engineering</option>
                                </select>
                            </div>
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
