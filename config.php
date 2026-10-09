<?php
/**
 * GaaTiTrack Modernization - Core Application Configuration
 */

// Prevent multiple inclusions
if (defined('GAATITRACK_CONFIG_LOADED')) {
    return;
}
define('GAATITRACK_CONFIG_LOADED', true);

// 1. Load .env file
(function() {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                // Remove surrounding quotes
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
})();

// Helper to get environment variables
if (!function_exists('env')) {
    function env($key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        if ($val === 'true' || $val === '(true)') return true;
        if ($val === 'false' || $val === '(false)') return false;
        if ($val === 'null' || $val === '(null)') return null;
        return $val;
    }
}

// 2. Application Constants
define('APP_NAME', env('APP_NAME', 'GaaTiTrack'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', false));
define('APP_URL', rtrim(env('APP_URL', 'http://localhost/courer'), '/'));

// 3. Timezone
date_default_timezone_set(env('APP_TIMEZONE', 'America/New_York'));

// 4. Safe Error Logging (Zero sensitive data exposed to visitor)
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/app.log');

if (APP_DEBUG && APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// 5. Application Logger
if (!function_exists('app_log')) {
    function app_log($message, $level = 'INFO') {
        $logFile = __DIR__ . '/logs/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $entry = sprintf("[%s] [%s] %s%s", $timestamp, strtoupper($level), $message, PHP_EOL);
        @file_put_contents($logFile, $entry, FILE_APPEND);
    }
}

// 6. Safe Output Escaping Helper
if (!function_exists('e')) {
    function e($val) {
        return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// 7. CSRF Helpers
if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    function generate_csrf_token() {
        return csrf_token();
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
