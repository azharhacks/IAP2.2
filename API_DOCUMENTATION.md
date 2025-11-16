# 🔌 API Documentation - SMARTDUKA

This document describes the APIs and integrations used in the SMARTDUKA e-commerce platform.

## 📱 M-Pesa Payment API

### Overview
SMARTDUKA integrates with Safaricom's M-Pesa API for mobile money payments in Kenya. This allows customers to pay directly from their mobile phones.

### Configuration
```php
// config.php - M-Pesa Settings
define('MPESA_CONSUMER_KEY', 'your_consumer_key');
define('MPESA_CONSUMER_SECRET', 'your_consumer_secret');
define('MPESA_SHORTCODE', 'your_business_shortcode');
define('MPESA_PASSKEY', 'your_lipa_na_mpesa_passkey');
define('MPESA_CALLBACK_URL', 'https://yourdomain.com/mpesa_callback.php');
define('MPESA_SANDBOX_URL', 'https://sandbox.safaricom.co.ke');
define('MPESA_LIVE_URL', 'https://api.safaricom.co.ke');
```

### Authentication Endpoint

#### Get Access Token
```http
POST /oauth/v1/generate?grant_type=client_credentials
Host: sandbox.safaricom.co.ke
Authorization: Basic base64(consumer_key:consumer_secret)
```

**Response:**
```json
{
    "access_token": "token_string",
    "expires_in": "3599"
}
```

### STK Push (Lipa na M-Pesa Online)

#### Initiate Payment
```http
POST /mpesa/stkpush/v1/processrequest
Host: sandbox.safaricom.co.ke
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "BusinessShortCode": "174379",
    "Password": "base64_encoded_password",
    "Timestamp": "20231116154530",
    "TransactionType": "CustomerPayBillOnline",
    "Amount": "511.60",
    "PartyA": "254708374149",
    "PartyB": "174379",
    "PhoneNumber": "254708374149",
    "CallBackURL": "https://yourdomain.com/mpesa_callback.php",
    "AccountReference": "ORD-20251116165528-3525",
    "TransactionDesc": "SMARTDUKA Order Payment"
}
```

**Response:**
```json
{
    "MerchantRequestID": "29115-34620561-1",
    "CheckoutRequestID": "ws_CO_191220231607000001",
    "ResponseCode": "0",
    "ResponseDescription": "Success. Request accepted for processing",
    "CustomerMessage": "Success. Request accepted for processing"
}
```

### Callback Handling

#### M-Pesa Callback Structure
```php
// mpesa_callback.php
<?php
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$stkCallback = $data['Body']['stkCallback'];
$merchantRequestID = $stkCallback['MerchantRequestID'];
$checkoutRequestID = $stkCallback['CheckoutRequestID'];
$resultCode = $stkCallback['ResultCode'];
$resultDesc = $stkCallback['ResultDesc'];

if ($resultCode == 0) {
    // Payment successful
    $callbackMetadata = $stkCallback['CallbackMetadata']['Item'];
    
    foreach ($callbackMetadata as $item) {
        switch ($item['Name']) {
            case 'Amount':
                $amount = $item['Value'];
                break;
            case 'MpesaReceiptNumber':
                $mpesaReceiptNumber = $item['Value'];
                break;
            case 'TransactionDate':
                $transactionDate = $item['Value'];
                break;
            case 'PhoneNumber':
                $phoneNumber = $item['Value'];
                break;
        }
    }
    
    // Update order status in database
    updateOrderPaymentStatus($merchantRequestID, 'paid', $mpesaReceiptNumber);
} else {
    // Payment failed
    updateOrderPaymentStatus($merchantRequestID, 'failed', null);
}
?>
```

---

## 🔒 Authentication API

### Session Management

#### Start Session
```php
// Signin.php
session_start();
$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $username;
$_SESSION['login_time'] = time();
```

#### Check Authentication
```php
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['2fa_verified']);
}

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: Signin.php');
        exit;
    }
}
```

