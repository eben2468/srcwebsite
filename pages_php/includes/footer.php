        </div> <!-- Close container-fluid -->
    </div> <!-- Close main-content -->

    <!-- Mobile Footer Toggle CSS -->
    <link rel="stylesheet" href="../css/mobile-footer-toggle.css">
    <!-- Footer Mobile Fix CSS - Ensures footer is always visible -->
    <link rel="stylesheet" href="../css/footer-mobile-fix.css">
    <!-- Footer Content Visibility Fix - Ensures all content is visible on iPhone SE -->
    <!-- Removed non-existent footer-content-visibility-fix.css -->
    <!-- Footer Text Visibility Fix - Ensures all text is fully visible and not cut off -->
    <!-- Removed non-existent footer-text-visibility-fix.css -->
    <!-- Footer Bottom Spacing Fix - Prevents copyright text cutoff -->
    <!-- Removed non-existent footer-bottom-spacing-fix.css -->
    <!-- Footer Height Override CSS - Aggressive mobile height reduction -->
    <!-- Removed non-existent footer-height-override.css -->

    <!-- Footer Section - Updated <?php echo date('Y-m-d H:i:s'); ?> -->
    <footer class="src-footer" style="background: linear-gradient(to right, #4b6cb7, #182848) !important; color: white !important; padding: 1rem 0 0 0 !important; width: 100% !important; position: relative !important; z-index: 999 !important; display: block !important; min-height: 120px !important; margin-top: 1rem !important;">
        <style>
        /* Force footer alignment - inline override */
        .src-footer {
            margin-left: 280px !important;
            width: calc(100% - 280px) !important;
        }
        .src-footer .container-fluid,
        .footer-container {
            padding-left: 30px !important;
            padding-right: 30px !important;
            margin: 0 !important;
            max-width: none !important;
            width: 100% !important;
        }

        /* Mobile footer height reduction - AGGRESSIVE 80% smaller */
        @media (max-width: 991.98px) {
            .src-footer {
                min-height: 20px !important; /* Significantly reduced from 120px (83% reduction) */
                margin-top: 0.1rem !important; /* Significantly reduced from 1rem (90% reduction) */
                padding: 2px 0 !important; /* Significantly reduced from 1rem (95% reduction) */
                margin-left: 0 !important; /* Remove desktop margin on mobile */
                width: 100% !important; /* Full width on mobile */
            }
            .src-footer .container-fluid,
            .footer-container {
                padding: 3px 8px 2px 8px !important; /* Significantly reduced padding */
                margin: 0 !important;
            }
            .src-footer .footer-bottom {
                padding: 2px 4px 3px 4px !important; /* Significantly reduced padding */
                margin-top: 1px !important;
            }
            .src-footer .footer-section {
                margin-bottom: 0.2rem !important;
                padding-bottom: 0.1rem !important;
            }
            .src-footer .footer-title {
                font-size: 1rem !important;
                margin-bottom: 0.2rem !important;
            }
            .src-footer .footer-description {
                font-size: 0.85rem !important;
                margin-bottom: 0.2rem !important;
                line-height: 1.2 !important;
            }
            .src-footer .footer-links a {
                font-size: 0.85rem !important;
                padding: 1px 0 !important;
                line-height: 1.2 !important;
            }
            .src-footer .contact-item {
                margin-bottom: 0.2rem !important;
                font-size: 0.85rem !important;
                line-height: 1.2 !important;
            }
            .src-footer .social-link {
                width: 18px !important;
                height: 18px !important;
                font-size: 0.8rem !important;
            }
            .src-footer .footer-info span {
                font-size: 0.85rem !important;
                line-height: 1.2 !important;
            }
            .src-footer .copyright {
                font-size: 0.8rem !important;
                line-height: 1.2 !important;
            }
            .src-footer .footer-links-bottom a {
                font-size: 0.85rem !important;
                padding: 1px 3px !important;
            }
        }
        .footer-bottom {
            background: transparent !important;
        }
        .copyright-and-links {
            display: flex !important;
            justify-content: flex-end !important;
            align-items: center !important;
            gap: 25px !important;
        }

        /* Responsive footer adjustments */
        @media (max-width: 992px) {
            .src-footer {
                margin-left: 0 !important;
                width: 100% !important;
                position: relative !important;
                bottom: auto !important;
                min-height: auto !important;
            }
            .src-footer .container-fluid,
            .footer-container {
                padding-left: 30px !important;
                padding-right: 30px !important;
            }

            /* Ensure footer content is not cut off on mobile */
            .src-footer .footer-bottom {
                padding-bottom: 35px !important;
                margin-bottom: 0 !important;
                overflow: visible !important;
                height: auto !important;
                min-height: auto !important;
            }

            .src-footer .copyright-and-links {
                flex-direction: column !important;
                gap: 15px !important;
                align-items: center !important;
                text-align: center !important;
                width: 100% !important;
                padding: 0 10px !important;
                overflow: visible !important;
                height: auto !important;
                min-height: auto !important;
            }

            /* Ensure copyright text is fully visible */
            .src-footer .copyright {
                width: 100% !important;
                text-align: center !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                white-space: normal !important;
                overflow: visible !important;
                display: block !important;
                margin-bottom: 10px !important;
                padding: 0 5px !important;
                box-sizing: border-box !important;
            }

            /* Ensure footer links are fully visible */
            .src-footer .footer-links-bottom {
                width: 100% !important;
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: center !important;
                gap: 12px !important;
                overflow: visible !important;
                padding: 0 5px !important;
                box-sizing: border-box !important;
            }

            .src-footer .footer-links-bottom a {
                overflow: visible !important;
                display: inline-block !important;
                white-space: nowrap !important;
            }
        }

        /* Collapsed sidebar state */
        .sidebar-collapsed .src-footer {
            margin-left: 0 !important;
            width: 100% !important;
        }
        .sidebar-collapsed .src-footer .container-fluid,
        .sidebar-collapsed .footer-container {
            padding-left: 30px !important;
            padding-right: 30px !important;
        }

        /* Remove empty space after footer */
        body {
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .main-content {
            flex: 1 !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        .src-footer {
            margin-top: auto !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        html {
            margin: 0 !important;
            padding: 0 !important;
            height: 100% !important;
        }
        </style>
        <div class="container-fluid footer-container">
            <div class="row justify-content-between">
                <!-- SRC Management Section -->
                <div class="col-lg-3 col-md-6 mb-4" style="padding-right: 2rem;">
                    <div class="footer-section">
                        <h5 class="footer-title" style="color: white !important; font-size: 1.1rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-university me-2" style="color: #4cc9f0 !important;"></i>
                            SRC Management
                        </h5>
                        <p class="footer-description" style="color: rgba(255, 255, 255, 0.9) !important; font-size: 0.85rem; line-height: 1.4;">
                            Student Representative Council<br>
                            Empowering student governance through modern technology.
                        </p>
                        <div class="social-links" style="margin-top: 0.5rem;">
                            <a href="#" class="social-link" title="Facebook" style="color: #4cc9f0; margin-right: 10px; font-size: 1.2rem;">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://tiktok.com/@vvusrc" class="social-link" title="TikTok" style="color: #4cc9f0; margin-right: 10px; font-size: 1.2rem;">
                                <i class="fab fa-tiktok"></i>
                            </a>
                            <a href="#" class="social-link" title="Instagram" style="color: #4cc9f0; margin-right: 10px; font-size: 1.2rem;">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link" title="Youtube" style="color: #4cc9f0; margin-right: 10px; font-size: 1.2rem;">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Links Section -->
                <div class="col-lg-2 col-md-6 mb-4" style="padding-left: 1.5rem; padding-right: 1.5rem;">
                    <div class="footer-section">
                        <h5 class="footer-title" style="color: white !important; font-size: 1.1rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-link me-2" style="color: #4cc9f0 !important;"></i>
                            Quick Links
                        </h5>
                        <ul class="footer-links" style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 0.5rem;"><a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? '../dashboard.php' : 'dashboard.php'; ?>" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-chevron-right me-2" style="font-size: 0.7rem;"></i>Dashboard</a></li>
                            <li style="margin-bottom: 0.5rem;"><a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? '../events.php' : 'events.php'; ?>" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-chevron-right me-2" style="font-size: 0.7rem;"></i>Events</a></li>
                            <li style="margin-bottom: 0.5rem;"><a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? '../documents.php' : 'documents.php'; ?>" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-chevron-right me-2" style="font-size: 0.7rem;"></i>Documents</a></li>
                            <li style="margin-bottom: 0.5rem;"><a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? '../finance.php' : 'finance.php'; ?>" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-chevron-right me-2" style="font-size: 0.7rem;"></i>Finance</a></li>
                            <li style="margin-bottom: 0.5rem;"><a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? '../welfare.php' : 'welfare.php'; ?>" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-chevron-right me-2" style="font-size: 0.7rem;"></i>Welfare</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Support Section -->
                <div class="col-lg-2 col-md-6 mb-4" style="padding-left: 1.5rem; padding-right: 1.5rem;">
                    <div class="footer-section">
                        <h5 class="footer-title" style="color: white !important; font-size: 1.1rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-headset me-2" style="color: #4cc9f0 !important;"></i>
                            Support
                        </h5>
                        <ul class="footer-links" style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 0.5rem;"><a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? 'user-guide.php' : 'support/user-guide.php'; ?>" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-chevron-right me-2" style="font-size: 0.7rem;"></i>User Guide</a></li>
                            <li style="margin-bottom: 0.5rem;"><a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? 'help-center.php' : 'support/help-center.php'; ?>" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-chevron-right me-2" style="font-size: 0.7rem;"></i>Help Center</a></li>
                            <li style="margin-bottom: 0.5rem;"><a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? 'contact-support.php' : 'support/contact-support.php'; ?>" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-chevron-right me-2" style="font-size: 0.7rem;"></i>Contact Support</a></li>
                            <li style="margin-bottom: 0.5rem;"><a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? 'notifications.php' : 'support/notifications.php'; ?>" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-chevron-right me-2" style="font-size: 0.7rem;"></i>Notifications</a></li>
                            <li style="margin-bottom: 0.5rem;"><a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? '../settings.php' : 'settings.php'; ?>" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-size: 0.9rem;"><i class="fas fa-chevron-right me-2" style="font-size: 0.7rem;"></i>Settings</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Info Section -->
                <div class="col-lg-4 col-md-6 mb-4" style="padding-left: 2rem;">
                    <div class="footer-section">
                        <h5 class="footer-title" style="color: white !important; font-size: 1.1rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-envelope me-2" style="color: #4cc9f0 !important;"></i>
                            Contact Info
                        </h5>
                        <div class="contact-info">
                            <div class="contact-item" style="margin-bottom: 0.8rem; color: rgba(255, 255, 255, 0.9); font-size: 0.9rem;">
                                <i class="fas fa-map-marker-alt me-2" style="color: #4cc9f0; width: 20px;"></i>
                                <span>University Campus<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Victory Building</span>
                            </div>
                            <div class="contact-item" style="margin-bottom: 0.8rem; color: rgba(255, 255, 255, 0.9); font-size: 0.9rem;">
                                <i class="fas fa-phone me-2" style="color: #4cc9f0; width: 20px;"></i>
                                <span>+233 (0) 54 881 1774</span>
                            </div>
                            <div class="contact-item" style="margin-bottom: 0.8rem; color: rgba(255, 255, 255, 0.9); font-size: 0.9rem;">
                                <i class="fas fa-envelope me-2" style="color: #4cc9f0; width: 20px;"></i>
                                <span>officialsrcvvu@gmail.com</span>
                            </div>
                            <div class="contact-item" style="margin-bottom: 0.8rem; color: rgba(255, 255, 255, 0.9); font-size: 0.9rem;">
                                <i class="fas fa-clock me-2" style="color: #4cc9f0; width: 20px;"></i>
                                <span>Mon - Thu 9:00 AM - 5:00 PM <br> Fri 9:00 AM - 1:00 PM <br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<small class="text-warning">Weekend Emergency only</small></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom" style="border-top: 1px solid rgba(255, 255, 255, 0.1); margin-top: 1rem; padding-top: 0.75rem; padding-bottom: 0.5rem;">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="footer-info" style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
                            <span class="version-info" style="color: rgba(255, 255, 255, 0.8); font-size: 1rem;">
                                <i class="fas fa-code-branch me-1" style="color: #4cc9f0;"></i>
                                <?php
                                // Include version config
                                require_once __DIR__ . '/../../includes/version_config.php';
                                echo getFormattedVersion(false, true);
                                ?>
                            </span>
                            <span class="system-status" style="color: rgba(255, 255, 255, 0.8); font-size: 1rem;">
                                <i class="fas fa-circle text-success me-1" style="color: #28a745;"></i>
                                System Online
                            </span>
                            <span class="user-count" style="color: rgba(255, 255, 255, 0.8); font-size: 1rem; padding-right: 2rem">
                                <i class="fas fa-users me-1" style="color: #4cc9f0;"></i>
                                <?php
                                // Get user count dynamically
                                if (isset($conn)) {
                                    $userCountQuery = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
                                    $userCountResult = mysqli_query($conn, $userCountQuery);
                                    $userCount = $userCountResult ? mysqli_fetch_assoc($userCountResult)['total'] : 6;
                                    echo $userCount . ' Users';
                                } else {
                                    echo '1 Users';
                                }
                                ?>
                            </span>
                            <!-- Designer Credit Section -->
                        <div class="designer-credit" style="text-align: center; justify-contents: center; color: #FFD700; font-size: 0.95rem; margin-bottom: 0.75rem; font-weight: 600;">
                            <span>✨ Designed by Ebenezer Owusu, SRC Editor for 2025/26 SRC Administration 🎓</span>
                        </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        
                        <div class="copyright-and-links" style="text-align: right;">
                            <div class="copyright" style="color: rgba(255, 255, 255, 0.9); font-size: 0.85rem; margin-bottom: 0.5rem; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; overflow: visible; display: block; width: 100%; text-align: right; padding: 6; box-sizing: border-box; max-width: 100%; line-height: 1.3;">
                                © 2025 Valley View University SRC. All rights reserved.
                            </div>
                            <div class="footer-links-bottom" style="display: flex; justify-content: flex-start; gap: 0.8rem; flex-wrap: wrap; width: 100%; padding: 0; box-sizing: border-box; overflow: visible;">
                                <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? '../privacy-policy.php' : 'privacy-policy.php'; ?>" class="footer-link-bottom" style="color: #4cc9f0; text-decoration: none; font-size: 0.85rem; padding: 5px 8px; border-radius: 4px; transition: all 0.3s ease; white-space: nowrap; overflow: visible; display: inline-block;">Privacy Policy</a>
                                <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? '../terms-of-service.php' : 'terms-of-service.php'; ?>" class="footer-link-bottom" style="color: #4cc9f0; text-decoration: none; font-size: 0.85rem; padding: 5px 8px; border-radius: 4px; transition: all 0.3s ease; white-space: nowrap; overflow: visible; display: inline-block;">Terms of Service</a>
                                <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/support/') !== false) ? '../cookie-policy.php' : 'cookie-policy.php'; ?>" class="footer-link-bottom" style="color: #4cc9f0; text-decoration: none; font-size: 0.85rem; padding: 5px 8px; border-radius: 4px; transition: all 0.3s ease; white-space: nowrap; overflow: visible; display: inline-block;">Cookie Policy</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Debug script for dropdown functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Debug dropdown functionality
        const userDropdown = document.getElementById('userDropdown');
        if (userDropdown) {
            console.log('✅ User dropdown found:', userDropdown);

            // Check if Bootstrap dropdown is initialized
            setTimeout(function() {
                const dropdownInstance = bootstrap.Dropdown.getInstance(userDropdown);
                if (!dropdownInstance) {
                    console.log('🔄 Initializing Bootstrap dropdown...');
                    try {
                        const dropdown = new bootstrap.Dropdown(userDropdown);
                        console.log('✅ Dropdown initialized:', dropdown);
                    } catch (error) {
                        console.error('❌ Error initializing dropdown:', error);
                    }
                } else {
                    console.log('✅ Dropdown already initialized:', dropdownInstance);
                }
            }, 100);

            // Add click event listener for debugging
            userDropdown.addEventListener('click', function(e) {
                console.log('🖱️ User dropdown clicked');
                console.log('Button attributes:', {
                    'data-bs-toggle': this.getAttribute('data-bs-toggle'),
                    'aria-expanded': this.getAttribute('aria-expanded'),
                    'id': this.id,
                    'class': this.className
                });

                // Check if dropdown menu exists and get positioning info
                const dropdownMenu = this.nextElementSibling;
                if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                    console.log('✅ Dropdown menu found:', dropdownMenu);

                    // Get positioning information
                    const rect = dropdownMenu.getBoundingClientRect();
                    const styles = window.getComputedStyle(dropdownMenu);
                    console.log('📐 Dropdown positioning:', {
                        'position': styles.position,
                        'top': styles.top,
                        'right': styles.right,
                        'left': styles.left,
                        'z-index': styles.zIndex,
                        'display': styles.display,
                        'visibility': styles.visibility,
                        'opacity': styles.opacity,
                        'rect': {
                            top: rect.top,
                            right: rect.right,
                            bottom: rect.bottom,
                            left: rect.left,
                            width: rect.width,
                            height: rect.height
                        }
                    });
                } else {
                    console.error('❌ Dropdown menu not found');
                }
            });

            // Listen for dropdown events with detailed positioning info
            userDropdown.addEventListener('show.bs.dropdown', function() {
                console.log('📂 Dropdown is showing');
            });

            userDropdown.addEventListener('shown.bs.dropdown', function() {
                console.log('📂 Dropdown is shown');

                // Check positioning after dropdown is shown
                const dropdownMenu = this.nextElementSibling;
                if (dropdownMenu) {
                    const rect = dropdownMenu.getBoundingClientRect();
                    const styles = window.getComputedStyle(dropdownMenu);
                    const viewport = {
                        width: window.innerWidth,
                        height: window.innerHeight
                    };

                    console.log('📍 Dropdown shown positioning:', {
                        'viewport': viewport,
                        'isVisible': rect.top >= 0 && rect.left >= 0 && rect.bottom <= viewport.height && rect.right <= viewport.width,
                        'position': styles.position,
                        'top': styles.top,
                        'right': styles.right,
                        'left': styles.left,
                        'zIndex': styles.zIndex,
                        'display': styles.display,
                        'visibility': styles.visibility,
                        'opacity': styles.opacity,
                        'classes': dropdownMenu.className,
                        'rect': rect
                    });

                    // Check if dropdown is off-screen
                    if (rect.top < 0 || rect.left < 0 || rect.bottom > viewport.height || rect.right > viewport.width) {
                        console.warn('⚠️ Dropdown appears to be off-screen!');
                    }
                }
            });

            userDropdown.addEventListener('hide.bs.dropdown', function() {
                console.log('📁 Dropdown is hiding');
            });

            userDropdown.addEventListener('hidden.bs.dropdown', function() {
                console.log('📁 Dropdown is hidden');
            });
        } else {
            console.error('❌ User dropdown not found!');
        }

        // Also check notifications dropdown
        const notificationsDropdown = document.getElementById('notificationsDropdown');
        if (notificationsDropdown) {
            console.log('✅ Notifications dropdown found');
        } else {
            console.log('⚠️ Notifications dropdown not found');
        }
    });
    </script>

    <?php
    // Define the path prefix for scripts - account for /vvusrc/pages_php/ base path
    // Need to go up one level: out of pages_php to vvusrc root
    $js_prefix = '../';
    ?>



    <!-- Custom scripts -->
    <script src="<?php echo $js_prefix; ?>js/dashboard.js"></script>
    <script src="<?php echo $js_prefix; ?>js/dashboard-animations.js"></script>
    <script src="<?php echo $js_prefix; ?>js/auto-dismiss.js"></script>
    <script src="<?php echo $js_prefix; ?>js/force-dismiss-all.js"></script>
    <script src="<?php echo $js_prefix; ?>js/force-css-refresh.js"></script>
    <script src="<?php echo $js_prefix; ?>assets/js/image-viewer.js"></script>
    <script src="<?php echo $js_prefix; ?>js/modal-helper.js"></script>

    <script>
    // Add mobile sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const userDropdown = document.getElementById('userDropdown');
        if (userDropdown) {
            const dropdownToggle = new bootstrap.Dropdown(userDropdown);
        }

        // Check if footer sidebar toggle should be disabled (used on specific pages)
        if (window.DISABLE_FOOTER_SIDEBAR_TOGGLE) {
            console.log('Footer sidebar toggle disabled for this page');
            // Skip the rest of the footer sidebar toggle initialization
        } else {
            // Mobile sidebar toggle functionality
            const sidebar = document.querySelector('.sidebar') ||
                           document.querySelector('.dashboard-sidebar') ||
                           document.querySelector('[class*="sidebar"]');
            const toggleBtn = document.getElementById('sidebar-toggle-navbar') ||
                             document.querySelector('[data-bs-toggle="sidebar"]') ||
                             document.querySelector('.sidebar-toggle');
            const closeBtn = document.getElementById('sidebar-close-btn');

            if (sidebar && toggleBtn) {
                console.log('Mobile sidebar toggle initialized');

                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const isMobile = window.innerWidth <= 991;
                    console.log('Toggle clicked, isMobile:', isMobile);

                    if (isMobile) {
                        const isHidden = sidebar.classList.contains('hide');
                        const isVisible = sidebar.classList.contains('show') || (!sidebar.classList.contains('hide'));
                        console.log('Footer toggle - isHidden:', isHidden, 'isVisible:', isVisible);
                        console.log('Footer sidebar classes before:', sidebar.className);

                        if (isHidden || !isVisible) {
                            sidebar.classList.remove('hide', 'collapsed');
                            sidebar.classList.add('show');
                            document.body.classList.add('sidebar-open');
                            document.body.style.overflow = 'hidden';

                            // Reset any desktop styles
                            sidebar.style.width = '';
                            const mainContent = document.querySelector('.main-content');
                            if (mainContent) mainContent.style.marginLeft = '';

                            console.log('Showing mobile sidebar via footer');
                        } else {
                            sidebar.classList.remove('show');
                            sidebar.classList.add('hide');
                            document.body.classList.remove('sidebar-open');
                            document.body.style.overflow = '';
                            console.log('Hiding mobile sidebar via footer');
                        }

                        console.log('Footer sidebar classes after:', sidebar.className);
                    }
                });
            } else {
                console.warn('Mobile sidebar toggle: Required elements not found');
                console.log('Sidebar found:', !!sidebar);
                console.log('Toggle button found:', !!toggleBtn);
            }

            // Add close button functionality
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const isMobile = window.innerWidth <= 991;
                    console.log('Close button clicked, isMobile:', isMobile);

                    if (isMobile) {
                        sidebar.classList.remove('show');
                        sidebar.classList.add('hide');
                        document.body.classList.remove('sidebar-open');
                        document.body.style.overflow = '';
                        console.log('Hiding mobile sidebar via close button');
                    }
                });
            } else {
                // Close button not found - this is optional functionality
                console.log('Sidebar close button not found (optional)');
            }

            // Sidebar dropdown functionality
            const sidebarDropdowns = document.querySelectorAll('.sidebar-dropdown > .sidebar-link');
            sidebarDropdowns.forEach(function(dropdown) {
                dropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    const parent = this.parentElement;
                    const isActive = parent.classList.contains('active');

                    // Close all other dropdowns
                    document.querySelectorAll('.sidebar-dropdown.active').forEach(function(activeDropdown) {
                        if (activeDropdown !== parent) {
                            activeDropdown.classList.remove('active');
                        }
                    });

                    // Toggle current dropdown
                    if (isActive) {
                        parent.classList.remove('active');
                    } else {
                        parent.classList.add('active');
                    }
                });
            });

            // Auto-open finance dropdown if on a finance page
            const currentPage = window.location.pathname.split('/').pop();
            if (currentPage.startsWith('finance-') || currentPage === 'finance.php') {
                const financeDropdown = document.querySelector('.sidebar-dropdown');
                if (financeDropdown) {
                    financeDropdown.classList.add('active');
                }
            }
        } // Close the else block for DISABLE_FOOTER_SIDEBAR_TOGGLE

        // Force footer alignment and remove bottom space - JavaScript override
        setTimeout(function() {
            const footerContainer = document.querySelector('.src-footer .container-fluid');
            if (footerContainer) {
                footerContainer.style.paddingLeft = '30px';
                footerContainer.style.paddingRight = '30px';
                footerContainer.style.margin = '0';
                footerContainer.style.maxWidth = 'none';
                footerContainer.style.width = '100%';
            }

            const footerBottom = document.querySelector('.footer-bottom');
            if (footerBottom) {
                footerBottom.style.background = 'transparent';
            }

            const copyrightLinks = document.querySelector('.copyright-and-links');
            if (copyrightLinks) {
                copyrightLinks.style.display = 'flex';
                copyrightLinks.style.justifyContent = 'flex-end';
                copyrightLinks.style.alignItems = 'center';
                copyrightLinks.style.gap = '25px';
            }

            // Remove any bottom spacing
            const body = document.body;
            const html = document.documentElement;

            body.style.margin = '0';
            body.style.padding = '0';
            body.style.minHeight = '100vh';
            body.style.display = 'flex';
            body.style.flexDirection = 'column';

            html.style.margin = '0';
            html.style.padding = '0';
            html.style.height = '100%';

            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.style.flex = '1';
                mainContent.style.marginBottom = '0';
                mainContent.style.paddingBottom = '0';
            }

            const footer = document.querySelector('.src-footer');
            if (footer) {
                footer.style.marginTop = 'auto';
                footer.style.marginBottom = '0';
                footer.style.paddingBottom = '0';
            }
        }, 100);
    });
    </script>

    <!-- Mobile Footer Toggle JavaScript - DISABLED (footer always visible) -->
    <!-- <script src="../js/mobile-footer-toggle.js"></script> -->
</body>
</html>
