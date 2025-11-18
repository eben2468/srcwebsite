<?php
/**
 * Messaging Service
 * 
 * This file provides functions to send messages through various channels:
 * - Email
 * - SMS
 * - WhatsApp
 * - In-App Notifications
 */

// Ensure this is being included from a valid source
if (!defined('INCLUDED_FROM_APP')) {
    define('INCLUDED_FROM_APP', true);
}

// Check PHP version before loading Infobip classes
$phpVersion = phpversion();
if (version_compare($phpVersion, '8.3.0', '<')) {
    // Create fallback classes for older PHP versions
    if (!class_exists('\\Infobip\\Configuration')) {
        // Define compatibility classes in global namespace
        class InfobipCompatConfiguration {
            private $host;
            private $apiKey;
            private $apiKeyPrefix = 'App';
            private $apiKeyHeader = 'Authorization';
            private $tempFolderPath;
            
            public function __construct() {
                $this->tempFolderPath = sys_get_temp_dir();
            }
            
            public function setHost($host) {
                $this->host = $host;
                return $this;
            }
            
            public function setApiKey($header, $key) {
                $this->apiKey = $key;
                return $this;
            }
            
            public function setApiKeyPrefix($header, $prefix) {
                $this->apiKeyPrefix = $prefix;
                return $this;
            }
            
            public function getHost() {
                return $this->host;
            }
            
            public function getApiKey() {
                return sprintf('%s %s', $this->apiKeyPrefix, $this->apiKey);
            }
            
            public function getApiKeyHeader() {
                return $this->apiKeyHeader;
            }
            
            public function getUserAgent() {
                return 'infobip-api-client-php-compat/1.0/PHP';
            }
            
            public function getTempFolderPath() {
                return $this->tempFolderPath;
            }
        }
    }
}

// Include Infobip autoloader if it exists
$infobipAutoload = __DIR__ . '/infobip-api-php-client-master/autoload.php';
if (file_exists($infobipAutoload)) {
    require_once $infobipAutoload;
}

// Include Twilio autoloader if it exists
$twilioAutoload = __DIR__ . '/twilio-php-main/src/Twilio/autoload.php';
if (file_exists($twilioAutoload)) {
    require_once $twilioAutoload;
}

// Include necessary database functions
if (file_exists(__DIR__ . '/includes/db_functions.php')) {
    require_once __DIR__ . '/includes/db_functions.php';
}

/**
 * Send an email using a configured email service
 * 
 * @param string|array $to Recipient email address(es)
 * @param string $subject Email subject
 * @param string $message Email body (can be HTML)
 * @param array $attachments Optional array of attachments
 * @param array $options Additional options (cc, bcc, reply-to, etc.)
 * @return array Result with success status and message
 */
function sendEmail($to, $subject, $message, $attachments = [], $options = []) {
    // Default response
    $response = [
        'success' => false,
        'message' => 'Email sending failed',
        'details' => null
    ];
    
    // Load configuration from database or file
    $emailConfig = getEmailConfig();
    
    // Determine which email service to use
    $emailService = $emailConfig['service'] ?? 'phpmailer';
    
    try {
        switch ($emailService) {
            case 'phpmailer':
                $response = sendEmailWithPHPMailer($to, $subject, $message, $attachments, $options, $emailConfig);
                break;
                
            case 'smtp':
                $response = sendEmailWithSMTP($to, $subject, $message, $attachments, $options, $emailConfig);
                break;
                
            case 'sendgrid':
                $response = sendEmailWithSendGrid($to, $subject, $message, $attachments, $options, $emailConfig);
                break;
                
            case 'mailchimp':
                $response = sendEmailWithMailchimp($to, $subject, $message, $attachments, $options, $emailConfig);
                break;
                
            case 'infobip':
                $response = sendEmailWithInfobip($to, $subject, $message, $attachments, $options, $emailConfig);
                break;
                
            default:
                $response['message'] = 'Invalid email service configuration';
        }
    } catch (Exception $e) {
        $response['message'] = 'Email sending error: ' . $e->getMessage();
        $response['details'] = $e->getTraceAsString();
        // Log the error
        error_log('Email sending error: ' . $e->getMessage());
    }
    
    return $response;
}

