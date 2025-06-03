/**
 * SRC Website - Index Page Animations
 * Advanced animations and interactive effects for the homepage
 */

document.addEventListener('DOMContentLoaded', function() {
    // Particle animation for the hero section
    initParticles();
    
    // Add hover effects to feature cards
    initFeatureCards();
    
    // Add scroll animations
    initScrollEffects();
    
    // Add typing effect to hero title
    initTypingEffect();
    
    // Ensure buttons are clickable
    ensureButtonsClickable();
});

/**
 * Initialize particle animation in hero section
 */
function initParticles() {
    const heroSection = document.querySelector('.hero-section');
    if (!heroSection) return;
    
    // Create particle container
    const particleContainer = document.createElement('div');
    particleContainer.className = 'particle-container';
    heroSection.appendChild(particleContainer);
    
    // Add particles
    for (let i = 0; i < 50; i++) {
        createParticle(particleContainer);
    }
    
    // Add styles for particles
    const style = document.createElement('style');
    style.textContent = `
        .particle-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            pointer-events: none;
        }
    `;
    document.head.appendChild(style);
}

/**
 * Create a single animated particle
 */
function createParticle(container) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    
    // Random size between 2-6px
    const size = Math.random() * 4 + 2;
    
    // Random position
    const posX = Math.random() * 100;
    const posY = Math.random() * 100;
    
    // Random animation duration between 10-25s
    const duration = Math.random() * 15 + 10;
    
    // Set particle styles
    particle.style.width = `${size}px`;
    particle.style.height = `${size}px`;
    particle.style.left = `${posX}%`;
    particle.style.top = `${posY}%`;
    particle.style.opacity = Math.random() * 0.5 + 0.1;
    
    // Set animation
    particle.style.animation = `floatParticle ${duration}s linear infinite`;
    
    // Add keyframes for this specific particle
    const keyframes = `
        @keyframes floatParticle {
            0% {
                transform: translate(0, 0);
            }
            25% {
                transform: translate(${Math.random() * 100 - 50}px, ${Math.random() * 50 - 25}px);
            }
            50% {
                transform: translate(${Math.random() * 100 - 50}px, ${Math.random() * 50 - 25}px);
            }
            75% {
                transform: translate(${Math.random() * 100 - 50}px, ${Math.random() * 50 - 25}px);
            }
            100% {
                transform: translate(0, 0);
            }
        }
    `;
    
    const styleElement = document.createElement('style');
    styleElement.textContent = keyframes;
    document.head.appendChild(styleElement);
    
    container.appendChild(particle);
}

/**
 * Add interactive effects to feature cards
 */
function initFeatureCards() {
    const cards = document.querySelectorAll('.feature-card');
    
    cards.forEach(card => {
        // Add tilt effect on mouse move
        card.addEventListener('mousemove', function(e) {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const moveX = (x - centerX) / 20;
            const moveY = (y - centerY) / 20;
            
            card.style.transform = `translateY(-10px) perspective(1000px) rotateX(${-moveY}deg) rotateY(${moveX}deg)`;
        });
        
        // Reset transform on mouse leave
        card.addEventListener('mouseleave', function() {
            card.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                card.style.transform = '';
            }, 200);
        });
        
        // Add pulse effect to icon on hover
        const icon = card.querySelector('.feature-icon');
        if (icon) {
            card.addEventListener('mouseenter', function() {
                icon.style.animation = 'pulse 1s ease-in-out';
            });
            
            card.addEventListener('mouseleave', function() {
                icon.style.animation = '';
            });
        }
    });
    
    // Add pulse animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Add scroll-based animations
 */
function initScrollEffects() {
    // Parallax effect for hero section
    const heroSection = document.querySelector('.hero-section');
    
    window.addEventListener('scroll', function() {
        if (!heroSection) return;
        
        const scrollPosition = window.pageYOffset;
        
        // Only animate shapes, not the container
        const shapes = document.querySelectorAll('.shape');
        shapes.forEach((shape, index) => {
            const speed = 0.05 * (index + 1);
            shape.style.transform = `translateY(${scrollPosition * speed}px)`;
        });
    });
    
    // Reveal elements on scroll if AOS is not available
    if (typeof AOS === 'undefined') {
        const revealElements = document.querySelectorAll('.feature-card, .cta-section h2');
        
        const revealOnScroll = function() {
            revealElements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (elementTop < windowHeight - 100) {
                    element.classList.add('revealed');
                }
            });
        };
        
        window.addEventListener('scroll', revealOnScroll);
        revealOnScroll(); // Initial check
        
        // Add reveal animation
        const style = document.createElement('style');
        style.textContent = `
            .feature-card, .cta-section h2 {
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.6s ease, transform 0.6s ease;
            }
            
            .revealed {
                opacity: 1;
                transform: translateY(0);
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Add typing effect to hero title
 */
function initTypingEffect() {
    const heroTitle = document.querySelector('.hero-title');
    const universityTitle = document.querySelector('.university-title');
    
    if (!heroTitle || !universityTitle) return;
    
    // Normal text with proper word spacing
    const heroText = "Students' Representative Council";
    const universityText = "Valley View University";
    
    // Check if screen is small (mobile)
    const isMobile = window.innerWidth < 768;
    
    // For mobile devices, just show the text without animation for better performance
    if (isMobile) {
        heroTitle.textContent = heroText;
        universityTitle.textContent = universityText;
        return;
    }
    
    // Clear both titles
    heroTitle.textContent = '';
    universityTitle.textContent = '';
    
    // Add active class for cursor animation
    universityTitle.classList.add('typing-active');
    
    let heroIndex = 0;
    let universityIndex = 0;
    const typingSpeed = 80; // milliseconds per character (slightly faster)
    
    // Type university title from right to left (but with words in correct order)
    function typeUniversityTitle() {
        if (universityIndex < universityText.length) {
            // Add character from right to left
            universityTitle.textContent += universityText.charAt(universityIndex);
            universityIndex++;
            setTimeout(typeUniversityTitle, typingSpeed);
        } else {
            // Remove cursor when done typing
            setTimeout(() => {
                universityTitle.classList.remove('typing-active');
                heroTitle.classList.add('typing-active');
                
                // Start hero title typing after university title is complete
                typeHeroTitle();
            }, 500);
        }
    }
    
    // Type hero title from left to right
    function typeHeroTitle() {
        if (heroIndex < heroText.length) {
            heroTitle.textContent += heroText.charAt(heroIndex);
            heroIndex++;
            setTimeout(typeHeroTitle, typingSpeed);
        } else {
            // Remove cursor when done typing
            setTimeout(() => {
                heroTitle.classList.remove('typing-active');
            }, 500);
        }
    }
    
    // Start typing effects with a slight delay
    setTimeout(typeUniversityTitle, 500);
}

/**
 * Ensure all buttons are clickable by adding explicit event listeners
 */
function ensureButtonsClickable() {
    // Get all buttons in the hero section and CTA section
    const buttons = document.querySelectorAll('.hero-section a.btn, .cta-section a.btn');
    
    buttons.forEach(button => {
        // Make sure the button has no custom styles that might interfere with clickability
        button.style.position = '';
        button.style.zIndex = '';
        button.style.transform = '';
        button.style.transition = '';
        
        // Remove any custom event listeners by cloning the button
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
    });
} 