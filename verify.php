<?php
/**
 * Email Verification Page
 * Handles email verification process for new user registrations
 * Validates verification tokens and activates user accounts
 */

require_once 'config.php';
require_once 'ClassAutoload.php';

// Initialize message variables for user feedback
$message = '';
$messageType = '';

// Check if verification token is provided in URL
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    // Validate token format - must be 64 character hexadecimal string
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $message = 'Invalid verification token format.';
        $messageType = 'error';
    } else {
        try {
            // Establish secure database connection with proper error handling
            $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Query to find user with unverified email and matching token
            $stmt = $pdo->prepare("SELECT id, username, email, token_expiry FROM users WHERE verification_token = ? AND email_verified = 0");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Check if verification token has expired
                if ($user['token_expiry'] && strtotime($user['token_expiry']) < time()) {
                    $message = 'Verification link has expired. Please sign up again.';
                    $messageType = 'error';
                } else {
                    // Successfully verify email and clean up token data
                    $updateStmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expiry = NULL WHERE id = ?");
                    if ($updateStmt->execute([$user['id']])) {
                        $message = 'Email verified successfully! Welcome, ' . htmlspecialchars($user['username']) . '! You can now sign in to your account.';
                        $messageType = 'success';
                    } else {
                        $message = 'Error verifying email. Please try again.';
                        $messageType = 'error';
                    }
                }
            } else {
                // Check if user with this token has already been verified
                $checkStmt = $pdo->prepare("SELECT id, username FROM users WHERE verification_token = ? AND email_verified = 1");
                $checkStmt->execute([$token]);
                $verifiedUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($verifiedUser) {
                    $message = 'This email has already been verified. You can sign in to your account.';
                    $messageType = 'info';
                } else {
                    $message = 'Invalid or expired verification link.';
                    $messageType = 'error';
                }
            }
            
        } catch (PDOException $e) {
            $message = 'Database error occurred. Please try again later.';
            $messageType = 'error';
            error_log("Database error in verify.php: " . $e->getMessage());
        }
    }
} else {
    $message = 'No verification token provided.';
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo $conf['site_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .verification-container { 
            max-width: 500px; 
            margin: 100px auto; 
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .btn-custom { 
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            padding: 10px 30px;
            border-radius: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-container text-center">
            <div class="mb-4">
                <?php if ($messageType == 'success'): ?>
                    <div class="text-success">
                        <i class="bi bi-check-circle" style="font-size: 3rem;"></i>
                    </div>
                <?php else: ?>
                    <div class="text-danger">
                        <i class="bi bi-x-circle" style="font-size: 3rem;"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <h2 class="mb-3">Email Verification</h2>
            
            <div class="alert alert-<?php echo $messageType == 'success' ? 'success' : 'danger'; ?> mb-4">
                <?php echo htmlspecialchars($message); ?>
            </div>
            
            <?php if ($messageType == 'success'): ?>
                <p class="text-muted mb-4">
                    Your email has been successfully verified. You can now access all features of your account.
                </p>
                <a href="Signin.php" class="btn btn-primary btn-custom">Sign In Now</a>
            <?php else: ?>
                <p class="text-muted mb-4">
                    If you're having trouble, please try signing up again or contact support.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="Signup.php" class="btn btn-outline-primary">Sign Up Again</a>
                    <a href="Signin.php" class="btn btn-primary btn-custom">Back to Sign In</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
