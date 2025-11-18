<?php
// Include simple authentication and required files
require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/db_functions.php';
require_once __DIR__ . '/../../includes/settings_functions.php';

// Check if user should use admin interface or is member
$shouldUseAdminInterface = shouldUseAdminInterface();
$isMember = isMember();

// Get current user info first
$currentUser = getCurrentUser();

// Ensure admin and super admin users are treated as agents with full access
if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin') {
    $shouldUseAdminInterface = true;
    $isMember = true; // Treat as member for chat purposes
}

// Require admin interface access or member access for this page
if (!$shouldUseAdminInterface && !$isMember) {
    header('Location: ../../access_denied.php');
    exit();
}

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Chat Management - " . $siteName;
$bodyClass = "page-chat-management";

// Include header
require_once '../includes/header.php';

// Define page title, icon, and actions for the modern header
$pageTitle = "Chat Management";
$pageIcon = "fa-comments";
$pageDescription = "Manage live chat sessions and conversations";
$actions = [
    [
        'url' => 'index.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Support',
        'class' => 'btn-outline-light'
    ],
    [
        'url' => 'live-chat.php',
        'icon' => 'fa-comment-alt',
        'text' => 'Live Chat',
        'class' => 'btn-secondary'
    ]
];

// Include the modern page header
include_once '../includes/modern_page_header.php';
?>

<style>
.chat-management-container {
    padding: 2rem 0;
}



.management-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
    border: none;
}

.status-card {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
}

.status-card h3 {
    margin-bottom: 0.5rem;
}

.status-card .display-4 {
    font-weight: 700;
    margin-bottom: 0;
}

.agent-status-controls {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
}

