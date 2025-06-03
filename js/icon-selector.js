/**
 * Icon Selector Functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    const iconSelector = document.getElementById('system-icon-selector');
    const selectedIconDisplay = document.getElementById('selected-icon-display');
    const iconInput = document.getElementById('system-icon');
    
    if (!iconSelector || !selectedIconDisplay || !iconInput) {
        return;
    }
    
    // Initialize dropdown
    const dropdown = new bootstrap.Dropdown(iconSelector);
    
    // Handle icon selection
    document.querySelectorAll('.icon-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const iconValue = this.getAttribute('data-value');
            const iconPath = this.getAttribute('data-path');
            const iconName = this.getAttribute('data-name');
            
            // Update hidden input value
            iconInput.value = iconValue;
            
            // Update display
            selectedIconDisplay.src = iconPath;
            
            // Update dropdown button text
            const buttonText = iconSelector.querySelector('.icon-selector-text');
            if (buttonText) {
                buttonText.textContent = iconName;
            }
        });
    });
}); 