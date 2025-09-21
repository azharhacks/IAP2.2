<?php 
// Database Configuration
define('DB_HOST', 'localhost'); 
define('DB_NAME', 'auth_db'); 
define('DB_USER', 'auth_user');   // Create this MySQL user if it doesn’t exist
define('DB_PASS', 'strong_password'); // Replace with your password

// SMTP (for PHPMailer – Person 2 will need this)
define('SMTP_HOST', 'smtp.gmail.com'); 
define('SMTP_PORT', 587); 
define('SMTP_USER', 'memusisanaet12@gmail.com');      // Replace with your Gmail
define('SMTP_PASS', 'your-app-password');         // Replace with Gmail App Password
define('SITE_URL', 'http://localhost/auth-system'); 

// Start Session
session_start(); 
?>
