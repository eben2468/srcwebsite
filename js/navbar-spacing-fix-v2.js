/**
 * Navbar Spacing Fix V2 - Optimized Balance
 * This version provides the perfect balance between compact and usable
 */

(function() {
    'use strict';

    // Optimized configuration for better usability
    const CONFIG = {
        gap: '3px',
        buttonMargin: '2px',
        buttonPadding: '4px',
        buttonWidth: '30px',
        buttonHeight: '30px',
        userDropdownMaxWidth: '70px',
        fontSize: '0.8rem'
    };

    function applyNavbarSpacing() {
        console.log('🔧 Applying optimized navbar spacing...');
        
        // Only apply on mobile/tablet
        if (window.innerWidth > 991) {
            console.log('Desktop view - skipping navbar spacing fix');
            return;
        }

        const rightSection = document.querySelector('.navbar .d-flex.ms-auto');
        if (!rightSection) {
            console.log('Navbar right section not found');
            return;
        }

        // Apply container styles
        rightSection.style.setProperty('gap', CONFIG.gap, 'important');
        rightSection.style.setProperty('margin', '0px', 'important');
        rightSection.style.setProperty('padding', '0px', 'important');
        rightSection.style.setProperty('display', 'flex', 'important');
        rightSection.style.setProperty('align-items', 'center', 'important');

        // Process all direct children
        const children = Array.from(rightSection.children);
        children.forEach((child, index) => {
            console.log(`Processing child ${index + 1}:`, child.tagName, child.className);

            // Apply margins to all children
            child.style.setProperty('margin', CONFIG.buttonMargin, 'important');

            // Handle buttons
            if (child.tagName === 'BUTTON') {
                applyButtonStyles(child);
            }

            // Handle dropdowns
            if (child.classList.contains('dropdown')) {
                const button = child.querySelector('button');
                if (button) {
                    applyButtonStyles(button, button.classList.contains('dropdown-toggle'));
                }
            }
        });

        // Inject CSS for additional enforcement
        injectCSS();

        console.log('✅ Navbar spacing applied successfully');
    }

    function applyButtonStyles(button, isDropdownToggle = false) {
        if (isDropdownToggle) {
            // User dropdown - wider but still compact
            button.style.setProperty('width', 'auto', 'important');
            button.style.setProperty('max-width', CONFIG.userDropdownMaxWidth, 'important');
            button.style.setProperty('height', CONFIG.buttonHeight, 'important');
            button.style.setProperty('padding', '2px 6px', 'important');
            button.style.setProperty('font-size', '0.7rem', 'important');
        } else {
            // Regular buttons
            button.style.setProperty('width', CONFIG.buttonWidth, 'important');
            button.style.setProperty('height', CONFIG.buttonHeight, 'important');
            button.style.setProperty('min-width', CONFIG.buttonWidth, 'important');
            button.style.setProperty('padding', CONFIG.buttonPadding, 'important');
            button.style.setProperty('font-size', CONFIG.fontSize, 'important');
        }

        // Common button styles
        button.style.setProperty('margin', CONFIG.buttonMargin, 'important');
        button.style.setProperty('flex-shrink', '0', 'important');
        button.style.setProperty('border-radius', '4px', 'important');
        button.style.setProperty('display', 'inline-flex', 'important');
        button.style.setProperty('align-items', 'center', 'important');
        button.style.setProperty('justify-content', 'center', 'important');
    }

    function injectCSS() {
        // Remove existing style if present
        const existingStyle = document.getElementById('navbar-spacing-fix-v2');
        if (existingStyle) {
            existingStyle.remove();
        }

        const style = document.createElement('style');
        style.id = 'navbar-spacing-fix-v2';
        style.innerHTML = `
            /* Navbar Spacing Fix V2 - Optimized */
            @media (max-width: 991px) {
                .navbar .d-flex.ms-auto {
                    gap: ${CONFIG.gap} !important;
                    margin: 0px !important;
                    padding: 0px !important;
                }
                
                .navbar .d-flex.ms-auto > * {
                    margin: ${CONFIG.buttonMargin} !important;
                }
                
                .navbar .d-flex.ms-auto button:not(.dropdown-toggle) {
                    width: ${CONFIG.buttonWidth} !important;
                    height: ${CONFIG.buttonHeight} !important;
                    min-width: ${CONFIG.buttonWidth} !important;
                    padding: ${CONFIG.buttonPadding} !important;
                    font-size: ${CONFIG.fontSize} !important;
                    margin: ${CONFIG.buttonMargin} !important;
                    flex-shrink: 0 !important;
                }
                
                .navbar .d-flex.ms-auto .dropdown-toggle {
                    width: auto !important;
                    max-width: ${CONFIG.userDropdownMaxWidth} !important;
                    height: ${CONFIG.buttonHeight} !important;
                    padding: 2px 6px !important;
                    font-size: 0.7rem !important;
                    margin: ${CONFIG.buttonMargin} !important;
                }
                
                .navbar .d-flex.ms-auto .dropdown {
                    margin: ${CONFIG.buttonMargin} !important;
                }
            }
        `;
        
        document.head.appendChild(style);
        console.log('✅ CSS injected');
    }

    function init() {
        console.log('🚀 Initializing Navbar Spacing Fix V2...');
        
        // Apply immediately
        applyNavbarSpacing();
        
        // Apply on resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(applyNavbarSpacing, 100);
        });
        
        // Apply on DOM changes
        const observer = new MutationObserver(function(mutations) {
            let shouldReapply = false;
            mutations.forEach(function(mutation) {
                if (mutation.target.closest && mutation.target.closest('.navbar')) {
                    shouldReapply = true;
                }
            });
            
            if (shouldReapply) {
                setTimeout(applyNavbarSpacing, 50);
            }
        });
        
        // Start observing
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            observer.observe(navbar, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        }
        
        // Periodic reapplication (less aggressive)
        setInterval(applyNavbarSpacing, 2000);
        
        console.log('✅ Navbar Spacing Fix V2 initialized');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Also try after a delay
    setTimeout(init, 500);

    // Expose function globally
    window.applyNavbarSpacing = applyNavbarSpacing;

})();