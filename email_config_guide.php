<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "=== EMAIL CONFIGURATION DIAGNOSIS ===\n\n";

echo "1. Current Configuration:\n";
echo "   Host: " . $conf['smtp_host'] . "\n";
echo "   Port: " . $conf['smtp_port'] . "\n";
echo "   User: " . $conf['smtp_user'] . "\n";
echo "   Pass: " . str_repeat('*', strlen($conf['smtp_pass'])) . " (length: " . strlen($conf['smtp_pass']) . ")\n";
echo "   Security: " . $conf['smtp_secure'] . "\n\n";

echo "2. Common Gmail SMTP Issues and Solutions:\n";
echo "   Issue: 'Username and Password not accepted'\n";
echo "   Solution: Use App Password instead of regular password\n";
echo "   Steps:\n";
echo "      1. Go to Google Account Settings\n";
echo "      2. Enable 2-Factor Authentication\n";
echo "      3. Generate App Password for 'Mail'\n";
echo "      4. Use the 16-character App Password in config\n\n";

echo "3. Recommended Gmail SMTP Settings:\n";
echo "   For SSL (port 465):\n";
echo "     - Host: smtp.gmail.com\n";
echo "     - Port: 465\n";
echo "     - Security: ssl\n\n";
echo "   For TLS (port 587) - RECOMMENDED:\n";
echo "     - Host: smtp.gmail.com\n";
echo "     - Port: 587\n";
echo "     - Security: tls\n\n";

echo "4. Alternative SMTP Providers (Free Tiers):\n";
echo "   - Mailtrap (testing): mailtrap.io\n";
echo "   - SendGrid: sendgrid.com (100 emails/day free)\n";
echo "   - Mailgun: mailgun.com (100 emails/day free)\n\n";

echo "5. Test Email Sending Without Authentication (localhost only):\n";
echo "   Would you like to test with a simple mail() function? (y/n)\n";

// For development/testing, create a fallback email method
echo "\n=== CREATING FALLBACK EMAIL METHOD ===\n";

// Create a simple fallback email sender for testing
$fallbackEmailCode = '<?php
class FallbackEmailSender {
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
?>';

file_put_contents('FallbackEmail.php', $fallbackEmailCode);
echo "✅ Created FallbackEmail.php for testing without SMTP\n";

echo "\nTo use the fallback method, modify your forms.php to use:\n";
echo "require_once 'FallbackEmail.php';\n";
echo "\$fallback = new FallbackEmailSender();\n";
echo "\$result = \$fallback->sendSimpleEmail(\$email, 'Verification', 'Check link: ' . \$verificationLink);\n";

?>
