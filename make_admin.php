<?php
/**
 * Admin User Creation Script
 * Use this script to make any user an admin from command line
 * 
 * Usage: php make_admin.php <user_email>
 * Example: php make_admin.php devyan.jethwa@strathmore.edu
 */

require_once 'config.php';

if ($argc < 2) {
    echo "Usage: php make_admin.php <user_email>\n";
    echo "Example: php make_admin.php devyan.jethwa@strathmore.edu\n";
    exit(1);
}

$email = $argv[1];

try {
    $pdo = new PDO("mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8", 
                   $conf['db_user'], $conf['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User with email '$email' not found.\n";
        exit(1);
    }
    
    if ($user['role'] === 'admin') {
        echo "ℹ️  User '{$user['username']}' is already an admin.\n";
        exit(0);
    }
    
    // Make user admin
    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
    $stmt->execute([$email]);
    
    echo "✅ Successfully made user '{$user['username']}' ({$email}) an admin!\n";
    echo "\n";
    echo "Admin Access Information:\n";
    echo "👤 Username: {$user['username']}\n";
    echo "📧 Email: {$user['email']}\n";
    echo "🔑 Role: admin\n";
    echo "\n";
    echo "🎯 Admin Panel URLs:\n";
    echo "📋 Order Management: http://localhost:8000/admin/orders.php\n";
    echo "👥 User Management: http://localhost:8000/admin/users.php\n";
    echo "\n";
    echo "🚀 To access admin panel:\n";
    echo "1. Sign in with the user's credentials\n";
    echo "2. Complete 2FA verification\n";
    echo "3. Look for 'Order Management' and 'User Management' in the user dropdown menu\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
