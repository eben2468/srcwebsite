/**
 * Portfolio Cards Enhancement Script
 * Adds dynamic effects and interactions to portfolio cards
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize portfolio cards
    initPortfolioCards();
    
    // Initialize profile image enhancement
    enhanceProfileImages();
    
    // Re-initialize on window resize
    window.addEventListener('resize', debounce(function() {
        initPortfolioCards();
        enhanceProfileImages();
    }, 250));
});

/**
 * Initialize all portfolio card enhancements
 */
function initPortfolioCards() {
    const portfolioCards = document.querySelectorAll('.portfolio-card');
    
    portfolioCards.forEach((card, index) => {
        // Add hover effect
        card.addEventListener('mouseenter', handleCardHover);
        card.addEventListener('mouseleave', handleCardLeave);
        
        // Add role-specific classes if not already set
        if (!card.dataset.role && card.querySelector('.card-header')) {
            const roleTitle = card.querySelector('.card-header').textContent.trim();
            setRoleBasedStyles(card, roleTitle);
        }
        
        // Add animation delay based on index if not already set
        if (!card.style.animationDelay) {
            card.style.animationDelay = `${0.1 * (index % 9)}s`;
        }
    });
}

/**
 * Handle card hover effect
 */
function handleCardHover(e) {
    const card = e.currentTarget;
    const otherCards = document.querySelectorAll('.portfolio-card:not(:hover)');
    
    // Add a slight scale effect to the hovered card
    card.style.transform = 'translateY(-10px) scale(1.02)';
    card.style.zIndex = '10';
    
    // Slightly dim other cards
    otherCards.forEach(otherCard => {
        otherCard.style.opacity = '0.8';
    });
}

/**
 * Handle card leave effect
 */
function handleCardLeave(e) {
    const card = e.currentTarget;
    const allCards = document.querySelectorAll('.portfolio-card');
    
    // Reset the hovered card
    card.style.transform = '';
    card.style.zIndex = '';
    
    // Reset all cards
    allCards.forEach(otherCard => {
        otherCard.style.opacity = '';
    });
}

/**
 * Set role-based styles based on title
 */
function setRoleBasedStyles(card, title) {
    // Extract the main role from the title
    let role = '';
    
    if (title.includes('President')) {
        role = 'President';
    } else if (title.includes('Vice')) {
        role = 'Vice President';
    } else if (title.includes('Secretary')) {
        role = 'Secretary';
    } else if (title.includes('Finance') || title.includes('Treasurer')) {
        role = 'Finance';
    } else if (title.includes('Sport')) {
        role = 'Sports';
    } else if (title.includes('Welfare')) {
        role = 'Welfare';
    } else if (title.includes('Women')) {
        role = 'Women';
    } else if (title.includes('Chaplain')) {
        role = 'Chaplain';
    }
    
    // Set data attribute
    if (role) {
        card.setAttribute('data-role', role);
    }
}

/**
 * Enhance profile images to properly fill their containers
 */
function enhanceProfileImages() {
    const profileImages = document.querySelectorAll('.member-image.fill-image');
    
    profileImages.forEach(img => {
        // Wait for image to load
        if (img.complete) {
            adjustImagePosition(img);
        } else {
            img.onload = function() {
                adjustImagePosition(img);
            };
        }
    });
}

/**
 * Adjust image position to ensure it fills the circular container
 */
function adjustImagePosition(img) {
    const container = img.parentElement;
    const imgWidth = img.naturalWidth;
    const imgHeight = img.naturalHeight;
    
    // If image is already loaded and has dimensions
    if (imgWidth && imgHeight) {
        // Calculate aspect ratio
        const imgRatio = imgWidth / imgHeight;
        
        // Determine if image is portrait or landscape
        if (imgRatio < 1) {
            // Portrait image - make sure width fills container
            img.style.width = '120%';
            img.style.height = 'auto';
        } else {
            // Landscape image - make sure height fills container
            img.style.height = '120%';
            img.style.width = 'auto';
        }
    }
}

/**
 * Debounce function for resize events
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
} 