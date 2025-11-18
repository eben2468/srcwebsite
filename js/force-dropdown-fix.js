/**
 * FORCE DROPDOWN FIX - JavaScript Nuclear Option
 * This script aggressively prevents dropdown overflow by any means necessary
 */

(function() {
    'use strict';
    
    console.log('🚨 FORCE DROPDOWN FIX: Loading nuclear option...');
    
    // Configuration
    const CONFIG = {
        debug: true,
        forceInterval: 100, // Check every 100ms
        maxAttempts: 50
    };
    
    let attempts = 0;
    let fixInterval;
    
    // Log function
    function log(message, data = null) {
        if (CONFIG.debug) {
            console.log(`🔧 FORCE FIX: ${message}`, data || '');
        }
    }
    
    // Force prevent horizontal scroll
    function forcePreventHorizontalScroll() {
        // Set styles directly on elements
        document.documentElement.style.setProperty('overflow-x', 'hidden', 'important');
        document.documentElement.style.setProperty('max-width', '100vw', 'important');
        document.body.style.setProperty('overflow-x', 'hidden', 'important');
        document.body.style.setProperty('max-width', '100vw', 'important');
        
        // Force container styles
        const containers = document.querySelectorAll('.container-fluid, .container, .main-content');
        containers.forEach(container => {
            container.style.setProperty('overflow-x', 'hidden', 'important');
            container.style.setProperty('max-width', '100%', 'important');
            container.style.setProperty('box-sizing', 'border-box', 'important');
        });
    }
    
    // Force fix select elements
    function forceFixSelects() {
        const selects = document.querySelectorAll('select, .form-select');
        const viewportWidth = window.innerWidth;
        const isMobile = viewportWidth <= 767;
        
        log(`Fixing ${selects.length} select elements (Mobile: ${isMobile}, Viewport: ${viewportWidth}px)`);
        
        selects.forEach((select, index) => {
            try {
                // Get container
                const container = select.closest('.container-fluid, .container, .main-content') || document.body;
                const containerRect = container.getBoundingClientRect();
                
                // Calculate safe width
                const padding = isMobile ? 32 : 48; // Account for padding
                const safeWidth = Math.min(containerRect.width - padding, viewportWidth - padding);
                
                // Apply styles with maximum force
                select.style.setProperty('max-width', `${safeWidth}px`, 'important');
                select.style.setProperty('width', '100%', 'important');
                select.style.setProperty('box-sizing', 'border-box', 'important');
                select.style.setProperty('overflow', 'hidden', 'important');
                select.style.setProperty('text-overflow', 'ellipsis', 'important');
                
                // Force parent container
                const parent = select.parentElement;
                if (parent) {
                    parent.style.setProperty('max-width', '100%', 'important');
                    parent.style.setProperty('overflow', 'hidden', 'important');
                    parent.style.setProperty('box-sizing', 'border-box', 'important');
                }
                
                log(`Fixed select ${index + 1}: width=${safeWidth}px`);
                
            } catch (error) {
                log(`Error fixing select ${index + 1}:`, error.message);
            }
        });
    }
    
    // Check for overflow and fix it
    function checkAndFixOverflow() {
        const bodyWidth = document.body.scrollWidth;
        const viewportWidth = window.innerWidth;
        const hasOverflow = bodyWidth > viewportWidth;
        
        if (hasOverflow) {
            log(`🚨 OVERFLOW DETECTED: Body=${bodyWidth}px, Viewport=${viewportWidth}px, Diff=${bodyWidth - viewportWidth}px`);
            
            // Find overflowing elements
            const allElements = document.querySelectorAll('*');
            const overflowingElements = [];
            
            allElements.forEach(element => {
                const rect = element.getBoundingClientRect();
                if (rect.right > viewportWidth) {
                    overflowingElements.push({
                        element: element,
                        tagName: element.tagName,
                        className: element.className,
                        right: rect.right,
                        width: rect.width,
                        overflow: rect.right - viewportWidth
                    });
                }
            });
            
            log(`Found ${overflowingElements.length} overflowing elements:`, overflowingElements);
            
            // Fix overflowing elements
            overflowingElements.forEach(item => {
                const element = item.element;
                
                // Skip certain elements
                if (element.tagName === 'HTML' || element.tagName === 'BODY') {
                    return;
                }
                
                // Force fix the element
                element.style.setProperty('max-width', '100%', 'important');
                element.style.setProperty('box-sizing', 'border-box', 'important');
                element.style.setProperty('overflow', 'hidden', 'important');
                
                if (element.tagName === 'SELECT') {
                    const safeWidth = Math.min(viewportWidth - 32, item.width);
                    element.style.setProperty('width', `${safeWidth}px`, 'important');
                    log(`🔧 Fixed overflowing select: ${item.overflow}px overflow reduced`);
                }
            });
            
            return false; // Still has overflow
        }
        
        return true; // No overflow
    }
    
    // Force fix everything
    function forceFixEverything() {
        attempts++;
        
        log(`Force fix attempt ${attempts}/${CONFIG.maxAttempts}`);
        
        // Apply all fixes
        forcePreventHorizontalScroll();
        forceFixSelects();
        
        // Check if fixed
        const isFixed = checkAndFixOverflow();
        
        if (isFixed) {
            log('✅ SUCCESS: No overflow detected, stopping force fix');
            if (fixInterval) {
                clearInterval(fixInterval);
                fixInterval = null;
            }
            return true;
        } else if (attempts >= CONFIG.maxAttempts) {
            log('❌ FAILED: Maximum attempts reached, stopping force fix');
            if (fixInterval) {
                clearInterval(fixInterval);
                fixInterval = null;
            }
            return false;
        }
        
        return false;
    }
    
    // Start aggressive fixing
    function startAggressiveFix() {
        log('🚀 Starting aggressive dropdown fix...');
        
        // Initial fix
        forceFixEverything();
        
        // Set up interval for continuous fixing
        if (!fixInterval) {
            fixInterval = setInterval(() => {
                forceFixEverything();
            }, CONFIG.forceInterval);
        }
        
        // Stop after reasonable time
        setTimeout(() => {
            if (fixInterval) {
                clearInterval(fixInterval);
                fixInterval = null;
                log('⏰ Stopping aggressive fix after timeout');
            }
        }, 5000); // Stop after 5 seconds
    }
    
    // Handle select interactions
    function handleSelectInteractions() {
        document.addEventListener('click', function(event) {
            if (event.target.tagName === 'SELECT' || event.target.classList.contains('form-select')) {
                log('🖱️ Select clicked, applying immediate fix');
                setTimeout(() => {
                    forceFixSelects();
                    checkAndFixOverflow();
                }, 10);
            }
        });
        
        document.addEventListener('focus', function(event) {
            if (event.target.tagName === 'SELECT' || event.target.classList.contains('form-select')) {
                log('🎯 Select focused, applying immediate fix');
                setTimeout(() => {
                    forceFixSelects();
                    checkAndFixOverflow();
                }, 10);
            }
        }, true);
    }
    
    // Initialize
    function init() {
        log('🚨 INITIALIZING FORCE DROPDOWN FIX');
        
        // Start aggressive fixing
        startAggressiveFix();
        
        // Handle interactions
        handleSelectInteractions();
        
        // Handle resize
        window.addEventListener('resize', () => {
            log('📱 Window resized, restarting aggressive fix');
            attempts = 0; // Reset attempts
            startAggressiveFix();
        });
        
        // Handle orientation change
        window.addEventListener('orientationchange', () => {
            log('🔄 Orientation changed, restarting aggressive fix');
            setTimeout(() => {
                attempts = 0; // Reset attempts
                startAggressiveFix();
            }, 100);
        });
    }
    
    // Expose global functions for manual control
    window.ForceDropdownFix = {
        start: startAggressiveFix,
        fix: forceFixEverything,
        check: checkAndFixOverflow,
        stop: () => {
            if (fixInterval) {
                clearInterval(fixInterval);
                fixInterval = null;
                log('🛑 Force fix stopped manually');
            }
        }
    };
    
    // Auto-start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    log('🚨 FORCE DROPDOWN FIX: Script loaded and ready');
    
})();