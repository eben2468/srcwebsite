/**
 * Page-Specific Nuclear Layout Fixes JavaScript
 * This script provides targeted fixes for specific pages that are not
 * responding to the general nuclear layout override system.
 * 
 * It detects the current page and applies specific fixes accordingly.
 */

(function() {
    'use strict';

    // Page detection
    function getCurrentPage() {
        const path = window.location.pathname;
        const filename = path.split('/').pop();
        return filename.toLowerCase();
    }

    // Get current padding based on viewport
    function getCurrentPadding() {
        const width = window.innerWidth;
        if (width <= 320) return '12px';
        if (width <= 374) return '12px';
        if (width <= 767) return '16px';
        if (width <= 991) return '20px';
        if (width <= 1199) return '24px';
        return '24px';
    }

    // Apply page-specific fixes
    function applyPageSpecificFixes() {
        // Only apply on screens under 1200px
        if (window.innerWidth >= 1200) return;

        const currentPage = getCurrentPage();
        const currentPadding = getCurrentPadding();
        
        console.log(`🎯 PAGE-SPECIFIC FIXES: Applying fixes for ${currentPage} with ${currentPadding} padding`);

        // Profile page fixes
        if (currentPage === 'profile.php') {
            applyProfilePageFixes(currentPadding);
        }

        // Settings page fixes
        if (currentPage === 'settings.php') {
            applySettingsPageFixes(currentPadding);
        }

        // Event detail page fixes
        if (currentPage === 'event-detail.php') {
            applyEventDetailPageFixes(currentPadding);
        }

        // Apply common fixes for all problematic pages
        applyCommonFixes(currentPadding);
    }

    // Profile page specific fixes
    function applyProfilePageFixes(padding) {
        console.log('🔧 Applying profile page fixes');

        // Fix container-fluid px-4
        const containers = document.querySelectorAll('.container-fluid.px-4');
        containers.forEach(container => {
            container.style.setProperty('padding-left', padding, 'important');
            container.style.setProperty('padding-right', padding, 'important');
            container.style.setProperty('width', '100%', 'important');
            container.style.setProperty('max-width', '100%', 'important');
            container.style.setProperty('margin-left', '0', 'important');
            container.style.setProperty('margin-right', '0', 'important');
            container.setAttribute('data-page-specific-fix', 'profile-container');
        });

        // Fix profile header
        const profileHeader = document.querySelector('.profile-header');
        if (profileHeader) {
            profileHeader.style.setProperty('margin-left', `calc(-1 * ${padding})`, 'important');
            profileHeader.style.setProperty('margin-right', `calc(-1 * ${padding})`, 'important');
            profileHeader.style.setProperty('padding-left', padding, 'important');
            profileHeader.style.setProperty('padding-right', padding, 'important');
            profileHeader.style.setProperty('width', `calc(100% + 2 * ${padding})`, 'important');
            profileHeader.setAttribute('data-page-specific-fix', 'profile-header');
        }
    }

    // Settings page specific fixes
    function applySettingsPageFixes(padding) {
        console.log('🔧 Applying settings page fixes');

        // Fix settings header
        const settingsHeader = document.querySelector('.settings-header');
        if (settingsHeader) {
            settingsHeader.style.setProperty('margin-left', `calc(-1 * ${padding})`, 'important');
            settingsHeader.style.setProperty('margin-right', `calc(-1 * ${padding})`, 'important');
            settingsHeader.style.setProperty('padding-left', padding, 'important');
            settingsHeader.style.setProperty('padding-right', padding, 'important');
            settingsHeader.style.setProperty('width', `calc(100% + 2 * ${padding})`, 'important');
            settingsHeader.setAttribute('data-page-specific-fix', 'settings-header');
        }

        // Fix content card
        const contentCard = document.querySelector('.content-card');
        if (contentCard) {
            contentCard.style.setProperty('margin-left', '0', 'important');
            contentCard.style.setProperty('margin-right', '0', 'important');
            contentCard.style.setProperty('width', '100%', 'important');
            contentCard.style.setProperty('max-width', '100%', 'important');
            contentCard.setAttribute('data-page-specific-fix', 'settings-content');
        }

        // Fix tab content
        const tabContent = document.querySelector('.tab-content');
        if (tabContent) {
            tabContent.style.setProperty('width', '100%', 'important');
            tabContent.style.setProperty('max-width', '100%', 'important');
            tabContent.style.setProperty('margin-left', '0', 'important');
            tabContent.style.setProperty('margin-right', '0', 'important');
            tabContent.setAttribute('data-page-specific-fix', 'settings-tabs');
        }
    }

    // Event detail page specific fixes
    function applyEventDetailPageFixes(padding) {
        console.log('🔧 Applying event detail page fixes');

        // Fix event detail header
        const eventHeader = document.querySelector('.event-detail-header');
        if (eventHeader) {
            eventHeader.style.setProperty('margin-left', `calc(-1 * ${padding})`, 'important');
            eventHeader.style.setProperty('margin-right', `calc(-1 * ${padding})`, 'important');
            eventHeader.style.setProperty('padding-left', padding, 'important');
            eventHeader.style.setProperty('padding-right', padding, 'important');
            eventHeader.style.setProperty('width', `calc(100% + 2 * ${padding})`, 'important');
            eventHeader.setAttribute('data-page-specific-fix', 'event-header');
        }

        // Fix container-fluid
        const containers = document.querySelectorAll('.container-fluid');
        containers.forEach(container => {
            if (!container.classList.contains('px-4')) {
                container.style.setProperty('padding-left', padding, 'important');
                container.style.setProperty('padding-right', padding, 'important');
                container.style.setProperty('width', '100%', 'important');
                container.style.setProperty('max-width', '100%', 'important');
                container.style.setProperty('margin-left', '0', 'important');
                container.style.setProperty('margin-right', '0', 'important');
                container.setAttribute('data-page-specific-fix', 'event-container');
            }
        });
    }

    // Common fixes for all problematic pages
    function applyCommonFixes(padding) {
        // Fix all cards
        const cards = document.querySelectorAll('.card, .content-card');
        cards.forEach(card => {
            card.style.setProperty('margin-left', '0', 'important');
            card.style.setProperty('margin-right', '0', 'important');
            card.style.setProperty('width', '100%', 'important');
            card.style.setProperty('max-width', '100%', 'important');
            card.style.setProperty('box-sizing', 'border-box', 'important');
        });

        // Fix all rows
        const rows = document.querySelectorAll('.row');
        rows.forEach(row => {
            row.style.setProperty('margin-left', '0', 'important');
            row.style.setProperty('margin-right', '0', 'important');
            row.style.setProperty('width', '100%', 'important');
            row.style.setProperty('max-width', '100%', 'important');
        });

        // Fix all columns
        const cols = document.querySelectorAll('.col, [class*="col-"]');
        cols.forEach(col => {
            const halfPadding = parseFloat(padding) / 2 + 'px';
            col.style.setProperty('padding-left', halfPadding, 'important');
            col.style.setProperty('padding-right', halfPadding, 'important');
        });

        // Fix all alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.setProperty('margin-left', '0', 'important');
            alert.style.setProperty('margin-right', '0', 'important');
            alert.style.setProperty('width', '100%', 'important');
            alert.style.setProperty('max-width', '100%', 'important');
        });

        // Prevent horizontal overflow
        document.documentElement.style.setProperty('overflow-x', 'hidden', 'important');
        document.body.style.setProperty('overflow-x', 'hidden', 'important');

        // Mark body as having page-specific fixes applied
        document.body.setAttribute('data-page-specific-fixes', 'applied');
        document.body.setAttribute('data-page-specific-padding', padding);
    }

    // Debounce function
    function debounce(func, wait) {
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

    // Initialize page-specific fixes
    function initializePageSpecificFixes() {
        const currentPage = getCurrentPage();
        console.log(`🎯 PAGE-SPECIFIC FIXES: Initializing for ${currentPage}`);
        
        // Apply fixes immediately
        applyPageSpecificFixes();
        
        // Setup resize handler
        const debouncedApply = debounce(applyPageSpecificFixes, 100);
        window.addEventListener('resize', debouncedApply);
        
        // Setup mutation observer for dynamic content
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                let shouldReapply = false;
                
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // Element node
                                const hasProblematicElements = node.querySelector && (
                                    node.querySelector('.container-fluid') ||
                                    node.querySelector('.card') ||
                                    node.querySelector('.alert') ||
                                    node.classList.contains('container-fluid') ||
                                    node.classList.contains('card') ||
                                    node.classList.contains('alert')
                                );
                                if (hasProblematicElements) {
                                    shouldReapply = true;
                                }
                            }
                        });
                    }
                });
                
                if (shouldReapply) {
                    console.log('🔄 PAGE-SPECIFIC FIXES: Reapplying due to DOM changes');
                    setTimeout(applyPageSpecificFixes, 50);
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        // Periodic check to ensure fixes are maintained
        setInterval(function() {
            if (window.innerWidth < 1200) {
                const hasPageFixes = document.body.getAttribute('data-page-specific-fixes') === 'applied';
                if (!hasPageFixes) {
                    console.log('🔧 PAGE-SPECIFIC FIXES: Reapplying lost fixes');
                    applyPageSpecificFixes();
                }
            }
        }, 3000);

        console.log('✅ PAGE-SPECIFIC FIXES: Initialization complete');
    }

    // Diagnostic function
    function runPageSpecificDiagnostic() {
        const currentPage = getCurrentPage();
        const currentPadding = getCurrentPadding();
        
        console.log('🔬 PAGE-SPECIFIC DIAGNOSTIC');
        console.log(`Page: ${currentPage}`);
        console.log(`Viewport: ${window.innerWidth}px`);
        console.log(`Expected padding: ${currentPadding}`);
        
        const hasPageFixes = document.body.getAttribute('data-page-specific-fixes') === 'applied';
        console.log(`Page fixes applied: ${hasPageFixes ? '✅' : '❌'}`);
        
        // Check specific elements based on page
        if (currentPage === 'profile.php') {
            const profileHeader = document.querySelector('.profile-header');
            const containerPx4 = document.querySelector('.container-fluid.px-4');
            
            console.log('Profile page elements:');
            console.log(`  Profile header: ${profileHeader ? '✅' : '❌'}`);
            console.log(`  Container px-4: ${containerPx4 ? '✅' : '❌'}`);
            
            if (profileHeader) {
                const computedStyle = window.getComputedStyle(profileHeader);
                console.log(`  Header padding: ${computedStyle.paddingLeft} / ${computedStyle.paddingRight}`);
            }
            
            if (containerPx4) {
                const computedStyle = window.getComputedStyle(containerPx4);
                console.log(`  Container padding: ${computedStyle.paddingLeft} / ${computedStyle.paddingRight}`);
            }
        }
        
        // Similar checks for other pages...
        
        return {
            page: currentPage,
            viewport: window.innerWidth,
            expectedPadding: currentPadding,
            hasPageFixes,
            timestamp: new Date().toISOString()
        };
    }

    // Initialize based on document state
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePageSpecificFixes);
    } else {
        initializePageSpecificFixes();
    }

    // Expose functions globally
    window.applyPageSpecificFixes = applyPageSpecificFixes;
    window.runPageSpecificDiagnostic = runPageSpecificDiagnostic;

    // Debug controls removed for production

})();