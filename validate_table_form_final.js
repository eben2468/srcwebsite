/**
 * Final Table and Form Responsiveness Validation
 * Task 8: Validate table and form responsiveness
 * Requirements 4.2 and 4.3 validation
 */

class FinalTableFormValidator {
    constructor() {
        this.results = {
            requirement_4_2: { // Tables fit within standardized layout and remain responsive
                passed: 0,
                failed: 0,
                tests: []
            },
            requirement_4_3: { // Forms align with consistent padding system across all devices
                passed: 0,
                failed: 0,
                tests: []
            },
            component_spacing: { // Component spacing and alignment within full-width layout
                passed: 0,
                failed: 0,
                tests: []
            }
        };
        
        this.breakpoints = {
            'very-small': { min: 0, max: 374, padding: 12 },
            'mobile': { min: 375, max: 767, padding: 16 },
            'tablet': { min: 768, max: 991, padding: 20 },
            'small-desktop': { min: 992, max: 1199, padding: 24 }
        };
    }

    getCurrentBreakpoint() {
        const width = window.innerWidth;
        for (const [name, config] of Object.entries(this.breakpoints)) {
            if (width >= config.min && width <= config.max) {
                return { name, ...config };
            }
        }
        return { name: 'large-desktop', min: 1200, max: Infinity, padding: 24 };
    }

    // Requirement 4.2: Ensure tables fit within standardized layout and remain responsive
    validateTableResponsiveness() {
        console.log('🔍 Validating Requirement 4.2: Table Responsiveness');
        
        const tables = document.querySelectorAll('.table');
        const tableContainers = document.querySelectorAll('.table-responsive');
        
        if (tables.length === 0) {
            this.recordTest('requirement_4_2', 'no-tables', false, 'No tables found on page');
            return;
        }

        // Test 1: Tables have responsive containers
        const hasResponsiveContainers = Array.from(tables).every(table => {
            const container = table.closest('.table-responsive');
            return container !== null;
        });
        this.recordTest('requirement_4_2', 'responsive-containers', hasResponsiveContainers, 
            `${tables.length} tables, ${tableContainers.length} responsive containers`);

        // Test 2: Table containers fit within viewport
        const containersFitViewport = Array.from(tableContainers).every(container => {
            const rect = container.getBoundingClientRect();
            return rect.width <= window.innerWidth;
        });
        this.recordTest('requirement_4_2', 'containers-fit-viewport', containersFitViewport,
            `All ${tableContainers.length} containers fit within ${window.innerWidth}px viewport`);

        // Test 3: Tables have horizontal scroll when content exceeds container
        const horizontalScrollWorks = Array.from(tableContainers).every(container => {
            const table = container.querySelector('.table');
            if (!table) return true;
            
            const needsScroll = table.scrollWidth > container.clientWidth;
            const hasScroll = container.scrollWidth > container.clientWidth || 
                             container.style.overflowX === 'auto' ||
                             getComputedStyle(container).overflowX === 'auto';
            
            return !needsScroll || hasScroll;
        });
        this.recordTest('requirement_4_2', 'horizontal-scroll', horizontalScrollWorks,
            'Tables provide horizontal scroll when content exceeds container width');

        // Test 4: Tables respect standardized padding
        const currentBreakpoint = this.getCurrentBreakpoint();
        const tablesRespectPadding = Array.from(tableContainers).every(container => {
            const parentContainer = container.closest('.container, .container-fluid, .main-content');
            if (!parentContainer) return true;
            
            const style = getComputedStyle(parentContainer);
            const paddingLeft = parseInt(style.paddingLeft);
            const paddingRight = parseInt(style.paddingRight);
            const expectedPadding = currentBreakpoint.padding;
            
            const tolerance = 4; // 4px tolerance
            return Math.abs(paddingLeft - expectedPadding) <= tolerance && 
                   Math.abs(paddingRight - expectedPadding) <= tolerance;
        });
        this.recordTest('requirement_4_2', 'standardized-padding', tablesRespectPadding,
            `Expected padding: ${currentBreakpoint.padding}px for ${currentBreakpoint.name} breakpoint`);

        // Test 5: Table action buttons are accessible
        const actionButtonsAccessible = Array.from(tables).every(table => {
            const buttons = table.querySelectorAll('.btn');
            return Array.from(buttons).every(btn => {
                const rect = btn.getBoundingClientRect();
                return rect.width >= 32 && rect.height >= 32; // Minimum touch target
            });
        });
        this.recordTest('requirement_4_2', 'accessible-buttons', actionButtonsAccessible,
            'Table action buttons meet minimum accessibility requirements');

        // Test 6: Mobile table transformation (if applicable)
        if (window.innerWidth <= 576) {
            const mobileTransformation = Array.from(tables).some(table => {
                const style = getComputedStyle(table);
                return style.display === 'block' || 
                       table.querySelectorAll('td[data-label]').length > 0;
            });
            this.recordTest('requirement_4_2', 'mobile-transformation', mobileTransformation,
                'Tables transform appropriately for mobile devices');
        }
    }

