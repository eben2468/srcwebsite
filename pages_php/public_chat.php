<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Check if public chat feature is enabled
if (!hasFeaturePermission('enable_public_chat')) {
    $_SESSION['error'] = "The public chat feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Get current user info
$currentUser = getCurrentUser();
require_once __DIR__ . '/../includes/auth_functions.php';
$shouldUseAdminInterface = shouldUseAdminInterface();

// Ensure public chat tables exist
function ensurePublicChatTables() {
    global $conn;
    
    // Create public_chat_messages table
    $createMessagesTable = "CREATE TABLE IF NOT EXISTS public_chat_messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        message_text TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_deleted BOOLEAN DEFAULT FALSE,
        INDEX idx_sender_id (sender_id),
        INDEX idx_sent_at (sent_at),
        FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // Create public_chat_reactions table
    $createReactionsTable = "CREATE TABLE IF NOT EXISTS public_chat_reactions (
        reaction_id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        user_id INT NOT NULL,
        reaction_type VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_message_id (message_id),
        INDEX idx_user_id (user_id),
        UNIQUE KEY unique_reaction (message_id, user_id, reaction_type),
        FOREIGN KEY (message_id) REFERENCES public_chat_messages(message_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // Execute table creation queries
    mysqli_query($conn, $createMessagesTable);
    mysqli_query($conn, $createReactionsTable);
    
    // Try to add last_activity column if it doesn't exist
    $checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'last_activity'");
    if (mysqli_num_rows($checkColumn) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN last_activity TIMESTAMP NULL DEFAULT NULL, ADD INDEX idx_last_activity (last_activity)");
    }
}

// Initialize database tables
ensurePublicChatTables();

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Public Chat - " . $siteName;
$bodyClass = "page-public-chat";

// Include header
require_once 'includes/header.php';

// Define page title, icon, and actions for the modern header
$pageTitle = "Public Chat";
$pageIcon = "fa-comments";
$pageDescription = "Chat with all users in real-time";
$actions = [
    [
        'url' => 'dashboard.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Dashboard',
        'class' => 'btn-outline-light'
    ]
];

// Include the modern page header
include_once 'includes/modern_page_header.php';
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

.chat-users {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
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

.message-sender {
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.message.own .message-sender {
    text-align: right;
    color: white;
}

.message.system .message-sender {
    color: #6c757d;
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

.chat-input.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
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

.emoji-picker {
    position: absolute;
    bottom: 70px;
    right: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    padding: 10px;
    display: none;
    z-index: 1000;
    width: 300px;
    max-height: 200px;
    overflow-y: auto;
}

.emoji-picker.show {
    display: block;
}

.emoji-item {
    display: inline-block;
    font-size: 1.5rem;
    padding: 5px;
    cursor: pointer;
    border-radius: 5px;
    transition: background 0.2s;
}

.emoji-item:hover {
    background: #f0f0f0;
}

.emoji-button {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
    margin-left: 10px;
}

.emoji-button:hover {
    color: #4facfe;
}

.reaction-container {
    display: flex;
    gap: 5px;
    margin-top: 5px;
    flex-wrap: wrap;
}

.reaction {
    background: rgba(0,0,0,0.05);
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 3px;
    cursor: pointer;
}

.reaction.own {
    background: rgba(79, 172, 254, 0.2);
}

.reaction-count {
    font-size: 0.7rem;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
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

.online-users {
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
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
    
    .emoji-picker {
        right: 10px;
        width: 250px;
    }
}
</style>

<!-- Main Content -->
<div class="container-fluid px-4" style="margin-top: 2rem;">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <!-- Chat Window -->
            <div class="chat-window">
                <div class="chat-window-header">
                    <div class="chat-status">
                        <div class="status-indicator" id="statusIndicator"></div>
                        <span>Public Chat Room</span>
                    </div>
                    <div class="chat-users">
                        <span class="online-users" id="onlineUsersCount">0 users online</span>
                    </div>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <!-- Messages will be loaded here -->
                </div>

                <div class="chat-input-area">
                    <form class="chat-input-form" id="messageForm">
                        <textarea class="chat-input" id="messageInput" placeholder="Type your message..." 
                                  rows="1" required></textarea>
                        <button type="button" class="emoji-button" id="emojiButton">😊</button>
                        <button type="submit" class="chat-send-btn" id="sendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                    
                    <div class="emoji-picker" id="emojiPicker">
                        <div class="emoji-item">😀</div>
                        <div class="emoji-item">😂</div>
                        <div class="emoji-item">😍</div>
                        <div class="emoji-item">😎</div>
                        <div class="emoji-item">👍</div>
                        <div class="emoji-item">👎</div>
                        <div class="emoji-item">❤️</div>
                        <div class="emoji-item">🔥</div>
                        <div class="emoji-item">🎉</div>
                        <div class="emoji-item">💯</div>
                        <div class="emoji-item">👏</div>
                        <div class="emoji-item">🙏</div>
                        <div class="emoji-item">🤔</div>
                        <div class="emoji-item">😢</div>
                        <div class="emoji-item">😡</div>
                        <div class="emoji-item">😱</div>
                        <div class="emoji-item">🤩</div>
                        <div class="emoji-item">🥳</div>
                        <div class="emoji-item">🤗</div>
                        <div class="emoji-item">🤪</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
class PublicChat {
    constructor() {
        this.lastMessageId = 0;
        this.pollInterval = null;
        this.currentUser = <?php echo json_encode($currentUser); ?>;
        this.onlineUsers = new Set();
        this.messageReactions = {};
        
        this.initializeEventListeners();
        this.loadMessages();
        this.startPolling();
        this.updateOnlineUsers();
    }

    initializeEventListeners() {
        // Message form submission
        const messageForm = document.getElementById('messageForm');
        if (messageForm) {
            messageForm.addEventListener('submit', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.sendMessage();
                return false;
            });
        }

        // Send button click event (as backup)
        const sendBtn = document.getElementById('sendBtn');
        if (sendBtn) {
            sendBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                // Manually trigger form submission
                const messageForm = document.getElementById('messageForm');
                if (messageForm) {
                    const submitEvent = new Event('submit', {
                        bubbles: true,
                        cancelable: true
                    });
                    messageForm.dispatchEvent(submitEvent);
                }
                return false;
            });
        }

        // Emoji picker
        const emojiButton = document.getElementById('emojiButton');
        if (emojiButton) {
            emojiButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const emojiPicker = document.getElementById('emojiPicker');
                if (emojiPicker) {
                    emojiPicker.classList.toggle('show');
                }
                return false;
            });
        }

        // Close emoji picker when clicking outside
        document.addEventListener('click', (e) => {
            const emojiPicker = document.getElementById('emojiPicker');
            const emojiButton = document.getElementById('emojiButton');
            
            if (emojiPicker && emojiButton && 
                !emojiPicker.contains(e.target) && e.target !== emojiButton) {
                emojiPicker.classList.remove('show');
            }
        });

        // Emoji selection
        const emojiItems = document.querySelectorAll('.emoji-item');
        emojiItems.forEach(emoji => {
            emoji.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const messageInput = document.getElementById('messageInput');
                if (messageInput) {
                    messageInput.value += e.target.textContent;
                    messageInput.focus();
                    const emojiPicker = document.getElementById('emojiPicker');
                    if (emojiPicker) {
                        emojiPicker.classList.remove('show');
                    }
                }
                return false;
            });
        });

        // Auto-resize textarea
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', () => {
                this.autoResizeTextarea(messageInput);
            });

            // Enter key to send (Shift+Enter for new line)
            messageInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.sendMessage();
                    return false;
                }
            });
        }
    }

    async sendMessage() {
        const messageInput = document.getElementById('messageInput');
        if (!messageInput) {
            console.error('Message input not found');
            return;
        }

        const message = messageInput.value.trim();
        if (!message) {
            // Show a subtle hint that message is required
            const originalPlaceholder = messageInput.placeholder;
            messageInput.placeholder = "Message cannot be empty...";
            messageInput.classList.add('error');
            
            setTimeout(() => {
                messageInput.placeholder = originalPlaceholder;
                messageInput.classList.remove('error');
            }, 2000);
            
            // Add visual feedback
            messageInput.style.borderColor = '#dc3545';
            setTimeout(() => {
                messageInput.style.borderColor = '';
            }, 2000);
            
            return;
        }

        const sendBtn = document.getElementById('sendBtn');
        if (sendBtn) {
            sendBtn.disabled = true;
            sendBtn.style.opacity = '0.6';
        }

        try {
            const response = await fetch('public_chat_api.php?action=send_message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message
                })
            });

            const result = await response.json();

            if (result.success) {
                messageInput.value = '';
                this.autoResizeTextarea(messageInput);
                this.loadMessages();
                
                // Show success feedback
                if (sendBtn) {
                    sendBtn.style.backgroundColor = '#28a745';
                    setTimeout(() => {
                        if (sendBtn) {
                            sendBtn.style.backgroundColor = '';
                        }
                    }, 500);
                }
            } else {
                this.showNotification(result.error || 'Failed to send message', 'error');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            this.showNotification('Failed to send message. Please try again.', 'error');
        } finally {
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.style.opacity = '1';
            }
        }
    }

    async loadMessages() {
        try {
            const response = await fetch(`public_chat_api.php?action=get_messages&last_message_id=${this.lastMessageId}`);
            const result = await response.json();

            if (result.success && result.messages.length > 0) {
                this.displayMessages(result.messages);
                this.lastMessageId = Math.max(...result.messages.map(m => parseInt(m.message_id)));
                this.updateOnlineUsers(result.online_users);
                
                // Load reactions for new messages
                const messageIds = result.messages.map(m => m.message_id).join(',');
                this.loadReactions(messageIds);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    async loadReactions(messageIds) {
        if (!messageIds) return;
        
        try {
            const response = await fetch(`public_chat_api.php?action=get_reactions&message_ids=${messageIds}`);
            const result = await response.json();

            if (result.success && result.reactions.length > 0) {
                // Group reactions by message_id
                const reactionsByMessage = {};
                result.reactions.forEach(reaction => {
                    if (!reactionsByMessage[reaction.message_id]) {
                        reactionsByMessage[reaction.message_id] = [];
                    }
                    reactionsByMessage[reaction.message_id].push(reaction);
                });

                // Update reactions in UI
                Object.keys(reactionsByMessage).forEach(messageId => {
                    this.updateMessageReactions(messageId, reactionsByMessage[messageId]);
                });
            }
        } catch (error) {
            console.error('Error loading reactions:', error);
        }
    }

    displayMessages(messages) {
        const chatMessages = document.getElementById('chatMessages');

        messages.forEach(message => {
            // Check if message already exists to avoid duplicates
            if (document.getElementById(`message-${message.message_id}`)) {
                return;
            }

            const messageElement = this.createMessageElement(message);
            chatMessages.appendChild(messageElement);
        });

        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    createMessageElement(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message';
        messageDiv.id = `message-${message.message_id}`;

        const isOwnMessage = parseInt(message.sender_id) === this.currentUser.user_id;

        if (isOwnMessage) {
            messageDiv.classList.add('own');
        }

        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';

        const initials = (message.first_name?.[0] || '') + (message.last_name?.[0] || '');
        avatar.textContent = initials || '?';

        const content = document.createElement('div');
        content.className = 'message-content';

        const sender = document.createElement('div');
        sender.className = 'message-sender';
        sender.textContent = `${message.first_name} ${message.last_name}`;

        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        bubble.textContent = message.message_text;

        // Add reaction container
        const reactionContainer = document.createElement('div');
        reactionContainer.className = 'reaction-container';
        reactionContainer.id = `reactions-${message.message_id}`;

        const time = document.createElement('div');
        time.className = 'message-time';
        time.textContent = this.formatTime(message.sent_at);

        content.appendChild(sender);
        content.appendChild(bubble);
        content.appendChild(reactionContainer);
        content.appendChild(time);

        messageDiv.appendChild(avatar);
        messageDiv.appendChild(content);

        // Add click event for reactions
        bubble.addEventListener('dblclick', () => {
            this.showReactionPicker(message.message_id);
        });

        return messageDiv;
    }

    updateMessageReactions(messageId, reactions) {
        const reactionContainer = document.getElementById(`reactions-${messageId}`);
        if (!reactionContainer) return;

        // Group reactions by type
        const reactionCounts = {};
        const userReactions = new Set();
        
        reactions.forEach(reaction => {
            if (!reactionCounts[reaction.reaction_type]) {
                reactionCounts[reaction.reaction_type] = 0;
            }
            reactionCounts[reaction.reaction_type]++;
            
            if (parseInt(reaction.user_id) === this.currentUser.user_id) {
                userReactions.add(reaction.reaction_type);
            }
        });

        // Clear existing reactions
        reactionContainer.innerHTML = '';

        // Add reactions to UI
        Object.keys(reactionCounts).forEach(reactionType => {
            const reactionElement = document.createElement('div');
            reactionElement.className = 'reaction';
            if (userReactions.has(reactionType)) {
                reactionElement.classList.add('own');
            }
            
            reactionElement.innerHTML = `
                <span>${reactionType}</span>
                <span class="reaction-count">${reactionCounts[reactionType]}</span>
            `;
            
            reactionElement.addEventListener('click', () => {
                this.toggleReaction(messageId, reactionType);
            });
            
            reactionContainer.appendChild(reactionElement);
        });
    }

    showReactionPicker(messageId) {
        // For simplicity, we'll just show a simple emoji picker
        // In a real implementation, this would be a more sophisticated picker
        const emojis = ['👍', '❤️', '😂', '😮', '😢', '😡'];
        const reaction = prompt(`React with an emoji:\n${emojis.join(' ')}`);
        
        if (reaction && emojis.includes(reaction)) {
            this.toggleReaction(messageId, reaction);
        }
    }

    async toggleReaction(messageId, reactionType) {
        try {
            const response = await fetch('public_chat_api.php?action=add_reaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message_id: messageId,
                    reaction: reactionType
                })
            });

            const result = await response.json();

            if (result.success) {
                // Reload reactions for this message
                this.loadReactions(messageId.toString());
            } else {
                this.showNotification(result.error || 'Failed to add reaction', 'error');
            }
        } catch (error) {
            console.error('Error toggling reaction:', error);
            this.showNotification('Failed to add reaction. Please try again.', 'error');
        }
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

    updateOnlineUsers(onlineUsers = []) {
        if (onlineUsers.length > 0) {
            this.onlineUsers = new Set(onlineUsers);
        }
        
        const countElement = document.getElementById('onlineUsersCount');
        countElement.textContent = `${this.onlineUsers.size} user${this.onlineUsers.size !== 1 ? 's' : ''} online`;
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
}

// Initialize chat when page loads - simplified approach
let publicChat;
function initializePublicChat() {
    if (!publicChat) {
        publicChat = new PublicChat();
    }
}

// Try multiple initialization methods
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePublicChat);
} else {
    // DOM is already ready
    initializePublicChat();
}

// Also initialize after a small delay to ensure everything is loaded
setTimeout(initializePublicChat, 100);

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (publicChat) {
        publicChat.stopPolling();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>