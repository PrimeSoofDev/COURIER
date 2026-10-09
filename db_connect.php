<?php
/**
 * Database Connection Handler
 * Provides safe environment-configured MySQLi ($conn) and PDO ($pdo) connections.
 */

require_once __DIR__ . '/config.php';

$db_host = env('DB_HOST', 'localhost');
$db_port = (int)env('DB_PORT', 3306);
$db_user = env('DB_USER', 'root');
$db_pass = env('DB_PASS', '');
$db_name = env('DB_NAME', 'gaatitrack');

// 1. Backward-compatible MySQLi instance
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    app_log("MySQLi Connection Failed: " . $conn->connect_error, "ERROR");
    if (APP_DEBUG) {
        die("Database connection failed. Please verify your .env configuration.");
    } else {
        die("A system error occurred. Please contact the administrator.");
    }
}
$conn->set_charset("utf8mb4");

// 2. Modern PDO instance for secure prepared statement migrations
try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    app_log("PDO Connection Failed: " . $e->getMessage(), "ERROR");
    $pdo = null;
}
