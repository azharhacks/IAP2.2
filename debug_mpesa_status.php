<?php
/**
 * M-Pesa Status Debug - SMARTDUKA
 * Debug M-Pesa payment status checking
 */

session_start();
require_once 'config.php';

echo "<h1>🔍 M-Pesa Status Debug</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f8f9fa; }
</style>";

try {
    if (!isset($_SESSION['user_id'])) {
        echo "<div class='error'>❌ Please login to test M-Pesa status</div>";
        exit();
    }
    
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    echo "<div class='success'>✅ User authenticated (ID: {$_SESSION['user_id']})</div>";
    echo "<div class='success'>✅ Database connected</div>";
    
    // Check M-Pesa transactions table
    echo "<div class='info'><h3>M-Pesa Transactions Check</h3>";
    
    if (!tableExists($pdo, 'mpesa_transactions')) {
        echo "<div class='error'>❌ mpesa_transactions table doesn't exist</div>";
        echo "<button onclick='createMpesaTable()' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Create Table</button>";
    } else {
        echo "✅ mpesa_transactions table exists<br>";
        
        // Get recent transactions for this user
        $stmt = $pdo->prepare("
            SELECT mt.*, o.order_number, o.order_status, o.payment_status
            FROM mpesa_transactions mt
            JOIN orders o ON mt.order_id = o.id
            WHERE o.user_id = ?
            ORDER BY mt.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($transactions) {
            echo "<strong>Recent Transactions:</strong><br>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Order</th><th>Checkout ID</th><th>Status</th><th>Amount</th><th>Created</th><th>Actions</th></tr>";
            
            foreach ($transactions as $txn) {
                $statusClass = match($txn['status']) {
                    'completed' => 'success',
                    'failed' => 'error',
                    'cancelled' => 'warning',
                    default => 'info'
                };
                
                echo "<tr>";
                echo "<td>{$txn['id']}</td>";
                echo "<td>{$txn['order_number']}</td>";
                echo "<td>{$txn['checkout_request_id']}</td>";
                echo "<td><span class='$statusClass' style='padding: 2px 8px; border-radius: 3px;'>{$txn['status']}</span></td>";
                echo "<td>KSh " . number_format($txn['amount'], 2) . "</td>";
                echo "<td>" . date('M j, H:i', strtotime($txn['created_at'])) . "</td>";
                echo "<td>";
                if ($txn['status'] === 'pending') {
                    echo "<button onclick='testStatusCheck(\"{$txn['checkout_request_id']}\")' style='background: #007bff; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin: 2px;'>Check Status</button>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ No transactions found for your account</div>";
        }
    }
    echo "</div>";
    
    // Test endpoints
    echo "<div class='info'><h3>Test M-Pesa Endpoints</h3>";
    $endpoints = ['mpesa_payment.php'];
    
    foreach ($endpoints as $endpoint) {
        if (file_exists($endpoint)) {
            echo "✅ $endpoint exists<br>";
        } else {
            echo "❌ $endpoint missing<br>";
        }
    }
    
    echo "<button onclick='testPaymentInit()' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>Test Payment Init</button>";
    echo "<button onclick='simulatePayment()' style='background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>Simulate Payment</button>";
    echo "</div>";
    
    // Status check results
    echo "<div id='testResults'></div>";
    
    // Helper function
    function tableExists($pdo, $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<script>
// Test status checking
async function testStatusCheck(checkoutRequestId) {
    const resultsDiv = document.getElementById('testResults');
    resultsDiv.innerHTML = '<div class="info">🔄 Testing status for: ' + checkoutRequestId + '</div>';
    
    try {
        const response = await fetch('mpesa_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'check_status',
                checkout_request_id: checkoutRequestId
            })
        });
        
        const data = await response.json();
        
        const resultClass = data.success ? 'success' : 'error';
        resultsDiv.innerHTML = 
            '<div class="' + resultClass + '">' +
            '<strong>Status Check Result:</strong><br>' +
            '<pre>' + JSON.stringify(data, null, 2) + '</pre>' +
            '</div>';
            
    } catch (error) {
        resultsDiv.innerHTML = 
            '<div class="error">' +
            '<strong>Error:</strong> ' + error.message +
            '</div>';
    }
}

// Test payment initiation
async function testPaymentInit() {
    const resultsDiv = document.getElementById('testResults');
    resultsDiv.innerHTML = '<div class="info">🔄 Testing payment initiation...</div>';
    
    // Get the first order for testing
    try {
        const response = await fetch('mpesa_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'initiate_payment',
                order_id: '14', // Test order
                phone_number: '254712345678'
            })
        });
        
        const data = await response.json();
        
        const resultClass = data.success ? 'success' : 'error';
        resultsDiv.innerHTML = 
            '<div class="' + resultClass + '">' +
            '<strong>Payment Init Result:</strong><br>' +
            '<pre>' + JSON.stringify(data, null, 2) + '</pre>' +
            '</div>';
            
        // If successful, start checking status
        if (data.success && data.checkout_request_id) {
            setTimeout(() => {
                testStatusCheck(data.checkout_request_id);
            }, 2000);
        }
            
    } catch (error) {
        resultsDiv.innerHTML = 
            '<div class="error">' +
            '<strong>Error:</strong> ' + error.message +
            '</div>';
    }
}

