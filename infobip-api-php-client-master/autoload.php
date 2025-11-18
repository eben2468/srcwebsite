<?php
/**
 * Infobip API PHP Client autoloader
 */

spl_autoload_register(function ($class) {
    // Check if the class uses Infobip namespace
    if (strpos($class, 'Infobip\\') === 0) {
        // Get the relative class path
        $relativeClass = substr($class, strlen('Infobip\\'));
        
        // Convert namespace to file path
        $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        
        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    
    return false;
}); 
