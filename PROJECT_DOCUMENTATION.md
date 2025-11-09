# E-Commerce Platform Technical Documentation
## IAP2.2Dev - Complete Amazon-Style Marketplace

### Overview
This is a comprehensive, production-ready e-commerce platform built with modern web technologies. The system features secure authentication with 2FA, complete shopping cart functionality, order management, user profile management, and administrative tools. The architecture follows MVC patterns with separation of concerns and modern design principles.

### System Architecture
```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                       │
├─────────────────────────────────────────────────────────────┤
│  Frontend: Bootstrap 5.3.0 + Font Awesome 6.4.0 + JS     │
│  Layout System: Centralized Layout Class                   │
│  Responsive Design: Mobile-First Approach                  │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                        │
├─────────────────────────────────────────────────────────────┤
│  Core Pages: Index, Products, Cart, Checkout, Dashboard    │
│  User Management: Profile, Addresses, Orders, Auth         │
│  Admin Panel: User Management, Product Management          │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                    BUSINESS LOGIC LAYER                     │
├─────────────────────────────────────────────────────────────┤
│  Manager Classes: ProductManager, OrderManager,            │
│                  CartManager, UserManager                  │
│  Authentication: 2FA, Email Verification, Sessions         │
│  Email System: PHPMailer Integration                       │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                    DATA ACCESS LAYER                        │
├─────────────────────────────────────────────────────────────┤
│  Database: MySQL/MariaDB with PDO                          │
│  Connection: Centralized Configuration                     │
│  Security: Prepared Statements, Input Sanitization         │
└─────────────────────────────────────────────────────────────┘
```

## DATABASE ARCHITECTURE

### Database Schema Overview
The system uses a MySQL/MariaDB database with 12 core tables, designed for scalability and data integrity:

```sql
DATABASE: auth_db
├── users                 (Authentication & Basic Info)
├── user_profiles         (Extended User Information)  
├── addresses            (User Shipping Addresses)
├── products             (Product Catalog)
├── product_images       (Product Image URLs)
├── brands              (Product Brands)
├── categories          (Product Categories)
├── cart                (Shopping Cart Items)
├── orders              (Order Headers)
├── order_items         (Order Line Items)
├── order_status_history (Order Status Tracking)
└── sessions            (User Sessions - if implemented)
```

### Detailed Table Structures

#### 1. **users** - Core Authentication
```sql
CREATE TABLE users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,          -- bcrypt hashed
    verification_token VARCHAR(255),         -- Email verification
    email_verified TINYINT(1) DEFAULT 0,
    verified TINYINT(1) DEFAULT 0,
    totp_secret VARCHAR(32),                 -- 2FA secret key
    token_expiry TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```
**Purpose**: Core user authentication and account management
**Key Features**: 
- Secure password hashing with bcrypt
- Email verification system
- 2FA (TOTP) support
- Unique email constraint

#### 2. **user_profiles** - Extended User Data
```sql
CREATE TABLE user_profiles (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male','female','other'),
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```
**Purpose**: Extended user profile information
**Relationship**: One-to-One with users table

#### 3. **addresses** - Shipping Addresses
```sql
CREATE TABLE addresses (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    type VARCHAR(50) DEFAULT 'shipping',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    company VARCHAR(255),
    address_type ENUM('home','work','other') DEFAULT 'home',
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    county VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Kenya',
    phone VARCHAR(20) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_user_default (user_id, is_default)
);
```
**Purpose**: User shipping and billing addresses
**Features**: 
- Multiple addresses per user
- Default address system
- Soft delete capability
- Kenya-focused location fields

#### 4. **products** - Product Catalog
```sql
CREATE TABLE products (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2),             -- Original price for discounts
    category_id INT(11),
    brand_id INT(11),
    sku VARCHAR(100) UNIQUE,
    stock_quantity INT(11) DEFAULT 0,
    weight DECIMAL(8,2),
    dimensions VARCHAR(100),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_brand (brand_id),
    INDEX idx_featured (is_featured),
    INDEX idx_active (is_active),
    INDEX idx_price (price)
);
```
**Purpose**: Complete product catalog with SEO and inventory management
**Features**:
- Price comparison support
- Stock management
- SEO meta tags
- Featured products
- Multi-dimensional search indexes

