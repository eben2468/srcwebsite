<?php
/**
 * Support Dashboard Widget
 * Shows support ticket statistics for admin/member users
 */

// Include required files
require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/db_config.php';

// Include auth functions
require_once __DIR__ . '/../../includes/auth_functions.php';

// Check if user should use admin interface or is member
$shouldUseAdminInterface = shouldUseAdminInterface();
$isMember = isMember();

if (!$shouldUseAdminInterface && !$isMember) {
    return; // Don't show widget for regular users
}

// Get current user
$currentUser = getCurrentUser();

// Get support ticket statistics
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'waiting_response' THEN 1 ELSE 0 END) as waiting_response_count,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE WHEN assigned_to IS NULL THEN 1 ELSE 0 END) as unassigned_count,
                SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
                SUM(CASE WHEN assigned_to = ? THEN 1 ELSE 0 END) as my_tickets_count,
                SUM(CASE WHEN assigned_to = ? AND status = 'waiting_response' THEN 1 ELSE 0 END) as my_waiting_count
              FROM support_tickets";

$stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stmt, "ii", $currentUser['user_id'], $currentUser['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result);

// Get recent tickets
$recent_sql = "SELECT st.ticket_id, st.title, st.priority, st.status, st.created_at,
                      u.first_name, u.last_name
               FROM support_tickets st
               JOIN users u ON st.user_id = u.user_id
               ORDER BY st.created_at DESC
               LIMIT 5";

$recent_result = mysqli_query($conn, $recent_sql);
$recent_tickets = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    $recent_tickets[] = $row;
}

// Get active chat sessions count
$chat_sql = "SELECT COUNT(*) as active_chats FROM chat_sessions WHERE status = 'active'";
$chat_result = mysqli_query($conn, $chat_sql);
$chat_data = mysqli_fetch_assoc($chat_result);
$active_chats = $chat_data['active_chats'] ?? 0;
?>

<style>
.support-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.support-widget h5 {
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.support-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-item {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.stat-item:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.75rem;
    opacity: 0.9;
}

.urgent-stat {
    background: rgba(220, 53, 69, 0.2);
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.urgent-stat .stat-number {
    color: #ffcccb;
    animation: pulse 2s infinite;
}

.recent-tickets {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 1rem;
    backdrop-filter: blur(10px);
}

.recent-ticket-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.recent-ticket-item:last-child {
    border-bottom: none;
}

.ticket-info {
    flex: 1;
}

.ticket-title {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.ticket-meta {
    font-size: 0.75rem;
    opacity: 0.8;
}

.priority-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
    font-size: 0.625rem;
    font-weight: 500;
}

.priority-urgent {
    background: rgba(220, 53, 69, 0.8);
    color: white;
}

.priority-high {
    background: rgba(255, 193, 7, 0.8);
    color: #333;
}

.priority-medium {
    background: rgba(13, 202, 240, 0.8);
    color: white;
}

.priority-low {
    background: rgba(25, 135, 84, 0.8);
    color: white;
}

.widget-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.widget-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.widget-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
    text-decoration: none;
    transform: translateY(-1px);
}

.widget-btn i {
    font-size: 0.875rem;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

@media (max-width: 768px) {
    .support-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .widget-actions {
        flex-direction: column;
    }
    
    .widget-btn {
        justify-content: center;
    }
}
</style>

<div class="support-widget">
    <h5>
        <span><i class="fas fa-headset me-2"></i>Support Center</span>
        <span class="badge bg-light text-dark"><?php echo $stats['total']; ?> Total</span>
    </h5>
    
    <div class="support-stats">
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['open_count']; ?></div>
            <div class="stat-label">Open</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['in_progress_count']; ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['unassigned_count']; ?></div>
            <div class="stat-label">Unassigned</div>
        </div>
        
        <div class="stat-item urgent-stat">
            <div class="stat-number"><?php echo $stats['urgent_count']; ?></div>
            <div class="stat-label">Urgent</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['my_tickets_count']; ?></div>
            <div class="stat-label">My Tickets</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-number"><?php echo $active_chats; ?></div>
            <div class="stat-label">Live Chats</div>
        </div>
    </div>
    
    <?php if (!empty($recent_tickets)): ?>
    <div class="recent-tickets">
        <h6 class="mb-3"><i class="fas fa-clock me-2"></i>Recent Tickets</h6>
        <?php foreach ($recent_tickets as $ticket): ?>
            <div class="recent-ticket-item">
                <div class="ticket-info">
                    <div class="ticket-title">
                        #<?php echo $ticket['ticket_id']; ?> - <?php echo htmlspecialchars(substr($ticket['title'], 0, 30)) . (strlen($ticket['title']) > 30 ? '...' : ''); ?>
                    </div>
                    <div class="ticket-meta">
                        <?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?> • 
                        <?php echo date('M j, g:i A', strtotime($ticket['created_at'])); ?>
                    </div>
                </div>
                <div>
                    <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                        <?php echo ucfirst($ticket['priority']); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="widget-actions">
        <a href="pages_php/support/tickets.php" class="widget-btn">
            <i class="fas fa-ticket-alt"></i>
            View All Tickets
        </a>
        
        <?php if ($stats['unassigned_count'] > 0): ?>
        <a href="pages_php/support/tickets.php?assigned=unassigned" class="widget-btn">
            <i class="fas fa-user-plus"></i>
            Assign Tickets (<?php echo $stats['unassigned_count']; ?>)
        </a>
        <?php endif; ?>
        
        <?php if ($stats['my_waiting_count'] > 0): ?>
        <a href="pages_php/support/tickets.php?assigned=me&status=waiting_response" class="widget-btn">
            <i class="fas fa-reply"></i>
            My Responses (<?php echo $stats['my_waiting_count']; ?>)
        </a>
        <?php endif; ?>
        
        <a href="pages_php/support/chat-management.php" class="widget-btn">
            <i class="fas fa-comments"></i>
            Live Chat
        </a>
        
        <a href="pages_php/support/index.php" class="widget-btn">
            <i class="fas fa-cog"></i>
            Support Center
        </a>
    </div>
</div>
