/**
 * Auto-dismiss notifications after a set time
 * 
 * This script automatically dismisses non-critical alert notifications after 
 * a specified amount of time has passed (default: 8 seconds).
 * Important notifications that users must read are excluded from auto-dismissal.
 */

// Function to check if we're on the portfolio-detail.php page
function isPortfolioDetailPage() {
    return window.location.href.includes('portfolio-detail.php');
}

// Function to check if an alert is important and should not be auto-dismissed
function isImportantAlert(alert) {
    // First check if we're on the portfolio-detail page - if so, all alerts are important
    if (isPortfolioDetailPage()) {
        return true;
    }
    
    // Check for CSS classes that indicate important alerts
    if (alert.classList.contains('alert-important') || 
        alert.classList.contains('alert-critical') || 
        alert.classList.contains('notification-important') ||
        alert.classList.contains('no-auto-dismiss')) {
        return true;
    }
    
    // Check for data attributes that indicate important alerts
    if (alert.dataset.important === 'true' || 
        alert.dataset.autoDismiss === 'false' ||
        alert.hasAttribute('data-no-auto-dismiss')) {
        return true;
    }
    
    // Check for content that suggests the alert is important
    const alertContent = alert.textContent.toLowerCase();
    const importantKeywords = [
        'important', 
        'attention', 
        'critical', 
        'warning', 
        'must read',
        'required action',
        'please note',
        'privacy'
    ];
    
    if (importantKeywords.some(keyword => alertContent.includes(keyword))) {
        return true;
    }
    
    // Check if the alert contains any elements with important classes
    if (alert.querySelector('.important-content, .critical-info, .required-action')) {
        return true;
    }
    
    // Check if the alert is in a specific context that indicates importance
    const parentContext = alert.closest('.important-section, .privacy-checks, .security-alerts');
    if (parentContext) {
        return true;
    }
    
    // Not an important alert
    return false;
}

// Main function to auto-dismiss alerts
function autoDismissAlerts() {
    // Skip auto-dismissal on portfolio-detail.php
    if (isPortfolioDetailPage()) {
        console.log('Auto-dismiss skipped for portfolio-detail.php');
        return;
    }
    
    // Find all alert elements
    const alerts = document.querySelectorAll('.alert, .alert-dismissible, .notification, .toast');
    
    alerts.forEach(alert => {
        // Skip if this alert is already being dismissed
        if (alert.dataset.autoDismissing === 'true') {
            return;
        }
        
        // Skip important alerts that should not be auto-dismissed
        if (isImportantAlert(alert)) {
            return;
        }
        
        // Mark this alert as being dismissed
        alert.dataset.autoDismissing = 'true';
        
        // Get dismiss delay (default: 8000ms = 8 seconds)
        const dismissDelay = alert.dataset.dismissDelay || 8000;
        
        // Set timeout to dismiss the alert
        setTimeout(() => {
            // Check if the alert has a close button
            const closeButton = alert.querySelector('.close, .btn-close');
            if (closeButton) {
                // Trigger click on close button
                closeButton.click();
            } else {
                // Manually fade out and remove the alert
                alert.style.transition = 'opacity 0.8s ease';
                alert.style.opacity = '0';
                
                setTimeout(() => {
                    alert.remove();
                }, 800);
            }
        }, dismissDelay);
    });
}

// Initialize when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Skip initialization on portfolio-detail.php
    if (isPortfolioDetailPage()) {
        console.log('Auto-dismiss initialization skipped for portfolio-detail.php');
        return;
    }
    
    // Initial run after a short delay
    setTimeout(autoDismissAlerts, 1000);
    
    // Set interval to check for new alerts periodically
    setInterval(() => {
        // Skip if on portfolio-detail.php
        if (isPortfolioDetailPage()) {
            return;
        }
        autoDismissAlerts();
    }, 2000);
    
    // Set up a MutationObserver to handle alerts that are added dynamically
    const observer = new MutationObserver(mutations => {
        // Skip if on portfolio-detail.php
        if (isPortfolioDetailPage()) {
            return;
        }
        
        mutations.forEach(mutation => {
            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                // Check if any of the added nodes are alerts or contain alerts
                let hasAlerts = false;
                
                mutation.addedNodes.forEach(node => {
                    // Check if the node itself is an alert
                    if (node.nodeType === 1 && 
                        (node.classList && 
                         (node.classList.contains('alert') || 
                          node.classList.contains('notification') || 
                          node.classList.contains('toast')))) {
                        hasAlerts = true;
                    }
                    
                    // Check if the node contains alerts
                    if (node.nodeType === 1 && node.querySelectorAll) {
                        const childAlerts = node.querySelectorAll('.alert, .notification, .toast');
                        if (childAlerts.length > 0) {
                            hasAlerts = true;
                        }
                    }
                });
                
                // If alerts were added, run the auto-dismiss function
                if (hasAlerts) {
                    setTimeout(autoDismissAlerts, 100);
                }
            }
        });
    });
    
    // Start observing the document body for changes
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}); 