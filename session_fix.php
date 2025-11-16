<?php
/**
 * Session Fix Helper - Set verification status
 */

session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data && $data['action'] === 'set_verified') {
        $_SESSION['verified'] = true;
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification status set',
            'user_id' => $_SESSION['user_id']
        ]);
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>