<?php
// Include simple authentication
require_once __DIR__ . '/../includes/simple_auth.php';
/**
 * Admin Tools Section for Elections
 * This file provides a well-styled section for election administration tools
 * It can be included in the elections.php page
 */

// Only show for admin users
if (!isAdmin()) {
    return;
}
?>

<!-- Admin Tools Section with detailed inline styles to match the screenshot exactly -->
<div style="margin-bottom: 1.5rem; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); background-color: white;">
    <!-- Header -->
    <div style="background-color: #4169E1; color: white; padding: 0.6rem 1rem;">
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600;"><i class="fas fa-shield-alt" style="margin-right: 0.5rem;"></i> Election Administration Tools</h3>
    </div>
    
    <!-- Body -->
    <div style="padding: 0; background-color: white;">
        <div class="row" style="margin: 0;">
            <!-- Diagnostics Tool -->
            <div class="col-md-6" style="padding: 1.75rem; border-right: 1px solid #eee;">
                <div style="text-align: center;">
                    <!-- Icon Circle -->
                    <div style="margin-bottom: 1rem;">
                        <div style="width: 65px; height: 65px; border-radius: 50%; background-color: rgba(65, 105, 225, 0.15); display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <i class="fas fa-wrench" style="font-size: 1.75rem; color: #4169E1;"></i>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h4 style="font-weight: 600; font-size: 1.2rem; margin-bottom: 0.75rem;">Diagnostics</h4>
                    
                    <!-- Description -->
                    <p style="color: #6c757d; margin-bottom: 1rem; font-size: 0.9rem;">Run system checks on election configuration and setup</p>
                    
                    <!-- Button -->
                    <a href="election_diagnostic.php" style="display: inline-block; font-weight: 400; line-height: 1.5; text-align: center; text-decoration: none; vertical-align: middle; cursor: pointer; user-select: none; background-color: #4169E1; border: 1px solid #4169E1; padding: 0.375rem 1.25rem; font-size: 0.9rem; border-radius: 0.25rem; color: white;">
                        <i class="fas fa-wrench" style="margin-right: 0.5rem;"></i>Run Diagnostics
                    </a>
                </div>
            </div>
            
            <!-- Vote Privacy Tool -->
            <div class="col-md-6" style="padding: 1.75rem;">
                <div style="text-align: center;">
                    <!-- Icon Circle -->
                    <div style="margin-bottom: 1rem;">
                        <div style="width: 65px; height: 65px; border-radius: 50%; background-color: rgba(108, 117, 125, 0.15); display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <i class="fas fa-eye-slash" style="font-size: 1.75rem; color: #6c757d;"></i>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h4 style="font-weight: 600; font-size: 1.2rem; margin-bottom: 0.75rem;">Vote Privacy</h4>
                    
                    <!-- Description -->
                    <p style="color: #6c757d; margin-bottom: 1rem; font-size: 0.9rem;">Verify vote privacy and security settings are correctly configured</p>
                    
                    <!-- Button -->
                    <a href="vote_privacy.php" style="display: inline-block; font-weight: 400; line-height: 1.5; text-align: center; text-decoration: none; vertical-align: middle; cursor: pointer; user-select: none; background-color: #6c757d; border: 1px solid #6c757d; padding: 0.375rem 1.25rem; font-size: 0.9rem; border-radius: 0.25rem; color: white;">
                        <i class="fas fa-eye-slash" style="margin-right: 0.5rem;"></i>Check Privacy Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div> 
