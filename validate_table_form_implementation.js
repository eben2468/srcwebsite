/**
 * Table and Form Responsiveness Validation Script
 * Tests Requirements 4.2 and 4.3 for VVU SRC Layout Standardization
 * 
 * This script validates:
 * - Tables fit within standardized layout and remain responsive
 * - Forms align with consistent padding system across all devices
 * - Component spacing and alignment within full-width layout
 */

class TableFormValidator {
    constructor() {
        this.results = {
            passed: 0,
            warnings: 0,
            failed: 0,
            details: []
        };
        
        this.breakpoints = {
            'very-small': { min: 0, max: 374 },
            'mobile': { min: 375, max: 767 },
            'tablet': { min: 768, max: 991 },
            'small-desktop': { min: 992, max: 1199 },
            'large-desktop': { min: 1200, max: Infinity }
        };
        
        this.expectedPadding = {
            'very-small': 12,
            'mobile': 16,
            'tablet': 20,
            'small-desktop': 24
        };
    }

    // Get current breakpoint
    getCurrentBreakpoint() {
        const width = window.innerWidth;
        for (const [name, range] of Object.entries(this.breakpoints)) {
            if (width >= range.min && width <= range.max) {
                return name;
            }
        }
        return 'unknown';
    }

    // Get expected padding for current breakpoint
    getExpectedPadding() {
        const breakpoint = this.getCurrentBreakpoint();
        return this.expectedPadding[breakpoint] || 16;
    }

    // Test if element has proper padding
    testElementPadding(element, testName) {
        const style = getComputedStyle(element);
        const paddingLeft = parseInt(style.paddingLeft);
        const paddingRight = parseInt(style.paddingRight);
        const expectedPadding = this.getExpectedPadding();
        
        const tolerance = 4; // Allow 4px tolerance
        const leftMatch = Math.abs(paddingLeft - expectedPadding) <= tolerance;
        const rightMatch = Math.abs(paddingRight - expectedPadding) <= tolerance;
        const symmetric = paddingLeft === paddingRight;
        
        return this.recordTest(testName, leftMatch && rightMatch && symmetric, {
            expected: expectedPadding,
            actualLeft: paddingLeft,
            actualRight: paddingRight,
            symmetric: symmetric
        });
    }

    // Test table responsiveness
    testTableResponsiveness() {
        console.log('Testing table responsiveness...');
        
        const tables = document.querySelectorAll('.table');
        const tableContainers = document.querySelectorAll('.table-responsive');
        
        if (tables.length === 0) {
            return this.recordTest('table-existence', false, 'No tables found on page');
        }

        let allTablesPassed = true;
        const tableResults = [];

        tables.forEach((table, index) => {
            const container = table.closest('.table-responsive') || table.parentElement;
            const tableRect = table.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            
            // Test 1: Table container fits within viewport
            const fitsViewport = containerRect.width <= window.innerWidth;
            if (!fitsViewport) allTablesPassed = false;
            
            // Test 2: Table has horizontal scroll when needed
            const hasScroll = table.scrollWidth > container.clientWidth;
            const needsScroll = table.scrollWidth > containerRect.width;
            const scrollTest = !needsScroll || hasScroll;
            if (!scrollTest) allTablesPassed = false;
            
            // Test 3: Container respects padding
            const paddingTest = this.testContainerPadding(container);
            if (!paddingTest) allTablesPassed = false;
            
            // Test 4: Table is responsive
            const isResponsive = container.classList.contains('table-responsive') || 
                               container.style.overflowX === 'auto';
            if (!isResponsive) allTablesPassed = false;
            
            tableResults.push({
                index,
                fitsViewport,
                scrollTest,
                paddingTest,
                isResponsive,
                tableWidth: table.scrollWidth,
                containerWidth: containerRect.width
            });
        });

        return this.recordTest('table-responsiveness', allTablesPassed, {
            tablesCount: tables.length,
            results: tableResults
        });
    }

