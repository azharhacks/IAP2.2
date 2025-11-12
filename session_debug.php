<?php
session_start();
echo "<h3>Session Debug Info:</h3>";
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p><strong>Username:</strong> " . ($_SESSION['username'] ?? 'Not set') . "</p>";
echo "<p><strong>Role:</strong> " . ($_SESSION['role'] ?? 'Not set') . "</p>";
echo "<p><strong>2FA Verified:</strong> " . (($_SESSION['2fa_verified'] ?? false) ? 'Yes' : 'No') . "</p>";

echo "<h3>Admin Check:</h3>";
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo "<p style='color: green;'><strong>✅ You should see admin menu items!</strong></p>";
    echo "<p><a href='admin/mpesa_transactions.php' style='background: #00D4AA; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Direct Link to M-Pesa Transactions</a></p>";
} else {
    echo "<p style='color: red;'><strong>❌ Admin role not detected in session</strong></p>";
    echo "<p>You may need to log out and log back in.</p>";
}

echo "<h3>Quick Links:</h3>";
echo "<ul>";
echo "<li><a href='admin/mpesa_transactions.php'>M-Pesa Transactions</a></li>";
echo "<li><a href='admin/index.php'>Admin Dashboard</a></li>";
echo "<li><a href='admin/orders.php'>Order Management</a></li>";
echo "<li><a href='admin/users.php'>User Management</a></li>";
echo "</ul>";
?>
