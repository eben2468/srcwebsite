// Initialize interactive elements (theme toggle and sidebar toggle)
(function() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
    
    function initAll() {
        console.log('[INIT] Starting interactive element initialization');
        
        // Initialize theme toggle
        // DISABLED: Theme toggle is now fully managed in header.php with localStorage persistence
        /*
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[THEME] Button clicked');
                
                const html = document.documentElement;
                const current = html.getAttribute('data-bs-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                
                console.log('[THEME] Switching from', current, 'to', next);
                html.setAttribute('data-bs-theme', next);
                
                const icon = themeToggle.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-' + (next === 'dark' ? 'sun' : 'moon');
                }
            });
            console.log('[THEME] Theme toggle initialized');
        } else {
            console.warn('[THEME] Theme toggle button not found');
        }
        */
        
        // Initialize sidebar toggle
        const sidebarToggle = document.getElementById('sidebar-toggle-navbar');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[SIDEBAR] Toggle clicked');
                
                const hasShow = sidebar.classList.contains('show');
                console.log('[SIDEBAR] Currently has show:', hasShow);
                
                if (hasShow) {
                    sidebar.classList.remove('show');
                    document.body.style.overflow = '';
                    console.log('[SIDEBAR] Hiding sidebar');
                } else {
                    sidebar.classList.add('show');
                    document.body.style.overflow = 'hidden';
                    console.log('[SIDEBAR] Showing sidebar');
                }
            });
            console.log('[SIDEBAR] Sidebar toggle initialized');
        } else {
            console.warn('[SIDEBAR] Sidebar toggle button or sidebar not found');
        }
    }
})();
