<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'telecom_appointment_system');

// Base URL
define('BASE_URL', 'http://localhost/telecom-appointment-system');

// Session configuration
session_start();

// Timezone
date_default_timezone_set('UTC');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload classes
spl_autoload_register(function ($className) {
    $file = __DIR__ . '/classes/' . str_replace('\\', '/', $className) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Include helper functions
require_once 'helpers.php';

//Email Sending
define('EMAIL_HOST', 'smtp.gmail.com');  
define('EMAIL_PORT', 587); 
define('EMAIL_USERNAME', '--Your Email Address--');
define('EMAIL_PASSWORD', '--Email Passkey--');
define('EMAIL_FROM', '--Your Email Address--');
define('EMAIL_FROM_NAME', 'Telecom Appointment System');
?>