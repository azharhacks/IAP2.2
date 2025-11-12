<?php
// Error reporting - show all errors except deprecation warnings
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// Site timezone
$conf['site_timezone'] = 'Africa/Nairobi';

// Site information
$conf['site_name'] = 'SMARTDUKA';
$conf['site_url'] = 'http://localhost/IAP2.2Dev';
$conf['admin_email'] = 'smartduka@support.com';

// Site language
$conf['site_lang'] = 'en';

// Database configuration
$conf['db_type'] = 'pdo';
$conf['db_host'] = 'localhost';
$conf['db_user'] = 'root';
$conf['db_pass'] = 'devyan2005';
$conf['db_name'] = 'auth_db';

// Email configuration
$conf['mail_type'] = 'smtp'; 
$conf['smtp_host'] = 'smtp.gmail.com';
$conf['smtp_user'] = 'smartduka@support.com';
$conf['smtp_pass'] = 'hvmc tezo jexn lbkg';
$conf['smtp_port'] = 465;
$conf['smtp_secure'] = 'ssl';
$conf['smtp_recepient']='SMARTDUKA Support';
$conf['recepient_email']='smartduka@support.com';

// Global PDO Database Connection
try {
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

// M-Pesa Configuration
$conf['mpesa'] = [
    'consumer_key' => 'cXfEmCCWj9N5fd2Z1Oz541C9n90RjtECBS1Ff6pKVWSSh88H',
    'consumer_secret' => 'UBbIDpR2sqPBDshDPaiAdyEIgAGX3FvLEg89ZXlRffjX2K8plnCmnlUI5lQwfiPg',
    'environment' => 'sandbox', // Change to 'production' for live
    'short_code' => '174379', // Safaricom test shortcode
    'passkey' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919', // Safaricom test passkey
    'callback_url' => 'https://webhook.site/95e84f8b-c43d-4a27-b7de-0a1e5f95e6c8',
    'account_reference' => 'SMARTDUKA',
    'transaction_desc' => 'SMARTDUKA Payment',
    'timeout_url' => 'https://webhook.site/95e84f8b-c43d-4a27-b7de-0a1e5f95e6c8'
];