    // Test form alignment and responsiveness
    testFormResponsiveness() {
        console.log('Testing form responsiveness...');
        
        const forms = document.querySelectorAll('form');
        const formContainers = document.querySelectorAll('.form-container, .card form, .modal form');
        
        if (forms.length === 0) {
            return this.recordTest('form-existence', false, 'No forms found on page');
        }

        let allFormsPassed = true;
        const formResults = [];

        forms.forEach((form, index) => {
            const formRect = form.getBoundingClientRect();
            const container = form.closest('.container, .container-fluid, .card-body, .modal-body') || form.parentElement;
            const containerRect = container.getBoundingClientRect();
            
            // Test 1: Form fits within container
            const fitsContainer = formRect.width <= containerRect.width;
            if (!fitsContainer) allFormsPassed = false;
            
            // Test 2: Form controls are properly sized
            const formControls = form.querySelectorAll('.form-control, .form-select, input, select, textarea');
            const controlsTest = Array.from(formControls).every(control => {
                const controlRect = control.getBoundingClientRect();
                return controlRect.width <= formRect.width;
            });
            if (!controlsTest) allFormsPassed = false;
            
            // Test 3: Form has proper spacing
            const formGroups = form.querySelectorAll('.form-group, .mb-3, .form-floating');
            const spacingTest = formGroups.length === 0 || Array.from(formGroups).every(group => {
                const style = getComputedStyle(group);
                return parseInt(style.marginBottom) > 0;
            });
            if (!spacingTest) allFormsPassed = false;
            
            // Test 4: Container respects padding
            const paddingTest = this.testContainerPadding(container);
            if (!paddingTest) allFormsPassed = false;
            
            formResults.push({
                index,
                fitsContainer,
                controlsTest,
                spacingTest,
                paddingTest,
                formWidth: formRect.width,
                containerWidth: containerRect.width,
                controlsCount: formControls.length
            });
        });

        return this.recordTest('form-responsiveness', allFormsPassed, {
            formsCount: forms.length,
            results: formResults
        });
    }

    // Test container padding
    testContainerPadding(container) {
        const style = getComputedStyle(container);
        const paddingLeft = parseInt(style.paddingLeft);
        const paddingRight = parseInt(style.paddingRight);
        
        // For layout standardization, we expect consistent padding
        const hasConsistentPadding = paddingLeft === paddingRight;
        const hasReasonablePadding = paddingLeft >= 8 && paddingRight >= 8; // Minimum reasonable padding
        
        return hasConsistentPadding && hasReasonablePadding;
    }

    // Test component spacing within full-width layout
    testComponentSpacing() {
        console.log('Testing component spacing...');
        
        const components = document.querySelectorAll('.card, .alert, .list-group, .nav');
        
        if (components.length === 0) {
            return this.recordTest('component-existence', false, 'No components found on page');
        }

        let allComponentsPassed = true;
        const componentResults = [];

        components.forEach((component, index) => {
            const componentRect = component.getBoundingClientRect();
            const container = component.closest('.container, .container-fluid, .main-content') || component.parentElement;
            const containerRect = container.getBoundingClientRect();
            
            // Test 1: Component fits within container
            const fitsContainer = componentRect.width <= containerRect.width;
            if (!fitsContainer) allComponentsPassed = false;
            
            // Test 2: Component has proper margin
            const style = getComputedStyle(component);
            const hasBottomMargin = parseInt(style.marginBottom) > 0;
            
            // Test 3: Component respects container padding
            const leftOffset = componentRect.left - containerRect.left;
            const rightOffset = containerRect.right - componentRect.right;
            const hasSymmetricOffset = Math.abs(leftOffset - rightOffset) <= 2; // 2px tolerance
            
            if (!hasSymmetricOffset) allComponentsPassed = false;
            
            componentResults.push({
                index,
                type: component.tagName.toLowerCase(),
                className: component.className,
                fitsContainer,
                hasBottomMargin,
                hasSymmetricOffset,
                leftOffset,
                rightOffset,
                width: componentRect.width,
                containerWidth: containerRect.width
            });
        });

        return this.recordTest('component-spacing', allComponentsPassed, {
            componentsCount: components.length,
            results: componentResults
        });
    }

