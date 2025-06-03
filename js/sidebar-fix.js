/**
 * Sidebar Fix Script
 * This script ensures that all sidebar links are visible, especially the Settings link
 */
document.addEventListener('DOMContentLoaded', function() {
    // Fix sidebar scrolling
    fixSidebarScrolling();
    
    // Add resize listener to handle window size changes
    window.addEventListener('resize', fixSidebarScrolling);
});

/**
 * Fix sidebar scrolling issues
 */
function fixSidebarScrolling() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;
    
    // Force sidebar to be scrollable
    sidebar.style.overflowY = 'scroll';
    
    // Make sure all links are visible
    const links = sidebar.querySelectorAll('.sidebar-link');
    const lastLink = links[links.length - 1];
    
    if (lastLink) {
        // Add padding to the bottom of the sidebar if needed
        const sidebarRect = sidebar.getBoundingClientRect();
        const lastLinkRect = lastLink.getBoundingClientRect();
        
        if (lastLinkRect.bottom > sidebarRect.bottom) {
            // Add padding to make sure the last link is visible
            sidebar.style.paddingBottom = '60px';
        }
    }
} 