    // Requirement 4.3: Verify forms align with consistent padding system across all devices
    validateFormAlignment() {
        console.log('🔍 Validating Requirement 4.3: Form Alignment');
        
        const forms = document.querySelectorAll('form');
        
        if (forms.length === 0) {
            this.recordTest('requirement_4_3', 'no-forms', false, 'No forms found on page');
            return;
        }

        // Test 1: Forms fit within their containers
        const formsFitContainers = Array.from(forms).every(form => {
            const formRect = form.getBoundingClientRect();
            const container = form.closest('.container, .container-fluid, .card-body, .modal-body') || form.parentElement;
            const containerRect = container.getBoundingClientRect();
            
            return formRect.width <= containerRect.width;
        });
        this.recordTest('requirement_4_3', 'forms-fit-containers', formsFitContainers,
            `${forms.length} forms tested for container fit`);

        // Test 2: Form controls are properly sized
        const formControlsProperSize = Array.from(forms).every(form => {
            const controls = form.querySelectorAll('.form-control, .form-select, input, select, textarea');
            return Array.from(controls).every(control => {
                const controlRect = control.getBoundingClientRect();
                const formRect = form.getBoundingClientRect();
                return controlRect.width <= formRect.width;
            });
        });
        this.recordTest('requirement_4_3', 'form-controls-sized', formControlsProperSize,
            'Form controls fit within their form containers');

        // Test 3: Forms respect standardized padding
        const currentBreakpoint = this.getCurrentBreakpoint();
        const formsRespectPadding = Array.from(forms).every(form => {
            const container = form.closest('.container, .container-fluid, .main-content');
            if (!container) return true;
            
            const style = getComputedStyle(container);
            const paddingLeft = parseInt(style.paddingLeft);
            const paddingRight = parseInt(style.paddingRight);
            const expectedPadding = currentBreakpoint.padding;
            
            const tolerance = 4; // 4px tolerance
            return Math.abs(paddingLeft - expectedPadding) <= tolerance && 
                   Math.abs(paddingRight - expectedPadding) <= tolerance;
        });
        this.recordTest('requirement_4_3', 'standardized-padding', formsRespectPadding,
            `Expected padding: ${currentBreakpoint.padding}px for ${currentBreakpoint.name} breakpoint`);

        // Test 4: Form groups have consistent spacing
        const formGroupsConsistentSpacing = Array.from(forms).every(form => {
            const formGroups = form.querySelectorAll('.form-group, .mb-3, .form-floating');
            if (formGroups.length === 0) return true;
            
            return Array.from(formGroups).every(group => {
                const style = getComputedStyle(group);
                const marginBottom = parseInt(style.marginBottom);
                return marginBottom > 0; // Should have some bottom margin
            });
        });
        this.recordTest('requirement_4_3', 'consistent-spacing', formGroupsConsistentSpacing,
            'Form groups maintain consistent spacing');

        // Test 5: Form buttons are properly sized and accessible
        const formButtonsAccessible = Array.from(forms).every(form => {
            const buttons = form.querySelectorAll('.btn, button');
            return Array.from(buttons).every(btn => {
                const rect = btn.getBoundingClientRect();
                return rect.width >= 44 && rect.height >= 44; // Touch target size
            });
        });
        this.recordTest('requirement_4_3', 'accessible-buttons', formButtonsAccessible,
            'Form buttons meet accessibility requirements');

        // Test 6: Multi-column forms adapt to screen size
        const multiColumnFormsAdapt = Array.from(forms).every(form => {
            const rows = form.querySelectorAll('.row');
            const columns = form.querySelectorAll('[class*="col-"]');
            
            if (columns.length === 0) return true; // No columns to test
            
            const isMobile = window.innerWidth < 768;
            if (isMobile) {
                // On mobile, columns should stack (full width)
                return Array.from(columns).every(col => {
                    const rect = col.getBoundingClientRect();
                    const parentRect = col.parentElement.getBoundingClientRect();
                    return Math.abs(rect.width - parentRect.width) <= 10; // Allow 10px tolerance
                });
            } else {
                // On desktop, columns should be side by side
                return true; // Assume correct if not mobile
            }
        });
        this.recordTest('requirement_4_3', 'multi-column-adaptation', multiColumnFormsAdapt,
            'Multi-column forms adapt properly to screen size');
    }