/**
 * Send an SMS using a configured SMS service
 * 
 * @param string|array $to Recipient phone number(s)
 * @param string $message SMS message
 * @param array $options Additional options
 * @param string $provider Specific provider to use (overrides default)
 * @return array Result with success status and message
 */
function sendSMS($to, $message, $options = [], $provider = null) {
    // Default response
    $response = [
        'success' => false,
        'message' => 'SMS sending failed',
        'details' => null
    ];
    
    // Load configuration from database
    $smsConfig = loadSMSConfig();
    
    // Determine which SMS service to use
    $smsService = $provider ?: ($smsConfig['default_provider'] ?? 'none');
    
    if ($smsService === 'none') {
        return [
            'success' => false,
            'message' => 'No SMS provider configured',
            'details' => null
        ];
    }
    
    try {
        switch ($smsService) {
            case 'twilio':
                $response = sendSMSWithTwilio($to, $message, $options, $smsConfig);
                break;

            case 'infobip':
                $response = sendSMSWithInfobip($to, $message, $options, $smsConfig);
                break;

            case 'zenoph':
                $response = sendSMSWithZenoph($to, $message, $options, $smsConfig);
                break;

            default:
                $response['message'] = 'Invalid SMS provider: ' . $smsService;
        }
    } catch (Exception $e) {
        $response['message'] = 'SMS sending error: ' . $e->getMessage();
        $response['details'] = $e->getTraceAsString();
        // Log the error
        error_log('SMS sending error: ' . $e->getMessage());
    }
    
    return $response;
}

/**
 * Send a WhatsApp message using a configured service
 * 
 * @param string|array $to Recipient phone number(s)
 * @param string $message WhatsApp message
 * @param array $options Additional options (media, templates, etc.)
 * @param string $provider Specific provider to use (overrides default)
 * @return array Result with success status and message
 */
function sendWhatsApp($to, $message, $options = [], $provider = null) {
    // Default response
    $response = [
        'success' => false,
        'message' => 'WhatsApp sending failed',
        'details' => null
    ];
    
    // Load configuration from database
    $waConfig = loadWhatsAppConfig();
    
    // Determine which WhatsApp service to use
    $waService = $provider ?: ($waConfig['default_provider'] ?? 'none');
    
    if ($waService === 'none') {
        return [
            'success' => false,
            'message' => 'No WhatsApp provider configured',
            'details' => null
        ];
    }
    
    try {
        switch ($waService) {
            case 'twilio':
                $response = sendWhatsAppWithTwilio($to, $message, $options, $waConfig);
                break;
                
            case 'infobip':
                $response = sendWhatsAppWithInfobip($to, $message, $options, $waConfig);
                break;
                
            default:
                $response['message'] = 'Invalid WhatsApp provider: ' . $waService;
        }
    } catch (Exception $e) {
        $response['message'] = 'WhatsApp sending error: ' . $e->getMessage();
        $response['details'] = $e->getTraceAsString();
        // Log the error
        error_log('WhatsApp sending error: ' . $e->getMessage());
    }
    
    return $response;
}

/**
 * Send an in-app notification
 * 
 * @param int|array $userId User ID(s) to receive the notification
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $type Notification type (standard, important, alert)
 * @param array $options Additional options
 * @return array Result with success status and message
 */
