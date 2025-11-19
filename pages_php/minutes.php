<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Require login for this page
requireLogin();
// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if minutes feature is enabled
if (!hasFeaturePermission('enable_minutes')) {
    $_SESSION['error'] = "Minutes feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = shouldUseAdminInterface();
$isMember = isMember();
$isSuperAdmin = isSuperAdmin();
$shouldUseAdminInterface = shouldUseAdminInterface(); // Includes super admin
$canManageMinutes = $shouldUseAdminInterface || $isMember; // Allow super admin, admin, and members to manage minutes

// Restrict access to super admin, admins and members only
if (!$shouldUseAdminInterface && !$isMember) {
    $_SESSION['error'] = "You do not have permission to access meeting minutes.";
    header("Location: senate.php");
    exit();
}

// Set page title
$pageTitle = "Meeting Minutes";

// Build query for minutes
$sql = "SELECT m.*
        FROM minutes m";
$params = [];
$whereAdded = false;

// Apply committee filter if provided
if (isset($_GET['committee']) && !empty($_GET['committee'])) {
    $sql .= " WHERE m.committee = ?";
    $params[] = $_GET['committee'];
    $whereAdded = true;
}

// Apply search filter if provided
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    if ($whereAdded) {
        $sql .= " AND (m.title LIKE ? OR m.summary LIKE ?)";
    } else {
        $sql .= " WHERE (m.title LIKE ? OR m.summary LIKE ?)";
        $whereAdded = true;
    }
    $params[] = $search;
    $params[] = $search;
}

// Order by meeting date (most recent first)
$sql .= " ORDER BY m.meeting_date DESC";

// Fetch minutes
$minutes = fetchAll($sql, $params);

// Get unique committees for dropdown
$committeeSql = "SELECT DISTINCT committee FROM minutes ORDER BY committee";
$committeesResult = fetchAll($committeeSql);
$committees = array_column($committeesResult, 'committee');

// Include header
require_once 'includes/header.php';

// Custom styles for this page
?>
<style>
/* Modern Minutes Card Styles */
.modern-minutes-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
}

.modern-minutes-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

