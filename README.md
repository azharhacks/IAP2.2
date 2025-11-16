# SMARTDUKA - E-Commerce Platform

## 🏪 Project Overview

SMARTDUKA is a modern e-commerce platform built for Strathmore University. It provides a complete online shopping experience with features like product catalog, shopping cart, order management, M-Pesa payment integration, and user authentication with 2FA security.

## 🎯 Key Features

### 🛍️ **E-Commerce Core**
- **Product Catalog** - Browse and search products
- **Shopping Cart** - Add, remove, and manage items
- **Order Management** - Track orders from placement to delivery
- **User Accounts** - Registration, login, and profile management

### 💳 **Payment & Security**
- **M-Pesa Integration** - Mobile money payment system
- **Two-Factor Authentication (2FA)** - Enhanced security
- **Order Confirmation** - PDF receipts and email notifications
- **Payment Tracking** - Real-time payment status updates

### 📊 **Advanced Features**
- **Admin Dashboard** - Manage products, orders, and users
- **Responsive Design** - Works on desktop and mobile
- **Database Safety** - Fallback queries for different database structures
- **Professional UI** - Modern Bootstrap-based interface

## 🏗️ Project Structure

```
IAP2.2-1/
├── 📁 Core Files
│   ├── config.php                 # Database configuration
│   ├── ClassAutoload.php          # Automatic class loading
│   └── Layout.php                 # UI layout and components
│
├── 📁 Authentication
│   ├── Signin.php                 # User login
│   ├── register.php               # User registration
│   └── two_factor_auth.php        # 2FA verification
│
├── 📁 E-Commerce
│   ├── products.php               # Product catalog
│   ├── cart.php                   # Shopping cart
│   ├── checkout.php               # Checkout process
│   └── orders.php                 # Order history
│
├── 📁 Order Management
│   ├── order_confirmation.php     # Order confirmation page
│   ├── order_confirmation_pdf.php # PDF receipt generator
│   └── mpesa_payment_page.php     # M-Pesa payment interface
│
├── 📁 Admin Panel
│   ├── admin_dashboard.php        # Admin overview
│   ├── admin_products.php         # Product management
│   └── admin_orders.php           # Order management
│
└── 📁 Documentation
    ├── README.md                  # This file
    ├── FILES_DOCUMENTATION.md     # Detailed file descriptions
    └── SETUP_GUIDE.md            # Installation and setup
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+
- MySQL/MariaDB
- Apache Web Server
- Composer (for dependencies)

### Installation
1. **Clone the project**
   ```bash
   git clone <repository-url>
   cd IAP2.2-1
   ```

2. **Set up database**
   - Import the SQL schema
   - Update `config.php` with your database credentials

3. **Configure web server**
   ```bash
   sudo cp -r * /var/www/html/IAP2.2Dev/
   sudo chown -R www-data:www-data /var/www/html/IAP2.2Dev/
   ```

4. **Access the application**
   ```
   http://localhost/IAP2.2Dev/
   ```

## 💡 Key Technologies

- **Backend**: PHP 8, MySQL
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Payment**: M-Pesa API Integration
- **Security**: 2FA, Session Management, SQL Injection Protection
- **UI/UX**: Responsive Design, Modern Gradients, Professional Layout

## 🎨 Design Philosophy

SMARTDUKA uses a modern orange gradient theme (#ff6b35 to #f7931e) that represents energy, enthusiasm, and trust. The design is:

- **Professional** - Clean, organized layout
- **Responsive** - Works on all devices
- **Accessible** - Easy to navigate and use
- **Secure** - Multiple layers of security

## 📱 Mobile-First Approach

The platform is designed mobile-first with:
- Responsive Bootstrap grid system
- Touch-friendly interfaces
- Optimized M-Pesa payment flow
- Fast loading times

## 🔐 Security Features

- **SQL Injection Protection** - Prepared statements
- **XSS Prevention** - Input sanitization
- **2FA Authentication** - SMS/Email verification
- **Session Security** - Secure session management
- **Database Fallbacks** - Safe error handling

## 🎯 Target Users

- **Customers** - Strathmore University students and staff
- **Administrators** - Store managers and staff
- **Developers** - For customization and maintenance

## 📈 Performance Optimizations

- **Database Optimization** - Multiple fallback queries
- **Caching** - CSS/JS minification
- **Image Optimization** - Responsive images
- **Error Handling** - Graceful degradation

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For technical support or questions:
- Email: support@smartduka.ke
- Documentation: See `FILES_DOCUMENTATION.md`
- Setup Help: See `SETUP_GUIDE.md`

## 📝 License

This project is proprietary software developed for Strathmore University.

---

**Made with ❤️ for Strathmore University**