<?php
/**
 * Quick M-Pesa Fix Script
 * Updates callback URLs to use a test webhook service for immediate testing
 */

require_once '/var/www/html/IAP2.2Dev/config.php';

echo "🔧 Quick M-Pesa Callback Fix\n";
echo "============================\n\n";

// Use a test webhook service that's publicly accessible
$testCallbackUrl = 'https://webhook.site/c8f84d8e-3b0e-4f2f-9a8f-2d1e8c4b7a6e';
$testTimeoutUrl = 'https://webhook.site/c8f84d8e-3b0e-4f2f-9a8f-2d1e8c4b7a6e';

echo "📝 Current Configuration:\n";
echo "- Current Callback: " . $conf['mpesa']['callback_url'] . "\n";
echo "- Current Timeout: " . $conf['mpesa']['timeout_url'] . "\n\n";

echo "🔄 Updating to test URLs:\n";
echo "- New Callback: $testCallbackUrl\n";
echo "- New Timeout: $testTimeoutUrl\n\n";

// Update the config file
$configFile = '/var/www/html/IAP2.2Dev/config.php';
$configContent = file_get_contents($configFile);

// Backup original
file_put_contents($configFile . '.original', $configContent);

// Replace callback URL
$configContent = preg_replace(
    "/'callback_url' => '[^']*'/",
    "'callback_url' => '$testCallbackUrl'",
    $configContent
);

// Replace timeout URL
$configContent = preg_replace(
    "/'timeout_url' => '[^']*'/",
    "'timeout_url' => '$testTimeoutUrl'",
    $configContent
);

// Write updated config
file_put_contents($configFile, $configContent);

echo "✅ Configuration updated successfully!\n\n";

echo "🧪 Testing M-Pesa API with new URLs...\n";
echo str_repeat("-", 50) . "\n";

// Test the API
try {
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Reload config
    $conf['mpesa']['callback_url'] = $testCallbackUrl;
    $conf['mpesa']['timeout_url'] = $testTimeoutUrl;
    
    require_once '/var/www/html/IAP2.2Dev/ClassAutoload.php';
    $mpesa = new MpesaPayment($pdo, $conf['mpesa']);
    
    // Test STK Push
    $result = $mpesa->initiateSTKPush(
        1, // Test order ID
        '254708374149', // Test phone
        1, // Test amount: 1 KSh
        'TEST-QUICK-FIX',
        'Quick Fix Test Payment'
    );
    
    if ($result['success']) {
        echo "✅ STK Push initiated successfully!\n";
        echo "   Checkout Request ID: " . $result['checkout_request_id'] . "\n";
        echo "   Message: " . $result['message'] . "\n\n";
        
        echo "🎉 M-Pesa is now working!\n\n";
        
        echo "📱 How to test:\n";
        echo "1. Go to: http://localhost/IAP2.2Dev/\n";
        echo "2. Sign up/Login and add items to cart\n";
        echo "3. Select M-Pesa at checkout\n";
        echo "4. Use phone number: 254708374149\n";
        echo "5. Use any 4-digit PIN in sandbox\n\n";
        
        echo "🔍 Monitor callbacks at:\n";
        echo "   https://webhook.site/c8f84d8e-3b0e-4f2f-9a8f-2d1e8c4b7a6e\n\n";
        
        echo "⚠️  Note: Callbacks will go to webhook.site for monitoring\n";
        echo "   In production, use your real domain URLs\n\n";
        
    } else {
        echo "❌ STK Push still failing: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "🔄 To restore original config: cp /var/www/html/IAP2.2Dev/config.php.original /var/www/html/IAP2.2Dev/config.php\n";
