/**
 * Dashboard Animations Enhancement
 * Adds additional interactive animations and effects to the dashboard
 */

document.addEventListener('DOMContentLoaded', function() {
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
    const header = document.querySelector('.header');
    if (!header) return;
    
    window.addEventListener('mousemove', function(e) {
        const mouseX = e.clientX / window.innerWidth;
        const mouseY = e.clientY / window.innerHeight;
        
        const moveX = mouseX * 10 - 5;
        const moveY = mouseY * 10 - 5;
        
        header.style.transform = `translate(${moveX}px, ${moveY}px)`;
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