function sendInAppNotification($userId, $title, $message, $type = 'standard', $options = []) {
    global $conn;
    
    // Default response
    $response = [
        'success' => false,
        'message' => 'Notification sending failed',
        'details' => null
    ];
    
    try {
        // Convert single user ID to array
        if (!is_array($userId)) {
            $userId = [$userId];
        }
        
        // Get current user ID for created_by field
        $currentUserId = isset($_SESSION['user']) ? $_SESSION['user']['user_id'] : 0;
        
        // Optional expiry date
        $expiryDate = $options['expiry_date'] ?? null;
        
        // Check if notifications table exists, create if not
        $checkTableSql = "SHOW TABLES LIKE 'notifications'";
        $tableExists = mysqli_query($conn, $checkTableSql);
        
        if (mysqli_num_rows($tableExists) == 0) {
            // Create notifications table
            $createTableSql = "CREATE TABLE `notifications` (
                `notification_id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `message` text NOT NULL,
                `type` varchar(50) NOT NULL DEFAULT 'standard',
                `created_by` int(11) NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `expiry_date` date DEFAULT NULL,
                PRIMARY KEY (`notification_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            if (!mysqli_query($conn, $createTableSql)) {
                throw new Exception('Failed to create notifications table: ' . mysqli_error($conn));
            }
            
            // Create user_notifications junction table
            $createJunctionTableSql = "CREATE TABLE `user_notifications` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `notification_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `is_read` tinyint(1) NOT NULL DEFAULT '0',
                `read_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `notification_id` (`notification_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            if (!mysqli_query($conn, $createJunctionTableSql)) {
                throw new Exception('Failed to create user_notifications table: ' . mysqli_error($conn));
            }
        }
        
        // Insert notification
        $insertNotificationSql = "INSERT INTO notifications (title, message, type) VALUES (?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $insertNotificationSql);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, 'sss', $title, $message, $type);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to insert notification: ' . mysqli_error($conn));
        }
        
        $notificationId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        // Insert user notifications
        $successCount = 0;
        
        foreach ($userId as $id) {
            $insertUserNotificationSql = "INSERT INTO user_notifications (notification_id, user_id) 
                                        VALUES (?, ?)";
            
            $userStmt = mysqli_prepare($conn, $insertUserNotificationSql);
            
            if ($userStmt) {
                mysqli_stmt_bind_param($userStmt, 'ii', $notificationId, $id);
                
                if (mysqli_stmt_execute($userStmt)) {
                    $successCount++;
                }
                
                mysqli_stmt_close($userStmt);
            }
        }
        
        $response['success'] = true;
        $response['message'] = "Notification sent successfully to $successCount recipients.";
        $response['details'] = [
            'notification_id' => $notificationId,
            'recipient_count' => $successCount
        ];
        
    } catch (Exception $e) {
        $response['message'] = 'Notification sending error: ' . $e->getMessage();
        $response['details'] = $e->getTraceAsString();
        // Log the error
        error_log('Notification sending error: ' . $e->getMessage());
    }
    
    return $response;
}

/**
 * Get email configuration from database or file
 * 
 * @return array Email configuration
 */
function getEmailConfig() {
    global $conn;
    
    $config = [
        'service' => 'phpmailer', // Default service
        'from_name' => 'VVUSRC',
        'from_email' => 'noreply@example.com',
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_secure' => 'tls',
        'smtp_auth' => true,
        'smtp_username' => '',
        'smtp_password' => '',
        'sendgrid_api_key' => '',
        'mailchimp_api_key' => '',
        'infobip_base_url' => '',
        'infobip_api_key' => '',
    ];
    
    // Try to get configuration from settings table
    $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'email_%'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $key = str_replace('email_', '', $row['setting_key']);
            $config[$key] = $row['setting_value'];
        }
    }
    
    return $config;
}

/**
 * Get SMS configuration from database
 * 
 * @return array SMS configuration
 */
function loadSMSConfig() {
    // Include the db_functions file if not already included
    if (!function_exists('getSMSConfig')) {
        require_once __DIR__ . '/includes/db_functions.php';
    }
    return getSMSConfig();
}

/**
 * Get WhatsApp configuration from database
 * 
 * @return array WhatsApp configuration
 */
function loadWhatsAppConfig() {
    // Include the db_functions file if not already included
    if (!function_exists('getWhatsAppConfig')) {
        require_once __DIR__ . '/includes/db_functions.php';
    }
    return getWhatsAppConfig();
}

/**
 * Send an email using PHPMailer
 */