#### 5. **orders** - Order Management
```sql
CREATE TABLE orders (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_cost DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KSH',
    payment_method VARCHAR(50),
    payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    shipping_address_id INT(11),
    billing_address_id INT(11),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shipping_address_id) REFERENCES addresses(id) ON SET NULL,
    FOREIGN KEY (billing_address_id) REFERENCES addresses(id) ON SET NULL,
    INDEX idx_user_orders (user_id),
    INDEX idx_status (status),
    INDEX idx_order_number (order_number)
);
```
**Purpose**: Order header information and status tracking
**Features**:
- Unique order number generation
- Comprehensive status tracking
- Multi-currency support
- Separate shipping/billing addresses
- Payment status tracking

### Database Relationships

```
users (1) ──────────── (∞) user_profiles
  │
  ├─ (1) ──────────── (∞) addresses
  │
  ├─ (1) ──────────── (∞) cart
  │
  └─ (1) ──────────── (∞) orders
                        │
                        └─ (1) ── (∞) order_items ── (∞) products
                                                        │
                                                        ├─ (∞) ── (1) categories
                                                        ├─ (∞) ── (1) brands  
                                                        └─ (1) ── (∞) product_images
```

## BACKEND ARCHITECTURE

### Core Configuration System

#### 1. **config.php** - Central Configuration
```php
<?php
// Database Configuration
$conf = [
    'db_host' => 'localhost',
    'db_name' => 'auth_db',
    'db_user' => 'root',
    'db_pass' => 'devyan2005',
    'site_name' => 'ShopKenya',
    'site_url' => 'http://localhost:8000',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'your-email@gmail.com',
    'smtp_pass' => 'your-app-password'
];
```
**Purpose**: Centralized configuration management
**Benefits**: Easy environment switching, secure credential management

#### 2. **ClassAutoload.php** - Automatic Class Loading
```php
<?php
spl_autoload_register(function ($class_name) {
    $paths = [
        __DIR__ . '/classes/' . $class_name . '.php',
        __DIR__ . '/Abstract/' . $class_name . '.php',
        __DIR__ . '/Mail/' . $class_name . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
```
**Purpose**: PSR-4 compatible autoloading system
**Benefits**: No manual require statements, cleaner code organization

### Manager Classes (Business Logic Layer)

#### 1. **ProductManager.php** - Product Operations
```php
class ProductManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    // Core Methods:
    public function getProducts($filters = [], $page = 1, $perPage = 12)
    public function getProductById($id)
    public function getFeaturedProducts($limit = 6)
    public function getCategories()
    public function getBrands()
    public function searchProducts($query, $limit = 20)
    public function getRelatedProducts($productId, $limit = 4)
}
```
**Responsibilities**:
- Product catalog management
- Search and filtering
- Category and brand management
- Featured products logic
- Related products algorithm

**Key Features**:
- Advanced filtering (price, category, brand, search)
- Pagination with performance optimization
- MariaDB-compatible SQL syntax
- Image URL handling
- SEO-friendly URL generation

#### 2. **OrderManager.php** - Order Processing
```php
class OrderManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    // Core Methods:
    public function createOrder($userId, $cartItems, $addressId, $paymentMethod)
    public function getUserOrders($userId, $page = 1, $limit = 10)
    public function getOrderById($orderId, $userId = null)
    public function updateOrderStatus($orderId, $status)
    public function generateOrderNumber()
    public function calculateOrderTotals($cartItems)
}
```
**Responsibilities**:
- Order creation and management
- Order status tracking
- Order number generation
- Total calculations
- Order history retrieval

**Key Features**:
- Atomic order creation (database transactions)
- Unique order number format: `ORD-YYYYMMDDHHmmss-RRRR`
- Comprehensive order status workflow
- Cart integration for seamless checkout
- Tax and shipping calculations

#### 3. **CartManager.php** - Shopping Cart Logic
```php
class CartManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    // Core Methods:
    public function addToCart($userId, $productId, $quantity = 1)
    public function updateCartItem($userId, $productId, $quantity)
    public function removeFromCart($userId, $productId)
    public function getCartItems($userId)
    public function getSessionCartItems()
    public function getCartTotals($userId)
    public function clearCart($userId)
    public function mergeSessionCart($userId)
}
```
**Responsibilities**:
- Cart item management
- Session cart handling
- Cart total calculations
- Cart merging (session to database)

**Key Features**:
- Dual cart system (session + database)
- Real-time total calculations
- Stock validation
- Cart persistence across sessions
- AJAX-compatible operations

### FRONTEND ARCHITECTURE

#### 1. **Layout System - Abstract/Layout.php**

The Layout class provides a centralized, consistent UI framework:

```php
class Layout {
    // Core Methods:
    public function header($pageTitle = '', $customCSS = '')
    public function navbar($activePage = '')
    public function banner($conf, $title = '', $subtitle = '', $ctaText = '', $ctaLink = '', $features = [])
    public function breadcrumb($breadcrumbs = [])
    public function footer()
    public function contentStart()
    public function contentEnd()
}
```

