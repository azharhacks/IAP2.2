<?php
/**
 * M-Pesa Database Helper
 * Ensures proper database connection for all M-Pesa operations
 */

function ensureMpesaDatabaseConnection() {
    global $pdo, $conf;
    
    // Check if PDO connection exists and is valid
    if (!isset($pdo) || !$pdo) {
        try {
            $pdo = new PDO(
                "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8",
                $conf['db_user'],
                $conf['db_pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("M-Pesa Database Connection Failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * Validate M-Pesa configuration
 */
function validateMpesaConfig($conf) {
    if (!isset($conf['mpesa'])) {
        throw new Exception("M-Pesa configuration not found");
    }
    
    $required_keys = ['consumer_key', 'consumer_secret', 'environment', 'short_code', 'passkey'];
    
    foreach ($required_keys as $key) {
        if (!isset($conf['mpesa'][$key]) || empty($conf['mpesa'][$key])) {
            throw new Exception("M-Pesa configuration missing: {$key}");
        }
    }
    
    return true;
}

/**
 * Log M-Pesa operations
 */
function logMpesaOperation($operation, $data = [], $level = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] M-Pesa {$operation}: " . json_encode($data);
    
    switch ($level) {
        case 'error':
            error_log($log_entry);
            break;
        case 'info':
        default:
            error_log($log_entry);
            break;
    }
}
?>