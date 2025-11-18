/**
 * User Guide JavaScript Functions
 * Handles search, navigation, and tutorial functionality
 */

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('guideSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const sections = document.querySelectorAll('.guide-section');

            sections.forEach(section => {
                const text = section.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    section.style.display = 'block';
                    section.style.opacity = '1';
                } else {
                    if (searchTerm === '') {
                        section.style.display = 'block';
                        section.style.opacity = '1';
                    } else {
                        section.style.display = 'none';
                        section.style.opacity = '0.5';
                    }
                }
            });
        });
    }

    // Smooth scrolling for anchor links
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            scrollToSection(targetId);
        });
    });
});

// Scroll to section functionality
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        element.style.backgroundColor = '#f8f9fa';
        setTimeout(() => {
            element.style.backgroundColor = '';
        }, 2000);
    }
}

// Tutorial modal functionality
function showTutorial(type) {
    const tutorials = {
        'overview': {
            title: 'System Overview',
            content: 'This tutorial covers the basic navigation and features of the VVU SRC Management System.',
            duration: '5:30',
            steps: [
                'Navigate to the dashboard and explore the main menu',
                'Understand the sidebar navigation structure',
                'Learn about user roles and permissions',
                'Explore notification system and alerts',
                'Understand the header controls and user menu'
            ],
            links: [
                { text: 'Go to Dashboard', url: '../dashboard.php' },
                { text: 'View Profile', url: '../profile.php' }
            ]
        },
        'profile': {
            title: 'Profile Setup',
            content: 'Learn how to set up and manage your student profile, including uploading photos and updating information.',
            duration: '3:15',
            steps: [
                'Access your profile from the user menu',
                'Upload a profile picture',
                'Update personal information',
                'Set your preferences and notifications',
                'Save and verify your changes'
            ],
            links: [
                { text: 'Edit Profile', url: '../profile.php' },
                { text: 'Account Settings', url: '../settings.php' }
            ]
        },
        'users': {
            title: 'User Management',
            content: 'Complete guide to managing users, roles, and permissions in the system.',
            duration: '8:15',
            steps: [
                'Access the user management section',
                'Create new user accounts',
                'Assign roles and permissions',
                'Manage user status and access',
                'Bulk import users from spreadsheet'
            ],
            links: [
                { text: 'Manage Users', url: '../users.php' },
                { text: 'Bulk Import', url: '../bulk-users.php' }
            ]
        },
        'events': {
            title: 'Event Management',
            content: 'Learn how to create, manage, and participate in SRC events and activities.',
            duration: '6:45',
            steps: [
                'Navigate to the events section',
                'Create a new event with details',
                'Set event date, time, and location',
                'Manage event attendees and RSVPs',
                'View and export attendance reports'
            ],
            links: [
                { text: 'View Events', url: '../events.php' },
                { text: 'Create Event', url: '../events.php?action=create' }
            ]
        },
        'finance': {
            title: 'Financial Reports',
            content: 'Generate and manage financial reports, budgets, and expense tracking.',
            duration: '7:20',
            steps: [
                'Access the finance management section',
                'Create budget categories and allocations',
                'Record income and expenses',
                'Generate financial reports',
                'Export data for external analysis'
            ],
            links: [
                { text: 'Finance Dashboard', url: '../finance.php' },
                { text: 'Budget Management', url: '../budget.php' }
            ]
        }
    };

    const tutorial = tutorials[type];
    if (tutorial) {
        // Create a more user-friendly modal instead of alert
        showTutorialModal(tutorial);
    }
}

