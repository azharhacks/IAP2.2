<?php
/**
 * M-Pesa Admin API - WORKING VERSION
 */

session_start();
require_once '../config.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Set content type
header('Content-Type: application/json');

try {
    // Use existing PDO connection from config
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_transaction':
            $transactionId = $_GET['id'] ?? '';
            if (!$transactionId) {
                throw new Exception('Transaction ID required');
            }
            
            $stmt = $pdo->prepare("
                SELECT mt.*, 
                       o.order_number, o.total_amount as order_total, o.order_status,
                       u.username, u.email
                FROM mpesa_transactions mt
                LEFT JOIN orders o ON mt.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE mt.id = ?
            ");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            echo json_encode(['success' => true, 'transaction' => $transaction]);
            break;
            
        case 'update_status':
            $transactionId = $_POST['transaction_id'] ?? '';
            $newStatus = $_POST['status'] ?? '';
            
            if (!$transactionId || !$newStatus) {
                throw new Exception('Transaction ID and status required');
            }
            
            $validStatuses = ['pending', 'completed', 'failed', 'cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $pdo->prepare("UPDATE mpesa_transactions SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $transactionId]);
            
            // If completing transaction, update order status
            if ($newStatus === 'completed') {
                $stmt = $pdo->prepare("
                    UPDATE orders o 
                    JOIN mpesa_transactions mt ON o.id = mt.order_id 
                    SET o.payment_status = 'paid', o.order_status = 'processing'
                    WHERE mt.id = ?
                ");
                $stmt->execute([$transactionId]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Transaction status updated successfully']);
            break;
            
        case 'get_statistics':
            $period = $_GET['period'] ?? '30'; // days
            
            // Overall stats for the period
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_transactions,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_transactions,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_transactions,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount END), 0) as total_revenue,
                    COALESCE(AVG(CASE WHEN status = 'completed' THEN amount END), 0) as avg_transaction
                FROM mpesa_transactions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$period]);
            $overallStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Daily stats for the period
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(created_at) as transaction_date,
                    COUNT(*) as daily_count,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as daily_revenue
                FROM mpesa_transactions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY transaction_date DESC
            ");
            $stmt->execute([$period]);
            $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'overall' => $overallStats,
                'daily' => $dailyStats
            ]);
            break;
            
        case 'search_transactions':
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));
            
            $whereConditions = [];
            $params = [];
            
            if ($search) {
                $whereConditions[] = "(mt.mpesa_receipt_number LIKE ? OR mt.phone_number LIKE ? OR o.order_number LIKE ? OR u.username LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            if ($status && $status !== 'all') {
                $whereConditions[] = "mt.status = ?";
                $params[] = $status;
            }
            
            $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
            
            $stmt = $pdo->prepare("
                SELECT mt.*, 
                       o.order_number, o.total_amount as order_total,
                       u.username, u.email
                FROM mpesa_transactions mt
                LEFT JOIN orders o ON mt.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                $whereClause
                ORDER BY mt.created_at DESC
                LIMIT ?
            ");
            
            $params[] = $limit;
            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'transactions' => $transactions]);
            break;
            
        case 'retry_transaction':
            $transactionId = $_POST['transaction_id'] ?? '';
            
            if (!$transactionId) {
                throw new Exception('Transaction ID required');
            }
            
            // Get transaction details
            $stmt = $pdo->prepare("SELECT * FROM mpesa_transactions WHERE id = ? AND status IN ('failed', 'pending')");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                throw new Exception('Transaction not found or cannot be retried');
            }
            
            // Update status to pending for retry
            $stmt = $pdo->prepare("UPDATE mpesa_transactions SET status = 'pending', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$transactionId]);
            
            echo json_encode(['success' => true, 'message' => 'Transaction retry initiated']);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log("M-Pesa Admin API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>