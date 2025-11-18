<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get current user info
$user = getCurrentUser();
$userId = $user['user_id'];

// Check if position ID and election ID are provided
if (!isset($_GET['position_id']) || empty($_GET['position_id']) || !isset($_GET['election_id']) || empty($_GET['election_id'])) {
    $_SESSION['error'] = "Position ID and Election ID are required.";
    header("Location: elections.php");
    exit();
}

$positionId = intval($_GET['position_id']);
$electionId = intval($_GET['election_id']);

// Get position details
$sql = "SELECT p.*, e.title as election_title, e.status as election_status, e.start_date, e.end_date
        FROM election_positions p 
        JOIN elections e ON p.election_id = e.election_id
        WHERE p.position_id = ? AND p.election_id = ?";
$position = fetchOne($sql, [$positionId, $electionId]);

if (!$position) {
    $_SESSION['error'] = "Position not found.";
    header("Location: elections.php");
    exit();
}

// Check if the election is in a valid state for registration (nomination status)
if ($position['election_status'] !== 'nomination') {
    $_SESSION['error'] = "Candidate registration is only allowed when nominations are open.";
    header("Location: election_detail.php?id=" . $electionId);
    exit();
}

// Check if user has already registered for this position
$sql = "SELECT * FROM election_candidates WHERE position_id = ? AND user_id = ?";
$existingCandidate = fetchOne($sql, [$positionId, $userId]);