function sendEmailWithPHPMailer($to, $subject, $message, $attachments, $options, $config) {
    // Check if PHPMailer is installed
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Try to require PHPMailer
        $phpmailerPath = __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
        if (file_exists($phpmailerPath)) {
            require $phpmailerPath;
            require __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
            require __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
        } else {
            return [
                'success' => false,
                'message' => 'PHPMailer not installed. Run: composer require phpmailer/phpmailer',
                'details' => null
            ];
        }
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        if (!empty($config['smtp_host'])) {
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->Port = $config['smtp_port'];
            
            if ($config['smtp_secure'] === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($config['smtp_secure'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }
            
            if ($config['smtp_auth']) {
                $mail->SMTPAuth = true;
                $mail->Username = $config['smtp_username'];
                $mail->Password = $config['smtp_password'];
            }
        }
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        
        // Add recipients
        if (is_array($to)) {
            foreach ($to as $recipient) {
                $mail->addAddress($recipient);
            }
        } else {
            $mail->addAddress($to);
        }
        
        // Add CC recipients if provided
        if (isset($options['cc']) && is_array($options['cc'])) {
            foreach ($options['cc'] as $cc) {
                $mail->addCC($cc);
            }
        }
        
        // Add BCC recipients if provided
        if (isset($options['bcc']) && is_array($options['bcc'])) {
            foreach ($options['bcc'] as $bcc) {
                $mail->addBCC($bcc);
            }
        }
        
        // Add Reply-To if provided
        if (isset($options['reply_to'])) {
            $mail->addReplyTo($options['reply_to']);
        }
        
        // Add attachments if provided
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (is_array($attachment) && isset($attachment['path'])) {
                    $filename = $attachment['name'] ?? basename($attachment['path']);
                    $mail->addAttachment($attachment['path'], $filename);
                } else {
                    $mail->addAttachment($attachment);
                }
            }
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        // Send the email
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Email sent successfully',
            'details' => null
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Email sending failed: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ];
    }
}

/**
 * Send SMS using Twilio
 */