// Create tutorial modal
function showTutorialModal(tutorial) {
    // Remove existing modal if any
    const existingModal = document.getElementById('tutorialModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Generate steps HTML
    let stepsHTML = '';
    if (tutorial.steps && tutorial.steps.length > 0) {
        stepsHTML = `
            <div class="mb-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-list-ol me-2"></i>Tutorial Steps:</h6>
                <ol class="list-group list-group-numbered">
                    ${tutorial.steps.map(step => `
                        <li class="list-group-item border-0 ps-0">${step}</li>
                    `).join('')}
                </ol>
            </div>
        `;
    }

    // Generate links HTML
    let linksHTML = '';
    if (tutorial.links && tutorial.links.length > 0) {
        linksHTML = `
            <div class="mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-external-link-alt me-2"></i>Quick Actions:</h6>
                <div class="d-flex gap-2 flex-wrap">
                    ${tutorial.links.map(link => `
                        <a href="${link.url}" class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="fas fa-arrow-right me-1"></i>${link.text}
                        </a>
                    `).join('')}
                </div>
            </div>
        `;
    }

    // Create modal HTML
    const modalHTML = `
        <div class="modal fade" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h5 class="modal-title" id="tutorialModalLabel">
                            <i class="fas fa-play-circle me-2"></i>${tutorial.title}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                                <i class="fas fa-graduation-cap fa-2x text-primary"></i>
                            </div>
                            <h6 class="text-muted mt-2 mb-0">Duration: ${tutorial.duration}</h6>
                        </div>

                        <div class="alert alert-light border-start border-primary border-4">
                            <p class="mb-0">${tutorial.content}</p>
                        </div>

                        ${stepsHTML}
                        ${linksHTML}

                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Interactive Tutorial:</strong> Follow the steps above and use the quick action buttons to practice in the actual system.
                            If you need additional help, contact our support team.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                        <button type="button" class="btn btn-success" onclick="startInteractiveTutorial('${tutorial.title.toLowerCase().replace(/\s+/g, '-')}')">
                            <i class="fas fa-play me-2"></i>Start Interactive Tutorial
                        </button>
                        <button type="button" class="btn btn-primary" onclick="window.open('../help-center.php', '_blank')">
                            <i class="fas fa-question-circle me-2"></i>Get More Help
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('tutorialModal'));
    modal.show();

    // Clean up when modal is hidden
    document.getElementById('tutorialModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Interactive tutorial functionality
function startInteractiveTutorial(tutorialType) {
    // Close the modal first
    const modal = bootstrap.Modal.getInstance(document.getElementById('tutorialModal'));
    if (modal) {
        modal.hide();
    }

    // Start the interactive tutorial based on type
    switch(tutorialType) {
        case 'system-overview':
            startSystemOverviewTutorial();
            break;
        case 'profile-setup':
            startProfileSetupTutorial();
            break;
        case 'user-management':
            startUserManagementTutorial();
            break;
        case 'event-management':
            startEventManagementTutorial();
            break;
        case 'financial-reports':
            startFinancialReportsTutorial();
            break;
        default:
            showToast('Tutorial not available', 'This interactive tutorial is coming soon!', 'info');
    }
}

// System Overview Interactive Tutorial
function startSystemOverviewTutorial() {
    const steps = [
        {
            element: '.sidebar',
            title: 'Navigation Sidebar',
            content: 'This is your main navigation menu. Use it to access different sections of the system.',
            position: 'right'
        },
        {
            element: '.header',
            title: 'Header Controls',
            content: 'The header contains notifications, theme toggle, and your user menu.',
            position: 'bottom'
        },
        {
            element: '.user-profile',
            title: 'User Profile',
            content: 'Your profile information and quick access to account settings.',
            position: 'right'
        }
    ];

    startTutorialSteps(steps, 'System Overview Tutorial');
}

// Profile Setup Interactive Tutorial
function startProfileSetupTutorial() {
    showToast('Profile Tutorial', 'Redirecting to your profile page to start the interactive tutorial...', 'info');
    setTimeout(() => {
        window.open('../profile.php?tutorial=true', '_blank');
    }, 2000);
}

// User Management Interactive Tutorial
function startUserManagementTutorial() {
    showToast('User Management Tutorial', 'Redirecting to user management page...', 'info');
    setTimeout(() => {
        window.open('../users.php?tutorial=true', '_blank');
    }, 2000);
}

// Event Management Interactive Tutorial
function startEventManagementTutorial() {
    showToast('Event Management Tutorial', 'Redirecting to events page...', 'info');
    setTimeout(() => {
        window.open('../events.php?tutorial=true', '_blank');
    }, 2000);
}

// Financial Reports Interactive Tutorial
function startFinancialReportsTutorial() {
    showToast('Financial Reports Tutorial', 'Redirecting to finance page...', 'info');
    setTimeout(() => {
        window.open('../finance.php?tutorial=true', '_blank');
    }, 2000);
}

// Generic tutorial steps handler
function startTutorialSteps(steps, title) {
    let currentStep = 0;

    function showStep(stepIndex) {
        if (stepIndex >= steps.length) {
            showToast('Tutorial Complete', 'You have completed the ' + title + '!', 'success');
            return;
        }

        const step = steps[stepIndex];
        const element = document.querySelector(step.element);

        if (!element) {
            console.warn('Tutorial element not found:', step.element);
            showStep(stepIndex + 1);
            return;
        }

        // Highlight the element
        element.style.outline = '3px solid #007bff';
        element.style.outlineOffset = '2px';
        element.style.position = 'relative';
        element.style.zIndex = '1050';

        // Create tooltip
        const tooltip = document.createElement('div');
        tooltip.className = 'tutorial-tooltip';
        tooltip.innerHTML = `
            <div class="card shadow-lg border-0" style="max-width: 300px;">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">${step.title}</h6>
                </div>
                <div class="card-body">
                    <p class="mb-3">${step.content}</p>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-sm btn-outline-secondary" onclick="endTutorial()">Skip</button>
                        <div>
                            <span class="text-muted small">${stepIndex + 1} of ${steps.length}</span>
                            <button class="btn btn-sm btn-primary ms-2" onclick="nextTutorialStep()">
                                ${stepIndex === steps.length - 1 ? 'Finish' : 'Next'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Position tooltip
        const rect = element.getBoundingClientRect();
        tooltip.style.position = 'fixed';
        tooltip.style.zIndex = '1051';

        switch(step.position) {
            case 'right':
                tooltip.style.left = (rect.right + 10) + 'px';
                tooltip.style.top = rect.top + 'px';
                break;
            case 'bottom':
                tooltip.style.left = rect.left + 'px';
                tooltip.style.top = (rect.bottom + 10) + 'px';
                break;
            case 'left':
                tooltip.style.right = (window.innerWidth - rect.left + 10) + 'px';
                tooltip.style.top = rect.top + 'px';
                break;
            default: // top
                tooltip.style.left = rect.left + 'px';
                tooltip.style.bottom = (window.innerHeight - rect.top + 10) + 'px';
        }

        document.body.appendChild(tooltip);

        // Store current step and tooltip for cleanup
        window.currentTutorialStep = stepIndex;
        window.currentTutorialTooltip = tooltip;
        window.currentTutorialElement = element;
    }

    // Global functions for tutorial navigation
    window.nextTutorialStep = function() {
        cleanupCurrentStep();
        currentStep++;
        showStep(currentStep);
    };

    window.endTutorial = function() {
        cleanupCurrentStep();
        showToast('Tutorial Ended', 'Tutorial has been ended. You can restart it anytime!', 'info');
    };

    function cleanupCurrentStep() {
        if (window.currentTutorialTooltip) {
            window.currentTutorialTooltip.remove();
        }
        if (window.currentTutorialElement) {
            window.currentTutorialElement.style.outline = '';
            window.currentTutorialElement.style.outlineOffset = '';
            window.currentTutorialElement.style.zIndex = '';
        }
    }

    // Start the tutorial
    showStep(0);
}

// Toast notification function
function showToast(title, message, type = 'info') {
    const toastHTML = `
        <div class="toast align-items-center text-white bg-${type === 'info' ? 'primary' : type === 'success' ? 'success' : 'warning'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1055';
        document.body.appendChild(toastContainer);
    }

    // Add toast to container
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);

    // Show toast
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();

    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// Button click handlers
function handleGuideButton(action, target) {
    switch(action) {
        case 'scroll':
            scrollToSection(target);
            break;
        case 'open':
            window.open(target, '_blank');
            break;
        case 'tutorial':
            showTutorial(target);
            break;
        default:
            console.log('Unknown action:', action);
    }
}
