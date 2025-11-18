/**
 * JavaScript-based Dropdown Overflow Fix
 * This script prevents dropdown overflow on mobile devices
 * Use this as a fallback when CSS solutions don't work
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        mobileBreakpoint: 768,
        tabletBreakpoint: 992,
        padding: {
            mobile: 16,
            tablet: 20,
            desktop: 24
        }
    };
    
    // Get current breakpoint
    function getCurrentBreakpoint() {
        const width = window.innerWidth;
        if (width < CONFIG.mobileBreakpoint) return 'mobile';
        if (width < CONFIG.tabletBreakpoint) return 'tablet';
        return 'desktop';
    }
    
    // Get expected padding for current breakpoint
    function getExpectedPadding() {
        const breakpoint = getCurrentBreakpoint();
        return CONFIG.padding[breakpoint] || CONFIG.padding.mobile;
    }
    
    // Fix select element overflow
    function fixSelectOverflow(selectElement) {
        const container = selectElement.closest('.container-fluid, .container, .main-content') || document.body;
        const containerRect = container.getBoundingClientRect();
        const padding = getExpectedPadding();
        
        // Calculate maximum width
        const maxWidth = containerRect.width - (padding * 2);
        
        // Apply styles
        selectElement.style.maxWidth = `${maxWidth}px`;
        selectElement.style.width = '100%';
        selectElement.style.boxSizing = 'border-box';
        selectElement.style.overflow = 'hidden';
        selectElement.style.textOverflow = 'ellipsis';
        
        // Fix parent container if needed
        const parent = selectElement.parentElement;
        if (parent) {
            parent.style.maxWidth = '100%';
            parent.style.overflow = 'hidden';
            parent.style.boxSizing = 'border-box';
        }
    }
    
    // Fix all select elements on the page
    function fixAllSelects() {
        const selects = document.querySelectorAll('select, .form-select');
        selects.forEach(fixSelectOverflow);
    }
    
    // Prevent horizontal overflow
    function preventHorizontalOverflow() {
        // Force body and html to prevent horizontal scroll
        document.documentElement.style.overflowX = 'hidden';
        document.documentElement.style.maxWidth = '100vw';
        document.body.style.overflowX = 'hidden';
        document.body.style.maxWidth = '100vw';
        
        // Fix containers
        const containers = document.querySelectorAll('.container-fluid, .container, .main-content, .row, .col, [class*="col-"]');
        containers.forEach(container => {
            container.style.overflowX = 'hidden';
            container.style.maxWidth = '100%';
            container.style.boxSizing = 'border-box';
        });
        
        // Fix form elements
        const formElements = document.querySelectorAll('.form-group, .mb-3, .form-floating, .input-group');
        formElements.forEach(element => {
            element.style.maxWidth = '100%';
            element.style.overflow = 'hidden';
            element.style.boxSizing = 'border-box';
        });
    }
    
    // Check for overflow and log issues
    function checkForOverflow() {
        const hasOverflow = document.body.scrollWidth > window.innerWidth;
        
        if (hasOverflow) {
            console.warn('Horizontal overflow detected!', {
                bodyWidth: document.body.scrollWidth,
                viewportWidth: window.innerWidth,
                difference: document.body.scrollWidth - window.innerWidth
            });
            
            // Try to find the offending element
            const allElements = document.querySelectorAll('*');
            const overflowingElements = [];
            
            allElements.forEach(element => {
                const rect = element.getBoundingClientRect();
                if (rect.right > window.innerWidth) {
                    overflowingElements.push({
                        element: element,
                        tagName: element.tagName,
                        className: element.className,
                        right: rect.right,
                        width: rect.width
                    });
                }
            });
            
            if (overflowingElements.length > 0) {
                console.warn('Overflowing elements found:', overflowingElements);
                
                // Try to fix the overflowing elements
                overflowingElements.forEach(item => {
                    const element = item.element;
                    element.style.maxWidth = '100%';
                    element.style.boxSizing = 'border-box';
                    element.style.overflow = 'hidden';
                    
                    if (element.tagName === 'SELECT') {
                        fixSelectOverflow(element);
                    }
                });
            }
        }
        
        return !hasOverflow;
    }
    
    // Initialize the fix
    function init() {
        console.log('Initializing dropdown overflow fix...');
        
        // Apply fixes
        preventHorizontalOverflow();
        fixAllSelects();
        
        // Check for issues
        setTimeout(() => {
            const isFixed = checkForOverflow();
            console.log('Overflow check result:', isFixed ? 'FIXED' : 'STILL HAS ISSUES');
        }, 100);
    }
    
    // Reinitialize on resize
    function handleResize() {
        setTimeout(() => {
            fixAllSelects();
            checkForOverflow();
        }, 100);
    }
    
    // Handle dynamic content
    function handleDynamicContent() {
        // Use MutationObserver to watch for new select elements
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(function(mutations) {
                let hasNewSelects = false;
                
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // Element node
                                if (node.tagName === 'SELECT' || node.classList.contains('form-select')) {
                                    hasNewSelects = true;
                                } else if (node.querySelector) {
                                    const selects = node.querySelectorAll('select, .form-select');
                                    if (selects.length > 0) {
                                        hasNewSelects = true;
                                    }
                                }
                            }
                        });
                    }
                });
                
                if (hasNewSelects) {
                    setTimeout(fixAllSelects, 50);
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }
    
    // Event listeners
    document.addEventListener('DOMContentLoaded', init);
    window.addEventListener('resize', handleResize);
    window.addEventListener('orientationchange', handleResize);
    
    // Handle dynamic content
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', handleDynamicContent);
    } else {
        handleDynamicContent();
    }
    
    // Expose functions globally for debugging
    window.DropdownOverflowFix = {
        init: init,
        fixAllSelects: fixAllSelects,
        checkForOverflow: checkForOverflow,
        preventHorizontalOverflow: preventHorizontalOverflow
    };
    
    console.log('Dropdown overflow fix script loaded');
})();