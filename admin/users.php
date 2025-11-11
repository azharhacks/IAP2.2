<?php
session_start();
require_once '../config.php';
require_once '../ClassAutoload.php';

// Admin check - require login and admin role
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Signin.php?redirect=admin/users.php');
    exit();
}

// Check if user has admin or super_admin role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    // Redirect non-admin users with error message
    header('Location: ../dashboard.php?error=access_denied');
    exit();
}

try {
    $pdo = new PDO("mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8", 
                   $conf['db_user'], $conf['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle user management actions
$success_message = '';
$error_message = '';

if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'toggle_status':
            $userId = (int)$_POST['user_id'];
            $newStatus = $_POST['status'] === '1' ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE users SET email_verified = ? WHERE id = ?");
            if ($stmt->execute([$newStatus, $userId])) {
                $success_message = "User status updated successfully!";
            } else {
                $error_message = "Failed to update user status.";
            }
            break;
            
        case 'change_role':
            $userId = (int)$_POST['user_id'];
            $newRole = $_POST['role'];
            
            // Prevent removing the last admin
            if ($newRole !== 'admin') {
                $stmt = $pdo->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin' AND id != ?");
                $stmt->execute([$userId]);
                $adminCount = $stmt->fetch()['admin_count'];
                
                if ($adminCount < 1) {
                    $error_message = "Cannot remove admin role - at least one admin must remain.";
                    break;
                }
            }
            
            $validRoles = ['user', 'admin', 'super_admin'];
            if (in_array($newRole, $validRoles)) {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                if ($stmt->execute([$newRole, $userId])) {
                    $success_message = "User role updated successfully!";
                } else {
                    $error_message = "Failed to update user role.";
                }
            } else {
                $error_message = "Invalid role specified.";
            }
            break;
            
        case 'delete_user':
            $userId = (int)$_POST['user_id'];
            
            // Prevent deleting the last admin
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userRole = $stmt->fetch()['role'];
            
            if ($userRole === 'admin') {
                $stmt = $pdo->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
                $stmt->execute();
                $adminCount = $stmt->fetch()['admin_count'];
                
                if ($adminCount <= 1) {
                    $error_message = "Cannot delete the last admin user.";
                    break;
                }
            }
            
            try {
                $pdo->beginTransaction();
                
                // Delete related records first
                $tables = ['user_profiles', 'addresses', 'cart', 'wishlist'];
                foreach ($tables as $table) {
                    $stmt = $pdo->prepare("DELETE FROM $table WHERE user_id = ?");
                    $stmt->execute([$userId]);
                }
                
                // Delete orders and related data
                $stmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ?");
                $stmt->execute([$userId]);
                $orders = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($orders)) {
                    $orderIds = implode(',', array_map('intval', $orders));
                    $pdo->exec("DELETE FROM order_status_history WHERE order_id IN ($orderIds)");
                    $pdo->exec("DELETE FROM order_items WHERE order_id IN ($orderIds)");
                    $pdo->exec("DELETE FROM orders WHERE user_id = $userId");
                }
                
                // Finally delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                
                $pdo->commit();
                $success_message = "User deleted successfully!";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Failed to delete user: " . $e->getMessage();
            }
            break;
    }
}

// Get users with profile information
$query = "SELECT u.*, up.first_name, up.last_name, up.phone 
          FROM users u 
          LEFT JOIN user_profiles up ON u.id = up.user_id 
          ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$layout = new Layout();
