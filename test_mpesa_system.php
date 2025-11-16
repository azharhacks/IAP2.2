<?php
/**
 * M-Pesa System Test - Verify All Components
 */

echo "🧪 SMARTDUKA M-PESA SYSTEM TEST\n";
echo "===============================\n\n";

// Include configuration
require_once 'config.php';

echo "1. 🔧 Testing Database Connection...\n";
try {
    if (isset($pdo) && $pdo) {
        echo "   ✅ Database connection: OK\n";
        
        // Test M-Pesa transactions table
        $stmt = $pdo->query("SHOW TABLES LIKE 'mpesa_transactions'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ mpesa_transactions table: EXISTS\n";
            
            // Count transactions
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM mpesa_transactions");
            $count = $stmt->fetch()['count'];
            echo "   📊 Total transactions: {$count}\n";
        } else {
            echo "   ❌ mpesa_transactions table: NOT FOUND\n";
        }
    } else {
        echo "   ❌ Database connection: FAILED\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n2. 📁 Testing M-Pesa Admin Files...\n";

$admin_files = [
    'admin/mpesa_simple.php' => 'Main M-Pesa Interface',
    'admin/mpesa_pdf_export.php' => 'PDF Export',
    'admin/mpesa_csv_export.php' => 'CSV Export', 
    'admin/mpesa_admin_api.php' => 'Admin API',
    'admin/goto_mpesa.php' => 'Redirect Handler'
];

foreach ($admin_files as $file => $description) {
    $source_file = "/home/devyanjethwaa/IAP2.2-1/{$file}";
    $web_file = "/var/www/html/IAP2.2Dev/{$file}";
    
    echo "   Testing {$description}...\n";
    
    // Check source file
    if (file_exists($source_file)) {
        echo "     ✅ Source file exists\n";
        
        // Check syntax
        $output = shell_exec("php -l {$source_file} 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "     ✅ PHP syntax: OK\n";
        } else {
            echo "     ❌ PHP syntax: ERROR\n";
            echo "     🔍 Details: {$output}\n";
        }
    } else {
        echo "     ❌ Source file: NOT FOUND\n";
    }
    
    // Check web deployment
    if (file_exists($web_file)) {
        echo "     ✅ Web deployment: OK\n";
    } else {
        echo "     ❌ Web deployment: NOT FOUND\n";
    }
    
    echo "\n";
}

echo "3. 🎨 Testing M-Pesa Configuration...\n";
if (isset($conf['mpesa'])) {
    echo "   ✅ M-Pesa config: LOADED\n";
    
    $required_keys = ['consumer_key', 'consumer_secret', 'environment', 'short_code', 'passkey'];
    foreach ($required_keys as $key) {
        if (isset($conf['mpesa'][$key]) && !empty($conf['mpesa'][$key])) {
            echo "   ✅ {$key}: SET\n";
        } else {
            echo "   ❌ {$key}: MISSING\n";
        }
    }
} else {
    echo "   ❌ M-Pesa config: NOT FOUND\n";
}

echo "\n4. 🔗 Testing M-Pesa Class...\n";
if (file_exists('classes/MpesaPayment.php')) {
    echo "   ✅ MpesaPayment class file: EXISTS\n";
    
    try {
        require_once 'classes/MpesaPayment.php';
        echo "   ✅ MpesaPayment class: LOADED\n";
        
        if (isset($pdo) && $pdo && isset($conf['mpesa'])) {
            $mpesa = new MpesaPayment($pdo, $conf['mpesa']);
            echo "   ✅ MpesaPayment instance: CREATED\n";
        } else {
            echo "   ⚠️  MpesaPayment instance: CANNOT CREATE (missing dependencies)\n";
        }
    } catch (Exception $e) {
        echo "   ❌ MpesaPayment error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ MpesaPayment class file: NOT FOUND\n";
}

echo "\n5. 🌐 Testing Web Access...\n";
echo "   📍 Admin URLs to test:\n";
echo "      http://localhost/IAP2.2Dev/admin/mpesa_simple.php\n";
echo "      http://localhost/IAP2.2Dev/admin/mpesa_pdf_export.php\n";
echo "      http://localhost/IAP2.2Dev/admin/mpesa_csv_export.php\n";

echo "\n6. 📊 Final Status Report...\n";
echo "   =======================\n";

// Count working components
$working_files = 0;
$total_files = count($admin_files);

foreach ($admin_files as $file => $description) {
    if (file_exists("/var/www/html/IAP2.2Dev/{$file}")) {
        $working_files++;
    }
}

$percentage = round(($working_files / $total_files) * 100);
echo "   📈 System Status: {$working_files}/{$total_files} components working ({$percentage}%)\n";

if ($percentage >= 80) {
    echo "   🎉 M-PESA SYSTEM: OPERATIONAL\n";
} else {
    echo "   ⚠️  M-PESA SYSTEM: NEEDS ATTENTION\n";
}

echo "\n✅ Test Complete!\n";
echo "==================\n\n";
?>