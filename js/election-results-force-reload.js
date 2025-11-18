/**
 * Force reload of election results resources
 */
(function() {
    // Force reload CSS files
    function reloadCSS() {
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        links.forEach(link => {
            const href = link.href;
            if (href.includes('election-results-print')) {
                // Add or update cache-busting parameter
                const url = new URL(href);
                url.searchParams.set('v', new Date().getTime());
                link.href = url.toString();
            }
        });
    }
    
    // Force reload JS files
    function reloadJS() {
        const scripts = document.querySelectorAll('script');
        scripts.forEach(script => {
            if (script.src && script.src.includes('election-results-print')) {
                const oldScript = script;
                const newScript = document.createElement('script');
                
                // Copy all attributes
                Array.from(oldScript.attributes).forEach(attr => {
                    newScript.setAttribute(attr.name, attr.value);
                });
                
                // Add cache-busting parameter
                const url = new URL(script.src);
                url.searchParams.set('v', new Date().getTime());
                newScript.src = url.toString();
                
                // Replace old script with new one
                if (oldScript.parentNode) {
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                }
            }
        });
    }
    
    // Execute immediately
    reloadCSS();
    reloadJS();
    
    // Also reload when print button is clicked
    document.addEventListener('DOMContentLoaded', function() {
        const printButton = document.getElementById('print-results');
        if (printButton) {
            printButton.addEventListener('click', function() {
                reloadCSS();
            });
        }
        
        const exportPdfButton = document.getElementById('export-pdf');
        if (exportPdfButton) {
            exportPdfButton.addEventListener('click', function() {
                reloadCSS();
                reloadJS();
            });
        }
    });
})(); 