function sendSMSWithTwilio($to, $message, $options, $config) {
    // Check if Twilio SDK is installed
    if (!class_exists('Twilio\Rest\Client')) {
        // Try the uploaded SDK package
        $twilioPath = __DIR__ . '/twilio-php-main/src/Twilio/Rest/Client.php';
        if (file_exists($twilioPath)) {
            // Include the Twilio SDK files directly from the uploaded package
            require_once __DIR__ . '/twilio-php-main/src/Twilio/autoload.php';
        } else {
            return [
                'success' => false,
                'message' => 'Twilio SDK not installed. Run: composer require twilio/sdk',
                'details' => null
            ];
        }
    }
    
    try {
        // Check required configuration
        if (empty($config['twilio_account_sid']) || empty($config['twilio_auth_token']) || empty($config['twilio_phone_number'])) {
            return [
                'success' => false,
                'message' => 'Twilio configuration incomplete. Please check settings.',
                'details' => null
            ];
        }
        
        // Initialize Twilio client
        $client = new Twilio\Rest\Client($config['twilio_account_sid'], $config['twilio_auth_token']);
        
        $sentMessages = [];
        
        // Send to multiple recipients if array
        $recipients = is_array($to) ? $to : [$to];
        
        foreach ($recipients as $recipient) {
            // Format phone number if needed
            if (!strpos($recipient, '+')) {
                // If it starts with 0, replace with country code
                if (strpos($recipient, '0') === 0) {
                    $recipient = '+233' . substr($recipient, 1); // Ghana code
                } else {
                    $recipient = '+' . $recipient;
                }
            }
            
            // Send message
            $smsMessage = $client->messages->create(
                $recipient,
                [
                    'from' => $config['twilio_phone_number'],
                    'body' => $message
                ]
            );
            
            $sentMessages[] = [
                'to' => $recipient,
                'sid' => $smsMessage->sid,
                'status' => $smsMessage->status
            ];
        }
        
        return [
            'success' => true,
            'message' => 'SMS sent successfully',
            'details' => $sentMessages
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'SMS sending failed: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ];
    }
}

/**
 * Send WhatsApp message using Twilio
 */
function sendWhatsAppWithTwilio($to, $message, $options, $config) {
    // Check if Twilio SDK is installed
    if (!class_exists('Twilio\Rest\Client')) {
        // Try the uploaded SDK package
        $twilioPath = __DIR__ . '/twilio-php-main/src/Twilio/Rest/Client.php';
        if (file_exists($twilioPath)) {
            // Include the Twilio SDK files directly from the uploaded package
            require_once __DIR__ . '/twilio-php-main/src/Twilio/autoload.php';
        } else {
            return [
                'success' => false,
                'message' => 'Twilio SDK not installed. Run: composer require twilio/sdk',
                'details' => null
            ];
        }
    }
    
    // Check if configuration is complete
    if (empty($config['twilio_account_sid']) || empty($config['twilio_auth_token']) || empty($config['twilio_phone_number'])) {
        return [
            'success' => false,
            'message' => 'Twilio configuration incomplete. Please check settings.',
            'details' => null
        ];
    }
    
    try {
        // Initialize Twilio client
        $client = new \Twilio\Rest\Client(
            $config['twilio_account_sid'],
            $config['twilio_auth_token']
        );
        
        // Process recipient(s)
        $recipients = is_array($to) ? $to : [$to];
        $sentMessages = [];
        
        foreach ($recipients as $recipient) {
            // Add whatsapp: prefix for recipient
            $waRecipient = 'whatsapp:' . $recipient;
            
            // Add whatsapp: prefix for sender
            $waSender = 'whatsapp:' . $config['twilio_phone_number'];
            
            // Send message
            $waMessage = $client->messages->create(
                $waRecipient,
                [
                    'from' => $waSender,
                    'body' => $message
                ]
            );
            
            $sentMessages[] = [
                'to' => $recipient,
                'sid' => $waMessage->sid,
                'status' => $waMessage->status
            ];
        }
        
        return [
            'success' => true,
            'message' => 'WhatsApp message sent successfully',
            'details' => $sentMessages
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'WhatsApp message sending failed: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ];
    }
}

/**
 * Send SMS message using Infobip
 * 
 * @param string|array $to Recipient phone number(s)
 * @param string $message SMS message
 * @param array $options Additional options
 * @param array $config Infobip configuration
 * @return array Result with success status and message
 */
function sendSMSWithInfobip($to, $message, $options, $config) {
    // Check if Infobip configuration is complete
    if (empty($config['infobip_base_url']) || empty($config['infobip_api_key'])) {
        return [
            'success' => false,
            'message' => 'Infobip configuration incomplete. Please check settings.',
            'details' => null
        ];
    }
    
    // For older PHP versions or when the Infobip library isn't compatible,
    // use a simpler HTTP request approach
    $phpVersion = phpversion();
    if (version_compare($phpVersion, '8.3.0', '<')) {
        return sendSMSWithInfobipHttpFallback($to, $message, $options, $config);
    }
    
    // Check if Infobip API client is available
    $autoloadPath = __DIR__ . '/infobip-api-php-client-master/autoload.php';
    if (!file_exists($autoloadPath)) {
        return [
            'success' => false,
            'message' => 'Infobip API client not found. Please make sure it is installed correctly.',
            'details' => null
        ];
    }
    
    try {
        // Include Infobip autoloader
        require_once $autoloadPath;
        
        // Initialize Infobip configuration - using proper syntax for older PHP versions
        $configuration = new \Infobip\Configuration();
        $configuration->setHost($config['infobip_base_url']);
        $configuration->setApiKeyPrefix('Authorization', 'App');
        $configuration->setApiKey('Authorization', $config['infobip_api_key']);
        
        // Initialize SMS API client
        $smsApi = new \Infobip\Api\SmsApi(
            new \GuzzleHttp\Client(),
            $configuration
        );
        
        // Prepare destinations
        $destinations = [];
        $recipients = is_array($to) ? $to : [$to];
        
        foreach ($recipients as $recipient) {
            $destination = new \Infobip\Model\SmsDestination();
            $destination->setTo($recipient);
            $destinations[] = $destination;
        }
        
        // Create message
        $smsMessage = new \Infobip\Model\SmsMessage();
        $smsMessage->setDestinations($destinations);
        
        // Set text content
        $textContent = new \Infobip\Model\SmsTextContent();
        $textContent->setText($message);
        $smsMessage->setText($message);
        
        // Set sender
        $sender = $config['infobip_sender'] ?? 'VVUSRC';
        $smsMessage->setFrom($sender);
        
        // Create request
        $smsRequest = new \Infobip\Model\SmsAdvancedTextualRequest();
        $smsRequest->setMessages([$smsMessage]);
        
        // Send SMS
        $smsResponse = $smsApi->sendSmsMessage($smsRequest);
        
        // Process response
        $sentMessages = [];
        foreach ($smsResponse->getMessages() as $messageResponse) {
            $sentMessages[] = [
                'to' => $messageResponse->getTo(),
                'messageId' => $messageResponse->getMessageId(),
                'status' => $messageResponse->getStatus()->getName()
            ];
        }
        
        return [
            'success' => true,
            'message' => 'SMS sent successfully',
            'details' => [
                'bulkId' => $smsResponse->getBulkId(),
                'messages' => $sentMessages
            ]
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'SMS sending failed: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ];
    }
}

/**
 * Fallback function to send SMS using Infobip via direct HTTP request
 * Used when the Infobip PHP library is not compatible with the current PHP version
 */
function sendSMSWithInfobipHttpFallback($to, $message, $options, $config) {
    try {
        // Format recipients
        $recipients = is_array($to) ? $to : [$to];
        $destinationsArray = [];
        
        foreach ($recipients as $recipient) {
            $destinationsArray[] = ['to' => $recipient];
        }
        
        // Create the request payload
        $payload = [
            'messages' => [
                [
                    'from' => $config['infobip_sender'] ?? 'VVUSRC',
                    'destinations' => $destinationsArray,
                    'text' => $message
                ]
            ]
        ];
        
        // Convert to JSON
        $jsonPayload = json_encode($payload);
        
        // Set up cURL
        $curl = curl_init();
        
        // Configure cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $config['infobip_base_url'] . '/sms/2/text/advanced',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: App ' . $config['infobip_api_key'],
                'Accept: application/json'
            ],
        ]);
        
        // Execute the request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        // Check for errors
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            return [
                'success' => false,
                'message' => 'HTTP request failed: ' . $error,
                'details' => null
            ];
        }
        
        curl_close($curl);
        
        // Parse the response
        $responseData = json_decode($response, true);
        
        // Check if the request was successful
        if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['messages'])) {
            $sentMessages = [];
            
            foreach ($responseData['messages'] as $messageResponse) {
                $sentMessages[] = [
                    'to' => $messageResponse['to'],
                    'messageId' => $messageResponse['messageId'],
                    'status' => $messageResponse['status']['name']
                ];
            }
            
            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'details' => [
                    'bulkId' => $responseData['bulkId'],
                    'messages' => $sentMessages
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'SMS sending failed. HTTP Code: ' . $httpCode,
                'details' => $responseData
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'SMS sending failed: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ];
    }
}

