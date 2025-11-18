/**
 * PROFILE MOBILE FULL-WIDTH FORCE
 * JavaScript to force full-width layout on mobile devices
 * Removes any conflicting styles and ensures edge-to-edge content
 */

(function() {
    'use strict';
    
    function forceFullWidthProfile() {
        // Only apply on mobile devices
        if (window.innerWidth > 768) return;
        
        console.log('🔧 PROFILE FULL-WIDTH: Forcing full-width layout');
        
        // Target the profile content container
        const profileContent = document.querySelector('.profile-content');
        if (profileContent) {
            // Force full-width container
            profileContent.style.setProperty('padding-left', '0', 'important');
            profileContent.style.setProperty('padding-right', '0', 'important');
            profileContent.style.setProperty('margin-left', '0', 'important');
            profileContent.style.setProperty('margin-right', '0', 'important');
            profileContent.style.setProperty('width', '100%', 'important');
            profileContent.style.setProperty('max-width', '100%', 'important');
        }
        
        // Force full-width alerts
        const alerts = document.querySelectorAll('.profile-content .alert');
        alerts.forEach(alert => {
            alert.style.setProperty('margin-left', '0', 'important');
            alert.style.setProperty('margin-right', '0', 'important');
            alert.style.setProperty('width', '100%', 'important');
            alert.style.setProperty('max-width', '100%', 'important');
            alert.style.setProperty('border-radius', '0', 'important');
            alert.style.setProperty('border-left', 'none', 'important');
            alert.style.setProperty('border-right', 'none', 'important');
        });
        
        // Force full-width rows
        const rows = document.querySelectorAll('.profile-content .row');
        rows.forEach(row => {
            row.style.setProperty('margin-left', '0', 'important');
            row.style.setProperty('margin-right', '0', 'important');
            row.style.setProperty('padding-left', '0', 'important');
            row.style.setProperty('padding-right', '0', 'important');
            row.style.setProperty('width', '100%', 'important');
            row.style.setProperty('max-width', '100%', 'important');
        });
        
        // Force full-width columns
        const columns = document.querySelectorAll('.profile-content [class*="col-"]');
        columns.forEach(col => {
            col.style.setProperty('padding-left', '0', 'important');
            col.style.setProperty('padding-right', '0', 'important');
            col.style.setProperty('margin-left', '0', 'important');
            col.style.setProperty('margin-right', '0', 'important');
            col.style.setProperty('width', '100%', 'important');
            col.style.setProperty('max-width', '100%', 'important');
            col.style.setProperty('flex', 'none', 'important');
        });
        
        // Force full-width cards
        const cards = document.querySelectorAll('.profile-content .card');
        cards.forEach(card => {
            card.style.setProperty('margin-left', '0', 'important');
            card.style.setProperty('margin-right', '0', 'important');
            card.style.setProperty('margin-bottom', '0', 'important');
            card.style.setProperty('width', '100%', 'important');
            card.style.setProperty('max-width', '100%', 'important');
            card.style.setProperty('border-radius', '0', 'important');
            card.style.setProperty('border-left', 'none', 'important');
            card.style.setProperty('border-right', 'none', 'important');
            card.style.setProperty('box-shadow', 'none', 'important');
        });
        
        // Force full-width card headers and bodies
        const cardHeaders = document.querySelectorAll('.profile-content .card-header');
        cardHeaders.forEach(header => {
            header.style.setProperty('padding', '1rem 1.5rem', 'important');
            header.style.setProperty('margin', '0', 'important');
            header.style.setProperty('border-radius', '0', 'important');
        });
        
        const cardBodies = document.querySelectorAll('.profile-content .card-body');
        cardBodies.forEach(body => {
            body.style.setProperty('padding', '1.5rem', 'important');
            body.style.setProperty('margin', '0', 'important');
            body.style.setProperty('border-radius', '0', 'important');
        });
        
        // Remove any Bootstrap container padding that might interfere
        const containers = document.querySelectorAll('.container, .container-fluid');
        containers.forEach(container => {
            if (container.classList.contains('profile-content')) {
                container.style.setProperty('padding-left', '0', 'important');
                container.style.setProperty('padding-right', '0', 'important');
            }
        });
        
        console.log('✅ PROFILE FULL-WIDTH: Applied to', cards.length, 'cards and', alerts.length, 'alerts');
    }
    
    // Initialize the force function
    function initializeProfileFullWidth() {
        console.log('🔧 PROFILE FULL-WIDTH: Initializing...');
        
        // Apply immediately
        forceFullWidthProfile();
        
        // Apply on resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                if (window.innerWidth <= 768) {
                    forceFullWidthProfile();
                }
            }, 100);
        });
        
        // Apply when new content is added
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                let shouldReapply = false;
                
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        for (let node of mutation.addedNodes) {
                            if (node.nodeType === 1) { // Element node
                                if (node.matches && (node.matches('.card') || node.matches('.alert') || node.matches('[class*="col-"]'))) {
                                    shouldReapply = true;
                                    break;
                                }
                                if (node.querySelector && (node.querySelector('.card') || node.querySelector('.alert') || node.querySelector('[class*="col-"]'))) {
                                    shouldReapply = true;
                                    break;
                                }
                            }
                        }
                    }
                });
                
                if (shouldReapply && window.innerWidth <= 768) {
                    setTimeout(forceFullWidthProfile, 50);
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
        
        console.log('✅ PROFILE FULL-WIDTH: Initialization complete');
    }
    
    // Run based on document state
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeProfileFullWidth);
    } else {
        initializeProfileFullWidth();
    }
    
    // Also run on next tick to ensure it runs after other scripts
    setTimeout(initializeProfileFullWidth, 0);
    
    // Expose for debugging
    window.forceFullWidthProfile = forceFullWidthProfile;
    
})();