<?php
// Error reporting - show all errors except deprecation warnings
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Site timezone
$conf['site_timezone'] = 'Africa/Nairobi';

// Site information
$conf['site_name'] = 'SMARTDUKA';
$conf['site_url'] = 'http://localhost/your-project';
$conf['admin_email'] = 'your-email@example.com';

// Site language
$conf['site_lang'] = 'en';

// Database configuration
$conf['db_type'] = 'pdo';
$conf['db_host'] = 'localhost';
$conf['db_user'] = 'your_db_user';
$conf['db_pass'] = 'your_db_password';
$conf['db_name'] = 'your_db_name';

// Email configuration
$conf['mail_type'] = 'smtp'; 
$conf['smtp_host'] = 'smtp.gmail.com';
$conf['smtp_user'] = 'your-email@gmail.com';
$conf['smtp_pass'] = 'your-app-password';
$conf['smtp_port'] = 465;
$conf['smtp_secure'] = 'ssl';
$conf['smtp_recepient'] = 'Your Name';
$conf['recepient_email'] = 'recipient@example.com';

// M-Pesa Configuration
$conf['mpesa'] = [
    'consumer_key' => 'your_mpesa_consumer_key',
    'consumer_secret' => 'your_mpesa_consumer_secret',
    'environment' => 'sandbox', // Change to 'production' for live
    'short_code' => 'your_short_code',
    'passkey' => 'your_passkey',
    'callback_url' => $conf['site_url'] . '/mpesa_callback.php',
    'account_reference' => 'SMARTDUKA',
    'transaction_desc' => 'SMARTDUKA Payment',
    'timeout_url' => $conf['site_url'] . '/mpesa_timeout.php'
];
