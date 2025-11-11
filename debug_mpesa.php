<?php
/**
 * M-Pesa Debug Script
 * Tests M-Pesa API connectivity and identifies issues
 */

require_once '/var/www/html/IAP2.2Dev/config.php';
require_once '/var/www/html/IAP2.2Dev/ClassAutoload.php';

echo "🔍 M-Pesa API Debug Script\n";
echo "==========================\n\n";

try {
    // Initialize database connection
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Database connection successful\n";
    
    // Initialize M-Pesa payment class
    $mpesa = new MpesaPayment($pdo, $conf['mpesa']);
    echo "✅ M-Pesa class initialized\n";
    
    // Display current configuration
    echo "\n🔧 Current M-Pesa Configuration:\n";
    echo "- Environment: " . $conf['mpesa']['environment'] . "\n";
    echo "- Consumer Key: " . substr($conf['mpesa']['consumer_key'], 0, 10) . "...\n";
    echo "- Consumer Secret: " . substr($conf['mpesa']['consumer_secret'], 0, 10) . "...\n";
    echo "- Short Code: " . $conf['mpesa']['short_code'] . "\n";
    echo "- Passkey: " . substr($conf['mpesa']['passkey'], 0, 10) . "...\n";
    echo "- Callback URL: " . $conf['mpesa']['callback_url'] . "\n";
    
    // Test 1: Check if curl is available
    echo "\n🧪 Test 1: CURL Extension\n";
    if (extension_loaded('curl')) {
        echo "✅ CURL extension is loaded\n";
        $curlVersion = curl_version();
        echo "   Version: " . $curlVersion['version'] . "\n";
        echo "   SSL Version: " . $curlVersion['ssl_version'] . "\n";
    } else {
        echo "❌ CURL extension is not loaded!\n";
        exit(1);
    }
    
    // Test 2: Test access token request
    echo "\n🧪 Test 2: Access Token Request\n";
    
    $url = $conf['mpesa']['environment'] === 'production' 
        ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
        : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    
    echo "   URL: $url\n";
    
    $credentials = base64_encode($conf['mpesa']['consumer_key'] . ':' . $conf['mpesa']['consumer_secret']);
    echo "   Credentials encoded: " . substr($credentials, 0, 20) . "...\n";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => false
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    echo "   HTTP Code: $httpCode\n";
    
    if ($curlError) {
        echo "❌ CURL Error: $curlError\n";
    }
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['access_token'])) {
            echo "✅ Access token obtained successfully\n";
            echo "   Token: " . substr($result['access_token'], 0, 20) . "...\n";
            echo "   Expires in: " . $result['expires_in'] . " seconds\n";
            
            // Test 3: Test STK Push with access token
            echo "\n🧪 Test 3: STK Push Request\n";
            
            $accessToken = $result['access_token'];
            $timestamp = date('YmdHis');
            $password = base64_encode($conf['mpesa']['short_code'] . $conf['mpesa']['passkey'] . $timestamp);
            
            echo "   Timestamp: $timestamp\n";
            echo "   Password: " . substr($password, 0, 20) . "...\n";
            
            $stkUrl = $conf['mpesa']['environment'] === 'production'
                ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
                : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            
            echo "   STK URL: $stkUrl\n";
            
            $postData = [
                'BusinessShortCode' => $conf['mpesa']['short_code'],
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => 1, // Test with 1 KSh
                'PartyA' => '254708374149', // Test number
                'PartyB' => $conf['mpesa']['short_code'],
                'PhoneNumber' => '254708374149',
                'CallBackURL' => $conf['mpesa']['callback_url'],
                'AccountReference' => 'TEST123',
                'TransactionDesc' => 'Test Transaction'
            ];
            
            echo "   Payload:\n";
            foreach ($postData as $key => $value) {
                if ($key === 'Password') {
                    echo "     $key: " . substr($value, 0, 20) . "...\n";
                } else {  
                    echo "     $key: $value\n";
                }
            }
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $stkUrl,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30
            ]);
            
            $stkResponse = curl_exec($curl);
            $stkHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $stkCurlError = curl_error($curl);
            curl_close($curl);
            
            echo "\n   STK Push Results:\n";
            echo "   HTTP Code: $stkHttpCode\n";
            
            if ($stkCurlError) {
                echo "❌ CURL Error: $stkCurlError\n";
            }
            
            if ($stkResponse) {
                echo "   Raw Response: $stkResponse\n";
                
                $stkResult = json_decode($stkResponse, true);
                if ($stkResult) {
                    echo "   Parsed Response:\n";
                    foreach ($stkResult as $key => $value) {
                        echo "     $key: $value\n";
                    }
                    
                    if ($stkHttpCode === 200 && isset($stkResult['CheckoutRequestID'])) {
                        echo "✅ STK Push initiated successfully!\n";
                        echo "   CheckoutRequestID: " . $stkResult['CheckoutRequestID'] . "\n";
                    } else {
                        echo "❌ STK Push failed\n";
                        if (isset($stkResult['errorMessage'])) {
                            echo "   Error: " . $stkResult['errorMessage'] . "\n";
                        }
                        if (isset($stkResult['errorCode'])) {
                            echo "   Error Code: " . $stkResult['errorCode'] . "\n";
                        }
                    }
                } else {
                    echo "❌ Could not parse STK response JSON\n";
                }
            } else {
                echo "❌ No STK response received\n";
            }
            
        } else {
            echo "❌ No access token in response\n";
            echo "   Response: $response\n";
        }
    } else {
        echo "❌ Failed to get access token\n";
        echo "   Response: $response\n";
        
        // Try to parse error
        $errorResult = json_decode($response, true);
        if ($errorResult && isset($errorResult['error_description'])) {
            echo "   Error: " . $errorResult['error_description'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Debug completed. Check the results above.\n";
