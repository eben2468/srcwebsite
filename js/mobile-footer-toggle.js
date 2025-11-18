/**
 * Mobile Footer Toggle JavaScript
 * Handles showing/hiding footer on mobile devices with toggle button
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Mobile footer toggle script - DISABLED (footer always visible)');

    // Footer toggle functionality disabled - footer is always visible
    // Check if we're on mobile
    function isMobile() {
        return window.innerWidth <= 991.98;
    }
    
    // Get footer element
    const footer = document.querySelector('footer.src-footer') || document.querySelector('.src-footer');
    
    if (!footer) {
        console.warn('Footer element not found');
        return;
    }
    
    // Create footer toggle button
    function createToggleButton() {
        const existingBtn = document.querySelector('.footer-toggle-btn');
        if (existingBtn) {
            existingBtn.remove();
        }
        
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'footer-toggle-btn';
        toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
        toggleBtn.setAttribute('aria-label', 'Toggle Footer');
        toggleBtn.setAttribute('title', 'Show Footer');
        
        document.body.appendChild(toggleBtn);
        return toggleBtn;
    }
    
    // Create backdrop overlay
    function createBackdrop() {
        const existingBackdrop = document.querySelector('.footer-backdrop');
        if (existingBackdrop) {
            existingBackdrop.remove();
        }
        
        const backdrop = document.createElement('div');
        backdrop.className = 'footer-backdrop';
        document.body.appendChild(backdrop);
        return backdrop;
    }
    
    // Create close button for footer
    function createCloseButton() {
        const existingCloseBtn = footer.querySelector('.footer-close-btn');
        if (existingCloseBtn) {
            existingCloseBtn.remove();
        }

        const closeBtn = document.createElement('button');
        closeBtn.className = 'footer-close-btn';
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        closeBtn.setAttribute('aria-label', 'Close Footer');
        closeBtn.setAttribute('title', 'Close Footer');
        closeBtn.setAttribute('type', 'button');
        closeBtn.style.cssText = `
            position: absolute !important;
            top: 15px !important;
            right: 15px !important;
            background: rgba(255, 255, 255, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            color: white !important;
            width: 36px !important;
            height: 36px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1rem !important;
            cursor: pointer !important;
            z-index: 10001 !important;
            outline: none !important;
        `;

        footer.style.position = 'relative';
        footer.appendChild(closeBtn);
        return closeBtn;
    }
    
    // Show footer
    function showFooter() {
        if (!isMobile()) return;

        console.log('Showing mobile footer');
        footer.classList.add('show');
        backdrop.classList.add('show');
        toggleBtn.classList.add('active');
        toggleBtn.setAttribute('title', 'Hide Footer');
        document.body.classList.add('footer-open');

        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Ensure footer content is fully visible
        footer.style.display = 'block';
        footer.style.opacity = '1';
        footer.style.transform = 'translateY(0)';

        // Focus management for accessibility
        footer.setAttribute('tabindex', '-1');

        // Scroll to top of footer to ensure all content is visible
        setTimeout(() => {
            footer.scrollTop = 0;
            footer.focus();
        }, 100);
    }
    
    // Hide footer
    function hideFooter() {
        if (!isMobile()) return;

        console.log('Hiding mobile footer');
        footer.classList.remove('show');
        backdrop.classList.remove('show');
        toggleBtn.classList.remove('active');
        toggleBtn.setAttribute('title', 'Show Footer');
        document.body.classList.remove('footer-open');

        // Restore body scroll
        document.body.style.overflow = '';

        // Reset footer styles
        footer.style.display = 'none';
        footer.style.opacity = '0';
        footer.style.transform = 'translateY(100%)';

        // Return focus to toggle button
        setTimeout(() => {
            toggleBtn.focus();
        }, 100);
    }
    
    // Toggle footer visibility
    function toggleFooter() {
        if (!isMobile()) return;
        
        const isVisible = footer.classList.contains('show');
        if (isVisible) {
            hideFooter();
        } else {
            showFooter();
        }
    }
    
    // Initialize mobile footer toggle - DISABLED
    function initMobileFooterToggle() {
        // Footer toggle functionality completely disabled
        // Remove any existing toggle elements
        const existingBtn = document.querySelector('.footer-toggle-btn');
        const existingBackdrop = document.querySelector('.footer-backdrop');
        const existingCloseBtn = footer ? footer.querySelector('.footer-close-btn') : null;

        if (existingBtn) existingBtn.remove();
        if (existingBackdrop) existingBackdrop.remove();
        if (existingCloseBtn) existingCloseBtn.remove();

        // Ensure footer is always visible
        if (footer) {
            footer.classList.remove('show');
            footer.style.display = 'block';
            footer.style.opacity = '1';
            footer.style.transform = 'none';
            footer.style.position = 'relative';
            footer.style.bottom = 'auto';
            footer.style.maxHeight = 'none';
            footer.style.overflow = 'visible';
        }

        document.body.classList.remove('footer-open');
        document.body.style.overflow = '';

        console.log('Footer toggle disabled - footer always visible');
        return;

        // Mobile footer toggle functionality disabled
        console.log('Mobile footer toggle functionality disabled');
        return;
        
        // Add event listeners with improved functionality
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Toggle button clicked');
            toggleFooter();
        });

        // Multiple event listeners for close button to ensure it works
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Close button clicked');
            hideFooter();
        });

        closeBtn.addEventListener('touchstart', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Close button touched');
            hideFooter();
        });

        closeBtn.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });

        backdrop.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Backdrop clicked');
            hideFooter();
        });
        
        // Keyboard support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && footer.classList.contains('show')) {
                hideFooter();
            }
        });
        
        // Touch support for mobile with improved swipe detection
        let touchStartY = 0;
        let touchStartTime = 0;

        footer.addEventListener('touchstart', function(e) {
            // Don't interfere with close button touches
            if (e.target.closest('.footer-close-btn')) {
                return;
            }
            touchStartY = e.touches[0].clientY;
            touchStartTime = Date.now();
        });

        footer.addEventListener('touchmove', function(e) {
            // Don't interfere with close button touches
            if (e.target.closest('.footer-close-btn')) {
                return;
            }

            const touchY = e.touches[0].clientY;
            const deltaY = touchY - touchStartY;
            const deltaTime = Date.now() - touchStartTime;

            // If swiping down quickly and at the top of footer content, allow closing
            if (deltaY > 80 && deltaTime < 500 && footer.scrollTop === 0) {
                console.log('Swipe down detected, closing footer');
                hideFooter();
            }
        });
        
        // Ensure footer is hidden initially on mobile
        footer.classList.remove('show');
        backdrop.classList.remove('show');
        toggleBtn.classList.remove('active');
        document.body.classList.remove('footer-open');
        document.body.style.overflow = '';
        
        console.log('Mobile footer toggle initialized successfully');
    }
    
    // Initialize on page load
    initMobileFooterToggle();
    
    // Re-initialize on window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            console.log('Window resized, reinitializing footer toggle');
            initMobileFooterToggle();
        }, 250);
    });
    
    // Handle orientation change
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            console.log('Orientation changed, reinitializing footer toggle');
            initMobileFooterToggle();
        }, 500);
    });
    
    // Expose functions globally for debugging
    window.mobileFooterToggle = {
        show: showFooter,
        hide: hideFooter,
        toggle: toggleFooter,
        isMobile: isMobile,
        init: initMobileFooterToggle
    };
    
    console.log('Mobile footer toggle script loaded successfully');
});

// Additional safety check for page navigation
window.addEventListener('beforeunload', function() {
    // Ensure footer is hidden when navigating away
    const footer = document.querySelector('footer.src-footer') || document.querySelector('.src-footer');
    if (footer) {
        footer.classList.remove('show');
        document.body.classList.remove('footer-open');
        document.body.style.overflow = '';
    }
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Hide footer when page becomes hidden
        const footer = document.querySelector('footer.src-footer') || document.querySelector('.src-footer');
        if (footer && footer.classList.contains('show')) {
            footer.classList.remove('show');
            const backdrop = document.querySelector('.footer-backdrop');
            if (backdrop) backdrop.classList.remove('show');
            const toggleBtn = document.querySelector('.footer-toggle-btn');
            if (toggleBtn) toggleBtn.classList.remove('active');
            document.body.classList.remove('footer-open');
            document.body.style.overflow = '';
        }
    }
});
