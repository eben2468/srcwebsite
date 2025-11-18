// Debug script to test sidebar and theme toggle functionality
console.log('=== Debugging Sidebar and Theme Toggle ===');

// Test 1: Check if sidebar element exists
const sidebar = document.querySelector('.sidebar');
console.log('Sidebar element found:', !!sidebar);
if (sidebar) {
    console.log('Sidebar classes:', sidebar.className);
    console.log('Sidebar display:', window.getComputedStyle(sidebar).display);
    console.log('Sidebar transform:', window.getComputedStyle(sidebar).transform);
}

// Test 2: Check if sidebar toggle button exists
const toggleBtn = document.getElementById('sidebar-toggle-navbar');
console.log('Sidebar toggle button found:', !!toggleBtn);
if (toggleBtn) {
    console.log('Toggle button classes:', toggleBtn.className);
}

// Test 3: Check if theme toggle button exists
const themeToggle = document.getElementById('themeToggle');
console.log('Theme toggle button found:', !!themeToggle);
if (themeToggle) {
    console.log('Theme toggle classes:', themeToggle.className);
    console.log('Theme toggle display:', window.getComputedStyle(themeToggle).display);
    console.log('Theme toggle pointer-events:', window.getComputedStyle(themeToggle).pointerEvents);
}

// Test 4: Check current theme
const html = document.documentElement;
console.log('Current data-bs-theme:', html.getAttribute('data-bs-theme'));

// Test 5: Manual theme toggle
// DISABLED: Theme toggle is now managed in header.php, this debug listener interferes
/*
if (themeToggle) {
    console.log('Adding manual click listener to theme toggle...');
    themeToggle.addEventListener('click', function() {
        console.log('THEME TOGGLE CLICKED!');
        const current = html.getAttribute('data-bs-theme') || 'light';
        const newTheme = current === 'dark' ? 'light' : 'dark';
        console.log('Switching from', current, 'to', newTheme);
    });
}
*/

// Test 6: Manual sidebar toggle
if (toggleBtn) {
    console.log('Adding manual click listener to sidebar toggle...');
    toggleBtn.addEventListener('click', function() {
        console.log('SIDEBAR TOGGLE CLICKED!');
        const hasSHow = sidebar.classList.contains('show');
        console.log('Sidebar currently has show class:', hasSHow);
    });
}

console.log('=== Debug complete ===');
