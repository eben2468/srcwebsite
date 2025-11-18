/**
 * Portfolio Cards Enhancement Script
 * Adds dynamic effects and interactions to portfolio cards
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize portfolio cards
    initPortfolioCards();
    
    // Initialize profile image enhancement
    enhanceProfileImages();
    
    // Force fix for specific problematic cards
    fixProblemCards();
    
    // Re-initialize on window resize
    window.addEventListener('resize', debounce(function() {
        initPortfolioCards();
        enhanceProfileImages();
        fixProblemCards();
    }, 250));
});

/**
 * Initialize all portfolio cards enhancements
 */
function initPortfolioCards() {
    const portfolioCards = document.querySelectorAll('.portfolio-card');
    
    portfolioCards.forEach((card, index) => {
        // Add hover effect
        card.addEventListener('mouseenter', handleCardHover);
        card.addEventListener('mouseleave', handleCardLeave);
        
        // Apply direct styling based on header content
        applyDirectStyling(card);
        
        // Add animation delay based on index if not already set
        if (!card.style.animationDelay) {
            card.style.animationDelay = `${0.1 * (index % 9)}s`;
        }
    });
}

/**
 * Apply direct styling based on card header content
 */
function applyDirectStyling(card) {
    const header = card.querySelector('.card-header');
    if (!header) return;
    
    const headerText = header.textContent.trim();
    let role = '';
    let color = '';
    let gradient = '';
    
    // Determine role and color based on header text
    if (headerText.includes('President') && !headerText.includes('Vice')) {
        role = 'President';
        color = '#4e73df';
        gradient = 'linear-gradient(135deg, #4e73df, #224abe)';
    } else if (headerText.includes('Vice President')) {
        role = 'Vice President';
        color = '#1cc88a';
        gradient = 'linear-gradient(135deg, #1cc88a, #13855c)';
    } else if (headerText.includes('Secretary')) {
        role = 'Secretary';
        color = '#f6c23e';
        gradient = 'linear-gradient(135deg, #f6c23e, #dda20a)';
    } else if (headerText.includes('Finance') || headerText.includes('Treasurer')) {
        role = 'Finance';
        color = '#36b9cc';
        gradient = 'linear-gradient(135deg, #36b9cc, #258391)';
    } else if (headerText.includes('Sport')) {
        role = 'Sports';
        color = '#e74a3b';
        gradient = 'linear-gradient(135deg, #e74a3b, #be2617)';
    } else if (headerText.includes('Welfare')) {
        role = 'Welfare';
        color = '#6f42c1';
        gradient = 'linear-gradient(135deg, #6f42c1, #4e2a84)';
    } else if (headerText.includes('Women')) {
        role = 'Women';
        color = '#e83e8c';
        gradient = 'linear-gradient(135deg, #e83e8c, #b52e6f)';
    } else if (headerText.includes('Chaplain')) {
        role = 'Chaplain';
        color = '#5a5c69';
        gradient = 'linear-gradient(135deg, #5a5c69, #373840)';
    } else if (headerText.includes('Senate')) {
        role = 'Senate';
        color = '#ff9800';
        gradient = 'linear-gradient(135deg, #ff9800, #e65100)';
    } else if (headerText.includes('Editor')) {
        role = 'Editor';
        color = '#009688';
        gradient = 'linear-gradient(135deg, #009688, #00695c)';
    } else if (headerText.includes('Public Relations') || headerText.includes('PRO')) {
        role = 'PRO';
        color = '#9c27b0';
        gradient = 'linear-gradient(135deg, #9c27b0, #6a0080)';
    } else {
        // Default color for unknown roles
        role = headerText.split(' ')[0];
        color = '#607d8b';
        gradient = 'linear-gradient(135deg, #607d8b, #455a64)';
    }
    
    // Set data-role attribute
    card.setAttribute('data-role', role);
    
    // Apply styles directly
    card.style.borderLeftColor = color;
    header.style.background = gradient;
    header.style.color = 'white';
    
    // Style responsibilities title underline
    const titleUnderline = card.querySelector('.responsibilities-title::after');
    if (titleUnderline) {
        titleUnderline.style.background = color;
    }
    
    // Style list icons
    const listIcons = card.querySelectorAll('.responsibilities-list li i');
    listIcons.forEach(icon => {
        icon.style.color = color;
    });
    
    // Style more items link
    const moreItemsLink = card.querySelector('.responsibilities-list li.more-items a');
    if (moreItemsLink) {
        moreItemsLink.style.color = color;
    }
    
    // Style primary button
    const primaryButton = card.querySelector('.card-actions .btn-primary');
    if (primaryButton) {
        primaryButton.style.background = gradient;
        primaryButton.style.boxShadow = `0 4px 15px ${color}33`;
        primaryButton.style.border = 'none';
    }
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
    
    // Reset transform
    card.style.transform = '';
    card.style.zIndex = '';
    
    // Reset opacity for all cards
    allCards.forEach(otherCard => {
        otherCard.style.opacity = '';
    });
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

/**
 * Force fix for specific problematic cards (Senate, Editor, PRO)
 */
function fixProblemCards() {
    // Get all cards
    const cards = document.querySelectorAll('.portfolio-card');
    
    // Check each card's header text and apply specific styling
    cards.forEach(card => {
        const header = card.querySelector('.card-header');
        if (!header) return;
        
        const headerText = header.textContent.trim();
        
        // Senate card
        if (headerText.includes('Senate')) {
            console.log('Fixing Senate card');
            const color = '#ff9800';
            const gradient = 'linear-gradient(135deg, #ff9800, #e65100)';
            applyCardStyle(card, header, color, gradient);
        }
        
        // Editor card
        if (headerText.includes('Editor')) {
            console.log('Fixing Editor card');
            const color = '#009688';
            const gradient = 'linear-gradient(135deg, #009688, #00695c)';
            applyCardStyle(card, header, color, gradient);
        }
        
        // PRO card
        if (headerText.includes('Public Relations') || headerText.includes('PRO')) {
            console.log('Fixing PRO card');
            const color = '#9c27b0';
            const gradient = 'linear-gradient(135deg, #9c27b0, #6a0080)';
            applyCardStyle(card, header, color, gradient);
        }
    });
}

/**
 * Apply specific styling to a card
 */
function applyCardStyle(card, header, color, gradient) {
    // Force override any existing styles
    header.style.setProperty('background', gradient, 'important');
    header.style.setProperty('color', 'white', 'important');
    
    // Style card subtitle underline
    const cardSubtitle = card.querySelector('.card-subtitle');
    if (cardSubtitle) {
        // Add an underline element if it doesn't exist
        if (!cardSubtitle.querySelector('.subtitle-underline')) {
            const underline = document.createElement('div');
            underline.className = 'subtitle-underline';
            underline.style.cssText = `
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 40px;
                height: 3px;
                background: ${color} !important;
                border-radius: 3px;
            `;
            cardSubtitle.style.position = 'relative';
            cardSubtitle.style.paddingBottom = '1rem';
            cardSubtitle.appendChild(underline);
        }
    }
    
    // Style responsibilities title underline
    const responsibilitiesTitle = card.querySelector('.responsibilities-title');
    if (responsibilitiesTitle) {
        responsibilitiesTitle.style.setProperty('border-bottom-color', '#edf2f7', 'important');
        // Add an underline element if it doesn't exist
        if (!responsibilitiesTitle.querySelector('.underline-element')) {
            const underline = document.createElement('div');
            underline.className = 'underline-element';
            underline.style.cssText = `
                position: absolute;
                bottom: -2px;
                left: 50%;
                transform: translateX(-50%);
                width: 50px;
                height: 2px;
                background: ${color} !important;
            `;
            responsibilitiesTitle.style.position = 'relative';
            responsibilitiesTitle.appendChild(underline);
        }
    }
    
    // Style list icons
    const listIcons = card.querySelectorAll('.responsibilities-list li i');
    listIcons.forEach(icon => {
        icon.style.setProperty('color', color, 'important');
    });
    
    // Style more items link
    const moreItemsLink = card.querySelector('.responsibilities-list li.more-items a');
    if (moreItemsLink) {
        moreItemsLink.style.setProperty('color', color, 'important');
        moreItemsLink.style.setProperty('background-color', `${color}1a`, 'important'); // 10% opacity
        
        // Add hover effect
        moreItemsLink.addEventListener('mouseenter', function() {
            this.style.setProperty('background-color', `${color}33`, 'important'); // 20% opacity
            this.style.setProperty('transform', 'translateY(-2px)', 'important');
        });
        
        moreItemsLink.addEventListener('mouseleave', function() {
            this.style.setProperty('background-color', `${color}1a`, 'important'); // 10% opacity
            this.style.setProperty('transform', 'translateY(0)', 'important');
        });
    }
    
    // Style primary button
    const primaryButton = card.querySelector('.card-actions .btn-primary');
    if (primaryButton) {
        primaryButton.style.setProperty('background', gradient, 'important');
        primaryButton.style.setProperty('box-shadow', `0 4px 15px ${color}33`, 'important');
        primaryButton.style.setProperty('border', 'none', 'important');
    }
} 