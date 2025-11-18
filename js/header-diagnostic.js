/**
 * HEADER DIAGNOSTIC SCRIPT
 * This script logs detailed information about header positioning
 * to help identify what's causing the spacing issue
 */

(function() {
    'use strict';
    
    function diagnoseHeader() {
        console.log('=== HEADER DIAGNOSTIC ===');
        console.log('Window width:', window.innerWidth);
        console.log('Window height:', window.innerHeight);
        
        // Check navbar
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            const navbarRect = navbar.getBoundingClientRect();
            const navbarStyles = window.getComputedStyle(navbar);
            console.log('Navbar found:');
            console.log('  - Height:', navbarRect.height);
            console.log('  - Top:', navbarRect.top);
            console.log('  - Bottom:', navbarRect.bottom);
            console.log('  - Position:', navbarStyles.position);
            console.log('  - Z-index:', navbarStyles.zIndex);
        } else {
            console.log('Navbar not found');
        }
        
        // Check elections header
        const electionsHeader = document.querySelector('.elections-header');
        if (electionsHeader) {
            const headerRect = electionsHeader.getBoundingClientRect();
            const headerStyles = window.getComputedStyle(electionsHeader);
            console.log('Elections header found:');
            console.log('  - Top:', headerRect.top);
            console.log('  - Left:', headerRect.left);
            console.log('  - Width:', headerRect.width);
            console.log('  - Height:', headerRect.height);
            console.log('  - Margin-top:', headerStyles.marginTop);
            console.log('  - Margin-left:', headerStyles.marginLeft);
            console.log('  - Margin-right:', headerStyles.marginRight);
            console.log('  - Position:', headerStyles.position);
            console.log('  - Transform:', headerStyles.transform);
            
            // Calculate actual spacing from navbar
            if (navbar) {
                const navbarRect = navbar.getBoundingClientRect();
                const actualSpacing = headerRect.top - navbarRect.bottom;
                console.log('  - Actual spacing from navbar:', actualSpacing + 'px');
                console.log('  - Expected spacing: 15px');
                console.log('  - Difference:', (actualSpacing - 15) + 'px');
            }
        } else {
            console.log('Elections header not found');
        }
        
        // Check any other headers
        const header = document.querySelector('.header');
        if (header) {
            const headerRect = header.getBoundingClientRect();
            const headerStyles = window.getComputedStyle(header);
            console.log('Standard header found:');
            console.log('  - Top:', headerRect.top);
            console.log('  - Margin-top:', headerStyles.marginTop);
            console.log('  - Position:', headerStyles.position);
        }
        
        // Check container-fluid
        const container = document.querySelector('.container-fluid');
        if (container) {
            const containerRect = container.getBoundingClientRect();
            const containerStyles = window.getComputedStyle(container);
            console.log('Container-fluid found:');
            console.log('  - Top:', containerRect.top);
            console.log('  - Padding-top:', containerStyles.paddingTop);
            console.log('  - Margin-top:', containerStyles.marginTop);
        }
        
        // Check body
        const bodyStyles = window.getComputedStyle(document.body);
        console.log('Body styles:');
        console.log('  - Padding-top:', bodyStyles.paddingTop);
        console.log('  - Margin-top:', bodyStyles.marginTop);
        
        console.log('=== END DIAGNOSTIC ===');
    }
    
    // Run diagnostic when page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(diagnoseHeader, 1000);
        });
    } else {
        setTimeout(diagnoseHeader, 1000);
    }
    
})();