/**
 * ELECTIONS PAGE SPECIFIC FIX
 * This script specifically targets the elections page to ensure
 * it follows the full-width layout standardization.
 */

(function() {
    'use strict';

    // Check if this is the elections page
    function isElectionsPage() {
        const path = window.location.pathname;
        return path.includes('elections.php') || document.body.classList.contains('elections-page');
    }

    // Apply elections page specific fixes
    function applyElectionsPageFixes() {
        if (!isElectionsPage() || window.innerWidth >= 1200) return;

        const currentPadding = window.innerWidth <= 320 ? '12px' :
                              window.innerWidth <= 374 ? '12px' :
                              window.innerWidth <= 767 ? '16px' :
                              window.innerWidth <= 991 ? '20px' : '24px';

        console.log(`🗳️ ELECTIONS PAGE FIX: Applying ${currentPadding} padding`);

        // Call comprehensive header fix first if available
        if (window.applyComprehensiveHeaderFixes) {
            window.applyComprehensiveHeaderFixes();
        }

        // Fix main container
        const mainContainer = document.querySelector('.container-fluid');
        if (mainContainer) {
            mainContainer.style.setProperty('padding-left', currentPadding, 'important');
            mainContainer.style.setProperty('padding-right', currentPadding, 'important');
            mainContainer.style.setProperty('width', '100%', 'important');
            mainContainer.style.setProperty('max-width', '100%', 'important');
            mainContainer.style.setProperty('margin-left', '0', 'important');
            mainContainer.style.setProperty('margin-right', '0', 'important');
            mainContainer.setAttribute('data-elections-fix', 'applied');
        }

        // Fix elections container
        const electionsContainer = document.querySelector('.elections-container');
        if (electionsContainer) {
            electionsContainer.style.setProperty('max-width', '100%', 'important');
            electionsContainer.style.setProperty('padding', '0', 'important');
            electionsContainer.style.setProperty('margin', '0', 'important');
            electionsContainer.style.setProperty('width', '100%', 'important');
            electionsContainer.setAttribute('data-elections-container-fix', 'applied');
        }

        // Fix all page headers (comprehensive list)
        const pageHeaders = document.querySelectorAll('.elections-header, .profile-header, .settings-header, .event-detail-header, .events-header, .news-header, .finance-header, .support-header, .chat-header, .tickets-header, .page-header, .custom-header, .header, .modern-header, .dashboard-header, .admin-header, .user-header, .management-header, .system-header, .welfare-header, .committees-header, .senate-header, .portfolio-header, .departments-header, .feedback-header, .messaging-header');
        
        pageHeaders.forEach(header => {
            header.style.setProperty('margin-left', `calc(-1 * ${currentPadding})`, 'important');
            header.style.setProperty('margin-right', `calc(-1 * ${currentPadding})`, 'important');
            header.style.setProperty('padding-left', currentPadding, 'important');
            header.style.setProperty('padding-right', currentPadding, 'important');
            header.style.setProperty('width', `calc(100% + 2 * ${currentPadding})`, 'important');
            header.style.setProperty('position', 'relative', 'important');
            header.style.setProperty('z-index', '10', 'important');
            header.style.setProperty('display', 'block', 'important');
            header.style.setProperty('visibility', 'visible', 'important');
            header.style.setProperty('opacity', '1', 'important');
            header.setAttribute('data-header-fix', 'applied');

            // Fix header content
            const headerContent = header.querySelector('[class*="header-content"], [class*="-content"]');
            if (headerContent) {
                headerContent.style.setProperty('display', 'flex', 'important');
                headerContent.style.setProperty('justify-content', 'space-between', 'important');
                headerContent.style.setProperty('align-items', 'center', 'important');
                headerContent.style.setProperty('flex-wrap', 'wrap', 'important');
                headerContent.style.setProperty('gap', '1.5rem', 'important');
                headerContent.style.setProperty('width', '100%', 'important');
                headerContent.style.setProperty('position', 'relative', 'important');
                headerContent.style.setProperty('z-index', '15', 'important');
                headerContent.style.setProperty('visibility', 'visible', 'important');
                headerContent.style.setProperty('opacity', '1', 'important');
            }

            // Fix header main content
            const headerMain = header.querySelector('[class*="header-main"], [class*="-main"]');
            if (headerMain) {
                headerMain.style.setProperty('flex', '1', 'important');
                headerMain.style.setProperty('text-align', 'center', 'important');
                headerMain.style.setProperty('width', '100%', 'important');
                headerMain.style.setProperty('max-width', '100%', 'important');
                headerMain.style.setProperty('display', 'flex', 'important');
                headerMain.style.setProperty('flex-direction', 'column', 'important');
                headerMain.style.setProperty('align-items', 'center', 'important');
                headerMain.style.setProperty('justify-content', 'center', 'important');
                headerMain.style.setProperty('position', 'relative', 'important');
                headerMain.style.setProperty('z-index', '16', 'important');
            }

            // Fix header actions
            const headerActions = header.querySelector('[class*="header-actions"], [class*="-actions"]');
            if (headerActions) {
                headerActions.style.setProperty('display', 'flex', 'important');
                headerActions.style.setProperty('align-items', 'center', 'important');
                headerActions.style.setProperty('gap', '0.8rem', 'important');
                headerActions.style.setProperty('flex-wrap', 'wrap', 'important');
                headerActions.style.setProperty('visibility', 'visible', 'important');
                headerActions.style.setProperty('opacity', '1', 'important');
                headerActions.style.setProperty('position', 'relative', 'important');
                headerActions.style.setProperty('z-index', '20', 'important');
                headerActions.style.setProperty('max-height', 'none', 'important');
                headerActions.style.setProperty('overflow', 'visible', 'important');
                headerActions.style.setProperty('clip', 'none', 'important');
                headerActions.style.setProperty('clip-path', 'none', 'important');
                headerActions.style.setProperty('min-height', '40px', 'important');
                headerActions.style.setProperty('width', 'auto', 'important');
                headerActions.style.setProperty('flex-shrink', '0', 'important');
            }

            // Fix all buttons in header
            const headerButtons = header.querySelectorAll('button, .btn, [class*="btn-"], a[class*="btn"]');
            headerButtons.forEach(button => {
                button.style.setProperty('display', 'inline-flex', 'important');
                button.style.setProperty('align-items', 'center', 'important');
                button.style.setProperty('justify-content', 'center', 'important');
                button.style.setProperty('visibility', 'visible', 'important');
                button.style.setProperty('opacity', '1', 'important');
                button.style.setProperty('position', 'relative', 'important');
                button.style.setProperty('z-index', '30', 'important');
                button.style.setProperty('min-height', '40px', 'important');
                button.style.setProperty('min-width', '80px', 'important');
                button.style.setProperty('white-space', 'nowrap', 'important');
                button.style.setProperty('max-height', 'none', 'important');
                button.style.setProperty('overflow', 'visible', 'important');
                button.style.setProperty('clip', 'none', 'important');
                button.style.setProperty('clip-path', 'none', 'important');
                button.style.setProperty('transform', 'none', 'important');
                button.style.setProperty('pointer-events', 'auto', 'important');
                button.style.setProperty('cursor', 'pointer', 'important');
            });
        });

        // Fix content panels
        const contentPanels = document.querySelectorAll('.content-panel');
        contentPanels.forEach(panel => {
            panel.style.setProperty('margin-left', '0', 'important');
            panel.style.setProperty('margin-right', '0', 'important');
            panel.style.setProperty('width', '100%', 'important');
            panel.style.setProperty('max-width', '100%', 'important');
            panel.style.setProperty('box-sizing', 'border-box', 'important');
        });

        // Fix section headers
        const sectionHeaders = document.querySelectorAll('.section-header');
        sectionHeaders.forEach(header => {
            header.style.setProperty('margin-left', '0', 'important');
            header.style.setProperty('margin-right', '0', 'important');
            header.style.setProperty('width', '100%', 'important');
            header.style.setProperty('max-width', '100%', 'important');
            header.style.setProperty('box-sizing', 'border-box', 'important');
        });

        // Fix admin tools container
        const adminToolsContainer = document.querySelector('.admin-tools-container');
        if (adminToolsContainer) {
            adminToolsContainer.style.setProperty('width', '100%', 'important');
            adminToolsContainer.style.setProperty('max-width', '100%', 'important');
            adminToolsContainer.style.setProperty('margin-left', '0', 'important');
            adminToolsContainer.style.setProperty('margin-right', '0', 'important');
            adminToolsContainer.style.setProperty('box-sizing', 'border-box', 'important');
        }

        // Fix admin tools
        const adminTools = document.querySelectorAll('.admin-tool');
        adminTools.forEach(tool => {
            tool.style.setProperty('width', '100%', 'important');
            tool.style.setProperty('max-width', '100%', 'important');
            tool.style.setProperty('margin-left', '0', 'important');
            tool.style.setProperty('margin-right', '0', 'important');
            tool.style.setProperty('box-sizing', 'border-box', 'important');
        });

        // Fix table responsive
        const tableResponsive = document.querySelector('.table-responsive');
        if (tableResponsive) {
            tableResponsive.style.setProperty('width', '100%', 'important');
            tableResponsive.style.setProperty('max-width', '100%', 'important');
            tableResponsive.style.setProperty('margin-left', '0', 'important');
            tableResponsive.style.setProperty('margin-right', '0', 'important');
            tableResponsive.style.setProperty('overflow-x', 'auto', 'important');
            tableResponsive.style.setProperty('box-sizing', 'border-box', 'important');
        }

        // Mark as fixed
        document.body.setAttribute('data-elections-page-fix', 'applied');
        document.body.setAttribute('data-elections-padding', currentPadding);

        console.log('✅ ELECTIONS PAGE FIX: Applied successfully');
    }

    // Initialize elections page fixes
    function initializeElectionsPageFixes() {
        if (!isElectionsPage()) return;

        console.log('🗳️ ELECTIONS PAGE FIX: Initializing...');
        
        // Apply immediately
        applyElectionsPageFixes();
        
        // Apply on resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(applyElectionsPageFixes, 50);
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
                    setTimeout(applyElectionsPageFixes, 10);
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        // Apply every 3 seconds to ensure consistency
        setInterval(function() {
            if (window.innerWidth < 1200) {
                const hasElectionsFix = document.body.getAttribute('data-elections-page-fix') === 'applied';
                if (!hasElectionsFix) {
                    console.log('🔧 ELECTIONS PAGE FIX: Reapplying lost fixes');
                    applyElectionsPageFixes();
                }
            }
        }, 3000);

        console.log('✅ ELECTIONS PAGE FIX: Initialization complete');
    }

    // Initialize based on document state
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeElectionsPageFixes);
    } else {
        initializeElectionsPageFixes();
    }

    // Also initialize on next tick
    setTimeout(initializeElectionsPageFixes, 0);

    // Expose function globally
    window.applyElectionsPageFixes = applyElectionsPageFixes;

})();