    // Test mobile table transformation
    testMobileTableTransformation() {
        console.log('Testing mobile table transformation...');
        
        const isMobile = window.innerWidth <= 576;
        if (!isMobile) {
            return this.recordTest('mobile-table-skip', true, 'Skipped - not mobile viewport');
        }

        const tables = document.querySelectorAll('.table');
        if (tables.length === 0) {
            return this.recordTest('mobile-table-existence', false, 'No tables found for mobile test');
        }

        let allMobileTablesPassed = true;
        const mobileResults = [];

        tables.forEach((table, index) => {
            const tableStyle = getComputedStyle(table);
            const container = table.closest('.table-responsive');
            
            // Test 1: Table transforms to block layout or has horizontal scroll
            const isBlockLayout = tableStyle.display === 'block';
            const hasHorizontalScroll = container && container.scrollWidth > container.clientWidth;
            const transformsCorrectly = isBlockLayout || hasHorizontalScroll;
            
            if (!transformsCorrectly) allMobileTablesPassed = false;
            
            // Test 2: Table cells are readable
            const cells = table.querySelectorAll('td');
            const cellsReadable = Array.from(cells).every(cell => {
                const cellStyle = getComputedStyle(cell);
                const fontSize = parseInt(cellStyle.fontSize);
                return fontSize >= 14; // Minimum readable size
            });
            
            if (!cellsReadable) allMobileTablesPassed = false;
            
            // Test 3: Action buttons meet touch target requirements
            const actionButtons = table.querySelectorAll('.btn');
            const buttonsAccessible = Array.from(actionButtons).every(btn => {
                const btnRect = btn.getBoundingClientRect();
                return btnRect.width >= 44 && btnRect.height >= 44;
            });
            
            if (!buttonsAccessible) allMobileTablesPassed = false;
            
            mobileResults.push({
                index,
                transformsCorrectly,
                cellsReadable,
                buttonsAccessible,
                isBlockLayout,
                hasHorizontalScroll,
                cellsCount: cells.length,
                buttonsCount: actionButtons.length
            });
        });

        return this.recordTest('mobile-table-transformation', allMobileTablesPassed, {
            tablesCount: tables.length,
            results: mobileResults
        });
    }

    // Record test result
    recordTest(testName, passed, details = null) {
        const result = {
            test: testName,
            passed,
            details,
            timestamp: new Date().toISOString(),
            viewport: `${window.innerWidth}x${window.innerHeight}`,
            breakpoint: this.getCurrentBreakpoint()
        };

        this.results.details.push(result);
        
        if (passed) {
            this.results.passed++;
        } else {
            this.results.failed++;
        }

        console.log(`Test ${testName}: ${passed ? 'PASSED' : 'FAILED'}`, details);
        return passed;
    }

    // Run all validation tests
    async runAllTests() {
        console.log('Starting table and form responsiveness validation...');
        console.log(`Current viewport: ${window.innerWidth}x${window.innerHeight}`);
        console.log(`Current breakpoint: ${this.getCurrentBreakpoint()}`);
        console.log(`Expected padding: ${this.getExpectedPadding()}px`);
        
        // Reset results
        this.results = { passed: 0, warnings: 0, failed: 0, details: [] };
        
        // Run all tests
        this.testTableResponsiveness();
        this.testFormResponsiveness();
        this.testComponentSpacing();
        this.testMobileTableTransformation();
        
        // Generate summary
        const summary = this.generateSummary();
        console.log('Validation complete:', summary);
        
        return this.results;
    }

    // Generate test summary
    generateSummary() {
        const total = this.results.passed + this.results.warnings + this.results.failed;
        const passRate = total > 0 ? (this.results.passed / total * 100).toFixed(1) : 0;
        
        return {
            total,
            passed: this.results.passed,
            warnings: this.results.warnings,
            failed: this.results.failed,
            passRate: `${passRate}%`,
            status: this.results.failed > 0 ? 'FAILED' : 
                   this.results.warnings > 0 ? 'WARNING' : 'PASSED'
        };
    }

    // Generate detailed report
    generateReport() {
        const report = {
            summary: this.generateSummary(),
            environment: {
                userAgent: navigator.userAgent,
                viewport: `${window.innerWidth}x${window.innerHeight}`,
                breakpoint: this.getCurrentBreakpoint(),
                expectedPadding: this.getExpectedPadding(),
                timestamp: new Date().toISOString()
            },
            results: this.results.details
        };

        return report;
    }

    // Export report as JSON
    exportReport() {
        const report = this.generateReport();
        const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `table-form-validation-${Date.now()}.json`;
        a.click();
        
        URL.revokeObjectURL(url);
        return report;
    }
}

// Auto-run validation when script loads
if (typeof window !== 'undefined') {
    window.TableFormValidator = TableFormValidator;
    
    // Auto-run after DOM is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            const validator = new TableFormValidator();
            validator.runAllTests();
        });
    } else {
        const validator = new TableFormValidator();
        validator.runAllTests();
    }
}

// Export for Node.js if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TableFormValidator;
}