<?php
// Import PHPMailer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * EmailService class - Handles all email operations
 * OOP Principles: Encapsulation, Single Responsibility Principle
 */
class EmailService {
    private $mail; // OOP: Private property (Encapsulation)
    
    // OOP: Constructor method
    public function __construct() {
        $this->mail = new PHPMailer(true); // OOP: Object instantiation
        $this->configureMailer(); // OOP: Method call
    }
    
    // OOP: Private method (Encapsulation) - hides configuration details
    private function configureMailer() {
        $this->mail->isSMTP();
        $this->mail->Host = SMTP_HOST;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = SMTP_USER;
        $this->mail->Password = SMTP_PASS;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // OOP: Class constant
        $this->mail->Port = SMTP_PORT;
        $this->mail->setFrom(SMTP_USER, 'Auth System');
    }
    
    // OOP: Public method - main interface for sending verification emails
    public function sendVerificationEmail($email, $token) {
        try {
            $this->mail->clearAddresses(); // Clear any previous addresses
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Verify Your Email';
            $this->mail->Body = $this->buildVerificationEmailBody($token); // OOP: Method call
            
            $this->mail->send(); // OOP: Method call
            return ['success' => true, 'message' => 'Verification email sent successfully'];
            
        } catch (Exception $e) { // OOP: Exception handling
            return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $this->mail->ErrorInfo];
        }
    }
    
    // OOP: Private method (Encapsulation) - separates email content creation
    private function buildVerificationEmailBody($token) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #007cba;'>Email Verification Required</h2>
            <p>Thank you for registering! Please verify your email address by clicking the button below:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . SITE_URL . "/verify.php?token=" . $token . "' 
                   style='background-color: #007cba; color: white; padding: 12px 30px; 
                          text-decoration: none; border-radius: 5px; display: inline-block;'>
                    Verify Email Address
                </a>
            </div>
            <p style='color: #666; font-size: 14px;'>
                If the button doesn't work, copy and paste this link into your browser:<br>
                <a href='" . SITE_URL . "/verify.php?token=" . $token . "'>" . SITE_URL . "/verify.php?token=" . $token . "</a>
            </p>
            <p style='color: #999; font-size: 12px;'>
                This verification link will expire in 24 hours for security reasons.
            </p>
        </div>";
    }
}
?>
