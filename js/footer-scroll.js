/**
 * Footer Scroll Behavior
 * 
 * This script manages the footer visibility based on scroll position.
 * The footer only displays when scrolled to the bottom of the page.
 */

document.addEventListener('DOMContentLoaded', function() {
    const footer = document.querySelector('.footer');
    if (!footer) return;

    // Hide the footer initially
    footer.classList.add('footer-scroll');
    footer.classList.add('footer-hidden');
    
    // Function to check if user has scrolled to the bottom of the page
    function isAtBottom() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.body.offsetHeight;
        const scrollPosition = window.scrollY;
        
        // Show footer when within 20px of the bottom
        return (windowHeight + scrollPosition) >= (documentHeight - 20);
    }
    
    // Function to handle scroll events
    function handleScroll() {
        window.requestAnimationFrame(function() {
            // Only show footer when at the bottom of the page
            if (isAtBottom()) {
                footer.classList.remove('footer-hidden');
            } else {
                footer.classList.add('footer-hidden');
            }
        });
    }
    
    // Add scroll event listener with passive flag for better performance
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    // Initial check
    handleScroll();
}); 