/**
 * Send SMS using Zenoph (SMS Online GH)
 *
 * @param string|array $to Recipient phone number(s)
 * @param string $message SMS message
 * @param array $options Additional options
 * @param array $config Zenoph configuration
 * @return array Result with success status and message
 */
function sendSMSWithZenoph($to, $message, $options, $config) {
    // Check if Zenoph configuration is complete
    if (empty($config['zenoph_api_key'])) {
        return [
            'success' => false,
            'message' => 'Zenoph configuration incomplete. API Key is required.',
            'details' => null
        ];
    }

    try {
        // Include Zenoph autoloader
        $zenophAutoload = __DIR__ . '/zenoph-notify/lib/Zenoph/Notify/AutoLoader.php';
        if (!file_exists($zenophAutoload)) {
            return [
                'success' => false,
                'message' => 'Zenoph SDK not found. Please make sure it is installed correctly.',
                'details' => null
            ];
        }

        require_once $zenophAutoload;

        // Create request subject
        $request = new \Zenoph\Notify\Request\SMSRequest();

        // Set host domain
        $host = $config['zenoph_host'] ?? 'api.smsonlinegh.com';
        $request->setHost($host);

        // Set authentication
        $request->setAuthModel(\Zenoph\Notify\Enums\AuthModel::API_KEY);
        $request->setAuthApiKey($config['zenoph_api_key']);

        // Set message properties
        $request->setMessage($message);
        $request->setSMSType(\Zenoph\Notify\Enums\SMSType::GSM_DEFAULT);

        // Set sender ID
        $sender = $config['zenoph_sender'] ?? 'VVUSRC';
        $request->setSender($sender);

        // Add destinations
        $recipients = is_array($to) ? $to : [$to];
        $sentMessages = [];

        foreach ($recipients as $recipient) {
            // Format phone number if needed
            if (!strpos($recipient, '+')) {
                // If it starts with 0, replace with country code
                if (strpos($recipient, '0') === 0) {
                    $recipient = '233' . substr($recipient, 1); // Ghana code without +
                }
            } else {
                // Remove + if present
                $recipient = ltrim($recipient, '+');
            }

            $request->addDestination($recipient);
        }

        // Submit message for response
        $msgResp = $request->submit();

        // Check if we got a response
        if ($msgResp) {
            $msgReport = $msgResp->getReport();

            // Check if we got a report
            if ($msgReport) {
                return [
                    'success' => true,
                    'message' => 'SMS sent successfully via Zenoph',
                    'details' => [
                        'batchId' => $msgReport->getBatchId(),
                        'category' => $msgReport->getCategory(),
                        'recipients' => count($recipients),
                        'destinationsCount' => $msgReport->getDestiniationsCount()
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'SMS sending failed via Zenoph: No report received',
                    'details' => null
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'SMS sending failed via Zenoph: No response received',
                'details' => null
            ];
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Zenoph SMS sending failed: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ];
    }
}

/**
 * Send WhatsApp message using Infobip
 * 
 * @param string|array $to Recipient phone number(s)
 * @param string $message WhatsApp message
 * @param array $options Additional options
 * @param array $config Infobip configuration
 * @return array Result with success status and message
 */
function sendWhatsAppWithInfobip($to, $message, $options, $config) {
    // Check if Infobip configuration is complete
    if (empty($config['infobip_base_url']) || empty($config['infobip_api_key'])) {
        return [
            'success' => false,
            'message' => 'Infobip configuration incomplete. Please check settings.',
            'details' => null
        ];
    }
    
    // For older PHP versions or when the Infobip library isn't compatible,
    // use the SMS fallback method - for most cases this is good enough
    $phpVersion = phpversion();
    if (version_compare($phpVersion, '8.3.0', '<')) {
        // Use SMS fallback - API call is nearly identical for basic messaging
        return sendSMSWithInfobipHttpFallback($to, $message, $options, $config);
    }
    
    // Check if Infobip API client is available
    $autoloadPath = __DIR__ . '/infobip-api-php-client-master/autoload.php';
    if (!file_exists($autoloadPath)) {
        return [
            'success' => false,
            'message' => 'Infobip API client not found. Please make sure it is installed correctly.',
            'details' => null
        ];
    }
    
    try {
        // Include Infobip autoloader
        require_once $autoloadPath;
        
        // Initialize Infobip configuration - using proper syntax for older PHP versions
        $configuration = new \Infobip\Configuration();
        $configuration->setHost($config['infobip_base_url']);
        $configuration->setApiKeyPrefix('Authorization', 'App');
        $configuration->setApiKey('Authorization', $config['infobip_api_key']);
        
        // Initialize WhatsApp API client (using the SmsApi for now as compatibility layer)
        $whatsappApi = new \Infobip\Api\SmsApi(
            new \GuzzleHttp\Client(),
            $configuration
        );
        
        // Prepare recipients
        $recipients = is_array($to) ? $to : [$to];
        $messages = [];
        
        foreach ($recipients as $recipient) {
            // Create message for each recipient
            $destination = new \Infobip\Model\SmsDestination();
            $destination->setTo($recipient);
            
            $smsMessage = new \Infobip\Model\SmsMessage();
            $smsMessage->setDestinations([$destination]);
            $smsMessage->setText($message);
            $smsMessage->setFrom($config['infobip_sender'] ?? 'VVUSRC');
            
            $messages[] = $smsMessage;
        }
        
        // Create request
        $whatsappRequest = new \Infobip\Model\SmsAdvancedTextualRequest();
        $whatsappRequest->setMessages($messages);
        
        // Send WhatsApp message (using SMS API as a fallback)
        $response = $whatsappApi->sendSmsMessage($whatsappRequest);
        
        // Process response
        $sentMessages = [];
        
        foreach ($response->getMessages() as $messageResponse) {
            $sentMessages[] = [
                'to' => $messageResponse->getTo(),
                'messageId' => $messageResponse->getMessageId(),
                'status' => $messageResponse->getStatus()->getName()
            ];
        }
        
        return [
            'success' => true,
            'message' => 'WhatsApp message sent successfully',
            'details' => [
                'bulkId' => $response->getBulkId(),
                'messages' => $sentMessages
            ]
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'WhatsApp message sending failed: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ];
    }
}

/**
 * Send email using Infobip
 * 
 * @param string|array $to Recipient email address(es)
 * @param string $subject Email subject
 * @param string $message Email body (can be HTML)
 * @param array $attachments Optional array of attachments
 * @param array $options Additional options (cc, bcc, reply-to, etc.)
 * @param array $config Infobip configuration
 * @return array Result with success status and message
 */
function sendEmailWithInfobip($to, $subject, $message, $attachments = [], $options = [], $config = []) {
    // Check if Infobip configuration is complete
    if (empty($config['infobip_base_url']) || empty($config['infobip_api_key'])) {
        return [
            'success' => false,
            'message' => 'Infobip configuration incomplete. Please check settings.',
            'details' => null
        ];
    }
    
    try {
        // Format recipients
        $recipients = is_array($to) ? $to : [$to];
        
        // Process CC recipients if provided
        $ccRecipients = [];
        if (isset($options['cc']) && is_array($options['cc'])) {
            $ccRecipients = $options['cc'];
        }
        
        // Process BCC recipients if provided
        $bccRecipients = [];
        if (isset($options['bcc']) && is_array($options['bcc'])) {
            $bccRecipients = $options['bcc'];
        }
        
        // Process attachments if provided
        $emailAttachments = [];
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (is_array($attachment) && isset($attachment['path'])) {
                    $attachmentPath = $attachment['path'];
                    $attachmentName = $attachment['name'] ?? basename($attachmentPath);
                    
                    // Read file content and encode as base64
                    $fileContent = file_get_contents($attachmentPath);
                    if ($fileContent !== false) {
                        $base64Content = base64_encode($fileContent);
                        $mimeType = mime_content_type($attachmentPath) ?: 'application/octet-stream';
                        
                        $emailAttachments[] = [
                            'name' => $attachmentName,
                            'content' => $base64Content,
                            'contentType' => $mimeType
                        ];
                    }
                }
            }
        }
        
        // Create the request payload
        $payload = [
            'from' => [
                'email' => $config['from_email'] ?? 'noreply@example.com',
                'name' => $config['from_name'] ?? 'VVUSRC'
            ],
            'subject' => $subject,
            'to' => array_map(function($email) {
                return ['email' => $email];
            }, $recipients),
            'html' => $message  // Changed from 'htmlContent' to 'html'
        ];
        
        // Add CC if present
        if (!empty($ccRecipients)) {
            $payload['cc'] = array_map(function($email) {
                return ['email' => $email];
            }, $ccRecipients);
        }
        
        // Add BCC if present
        if (!empty($bccRecipients)) {
            $payload['bcc'] = array_map(function($email) {
                return ['email' => $email];
            }, $bccRecipients);
        }
        
        // Add attachments if present
        if (!empty($emailAttachments)) {
            $payload['attachments'] = $emailAttachments;
        }
        
        // Convert to JSON
        $jsonPayload = json_encode($payload);
        
        // Ensure base URL doesn't end with a slash
        $baseUrl = rtrim($config['infobip_base_url'], '/');
        
        // Set up cURL
        $curl = curl_init();
        
        // Configure cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $baseUrl . '/email/2/send',  // Changed from '/email/3/send' to '/email/2/send'
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: App ' . $config['infobip_api_key'],
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,  // Added to bypass SSL verification if needed
            CURLOPT_SSL_VERIFYHOST => 0       // Added to bypass SSL verification if needed
        ]);
        
        // Execute the request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        // Check for errors
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            return [
                'success' => false,
                'message' => 'HTTP request failed: ' . $error,
                'details' => null
            ];
        }
        
        curl_close($curl);
        
        // Parse the response
        $responseData = json_decode($response, true);
        
        // Check if the request was successful
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'details' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Email sending failed. HTTP Code: ' . $httpCode,
                'details' => $responseData
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Email sending failed: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ];
    }
} 