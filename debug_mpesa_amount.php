<?php
/**
 * M-Pesa Debug Test - SMARTDUKA
 * Test M-Pesa API with exact values to debug the amount issue
 */

echo "<h1>🔧 M-Pesa Debug Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
</style>";

// M-Pesa API Configuration
class MpesaDebug {
    private $consumerKey = 'cXfEmCCWj9N5fd2Z1Oz541C9n90RjtECBS1Ff6pKVWSSh88H';
    private $consumerSecret = 'UBbIDpR2sqPBDshDPaiAdyEIgAGX3FvLEg89ZXlRffjX2K8plnCmnlUI5lQwfiPg';
    private $businessShortCode = '174379';
    private $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
    private $callbackURL = 'http://localhost/IAP2.2Dev/mpesa_callback.php';
    
    public function generateAccessToken() {
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        echo "<div class='info'><strong>Access Token Request:</strong><br>";
        echo "URL: $url<br>";
        echo "HTTP Code: $httpCode<br>";
        if ($error) echo "cURL Error: $error<br>";
        echo "Response: " . htmlspecialchars($response) . "</div>";
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['access_token'] ?? null;
        }
        
        return null;
    }
    
    public function testStkPush($phoneNumber, $amount) {
        echo "<div class='info'><h3>🧪 Testing STK Push</h3>";
        echo "<strong>Phone Number:</strong> $phoneNumber<br>";
        echo "<strong>Original Amount:</strong> $amount<br>";
        
        // Process amount exactly like the real API
        $processedAmount = (int)round(abs($amount));
        echo "<strong>Processed Amount:</strong> $processedAmount<br>";
        echo "</div>";
        
        $accessToken = $this->generateAccessToken();
        if (!$accessToken) {
            echo "<div class='error'>❌ Failed to get access token</div>";
            return;
        }
        
        echo "<div class='success'>✅ Access token obtained: " . substr($accessToken, 0, 20) . "...</div>";
        
        // Generate password
        $timestamp = date('YmdHis');
        $password = base64_encode($this->businessShortCode . $this->passkey . $timestamp);
        
        echo "<div class='info'><strong>Password Generation:</strong><br>";
        echo "Timestamp: $timestamp<br>";
        echo "Password: " . substr($password, 0, 30) . "...</div>";
        
        // Format phone number
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '254' . substr($phoneNumber, 1);
        } elseif (substr($phoneNumber, 0, 3) !== '254') {
            $phoneNumber = '254' . $phoneNumber;
        }
        
        echo "<div class='info'><strong>Formatted Phone:</strong> $phoneNumber</div>";
        
        $requestData = [
            'BusinessShortCode' => $this->businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $processedAmount, // Integer amount
            'PartyA' => $phoneNumber,
            'PartyB' => $this->businessShortCode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $this->callbackURL,
            'AccountReference' => 'TEST-ORDER-1',
            'TransactionDesc' => 'Test Payment'
        ];
        
        echo "<div class='info'><h4>📤 Request Payload:</h4>";
        echo "<div class='code'>" . json_encode($requestData, JSON_PRETTY_PRINT) . "</div></div>";
        
        $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        echo "<div class='info'><h4>📥 STK Push Response:</h4>";
        echo "HTTP Code: $httpCode<br>";
        if ($error) echo "cURL Error: $error<br>";
        echo "Response: <div class='code'>" . htmlspecialchars($response) . "</div></div>";
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['CheckoutRequestID'])) {
                echo "<div class='success'>✅ STK Push successful!<br>";
                echo "CheckoutRequestID: " . $result['CheckoutRequestID'] . "</div>";
            } else {
                echo "<div class='error'>❌ No CheckoutRequestID in response</div>";
            }
        } else {
            echo "<div class='error'>❌ STK Push failed with HTTP $httpCode</div>";
        }
    }
}

// Test with different amounts
$debug = new MpesaDebug();

// Test amounts
$testCases = [
    ['phone' => '708374149', 'amount' => 511.60],
    ['phone' => '708374149', 'amount' => 512],
    ['phone' => '708374149', 'amount' => 1],
    ['phone' => '708374149', 'amount' => 100]
];

echo "<div class='warning'>";
echo "<h3>🧪 Running Test Cases</h3>";
echo "Testing different amounts to identify the issue...";
echo "</div>";

foreach ($testCases as $i => $test) {
    echo "<div style='border: 2px solid #007bff; margin: 20px 0; padding: 15px; border-radius: 10px;'>";
    echo "<h4>Test Case " . ($i + 1) . "</h4>";
    $debug->testStkPush($test['phone'], $test['amount']);
    echo "</div>";
    
    // Add delay between requests
    if ($i < count($testCases) - 1) {
        echo "<div class='info'>Waiting 3 seconds before next test...</div>";
        sleep(3);
    }
}
?>