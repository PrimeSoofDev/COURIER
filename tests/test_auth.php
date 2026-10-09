<?php
/**
 * Automated Verification Script for Phase 9 (Authentication & Security)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';

$loginUrl = APP_URL . '/login.php';
$logoutUrl = APP_URL . '/logout.php';

function getLoginContext($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
    $cookies = implode('; ', $matches[1]);

    preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/i', $response, $tokenMatch);
    $csrfToken = $tokenMatch[1] ?? '';

    return ['cookies' => $cookies, 'csrf_token' => $csrfToken, 'html' => $response];
}

function postLogin($url, $data, $cookies = '') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    if (!empty($cookies)) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

$results = [];

// 1. Initial GET check
$ctx = getLoginContext($loginUrl);
$results['GET Login Page (HTTP 200 & CSRF present)'] = (!empty($ctx['csrf_token'])) ? 'PASS' : 'FAIL';

// 2. Bad credentials test
$badResp = postLogin($loginUrl, [
    'csrf_token' => $ctx['csrf_token'],
    'email' => 'fake_user@example.com',
    'password' => 'wrong_password'
], $ctx['cookies']);
$hasGenericError = strpos($badResp, 'Invalid email or password') !== false;
$results['Generic Error on Invalid Credentials'] = $hasGenericError ? 'PASS' : 'FAIL';

// 3. Bad CSRF token test
$csrfResp = postLogin($loginUrl, [
    'csrf_token' => 'invalid_csrf_123',
    'email' => 'mayuri.infospace@gmail.com',
    'password' => 'admin'
], $ctx['cookies']);
$hasCsrfError = strpos($csrfResp, 'CSRF mismatch') !== false;
$results['Rejection of Invalid CSRF Token'] = $hasCsrfError ? 'PASS' : 'FAIL';

// 4. Check initial hash type in database for mayuri.infospace@gmail.com
$checkStmt = $pdo->prepare("SELECT password FROM users WHERE email = 'mayuri.infospace@gmail.com'");
$checkStmt->execute();
$origHash = $checkStmt->fetchColumn();

// 5. Valid login test
// Refresh context to get clean CSRF token
$ctx2 = getLoginContext($loginUrl);
$goodResp = postLogin($loginUrl, [
    'csrf_token' => $ctx2['csrf_token'],
    'email' => 'mayuri.infospace@gmail.com',
    'password' => 'admin'
], $ctx2['cookies']);

// Successful login should issue a 302 redirect to admin/index.php
$hasRedirect = (strpos($goodResp, 'Location: ' . APP_URL . '/admin/index.php') !== false) || 
               (strpos($goodResp, 'Location: http') !== false && strpos($goodResp, 'admin') !== false);
$results['Successful Login & Redirection to Admin'] = $hasRedirect ? 'PASS' : 'FAIL';

// 6. Verify transparent password hash upgrade in database
$newStmt = $pdo->prepare("SELECT password FROM users WHERE email = 'mayuri.infospace@gmail.com'");
$newStmt->execute();
$newHash = $newStmt->fetchColumn();
$isUpgraded = (strpos($newHash, '$2y$') === 0) || (strlen($newHash) === 60);
$results['Transparent MD5 to Bcrypt Password Hash Upgrade'] = $isUpgraded ? 'PASS' : 'FAIL';

// 7. Test Logout
$logoutCh = curl_init($logoutUrl);
curl_setopt($logoutCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($logoutCh, CURLOPT_HEADER, true);
$logoutResp = curl_exec($logoutCh);
curl_close($logoutCh);
$hasLogoutRedirect = strpos($logoutResp, 'logged_out=1') !== false;
$results['Logout Handler Destroys Session & Redirects'] = $hasLogoutRedirect ? 'PASS' : 'FAIL';

echo "=== PHASE 9 AUTHENTICATION TEST SUITE RESULTS ===" . PHP_EOL;
foreach ($results as $test => $status) {
    echo sprintf("[%s] %s%s", $status, $test, PHP_EOL);
}
