/**
 * Budget Management System JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize animations
    initAnimations();
    
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        initCharts();
    }
    
    // Initialize budget item form handlers
    initBudgetItemsForm();
    
    // Initialize modals
    initModals();
    
    // Initialize filters and search
    initFilters();
    
    // Initialize status change handlers
    initStatusChanges();
    
    // Initialize delete confirmation
    initDeleteConfirmation();
    
    // Initialize comments
    initComments();
    
    // Initialize pagination
    initPagination();
});

/**
 * Initialize animations for elements
 */
function initAnimations() {
    // Add animation class to budget cards
    const budgetCards = document.querySelectorAll('.budget-card');
    budgetCards.forEach((card, index) => {
        card.classList.add('animate-fade-in');
        // Staggered animation delay is added via CSS
    });
    
    // Animate progress bars
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        // Get the width from data attribute
        const width = bar.getAttribute('data-width');
        
        // Set initial width to 0
        bar.style.width = '0%';
        
        // Trigger animation after a small delay
        setTimeout(() => {
            bar.style.width = width + '%';
        }, 300);
    });
}

/**
 * Initialize Chart.js charts
 */
function initCharts() {
    // Budget distribution chart
    const budgetDistributionCtx = document.getElementById('budgetDistributionChart');
    if (budgetDistributionCtx) {
        new Chart(budgetDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Declined'],
                datasets: [{
                    data: [
                        parseFloat(budgetDistributionCtx.getAttribute('data-approved') || 0),
                        parseFloat(budgetDistributionCtx.getAttribute('data-pending') || 0),
                        parseFloat(budgetDistributionCtx.getAttribute('data-declined') || 0)
                    ],
                    backgroundColor: [
                        '#10b981', // success color
                        '#f59e0b', // warning color
                        '#ef4444'  // danger color
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // Monthly budget trends chart
    const budgetTrendsCtx = document.getElementById('budgetTrendsChart');
    if (budgetTrendsCtx) {
        // Get data from data attributes
        const months = JSON.parse(budgetTrendsCtx.getAttribute('data-months') || '[]');
        const approvedData = JSON.parse(budgetTrendsCtx.getAttribute('data-approved') || '[]');
        const pendingData = JSON.parse(budgetTrendsCtx.getAttribute('data-pending') || '[]');
        
        new Chart(budgetTrendsCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Approved',
                        data: approvedData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Pending',
                        data: pendingData,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Category distribution chart
    const categoryChartCtx = document.getElementById('categoryDistributionChart');
    if (categoryChartCtx) {
        // Get data from data attributes
        const categories = JSON.parse(categoryChartCtx.getAttribute('data-categories') || '[]');
        const values = JSON.parse(categoryChartCtx.getAttribute('data-values') || '[]');
        
        new Chart(categoryChartCtx, {
            type: 'bar',
            data: {
                labels: categories,
                datasets: [{
                    label: 'Budget Amount',
                    data: values,
                    backgroundColor: [
                        '#4361ee', // blue
                        '#3a0ca3', // purple
                        '#7209b7', // violet
                        '#f72585', // pink
                        '#4cc9f0', // cyan
                        '#4f772d', // green
                        '#fb8500', // orange
                        '#9d4edd'  // lavender
                    ],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

/**
 * Initialize dynamic budget items form
 */
function initBudgetItemsForm() {
    const budgetItemsContainer = document.getElementById('budgetItemsContainer');
    const addItemButton = document.getElementById('addBudgetItem');
    
    if (budgetItemsContainer && addItemButton) {
        let itemCounter = document.querySelectorAll('.budget-item-form').length;
        
        // Add new budget item form
        addItemButton.addEventListener('click', function() {
            itemCounter++;
            
            const itemForm = document.createElement('div');
            itemForm.className = 'budget-item-form';
            itemForm.innerHTML = `
                <button type="button" class="remove-item">&times;</button>
                <div class="form-row">
                    <div class="form-group">
                        <label for="item_name_${itemCounter}">Item Name</label>
                        <input type="text" class="form-control" id="item_name_${itemCounter}" name="items[${itemCounter}][name]" required>
                    </div>
                    <div class="form-group">
                        <label for="item_amount_${itemCounter}">Amount</label>
                        <input type="number" step="0.01" class="form-control" id="item_amount_${itemCounter}" name="items[${itemCounter}][amount]" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="item_description_${itemCounter}">Description</label>
                    <textarea class="form-control" id="item_description_${itemCounter}" name="items[${itemCounter}][description]" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label for="item_quantity_${itemCounter}">Quantity</label>
                    <input type="number" class="form-control" id="item_quantity_${itemCounter}" name="items[${itemCounter}][quantity]" value="1" min="1">
                </div>
            `;
            
            budgetItemsContainer.appendChild(itemForm);
            
            // Add event listener to remove button
            const removeButton = itemForm.querySelector('.remove-item');
            removeButton.addEventListener('click', function() {
                budgetItemsContainer.removeChild(itemForm);
                updateTotalAmount();
            });
            
            // Add event listeners for amount and quantity changes
            const amountInput = itemForm.querySelector(`#item_amount_${itemCounter}`);
            const quantityInput = itemForm.querySelector(`#item_quantity_${itemCounter}`);
            
            amountInput.addEventListener('input', updateTotalAmount);
            quantityInput.addEventListener('input', updateTotalAmount);
            
            // Focus on the name field
            itemForm.querySelector(`#item_name_${itemCounter}`).focus();
        });
        
        // Handle existing remove buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                const itemForm = button.closest('.budget-item-form');
                budgetItemsContainer.removeChild(itemForm);
                updateTotalAmount();
            });
        });
        
        // Add event listeners to existing amount and quantity inputs
        document.querySelectorAll('.budget-item-form input[id^="item_amount_"], .budget-item-form input[id^="item_quantity_"]').forEach(input => {
            input.addEventListener('input', updateTotalAmount);
        });
        
        // Initial total calculation
        updateTotalAmount();
    }
}

/**
 * Update total budget amount based on items
 */
function updateTotalAmount() {
    const totalAmountField = document.getElementById('total_amount');
    if (!totalAmountField) return;
    
    let total = 0;
    
    document.querySelectorAll('.budget-item-form').forEach(itemForm => {
        const amountInput = itemForm.querySelector('input[id^="item_amount_"]');
        const quantityInput = itemForm.querySelector('input[id^="item_quantity_"]');
        
        if (amountInput && quantityInput) {
            const amount = parseFloat(amountInput.value) || 0;
            const quantity = parseInt(quantityInput.value) || 1;
            total += amount * quantity;
        }
    });
    
    totalAmountField.value = total.toFixed(2);
}

/**
 * Initialize modal functionality
 */
function initModals() {
    // Open modal buttons
    document.querySelectorAll('[data-modal-target]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal-target');
            const modal = document.getElementById(modalId);
            
            if (modal) {
                const modalOverlay = document.getElementById(modalId + 'Overlay') || modal.closest('.modal-overlay');
                if (modalOverlay) {
                    // Make sure the overlay is visible
                    modalOverlay.style.display = 'flex';
                    modalOverlay.classList.add('active');
                    
                    // Add a small delay before showing the modal for smooth animation
                    setTimeout(() => {
                        modal.classList.add('active');
                    }, 10);
                    
                    // Focus on the first input field in the modal
                    setTimeout(() => {
                        const firstInput = modal.querySelector('input, textarea, select');
                        if (firstInput) {
                            firstInput.focus();
                        }
                    }, 300);
                }
            }
        });
    });
    
    // Close modal buttons
    document.querySelectorAll('.modal-close, [data-modal-close]').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            const modalOverlay = modal ? modal.closest('.modal-overlay') : null;
            
            if (modal && modalOverlay) {
                modal.classList.remove('active');
                setTimeout(() => {
                    modalOverlay.classList.remove('active');
                }, 300);
            }
        });
    });
    
    // Close modal when clicking on overlay
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                const modal = overlay.querySelector('.modal');
                if (modal) {
                    modal.classList.remove('active');
                    setTimeout(() => {
                        overlay.classList.remove('active');
                    }, 300);
                }
            }
        });
    });
    
    // Handle form submissions in modals
    const createBudgetForm = document.getElementById('createBudgetForm');
    if (createBudgetForm) {
        createBudgetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Create form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('budget_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to the new budget detail page or reload the current page
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert('Failed to create budget: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating the budget.');
            });
        });
    }
}

