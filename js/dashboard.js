/**
 * SRC Management System Dashboard JS
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    const toggleSidebar = () => {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.toggle('show');
        }
    };

    // Add scroll padding to accommodate fixed header
    document.documentElement.style.scrollPaddingTop = '60px';

    // Handle window resize
    const handleResize = () => {
        const windowWidth = window.innerWidth;
        const sidebar = document.querySelector('.sidebar');
        
        if (windowWidth > 992 && sidebar) {
            sidebar.classList.remove('show');
        }
    };

    // Listen for window resize
    window.addEventListener('resize', handleResize);

    // Initialize
    handleResize();
}); 