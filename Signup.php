<?php
require_once 'config.php';
require_once 'db_connect.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use RobThree\Auth\TwoFactorAuth;

$error = '';
$success = '';
$qrCode = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = 'Email already registered.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));
            $tfa = new TwoFactorAuth();
            $totp_secret = $tfa->createSecret();

            $stmt = $pdo->prepare("INSERT INTO users (email, password, verification_token, totp_secret) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$email, $hashed_password, $token, $totp_secret])) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = SMTP_HOST;
                    $mail->SMTPAuth = true;
                    $mail->Username = SMTP_USER;
                    $mail->Password = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = SMTP_PORT;

                    $mail->setFrom(SMTP_USER, 'Auth System');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Verify Your Email';
                    $mail->Body = "Click <a href='" . SITE_URL . "/verify.php?token=" . $token . "'>here</a> to verify.";

                    $mail->send();
                    $success = 'Registration successful! Check your email to verify.';
                    $qrCode = $tfa->getQRCodeImageAsDataUri($email, $totp_secret);
                } catch (Exception $e) {
                    $error = 'Email could not be sent. Error: ' . $mail->ErrorInfo;
                    $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);
                }
            } else {
                $error = 'Registration failed.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Signup</title></head>
<body>
    <h2>Signup</h2>
    <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>
    <?php if ($success): ?>
        <p style="color:green;"><?php echo $success; ?></p>
        <p>After verification, scan this QR code with your authenticator app (e.g., Google Authenticator) to enable 2FA:</p>
        <img src="<?php echo $qrCode; ?>" alt="QR Code">
    <?php endif; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Signup</button>
    </form>
    <a href="signin.php">Sign In</a>
</body>
</html>
