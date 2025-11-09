<?php
require 'config.php';
require 'ClassAutoload.php';

// Handle form processing FIRST, before any HTML output
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connect to database
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check user credentials and verification status
    $stmt = $pdo->prepare("SELECT id, username, password, email_verified, totp_secret FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['email_verified'] == 1) {
            // User is verified, set session for 2FA verification
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['pending_2fa_user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $email;
            $_SESSION['totp_secret'] = $user['totp_secret'] ?? ''; // Handle NULL case
            
            // Store redirect URL if provided
            if (isset($_GET['redirect'])) {
                $_SESSION['redirect_after_2fa'] = $_GET['redirect'];
            }
            
            // Redirect to 2FA verification
            header("Location: 2fa_verify.php");
            exit();
        } else {
            $error_message = "Please verify your email address before signing in. Check your email for the verification link.";
        }
    } else {
        $error_message = "Invalid email or password.";
    }
}

// Now we can safely output HTML
$layout = new Layout();
$customCSS = '
    .signin-container {
        max-width: 500px;
        margin: 50px auto;
        padding: 2rem;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .signin-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .signin-header h2 {
        color: #333;
        margin-bottom: 0.5rem;
    }
    .signin-header p {
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

$layout->header('Sign In', $customCSS);
$layout->navbar('signin');
$layout->contentStart();
?>

<div class="signin-container">
    <div class="signin-header">
        <h2><i class="fas fa-sign-in-alt me-2"></i>Sign In</h2>
        <p>Welcome back! Please sign in to your account</p>
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="mb-3">
            <label for="email" class="form-label">
                <i class="fas fa-envelope me-1"></i>Email Address
            </label>
            <input type="email" class="form-control" id="email" name="email" required 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">
                <i class="fas fa-lock me-1"></i>Password
            </label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </button>
        </div>
        <div class="text-center mt-3">
            <a href="Signup.php" class="text-decoration-none">
                <i class="fas fa-user-plus me-1"></i>Don't have an account? Sign Up
            </a>
        </div>
    </form>
</div>

<?php
$layout->contentEnd();
$layout->footer();
?>