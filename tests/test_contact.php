<?php
/**
 * Automated Verification Script for Phase 8 (Contact Page)
 */

$baseUrl = 'http://localhost/courer/contact.php';

// Helper to extract CSRF token and cookies
function getFormContext($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    // Extract cookie
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
    $cookies = implode('; ', $matches[1]);

    // Extract CSRF token
    preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/i', $response, $tokenMatch);
    $csrfToken = $tokenMatch[1] ?? '';

    return ['cookies' => $cookies, 'csrf_token' => $csrfToken, 'html' => $response];
}

function postForm($url, $data, $cookies = '') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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

// 1. Initial GET Request Test
$ctx = getFormContext($baseUrl);
$results['GET Contact Page Returns 200 with CSRF Token'] = (!empty($ctx['csrf_token'])) ? 'PASS' : 'FAIL';

// 2. Valid Form Submission Test
$validData = [
    'csrf_token' => $ctx['csrf_token'],
    'website_hp' => '', // Empty honeypot
    'name' => 'Automated Test User',
    'email' => 'testuser@example.com',
    'subject' => 'Freight Rate Inquiry',
    'message' => 'This is an automated test message regarding freight dispatch services.'
];
$respValid = postForm($baseUrl, $validData, $ctx['cookies']);
$hasSuccess = strpos($respValid, 'Inquiry Submitted') !== false || strpos($respValid, 'Your message has been received') !== false;
$results['Valid Form Submission with CSRF & Honeypot'] = $hasSuccess ? 'PASS' : 'FAIL';

// 3. Invalid CSRF Token Test
$invalidCsrfData = $validData;
$invalidCsrfData['csrf_token'] = 'bad_token_12345';
$respBadCsrf = postForm($baseUrl, $invalidCsrfData, $ctx['cookies']);
$hasCsrfError = strpos($respBadCsrf, 'CSRF token mismatch') !== false;
$results['CSRF Protection Blocks Forged Post'] = $hasCsrfError ? 'PASS' : 'FAIL';

// 4. Honeypot Bot Trap Test
$botData = $validData;
$botData['website_hp'] = 'http://spam-bot.com';
$respBot = postForm($baseUrl, $botData, $ctx['cookies']);
$hasSpamError = strpos($respBot, 'Spam submission detected') !== false;
$results['Honeypot Trap Blocks Spam Bot'] = $hasSpamError ? 'PASS' : 'FAIL';

// 5. Invalid Email Address Test
$badEmailData = $validData;
$badEmailData['email'] = 'invalid-email-address';
$respBadEmail = postForm($baseUrl, $badEmailData, $ctx['cookies']);
$hasEmailError = strpos($respBadEmail, 'valid email address') !== false;
$results['Email Validation Rejects Malformed Email'] = $hasEmailError ? 'PASS' : 'FAIL';

// 6. Header Injection Defense Test
$injectionData = $validData;
$injectionData['email'] = "test@example.com\r\nBcc:spam@attacker.com";
$respInject = postForm($baseUrl, $injectionData, $ctx['cookies']);
$hasInjectBlock = strpos($respInject, 'Invalid characters detected') !== false;
$results['Email Header Injection Defense'] = $hasInjectBlock ? 'PASS' : 'FAIL';

echo "=== PHASE 8 CONTACT PAGE TEST SUITE RESULTS ===" . PHP_EOL;
foreach ($results as $test => $status) {
    echo sprintf("[%s] %s%s", $status, $test, PHP_EOL);
}