    // Test component spacing and alignment within full-width layout
    validateComponentSpacing() {
        console.log('🔍 Validating Component Spacing and Alignment');
        
        const components = document.querySelectorAll('.card, .alert, .list-group, .nav, .table-responsive, form');
        
        if (components.length === 0) {
            this.recordTest('component_spacing', 'no-components', false, 'No components found on page');
            return;
        }

        // Test 1: Components fit within their containers
        const componentsFitContainers = Array.from(components).every(component => {
            const componentRect = component.getBoundingClientRect();
            const container = component.closest('.container, .container-fluid, .main-content') || component.parentElement;
            const containerRect = container.getBoundingClientRect();
            
            return componentRect.width <= containerRect.width;
        });
        this.recordTest('component_spacing', 'components-fit-containers', componentsFitContainers,
            `${components.length} components tested for container fit`);

        // Test 2: Components have proper spacing
        const componentsHaveSpacing = Array.from(components).every(component => {
            const style = getComputedStyle(component);
            const marginBottom = parseInt(style.marginBottom);
            const marginTop = parseInt(style.marginTop);
            
            // Should have some margin (top or bottom)
            return marginBottom > 0 || marginTop > 0;
        });
        this.recordTest('component_spacing', 'proper-spacing', componentsHaveSpacing,
            'Components maintain proper spacing');

        // Test 3: Components respect container padding symmetrically
        const componentsSymmetricPadding = Array.from(components).every(component => {
            const componentRect = component.getBoundingClientRect();
            const container = component.closest('.container, .container-fluid, .main-content');
            if (!container) return true;
            
            const containerRect = container.getBoundingClientRect();
            const leftOffset = componentRect.left - containerRect.left;
            const rightOffset = containerRect.right - componentRect.right;
            
            // Allow 5px tolerance for symmetric positioning
            return Math.abs(leftOffset - rightOffset) <= 5;
        });
        this.recordTest('component_spacing', 'symmetric-padding', componentsSymmetricPadding,
            'Components are positioned symmetrically within containers');

        // Test 4: Nested components maintain proper hierarchy
        const nestedComponentsProper = Array.from(components).every(component => {
            const nestedComponents = component.querySelectorAll('.card, .alert, .list-group, form');
            return Array.from(nestedComponents).every(nested => {
                const nestedRect = nested.getBoundingClientRect();
                const parentRect = component.getBoundingClientRect();
                return nestedRect.width <= parentRect.width;
            });
        });
        this.recordTest('component_spacing', 'nested-hierarchy', nestedComponentsProper,
            'Nested components maintain proper size hierarchy');
    }

    // Record test result
    recordTest(category, testName, passed, details) {
        const result = {
            test: testName,
            passed,
            details,
            timestamp: new Date().toISOString(),
            viewport: `${window.innerWidth}x${window.innerHeight}`,
            breakpoint: this.getCurrentBreakpoint().name
        };

        this.results[category].tests.push(result);
        
        if (passed) {
            this.results[category].passed++;
        } else {
            this.results[category].failed++;
        }

        const status = passed ? '✅ PASS' : '❌ FAIL';
        console.log(`${status} [${category}] ${testName}: ${details}`);
    }

