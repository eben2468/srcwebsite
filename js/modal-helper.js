/**
 * Modal Helper
 * Utility script to handle Bootstrap modals across the site
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all Bootstrap modals on the page
    const initializeModals = function() {
        // Check if Bootstrap is loaded
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap JavaScript is not loaded. Modals will not function properly.');
            return;
        }
        
        // Get all modal triggers
        const modalTriggers = document.querySelectorAll('[data-bs-toggle="modal"]');
        
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default action
                
                const targetSelector = this.getAttribute('data-bs-target');
                if (!targetSelector) return;
                
                const modalElement = document.querySelector(targetSelector);
                if (!modalElement) {
                    console.error(`Modal element ${targetSelector} not found`);
                    return;
                }
                
                try {
                    // Check if modal is already initialized
                    let modalInstance = bootstrap.Modal.getInstance(modalElement);
                    
                    // If not initialized, create a new instance
                    if (!modalInstance) {
                        modalInstance = new bootstrap.Modal(modalElement);
                    }
                    
                    modalInstance.show();
                } catch (error) {
                    console.error('Error showing modal:', error);
                }
            });
        });
        
        // Also initialize all modal elements directly
        const modalElements = document.querySelectorAll('.modal');
        modalElements.forEach(modal => {
            try {
                // Only initialize if not already initialized
                if (!bootstrap.Modal.getInstance(modal)) {
                    new bootstrap.Modal(modal);
                }
            } catch (error) {
                console.error('Error initializing modal:', error);
            }
        });
    };
    
    // Call initialization
    initializeModals();
    
    // Re-initialize after 500ms (to catch any dynamically added modals)
    setTimeout(initializeModals, 500);
    
    // Make the function available globally
    window.initializeModals = initializeModals;
}); 