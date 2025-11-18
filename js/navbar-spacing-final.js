/**
 * Final Navbar Spacing Solution
 * Adds proper spacing between buttons and increases size slightly for better usability
 */

(function() {
    'use strict';

    // Final optimized configuration - perfect balance
    const FINAL_CONFIG = {
        gap: '4px',              // Small but visible gap between buttons
        buttonMargin: '3px',     // Margin around each button
        buttonPadding: '5px',    // Internal button padding
        buttonWidth: '32px',     // Slightly larger buttons
        buttonHeight: '32px',    // Slightly larger buttons
        userDropdownMaxWidth: '75px',  // User dropdown width
        fontSize: '0.85rem'      // Readable font size
    };

    function applyFinalSpacing() {
        console.log('🎯 Applying final navbar spacing...');
        
        // Only apply on mobile/tablet screens
        if (window.innerWidth > 991) {
            console.log('Desktop view - no spacing adjustment needed');
            return;
        }

        const rightSection = document.querySelector('.navbar .d-flex.ms-auto');
        if (!rightSection) {
            console.log('Navbar right section not found');
            return;
        }

        // Apply container styles with proper spacing
        rightSection.style.setProperty('gap', FINAL_CONFIG.gap, 'important');
        rightSection.style.setProperty('margin', '0px', 'important');
        rightSection.style.setProperty('padding', '0px', 'important');
        rightSection.style.setProperty('display', 'flex', 'important');
        rightSection.style.setProperty('align-items', 'center', 'important');
        rightSection.style.setProperty('justify-content', 'flex-end', 'important');

        // Process all direct children (buttons and dropdowns)
        const children = Array.from(rightSection.children);
        children.forEach((child, index) => {
            console.log(`Processing element ${index + 1}:`, child.tagName, child.className);

            // Apply spacing to all children
            child.style.setProperty('margin', FINAL_CONFIG.buttonMargin, 'important');

            // Handle direct buttons
            if (child.tagName === 'BUTTON') {
                applyFinalButtonStyles(child);
            }

            // Handle dropdown containers
            if (child.classList.contains('dropdown')) {
                child.style.setProperty('margin', FINAL_CONFIG.buttonMargin, 'important');
                
                const button = child.querySelector('button');
                if (button) {
                    const isUserDropdown = button.classList.contains('dropdown-toggle');
                    applyFinalButtonStyles(button, isUserDropdown);
                }
            }
        });

        // Inject supporting CSS
        injectFinalCSS();

        console.log('✅ Final navbar spacing applied successfully');
    }

    function applyFinalButtonStyles(button, isUserDropdown = false) {
        if (isUserDropdown) {
            // User dropdown - wider but controlled
            button.style.setProperty('width', 'auto', 'important');
            button.style.setProperty('max-width', FINAL_CONFIG.userDropdownMaxWidth, 'important');
            button.style.setProperty('height', FINAL_CONFIG.buttonHeight, 'important');
            button.style.setProperty('padding', '3px 8px', 'important');
            button.style.setProperty('font-size', '0.75rem', 'important');
        } else {
            // Regular icon buttons
            button.style.setProperty('width', FINAL_CONFIG.buttonWidth, 'important');
            button.style.setProperty('height', FINAL_CONFIG.buttonHeight, 'important');
            button.style.setProperty('min-width', FINAL_CONFIG.buttonWidth, 'important');
            button.style.setProperty('padding', FINAL_CONFIG.buttonPadding, 'important');
            button.style.setProperty('font-size', FINAL_CONFIG.fontSize, 'important');
        }

        // Common styles for all buttons
        button.style.setProperty('margin', FINAL_CONFIG.buttonMargin, 'important');
        button.style.setProperty('flex-shrink', '0', 'important');
        button.style.setProperty('border-radius', '6px', 'important');
        button.style.setProperty('display', 'inline-flex', 'important');
        button.style.setProperty('align-items', 'center', 'important');
        button.style.setProperty('justify-content', 'center', 'important');
        button.style.setProperty('box-sizing', 'border-box', 'important');
    }

    function injectFinalCSS() {
        // Remove any existing styles
        const existingStyle = document.getElementById('navbar-spacing-final');
        if (existingStyle) {
            existingStyle.remove();
        }

        const style = document.createElement('style');
        style.id = 'navbar-spacing-final';
        style.innerHTML = `
            /* Final Navbar Spacing Solution */
            @media (max-width: 991px) {
                .navbar .d-flex.ms-auto {
                    gap: ${FINAL_CONFIG.gap} !important;
                    margin: 0px !important;
                    padding: 0px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: flex-end !important;
                }
                
                .navbar .d-flex.ms-auto > * {
                    margin: ${FINAL_CONFIG.buttonMargin} !important;
                    flex-shrink: 0 !important;
                }
                
                .navbar .d-flex.ms-auto button:not(.dropdown-toggle) {
                    width: ${FINAL_CONFIG.buttonWidth} !important;
                    height: ${FINAL_CONFIG.buttonHeight} !important;
                    min-width: ${FINAL_CONFIG.buttonWidth} !important;
                    padding: ${FINAL_CONFIG.buttonPadding} !important;
                    font-size: ${FINAL_CONFIG.fontSize} !important;
                    margin: ${FINAL_CONFIG.buttonMargin} !important;
                    border-radius: 6px !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    box-sizing: border-box !important;
                }
                
                .navbar .d-flex.ms-auto .dropdown-toggle {
                    width: auto !important;
                    max-width: ${FINAL_CONFIG.userDropdownMaxWidth} !important;
                    height: ${FINAL_CONFIG.buttonHeight} !important;
                    padding: 3px 8px !important;
                    font-size: 0.75rem !important;
                    margin: ${FINAL_CONFIG.buttonMargin} !important;
                    border-radius: 6px !important;
                }
                
                .navbar .d-flex.ms-auto .dropdown {
                    margin: ${FINAL_CONFIG.buttonMargin} !important;
                }
                
                /* Remove Bootstrap spacing classes that might interfere */
                .navbar .d-flex.ms-auto .me-1,
                .navbar .d-flex.ms-auto .me-2,
                .navbar .d-flex.ms-auto .me-3,
                .navbar .d-flex.ms-auto .ms-1,
                .navbar .d-flex.ms-auto .ms-2,
                .navbar .d-flex.ms-auto .ms-3 {
                    margin: ${FINAL_CONFIG.buttonMargin} !important;
                }
            }
        `;
        
        document.head.appendChild(style);
        console.log('✅ Final CSS injected');
    }

    function initFinalSpacing() {
        console.log('🚀 Initializing Final Navbar Spacing...');
        
        // Apply immediately
        applyFinalSpacing();
        
        // Apply on window resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(applyFinalSpacing, 150);
        });
        
        // Apply on orientation change
        window.addEventListener('orientationchange', function() {
            setTimeout(applyFinalSpacing, 300);
        });
        
        // Monitor for DOM changes
        const observer = new MutationObserver(function(mutations) {
            let shouldReapply = false;
            mutations.forEach(function(mutation) {
                if (mutation.target.closest && mutation.target.closest('.navbar')) {
                    shouldReapply = true;
                }
            });
            
            if (shouldReapply) {
                setTimeout(applyFinalSpacing, 100);
            }
        });
        
        // Start observing navbar changes
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            observer.observe(navbar, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        }
        
        // Periodic reapplication (less frequent)
        setInterval(applyFinalSpacing, 3000);
        
        console.log('✅ Final Navbar Spacing initialized');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFinalSpacing);
    } else {
        initFinalSpacing();
    }

    // Also initialize after a short delay
    setTimeout(initFinalSpacing, 1000);

    // Expose function globally for manual triggering
    window.applyFinalSpacing = applyFinalSpacing;

})();