/**
 * Modal Helper
 * Utility script to handle Bootstrap modals across the site
 */

document.addEventListener('DOMContentLoaded', function() {
    // Global function to remove modal backdrops
    const removeBackdrops = function() {
        // Remove any existing modal backdrops
        const backdrops = document.querySelectorAll(".modal-backdrop");
        backdrops.forEach(function(backdrop) {
            backdrop.remove();
        });
        
        // Fix body classes
        document.body.classList.remove("modal-open");
        document.body.style.overflow = "";
        document.body.style.paddingRight = "";
        document.body.removeAttribute("style");
    };
    
    // Function to adjust modal position below header
    const adjustModalPosition = function(modalElement) {
        if (!modalElement) return;
        
        // Get header height
        const header = document.querySelector('.navbar');
        const headerHeight = header ? header.offsetHeight : 60;
        
        // Add a slight buffer
        const topPosition = headerHeight + 10;
        
        // Set modal dialog top margin
        const modalDialog = modalElement.querySelector('.modal-dialog');
        if (modalDialog) {
            modalDialog.style.marginTop = topPosition + 'px';
            
            // Also adjust max height for content to prevent extending beyond viewport
            const modalContent = modalDialog.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.maxHeight = `calc(100vh - ${topPosition + 50}px)`;
                modalContent.style.overflowY = 'auto';
            }
        }
    };
    
    // Remove backdrops immediately when page loads
    removeBackdrops();
    
    // Set an interval to continuously check and remove any backdrops
    const backdropInterval = setInterval(removeBackdrops, 500);
    
    // Add a mutation observer to detect any new backdrops being added
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                for (let i = 0; i < mutation.addedNodes.length; i++) {
                    const node = mutation.addedNodes[i];
                    if (node.classList && node.classList.contains("modal-backdrop")) {
                        node.remove();
                    }
                }
            }
        });
    });
    
    // Start observing the document body for added nodes
    observer.observe(document.body, { childList: true, subtree: true });
    
    // Override Bootstrap's modal method to prevent adding backdrop
    if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
        const originalModalShow = bootstrap.Modal.prototype.show;
        bootstrap.Modal.prototype.show = function() {
            // Set backdrop option to false for all modals
            if (this._config) {
                this._config.backdrop = false;
            }
            
            // Call the original method
            originalModalShow.apply(this, arguments);
            
            // Adjust modal position
            if (this._element) {
                adjustModalPosition(this._element);
            }
            
            // Then remove any backdrops
            setTimeout(removeBackdrops, 50);
        };
    }
    
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
                    // First, remove any lingering backdrops
                    removeBackdrops();
                    
                    // Add data-bs-backdrop="false" attribute to the modal
                    modalElement.setAttribute('data-bs-backdrop', 'false');
                    
                    // Check if modal is already initialized
                    let modalInstance = bootstrap.Modal.getInstance(modalElement);
                    
                    // If not initialized, create a new instance
                    if (!modalInstance) {
                        modalInstance = new bootstrap.Modal(modalElement, {
                            backdrop: false,
                            keyboard: true,
                            focus: true
                        });
                    }
                    
                    // Add event listeners for modal events
                    modalElement.addEventListener('hidden.bs.modal', removeBackdrops);
                    modalElement.addEventListener('show.bs.modal', function() {
                        removeBackdrops();
                        // Adjust position when modal is shown
                        adjustModalPosition(modalElement);
                    });
                    
                    modalInstance.show();
                    
                    // Adjust position after modal is shown
                    setTimeout(function() {
                        adjustModalPosition(modalElement);
                    }, 100);
                    
                    // Remove backdrops again after a short delay
                    setTimeout(removeBackdrops, 100);
                } catch (error) {
                    console.error('Error showing modal:', error);
                }
            });
        });
        
        // Also initialize all modal elements directly
        const modalElements = document.querySelectorAll('.modal');
        modalElements.forEach(modal => {
            try {
                // Add data-bs-backdrop="false" attribute
                modal.setAttribute('data-bs-backdrop', 'false');
                
                // Only initialize if not already initialized
                if (!bootstrap.Modal.getInstance(modal)) {
                    const modalInstance = new bootstrap.Modal(modal, {
                        backdrop: false,
                        keyboard: true,
                        focus: true
                    });
                    
                    // Add event listeners for modal events
                    modal.addEventListener('hidden.bs.modal', removeBackdrops);
                    modal.addEventListener('show.bs.modal', function() {
                        removeBackdrops();
                        // Adjust position when modal is shown
                        adjustModalPosition(modal);
                    });
                }
                
                // Pre-adjust position for all modals
                adjustModalPosition(modal);
            } catch (error) {
                console.error('Error initializing modal:', error);
            }
        });
    };
    
    // Call initialization
    initializeModals();
    
    // Re-initialize after 500ms (to catch any dynamically added modals)
    setTimeout(initializeModals, 500);
    
    // Make the functions available globally
    window.initializeModals = initializeModals;
    window.clearModalBackdrops = removeBackdrops;
    window.adjustModalPosition = adjustModalPosition;
    
    // Add a global click event listener to clear backdrops when clicking outside modal
    document.addEventListener('click', function(e) {
        if (e.target.classList && e.target.classList.contains('modal')) {
            removeBackdrops();
        }
    });
    
    // Also clear backdrops when ESC key is pressed
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            removeBackdrops();
        }
    });
    
    // On window resize, readjust modal positions
    window.addEventListener('resize', function() {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(function(modal) {
            adjustModalPosition(modal);
        });
    });
}); 