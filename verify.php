<?php
require_once 'config.php';
require_once 'ClassAutoload.php';

$message = '';
$messageType = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Connect to database using socket (XAMPP)
        $socket_path = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
        if (file_exists($socket_path)) {
            $dsn = "mysql:unix_socket={$socket_path};dbname={$conf['db_name']};charset=utf8mb4";
        } else {
            $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
        }
        $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if token exists and is not expired
        $stmt = $pdo->prepare("SELECT id, email, token_expiry FROM users WHERE verification_token = ? AND email_verified = 0");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check if token is expired
            if ($user['token_expiry'] && strtotime($user['token_expiry']) < time()) {
                $message = 'Verification link has expired. Please sign up again.';
                $messageType = 'error';
            } else {
                // Mark email as verified
                $updateStmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expiry = NULL WHERE id = ?");
                if ($updateStmt->execute([$user['id']])) {
                    $message = 'Email verified successfully! You can now sign in to your account.';
                    $messageType = 'success';
                } else {
                    $message = 'Error verifying email. Please try again.';
                    $messageType = 'error';
                }
            }
        } else {
            $message = 'Invalid verification link or email already verified.';
            $messageType = 'error';
        }
        
    } catch (PDOException $e) {
        $message = 'Database error occurred. Please try again later.';
        $messageType = 'error';
        error_log("Database error in verify.php: " . $e->getMessage());
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
                <a href="signin.php" class="btn btn-primary btn-custom">Sign In Now</a>
            <?php else: ?>
                <p class="text-muted mb-4">
                    If you're having trouble, please try signing up again or contact support.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="signup.php" class="btn btn-outline-primary">Sign Up Again</a>
                    <a href="signin.php" class="btn btn-primary btn-custom">Back to Sign In</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
