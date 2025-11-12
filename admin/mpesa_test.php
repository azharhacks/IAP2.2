<?php
echo "M-Pesa Transactions Page Test";
echo "<br>";
echo "If you can see this, the file is accessible.";

// Test session
session_start();
echo "<br>User ID: " . ($_SESSION['user_id'] ?? 'Not set');
echo "<br>Role: " . ($_SESSION['role'] ?? 'Not set');

// Test admin access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    echo "<br>❌ Not authenticated";
} elseif (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo "<br>❌ Not admin";
} else {
    echo "<br>✅ Admin access confirmed";
}

echo "<br><br><a href='users.php'>Back to Users</a>";
echo "<br><a href='../dashboard.php'>Back to Dashboard</a>";
?>