/**
 * Initialize filters and search functionality
 */
function initFilters() {
    const filterSelects = document.querySelectorAll('.filter-select');
    const searchInput = document.querySelector('.search-input');
    
    // Filter change event
    filterSelects.forEach(select => {
        select.addEventListener('change', applyFilters);
    });
    
    // Search input event
    if (searchInput) {
        searchInput.addEventListener('input', debounce(applyFilters, 300));
    }
}

/**
 * Apply filters to the budget table
 */
function applyFilters() {
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const searchInput = document.querySelector('.search-input');
    
    const status = statusFilter ? statusFilter.value : '';
    const category = categoryFilter ? categoryFilter.value : '';
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    
    const tableRows = document.querySelectorAll('.budget-table tbody tr');
    
    tableRows.forEach(row => {
        const rowStatus = row.getAttribute('data-status') || '';
        const rowCategory = row.getAttribute('data-category') || '';
        const rowTitle = row.getAttribute('data-title') || '';
        const rowDescription = row.getAttribute('data-description') || '';
        
        const matchesStatus = status === '' || rowStatus === status;
        const matchesCategory = category === '' || rowCategory === category;
        const matchesSearch = searchTerm === '' || 
                             rowTitle.toLowerCase().includes(searchTerm) || 
                             rowDescription.toLowerCase().includes(searchTerm);
        
        if (matchesStatus && matchesCategory && matchesSearch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update pagination after filtering
    updatePagination();
}

/**
 * Initialize status change functionality
 */
function initStatusChanges() {
    document.querySelectorAll('.status-change-btn').forEach(button => {
        button.addEventListener('click', function() {
            const budgetId = this.getAttribute('data-budget-id');
            const newStatus = this.getAttribute('data-status');
            
            if (budgetId && newStatus) {
                // Show confirmation before changing status
                if (confirm(`Are you sure you want to mark this budget as ${newStatus}?`)) {
                    // Create form data
                    const formData = new FormData();
                    formData.append('budget_id', budgetId);
                    formData.append('status', newStatus);
                    formData.append('action', 'update_status');
                    
                    // Send AJAX request
                    fetch('budget_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Refresh the page to show updated status
                            window.location.reload();
                        } else {
                            alert('Failed to update status: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the status.');
                    });
                }
            }
        });
    });
}

/**
 * Initialize delete confirmation
 */
function initDeleteConfirmation() {
    document.querySelectorAll('.delete-budget-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const budgetId = this.getAttribute('data-budget-id');
            const budgetTitle = this.getAttribute('data-title');
            
            if (confirm(`Are you sure you want to delete the budget "${budgetTitle}"? This action cannot be undone.`)) {
                // Create form data
                const formData = new FormData();
                formData.append('budget_id', budgetId);
                formData.append('action', 'delete');
                
                // Send AJAX request
                fetch('budget_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table
                        const row = document.querySelector(`tr[data-budget-id="${budgetId}"]`);
                        if (row) {
                            row.remove();
                        }
                        
                        // Show success message
                        alert('Budget deleted successfully.');
                        
                        // Redirect to budget list if on detail page
                        if (window.location.href.includes('budget-detail.php')) {
                            window.location.href = 'budget.php';
                        }
                    } else {
                        alert('Failed to delete budget: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the budget.');
                });
            }
        });
    });
}

