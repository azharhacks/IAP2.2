<?php
require_once __DIR__ . '/EmailService.php';

/**
 * UserRegistration class - Handles user registration logic
 * OOP Principles: Single Responsibility, Encapsulation, Dependency Injection
 */
class UserRegistration {
    private $pdo; // OOP: Private property (Encapsulation)
    private $emailService; // OOP: Dependency injection
    
    // OOP: Constructor with dependency injection
    public function __construct($database, EmailService $emailService) {
        $this->pdo = $database; // OOP: Property assignment
        $this->emailService = $emailService; // OOP: Dependency injection
    }
    
    // OOP: Public method - main registration interface
    public function registerUser($email, $password) {
        // Validate input
        $validation = $this->validateInput($email, $password); // OOP: Method call
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }
        
        // Check if email already exists
        if ($this->emailExists($email)) { // OOP: Method call
            return ['success' => false, 'message' => 'Email already registered.'];
        }
        
        // Create user account
        $userData = $this->prepareUserData($email, $password); // OOP: Method call
        $userId = $this->saveUser($userData); // OOP: Method call
        
        if (!$userId) {
            return ['success' => false, 'message' => 'Registration failed.'];
        }
        
        // Send verification email
        $emailResult = $this->emailService->sendVerificationEmail($email, $userData['token']); // OOP: Method call
        
        if (!$emailResult['success']) {
            // If email fails, remove the user (rollback)
            $this->removeUser($userId); // OOP: Method call
            return ['success' => false, 'message' => $emailResult['message']];
        }
        
        return ['success' => true, 'message' => 'Registration successful! Check your email to verify.'];
    }
    
    // OOP: Private method (Encapsulation) - input validation
    private function validateInput($email, $password) {
        if (empty($email) || empty($password)) {
            return ['valid' => false, 'message' => 'All fields are required.'];
        }
        
        if (strlen($password) < 6) {
            return ['valid' => false, 'message' => 'Password must be at least 6 characters.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Please enter a valid email address.'];
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    // OOP: Private method (Encapsulation) - check email existence
    private function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?"); // OOP: Method call
        $stmt->execute([$email]); // OOP: Method call
        return $stmt->rowCount() > 0; // OOP: Method call
    }
    
    // OOP: Private method (Encapsulation) - prepare user data
    private function prepareUserData($email, $password) {
        return [
            'email' => trim($email),
            'password' => password_hash($password, PASSWORD_DEFAULT), // Hash password for security
            'token' => bin2hex(random_bytes(32)) // Generate secure token
        ];
    }
    
    // OOP: Private method (Encapsulation) - save user to database
    private function saveUser($userData) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (email, password, verification_token) VALUES (?, ?, ?)");
            $stmt->execute([$userData['email'], $userData['password'], $userData['token']]);
            return $this->pdo->lastInsertId(); // Return the new user ID
        } catch (Exception $e) {
            return false;
        }
    }
    
    // OOP: Private method (Encapsulation) - remove user (cleanup)
    private function removeUser($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
    }
}
?>
