/**
 * Mobile Navbar Spacing - JavaScript Fallback Force Fix
 * Direct DOM manipulation to override Bootstrap styles completely
 * 
 * Requirements: 3.1, 3.2, 3.3
 */

(function() {
    'use strict';

    // Configuration object with fallback values
    const NAVBAR_CONFIG = {
        buttonWidth: '32px',
        buttonHeight: '32px',
        buttonMargin: '3px',
        buttonPadding: '5px',
        buttonFontSize: '0.85rem',
        buttonBorderRadius: '6px',
        gap: '4px',
        userDropdownMaxWidth: '75px'
    };

    /**
     * Apply styles directly to element with maximum priority
     */
    function forceApplyStyles(element, styles) {
        if (!element) return;
        
        Object.keys(styles).forEach(property => {
            const value = styles[property];
            // Set style with !important
            element.style.setProperty(property, value, 'important');
        });
    }

    /**
     * Fix navbar container styles
     */
    function fixNavbarContainer(container) {
        const containerStyles = {
            'margin': '0px',
            'padding': '0px',
            'display': 'flex',
            'align-items': 'center',
            'justify-content': 'flex-end',
            'flex-wrap': 'nowrap'
        };

        // Check for flexbox gap support
        if (CSS.supports('gap', '4px')) {
            containerStyles['gap'] = NAVBAR_CONFIG.gap;
        }

        forceApplyStyles(container, containerStyles);
    }

    /**
     * Fix individual button styles
     */
    function fixButton(button) {
        const buttonStyles = {
            'width': NAVBAR_CONFIG.buttonWidth,
            'height': NAVBAR_CONFIG.buttonHeight,
            'min-width': NAVBAR_CONFIG.buttonWidth,
            'min-height': NAVBAR_CONFIG.buttonHeight,
            'max-width': NAVBAR_CONFIG.buttonWidth,
            'max-height': NAVBAR_CONFIG.buttonHeight,
            'margin': NAVBAR_CONFIG.buttonMargin,
            'padding': NAVBAR_CONFIG.buttonPadding,
            'font-size': NAVBAR_CONFIG.buttonFontSize,
            'border-radius': NAVBAR_CONFIG.buttonBorderRadius,
            'box-sizing': 'border-box',
            'white-space': 'nowrap',
            'display': 'inline-flex',
            'align-items': 'center',
            'justify-content': 'center',
            'vertical-align': 'middle',
            'text-align': 'center',
            'flex-shrink': '0',
            'line-height': '1',
            'text-decoration': 'none',
            'cursor': 'pointer'
        };

        forceApplyStyles(button, buttonStyles);
    }

    /**
     * Fix dropdown toggle styles
     */
    function fixDropdownToggle(dropdown) {
        const dropdownStyles = {
            'max-width': NAVBAR_CONFIG.userDropdownMaxWidth,
            'height': NAVBAR_CONFIG.buttonHeight,
            'min-height': NAVBAR_CONFIG.buttonHeight,
            'margin': NAVBAR_CONFIG.buttonMargin,
            'padding': '3px 8px',
            'font-size': NAVBAR_CONFIG.buttonFontSize,
            'border-radius': NAVBAR_CONFIG.buttonBorderRadius,
            'box-sizing': 'border-box',
            'overflow': 'hidden',
            'text-overflow': 'ellipsis',
            'white-space': 'nowrap',
            'display': 'inline-flex',
            'align-items': 'center',
            'justify-content': 'center',
            'vertical-align': 'middle',
            'text-align': 'center',
            'flex-shrink': '0',
            'line-height': '1',
            'text-decoration': 'none',
            'cursor': 'pointer'
        };

        forceApplyStyles(dropdown, dropdownStyles);
    }

    /**
     * Apply margin-based spacing for browsers without gap support
     */
    function applyMarginSpacing(container) {
        if (!CSS.supports('gap', '4px')) {
            const buttons = container.querySelectorAll('button, .btn');
            buttons.forEach((button, index) => {
                if (index > 0) {
                    forceApplyStyles(button, {
                        'margin-left': '4px'
                    });
                }
            });
        }
    }

    /**
     * Main function to fix navbar spacing
     */
    function fixNavbarSpacing() {
        // Only apply on mobile screens
        if (window.innerWidth > 991) {
            return;
        }

        // Find all navbar containers
        const navbarContainers = document.querySelectorAll('.navbar .d-flex.ms-auto');
        
        navbarContainers.forEach(container => {
            // Fix container
            fixNavbarContainer(container);
            
            // Fix regular buttons (not dropdown toggles)
            const regularButtons = container.querySelectorAll('button:not(.dropdown-toggle), .btn:not(.dropdown-toggle)');
            regularButtons.forEach(fixButton);
            
            // Fix dropdown toggles
            const dropdownToggles = container.querySelectorAll('.dropdown-toggle');
            dropdownToggles.forEach(fixDropdownToggle);
            
            // Apply margin-based spacing if needed
            applyMarginSpacing(container);
        });
    }

    /**
     * Internet Explorer 11 specific fixes
     */
    function applyIE11Fixes() {
        const isIE11 = navigator.userAgent.indexOf('Trident') > -1;
        if (!isIE11) return;

        const navbarContainers = document.querySelectorAll('.navbar .d-flex.ms-auto');
        
        navbarContainers.forEach(container => {
            // IE11 flexbox fixes
            forceApplyStyles(container, {
                'display': '-ms-flexbox',
                '-ms-flex-align': 'center',
                '-ms-flex-pack': 'end',
                '-ms-flex-wrap': 'nowrap'
            });

            const buttons = container.querySelectorAll('button, .btn');
            buttons.forEach(button => {
                forceApplyStyles(button, {
                    'display': '-ms-inline-flexbox',
                    '-ms-flex-align': 'center',
                    '-ms-flex-pack': 'center'
                });
            });
        });
    }

    /**
     * Feature detection and progressive enhancement
     */
    function applyProgressiveEnhancement() {
        // Layer 1: Basic support (all browsers)
        fixNavbarSpacing();

        // Layer 2: IE11 specific fixes
        applyIE11Fixes();

        // Layer 3: CSS Custom Properties support
        if (CSS.supports('color', 'var(--fake-var)')) {
            // Use CSS variables if supported
            document.documentElement.style.setProperty('--navbar-button-width', NAVBAR_CONFIG.buttonWidth);
            document.documentElement.style.setProperty('--navbar-button-height', NAVBAR_CONFIG.buttonHeight);
            document.documentElement.style.setProperty('--navbar-button-gap', NAVBAR_CONFIG.gap);
        }
    }

    /**
     * Initialize the navbar fix
     */
    function init() {
        // Apply fixes immediately
        applyProgressiveEnhancement();

        // Reapply on window resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(applyProgressiveEnhancement, 100);
        });

        // Reapply when DOM changes (for dynamic content)
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                let shouldReapply = false;
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        const addedNodes = Array.from(mutation.addedNodes);
                        if (addedNodes.some(node => 
                            node.nodeType === 1 && 
                            (node.classList.contains('navbar') || node.querySelector('.navbar'))
                        )) {
                            shouldReapply = true;
                        }
                    }
                });
                
                if (shouldReapply) {
                    setTimeout(applyProgressiveEnhancement, 50);
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    /**
     * Manual trigger function for debugging
     */
    window.forceNavbarFix = function() {
        console.log('🔧 Manually triggering navbar fix...');
        applyProgressiveEnhancement();
        console.log('✅ Navbar fix applied');
    };

    /**
     * Validation function for testing
     */
    window.validateNavbarFix = function() {
        const results = [];
        const containers = document.querySelectorAll('.navbar .d-flex.ms-auto');
        
        containers.forEach((container, index) => {
            const containerStyle = window.getComputedStyle(container);
            results.push({
                container: index + 1,
                display: containerStyle.display,
                justifyContent: containerStyle.justifyContent,
                alignItems: containerStyle.alignItems,
                gap: containerStyle.gap
            });

            const buttons = container.querySelectorAll('button:not(.dropdown-toggle), .btn:not(.dropdown-toggle)');
            buttons.forEach((button, btnIndex) => {
                const buttonStyle = window.getComputedStyle(button);
                const rect = button.getBoundingClientRect();
                results.push({
                    button: `${index + 1}.${btnIndex + 1}`,
                    width: rect.width,
                    height: rect.height,
                    display: buttonStyle.display,
                    whiteSpace: buttonStyle.whiteSpace
                });
            });
        });

        console.table(results);
        return results;
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Also initialize on window load as a fallback
    window.addEventListener('load', init);

    console.log('📱 Navbar Fallback Force Fix loaded. Use window.forceNavbarFix() to manually trigger.');

})();