$layout->header('Admin - User Management');
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Admin Panel</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="orders.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart me-2"></i>Orders
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                    <a href="../dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-users me-2"></i>User Management</h2>
            </div>

            <!-- Alerts -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-2"></i>Registered Users 
                        <span class="badge bg-secondary"><?= count($users) ?></span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No users found</h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>User Details</th>
                                        <th>Contact</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Registration</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($user['id']) ?></strong></td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                                                <?php if ($user['first_name'] || $user['last_name']): ?>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <small><?= htmlspecialchars($user['email']) ?></small><br>
                                                <?php if ($user['phone']): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($user['phone']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $roleColors = [
                                                'user' => 'secondary',
                                                'admin' => 'danger',
                                                'super_admin' => 'dark'
                                            ];
                                            $roleColor = $roleColors[$user['role']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $roleColor ?>">
                                                <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <?php if ($user['email_verified']): ?>
                                                    <span class="badge bg-success mb-1">Email Verified</span><br>
                                                <?php else: ?>
                                                    <span class="badge bg-warning mb-1">Email Pending</span><br>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['totp_secret']): ?>
                                                    <span class="badge bg-info">2FA Enabled</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">2FA Disabled</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?= date('M j, Y', strtotime($user['created_at'])) ?><br>
                                                <small class="text-muted"><?= date('g:i A', strtotime($user['created_at'])) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#userModal"
                                                        onclick="loadUserDetails(<?= $user['id'] ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#roleModal"
                                                        onclick="openRoleModal(<?= $user['id'] ?>, '<?= $user['role'] ?>', '<?= htmlspecialchars($user['username']) ?>')"
                                                        title="Change Role">
                                                    <i class="fas fa-user-cog"></i>
                                                </button>
                                                <button class="btn btn-outline-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#statusModal"
                                                        onclick="openStatusModal(<?= $user['id'] ?>, <?= $user['email_verified'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                                        title="Toggle Status">
                                                    <i class="fas fa-toggle-<?= $user['email_verified'] ? 'on' : 'off' ?>"></i>
                                                </button>
                                                <?php if ($user['role'] !== 'admin' || 
                                                         (array_filter($users, fn($u) => $u['role'] === 'admin') > 1)): ?>
                                                <button class="btn btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal"
                                                        onclick="openDeleteModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                                        title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center py-3">
                    <div class="spinner-border" role="status"></div>
                    <p class="mt-2">Loading user details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change User Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="change_role">
                    <input type="hidden" name="user_id" id="roleUserId">
                    
                    <p>Change role for user: <strong id="roleUsername"></strong></p>
                    
                    <div class="mb-3">
                        <label for="roleSelect" class="form-label">New Role</label>
                        <select class="form-select" name="role" id="roleSelect" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Changing user roles affects their access to admin features.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toggle Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Toggle User Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" id="statusUserId">
                    <input type="hidden" name="status" id="statusValue">
                    
                    <p id="statusMessage"></p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will toggle the user's email verification status.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="statusSubmitBtn">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    
                    <p>Are you sure you want to delete user: <strong id="deleteUsername"></strong>?</p>
                    
                    <p class="text-muted">This will permanently delete:</p>
                    <ul class="text-muted">
                        <li>User account and profile</li>
                        <li>All user addresses</li>
                        <li>Order history</li>
                        <li>Cart items and wishlist</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function loadUserDetails(userId) {
    const content = document.getElementById('userDetailsContent');
    
    fetch(`user_details.php?id=${userId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Failed to load user details.</div>';
        });
}

function openRoleModal(userId, currentRole, username) {
    document.getElementById('roleUserId').value = userId;
    document.getElementById('roleUsername').textContent = username;
    document.getElementById('roleSelect').value = currentRole;
}

function openStatusModal(userId, currentStatus, username) {
    document.getElementById('statusUserId').value = userId;
    document.getElementById('statusValue').value = currentStatus ? '0' : '1';
    
    const action = currentStatus ? 'disable' : 'enable';
    const statusText = currentStatus ? 'verified' : 'unverified';
    
    document.getElementById('statusMessage').innerHTML = 
        `${action.charAt(0).toUpperCase() + action.slice(1)} user: <strong>${username}</strong>?<br>` +
        `Current status: <span class="badge bg-${currentStatus ? 'success' : 'warning'}">${statusText}</span>`;
    
    document.getElementById('statusSubmitBtn').textContent = action.charAt(0).toUpperCase() + action.slice(1);
}

function openDeleteModal(userId, username) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUsername').textContent = username;
}
</script>

<?php $layout->footer(); ?>