<?php
// Include simple authentication and required files
require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/db_functions.php';
require_once __DIR__ . '/../../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Get current user info
$currentUser = getCurrentUser();
require_once __DIR__ . '/../../includes/auth_functions.php';
$shouldUseAdminInterface = shouldUseAdminInterface();
$isMember = isMember();
$isAgent = $shouldUseAdminInterface || $isMember;

// Ensure admin and super admin users are treated as agents with full access
if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin') {
    $isAgent = true;
    $shouldUseAdminInterface = true;
}

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Live Chat - " . $siteName;
$bodyClass = "page-live-chat";

// Include header
require_once '../includes/header.php';

// Define page title, icon, and actions for the modern header
$pageTitle = "Live Chat";
$pageIcon = "fa-comments";
$pageDescription = "Get instant support through live chat";
$actions = [
    [
        'url' => 'index.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Support',
        'class' => 'btn-outline-light'
    ],
    [
        'url' => 'help-center.php',
        'icon' => 'fa-question-circle',
        'text' => 'Help Center',
        'class' => 'btn-secondary'
    ],
    [
        'url' => 'user-guide.php',
        'icon' => 'fa-book',
        'text' => 'User Guide',
        'class' => 'btn-secondary'
    ]
];

// Include the modern page header
include_once '../includes/modern_page_header.php';
?>

<style>
.chat-container {
    min-height: calc(100vh - 200px);
    padding: 2rem 0;
}

.chat-header {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    padding: 3rem 2rem;
    margin-top: 60px;
    margin-bottom: 2rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.chat-header .container-fluid {
    max-width: 1200px;
    margin: 0 auto;
}

.chat-header h1 {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    margin-bottom: 1rem;
    font-weight: 600;
}

.chat-window {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
    height: 600px;
    display: flex;
    flex-direction: column;
}

.chat-window-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: between;
    align-items: center;
}

.chat-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #28a745;
    animation: pulse 2s infinite;
}

.status-indicator.waiting {
    background: #ffc107;
}

.status-indicator.offline {
    background: #dc3545;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.chat-messages {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    background: #f8f9fa;
    max-height: 400px;
}