**Design System Features**:
- **CSS Custom Properties**: Modern variable system for consistent theming
- **Glassmorphism Effects**: Backdrop blur and transparency effects
- **Gradient System**: Primary, secondary, success, danger gradients
- **Animation System**: Smooth transitions and hover effects
- **Responsive Design**: Mobile-first approach with breakpoints

#### 2. **Frontend Technology Stack**

**CSS Framework**: Bootstrap 5.3.0
- Modern utility classes
- Flexbox and Grid layouts
- Responsive components
- Custom theme integration

**Icons**: Font Awesome 6.4.0
- 1000+ vector icons
- Consistent iconography
- Scalable and accessible

**Typography**: Inter Font Family
- Modern, readable typeface
- Multiple font weights (300-800)
- Optimized for screens

**JavaScript Enhancements**:
```javascript
// Enhanced notification system
function showNotification(message, type = 'info', duration = 5000)

// Cart management
function updateCartCount()

// Form enhancements
function enhanceFormValidation()

// Smooth scrolling and animations
function initSmoothScroll()
function initLazyLoading()
function initBackToTop()
```

#### 3. **Page Structure and Components**

**Core Pages**:
1. **Index.php** - Homepage with featured products
2. **products.php** - Product catalog with filtering
3. **product.php** - Individual product details
4. **cart.php** - Shopping cart management
5. **checkout.php** - Checkout process
6. **dashboard.php** - User dashboard
7. **profile.php** - User profile management
8. **addresses.php** - Address management
9. **orders.php** - Order history
10. **order_confirmation.php** - Order confirmation

**Authentication Pages**:
- **Signin.php** - User login
- **Signup.php** - User registration
- **verify.php** - Email verification
- **database/2fa_verify.php** - Two-factor authentication

## SECURITY ARCHITECTURE

### Authentication System

#### 1. **Multi-Layer Authentication**
```php
// Layer 1: Username/Password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$isValid = password_verify($inputPassword, $hashedPassword);

// Layer 2: Email Verification
$verificationToken = bin2hex(random_bytes(32));

// Layer 3: Two-Factor Authentication (TOTP)
$twoFA = new RobThree\Auth\TwoFactorAuth('ShopKenya');
$secret = $twoFA->createSecret();
$isValid = $twoFA->verifyCode($secret, $code);
```

#### 2. **Session Management**
```php
// Secure session configuration
session_start([
    'cookie_lifetime' => 0,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Session validation
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    // Redirect to authentication
}
```

#### 3. **Input Validation and Sanitization**
```php
// SQL Injection Prevention
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// XSS Prevention
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// CSRF Protection (implemented via form tokens)
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;
```

### Data Protection

#### 1. **Database Security**
- Prepared statements for all queries
- Foreign key constraints for data integrity
- Proper indexing for performance
- Soft deletes for data recovery

#### 2. **File Security**
- Restricted file uploads
- Path traversal prevention
- Secure file storage locations
- Input validation on all file operations

## APPLICATION WORKFLOW

### 1. **User Registration Flow**
```
User Registration → Email Verification → 2FA Setup → Profile Creation → Address Setup
```

1. **Signup.php**: Collect user credentials
2. **Email Verification**: Send verification email via PHPMailer
3. **verify.php**: Confirm email and activate account
4. **2fa_verify.php**: Setup TOTP authentication
5. **profile.php**: Complete profile information
6. **addresses.php**: Add shipping addresses

### 2. **Shopping Flow**
```
Browse Products → Add to Cart → Checkout → Payment → Order Confirmation
```

1. **products.php**: Browse and filter products
2. **product.php**: View product details
3. **cart.php**: Manage cart items
4. **checkout.php**: Process order with address selection
5. **order_confirmation.php**: Display order details

### 3. **Order Processing Flow**
```
Order Created → Payment Processing → Order Fulfillment → Shipping → Delivery
```

**Status Transitions**:
- `pending` → `processing` → `shipped` → `delivered`
- `pending` → `cancelled` (if needed)

### 4. **Admin Management Flow**
```
Admin Login → User Management → Product Management → Order Management
```

## FILE STRUCTURE AND ORGANIZATION