/**
 * Initialize comments functionality
 */
function initComments() {
    const commentForm = document.getElementById('commentForm');
    
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const budgetId = this.querySelector('input[name="budget_id"]').value;
            const commentText = this.querySelector('textarea[name="comment"]').value;
            
            if (!commentText.trim()) {
                alert('Please enter a comment.');
                return;
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('budget_id', budgetId);
            formData.append('comment', commentText);
            formData.append('action', 'add_comment');
            
            // Send AJAX request
            fetch('budget_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear the comment field
                    this.querySelector('textarea[name="comment"]').value = '';
                    
                    // Add the new comment to the list
                    const commentsContainer = document.querySelector('.comments-list');
                    if (commentsContainer) {
                        const newComment = document.createElement('div');
                        newComment.className = 'comment';
                        newComment.innerHTML = `
                            <div class="comment-avatar">${data.user_initial}</div>
                            <div class="comment-content">
                                <div class="comment-header">
                                    <div class="comment-author">${data.user_name}</div>
                                    <div class="comment-date">Just now</div>
                                </div>
                                <div class="comment-text">${data.comment}</div>
                            </div>
                        `;
                        
                        commentsContainer.prepend(newComment);
                    }
                } else {
                    alert('Failed to add comment: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the comment.');
            });
        });
    }
}

/**
 * Initialize pagination
 */
function initPagination() {
    const paginationItems = document.querySelectorAll('.pagination-item');
    const tableRows = document.querySelectorAll('.budget-table tbody tr');
    const rowsPerPage = 10;
    
    if (paginationItems.length > 0) {
        // Set up pagination click events
        paginationItems.forEach(item => {
            item.addEventListener('click', function() {
                const page = parseInt(this.getAttribute('data-page'));
                goToPage(page, rowsPerPage, tableRows);
                
                // Update active state
                paginationItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Initialize first page
        goToPage(1, rowsPerPage, tableRows);
        paginationItems[0].classList.add('active');
    }
}

/**
 * Update pagination after filtering
 */
function updatePagination() {
    const visibleRows = document.querySelectorAll('.budget-table tbody tr:not([style*="display: none"])');
    const rowsPerPage = 10;
    const totalPages = Math.ceil(visibleRows.length / rowsPerPage);
    
    // Update pagination items
    const paginationContainer = document.querySelector('.pagination');
    if (paginationContainer) {
        paginationContainer.innerHTML = '';
        
        for (let i = 1; i <= totalPages; i++) {
            const pageItem = document.createElement('div');
            pageItem.className = 'pagination-item';
            pageItem.setAttribute('data-page', i);
            pageItem.textContent = i;
            
            pageItem.addEventListener('click', function() {
                const page = parseInt(this.getAttribute('data-page'));
                goToPage(page, rowsPerPage, visibleRows);
                
                // Update active state
                document.querySelectorAll('.pagination-item').forEach(item => {
                    item.classList.remove('active');
                });
                this.classList.add('active');
            });
            
            paginationContainer.appendChild(pageItem);
        }
        
        // Set first page as active
        const firstPageItem = paginationContainer.querySelector('.pagination-item');
        if (firstPageItem) {
            firstPageItem.classList.add('active');
        }
        
        // Go to first page
        goToPage(1, rowsPerPage, visibleRows);
    }
}

/**
 * Go to specific page in pagination
 */
function goToPage(page, rowsPerPage, rows) {
    const startIndex = (page - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    
    rows.forEach((row, index) => {
        if (index >= startIndex && index < endIndex) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

/**
 * Debounce function for search input
 */
function debounce(func, delay) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), delay);
    };
} 