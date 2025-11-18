/**
 * Mobile Navigation Diagnostic Script
 * Helps identify the root cause of sidebar opening when clicking navigation links
 */

(function() {
    'use strict';

    console.log('🔍 Mobile Navigation Diagnostic Script Loaded');

    function isMobile() {
        return window.innerWidth <= 991;
    }

    function logEvent(eventType, element, details) {
        if (isMobile()) {
            console.log(`📱 [${eventType}] Element: ${element.tagName}${element.className ? '.' + element.className.split(' ').join('.') : ''}`, details);
        }
    }

    function setupDiagnostics() {
        if (!isMobile()) {
            console.log('🖥️ Desktop detected - diagnostics disabled');
            return;
        }

        console.log('📱 Mobile detected - setting up diagnostics');

        // Monitor all click events on the document
        document.addEventListener('click', function(e) {
            logEvent('CLICK', e.target, {
                href: e.target.href,
                classList: Array.from(e.target.classList || []),
                parentElement: e.target.parentElement?.tagName,
                eventPhase: e.eventPhase,
                bubbles: e.bubbles,
                cancelable: e.cancelable,
                defaultPrevented: e.defaultPrevented
            });

            // Check if this is a sidebar link
            if (e.target.classList.contains('sidebar-link')) {
                console.log('🔗 SIDEBAR LINK CLICKED:', e.target.href);
                
                // Check sidebar state before and after
                const sidebar = document.querySelector('.sidebar');
                if (sidebar) {
                    console.log('📂 Sidebar state before click:', {
                        classes: Array.from(sidebar.classList),
                        display: getComputedStyle(sidebar).display,
                        visibility: getComputedStyle(sidebar).visibility,
                        transform: getComputedStyle(sidebar).transform
                    });

                    // Check again after a short delay
                    setTimeout(() => {
                        console.log('📂 Sidebar state after click:', {
                            classes: Array.from(sidebar.classList),
                            display: getComputedStyle(sidebar).display,
                            visibility: getComputedStyle(sidebar).visibility,
                            transform: getComputedStyle(sidebar).transform
                        });
                    }, 100);
                }
            }

            // Check if this is the sidebar toggle
            if (e.target.id === 'sidebar-toggle-navbar' || e.target.closest('#sidebar-toggle-navbar')) {
                console.log('🔄 SIDEBAR TOGGLE CLICKED');
            }
        }, true); // Use capture phase

        // Monitor touch events
        document.addEventListener('touchstart', function(e) {
            if (e.target.classList.contains('sidebar-link')) {
                logEvent('TOUCHSTART', e.target, {
                    href: e.target.href,
                    touches: e.touches.length,
                    targetTouches: e.targetTouches.length
                });
            }
        }, { passive: true });

        document.addEventListener('touchend', function(e) {
            if (e.target.classList.contains('sidebar-link')) {
                logEvent('TOUCHEND', e.target, {
                    href: e.target.href,
                    changedTouches: e.changedTouches.length
                });
            }
        }, { passive: true });

        // Monitor sidebar class changes
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        console.log('📂 SIDEBAR CLASS CHANGED:', {
                            oldValue: mutation.oldValue,
                            newValue: sidebar.className,
                            timestamp: Date.now()
                        });
                    }
                });
            });

            observer.observe(sidebar, {
                attributes: true,
                attributeOldValue: true,
                attributeFilter: ['class']
            });
        }

        // Monitor body class changes (for sidebar-open)
        const bodyObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (document.body.classList.contains('sidebar-open')) {
                        console.log('🚪 BODY SIDEBAR-OPEN CLASS ADDED');
                    } else {
                        console.log('🚪 BODY SIDEBAR-OPEN CLASS REMOVED');
                    }
                }
            });
        });

        bodyObserver.observe(document.body, {
            attributes: true,
            attributeFilter: ['class']
        });

        // Check for any existing event listeners on sidebar links
        const sidebarLinks = document.querySelectorAll('.sidebar .sidebar-link');
        console.log(`🔗 Found ${sidebarLinks.length} sidebar links`);

        sidebarLinks.forEach((link, index) => {
            console.log(`🔗 Link ${index + 1}:`, {
                href: link.href,
                text: link.textContent.trim(),
                classList: Array.from(link.classList),
                hasClickListener: link.onclick !== null,
                eventListeners: getEventListeners ? getEventListeners(link) : 'Not available'
            });
        });

        // Check for Bootstrap or other framework interference
        if (typeof bootstrap !== 'undefined') {
            console.log('🅱️ Bootstrap detected:', bootstrap.Collapse ? 'with Collapse' : 'without Collapse');
        }

        if (typeof jQuery !== 'undefined') {
            console.log('💲 jQuery detected:', jQuery.fn.jquery);
        }

        console.log('✅ Diagnostics setup complete');
    }

    // Setup diagnostics when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupDiagnostics);
    } else {
        setupDiagnostics();
    }

    // Re-setup on window resize
    window.addEventListener('resize', function() {
        setTimeout(setupDiagnostics, 100);
    });

})();