### Root Directory Structure
```
IAP2.2Dev/
├── Index.php                 # Homepage
├── config.php               # Configuration
├── ClassAutoload.php        # Autoloader
├── composer.json            # Dependencies
├── composer.lock            # Dependency lock file
│
├── Abstract/                # Abstract classes and layouts
│   ├── Layout.php           # Centralized layout system
│   └── forms.php           # Form abstractions
│
├── classes/                 # Business logic classes
│   ├── CartManager.php      # Shopping cart logic
│   ├── OrderManager.php     # Order processing
│   └── ProductManager.php   # Product management
│
├── database/                # Database utilities
│   ├── 2fa_verify.php      # 2FA verification
│   ├── *.sql               # Database schemas
│   └── migrations/         # Database migrations
│
├── Mail/                    # Email system
│   └── SendMail.php        # Email sending class
│
├── Plugins/                 # Third-party libraries
│   └── PHPMailer/          # Email library
│
├── vendor/                  # Composer dependencies
│   └── robthree/           # 2FA library
│
├── admin/                   # Administrative interface
│   └── users.php           # User management
│
└── tables/                  # Database table creation scripts
    ├── ecommerce_tables.sql
    ├── user.sql
    └── add_more_products.sql
```

### Core Application Files

#### **User-Facing Pages**
- `Index.php` - Landing page with featured products and company highlights
- `products.php` - Product catalog with advanced filtering and pagination
- `product.php` - Individual product detail page with related products
- `cart.php` - Shopping cart with quantity management and totals
- `checkout.php` - Multi-step checkout with address and payment selection
- `dashboard.php` - User dashboard with order statistics and recent activity
- `profile.php` - Comprehensive user profile management
- `addresses.php` - Address book management with CRUD operations
- `orders.php` - Order history with status tracking
- `order_confirmation.php` - Post-purchase confirmation page

#### **Authentication System**
- `Signin.php` - User login with 2FA integration
- `Signup.php` - User registration with email verification
- `verify.php` - Email verification handler
- `logout.php` - Secure session termination
- `database/2fa_verify.php` - Two-factor authentication setup and verification

#### **AJAX Endpoints**
- `cart_ajax.php` - Cart operations API endpoint

## DEPLOYMENT AND PERFORMANCE

### Development Environment
- **PHP Version**: 8.4.14
- **Database**: MySQL/MariaDB
- **Web Server**: PHP Development Server (php -S localhost:8000)
- **Dependencies**: Managed via Composer

### Production Considerations

#### 1. **Database Optimization**
```sql
-- Performance indexes
CREATE INDEX idx_products_search ON products(name, description);
CREATE INDEX idx_orders_user_status ON orders(user_id, status);
CREATE INDEX idx_cart_user_product ON cart(user_id, product_id);
```

#### 2. **Caching Strategy**
- Database query result caching
- Session data optimization
- Static asset caching (CSS, JS, images)
- Product image optimization

#### 3. **Security Hardening**
- HTTPS enforcement
- Environment variable configuration
- Database credential encryption
- Rate limiting on authentication endpoints
- Input validation and sanitization
- CSRF token implementation

#### 4. **Scalability Features**
- Prepared statement caching
- Connection pooling
- Image CDN integration
- Search index optimization
- API-ready architecture for mobile apps

## MAINTENANCE AND MONITORING

### Error Handling
```php
// Centralized error handling
try {
    // Database operations
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    // User-friendly error display
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    // Graceful degradation
}
```

### Logging System
- Database query logging
- Authentication attempt logging
- Error tracking and reporting
- Performance monitoring

### Backup Strategy
- Automated database backups
- File system backups
- Version control integration
- Recovery procedures

## ANALYTICS AND REPORTING

### Built-in Analytics
- Order tracking and reporting
- User registration trends
- Product performance metrics
- Cart abandonment tracking

### Integration Points
- Google Analytics ready
- Payment gateway integration points
- Email marketing integration
- Customer support integration

## FEATURES SUMMARY

### Completed Features
1. **User Management**: Registration, login, 2FA, profile management
2. **Product Catalog**: Full product management with categories and brands
3. **Shopping Cart**: Session and database cart management
4. **Checkout Process**: Multi-step checkout with address management
5. **Order Management**: Complete order lifecycle tracking
6. **Address Book**: Multiple address management per user
7. **Email System**: Verification emails and notifications
8. **Admin Panel**: User and product management
9. **Responsive Design**: Mobile-optimized interface
10. **Security**: Multi-layer authentication and data protection

### Production Ready Features
- Secure authentication with 2FA
- Complete e-commerce workflow
- Professional UI/UX design
- Scalable database architecture
- Error handling and logging
- Mobile-responsive design
- SEO-optimized structure

