/**
 * Support Index JavaScript Functions
 * Handles support page interactions and functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Handle support card clicks
    const supportCards = document.querySelectorAll('.support-card');
    supportCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on a button or link inside the card
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('a') || e.target.closest('button')) {
                return;
            }

            const link = this.querySelector('a');
            if (link) {
                window.location.href = link.href;
            }
        });

        // Add hover effect
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
        });
    });

    // Handle quick action buttons
    const quickActionButtons = document.querySelectorAll('.quick-action-btn');
    quickActionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('data-action');
            const target = this.getAttribute('data-target');
            
            switch(action) {
                case 'navigate':
                    window.location.href = target;
                    break;
                case 'open':
                    window.open(target, '_blank');
                    break;
                case 'modal':
                    showInfoModal(target);
                    break;
                default:
                    console.log('Unknown action:', action);
            }
        });
    });

    // Handle search functionality if search box exists
    const searchBox = document.getElementById('supportSearch');
    if (searchBox) {
        searchBox.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.support-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm) || searchTerm === '') {
                    card.style.display = 'block';
                    card.style.opacity = '1';
                } else {
                    card.style.display = 'none';
                    card.style.opacity = '0.5';
                }
            });
        });
    }
});

// Show information modal
function showInfoModal(type) {
    const modalContent = {
        'system-status': {
            title: 'System Status',
            content: 'All systems are currently operational. Last updated: ' + new Date().toLocaleString(),
            icon: 'fas fa-check-circle text-success'
        },
        'maintenance': {
            title: 'Maintenance Schedule',
            content: 'Regular maintenance is performed every Sunday from 2:00 AM to 4:00 AM. No downtime is expected.',
            icon: 'fas fa-tools text-warning'
        },
        'updates': {
            title: 'Recent Updates',
            content: 'The support system has been enhanced with role-based access control and improved user experience.',
            icon: 'fas fa-sync-alt text-info'
        }
    };

    const info = modalContent[type];
    if (!info) return;

    // Remove existing modal if any
    const existingModal = document.getElementById('infoModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Create modal HTML
    const modalHTML = `
        <div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="infoModalLabel">
                            <i class="${info.icon} me-2"></i>${info.title}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>${info.content}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Show modal
    if (typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(document.getElementById('infoModal'));
        modal.show();

        // Clean up when modal is hidden
        document.getElementById('infoModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }
}

// Smooth scroll to section
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Highlight the section briefly
        element.style.backgroundColor = '#f8f9fa';
        setTimeout(() => {
            element.style.backgroundColor = '';
        }, 2000);
    }
}

// Handle notification actions
function handleNotification(action, notificationId) {
    switch(action) {
        case 'mark-read':
            markNotificationAsRead(notificationId);
            break;
        case 'dismiss':
            dismissNotification(notificationId);
            break;
        default:
            console.log('Unknown notification action:', action);
    }
}

function markNotificationAsRead(notificationId) {
    // This would typically make an AJAX call to mark the notification as read
    console.log('Marking notification as read:', notificationId);
    
    const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (notificationElement) {
        notificationElement.classList.add('read');
        notificationElement.style.opacity = '0.7';
    }
}

function dismissNotification(notificationId) {
    // This would typically make an AJAX call to dismiss the notification
    console.log('Dismissing notification:', notificationId);
    
    const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (notificationElement) {
        notificationElement.style.transition = 'opacity 0.3s ease';
        notificationElement.style.opacity = '0';
        setTimeout(() => {
            notificationElement.remove();
        }, 300);
    }
}
