        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="copyright">
                    <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> SRC Management System. All rights reserved.
                </div>
                <div class="footer-divider"></div>
                <div class="footer-links">
                    <a href="#" class="text-white text-decoration-none"><i class="fas fa-shield-alt"></i> Privacy Policy</a>
                </div>
                <div class="footer-divider"></div>
                <div class="footer-links">
                    <a href="#" class="text-white text-decoration-none"><i class="fas fa-question-circle"></i> Help</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php 
    // Define the path prefix for scripts if not already set
    $js_prefix = isset($GLOBALS['path_prefix']) ? dirname($GLOBALS['path_prefix']) : '..';
    $js_prefix = rtrim($js_prefix, '/') . '/';
    ?>
    
    <!-- Custom scripts -->
    <script src="<?php echo $js_prefix; ?>js/dashboard.js"></script>
    <!-- Enhanced dashboard animations -->
    <script src="<?php echo $js_prefix; ?>js/dashboard-animations.js"></script>
    <!-- Auto-dismiss notifications script -->
    <script src="<?php echo $js_prefix; ?>js/auto-dismiss.js"></script>
    <!-- Image viewer script -->
    <script src="<?php echo $js_prefix; ?>assets/js/image-viewer.js"></script>
    <!-- Modal helper script -->
    <script src="<?php echo $js_prefix; ?>js/modal-helper.js"></script>
    
    <!-- Custom page-specific footer script if defined -->
    <?php if (isset($customFooterScript) && !empty($customFooterScript)): ?>
    <script src="<?php echo $customFooterScript; ?>"></script>
    <?php endif; ?>
    
    <script>
    // Add mobile sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const userDropdown = document.getElementById('userDropdown');
        if (userDropdown) {
            const dropdownToggle = new bootstrap.Dropdown(userDropdown);
        }
    });
    </script>
</body>
</html>