.status-online { background: #28a745; }
.status-busy { background: #ffc107; }
.status-away { background: #fd7e14; }
.status-offline { background: #dc3545; }

.chat-session-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.chat-session-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.chat-session-card.active {
    border-color: #4facfe;
    background: #f8f9ff;
}

.chat-session-card.waiting {
    border-left: 4px solid #ffc107;
}

.chat-session-card.in-progress {
    border-left: 4px solid #28a745;
}

.chat-session-card.ended {
    border-left: 4px solid #6c757d;
}

.priority-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 500;
}

.priority-low {
    background: #d4edda;
    color: #155724;
}

.priority-medium {
    background: #fff3cd;
    color: #856404;
}

.priority-high {
    background: #f8d7da;
    color: #721c24;
}

.priority-urgent {
    background: #f5c6cb;
    color: #721c24;
    animation: pulse 2s infinite;
}

.btn-assign {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border: none;
    border-radius: 20px;
    padding: 0.5rem 1rem;
    color: white;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.btn-assign:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(79, 172, 254, 0.4);
    color: white;
}

.btn-view-chat {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 20px;
    padding: 0.5rem 1rem;
    color: white;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.btn-view-chat:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.4);
    color: white;
}

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.nav-tabs .nav-link {
    border-radius: 10px 10px 0 0;
    border: none;
    background: #f8f9fa;
    color: #6c757d;
    margin-right: 0.5rem;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link.active {
    background: white;
    color: #667eea;
    border-bottom: 3px solid #667eea;
}

.tab-content {
    background: white;
    border-radius: 0 15px 15px 15px;
    padding: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .management-card {
        padding: 1.5rem;
    }
    
    .status-card {
        padding: 1.5rem;
    }
}
</style>

<div class="container-fluid px-4" style="margin-top: 2rem;">

    <!-- Agent Status Controls -->
    <div class="row mb-4">
        <div class="col-lg-4">
            <div class="status-card">
                <h3>Your Status</h3>
                <div class="display-4" id="currentStatus">
                    <span class="status-indicator status-offline"></span>
                    Offline
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="agent-status-controls">
                <h5 class="mb-3">
                    <i class="fas fa-user-cog me-2"></i>Agent Controls
                </h5>
                <form id="statusForm" class="row g-3">
                    <div class="col-md-4">
                        <label for="agentStatus" class="form-label">Status</label>
                        <select class="form-select" id="agentStatus" name="status">
                            <option value="online">Online</option>
                            <option value="busy">Busy</option>
                            <option value="away">Away</option>
                            <option value="offline" selected>Offline</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="maxChats" class="form-label">Max Concurrent Chats</label>
                        <select class="form-select" id="maxChats" name="max_chats">
                            <option value="1">1</option>
                            <option value="3">3</option>
                            <option value="5" selected>5</option>
                            <option value="10">10</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check me-3">
                            <input class="form-check-input" type="checkbox" id="autoAssign" name="auto_assign" checked>
                            <label class="form-check-label" for="autoAssign">
                                Auto-assign
                            </label>
                        </div>
                        <button type="submit" class="btn btn-assign">
                            <i class="fas fa-save me-1"></i>Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Chat Sessions Management -->
    <div class="management-card">
        <ul class="nav nav-tabs" id="chatTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">
                    <i class="fas fa-comments me-2"></i>Active Chats <span class="badge bg-success ms-1" id="activeCount">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="waiting-tab" data-bs-toggle="tab" data-bs-target="#waiting" type="button" role="tab">
                    <i class="fas fa-clock me-2"></i>Waiting <span class="badge bg-warning ms-1" id="waitingCount">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                    <i class="fas fa-history me-2"></i>History
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="chatTabContent">
            <div class="tab-pane fade show active" id="active" role="tabpanel">
                <div id="activeChatsList">
                    <!-- Active chats will be loaded here -->
                </div>
            </div>
            
            <div class="tab-pane fade" id="waiting" role="tabpanel">
                <div id="waitingChatsList">
                    <!-- Waiting chats will be loaded here -->
                </div>
            </div>
            
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div id="historyChatsList">
                    <!-- Chat history will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
class ChatManagement {
    constructor() {
        this.currentUser = <?php echo json_encode($currentUser); ?>;
        this.refreshInterval = null;

        this.initializeEventListeners();
        this.loadAgentStatus();
        this.loadChatSessions();
        this.startAutoRefresh();
    }

    initializeEventListeners() {
        // Status form submission
        document.getElementById('statusForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateAgentStatus();
        });

        // Tab switching
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                this.loadChatSessions();
            });
        });
    }

    async updateAgentStatus() {
        const formData = new FormData(document.getElementById('statusForm'));
        const data = {
            status: formData.get('status'),
            max_chats: parseInt(formData.get('max_chats')),
            auto_assign: formData.has('auto_assign')
        };

        try {
            const response = await fetch('chat_api.php?action=update_agent_status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.updateStatusDisplay(data.status);
                this.showNotification('Status updated successfully!', 'success');
            } else {
                this.showNotification(result.error || 'Failed to update status', 'error');
            }
        } catch (error) {
            console.error('Error updating status:', error);
            this.showNotification('Failed to update status. Please try again.', 'error');
        }
    }

    updateStatusDisplay(status) {
        const statusElement = document.getElementById('currentStatus');
        const indicator = statusElement.querySelector('.status-indicator');

        // Remove all status classes
        indicator.className = 'status-indicator';

        // Add new status class
        indicator.classList.add(`status-${status}`);

        // Update text
        statusElement.innerHTML = `
            <span class="status-indicator status-${status}"></span>
            ${status.charAt(0).toUpperCase() + status.slice(1)}
        `;
    }

    async loadAgentStatus() {
        try {
            // This would typically load the current agent status from the API
            // For now, we'll use the form values
            const status = document.getElementById('agentStatus').value;
            this.updateStatusDisplay(status);
        } catch (error) {
            console.error('Error loading agent status:', error);
        }
    }

    async loadChatSessions() {
        const activeTab = document.querySelector('.nav-link.active').id;
        let status = 'active';

        if (activeTab === 'waiting-tab') {
            status = 'waiting';
        } else if (activeTab === 'history-tab') {
            status = 'ended';
        }

        try {
            const response = await fetch(`chat_api.php?action=get_agent_sessions&status=${status}`);
            const result = await response.json();

            if (result.success) {
                this.displayChatSessions(result.sessions, status);
                this.updateCounts(result.sessions, status);
            }
        } catch (error) {
            console.error('Error loading chat sessions:', error);
        }
    }

    displayChatSessions(sessions, status) {
        let containerId = 'activeChatsList';

        if (status === 'waiting') {
            containerId = 'waitingChatsList';
        } else if (status === 'ended') {
            containerId = 'historyChatsList';
        }

        const container = document.getElementById(containerId);
        container.innerHTML = '';

        if (sessions.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No ${status} chat sessions found.</p>
                </div>
            `;
            return;
        }

        sessions.forEach(session => {
            const sessionElement = this.createSessionElement(session, status);
            container.appendChild(sessionElement);
        });
    }

    createSessionElement(session, status) {
        const div = document.createElement('div');
        div.className = `chat-session-card ${status}`;
        div.dataset.sessionId = session.session_id;

        const timeAgo = this.getTimeAgo(session.started_at);
        const lastActivity = this.getTimeAgo(session.last_activity);

        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-2">
                        <h6 class="mb-0 me-2">${session.first_name} ${session.last_name}</h6>
                        <span class="priority-badge priority-${session.priority}">${session.priority}</span>
                    </div>
                    <p class="text-muted mb-1">${session.subject || 'No subject'}</p>
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>Started ${timeAgo}
                        ${status !== 'ended' ? `• Last activity ${lastActivity}` : ''}
                        ${session.unread_count > 0 ? `• <span class="text-danger">${session.unread_count} unread</span>` : ''}
                    </small>
                </div>
                <div class="d-flex gap-2">
                    ${status === 'waiting' ? `
                        <button class="btn btn-assign btn-sm" onclick="chatManagement.assignSession(${session.session_id})">
                            <i class="fas fa-user-plus me-1"></i>Assign
                        </button>
                    ` : ''}
                    <button class="btn btn-view-chat btn-sm" onclick="chatManagement.openChat(${session.session_id})">
                        <i class="fas fa-eye me-1"></i>View
                    </button>
                </div>
            </div>
        `;

        return div;
    }

    async assignSession(sessionId) {
        try {
            const response = await fetch('chat_api.php?action=assign_session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: sessionId
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Session assigned successfully!', 'success');
                this.loadChatSessions();
            } else {
                this.showNotification(result.error || 'Failed to assign session', 'error');
            }
        } catch (error) {
            console.error('Error assigning session:', error);
            this.showNotification('Failed to assign session. Please try again.', 'error');
        }
    }

    openChat(sessionId) {
        // Open chat in a new window
        window.open(`live-chat.php?session_id=${sessionId}`, 'chat_' + sessionId, 'width=800,height=600,scrollbars=yes,resizable=yes');
    }

    updateCounts(sessions, status) {
        if (status === 'active') {
            document.getElementById('activeCount').textContent = sessions.length;
        } else if (status === 'waiting') {
            document.getElementById('waitingCount').textContent = sessions.length;
        }
    }

    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            this.loadChatSessions();
        }, 10000); // Refresh every 10 seconds
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    getTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffInSeconds = Math.floor((now - time) / 1000);

        if (diffInSeconds < 60) {
            return 'just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';

        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
}

// Initialize chat management when page loads
let chatManagement;
document.addEventListener('DOMContentLoaded', () => {
    chatManagement = new ChatManagement();
});

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (chatManagement) {
        chatManagement.stopAutoRefresh();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