/* Card Header */
.card-header-modern {
    position: relative;
    padding: 1.5rem;
    color: white;
    min-height: 80px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header-modern.approved {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.card-header-modern.draft {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.meeting-icon {
    font-size: 1.75rem;
    opacity: 0.8;
}

/* Card Body */
.card-body-modern {
    padding: 1.5rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.minutes-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 1rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.minutes-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
}

.meta-item i {
    width: 16px;
    color: #94a3b8;
}

.badge-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.modern-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.committee-badge {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
}

.attachment-badge {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: white;
}

.minutes-summary {
    color: #4a5568;
    line-height: 1.6;
    margin-bottom: 1rem;
    flex-grow: 1;
    font-size: 0.9rem;
}

.next-meeting-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8fafc;
    padding: 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
    color: #475569;
    border: 1px solid #e2e8f0;
    margin-bottom: 1rem;
}

.next-meeting-info i {
    color: #10b981;
}

/* Card Footer */
.card-footer-modern {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.primary-actions {
    flex: 1;
}

.btn-primary-modern {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    border: none;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.secondary-actions {
    display: flex;
    gap: 0.5rem;
    margin-left: 1rem;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: white;
    color: #64748b;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid #e2e8f0;
}

.action-btn:hover {
    background: #f1f5f9;
    color: #475569;
    transform: translateY(-2px);
    text-decoration: none;
}

.action-btn.delete-btn:hover {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}

/* Animation */
.animate-fadeIn {
    animation: fadeInUp 0.6s ease-out forwards;
    animation-delay: calc(var(--card-index) * 0.1s);
    opacity: 0;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-footer-modern {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .secondary-actions {
        margin-left: 0;
        justify-content: center;
    }

    .btn-primary-modern {
        justify-content: center;
    }
}

/* Keep existing pre styles for other content */
pre {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    white-space: pre-wrap;
    font-family: inherit;
}
</style>

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

    <script>
        document.body.classList.add('minutes-page');
    </script>

    <!-- Custom Minutes Header -->
    <div class="minutes-header animate__animated animate__fadeInDown">
        <div class="minutes-header-content">
            <div class="minutes-header-main">
                <h1 class="minutes-title">
                    <i class="fas fa-clipboard me-3"></i>
                    Meeting Minutes
                </h1>
                <p class="minutes-description">Access and manage official SRC meeting minutes and records</p>
            </div>
            <?php if ($canManageMinutes): ?>
            <div class="minutes-header-actions">
                <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#addMinutesModal">
                    <i class="fas fa-plus me-2"></i>New Minutes
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .minutes-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2.5rem 2rem;
        border-radius: 12px;
        margin-top: 60px;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .minutes-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .minutes-header-main {
        flex: 1;
        text-align: center;
    }

    .minutes-header .minutes-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 1rem 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.8rem;
        color: white;
    }

    .minutes-header .minutes-title i {
        font-size: 2.2rem;
        opacity: 0.9;
    }

    .minutes-description {
        margin: 0;
        opacity: 0.95;
        font-size: 1.2rem;
        font-weight: 400;
        line-height: 1.4;
    }

    .minutes-header-actions {
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
    }

    .btn-header-action:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 768px) {
        .minutes-header {
            padding: 2rem 1.5rem;
        }

        .minutes-header-content {
            flex-direction: column;
            align-items: center;
        }

        .minutes-header .minutes-title {
            font-size: 2rem;
            gap: 0.6rem;
        }

        .minutes-header .minutes-title i {
            font-size: 1.8rem;
        }

        .minutes-description {
            font-size: 1.1rem;
        }

        .minutes-header-actions {
            width: 100%;
            justify-content: center;
        }

        .btn-header-action {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
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
    
    /* Mobile Full-Width Optimization for Minutes Page */
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
        
        /* Ensure minutes header has border-radius on mobile */
        .header, .minutes-header {
            border-radius: 12px !important;
        }
        
        /* Ensure content cards extend full width */
        .card, .content-card, .modern-minutes-card {
            margin-left: 0 !important;
            margin-right: 0 !important;
            border-radius: 0 !important;
        }
    }
    </style>
                
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="content-card animate-fadeIn">
                <div class="content-card-header">
                    <h3 class="content-card-title"><i class="fas fa-info-circle me-2"></i> SRC Meeting Minutes Repository</h3>
                </div>
                <div class="content-card-body">
                    <p>Access and review all SRC meeting minutes. Minutes are available once they have been approved.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="content-card animate-fadeIn">
        <div class="content-card-header">
            <h3 class="content-card-title"><i class="fas fa-filter me-2"></i> Filter Minutes</h3>
        </div>
        <div class="content-card-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3 align-items-end">
                <div class="col-lg-6 col-md-8">
                    <div class="input-group">
                        <span class="input-group-text d-flex align-items-center justify-content-center" style="width: 45px;">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" name="search" placeholder="Search minutes..."
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <select class="form-select" id="committee" name="committee">
                        <option value="">All Committees</option>
                        <?php foreach ($committees as $committee): ?>
                        <option value="<?php echo htmlspecialchars($committee); ?>" <?php echo isset($_GET['committee']) && $_GET['committee'] === $committee ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($committee); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-1 col-md-6">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <div class="d-grid">
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Minutes Cards -->
    <div class="row mt-4">
        <?php if (empty($minutes)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> No meeting minutes found. 
                <?php if (isset($_GET['committee']) || isset($_GET['search'])): ?>
                Try changing your search criteria.
                <?php elseif ($isAdmin): ?>
                Click the "New Minutes" button to add meeting minutes.
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
            <?php 
            $cardIndex = 0;
            foreach ($minutes as $minute): 
                $cardIndex++;
            ?>
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="modern-minutes-card animate-fadeIn h-100" style="--card-index: <?php echo $cardIndex; ?>">
                    <!-- Card Header with Status -->
                    <div class="card-header-modern <?php echo $minute['status'] === 'Approved' ? 'approved' : 'draft'; ?>">
                        <?php if ($minute['status'] === 'Approved'): ?>
                        <div class="status-indicator approved">
                            <i class="fas fa-check-circle"></i>
                            <span>Approved</span>
                        </div>
                        <?php else: ?>
                        <div class="status-indicator draft">
                            <i class="fas fa-clock"></i>
                            <span>Draft</span>
                        </div>
                        <?php endif; ?>

                        <!-- Meeting Icon -->
                        <div class="meeting-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body-modern">
                        <!-- Title -->
                        <h3 class="minutes-title">
                            <?php echo htmlspecialchars($minute['title']); ?>
                        </h3>

                        <!-- Meta Information -->
                        <div class="minutes-meta">
                            <div class="meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo date('M j, Y', strtotime($minute['meeting_date'])); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-users-cog"></i>
                                <span><?php echo htmlspecialchars($minute['committee']); ?></span>
                            </div>
                        </div>

                        <!-- Status and Attachment Badges -->
                        <div class="badge-row">
                            <span class="modern-badge committee-badge">
                                <i class="fas fa-sitemap"></i>
                                <?php echo htmlspecialchars($minute['committee']); ?>
                            </span>
                            <?php if (!empty($minute['file_path'])): ?>
                            <span class="modern-badge attachment-badge">
                                <i class="fas fa-paperclip"></i>
                                Attachment
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Summary -->
                        <p class="minutes-summary">
                            <?php echo htmlspecialchars(substr($minute['summary'], 0, 120)); ?>
                            <?php echo strlen($minute['summary']) > 120 ? '...' : ''; ?>
                        </p>

                        <!-- Next Meeting Info -->
                        <?php if (!empty($minute['next_meeting_date'])): ?>
                        <div class="next-meeting-info">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Next Meeting: <?php echo date('M j, Y', strtotime($minute['next_meeting_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Card Footer with Actions -->
                    <div class="card-footer-modern">
                        <!-- Primary Action -->
                        <div class="primary-actions">
                            <a href="minutes_detail.php?id=<?php echo $minute['minutes_id']; ?>" class="btn-primary-modern">
                                <i class="fas fa-file-alt"></i>
                                <span>View Minutes</span>
                            </a>
                        </div>

                        <!-- Secondary Actions -->
                        <?php if ($canManageMinutes): ?>
                        <div class="secondary-actions">
                            <a href="minutes_edit.php?id=<?php echo $minute['minutes_id']; ?>" class="action-btn" title="Edit Minutes">
                                <i class="fas fa-edit"></i>
                            </a>

                            <a href="minutes_handler.php?action=delete&id=<?php echo $minute['minutes_id']; ?>"
                               class="action-btn delete-btn"
                               title="Delete Minutes"
                               onclick="return confirm('Are you sure you want to delete these minutes?')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
    </div>

    <!-- Add Minutes Modal -->
    <?php if ($canManageMinutes): ?>
    <div class="modal fade" id="addMinutesModal" tabindex="-1" aria-labelledby="addMinutesModalLabel" aria-hidden="true">
        
<?php endif; ?><div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMinutesModalLabel">Add New Meeting Minutes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="minutes_handler.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="title" class="form-label">Meeting Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-4">
                                <label for="meeting_date" class="form-label">Meeting Date</label>
                                <input type="date" class="form-control" id="meeting_date" name="meeting_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" required>
                            </div>
                            <div class="col-md-6">
                                <label for="committee" class="form-label">Committee</label>
                                <select class="form-select" id="committee" name="committee" required>
                                    <option value="">Select Committee</option>
                                    <?php foreach ($committees as $committee): ?>
                                        <option value="<?php echo htmlspecialchars($committee); ?>">
                                            <?php echo htmlspecialchars($committee); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="new">Add New Committee</option>
                                </select>
                                <div id="newCommitteeField" class="mt-2" style="display: none;">
                                    <input type="text" class="form-control" id="new_committee" name="new_committee" placeholder="Enter new committee name">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="attendees" class="form-label">Attendees</label>
                            <textarea class="form-control" id="attendees" name="attendees" rows="2" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="apologies" class="form-label">Apologies</label>
                            <textarea class="form-control" id="apologies" name="apologies" rows="1"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="agenda" class="form-label">Agenda</label>
                            <textarea class="form-control" id="agenda" name="agenda" rows="3" required></textarea>
                            <small class="form-text">Enter each agenda item on a new line.</small>
                        </div>

                        <div class="mb-3">
                            <label for="summary" class="form-label">Summary</label>
                            <textarea class="form-control" id="summary" name="summary" rows="2" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="decisions" class="form-label">Decisions</label>
                            <textarea class="form-control" id="decisions" name="decisions" rows="3" required></textarea>
                            <small class="form-text">Enter each decision on a new line.</small>
                        </div>

                        <div class="mb-3">
                            <label for="actions" class="form-label">Action Items</label>
                            <textarea class="form-control" id="actions" name="actions" rows="3" required></textarea>
                            <small class="form-text">Enter each action item on a new line.</small>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="next_meeting_date" class="form-label">Next Meeting Date</label>
                                <input type="date" class="form-control" id="next_meeting_date" name="next_meeting_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Draft">Draft</option>
                                    <option value="Approved">Approved</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="minutes_file" class="form-label">Attach Minutes File (Optional)</label>
                            <input type="file" class="form-control" id="minutes_file" name="minutes_file">
                            <div class="form-text">Accepted file types: PDF, DOC, DOCX, TXT (Max size: 5MB)</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_minutes">Save Minutes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle new committee selection in add form
    const committeeSelect = document.getElementById('committee');
    const newCommitteeField = document.getElementById('newCommitteeField');

    if (committeeSelect && newCommitteeField) {
        committeeSelect.addEventListener('change', function() {
            if (this.value === 'new') {
                newCommitteeField.style.display = 'block';
                document.getElementById('new_committee').setAttribute('required', 'required');
            } else {
                newCommitteeField.style.display = 'none';
                document.getElementById('new_committee').removeAttribute('required');
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
