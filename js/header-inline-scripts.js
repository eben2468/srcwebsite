// Header inline scripts moved to external file to fix CSP violation

// Immediate modal backdrop fix for all pages
(function() {
    // Remove any existing modal backdrops - ONLY when needed, not continuously
    function removeBackdrops() {
        // Only remove backdrops if no modals are currently shown
        var activeModals = document.querySelectorAll('.modal.show');
        if (activeModals.length === 0) {
            var backdrops = document.querySelectorAll('.modal-backdrop');
            for (var i = 0; i < backdrops.length; i++) {
                if (backdrops[i] && backdrops[i].parentNode) {
                    backdrops[i].parentNode.removeChild(backdrops[i]);
                }
            }
            
            // Reset body styles only if no modals are active and body exists
            if (document.body) {
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        }
    }
    
    // Apply fix only once on load - NO continuous interval
    removeBackdrops();
    
    // Clean up any leftover backdrops on page load only
    document.addEventListener('DOMContentLoaded', function() {
        // Clean up once on page load
        removeBackdrops();
        
        // Listen for modal events to clean up properly
        document.addEventListener('hidden.bs.modal', function() {
            // Clean up after modal is fully hidden
            setTimeout(removeBackdrops, 100);
        });
    });
})();

// Function to detect system dark mode preference
// DISABLED: Theme is now managed by localStorage in header.php
/*
function detectColorScheme() {
    const theme = document.documentElement.getAttribute('data-theme-mode') || 'system';
    if (theme === 'system') {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-bs-theme', 'light');
        }
        
        // Listen for changes in system theme
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            document.documentElement.setAttribute('data-bs-theme', e.matches ? 'dark' : 'light');
        });
    }
}
*/

// Theme toggle functionality is now handled in header.php - DO NOT USE THIS FUNCTION
/*
function initializeThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;

    themeToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Get current theme
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-bs-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        // Update theme attribute
        html.setAttribute('data-bs-theme', newTheme);

        // Update icon
        const icon = themeToggle.querySelector('i');
        if (icon) {
            icon.classList.remove(currentTheme === 'dark' ? 'fa-sun' : 'fa-moon');
            icon.classList.add(newTheme === 'dark' ? 'fa-sun' : 'fa-moon');
        }

        // Store preference in localStorage
        localStorage.setItem('theme-preference', newTheme);
        
        // Emit custom event for other scripts to listen to
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
    });

    // Apply saved theme preference on load
    const savedTheme = localStorage.getItem('theme-preference');
    if (savedTheme) {
        html.setAttribute('data-bs-theme', savedTheme);
        const icon = themeToggle.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-sun', 'fa-moon');
            icon.classList.add(savedTheme === 'dark' ? 'fa-sun' : 'fa-moon');
        }
    }
}
*/

// Function to navigate to dashboard from any location
function goToDashboard() {
    const path = window.location.pathname;
    let dashboardUrl = '';
    
    try {
        // First approach: Check path segments
        if (path.includes('/admin/')) {
            dashboardUrl = '../pages_php/dashboard.php';
        } else if (path.includes('/pages_php/')) {
            dashboardUrl = 'dashboard.php';
        } else {
            dashboardUrl = 'pages_php/dashboard.php';
        }
        
        // Second approach: Use absolute path as fallback
        if (typeof baseUrl !== 'undefined') {
            // If baseUrl is defined elsewhere in the code
            window.location.href = baseUrl + '/pages_php/dashboard.php';
            return;
        }
        
        // Navigate to the determined URL
        window.location.href = dashboardUrl;
    } catch (e) {
        // Final fallback: Try to navigate to dashboard
        console.error('Error navigating to dashboard:', e);
        window.location.href = 'dashboard.php';
    }
}

// Run theme detection on page load
// DISABLED: Theme is now managed by localStorage in header.php
// document.addEventListener('DOMContentLoaded', detectColorScheme);

// Initialize theme toggle on page load - DISABLED: Theme toggle now handled in header.php
// document.addEventListener('DOMContentLoaded', initializeThemeToggle);

// Immediate sidebar fix - runs as soon as the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Force sidebar visibility and positioning
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        // Ensure sidebar is properly positioned
        sidebar.style.position = 'fixed';
        sidebar.style.top = '0';
        sidebar.style.left = '0';
        sidebar.style.height = '100vh';
        sidebar.style.zIndex = '1000';
        
        // Force sidebar links to be visible and clickable
        const sidebarLinks = sidebar.querySelectorAll('.sidebar-link');
        sidebarLinks.forEach(link => {
            link.style.display = 'flex';
            link.style.alignItems = 'center';
            link.style.padding = '0.75rem 1.25rem';
            link.style.color = 'white';
            link.style.textDecoration = 'none';
            link.style.transition = 'background-color 0.2s ease';
        });
        
        console.log('Sidebar immediate fix applied');
    }
});

// Auto-dismiss notifications after 8 seconds (excluding persistent alerts)
document.addEventListener('DOMContentLoaded', function() {
    // Wait a moment for all alerts to be rendered
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent):not([data-no-dismiss])');
        
        alerts.forEach(function(alert) {
            // Only auto-dismiss success and info alerts, not errors or warnings
            if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
                setTimeout(function() {
                    // Check if alert still exists and is visible
                    if (alert && alert.parentNode && alert.offsetParent !== null) {
                        // Fade out the alert
                        alert.style.transition = 'opacity 0.5s ease';
                        alert.style.opacity = '0';
                        
                        // Remove after fade
                        setTimeout(function() {
                            if (alert && alert.parentNode) {
                                alert.remove();
                            }
                        }, 500);
                    }
                }, 8000); // 8 seconds
            }
        });
    }, 1000); // Wait 1 second before starting auto-dismiss
});