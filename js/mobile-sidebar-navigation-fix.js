/**
 * Mobile Sidebar Navigation Fix
 * Ensures both sidebar toggle and navigation links work properly on mobile
 */

(function() {
    'use strict';

    function isMobile() {
        return window.innerWidth <= 991;
    }

    function setupMobileSidebar() {
        if (!isMobile()) return;

        console.log('📱 Setting up mobile sidebar and navigation...');

        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle-navbar');
        const sidebarLinks = document.querySelectorAll('.sidebar .sidebar-link[href]');

        if (!sidebar) {
            console.log('❌ Sidebar not found');
            return;
        }

        // 1. Fix sidebar toggle functionality
        if (sidebarToggle) {
            // Remove existing event listeners by cloning
            const newToggle = sidebarToggle.cloneNode(true);
            sidebarToggle.parentNode.replaceChild(newToggle, sidebarToggle);

            // Add clean toggle functionality
            newToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('🔄 Sidebar toggle clicked');
                
                const isHidden = !sidebar.classList.contains('show');
                
                if (isHidden) {
                    // Show sidebar
                    sidebar.classList.add('show');
                    sidebar.classList.remove('hide');
                    document.body.classList.add('sidebar-open');
                    console.log('📂 Sidebar opened');
                } else {
                    // Hide sidebar
                    sidebar.classList.remove('show');
                    sidebar.classList.add('hide');
                    document.body.classList.remove('sidebar-open');
                    console.log('📁 Sidebar closed');
                }
            });

            console.log('✅ Sidebar toggle fixed');
        }

        // 2. Fix navigation links
        if (sidebarLinks.length > 0) {
            console.log(`🔗 Fixing ${sidebarLinks.length} navigation links`);

            sidebarLinks.forEach((link, index) => {
                const href = link.getAttribute('href');
                
                if (href && href !== '#' && !href.startsWith('javascript:')) {
                    // Remove existing event listeners by cloning
                    const newLink = link.cloneNode(true);
                    link.parentNode.replaceChild(newLink, link);

                    // Add immediate navigation
                    newLink.addEventListener('click', function(e) {
                        console.log(`🚀 Navigation link clicked: ${href}`);
                        
                        // Close sidebar first
                        sidebar.classList.remove('show');
                        sidebar.classList.add('hide');
                        document.body.classList.remove('sidebar-open');
                        
                        // Navigate immediately
                        window.location.href = href;
                        
                        // Prevent other handlers
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    });

                    // Ensure link is clickable
                    newLink.style.pointerEvents = 'auto';
                    newLink.style.cursor = 'pointer';
                    
                    console.log(`✅ Fixed navigation link ${index + 1}: ${href}`);
                }
            });
        }

        // 3. Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!isMobile()) return;
            
            if (sidebar.classList.contains('show')) {
                // Check if click is outside sidebar and not on toggle
                if (!sidebar.contains(e.target) && 
                    !document.getElementById('sidebar-toggle-navbar')?.contains(e.target)) {
                    
                    sidebar.classList.remove('show');
                    sidebar.classList.add('hide');
                    document.body.classList.remove('sidebar-open');
                    console.log('📁 Sidebar closed by outside click');
                }
            }
        });

        // 4. Ensure sidebar starts hidden on mobile
        sidebar.classList.remove('show');
        sidebar.classList.add('hide');
        document.body.classList.remove('sidebar-open');

        console.log('✅ Mobile sidebar and navigation setup complete');
    }

    // Apply fix when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupMobileSidebar);
    } else {
        setupMobileSidebar();
    }

    // Re-apply on window resize
    window.addEventListener('resize', function() {
        setTimeout(setupMobileSidebar, 100);
    });

})();