This documentation serves as a comprehensive guide for developers, administrators, and stakeholders to understand the complete system architecture and functionality of the IAP2.2Dev e-commerce platform.
```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                       │
├─────────────────────────────────────────────────────────────┤
│  Frontend: Bootstrap 5.3.0 + Font Awesome 6.4.0 + JS     │
│  Layout System: Centralized Layout Class                   │
│  Responsive Design: Mobile-First Approach                  │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                        │
├─────────────────────────────────────────────────────────────┤
│  Core Pages: Index, Products, Cart, Checkout, Dashboard    │
│  User Management: Profile, Addresses, Orders, Auth         │
│  Admin Panel: User Management, Product Management          │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                    BUSINESS LOGIC LAYER                     │
├─────────────────────���───────────────────────────────────────┤
│  Manager Classes: ProductManager, OrderManager,            │
│                  CartManager, UserManager                  │
│  Authentication: 2FA, Email Verification, Sessions         │
│  Email System: PHPMailer Integration                       │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                    DATA ACCESS LAYER                        │
├─────────────────────────────────────────────────────────────┤
│  Database: MySQL/MariaDB with PDO                          │
│  Connection: Centralized Configuration                     │
│  Security: Prepared Statements, Input Sanitization         │
└─────────────────────────────────────────────────────────────┘
```

---

## System Architecture

### Authentication Flow
1. **User Registration** (`Signup.php`)
   - User registers with username, email, and password
   - System generates TOTP secret for 2FA
   - Email verification token sent
   - User must verify email before login

2. **Email Verification** (`verify.php`)
   - Users click verification link from email
   - Token validated and account activated
   - User can now sign in

3. **Sign In Process** (`Signin.php`)
   - User enters email and password
   - System validates credentials
   - Redirects to 2FA verification if valid

4. **Two-Factor Authentication** (`2fa_verify.php`)
   - User enters 6-digit code from authenticator app
   - System validates TOTP code
   - On success, user is fully authenticated
   - Redirects to intended page or dashboard

### Database Structure

#### Core Tables
- **users**: User accounts with 2FA secrets
- **addresses**: User shipping/billing addresses
- **categories**: Product categories
- **brands**: Product brands
- **products**: Product catalog with inventory
- **product_images**: Multiple images per product
- **product_attributes**: Product specifications
- **cart**: Shopping cart items
- **orders**: Order headers
- **order_items**: Order line items
- **order_status_history**: Order tracking
- **reviews**: Product reviews and ratings
- **wishlist**: User wishlists
- **coupons**: Discount codes

### Application Structure

```
/var/www/html/IAP2.2Dev/
├── config.php                 # Database and site configuration
├── ClassAutoload.php          # Autoloader for classes
├── Index.php                  # Homepage with featured products
├── Signin.php                 # Login page
├── Signup.php                 # Registration page
├── 2fa_verify.php            # Two-factor authentication
├── verify.php                # Email verification
├── dashboard.php             # User dashboard
├── products.php              # Product catalog
├── product.php               # Product details
├── cart.php                  # Shopping cart
├── cart_ajax.php             # Cart API endpoints
├── checkout.php              # Checkout process
├── order_confirmation.php    # Order confirmation
├── orders.php                # Order history
├── logout.php                # Logout handler
│
├── Abstract/
│   ├── Layout.php            # Page layout and navigation
│   └── forms.php             # Authentication forms
│
├── classes/
│   ├── ProductManager.php    # Product business logic
│   ├── CartManager.php       # Cart operations
│   └── OrderManager.php      # Order processing
│
├── database/
│   └── complete_ecommerce_schema.sql  # Database schema
│
├── Mail/
│   └── SendMail.php          # Email functionality
│
└── vendor/                   # Composer dependencies
    └── robthree/twofactorauth/  # 2FA library
```

---

## Key Features

### 🔐 Security Features
- **Password Hashing**: All passwords hashed with PHP's password_hash()
- **2FA Authentication**: TOTP-based using Google Authenticator
- **Email Verification**: Required before account activation
- **SQL Injection Protection**: PDO prepared statements
- **Session Management**: Secure session handling
- **CSRF Protection**: Form validation and tokens

### 🛒 E-Commerce Features
- **Product Catalog**: Categories, brands, search, filtering
- **Shopping Cart**: Guest and user carts, stock validation
- **Checkout Process**: Address management, payment methods
- **Order Management**: Status tracking, history
- **Inventory Management**: Stock tracking and validation
- **Tax Calculation**: 16% VAT for Kenya
- **Free Shipping**: Orders over KSh 5,000

### 📱 User Experience
- **Responsive Design**: Mobile-first Bootstrap 5
- **AJAX Operations**: Real-time cart updates
- **Progress Indicators**: Order status tracking
- **Search & Filters**: Advanced product discovery
- **User Dashboard**: Order history, account management

---

