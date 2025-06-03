<?php
/**
 * OAuth Configuration File
 * 
 * This file contains the configuration settings for OAuth providers.
 * In a production environment, these values should be stored securely
 * and not committed to version control.
 */

// Base URL for your application
$baseUrl = 'http://localhost/srcwebsite';

// Google OAuth Configuration
$googleConfig = [
    'client_id'     => '123456789012-example.apps.googleusercontent.com',  // Get from Google Cloud Console
    'client_secret' => 'GOCSPX-exampleSecretKey123456',                    // Get from Google Cloud Console
    'redirect_uri'  => $baseUrl . '/auth/google_auth.php'
];

// Facebook OAuth Configuration
$facebookConfig = [
    'app_id'        => 'YOUR_FACEBOOK_APP_ID',      // Replace with your Facebook App ID
    'app_secret'    => 'YOUR_FACEBOOK_APP_SECRET',  // Replace with your Facebook App Secret
    'redirect_uri'  => $baseUrl . '/auth/facebook_auth.php'
];
?> 