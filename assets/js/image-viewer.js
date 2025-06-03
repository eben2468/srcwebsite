/**
 * Image Viewer JavaScript
 * Handles image viewing functionality in modals
 */

document.addEventListener('DOMContentLoaded', function() {
    // Add image-modal class to all image modals
    const imageModals = document.querySelectorAll('#imageModal');
    imageModals.forEach(modal => {
        modal.classList.add('image-modal');
    });

    // Initialize zoom functionality for image modals
    initializeImageZoom();
});

/**
 * Initialize zoom functionality for images in modals
 */
function initializeImageZoom() {
    // Find all image modals
    const imageModals = document.querySelectorAll('.image-modal');
    
    imageModals.forEach(modal => {
        const modalBody = modal.querySelector('.modal-body');
        const img = modalBody ? modalBody.querySelector('img') : null;
        
        if (img) {
            // Create zoom controls if they don't exist
            if (!modal.querySelector('.image-zoom-controls')) {
                const zoomControls = document.createElement('div');
                zoomControls.className = 'image-zoom-controls';
                zoomControls.innerHTML = `
                    <button type="button" class="zoom-in" title="Zoom In">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button type="button" class="zoom-out" title="Zoom Out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button type="button" class="zoom-reset" title="Reset Zoom">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                `;
                modalBody.appendChild(zoomControls);
                
                // Initialize zoom level
                img.style.transform = 'scale(1)';
                img.style.transition = 'transform 0.3s ease';
                
                // Zoom in button
                zoomControls.querySelector('.zoom-in').addEventListener('click', function() {
                    const currentScale = parseFloat(img.style.transform.replace('scale(', '').replace(')', '') || 1);
                    const newScale = currentScale + 0.1;
                    img.style.transform = `scale(${newScale})`;
                });
                
                // Zoom out button
                zoomControls.querySelector('.zoom-out').addEventListener('click', function() {
                    const currentScale = parseFloat(img.style.transform.replace('scale(', '').replace(')', '') || 1);
                    const newScale = Math.max(0.5, currentScale - 0.1);
                    img.style.transform = `scale(${newScale})`;
                });
                
                // Reset zoom button
                zoomControls.querySelector('.zoom-reset').addEventListener('click', function() {
                    img.style.transform = 'scale(1)';
                });
                
                // Reset zoom when modal is closed
                modal.addEventListener('hidden.bs.modal', function() {
                    img.style.transform = 'scale(1)';
                });
            }
        }
    });
} 