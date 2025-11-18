/**
 * Finance Categories Mobile Optimization JavaScript
 * Handles dynamic mobile layout adjustments and interactions
 */

(function() {
    'use strict';
    
    // Mobile optimization functions
    const FinanceCategoriesMobile = {
        
        // Initialize mobile optimizations
        init: function() {
            this.setupMobileLayout();
            this.optimizeModals();
            this.handleResponsiveTables();
            this.setupTouchOptimizations();
            this.handleOrientationChange();
            this.setupMobileNavigation();
            
            // Run on DOM ready and window resize
            document.addEventListener('DOMContentLoaded', () => {
                this.applyMobileOptimizations();
            });
            
            window.addEventListener('resize', () => {
                this.handleResize();
            });
            
            window.addEventListener('orientationchange', () => {
                setTimeout(() => {
                    this.handleOrientationChange();
                }, 100);
            });
        },
        
        // Setup mobile layout optimizations
        setupMobileLayout: function() {
            if (window.innerWidth <= 991) {
                // Force full width layout
                const main = document.querySelector('main');
                if (main) {
                    main.style.width = '100vw';
                    main.style.maxWidth = '100vw';
                    main.style.marginLeft = '0';
                    main.style.padding = '0';
                    main.style.position = 'relative';
                }
                
                // Optimize container
                const container = document.querySelector('.container-fluid');
                if (container) {
                    container.style.padding = '0';
                    container.style.margin = '0';
                    container.style.maxWidth = '100vw';
                    container.style.width = '100vw';
                    container.style.overflowX = 'hidden';
                }
                
                // Setup content area
                const contentArea = document.querySelector('.content-area') || 
                                  document.querySelector('main > div') ||
                                  main;
                if (contentArea) {
                    contentArea.style.width = '100%';
                    contentArea.style.padding = '0 0.75rem';
                    contentArea.style.margin = '0';
                    contentArea.style.maxWidth = '100%';
                    contentArea.style.boxSizing = 'border-box';
                }
            }
        },
        
        // Optimize modals for mobile
        optimizeModals: function() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                const dialog = modal.querySelector('.modal-dialog');
                if (dialog && window.innerWidth <= 991) {
                    dialog.style.margin = '1rem';
                    dialog.style.maxWidth = 'calc(100vw - 2rem)';
                    dialog.style.width = 'calc(100vw - 2rem)';
                }
                
                const content = modal.querySelector('.modal-content');
                if (content && window.innerWidth <= 991) {
                    content.style.maxHeight = 'calc(100vh - 2rem)';
                    content.style.overflowY = 'auto';
                }
                
                const body = modal.querySelector('.modal-body');
                if (body && window.innerWidth <= 991) {
                    body.style.maxHeight = 'calc(100vh - 200px)';
                    body.style.overflowY = 'auto';
                }
            });
        },
        
        // Handle responsive tables
        handleResponsiveTables: function() {
            const tables = document.querySelectorAll('.table');
            tables.forEach(table => {
                if (window.innerWidth <= 991) {
                    // Wrap table in responsive container if not already wrapped
                    if (!table.closest('.table-responsive')) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'table-responsive';
                        wrapper.style.width = '100%';
                        wrapper.style.overflowX = 'auto';
                        wrapper.style.webkitOverflowScrolling = 'touch';
                        wrapper.style.borderRadius = '8px';
                        
                        table.parentNode.insertBefore(wrapper, table);
                        wrapper.appendChild(table);
                    }
                    
                    // Set minimum width for table
                    table.style.minWidth = '600px';
                    
                    // Optimize table cells
                    const cells = table.querySelectorAll('th, td');
                    cells.forEach(cell => {
                        cell.style.padding = '0.75rem 0.5rem';
                        cell.style.fontSize = '0.9rem';
                        cell.style.whiteSpace = 'nowrap';
                    });
                }
            });
        },
        
        // Setup touch optimizations
        setupTouchOptimizations: function() {
            if (window.innerWidth <= 991) {
                // Improve touch targets
                const touchTargets = document.querySelectorAll('.btn, .form-control, .dropdown-toggle');
                touchTargets.forEach(target => {
                    const currentHeight = parseInt(window.getComputedStyle(target).height);
                    if (currentHeight < 44) {
                        target.style.minHeight = '44px';
                    }
                });
                
                // Add touch-friendly spacing
                const buttons = document.querySelectorAll('.btn');
                buttons.forEach(button => {
                    button.style.margin = '0.25rem';
                });
            }
        },
        
        // Handle orientation changes
        handleOrientationChange: function() {
            setTimeout(() => {
                this.setupMobileLayout();
                this.optimizeModals();
                this.handleResponsiveTables();
            }, 200);
        },
        
        // Handle window resize
        handleResize: function() {
            clearTimeout(this.resizeTimeout);
            this.resizeTimeout = setTimeout(() => {
                this.applyMobileOptimizations();
            }, 250);
        },
        
        // Apply all mobile optimizations
        applyMobileOptimizations: function() {
            this.setupMobileLayout();
            this.optimizeModals();
            this.handleResponsiveTables();
            this.setupTouchOptimizations();
            this.optimizeBudgetStats();
            this.optimizeCards();
            this.optimizeForms();
        },
        
        // Optimize budget statistics display
        optimizeBudgetStats: function() {
            if (window.innerWidth <= 991) {
                const budgetStats = document.querySelectorAll('.budget-stat');
                budgetStats.forEach(stat => {
                    stat.style.flexDirection = 'row';
                    stat.style.alignItems = 'center';
                    stat.style.padding = '1rem';
                    stat.style.margin = '0.5rem 0';
                    stat.style.width = '100%';
                    stat.style.boxSizing = 'border-box';
                    
                    const icon = stat.querySelector('.budget-stat-icon');
                    if (icon) {
                        icon.style.width = '45px';
                        icon.style.height = '45px';
                        icon.style.marginRight = '1rem';
                        icon.style.flexShrink = '0';
                    }
                    
                    const content = stat.querySelector('.budget-stat-content');
                    if (content) {
                        content.style.flex = '1';
                        content.style.textAlign = 'left';
                    }
                });
            }
        },
        
        // Optimize cards for mobile
        optimizeCards: function() {
            if (window.innerWidth <= 991) {
                const cards = document.querySelectorAll('.card');
                cards.forEach(card => {
                    card.style.marginBottom = '1.5rem';
                    card.style.borderRadius = '8px';
                    card.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.1)';
                    card.style.width = '100%';
                    card.style.maxWidth = '100%';
                    
                    const header = card.querySelector('.card-header');
                    if (header) {
                        header.style.padding = '1rem';
                        header.style.fontSize = '1.1rem';
                        header.style.fontWeight = '600';
                    }
                    
                    const body = card.querySelector('.card-body');
                    if (body) {
                        body.style.padding = '1rem';
                    }
                });
            }
        },
        
        // Optimize forms for mobile
        optimizeForms: function() {
            if (window.innerWidth <= 991) {
                const formControls = document.querySelectorAll('.form-control');
                formControls.forEach(control => {
                    control.style.padding = '0.75rem';
                    control.style.fontSize = '1rem';
                    control.style.borderRadius = '6px';
                    control.style.width = '100%';
                    control.style.boxSizing = 'border-box';
                });
                
                const labels = document.querySelectorAll('.form-label');
                labels.forEach(label => {
                    label.style.fontSize = '0.95rem';
                    label.style.fontWeight = '600';
                    label.style.marginBottom = '0.5rem';
                });
                
                const buttons = document.querySelectorAll('.btn');
                buttons.forEach(button => {
                    button.style.padding = '0.75rem 1.25rem';
                    button.style.fontSize = '0.95rem';
                    button.style.borderRadius = '6px';
                    button.style.fontWeight = '500';
                });
            }
        },
        
        // Setup mobile navigation optimizations
        setupMobileNavigation: function() {
            if (window.innerWidth <= 991) {
                // Ensure mobile navigation works properly
                const navToggle = document.querySelector('.navbar-toggler');
                const navCollapse = document.querySelector('.navbar-collapse');
                
                if (navToggle && navCollapse) {
                    navToggle.addEventListener('click', function() {
                        navCollapse.classList.toggle('show');
                    });
                }
                
                // Close mobile menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (navCollapse && navCollapse.classList.contains('show')) {
                        if (!navToggle.contains(e.target) && !navCollapse.contains(e.target)) {
                            navCollapse.classList.remove('show');
                        }
                    }
                });
            }
        },
        
        // Optimize dropdown menus for mobile
        optimizeDropdowns: function() {
            if (window.innerWidth <= 991) {
                const dropdowns = document.querySelectorAll('.dropdown-menu');
                dropdowns.forEach(dropdown => {
                    dropdown.style.minWidth = '120px';
                    dropdown.style.fontSize = '0.9rem';
                    dropdown.style.zIndex = '1060';
                });
            }
        },
        
        // Handle modal backdrop issues on mobile
        fixModalBackdrop: function() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('show.bs.modal', function() {
                    document.body.style.paddingRight = '0px';
                    document.body.style.overflow = 'hidden';
                });
                
                modal.addEventListener('hidden.bs.modal', function() {
                    document.body.style.paddingRight = '';
                    document.body.style.overflow = '';
                });
            });
        },
        
        // Optimize alerts for mobile
        optimizeAlerts: function() {
            if (window.innerWidth <= 991) {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.margin = '0 0 1rem 0';
                    alert.style.padding = '0.75rem 1rem';
                    alert.style.borderRadius = '6px';
                    alert.style.fontSize = '0.95rem';
                });
            }
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            FinanceCategoriesMobile.init();
        });
    } else {
        FinanceCategoriesMobile.init();
    }
    
    // Make available globally for debugging
    window.FinanceCategoriesMobile = FinanceCategoriesMobile;
    
})();