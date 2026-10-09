<?php
/**
 * GaaTiTrack - Phase 12: Live Shipment Map & Tracking Validation Test Suite
 * Mirrors bootstrap style of test_e2e_lifecycle.php (config.php + db_connect.php).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/geocoding.php';

$baseUrl = 'http://localhost/courer';
$passed  = 0;
$total   = 0;

function run_map_test(string $name, callable $fn): void {
    global $passed, $total;
    $total++;
    try {
        $result = $fn();
        if ($result === true) {
            echo "[PASS] Test {$total}: {$name}\n";
            $passed++;
        } else {
            echo "[FAIL] Test {$total}: {$name} — {$result}\n";
        }
    } catch (Throwable $e) {
        echo "[ERROR] Test {$total}: {$name} — " . $e->getMessage() . "\n";
    }
}

function do_http_get(string $url): array {
    $ctx  = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 10]]);
    $body = (string) @file_get_contents($url, false, $ctx);
    return ['body' => $body, 'json' => json_decode($body, true)];
}

echo "====================================================================\n";
echo "  GAATITRACK PHASE 12: LIVE SHIPMENT MAP & TRACKING VALIDATION       \n";
echo "====================================================================\n\n";

// ---------------------------------------------------------------
// Case 1: Valid tracking number — full map payload returned
// ---------------------------------------------------------------
run_map_test("Valid tracking number returns full map payload (coords + waypoints + timeline)", function () use ($baseUrl) {
    $r = do_http_get("{$baseUrl}/api/tracking.php?ref=201406231415");
    $j = $r['json'];
    if (!($j['success'] ?? false))                        return "API returned success:false";
    if (empty($j['origin']['lat']))                       return "Missing origin lat";
    if (empty($j['destination']['lng']))                  return "Missing destination lng";
    if (!is_array($j['waypoints']) || count($j['waypoints']) < 10) return "Waypoints missing or too few";
    if (!is_array($j['timeline']))                        return "Timeline missing";
    return true;
});

// ---------------------------------------------------------------
// Case 2: Geocoding — verified city lookup returns correct coords
// ---------------------------------------------------------------
run_map_test("Known US city 'Chicago' resolves to verified IL coordinates", function () {
    $cities = get_verified_city_coordinates();
    if (!isset($cities['chicago']))         return "Chicago not in verified coordinate table";
    $c = $cities['chicago'];
    if (abs($c['lat'] - 41.8789) > 0.1)    return "Latitude out of expected range";
    if ($c['state'] !== 'IL')              return "State should be IL";
    return true;
});

// ---------------------------------------------------------------
// Case 3: Destination resolver falls back gracefully for unknown address
// ---------------------------------------------------------------
run_map_test("Unknown recipient address falls back to unverified default coords", function () {
    // No dest branch, completely unknown address
    $coords = resolve_destination_coordinates(null, "Unknown Road, Nowhere, ZZ 99999");
    if ($coords === null)          return "Resolver returned null unexpectedly";
    // Fallback should not be flagged verified (address unrecognised)
    if ($coords['verified'] === true) return "Verified should be false for unrecognised address";
    return true;
});

// ---------------------------------------------------------------
// Case 4: Shipment with multiple tracking events — chronological order
// ---------------------------------------------------------------
run_map_test("Timeline for known shipment is sorted newest-first and has >1 event", function () use ($baseUrl) {
    $r = do_http_get("{$baseUrl}/api/tracking.php?ref=201406231415");
    $j = $r['json'];
    $timeline = $j['timeline'] ?? [];
    if (count($timeline) < 2)  return "Expected multiple timeline events";
    $dates  = array_column($timeline, 'date');
    $sorted = $dates;
    rsort($sorted);
    if ($dates !== $sorted)    return "Timeline not in descending chronological order";
    return true;
});

// ---------------------------------------------------------------
// Case 5: Delivered shipment → milestone progress = 1.00
// ---------------------------------------------------------------
run_map_test("Status code 7 (Delivered) yields 100% progress from milestone resolver", function () {
    $progress = get_milestone_route_progress(7);
    if ($progress !== 1.00)  return "Expected 1.0 for Delivered, got {$progress}";
    return true;
});

// ---------------------------------------------------------------
// Case 6: Exceptional statuses resolve to non-zero progress
// ---------------------------------------------------------------
run_map_test("On-Hold (6) and Failed Attempt (9) status codes resolve to non-zero progress", function () {
    $hold   = get_milestone_route_progress(6);
    $failed = get_milestone_route_progress(9);
    if ($hold   <= 0) return "On-Hold status returned zero progress";
    if ($failed <= 0) return "Failed Attempt status returned zero progress";
    return true;
});

// ---------------------------------------------------------------
// Case 7: Unknown tracking reference → clean error JSON
// ---------------------------------------------------------------
run_map_test("Unknown tracking reference returns success:false without a crash", function () use ($baseUrl) {
    $r = do_http_get("{$baseUrl}/api/tracking.php?ref=ZZZUNKNOWN9999");
    $j = $r['json'];
    if ($j === null)              return "Response is not valid JSON";
    if ($j['success'] !== false)  return "Expected success:false for unknown ref";
    return true;
});

// ---------------------------------------------------------------
// Case 8: SQL injection attempt is rejected gracefully
// ---------------------------------------------------------------
run_map_test("SQL injection attempt returns safe error JSON without crashing", function () use ($baseUrl) {
    $payload = urlencode("'; DROP TABLE parcels; --");
    $r = do_http_get("{$baseUrl}/api/tracking.php?ref={$payload}");
    $j = $r['json'];
    if ($j === null)              return "Response is not valid JSON";
    if ($j['success'] !== false)  return "Expected success:false for injected ref";
    return true;
});

// ---------------------------------------------------------------
// Case 9: New parcel always gets an auto-computed ETA (creation + 3 days)
// ---------------------------------------------------------------
run_map_test("New parcel receives auto-computed ETA (creation date + 3 days) as valid date string", function () use ($baseUrl, $pdo) {
    $testRef = 'NOETA' . rand(10000, 99999);
    $stmt = $pdo->prepare(
        "INSERT INTO parcels (reference_number, sender_name, sender_address, sender_contact,
         recipient_name, recipient_address, recipient_contact, type, from_branch_id, to_branch_id,
         weight, height, width, length, price, status, date_created)
         VALUES (?, 'Test Shipper', '350 5th Ave, New York, NY 10118', '2125551234',
                 'Test Receiver', '233 S Wacker Dr, Chicago, IL 60606', '3125557890',
                 1, 4, 5, '2kg', 10, 10, 10, 25, 0, NOW())"
    );
    $stmt->execute([$testRef]);
    $newId = (int) $pdo->lastInsertId();

    try {
        $r = do_http_get("{$baseUrl}/api/tracking.php?ref={$testRef}");
        $j = $r['json'];
        if (!($j['success'] ?? false)) return "New parcel API call failed — check branch IDs 4/5 exist";

        $eta = $j['estimated_delivery'] ?? null;
        // API always auto-computes ETA (date_created + 3 days), so it should be a valid month-day-year string
        if (empty($eta)) return "estimated_delivery should be auto-computed, got empty/null";
        if (!preg_match('/^[A-Z][a-z]{2} \d{2}, \d{4}$/', $eta)) {
            return "estimated_delivery not in expected 'Mon DD, YYYY' format: {$eta}";
        }
        return true;
    } finally {
        $pdo->prepare("DELETE FROM parcels WHERE id = ?")->execute([$newId]);
    }
});

// ---------------------------------------------------------------
// Case 10: Local Leaflet vendor assets exist on disk
// ---------------------------------------------------------------
run_map_test("Local Leaflet CSS and JS files present (no CDN single-point-of-failure)", function () {
    $base = __DIR__ . '/../assets/plugins/leaflet';
    if (!file_exists("{$base}/leaflet.css"))         return "Missing leaflet.css";
    if (!file_exists("{$base}/leaflet.js"))          return "Missing leaflet.js";
    if (filesize("{$base}/leaflet.js") < 100000)     return "leaflet.js seems corrupt or too small";
    return true;
});

// ---------------------------------------------------------------
// Case 11: Repeated poll calls return consistent status
// ---------------------------------------------------------------
run_map_test("Two successive API polls return consistent and non-crashing responses", function () use ($baseUrl) {
    $j1 = do_http_get("{$baseUrl}/api/tracking.php?ref=201406231415")['json'];
    $j2 = do_http_get("{$baseUrl}/api/tracking.php?ref=201406231415")['json'];
    if (!($j1['success'] ?? false) || !($j2['success'] ?? false)) return "At least one poll call failed";
    if ($j1['status_code'] !== $j2['status_code'])                 return "Inconsistent status between polls";
    return true;
});

// ---------------------------------------------------------------
// Case 12: Recipient identity masked in API response
// ---------------------------------------------------------------
run_map_test("Recipient identity is privacy-masked with *** in API response", function () use ($baseUrl) {
    $j = do_http_get("{$baseUrl}/api/tracking.php?ref=201406231415")['json'];
    $masked = $j['masked_recipient'] ?? '';
    if (empty($masked))                   return "masked_recipient field missing from response";
    if (strpos($masked, '***') === false) return "Recipient name was not masked: {$masked}";
    return true;
});

// ---------------------------------------------------------------
// Case 13: Responsive breakpoints in tracking-map.css
// ---------------------------------------------------------------
run_map_test("Responsive breakpoints (992px, 768px) defined in tracking-map.css", function () {
    $css = file_get_contents(__DIR__ . '/../assets/css/tracking-map.css');
    if (strpos($css, '@media (max-width: 992px)') === false) return "Missing 992px breakpoint";
    if (strpos($css, '@media (max-width: 768px)') === false) return "Missing 768px breakpoint";
    return true;
});

// ---------------------------------------------------------------
// Case 14: prefers-reduced-motion honoured in CSS and JS
// ---------------------------------------------------------------
run_map_test("prefers-reduced-motion respected in tracking-map.css and tracking.php JS", function () {
    $css  = file_get_contents(__DIR__ . '/../assets/css/tracking-map.css');
    $page = file_get_contents(__DIR__ . '/../tracking.php');
    if (strpos($css,  'prefers-reduced-motion') === false) return "Missing in tracking-map.css";
    if (strpos($page, 'prefers-reduced-motion') === false) return "Missing in tracking.php JS";
    return true;
});

// ---------------------------------------------------------------
// Case 15: API does not leak credentials or internal schema
// ---------------------------------------------------------------
run_map_test("API response does not leak DB credentials or sensitive schema details", function () use ($baseUrl) {
    $body = do_http_get("{$baseUrl}/api/tracking.php?ref=201406231415")['body'];
    $forbidden = ['DB_PASS', 'DB_USER', 'password_hash', 'mysql_error', 'gaatitrack_db'];
    foreach ($forbidden as $token) {
        if (stripos($body, $token) !== false) return "Sensitive token '{$token}' found in response";
    }
    return true;
});

// ---------------------------------------------------------------
// Summary
// ---------------------------------------------------------------
echo "\n====================================================================\n";
echo "Phase 12 Map Score: {$passed} / {$total} Tests Passed.\n";
echo "====================================================================\n";

if ($passed === $total) {
    echo "VERDICT: 100% PHASE 12 MAP & TRACKING VALIDATION SUCCESSFUL!\n";
} else {
    $failed = $total - $passed;
    echo "VERDICT: {$failed} test(s) FAILED. Review output above.\n";
    exit(1);
}
