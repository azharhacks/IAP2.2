<?php
/**
 * Session Debug - Check Login Status
 */

session_start();

echo "<h1>🔍 SMARTDUKA Session Debug</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

echo "<div class='info'>";
echo "<h3>Session Information</h3>";
echo "<strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Inactive") . "<br>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>Time:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>Session Variables</h3>";
if (empty($_SESSION)) {
    echo "<div class='warning'>⚠️ No session variables found</div>";
} else {
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
}
echo "</div>";

// Check specific login variables
echo "<div class='info'>";
echo "<h3>Login Status Check</h3>";

if (isset($_SESSION['user_id'])) {
    echo "<div class='success'>✅ user_id: " . $_SESSION['user_id'] . "</div>";
} else {
    echo "<div class='error'>❌ user_id: NOT SET</div>";
}

if (isset($_SESSION['verified'])) {
    $verifiedStatus = $_SESSION['verified'] === true ? "TRUE" : "FALSE";
    $class = $_SESSION['verified'] === true ? "success" : "warning";
    echo "<div class='$class'>📋 verified: $verifiedStatus</div>";
} else {
    echo "<div class='warning'>⚠️ verified: NOT SET</div>";
}

if (isset($_SESSION['username'])) {
    echo "<div class='success'>✅ username: " . $_SESSION['username'] . "</div>";
} else {
    echo "<div class='warning'>⚠️ username: NOT SET</div>";
}

if (isset($_SESSION['email'])) {
    echo "<div class='success'>✅ email: " . $_SESSION['email'] . "</div>";
} else {
    echo "<div class='warning'>⚠️ email: NOT SET</div>";
}
echo "</div>";

// Test orders.php access logic
echo "<div class='info'>";
echo "<h3>Orders.php Access Test</h3>";

$canAccessOrders = true;
$reasons = [];

// Check user_id
if (!isset($_SESSION['user_id'])) {
    $canAccessOrders = false;
    $reasons[] = "❌ No user_id in session";
} else {
    $reasons[] = "✅ user_id found: " . $_SESSION['user_id'];
}

// Check verification (flexible check)
if (isset($_SESSION['verified']) && $_SESSION['verified'] === false) {
    $canAccessOrders = false;
    $reasons[] = "❌ User explicitly not verified";
} else {
    $reasons[] = "✅ Verification check passed";
}

if ($canAccessOrders) {
    echo "<div class='success'>";
    echo "<h4>✅ CAN ACCESS ORDERS.PHP</h4>";
    foreach ($reasons as $reason) {
        echo "$reason<br>";
    }
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h4>❌ CANNOT ACCESS ORDERS.PHP</h4>";
    foreach ($reasons as $reason) {
        echo "$reason<br>";
    }
    echo "</div>";
}
echo "</div>";

// Database check
echo "<div class='info'>";
echo "<h3>Database Connection Test</h3>";
try {
    require_once 'config.php';
    if (isset($pdo) && $pdo) {
        echo "<div class='success'>✅ Database connection available</div>";
        
        // Test user lookup if user_id exists
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "<div class='success'>✅ User found in database: " . $user['username'] . " (" . $user['email'] . ")</div>";
            } else {
                echo "<div class='error'>❌ User ID " . $_SESSION['user_id'] . " not found in database</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ Database connection not available</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Quick actions
echo "<div class='info'>";
echo "<h3>Quick Actions</h3>";
echo "<ul>";
echo "<li><a href='orders.php'>Try Orders Page</a></li>";
echo "<li><a href='dashboard.php'>Try Dashboard</a></li>";
echo "<li><a href='Signin.php'>Go to Sign In</a></li>";
echo "<li><a href='logout.php'>Logout</a> (if logout exists)</li>";
echo "</ul>";
echo "</div>";

// Fix session helper
echo "<div class='info'>";
echo "<h3>Session Fix Helper</h3>";
if (isset($_SESSION['user_id']) && !isset($_SESSION['verified'])) {
    echo "<div class='warning'>";
    echo "💡 <strong>Quick Fix:</strong> Your session has user_id but no 'verified' flag.<br>";
    echo "This is likely why orders.php redirects you to login.<br><br>";
    echo "<button onclick='setVerified()' style='background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;'>Set Verified Status</button>";
    echo "</div>";
}
echo "</div>";
?>

<script>
function setVerified() {
    if (confirm('Set your session as verified? This will allow access to orders.php')) {
        fetch('session_fix.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({action: 'set_verified'})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Session updated! You can now access orders.php');
                location.reload();
            } else {
                alert('❌ Failed to update session');
            }
        })
        .catch(error => {
            alert('❌ Error: ' + error.message);
        });
    }
}
</script>
