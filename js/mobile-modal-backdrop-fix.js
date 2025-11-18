/**
 * Mobile Modal Backdrop Fix
 * Fixes the black screen backdrop issue on mobile devices
 */

(function() {
    'use strict';
    
    // Detect mobile devices
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                     window.innerWidth <= 768;
    
    if (!isMobile) return; // Only apply fixes on mobile
    
    // Initialize modal fixes when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileModalFixes);
    } else {
        initMobileModalFixes();
    }
    
    function initMobileModalFixes() {
        console.log('Initializing mobile modal backdrop fixes...');
        
        // Fix existing modals
        fixExistingModals();
        
        // Set up event listeners for modal events
        setupModalEventListeners();
        
        // Fix Bootstrap modal backdrop behavior
        fixBootstrapModalBackdrop();
        
        // Handle touch events properly
        setupTouchEventHandlers();
    }
    
    function fixExistingModals() {
        // Find all Bootstrap modals and disable backdrop
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            // Disable backdrop for mobile
            modal.setAttribute('data-bs-backdrop', 'false');
            modal.setAttribute('data-bs-keyboard', 'true');
            
            // Ensure proper z-index
            modal.style.zIndex = '1055';
            
            // Fix modal dialog
            const modalDialog = modal.querySelector('.modal-dialog');
            if (modalDialog) {
                modalDialog.style.pointerEvents = 'auto';
                modalDialog.style.position = 'relative';
                modalDialog.style.zIndex = '1055';
            }
        });
        
        // Fix custom modal overlays
        const customOverlays = document.querySelectorAll('.modal-overlay');
        customOverlays.forEach(overlay => {
            overlay.style.pointerEvents = 'none';
            overlay.addEventListener('click', function(e) {
                e.stopPropagation();
                // Close the modal when clicking overlay
                const modal = overlay.querySelector('.modal');
                if (modal) {
                    closeModal(modal);
                }
            });
        });
    }
    
    function setupModalEventListeners() {
        // Listen for modal show events
        document.addEventListener('show.bs.modal', function(e) {
            const modal = e.target;
            console.log('Modal showing:', modal.id);
            
            // Remove any existing backdrop
            removeExistingBackdrops();
            
            // Ensure modal is properly positioned
            setTimeout(() => {
                fixModalPositioning(modal);
            }, 50);
        });
        
        // Listen for modal shown events
        document.addEventListener('shown.bs.modal', function(e) {
            const modal = e.target;
            console.log('Modal shown:', modal.id);
            
            // Fix backdrop issues after modal is shown
            fixModalBackdropAfterShow(modal);
            
            // Focus management
            focusFirstElement(modal);
        });
        
        // Listen for modal hide events
        document.addEventListener('hide.bs.modal', function(e) {
            const modal = e.target;
            console.log('Modal hiding:', modal.id);
            
            // Clean up any backdrop issues
            cleanupModalBackdrop(modal);
        });
        
        // Listen for modal hidden events
        document.addEventListener('hidden.bs.modal', function(e) {
            const modal = e.target;
            console.log('Modal hidden:', modal.id);
            
            // Final cleanup
            finalCleanup();
        });
    }
    
    function fixBootstrapModalBackdrop() {
        // Override Bootstrap's modal backdrop behavior
        if (window.bootstrap && window.bootstrap.Modal) {
            const originalShow = window.bootstrap.Modal.prototype.show;
            window.bootstrap.Modal.prototype.show = function() {
                // Disable backdrop for mobile
                this._config.backdrop = false;
                return originalShow.call(this);
            };
        }
    }
    
    function setupTouchEventHandlers() {
        // Handle touch events on modal backdrops
        document.addEventListener('touchstart', function(e) {
            const backdrop = e.target.closest('.modal-backdrop');
            if (backdrop) {
                e.preventDefault();
                e.stopPropagation();
                
                // Find and close the associated modal
                const modal = document.querySelector('.modal.show');
                if (modal) {
                    closeModal(modal);
                }
            }
        }, { passive: false });
        
        // Prevent touch events from bubbling on modal content
        document.addEventListener('touchstart', function(e) {
            const modalContent = e.target.closest('.modal-content');
            if (modalContent) {
                e.stopPropagation();
            }
        });
    }
    
    function removeExistingBackdrops() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.style.pointerEvents = 'none';
            backdrop.style.opacity = '0.3';
        });
    }
    
    function fixModalPositioning(modal) {
        const modalDialog = modal.querySelector('.modal-dialog');
        if (modalDialog) {
            modalDialog.style.pointerEvents = 'auto';
            modalDialog.style.position = 'relative';
            modalDialog.style.zIndex = '1055';
            
            // Ensure modal content is clickable
            const modalContent = modalDialog.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.pointerEvents = 'auto';
                modalContent.style.position = 'relative';
            }
        }
    }
    
    function fixModalBackdropAfterShow(modal) {
        setTimeout(() => {
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.style.pointerEvents = 'none';
                backdrop.style.opacity = '0.3';
                
                // Add click handler to backdrop for closing modal
                backdrop.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeModal(modal);
                });
            }
        }, 100);
    }
    
    function focusFirstElement(modal) {
        // Focus first focusable element in modal
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length > 0) {
            focusableElements[0].focus();
        }
    }
    
    function cleanupModalBackdrop(modal) {
        // Remove pointer-events blocking
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.style.pointerEvents = 'none';
        }
        
        // Ensure body is scrollable
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    function finalCleanup() {
        // Final cleanup after modal is completely hidden
        setTimeout(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                if (!document.querySelector('.modal.show')) {
                    backdrop.remove();
                }
            });
            
            // Restore body scroll
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            document.body.classList.remove('modal-open');
        }, 150);
    }
    
    function closeModal(modal) {
        if (window.bootstrap && window.bootstrap.Modal) {
            const modalInstance = window.bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        } else {
            // Fallback for custom modals
            modal.style.display = 'none';
            modal.classList.remove('show');
            
            // Remove backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            // Restore body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }
    }
    
    // Handle custom modal close buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('.modal-close, [data-modal-close]')) {
            e.preventDefault();
            const modal = e.target.closest('.modal, .modal-overlay');
            if (modal) {
                closeModal(modal);
            }
        }
    });
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.querySelector('.modal.show');
            if (modal) {
                closeModal(modal);
            }
        }
    });
    
    // Emergency fix for completely broken modals
    function emergencyModalFix() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.style.display = 'none';
        });
        
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        document.body.classList.remove('modal-open');
    }
    
    // Expose emergency fix globally
    window.emergencyModalFix = emergencyModalFix;
    
    console.log('Mobile modal backdrop fixes initialized successfully');
})();  
  // Ensure all page buttons remain clickable
    function ensureButtonsClickable() {
        // Fix all buttons on the page
        const allButtons = document.querySelectorAll('button, .btn, a, input, select, textarea, [onclick], [data-bs-toggle], [data-bs-dismiss], [data-bs-target]');
        allButtons.forEach(element => {
            if (!element.closest('.modal-backdrop')) {
                element.style.pointerEvents = 'auto';
                element.style.touchAction = 'manipulation';
                if (element.tagName === 'BUTTON' || element.classList.contains('btn') || element.hasAttribute('onclick')) {
                    element.style.cursor = 'pointer';
                }
            }
        });
        
        // Special fix for gallery buttons
        const galleryButtons = document.querySelectorAll('.gallery-item button, .gallery-item .btn, .media-controls button, .media-controls .btn');
        galleryButtons.forEach(button => {
            button.style.pointerEvents = 'auto';
            button.style.touchAction = 'manipulation';
            button.style.cursor = 'pointer';
        });
    }
    
    // Run button fix on page load and after modal events
    document.addEventListener('DOMContentLoaded', ensureButtonsClickable);
    document.addEventListener('shown.bs.modal', ensureButtonsClickable);
    document.addEventListener('hidden.bs.modal', ensureButtonsClickable);
    
    // Run periodically to catch dynamically added buttons
    setInterval(ensureButtonsClickable, 2000);
    
    console.log('Enhanced mobile modal backdrop fixes with button clickability protection loaded');