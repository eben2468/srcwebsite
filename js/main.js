// Mock authentication
const mockUsers = [
    { email: 'admin@example.com', password: 'password', name: 'Admin User', role: 'admin' },
    { email: 'user@example.com', password: 'password', name: 'Regular User', role: 'user' }
];

// Helper functions
function getLocalStorageItem(key) {
    return localStorage.getItem(key) ? JSON.parse(localStorage.getItem(key)) : null;
}

function setLocalStorageItem(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}

function getCurrentUser() {
    return getLocalStorageItem('user');
}

function isLoggedIn() {
    return !!getCurrentUser();
}

function logout() {
    localStorage.removeItem('user');
    localStorage.removeItem('authToken');
    window.location.href = '../pages/login.html';
}

// Initialize UI
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a protected page
    const protectedPages = ['dashboard.html', 'events.html', 'news.html', 'documents.html', 'elections.html', 'users.html', 'budget.html', 'settings.html'];
    const currentPage = window.location.pathname.split('/').pop();
    
    if (protectedPages.includes(currentPage) && !isLoggedIn()) {
        window.location.href = '../pages/login.html?redirect=' + currentPage;
        return;
    }
    
    // Set up logout button
    const logoutButton = document.getElementById('logout-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }
    
    // Initialize specific page functionality
    initCurrentPage();
});

// Login functionality
function handleLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const errorMessage = document.getElementById('error-message');
    
    // Clear previous error
    if (errorMessage) {
        errorMessage.textContent = '';
        errorMessage.style.display = 'none';
    }
    
    // Find user
    const user = mockUsers.find(u => u.email === email && u.password === password);
    
    if (user) {
        // Store user info in localStorage
        setLocalStorageItem('user', {
            name: user.name,
            email: user.email,
            role: user.role
        });
        localStorage.setItem('authToken', 'mock-jwt-token-' + user.role);
        
        // Redirect to dashboard
        window.location.href = '../pages/dashboard.html';
    } else {
        // Show error
        if (errorMessage) {
            errorMessage.textContent = 'Invalid email or password';
            errorMessage.style.display = 'block';
        }
    }
}

// Initialize current page
function initCurrentPage() {
    const currentPage = window.location.pathname.split('/').pop();
    
    // Add 'active' class to current sidebar link
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    sidebarLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
        }
    });
    
    // Ensure settings link is visible in sidebar
    ensureSidebarLinksVisible();
    
    // Initialize page-specific functionality
    switch (currentPage) {
        case 'dashboard.html':
            initDashboard();
            break;
        case 'events.html':
            initEvents();
            break;
        case 'login.html':
            initLogin();
            break;
        // Add other pages as needed
    }
}

// Ensure all sidebar links are visible, especially the settings link
function ensureSidebarLinksVisible() {
    const sidebar = document.querySelector('.sidebar') ||
                   document.querySelector('.dashboard-sidebar') ||
                   document.querySelector('[class*="sidebar"]');
    if (!sidebar) return;
    
    // Force sidebar to be scrollable
    sidebar.style.overflowY = 'scroll';
    
    // Check for the settings link
    const settingsLink = document.querySelector('.sidebar-link[href="settings.php"]');
    if (settingsLink) {
        // Make sure settings link is visible
        setTimeout(() => {
            settingsLink.scrollIntoView({ behavior: 'auto', block: 'nearest' });
        }, 100);
    }
}

// Initialize Dashboard
function initDashboard() {
    const user = getCurrentUser();
    if (!user) return;
    
    // Set user name
    const userNameElement = document.getElementById('user-name');
    if (userNameElement) {
        userNameElement.textContent = user.name;
    }
    
    // Load dashboard data
    loadDashboardStats();
    loadRecentEvents();
    loadRecentNews();
}

// Initialize Login
function initLogin() {
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
}

