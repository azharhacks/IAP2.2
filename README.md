# E-Commerce Platform with M-Pesa Integration

A complete PHP-based e-commerce platform with M-Pesa payment integration, user authentication, and administrative features.

## 🚀 Features

### Core Features
- **User Authentication**: Registration, login, 2FA support
- **Product Management**: Complete product catalog with categories
- **Shopping Cart**: Add, remove, modify cart items
- **Order Management**: Complete order processing workflow
- **M-Pesa Integration**: STK Push payments via Safaricom Daraja API
- **Admin Panel**: User management, order tracking, product management

### Payment Features
- **M-Pesa STK Push**: Direct mobile payment integration
- **Transaction Tracking**: Real-time payment status updates
- **Callback Handling**: Automatic payment confirmation
- **Admin Transaction View**: Complete payment history

### Security Features
- **2FA Authentication**: Two-factor authentication support
- **Email Verification**: Account verification system
- **Session Management**: Secure user sessions
- **Input Validation**: Protection against common attacks

## 🛠️ Technology Stack

- **Backend**: PHP 8.4
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Payment API**: Safaricom Daraja M-Pesa API
- **Email**: SMTP with PHPMailer
- **Authentication**: Custom PHP authentication system

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7 or MariaDB 10.3+
- Apache/Nginx web server
- cURL extension
- PDO extension
- OpenSSL extension

## 🔧 Installation

1. **Clone the repository**
   ```bash
   git clone <your-repo-url>
   cd your-project-name
   ```

2. **Database Setup**
   - Import the database schema from `database/complete_ecommerce_schema.sql`
   - Update database credentials in `config.php`

3. **Configuration**
   - Copy `config.sample.php` to `config.php`
   - Update all configuration values:
     - Database credentials
     - Email settings
     - M-Pesa API credentials
     - Site URL and settings

4. **M-Pesa Setup**
   - Get your Consumer Key and Consumer Secret from Safaricom Developer Portal
   - Configure callback URLs (must be publicly accessible)
   - Update M-Pesa configuration in `config.php`

5. **Web Server Setup**
   - Point your web server to the project directory
   - Ensure proper file permissions
   - Enable required PHP extensions

## 🔐 M-Pesa Configuration

To set up M-Pesa payments:

1. Register at [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Create a new app and get your credentials
3. Update `config.php` with your M-Pesa credentials:
   - Consumer Key
   - Consumer Secret
   - Short Code
   - Passkey
   - Callback URL (must be publicly accessible)

## 📱 Usage

### For Customers
1. Register/Login to your account
2. Browse products and add to cart
3. Proceed to checkout
4. Pay via M-Pesa STK Push
5. Receive payment confirmation

### For Administrators
1. Access admin panel at `/admin/`
2. Manage users and orders
3. View M-Pesa transactions
4. Monitor system activity

## 🗂️ Project Structure

```
├── classes/                 # Core PHP classes
│   ├── CartManager.php     # Shopping cart management
│   ├── OrderManager.php    # Order processing
│   ├── ProductManager.php  # Product management
│   └── MpesaPayment.php   # M-Pesa integration
├── admin/                  # Admin panel files
├── database/              # Database schemas and migrations
├── Mail/                  # Email handling
├── vendor/                # Composer dependencies
├── config.sample.php      # Configuration template
└── README.md             # This file
```

## 🔒 Security Notes

- Never commit `config.php` with real credentials
- Use HTTPS in production
- Regularly update dependencies
- Implement proper input validation
- Use prepared statements for database queries

## 🚀 Deployment

1. Set up a production web server
2. Configure SSL certificate
3. Update `config.php` for production settings
4. Set M-Pesa environment to 'production'
5. Ensure callback URLs are publicly accessible

## 📞 Support

For support and questions:
- Email: your-email@example.com
- Documentation: See PROJECT_DOCUMENTATION.md

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

---

**Note**: This project includes M-Pesa integration for the Kenyan market. Ensure you comply with Safaricom's terms of service and local regulations.
