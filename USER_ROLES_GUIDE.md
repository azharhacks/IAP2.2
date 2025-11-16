# USER ROLE SYSTEM - COMPREHENSIVE GUIDE

## Overview
The e-commerce platform implements a three-tier user role system that provides different levels of access and permissions:

1. **User** (Regular Customers)
2. **Admin** (Store Administrators) 
3. **Super Admin** (System Administrators)

---

## Role Definitions and Permissions

### 1. **USER** - Regular Customer Role
**Default Role**: All new registrations automatically receive 'user' role

**Permissions & Access**:
- ✅ **Shopping Features**:
  - Browse product catalog
  - Add items to cart
  - Complete checkout process
  - Place orders and make payments
  - Track order status and history

- ✅ **Account Management**:
  - View and edit personal profile
  - Manage shipping addresses
  - View order history and details
  - Enable/disable 2FA authentication
  - Update account settings

- ✅ **Available Pages**:
  - Homepage (`Index.php`)
  - Product catalog (`products.php`)
  - Product details (`product.php`)
  - Shopping cart (`cart.php`)
  - Checkout (`checkout.php`)
  - Personal dashboard (`dashboard.php`)
  - Profile management (`profile.php`)
  - Order history (`orders.php`)
  - Address management (`addresses.php`)

- ❌ **Restricted Areas**:
  - Admin panel (`/admin/` directory)
  - User management functions
  - Product management (adding/editing products)
  - Order management for other users
  - System configuration access

**Database Role**: `role = 'user'`

---

### 2. **ADMIN** - Store Administrator Role
**Purpose**: Manage day-to-day store operations and customer service

**Permissions & Access**:
- ✅ **All User Permissions** (can shop like regular customers)
- ✅ **Administrative Features**:
  - Access admin panel (`/admin/`)
  - View and manage all users
  - View user details and statistics
  - Manage orders for all customers
  - Update order statuses
  - View sales reports and analytics

- ✅ **User Management**:
  - View all registered users (`admin/users.php`)
  - Access detailed user profiles (`admin/user_details.php`)
  - View user order history and addresses
  - Check user verification status (email, 2FA)
  - View user statistics (orders, spending, etc.)

- ✅ **Order Management**:
  - Process and update order statuses
  - Handle customer service issues
  - Generate shipping labels
  - Manage returns and refunds

- ❌ **Restricted Areas**:
  - Cannot change user roles (only Super Admin)
  - Cannot delete users (safety feature)
  - Cannot access system configuration
  - Cannot manage other admins

**Database Role**: `role = 'admin'`

**Access Check Example**:
```php
// Admin required pages
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../Signin.php');
    exit();
}
```

---

### 3. **SUPER ADMIN** - System Administrator Role
**Purpose**: Full system control and administrative oversight

**Permissions & Access**:
- ✅ **All Admin Permissions** (complete admin functionality)
- ✅ **System Administration**:
  - Manage admin accounts
  - Change user roles (promote/demote users)
  - Access system configuration
  - Database management
  - Security settings management

- ✅ **Advanced User Management**:
  - Create and delete admin accounts
  - Promote users to admin status
  - Demote admins to regular users
  - Override security restrictions
  - Access sensitive user data

- ✅ **System Control**:
  - Server configuration access
  - Database schema modifications
  - Security policy management
  - Backup and restore operations
  - System monitoring and logs

**Database Role**: `role = 'super_admin'`

**Access Check Example**:
```php
// Super admin required functions
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    exit('Super Admin access required');
}
```

---

## Role Management Implementation

### Database Schema
```sql
-- Users table with role column
CREATE TABLE users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin','super_admin') DEFAULT 'user',
    verification_token VARCHAR(255),
    email_verified TINYINT(1) DEFAULT 0,
    verified TINYINT(1) DEFAULT 0,
    totp_secret VARCHAR(32),
    token_expiry TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Current Users in System
Based on database query:
- **User ID 15**: `devyanjets` - **Admin** role
- **User ID 17**: `devyanj2805` - **User** role

### Session Role Management
```php
// Set role in session during login
$_SESSION['role'] = $user['role']; // 'user', 'admin', or 'super_admin'

// Check role throughout application
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    // Admin-only functionality
}
```

### Role-Based Access Control Examples

#### 1. **Navigation Menu** (Layout.php)
```php
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <li class="nav-item">
        <a href="<?= SITE_URL ?>/admin/users.php">Manage Users</a>
    </li>
