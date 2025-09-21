<?php
// Include configuration and dependencies
require_once 'Config.php';
require_once 'db_connect.php';

// OOP: Include our custom classes
require_once 'Forms/EmailService.php';
require_once 'Forms/UserRegistration.php';
require_once 'Forms/Layout.php';

// Initialize variables
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // OOP: Dependency injection - create services
    $emailService = new EmailService(); // OOP: Object instantiation
    $userRegistration = new UserRegistration($pdo, $emailService); // OOP: Constructor with dependencies
    
    // OOP: Method call - handle registration
    $result = $userRegistration->registerUser($email, $password);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// OOP: Static method call - render the signup form
echo FormRenderer::renderSignupForm($error, $success);
?>
