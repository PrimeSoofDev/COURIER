<?php
/**
 * Automated Verification Script for Phase 7 (Tracking Portal)
 */

$baseUrl = 'http://localhost/courer/tracking.php';

function fetchPage($url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    return file_get_contents($url, false, $ctx);
}

$results = [];

// Test 1: Valid Delivered Shipment
$html1 = fetchPage($baseUrl . '?ref=201406231415');
$hasRef = strpos($html1, '201406231415') !== false;
$hasDelivered = strpos($html1, 'Delivered') !== false;
$hasTimeline = strpos($html1, 'Transit Milestone History') !== false;
$noPhoneLeak = strpos($html1, '+123456') === false; // Recipient phone must NOT be leaked
$results['Valid Delivered Parcel (201406231415)'] = ($hasRef && $hasDelivered && $hasTimeline && $noPhoneLeak) ? 'PASS' : 'FAIL';

// Test 2: Valid Shipped Parcel
$html2 = fetchPage($baseUrl . '?ref=983186540795');
$hasRef2 = strpos($html2, '983186540795') !== false;
$hasShipped = strpos($html2, 'Shipped') !== false;
$results['Valid Shipped Parcel (983186540795)'] = ($hasRef2 && $hasShipped) ? 'PASS' : 'FAIL';

// Test 3: Unknown Tracking Number
$html3 = fetchPage($baseUrl . '?ref=UNKNOWN999999');
$hasNotFound = strpos($html3, 'No shipment found') !== false;
$results['Unknown Tracking Number Handling'] = $hasNotFound ? 'PASS' : 'FAIL';

// Test 4: Malformed / SQL Injection Attempt
$html4 = fetchPage($baseUrl . '?ref=' . urlencode("' OR 1=1--"));
$hasFormatError = strpos($html4, 'Invalid tracking reference format') !== false;
$results['Malformed / SQLi Input Sanitization'] = $hasFormatError ? 'PASS' : 'FAIL';

// Test 5: Default Empty State
$html5 = fetchPage($baseUrl);
$hasPrompt = strpos($html5, 'Enter a Tracking Number Above') !== false;
$results['Default Empty Prompt State'] = $hasPrompt ? 'PASS' : 'FAIL';

echo "=== PHASE 7 TRACKING TEST SUITE RESULTS ===" . PHP_EOL;
foreach ($results as $test => $status) {
    echo sprintf("[%s] %s%s", $status, $test, PHP_EOL);
}
