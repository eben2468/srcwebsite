// Force CSS refresh utility
(function() {
    'use strict';
    
    // Function to force refresh CSS files
    function forceCSSRefresh() {
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        const timestamp = new Date().getTime();
        
        links.forEach(link => {
            if (link.href && !link.href.includes('cdn.') && !link.href.includes('cdnjs.')) {
                const url = new URL(link.href);
                url.searchParams.set('v', timestamp);
                link.href = url.toString();
            }
        });
        
        console.log('CSS files refreshed with timestamp:', timestamp);
    }
    
    // Function to reload specific CSS file
    function reloadCSS(filename) {
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        const timestamp = new Date().getTime();
        
        links.forEach(link => {
            if (link.href.includes(filename)) {
                const url = new URL(link.href);
                url.searchParams.set('v', timestamp);
                link.href = url.toString();
                console.log('Reloaded CSS:', filename);
            }
        });
    }
    
    // Expose functions globally
    window.forceCSSRefresh = forceCSSRefresh;
    window.reloadCSS = reloadCSS;
    
    // Auto-refresh CSS on development mode (if needed)
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        // Optional: Auto-refresh CSS every 30 seconds in development
        // setInterval(forceCSSRefresh, 30000);
    }
})();