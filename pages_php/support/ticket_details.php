<?php
// Include simple authentication and required files
require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/db_config.php';

// Check if user should use admin interface or is member
$shouldUseAdminInterface = shouldUseAdminInterface();
$isMember = isMember();

// Require admin interface access or member access for this page
if (!$shouldUseAdminInterface && !$isMember) {
    header('Location: ../../access_denied.php');
    exit();
}

// Get current user info
$currentUser = getCurrentUser();
$isAdmin = $shouldUseAdminInterface;

// Get ticket ID
$ticketId = (int)($_GET['ticket_id'] ?? 0);

if ($ticketId <= 0) {
    echo '<div class="alert alert-danger">Invalid ticket ID.</div>';
    exit;
}

// Get ticket details with user information
$sql = "SELECT st.*, 
               u.first_name, u.last_name, u.email, u.role, u.phone,
               assigned_user.first_name as assigned_first_name, 
               assigned_user.last_name as assigned_last_name
        FROM support_tickets st
        JOIN users u ON st.user_id = u.user_id
        LEFT JOIN users assigned_user ON st.assigned_to = assigned_user.user_id
        WHERE st.ticket_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $ticketId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ticket = mysqli_fetch_assoc($result);

if (!$ticket) {
    echo '<div class="alert alert-danger">Ticket not found.</div>';
    exit;
}

// Get ticket responses
$responses_sql = "SELECT str.*, u.first_name, u.last_name, u.role
                  FROM support_ticket_responses str
                  JOIN users u ON str.user_id = u.user_id
                  WHERE str.ticket_id = ?
                  ORDER BY str.created_at ASC";

$responses_stmt = mysqli_prepare($conn, $responses_sql);
mysqli_stmt_bind_param($responses_stmt, "i", $ticketId);
mysqli_stmt_execute($responses_stmt);
$responses_result = mysqli_stmt_get_result($responses_stmt);
$responses = [];
while ($row = mysqli_fetch_assoc($responses_result)) {
    $responses[] = $row;
}

// Get staff members for assignment dropdown
$staff_sql = "SELECT user_id, first_name, last_name FROM users WHERE role IN ('admin', 'member') ORDER BY first_name, last_name";
$staff_result = mysqli_query($conn, $staff_sql);
$staff_members = [];
while ($row = mysqli_fetch_assoc($staff_result)) {
    $staff_members[] = $row;
}
?>

<style>
.ticket-detail-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.priority-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 500;
}

.priority-urgent {
    background: #f5c6cb;
    color: #721c24;
}

.priority-high {
    background: #ffeaa7;
    color: #856404;
}

.priority-medium {
    background: #fff3cd;
    color: #856404;
}

.priority-low {
    background: #d4edda;
    color: #155724;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-open {
    background: #fff3cd;
    color: #856404;
}

.status-in_progress {
    background: #cce5ff;
    color: #004085;
}

.status-waiting_response {
    background: #e2e3e5;
    color: #383d41;
}

.status-resolved {
    background: #d4edda;
    color: #155724;
}

.status-closed {
    background: #f8d7da;
    color: #721c24;
}

.response-item {
    border-left: 4px solid #e9ecef;
    padding: 1rem;
    margin-bottom: 1rem;
    background: #f8f9fa;
    border-radius: 0 10px 10px 0;
}

.response-item.staff-response {
    border-left-color: #007bff;
    background: #e7f3ff;
}

.response-item.customer-response {
    border-left-color: #28a745;
    background: #e8f5e8;
}

.action-buttons .btn {
    border-radius: 20px;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}
</style>