// Simulate payment completion
async function simulatePayment() {
    const resultsDiv = document.getElementById('testResults');
    resultsDiv.innerHTML = '<div class="info">🔄 Simulating complete payment flow...</div>';
    
    try {
        // Step 1: Initiate payment
        const initResponse = await fetch('mpesa_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'initiate_payment',
                order_id: '14',
                phone_number: '254712345678'
            })
        });
        
        const initData = await initResponse.json();
        
        if (!initData.success) {
            throw new Error('Payment initiation failed: ' + initData.message);
        }
        
        const checkoutId = initData.checkout_request_id;
        
        // Step 2: Wait and check status multiple times
        let attempts = 0;
        const maxAttempts = 6;
        const checkInterval = setInterval(async () => {
            attempts++;
            
            try {
                const statusResponse = await fetch('mpesa_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'check_status',
                        checkout_request_id: checkoutId
                    })
                });
                
                const statusData = await statusResponse.json();
                
                resultsDiv.innerHTML = 
                    '<div class="info">' +
                    '<strong>Payment Simulation (Attempt ' + attempts + '/' + maxAttempts + '):</strong><br>' +
                    '<strong>Status:</strong> ' + statusData.status + '<br>' +
                    '<strong>Message:</strong> ' + statusData.message + '<br>' +
                    '<pre>' + JSON.stringify(statusData, null, 2) + '</pre>' +
                    '</div>';
                
                // Stop if completed or failed
                if (statusData.status === 'completed' || statusData.status === 'failed' || attempts >= maxAttempts) {
                    clearInterval(checkInterval);
                    
                    const finalClass = statusData.status === 'completed' ? 'success' : 
                                     statusData.status === 'failed' ? 'error' : 'warning';
                    
                    resultsDiv.innerHTML = 
                        '<div class="' + finalClass + '">' +
                        '<strong>Final Result:</strong><br>' +
                        '<strong>Status:</strong> ' + statusData.status + '<br>' +
                        '<strong>Message:</strong> ' + statusData.message + '<br>' +
                        '</div>';
                }
                
            } catch (error) {
                clearInterval(checkInterval);
                resultsDiv.innerHTML = 
                    '<div class="error">' +
                    '<strong>Status Check Error:</strong> ' + error.message +
                    '</div>';
            }
        }, 5000); // Check every 5 seconds
        
    } catch (error) {
        resultsDiv.innerHTML = 
            '<div class="error">' +
            '<strong>Simulation Error:</strong> ' + error.message +
            '</div>';
    }
}

// Create M-Pesa table
async function createMpesaTable() {
    try {
        const response = await fetch('fix_database_schema.php');
        const text = await response.text();
        alert('Check the schema fixer page for table creation results');
        location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
</script>

<hr>
<h3>Quick Links</h3>
<ul>
    <li><a href="mpesa_payment_page.php?order=14">M-Pesa Payment Page</a></li>
    <li><a href="orders.php">My Orders</a></li>
    <li><a href="fix_database_schema.php">Fix Database Schema</a></li>
</ul>