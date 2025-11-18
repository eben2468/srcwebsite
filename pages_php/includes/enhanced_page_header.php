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
                
                // Check if this is a modal toggle button
                $isModal = strpos($attributesStr, 'data-bs-toggle="modal"') !== false;
                if ($isModal) {
                    $btnClass .= ' header-modal-btn';
                    // Extract modal target ID
                    preg_match('/data-bs-target="#([^"]+)"/', $attributesStr, $matches);
                    $modalId = isset($matches[1]) ? $matches[1] : '';
                }
                
                // Check if this is a dropdown button
                $isDropdown = strpos($attributesStr, 'data-bs-toggle="dropdown"') !== false;
                
                // Get button text and icon
                $btnText = $action['label'] ?? $action['text'] ?? '';
                $btnIcon = $action['icon'] ?? '';
                
                // If it's a dropdown button
                if ($isDropdown && isset($action['dropdown'])):
                ?>
                <div class="btn-group ms-2">
                    <button type="button" class="btn <?php echo $btnClass; ?>"<?php echo $attributesStr; ?>>
                        <?php echo $btnText; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($action['dropdown'] as $dropdownItem): ?>
                            <?php
                            $dropdownAttributesStr = '';
                            if (isset($dropdownItem['attributes'])) {
                                $dropdownAttributesStr = ' ' . $dropdownItem['attributes'];
                            }
                            ?>
                            <a class="dropdown-item" href="<?php echo $dropdownItem['url']; ?>"<?php echo $dropdownAttributesStr; ?>>
                                <?php echo $dropdownItem['label']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
                // If it's a modal button, use our reliable button instead
                elseif ($isModal && !empty($modalId)):
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
    
    // Ensure dropdown functionality works
    const dropdownToggles = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    console.log('Found', dropdownToggles.length, 'dropdown toggles');
    
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Find parent btn-group and toggle dropdown
            const btnGroup = this.closest('.btn-group');
            if (btnGroup) {
                btnGroup.classList.toggle('show');
                const dropdown = btnGroup.querySelector('.dropdown-menu');
                if (dropdown) {
                    dropdown.classList.toggle('show');
                }
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const openDropdowns = document.querySelectorAll('.btn-group.show');
        openDropdowns.forEach(function(dropdown) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
                const menu = dropdown.querySelector('.dropdown-menu');
                if (menu) {
                    menu.classList.remove('show');
                }
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

/* Dropdown menu styling */
.btn-group {
    position: relative;
    z-index: 1050;
}

.dropdown-menu {
    padding: 0.5rem 0;
    margin: 0.125rem 0 0;
    border: none;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.dropdown-item {
    padding: 0.5rem 1.5rem;
    transition: background-color 0.2s ease;
}

.dropdown-item:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.dropdown-item:active {
    background-color: rgba(0, 0, 0, 0.1);
}

/* Fix for dropdown positioning */
.dropdown-menu-end {
    right: 0;
    left: auto;
}

/* Show dropdown when active */
.btn-group.show .dropdown-menu {
    display: block;
}

/* Additional button styling */
.btn {
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
</style> 