<div class="ticket-detail-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h4 class="mb-2">Ticket #<?php echo $ticket['ticket_id']; ?></h4>
            <h5 class="mb-3"><?php echo htmlspecialchars($ticket['title']); ?></h5>
            <div class="d-flex gap-2">
                <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                    <?php echo ucfirst($ticket['priority']); ?> Priority
                </span>
                <span class="status-badge status-<?php echo $ticket['status']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                </span>
            </div>
        </div>
        <div class="text-end">
            <small>Created: <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></small><br>
            <small>Updated: <?php echo date('M j, Y g:i A', strtotime($ticket['updated_at'])); ?></small>
            <?php if ($ticket['resolved_at']): ?>
                <br><small>Resolved: <?php echo date('M j, Y g:i A', strtotime($ticket['resolved_at'])); ?></small>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Customer Information -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <strong>Name:</strong> <?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($ticket['email']); ?><br>
                <strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($ticket['role'])); ?>
            </div>
            <div class="col-md-6">
                <?php if ($ticket['phone']): ?>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($ticket['phone']); ?><br>
                <?php endif; ?>
                <strong>Category:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $ticket['category']))); ?><br>
                <?php if ($ticket['assigned_first_name']): ?>
                    <strong>Assigned to:</strong> <?php echo htmlspecialchars($ticket['assigned_first_name'] . ' ' . $ticket['assigned_last_name']); ?>
                <?php else: ?>
                    <strong>Assigned to:</strong> <span class="text-muted">Unassigned</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Ticket Description -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Issue Description</h6>
    </div>
    <div class="card-body">
        <p><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-tools me-2"></i>Quick Actions</h6>
    </div>
    <div class="card-body">
        <div class="action-buttons">
            <?php if (!$ticket['assigned_to']): ?>
                <button class="btn btn-success btn-sm" onclick="assignTicket(<?php echo $ticket['ticket_id']; ?>, <?php echo $currentUser['user_id']; ?>)">
                    <i class="fas fa-user-plus me-1"></i>Assign to Me
                </button>
            <?php endif; ?>
            
            <?php if ($ticket['status'] !== 'in_progress'): ?>
                <button class="btn btn-primary btn-sm" onclick="updateStatus(<?php echo $ticket['ticket_id']; ?>, 'in_progress')">
                    <i class="fas fa-play me-1"></i>Start Working
                </button>
            <?php endif; ?>
            
            <?php if ($ticket['status'] !== 'waiting_response'): ?>
                <button class="btn btn-info btn-sm" onclick="updateStatus(<?php echo $ticket['ticket_id']; ?>, 'waiting_response')">
                    <i class="fas fa-clock me-1"></i>Waiting Response
                </button>
            <?php endif; ?>
            
            <?php if ($ticket['status'] !== 'resolved'): ?>
                <button class="btn btn-warning btn-sm" onclick="updateStatus(<?php echo $ticket['ticket_id']; ?>, 'resolved')">
                    <i class="fas fa-check me-1"></i>Mark Resolved
                </button>
            <?php endif; ?>
            
            <button class="btn btn-secondary btn-sm" onclick="startChat(<?php echo $ticket['user_id']; ?>)">
                <i class="fas fa-comments me-1"></i>Start Live Chat
            </button>
        </div>
        
        <!-- Assignment Form -->
        <div class="row mt-3">
            <div class="col-md-6">
                <label for="assign_to" class="form-label">Reassign Ticket:</label>
                <div class="input-group">
                    <select class="form-select" id="assign_to">
                        <option value="">Select staff member...</option>
                        <?php foreach ($staff_members as $staff): ?>
                            <option value="<?php echo $staff['user_id']; ?>" 
                                    <?php echo ($ticket['assigned_to'] == $staff['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-primary" onclick="assignTicket(<?php echo $ticket['ticket_id']; ?>, document.getElementById('assign_to').value)">
                        <i class="fas fa-user-check"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Responses -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Responses (<?php echo count($responses); ?>)</h6>
    </div>
    <div class="card-body">
        <?php if (empty($responses)): ?>
            <p class="text-muted">No responses yet.</p>
        <?php else: ?>
            <?php foreach ($responses as $response): ?>
                <div class="response-item <?php echo $response['is_staff_response'] ? 'staff-response' : 'customer-response'; ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <strong>
                            <?php echo htmlspecialchars($response['first_name'] . ' ' . $response['last_name']); ?>
                            <?php if ($response['is_staff_response']): ?>
                                <span class="badge bg-primary ms-1">Staff</span>
                            <?php else: ?>
                                <span class="badge bg-success ms-1">Customer</span>
                            <?php endif; ?>
                        </strong>
                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($response['created_at'])); ?></small>
                    </div>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($response['message'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Response -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-reply me-2"></i>Add Response</h6>
    </div>
    <div class="card-body">
        <form onsubmit="addTicketResponse(event, <?php echo $ticket['ticket_id']; ?>)">
            <div class="mb-3">
                <textarea class="form-control" id="response_<?php echo $ticket['ticket_id']; ?>" rows="4" 
                          placeholder="Type your response here..." required></textarea>
            </div>
            <div class="d-flex justify-content-between">
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Send Response
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="loadQuickResponse(<?php echo $ticket['ticket_id']; ?>)">
                        <i class="fas fa-bolt me-1"></i>Quick Response
                    </button>
                </div>
                <div>
                    <label class="form-check-label me-3">
                        <input class="form-check-input me-1" type="checkbox" id="close_after_response">
                        Mark as resolved after sending
                    </label>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function assignTicket(ticketId, userId) {
    if (!userId) {
        alert('Please select a staff member to assign the ticket to.');
        return;
    }
    
    if (confirm('Are you sure you want to assign this ticket?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'tickets.php';
        form.innerHTML = `
            <input type="hidden" name="action" value="assign_ticket">
            <input type="hidden" name="ticket_id" value="${ticketId}">
            <input type="hidden" name="assigned_to" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function updateStatus(ticketId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'tickets.php';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="ticket_id" value="${ticketId}">
        <input type="hidden" name="status" value="${status}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function addTicketResponse(event, ticketId) {
    event.preventDefault();
    
    const message = document.getElementById(`response_${ticketId}`).value.trim();
    if (!message) {
        alert('Please enter a response message.');
        return;
    }
    
    const closeAfter = document.getElementById('close_after_response').checked;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'tickets.php';
    form.innerHTML = `
        <input type="hidden" name="action" value="add_response">
        <input type="hidden" name="ticket_id" value="${ticketId}">
        <input type="hidden" name="message" value="${message}">
        ${closeAfter ? '<input type="hidden" name="close_after" value="1">' : ''}
    `;
    document.body.appendChild(form);
    form.submit();
}

function startChat(userId) {
    window.open(`live-chat.php?user_id=${userId}`, 'livechat', 'width=800,height=600,scrollbars=yes,resizable=yes');
}

function loadQuickResponse(ticketId) {
    // This could be expanded to show a dropdown of quick responses
    const responses = [
        "Thank you for contacting us. I'm looking into your issue right now.",
        "Could you please provide more details about the problem you're experiencing?",
        "I understand your concern. Let me check this for you.",
        "This issue has been resolved. Please let us know if you need any further assistance.",
        "Thank you for your patience. The issue should now be fixed."
    ];
    
    const response = prompt("Select a quick response:\n" + responses.map((r, i) => `${i+1}. ${r}`).join('\n') + "\n\nEnter number (1-5):");
    
    if (response && response >= 1 && response <= 5) {
        document.getElementById(`response_${ticketId}`).value = responses[response - 1];
    }
}
</script>