if ($existingCandidate) {
    $_SESSION['error'] = "You have already registered as a candidate for this position.";
    header("Location: election_detail.php?id=" . $electionId);
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manifestoType = $_POST['manifesto_type'] ?? 'text';
    $manifesto = '';
    $manifestoFilePath = null;
    
    // Handle manifesto based on type
    if ($manifestoType === 'text') {
        $manifesto = $_POST['manifesto'] ?? '';
        
        // Validate manifesto text
        if (strlen($manifesto) < 100) {
            $_SESSION['error'] = "Your manifesto must be at least 100 characters long.";
            header("Location: candidate_registration.php?position_id=" . $positionId . "&election_id=" . $electionId);
            exit();
        }
    } else {
        // Handle manifesto file upload
        if (isset($_FILES['manifesto_file']) && $_FILES['manifesto_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/manifestos/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['manifesto_file']['name']);
            $targetFilePath = $uploadDir . $fileName;
            
            // Check file size (limit to 5MB)
            if ($_FILES['manifesto_file']['size'] > 5000000) {
                $_SESSION['error'] = "Sorry, your manifesto file is too large. Maximum size is 5MB.";
                header("Location: candidate_registration.php?position_id=" . $positionId . "&election_id=" . $electionId);
                exit();
            }
            
            // Allow certain file formats
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            if ($fileType != "pdf" && $fileType != "doc" && $fileType != "docx") {
                $_SESSION['error'] = "Sorry, only PDF, DOC & DOCX files are allowed for manifestos.";
                header("Location: candidate_registration.php?position_id=" . $positionId . "&election_id=" . $electionId);
                exit();
            }
            
            // Upload file
            if (move_uploaded_file($_FILES['manifesto_file']['tmp_name'], $targetFilePath)) {
                $manifestoFilePath = $fileName;
                // Set a placeholder for the manifesto text field
                $manifesto = "[Manifesto uploaded as file: " . $fileName . "]";
            } else {
                $_SESSION['error'] = "Sorry, there was an error uploading your manifesto file.";
                header("Location: candidate_registration.php?position_id=" . $positionId . "&election_id=" . $electionId);
                exit();
            }
        } else {
            $_SESSION['error'] = "Manifesto file is required when choosing file upload option.";
            header("Location: candidate_registration.php?position_id=" . $positionId . "&election_id=" . $electionId);
            exit();
        }
    }
    
    // Handle photo upload if present
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/candidates/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        $targetFilePath = $uploadDir . $fileName;
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES['photo']['tmp_name']);
        if ($check === false) {
            $_SESSION['error'] = "File is not an image.";
            header("Location: candidate_registration.php?position_id=" . $positionId . "&election_id=" . $electionId);
            exit();
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['photo']['size'] > 5000000) {
            $_SESSION['error'] = "Sorry, your file is too large. Maximum size is 5MB.";
            header("Location: candidate_registration.php?position_id=" . $positionId . "&election_id=" . $electionId);
            exit();
        }
        
        // Allow certain file formats
        $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            $_SESSION['error'] = "Sorry, only JPG, JPEG & PNG files are allowed.";
            header("Location: candidate_registration.php?position_id=" . $positionId . "&election_id=" . $electionId);
            exit();
        }
        
        // Upload file
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFilePath)) {
            $photoPath = $fileName;
        } else {
            $_SESSION['error'] = "Sorry, there was an error uploading your file.";
            header("Location: candidate_registration.php?position_id=" . $positionId . "&election_id=" . $electionId);
            exit();
        }
    }
    
    // Insert candidate into database
    try {
        // Only include the essential fields to avoid column name issues
        $sql = "INSERT INTO election_candidates (election_id, position_id, user_id, manifesto, status) 
                VALUES (?, ?, ?, ?, 'pending')";
        
        $params = [
            $electionId,
            $positionId,
            $userId,
            $manifesto
        ];
        
        $result = insert($sql, $params);
        
        if ($result) {
            $candidateId = $result; // Assuming insert() returns the last insert ID
            
            // If photo was uploaded, update the record with photo
            if ($photoPath !== null) {
                // Update the photo separately using a different column name
                $updateSql = "UPDATE election_candidates SET candidate_photo = ? WHERE candidate_id = ?";
                $updateParams = [$photoPath, $candidateId];
                
                // Try to update, but don't throw error if it fails
                update($updateSql, $updateParams);
            }
            
            // If manifesto file was uploaded, update the record with file path
            if ($manifestoFilePath !== null) {
                // Update the manifesto file path
                $updateSql = "UPDATE election_candidates SET manifesto_file = ? WHERE candidate_id = ?";
                $updateParams = [$manifestoFilePath, $candidateId];
                
                // Try to update, but don't throw error if it fails
                update($updateSql, $updateParams);
            }
            
            $_SESSION['success'] = "Your candidacy has been submitted successfully. It is pending approval.";
            header("Location: election_detail.php?id=" . $electionId);
            exit();
        } else {
            $_SESSION['error'] = "Failed to submit your candidacy. Please try again.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Set page title
$pageTitle = "Candidate Registration - " . $position['title'] . " - SRC Management System";

// Include header
require_once 'includes/header.php';

// Add mobile fix CSS for candidate cards
echo '<link rel="stylesheet" href="../css/candidate-card-mobile-fix.css">';
echo '<link rel="stylesheet" href="../css/election-mobile-fix.css">';
?>

<!-- Page Content -->
<div class="container-fluid" style="margin-top: 60px;">
    <?php
    // Set up modern page header variables
    $pageTitle = "Candidate Registration";
    $pageIcon = "fa-user-plus";
    $pageDescription = "Register as a candidate for " . $position['title'] . " - " . $position['election_title'];
    $actions = [];

    // Back button
    $actions[] = [
        'url' => 'election_detail.php?id=' . $electionId,
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Election',
        'class' => 'btn-outline-light'
    ];

    // Admin actions
    if (isAdmin()) {
        $actions[] = [
            'url' => '#',
            'icon' => 'fa-cog',
            'text' => 'Admin Settings',
            'class' => 'btn-outline-light'
        ];
    }

    // Include the modern page header
    include_once 'includes/modern_page_header.php';
    ?>
    <!-- Display success/error messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Registration Form - Now full width -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Registration Form for <?php echo htmlspecialchars($position['title']); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?position_id=" . $positionId . "&election_id=" . $electionId); ?>" enctype="multipart/form-data" id="candidateForm">
                        <div class="mb-3">
                            <label for="candidate-name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="candidate-name" value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" readonly>
                            <div class="form-text">You will be registered with your account name.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Manifesto/Campaign Statement <span class="text-danger">*</span></label>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="form-check form-check-inline mb-3">
                                        <input class="form-check-input" type="radio" name="manifesto_type" id="manifesto-type-text" value="text" checked>
                                        <label class="form-check-label" for="manifesto-type-text">Type my manifesto</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="manifesto_type" id="manifesto-type-file" value="file">
                                        <label class="form-check-label" for="manifesto-type-file">Upload manifesto document</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="manifesto-text-container">
                                <textarea class="form-control" id="manifesto" name="manifesto" rows="6" minlength="100"></textarea>
                                <div class="form-text">
                                    <p>Your manifesto should include:</p>
                                    <ul>
                                        <li>Your vision and goals for the position</li>
                                        <li>Relevant experience and qualifications</li>
                                        <li>Key initiatives you plan to implement</li>
                                        <li>Why students should vote for you</li>
                                    </ul>
                                    <p>Minimum 100 characters. Keep it concise but informative.</p>
                                    <div id="character-count" class="mt-2">0 characters (minimum 100 required)</div>
                                </div>
                            </div>
                            
                            <div id="manifesto-file-container" style="display:none;">
                                <input type="file" class="form-control" id="manifesto_file" name="manifesto_file" accept=".pdf,.doc,.docx">
                                <div class="form-text">
                                    <p>Upload your manifesto as a document. Please ensure it includes:</p>
                                    <ul>
                                        <li>Your vision and goals for the position</li>
                                        <li>Relevant experience and qualifications</li>
                                        <li>Key initiatives you plan to implement</li>
                                        <li>Why students should vote for you</li>
                                    </ul>
                                    <p>Allowed formats: PDF, DOC, DOCX. Maximum size: 5MB</p>
                                </div>
                                <div class="mt-2" id="file-name-display"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="photo" class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg">
                            <div class="form-text">
                                <p>Upload a professional headshot photo. This will be displayed on your candidate profile and ballot.</p>
                                <ul>
                                    <li>Maximum size: 5MB</li>
                                    <li>Allowed formats: JPG, JPEG, PNG</li>
                                    <li>Recommended dimensions: Square (1:1 ratio)</li>
                                </ul>
                            </div>
                            <div class="mt-3">
                                <div id="photo-preview-container" style="display: none;">
                                    <p>Photo Preview:</p>
                                    <img id="photo-preview" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Candidate Code of Conduct</h5>
                                    <p>By submitting this form, you agree to:</p>
                                    <ul>
                                        <li>Conduct your campaign with honesty, integrity, and respect for all</li>
                                        <li>Refrain from making false statements or personal attacks against other candidates</li>
                                        <li>Comply with all election rules and guidelines</li>
                                        <li>Accept the final election results as determined by the election committee</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms-check" required>
                            <label class="form-check-label" for="terms-check">
                                I confirm that all information provided is accurate and I agree to abide by the election rules and code of conduct.
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="election_detail.php?id=<?php echo $electionId; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Submit Registration</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manifesto character count
    const manifestoTextarea = document.getElementById('manifesto');
    const characterCount = document.getElementById('character-count');
    const manifestoTypeText = document.getElementById('manifesto-type-text');
    const manifestoTypeFile = document.getElementById('manifesto-type-file');
    const manifestoTextContainer = document.getElementById('manifesto-text-container');
    const manifestoFileContainer = document.getElementById('manifesto-file-container');
    const manifestoFileInput = document.getElementById('manifesto_file');
    const fileNameDisplay = document.getElementById('file-name-display');
    
    // Toggle between text and file upload
    manifestoTypeText.addEventListener('change', function() {
        if (this.checked) {
            manifestoTextContainer.style.display = 'block';
            manifestoFileContainer.style.display = 'none';
            manifestoTextarea.setAttribute('required', 'required');
            manifestoFileInput.removeAttribute('required');
        }
    });
    
    manifestoTypeFile.addEventListener('change', function() {
        if (this.checked) {
            manifestoTextContainer.style.display = 'none';
            manifestoFileContainer.style.display = 'block';
            manifestoTextarea.removeAttribute('required');
            manifestoFileInput.setAttribute('required', 'required');
        }
    });
    
    // Manifesto file display
    manifestoFileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Validate file size
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB limit. Please choose a smaller file.');
                this.value = '';
                fileNameDisplay.textContent = '';
                return;
            }
            
            // Validate file type
            const fileType = file.type.toLowerCase();
            if (fileType !== 'application/pdf' && 
                fileType !== 'application/msword' && 
                fileType !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                alert('Only PDF, DOC & DOCX files are allowed.');
                this.value = '';
                fileNameDisplay.textContent = '';
                return;
            }
            
            fileNameDisplay.textContent = 'Selected file: ' + file.name;
            fileNameDisplay.classList.add('text-success');
        } else {
            fileNameDisplay.textContent = '';
        }
    });
    
    manifestoTextarea.addEventListener('input', function() {
        const count = this.value.length;
        characterCount.textContent = count + ' characters' + (count < 100 ? ' (minimum 100 required)' : '');
        
        if (count < 100) {
            characterCount.classList.add('text-danger');
            characterCount.classList.remove('text-success');
        } else {
            characterCount.classList.remove('text-danger');
            characterCount.classList.add('text-success');
        }
    });
    
    // Photo preview
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photo-preview');
    const photoPreviewContainer = document.getElementById('photo-preview-container');
    
    photoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Validate file size
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB limit. Please choose a smaller file.');
                this.value = '';
                photoPreviewContainer.style.display = 'none';
                return;
            }
            
            // Validate file type
            const fileType = file.type.toLowerCase();
            if (fileType !== 'image/jpeg' && fileType !== 'image/jpg' && fileType !== 'image/png') {
                alert('Only JPG, JPEG & PNG files are allowed.');
                this.value = '';
                photoPreviewContainer.style.display = 'none';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
                photoPreviewContainer.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            photoPreviewContainer.style.display = 'none';
        }
    });
    
    // Form validation
    const candidateForm = document.getElementById('candidateForm');
    
    candidateForm.addEventListener('submit', function(e) {
        if (manifestoTypeText.checked) {
            const manifesto = manifestoTextarea.value;
            if (manifesto.length < 100) {
                e.preventDefault();
                alert('Your manifesto must be at least 100 characters long.');
                manifestoTextarea.focus();
            }
        } else if (manifestoTypeFile.checked) {
            if (!manifestoFileInput.value) {
                e.preventDefault();
                alert('Please upload your manifesto document.');
                manifestoFileInput.focus();
            }
        }
    });
});
</script>

</div>

<?php require_once 'includes/footer.php'; ?>
