/**
 * Notifications JavaScript Functions
 * Handles notification filtering, marking as read, and interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize filter tabs
    const filterTabs = document.querySelectorAll('.filter-tab');
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const filterType = this.getAttribute('data-filter');
            filterNotifications(filterType);
        });
    });

    // Initialize notification action buttons
    const actionButtons = document.querySelectorAll('.notification-action');
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('data-action');
            const notificationId = this.getAttribute('data-notification-id');
            handleNotificationAction(action, notificationId);
        });
    });

    // Initialize mark all as read button
    const markAllButton = document.getElementById('markAllRead');
    if (markAllButton) {
        markAllButton.addEventListener('click', function(e) {
            e.preventDefault();
            markAllAsRead();
        });
    }

    // Initialize clear all button
    const clearAllButton = document.getElementById('clearAll');
    if (clearAllButton) {
        clearAllButton.addEventListener('click', function(e) {
            e.preventDefault();
            clearAllNotifications();
        });
    }

    // Auto-refresh notifications every 30 seconds
    setInterval(refreshNotifications, 30000);
});

// Filter notifications by type
function filterNotifications(type) {
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
        if (tab.getAttribute('data-filter') === type) {
            tab.classList.add('active');
        }
    });

    // Show/hide notifications based on type
    const notifications = document.querySelectorAll('.notification-item');
    notifications.forEach(notification => {
        const notificationType = notification.getAttribute('data-type');
        
        if (type === 'all' || notificationType === type) {
            notification.style.display = 'block';
            notification.style.opacity = '1';
        } else {
            notification.style.display = 'none';
            notification.style.opacity = '0.5';
        }
    });

    // Update notification count
    updateNotificationCount(type);
}

// Handle notification actions
function handleNotificationAction(action, notificationId) {
    switch(action) {
        case 'mark-read':
            markAsRead(notificationId);
            break;
        case 'mark-unread':
            markAsUnread(notificationId);
            break;
        case 'delete':
            deleteNotification(notificationId);
            break;
        case 'archive':
            archiveNotification(notificationId);
            break;
        default:
            console.log('Unknown notification action:', action);
    }
}

// Mark notification as read
function markAsRead(notificationId) {
    const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (notification) {
        notification.classList.add('read');
        notification.classList.remove('unread');
        
        // Update the action button
        const actionButton = notification.querySelector('.notification-action[data-action="mark-read"]');
        if (actionButton) {
            actionButton.setAttribute('data-action', 'mark-unread');
            actionButton.innerHTML = '<i class="fas fa-envelope me-1"></i>Mark Unread';
        }
        
        // Make AJAX call to update database
        updateNotificationStatus(notificationId, 'read');
    }
}

// Mark notification as unread
function markAsUnread(notificationId) {
    const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (notification) {
        notification.classList.add('unread');
        notification.classList.remove('read');
        
        // Update the action button
        const actionButton = notification.querySelector('.notification-action[data-action="mark-unread"]');
        if (actionButton) {
            actionButton.setAttribute('data-action', 'mark-read');
            actionButton.innerHTML = '<i class="fas fa-envelope-open me-1"></i>Mark Read';
        }
        
        // Make AJAX call to update database
        updateNotificationStatus(notificationId, 'unread');
    }
}

// Delete notification
function deleteNotification(notificationId) {
    if (confirm('Are you sure you want to delete this notification?')) {
        const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
        if (notification) {
            notification.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            
            setTimeout(() => {
                notification.remove();
                updateNotificationCount();
            }, 300);
            
            // Make AJAX call to delete from database
            deleteNotificationFromDB(notificationId);
        }
    }
}

// Archive notification
function archiveNotification(notificationId) {
    const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (notification) {
        notification.classList.add('archived');
        notification.style.opacity = '0.5';
        
        // Make AJAX call to archive in database
        updateNotificationStatus(notificationId, 'archived');
        
        // Show success message
        showToast('Notification archived successfully', 'success');
    }
}

// Mark all notifications as read
function markAllAsRead() {
    const unreadNotifications = document.querySelectorAll('.notification-item.unread');
    unreadNotifications.forEach(notification => {
        const notificationId = notification.getAttribute('data-notification-id');
        markAsRead(notificationId);
    });
    
    showToast('All notifications marked as read', 'success');
}

// Clear all notifications
function clearAllNotifications() {
    if (confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
        const notifications = document.querySelectorAll('.notification-item');
        notifications.forEach(notification => {
            const notificationId = notification.getAttribute('data-notification-id');
            deleteNotification(notificationId);
        });
    }
}

// Update notification count
function updateNotificationCount(filterType = 'all') {
    const visibleNotifications = document.querySelectorAll('.notification-item[style*="display: block"], .notification-item:not([style*="display: none"])');
    const count = visibleNotifications.length;
    
    const countElement = document.querySelector('.notification-count');
    if (countElement) {
        countElement.textContent = count;
    }
    
    // Update tab counts
    const tabs = document.querySelectorAll('.filter-tab');
    tabs.forEach(tab => {
        const tabType = tab.getAttribute('data-filter');
        const tabNotifications = document.querySelectorAll(`.notification-item[data-type="${tabType}"]`);
        const tabCount = tab.querySelector('.tab-count');
        if (tabCount) {
            tabCount.textContent = tabNotifications.length;
        }
    });
}

// Refresh notifications
function refreshNotifications() {
    // This would typically make an AJAX call to get new notifications
    console.log('Refreshing notifications...');
    
    // For now, just update the timestamp
    const timestampElements = document.querySelectorAll('.notification-time');
    timestampElements.forEach(element => {
        // Update relative time display
        const timestamp = element.getAttribute('data-timestamp');
        if (timestamp) {
            element.textContent = getRelativeTime(new Date(timestamp));
        }
    });
}

// Update notification status in database
function updateNotificationStatus(notificationId, status) {
    // This would make an AJAX call to update the database
    console.log(`Updating notification ${notificationId} status to ${status}`);
    
    // Example AJAX call (commented out as it requires backend implementation)
    /*
    fetch('update_notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            notification_id: notificationId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Notification updated successfully');
        } else {
            console.error('Failed to update notification');
        }
    })
    .catch(error => {
        console.error('Error updating notification:', error);
    });
    */
}

// Delete notification from database
function deleteNotificationFromDB(notificationId) {
    // This would make an AJAX call to delete from database
    console.log(`Deleting notification ${notificationId} from database`);
}

// Show toast message
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} toast-message`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'info'}-circle me-2"></i>
        ${message}
    `;
    
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => {
        toast.style.opacity = '1';
    }, 100);
    
    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// Get relative time string
function getRelativeTime(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Just now';
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
