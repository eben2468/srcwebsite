<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check for admin status - use unified admin interface check for super admin users
$isAdmin = shouldUseAdminInterface();
if (!$isAdmin) {
    $_SESSION['error'] = "You don't have permission to access this page.";
    header("Location: committees.php");
    exit();
}

// Page title
$pageTitle = "Edit Committee - SRC Management System";

// Get committee ID from URL
$committeeId = $_GET['id'] ?? null;
if (!$committeeId) {
    $_SESSION['error'] = "No committee ID provided.";
    header("Location: committees.php");
    exit();
}

// Get committee data
try {
    $sql = "SELECT * FROM committees WHERE committee_id = ?";
    $committee = fetchOne($sql, [$committeeId]);
    
    if (!$committee) {
        $_SESSION['error'] = "Committee not found.";
        header("Location: committees.php");
        exit();
    }
    
    // Get committee members
    $membersSql = "SELECT * FROM committee_members WHERE committee_id = ? ORDER BY position";
    $members = fetchAll($membersSql, [$committeeId]);
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching committee data: " . $e->getMessage();
    header("Location: committees.php");
    exit();
}

// Include header
require_once 'includes/header.php';

// Define page title, icon, and actions for the modern header
$pageTitle = "Edit Committee";
$pageIcon = "fa-edit";
$pageDescription = "Modify committee details and information";
$actions = [
    [
        'url' => 'committees.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Committees',
        'class' => 'btn-outline-light'
    ]
];

// Include the modern page header
include_once 'includes/modern_page_header.php';
?>

