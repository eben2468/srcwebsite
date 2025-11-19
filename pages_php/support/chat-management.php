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
    padding: 1rem 0;
}

/* Desktop padding */
@media (min-width: 992px) {
    .container-fluid {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
}

.management-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: none;
}

.status-overview {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.status-card {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    margin-bottom: 0;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.status-card h6 {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-card .display-4 {
    font-weight: 700;
    margin-bottom: 0;
    font-size: 2rem;
    line-height: 1.2;
}

.agent-status-controls {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
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
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
    background: white;
}

.chat-session-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
    border-color: #667eea;
}

.chat-session-card.active {
    border-color: #4facfe;
    background: #f8f9ff;
}

.chat-session-card.waiting {
    border-left: 4px solid #ffc107;
    background: #fffbf0;
}

.chat-session-card.in-progress,
.chat-session-card.active {
    border-left: 4px solid #28a745;
    background: #f0fff4;
}

.chat-session-card.ended {
    border-left: 4px solid #6c757d;
    background: #f8f9fa;
}

.session-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.session-info {
    flex: 1;
    min-width: 0;
}

.session-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

.session-user-name {
    font-size: 1rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.25rem;
    line-height: 1.4;
}

.session-subject {
    color: #718096;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    line-height: 1.5;
}

.session-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
    font-size: 0.8125rem;
    color: #a0aec0;
}

.priority-badge {
    padding: 0.25rem 0.625rem;
    border-radius: 12px;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
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

.status-badge {
    padding: 0.25rem 0.625rem;
    border-radius: 12px;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-assign {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    color: white;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-assign:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(79, 172, 254, 0.4);
    color: white;
}

.btn-view-chat {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    color: white;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-view-chat:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 0.625rem 0.875rem;
    transition: all 0.3s ease;
    font-size: 0.875rem;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.375rem;
}

.nav-tabs {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 1.5rem;
    flex-wrap: nowrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.nav-tabs::-webkit-scrollbar {
    height: 4px;
}

.nav-tabs::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 2px;
}

.nav-tabs .nav-link {
    border-radius: 8px 8px 0 0;
    border: none;
    background: transparent;
    color: #718096;
    margin-right: 0;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    white-space: nowrap;
    font-weight: 500;
    position: relative;
}

.nav-tabs .nav-link:hover {
    color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.nav-tabs .nav-link.active {
    background: transparent;
    color: #667eea;
    border-bottom: 3px solid #667eea;
}

.nav-tabs .nav-link .badge {
    margin-left: 0.5rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
}

.tab-content {
    background: transparent;
    border-radius: 0;
    padding: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.stat-card .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 0.25rem;
    line-height: 1;
}

.stat-card .stat-label {
    font-size: 0.75rem;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.stat-card.stat-waiting {
    border-left: 4px solid #ffc107;
}

.stat-card.stat-active {
    border-left: 4px solid #28a745;
}

.stat-card.stat-total {
    border-left: 4px solid #667eea;
}

.empty-state {
    text-align: center;
    padding: 3rem 1.5rem;
    color: #a0aec0;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state p {
    font-size: 0.9375rem;
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .management-card {
        padding: 1rem;
        border-radius: 12px;
    }
    
    .status-card {
        padding: 1rem;
        min-height: 100px;
    }
    
    .status-card .display-4 {
        font-size: 1.5rem;
    }
    
    .chat-session-card {
        padding: 1rem;
    }
    
    .session-actions {
        width: 100%;
        justify-content: stretch;
    }
    
    .session-actions .btn {
        flex: 1;
    }
    
    .agent-status-controls {
        padding: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-card .stat-value {
        font-size: 1.5rem;
    }
}

/* Mobile Full-Width Optimization for Chat Management Page */
@media (max-width: 991px) {
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    .chat-management-container {
        padding: 0.5rem 0;
    }
    
    .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    
    .row > [class*="col-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Make all cards full width */
    .stats-grid,
    .status-overview,
    .management-card,
    .status-card,
    .agent-status-controls,
    .chat-session-card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
    
    /* Maintain internal padding for readability */
    .stats-grid {
        padding: 0 1rem;
        gap: 0.75rem;
    }
    
    .status-overview,
    .management-card {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .nav-tabs {
        margin-bottom: 1rem;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .nav-tabs .nav-link {
        padding: 0.625rem 0.875rem;
        font-size: 0.875rem;
    }
    
    .tab-content {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>

<div class="container-fluid" style="margin-top: 2rem;">

    <!-- Statistics Overview -->
    <div class="stats-grid">
        <div class="stat-card stat-active">
            <div class="stat-value" id="activeCount">0</div>
            <div class="stat-label">Active Chats</div>
        </div>
        <div class="stat-card stat-waiting">
            <div class="stat-value" id="waitingCount">0</div>
            <div class="stat-label">Waiting</div>
        </div>
        <div class="stat-card stat-total">
            <div class="stat-value" id="totalCount">0</div>
            <div class="stat-label">Total Today</div>
        </div>
    </div>

    <!-- Agent Status Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="status-overview">
                <div class="row align-items-center g-3">
                    <div class="col-md-4">
                        <div class="status-card">
                            <h6>Current Status</h6>
                            <div class="display-4" id="currentStatus">
                                <span class="status-indicator status-offline"></span>
                                Offline
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="agent-status-controls">
                            <h5 class="mb-3">
                                <i class="fas fa-user-cog me-2"></i>Agent Controls
                            </h5>
                            <form id="statusForm">
                                <div class="row g-2">
                                    <div class="col-md-4 col-6">
                                        <label for="agentStatus" class="form-label">Status</label>
                                        <select class="form-select" id="agentStatus" name="status">
                                            <option value="online">Online</option>
                                            <option value="busy">Busy</option>
                                            <option value="away">Away</option>
                                            <option value="offline" selected>Offline</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-6">
                                        <label for="maxChats" class="form-label">Max Chats</label>
                                        <select class="form-select" id="maxChats" name="max_chats">
                                            <option value="1">1</option>
                                            <option value="3">3</option>
                                            <option value="5" selected>5</option>
                                            <option value="10">10</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-12">
                                        <label class="form-label d-block">&nbsp;</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="autoAssign" name="auto_assign" checked>
                                                <label class="form-check-label" for="autoAssign">
                                                    Auto-assign
                                                </label>
                                            </div>
                                            <button type="submit" class="btn btn-assign ms-auto">
                                                <i class="fas fa-save me-1"></i>Update
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Sessions Management -->
    <div class="management-card">
        <ul class="nav nav-tabs" id="chatTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">
                    <i class="fas fa-comments me-2"></i>Active<span class="badge bg-success ms-1" id="activeCountBadge">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="waiting-tab" data-bs-toggle="tab" data-bs-target="#waiting" type="button" role="tab">
                    <i class="fas fa-clock me-2"></i>Waiting<span class="badge bg-warning ms-1" id="waitingCountBadge">0</span>
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
        this.previousCounts = { waiting: 0, active: 0 };
        this.notificationSound = null;

        this.initializeEventListeners();
        this.initializeNotificationSound();
        this.loadAgentStatus();
        this.loadChatSessions();
        this.startAutoRefresh();
    }

    initializeNotificationSound() {
        // Create audio element for new chat notifications
        this.notificationSound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZRQ0PVqzn77BdGAg+ltryxHUpBSl+zPLaizsIGGS56+OgUQwKTqXh8bllHAU2jdXzzn0vBSF1xe/glEILElyx6OyrWBUIQ5zd8sFuJAUuhM/y1YU2Bhxqvu7mnEgODlOq5O+zYBoGPJPY88p6KwUme8rx3I4+CRZiturjpVMMCkqh3/G8aB8GM4nU8tGAMQYfb8Tv45dFDBBUreXusV0XCECa3PLEcSYELIHO8diKNggZaLvt559NEAxPp+PwsWIbBjeR1/PMeS0GI3fH8N2RQAoUXrTp66hVFApGnt/yvmwhBTGH0fPTgjQGHW/A7+OYRAwPVqzn77BdGAg+ltryxHUpBSl+zPLaizsIGGS56+OgUQwKTqXh8bllHAU2jdXzzn0vBSF1xe/glEILElyx6OyrWBUIRJzd8sFuJAUuhM/y1YU2Bhxqvu7mnEgODlOq5O+zYBoGPJPY88p6KwUme8rx3I4+CRZiturjpVMMCkqh3/G8aB8GM4nU8tGAMQYfb8Tv45dFDBBUreXusV0XCECa3PLEcSYELIHO8diKNggZaLvt559NEAxPp+PwsWIbBjeR1/PMeS0GI3fH8N2RQAoUXrTp66hVFApGnt/yvmwhBTGH0fPTgjQGHW/A7+OYRAwPVqzn77BdGAg+ltryxHUpBSl+zPLaizsIGGS56+OgUQwKTqXh8bllHAU2jdXzzn0vBSF1xe/glEILElyx6OyrWBUIRJzd8sFuJAUuhM/y1YU2Bhxqvu7mnEgODlOq5O+zYBoGPJPY88p6KwUme8rx3I4+CRZiturjpVMMCkqh3/G8aB8GM4nU8tGAMQYfb8Tv45dFDBBUreXusV0XCECa3PLEcSYELIHO8diKNggZaLvt559NEAxPp+PwsWIbBjeR1/PMeS0GI3fH8N2RQAoUXrTp66hVFApGnt/yvmwhBTGH0fPTgjQGHW/A7+OYRAwPVqzn77BdGAg+ltryxHUpBSl+zPLaizsIGGS56+OgUQwKTqXh8bllHAU2jdXzzn0vBSF1xe/glEILElyx6OyrWBUIQ5zd8sFuJAUuhM/y1YU2Bhxqvu7mnEgODlOq5O+zYBoGPJPY88p6KwUme8rx3I4+CRZiturjpVMMCkqh3/G8aB8GM4nU8tGAMQYfb8Tv45dFDBBUreXusV0XCECa3PLEcSYELIHO8diKNggZaLvt559NEAxPp+PwsWIbBjeR1/PMeS0GI3fH8N2RQAoUXrTp66hVFApGnt/yvmwhBTGH0fPTgjQGHW/A7+OYRAwPVqzn77BdGAg+ltryxHUpBSl+zPLaizsIGGS56+OgUQwKTqXh8bllHAU2jdXzzn0vBSF1xe/glEILElyx6OyrWBUIQ5zd8sFuJAUuhM/y1YU2Bhxqvu7mnEgODlOq5O+zYBoGPJPY88p6KwUme8rx3I4+CRZiturjpVMMCkqh3/G8aB8GM4nU8tGAMQYfb8Tv45dFDBBUreXusV0XCECa3PLEcSYELIHO8diKNggZaLvt559NEAxPp+PwsWIbBjeR1/PMeS0GI3fH8N2RQAoUXrTp66hVFA==');
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
            const response = await fetch('chat_api.php?action=get_agent_status');
            const result = await response.json();

            if (result.success && result.status) {
                const status = result.status.status;
                const maxChats = result.status.max_concurrent_chats;
                const autoAssign = result.status.auto_assign;

                // Update form values
                document.getElementById('agentStatus').value = status;
                document.getElementById('maxChats').value = maxChats;
                document.getElementById('autoAssign').checked = autoAssign == 1;

                // Update status display
                this.updateStatusDisplay(status);
            }
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
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
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
        
        // Get status badge color
        let statusBadgeClass = 'bg-secondary';
        if (status === 'waiting') statusBadgeClass = 'bg-warning';
        else if (status === 'active') statusBadgeClass = 'bg-success';
        else if (status === 'ended') statusBadgeClass = 'bg-secondary';

        div.innerHTML = `
            <div class="session-header">
                <div class="session-info">
                    <div class="session-user-name">
                        ${session.first_name} ${session.last_name}
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                        <span class="priority-badge priority-${session.priority}">
                            <i class="fas fa-flag"></i> ${session.priority}
                        </span>
                        <span class="status-badge ${statusBadgeClass}">${status.toUpperCase()}</span>
                    </div>
                    <div class="session-subject">${session.subject || 'No subject'}</div>
                    <div class="session-meta">
                        <span><i class="fas fa-clock me-1"></i>${timeAgo}</span>
                        ${status !== 'ended' ? `<span><i class="fas fa-circle-dot me-1"></i>${lastActivity}</span>` : ''}
                        ${session.unread_count > 0 ? `<span class="text-danger"><i class="fas fa-envelope me-1"></i>${session.unread_count} unread</span>` : ''}
                    </div>
                </div>
                <div class="session-actions">
                    ${status === 'waiting' ? `
                        <button class="btn btn-assign btn-sm" onclick="chatManagement.assignSession(${session.session_id})">
                            <i class="fas fa-user-plus"></i><span class="d-none d-sm-inline ms-1">Assign</span>
                        </button>
                    ` : ''}
                    <button class="btn btn-view-chat btn-sm" onclick="chatManagement.openChat(${session.session_id})">
                        <i class="fas fa-eye"></i><span class="d-none d-sm-inline ms-1">View</span>
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
        const count = sessions.length;
        
        if (status === 'active') {
            document.getElementById('activeCount').textContent = count;
            document.getElementById('activeCountBadge').textContent = count;
            
            // Play notification if count increased
            if (count > this.previousCounts.active) {
                this.playNotificationSound();
            }
            this.previousCounts.active = count;
        } else if (status === 'waiting') {
            document.getElementById('waitingCount').textContent = count;
            document.getElementById('waitingCountBadge').textContent = count;
            
            // Play notification if count increased
            if (count > this.previousCounts.waiting) {
                this.playNotificationSound();
                this.showNotification('New chat session waiting for assignment!', 'info');
            }
            this.previousCounts.waiting = count;
        }
        
        // Update total count (active + waiting)
        const totalCount = this.previousCounts.active + this.previousCounts.waiting;
        document.getElementById('totalCount').textContent = totalCount;
    }

    playNotificationSound() {
        if (this.notificationSound) {
            this.notificationSound.play().catch(e => console.log('Could not play notification sound'));
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
