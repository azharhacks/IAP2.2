<?php
/**
 * User Logout Handler
 * Securely logs out users by destroying session and clearing cookies
 */

// Include configuration
require_once __DIR__ . '/config.php';

// Start the session
session_start();

// Regenerate session ID before destroying
session_regenerate_id(true);

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Also clear any remember me cookies if they exist
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Finally, destroy the session
session_destroy();

// Clear any existing output buffer
if (ob_get_length()) {
    ob_clean();
}

// Redirect to signin page with a success message
header('Location: ' . $conf['site_url'] . '/Signin.php?logout=success');
exit();