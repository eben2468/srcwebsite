<?php
/**
 * Nuclear Layout Verification Script
 * This script can be run on any page to verify that the nuclear layout override is working
 * 
 * Usage: Add ?verify_nuclear=1 to any page URL
 */

if (isset($_GET['verify_nuclear'])) {
    ?>
    <script>
    // Nuclear Layout Verification
    (function() {
        console.log('🔬 NUCLEAR LAYOUT VERIFICATION STARTING...');
        
        setTimeout(function() {
            const width = window.innerWidth;
            const expectedPadding = width <= 320 ? '12px' : 
                                  width <= 374 ? '12px' : 
                                  width <= 767 ? '16px' : 
                                  width <= 991 ? '20px' : '24px';
            
            console.log(`📐 Viewport: ${width}px, Expected padding: ${expectedPadding}`);
            
            // Check if nuclear layout is applied
            const nuclearApplied = document.body.classList.contains('nuclear-layout-applied');
            console.log(`🚀 Nuclear layout applied: ${nuclearApplied ? '✅' : '❌'}`);
            
            // Check containers
            const containers = document.querySelectorAll('.container, .container-fluid, .main-content');
            const nuclearContainers = document.querySelectorAll('[data-nuclear-standardized="true"]');
            
            console.log(`📦 Total containers: ${containers.length}`);
            console.log(`⚡ Nuclear containers: ${nuclearContainers.length}`);
            
            let correctPadding = 0;
            containers.forEach((container, index) => {
                const computedStyle = window.getComputedStyle(container);
                const paddingLeft = computedStyle.paddingLeft;
                const paddingRight = computedStyle.paddingRight;
                const isNuclear = container.getAttribute('data-nuclear-standardized') === 'true';
                const paddingMatch = paddingLeft === expectedPadding && paddingRight === expectedPadding;
                
                if (paddingMatch) correctPadding++;
                
                console.log(`Container ${index + 1}:`, {
                    classes: container.className,
                    paddingLeft,
                    paddingRight,
                    expected: expectedPadding,
                    nuclear: isNuclear,
                    correct: paddingMatch ? '✅' : '❌'
                });
            });
            
            const successRate = containers.length > 0 ? Math.round((correctPadding / containers.length) * 100) : 0;
            console.log(`📊 Success rate: ${successRate}% (${correctPadding}/${containers.length})`);
            
            // Show visual indicator
            const indicator = document.createElement('div');
            indicator.innerHTML = `
                <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                           background: ${successRate >= 90 ? '#28a745' : successRate >= 70 ? '#ffc107' : '#dc3545'}; 
                           color: white; padding: 20px; border-radius: 10px; z-index: 99999; 
                           font-family: monospace; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                    <h3>🔬 NUCLEAR LAYOUT VERIFICATION</h3>
                    <p><strong>Viewport:</strong> ${width}px</p>
                    <p><strong>Expected Padding:</strong> ${expectedPadding}</p>
                    <p><strong>Nuclear Applied:</strong> ${nuclearApplied ? '✅ YES' : '❌ NO'}</p>
                    <p><strong>Containers:</strong> ${nuclearContainers.length}/${containers.length}</p>
                    <p><strong>Success Rate:</strong> ${successRate}%</p>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="background: white; color: black; border: none; padding: 5px 10px; 
                                   border-radius: 3px; cursor: pointer; margin-top: 10px;">Close</button>
                </div>
            `;
            document.body.appendChild(indicator);
            
            // Auto-remove after 10 seconds
            setTimeout(() => {
                if (indicator.parentElement) {
                    indicator.remove();
                }
            }, 10000);
            
        }, 2000); // Wait 2 seconds for everything to load
    })();
    </script>
    <?php
}
?>