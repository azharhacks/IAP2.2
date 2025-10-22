<?php
// Database connection test
require_once 'config.php';

echo "Testing database connection...\n";
echo "DB Host: " . $conf['db_host'] . "\n";
echo "DB Name: " . $conf['db_name'] . "\n";
echo "DB User: " . $conf['db_user'] . "\n";

try {
    // Connect to database using socket (XAMPP)
    $socket_path = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
    if (file_exists($socket_path)) {
        $dsn = "mysql:unix_socket={$socket_path};dbname={$conf['db_name']};charset=utf8mb4";
        echo "Using socket connection: $socket_path\n";
    } else {
        $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
        echo "Using host connection: {$conf['db_host']}\n";
    }
    
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful!\n";
    
    // Test if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "Users table exists!\n";
    } else {
        echo "Users table does not exist!\n";
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}

// Test TwoFactorAuth library
try {
    require_once 'vendor/autoload.php';
    $tfa = new RobThree\Auth\TwoFactorAuth();
    echo "TwoFactorAuth library loaded successfully!\n";
} catch (Exception $e) {
    echo "TwoFactorAuth library error: " . $e->getMessage() . "\n";
}
?>
