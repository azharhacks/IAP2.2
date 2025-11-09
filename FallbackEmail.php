<?php
/**
 * Fallback Email Sending Class - For development and testing
 */
class FallbackEmail {
    
    public function sendVerificationMail($conf, $username, $email, $verificationToken) {
        // Create verification link
        $verificationLink = $conf['site_url'] . '/verify.php?token=' . $verificationToken;
        
        $subject = 'Verify Your Email - ' . $conf['site_name'];
        $message = "
Hello $username,

Thank you for signing up for " . $conf['site_name'] . "!

Please verify your email address by clicking the link below:
$verificationLink

This link will expire in 24 hours.

Best regards,
The " . $conf['site_name'] . " Team
        ";
        
        // For development - log the email content
        $logMessage = "
=== EMAIL VERIFICATION LOG ===
Date: " . date('Y-m-d H:i:s') . "
To: $email
Subject: $subject
Message:
$message
Verification Link: $verificationLink
===============================
        ";
        
        // Write to a log file for development
        $logFile = __DIR__ . '/email_log.txt';
        file_put_contents($logFile, $logMessage . "\n", FILE_APPEND | LOCK_EX);
        
        // Try PHP's mail() function as fallback
        $headers = "From: " . $conf['admin_email'] . "\r\n";
        $headers .= "Reply-To: " . $conf['admin_email'] . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        $mailResult = @mail($email, $subject, $message, $headers);
        
        // Return true for development (so signup process continues)
        // In production, you'd return $mailResult
        return true;
    }
    
    public function sendSimpleEmail($to, $subject, $message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@localhost" . "\r\n";
        
        if (mail($to, $subject, $message, $headers)) {
            return true;
        } else {
            return false;
        }
    }
}
?>