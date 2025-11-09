<?php
require 'config.php';
require 'ClassAutoload.php';
require_once 'Mail/SendMail.php';
require_once 'FallbackEmail.php';

// Handle form processing FIRST, before any HTML output
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connect to database
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } else {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->fetch()) {
            $error_message = "User with this email or username already exists.";
        } else {
            // Create new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $verificationToken = bin2hex(random_bytes(32));
            
            // Generate TOTP secret for 2FA (Base32 encoded, 160 bits = 32 characters)
            $totpSecret = '';
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
            for ($i = 0; $i < 32; $i++) {
                $totpSecret .= $chars[random_int(0, 31)];
            }
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_token, totp_secret, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            
            if ($stmt->execute([$username, $email, $hashedPassword, $verificationToken, $totpSecret])) {
                // Try to send verification email with primary method
                $sendMail = new SendMail();
                $emailSent = $sendMail->sendVerificationMail($conf, $username, $email, $verificationToken);
                
                // If primary method fails, use fallback
                if (!$emailSent) {
                    $fallbackEmail = new FallbackEmail();
                    $emailSent = $fallbackEmail->sendVerificationMail($conf, $username, $email, $verificationToken);
                }
                
                if ($emailSent) {
                    $success_message = "Registration successful! Please check your email ($email) for verification instructions. For development, check the email_log.txt file.";
                } else {
                    $success_message = "Registration successful! However, there was an issue sending the verification email. Please contact support or try again later.";
                    // Log the error for debugging
                    error_log("Failed to send verification email to: $email");
                }
                
                // Clear form data
                $username = $email = '';
            } else {
                $error_message = "Registration failed. Please try again.";
            }
        }
    }
}

// Now we can safely output HTML
$layout = new Layout();
$customCSS = '
    .signup-container {
        max-width: 500px;
        margin: 50px auto;
        padding: 2rem;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .signup-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .signup-header h2 {
        color: #333;
        margin-bottom: 0.5rem;
    }
    .signup-header p {
        color: #666;
        margin: 0;
    }
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 12px 30px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
';

$layout->header('Sign Up', $customCSS);
$layout->navbar('signup');
$layout->contentStart();
?>

<div class="signup-container">
    <div class="signup-header">
        <h2><i class="fas fa-user-plus me-2"></i>Create Account</h2>
        <p>Join us today and start shopping!</p>
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="mb-3">
            <label for="username" class="form-label">
                <i class="fas fa-user me-1"></i>Username
            </label>
            <input type="text" class="form-control" id="username" name="username" required 
                   value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">
                <i class="fas fa-envelope me-1"></i>Email Address
            </label>
            <input type="email" class="form-control" id="email" name="email" required 
                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">
                <i class="fas fa-lock me-1"></i>Password
            </label>
            <input type="password" class="form-control" id="password" name="password" required 
                   minlength="8" placeholder="At least 8 characters">
            <div class="form-text">Password must be at least 8 characters long.</div>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
        </div>
        <div class="text-center mt-3">
            <a href="Signin.php" class="text-decoration-none">
                <i class="fas fa-sign-in-alt me-1"></i>Already have an account? Sign In
            </a>
        </div>
    </form>
</div>

<?php
$layout->contentEnd();
$layout->footer();
?>