## Configuration

### Database Setup
1. Import `/database/complete_ecommerce_schema.sql`
2. Update `config.php` with your database credentials:
```php
$conf = [
    'db_host' => 'localhost',
    'db_name' => 'your_database',
    'db_user' => 'your_username',
    'db_pass' => 'your_password',
    'site_name' => 'Your Store Name',
    'site_url' => 'http://your-domain.com'
];
```

### Email Configuration
1. Configure SMTP settings in `Mail/SendMail.php`
2. Update `FallbackEmail.php` for development

### 2FA Setup
1. Composer dependencies already included
2. Users scan QR code with authenticator app
3. TOTP secrets stored securely in database

---

## Usage Guide

### For Customers

1. **Registration**
   - Sign up with email and password
   - Check email for verification link
   - Click link to activate account

2. **Shopping**
   - Browse products by category or search
   - Add items to cart (requires login)
   - Proceed to checkout

3. **Checkout**
   - Add shipping address
   - Select payment method (M-Pesa/COD)
   - Review and place order

4. **Order Tracking**
   - View order history in dashboard
   - Track delivery status
   - View order details

### For Administrators

1. **User Management**
   - Access via `/admin/users.php` (admin role required)
   - View and manage user accounts

2. **Order Management**
   - Update order statuses
   - Process payments
   - Manage shipping

---

## Payment Integration

### Current Methods
- **M-Pesa**: Mobile money payment (integration ready)
- **Cash on Delivery**: Pay on delivery

### Implementation Notes
- M-Pesa API integration requires Safaricom credentials
- Payment status tracked in orders table
- Webhook endpoints can be added for real-time updates

---

## API Endpoints

### Cart Operations (`cart_ajax.php`)
- `POST action=add` - Add item to cart
- `POST action=update` - Update item quantity
- `POST action=remove` - Remove item from cart
- `POST action=clear` - Clear entire cart
- `POST action=get_totals` - Get cart totals
- `POST action=get_items` - Get cart items

### Response Format
```json
{
    "success": true,
    "message": "Product added to cart",
    "cart_count": 3,
    "cart_total": "15000.00"
}
```

---

## Customization

### Adding New Product Attributes
1. Add columns to `product_attributes` table
2. Update `ProductManager::getProductDetails()`
3. Modify product display templates

### Custom Payment Methods
1. Add payment option to checkout form
2. Update `OrderManager::createOrder()`
3. Implement payment gateway integration

### Theming
1. Modify CSS in page templates
2. Update Bootstrap variables
3. Customize `Abstract/Layout.php`

---

## Security Best Practices

### Implemented
- Password hashing with salt
- 2FA authentication required
- Email verification mandatory
- SQL injection prevention
- Session security
- Input validation and sanitization

### Additional Recommendations
- Implement rate limiting for login attempts
- Add CSRF tokens to all forms
- Enable HTTPS in production
- Regular security audits
- Keep dependencies updated

---

## Common Errors and Troubleshooting Guide

### Database-Related Errors

#### 1. **Error: Connection refused / Database connection failed**
**Symptoms**: 
- Pages showing "Database connection error"
- Fatal error: Uncaught PDOException

**Common Causes**:
```bash
# Check if MySQL/MariaDB is running
sudo systemctl status mysql
# or
sudo systemctl status mariadb

# Start the service if stopped
sudo systemctl start mysql
```

**Solutions**:
1. Verify database service is running
2. Check `config.php` credentials:
   ```php
   $conf = [
       'db_host' => 'localhost',     // Ensure correct host
       'db_name' => 'auth_db',       // Database exists
       'db_user' => 'root',          // Valid username
       'db_pass' => 'devyan2005'     // Correct password
   ];
   ```
3. Test connection manually:
   ```bash
   mysql -u root -pdevyan2005 auth_db
   ```

#### 2. **Error: Table doesn't exist**
**Symptoms**: 
- "Table 'auth_db.users' doesn't exist"
- "Table 'auth_db.products' doesn't exist"

**Solutions**:
1. Import the complete database schema:
   ```bash
   mysql -u root -pdevyan2005 auth_db < database/complete_ecommerce_schema.sql
   ```
2. Verify all tables exist:
   ```sql
   SHOW TABLES;
   ```
3. Check table structure:
   ```sql
   DESCRIBE users;
   DESCRIBE products;
   ```

#### 3. **Error: Column doesn't exist**
**Symptoms**: 
- "Unknown column 'first_name' in 'addresses'"
- "Column count doesn't match value count"

