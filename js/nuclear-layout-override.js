/**
 * NUCLEAR LAYOUT OVERRIDE JAVASCRIPT
 * This is the most aggressive JavaScript solution to force
 * layout standardization on ALL pages without exception.
 * 
 * This script will:
 * 1. Apply styles directly via JavaScript with maximum priority
 * 2. Override any conflicting CSS rules
 * 3. Monitor for changes and reapply as needed
 * 4. Work even if CSS files fail to load
 */

(function() {
    'use strict';

    // Nuclear padding values
    const NUCLEAR_PADDING = {
        320: '12px',
        375: '16px',
        768: '20px',
        991: '24px'
    };

    // Get current padding based on viewport width
    function getNuclearPadding() {
        const width = window.innerWidth;
        if (width <= 320) return NUCLEAR_PADDING[320];
        if (width <= 374) return NUCLEAR_PADDING[320];
        if (width <= 767) return NUCLEAR_PADDING[375];
        if (width <= 991) return NUCLEAR_PADDING[768];
        if (width <= 1199) return NUCLEAR_PADDING[991];
        return NUCLEAR_PADDING[991]; // Default for larger screens
    }

    // Nuclear layout application function
    function applyNuclearLayout() {
        // Only apply on screens under 1200px
        if (window.innerWidth >= 1200) return;

        const currentPadding = getNuclearPadding();
        
        console.log(`🚀 NUCLEAR LAYOUT OVERRIDE: Applying ${currentPadding} padding for ${window.innerWidth}px viewport`);

        // LEVEL 1: All container selectors
        const containerSelectors = [
            '.container',
            '.container-fluid',
            '.main-content',
            '.container-sm',
            '.container-md',
            '.container-lg',
            '.container-xl',
            '.container-xxl',
            '.standardized-container',
            'div.container',
            'div.container-fluid',
            'div.main-content',
            'section.container',
            'section.container-fluid',
            'main.container',
            'main.container-fluid',
            'article.container',
            'article.container-fluid'
        ];

        // LEVEL 2: All padding utility selectors
        const paddingSelectors = [
            '.px-0', '.px-1', '.px-2', '.px-3', '.px-4', '.px-5',
            '.ps-0', '.ps-1', '.ps-2', '.ps-3', '.ps-4', '.ps-5',
            '.pe-0', '.pe-1', '.pe-2', '.pe-3', '.pe-4', '.pe-5'
        ];

        // Apply nuclear override to all containers
        containerSelectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                // Force full width with maximum priority
                element.style.setProperty('width', '100%', 'important');
                element.style.setProperty('max-width', '100%', 'important');
                element.style.setProperty('margin-left', '0', 'important');
                element.style.setProperty('margin-right', '0', 'important');
                element.style.setProperty('box-sizing', 'border-box', 'important');
                
                // Apply nuclear padding
                element.style.setProperty('padding-left', currentPadding, 'important');
                element.style.setProperty('padding-right', currentPadding, 'important');
                
                // Mark as nuclear-standardized
                element.setAttribute('data-nuclear-standardized', 'true');
                element.setAttribute('data-nuclear-padding', currentPadding);
                
                // Add nuclear class for CSS targeting
                element.classList.add('nuclear-standardized');
            });
        });

        // Override all padding utilities
        paddingSelectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                element.style.setProperty('padding-left', currentPadding, 'important');
                element.style.setProperty('padding-right', currentPadding, 'important');
                element.setAttribute('data-nuclear-padding-override', 'true');
            });
        });

        // LEVEL 3: Fix content sections
        const contentSelectors = [
            '.content-section', '.page-content', '.dashboard-content',
            '.content-wrapper', '.page-wrapper', '.section-wrapper',
            '.dashboard-section', '.dashboard-stats'
        ];

        contentSelectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                element.style.setProperty('width', '100%', 'important');
                element.style.setProperty('max-width', '100%', 'important');
                element.style.setProperty('padding-left', '0', 'important');
                element.style.setProperty('padding-right', '0', 'important');
                element.style.setProperty('margin-left', '0', 'important');
                element.style.setProperty('margin-right', '0', 'important');
                element.style.setProperty('box-sizing', 'border-box', 'important');
            });
        });

        // LEVEL 4: Fix rows
        const rows = document.querySelectorAll('.row');
        rows.forEach(row => {
            row.style.setProperty('margin-left', '0', 'important');
            row.style.setProperty('margin-right', '0', 'important');
            row.style.setProperty('width', '100%', 'important');
            row.style.setProperty('max-width', '100%', 'important');
        });

        // LEVEL 5: Prevent horizontal overflow
        document.documentElement.style.setProperty('overflow-x', 'hidden', 'important');
        document.documentElement.style.setProperty('max-width', '100%', 'important');
        document.body.style.setProperty('overflow-x', 'hidden', 'important');
        document.body.style.setProperty('max-width', '100%', 'important');

        // LEVEL 6: Fix custom headers
        const headerSelectors = [
            '.profile-header', '.events-header', '.news-header', '.finance-header',
            '.support-header', '.chat-header', '.tickets-header', '.page-header',
            '.custom-header', '.section-header', '.users-header', '.documents-header',
            '.gallery-header', '.elections-header', '.minutes-header', '.reports-header',
            '.portfolio-header', '.departments-header', '.senate-header', '.committees-header',
            '.feedback-header', '.welfare-header', '.budget-header', '.settings-header'
        ];

        headerSelectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                element.style.setProperty('margin-left', `calc(-1 * ${currentPadding})`, 'important');
                element.style.setProperty('margin-right', `calc(-1 * ${currentPadding})`, 'important');
                element.style.setProperty('padding-left', currentPadding, 'important');
                element.style.setProperty('padding-right', currentPadding, 'important');
            });
        });

        // LEVEL 7: Add nuclear CSS class to body for additional targeting
        document.body.classList.add('nuclear-layout-applied');
        document.body.setAttribute('data-nuclear-viewport', window.innerWidth);
        document.body.setAttribute('data-nuclear-padding', currentPadding);

        console.log(`✅ NUCLEAR LAYOUT OVERRIDE: Applied to ${document.querySelectorAll('.nuclear-standardized').length} containers`);
    }

    // Enhanced debounce function
    function nuclearDebounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Nuclear mutation observer for dynamic content
    function setupNuclearObserver() {
        if (!window.MutationObserver) return;

        const observer = new MutationObserver(function(mutations) {
            let shouldReapply = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            const hasContainer = node.querySelector && (
                                node.querySelector('.container') ||
                                node.querySelector('.container-fluid') ||
                                node.querySelector('.main-content') ||
                                node.classList.contains('container') ||
                                node.classList.contains('container-fluid') ||
                                node.classList.contains('main-content')
                            );
                            if (hasContainer) {
                                shouldReapply = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldReapply) {
                console.log('🔄 NUCLEAR LAYOUT: Reapplying due to DOM changes');
                setTimeout(applyNuclearLayout, 50);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        console.log('🔍 NUCLEAR LAYOUT: Observer setup complete');
    }

    // Nuclear resize handler
    const nuclearResizeHandler = nuclearDebounce(function() {
        console.log('📐 NUCLEAR LAYOUT: Viewport changed, reapplying...');
        applyNuclearLayout();
    }, 100);

    // Nuclear initialization
    function initializeNuclearLayout() {
        console.log('🚀 NUCLEAR LAYOUT OVERRIDE: Initializing...');
        
        // Apply immediately
        applyNuclearLayout();
        
        // Setup resize handler
        window.addEventListener('resize', nuclearResizeHandler);
        
        // Setup mutation observer
        setupNuclearObserver();
        
        // Reapply periodically to catch any missed changes
        setInterval(function() {
            if (window.innerWidth < 1200) {
                const containers = document.querySelectorAll('.container, .container-fluid, .main-content');
                const nuclearContainers = document.querySelectorAll('[data-nuclear-standardized="true"]');
                
                if (containers.length > nuclearContainers.length) {
                    console.log('🔧 NUCLEAR LAYOUT: Found unstandardized containers, reapplying...');
                    applyNuclearLayout();
                }
            }
        }, 5000); // Check every 5 seconds
        
        console.log('✅ NUCLEAR LAYOUT OVERRIDE: Initialization complete');
    }

    // Nuclear diagnostic function
    function runNuclearDiagnostic() {
        console.log('🔬 NUCLEAR LAYOUT DIAGNOSTIC');
        console.log('Viewport:', window.innerWidth + 'x' + window.innerHeight);
        console.log('Expected padding:', getNuclearPadding());
        
        const containers = document.querySelectorAll('.container, .container-fluid, .main-content');
        const nuclearContainers = document.querySelectorAll('[data-nuclear-standardized="true"]');
        
        console.log(`Total containers: ${containers.length}`);
        console.log(`Nuclear standardized: ${nuclearContainers.length}`);
        
        containers.forEach((container, index) => {
            const computedStyle = window.getComputedStyle(container);
            const isNuclear = container.getAttribute('data-nuclear-standardized') === 'true';
            
            console.log(`Container ${index + 1}:`, {
                element: container,
                classes: container.className,
                paddingLeft: computedStyle.paddingLeft,
                paddingRight: computedStyle.paddingRight,
                width: computedStyle.width,
                nuclear: isNuclear
            });
        });
    }

    // Initialize based on document state
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeNuclearLayout);
    } else {
        // Document already loaded, initialize immediately
        initializeNuclearLayout();
    }

    // Expose functions globally
    window.applyNuclearLayout = applyNuclearLayout;
    window.runNuclearDiagnostic = runNuclearDiagnostic;

    // Debug control panel removed for production

})();