### Two-Factor Authentication

#### Generate 2FA Code
```php
function generate2FACode() {
    return sprintf("%06d", mt_rand(100000, 999999));
}

function send2FACode($phone, $code) {
    // SMS API integration
    $message = "Your SMARTDUKA verification code is: $code";
    return sendSMS($phone, $message);
}
```

#### Verify 2FA Code
```php
function verify2FACode($userCode, $storedCode, $timestamp) {
    $timeExpired = (time() - $timestamp) > 300; // 5 minutes
    return !$timeExpired && $userCode === $storedCode;
}
```

---

## 📊 Database API

### Order Management

#### Create Order
```php
function createOrder($userId, $cartItems, $shippingInfo, $paymentMethod) {
    global $pdo;
    
    $orderNumber = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
    $total = calculateCartTotal($cartItems);
    
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, 
                          payment_method, shipping_address, shipping_city, 
                          shipping_state, shipping_postal_code, shipping_country) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId, $orderNumber, $total, $paymentMethod,
        $shippingInfo['address'], $shippingInfo['city'],
        $shippingInfo['state'], $shippingInfo['postal_code'],
        $shippingInfo['country'] ?? 'Kenya'
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    // Add order items
    foreach ($cartItems as $item) {
        addOrderItem($orderId, $item);
    }
    
    return ['order_id' => $orderId, 'order_number' => $orderNumber];
}
```

#### Update Order Status
```php
function updateOrderStatus($orderId, $status, $notes = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    
    // Add to status history
    $stmt = $pdo->prepare("
        INSERT INTO order_status_history (order_id, status, notes) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$orderId, $status, $notes]);
}
```

### Product Management

#### Get Products with Filters
```php
function getProducts($filters = []) {
    global $pdo;
    
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];
    
    if (!empty($filters['category'])) {
        $sql .= " AND category = ?";
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['min_price'])) {
        $sql .= " AND price >= ?";
        $params[] = $filters['min_price'];
    }
    
    if (!empty($filters['max_price'])) {
        $sql .= " AND price <= ?";
        $params[] = $filters['max_price'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (name LIKE ? OR description LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}
```

---

## 📧 Email API

### Order Confirmation Email

#### Send Order Confirmation
```php
function sendOrderConfirmation($orderData, $userEmail) {
    $subject = "Order Confirmation - " . $orderData['order_number'];
    
    $message = generateOrderEmailTemplate($orderData);
    
    $headers = [
        'From: noreply@smartduka.ke',
        'Reply-To: support@smartduka.ke',
        'X-Mailer: SMARTDUKA',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    return mail($userEmail, $subject, $message, implode("\r\n", $headers));
}

function generateOrderEmailTemplate($orderData) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            .email-container { max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; }
            .header { background: #ff6b35; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .order-summary { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <h1>SMARTDUKA</h1>
                <h2>Order Confirmation</h2>
            </div>
            <div class="content">
                <p>Thank you for your order!</p>
                <div class="order-summary">
                    <p><strong>Order Number:</strong> <?= $orderData['order_number'] ?></p>
                    <p><strong>Total Amount:</strong> KSh <?= number_format($orderData['total_amount'], 2) ?></p>
                    <p><strong>Payment Method:</strong> <?= ucfirst($orderData['payment_method']) ?></p>
                </div>
                <p>We will send you tracking information once your order ships.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
```

---

## 🔍 Search API

### Product Search

#### Advanced Search
```php
function searchProducts($query, $filters = []) {
    global $pdo;
    
    $sql = "
        SELECT p.*, 
               MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
        FROM products p 
        WHERE MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE)
    ";
    
    $params = [$query, $query];
    
    // Add filters
    if (!empty($filters['category'])) {
        $sql .= " AND p.category = ?";
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['price_range'])) {
        $sql .= " AND p.price BETWEEN ? AND ?";
        $params[] = $filters['price_range']['min'];
        $params[] = $filters['price_range']['max'];
    }
    
    $sql .= " ORDER BY relevance DESC, p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}
```

