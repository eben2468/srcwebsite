/**
 * Profile Header Full-Width JavaScript
 * Ensures the profile header maintains full-width layout and proper alignment
 */

(function() {
    'use strict';

    // Function to adjust profile header layout
    function adjustProfileHeaderLayout() {
        const profileHeader = document.querySelector('.profile-header');
        if (!profileHeader) return;

        const profileContent = profileHeader.querySelector('.profile-header-content');
        const profileMain = profileHeader.querySelector('.profile-header-main');
        const profileActions = profileHeader.querySelector('.profile-header-actions');

        if (!profileContent || !profileMain || !profileActions) return;

        // Get viewport width
        const viewportWidth = window.innerWidth;
        const isMobile = viewportWidth <= 768;
        const isTablet = viewportWidth <= 991 && viewportWidth > 768;
        const isDesktop = viewportWidth > 991;

        // Apply full-width styling
        profileHeader.style.width = '100vw';
        profileHeader.style.maxWidth = '100vw';
        profileHeader.style.marginLeft = 'calc(-50vw + 50%)';
        profileHeader.style.marginRight = 'calc(-50vw + 50%)';
        profileHeader.style.position = 'relative';
        profileHeader.style.borderRadius = '0';

        // Adjust content container
        profileContent.style.maxWidth = isDesktop ? '1200px' : '100%';
        profileContent.style.margin = '0 auto';
        profileContent.style.padding = isMobile ? '0 1rem' : (isTablet ? '0 1.5rem' : '0 2rem');

        if (isMobile || isTablet) {
            // Mobile/Tablet layout
            profileContent.style.flexDirection = 'column';
            profileContent.style.alignItems = 'center';
            profileContent.style.textAlign = 'center';
            profileContent.style.gap = '1rem';

            // Center main content
            profileMain.style.width = '100%';
            profileMain.style.textAlign = 'center';
            profileMain.style.display = 'flex';
            profileMain.style.flexDirection = 'column';
            profileMain.style.alignItems = 'center';

            // Center actions below main content
            profileActions.style.position = 'static';
            profileActions.style.transform = 'none';
            profileActions.style.justifyContent = 'center';
            profileActions.style.width = '100%';
            profileActions.style.marginTop = '1rem';

            if (isMobile) {
                profileActions.style.flexDirection = 'column';
                profileActions.style.gap = '0.5rem';
            }
        } else {
            // Desktop layout
            profileContent.style.flexDirection = 'row';
            profileContent.style.justifyContent = 'space-between';
            profileContent.style.alignItems = 'center';
            profileContent.style.gap = '1.5rem';

            // Center main content
            profileMain.style.flex = '1';
            profileMain.style.textAlign = 'center';
            profileMain.style.display = 'flex';
            profileMain.style.flexDirection = 'column';
            profileMain.style.alignItems = 'center';

            // Right-align actions
            profileActions.style.position = 'absolute';
            profileActions.style.right = '2rem';
            profileActions.style.top = '50%';
            profileActions.style.transform = 'translateY(-50%)';
            profileActions.style.justifyContent = 'flex-end';
            profileActions.style.flexDirection = 'row';
            profileActions.style.gap = '0.8rem';
            profileActions.style.width = 'auto';
            profileActions.style.marginTop = '0';
        }

        // Adjust for sidebar on desktop
        if (isDesktop) {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebar && mainContent) {
                const sidebarWidth = sidebar.classList.contains('collapsed') ? 60 : 260;
                const adjustment = sidebarWidth / 2;
                
                profileHeader.style.width = `calc(100vw - ${sidebarWidth}px)`;
                profileHeader.style.marginLeft = `calc(-50vw + 50% + ${adjustment}px)`;
                profileHeader.style.marginRight = `calc(-50vw + 50% - ${adjustment}px)`;
                
                // Adjust actions position for sidebar
                profileActions.style.right = '2rem';
            }
        }

        console.log('✅ Profile header layout adjusted for', isMobile ? 'mobile' : (isTablet ? 'tablet' : 'desktop'));
    }

    // Function to handle button alignment
    function alignHeaderButtons() {
        const buttons = document.querySelectorAll('.btn-header-action');
        const isMobile = window.innerWidth <= 768;

        buttons.forEach(button => {
            if (isMobile) {
                button.style.width = '100%';
                button.style.maxWidth = '280px';
                button.style.textAlign = 'center';
            } else {
                button.style.width = 'auto';
                button.style.maxWidth = 'none';
                button.style.textAlign = 'left';
            }
        });
    }

    // Function to ensure proper spacing
    function adjustSpacing() {
        const profileHeader = document.querySelector('.profile-header');
        if (!profileHeader) return;

        // Ensure proper top margin (account for fixed navbar)
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            const navbarHeight = navbar.offsetHeight;
            profileHeader.style.marginTop = '0';
            
            // Add padding to body if needed
            const body = document.body;
            if (body.style.paddingTop) {
                // Body already has padding for navbar
            } else {
                // Ensure there's space for the navbar
                profileHeader.style.marginTop = '0';
            }
        }
    }

    // Function to handle animations
    function handleAnimations() {
        const profileHeader = document.querySelector('.profile-header');
        if (!profileHeader) return;

        // Add animation classes if not present
        if (!profileHeader.classList.contains('animate__animated')) {
            profileHeader.classList.add('animate__animated', 'animate__fadeInDown');
        }

        // Remove animation after completion to allow re-triggering
        setTimeout(() => {
            profileHeader.classList.remove('animate__fadeInDown');
        }, 600);
    }

    // Debounce function for resize events
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

    // Initialize profile header layout
    function initializeProfileHeader() {
        console.log('🎨 Initializing profile header full-width layout...');

        // Apply initial layout
        adjustProfileHeaderLayout();
        alignHeaderButtons();
        adjustSpacing();
        handleAnimations();

        console.log('✅ Profile header full-width layout initialized');
    }

    // Handle window resize
    const debouncedResize = debounce(() => {
        adjustProfileHeaderLayout();
        alignHeaderButtons();
        adjustSpacing();
    }, 150);

    // Handle sidebar toggle
    function handleSidebarToggle() {
        // Wait for sidebar animation to complete
        setTimeout(() => {
            adjustProfileHeaderLayout();
        }, 350);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeProfileHeader);
    } else {
        initializeProfileHeader();
    }

    // Handle window resize
    window.addEventListener('resize', debouncedResize);

    // Handle sidebar toggle events
    document.addEventListener('click', function(e) {
        if (e.target.matches('#sidebar-toggle-navbar') || 
            e.target.closest('#sidebar-toggle-navbar')) {
            handleSidebarToggle();
        }
    });

    // Handle orientation change on mobile
    window.addEventListener('orientationchange', function() {
        setTimeout(() => {
            adjustProfileHeaderLayout();
            alignHeaderButtons();
        }, 100);
    });

    // Re-initialize on visibility change (for mobile browsers)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            setTimeout(initializeProfileHeader, 100);
        }
    });

    // Expose global function for manual adjustment
    window.adjustProfileHeaderLayout = adjustProfileHeaderLayout;

    console.log('📱 Profile header full-width JavaScript loaded');

})();