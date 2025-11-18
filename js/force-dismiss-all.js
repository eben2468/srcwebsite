// Force dismiss all notifications and alerts
(function() {
    'use strict';
    
    // Function to dismiss all alerts and notifications
    function forceDismissAll() {
        // Dismiss Bootstrap alerts
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            if (alert.querySelector('.btn-close')) {
                alert.remove();
            }
        });
        
        // Dismiss any notification toasts
        const toasts = document.querySelectorAll('.toast');
        toasts.forEach(toast => {
            if (toast.classList.contains('show')) {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }
        });
        
        // Clear any notification badges
        const badges = document.querySelectorAll('.notification-badge');
        badges.forEach(badge => {
            if (badge.textContent === '0' || badge.textContent === '') {
                badge.style.display = 'none';
            }
        });
    }
    
    // Auto-run on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Add a small delay to ensure all elements are loaded
        setTimeout(forceDismissAll, 1000);
    });
    
    // Expose function globally
    window.forceDismissAll = forceDismissAll;
})();