// Load dashboard stats (mock data)
function loadDashboardStats() {
    const stats = {
        events: 6,
        news: 6,
        documents: 6,
        activeElections: 1
    };
    
    for (const [key, value] of Object.entries(stats)) {
        const element = document.getElementById(`${key}-count`);
        if (element) {
            element.textContent = value;
        }
    }
}

// Load recent events (mock data)
function loadRecentEvents() {
    const events = [
        { id: 1, name: 'Orientation Week', date: '2023-08-15', location: 'Main Campus', status: 'Upcoming' },
        { id: 2, name: 'Leadership Workshop', date: '2023-08-20', location: 'Conference Hall', status: 'Upcoming' },
        { id: 3, name: 'Cultural Festival', date: '2023-09-05', location: 'Student Center', status: 'Upcoming' }
    ];
    
    const container = document.getElementById('recent-events');
    if (!container) return;
    
    container.innerHTML = events.map(event => `
        <tr>
            <td>${event.name}</td>
            <td>${event.date}</td>
            <td>${event.location}</td>
            <td><span class="badge bg-success">${event.status}</span></td>
            <td>
                <a href="event-detail.html?id=${event.id}" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye"></i> View
                </a>
            </td>
        </tr>
    `).join('');
}

// Load recent news (mock data)
function loadRecentNews() {
    const news = [
        { id: 1, title: 'SRC Elections Announced', date: '2023-07-20', author: 'Admin', status: 'Published' },
        { id: 2, title: 'New Campus Facilities Opening', date: '2023-07-15', author: 'Admin', status: 'Published' },
        { id: 3, title: 'Student Achievements 2023', date: '2023-07-10', author: 'Admin', status: 'Published' }
    ];
    
    const container = document.getElementById('recent-news');
    if (!container) return;
    
    container.innerHTML = news.map(item => `
        <tr>
            <td>${item.title}</td>
            <td>${item.date}</td>
            <td>${item.author}</td>
            <td><span class="badge bg-success">${item.status}</span></td>
        </tr>
    `).join('');
}

// Initialize Events
function initEvents() {
    loadEvents();
}

// Load events (mock data)
function loadEvents() {
    const events = [
        { id: 1, name: 'Orientation Week', date: '2023-08-15', location: 'Main Campus', status: 'Upcoming' },
        { id: 2, name: 'Leadership Workshop', date: '2023-08-20', location: 'Conference Hall', status: 'Upcoming' },
        { id: 3, name: 'Cultural Festival', date: '2023-09-05', location: 'Student Center', status: 'Upcoming' },
        { id: 4, name: 'Career Fair', date: '2023-09-15', location: 'Exhibition Hall', status: 'Planning' },
        { id: 5, name: 'Academic Excellence Awards', date: '2023-10-10', location: 'Auditorium', status: 'Planning' },
        { id: 6, name: 'Sports Tournament', date: '2023-07-10', location: 'Sports Complex', status: 'Completed' }
    ];
    
    const container = document.getElementById('events-table-body');
    if (!container) return;
    
    container.innerHTML = events.map(event => `
        <tr>
            <td>${event.name}</td>
            <td>${event.date}</td>
            <td>${event.location}</td>
            <td><span class="badge bg-${event.status === 'Upcoming' ? 'success' : event.status === 'Planning' ? 'warning' : 'secondary'}">${event.status}</span></td>
            <td>
                <a href="event-detail.html?id=${event.id}" class="btn btn-sm btn-primary btn-action">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="event-edit.html?id=${event.id}" class="btn btn-sm btn-secondary btn-action">
                    <i class="fas fa-edit"></i>
                </a>
                <button class="btn btn-sm btn-danger btn-action" onclick="deleteEvent(${event.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Delete event (mock)
function deleteEvent(id) {
    if (confirm('Are you sure you want to delete this event?')) {
        alert('Event deleted successfully (mock)');
        // In a real app, we would make an API call and then reload the events
        loadEvents();
    }
}

// Get URL parameters
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    const results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
} 