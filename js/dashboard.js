/**
 * SRC Management System Dashboard JS
 * Enhanced with comprehensive mobile responsive functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar elements - try multiple selectors
    const sidebar = document.querySelector('.sidebar') ||
                   document.querySelector('.dashboard-sidebar') ||
                   document.querySelector('[class*="sidebar"]');
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-navbar') ||
                            document.querySelector('[data-bs-toggle="sidebar"]') ||
                            document.querySelector('.sidebar-toggle');
    const body = document.body;

    // Debug info (can be removed in production)
    if (!sidebar) console.warn('Sidebar element not found');
    if (!sidebarToggleBtn) console.warn('Sidebar toggle button not found');

    // Mobile sidebar toggle function
    const toggleSidebar = () => {
        if (!sidebar) return;

        const isOpen = sidebar.classList.contains('show');

        if (isOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    };

    // Open sidebar function
    const openSidebar = () => {
        if (!sidebar) return;

        sidebar.classList.add('show');
        body.classList.add('sidebar-open');

        // Prevent body scroll on mobile
        if (window.innerWidth <= 991) {
            body.style.overflow = 'hidden';
        }
    };

    // Close sidebar function
    const closeSidebar = () => {
        if (!sidebar) return;

        sidebar.classList.remove('show');
        body.classList.remove('sidebar-open');

        // Restore body scroll
        body.style.overflow = '';
    };

    // Add event listeners for sidebar toggle
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Also try to find alternative toggle buttons
    const altToggleBtn = document.querySelector('[data-bs-toggle="sidebar"], .sidebar-toggle, .mobile-toggle');
    if (altToggleBtn && altToggleBtn !== sidebarToggleBtn) {
        altToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Remove overlay click handler since we're not using overlay

    // Close sidebar when clicking the close button (CSS ::before element)
    if (sidebar) {
        sidebar.addEventListener('click', function(e) {
            // Only handle close button on mobile
            if (window.innerWidth <= 991) {
                const rect = sidebar.getBoundingClientRect();
                const closeButtonArea = {
                    left: rect.right - 60,
                    right: rect.right - 15,
                    top: rect.top + 10,
                    bottom: rect.top + 55
                };

                if (e.clientX >= closeButtonArea.left && e.clientX <= closeButtonArea.right &&
                    e.clientY >= closeButtonArea.top && e.clientY <= closeButtonArea.bottom) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeSidebar();
                }
            }
        });
    }

    // Handle escape key to close sidebar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });

    // Handle window resize
    const handleResize = () => {
        const windowWidth = window.innerWidth;

        // Close sidebar on desktop
        if (windowWidth > 992 && sidebar) {
            closeSidebar();
        }

        // Adjust scroll padding based on screen size
        if (windowWidth <= 374) {
            document.documentElement.style.scrollPaddingTop = '50px';
        } else if (windowWidth <= 413) {
            document.documentElement.style.scrollPaddingTop = '55px';
        } else {
            document.documentElement.style.scrollPaddingTop = '60px';
        }
    };

    // Touch gesture support for mobile
    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;

    // Handle touch start
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    });

    // Handle touch end
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipeGesture();
    });

    // Handle swipe gestures
    const handleSwipeGesture = () => {
        const deltaX = touchEndX - touchStartX;
        const deltaY = touchEndY - touchStartY;
        const minSwipeDistance = 50;

        // Only handle horizontal swipes
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > minSwipeDistance) {
            if (window.innerWidth <= 991) {
                if (deltaX > 0 && touchStartX < 50) {
                    // Swipe right from left edge - open sidebar
                    openSidebar();
                } else if (deltaX < 0 && sidebar && sidebar.classList.contains('show')) {
                    // Swipe left when sidebar is open - close sidebar
                    closeSidebar();
                }
            }
        }
    };

    // Listen for window resize
    window.addEventListener('resize', handleResize);

    // Initialize
    handleResize();

    // Expose functions globally for other scripts
    window.mobileSidebar = {
        open: openSidebar,
        close: closeSidebar,
        toggle: toggleSidebar
    };
});