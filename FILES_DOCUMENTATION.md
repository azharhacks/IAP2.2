# 📁 Files Documentation - SMARTDUKA

This document provides detailed information about each file in the SMARTDUKA e-commerce platform, including their purpose, functionality, and key features.

## 🏗️ Core System Files

### `config.php`
**Purpose**: Database configuration and global settings
- **Database Connection**: PDO connection with error handling
- **Security Settings**: SQL injection prevention
- **Environment Configuration**: Development/production settings
- **Global Constants**: Site-wide configuration values

### `ClassAutoload.php`
**Purpose**: Automatic class loading system
- **PSR-4 Autoloading**: Automatically loads PHP classes
- **Layout Class**: Manages UI components and page structure
- **Database Classes**: Handles database operations
- **Utility Classes**: Common functionality across the application

### `Layout.php`
**Purpose**: UI layout and component management
- **Header Generation**: HTML head, CSS, and meta tags
- **Navigation Bar**: Dynamic navigation with user context
- **Breadcrumbs**: Page navigation trail
- **Footer**: Site footer with links and information
- **Responsive Design**: Mobile-first Bootstrap implementation

---

## 🔐 Authentication System

### `Signin.php`
**Purpose**: User login and authentication
- **Login Form**: Username/password authentication
- **Session Management**: Secure session handling
- **2FA Integration**: Two-factor authentication flow
- **Remember Me**: Optional persistent login
- **Security**: Protection against brute force attacks

### `register.php`
**Purpose**: New user registration
- **Registration Form**: User account creation
- **Email Verification**: Optional email confirmation
- **Password Security**: Strong password requirements
- **Data Validation**: Input sanitization and validation
- **User Profile**: Basic profile information collection

### `two_factor_auth.php`
**Purpose**: Two-factor authentication verification
- **SMS Verification**: Send and verify SMS codes
- **Email Verification**: Send and verify email codes
- **QR Code Generation**: For authenticator apps
- **Backup Codes**: Emergency access codes
- **Security Enhancement**: Additional layer of protection

---

## 🛍️ E-Commerce Core

### `products.php`
**Purpose**: Product catalog and browsing
- **Product Listing**: Display all available products
- **Search Functionality**: Find products by name/category
- **Filtering**: Filter by price, category, availability
- **Product Details**: Detailed product information
- **Add to Cart**: Shopping cart integration
- **Responsive Grid**: Mobile-friendly product display

### `cart.php`
**Purpose**: Shopping cart management
- **Cart Display**: Show selected items
- **Quantity Management**: Update item quantities
- **Price Calculation**: Real-time total calculation
- **Remove Items**: Delete items from cart
- **Cart Persistence**: Save cart across sessions
- **Checkout Integration**: Proceed to checkout

### `checkout.php`
**Purpose**: Order placement and payment processing
- **Order Summary**: Review items before purchase
- **Shipping Information**: Delivery address collection
- **Payment Selection**: Choose payment method
- **M-Pesa Integration**: Mobile money payment
- **Order Creation**: Generate order records
- **Confirmation**: Redirect to order confirmation

### `orders.php`
**Purpose**: Order history and tracking
- **Order List**: Display user's order history
- **Order Details**: Detailed order information
- **Status Tracking**: Track order progress
- **Reorder**: Quickly reorder previous purchases
- **Download Receipts**: PDF order confirmations
- **Filter/Search**: Find specific orders

---

## 📋 Order Management

### `order_confirmation.php`
**Purpose**: Order confirmation and receipt display
**Key Features**:
- **Order Details**: Complete order information
- **Payment Status**: Real-time payment verification
- **M-Pesa Integration**: Show M-Pesa receipt numbers
- **Shipping Information**: Delivery details with fallbacks
- **PDF Download**: Generate printable receipts
- **Timeline**: Order status progression
- **Professional UI**: Orange gradient theme with modern design

**Database Safety Features**:
- **Multiple Fallback Queries**: Works with any database structure
- **Missing Table Handling**: Graceful error handling
- **Column Flexibility**: Handles missing fields safely
- **Default Values**: Strathmore University fallback addresses

**Technical Implementation**:
```php
// Safe order retrieval with fallbacks
$queries = [
    "SELECT oi.*, p.name, p.image_url, p.sku FROM order_items oi JOIN products p...",
    "SELECT oi.*, p.name, '' as image_url, '' as sku FROM order_items oi JOIN...",
    "SELECT oi.*, oi.product_name, '' as image_url FROM order_items oi WHERE...",
    "SELECT *, 'Product' as product_name FROM order_items WHERE order_id = ?"
];
```

### `order_confirmation_pdf.php`
**Purpose**: PDF receipt generation and printing
**Key Features**:
- **Professional Layout**: SMARTDUKA branded design
- **Complete Order Info**: All order and customer details
- **Responsive Design**: Works on desktop and mobile
- **Print Optimization**: Clean PDF generation
- **Auto-Print Dialog**: Automatic browser print trigger
- **Grid Layout**: Organized information display

