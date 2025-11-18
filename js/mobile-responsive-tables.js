/**
 * VVUSRC Mobile Responsive Tables JavaScript
 * Automatic table responsiveness and mobile optimization
 */

class MobileTableUtils {
    constructor() {
        this.init();
    }
    
    init() {
        // Auto-initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.autoInitialize());
        } else {
            this.autoInitialize();
        }
        
        // Re-initialize on window resize
        window.addEventListener('resize', () => this.handleResize());
    }
    
    autoInitialize() {
        // Find all tables and make them responsive
        const tables = document.querySelectorAll('table:not(.no-mobile-responsive)');
        tables.forEach(table => this.makeTableResponsive(table));
        
        // Initialize search functionality for tables with search inputs
        this.initializeTableSearch();
        
        // Initialize sorting for sortable tables
        this.initializeSorting();
    }
    
    makeTableResponsive(table) {
        if (!table || table.classList.contains('mobile-responsive-initialized')) {
            return;
        }
        
        // Mark as initialized
        table.classList.add('mobile-responsive-initialized');
        
        // Wrap table in responsive container if not already wrapped
        if (!table.closest('.table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
        
        // Add data labels for mobile card view
        this.addDataLabels(table);
        
        // Apply appropriate mobile class based on screen size
        this.applyMobileClass(table);
        
        // Add mobile-specific features
        this.addMobileFeatures(table);
    }
    
    addDataLabels(table) {
        const headers = table.querySelectorAll('thead th');
        const rows = table.querySelectorAll('tbody tr');
        
        headers.forEach((header, index) => {
            const headerText = header.textContent.trim();
            
            rows.forEach(row => {
                const cell = row.cells[index];
                if (cell) {
                    cell.setAttribute('data-label', headerText);
                }
            });
        });
    }
    
    applyMobileClass(table) {
        const screenWidth = window.innerWidth;
        
        // Remove existing mobile classes
        table.classList.remove('table-mobile-cards', 'table-stacked', 'table-scroll-horizontal');
        
        if (screenWidth <= 576) {
            // Very small screens - use card layout
            table.classList.add('table-mobile-cards');
        } else if (screenWidth <= 768) {
            // Small screens - use stacked layout
            table.classList.add('table-stacked');
        } else if (screenWidth <= 991) {
            // Medium screens - use horizontal scroll
            table.classList.add('table-scroll-horizontal');
        }
    }
    
    addMobileFeatures(table) {
        // Add action column class for better mobile display
        const actionCells = table.querySelectorAll('td:last-child');
        actionCells.forEach(cell => {
            if (cell.querySelector('.btn, button, a[class*="btn"]')) {
                cell.classList.add('table-actions');
            }
        });
        
        // Add touch-friendly hover effects
        if ('ontouchstart' in window) {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.addEventListener('touchstart', function() {
                    this.classList.add('touch-active');
                });
                
                row.addEventListener('touchend', function() {
                    setTimeout(() => {
                        this.classList.remove('touch-active');
                    }, 150);
                });
            });
        }
    }
    
    initializeTableSearch() {
        const searchInputs = document.querySelectorAll('[data-table-search]');
        
        searchInputs.forEach(input => {
            const tableId = input.getAttribute('data-table-search');
            const table = document.getElementById(tableId);
            
            if (table) {
                this.addTableSearch(table, input);
            }
        });
    }
    
    addTableSearch(table, searchInput) {
        if (!table || !searchInput) return;
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const shouldShow = text.includes(searchTerm);
                
                row.style.display = shouldShow ? '' : 'none';
                
                // Add search highlight
                if (searchTerm && shouldShow) {
                    row.classList.add('search-match');
                } else {
                    row.classList.remove('search-match');
                }
            });
            
            // Update empty state
            const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
            const emptyState = table.querySelector('.table-empty-state');
            
            if (visibleRows.length === 0 && searchTerm) {
                if (!emptyState) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.className = 'table-empty-state';
                    emptyRow.innerHTML = `
                        <td colspan="100%" class="text-center py-4 text-muted">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <p class="mb-0">No results found for "${searchTerm}"</p>
                        </td>
                    `;
                    table.querySelector('tbody').appendChild(emptyRow);
                }
            } else if (emptyState) {
                emptyState.remove();
            }
        });
    }
    
    initializeSorting() {
        const sortableTables = document.querySelectorAll('.table-sortable');
        
        sortableTables.forEach(table => {
            this.addTableSort(table);
        });
    }
    
    addTableSort(table) {
        if (!table || table.classList.contains('sort-initialized')) return;
        
        table.classList.add('sort-initialized');
        const headers = table.querySelectorAll('thead th[data-sort]');
        
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.classList.add('sortable');
            
            // Add sort icon
            if (!header.querySelector('.sort-icon')) {
                const icon = document.createElement('i');
                icon.className = 'fas fa-sort sort-icon ms-1';
                header.appendChild(icon);
            }
            
            header.addEventListener('click', () => {
                this.sortTable(table, header);
            });
        });
    }
    
    sortTable(table, header) {
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const sortType = header.getAttribute('data-sort');
        const isAscending = !header.classList.contains('sort-asc');
        
        // Reset all headers
        table.querySelectorAll('thead th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
            const icon = th.querySelector('.sort-icon');
            if (icon) {
                icon.className = 'fas fa-sort sort-icon ms-1';
            }
        });
        
        // Set current header
        header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
        const icon = header.querySelector('.sort-icon');
        if (icon) {
            icon.className = `fas fa-sort-${isAscending ? 'up' : 'down'} sort-icon ms-1`;
        }
        
        // Sort rows
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();
            
            let comparison = 0;
            
            if (sortType === 'number') {
                comparison = parseFloat(aValue) - parseFloat(bValue);
            } else if (sortType === 'date') {
                comparison = new Date(aValue) - new Date(bValue);
            } else {
                comparison = aValue.localeCompare(bValue);
            }
            
            return isAscending ? comparison : -comparison;
        });
        
        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }
    
    handleResize() {
        // Re-apply mobile classes on resize
        const tables = document.querySelectorAll('.mobile-responsive-initialized');
        tables.forEach(table => this.applyMobileClass(table));
    }
    
    // Static methods for external use
    static makeTableResponsive(table) {
        const instance = new MobileTableUtils();
        instance.makeTableResponsive(table);
    }
    
    static addMobileTableSearch(tableId, searchInputId) {
        const table = document.getElementById(tableId);
        const searchInput = document.getElementById(searchInputId);
        
        if (table && searchInput) {
            const instance = new MobileTableUtils();
            instance.addTableSearch(table, searchInput);
        }
    }
    
    static addMobileTableSort(tableId) {
        const table = document.getElementById(tableId);
        
        if (table) {
            const instance = new MobileTableUtils();
            instance.addTableSort(table);
        }
    }
}

// Initialize when DOM is ready
if (typeof window !== 'undefined') {
    window.MobileTableUtils = MobileTableUtils;
    new MobileTableUtils();
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MobileTableUtils;
}