### Auto-complete Search
```php
function getSearchSuggestions($query, $limit = 10) {
    global $pdo;
    
    $sql = "
        SELECT DISTINCT name 
        FROM products 
        WHERE name LIKE ? 
        ORDER BY name 
        LIMIT ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $query . '%', $limit]);
    
    return array_column($stmt->fetchAll(), 'name');
}
```

---

## 📈 Analytics API

### Sales Analytics

#### Get Sales Data
```php
function getSalesAnalytics($dateRange = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as order_count,
            SUM(total_amount) as total_sales,
            AVG(total_amount) as average_order_value
        FROM orders 
        WHERE payment_status = 'paid'
    ";
    
    $params = [];
    
    if ($dateRange) {
        $sql .= " AND created_at BETWEEN ? AND ?";
        $params[] = $dateRange['start'];
        $params[] = $dateRange['end'];
    }
    
    $sql .= " GROUP BY DATE(created_at) ORDER BY date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}
```

#### Top Products
```php
function getTopProducts($limit = 10) {
    global $pdo;
    
    $sql = "
        SELECT 
            p.name,
            p.price,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.unit_price) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.payment_status = 'paid'
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll();
}
```

---

## 🛡️ Security API

### Input Validation

#### Sanitize Input
```php
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
```

#### Validate Input
```php
function validateInput($input, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $input[$field] ?? null;
        
        if ($rule['required'] && empty($value)) {
            $errors[$field] = "{$field} is required";
            continue;
        }
        
        if (!empty($value)) {
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = "{$field} must be at least {$rule['min_length']} characters";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = "{$field} must be less than {$rule['max_length']} characters";
            }
            
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = "{$field} format is invalid";
            }
        }
    }
    
    return $errors;
}
```

---

## 📱 Mobile API Endpoints

### REST API Structure

#### Get Order Details
```http
GET /api/orders/{order_number}
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "order_number": "ORD-20251116165528-3525",
        "total_amount": 511.60,
        "status": "confirmed",
        "payment_status": "paid",
        "items": [
            {
                "product_name": "Sample Product",
                "quantity": 1,
                "unit_price": 299.99
            }
        ],
        "shipping_address": {
            "address": "Strathmore University",
            "city": "Nairobi",
            "country": "Kenya"
        }
    }
}
```

#### Create Order
```http
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json
```

**Request:**
```json
{
    "items": [
        {
            "product_id": 1,
            "quantity": 2
        }
    ],
    "shipping_address": {
        "address": "123 Main Street",
        "city": "Nairobi",
        "postal_code": "00100"
    },
    "payment_method": "mpesa"
}
```

---

## 🔧 Webhook Configuration

### M-Pesa Webhooks

#### Callback URL Setup
```php
// mpesa_callback.php
<?php
// Verify callback authenticity
$headers = getallheaders();
$expectedAuth = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);

if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Basic ' . $expectedAuth) {
    http_response_code(401);
    exit('Unauthorized');
}

// Process callback
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log callback for debugging
file_put_contents('mpesa_callbacks.log', date('Y-m-d H:i:s') . ': ' . $json . "\n", FILE_APPEND);

// Process payment result
processPaymentCallback($data);

// Respond to M-Pesa
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
?>
```

---

## 📊 Error Handling

### API Error Responses

#### Standard Error Format
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid input data",
        "details": {
            "email": ["Email format is invalid"],
            "phone": ["Phone number is required"]
        }
    }
}
```

#### Error Codes
- `VALIDATION_ERROR` - Input validation failed
- `AUTHENTICATION_ERROR` - Invalid credentials
- `AUTHORIZATION_ERROR` - Insufficient permissions
- `NOT_FOUND` - Resource not found
- `PAYMENT_ERROR` - Payment processing failed
- `SERVER_ERROR` - Internal server error

---

This API documentation covers the main integrations and endpoints used in the SMARTDUKA platform. For implementation details, refer to the individual PHP files and the setup guide.