<?php
/**
 * M-Pesa Webhook Setup - SMARTDUKA
 * Setup proper callback URL for M-Pesa sandbox testing
 */

echo "<h1>🔗 M-Pesa Webhook Setup</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
.code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
.step { background: #e7f3ff; border-left: 4px solid #007bff; padding: 15px; margin: 15px 0; }
</style>";

echo "<div class='error'>";
echo "<h3>❌ Callback URL Issue</h3>";
echo "<p>The current callback URL <code>http://localhost/IAP2.2Dev/mpesa_callback.php</code> is not accessible from Safaricom's servers because:</p>";
echo "<ul>";
echo "<li>❌ <code>localhost</code> is only accessible from your local machine</li>";
echo "<li>❌ Safaricom servers cannot reach your local development environment</li>";
echo "<li>❌ M-Pesa requires a publicly accessible HTTPS URL</li>";
echo "</ul>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🔧 Quick Solutions for Sandbox Testing</h3>";
echo "</div>";

echo "<div class='step'>";
echo "<h4>Option 1: Use Webhook.site (Easiest for Testing)</h4>";
echo "<ol>";
echo "<li><strong>Go to:</strong> <a href='https://webhook.site' target='_blank'>https://webhook.site</a></li>";
echo "<li><strong>Copy your unique URL</strong> (e.g., https://webhook.site/12345678-abcd-1234-abcd-123456789abc)</li>";
echo "<li><strong>Update the callback URL below</strong></li>";
echo "</ol>";

echo "<form method='post' style='margin: 15px 0;'>";
echo "<label for='webhook_url'>Webhook.site URL:</label><br>";
echo "<input type='url' name='webhook_url' id='webhook_url' placeholder='https://webhook.site/your-unique-id' style='width: 100%; padding: 8px; margin: 5px 0;'>";
echo "<br><button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin: 10px 0;'>Update Callback URL</button>";
echo "</form>";
echo "</div>";

if ($_POST['webhook_url'] ?? false) {
    $webhookUrl = trim($_POST['webhook_url']);
    
    if (filter_var($webhookUrl, FILTER_VALIDATE_URL) && strpos($webhookUrl, 'https://') === 0) {
        // Update the callback URL in the real_mpesa_payment.php file
        $mpesaFile = '/home/devyanjethwaa/IAP2.2-1/real_mpesa_payment.php';
        $mpesaContent = file_get_contents($mpesaFile);
        
        $updatedContent = preg_replace(
            '/\$this->callbackURL = [\'"][^\'";]+[\'"];/',
            "\$this->callbackURL = '$webhookUrl';",
            $mpesaContent
        );
        
        file_put_contents($mpesaFile, $updatedContent);
        
        echo "<div class='success'>";
        echo "<h4>✅ Callback URL Updated!</h4>";
        echo "<p>Updated to: <code>$webhookUrl</code></p>";
        echo "<p>You can now test M-Pesa payments and see the callbacks on webhook.site</p>";
        echo "</div>";
        
    } else {
        echo "<div class='error'>❌ Invalid URL. Please use a valid HTTPS URL from webhook.site</div>";
    }
}

echo "<div class='step'>";
echo "<h4>Option 2: Use ngrok (For Advanced Users)</h4>";
echo "<ol>";
echo "<li><strong>Install ngrok:</strong> <a href='https://ngrok.com/download' target='_blank'>https://ngrok.com/download</a></li>";
echo "<li><strong>Run:</strong> <code>ngrok http 80</code></li>";
echo "<li><strong>Use the HTTPS URL:</strong> https://abc123.ngrok.io/IAP2.2Dev/mpesa_callback.php</li>";
echo "</ol>";
echo "</div>";

echo "<div class='step'>";
echo "<h4>Option 3: Use PostBin (Alternative)</h4>";
echo "<ol>";
echo "<li><strong>Go to:</strong> <a href='https://postb.in' target='_blank'>https://postb.in</a></li>";
echo "<li><strong>Create a bin</strong> and copy the URL</li>";
echo "<li><strong>Use that URL</strong> as your callback</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ For Production</h3>";
echo "<p>When you go live, you'll need:</p>";
echo "<ul>";
echo "<li>✅ A real domain with HTTPS (SSL certificate)</li>";
echo "<li>✅ Proper server hosting (not localhost)</li>";
echo "<li>✅ Valid callback endpoint that can process M-Pesa responses</li>";
echo "</ul>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🧪 Testing After Setup</h3>";
echo "<p>Once you've updated the callback URL:</p>";
echo "<ol>";
echo "<li><strong>Test M-Pesa payment:</strong> <a href='real_mpesa_payment.php?order=16'>real_mpesa_payment.php?order=16</a></li>";
echo "<li><strong>Watch for callbacks</strong> on your webhook.site URL</li>";
echo "<li><strong>Use sandbox phone numbers:</strong> 254708374149, 254712345678</li>";
echo "</ol>";
echo "</div>";

// Show current callback URL
$mpesaFile = '/home/devyanjethwaa/IAP2.2-1/real_mpesa_payment.php';
if (file_exists($mpesaFile)) {
    $content = file_get_contents($mpesaFile);
    if (preg_match('/\$this->callbackURL = [\'"]([^\'"]+)[\'"];/', $content, $matches)) {
        $currentUrl = $matches[1];
        echo "<div class='info'>";
        echo "<h4>Current Callback URL:</h4>";
        echo "<code>$currentUrl</code>";
        echo "</div>";
    }
}
?>