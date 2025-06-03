<?php
// Include authentication file
require_once '../auth_functions.php';
require_once '../db_config.php';

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

// Check if the election is in a valid state for registration (upcoming or active)
if ($position['election_status'] !== 'upcoming' && $position['election_status'] !== 'active') {
    $_SESSION['error'] = "Registration is only allowed for upcoming or active elections.";
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
    $manifesto = $_POST['manifesto'] ?? '';
    
    // Handle file upload if present
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
            // If photo was uploaded and insert was successful, update the record with photo
            if ($photoPath !== null) {
                $candidateId = $result; // Assuming insert() returns the last insert ID
                
                // Update the photo separately using a different column name
                $updateSql = "UPDATE election_candidates SET candidate_photo = ? WHERE candidate_id = ?";
                $updateParams = [$photoPath, $candidateId];
                
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
?>

<!-- Page Content -->
<div class="container-fluid">
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

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Register as Candidate</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="elections.php">Elections</a></li>
                    <li class="breadcrumb-item"><a href="election_detail.php?id=<?php echo $electionId; ?>"><?php echo htmlspecialchars($position['election_title']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Register as Candidate</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Registration Form -->
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Registration Form for <?php echo htmlspecialchars($position['title']); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?position_id=" . $positionId . "&election_id=" . $electionId); ?>" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="candidate-name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="candidate-name" value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" readonly>
                            <div class="form-text">You will be registered with your account name.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="manifesto" class="form-label">Manifesto/Campaign Statement</label>
                            <textarea class="form-control" id="manifesto" name="manifesto" rows="6" required></textarea>
                            <div class="form-text">Describe your vision, goals, and why students should vote for you.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="photo" class="form-label">Profile Photo (Optional)</label>
                            <input type="file" class="form-control" id="photo" name="photo">
                            <div class="form-text">Upload a professional photo. Maximum size: 5MB. Allowed formats: JPG, JPEG, PNG.</div>
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

<?php require_once 'includes/footer.php'; ?> 