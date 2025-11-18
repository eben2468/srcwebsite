/**
 * Remove Dropdown Scroll Buttons - Direct JavaScript Fix
 * This script directly removes any scroll buttons that appear when dropdowns are opened
 */

(function() {
    'use strict';
    
    console.log('🔧 Remove Dropdown Scroll Buttons: Initializing...');
    
    // Function to remove all scroll-related elements
    function removeScrollButtons() {
        // Remove any fixed positioned elements on the right side
        const fixedElements = document.querySelectorAll('*[style*="position: fixed"][style*="right"]');
        let removedCount = 0;
        
        fixedElements.forEach(element => {
            // Don't remove legitimate elements like modals, tooltips
            const className = element.className || '';
            const id = element.id || '';
            
            if (!className.includes('modal') && 
                !className.includes('tooltip') && 
                !className.includes('popover') && 
                !className.includes('toast') && 
                !id.includes('modal') && 
                !id.includes('tooltip')) {
                
                element.remove();
                removedCount++;
                console.log('🗑️ Removed fixed positioned element:', element);
            }
        });
        
        // Remove scroll-related elements
        const scrollSelectors = [
            '.scroll-up',
            '.scroll-down',
            '.scroll-top',
            '.scroll-bottom',
            '.scrollbar-up',
            '.scrollbar-down',
            '[class*="scroll-button"]',
            '[class*="scroll-arrow"]',
            '[class*="scroll-indicator"]',
            '#scroll-up',
            '#scroll-down',
            '#scroll-top',
            '#scroll-bottom',
            '[id*="scroll-button"]',
            '[id*="scroll-arrow"]'
        ];
        
        scrollSelectors.forEach(selector => {
            try {
                const elements = document.querySelectorAll(selector);
                elements.forEach(element => {
                    element.remove();
                    removedCount++;
                    console.log('🗑️ Removed scroll element:', selector, element);
                });
            } catch (e) {
                // Ignore selector errors
            }
        });
        
        // Remove scroll elements from dropdowns specifically
        const dropdownMenus = document.querySelectorAll('.dropdown-menu');
        dropdownMenus.forEach(menu => {
            // Remove any scroll-related children
            const scrollChildren = menu.querySelectorAll('*[class*="scroll"], *[id*="scroll"]');
            scrollChildren.forEach(child => {
                child.remove();
                removedCount++;
                console.log('🗑️ Removed scroll element from dropdown:', child);
            });
            
            // Force dropdown styles
            menu.style.overflow = 'visible';
            menu.style.maxHeight = 'none';
            menu.style.scrollbarWidth = 'none';
            menu.style.msOverflowStyle = 'none';
        });
        
        console.log(`✅ Removed ${removedCount} scroll-related elements`);
        return removedCount;
    }
    
    // Function to handle dropdown events
    function handleDropdownEvents() {
        // Listen for Bootstrap dropdown events
        document.addEventListener('show.bs.dropdown', function(e) {
            console.log('📂 Dropdown showing, removing scroll buttons...');
            setTimeout(removeScrollButtons, 50);
        });
        
        document.addEventListener('shown.bs.dropdown', function(e) {
            console.log('📂 Dropdown shown, removing scroll buttons...');
            setTimeout(removeScrollButtons, 100);
        });
        
        // Listen for clicks on dropdown toggles
        document.addEventListener('click', function(e) {
            if (e.target.matches('.dropdown-toggle') || 
                e.target.closest('.dropdown-toggle') ||
                e.target.matches('[data-bs-toggle="dropdown"]') ||
                e.target.closest('[data-bs-toggle="dropdown"]')) {
                
                console.log('🖱️ Dropdown toggle clicked, removing scroll buttons...');
                setTimeout(removeScrollButtons, 100);
                setTimeout(removeScrollButtons, 500); // Double check
            }
        });
    }
    
    // Function to monitor for dynamically added scroll elements
    function setupMutationObserver() {
        if (!window.MutationObserver) return;
        
        const observer = new MutationObserver(function(mutations) {
            let shouldRemove = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            const style = node.getAttribute ? (node.getAttribute('style') || '') : '';
                            const className = node.className || '';
                            const id = node.id || '';
                            
                            // Check if it's a scroll-related element
                            if ((style.includes('position: fixed') && style.includes('right')) ||
                                className.includes('scroll') ||
                                id.includes('scroll')) {
                                
                                shouldRemove = true;
                                console.log('👀 Detected potential scroll button:', node);
                            }
                        }
                    });
                }
            });
            
            if (shouldRemove) {
                setTimeout(removeScrollButtons, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class', 'id']
        });
        
        console.log('👁️ Mutation observer activated for scroll button detection');
    }
    
    // Function to override common methods that might add scroll buttons
    function preventScrollButtonCreation() {
        // Override appendChild
        const originalAppendChild = Element.prototype.appendChild;
        Element.prototype.appendChild = function(child) {
            if (child && child.nodeType === 1) {
                const style = child.getAttribute ? (child.getAttribute('style') || '') : '';
                const className = child.className || '';
                const id = child.id || '';
                
                // Block scroll buttons
                if ((style.includes('position: fixed') && style.includes('right')) ||
                    className.includes('scroll') ||
                    id.includes('scroll')) {
                    
                    console.log('🚫 Blocked scroll button creation:', child);
                    return child; // Don't actually append
                }
            }
            
            return originalAppendChild.call(this, child);
        };
        
        console.log('🛡️ Scroll button creation prevention activated');
    }
    
    // Initialize everything
    function initialize() {
        console.log('🚀 Initializing dropdown scroll button removal');
        
        // Remove existing scroll buttons
        removeScrollButtons();
        
        // Handle dropdown events
        handleDropdownEvents();
        
        // Set up mutation observer
        setupMutationObserver();
        
        // Prevent scroll button creation
        preventScrollButtonCreation();
        
        // Run removal periodically
        setInterval(removeScrollButtons, 3000);
        
        // Handle window events
        window.addEventListener('resize', () => {
            setTimeout(removeScrollButtons, 500);
        });
        
        window.addEventListener('orientationchange', () => {
            setTimeout(removeScrollButtons, 500);
        });
        
        console.log('✅ Dropdown scroll button removal system activated');
    }
    
    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
    
    // Also run on window load
    window.addEventListener('load', function() {
        setTimeout(removeScrollButtons, 1000);
    });
    
    // Expose global function for manual removal
    window.removeDropdownScrollButtons = removeScrollButtons;
    
    console.log('🔧 Remove Dropdown Scroll Buttons: Script loaded');
})();