**Design Elements**:
- **Company Header**: SMARTDUKA branding with university address
- **Status Badges**: Color-coded payment status indicators
- **Order Items Table**: Professional table with totals
- **Information Grids**: Two-column layout for details
- **Print Styles**: Optimized for PDF generation

**Usage Flow**:
1. User clicks "Download PDF" on order confirmation
2. Opens in new tab with formatted layout
3. Browser automatically shows print dialog
4. User selects "Save as PDF" and downloads

### `mpesa_payment_page.php`
**Purpose**: M-Pesa mobile money payment processing
- **Payment Interface**: M-Pesa payment form
- **Transaction Tracking**: Real-time payment status
- **Receipt Generation**: M-Pesa receipt handling
- **Error Handling**: Payment failure management
- **Status Updates**: Update order payment status
- **Security**: Secure payment processing

---

## 👨‍💼 Admin Panel

### `admin_dashboard.php`
**Purpose**: Administrator overview and controls
- **Sales Analytics**: Revenue and order statistics
- **Quick Actions**: Common administrative tasks
- **System Status**: Server and database health
- **User Management**: User account oversight
- **Notification Center**: Important system alerts
- **Performance Metrics**: Site performance data

### `admin_products.php`
**Purpose**: Product inventory management
- **Product CRUD**: Create, read, update, delete products
- **Inventory Tracking**: Stock level management
- **Category Management**: Organize product categories
- **Image Upload**: Product photo management
- **Bulk Operations**: Mass product updates
- **Search/Filter**: Find specific products

### `admin_orders.php`
**Purpose**: Order management and fulfillment
- **Order Processing**: Manage order lifecycle
- **Status Updates**: Change order status
- **Customer Communication**: Send order updates
- **Fulfillment**: Mark orders as shipped/delivered
- **Reports**: Order analytics and reports
- **Refund Processing**: Handle returns and refunds

---

## 🛠️ Utility Scripts

### `deploy_fixed_order_confirmation.sh`
**Purpose**: Deployment script for order confirmation fixes
- **Syntax Checking**: Validate PHP syntax before deployment
- **File Deployment**: Copy files to web directory
- **Permission Setting**: Set proper file permissions
- **Testing URLs**: Provide test links
- **Status Reporting**: Show deployment results

### `deploy_pdf_fix.sh`
**Purpose**: PDF functionality deployment
- **PDF System**: Deploy PDF generation system
- **Syntax Validation**: Check PHP files
- **Permission Management**: Set web server permissions
- **Feature Testing**: Provide test cases
- **Documentation**: Usage instructions

### `fix_http_500.sh`
**Purpose**: HTTP 500 error troubleshooting
- **Error Detection**: Identify PHP syntax errors
- **Log Analysis**: Check Apache error logs
- **Service Management**: Restart web services
- **Diagnostic Tools**: System health checks
- **Resolution Steps**: Fix common issues

---

## 🎨 Styling and Design

### CSS Architecture
- **Orange Gradient Theme**: `#ff6b35` to `#f7931e`
- **Responsive Design**: Mobile-first approach
- **Bootstrap 5**: Modern component framework
- **Custom Components**: Specialized UI elements
- **Animation Effects**: Smooth transitions and hover effects

### Design Patterns
- **Card-Based Layout**: Clean, organized content blocks
- **Status Indicators**: Color-coded badges and alerts
- **Timeline Components**: Order status progression
- **Grid Systems**: Responsive information display
- **Professional Typography**: Clear, readable fonts

---

## 🔒 Security Features

### Input Validation
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization
- **CSRF Protection**: Token-based form security
- **Data Validation**: Server-side input validation

### Authentication Security
- **Password Hashing**: Secure password storage
- **Session Security**: Secure session management
- **2FA Implementation**: Two-factor authentication
- **Brute Force Protection**: Login attempt limiting

### Database Security
- **Prepared Statements**: Prevent SQL injection
- **Error Handling**: Safe error management
- **Fallback Queries**: Graceful degradation
- **Data Sanitization**: Clean user input

---

## 📱 Mobile Optimization

### Responsive Features
- **Mobile-First Design**: Optimized for small screens
- **Touch-Friendly**: Large buttons and touch targets
- **Fast Loading**: Optimized images and code
- **Progressive Enhancement**: Works without JavaScript

### Mobile Payment
- **M-Pesa Integration**: Native mobile money support
- **Simple Interface**: Easy payment process
- **Status Updates**: Real-time payment feedback
- **Error Handling**: Clear error messages

---

## 🔧 Maintenance and Updates

### Code Quality
- **Documentation**: Comprehensive code comments
- **Error Handling**: Robust error management
- **Logging**: System activity logging
- **Testing**: Built-in test mechanisms

### Deployment
- **Automated Scripts**: Easy deployment process
- **Version Control**: Git-based development
- **Backup Systems**: Data protection
- **Rollback Procedures**: Safe update process

---

**This documentation is maintained alongside the codebase to ensure accuracy and completeness.**