**Solutions**:
1. Update address table structure:
   ```sql
   ALTER TABLE addresses 
   ADD COLUMN first_name VARCHAR(100) NOT NULL AFTER user_id,
   ADD COLUMN last_name VARCHAR(100) NOT NULL AFTER first_name,
   ADD COLUMN company VARCHAR(255) NULL AFTER last_name,
   ADD COLUMN phone VARCHAR(20) NOT NULL AFTER country;
   ```

### Authentication Errors

#### 4. **Error: 2FA verification fails**
**Symptoms**: 
- "Invalid verification code"
- "TOTP code expired"

**Common Causes**:
- Device time synchronization issues
- Wrong TOTP secret
- Code already used

**Solutions**:
1. Synchronize device time:
   ```bash
   # On Linux server
   sudo ntpdate -s time.nist.gov
   ```
2. Check TOTP secret in database:
   ```sql
   SELECT username, totp_secret FROM users WHERE id = [user_id];
   ```
3. Reset 2FA for user:
   ```sql
   UPDATE users SET totp_secret = NULL WHERE email = 'user@example.com';
   ```

#### 5. **Error: Email verification not working**
**Symptoms**: 
- Verification emails not sent
- "Invalid verification token"

**Solutions**:
1. Check email configuration in `Mail/SendMail.php`:
   ```php
   // Verify SMTP settings
   $mail->Host = 'smtp.gmail.com';
   $mail->Port = 587;
   $mail->Username = 'your_email@gmail.com';
   $mail->Password = 'your_app_password';  // Not regular password
   ```
2. Test with fallback email for development:
   ```php
   // In FallbackEmail.php
   error_log("Verification link: " . $verificationLink);
   ```

### Session and Authentication Issues

#### 6. **Error: Session expired / User logged out unexpectedly**
**Symptoms**: 
- Users redirected to login frequently
- "Please log in to continue"

**Solutions**:
1. Check session configuration:
   ```php
   // Increase session lifetime in php.ini or code
   ini_set('session.gc_maxlifetime', 3600); // 1 hour
   session_set_cookie_params(3600);
   ```
2. Verify session storage:
   ```bash
   # Check session directory permissions
   ls -la /var/lib/php/sessions/
   ```

#### 7. **Error: CSRF token mismatch**
**Symptoms**: 
- Form submissions fail
- "Invalid request token"

**Solutions**:
1. Generate CSRF tokens in forms:
   ```php
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   ```
2. Validate tokens on submission:
   ```php
   if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
       die('CSRF token mismatch');
   }
   ```

### E-Commerce Functionality Errors

#### 8. **Error: Cart not updating via AJAX**
**Symptoms**: 
- Cart quantity doesn't change
- "Failed to add to cart" messages

**Solutions**:
1. Check JavaScript console for errors
2. Verify AJAX endpoint (`cart_ajax.php`):
   ```php
   // Add debugging to cart_ajax.php
   error_log("Cart action: " . $_POST['action']);
   error_log("User ID: " . $_SESSION['user_id']);
   ```
3. Test AJAX manually:
   ```javascript
   // Browser console test
   fetch('cart_ajax.php', {
       method: 'POST',
       body: new FormData(document.getElementById('add-to-cart-form'))
   }).then(response => response.json()).then(console.log);
   ```

#### 9. **Error: Product images not displaying**
**Symptoms**: 
- Broken image icons
- Missing product photos

**Solutions**:
1. Check image paths in database:
   ```sql
   SELECT name, image_url FROM products WHERE id = 1;
   ```
2. Verify image files exist:
   ```bash
   ls -la images/products/
   ```
3. Update image URLs if needed:
   ```sql
   UPDATE products SET image_url = 'images/products/sample.jpg' WHERE image_url IS NULL;
   ```

### Order Processing Errors

#### 10. **Error: Order creation fails**
**Symptoms**: 
- "Failed to place order"
- Orders not saved to database

**Solutions**:
1. Check order table structure:
   ```sql
   DESCRIBE orders;
   ```
2. Verify required fields:
   ```php
   // Debug order creation in OrderManager
   error_log("Creating order for user: " . $userId);
   error_log("Cart items: " . print_r($cartItems, true));
   ```
3. Check database transaction handling:
   ```php
   try {
       $pdo->beginTransaction();
       // Order creation code
       $pdo->commit();
   } catch (Exception $e) {
       $pdo->rollback();
       error_log("Order creation failed: " . $e->getMessage());
   }
   ```

### File and Permission Issues

#### 11. **Error: Permission denied**
**Symptoms**: 
- "Unable to write to file"
- "Permission denied" errors

