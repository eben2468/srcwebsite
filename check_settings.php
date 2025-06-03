<?php
// Include required files
require_once 'db_config.php';
require_once 'settings_functions.php';

// Check all settings
echo "All settings:\n";
print_r(getAllSettings());

// Check features specifically
echo "\nFeatures settings:\n";
print_r(getAllSettings('features'));

// Check individual features
echo "\nIndividual feature checks:\n";
$features = ['enable_elections', 'enable_documents', 'enable_news', 'enable_budget'];
foreach ($features as $feature) {
    $enabled = isFeatureEnabled($feature);
    echo $feature . ": " . ($enabled ? "Enabled" : "Disabled") . "\n";
}

// Done
echo "\nCheck complete.\n";
?> 