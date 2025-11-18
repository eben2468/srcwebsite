/**
 * Mobile Navigation Nuclear Fix
 * The ultimate solution to prevent sidebar from opening when clicking navigation links
 * This script completely overrides any other JavaScript that might interfere
 */

(function() {
    'use strict';

    console.log('💥 Mobile Navigation Nuclear Fix Loading...');

    function isMobile() {
        return window.innerWidth <= 991;
    }

    // Global flag to track navigation state
    window.isNavigating = false;

    function applyNuclearFix() {
        if (!isMobile()) {
            console.log('🖥️ Desktop detected - nuclear fix disabled');
            return;
        }

        console.log('💥 Applying nuclear fix for mobile navigation...');

        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) {
            console.log('❌ Sidebar not found');
            return;
        }

        // NUCLEAR OPTION 1: Override classList.add method
        const originalAdd = sidebar.classList.add;
        sidebar.classList.add = function(className) {
            if (className === 'show' && (isMobile() || window.isNavigating)) {
                console.log('💥 NUCLEAR: Blocked attempt to add "show" class');
                return;
            }
            return originalAdd.call(this, className);
        };

        // NUCLEAR OPTION 2: Override classList.remove method to prevent removing 'hide'
        const originalRemove = sidebar.classList.remove;
        sidebar.classList.remove = function(className) {
            if (className === 'hide' && window.isNavigating && isMobile()) {
                console.log('💥 NUCLEAR: Blocked attempt to remove "hide" class during navigation');
                return;
            }
            return originalRemove.call(this, className);
        };

        // NUCLEAR OPTION 3: Override style.transform property
        const originalTransform = sidebar.style.transform;
        Object.defineProperty(sidebar.style, 'transform', {
            get: function() {
                return originalTransform;
            },
            set: function(value) {
                if (window.isNavigating && isMobile() && value !== 'translateX(-100%)') {
                    console.log('💥 NUCLEAR: Blocked transform change during navigation');
                    return;
                }
                originalTransform = value;
                sidebar.style.setProperty('transform', value);
            }
        });

        // NUCLEAR OPTION 4: Intercept all navigation link clicks
        const sidebarLinks = document.querySelectorAll('.sidebar .sidebar-link');
        console.log(`💥 Found ${sidebarLinks.length} navigation links to protect`);

        sidebarLinks.forEach((link, index) => {
            const href = link.getAttribute('href');
            
            if (href && href !== '#') {
                // Remove all existing event listeners by cloning the element
                const newLink = link.cloneNode(true);
                link.parentNode.replaceChild(newLink, link);

                // Add our nuclear click handler
                newLink.addEventListener('click', function(e) {
                    console.log(`💥 NUCLEAR: Navigation link clicked - ${href}`);
                    
                    // Set global navigation flag
                    window.isNavigating = true;
                    
                    // Add body class to prevent any CSS interference
                    document.body.classList.add('navigating');
                    
                    // Force sidebar to be hidden with nuclear methods
                    sidebar.classList.add('navigating');
                    sidebar.classList.remove('show');
                    sidebar.classList.add('hide');
                    sidebar.style.display = 'none';
                    sidebar.style.visibility = 'hidden';
                    sidebar.style.opacity = '0';
                    sidebar.style.transform = 'translateX(-100%)';
                    sidebar.style.pointerEvents = 'none';
                    
                    // Clean up body classes
                    document.body.classList.remove('sidebar-open');
                    document.body.style.overflow = '';
                    
                    console.log(`💥 NUCLEAR: Navigating to ${href}`);
                    
                    // Allow normal navigation (don't prevent default)
                }, true); // Use capture phase

                // Add touch handlers
                newLink.addEventListener('touchstart', function(e) {
                    console.log(`💥 NUCLEAR: Touch started on navigation link`);
                    window.isNavigating = true;
                    document.body.classList.add('navigating');
                    sidebar.classList.add('navigating');
                    sidebar.style.display = 'none';
                }, { passive: true });

                console.log(`💥 Protected navigation link ${index + 1}: ${href}`);
            }
        });

        // NUCLEAR OPTION 5: Global event interceptor
        document.addEventListener('click', function(e) {
            if (!isMobile()) return;
            
            // If clicking on a sidebar link, set navigation flag
            if (e.target.classList.contains('sidebar-link') || e.target.closest('.sidebar-link')) {
                console.log('💥 NUCLEAR: Global click interceptor - navigation detected');
                window.isNavigating = true;
                document.body.classList.add('navigating');
                sidebar.classList.add('navigating');
                sidebar.style.display = 'none';
            }
            
            // If clicking on sidebar toggle, allow it only if not navigating
            if (e.target.id === 'sidebar-toggle-navbar' || e.target.closest('#sidebar-toggle-navbar')) {
                if (window.isNavigating) {
                    console.log('💥 NUCLEAR: Blocked sidebar toggle during navigation');
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        }, true);

        // NUCLEAR OPTION 6: Mutation observer with nuclear response
        const nuclearObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target === sidebar && isMobile()) {
                        if (target.classList.contains('show') && window.isNavigating) {
                            console.log('💥 NUCLEAR: Mutation observer detected "show" class - removing it');
                            target.classList.remove('show');
                            target.classList.add('hide', 'navigating');
                            target.style.display = 'none';
                        }
                    }
                }
            });
        });

        nuclearObserver.observe(sidebar, {
            attributes: true,
            attributeFilter: ['class', 'style']
        });

        // NUCLEAR OPTION 7: Override any setTimeout/setInterval that might show sidebar
        const originalSetTimeout = window.setTimeout;
        window.setTimeout = function(callback, delay) {
            const wrappedCallback = function() {
                if (window.isNavigating && isMobile()) {
                    console.log('💥 NUCLEAR: Blocked setTimeout during navigation');
                    return;
                }
                return callback.apply(this, arguments);
            };
            return originalSetTimeout.call(this, wrappedCallback, delay);
        };

        // Reset navigation flag after a delay
        function resetNavigationFlag() {
            setTimeout(() => {
                if (window.isNavigating) {
                    console.log('💥 NUCLEAR: Resetting navigation flag');
                    window.isNavigating = false;
                    document.body.classList.remove('navigating');
                    // Don't reset sidebar classes - let it stay hidden
                }
            }, 1000);
        }

        // Reset flag on page visibility change
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                resetNavigationFlag();
            }
        });

        // Reset flag on beforeunload
        window.addEventListener('beforeunload', function() {
            window.isNavigating = false;
        });

        console.log('💥 Nuclear fix applied successfully - sidebar interference eliminated');
    }

    // Apply nuclear fix when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyNuclearFix);
    } else {
        applyNuclearFix();
    }

    // Re-apply on window resize
    window.addEventListener('resize', function() {
        if (isMobile()) {
            setTimeout(applyNuclearFix, 100);
        }
    });

    console.log('💥 Mobile Navigation Nuclear Fix Loaded Successfully');

})();