/**
 * Footer Responsive JavaScript
 * Makes footer respond to sidebar toggle states
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const footerContainer = document.querySelector('.footer-container');
    const mainContent = document.querySelector('.main-content');
    
    if (!sidebar || !footerContainer) {
        console.log('Footer responsive: Required elements not found');
        return;
    }
    
    // Function to update footer padding based on sidebar state
    function updateFooterPadding() {
        const isCollapsed = sidebar.classList.contains('collapsed');
        const isMobile = window.innerWidth <= 991.98;
        
        if (isMobile) {
            // Mobile: Equal padding
            footerContainer.style.paddingLeft = '30px';
            footerContainer.style.paddingRight = '30px';
        } else if (isCollapsed) {
            // Collapsed sidebar: Centered content with equal padding
            footerContainer.style.paddingLeft = '80px';
            footerContainer.style.paddingRight = '80px';
        } else {
            // Expanded sidebar: Account for 260px sidebar
            footerContainer.style.paddingLeft = '260px';
            footerContainer.style.paddingRight = '30px';
        }
        
        console.log(`Footer padding updated - Collapsed: ${isCollapsed}, Mobile: ${isMobile}`);
    }
    
    // Initial setup
    updateFooterPadding();
    
    // Listen for sidebar toggle events
    const navbarToggleBtn = document.getElementById('sidebar-toggle-navbar');
    if (navbarToggleBtn) {
        navbarToggleBtn.addEventListener('click', function() {
            // Wait for sidebar animation to complete
            setTimeout(updateFooterPadding, 100);
        });
    }
    
    // Listen for window resize events
    window.addEventListener('resize', function() {
        updateFooterPadding();
    });
    
    // Listen for sidebar state changes using MutationObserver
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                updateFooterPadding();
            }
        });
    });
    
    // Start observing sidebar for class changes
    observer.observe(sidebar, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    // Check localStorage for initial sidebar state
    const sidebarCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    if (sidebarCollapsed) {
        // Apply collapsed state immediately
        setTimeout(updateFooterPadding, 50);
    }
    
    // Listen for keyboard shortcut (Ctrl+B) that toggles sidebar
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'b') {
            setTimeout(updateFooterPadding, 100);
        }
    });
    
    console.log('Footer responsive script initialized');
});
