<?php
// Start session and include necessary files
session_start();
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Check if user is logged in and 2FA is verified
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/Signin.php');
    exit();
}

// Check if 2FA is verified
if (!isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header('Location: ' . SITE_URL . '/verify-2fa.php');
    exit();
}

// Get user data
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If user not found, destroy session and redirect
    if (!$user) {
        session_destroy();
        header('Location: ' . SITE_URL . '/Signin.php?error=user_not_found');
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Secure Area</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2>Dashboard</h2>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            Welcome back, <?php echo htmlspecialchars($user['email']); ?>!
                        </div>
                        <p>This is your secure dashboard. You have successfully logged in with 2FA verification.</p>
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
