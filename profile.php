<?php
/**
 * User Profile Management Page
 * Allows users to view and edit their profile information
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Check if user is logged in and 2FA is verified
if (!isset($_SESSION['user_id'])) {
    header('Location: Signin.php?redirect=profile.php');
    exit();
}

if (!isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header('Location: database/2fa_verify.php?redirect=profile.php');
    exit();
}

// Initialize database connection
try {
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_profile':
                    $firstName = trim($_POST['first_name'] ?? '');
                    $lastName = trim($_POST['last_name'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $dateOfBirth = $_POST['date_of_birth'] ?? null;
                    $gender = $_POST['gender'] ?? null;
                    
                    // Validation
                    if (empty($firstName) || empty($lastName)) {
                        throw new Exception("First name and last name are required.");
                    }
                    
                    if (!empty($phone) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $phone)) {
                        throw new Exception("Please enter a valid phone number.");
                    }
                    
                    if (!empty($dateOfBirth)) {
                        $birthDate = new DateTime($dateOfBirth);
                        $today = new DateTime();
                        if ($birthDate >= $today) {
                            throw new Exception("Please enter a valid date of birth.");
                        }
                    }
                    
                    // Check if profile exists
                    $stmt = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $profileExists = $stmt->fetch();
                    
                    if ($profileExists) {
                        // Update existing profile
                        $stmt = $pdo->prepare("
                            UPDATE user_profiles 
                            SET first_name = ?, last_name = ?, phone = ?, date_of_birth = ?, gender = ?, updated_at = NOW()
                            WHERE user_id = ?
                        ");
                        $stmt->execute([$firstName, $lastName, $phone, $dateOfBirth ?: null, $gender ?: null, $userId]);
                    } else {
                        // Insert new profile
                        $stmt = $pdo->prepare("
                            INSERT INTO user_profiles (user_id, first_name, last_name, phone, date_of_birth, gender)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$userId, $firstName, $lastName, $phone, $dateOfBirth ?: null, $gender ?: null]);
                    }
                    
                    $success = "Profile updated successfully!";
                    break;
                    
                case 'update_account':
                    $username = trim($_POST['username'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    
                    // Validation
                    if (empty($username) || empty($email)) {
                        throw new Exception("Username and email are required.");
                    }
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Please enter a valid email address.");
                    }
                    
                    if (strlen($username) < 3) {
                        throw new Exception("Username must be at least 3 characters long.");
                    }
                    
                    // Check if username or email already exists (excluding current user)
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                    $stmt->execute([$username, $email, $userId]);
                    if ($stmt->fetch()) {
                        throw new Exception("Username or email already exists.");
                    }
                    
                    // Update account information
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $userId]);
                    
                    // Update session
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    
                    $success = "Account information updated successfully!";
                    break;
                    
                case 'change_password':
                    $currentPassword = $_POST['current_password'] ?? '';
                    $newPassword = $_POST['new_password'] ?? '';
                    $confirmPassword = $_POST['confirm_password'] ?? '';
                    
                    // Validation
                    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                        throw new Exception("All password fields are required.");
                    }
                    
                    if ($newPassword !== $confirmPassword) {
                        throw new Exception("New passwords do not match.");
                    }
                    
                    if (strlen($newPassword) < 6) {
                        throw new Exception("New password must be at least 6 characters long.");
                    }
                    
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    if (!$user || !password_verify($currentPassword, $user['password'])) {
                        throw new Exception("Current password is incorrect.");
                    }
                    
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    
                    $success = "Password changed successfully!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch current user data
try {
    $stmt = $pdo->prepare("
        SELECT u.*, up.first_name, up.last_name, up.phone, up.date_of_birth, up.gender, up.profile_picture
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch();
    
    if (!$userData) {
        throw new Exception("User not found.");
    }
} catch (Exception $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// Create layout instance
$layout = new Layout();

// Custom CSS for profile page
$customCSS = '
.profile-card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: var(--backdrop-blur);
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid var(--primary-gradient);
    object-fit: cover;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
}

.nav-pills .nav-link {
    border-radius: var(--border-radius);
    margin-right: 0.5rem;
    transition: var(--transition);
}

.nav-pills .nav-link.active {
    background: var(--primary-gradient);
    border: none;
}

.form-section {
    background: rgba(255, 255, 255, 0.8);
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stats-card {
    background: var(--primary-gradient);
    color: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    text-align: center;
    border: none;
    box-shadow: var(--card-shadow);
}

.stats-card i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}
';

$layout->header('My Profile', $customCSS);
$layout->navbar('profile');

// Breadcrumb
$layout->breadcrumb(['My Profile']);

$layout->contentStart();
?>

<div class="container py-4">
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-lg-4 mb-4">
            <div class="card profile-card">
                <div class="card-body text-center p-4">
                    <div class="profile-avatar mx-auto mb-3">
                        <?php if ($userData['profile_picture']): ?>
                            <img src="<?php echo htmlspecialchars($userData['profile_picture']); ?>" 
                                 alt="Profile Picture" class="profile-avatar">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="mb-1">
                        <?php 
                        $displayName = trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''));
                        echo htmlspecialchars($displayName ?: $userData['username']); 
                        ?>
                    </h4>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($userData['email']); ?></p>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="stats-card">
                                <i class="fas fa-shopping-bag"></i>
                                <div class="fw-bold">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
                                    $stmt->execute([$userId]);
                                    $orderStats = $stmt->fetch();
                                    echo $orderStats['order_count'] ?? 0;
                                    ?>
                                </div>
                                <small>Orders</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card" style="background: var(--success-gradient);">
                                <i class="fas fa-heart"></i>
                                <div class="fw-bold">0</div>
                                <small>Wishlist</small>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-muted small">
                        <i class="fas fa-calendar-alt me-1"></i>
                        Member since <?php echo date('M Y', strtotime($userData['created_at'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="col-lg-8">
            <!-- Navigation Tabs -->
            <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" 
                            data-bs-target="#profile-content" type="button" role="tab">
                        <i class="fas fa-user me-2"></i>Profile Information
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="account-tab" data-bs-toggle="pill" 
                            data-bs-target="#account-content" type="button" role="tab">
                        <i class="fas fa-cog me-2"></i>Account Settings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="pill" 
                            data-bs-target="#security-content" type="button" role="tab">
                        <i class="fas fa-shield-alt me-2"></i>Security
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="profileTabContent">
                <!-- Profile Information Tab -->
                <div class="tab-pane fade show active" id="profile-content" role="tabpanel">
                    <div class="form-section">
                        <h5 class="mb-4">
                            <i class="fas fa-user-edit text-primary me-2"></i>Personal Information
                        </h5>
                        
                        <form method="POST" data-validate="true">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                           value="<?php echo $userData['date_of_birth'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($userData['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($userData['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($userData['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Account Settings Tab -->
                <div class="tab-pane fade" id="account-content" role="tabpanel">
                    <div class="form-section">
                        <h5 class="mb-4">
                            <i class="fas fa-user-cog text-primary me-2"></i>Account Settings
                        </h5>
                        
                        <form method="POST" data-validate="true">
                            <input type="hidden" name="action" value="update_account">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                <div class="form-text">
                                    <?php if ($userData['email_verified']): ?>
                                        <i class="fas fa-check-circle text-success me-1"></i>Email verified
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-triangle text-warning me-1"></i>Email not verified
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Account
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div class="tab-pane fade" id="security-content" role="tabpanel">
                    <div class="form-section">
                        <h5 class="mb-4">
                            <i class="fas fa-key text-primary me-2"></i>Change Password
                        </h5>
                        
                        <form method="POST" data-validate="true">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password *</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password *</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                    
                    <!-- 2FA Status -->
                    <div class="form-section">
                        <h5 class="mb-4">
                            <i class="fas fa-shield-alt text-primary me-2"></i>Two-Factor Authentication
                        </h5>
                        
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1">2FA Status</h6>
                                <p class="text-muted mb-0">
                                    <?php if ($userData['totp_secret']): ?>
                                        <i class="fas fa-shield-alt text-success me-1"></i>Two-factor authentication is enabled
                                    <?php else: ?>
                                        <i class="fas fa-shield text-warning me-1"></i>Two-factor authentication is not set up
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <?php if ($userData['totp_secret']): ?>
                                    <span class="badge bg-success">Enabled</span>
                                <?php else: ?>
                                    <a href="database/2fa_verify.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i>Setup 2FA
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$layout->contentEnd();
$layout->footer();
?>
