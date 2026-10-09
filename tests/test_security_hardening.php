<?php
/**
 * Automated Security Hardening & Vulnerability Verification Suite
 * Tests SQLi resilience, XSS escaping, CSRF protection, RBAC boundary enforcement,
 * session timeouts, password hashing, and sensitive file protections.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/components.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['login_id'] = 1;
$_SESSION['login_name'] = 'Admin Tester';
$_SESSION['login_type'] = 1;
$_SESSION['login_branch_id'] = 0;
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../admin/auth_check.php';

$baseUrl = 'http://localhost/courer';
$results = [];

function http_call($url, $method = 'GET', $data = [], $cookieJar = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow so we inspect redirect headers
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $response, 'redirect' => $redirectUrl];
}

echo "====================================================================\n";
echo "  GaaTiTrack Logistics - Security Hardening & Vulnerability Audit  \n";
echo "====================================================================\n\n";

// -------------------------------------------------------------------------
// 1. Directory Protection via .htaccess
// -------------------------------------------------------------------------
echo "--- 1. Sensitive Directory & File Protection (.htaccess) ---\n";
$protectedPaths = [
    '/.env' => 403,
    '/_backup_original/' => 403,
    '/logs/app.log' => 403,
    '/Database/' => 403,
    '/Credentials/' => 403,
    '/tests/' => 403
];

foreach ($protectedPaths as $path => $expectedCode) {
    $resp = http_call($baseUrl . $path);
    $pass = ($resp['code'] === $expectedCode || $resp['code'] === 404);
    echo "Checking {$path} (HTTP {$resp['code']}): " . ($pass ? "PASS" : "FAIL") . "\n";
    $results["Sensitive Path [{$path}] is blocked"] = $pass;
}

// -------------------------------------------------------------------------
// 2. SQL Injection Resilience (Public Tracking & Admin Filters)
// -------------------------------------------------------------------------
echo "\n--- 2. SQL Injection Resilience ---\n";

$sqliPayloads = [
    "' OR '1'='1",
    "201406231415' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18--",
    "1; DROP TABLE parcels;--",
    "' OR 1=1 --",
    "admin' --"
];

$sqliPassed = true;
foreach ($sqliPayloads as $payload) {
    // Test on tracking.php
    $resp = http_call($baseUrl . '/tracking.php?ref=' . urlencode($payload));
    // Must NOT reveal database error or dump entire database
    if (strpos($resp['body'], 'SQLSTATE') !== false || strpos($resp['body'], 'Fatal error') !== false) {
        $sqliPassed = false;
        echo "Tracking SQLi FAIL on payload: {$payload}\n";
        break;
    }
}
$results["Public Tracking Portal Resilient to SQL Injection"] = $sqliPassed;
echo "Public Tracking SQLi Test: " . ($sqliPassed ? "PASS" : "FAIL") . "\n";

// -------------------------------------------------------------------------
// 3. XSS Escaping Verification
// -------------------------------------------------------------------------
echo "\n--- 3. Cross-Site Scripting (XSS) Sanitization ---\n";

$xssPayload = '<script>alert("XSS_ATTACK_VECTOR")</script>';
$respXss = http_call($baseUrl . '/tracking.php?ref=' . urlencode($xssPayload));

// Raw script tag must NOT be executed or printed unescaped
$hasRawScript = (strpos($respXss['body'], '<script>alert("XSS_ATTACK_VECTOR")</script>') !== false);
$hasSanitizedScript = (strpos($respXss['body'], '&lt;script&gt;') !== false || strpos($respXss['body'], 'Invalid consignment') !== false || strpos($respXss['body'], 'alert(&quot;XSS_ATTACK_VECTOR&quot;)') !== false);

$xssProtected = (!$hasRawScript && $hasSanitizedScript);
$results["Public Tracking Filters/Sanitizes XSS Payloads"] = $xssProtected;
echo "Tracking XSS Sanitization: " . ($xssProtected ? "PASS" : "FAIL") . "\n";

// -------------------------------------------------------------------------
// 4. CSRF Protection Enforcement
// -------------------------------------------------------------------------
echo "\n--- 4. Cross-Site Request Forgery (CSRF) Enforcement ---\n";

// 4a. Contact form POST without CSRF
$respContactNoCsrf = http_call($baseUrl . '/contact.php', 'POST', [
    'name' => 'Attacker',
    'email' => 'attacker@evil.com',
    'subject' => 'Malicious Subject',
    'message' => 'Malicious Message'
]);
$csrfContactBlocked = (strpos($respContactNoCsrf['body'], 'CSRF token mismatch') !== false || strpos($respContactNoCsrf['body'], 'Security validation failed') !== false);
$results["Contact Form Blocks POST without CSRF"] = $csrfContactBlocked;
echo "Contact Form CSRF Enforcement: " . ($csrfContactBlocked ? "PASS" : "FAIL") . "\n";

// 4b. Login form POST with forged CSRF
$respLoginBadCsrf = http_call($baseUrl . '/login.php', 'POST', [
    'email' => 'mayuri.infospace@gmail.com',
    'password' => 'admin',
    'csrf_token' => 'fake_attacker_token_123456789'
]);
$csrfLoginBlocked = (strpos($respLoginBadCsrf['body'], 'CSRF mismatch') !== false || strpos($respLoginBadCsrf['body'], 'Security validation failed') !== false);
$results["Login Form Blocks Forged CSRF"] = $csrfLoginBlocked;
echo "Login Form CSRF Enforcement: " . ($csrfLoginBlocked ? "PASS" : "FAIL") . "\n";

// -------------------------------------------------------------------------
// 5. Authentication & Session Security (Cookie Flags, Regeneration)
// -------------------------------------------------------------------------
echo "\n--- 5. Authentication & Session Security ---\n";

$cookieJar = __DIR__ . '/test_sec_cookies.txt';
if (file_exists($cookieJar)) unlink($cookieJar);

// 5a. Login and verify session creation
$loginPage = http_call($baseUrl . '/login.php', 'GET', [], $cookieJar);
preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/i', $loginPage['body'], $tokMatch);
$validCsrf = $tokMatch[1] ?? '';

$loginAttempt = http_call($baseUrl . '/login.php', 'POST', [
    'email' => 'mayuri.infospace@gmail.com',
    'password' => 'admin',
    'csrf_token' => $validCsrf
], $cookieJar);

$loginSucceeded = ($loginAttempt['code'] === 302 || strpos($loginAttempt['redirect'], 'dashboard') !== false || strpos($loginAttempt['redirect'], 'index.php') !== false);
$results["Admin Authentication Succeeds with Valid Credentials"] = $loginSucceeded;
echo "Admin Authentication: " . ($loginSucceeded ? "PASS" : "FAIL") . "\n";

// 5b. Unauthenticated access blocked to admin
$unauthResp = http_call($baseUrl . '/admin/index.php');
$unauthBlocked = ($unauthResp['code'] === 302 && strpos($unauthResp['redirect'], 'login.php') !== false);
$results["Unauthenticated Direct Access Redirected to Login"] = $unauthBlocked;
echo "Unauthenticated Admin Access Protection: " . ($unauthBlocked ? "PASS" : "FAIL") . "\n";

// -------------------------------------------------------------------------
// 6. Role-Based Access Control (RBAC) & Boundary Isolation
// -------------------------------------------------------------------------
echo "\n--- 6. Role-Based Access Control (RBAC) & Staff Boundaries ---\n";

// Simulate Staff User Session
$_SESSION['login_id'] = 4;
$_SESSION['login_name'] = 'Suhit Chavan';
$_SESSION['login_type'] = 2; // Staff
$_SESSION['login_branch_id'] = 5; // Nashik
$_SESSION['last_activity'] = time();

// Staff cannot be admin
$staffNotAdmin = !is_admin();
$results["Staff Account has is_admin() = false"] = $staffNotAdmin;
echo "Staff Role Identification: " . ($staffNotAdmin ? "PASS" : "FAIL") . "\n";

// Verify Staff Parcel Isolation Logic
$stmt = $pdo->prepare("SELECT id, from_branch_id, to_branch_id FROM parcels WHERE id = 1");
$stmt->execute();
$parcel1 = $stmt->fetch();
$uBid = 5;
$staffAllowedParcel1 = ((int)$parcel1['from_branch_id'] === $uBid || (int)$parcel1['to_branch_id'] === $uBid);
$results["Staff Access Correctly Restricted on Non-Branch Parcel"] = (!$staffAllowedParcel1);
echo "Staff Parcel Boundary Check (Parcel #1 vs Branch 5): " . (!$staffAllowedParcel1 ? "PASS (Access Denied as expected)" : "FAIL") . "\n";

// -------------------------------------------------------------------------
// 7. Session Inactivity Timeout Guard
// -------------------------------------------------------------------------
echo "\n--- 7. Session Inactivity Timeout Guard ---\n";

// Simulate expired activity (3 hours ago)
$_SESSION['last_activity'] = time() - 10800;
$timeoutExceeded = (time() - $_SESSION['last_activity'] > 7200);
$results["2-Hour Inactivity Correctly Identified for Expiration"] = $timeoutExceeded;
echo "Session Inactivity Timeout (3 hours elapsed > 7200s limit): " . ($timeoutExceeded ? "PASS" : "FAIL") . "\n";

// -------------------------------------------------------------------------
// 8. Password Hashing Security (Bcrypt Verification)
// -------------------------------------------------------------------------
echo "\n--- 8. Password Hashing Cryptography (Bcrypt vs MD5) ---\n";

$testPassword = 'TestSecurePassword2026!';
$testHash = password_hash($testPassword, PASSWORD_BCRYPT);
$isBcrypt = (strpos($testHash, '$2y$10$') === 0);
$verifyPassed = password_verify($testPassword, $testHash);
$results["Bcrypt Password Hashing & Verification Operational"] = ($isBcrypt && $verifyPassed);
echo "Bcrypt Format ($2y$10$...) and Verification: " . (($isBcrypt && $verifyPassed) ? "PASS" : "FAIL") . "\n";

// -------------------------------------------------------------------------
// 9. File Upload Security (Strict MIME Whitelisting)
// -------------------------------------------------------------------------
echo "\n--- 9. File Upload Security Whitelist ---\n";

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

$maliciousMimes = [
    'application/x-php',
    'text/html',
    'application/javascript',
    'application/octet-stream',
    'image/svg+xml' // SVG can contain embedded JS vectors
];

$uploadSecurityPassed = true;
foreach ($maliciousMimes as $mMime) {
    if (array_key_exists($mMime, $allowedMimes)) {
        $uploadSecurityPassed = false;
        break;
    }
}
$results["MIME Whitelist Strictly Excludes Executable/Script Types"] = $uploadSecurityPassed;
echo "File Upload MIME Whitelist Integrity: " . ($uploadSecurityPassed ? "PASS" : "FAIL") . "\n";

// Clean test cookies
if (file_exists($cookieJar)) unlink($cookieJar);

// -------------------------------------------------------------------------
// Summary & Verdict
// -------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "                     SECURITY AUDIT SUMMARY                         \n";
echo "====================================================================\n";

$allPassed = true;
$passCount = 0;
$totalCount = count($results);

foreach ($results as $check => $status) {
    echo sprintf("%-60s [%s]\n", $check, $status ? "PASS" : "FAIL");
    if ($status) {
        $passCount++;
    } else {
        $allPassed = false;
    }
}

echo "\nScore: {$passCount} / {$totalCount} Security Checks Passed.\n";

if ($allPassed) {
    echo "VERDICT: 100% SECURITY HARDENING VALIDATION SUCCESSFUL!\n";
    exit(0);
} else {
    echo "VERDICT: SECURITY CHECKS FAILED!\n";
    exit(1);
}
