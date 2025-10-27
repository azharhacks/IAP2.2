<?php
/**
 * Fallback Email Sender Class
 * Provides a simple email sending mechanism when SMTP is not available
 * Uses PHP's built-in mail() function as a backup email solution
 */

class FallbackEmailSender {
    /**
     * Send a simple HTML email using PHP's built-in mail() function
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject line
     * @param string $message HTML email content
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendSimpleEmail($to, $subject, $message) {
        // Set up email headers for HTML content
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@localhost" . "\r\n";
        
        // Attempt to send email using PHP's mail() function
        if (mail($to, $subject, $message, $headers)) {
            return true;
        } else {
            return false;
        }
    }
}
?>