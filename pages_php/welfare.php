<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Include auto notifications system
require_once __DIR__ . '/includes/auto_notifications.php';

// Require login for this page
requireLogin();

// Check if welfare feature is enabled
if (!hasFeaturePermission('enable_welfare')) {
    header('Location: ../dashboard.php?error=feature_disabled');
    exit();
}

// Check user roles - use unified admin interface check for super admin
$isAdmin = shouldUseAdminInterface();
$isMember = isMember();
$isSuperAdmin = isSuperAdmin();
$shouldUseAdminInterface = shouldUseAdminInterface(); // This includes super admin
$isStudent = !$shouldUseAdminInterface && !$isMember;

// Get welfare requests from database
$sql = "SELECT wr.*, CONCAT(u.first_name, ' ', u.last_name) as requester_name, u.email as requester_email
        FROM welfare_requests wr
        LEFT JOIN users u ON wr.user_id = u.user_id
        ORDER BY wr.created_at DESC";
$welfareRequests = fetchAll($sql);

// Ensure $welfareRequests is an array
if (!is_array($welfareRequests)) {
    $welfareRequests = [];
}

// Get welfare statistics
$totalRequests = is_array($welfareRequests) ? count($welfareRequests) : 0;

// Calculate pending requests with proper error handling
$pendingRequestsArray = is_array($welfareRequests) ? array_filter($welfareRequests, function($req) {
    return is_array($req) && isset($req['status']) && $req['status'] === 'pending';
}) : [];
$pendingRequests = count($pendingRequestsArray);

// Calculate approved requests with proper error handling
$approvedRequestsArray = is_array($welfareRequests) ? array_filter($welfareRequests, function($req) {
    return is_array($req) && isset($req['status']) && $req['status'] === 'approved';
}) : [];
$approvedRequests = count($approvedRequestsArray);

// Calculate rejected requests with proper error handling
$rejectedRequestsArray = is_array($welfareRequests) ? array_filter($welfareRequests, function($req) {
    return is_array($req) && isset($req['status']) && $req['status'] === 'rejected';
}) : [];
$rejectedRequests = count($rejectedRequestsArray);

// Ensure all statistics are integers and handle any unexpected values
$totalRequests = is_numeric($totalRequests) ? (int) $totalRequests : 0;
$pendingRequests = is_numeric($pendingRequests) ? (int) $pendingRequests : 0;
$approvedRequests = is_numeric($approvedRequests) ? (int) $approvedRequests : 0;
$rejectedRequests = is_numeric($rejectedRequests) ? (int) $rejectedRequests : 0;

// Debug: Check if variables are properly set (remove this after testing)
if (!is_numeric($totalRequests) || !is_numeric($pendingRequests) || !is_numeric($approvedRequests) || !is_numeric($rejectedRequests)) {
    error_log("Welfare statistics error - totalRequests: " . print_r($totalRequests, true) .
              ", pendingRequests: " . print_r($pendingRequests, true) .
              ", approvedRequests: " . print_r($approvedRequests, true) .
              ", rejectedRequests: " . print_r($rejectedRequests, true));
}

// Get welfare announcements
$announcementsSql = "SELECT * FROM welfare_announcements WHERE status = 'active' ORDER BY created_at DESC LIMIT 5";
$announcements = fetchAll($announcementsSql);

// Set page title
$pageTitle = "Student Welfare - SRC Management System";

// Include header
require_once 'includes/header.php';
?>

