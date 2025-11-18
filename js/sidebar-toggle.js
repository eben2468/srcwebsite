/**
 * Optimized Sidebar Toggle Functionality
 * Handles both mobile and desktop sidebar behavior
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const navbarToggleBtn = document.getElementById('sidebar-toggle-navbar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    if (!sidebar || !navbarToggleBtn) {
        console.warn('Sidebar or navbar toggle button not found');
        return;
    }

    function isMobile() {
        return window.innerWidth <= 991;
    }

    function isDesktop() {
        return window.innerWidth > 991;
    }

    // Mobile sidebar toggle - show/hide sidebar
    function toggleMobileSidebar() {
        if (!isMobile()) return;

        const isShown = sidebar.classList.contains('show');
        console.log('Toggling sidebar. Currently shown:', isShown);

        if (isShown) {
            // Hide sidebar
            sidebar.classList.remove('show');
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('visible');
            }
            // Restore body scroll
            document.body.style.overflow = '';
        } else {
            // Show sidebar
            sidebar.classList.add('show');
            if (sidebarOverlay) {
                sidebarOverlay.classList.add('visible');
            }
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }
    }

    // Desktop sidebar toggle functionality
    function toggleDesktopSidebar() {
        if (!isDesktop()) return;

        const isCollapsed = sidebar.classList.contains('collapsed');

        if (isCollapsed) {
            // Expand sidebar
            sidebar.classList.remove('collapsed');
            sidebar.style.width = '260px';
            if (mainContent) {
                mainContent.style.marginLeft = '260px';
                mainContent.classList.remove('sidebar-collapsed');
            }
            localStorage.setItem('sidebar-collapsed', 'false');
            
            // Restore text for all sidebar links
            const sidebarLinks = sidebar.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                const icon = link.querySelector('i');
                if (icon && link.hasAttribute('data-original-text')) {
                    const originalText = link.getAttribute('data-original-text');
                    link.innerHTML = icon.outerHTML + ' <span class="link-text">' + originalText + '</span>';
                    link.classList.remove('icon-only');
                }
            });
        } else {
            // Collapse sidebar to icon-only mode
            sidebar.classList.add('collapsed');
            sidebar.style.width = '60px';
            if (mainContent) {
                mainContent.style.marginLeft = '60px';
                mainContent.classList.add('sidebar-collapsed');
            }
            localStorage.setItem('sidebar-collapsed', 'true');
            
            // Convert all sidebar links to icon-only
            const sidebarLinks = sidebar.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                const icon = link.querySelector('i');
                if (icon) {
                    const linkText = link.textContent.trim().replace(icon.textContent.trim(), '').trim();
                    link.setAttribute('data-original-text', linkText);
                    link.setAttribute('title', linkText);
                    link.innerHTML = icon.outerHTML;
                    link.classList.add('icon-only');
                }
            });
        }
    }

    // Handle toggle button click
    navbarToggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Sidebar toggle clicked. Screen width:', window.innerWidth);

        if (isMobile()) {
            toggleMobileSidebar();
        } else {
            toggleDesktopSidebar();
        }
    });

    // Close sidebar when clicking overlay on mobile
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function(e) {
            if (isMobile()) {
                e.preventDefault();
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('visible');
                document.body.style.overflow = '';
            }
        });
    }

    // Handle window resize - close sidebar when switching to desktop
    window.addEventListener('resize', function() {
        if (isDesktop() && sidebar && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('visible');
            }
            document.body.style.overflow = '';
        }
    });

    // On load: hide sidebar on mobile, show on desktop
    if (isDesktop()) {
        sidebar.classList.remove('show');
    } else {
        sidebar.classList.remove('show');
    }

    // Apply saved desktop state on load
    if (isDesktop()) {
        const sidebarCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            sidebar.style.width = '60px';
            if (mainContent) {
                mainContent.style.marginLeft = '60px';
                mainContent.classList.add('sidebar-collapsed');
            }
            
            // Store original text and convert to icon-only
            const sidebarLinks = sidebar.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                const icon = link.querySelector('i');
                if (icon) {
                    const linkText = link.textContent.trim().replace(icon.textContent.trim(), '').trim();
                    link.setAttribute('data-original-text', linkText);
                    link.setAttribute('title', linkText);
                    link.innerHTML = icon.outerHTML;
                    link.classList.add('icon-only');
                }
            });
        } else {
            sidebar.classList.remove('collapsed');
            sidebar.style.width = '260px';
            if (mainContent) {
                mainContent.style.marginLeft = '260px';
                mainContent.classList.remove('sidebar-collapsed');
            }
        }
    }

    // Handle window resize
    window.addEventListener('resize', function() {
        if (isDesktop()) {
            // Apply saved desktop state
            const sidebarCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                sidebar.style.width = '60px';
                if (mainContent) {
                    mainContent.style.marginLeft = '60px';
                    mainContent.classList.add('sidebar-collapsed');
                }
            } else {
                sidebar.classList.remove('collapsed');
                sidebar.style.width = '260px';
                if (mainContent) {
                    mainContent.style.marginLeft = '260px';
                    mainContent.classList.remove('sidebar-collapsed');
                }
            }
        } else {
            // Reset desktop styles on mobile
            sidebar.classList.remove('collapsed');
            sidebar.style.width = '';
            if (mainContent) mainContent.style.marginLeft = '';
        }
    });

    // Keyboard shortcut (Ctrl+B) for desktop only
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'b' && isDesktop()) {
            e.preventDefault();
            toggleDesktopSidebar();
        }
    });
});