/**
 * Mobile Navbar Spacing - Absolute Fix Solution
 * Final solution using multiple approaches to guarantee override
 * 
 * Requirements: 3.1, 3.2, 3.3
 */

(function() {
    'use strict';

    console.log('🚀 Navbar Absolute Fix loading...');

    // Configuration
    const CONFIG = {
        buttonWidth: 32,
        buttonHeight: 32,
        buttonMargin: 0, // Set to 0 to rely on gap instead
        buttonPadding: 5,
        buttonFontSize: '0.85rem',
        buttonBorderRadius: 6,
        gap: 4,
        userDropdownMaxWidth: 75,
        mobileBreakpoint: 1400 // Set higher for testing purposes
    };

    /**
     * Force apply styles using multiple methods
     */
    function absoluteForceStyles(element, styles) {
        if (!element) {
            console.warn('Element not found for styling');
            return;
        }

        console.log('Applying styles to:', element, styles);

        // Method 1: Direct style property setting with !important
        Object.keys(styles).forEach(property => {
            const value = styles[property];
            try {
                element.style.setProperty(property, value, 'important');
            } catch (e) {
                console.warn('Failed to set property:', property, value, e);
            }
        });

        // Method 2: Inline style attribute manipulation
        let inlineStyles = element.getAttribute('style') || '';
        Object.keys(styles).forEach(property => {
            const value = styles[property];
            const cssProperty = property.replace(/([A-Z])/g, '-$1').toLowerCase();
            const styleRule = `${cssProperty}: ${value} !important;`;
            
            // Remove existing rule if present
            const regex = new RegExp(`${cssProperty}\\s*:[^;]*;?`, 'gi');
            inlineStyles = inlineStyles.replace(regex, '');
            
            // Add new rule
            inlineStyles += ` ${styleRule}`;
        });
        element.setAttribute('style', inlineStyles);

        // Method 3: CSS class injection
        const className = 'navbar-absolute-fix-' + Date.now();
        const cssRules = Object.keys(styles).map(property => {
            const cssProperty = property.replace(/([A-Z])/g, '-$1').toLowerCase();
            return `${cssProperty}: ${styles[property]} !important`;
        }).join('; ');

        // Inject CSS rule
        const style = document.createElement('style');
        style.textContent = `.${className} { ${cssRules} }`;
        document.head.appendChild(style);
        element.classList.add(className);
    }

    /**
     * Check if we're on mobile
     */
    function isMobile() {
        return window.innerWidth <= CONFIG.mobileBreakpoint;
    }

    /**
     * Fix navbar container
     */
    function fixNavbarContainer(container) {
        console.log('Fixing navbar container:', container);
        
        const styles = {
            'margin': '0px',
            'padding': '0px',
            'display': 'flex',
            'align-items': 'center',
            'justify-content': 'flex-end',
            'flex-wrap': 'nowrap',
            'gap': CONFIG.gap + 'px' // Force 4px gap
        };

        absoluteForceStyles(container, styles);
    }

    /**
     * Fix individual buttons
     */
    function fixButton(button, index) {
        console.log('Fixing button:', button, 'index:', index);
        
        const styles = {
            'width': CONFIG.buttonWidth + 'px',
            'height': CONFIG.buttonHeight + 'px',
            'min-width': CONFIG.buttonWidth + 'px',
            'min-height': CONFIG.buttonHeight + 'px',
            'max-width': CONFIG.buttonWidth + 'px',
            'max-height': CONFIG.buttonHeight + 'px',
            'margin': '0px', // Use gap instead of margin
            'padding': CONFIG.buttonPadding + 'px',
            'font-size': CONFIG.buttonFontSize,
            'border-radius': CONFIG.buttonBorderRadius + 'px',
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

        absoluteForceStyles(button, styles);
        
        // Extra aggressive fix for display property
        setTimeout(() => {
            button.style.setProperty('display', 'inline-flex', 'important');
            button.setAttribute('style', button.getAttribute('style') + '; display: inline-flex !important;');
        }, 10);
    }

    /**
     * Fix dropdown toggle
     */
    function fixDropdownToggle(dropdown, index) {
        console.log('Fixing dropdown toggle:', dropdown, 'index:', index);
        
        const styles = {
            'max-width': CONFIG.userDropdownMaxWidth + 'px',
            'height': CONFIG.buttonHeight + 'px',
            'min-height': CONFIG.buttonHeight + 'px',
            'margin': '0px', // Use gap instead of margin
            'padding': '3px 8px',
            'font-size': CONFIG.buttonFontSize,
            'border-radius': CONFIG.buttonBorderRadius + 'px',
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

        absoluteForceStyles(dropdown, styles);
        
        // Extra aggressive fix for display property
        setTimeout(() => {
            dropdown.style.setProperty('display', 'inline-flex', 'important');
            dropdown.setAttribute('style', dropdown.getAttribute('style') + '; display: inline-flex !important;');
        }, 10);
    }

    /**
     * Main fix function
     */
    function applyAbsoluteFix() {
        console.log('🔧 Applying absolute navbar fix...');
        console.log('Window width:', window.innerWidth, 'Mobile:', isMobile());

        if (!isMobile()) {
            console.log('Not mobile, skipping fix');
            return;
        }

        // Find all navbar containers
        const selectors = [
            '.navbar .d-flex.ms-auto',
            'nav .d-flex.ms-auto',
            '.navbar .ms-auto',
            '.d-flex.ms-auto'
        ];

        let containersFound = 0;
        selectors.forEach(selector => {
            const containers = document.querySelectorAll(selector);
            console.log('Found', containers.length, 'containers with selector:', selector);
            
            containers.forEach(container => {
                containersFound++;
                console.log('Processing container:', container);
                
                // Fix container
                fixNavbarContainer(container);
                
                // Get all buttons and dropdowns
                const allElements = Array.from(container.children);
                console.log('Container children:', allElements);
                
                let elementIndex = 0;
                allElements.forEach(element => {
                    // Check if it's a button or contains buttons
                    const buttons = element.tagName === 'BUTTON' ? [element] : element.querySelectorAll('button, .btn');
                    
                    buttons.forEach(button => {
                        if (button.classList.contains('dropdown-toggle')) {
                            fixDropdownToggle(button, elementIndex);
                        } else {
                            fixButton(button, elementIndex);
                        }
                        elementIndex++;
                    });
                });
            });
        });

        console.log('Total containers processed:', containersFound);
        
        if (containersFound === 0) {
            console.warn('No navbar containers found! Available elements:');
            console.log('All .navbar elements:', document.querySelectorAll('.navbar'));
            console.log('All .d-flex elements:', document.querySelectorAll('.d-flex'));
            console.log('All .ms-auto elements:', document.querySelectorAll('.ms-auto'));
        }
    }

    /**
     * Initialize with multiple triggers
     */
    function init() {
        console.log('🚀 Initializing navbar absolute fix...');
        
        // Apply immediately
        applyAbsoluteFix();
        
        // Apply after a short delay
        setTimeout(applyAbsoluteFix, 100);
        setTimeout(applyAbsoluteFix, 500);
        setTimeout(applyAbsoluteFix, 1000);
        
        // Apply on window resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(applyAbsoluteFix, 100);
        });
        
        // Apply on DOM changes
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                let shouldReapply = false;
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        shouldReapply = true;
                    }
                });
                
                if (shouldReapply) {
                    setTimeout(applyAbsoluteFix, 50);
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
        
        // Start continuous display monitoring
        setTimeout(startDisplayMonitor, 1000);
    }

    /**
     * Continuous display property monitor
     */
    function startDisplayMonitor() {
        const buttons = document.querySelectorAll('.navbar .d-flex.ms-auto button:not(.dropdown-toggle)');
        
        // Check and fix display property every 100ms
        setInterval(() => {
            buttons.forEach(button => {
                const computedStyle = window.getComputedStyle(button);
                if (computedStyle.display !== 'inline-flex') {
                    button.style.setProperty('display', 'inline-flex', 'important');
                    button.style.display = 'inline-flex';
                    
                    // Force reflow
                    button.offsetHeight;
                }
            });
        }, 100);
    }

    // Global functions for debugging
    window.forceNavbarAbsoluteFix = function() {
        console.log('🔧 Manual trigger of absolute navbar fix');
        applyAbsoluteFix();
    };

    window.debugNavbarElements = function() {
        console.log('🔍 Debugging navbar elements...');
        console.log('Window width:', window.innerWidth);
        console.log('Is mobile:', isMobile());
        console.log('.navbar elements:', document.querySelectorAll('.navbar'));
        console.log('.d-flex.ms-auto elements:', document.querySelectorAll('.d-flex.ms-auto'));
        console.log('All buttons:', document.querySelectorAll('button'));
        console.log('All .btn elements:', document.querySelectorAll('.btn'));
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Also initialize on window load
    window.addEventListener('load', init);

    console.log('📱 Navbar Absolute Fix loaded. Use window.forceNavbarAbsoluteFix() to manually trigger.');

})();