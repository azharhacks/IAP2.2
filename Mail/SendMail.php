<?php
require_once __DIR__ . '/../Plugins/PHPMailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SendMail {
    public function sendVerificationMail($conf, $username, $email, $verificationToken) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $conf['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $conf['smtp_user'];
            $mail->Password = $conf['smtp_pass'];
            
            // Set encryption method based on config
            if ($conf['smtp_secure'] == 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL/SMTPS for port 465
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS for port 587
            }
            
            $mail->Port = $conf['smtp_port'];
            
            // Disable debug output in production
            $mail->SMTPDebug = 0; // Set to 2 for debugging
            
            // Timeout settings
            $mail->Timeout = 30;
            $mail->SMTPKeepAlive = true;            // Recipients
            $mail->setFrom($conf['smtp_user'], $conf['site_name']);
            $mail->addAddress($email, $username);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email - ' . $conf['site_name'];
            
            $verificationLink = $conf['site_url'] . '/verify.php?token=' . $verificationToken;
            
            $mail->Body = "
                <h2>Welcome to " . $conf['site_name'] . "!</h2>
                <p>Hello " . htmlspecialchars($username) . ",</p>
                <p>Thank you for signing up. Please verify your email address by clicking the link below:</p>
                <p><a href='" . $verificationLink . "' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email Address</a></p>
                <p>If the button doesn't work, copy and paste this link into your browser:</p>
                <p>" . $verificationLink . "</p>
                <p>This link will expire in 24 hours.</p>
                <p>Best regards,<br>The " . $conf['site_name'] . " Team</p>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            return false;
        }
    }
}
?>