/**
 * Remove Scroll Button JavaScript
 * Removes any dynamically added scroll buttons from the page
 */

(function() {
    'use strict';
    
    // Function to remove scroll buttons
    function removeScrollButtons() {
        // Common scroll button selectors
        const scrollButtonSelectors = [
            '.scroll-to-top',
            '.back-to-top',
            '.scroll-btn',
            '.scroll-button',
            '.floating-scroll',
            '.scroll-up',
            '.scroll-down',
            '.fab',
            '.floating-action-button',
            '.fixed-action-btn',
            '#scroll-to-top',
            '#back-to-top',
            '#scroll-btn',
            '#scroll-button',
            '#page-scroll',
            '#scroll-up',
            '#scroll-down',
            '[data-scroll]',
            '[data-scroll-btn]',
            '[data-back-to-top]',
            '.btn-floating',
            '.btn-fixed',
            '.fixed-btn'
        ];
        
        // Remove elements matching scroll button selectors
        scrollButtonSelectors.forEach(selector => {
            try {
                const elements = document.querySelectorAll(selector);
                elements.forEach(element => {
                    if (element && element.parentNode) {
                        element.parentNode.removeChild(element);
                        console.log('Removed scroll button:', selector);
                    }
                });
            } catch (e) {
                // Ignore selector errors
            }
        });
        
        // Remove any fixed positioned buttons on the right side
        const fixedElements = document.querySelectorAll('*[style*="position: fixed"]');
        fixedElements.forEach(element => {
            const style = element.getAttribute('style') || '';
            if (style.includes('right:') || style.includes('right :')) {
                // Check if it might be a scroll button
                const classList = element.className.toLowerCase();
                const innerHTML = element.innerHTML.toLowerCase();
                
                if (classList.includes('scroll') || 
                    classList.includes('back') || 
                    classList.includes('top') ||
                    innerHTML.includes('chevron-up') ||
                    innerHTML.includes('chevron-down') ||
                    innerHTML.includes('arrow-up') ||
                    innerHTML.includes('arrow-down') ||
                    innerHTML.includes('fa-up') ||
                    innerHTML.includes('fa-down')) {
                    
                    if (element.parentNode) {
                        element.parentNode.removeChild(element);
                        console.log('Removed fixed positioned scroll button');
                    }
                }
            }
        });
        
        // Remove any elements with scroll-related classes
        const scrollElements = document.querySelectorAll('[class*="scroll-"], [class*="back-to-"], [class*="scroll-up"], [class*="scroll-down"]');
        scrollElements.forEach(element => {
            // Don't remove legitimate scroll containers
            const classList = element.className.toLowerCase();
            if (!classList.includes('overflow') && 
                !classList.includes('container') && 
                !classList.includes('content') &&
                !classList.includes('table') &&
                !element.tagName.toLowerCase().includes('table')) {
                
                if (element.parentNode) {
                    element.parentNode.removeChild(element);
                    console.log('Removed scroll-related element');
                }
            }
        });
        
        // Remove any buttons with scroll arrow icons
        const arrowButtons = document.querySelectorAll('button, .btn');
        arrowButtons.forEach(button => {
            const style = button.getAttribute('style') || '';
            if (style.includes('position: fixed') || style.includes('position:fixed')) {
                const icons = button.querySelectorAll('i');
                icons.forEach(icon => {
                    const iconClass = icon.className.toLowerCase();
                    if (iconClass.includes('chevron-up') ||
                        iconClass.includes('chevron-down') ||
                        iconClass.includes('arrow-up') ||
                        iconClass.includes('arrow-down')) {
                        
                        if (button.parentNode) {
                            button.parentNode.removeChild(button);
                            console.log('Removed arrow button');
                        }
                    }
                });
            }
        });
    }
    
    // Function to prevent scroll buttons from being added
    function preventScrollButtons() {
        // Override common methods used to add scroll buttons
        const originalAppendChild = Element.prototype.appendChild;
        const originalInsertBefore = Element.prototype.insertBefore;
        
        Element.prototype.appendChild = function(child) {
            if (child && child.className && typeof child.className === 'string') {
                const className = child.className.toLowerCase();
                if (className.includes('scroll-to-top') ||
                    className.includes('back-to-top') ||
                    className.includes('scroll-btn') ||
                    className.includes('scroll-button') ||
                    className.includes('floating-scroll') ||
                    className.includes('fab')) {
                    console.log('Prevented scroll button from being added');
                    return child; // Don't actually append
                }
            }
            return originalAppendChild.call(this, child);
        };
        
        Element.prototype.insertBefore = function(newNode, referenceNode) {
            if (newNode && newNode.className && typeof newNode.className === 'string') {
                const className = newNode.className.toLowerCase();
                if (className.includes('scroll-to-top') ||
                    className.includes('back-to-top') ||
                    className.includes('scroll-btn') ||
                    className.includes('scroll-button') ||
                    className.includes('floating-scroll') ||
                    className.includes('fab')) {
                    console.log('Prevented scroll button from being inserted');
                    return newNode; // Don't actually insert
                }
            }
            return originalInsertBefore.call(this, newNode, referenceNode);
        };
    }
    
    // Function to remove scroll button event listeners
    function removeScrollEventListeners() {
        // Remove scroll event listeners that might show/hide scroll buttons
        const scrollEvents = ['scroll', 'resize', 'load'];
        scrollEvents.forEach(eventType => {
            window.removeEventListener(eventType, showScrollButton, true);
            window.removeEventListener(eventType, hideScrollButton, true);
            document.removeEventListener(eventType, showScrollButton, true);
            document.removeEventListener(eventType, hideScrollButton, true);
        });
    }
    
    // Dummy functions to catch common scroll button function names
    function showScrollButton() {}
    function hideScrollButton() {}
    
    // Initialize removal on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            removeScrollButtons();
            preventScrollButtons();
            removeScrollEventListeners();
        });
    } else {
        removeScrollButtons();
        preventScrollButtons();
        removeScrollEventListeners();
    }
    
    // Run removal periodically to catch dynamically added buttons
    setInterval(removeScrollButtons, 2000);
    
    // Run removal on window load
    window.addEventListener('load', function() {
        setTimeout(removeScrollButtons, 1000);
    });
    
    // Run removal on page visibility change
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            setTimeout(removeScrollButtons, 500);
        }
    });
    
    // Mutation observer to catch dynamically added scroll buttons
    if (window.MutationObserver) {
        const observer = new MutationObserver(function(mutations) {
            let shouldRemove = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            const className = node.className || '';
                            if (typeof className === 'string' && 
                                (className.includes('scroll') || 
                                 className.includes('back-to-top') || 
                                 className.includes('fab'))) {
                                shouldRemove = true;
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
            subtree: true
        });
    }
    
    console.log('Scroll button removal script initialized');
})();