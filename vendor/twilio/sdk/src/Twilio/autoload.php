<?php
/**
 * Autoload function for Twilio SDK
 */

spl_autoload_register(function ($class) {
    // Check if the class uses Twilio namespace
    if (strpos($class, 'Twilio\\') === 0) {
        // Convert namespace to directory structure
        $file = __DIR__ . '/../' . str_replace('\\', '/', substr($class, 7)) . '.php';
        
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    
    return false;
});

// Load basic Twilio classes directly
require_once __DIR__ . '/Rest/Client.php';
require_once __DIR__ . '/Version.php';
require_once __DIR__ . '/VersionInfo.php';
require_once __DIR__ . '/Http/Client.php';
require_once __DIR__ . '/Http/Response.php';
require_once __DIR__ . '/Exceptions/TwilioException.php';
require_once __DIR__ . '/Rest/Api.php';
require_once __DIR__ . '/Rest/Api/V2010.php';
require_once __DIR__ . '/Rest/Api/V2010/AccountInstance.php';
require_once __DIR__ . '/Rest/Api/V2010/AccountContext.php';
require_once __DIR__ . '/Rest/Api/V2010/Account/MessageList.php';
require_once __DIR__ . '/Rest/Api/V2010/Account/MessageInstance.php';
require_once __DIR__ . '/Rest/Api/V2010/Account/MessageContext.php';
?>
