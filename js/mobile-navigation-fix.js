/**
 * Optimized Mobile Navigation JavaScript
 * Streamlined solution for mobile sidebar and navigation
 */

(function() {
    'use strict';

    function isMobile() {
        return window.innerWidth <= 991;
    }

    function setupMobileNavigation() {
        if (!isMobile()) return;

        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle-navbar');
        const sidebarLinks = document.querySelectorAll('.sidebar .sidebar-link[href]');

        if (!sidebar) return;

        // 1. Setup sidebar toggle
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const isVisible = sidebar.classList.contains('show');
                
                if (isVisible) {
                    sidebar.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                } else {
                    sidebar.classList.add('show');
                    document.body.classList.add('sidebar-open');
                }
            });
        }

        // 2. Setup navigation links for immediate navigation
        sidebarLinks.forEach(link => {
            const href = link.getAttribute('href');
            
            if (href && href !== '#' && !href.startsWith('javascript:')) {
                link.addEventListener('click', function(e) {
                    // Close sidebar immediately
                    sidebar.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                    
                    // Navigate immediately
                    window.location.href = href;
                    
                    // Prevent other handlers
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
            }
        });

        // 3. Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!isMobile() || !sidebar.classList.contains('show')) return;
            
            if (!sidebar.contains(e.target) && 
                !sidebarToggle?.contains(e.target)) {
                sidebar.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            }
        });

        // 4. Ensure sidebar starts hidden
        sidebar.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupMobileNavigation);
    } else {
        setupMobileNavigation();
    }

    // Re-initialize on window resize
    window.addEventListener('resize', function() {
        setTimeout(setupMobileNavigation, 100);
    });

})();