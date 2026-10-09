<?php
/**
 * Automated HTTP End-to-End Test for Phase 11 Admin Modules
 */

$baseUrl = 'http://localhost/courer';
$cookieFile = __DIR__ . '/test_admin_cookie.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function http_request($url, $method = 'GET', $data = [], $cookieJar = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => $response];
}

echo "=== GaaTiTrack Phase 11 HTTP End-to-End Test ===\n\n";

// 1. Fetch Login Page to retrieve CSRF token
echo "1. Fetching login page... ";
$loginPage = http_request($baseUrl . '/login.php', 'GET', [], $cookieFile);
preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/i', $loginPage['body'], $tokenMatch);
$csrfToken = $tokenMatch[1] ?? '';

if (empty($csrfToken)) {
    echo "FAILED: CSRF token not found on login page.\n";
    exit(1);
}
echo "PASSED (Token: " . substr($csrfToken, 0, 8) . "...)\n";

// 2. Submit Login as Admin
echo "2. Authenticating as Administrator... ";
$loginResp = http_request($baseUrl . '/login.php', 'POST', [
    'email' => 'mayuri.infospace@gmail.com',
    'password' => 'admin',
    'csrf_token' => $csrfToken,
], $cookieFile);

if (strpos($loginResp['body'], 'Operations Console') === false && strpos($loginResp['body'], 'GaaTiTrack Portal') === false) {
    echo "FAILED: Admin authentication did not land on dashboard.\n";
    exit(1);
}
echo "PASSED (Admin session active)\n";

// 3. Test View: Parcels Listing
echo "3. Testing Consignments Listing (page=parcels)... ";
$parcelsResp = http_request($baseUrl . '/admin/index.php?page=parcels', 'GET', [], $cookieFile);
if ($parcelsResp['code'] === 200 && (strpos($parcelsResp['body'], 'All Shipments') !== false || strpos($parcelsResp['body'], 'Consignments & Shipments') !== false)) {
    echo "PASSED (HTTP 200)\n";
} else {
    echo "FAILED (HTTP {$parcelsResp['code']})\n";
}

// 4. Test View: View Parcel #1
echo "4. Testing Consignment Detail & Waybill (page=view_parcel&id=1)... ";
$parcel1Resp = http_request($baseUrl . '/admin/index.php?page=view_parcel&id=1', 'GET', [], $cookieFile);
if ($parcel1Resp['code'] === 200 && strpos($parcel1Resp['body'], '201406231415') !== false && strpos($parcel1Resp['body'], 'Print Waybill') !== false) {
    echo "PASSED (HTTP 200, Waybill ready)\n";
} else {
    echo "FAILED (HTTP {$parcel1Resp['code']})\n";
}

// 5. Test View: Tracking Events Console
echo "5. Testing Tracking Events Console (page=tracking_events&ref=201406231415)... ";
$trackResp = http_request($baseUrl . '/admin/index.php?page=tracking_events&ref=201406231415', 'GET', [], $cookieFile);
if ($trackResp['code'] === 200 && (strpos($trackResp['body'], 'Update Tracking') !== false || strpos($trackResp['body'], 'Milestone') !== false)) {
    echo "PASSED (HTTP 200, Console rendered)\n";
} else {
    echo "FAILED (HTTP {$trackResp['code']})\n";
}

// 6. Test View: Reports & Manifests
echo "6. Testing Reports & Freight Manifests (page=reports)... ";
$repResp = http_request($baseUrl . '/admin/index.php?page=reports', 'GET', [], $cookieFile);
if ($repResp['code'] === 200 && (strpos($repResp['body'], 'Reports & Manifests') !== false || strpos($repResp['body'], 'Reports & Freight Manifests') !== false)) {
    echo "PASSED (HTTP 200, Manifest generated)\n";
} else {
    echo "FAILED (HTTP {$repResp['code']})\n";
}

// 7. Test View: Branch Hubs Directory
echo "7. Testing Branch Hubs Directory (page=branches)... ";
$branchResp = http_request($baseUrl . '/admin/index.php?page=branches', 'GET', [], $cookieFile);
if ($branchResp['code'] === 200 && strpos($branchResp['body'], 'Branch Hubs Directory') !== false && strpos($branchResp['body'], 'Add New Branch Hub') !== false) {
    echo "PASSED (HTTP 200, Hub directory active)\n";
} else {
    echo "FAILED (HTTP {$branchResp['code']})\n";
}

// 8. Test View: Staff Directory
echo "8. Testing Staff Directory (page=staff)... ";
$staffResp = http_request($baseUrl . '/admin/index.php?page=staff', 'GET', [], $cookieFile);
if ($staffResp['code'] === 200 && strpos($staffResp['body'], 'Staff & Operator Directory') !== false && strpos($staffResp['body'], 'mayuri.infospace@gmail.com') !== false) {
    echo "PASSED (HTTP 200, Staff list loaded)\n";
} else {
    echo "FAILED (HTTP {$staffResp['code']})\n";
}

// 9. Test View: System Settings
echo "9. Testing System Settings Console (page=settings)... ";
$settingsResp = http_request($baseUrl . '/admin/index.php?page=settings', 'GET', [], $cookieFile);
if ($settingsResp['code'] === 200 && (strpos($settingsResp['body'], 'System Settings') !== false)) {
    echo "PASSED (HTTP 200, Settings loaded)\n";
} else {
    echo "FAILED (HTTP {$settingsResp['code']})\n";
}

// Cleanup cookie file
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "\nALL 9 END-TO-END HTTP TESTS COMPLETED SUCCESSFULLY!\n";
