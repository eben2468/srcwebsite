<?php
/**
 * Remove Dropdown Scroll Buttons Fix
 * Include this file in pages where dropdown scroll buttons appear
 */
?>

<!-- Remove Dropdown Scroll Buttons CSS -->
<link rel="stylesheet" href="<?php echo isset($cssPath) ? $cssPath : '../css/'; ?>remove-dropdown-scroll-buttons.css">

<!-- Remove Dropdown Scroll Buttons JavaScript -->
<script src="<?php echo isset($jsPath) ? $jsPath : '../js/'; ?>remove-dropdown-scroll-buttons.js"></script>

<style>
/* Immediate CSS fix for dropdown scroll buttons */
.dropdown-menu {
    overflow: visible !important;
    max-height: none !important;
    scrollbar-width: none !important;
    -ms-overflow-style: none !important;
}

.dropdown-menu::-webkit-scrollbar {
    display: none !important;
}

/* Hide any scroll buttons immediately */
*[class*="scroll"],
*[id*="scroll"],
*[style*="position: fixed"][style*="right"] {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
}

/* Exception for legitimate elements */
.modal,
.modal-backdrop,
.tooltip,
.popover {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}
</style>

<script>
// Immediate JavaScript fix
(function() {
    // Remove scroll buttons immediately
    function immediateRemoval() {
        // Remove fixed positioned elements on the right
        const fixedElements = document.querySelectorAll('*[style*="position: fixed"][style*="right"]');
        fixedElements.forEach(el => {
            if (!el.className.includes('modal') && !el.className.includes('tooltip')) {
                el.style.display = 'none';
            }
        });
        
        // Remove scroll-related elements
        const scrollElements = document.querySelectorAll('*[class*="scroll"], *[id*="scroll"]');
        scrollElements.forEach(el => {
            if (!el.className.includes('overflow') && !el.className.includes('table')) {
                el.style.display = 'none';
            }
        });
        
        // Fix dropdown menus
        const dropdowns = document.querySelectorAll('.dropdown-menu');
        dropdowns.forEach(menu => {
            menu.style.overflow = 'visible';
            menu.style.maxHeight = 'none';
        });
    }
    
    // Run immediately
    immediateRemoval();
    
    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', immediateRemoval);
    }
    
    // Run on dropdown events
    document.addEventListener('click', function(e) {
        if (e.target.matches('.dropdown-toggle') || e.target.closest('.dropdown-toggle')) {
            setTimeout(immediateRemoval, 100);
        }
    });
})();
</script>