<?php
/**
 * Order CSV Export
 * Generates CSV export for orders (single order or all orders)
 */

session_start();
require_once '../config.php';

// Basic auth check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Access denied - Admin privileges required');
}

$exportType = $_GET['type'] ?? 'single'; // 'single' or 'all'
$orderId = (int)($_GET['id'] ?? 0);

try {
    $pdo = new PDO("mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8", 
                   $conf['db_user'], $conf['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($exportType === 'single' && $orderId) {
        // Export single order with items
        exportSingleOrder($pdo, $orderId, $conf);
    } else {
        // Export all orders summary
        exportAllOrders($pdo, $conf);
    }
    
} catch (Exception $e) {
    exit('Database error: ' . $e->getMessage());
}

function exportSingleOrder($pdo, $orderId, $conf) {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        exit('Order not found');
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.description 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    $filename = 'Order_' . $order['order_number'] . '_Details_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create file pointer
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Order Information Header
    fputcsv($output, ['ORDER INFORMATION - ' . strtoupper($conf['site_name'])]);
    fputcsv($output, []);
    
    // Order Details
    fputcsv($output, ['Order Number', $order['order_number'] ?? 'N/A']);
    fputcsv($output, ['Order Date', date('M j, Y g:i A', strtotime($order['created_at']))]);
    fputcsv($output, ['Customer', $order['username'] ?? 'Unknown']);
    fputcsv($output, ['Email', $order['email'] ?? 'N/A']);
    fputcsv($output, ['Order Status', ucfirst($order['status'])]);
    fputcsv($output, ['Payment Status', ucfirst($order['payment_status'])]);
    fputcsv($output, ['Total Amount', 'KSh ' . number_format($order['total_amount'], 2)]);
    fputcsv($output, []);
    
    // Order Items Header
    fputcsv($output, ['ORDER ITEMS']);
    fputcsv($output, ['Product Name', 'Quantity', 'Unit Price (KSh)', 'Total Price (KSh)']);
    
    // Order Items Data
    $subtotal = 0;
    if (!empty($orderItems)) {
        foreach ($orderItems as $item) {
            $itemTotal = $item['unit_price'] * $item['quantity'];
            $subtotal += $itemTotal;
            fputcsv($output, [
                $item['product_name'] ?? 'Unknown Product',
                $item['quantity'],
                number_format($item['unit_price'], 2),
                number_format($itemTotal, 2)
            ]);
        }
    } else {
        fputcsv($output, ['No items found', '', '', '']);
    }
    
    // Totals
    fputcsv($output, []);
    fputcsv($output, ['SUBTOTAL', '', '', 'KSh ' . number_format($subtotal, 2)]);
    fputcsv($output, ['TOTAL AMOUNT', '', '', 'KSh ' . number_format($order['total_amount'], 2)]);
    fputcsv($output, []);
    
    // Shipping Address
    if (!empty($order['shipping_address'])) {
        fputcsv($output, ['SHIPPING ADDRESS']);
        $addressLines = explode("\n", $order['shipping_address']);
        foreach ($addressLines as $line) {
            fputcsv($output, [trim($line)]);
        }
    }
    
    fputcsv($output, []);
    fputcsv($output, ['Generated on ' . date('M j, Y g:i A') . ' by ' . $conf['site_name']]);
    
    fclose($output);
}

function exportAllOrders($pdo, $conf) {
    // Get all orders with customer info
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email,
               COUNT(oi.id) as item_count,
               SUM(oi.total_price) as items_total
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    $filename = 'All_Orders_Export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create file pointer
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['ORDERS EXPORT - ' . strtoupper($conf['site_name'])]);
    fputcsv($output, ['Generated on: ' . date('M j, Y g:i A')]);
    fputcsv($output, ['Total Orders: ' . count($orders)]);
    fputcsv($output, []);
    
    // Column headers
    fputcsv($output, [
        'Order ID',
        'Order Number', 
        'Customer Name',
        'Email',
        'Order Date',
        'Status',
        'Payment Status',
        'Items Count',
        'Total Amount (KSh)',
        'Shipping Address'
    ]);
    
    // Data rows
    foreach ($orders as $order) {
        fputcsv($output, [
            $order['id'],
            $order['order_number'] ?? 'N/A',
            $order['username'] ?? 'Unknown',
            $order['email'] ?? 'N/A',
            date('M j, Y g:i A', strtotime($order['created_at'])),
            ucfirst($order['status']),
            ucfirst($order['payment_status']),
            $order['item_count'],
            number_format($order['total_amount'], 2),
            str_replace(["\r", "\n"], [' ', ' '], $order['shipping_address'] ?? 'N/A')
        ]);
    }
    
    fputcsv($output, []);
    
    // Summary statistics
    $totalRevenue = array_sum(array_column($orders, 'total_amount'));
    $statusCount = array_count_values(array_column($orders, 'status'));
    
    fputcsv($output, ['SUMMARY STATISTICS']);
    fputcsv($output, ['Total Revenue', 'KSh ' . number_format($totalRevenue, 2)]);
    fputcsv($output, ['Average Order Value', 'KSh ' . number_format($totalRevenue / count($orders), 2)]);
    fputcsv($output, []);
    
    fputcsv($output, ['ORDER STATUS BREAKDOWN']);
    foreach ($statusCount as $status => $count) {
        fputcsv($output, [ucfirst($status), $count . ' orders']);
    }
    
    fclose($output);
}
?>
