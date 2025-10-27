<?php
/**
 * Database Connection and Library Testing Script
 * Tests database connectivity with different connection methods (socket vs host)
 * Verifies TwoFactorAuth library is properly installed and functional
 */

// Database connection test
require_once 'config.php';

echo "Testing database connection...\n";
echo "DB Host: " . $conf['db_host'] . "\n";
echo "DB Name: " . $conf['db_name'] . "\n";
echo "DB User: " . $conf['db_user'] . "\n";

try {
    // First attempt: Connect to database using Unix socket (XAMPP/MAMP)
    $socket_path = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
    if (file_exists($socket_path)) {
        $dsn = "mysql:unix_socket={$socket_path};dbname={$conf['db_name']};charset=utf8mb4";
        echo "Using socket connection: $socket_path\n";
    } else {
        // Fallback: Connect using standard TCP/IP host connection
        $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
        echo "Using host connection: {$conf['db_host']}\n";
    }
    
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful!\n";
    
    // Verify that users table exists in the database
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "Users table exists!\n";
    } else {
        echo "Users table does not exist!\n";
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}

// Test TwoFactorAuth library availability and functionality
try {
    require_once 'vendor/autoload.php';
    $tfa = new RobThree\Auth\TwoFactorAuth();
    echo "TwoFactorAuth library loaded successfully!\n";
} catch (Exception $e) {
    echo "TwoFactorAuth library error: " . $e->getMessage() . "\n";
}
?>
