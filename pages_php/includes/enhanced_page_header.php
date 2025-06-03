<?php
/**
 * Enhanced Page Header Template
 * Provides consistent header styling with animations across all pages
 * 
 * @param string $pageTitle The title of the page to display
 * @param string $pageIcon The Font Awesome icon class for the page (e.g., 'fa-vote-yea')
 * @param array $actions Optional array of action buttons [['url' => '...', 'icon' => '...', 'text' => '...', 'class' => '...'], ...]
 */

// Default icon if not specified
$pageIcon = $pageIcon ?? 'fa-file-alt';

// Get current date
$currentDate = date('l, F j, Y');
?>

<div class="header">
    <h1 class="page-title">
        <?php if (!empty($pageIcon)): ?>
            <i class="fas <?php echo $pageIcon; ?> me-2"></i>
        <?php endif; ?>
        <?php echo $pageTitle; ?>
    </h1>
    
    <div class="header-actions">
        <div class="header-date">
            <i class="far fa-calendar-alt"></i>
            <span><?php echo $currentDate; ?></span>
        </div>
        
        <?php if (!empty($actions)): ?>
            <?php foreach ($actions as $action): ?>
                <?php
                // Build attributes string
                $attributesStr = '';
                if (isset($action['attributes'])) {
                    $attributesStr = ' ' . $action['attributes'];
                }
                
                // Add special header-action-btn class for better targeting
                $btnClass = $action['class'] ?? 'btn-primary';
                $btnClass .= ' header-action-btn';
                
                // Check if this is a modal toggle button
                $isModal = strpos($attributesStr, 'data-bs-toggle="modal"') !== false;
                if ($isModal) {
                    $btnClass .= ' header-modal-btn';
                    // Extract modal target ID
                    preg_match('/data-bs-target="#([^"]+)"/', $attributesStr, $matches);
                    $modalId = isset($matches[1]) ? $matches[1] : '';
                }
                
                // Get button text and icon
                $btnText = $action['text'];
                $btnIcon = $action['icon'];
                
                // If it's a modal button, use our reliable button instead
                if ($isModal && !empty($modalId)):
                ?>
                <button type="button" class="btn <?php echo $btnClass; ?> enhanced-modal-btn animate-pulse ms-2" 
                        onclick="openModal('<?php echo $modalId; ?>')" 
                        style="cursor: pointer !important; pointer-events: auto !important; position: relative; z-index: 9999;">
                    <i class="fas <?php echo $btnIcon; ?> me-2"></i><?php echo $btnText; ?>
                </button>
                <?php else: ?>
                <a href="<?php echo $action['url']; ?>" class="btn <?php echo $btnClass; ?> animate-pulse ms-2"<?php echo $attributesStr; ?> style="position: relative; z-index: 1050; pointer-events: auto !important;">
                    <i class="fas <?php echo $btnIcon; ?> me-2"></i><?php echo $btnText; ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add script to ensure header buttons work -->
<script>
// Function to open modals - globally accessible
function openModal(modalId) {
    try {
        console.log('Opening modal:', modalId);
        const modalElement = document.getElementById(modalId);
        if (!modalElement) {
            console.error('Modal element not found:', modalId);
            return;
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalInstance = new bootstrap.Modal(modalElement);
            modalInstance.show();
        } else {
            // Fallback: manually show the modal using CSS
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
            
            // Create backdrop if it doesn't exist
            let backdrop = document.querySelector('.modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
        }
    } catch (error) {
        console.error('Error showing modal:', error);
        alert('Error showing modal. Please try again or refresh the page.');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Fix for enhanced modal buttons
    const enhancedModalBtns = document.querySelectorAll('.enhanced-modal-btn');
    console.log('Found', enhancedModalBtns.length, 'enhanced modal buttons');
    
    // Add additional click handlers for older browsers that might not respect the onclick attribute
    enhancedModalBtns.forEach(function(btn) {
        const originalOnClick = btn.onclick;
        btn.onclick = null; // Remove original onclick
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Extract modal ID from the button's onclick attribute or data attribute
            const onclickStr = btn.getAttribute('onclick') || '';
            const modalId = btn.getAttribute('data-modal-id') || onclickStr.match(/openModal\(['"]([^'"]+)['"]\)/)?.[1];
            
            if (modalId) {
                openModal(modalId);
            } else if (typeof originalOnClick === 'function') {
                originalOnClick.call(btn, e);
            }
        });
    });
});
</script>

<style>
/* Enhanced styling for modal buttons */
.enhanced-modal-btn {
    cursor: pointer !important;
    pointer-events: auto !important;
    user-select: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.enhanced-modal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Ensure a larger click area */
.enhanced-modal-btn:before {
    content: "";
    position: absolute;
    top: -10px;
    left: -10px;
    right: -10px;
    bottom: -10px;
    z-index: -1;
}
</style> 