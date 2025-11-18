/**
 * Mobile Navbar Buttons Force Styling
 * JavaScript solution to ensure all navbar buttons are properly styled
 * when CSS alone isn't sufficient due to conflicting styles
 */

(function() {
    'use strict';

    function forceNavbarButtonStyling() {
        // Only apply on mobile devices
        if (window.innerWidth > 991) return;

        // Find the navbar right section
        const navbarRightSections = document.querySelectorAll(
            '.navbar .ms-auto, .navbar .navbar-nav:last-child, .navbar .d-flex.ms-auto'
        );

        navbarRightSections.forEach(section => {
            // Style the container
            section.style.cssText = `
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: flex-end !important;
                gap: 6px !important;
                height: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            `;

            // Get all direct children (buttons)
            const buttons = section.children;
            
            for (let i = 0; i < buttons.length; i++) {
                const button = buttons[i];
                
                // Check if it's a dropdown toggle (user button)
                const isDropdownToggle = button.classList.contains('dropdown-toggle') || 
                                        button.querySelector('.dropdown-toggle') ||
                                        button.tagName === 'A' && button.textContent.includes('User');

                if (isDropdownToggle) {
                    // Style user dropdown button
                    button.style.cssText = `
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        background: rgba(255, 255, 255, 0.15) !important;
                        border: 1px solid rgba(255, 255, 255, 0.3) !important;
                        color: white !important;
                        font-size: 0.8rem !important;
                        padding: 4px 8px !important;
                        margin: 0 !important;
                        border-radius: 6px !important;
                        cursor: pointer !important;
                        transition: all 0.2s ease !important;
                        min-width: 60px !important;
                        max-width: 85px !important;
                        height: 36px !important;
                        flex-shrink: 0 !important;
                        white-space: nowrap !important;
                        overflow: hidden !important;
                        text-overflow: ellipsis !important;
                        gap: 4px !important;
                        box-sizing: border-box !important;
                        text-decoration: none !important;
                        pointer-events: auto !important;
                        z-index: 1050 !important;
                        position: relative !important;
                    `;
                } else {
                    // Style regular buttons (theme, notifications, settings)
                    button.style.cssText = `
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        background: rgba(255, 255, 255, 0.15) !important;
                        border: 1px solid rgba(255, 255, 255, 0.3) !important;
                        color: white !important;
                        font-size: 0.9rem !important;
                        padding: 6px !important;
                        margin: 0 !important;
                        border-radius: 6px !important;
                        cursor: pointer !important;
                        transition: all 0.2s ease !important;
                        width: 36px !important;
                        height: 36px !important;
                        flex-shrink: 0 !important;
                        min-width: 36px !important;
                        min-height: 36px !important;
                        box-sizing: border-box !important;
                        text-decoration: none !important;
                        pointer-events: auto !important;
                    `;
                }

                // Add hover effects
                button.addEventListener('mouseenter', function() {
                    this.style.background = 'rgba(255, 255, 255, 0.25) !important';
                    this.style.borderColor = 'rgba(255, 255, 255, 0.5) !important';
                    this.style.transform = 'translateY(-1px)';
                });

                button.addEventListener('mouseleave', function() {
                    this.style.background = 'rgba(255, 255, 255, 0.15) !important';
                    this.style.borderColor = 'rgba(255, 255, 255, 0.3) !important';
                    this.style.transform = 'translateY(0)';
                });

                // Style icons inside buttons
                const icons = button.querySelectorAll('i, .fa, [class*="icon"]');
                icons.forEach(icon => {
                    icon.style.cssText = `
                        color: white !important;
                        font-size: 1rem !important;
                        width: 16px !important;
                        height: 16px !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                    `;
                });

                // Style profile pictures and avatars
                const profileElements = button.querySelectorAll('.profile-picture, .avatar-initials');
                profileElements.forEach(element => {
                    element.style.cssText = `
                        width: 24px !important;
                        height: 24px !important;
                        border-radius: 50% !important;
                        flex-shrink: 0 !important;
                        margin: 0 !important;
                    `;
                    
                    if (element.classList.contains('avatar-initials')) {
                        element.style.background = 'rgba(255, 255, 255, 0.3) !important';
                        element.style.color = 'white !important';
                        element.style.fontSize = '0.8rem !important';
                        element.style.fontWeight = 'bold !important';
                        element.style.display = 'flex !important';
                        element.style.alignItems = 'center !important';
                        element.style.justifyContent = 'center !important';
                    }
                });

                // Style notification badges
                const badges = button.querySelectorAll('.badge');
                badges.forEach(badge => {
                    badge.style.cssText = `
                        position: absolute !important;
                        top: -4px !important;
                        right: -4px !important;
                        background-color: #dc3545 !important;
                        color: white !important;
                        font-size: 0.7rem !important;
                        padding: 2px 6px !important;
                        border-radius: 12px !important;
                        min-width: 18px !important;
                        height: 18px !important;
                        line-height: 14px !important;
                        text-align: center !important;
                        z-index: 2 !important;
                        border: 2px solid rgba(255, 255, 255, 0.2) !important;
                    `;
                });
            }
        });
    }

    // Apply styling when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceNavbarButtonStyling);
    } else {
        forceNavbarButtonStyling();
    }

    // Re-apply on window resize
    window.addEventListener('resize', function() {
        setTimeout(forceNavbarButtonStyling, 100);
    });

    // Re-apply periodically to handle dynamic content
    setInterval(forceNavbarButtonStyling, 2000);

    // Watch for DOM changes and re-apply styling
    if (window.MutationObserver) {
        const observer = new MutationObserver(function(mutations) {
            let shouldUpdate = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' || mutation.type === 'attributes') {
                    const target = mutation.target;
                    if (target.closest && target.closest('.navbar')) {
                        shouldUpdate = true;
                    }
                }
            });
            
            if (shouldUpdate) {
                setTimeout(forceNavbarButtonStyling, 100);
            }
        });

        // Only observe if document.body exists
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style']
            });
        }
    }
})();