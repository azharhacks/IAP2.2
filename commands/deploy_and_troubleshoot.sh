#!/bin/bash

echo "🔧 Order Confirmation Deployment & Troubleshooting"
echo "=================================================="

# Check if Apache is running
echo "🔍 Checking Apache status..."
if systemctl is-active --quiet apache2; then
    echo "✅ Apache is running"
else
    echo "❌ Apache is not running - starting Apache..."
    sudo systemctl start apache2
    if systemctl is-active --quiet apache2; then
        echo "✅ Apache started successfully"
    else
        echo "❌ Failed to start Apache"
        exit 1
    fi
fi

# Check if the web directory exists
echo ""
echo "🔍 Checking web directory..."
if [ -d "/var/www/html/IAP2.2Dev" ]; then
    echo "✅ Web directory exists"
else
    echo "❌ Creating web directory..."
    sudo mkdir -p /var/www/html/IAP2.2Dev
    sudo chown -R www-data:www-data /var/www/html/IAP2.2Dev
    echo "✅ Web directory created"
fi

# Copy the order confirmation file
echo ""
echo "📁 Deploying order confirmation file..."
sudo cp /home/devyanjethwaa/IAP2.2-1/order_confirmation.php /var/www/html/IAP2.2Dev/
sudo chmod 644 /var/www/html/IAP2.2Dev/order_confirmation.php
sudo chown www-data:www-data /var/www/html/IAP2.2Dev/order_confirmation.php
echo "✅ Order confirmation file deployed"

# Create a simple test page
echo ""
echo "🧪 Creating test page..."
sudo tee /var/www/html/IAP2.2Dev/test.php > /dev/null << 'EOF'
<?php
phpinfo();
?>
EOF
sudo chmod 644 /var/www/html/IAP2.2Dev/test.php
sudo chown www-data:www-data /var/www/html/IAP2.2Dev/test.php
echo "✅ Test page created"

# Create a simple order confirmation test
echo ""
echo "🧪 Creating order confirmation test..."
sudo tee /var/www/html/IAP2.2Dev/order_test.php > /dev/null << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); min-height: 100vh; }
        .test-card { background: white; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="test-card p-4">
                    <h1 class="text-center text-success mb-4">
                        <i class="fas fa-check-circle"></i> Order Confirmation Test
                    </h1>
                    
                    <div class="alert alert-success">
                        <h5>✅ Web Server Working!</h5>
                        <p>If you can see this page, your web server is working correctly.</p>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5>🧪 Test Results</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                            <p><strong>Web Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
                            <p><strong>Current Path:</strong> <?php echo __DIR__; ?></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>📋 Test Order Details</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Order Number:</strong> ORD-20251116165528-3525</p>
                                    <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A'); ?></p>
                                    <p><strong>Amount:</strong> KSh 511.60</p>
                                    <p><strong>Status:</strong> <span class="badge bg-success">Confirmed</span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>🏢 Shipping Address</h6>
                                </div>
                                <div class="card-body">
                                    <p>Strathmore University<br>
                                    Ole sangale road<br>
                                    Nairobi, Kenya 00200</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="order_confirmation.php?order=ORD-20251116165528-3525" class="btn btn-success btn-lg">
                            <i class="fas fa-arrow-right"></i> Test Real Order Confirmation
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://kit.fontawesome.com/your-kit-id.js"></script>
</body>
</html>
EOF
sudo chmod 644 /var/www/html/IAP2.2Dev/order_test.php
sudo chown www-data:www-data /var/www/html/IAP2.2Dev/order_test.php
echo "✅ Order test page created"

# Check ports
echo ""
echo "🔍 Checking network ports..."
if netstat -tuln | grep -q ":80 "; then
    echo "✅ Port 80 is open"
else
    echo "❌ Port 80 is not open"
fi

if netstat -tuln | grep -q ":443 "; then
    echo "✅ Port 443 is open"
else
    echo "⚠️  Port 443 is not open (HTTPS)"
fi

# Check firewall
echo ""
echo "🔍 Checking firewall..."
if command -v ufw &> /dev/null; then
    ufw_status=$(sudo ufw status | grep "Status:" | awk '{print $2}')
    echo "UFW Status: $ufw_status"
    if [ "$ufw_status" = "active" ]; then
        echo "🔥 Firewall is active - checking HTTP rules..."
        if sudo ufw status | grep -q "80/tcp"; then
            echo "✅ HTTP (80) is allowed"
        else
            echo "❌ HTTP (80) not allowed - adding rule..."
            sudo ufw allow 80/tcp
        fi
    fi
fi

echo ""
echo "🎯 TESTING URLS:"
echo "=============="
echo "📋 Basic Test:           http://localhost/IAP2.2Dev/test.php"
echo "🧪 Order Test:           http://localhost/IAP2.2Dev/order_test.php"
echo "📄 Order Confirmation:   http://localhost/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo ""
echo "🔧 Alternative URLs (if localhost fails):"
echo "📋 Basic Test:           http://127.0.0.1/IAP2.2Dev/test.php"
echo "🧪 Order Test:           http://127.0.0.1/IAP2.2Dev/order_test.php"
echo "📄 Order Confirmation:   http://127.0.0.1/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo ""
echo "💡 TROUBLESHOOTING:"
echo "=================="
echo "1. Try the test URLs above in order"
echo "2. If localhost fails, try 127.0.0.1"
echo "3. Check browser console for errors"
echo "4. Check Apache error logs: sudo tail -f /var/log/apache2/error.log"
echo "5. Restart Apache: sudo systemctl restart apache2"
echo ""
echo "✅ Deployment complete!"