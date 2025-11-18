/**
 * Help Center JavaScript Functions
 * Handles FAQ interactions and category filtering
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize search functionality
    const searchInput = document.getElementById('faqSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterFAQs(searchTerm);
        });
    }

    // Initialize category buttons
    const categoryButtons = document.querySelectorAll('.category-btn');
    categoryButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.getAttribute('data-category');
            showCategory(category);
        });
    });

    // Initialize FAQ item toggles
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        if (question) {
            question.addEventListener('click', function() {
                toggleFAQItem(item);
            });
        }
    });

    // Initialize helpful buttons
    const helpfulButtons = document.querySelectorAll('.helpful-btn');
    helpfulButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const faqId = this.getAttribute('data-faq-id');
            const isHelpful = this.getAttribute('data-helpful') === 'true';
            markAsHelpful(faqId, isHelpful);
        });
    });
});

// Show category-specific articles
function showCategory(category) {
    // Hide all FAQ items first
    const allItems = document.querySelectorAll('.faq-item');
    allItems.forEach(item => {
        item.style.display = 'none';
    });

    // Show items for the selected category
    const categoryItems = document.querySelectorAll(`[data-category="${category}"]`);
    if (categoryItems.length > 0) {
        categoryItems.forEach(item => {
            item.style.display = 'block';
        });
    } else {
        // If no specific items found, show a message
        showCategoryMessage(category);
    }

    // Update active category button
    const categoryButtons = document.querySelectorAll('.category-btn');
    categoryButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-category') === category) {
            btn.classList.add('active');
        }
    });
}

// Show all categories
function showAllCategories() {
    const allItems = document.querySelectorAll('.faq-item');
    allItems.forEach(item => {
        item.style.display = 'block';
    });

    // Update active button
    const categoryButtons = document.querySelectorAll('.category-btn');
    categoryButtons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    const allButton = document.querySelector('.category-btn[data-category="all"]');
    if (allButton) {
        allButton.classList.add('active');
    }
}

// Filter FAQs based on search term
function filterFAQs(searchTerm) {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        
        const questionText = question ? question.textContent.toLowerCase() : '';
        const answerText = answer ? answer.textContent.toLowerCase() : '';
        
        if (questionText.includes(searchTerm) || answerText.includes(searchTerm) || searchTerm === '') {
            item.style.display = 'block';
            item.style.opacity = '1';
        } else {
            item.style.display = 'none';
            item.style.opacity = '0.5';
        }
    });
}

// Toggle FAQ item open/closed
function toggleFAQItem(item) {
    const answer = item.querySelector('.faq-answer');
    const icon = item.querySelector('.faq-toggle-icon');
    
    if (answer && icon) {
        const isOpen = answer.style.display === 'block';
        
        if (isOpen) {
            answer.style.display = 'none';
            icon.classList.remove('fa-minus');
            icon.classList.add('fa-plus');
            item.classList.remove('active');
        } else {
            answer.style.display = 'block';
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-minus');
            item.classList.add('active');
        }
    }
}

// Mark FAQ as helpful
function markAsHelpful(faqId, isHelpful) {
    // This would typically make an AJAX call to update the database
    console.log(`Marking FAQ ${faqId} as ${isHelpful ? 'helpful' : 'not helpful'}`);
    
    // Update the UI
    const button = document.querySelector(`[data-faq-id="${faqId}"][data-helpful="${isHelpful}"]`);
    if (button) {
        const countSpan = button.querySelector('.helpful-count');
        if (countSpan) {
            let currentCount = parseInt(countSpan.textContent) || 0;
            countSpan.textContent = currentCount + 1;
        }
        
        // Disable the button to prevent multiple clicks
        button.disabled = true;
        button.classList.add('clicked');
        
        // Show thank you message
        showThankYouMessage(button);
    }
}

// Show thank you message
function showThankYouMessage(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check me-1"></i>Thank you!';
    
    setTimeout(() => {
        button.innerHTML = originalText;
    }, 2000);
}

// Show category message when no items found
function showCategoryMessage(category) {
    const container = document.querySelector('.faq-container');
    if (container) {
        const messageHTML = `
            <div class="alert alert-info category-message">
                <i class="fas fa-info-circle me-2"></i>
                <strong>No articles found for "${category}" category.</strong><br>
                This section is being updated. Please check back later or contact support for assistance.
            </div>
        `;
        
        // Remove existing message
        const existingMessage = container.querySelector('.category-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Add new message
        container.insertAdjacentHTML('afterbegin', messageHTML);
    }
}
