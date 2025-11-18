/**
 * ULTRA-AGGRESSIVE Force Navbar Spacing - Nuclear Option
 * This script uses the most forceful techniques possible to compress navbar spacing
 * Uses inline styles, continuous monitoring, and DOM manipulation
 */

(function() {
    'use strict';

    // Optimized configuration - compact but usable
    const SPACING_CONFIG = {
        gap: '2px',
        buttonMargin: '1px',
        buttonPadding: '3px',
        buttonWidth: '28px',
        buttonHeight: '28px',
        userDropdownMaxWidth: '65px',
        fontSize: '0.75rem',
        containerPadding: '0px'
    };

    // Ultra-aggressive navbar spacing function
    function forceNavbarSpacing() {
        console.log('🚀 ULTRA-AGGRESSIVE navbar spacing starting...');
        
        // Find the navbar right section
        const rightSection = document.querySelector('.navbar .d-flex.ms-auto');
        if (!rightSection) {
            console.log('❌ No navbar right section found');
            return;
        }

        // NUCLEAR OPTION: Force container properties with maximum specificity
        const containerStyles = {
            'gap': SPACING_CONFIG.gap,
            'margin': '0px',
            'margin-left': '0px',
            'margin-right': '0px',
            'margin-top': '0px',
            'margin-bottom': '0px',
            'padding': SPACING_CONFIG.containerPadding,
            'padding-left': SPACING_CONFIG.containerPadding,
            'padding-right': SPACING_CONFIG.containerPadding,
            'padding-top': SPACING_CONFIG.containerPadding,
            'padding-bottom': SPACING_CONFIG.containerPadding,
            'display': 'flex',
            'flex-direction': 'row',
            'flex-wrap': 'nowrap',
            'align-items': 'center',
            'justify-content': 'flex-end',
            'width': 'auto',
            'min-width': 'auto',
            'max-width': 'none'
        };

        // Apply all container styles with maximum force
        Object.entries(containerStyles).forEach(([property, value]) => {
            rightSection.style.setProperty(property, value, 'important');
        });

        // Also set via direct style attribute as backup
        rightSection.setAttribute('style', 
            rightSection.getAttribute('style') + 
            '; gap: ' + SPACING_CONFIG.gap + ' !important; margin: 0px !important; padding: 0px !important;'
        );

        // Find ALL possible elements that could cause spacing
        const allElements = rightSection.querySelectorAll('*');
        const directChildren = Array.from(rightSection.children);
        
        console.log(`Found ${directChildren.length} direct children, ${allElements.length} total elements`);
        
        // ULTRA-AGGRESSIVE: Force every single element to have minimal spacing
        allElements.forEach(element => {
            const spacingStyles = {
                'margin': '0px',
                'margin-left': '0px',
                'margin-right': '0px',
                'margin-top': '0px',
                'margin-bottom': '0px',
                'padding-left': SPACING_CONFIG.buttonPadding,
                'padding-right': SPACING_CONFIG.buttonPadding,
                'padding-top': SPACING_CONFIG.buttonPadding,
                'padding-bottom': SPACING_CONFIG.buttonPadding
            };
            
            Object.entries(spacingStyles).forEach(([property, value]) => {
                element.style.setProperty(property, value, 'important');
            });
        });
        
        // NUCLEAR OPTION: Process direct children with extreme force
        directChildren.forEach((element, index) => {
            console.log(`Processing element ${index + 1}:`, element.tagName, element.className);
            
            // Force minimal margins with every possible method
            const marginProperties = ['margin', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom'];
            marginProperties.forEach(prop => {
                element.style.setProperty(prop, SPACING_CONFIG.buttonMargin, 'important');
                element.style[prop] = SPACING_CONFIG.buttonMargin;
            });
            
            // Force minimal padding
            const paddingProperties = ['padding-left', 'padding-right', 'padding-top', 'padding-bottom'];
            paddingProperties.forEach(prop => {
                element.style.setProperty(prop, SPACING_CONFIG.buttonPadding, 'important');
            });
            
            // BUTTON-SPECIFIC ULTRA-AGGRESSIVE SIZING
            if (element.tagName === 'BUTTON' || element.querySelector('button')) {
                const button = element.tagName === 'BUTTON' ? element : element.querySelector('button');
                
                if (button) {
                    // Force ultra-small dimensions
                    const buttonStyles = {
                        'width': SPACING_CONFIG.buttonWidth,
                        'min-width': SPACING_CONFIG.buttonWidth,
                        'max-width': button.classList.contains('dropdown-toggle') ? SPACING_CONFIG.userDropdownMaxWidth : SPACING_CONFIG.buttonWidth,
                        'height': SPACING_CONFIG.buttonHeight,
                        'min-height': SPACING_CONFIG.buttonHeight,
                        'max-height': SPACING_CONFIG.buttonHeight,
                        'font-size': SPACING_CONFIG.fontSize,
                        'line-height': '1',
                        'padding': SPACING_CONFIG.buttonPadding,
                        'border-width': '1px',
                        'flex-shrink': '0',
                        'flex-grow': '0',
                        'flex-basis': 'auto',
                        'box-sizing': 'border-box'
                    };
                    
                    // Apply with maximum force
                    Object.entries(buttonStyles).forEach(([property, value]) => {
                        button.style.setProperty(property, value, 'important');
                        button.style[property] = value; // Backup method
                    });
                    
                    // Special handling for user dropdown
                    if (button.classList.contains('dropdown-toggle')) {
                        button.style.setProperty('width', 'auto', 'important');
                        button.style.setProperty('padding', '1px 3px', 'important');
                        button.style.setProperty('font-size', '0.55rem', 'important');
                    }
                    
                    // Force via setAttribute as ultimate backup
                    const styleAttr = button.getAttribute('style') || '';
                    button.setAttribute('style', styleAttr + 
                        `; margin: ${SPACING_CONFIG.buttonMargin} !important; width: ${SPACING_CONFIG.buttonWidth} !important; height: ${SPACING_CONFIG.buttonHeight} !important;`
                    );
                }
            }
            
            // DROPDOWN-SPECIFIC HANDLING
            if (element.classList.contains('dropdown')) {
                element.style.setProperty('margin', '0px', 'important');
                element.style.setProperty('padding', '0px', 'important');
                element.style.setProperty('display', 'inline-block', 'important');
                element.style.setProperty('vertical-align', 'middle', 'important');
            }
        });

        // NUCLEAR OPTION: Remove ALL possible Bootstrap spacing classes
        const allSpacingClasses = [
            'me-1', 'me-2', 'me-3', 'me-4', 'me-5', 'me-auto',
            'ms-1', 'ms-2', 'ms-3', 'ms-4', 'ms-5', 'ms-auto',
            'mx-1', 'mx-2', 'mx-3', 'mx-4', 'mx-5', 'mx-auto',
            'my-1', 'my-2', 'my-3', 'my-4', 'my-5', 'my-auto',
            'pe-1', 'pe-2', 'pe-3', 'pe-4', 'pe-5',
            'ps-1', 'ps-2', 'ps-3', 'ps-4', 'ps-5',
            'px-1', 'px-2', 'px-3', 'px-4', 'px-5',
            'py-1', 'py-2', 'py-3', 'py-4', 'py-5',
            'p-1', 'p-2', 'p-3', 'p-4', 'p-5',
            'm-1', 'm-2', 'm-3', 'm-4', 'm-5',
            'gap-1', 'gap-2', 'gap-3', 'gap-4', 'gap-5'
        ];
        
        allElements.forEach(element => {
            allSpacingClasses.forEach(className => {
                element.classList.remove(className);
            });
        });

        // FINAL NUCLEAR OPTION: Inject CSS directly into the page
        injectUltraAggressiveCSS();

        console.log('✅ ULTRA-AGGRESSIVE navbar spacing applied:', directChildren.length, 'direct children processed');
    }

    // Inject CSS that cannot be overridden
    function injectUltraAggressiveCSS() {
        const existingStyle = document.getElementById('ultra-aggressive-navbar-css');
        if (existingStyle) {
            existingStyle.remove();
        }

        const style = document.createElement('style');
        style.id = 'ultra-aggressive-navbar-css';
        style.innerHTML = `
            /* ULTRA-AGGRESSIVE NAVBAR SPACING - NUCLEAR OPTION */
            @media (max-width: 991px) {
                .navbar .d-flex.ms-auto {
                    gap: ${SPACING_CONFIG.gap} !important;
                    margin: 0px !important;
                    padding: 0px !important;
                }
                
                .navbar .d-flex.ms-auto > * {
                    margin: ${SPACING_CONFIG.buttonMargin} !important;
                    margin-left: ${SPACING_CONFIG.buttonMargin} !important;
                    margin-right: ${SPACING_CONFIG.buttonMargin} !important;
                }
                
                .navbar .d-flex.ms-auto button,
                .navbar .d-flex.ms-auto .btn {
                    width: ${SPACING_CONFIG.buttonWidth} !important;
                    height: ${SPACING_CONFIG.buttonHeight} !important;
                    min-width: ${SPACING_CONFIG.buttonWidth} !important;
                    max-width: ${SPACING_CONFIG.buttonWidth} !important;
                    padding: ${SPACING_CONFIG.buttonPadding} !important;
                    margin: ${SPACING_CONFIG.buttonMargin} !important;
                    font-size: ${SPACING_CONFIG.fontSize} !important;
                    flex-shrink: 0 !important;
                }
                
                .navbar .d-flex.ms-auto .dropdown-toggle {
                    width: auto !important;
                    max-width: ${SPACING_CONFIG.userDropdownMaxWidth} !important;
                    padding: 1px 3px !important;
                    font-size: 0.55rem !important;
                }
                
                .navbar .d-flex.ms-auto .dropdown {
                    margin: ${SPACING_CONFIG.buttonMargin} !important;
                    padding: 0px !important;
                }
            }
        `;
        
        document.head.appendChild(style);
        console.log('🚀 Ultra-aggressive CSS injected');
    }

    // ULTRA-AGGRESSIVE initialization with maximum monitoring
    function initForceSpacing() {
        console.log('🚀 Initializing ULTRA-AGGRESSIVE navbar spacing...');
        
        // Apply immediately with multiple attempts
        setTimeout(forceNavbarSpacing, 100);
        setTimeout(forceNavbarSpacing, 500);
        setTimeout(forceNavbarSpacing, 1000);
        
        // Apply on window resize with immediate response
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            forceNavbarSpacing(); // Immediate
            resizeTimeout = setTimeout(forceNavbarSpacing, 50); // And delayed
        });
        
        // Apply on orientation change
        window.addEventListener('orientationchange', function() {
            setTimeout(forceNavbarSpacing, 100);
            setTimeout(forceNavbarSpacing, 500);
        });
        
        // ULTRA-AGGRESSIVE DOM monitoring
        const observer = new MutationObserver(function(mutations) {
            let shouldReapply = false;
            mutations.forEach(function(mutation) {
                // Monitor any change in the navbar area
                if (mutation.target.closest && mutation.target.closest('.navbar')) {
                    shouldReapply = true;
                }
                if (mutation.type === 'childList' || mutation.type === 'attributes') {
                    shouldReapply = true;
                }
            });
            
            if (shouldReapply) {
                forceNavbarSpacing(); // Immediate
                setTimeout(forceNavbarSpacing, 25); // Quick follow-up
            }
        });
        
        // Monitor the entire document for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class', 'id']
        });
        
        // NUCLEAR OPTION: Force reapply every 500ms (very aggressive)
        setInterval(forceNavbarSpacing, 500);
        
        // Also monitor for focus/blur events that might change styles
        document.addEventListener('focusin', () => setTimeout(forceNavbarSpacing, 10));
        document.addEventListener('focusout', () => setTimeout(forceNavbarSpacing, 10));
        
        console.log('✅ ULTRA-AGGRESSIVE monitoring initialized');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initForceSpacing);
    } else {
        initForceSpacing();
    }

    // Also initialize after a short delay to catch any late-loading elements
    setTimeout(initForceSpacing, 1000);

    // Expose function globally for manual triggering
    window.forceNavbarSpacing = forceNavbarSpacing;

})();