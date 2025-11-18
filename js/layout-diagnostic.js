/**
 * Layout Standardization Diagnostic Tool
 * Run this script on any page to check if layout standardization is working
 * 
 * Usage: Add this script to any page or run in browser console
 */

(function() {
    'use strict';

    function runLayoutDiagnostic() {
        console.log('=== LAYOUT STANDARDIZATION DIAGNOSTIC ===');
        console.log('Timestamp:', new Date().toISOString());
        console.log('User Agent:', navigator.userAgent);
        console.log('Viewport:', window.innerWidth + 'x' + window.innerHeight);
        
        // Expected padding values
        const expectedPadding = {
            320: '12px',
            375: '16px', 
            768: '20px',
            991: '24px'
        };

        // Get expected padding for current viewport
        const width = window.innerWidth;
        let expected;
        if (width <= 320) expected = expectedPadding[320];
        else if (width <= 374) expected = expectedPadding[320];
        else if (width <= 767) expected = expectedPadding[375];
        else if (width <= 991) expected = expectedPadding[768];
        else if (width <= 1199) expected = expectedPadding[991];
        else expected = expectedPadding[991];

        console.log('Expected padding for', width + 'px:', expected);
        console.log('');

        // Check CSS files
        console.log('=== CSS FILES CHECK ===');
        const cssFiles = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
        const layoutCssFiles = cssFiles.filter(link => 
            link.href.includes('layout-standardization') || 
            link.href.includes('mobile-responsive')
        );
        
        console.log('Total CSS files:', cssFiles.length);
        console.log('Layout-related CSS files:', layoutCssFiles.length);
        layoutCssFiles.forEach(file => {
            console.log('  -', file.href);
        });
        console.log('');

        // Check JavaScript files
        console.log('=== JAVASCRIPT FILES CHECK ===');
        const jsFiles = Array.from(document.querySelectorAll('script[src]'));
        const layoutJsFiles = jsFiles.filter(script => 
            script.src.includes('layout') || 
            script.src.includes('force-layout')
        );
        
        console.log('Layout-related JS files:', layoutJsFiles.length);
        layoutJsFiles.forEach(file => {
            console.log('  -', file.src);
        });
        
        // Check if force layout function exists
        console.log('Force layout function available:', typeof window.forceLayoutStandardization === 'function');
        console.log('');

        // Check containers
        console.log('=== CONTAINERS CHECK ===');
        const containerSelectors = [
            '.container',
            '.container-fluid', 
            '.main-content',
            '.container.px-4',
            '.container-fluid.px-4'
        ];

        const results = [];
        
        containerSelectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            console.log(`${selector}: ${elements.length} found`);
            
            elements.forEach((element, index) => {
                const computedStyle = window.getComputedStyle(element);
                const paddingLeft = computedStyle.paddingLeft;
                const paddingRight = computedStyle.paddingRight;
                const width = computedStyle.width;
                const maxWidth = computedStyle.maxWidth;
                const marginLeft = computedStyle.marginLeft;
                const marginRight = computedStyle.marginRight;
                
                const isStandardized = element.getAttribute('data-layout-standardized') === 'true';
                const paddingMatch = paddingLeft === expected && paddingRight === expected;
                
                const result = {
                    selector,
                    index,
                    element,
                    paddingLeft,
                    paddingRight,
                    width,
                    maxWidth,
                    marginLeft,
                    marginRight,
                    isStandardized,
                    paddingMatch,
                    expected,
                    classes: element.className
                };
                
                results.push(result);
                
                console.log(`  [${index}] Classes: ${element.className}`);
                console.log(`      Padding: ${paddingLeft} / ${paddingRight} (expected: ${expected}) ${paddingMatch ? '✓' : '✗'}`);
                console.log(`      Width: ${width} (max: ${maxWidth})`);
                console.log(`      Margin: ${marginLeft} / ${marginRight}`);
                console.log(`      Standardized: ${isStandardized ? '✓' : '✗'}`);
                console.log('');
            });
        });

        // Summary
        console.log('=== SUMMARY ===');
        const totalContainers = results.length;
        const standardizedContainers = results.filter(r => r.isStandardized).length;
        const correctPaddingContainers = results.filter(r => r.paddingMatch).length;
        
        console.log(`Total containers found: ${totalContainers}`);
        console.log(`Containers marked as standardized: ${standardizedContainers}`);
        console.log(`Containers with correct padding: ${correctPaddingContainers}`);
        console.log(`Success rate: ${totalContainers > 0 ? Math.round((correctPaddingContainers / totalContainers) * 100) : 0}%`);
        
        if (correctPaddingContainers < totalContainers) {
            console.log('');
            console.log('=== ISSUES FOUND ===');
            const issues = results.filter(r => !r.paddingMatch);
            issues.forEach(issue => {
                console.log(`${issue.selector}[${issue.index}]: Expected ${issue.expected}, got ${issue.paddingLeft}/${issue.paddingRight}`);
                console.log(`  Classes: ${issue.classes}`);
            });
            
            console.log('');
            console.log('=== SUGGESTED FIXES ===');
            console.log('1. Try running: window.forceLayoutStandardization()');
            console.log('2. Check if CSS files are loading properly');
            console.log('3. Check for conflicting CSS rules');
            console.log('4. Verify viewport width is under 1200px');
        } else {
            console.log('');
            console.log('✓ All containers have correct standardized padding!');
        }
        
        // Return results for programmatic use
        return {
            viewport: { width: window.innerWidth, height: window.innerHeight },
            expected,
            totalContainers,
            standardizedContainers,
            correctPaddingContainers,
            successRate: totalContainers > 0 ? Math.round((correctPaddingContainers / totalContainers) * 100) : 0,
            results,
            issues: results.filter(r => !r.paddingMatch)
        };
    }

    // Auto-run diagnostic
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(runLayoutDiagnostic, 1000);
        });
    } else {
        setTimeout(runLayoutDiagnostic, 1000);
    }

    // Expose function globally
    window.runLayoutDiagnostic = runLayoutDiagnostic;

    // Debug button removed for production

})();