<!-- Page Content -->
<div class="container-fluid" style="margin-top: 60px;">
    <?php
    // Set up modern page header variables
    $pageTitle = "Student Welfare";
    $pageIcon = "fa-heart";
    $pageDescription = "Supporting student well-being and assistance programs";
    $actions = [];
    
    if ($shouldUseAdminInterface || $isMember) {
        $actions[] = [
            'url' => '#',
            'icon' => 'fa-plus',
            'text' => 'New Announcement',
            'class' => 'btn-outline-light',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#newAnnouncementModal'
        ];
        $actions[] = [
            'url' => '#',
            'icon' => 'fa-chart-bar',
            'text' => 'View Reports',
            'class' => 'btn-outline-light',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#reportsModal'
        ];
        $actions[] = [
            'url' => 'welfare_settings.php',
            'icon' => 'fa-cog',
            'text' => 'Settings',
            'class' => 'btn-outline-light'
        ];
    }
    
    if ($isStudent) {
        $actions[] = [
            'url' => '#',
            'icon' => 'fa-plus',
            'text' => 'Submit Request',
            'class' => 'btn-outline-light',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#submitRequestModal'
        ];
    }
    
    // Include modern page header
    include 'includes/modern_page_header.php';
    ?>

    <!-- Display success/error messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards (Admin/Member View) -->
    <?php if ($shouldUseAdminInterface || $isMember): ?>
    <?php
    // Final safety check to ensure all variables are integers before display
    $totalRequests = is_array($totalRequests) ? 0 : (int) $totalRequests;
    $pendingRequests = is_array($pendingRequests) ? 0 : (int) $pendingRequests;
    $approvedRequests = is_array($approvedRequests) ? 0 : (int) $approvedRequests;
    $rejectedRequests = is_array($rejectedRequests) ? 0 : (int) $rejectedRequests;
    ?>
    <div class="row mb-4 g-3">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card stat-primary">
                <div class="stat-icon-wrapper">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h2 class="stat-number" data-count="<?php echo $totalRequests; ?>">0</h2>
                    <p class="stat-label">Total Requests</p>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>All Time</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card stat-warning">
                <div class="stat-icon-wrapper">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h2 class="stat-number" data-count="<?php echo $pendingRequests; ?>">0</h2>
                    <p class="stat-label">Pending</p>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-hourglass-half"></i>
                    <span>In Queue</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card stat-success">
                <div class="stat-icon-wrapper">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h2 class="stat-number" data-count="<?php echo $approvedRequests; ?>">0</h2>
                    <p class="stat-label">Approved</p>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-thumbs-up"></i>
                    <span>Completed</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card stat-danger">
                <div class="stat-icon-wrapper">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h2 class="stat-number" data-count="<?php echo $rejectedRequests; ?>">0</h2>
                    <p class="stat-label">Rejected</p>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-ban"></i>
                    <span>Declined</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Welfare Announcements -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Welfare Announcements</h5>
                    <?php if ($shouldUseAdminInterface || $isMember): ?>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
                        <i class="fas fa-plus me-1"></i>Add
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($announcements)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-bullhorn fa-3x mb-3"></i>
                            <p>No announcements available</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <h6 class="fw-bold"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                            <p class="mb-1"><?php echo htmlspecialchars($announcement['content']); ?></p>
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <?php if ($isStudent): ?>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#submitRequestModal">
                                <i class="fas fa-plus me-2"></i>Submit Welfare Request
                            </button>
                            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#myRequestsModal">
                                <i class="fas fa-list me-2"></i>View My Requests
                            </button>
                            <a href="welfare_guidelines.php" class="btn btn-outline-info">
                                <i class="fas fa-info-circle me-2"></i>Welfare Guidelines
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
                                <i class="fas fa-bullhorn me-2"></i>Create Announcement
                            </button>
                            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#reportsModal">
                                <i class="fas fa-chart-bar me-2"></i>Generate Reports
                            </button>
                            <a href="welfare_settings.php" class="btn btn-outline-info">
                                <i class="fas fa-cog me-2"></i>Welfare Settings
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Welfare Requests Table (Admin/Member View) -->
    <?php if ($shouldUseAdminInterface || $isMember): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Welfare Requests</h5>
        </div>
        <div class="card-body">
            <?php if (empty($welfareRequests)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                    <p>No welfare requests found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Requester</th>
                                <th>Type</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($welfareRequests as $request): ?>
                            <tr>
                                <td>#<?php echo $request['request_id']; ?></td>
                                <td><?php echo htmlspecialchars($request['requester_name'] ?? 'Unknown'); ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($request['request_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'in_progress' => 'info'
                                    ];
                                    $class = $statusClass[$request['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $class; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewRequest(<?php echo $request['request_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-outline-success" 
                                            onclick="updateRequestStatus(<?php echo $request['request_id']; ?>, 'approved')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="updateRequestStatus(<?php echo $request['request_id']; ?>, 'rejected')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Submit Request Modal (Students) -->
<?php if ($isStudent): ?>
<div class="modal fade" id="submitRequestModal" tabindex="-1" aria-labelledby="submitRequestModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="submitRequestModalLabel">Submit Welfare Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="welfare_handler.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="submit_request">

                    <div class="mb-3">
                        <label for="request_type" class="form-label">Request Type</label>
                        <select class="form-select" id="request_type" name="request_type" required>
                            <option value="">Select request type</option>
                            <option value="financial_assistance">Financial Assistance</option>
                            <option value="medical_support">Medical Support</option>
                            <option value="academic_support">Academic Support</option>
                            <option value="accommodation">Accommodation</option>
                            <option value="emergency_support">Emergency Support</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required
                                  placeholder="Please provide detailed information about your request..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="urgency" class="form-label">Urgency Level</label>
                        <select class="form-select" id="urgency" name="urgency" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="supporting_documents" class="form-label">Supporting Documents (Optional)</label>
                        <input type="file" class="form-control" id="supporting_documents" name="supporting_documents[]" multiple>
                        <div class="form-text">You can upload multiple files (PDF, DOC, JPG, PNG)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- My Requests Modal (Students) -->
<div class="modal fade" id="myRequestsModal" tabindex="-1" aria-labelledby="myRequestsModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myRequestsModalLabel">My Welfare Requests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="myRequestsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- New Announcement Modal (Admin/Member) -->
<?php if ($shouldUseAdminInterface || $isMember): ?>
<div class="modal fade" id="newAnnouncementModal" tabindex="-1" aria-labelledby="newAnnouncementModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newAnnouncementModalLabel">Create Welfare Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="welfare_handler.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_announcement">

                    <div class="mb-3">
                        <label for="announcement_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="announcement_title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="announcement_content" class="form-label">Content</label>
                        <textarea class="form-control" id="announcement_content" name="content" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="announcement_priority" class="form-label">Priority</label>
                        <select class="form-select" id="announcement_priority" name="priority">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reports Modal (Admin/Member) -->
<div class="modal fade" id="reportsModal" tabindex="-1" aria-labelledby="reportsModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportsModalLabel">Welfare Reports</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-pie fa-3x text-primary mb-3"></i>
                                <h6>Request Statistics</h6>
                                <p class="text-muted">Generate detailed statistics report</p>
                                <button class="btn btn-primary" onclick="generateReport('statistics')">Generate</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-file-export fa-3x text-success mb-3"></i>
                                <h6>Export Data</h6>
                                <p class="text-muted">Export welfare data to Excel</p>
                                <button class="btn btn-success" onclick="exportData()">Export</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Request Details Modal -->
<div class="modal fade" id="requestDetailsModal" tabindex="-1" aria-labelledby="requestDetailsModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestDetailsModalLabel">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="requestDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// View request details
function viewRequest(requestId) {
    const modal = document.getElementById('requestDetailsModal');
    const contentDiv = document.getElementById('requestDetailsContent');

    if (modal && contentDiv) {
        // Show loading spinner
        contentDiv.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading request details...</p>
            </div>
        `;

        // Show modal using Bootstrap 5 API
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        fetch('welfare_handler.php?action=get_request&id=' + requestId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    contentDiv.innerHTML = data.html;
                } else {
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.message || 'Error loading request details'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading request details:', error);
                contentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading request details. Please try again.
                    </div>
                `;
            });
    }
}

// Update request status
function updateRequestStatus(requestId, status) {
    if (confirm('Are you sure you want to ' + status + ' this request?')) {
        fetch('welfare_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update_status&request_id=' + requestId + '&status=' + status
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating request status');
            }
        })
        .catch(error => {
            alert('Error updating request status');
        });
    }
}