    // Run all validations
    async runAllValidations() {
        console.log('🚀 Starting Final Table and Form Responsiveness Validation');
        console.log(`📱 Viewport: ${window.innerWidth}x${window.innerHeight}`);
        console.log(`📏 Breakpoint: ${this.getCurrentBreakpoint().name}`);
        console.log(`📐 Expected Padding: ${this.getCurrentBreakpoint().padding}px`);
        console.log('─'.repeat(60));

        // Reset results
        Object.keys(this.results).forEach(key => {
            this.results[key] = { passed: 0, failed: 0, tests: [] };
        });

        // Run all validations
        this.validateTableResponsiveness();
        this.validateFormAlignment();
        this.validateComponentSpacing();

        // Generate final report
        const report = this.generateFinalReport();
        console.log('─'.repeat(60));
        console.log('📊 FINAL VALIDATION REPORT');
        console.log('─'.repeat(60));
        console.log(report.summary);
        
        return report;
    }

    // Generate final report
    generateFinalReport() {
        const totalPassed = Object.values(this.results).reduce((sum, cat) => sum + cat.passed, 0);
        const totalFailed = Object.values(this.results).reduce((sum, cat) => sum + cat.failed, 0);
        const totalTests = totalPassed + totalFailed;
        const passRate = totalTests > 0 ? (totalPassed / totalTests * 100).toFixed(1) : 0;

        const summary = `
🎯 TASK 8 VALIDATION RESULTS
═══════════════════════════════════════════════════════════

📋 REQUIREMENT 4.2 - Table Responsiveness:
   ✅ Passed: ${this.results.requirement_4_2.passed}
   ❌ Failed: ${this.results.requirement_4_2.failed}

📋 REQUIREMENT 4.3 - Form Alignment:
   ✅ Passed: ${this.results.requirement_4_3.passed}
   ❌ Failed: ${this.results.requirement_4_3.failed}

📋 COMPONENT SPACING:
   ✅ Passed: ${this.results.component_spacing.passed}
   ❌ Failed: ${this.results.component_spacing.failed}

📊 OVERALL RESULTS:
   Total Tests: ${totalTests}
   Pass Rate: ${passRate}%
   Status: ${totalFailed === 0 ? '🎉 ALL TESTS PASSED' : '⚠️ SOME TESTS FAILED'}

🌐 Test Environment:
   Viewport: ${window.innerWidth}x${window.innerHeight}
   Breakpoint: ${this.getCurrentBreakpoint().name}
   Expected Padding: ${this.getCurrentBreakpoint().padding}px
   Timestamp: ${new Date().toISOString()}
`;

        return {
            summary,
            passed: totalPassed,
            failed: totalFailed,
            total: totalTests,
            passRate: `${passRate}%`,
            status: totalFailed === 0 ? 'PASSED' : 'FAILED',
            details: this.results,
            environment: {
                viewport: `${window.innerWidth}x${window.innerHeight}`,
                breakpoint: this.getCurrentBreakpoint(),
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString()
            }
        };
    }

    // Export detailed report
    exportDetailedReport() {
        const report = this.generateFinalReport();
        const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `task-8-validation-report-${Date.now()}.json`;
        a.click();
        
        URL.revokeObjectURL(url);
        return report;
    }
}

// Auto-initialize when script loads
if (typeof window !== 'undefined') {
    window.FinalTableFormValidator = FinalTableFormValidator;
    
    // Auto-run validation after DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        const validator = new FinalTableFormValidator();
        validator.runAllValidations().then(report => {
            // Store results globally for access
            window.validationReport = report;
            
            // Display results in console
            console.log('✨ Validation complete! Results stored in window.validationReport');
            console.log('💾 Run validator.exportDetailedReport() to download full report');
        });
    });
}

// Export for Node.js if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FinalTableFormValidator;
}