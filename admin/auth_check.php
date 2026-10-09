<?php
/**
 * GaaTiTrack Admin - Authentication Guard & Session Enforcement
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/components.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// 1. Enforce Authentication
if (!isset($_SESSION['login_id'])) {
    header("Location: " . APP_URL . "/login.php");
    exit;
}

// 2. Enforce Session Inactivity Timeout (2 Hours)
$timeout = 7200;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: " . APP_URL . "/login.php?logged_out=timeout");
    exit;
}
$_SESSION['last_activity'] = time();

// 3. Current User Helper Functions
function current_user_id() {
    return (int)($_SESSION['login_id'] ?? 0);
}

function is_admin() {
    return ((int)($_SESSION['login_type'] ?? 0)) === 1;
}

function user_branch_id() {
    return (int)($_SESSION['login_branch_id'] ?? 0);
}

function require_admin() {
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Access denied. Administrator privileges required.';
        header("Location: " . APP_URL . "/admin/index.php?page=dashboard");
        exit;
    }
}
