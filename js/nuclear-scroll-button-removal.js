/**
 * Nuclear Scroll Button Removal JavaScript
 * This is an aggressive approach to remove ANY scroll buttons from the page
 */

(function() {
    'use strict';
    
    console.log('🚀 Nuclear scroll button removal initiated');
    
    // Function to aggressively remove scroll buttons
    function nuclearScrollButtonRemoval() {
        console.log('🔥 Running nuclear scroll button removal');
        
        // Remove by style attribute - any fixed positioned element on the right
        const fixedElements = document.querySelectorAll('*');
        let removedCount = 0;
        
        fixedElements.forEach(element => {
            const style = element.getAttribute('style') || '';
            const computedStyle = window.getComputedStyle(element);
            
            // Check if element is fixed positioned on the right
            if ((style.includes('position: fixed') || style.includes('position:fixed')) && 
                (style.includes('right:') || style.includes('right :'))) {
                
                // Additional checks to avoid removing legitimate elements
                const tagName = element.tagName.toLowerCase();
                const className = element.className || '';
                const id = element.id || '';
                
                // Don't remove modals, dropdowns, tooltips, etc.
                if (!className.includes('modal') && 
                    !className.includes('dropdown') && 
                    !className.includes('tooltip') && 
                    !className.includes('popover') && 
                    !className.includes('toast') && 
                    !id.includes('modal') && 
                    !id.includes('dropdown')) {
                    
                    try {
                        element.remove();
                        removedCount++;
                        console.log('🗑️ Removed fixed positioned element:', element);
                    } catch (e) {
                        // Fallback - hide the element
                        element.style.display = 'none';
                        element.style.visibility = 'hidden';
                        element.style.opacity = '0';
                        element.style.pointerEvents = 'none';
                        element.style.position = 'absolute';
                        element.style.left = '-99999px';
                        element.style.top = '-99999px';
                        element.style.zIndex = '-99999';
                        removedCount++;
                        console.log('🙈 Hidden fixed positioned element:', element);
                    }
                }
            }
            
            // Check computed style as well
            if (computedStyle.position === 'fixed' && 
                (computedStyle.right !== 'auto' && computedStyle.right !== '')) {
                
                const className = element.className || '';
                const id = element.id || '';
                
                // Don't remove legitimate fixed elements
                if (!className.includes('modal') && 
                    !className.includes('dropdown') && 
                    !className.includes('tooltip') && 
                    !className.includes('popover') && 
                    !className.includes('toast') && 
                    !className.includes('navbar') && 
                    !className.includes('header') && 
                    !id.includes('modal') && 
                    !id.includes('dropdown')) {
                    
                    try {
                        element.remove();
                        removedCount++;
                        console.log('🗑️ Removed computed fixed positioned element:', element);
                    } catch (e) {
                        element.style.display = 'none';
                        removedCount++;
                        console.log('🙈 Hidden computed fixed positioned element:', element);
                    }
                }
            }
        });
        
        // Remove by class and id patterns
        const scrollSelectors = [
            '*[class*="scroll"]',
            '*[id*="scroll"]',
            '*[class*="back-to-top"]',
            '*[id*="back-to-top"]',
            '*[class*="scroll-to-top"]',
            '*[id*="scroll-to-top"]',
            '*[class*="fab"]',
            '*[id*="fab"]',
            '*[class*="floating"]',
            '*[id*="floating"]',
            '*[data-scroll]',
            '*[data-scroll-btn]',
            '*[data-back-to-top]',
            '*[aria-label*="scroll"]',
            '*[title*="scroll"]',
            '*[title*="back to top"]'
        ];
        
        scrollSelectors.forEach(selector => {
            try {
                const elements = document.querySelectorAll(selector);
                elements.forEach(element => {
                    // Don't remove legitimate scroll containers
                    const className = element.className || '';
                    if (!className.includes('overflow') && 
                        !className.includes('table') && 
                        !className.includes('container') && 
                        !className.includes('content') &&
                        !element.tagName.toLowerCase().includes('table')) {
                        
                        try {
                            element.remove();
                            removedCount++;
                            console.log('🗑️ Removed scroll element:', selector, element);
                        } catch (e) {
                            element.style.display = 'none';
                            removedCount++;
                            console.log('🙈 Hidden scroll element:', selector, element);
                        }
                    }
                });
            } catch (e) {
                console.log('⚠️ Selector error:', selector, e);
            }
        });
        
        // Remove elements with arrow icons
        const arrowSelectors = [
            '.fa-chevron-up',
            '.fa-chevron-down',
            '.fa-arrow-up',
            '.fa-arrow-down',
            '.fas.fa-chevron-up',
            '.fas.fa-chevron-down',
            '.fas.fa-arrow-up',
            '.fas.fa-arrow-down'
        ];
        
        arrowSelectors.forEach(selector => {
            try {
                const icons = document.querySelectorAll(selector);
                icons.forEach(icon => {
                    const button = icon.closest('button, .btn, a');
                    if (button) {
                        const style = button.getAttribute('style') || '';
                        const computedStyle = window.getComputedStyle(button);
                        
                        if ((style.includes('position: fixed') || computedStyle.position === 'fixed')) {
                            try {
                                button.remove();
                                removedCount++;
                                console.log('🗑️ Removed arrow button:', button);
                            } catch (e) {
                                button.style.display = 'none';
                                removedCount++;
                                console.log('🙈 Hidden arrow button:', button);
                            }
                        }
                    }
                });
            } catch (e) {
                console.log('⚠️ Arrow selector error:', selector, e);
            }
        });
        
        console.log(`✅ Nuclear removal complete. Removed/hidden ${removedCount} elements.`);
        return removedCount;
    }
    
    // Function to prevent scroll buttons from being added
    function preventScrollButtonAddition() {
        // Override appendChild
        const originalAppendChild = Element.prototype.appendChild;
        Element.prototype.appendChild = function(child) {
            if (child && child.nodeType === 1) { // Element node
                const style = child.getAttribute('style') || '';
                const className = child.className || '';
                const id = child.id || '';
                
                // Block scroll buttons
                if ((style.includes('position: fixed') && style.includes('right')) ||
                    className.includes('scroll') ||
                    className.includes('back-to-top') ||
                    className.includes('fab') ||
                    id.includes('scroll')) {
                    
                    console.log('🚫 Blocked scroll button addition:', child);
                    return child; // Don't actually append
                }
            }
            return originalAppendChild.call(this, child);
        };
        
        // Override insertBefore
        const originalInsertBefore = Element.prototype.insertBefore;
        Element.prototype.insertBefore = function(newNode, referenceNode) {
            if (newNode && newNode.nodeType === 1) { // Element node
                const style = newNode.getAttribute('style') || '';
                const className = newNode.className || '';
                const id = newNode.id || '';
                
                // Block scroll buttons
                if ((style.includes('position: fixed') && style.includes('right')) ||
                    className.includes('scroll') ||
                    className.includes('back-to-top') ||
                    className.includes('fab') ||
                    id.includes('scroll')) {
                    
                    console.log('🚫 Blocked scroll button insertion:', newNode);
                    return newNode; // Don't actually insert
                }
            }
            return originalInsertBefore.call(this, newNode, referenceNode);
        };
        
        console.log('🛡️ Scroll button prevention activated');
    }
    
    // Function to remove scroll event listeners
    function removeScrollEventListeners() {
        // Remove common scroll event listeners
        const events = ['scroll', 'resize', 'load'];
        events.forEach(eventType => {
            // Remove from window
            const listeners = getEventListeners ? getEventListeners(window)[eventType] : [];
            if (listeners) {
                listeners.forEach(listener => {
                    try {
                        window.removeEventListener(eventType, listener.listener, listener.useCapture);
                        console.log('🗑️ Removed window scroll listener:', eventType);
                    } catch (e) {
                        // Ignore errors
                    }
                });
            }
            
            // Remove from document
            const docListeners = getEventListeners ? getEventListeners(document)[eventType] : [];
            if (docListeners) {
                docListeners.forEach(listener => {
                    try {
                        document.removeEventListener(eventType, listener.listener, listener.useCapture);
                        console.log('🗑️ Removed document scroll listener:', eventType);
                    } catch (e) {
                        // Ignore errors
                    }
                });
            }
        });
    }
    
    // Mutation observer to catch dynamically added elements
    function setupMutationObserver() {
        if (!window.MutationObserver) return;
        
        const observer = new MutationObserver(function(mutations) {
            let shouldRunRemoval = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            const style = node.getAttribute ? (node.getAttribute('style') || '') : '';
                            const className = node.className || '';
                            const id = node.id || '';
                            
                            if ((style.includes('position: fixed') && style.includes('right')) ||
                                className.includes('scroll') ||
                                className.includes('back-to-top') ||
                                className.includes('fab') ||
                                id.includes('scroll')) {
                                
                                shouldRunRemoval = true;
                                console.log('👀 Detected potential scroll button addition:', node);
                            }
                        }
                    });
                }
            });
            
            if (shouldRunRemoval) {
                setTimeout(nuclearScrollButtonRemoval, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class', 'id']
        });
        
        console.log('👁️ Mutation observer activated');
    }
    
    // Initialize everything
    function initialize() {
        console.log('🚀 Initializing nuclear scroll button removal');
        
        // Run initial removal
        nuclearScrollButtonRemoval();
        
        // Set up prevention
        preventScrollButtonAddition();
        
        // Remove event listeners
        removeScrollEventListeners();
        
        // Set up mutation observer
        setupMutationObserver();
        
        // Run removal periodically
        setInterval(nuclearScrollButtonRemoval, 3000);
        
        // Run on window events
        window.addEventListener('load', () => setTimeout(nuclearScrollButtonRemoval, 1000));
        window.addEventListener('resize', () => setTimeout(nuclearScrollButtonRemoval, 500));
        
        // Run on visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                setTimeout(nuclearScrollButtonRemoval, 500);
            }
        });
        
        console.log('✅ Nuclear scroll button removal system activated');
    }
    
    // Start immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
    
    // Also run on window load as backup
    window.addEventListener('load', initialize);
    
    // Expose global function for manual removal
    window.nuclearRemoveScrollButtons = nuclearScrollButtonRemoval;
    
    console.log('💥 Nuclear scroll button removal script loaded');
})();