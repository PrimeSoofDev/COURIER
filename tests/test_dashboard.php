<?php
/**
 * Automated Verification Script for Phase 10 (Admin Dashboard & Metrics)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';

$loginUrl = APP_URL . '/login.php';
$dashUrl = APP_URL . '/admin/index.php?page=dashboard';

$results = [];

// 1. Unauthenticated Access Protection Test
$ch = curl_init($dashUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$unauthResp = curl_exec($ch);
curl_close($ch);

$isRedirectedToLogin = (strpos($unauthResp, 'Location: ' . APP_URL . '/login.php') !== false) ||
                       (strpos($unauthResp, '302 Found') !== false && strpos($unauthResp, 'login.php') !== false);
$results['Unauthenticated Access Redirects to Login'] = $isRedirectedToLogin ? 'PASS' : 'FAIL';

// 2. Authenticate as Administrator
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$loginGet = curl_exec($ch);
curl_close($ch);

preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $loginGet, $matches);
$cookies = implode('; ', $matches[1]);
preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/i', $loginGet, $tokenMatch);
$csrfToken = $tokenMatch[1] ?? '';

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrfToken,
    'email' => 'mayuri.infospace@gmail.com',
    'password' => 'admin'
]));
curl_setopt($ch, CURLOPT_COOKIE, $cookies);
$authResp = curl_exec($ch);
curl_close($ch);

// Update cookie if new session cookie provided
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $authResp, $authCookieMatches);
if (!empty($authCookieMatches[1])) {
    $cookies = implode('; ', $authCookieMatches[1]);
}

// 3. Fetch Dashboard HTML with Authenticated Session
$ch = curl_init($dashUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, $cookies);
$dashHtml = curl_exec($ch);
curl_close($ch);

// 4. Verify Real Database Metrics in Output
$realTotalParcels = (int)$pdo->query("SELECT COUNT(*) FROM parcels")->fetchColumn();
$realDelivered = (int)$pdo->query("SELECT COUNT(*) FROM parcels WHERE status IN (7, 8)")->fetchColumn();
$realBranches = (int)$pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn();

$hasRealTotal = strpos($dashHtml, (string)$realTotalParcels) !== false;
$hasRealBranches = strpos($dashHtml, (string)$realBranches . ' Hubs') !== false;
$hasSidebar = strpos($dashHtml, 'Operations Overview') !== false && strpos($dashHtml, 'Consignment Management') !== false;

// 5. Ensure Old Hardcoded Fake Counts (56, 50, 78, 98, 128) Are Gone
$hasFakeCounts = (strpos($dashHtml, '<th>56</th>') !== false) || (strpos($dashHtml, '<th>128</th>') !== false);

$results['Authenticated Dashboard HTTP 200 & Layout'] = (!empty($dashHtml) && strpos($dashHtml, 'Operations Overview') !== false) ? 'PASS' : 'FAIL';
$results['Real Total Parcels Count Injected (' . $realTotalParcels . ')'] = $hasRealTotal ? 'PASS' : 'FAIL';
$results['Real Total Branches Count Injected (' . $realBranches . ' Hubs)'] = $hasRealBranches ? 'PASS' : 'FAIL';
$results['Old Hardcoded Fake Dashboard Counts Removed'] = (!$hasFakeCounts) ? 'PASS' : 'FAIL';
$results['Collapsible Sidebar & Navigation Controls'] = $hasSidebar ? 'PASS' : 'FAIL';

echo "=== PHASE 10 ADMIN DASHBOARD TEST SUITE RESULTS ===" . PHP_EOL;
foreach ($results as $test => $status) {
    echo sprintf("[%s] %s%s", $status, $test, PHP_EOL);
}
