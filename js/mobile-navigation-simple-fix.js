/**
 * Mobile Navigation Simple Fix
 * Simple solution: Navigate immediately when sidebar links are clicked on mobile
 * Don't try to prevent sidebar - just ensure navigation happens first
 */

(function() {
    'use strict';

    function isMobile() {
        return window.innerWidth <= 991;
    }

    function setupMobileNavigation() {
        if (!isMobile()) return;

        console.log('📱 Setting up mobile navigation fix...');

        // Get all sidebar navigation links
        const sidebarLinks = document.querySelectorAll('.sidebar .sidebar-link[href]');
        
        if (!sidebarLinks.length) {
            console.log('❌ No sidebar links found');
            return;
        }

        console.log(`🔗 Found ${sidebarLinks.length} sidebar links`);

        // Add immediate navigation to each link
        sidebarLinks.forEach((link, index) => {
            const href = link.getAttribute('href');
            
            if (href && href !== '#' && !href.startsWith('javascript:')) {
                // Add click handler that navigates immediately
                link.addEventListener('click', function(e) {
                    console.log(`🚀 Immediate navigation to: ${href}`);
                    
                    // Navigate immediately - don't wait for anything
                    window.location.href = href;
                    
                    // Prevent any other handlers from running
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    return false;
                }, true); // Use capture phase to run first

                // Also handle touch events for better mobile support
                link.addEventListener('touchend', function(e) {
                    // Only handle if this was a tap (not a scroll)
                    if (e.changedTouches.length === 1) {
                        console.log(`👆 Touch navigation to: ${href}`);
                        
                        // Small delay to ensure it's not a scroll
                        setTimeout(() => {
                            window.location.href = href;
                        }, 50);
                        
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }, { passive: false });

                // Ensure the link is clickable
                link.style.pointerEvents = 'auto';
                link.style.cursor = 'pointer';
                
                console.log(`✅ Enhanced link ${index + 1}: ${href}`);
            }
        });

        console.log('✅ Mobile navigation fix applied');
    }

    // Apply fix when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupMobileNavigation);
    } else {
        setupMobileNavigation();
    }

    // Re-apply on window resize
    window.addEventListener('resize', function() {
        setTimeout(setupMobileNavigation, 100);
    });

})();