<?php
session_start();
require_once __DIR__ . '/config.php';

echo "<h3>Username Debug Test</h3>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>No user logged in. Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
    exit();
}

echo "<p>User ID in session: " . $_SESSION['user_id'] . "</p>";

// Connect to database
try {
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $conn = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Get user data
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user) {
        echo "<h4>✅ User found in database:</h4>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        $userName = $user['username'] ?? 'User';
        echo "<p><strong>Username:</strong> <span style='color: blue; font-weight: bold;'>" . htmlspecialchars($userName) . "</span></p>";
        
        // Show all users for debugging
        echo "<h4>All users in database:</h4>";
        $allStmt = $conn->prepare("SELECT id, username, email FROM users ORDER BY id DESC LIMIT 5");
        $allStmt->execute();
        $allUsers = $allStmt->fetchAll();
        echo "<pre>";
        print_r($allUsers);
        echo "</pre>";
        
    } else {
        echo "<p style='color: red;'>❌ User not found in database with ID: " . $_SESSION['user_id'] . "</p>";
        
        // Show all users for debugging
        echo "<h4>All users in database:</h4>";
        $allStmt = $conn->prepare("SELECT id, username, email, first_name, last_name FROM users ORDER BY id DESC LIMIT 5");
        $allStmt->execute();
        $allUsers = $allStmt->fetchAll();
        echo "<pre>";
        print_r($allUsers);
        echo "</pre>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'> Database error: " . $e->getMessage() . "</p>";
}
?>
