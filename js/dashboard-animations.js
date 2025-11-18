/**
 * Dashboard Animations Enhancement
 * Adds additional interactive animations and effects to the dashboard
 */

document.addEventListener('DOMContentLoaded', function() {
    // Only run dashboard animations on dashboard pages
    const isDashboardPage = document.body.classList.contains('dashboard-page') || 
                           window.location.pathname.includes('dashboard') ||
                           document.querySelector('.dashboard-container') ||
                           document.querySelector('.stat-card');
    
    if (!isDashboardPage) {
        return; // Exit early if not on dashboard
    }
    
    // Initialize animations with a slight delay
    setTimeout(initializeAnimations, 100);
    
    // Add scroll animation effects
    window.addEventListener('scroll', handleScrollAnimations);
    
    // Add hover effects to stat cards
    initializeCardEffects();
    
    // Add parallax effect to header
    initializeParallaxHeader();
});

/**
 * Initialize animations for dashboard elements
 */
function initializeAnimations() {
    // Add entrance animations for all stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate-fadeIn');
    });
    
    // Add animated counters for stat values
    initializeCounters();
    
    // Add subtle hover animations to action buttons
    const actionButtons = document.querySelectorAll('.btn-action, .quick-action-btn');
    actionButtons.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.querySelector('i')?.classList.add('animate-float');
        });
        btn.addEventListener('mouseleave', function() {
            this.querySelector('i')?.classList.remove('animate-float');
        });
    });
}

/**
 * Initialize animated counters for stat values
 */
function initializeCounters() {
    const statValues = document.querySelectorAll('.stat-card-value');
    
    statValues.forEach(valueElement => {
        const finalValue = parseInt(valueElement.textContent, 10);
        if (isNaN(finalValue)) return;
        
        // Set initial value to 0
        valueElement.textContent = '0';
        
        // Animate count up
        let currentValue = 0;
        const duration = 1500; // milliseconds
        const interval = 50; // update every 50ms
        const steps = duration / interval;
        const increment = finalValue / steps;
        
        const counter = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                valueElement.textContent = finalValue;
                clearInterval(counter);
            } else {
                valueElement.textContent = Math.floor(currentValue);
            }
        }, interval);
    });
}

/**
 * Initialize parallax effect for the header
 */
function initializeParallaxHeader() {
    const header = document.querySelector('.dashboard-header, .header');
    if (!header) return;
    
    // Only apply parallax on larger screens and dashboard pages
    if (window.innerWidth < 768) return;
    
    let isMouseMoving = false;
    let mouseTimeout;
    
    window.addEventListener('mousemove', function(e) {
        // Throttle the mousemove event
        if (!isMouseMoving) {
            isMouseMoving = true;
            
            requestAnimationFrame(() => {
                const mouseX = e.clientX / window.innerWidth;
                const mouseY = e.clientY / window.innerHeight;
                
                const moveX = mouseX * 5 - 2.5; // Reduced movement
                const moveY = mouseY * 5 - 2.5; // Reduced movement
                
                header.style.transform = `translate(${moveX}px, ${moveY}px)`;
                header.style.transition = 'transform 0.1s ease-out';
                
                isMouseMoving = false;
            });
        }
        
        // Reset position after mouse stops moving
        clearTimeout(mouseTimeout);
        mouseTimeout = setTimeout(() => {
            header.style.transform = 'translate(0px, 0px)';
            header.style.transition = 'transform 0.3s ease-out';
        }, 1000);
    });
}

/**
 * Initialize hover effects for cards
 */
function initializeCardEffects() {
    const cards = document.querySelectorAll('.content-card, .stat-card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('card-active');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('card-active');
        });
    });
}

/**
 * Handle scroll-based animations
 */
function handleScrollAnimations() {
    const animatedElements = document.querySelectorAll('.content-card, .quick-action-btn');
    const windowHeight = window.innerHeight;
    
    animatedElements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        
        // If element is in viewport
        if (elementTop < windowHeight * 0.9) {
            element.classList.add('visible');
        }
    });
}

/**
 * Add a subtle pulse effect to cards
 */
function addPulseEffect() {
    const elements = document.querySelectorAll('.stat-card, .badge');
    
    elements.forEach(element => {
        element.addEventListener('click', function() {
            this.classList.add('animate-pulse');
            
            // Remove the class after animation completes
            setTimeout(() => {
                this.classList.remove('animate-pulse');
            }, 2000);
        });
    });
} 