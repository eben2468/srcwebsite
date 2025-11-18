/**
 * Mobile Sidebar Close Button Fix
 * Ensures the close button is properly clickable and functional
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Mobile Sidebar Close Fix Script Loaded');
    
    // Wait a bit for all elements to be rendered
    setTimeout(function() {
        initializeCloseButton();
    }, 100);
    
    function initializeCloseButton() {
        const sidebar = document.querySelector('.sidebar') ||
                       document.querySelector('.dashboard-sidebar') ||
                       document.querySelector('[class*="sidebar"]');
        
        let closeBtn = document.getElementById('sidebar-close-btn');
        
        console.log('Sidebar found:', !!sidebar);
        console.log('Close button found:', !!closeBtn);
        
        if (!sidebar) {
            console.error('Sidebar not found for close button initialization');
            return;
        }
        
        // If close button doesn't exist, create it
        if (!closeBtn) {
            console.log('Creating close button...');
            createCloseButton(sidebar);
            closeBtn = document.getElementById('sidebar-close-btn');
        }
        
        if (closeBtn) {
            // Remove any existing event listeners
            const newCloseBtn = closeBtn.cloneNode(true);
            closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);
            closeBtn = newCloseBtn;
            
            // Add click event listener
            closeBtn.addEventListener('click', function(e) {
                console.log('Close button clicked!');
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const isMobile = window.innerWidth <= 991;
                console.log('Is mobile:', isMobile);
                
                if (isMobile && sidebar) {
                    console.log('Closing sidebar...');
                    sidebar.classList.remove('show', 'collapsed');
                    sidebar.classList.add('hide');
                    document.body.classList.remove('sidebar-open');
                    document.body.style.overflow = '';

                    console.log('Sidebar closed successfully');
                }
            });
            
            // Add touch event for better mobile support
            closeBtn.addEventListener('touchstart', function(e) {
                console.log('Close button touched!');
                e.preventDefault();
                e.stopPropagation();
                
                const isMobile = window.innerWidth <= 991;
                if (isMobile && sidebar) {
                    sidebar.classList.remove('show', 'collapsed');
                    sidebar.classList.add('hide');
                    document.body.classList.remove('sidebar-open');
                    document.body.style.overflow = '';
                }
            });
            
            console.log('Close button event listeners attached successfully');
        } else {
            console.error('Failed to find or create close button');
        }
    }
    
    function createCloseButton(sidebar) {
        // Create the header div if it doesn't exist
        let header = sidebar.querySelector('.sidebar-header');
        if (!header) {
            header = document.createElement('div');
            header.className = 'sidebar-header d-mobile-only';
            sidebar.insertBefore(header, sidebar.firstChild);
        }
        
        // Create the close button
        const closeBtn = document.createElement('button');
        closeBtn.id = 'sidebar-close-btn';
        closeBtn.className = 'btn btn-link text-white p-2';
        closeBtn.title = 'Close Sidebar';
        closeBtn.type = 'button';
        closeBtn.innerHTML = '<i class="fas fa-times fa-lg"></i>';
        
        header.appendChild(closeBtn);
        
        console.log('Close button created and added to sidebar');
    }
    
    // Also try to fix any existing close button that might not be working
    function fixExistingCloseButton() {
        const closeBtn = document.getElementById('sidebar-close-btn');
        if (closeBtn) {
            // Ensure it's properly styled and positioned
            closeBtn.style.position = 'relative';
            closeBtn.style.zIndex = '1090';
            closeBtn.style.pointerEvents = 'auto';
            closeBtn.style.cursor = 'pointer';
            
            console.log('Fixed existing close button styling');
        }
    }
    
    // Run the fix
    fixExistingCloseButton();
    
    // Add alternative close method - clicking on top-right area of sidebar
    function addAlternativeCloseMethod() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        // Create an invisible close area in the top-right corner
        let closeArea = document.getElementById('sidebar-close-area');
        if (!closeArea) {
            closeArea = document.createElement('div');
            closeArea.id = 'sidebar-close-area';
            closeArea.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                right: 0 !important;
                width: 80px !important;
                height: 80px !important;
                z-index: 1095 !important;
                cursor: pointer !important;
                background: transparent !important;
                pointer-events: auto !important;
            `;

            closeArea.addEventListener('click', function(e) {
                console.log('Close area clicked!');
                e.preventDefault();
                e.stopPropagation();

                const isMobile = window.innerWidth <= 991;
                if (isMobile && sidebar) {
                    sidebar.classList.remove('show', 'collapsed');
                    sidebar.classList.add('hide');
                    document.body.classList.remove('sidebar-open');
                    document.body.style.overflow = '';
                    console.log('Sidebar closed via close area');
                }
            });

            document.body.appendChild(closeArea);
            console.log('Alternative close area created');
        }

        // Show/hide close area based on sidebar visibility
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isMobile = window.innerWidth <= 991;
                    const isHidden = sidebar.classList.contains('hide');
                    const sidebarVisible = sidebar.classList.contains('show') || (!isHidden && isMobile);
                    closeArea.style.display = (sidebarVisible && isMobile) ? 'block' : 'none';
                    console.log('Close area visibility updated:', sidebarVisible && isMobile);
                }
            });
        });

        observer.observe(sidebar, {
            attributes: true,
            attributeFilter: ['class']
        });

        // Initial state
        const isMobile = window.innerWidth <= 991;
        const isHidden = sidebar.classList.contains('hide');
        const sidebarVisible = sidebar.classList.contains('show') || (!isHidden && isMobile);
        closeArea.style.display = (sidebarVisible && isMobile) ? 'block' : 'none';
        console.log('Initial close area state:', sidebarVisible && isMobile);
    }

    // Add the alternative close method
    addAlternativeCloseMethod();

    // Re-initialize on window resize
    window.addEventListener('resize', function() {
        setTimeout(initializeCloseButton, 100);
    });
});