// Load my requests (for students)
<?php if ($isStudent): ?>
document.addEventListener('DOMContentLoaded', function() {
    const myRequestsModal = document.getElementById('myRequestsModal');
    if (myRequestsModal) {
        myRequestsModal.addEventListener('show.bs.modal', function() {
            const contentDiv = document.getElementById('myRequestsContent');

            // Show loading spinner
            contentDiv.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading your requests...</p>
                </div>
            `;

            fetch('welfare_handler.php?action=get_my_requests')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        contentDiv.innerHTML = data.html;
                    } else {
                        contentDiv.innerHTML = `
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                ${data.message || 'No requests found'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading requests:', error);
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading requests. Please try again.
                        </div>
                    `;
                });
        });
    }
});
<?php endif; ?>

// Generate reports
function generateReport(type) {
    window.open('welfare_handler.php?action=generate_report&type=' + type, '_blank');
}

// Export data
function exportData() {
    window.open('welfare_handler.php?action=export_data', '_blank');
}
</script>

<style>
/* Modern Statistics Cards */
.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: none;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.5) 50%, rgba(255,255,255,0) 100%);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.stat-card.stat-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-card.stat-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.stat-card.stat-success {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.stat-card.stat-danger {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.stat-icon-wrapper {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}

.stat-icon i {
    font-size: 1.8rem;
    color: white;
}

.stat-content {
    margin-bottom: 1rem;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    line-height: 1;
}

.stat-label {
    font-size: 1rem;
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-weight: 500;
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    opacity: 0.9;
}

.stat-trend i {
    font-size: 0.75rem;
}

/* Enhanced Announcements Card */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 15px 15px 0 0 !important;
    padding: 1.25rem 1.5rem;
}

.card-header h5 {
    margin: 0;
    font-weight: 600;
}

.card-body {
    padding: 1.5rem;
}

/* Announcements List */
.border-bottom {
    border-color: #e9ecef !important;
    padding: 1.25rem 0;
    transition: all 0.3s ease;
}

.border-bottom:hover {
    background: #f8f9fa;
    padding: 1.25rem 1rem;
    margin: 0 -1rem;
    border-radius: 8px;
}

.border-bottom h6 {
    color: #333;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.border-bottom p {
    color: #666;
    line-height: 1.6;
}

/* Enhanced Quick Actions */
.d-grid .btn {
    padding: 1rem;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.d-grid .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.d-grid .btn-primary:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.d-grid .btn-outline-secondary {
    border-color: #e9ecef;
    color: #666;
}

.d-grid .btn-outline-secondary:hover {
    background: #667eea;
    border-color: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
}

.d-grid .btn-outline-info {
    border-color: #e9ecef;
    color: #666;
}

.d-grid .btn-outline-info:hover {
    background: #4facfe;
    border-color: #4facfe;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(79, 172, 254, 0.3);
}

/* Enhanced Table Styling */
.table-responsive {
    border-radius: 10px;
    overflow: hidden;
}

.table {
    margin: 0;
}

.table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.table thead th {
    border: none;
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.5px;
}

.table tbody tr {
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    background: #f8f9fa;
    transform: scale(1.01);
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-color: #e9ecef;
}

.table .badge {
    padding: 0.5rem 1rem;
    font-weight: 500;
    border-radius: 20px;
}

.table .btn-sm {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.table .btn-sm:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Counter Animation */
@keyframes countUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.stat-number {
    animation: countUp 0.6s ease-out;
}

/* Mobile Full-Width Optimization for Welfare Page */
@media (max-width: 991px) {
    [class*="col-md-"], [class*="col-sm-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    
    .row.g-3 {
        gap: 0.75rem !important;
    }
    
    /* Ensure page header has border-radius on mobile */
    .header, .page-hero, .modern-page-header {
        border-radius: 12px !important;
    }
    
    /* Stat cards on mobile */
    .stat-card {
        margin-bottom: 1rem;
        border-radius: 0 !important;
    }
    
    /* Ensure content cards extend full width */
    .card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
        margin-bottom: 1rem;
    }
    
    .card-header {
        border-radius: 0 !important;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
    }
    
    .stat-icon i {
        font-size: 1.5rem;
    }
}

@media (max-width: 576px) {
    .stat-number {
        font-size: 1.75rem;
    }
    
    .stat-label {
        font-size: 0.9rem;
    }
}
</style>

<script>
// Animated Counter for Statistics
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.stat-number');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-count'));
        const duration = 1500; // Animation duration in ms
        const increment = target / (duration / 16); // 60 FPS
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target;
            }
        };
        
        // Start animation when element is in view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(counter);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