<?php endif; ?>
```

#### 2. **Admin Panel Access** (admin/users.php)
```php
// Require admin or super_admin role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Access denied - Admin privileges required');
}
```

#### 3. **User Details View** (admin/user_details.php)
```php
// Display role with appropriate styling
$roleColors = [
    'user' => 'secondary',        // Gray badge
    'admin' => 'danger',          // Red badge  
    'super_admin' => 'dark'       // Dark badge
];
?>
<span class="badge bg-<?= $roleColors[$user['role']] ?>">
    <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
</span>
```

---

## Administrative Interface Features

### Admin Dashboard Components

#### 1. **User Management** (`admin/users.php`)
- List all registered users
- Display user roles with color-coded badges
- Quick access to user details
- User statistics and status overview

#### 2. **User Details** (`admin/user_details.php`)
- Complete user profile information
- Order history and statistics
- Address management view
- Account verification status
- Role display and management

#### 3. **Order Management** (`admin/orders.php`)
- View all customer orders
- Update order statuses
- Process payments and refunds
- Generate reports

### Security Features

#### 1. **Role Verification**
Every admin page includes role verification:
```php
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access denied');
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Access denied - Admin privileges required');
}
```

#### 2. **Safety Measures**
- Prevent deletion of last admin account
- Role change confirmation requirements
- Audit logging for administrative actions
- Session timeout for security

---

## Role Transition and Management

### Promoting Users to Admin
```sql
-- Promote user to admin (Super Admin action)
UPDATE users SET role = 'admin' WHERE id = [user_id];
```

### Creating Super Admin Account
```sql
-- Create super admin (manual database operation)
INSERT INTO users (username, email, password, role, email_verified) 
VALUES ('superadmin', 'admin@domain.com', 'hashed_password', 'super_admin', 1);
```

### Role-Based Features Summary

| Feature | User | Admin | Super Admin |
|---------|------|-------|-------------|
| Shopping & Orders | ✅ | ✅ | ✅ |
| Profile Management | ✅ | ✅ | ✅ |
| View All Users | ❌ | ✅ | ✅ |
| Manage Orders | Own Only | All Orders | All Orders |
| User Role Changes | ❌ | ❌ | ✅ |
| System Configuration | ❌ | ❌ | ✅ |
| Admin Panel Access | ❌ | ✅ | ✅ |
| Delete Users | ❌ | ❌ | ✅ |

---

## Best Practices for Role Management

### 1. **Principle of Least Privilege**
- Users get minimum permissions needed
- Regular review of admin privileges
- Temporary role elevation when needed

### 2. **Security Considerations**
- Regular audit of admin accounts
- Strong password requirements for admins
- Mandatory 2FA for admin accounts
- Session timeout for administrative sessions

### 3. **Role Assignment Guidelines**
- **User**: Default for all customers
- **Admin**: Store managers, customer service
- **Super Admin**: Technical administrators only

---

## Practical Examples

### Testing Different Roles

#### 1. **As a Regular User**:
```
Login as: devyanj2805 (User role)
- Can browse products, add to cart, checkout
- Can manage own profile and addresses
- Cannot access /admin/ URLs
- Dashboard shows personal orders only
```

#### 2. **As an Admin**:
```
Login as: devyanjets (Admin role)
- All user capabilities PLUS:
- Access to admin/users.php (user management)
- Can view all customer details
- Can manage orders for all users
- Cannot change user roles or delete users
```

#### 3. **As a Super Admin**:
```
Would have access to:
- All admin capabilities PLUS:
- User role management (promote/demote)
- System configuration access
- Database administration
- Can delete users and manage admins
```

---

## Security Implementation Details

### Session-Based Role Storage
```php
// During login (after password verification)
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['2fa_verified'] = false; // Requires 2FA next

// During 2FA verification
$_SESSION['2fa_verified'] = true; // Now fully authenticated
```

### Page-Level Protection
```php
// Standard user authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    header('Location: Signin.php');
    exit();
}

// Admin-level protection
if (!in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Admin access required');
}
```

### Database Role Validation
```php
// Always verify role from database for sensitive operations
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentRole = $stmt->fetchColumn();

if ($currentRole !== 'admin' && $currentRole !== 'super_admin') {
    exit('Insufficient privileges');
}
```

This comprehensive role system ensures secure, scalable user management while maintaining clear separation of concerns and appropriate access controls throughout the e-commerce platform.
