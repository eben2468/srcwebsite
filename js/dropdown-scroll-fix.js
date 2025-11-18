/**
 * Dropdown Scroll Fix JavaScript
 * Removes scroll buttons that appear when dropdowns are opened
 */

(function() {
    'use strict';
    
    console.log('🔧 Dropdown scroll fix initialized');
    
    // Function to fix dropdown scroll issues
    function fixDropdownScroll() {
        // Find all dropdown menus
        const dropdownMenus = document.querySelectorAll('.dropdown-menu');
        
        dropdownMenus.forEach(menu => {
            // Remove scroll properties
            menu.style.overflow = 'visible';
            menu.style.maxHeight = 'none';
            menu.style.scrollbarWidth = 'none';
            menu.style.msOverflowStyle = 'none';
            
            // Remove scroll-related classes
            menu.classList.remove('scrollable', 'scroll-enabled', 'has-scroll');
            
            // Remove scroll-related attributes
            menu.removeAttribute('data-scroll');
            menu.removeAttribute('data-scrollable');
            menu.removeAttribute('data-max-height');
        });
        
        // Remove any scroll buttons or indicators
        const scrollElements = document.querySelectorAll(
            '.dropdown-menu .scroll-up, ' +
            '.dropdown-menu .scroll-down, ' +
            '.dropdown-menu .dropdown-scroll-up, ' +
            '.dropdown-menu .dropdown-scroll-down, ' +
            '.dropdown-menu .scroll-indicator, ' +
            '.dropdown-menu .scroll-arrow, ' +
            '.dropdown-scroll-wrapper, ' +
            '.dropdown-scroll-container'
        );
        
        scrollElements.forEach(element => {
            element.style.display = 'none';
            element.style.visibility = 'hidden';
            element.style.opacity = '0';
        });
        
        console.log(`🔧 Fixed ${dropdownMenus.length} dropdown menus and removed ${scrollElements.length} scroll elements`);
    }
    
    // Function to handle dropdown events
    function handleDropdownEvents() {
        // Listen for dropdown show events
        document.addEventListener('show.bs.dropdown', function(e) {
            console.log('📂 Dropdown showing:', e.target);
            
            setTimeout(() => {
                const dropdownMenu = e.target.nextElementSibling;
                if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                    dropdownMenu.style.overflow = 'visible';
                    dropdownMenu.style.maxHeight = 'none';
                    
                    // Remove any scroll buttons that might have been added
                    const scrollButtons = dropdownMenu.querySelectorAll('[class*="scroll"]');
                    scrollButtons.forEach(btn => {
                        btn.style.display = 'none';
                    });
                }
                
                // Also fix any scroll buttons that might appear elsewhere
                fixDropdownScroll();
            }, 50);
        });
        
        // Listen for dropdown shown events
        document.addEventListener('shown.bs.dropdown', function(e) {
            console.log('📂 Dropdown shown:', e.target);
            
            setTimeout(() => {
                fixDropdownScroll();
                
                // Remove any scroll buttons that might have been added after show
                const allScrollButtons = document.querySelectorAll(
                    '.scroll-up, .scroll-down, .scroll-indicator, .scroll-arrow, ' +
                    '[class*="scroll-btn"], [id*="scroll-btn"]'
                );
                
                allScrollButtons.forEach(btn => {
                    const parent = btn.closest('.dropdown-menu');
                    if (parent) {
                        btn.style.display = 'none';
                        btn.style.visibility = 'hidden';
                        btn.style.opacity = '0';
                    }
                });
            }, 100);
        });
        
        // Listen for dropdown hide events
        document.addEventListener('hide.bs.dropdown', function(e) {
            console.log('📂 Dropdown hiding:', e.target);
            
            // Clean up any scroll-related elements
            setTimeout(fixDropdownScroll, 50);
        });
        
        // Listen for clicks on dropdown toggles
        document.addEventListener('click', function(e) {
            if (e.target.matches('.dropdown-toggle') || e.target.closest('.dropdown-toggle')) {
                console.log('🖱️ Dropdown toggle clicked');
                
                setTimeout(() => {
                    fixDropdownScroll();
                    
                    // Remove any scroll buttons that appear after click
                    const scrollButtons = document.querySelectorAll(
                        '.scroll-up, .scroll-down, .dropdown-scroll-up, .dropdown-scroll-down'
                    );
                    
                    scrollButtons.forEach(btn => {
                        btn.remove();
                    });
                }, 100);
            }
        });
    }
    
    // Function to override Bootstrap dropdown scroll behavior
    function overrideBootstrapScroll() {
        // Override Bootstrap's dropdown positioning if it exists
        if (window.bootstrap && window.bootstrap.Dropdown) {
            const originalShow = window.bootstrap.Dropdown.prototype.show;
            
            window.bootstrap.Dropdown.prototype.show = function() {
                const result = originalShow.call(this);
                
                // Fix scroll after showing
                setTimeout(() => {
                    const menu = this._menu;
                    if (menu) {
                        menu.style.overflow = 'visible';
                        menu.style.maxHeight = 'none';
                        
                        // Remove any scroll elements
                        const scrollElements = menu.querySelectorAll('[class*="scroll"]');
                        scrollElements.forEach(el => el.style.display = 'none');
                    }
                }, 50);
                
                return result;
            };
        }
        
        console.log('🔧 Bootstrap dropdown scroll behavior overridden');
    }
    
    // Function to prevent scroll button creation
    function preventScrollButtonCreation() {
        // Override appendChild to prevent scroll button addition
        const originalAppendChild = Element.prototype.appendChild;
        
        Element.prototype.appendChild = function(child) {
            if (child && child.nodeType === 1) {
                const className = child.className || '';
                const id = child.id || '';
                
                // Block scroll buttons in dropdowns
                if (this.classList && this.classList.contains('dropdown-menu')) {
                    if (className.includes('scroll') || id.includes('scroll')) {
                        console.log('🚫 Blocked scroll button creation in dropdown:', child);
                        return child; // Don't actually append
                    }
                }
            }
            
            return originalAppendChild.call(this, child);
        };
        
        console.log('🛡️ Scroll button creation prevention activated');
    }
    
    // Function to monitor for dynamically added scroll elements
    function setupScrollMonitoring() {
        if (!window.MutationObserver) return;
        
        const observer = new MutationObserver(function(mutations) {
            let shouldFix = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            const className = node.className || '';
                            const id = node.id || '';
                            
                            // Check if it's a scroll-related element
                            if (className.includes('scroll') || id.includes('scroll')) {
                                const dropdown = node.closest('.dropdown-menu');
                                if (dropdown) {
                                    shouldFix = true;
                                    console.log('👀 Detected scroll element in dropdown:', node);
                                }
                            }
                        }
                    });
                }
                
                // Also check for attribute changes that might enable scrolling
                if (mutation.type === 'attributes') {
                    const target = mutation.target;
                    if (target.classList && target.classList.contains('dropdown-menu')) {
                        if (mutation.attributeName === 'style' || 
                            mutation.attributeName === 'class') {
                            shouldFix = true;
                        }
                    }
                }
            });
            
            if (shouldFix) {
                setTimeout(fixDropdownScroll, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class', 'data-scroll', 'data-scrollable']
        });
        
        console.log('👁️ Dropdown scroll monitoring activated');
    }
    
    // Initialize everything
    function initialize() {
        console.log('🚀 Initializing dropdown scroll fix');
        
        // Run initial fix
        fixDropdownScroll();
        
        // Set up event handlers
        handleDropdownEvents();
        
        // Override Bootstrap behavior
        overrideBootstrapScroll();
        
        // Prevent scroll button creation
        preventScrollButtonCreation();
        
        // Set up monitoring
        setupScrollMonitoring();
        
        // Run fix periodically
        setInterval(fixDropdownScroll, 5000);
        
        console.log('✅ Dropdown scroll fix system activated');
    }
    
    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
    
    // Also run on window load
    window.addEventListener('load', function() {
        setTimeout(fixDropdownScroll, 1000);
    });
    
    // Expose global function for manual fixing
    window.fixDropdownScroll = fixDropdownScroll;
    
    console.log('🔧 Dropdown scroll fix script loaded');
})();