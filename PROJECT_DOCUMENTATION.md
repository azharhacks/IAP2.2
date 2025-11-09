# E-Commerce Platform Documentation
## IAP2.2Dev - Amazon-Style Marketplace

### Overview
This is a comprehensive e-commerce platform built with PHP, MySQL, and Bootstrap 5. The system integrates secure authentication with 2FA, complete shopping cart functionality, order management, and user tracking.

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
- ✅ Password hashing with salt
- ✅ 2FA authentication required
- ✅ Email verification mandatory
- ✅ SQL injection prevention
- ✅ Session security
- ✅ Input validation and sanitization

### Additional Recommendations
- Implement rate limiting for login attempts
- Add CSRF tokens to all forms
- Enable HTTPS in production
- Regular security audits
- Keep dependencies updated

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
