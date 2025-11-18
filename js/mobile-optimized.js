/**
 * Mobile Optimized JavaScript
 * Consolidated mobile functionality for fast loading and no interference
 */

(function() {
    'use strict';

    let isInitialized = false;

    function isMobile() {
        return window.innerWidth <= 991;
    }

    function initializeMobile() {
        if (!isMobile() || isInitialized) return;

        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle-navbar');
        
        if (!sidebar || !sidebarToggle) return;

        // 1. Sidebar toggle functionality
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isVisible = sidebar.classList.contains('show');
            
            if (isVisible) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        // 2. Navigation links - immediate navigation
        const sidebarLinks = document.querySelectorAll('.sidebar .sidebar-link[href]');
        sidebarLinks.forEach(link => {
            const href = link.getAttribute('href');
            
            if (href && href !== '#' && !href.startsWith('javascript:')) {
                link.addEventListener('click', function(e) {
                    // Close sidebar and navigate immediately
                    closeSidebar();
                    window.location.href = href;
                    
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
            }
        });

        // 3. Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!isMobile() || !sidebar.classList.contains('show')) return;
            
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                closeSidebar();
            }
        });

        // 4. Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                closeSidebar();
            }
        });

        // 5. Ensure sidebar starts closed
        closeSidebar();

        isInitialized = true;
    }

    function openSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.add('show');
            document.body.classList.add('sidebar-open');
        }
    }

    function closeSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    }

    function resetOnResize() {
        if (!isMobile()) {
            // Desktop mode - reset mobile classes
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            }
            isInitialized = false;
        } else {
            // Mobile mode - initialize if needed
            setTimeout(initializeMobile, 100);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMobile);
    } else {
        initializeMobile();
    }

    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(resetOnResize, 150);
    });

})();