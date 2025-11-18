// Users Page Mobile Optimization Test Script
document.addEventListener('DOMContentLoaded', function() {
    // Only run on users page
    if (!document.body.classList.contains('users-page')) {
        return;
    }

    console.log('Users page mobile optimization loaded');

    // Function to check if we're on mobile
    function isMobile() {
        return window.innerWidth <= 768;
    }

    // Function to ensure full width on mobile
    function ensureFullWidth() {
        if (isMobile()) {
            // Force full width on all container elements
            const containers = document.querySelectorAll('.container-fluid, #main-wrapper, .content-wrapper');
            containers.forEach(container => {
                container.style.width = '100%';
                container.style.maxWidth = '100%';
                container.style.paddingLeft = '0';
                container.style.paddingRight = '0';
                container.style.marginLeft = '0';
                container.style.marginRight = '0';
            });

            // Ensure content cards use full width
            const contentCards = document.querySelectorAll('.content-card');
            contentCards.forEach(card => {
                card.style.marginLeft = '0';
                card.style.marginRight = '0';
                card.style.borderRadius = '0';
                card.style.borderLeft = 'none';
                card.style.borderRight = 'none';
            });

            console.log('Mobile full-width optimization applied');
        }
    }

    // Apply optimizations on load
    ensureFullWidth();

    // Reapply on window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(ensureFullWidth, 100);
    });

    // Add smooth scrolling for mobile
    if (isMobile()) {
        document.documentElement.style.scrollBehavior = 'smooth';
    }

    // Improve touch interactions on mobile
    if (isMobile()) {
        const actionButtons = document.querySelectorAll('.user-card-actions .btn, .btn-group .btn');
        actionButtons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            button.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });
    }

    // Log viewport information for debugging
    console.log('Viewport width:', window.innerWidth);
    console.log('Is mobile:', isMobile());
    console.log('Body classes:', document.body.className);
});