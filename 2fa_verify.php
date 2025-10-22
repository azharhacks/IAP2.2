<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

// Check if user came from signin process
if (!isset($_SESSION['pending_2fa_user_id'])) {
    header('Location: Signin.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = trim($_POST['code']);
    
    if (empty($code)) {
        $error = 'Please enter your 6-digit authentication code.';
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Please enter a valid 6-digit code.';
    } else {
        try {
            // Connect to database - Linux/Fedora standard connection
            $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Get user's TOTP secret
            $stmt = $pdo->prepare("SELECT id, totp_secret, email FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['pending_2fa_user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && $user['totp_secret']) {
                // Verify TOTP code
                require_once 'vendor/autoload.php';
                $tfa = new RobThree\Auth\TwoFactorAuth();
                
                if ($tfa->verifyCode($user['totp_secret'], $code)) {
                    // 2FA successful - complete login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['2fa_verified'] = true;
                    unset($_SESSION['pending_2fa_user_id']);
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid authentication code. Please try again.';
                }
            } else {
                $error = 'User not found or 2FA not set up. Please contact support.';
            }
            
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again later.';
            error_log("Database error in 2fa_verify.php: " . $e->getMessage());
        } catch (Exception $e) {
            $error = 'Authentication error. Please try again.';
            error_log("TOTP error in 2fa_verify.php: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - <?php echo $conf['site_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .auth-container { 
            max-width: 400px; 
            margin: 80px auto; 
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .security-icon {
            background: linear-gradient(135deg, #28a745, #20c997);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }
        .code-input {
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.5rem;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
        }
        .code-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .btn-verify {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            padding: 12px 40px;
            border-radius: 25px;
            font-weight: 500;
        }
        .btn-verify:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }
        .help-text {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="text-center mb-4">
                <div class="security-icon">
                    🔐
                </div>
                <h3 class="mb-2">Two-Factor Authentication</h3>
                <p class="text-muted mb-0">Enter the 6-digit code from your authenticator app</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="code" class="form-label">Authentication Code</label>
                    <input type="text" 
                           id="code" 
                           name="code" 
                           class="form-control code-input" 
                           placeholder="000000" 
                           maxlength="6" 
                           required
                           autocomplete="off"
                           autofocus>
                    <div class="help-text mt-2">
                        Open your authenticator app (Google Authenticator, Authy, etc.) and enter the 6-digit code
                    </div>
                </div>
                
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-verify">
                        Verify & Continue
                    </button>
                </div>
            </form>

            <!-- 2FA Setup Instructions -->
            <div class="mt-4 pt-3 border-top">
                <h6 class="text-center mb-3">First time setup? Add this account to your authenticator app:</h6>
                
                <?php
                // Get TOTP secret for QR code generation
                if (isset($_SESSION['totp_secret'])) {
                    require_once 'vendor/autoload.php';
                    $tfa = new RobThree\Auth\TwoFactorAuth($conf['site_name']);
                    $totpSecret = $_SESSION['totp_secret'];
                    $userEmail = $_SESSION['email'];
                    
                    // Generate QR code URL
                    $qrCodeUrl = $tfa->getQRCodeImageAsDataUri($userEmail, $totpSecret);
                    
                    echo '<div class="text-center mb-3">';
                    echo '<div class="mb-3">';
                    echo '<strong>Option 1: Scan QR Code</strong><br>';
                    echo '<img src="' . $qrCodeUrl . '" alt="QR Code" class="img-fluid" style="max-width: 200px;">';
                    echo '</div>';
                    
                    echo '<div class="mb-3">';
                    echo '<strong>Option 2: Manual Setup Key</strong><br>';
                    echo '<code style="font-size: 0.9rem; background: #f8f9fa; padding: 8px; border-radius: 4px; display: inline-block; margin-top: 5px;">' . $totpSecret . '</code>';
                    echo '<br><small class="text-muted">Enter this key manually in your authenticator app</small>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
            
            <div class="text-center">
                <a href="Signin.php" class="text-decoration-none">
                    ← Back to Sign In
                </a>
            </div>
            
            <div class="mt-4 pt-3 border-top">
                <div class="text-center">
                    <small class="text-muted">
                        Having trouble? Make sure your device time is synchronized
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-format the input to only allow numbers
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Auto-submit when 6 digits are entered
        document.getElementById('code').addEventListener('input', function(e) {
            if (this.value.length === 6) {
                // Small delay for better UX
                setTimeout(() => {
                    this.form.submit();
                }, 500);
            }
        });
        
        // Focus on input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('code').focus();
        });
    </script>
</body>
</html>