.message {
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.message.own {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.message.own .message-avatar {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.message.system .message-avatar {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.message-content {
    max-width: 70%;
}

.message-bubble {
    background: white;
    padding: 0.75rem 1rem;
    border-radius: 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: relative;
}

.message.own .message-bubble {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.message.system .message-bubble {
    background: #e9ecef;
    color: #6c757d;
    font-style: italic;
    text-align: center;
}

.message-time {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.message.own .message-time {
    text-align: right;
}

.chat-input-area {
    padding: 1rem 1.5rem;
    background: white;
    border-top: 1px solid #e9ecef;
}

.chat-input-form {
    display: flex;
    gap: 0.75rem;
    align-items: flex-end;
}

.chat-input {
    flex: 1;
    border: 2px solid #e9ecef;
    border-radius: 25px;
    padding: 0.75rem 1rem;
    resize: none;
    max-height: 100px;
    transition: all 0.3s ease;
}

.chat-input:focus {
    border-color: #4facfe;
    box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
    outline: none;
}

.chat-send-btn {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border: none;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.chat-send-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
}

.chat-send-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.typing-indicator {
    display: none;
    padding: 0.5rem 1rem;
    color: #6c757d;
    font-style: italic;
    font-size: 0.875rem;
}

.typing-dots {
    display: inline-block;
}

.typing-dots span {
    display: inline-block;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #6c757d;
    margin: 0 1px;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

.chat-start-form {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #4facfe;
    box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
}

.btn-start-chat {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border: none;
    border-radius: 25px;
    padding: 0.75rem 2rem;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-start-chat:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
    color: white;
}

.chat-actions {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
}

.chat-action-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    border-radius: 8px;
    color: white;
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.chat-action-btn:hover {
    background: rgba(255,255,255,0.3);
}

.quick-responses {
    display: none;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    max-height: 150px;
    overflow-y: auto;
}

.quick-response-item {
    padding: 0.5rem;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.2s ease;
    font-size: 0.875rem;
}

.quick-response-item:hover {
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .chat-window {
        height: 500px;
    }
    
    .chat-messages {
        max-height: 300px;
    }
    
    .message-content {
        max-width: 85%;
    }
    
    .chat-header {
        padding: 2rem 1rem;
    }
}
</style>

<!-- Main Content -->
<div class="container-fluid px-4" style="margin-top: 2rem;">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <!-- Chat Start Form (shown initially) -->
            <div id="chatStartForm" class="chat-start-form text-center">
                <div class="mb-4">
                    <i class="fas fa-comments display-1 text-primary mb-3"></i>
                    <h3 class="mb-3">
                        <i class="fas fa-play-circle me-2"></i>Start Live Chat
                    </h3>
                    <p class="text-muted mb-4">
                        Get instant support through live chat. Click the button below to connect with our support team.
                    </p>
                </div>

                <form id="startChatForm">
                    <!-- Hidden default values for backend compatibility -->
                    <input type="hidden" name="subject" value="General Support Request">
                    <input type="hidden" name="priority" value="medium">
                    <input type="hidden" name="department" value="general">

                    <div class="text-center">
                        <button type="submit" class="btn btn-start-chat btn-lg px-5 py-3">
                            <i class="fas fa-comments me-2"></i>Start Chat
                        </button>
                    </div>
                </form>

                <div class="mt-4">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Our support team is available to help you with any questions or issues.
                    </small>
                </div>
            </div>

            <!-- Chat Window (hidden initially) -->
            <div id="chatWindow" class="chat-window" style="display: none;">
                <div class="chat-window-header">
                    <div class="chat-status">
                        <div class="status-indicator waiting" id="statusIndicator"></div>
                        <span id="statusText">Waiting for agent...</span>
                    </div>
                    <div class="chat-actions">
                        <?php if ($isAgent): ?>
                        <button class="chat-action-btn" id="quickResponseBtn" title="Quick Responses">
                            <i class="fas fa-bolt"></i>
                        </button>
                        <?php endif; ?>
                        <button class="chat-action-btn" id="endChatBtn" title="End Chat">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <?php if ($isAgent): ?>
                <div class="quick-responses" id="quickResponses">
                    <!-- Quick responses will be loaded here -->
                </div>
                <?php endif; ?>

                <div class="chat-messages" id="chatMessages">
                    <!-- Messages will be loaded here -->
                </div>

                <div class="typing-indicator" id="typingIndicator">
                    <span>Agent is typing</span>
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>

                <div class="chat-input-area">
                    <form class="chat-input-form" id="messageForm">
                        <textarea class="chat-input" id="messageInput" placeholder="Type your message..." 
                                  rows="1" required></textarea>
                        <button type="submit" class="chat-send-btn" id="sendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
class LiveChat {
    constructor() {
        this.sessionId = null;
        this.lastMessageId = 0;
        this.pollInterval = null;
        this.notificationInterval = null;
        this.isAgent = <?php echo $isAgent ? 'true' : 'false'; ?>;
        this.currentUser = <?php echo json_encode($currentUser); ?>;

        // Check for session ID in URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const sessionIdParam = urlParams.get('session_id');
        if (sessionIdParam) {
            this.sessionId = sessionIdParam;
        }

        this.initializeEventListeners();
        this.checkExistingSession();
        this.startNotificationPolling();
    }

    initializeEventListeners() {
        // Start chat form
        document.getElementById('startChatForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.startChat();
        });

        // Message form
        document.getElementById('messageForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });

        // End chat button
        document.getElementById('endChatBtn').addEventListener('click', () => {
            this.endChat();
        });

        // Quick responses (for agents)
        if (this.isAgent) {
            document.getElementById('quickResponseBtn').addEventListener('click', () => {
                this.toggleQuickResponses();
            });
        }

        // Auto-resize textarea
        const messageInput = document.getElementById('messageInput');
        messageInput.addEventListener('input', () => {
            this.autoResizeTextarea(messageInput);
        });

        // Enter key to send (Shift+Enter for new line)
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
    }

    async checkExistingSession() {
        try {
            let url = 'chat_api.php?action=get_session';
            if (this.sessionId) {
                url += `&session_id=${this.sessionId}`;
            }

            const response = await fetch(url);
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.session) {
                    this.sessionId = data.session.session_id;
                    this.showChatWindow();
                    this.loadMessages();
                    this.startPolling();
                    this.updateChatStatus(data.session.status);
                }
            }
        } catch (error) {
            console.error('Error checking existing session:', error);
        }
    }

    async startChat() {
        const formData = new FormData(document.getElementById('startChatForm'));
        const data = {
            subject: formData.get('subject'),
            priority: formData.get('priority'),
            department: formData.get('department')
        };

        try {
            const response = await fetch('chat_api.php?action=start_session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.sessionId = result.session_id;
                this.showChatWindow();
                this.loadMessages();
                this.startPolling();
                this.showNotification('Chat session started successfully!', 'success');
            } else {
                this.showNotification(result.error || 'Failed to start chat', 'error');
            }
        } catch (error) {
            console.error('Error starting chat:', error);
            this.showNotification('Failed to start chat. Please try again.', 'error');
        }
    }

    async sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();

        if (!message || !this.sessionId) return;

        const sendBtn = document.getElementById('sendBtn');
        sendBtn.disabled = true;

        try {
            const response = await fetch('chat_api.php?action=send_message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    message: message,
                    type: 'text'
                })
            });

            const result = await response.json();

            if (result.success) {
                messageInput.value = '';
                this.autoResizeTextarea(messageInput);
                this.loadMessages();
            } else {
                this.showNotification(result.error || 'Failed to send message', 'error');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            this.showNotification('Failed to send message. Please try again.', 'error');
        } finally {
            sendBtn.disabled = false;
        }
    }

    async loadMessages() {
        if (!this.sessionId) return;

        try {
            const response = await fetch(`chat_api.php?action=get_messages&session_id=${this.sessionId}&last_message_id=${this.lastMessageId}`);
            const result = await response.json();

            if (result.success && result.messages.length > 0) {
                this.displayMessages(result.messages);
                this.lastMessageId = Math.max(...result.messages.map(m => parseInt(m.message_id)));
                this.markMessagesRead();
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    displayMessages(messages) {
        const chatMessages = document.getElementById('chatMessages');

        messages.forEach(message => {
            const messageElement = this.createMessageElement(message);
            chatMessages.appendChild(messageElement);
        });

        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    createMessageElement(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message';

        const isOwnMessage = parseInt(message.sender_id) === this.currentUser.user_id;
        const isSystemMessage = message.message_type === 'system';

        if (isOwnMessage) {
            messageDiv.classList.add('own');
        } else if (isSystemMessage) {
            messageDiv.classList.add('system');
        }

        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';

        if (isSystemMessage) {
            avatar.innerHTML = '<i class="fas fa-robot"></i>';
        } else {
            const initials = isOwnMessage
                ? (this.currentUser.first_name?.[0] || '') + (this.currentUser.last_name?.[0] || '')
                : (message.first_name?.[0] || '') + (message.last_name?.[0] || '');
            avatar.textContent = initials || '?';
        }

        const content = document.createElement('div');
        content.className = 'message-content';

        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        bubble.textContent = message.message_text;

        const time = document.createElement('div');
        time.className = 'message-time';
        time.textContent = this.formatTime(message.sent_at);

        content.appendChild(bubble);
        content.appendChild(time);

        messageDiv.appendChild(avatar);
        messageDiv.appendChild(content);

        return messageDiv;
    }

    async markMessagesRead() {
        if (!this.sessionId) return;

        try {
            await fetch('chat_api.php?action=mark_messages_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: this.sessionId
                })
            });
        } catch (error) {
            console.error('Error marking messages as read:', error);
        }
    }

    async endChat() {
        if (!this.sessionId) return;

        if (!confirm('Are you sure you want to end this chat session?')) {
            return;
        }

        try {
            const response = await fetch('chat_api.php?action=end_session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: this.sessionId
                })
            });

            const result = await response.json();

            if (result.success) {
                this.stopPolling();
                this.showChatStartForm();
                this.showNotification('Chat session ended successfully!', 'success');
                this.sessionId = null;
                this.lastMessageId = 0;
            } else {
                this.showNotification(result.error || 'Failed to end chat', 'error');
            }
        } catch (error) {
            console.error('Error ending chat:', error);
            this.showNotification('Failed to end chat. Please try again.', 'error');
        }
    }

    showChatWindow() {
        document.getElementById('chatStartForm').style.display = 'none';
        document.getElementById('chatWindow').style.display = 'flex';
    }

    showChatStartForm() {
        document.getElementById('chatWindow').style.display = 'none';
        document.getElementById('chatStartForm').style.display = 'block';
        document.getElementById('chatMessages').innerHTML = '';
    }

    startPolling() {
        this.stopPolling();
        this.pollInterval = setInterval(() => {
            this.loadMessages();
        }, 3000); // Poll every 3 seconds
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    startNotificationPolling() {
        this.stopNotificationPolling();
        this.notificationInterval = setInterval(() => {
            this.checkNotifications();
        }, 5000); // Check notifications every 5 seconds
    }

    stopNotificationPolling() {
        if (this.notificationInterval) {
            clearInterval(this.notificationInterval);
            this.notificationInterval = null;
        }
    }

    async checkNotifications() {
        try {
            const response = await fetch('chat_notifications.php?action=get_unread_count');
            const result = await response.json();

            if (result.success && result.unread_count > 0) {
                this.updateNotificationBadge(result.unread_count);
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    }

    updateNotificationBadge(count) {
        // Update browser title with notification count
        const originalTitle = document.title.replace(/^\(\d+\)\s*/, '');
        if (count > 0) {
            document.title = `(${count}) ${originalTitle}`;
        } else {
            document.title = originalTitle;
        }
    }

    updateChatStatus(status) {
        const statusIndicator = document.getElementById('statusIndicator');
        const statusText = document.getElementById('statusText');

        // Remove all status classes
        statusIndicator.className = 'status-indicator';

        switch (status) {
            case 'waiting':
                statusIndicator.classList.add('waiting');
                statusText.textContent = 'Waiting for agent...';
                break;
            case 'active':
                statusIndicator.classList.add('status-online');
                statusText.textContent = 'Connected to agent';
                break;
            case 'ended':
                statusIndicator.classList.add('offline');
                statusText.textContent = 'Chat ended';
                break;
            default:
                statusIndicator.classList.add('waiting');
                statusText.textContent = 'Connecting...';
        }
    }

    autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
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

    async toggleQuickResponses() {
        const quickResponses = document.getElementById('quickResponses');

        if (quickResponses.style.display === 'none' || !quickResponses.style.display) {
            await this.loadQuickResponses();
            quickResponses.style.display = 'block';
        } else {
            quickResponses.style.display = 'none';
        }
    }

    async loadQuickResponses() {
        try {
            const response = await fetch('chat_api.php?action=get_quick_responses');
            const result = await response.json();

            if (result.success) {
                const quickResponses = document.getElementById('quickResponses');
                quickResponses.innerHTML = '';

                result.responses.forEach(response => {
                    const item = document.createElement('div');
                    item.className = 'quick-response-item';
                    item.textContent = response.title;
                    item.title = response.message;

                    item.addEventListener('click', () => {
                        document.getElementById('messageInput').value = response.message;
                        quickResponses.style.display = 'none';
                    });

                    quickResponses.appendChild(item);
                });
            }
        } catch (error) {
            console.error('Error loading quick responses:', error);
        }
    }
}

// Initialize chat when page loads
let liveChat;
document.addEventListener('DOMContentLoaded', () => {
    liveChat = new LiveChat();
});

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (liveChat) {
        liveChat.stopPolling();
        liveChat.stopNotificationPolling();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
