/**
 * NAVBAR DROPDOWN FIX JAVASCRIPT
 * Handles dynamic positioning and prevents scroll button issues
 */

(function() {
    'use strict';
    
    // Function to fix dropdown positioning
    function fixDropdownPositioning() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;
        
        const notificationDropdown = document.getElementById('notificationsDropdown');
        const userDropdown = document.getElementById('userDropdown');
        
        // Fix notification dropdown
        if (notificationDropdown) {
            const notificationMenu = notificationDropdown.nextElementSibling;
            if (notificationMenu && notificationMenu.classList.contains('dropdown-menu')) {
                // Calculate position
                const rect = notificationDropdown.getBoundingClientRect();
                const navbarHeight = navbar.offsetHeight;
                
                // Set fixed positioning
                notificationMenu.style.position = 'fixed';
                notificationMenu.style.top = navbarHeight + 'px';
                notificationMenu.style.zIndex = '1040';
                notificationMenu.style.overflow = 'visible';
                
                // Responsive positioning
                if (window.innerWidth <= 768) {
                    notificationMenu.style.left = '10px';
                    notificationMenu.style.right = '10px';
                    notificationMenu.style.width = 'auto';
                } else {
                    notificationMenu.style.right = '60px';
                    notificationMenu.style.left = 'auto';
                    notificationMenu.style.width = '300px';
                }
                
                // Ensure max width doesn't exceed viewport
                notificationMenu.style.maxWidth = 'calc(100vw - 20px)';
            }
        }
        
        // Fix user dropdown
        if (userDropdown) {
            const userMenu = userDropdown.nextElementSibling;
            if (userMenu && userMenu.classList.contains('dropdown-menu')) {
                // Calculate position
                const rect = userDropdown.getBoundingClientRect();
                const navbarHeight = navbar.offsetHeight;
                
                // Set fixed positioning
                userMenu.style.position = 'fixed';
                userMenu.style.top = navbarHeight + 'px';
                userMenu.style.zIndex = '1040';
                userMenu.style.overflow = 'visible';
                
                // Responsive positioning
                if (window.innerWidth <= 768) {
                    userMenu.style.right = '10px';
                    userMenu.style.left = 'auto';
                    userMenu.style.minWidth = '160px';
                    userMenu.style.maxWidth = 'calc(100vw - 20px)';
                } else {
                    userMenu.style.right = '10px';
                    userMenu.style.left = 'auto';
                    userMenu.style.minWidth = '180px';
                    userMenu.style.maxWidth = '250px';
                }
            }
        }
    }
    
    // Function to remove scroll buttons from dropdowns
    function removeScrollButtons() {
        const dropdownMenus = document.querySelectorAll('.navbar .dropdown-menu');
        
        dropdownMenus.forEach(menu => {
            // Remove any scroll-related attributes
            menu.style.overflowY = 'visible';
            menu.style.overflowX = 'visible';
            menu.style.maxHeight = 'none';
            
            // Remove webkit scrollbars
            menu.style.scrollbarWidth = 'none';
            menu.style.msOverflowStyle = 'none';
            
            // For notification dropdown content, allow internal scrolling
            if (menu.classList.contains('notification-dropdown')) {
                const notificationsList = menu.querySelector('#notificationsList');
                if (notificationsList) {
                    notificationsList.style.maxHeight = '300px';
                    notificationsList.style.overflowY = 'auto';
                    notificationsList.style.overflowX = 'hidden';
                    notificationsList.style.scrollbarWidth = 'thin';
                }
            }
        });
    }
    
    // Function to handle dropdown show events
    function handleDropdownShow(event) {
        const dropdown = event.target;
        const menu = dropdown.nextElementSibling;
        
        if (menu && menu.classList.contains('dropdown-menu')) {
            // Force positioning update
            setTimeout(() => {
                fixDropdownPositioning();
                removeScrollButtons();
            }, 10);
        }
    }
    
    // Function to handle dropdown hide events
    function handleDropdownHide(event) {
        // Clean up any positioning issues
        removeScrollButtons();
    }
    
    // Function to handle window resize
    function handleResize() {
        // Debounce resize events
        clearTimeout(window.dropdownResizeTimeout);
        window.dropdownResizeTimeout = setTimeout(() => {
            fixDropdownPositioning();
            removeScrollButtons();
        }, 100);
    }
    
    // Initialize when DOM is ready
    function init() {
        // Initial setup
        fixDropdownPositioning();
        removeScrollButtons();
        
        // Add event listeners for dropdown events
        document.addEventListener('show.bs.dropdown', handleDropdownShow);
        document.addEventListener('hide.bs.dropdown', handleDropdownHide);
        
        // Add resize listener
        window.addEventListener('resize', handleResize);
        
        // Add click listeners to dropdown toggles
        const notificationToggle = document.getElementById('notificationsDropdown');
        const userToggle = document.getElementById('userDropdown');
        
        if (notificationToggle) {
            notificationToggle.addEventListener('click', () => {
                setTimeout(() => {
                    fixDropdownPositioning();
                    removeScrollButtons();
                }, 10);
            });
        }
        
        if (userToggle) {
            userToggle.addEventListener('click', () => {
                setTimeout(() => {
                    fixDropdownPositioning();
                    removeScrollButtons();
                }, 10);
            });
        }
        
        // Periodic cleanup to ensure dropdowns stay fixed
        setInterval(() => {
            removeScrollButtons();
        }, 1000);
    }
    
    // Run initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Also run on window load as backup
    window.addEventListener('load', () => {
        setTimeout(init, 100);
    });
    
})();