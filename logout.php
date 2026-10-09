<?php
/**
 * GaaTiTrack - Secure Logout Handler
 */

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

app_log("User logged out: " . ($_SESSION['login_email'] ?? 'Unknown'), 'INFO');

// Clear all session variables
$_SESSION = [];

// Invalidate session cookie
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

// Destroy session
session_destroy();

// Redirect to login with status indicator
header("Location: " . APP_URL . "/login.php?logged_out=1");
exit;
