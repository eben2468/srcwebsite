<?php
/**
 * Modern Page Header Template
 * Provides consistent modern header styling with animations across all pages
 * 
 * @param string $pageTitle The title of the page to display
 * @param string $pageIcon The Font Awesome icon class for the page (e.g., 'fa-tachometer-alt')
 * @param string $pageDescription Optional description text for the page
 * @param array $actions Optional array of action buttons [['url' => '...', 'icon' => '...', 'text' => '...', 'class' => '...'], ...]
 */

// Default values if not specified
$pageTitle = $pageTitle ?? 'Page';
$pageIcon = $pageIcon ?? 'fa-file-alt';
$pageDescription = $pageDescription ?? '';
$actions = $actions ?? [];
$backButton = $backButton ?? null;

// Get current date
$currentDate = date('l, F j, Y');
?>

<div class="header animate__animated animate__fadeInDown">
    <div class="header-content">
        <div class="header-main">
            <h1 class="page-title">
                <?php if (!empty($pageIcon)): ?>
                    <i class="fas <?php echo htmlspecialchars($pageIcon); ?> me-2"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($pageTitle); ?>
            </h1>
            
            <?php if (!empty($pageDescription)): ?>
                <p class="page-description"><?php echo htmlspecialchars($pageDescription); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="header-actions">
            <?php if (!empty($backButton)): ?>
                <a href="<?php echo htmlspecialchars($backButton['href']); ?>"
                   class="btn btn-outline-light">
                    <?php if (!empty($backButton['icon'])): ?>
                        <i class="fas <?php echo htmlspecialchars($backButton['icon']); ?> me-2"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($backButton['text']); ?>
                </a>
            <?php endif; ?>

            <?php if (!empty($actions)): ?>
                <?php foreach ($actions as $action): ?>
                    <?php if (isset($action['data-bs-toggle'])): ?>
                        <button type="button"
                                class="btn <?php echo htmlspecialchars($action['class'] ?? 'btn-primary'); ?>"
                                data-bs-toggle="<?php echo htmlspecialchars($action['data-bs-toggle']); ?>"
                                data-bs-target="<?php echo htmlspecialchars($action['data-bs-target']); ?>">
                            <?php if (!empty($action['icon'])): ?>
                                <i class="fas <?php echo htmlspecialchars($action['icon']); ?> me-2"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($action['text']); ?>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo htmlspecialchars($action['url']); ?>"
                           class="btn <?php echo htmlspecialchars($action['class'] ?? 'btn-primary'); ?>"
                           <?php if (!empty($action['id'])): ?>id="<?php echo htmlspecialchars($action['id']); ?>"<?php endif; ?>>
                            <?php if (!empty($action['icon'])): ?>
                                <i class="fas <?php echo htmlspecialchars($action['icon']); ?> me-2"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($action['text']); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 3rem;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    position: relative;
    width: 100%;
    max-width: none;
    z-index: 1;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    min-height: 120px;
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 0;
}

.header-main {
    text-align: center;
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 0;
    padding: 0 1rem;
}

.page-title {
    font-size: 2.8rem;
    font-weight: 700;
    margin: 0 0 0.6rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    letter-spacing: -0.5px;
}

.page-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.1rem;
    font-weight: 400;
    line-height: 1.4;
    text-align: center;
}

.header-actions {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    flex: 0 0 auto;
    align-items: stretch;
    max-width: 220px;
    min-width: 180px;
}

/* Custom scrollbar for header actions */
.header-actions::-webkit-scrollbar {
    width: 4px;
}

.header-actions::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
}

.header-actions::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
}

.header-actions::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

.header-actions .btn {
    background: rgba(255, 255, 255, 0.15);
    border: 2px solid rgba(255, 255, 255, 0.4);
    color: white;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    white-space: nowrap;
    padding: 0.7rem 1.4rem;
    font-size: 0.9rem;
    text-align: center;
    width: 100%;
    min-width: 150px;
    border-radius: 12px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    /* Ensure button is clickable */
    pointer-events: auto !important;
    cursor: pointer !important;
    position: relative !important;
    z-index: 1060 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.header-actions .btn:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.6);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.header-actions .btn-primary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
}

.header-actions .btn-primary:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
    border-color: rgba(255, 255, 255, 0.7);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

/* Improve header content spacing and alignment */
.header-content {
    padding: 0 2rem;
    gap: 2rem;
}

@media (max-width: 768px) {
    .header {
        margin-top: 50px; /* 70px navbar + 30px spacing */
        margin-bottom: 1.5rem;
        padding: 2rem 1.5rem;
        border-radius: 8px;
        
    }

    .header-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        padding: 0;
        justify-content: center;
    }

    .header-main {
        width: 100%;
        text-align: center;
        margin-bottom: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex: none;
    }

    .header-actions {
        flex: none;
        width: 100%;
        justify-content: center;
        margin-top: 0;
        flex-direction: row;
        flex-wrap: wrap;
        max-width: none;
        display: flex;
        align-items: center;
    }

    .header-actions .btn {
        width: auto;
        max-width: 280px;
        min-width: 200px;
    }

    .page-title {
        font-size: 2rem;
        text-align: center !important;
        width: 100%;
        margin: 0 auto 0.5rem auto;
        display: block;
    }
    
    .page-description {
        font-size: 1.1rem;
        text-align: center !important;
        width: 100%;
        margin: 0;
        display: block;
    }
}

