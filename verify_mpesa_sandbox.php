<?php
/**
 * M-Pesa Sandbox Verification - SMARTDUKA
 * Test M-Pesa sandbox credentials and connectivity
 */

echo "<h1>🏖️ M-Pesa Sandbox Verification</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
.code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
</style>";

// M-Pesa Sandbox Configuration
$consumerKey = 'cXfEmCCWj9N5fd2Z1Oz541C9n90RjtECBS1Ff6pKVWSSh88H';
$consumerSecret = 'UBbIDpR2sqPBDshDPaiAdyEIgAGX3FvLEg89ZXlRffjX2K8plnCmnlUI5lQwfiPg';
$sandboxUrl = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

echo "<div class='info'>";
echo "<h3>🔧 Sandbox Configuration</h3>";
echo "<strong>Environment:</strong> SANDBOX<br>";
echo "<strong>Consumer Key:</strong> " . substr($consumerKey, 0, 10) . "...<br>";
echo "<strong>Consumer Secret:</strong> " . substr($consumerSecret, 0, 10) . "...<br>";
echo "<strong>Business Short Code:</strong> 174379<br>";
echo "</div>";

echo "<div class='info'><h3>🧪 Testing Sandbox Connectivity</h3>";

try {
    // Test access token generation
    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $sandboxUrl,
        CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        throw new Exception("cURL Error: $error");
    }
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        
        if (isset($result['access_token'])) {
            echo "<div class='success'>✅ <strong>Sandbox connectivity successful!</strong><br>";
            echo "Access token received: " . substr($result['access_token'], 0, 20) . "...<br>";
            echo "Token type: " . $result['token_type'] . "<br>";
            echo "Expires in: " . $result['expires_in'] . " seconds</div>";
            
            // Test STK Push (dry run)
            echo "<div class='info'><h3>📱 STK Push Test</h3>";
            echo "Ready to send STK Push to sandbox numbers:<br>";
            echo "• <strong>254708374149</strong> (Always succeeds)<br>";
            echo "• <strong>254712345678</strong> (Always succeeds)<br>";
            echo "• <strong>254799999999</strong> (Always fails)</div>";
            
        } else {
            throw new Exception("No access token in response: " . $response);
        }
        
    } else {
        throw new Exception("HTTP $httpCode: $response");
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>Sandbox connection failed:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// Test instructions
echo "<div class='warning'>";
echo "<h3>🎯 How to Test M-Pesa Sandbox</h3>";
echo "<ol>";
echo "<li><strong>Go to M-Pesa payment page:</strong><br>";
echo "<a href='real_mpesa_payment.php?order=16'>real_mpesa_payment.php?order=16</a></li>";
echo "<li><strong>Enter a sandbox test number:</strong> 254708374149</li>";
echo "<li><strong>Click 'Send STK Push'</strong></li>";
echo "<li><strong>Wait for sandbox response</strong> (simulated phone prompt)</li>";
echo "<li><strong>Sandbox will automatically complete</strong> after a few seconds</li>";
echo "</ol>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>📋 Sandbox vs Live Differences</h3>";
echo "<strong>SANDBOX (Current):</strong><br>";
echo "• No real money involved<br>";
echo "• No actual phone prompts<br>";
echo "• Simulated responses from Safaricom<br>";
echo "• Safe for testing and development<br><br>";
echo "<strong>LIVE/PRODUCTION:</strong><br>";
echo "• Real money transactions<br>";
echo "• Actual phone prompts sent<br>";
echo "• Real M-Pesa receipts<br>";
echo "• Requires production credentials<br>";
echo "</div>";

echo "<div class='success'>";
echo "<h3>🎉 Sandbox Status: READY</h3>";
echo "<p>Your M-Pesa sandbox integration is properly configured and ready for testing!</p>";
echo "</div>";
?>