<div class="container-fluid px-4">
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-primary text-white">
            <h5 class="card-title mb-0">Edit <?php echo htmlspecialchars($committee['name']); ?></h5>
        </div>
        <div class="card-body">
            <form action="committees_actions.php" method="post">
                <input type="hidden" name="action" value="edit_committee">
                <input type="hidden" name="committee_id" value="<?php echo $committeeId; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="committee_name" class="form-label">Committee Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="committee_name" name="committee_name" value="<?php echo htmlspecialchars($committee['name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="committee_type" class="form-label">Committee Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="committee_type" name="committee_type" required>
                            <option value="">Select Type</option>
                            <option value="Standing" <?php echo $committee['type'] === 'Standing' ? 'selected' : ''; ?>>Standing Committee</option>
                            <option value="Ad Hoc" <?php echo $committee['type'] === 'Ad Hoc' ? 'selected' : ''; ?>>Ad Hoc Committee</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="committee_purpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="committee_purpose" name="committee_purpose" rows="2" required><?php echo htmlspecialchars($committee['purpose']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="committee_composition" class="form-label">Composition</label>
                    <textarea class="form-control" id="committee_composition" name="committee_composition" rows="4"><?php 
                        // Extract content from HTML for editing
                        if (!empty($committee['composition'])) {
                            $dom = new DOMDocument();
                            $dom->loadHTML(mb_convert_encoding($committee['composition'], 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                            $lis = $dom->getElementsByTagName('li');
                            $listItems = [];
                            foreach ($lis as $li) {
                                $listItems[] = $li->textContent;
                            }
                            echo htmlspecialchars(implode("\n", $listItems));
                        }
                    ?></textarea>
                    <small class="form-text text-muted">Enter each member/position on a separate line. HTML formatting will be added automatically.</small>
                </div>
                
                <div class="mb-3">
                    <label for="committee_responsibilities" class="form-label">Key Responsibilities</label>
                    <textarea class="form-control" id="committee_responsibilities" name="committee_responsibilities" rows="4"><?php 
                        // Extract content from HTML for editing
                        if (!empty($committee['responsibilities'])) {
                            $dom = new DOMDocument();
                            $dom->loadHTML(mb_convert_encoding($committee['responsibilities'], 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                            $lis = $dom->getElementsByTagName('li');
                            $listItems = [];
                            foreach ($lis as $li) {
                                $listItems[] = $li->textContent;
                            }
                            echo htmlspecialchars(implode("\n", $listItems));
                        }
                    ?></textarea>
                    <small class="form-text text-muted">Enter each responsibility on a separate line. HTML formatting will be added automatically.</small>
                </div>
                
                <div class="mb-3">
                    <label for="committee_description" class="form-label">Additional Information (Optional)</label>
                    <textarea class="form-control" id="committee_description" name="committee_description" rows="2"><?php echo htmlspecialchars($committee['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="border-top pt-3 d-flex justify-content-between">
                    <a href="committees.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Committee Members Section -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-success text-white">
            <h5 class="card-title mb-0">Committee Members</h5>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                <i class="fas fa-plus me-1"></i> Add Member
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($members)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No members have been added to this committee yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['position']); ?></td>
                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['department'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (!empty($member['email'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>">
                                                <i class="fas fa-envelope me-1"></i> Email
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($member['phone'])): ?>
                                            <?php if (!empty($member['email'])): ?> | <?php endif; ?>
                                            <a href="tel:<?php echo htmlspecialchars($member['phone']); ?>">
                                                <i class="fas fa-phone me-1"></i> Call
                                            </a>
                                        <?php endif; ?>
                                        <?php if (empty($member['email']) && empty($member['phone'])): ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-member" data-id="<?php echo $member['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-member" data-id="<?php echo $member['id']; ?>" data-name="<?php echo htmlspecialchars($member['name']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addMemberModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add Committee Member
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="committees_actions.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_member">
                    <input type="hidden" name="committee_id" value="<?php echo $committeeId; ?>">
                    
                    <div class="mb-3">
                        <label for="member_position" class="form-label">Position <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="member_position" name="member_position" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="member_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="member_name" name="member_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="member_department" class="form-label">Department/Faculty</label>
                        <input type="text" class="form-control" id="member_department" name="member_department">
                    </div>
                    
                    <div class="mb-3">
                        <label for="member_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="member_email" name="member_email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="member_phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="member_phone" name="member_phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus me-1"></i> Add Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Member Modal -->
<div class="modal fade" id="editMemberModal" tabindex="-1" aria-labelledby="editMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editMemberModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Member
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="committees_actions.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_member">
                    <input type="hidden" name="committee_id" value="<?php echo $committeeId; ?>">
                    <input type="hidden" name="member_id" id="editMemberId">

                    <div class="mb-3">
                        <label for="edit_member_position" class="form-label">Position <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_member_position" name="member_position" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_member_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_member_name" name="member_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_member_department" class="form-label">Department/Faculty</label>
                        <input type="text" class="form-control" id="edit_member_department" name="member_department">
                    </div>

                    <div class="mb-3">
                        <label for="edit_member_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="edit_member_email" name="member_email">
                    </div>

                    <div class="mb-3">
                        <label for="edit_member_phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="edit_member_phone" name="member_phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Member Confirmation Modal -->
<div class="modal fade" id="deleteMemberModal" tabindex="-1" aria-labelledby="deleteMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteMemberModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="memberNameToDelete">this member</span>?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <form action="committees_actions.php" method="post">
                    <input type="hidden" name="action" value="delete_member">
                    <input type="hidden" name="member_id" id="memberIdToDelete">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit member functionality
    const editMemberBtns = document.querySelectorAll('.edit-member');
    if (editMemberBtns.length > 0) {
        editMemberBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const memberId = this.getAttribute('data-id');

                // Fetch member data via AJAX
                fetch('committees_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_member&member_id=' + memberId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate the edit form
                        document.getElementById('editMemberId').value = data.member.id;
                        document.getElementById('edit_member_position').value = data.member.position || '';
                        document.getElementById('edit_member_name').value = data.member.name || '';
                        document.getElementById('edit_member_department').value = data.member.department || '';
                        document.getElementById('edit_member_email').value = data.member.email || '';
                        document.getElementById('edit_member_phone').value = data.member.phone || '';

                        // Show the modal
                        const editModal = new bootstrap.Modal(document.getElementById('editMemberModal'));
                        editModal.show();
                    } else {
                        alert('Error loading member data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading member data');
                });
            });
        });
    }

    // Delete member functionality
    const deleteMemberBtns = document.querySelectorAll('.delete-member');
    if (deleteMemberBtns.length > 0) {
        deleteMemberBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const memberId = this.getAttribute('data-id');
                const memberName = this.getAttribute('data-name');

                document.getElementById('memberIdToDelete').value = memberId;
                document.getElementById('memberNameToDelete').textContent = memberName;

                const deleteModal = new bootstrap.Modal(document.getElementById('deleteMemberModal'));
                deleteModal.show();
            });
        });
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
