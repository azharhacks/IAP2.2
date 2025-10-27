<?php
/**
 * User Logout Handler
 * Securely terminates user session and clears all authentication data
 * Implements proper session cleanup to prevent session fixation attacks
 */

// Initialize session to access current session data
session_start();

// Regenerate session ID to prevent session fixation attacks
// This creates a new session ID and invalidates the old one
session_regenerate_id(true);

// Clear all session variables to remove user data
$_SESSION = array();

// Securely delete the session cookie from client browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',                    // Empty value
        time() - 42000,        // Expire in the past
        $params["path"],       // Same path as original cookie
        $params["domain"],     // Same domain as original cookie
        $params["secure"],     // Same security setting
        $params["httponly"]    // Same httponly setting
    );
}

// Completely destroy the session data
session_destroy();

// Clear any existing output buffer to prevent header issues
if (ob_get_length()) {
    ob_clean();
}

// Redirect user to signin page with logout confirmation
header('Location: ' . SITE_URL . '/Signin.php?logout=success');
exit();