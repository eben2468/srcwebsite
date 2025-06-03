/**
 * Auto-dismiss notifications after a set time
 * 
 * This script automatically dismisses alert notifications after 
 * a specified amount of time has passed.
 */

// Function to automatically dismiss alerts
function autoDismissAlerts(delay = 4000) {
    // Get all dismissible alerts
    const alerts = document.querySelectorAll('.alert.alert-dismissible');
    
    // Set a timeout for each alert
    alerts.forEach(alert => {
        setTimeout(() => {
            try {
                // Create a bootstrap alert instance and hide it
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } catch (e) {
                // Fallback method if bootstrap alert instance fails
                if (alert.querySelector('.btn-close')) {
                    alert.querySelector('.btn-close').click();
                } else {
                    alert.style.display = 'none';
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }
            }
        }, delay);
    });
}

// Run the function when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    autoDismissAlerts();
    
    // Special handling for budget page alerts
    if (window.location.pathname.includes('budget.php')) {
        // Run again after a short delay to catch any alerts that might be added after initial load
        setTimeout(() => autoDismissAlerts(), 500);
        setTimeout(() => autoDismissAlerts(), 1000);
    }
});

// Also set up a MutationObserver to handle alerts that are dynamically added
const observer = new MutationObserver(mutations => {
    mutations.forEach(mutation => {
        if (mutation.addedNodes && mutation.addedNodes.length > 0) {
            // Check if any of the added nodes are alerts or contain alerts
            mutation.addedNodes.forEach(node => {
                if (node.classList && node.classList.contains('alert') && node.classList.contains('alert-dismissible')) {
                    // If the added node is an alert itself
                    setTimeout(() => {
                        try {
                            const bsAlert = new bootstrap.Alert(node);
                            bsAlert.close();
                        } catch (e) {
                            // Fallback method
                            if (node.querySelector('.btn-close')) {
                                node.querySelector('.btn-close').click();
                            } else {
                                node.style.display = 'none';
                                if (node.parentNode) {
                                    node.parentNode.removeChild(node);
                                }
                            }
                        }
                    }, 4000);
                } else if (node.querySelectorAll) {
                    // If the added node might contain alerts
                    const alerts = node.querySelectorAll('.alert.alert-dismissible');
                    alerts.forEach(alert => {
                        setTimeout(() => {
                            try {
                                const bsAlert = new bootstrap.Alert(alert);
                                bsAlert.close();
                            } catch (e) {
                                // Fallback method
                                if (alert.querySelector('.btn-close')) {
                                    alert.querySelector('.btn-close').click();
                                } else {
                                    alert.style.display = 'none';
                                    if (alert.parentNode) {
                                        alert.parentNode.removeChild(alert);
                                    }
                                }
                            }
                        }, 4000);
                    });
                }
            });
        }
    });
});

// Start observing the document body for changes
observer.observe(document.body, {
    childList: true,
    subtree: true
}); 