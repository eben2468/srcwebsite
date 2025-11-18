/**
 * NUCLEAR DROPDOWN FIX - JavaScript Component
 * This completely replaces native select elements with custom dropdowns
 * that are guaranteed to stay within container boundaries
 */

(function() {
    'use strict';
    
    console.log('🚨 NUCLEAR DROPDOWN FIX: Initializing complete select replacement...');
    
    // Configuration
    const CONFIG = {
        debug: true,
        mobileBreakpoint: 767,
        replaceOnMobile: true,
        forceReplace: true
    };
    
    // Track replaced selects
    let replacedSelects = new Map();
    
    // Log function
    function log(message, data = null) {
        if (CONFIG.debug) {
            console.log(`💥 NUCLEAR FIX: ${message}`, data || '');
        }
    }
    
    // Check if we should replace selects
    function shouldReplaceSelects() {
        const isMobile = window.innerWidth <= CONFIG.mobileBreakpoint;
        return CONFIG.forceReplace || (CONFIG.replaceOnMobile && isMobile);
    }
    
    // Create custom dropdown HTML
    function createCustomDropdown(originalSelect) {
        const selectId = originalSelect.id || `custom-select-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const selectClasses = originalSelect.className;
        const isRequired = originalSelect.hasAttribute('required');
        
        // Get all options
        const options = Array.from(originalSelect.options).map(option => ({
            value: option.value,
            text: option.textContent,
            selected: option.selected,
            disabled: option.disabled
        }));
        
        // Find selected option
        const selectedOption = options.find(opt => opt.selected) || options[0];
        const selectedText = selectedOption ? selectedOption.text : 'Select...';
        
        // Create custom dropdown HTML
        const dropdownHTML = `
            <div class="custom-dropdown" data-original-id="${originalSelect.id}" data-original-name="${originalSelect.name}">
                <button type="button" class="custom-dropdown-button" id="${selectId}-button" aria-haspopup="listbox" aria-expanded="false" ${isRequired ? 'required' : ''}>
                    <span class="selected-text">${selectedText}</span>
                </button>
                <div class="custom-dropdown-menu" id="${selectId}-menu" role="listbox">
                    ${options.map(option => `
                        <div class="custom-dropdown-item ${option.selected ? 'active' : ''}" 
                             role="option" 
                             data-value="${option.value}" 
                             ${option.disabled ? 'aria-disabled="true"' : ''}
                             tabindex="0">
                            ${option.text}
                        </div>
                    `).join('')}
                </div>
                <select style="display: none;" name="${originalSelect.name}" ${isRequired ? 'required' : ''}>
                    ${options.map(option => `
                        <option value="${option.value}" ${option.selected ? 'selected' : ''} ${option.disabled ? 'disabled' : ''}>
                            ${option.text}
                        </option>
                    `).join('')}
                </select>
            </div>
        `;
        
        return dropdownHTML;
    }
    
    // Add event listeners to custom dropdown
    function addDropdownListeners(dropdownElement) {
        const button = dropdownElement.querySelector('.custom-dropdown-button');
        const menu = dropdownElement.querySelector('.custom-dropdown-menu');
        const hiddenSelect = dropdownElement.querySelector('select');
        const items = dropdownElement.querySelectorAll('.custom-dropdown-item');
        const selectedTextSpan = button.querySelector('.selected-text');
        
        // Toggle dropdown
        function toggleDropdown() {
            const isOpen = menu.classList.contains('show');
            
            // Close all other dropdowns first
            document.querySelectorAll('.custom-dropdown-menu.show').forEach(otherMenu => {
                if (otherMenu !== menu) {
                    otherMenu.classList.remove('show');
                    const otherButton = otherMenu.parentElement.querySelector('.custom-dropdown-button');
                    otherButton.setAttribute('aria-expanded', 'false');
                }
            });
            
            if (isOpen) {
                menu.classList.remove('show');
                button.setAttribute('aria-expanded', 'false');
            } else {
                menu.classList.add('show');
                button.setAttribute('aria-expanded', 'true');
                
                // Ensure menu stays within viewport
                setTimeout(() => {
                    const menuRect = menu.getBoundingClientRect();
                    const viewportWidth = window.innerWidth;
                    
                    if (menuRect.right > viewportWidth) {
                        const overflow = menuRect.right - viewportWidth;
                        menu.style.transform = `translateX(-${overflow + 10}px)`;
                        log(`Adjusted menu position by -${overflow + 10}px to prevent overflow`);
                    }
                }, 10);
            }
        }
        
        // Select item
        function selectItem(item) {
            const value = item.getAttribute('data-value');
            const text = item.textContent;
            
            // Update visual state
            items.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            selectedTextSpan.textContent = text;
            
            // Update hidden select
            hiddenSelect.value = value;
            
            // Trigger change event
            const changeEvent = new Event('change', { bubbles: true });
            hiddenSelect.dispatchEvent(changeEvent);
            
            // Close dropdown
            menu.classList.remove('show');
            button.setAttribute('aria-expanded', 'false');
            
            log(`Selected: ${text} (${value})`);
        }
        
        // Button click
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleDropdown();
        });
        
        // Item clicks
        items.forEach(item => {
            if (!item.hasAttribute('aria-disabled')) {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectItem(this);
                });
                
                // Keyboard support
                item.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        selectItem(this);
                    }
                });
            }
        });
        
        // Keyboard navigation for button
        button.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                toggleDropdown();
            }
        });
        
        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!dropdownElement.contains(e.target)) {
                menu.classList.remove('show');
                button.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Close on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && menu.classList.contains('show')) {
                menu.classList.remove('show');
                button.setAttribute('aria-expanded', 'false');
                button.focus();
            }
        });
    }
    
    // Replace a single select element
    function replaceSelect(originalSelect) {
        try {
            log(`Replacing select: ${originalSelect.id || 'unnamed'}`);
            
            // Create custom dropdown
            const dropdownHTML = createCustomDropdown(originalSelect);
            
            // Create temporary container
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = dropdownHTML;
            const customDropdown = tempDiv.firstElementChild;
            
            // Insert custom dropdown before original
            originalSelect.parentNode.insertBefore(customDropdown, originalSelect);
            
            // Hide original select
            originalSelect.style.display = 'none';
            originalSelect.setAttribute('aria-hidden', 'true');
            originalSelect.setAttribute('tabindex', '-1');
            
            // Add event listeners
            addDropdownListeners(customDropdown);
            
            // Store reference
            replacedSelects.set(originalSelect, customDropdown);
            
            log(`Successfully replaced select: ${originalSelect.id || 'unnamed'}`);
            
        } catch (error) {
            log(`Error replacing select: ${error.message}`, error);
        }
    }
    
    // Replace all select elements
    function replaceAllSelects() {
        if (!shouldReplaceSelects()) {
            log('Skipping select replacement (not mobile or not forced)');
            return;
        }
        
        const selects = document.querySelectorAll('select:not([data-nuclear-replaced])');
        log(`Found ${selects.length} select elements to replace`);
        
        selects.forEach(select => {
            // Mark as processed
            select.setAttribute('data-nuclear-replaced', 'true');
            
            // Replace the select
            replaceSelect(select);
        });
        
        log(`Replaced ${selects.length} select elements with custom dropdowns`);
    }
    
    // Force prevent any horizontal overflow
    function forcePreventOverflow() {
        // Set viewport constraints
        document.documentElement.style.setProperty('overflow-x', 'hidden', 'important');
        document.documentElement.style.setProperty('max-width', '100vw', 'important');
        document.documentElement.style.setProperty('width', '100vw', 'important');
        
        document.body.style.setProperty('overflow-x', 'hidden', 'important');
        document.body.style.setProperty('max-width', '100vw', 'important');
        document.body.style.setProperty('width', '100vw', 'important');
        
        // Force all containers
        const containers = document.querySelectorAll('.container-fluid, .container, .main-content, .row, .col, [class*="col-"], .form-group, .mb-3, form, div');
        containers.forEach(container => {
            container.style.setProperty('max-width', '100%', 'important');
            container.style.setProperty('overflow-x', 'hidden', 'important');
            container.style.setProperty('box-sizing', 'border-box', 'important');
        });
        
        log('Applied nuclear overflow prevention');
    }
    
    // Check for any remaining overflow
    function checkForOverflow() {
        const bodyWidth = document.body.scrollWidth;
        const viewportWidth = window.innerWidth;
        const hasOverflow = bodyWidth > viewportWidth;
        
        if (hasOverflow) {
            log(`🚨 OVERFLOW STILL DETECTED: Body=${bodyWidth}px, Viewport=${viewportWidth}px, Diff=${bodyWidth - viewportWidth}px`);
            
            // Find and fix overflowing elements
            const allElements = document.querySelectorAll('*');
            allElements.forEach(element => {
                const rect = element.getBoundingClientRect();
                if (rect.right > viewportWidth) {
                    element.style.setProperty('max-width', '100%', 'important');
                    element.style.setProperty('overflow', 'hidden', 'important');
                    element.style.setProperty('box-sizing', 'border-box', 'important');
                    
                    log(`Fixed overflowing element: ${element.tagName}.${element.className}`);
                }
            });
            
            return false;
        }
        
        log('✅ No overflow detected');
        return true;
    }
    
    // Initialize nuclear fix
    function initNuclearFix() {
        log('🚨 INITIALIZING NUCLEAR DROPDOWN FIX');
        
        // Force prevent overflow first
        forcePreventOverflow();
        
        // Replace all selects
        replaceAllSelects();
        
        // Check for overflow
        setTimeout(() => {
            checkForOverflow();
        }, 100);
        
        // Set up mutation observer for dynamic content
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(function(mutations) {
                let hasNewSelects = false;
                
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) {
                                if (node.tagName === 'SELECT' && !node.hasAttribute('data-nuclear-replaced')) {
                                    hasNewSelects = true;
                                } else if (node.querySelector) {
                                    const newSelects = node.querySelectorAll('select:not([data-nuclear-replaced])');
                                    if (newSelects.length > 0) {
                                        hasNewSelects = true;
                                    }
                                }
                            }
                        });
                    }
                });
                
                if (hasNewSelects) {
                    log('New select elements detected, replacing...');
                    setTimeout(replaceAllSelects, 50);
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
        
        log('✅ Nuclear dropdown fix initialized');
    }
    
    // Handle resize
    function handleResize() {
        setTimeout(() => {
            forcePreventOverflow();
            checkForOverflow();
        }, 100);
    }
    
    // Expose global functions
    window.NuclearDropdownFix = {
        init: initNuclearFix,
        replaceAll: replaceAllSelects,
        checkOverflow: checkForOverflow,
        forcePrevent: forcePreventOverflow
    };
    
    // Auto-initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNuclearFix);
    } else {
        initNuclearFix();
    }
    
    // Handle resize and orientation change
    window.addEventListener('resize', handleResize);
    window.addEventListener('orientationchange', () => {
        setTimeout(handleResize, 200);
    });
    
    log('🚨 NUCLEAR DROPDOWN FIX: Script loaded and ready');
    
})();