@media (max-width: 480px) {
    .header {
        margin-left: 0.45rem !important;
        margin-right: 0.45rem !important;
        margin-top: 55px !important; /* 65px navbar + 30px spacing */
        margin-bottom: 15px !important;
        padding: 1.2rem 1rem !important;
        width: calc(100% - 1.5rem) !important;
        max-width: none !important;
        box-sizing: border-box !important;
    }
    
    .header-content {
        padding: 0 !important;
        justify-content: center !important;
        align-items: center !important;
    }
    
    .header-main {
        text-align: center !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
    }
    
    .page-title {
        text-align: center !important;
        font-size: 1.4rem !important;
        margin: 0 auto !important;
        display: block !important;
        width: 100% !important;
    }
    
    .page-description {
        text-align: center !important;
        margin: 0.5rem auto 0 auto !important;
        display: block !important;
        width: 100% !important;
    }
}

@media (max-width: 375px) {
    .header {
        margin-left: 0.55rem !important;
        margin-right: 0.55rem !important;
        margin-top: 40px !important; /* 60px navbar + 30px spacing */
        margin-bottom: 15px !important;
        padding: 1rem !important;
        width: calc(100% - 1.5rem) !important;
        max-width: none !important;
        box-sizing: border-box !important;
    }
    
    .header-content {
        padding: 0 !important;
        justify-content: center !important;
        align-items: center !important;
    }
    
    .header-main {
        text-align: center !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
    }
    
    .page-title {
        text-align: center !important;
        font-size: 1.3rem !important;
        margin: 0 auto !important;
        display: block !important;
        width: 100% !important;
    }
    
    .page-description {
        text-align: center !important;
        font-size: 1rem !important;
        margin: 0.5rem auto 0 auto !important;
        display: block !important;
        width: 100% !important;
    }
}

@media (max-width: 320px) {
    .header {
        margin-left: 0.55rem !important;
        margin-right: 0.55rem !important;
        margin-top: 40px !important; /* 60px navbar + 30px spacing */
        margin-bottom: 15px !important;
        padding: 0.8rem !important;
        width: calc(100% - 1.5rem) !important;
        max-width: none !important;
        box-sizing: border-box !important;
    }
    
    .header-content {
        padding: 0 !important;
        justify-content: center !important;
        align-items: center !important;
    }
    
    .header-main {
        text-align: center !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
    }
    
    .page-title {
        text-align: center !important;
        font-size: 1.2rem !important;
        margin: 0 auto !important;
        display: block !important;
        width: 100% !important;
    }
    
    .page-description {
        text-align: center !important;
        font-size: 0.95rem !important;
        margin: 0.5rem auto 0 auto !important;
        display: block !important;
        width: 100% !important;
    }
}

/* Medium screens - maintain vertical layout */
@media (max-width: 1024px) and (min-width: 769px) {
    .header-actions {
        max-width: 200px;
        gap: 0.4rem;
    }

    .header-actions .btn {
        font-size: 0.8rem;
        padding: 0.4rem 0.6rem;
        min-width: 140px;
    }
}

/* Small desktop screens - maintain vertical layout */
@media (max-width: 1200px) and (min-width: 1025px) {
    .header-actions {
        max-width: 200px;
    }

    .header-actions .btn {
        font-size: 0.82rem;
        min-width: 140px;
    }
}

/* Override any container constraints for full-width header */
.header {
    max-width: none !important;
}

/* Animation classes */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translate3d(0, -100%, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

.animate__animated {
    animation-duration: 0.6s;
    animation-fill-mode: both;
}

.animate__fadeInDown {
    animation-name: fadeInDown;
}
</style>

<script>
// Ensure header buttons are clickable, especially modal triggers
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Modern page header: Initializing button functionality...');
    
    // Find all header action buttons
    const headerButtons = document.querySelectorAll('.header-actions .btn, .header-actions button');
    
    headerButtons.forEach(function(btn, index) {
        console.log(`Button ${index + 1}:`, {
            text: btn.textContent.trim(),
            hasDataBsToggle: btn.hasAttribute('data-bs-toggle'),
            hasDataBsTarget: btn.hasAttribute('data-bs-target'),
            targetModal: btn.getAttribute('data-bs-target')
        });
        
        // Ensure button is properly configured
        btn.style.pointerEvents = 'auto';
        btn.style.cursor = 'pointer';
        btn.style.position = 'relative';
        btn.style.zIndex = '1060';
        
        // If it's a modal trigger button, add extra handling
        if (btn.hasAttribute('data-bs-toggle') && btn.getAttribute('data-bs-toggle') === 'modal') {
            const modalTarget = btn.getAttribute('data-bs-target');
            
            btn.addEventListener('click', function(e) {
                console.log('🖱️ Modal trigger button clicked:', modalTarget);
                
                // Ensure the event is not prevented
                e.stopPropagation();
                
                // Check if Bootstrap is loaded
                if (typeof bootstrap === 'undefined') {
                    console.error('❌ Bootstrap is not loaded!');
                    return;
                }
                
                // Find the target modal
                const modal = document.querySelector(modalTarget);
                if (!modal) {
                    console.error('❌ Modal not found:', modalTarget);
                    return;
                }
                
                console.log('✅ Modal found, attempting to show:', modalTarget);
                
                // Get or create modal instance
                let modalInstance = bootstrap.Modal.getInstance(modal);
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(modal, {
                        backdrop: false, // Consistent with our modal fix
                        keyboard: true,
                        focus: true
                    });
                    console.log('✅ New modal instance created');
                } else {
                    console.log('✅ Existing modal instance found');
                }
                
                // Show the modal
                modalInstance.show();
                console.log('✅ Modal show() called');
            });
            
            console.log('✅ Modal trigger button configured:', modalTarget);
        }
    });
    
    console.log('✅ Modern page header: Button initialization complete');
});
</script>
