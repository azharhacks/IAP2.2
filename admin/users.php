<?php
/**
 * Admin User Management Page
 * Allows administrators to view and manage all registered users in the system
 * Requires admin role for access
 */

// Start session to maintain authentication state
session_start();
require_once '../config.php';
require_once '../ClassAutoload.php';

// Admin authorization check - ensure user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect unauthorized users to login page
    header('Location: ../Signin.php');
    exit();
}

// Get database connection instance using singleton pattern
$db = Database::getInstance()->getConnection();

// Query to fetch all users with their customer details
// LEFT JOIN ensures users without customer records are still displayed
// Ordered by creation date to show most recent users first
$query = "SELECT u.*, c.first_name, c.last_name, c.phone 
          FROM users u 
          LEFT JOIN customers c ON u.id = c.user_id 
          ORDER BY u.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
// Fetch all users as associative array
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>User Management</h2>
        <!-- User management table displaying all registered users -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Loop through each user and display their information -->
                <?php foreach ($users as $user): ?>
                <tr>
                    <!-- User ID for reference -->
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <!-- Display full name (first + last) -->
                    <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                    <!-- User email address -->
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <!-- Phone number from customer table -->
                    <td><?= htmlspecialchars($user['phone']) ?></td>
                    <!-- Account status (active/inactive) -->
                    <td><?= htmlspecialchars($user['status']) ?></td>
                    <!-- Action buttons for user management -->
                    <td>
                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>