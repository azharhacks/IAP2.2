<?php
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

// Finally, destroy the session
session_destroy();

// Clear any existing output buffer
if (ob_get_length()) {
    ob_clean();
}

// Redirect to signin page with a success message
header('Location: ' . SITE_URL . '/Signin.php?logout=success');
exit();