**Solutions**:
1. Set correct file permissions:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/IAP2.2Dev
   sudo chmod -R 755 /var/www/html/IAP2.2Dev
   sudo chmod -R 777 /var/www/html/IAP2.2Dev/uploads  # If upload directory exists
   ```

#### 12. **Error: Class not found**
**Symptoms**: 
- "Fatal error: Class 'ProductManager' not found"
- "Class 'CartManager' not found"

**Solutions**:
1. Verify autoloader is included:
   ```php
   require_once 'ClassAutoload.php';
   ```
2. Check class file locations:
   ```bash
   ls -la classes/
   ls -la Abstract/
   ```
3. Verify class names match file names exactly

### Development Server Issues

#### 13. **Error: PHP development server not accessible**
**Symptoms**: 
- "This site can't be reached"
- Connection refused on localhost:8000

**Solutions**:
1. Check if server is running:
   ```bash
   ps aux | grep "php -S" | grep -v grep
   ```
2. Start development server:
   ```bash
   cd /var/www/html/IAP2.2Dev
   php -S localhost:8000
   ```
3. Kill existing server if needed:
   ```bash
   pkill -f "php -S localhost:8000"
   ```

### Email Configuration Issues

#### 14. **Error: SMTP authentication failed**
**Symptoms**: 
- "SMTP Error: Could not authenticate"
- Email sending fails

**Solutions**:
1. Use App Password (not regular password) for Gmail:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate app-specific password
2. Update email configuration:
   ```php
   $mail->Username = 'your_email@gmail.com';
   $mail->Password = 'generated_app_password';  // 16-character app password
   ```

### Performance and Memory Issues

#### 15. **Error: Maximum execution time exceeded**
**Symptoms**: 
- Page takes too long to load
- "Fatal error: Maximum execution time of 30 seconds exceeded"

**Solutions**:
1. Optimize database queries:
   ```sql
   -- Add indexes for frequently queried columns
   CREATE INDEX idx_products_category ON products(category_id);
   CREATE INDEX idx_orders_user ON orders(user_id);
   ```
2. Increase execution time for specific operations:
   ```php
   set_time_limit(60); // For heavy operations
   ```

### Debugging Best Practices

#### 16. **Enable Error Reporting for Development**
```php
// Add to config.php for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');
```

#### 17. **Database Query Debugging**
```php
// Add to manager classes for debugging
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    error_log("Query executed successfully: " . $sql);
} catch (PDOException $e) {
    error_log("Query failed: " . $sql);
    error_log("Error: " . $e->getMessage());
    error_log("Parameters: " . print_r($params, true));
}
```

#### 18. **AJAX Request Debugging**
```javascript
// Add to frontend JavaScript
fetch(url, options)
.then(response => {
    if (!response.ok) {
        console.error('HTTP Error:', response.status, response.statusText);
    }
    return response.json();
})
.then(data => {
    console.log('AJAX Response:', data);
})
.catch(error => {
    console.error('AJAX Error:', error);
});
```

---

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check `config.php` credentials
   - Ensure MySQL service running
   - Verify database exists

2. **Email Not Sending**
   - Check SMTP configuration
   - Verify fallback email settings
   - Check spam folders

3. **2FA Not Working**
   - Ensure device time synchronized
   - Check TOTP secret generation
   - Verify authenticator app setup

4. **Cart Not Updating**
   - Check JavaScript console for errors
   - Verify AJAX endpoints
   - Ensure session management working

### Debug Mode
Set `error_reporting(E_ALL)` in `config.php` for development debugging.

---

## Performance Optimization

### Database
- Indexes on frequently queried columns
- Query optimization in manager classes
- Connection pooling for high traffic

### Frontend
- Minify CSS/JS in production
- Image optimization
- CDN for static assets
- Caching for product data

### Server
- PHP OPcache enabled
- Proper session handling
- GZIP compression
- Security headers

---

## Future Enhancements

### Planned Features
- [ ] Product reviews and ratings display
- [ ] Wishlist functionality
- [ ] Coupon system
- [ ] Advanced admin panel
- [ ] Mobile app API
- [ ] Social media integration
- [ ] Multi-vendor support
- [ ] Advanced analytics

### Technical Improvements
- [ ] Move to MVC framework
- [ ] API rate limiting
- [ ] Automated testing
- [ ] CI/CD pipeline
- [ ] Container deployment
- [ ] Microservices architecture

---

## Support

For technical support or feature requests:
- Review code comments for implementation details
- Check error logs for debugging information
- Refer to database schema for data relationships
- Test with sample data provided in schema

---

## License

This e-commerce platform is developed for educational purposes as part of the IAP2.2 project.

**© 2025 IAP2.2Dev E-Commerce Platform**
