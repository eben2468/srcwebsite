<?php
/**
 * Mobile Modal Fix Include
 * Include this file in pages that use modals to fix mobile backdrop issues
 */
?>

<!-- Mobile Modal Backdrop Fix CSS -->
<link rel="stylesheet" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/../css/mobile-modal-backdrop-fix.css">

<!-- Mobile Modal Backdrop Fix JavaScript -->
<script src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/../js/mobile-modal-backdrop-fix.js"></script>

<script>
// Additional mobile modal fixes
document.addEventListener('DOMContentLoaded', function() {
    // Apply mobile modal fixes to all existing modals
    const modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        // Disable backdrop on mobile
        if (window.innerWidth <= 768) {
            modal.setAttribute('data-bs-backdrop', 'false');
            modal.setAttribute('data-bs-keyboard', 'true');
        }
        
        // Fix modal positioning
        const modalDialog = modal.querySelector('.modal-dialog');
        if (modalDialog) {
            modalDialog.style.pointerEvents = 'auto';
            modalDialog.style.position = 'relative';
        }
    });
    
    // Emergency fix function for stuck modals
    window.fixStuckModal = function() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            modal.classList.remove('show');
            modal.style.display = 'none';
        });
        
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        console.log('Emergency modal fix applied');
    };
});
</script>