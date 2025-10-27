<?php
/**
 * Database Structure Debugging Tool
 * Displays the structure of the users table and recent user registrations
 * Useful for development and troubleshooting database schema issues
 */

require_once __DIR__ . '/config.php';

echo "<h3>Database Structure Test</h3>";

try {
    // Establish database connection with error handling
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $conn = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Query to get users table structure (column definitions)
    $stmt = $conn->prepare("DESCRIBE users");
    $stmt->execute();
    $structure = $stmt->fetchAll();
    
    // Display table structure in HTML format
    echo "<h4>Users table structure:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($structure as $field) {
        echo "<tr>";
        echo "<td>" . $field['Field'] . "</td>";
        echo "<td>" . $field['Type'] . "</td>";
        echo "<td>" . $field['Null'] . "</td>";
        echo "<td>" . $field['Key'] . "</td>";
        echo "<td>" . $field['Default'] . "</td>";
        echo "<td>" . $field['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Query to get recent user registrations (last 10 users)
    echo "<h4>Recent users in database:</h4>";
    $userStmt = $conn->prepare("SELECT id, username, email, email_verified, created_at FROM users ORDER BY id DESC LIMIT 10");
    $userStmt->execute();
    $users = $userStmt->fetchAll();
    
    // Display user data or show message if no users found
    if (empty($users)) {
        echo "<p>No users found in database.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Email Verified</th><th>Created At</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            // Show checkmark or X for email verification status
            echo "<td>" . ($user['email_verified'] ? '✅ Yes' : '❌ No') . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (PDOException $e) {
    // Display database error in red text
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>
