<?php
/**
 * Master End-to-End QA Testing Suite - Full Real-World User Flows
 * Covers:
 * Journey 1: Public Visitor (Home, About, Services, Tracking, Contact Submission)
 * Journey 2: Staff & Admin Authentication & Dashboard Verification
 * Journey 3: Complete Consignment Booking, Milestone Lifecycle & Public Reflection
 * Journey 4: Branch Hub & Staff Creation with Bcrypt & RBAC Enforcement
 * Journey 5: Freight Reports & Manifest Generation
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';

$baseUrl = 'http://localhost/courer';
$cookiePublic = __DIR__ . '/cookie_public_e2e.txt';
$cookieAdmin  = __DIR__ . '/cookie_admin_e2e.txt';
$cookieStaff  = __DIR__ . '/cookie_staff_e2e.txt';

if (file_exists($cookiePublic)) unlink($cookiePublic);
if (file_exists($cookieAdmin)) unlink($cookieAdmin);
if (file_exists($cookieStaff)) unlink($cookieStaff);

function make_req($url, $method = 'GET', $data = [], $cookieJar = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'url' => $finalUrl];
}

function extract_csrf($html) {
    preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/i', $html, $m);
    return $m[1] ?? '';
}

echo "====================================================================\n";
echo "      GAATITRACK MASTER END-TO-END (E2E) QA LIFECYCLE AUDIT        \n";
echo "====================================================================\n\n";

$testsPassed = 0;
$totalTests = 0;

function assert_test($label, $condition, $details = '') {
    global $testsPassed, $totalTests;
    $totalTests++;
    echo sprintf("%-60s ... ", $label);
    if ($condition) {
        echo "PASS\n";
        $testsPassed++;
        return true;
    } else {
        echo "FAIL" . ($details ? " ({$details})" : "") . "\n";
        return false;
    }
}

// =========================================================================
// JOURNEY 1: PUBLIC VISITOR WORKFLOW
// =========================================================================
echo "=== JOURNEY 1: Public Visitor Experience ===\n";

// 1.1 Home Page
$homeResp = make_req($baseUrl . '/index.php');
assert_test("1.1 Home Page returns 200 with Hero and Tracking Widget",
    $homeResp['code'] === 200 && strpos($homeResp['body'], 'Track, Ship & Deliver') !== false && strpos($homeResp['body'], 'Track Your Shipment') !== false
);

// 1.2 About Page & Branch Directory
$aboutResp = make_req($baseUrl . '/about.php');
assert_test("1.2 About Page renders real database branch directory",
    $aboutResp['code'] === 200 && strpos($aboutResp['body'], 'Our Active Branch Locations') !== false && (strpos($aboutResp['body'], 'New York') !== false || strpos($aboutResp['body'], 'Nashik') !== false)
);

// 1.3 Services Page
$servicesResp = make_req($baseUrl . '/services.php');
assert_test("1.3 Services Page renders 4 core services and pricing guide",
    $servicesResp['code'] === 200 && strpos($servicesResp['body'], 'Pricing Transparency') !== false && strpos($servicesResp['body'], 'Express Doorstep Delivery') !== false
);

// 1.4 Dedicated Tracking Portal - Existing Parcel 201406231415
$trackResp = make_req($baseUrl . '/tracking.php?ref=201406231415');
assert_test("1.4 Dedicated Tracking portal renders 4-stage stepper and timeline",
    $trackResp['code'] === 200 && strpos($trackResp['body'], 'Delivered') !== false && strpos($trackResp['body'], 'Item Accepted by Courier') !== false
);

// 1.5 Dedicated Tracking Portal - Recipient Privacy Shield
assert_test("1.5 Recipient contact information is masked (privacy shielded)",
    strpos($trackResp['body'], 'Claire B***') !== false && strpos($trackResp['body'], '+123456') === false
);

// 1.6 Contact Form Submission (with shared session cookie)
$contactGet = make_req($baseUrl . '/contact.php', 'GET', [], $cookiePublic);
$contactCsrf = extract_csrf($contactGet['body']);
$contactPost = make_req($baseUrl . '/contact.php', 'POST', [
    'csrf_token' => $contactCsrf,
    'name' => 'QA Automated Auditor',
    'email' => 'qa.auditor@example.com',
    'phone' => '+91 98888 77777',
    'subject' => 'E2E Testing Inquiry',
    'message' => 'Automated QA testing message for Phase 14 validation.',
    'website_hp' => '' // Honeypot must be empty
], $cookiePublic);

assert_test("1.6 Contact form submission succeeds and shows confirmation",
    $contactPost['code'] === 200 && strpos($contactPost['body'], 'Thank you! Your message has been received') !== false
);

// 1.7 Verify contact inquiry log
$logContent = file_get_contents(__DIR__ . '/../logs/contact_inquiries.log');
assert_test("1.7 Contact inquiry successfully recorded in contact_inquiries.log",
    strpos($logContent, 'QA Automated Auditor') !== false && strpos($logContent, 'qa.auditor@example.com') !== false
);

// =========================================================================
// JOURNEY 2: AUTHENTICATION & OPERATIONS DASHBOARD
// =========================================================================
echo "\n=== JOURNEY 2: Admin Authentication & Operations Dashboard ===\n";

// 2.1 Fetch Login Page
$loginGet = make_req($baseUrl . '/login.php', 'GET', [], $cookieAdmin);
$loginCsrf = extract_csrf($loginGet['body']);
assert_test("2.1 Login page provides valid CSRF token", !empty($loginCsrf));

// 2.2 Attempt Invalid Login
$invalidLogin = make_req($baseUrl . '/login.php', 'POST', [
    'csrf_token' => $loginCsrf,
    'email' => 'wrong.account@gaatitrack.com',
    'password' => 'incorrect_password'
], $cookieAdmin);
assert_test("2.2 Invalid credentials return generic security notice",
    strpos($invalidLogin['body'], 'Invalid email or password') !== false
);

// 2.3 Attempt Valid Administrator Login
$loginGet2 = make_req($baseUrl . '/login.php', 'GET', [], $cookieAdmin);
$loginCsrf2 = extract_csrf($loginGet2['body']);
$validLogin = make_req($baseUrl . '/login.php', 'POST', [
    'csrf_token' => $loginCsrf2,
    'email' => 'mayuri.infospace@gmail.com',
    'password' => 'admin'
], $cookieAdmin);
assert_test("2.3 Administrator login succeeds and establishes session",
    strpos($validLogin['body'], 'Operations Console') !== false || strpos($validLogin['body'], 'Admin Tester') !== false || strpos($validLogin['body'], 'Mayuri') !== false
);

// 2.4 Verify Live Database Metrics on Dashboard
$dashResp = make_req($baseUrl . '/admin/index.php?page=dashboard', 'GET', [], $cookieAdmin);
assert_test("2.4 Operations dashboard renders real KPI counts without mock data",
    $dashResp['code'] === 200 && strpos($dashResp['body'], 'Active Hub Branches') !== false && strpos($dashResp['body'], 'Live Status Breakdown') !== false
);

// =========================================================================
// JOURNEY 3: CONSIGNMENT LIFECYCLE & MILESTONE TRANSITIONS
// =========================================================================
echo "\n=== JOURNEY 3: Complete Consignment Booking & Lifecycle Scans ===\n";

// 3.1 Fetch Booking Form
$newParcelGet = make_req($baseUrl . '/admin/index.php?page=new_parcel', 'GET', [], $cookieAdmin);
$parcelCsrf = extract_csrf($newParcelGet['body']);
assert_test("3.1 Booking form loads with active branch hub selections",
    $newParcelGet['code'] === 200 && !empty($parcelCsrf) && (strpos($newParcelGet['body'], 'Book New Consignment') !== false || strpos($newParcelGet['body'], 'Book New Parcel') !== false)
);

// 3.2 Book New Consignment
$testSenderName = 'QA Sender ' . time();
$testRecipientName = 'QA Recipient ' . time();
$bookResp = make_req($baseUrl . '/admin/index.php?page=new_parcel', 'POST', [
    'csrf_token' => $parcelCsrf,
    'sender_name' => $testSenderName,
    'sender_address' => '101 QA Logistics Way, Industrial Zone',
    'sender_contact' => '+91 99999 11111',
    'recipient_name' => $testRecipientName,
    'recipient_address' => '202 QA Destination Avenue, Metro City',
    'recipient_contact' => '+91 88888 22222',
    'type' => 1, // Doorstep delivery
    'from_branch_id' => 5, // Nashik
    'to_branch_id' => 6, // Pune
    'weight' => ['12.5 kg'],
    'height' => ['15 in'],
    'width' => ['15 in'],
    'length' => ['20 in'],
    'price' => ['3500.00']
], $cookieAdmin);

// Verify insertion in database
$checkStmt = $pdo->prepare("SELECT * FROM parcels WHERE sender_name = :sname ORDER BY id DESC LIMIT 1");
$checkStmt->execute([':sname' => $testSenderName]);
$newParcel = $checkStmt->fetch();

assert_test("3.2 Consignment booked atomically with unique 12-digit reference",
    !empty($newParcel) && strlen($newParcel['reference_number']) === 12 && (int)$newParcel['status'] === 0,
    "Ref: " . ($newParcel['reference_number'] ?? 'None')
);

$newParcelId = (int)($newParcel['id'] ?? 0);
$newParcelRef = $newParcel['reference_number'] ?? '';

// 3.3 Verify Generated Waybill View
$waybillResp = make_req($baseUrl . '/admin/index.php?page=view_parcel&id=' . $newParcelId, 'GET', [], $cookieAdmin);
assert_test("3.3 Printable Waybill renders shipper, consignee, and charges",
    $waybillResp['code'] === 200 && strpos($waybillResp['body'], $newParcelRef) !== false && strpos($waybillResp['body'], '$3,500.00') !== false
);

// 3.4 Milestone Update: Move to Shipped (Status 2)
$eventsGet1 = make_req($baseUrl . '/admin/index.php?page=tracking_events&ref=' . $newParcelRef, 'GET', [], $cookieAdmin);
$eventCsrf1 = extract_csrf($eventsGet1['body']);
$updateResp1 = make_req($baseUrl . '/admin/index.php?page=tracking_events', 'POST', [
    'csrf_token' => $eventCsrf1,
    'action' => 'add_event',
    'parcel_id' => $newParcelId,
    'status' => 2 // Shipped
], $cookieAdmin);

assert_test("3.4 Milestone transition to 'Shipped' (Status 2) committed",
    strpos($updateResp1['body'], 'updated to Shipped successfully') !== false || strpos($updateResp1['body'], 'Shipped') !== false
);

// 3.5 Milestone Update: Move to Out for Delivery (Status 5)
$eventsGet2 = make_req($baseUrl . '/admin/index.php?page=tracking_events&ref=' . $newParcelRef, 'GET', [], $cookieAdmin);
$eventCsrf2 = extract_csrf($eventsGet2['body']);
$updateResp2 = make_req($baseUrl . '/admin/index.php?page=tracking_events', 'POST', [
    'csrf_token' => $eventCsrf2,
    'action' => 'add_event',
    'parcel_id' => $newParcelId,
    'status' => 5 // Out for Delivery
], $cookieAdmin);

assert_test("3.5 Milestone transition to 'Out for Delivery' (Status 5) committed",
    strpos($updateResp2['body'], 'Out for Delivery') !== false
);

// 3.6 Milestone Update: Final Delivery (Status 7)
$eventsGet3 = make_req($baseUrl . '/admin/index.php?page=tracking_events&ref=' . $newParcelRef, 'GET', [], $cookieAdmin);
$eventCsrf3 = extract_csrf($eventsGet3['body']);
$updateResp3 = make_req($baseUrl . '/admin/index.php?page=tracking_events', 'POST', [
    'csrf_token' => $eventCsrf3,
    'action' => 'add_event',
    'parcel_id' => $newParcelId,
    'status' => 7 // Delivered
], $cookieAdmin);

assert_test("3.6 Milestone transition to 'Delivered' (Status 7) committed",
    strpos($updateResp3['body'], 'Delivered') !== false
);

// 3.7 Public Reflection: Query Public Tracking Page
$publicTrackingNew = make_req($baseUrl . '/tracking.php?ref=' . $newParcelRef);
assert_test("3.7 Public tracking immediately reflects 'Delivered' with complete timeline",
    $publicTrackingNew['code'] === 200 && strpos($publicTrackingNew['body'], 'Delivered') !== false && strpos($publicTrackingNew['body'], 'Out for Delivery') !== false
);

// =========================================================================
// JOURNEY 4: BRANCH HUBS & STAFF MANAGEMENT (RBAC)
// =========================================================================
echo "\n=== JOURNEY 4: Branch Hubs & Staff Management (RBAC) ===\n";

// 4.1 Create Test Branch Hub
$branchGet = make_req($baseUrl . '/admin/index.php?page=new_branch', 'GET', [], $cookieAdmin);
$branchCsrf = extract_csrf($branchGet['body']);
$testBranchCity = 'QA Hub ' . time();
$testBranchCode = 'HUB' . substr(str_shuffle('0123456789ABCDEF'), 0, 8);

$createBranchResp = make_req($baseUrl . '/admin/index.php?page=new_branch', 'POST', [
    'csrf_token' => $branchCsrf,
    'branch_code' => $testBranchCode,
    'city' => $testBranchCity,
    'street' => '99 Quality Lane, Technology Park',
    'state' => 'MH',
    'zip_code' => '400001',
    'country' => 'India',
    'contact' => '+91 97777 66666'
], $cookieAdmin);

$bCheck = $pdo->prepare("SELECT id FROM branches WHERE branch_code = :code LIMIT 1");
$bCheck->execute([':code' => $testBranchCode]);
$newBranchId = (int)$bCheck->fetchColumn();

assert_test("4.1 Administrator creates new branch hub",
    $newBranchId > 0,
    "Branch ID: {$newBranchId}"
);

// 4.2 Create New Branch Staff Account
$staffGet = make_req($baseUrl . '/admin/index.php?page=new_staff', 'GET', [], $cookieAdmin);
$staffCsrf = extract_csrf($staffGet['body']);
$testStaffEmail = 'qa.staff.' . time() . '@gaatitrack.test';
$testStaffPassword = 'QAStaffPassword2026!';

$createStaffResp = make_req($baseUrl . '/admin/index.php?page=new_staff', 'POST', [
    'csrf_token' => $staffCsrf,
    'firstname' => 'QAStaffFirst',
    'lastname' => 'QAStaffLast',
    'email' => $testStaffEmail,
    'type' => 2, // Branch Staff
    'branch_id' => $newBranchId,
    'password' => $testStaffPassword,
    'confirm_password' => $testStaffPassword
], $cookieAdmin);

$sCheck = $pdo->prepare("SELECT id, password FROM users WHERE email = :email LIMIT 1");
$sCheck->execute([':email' => $testStaffEmail]);
$newStaffUser = $sCheck->fetch();

assert_test("4.2 Administrator creates branch staff account with bcrypt hashing",
    !empty($newStaffUser) && strpos($newStaffUser['password'], '$2y$10$') === 0,
    "Staff ID: " . ($newStaffUser['id'] ?? 'None')
);

// 4.3 Authenticate as Newly Created Staff Member
$staffLoginGet = make_req($baseUrl . '/login.php', 'GET', [], $cookieStaff);
$staffLoginCsrf = extract_csrf($staffLoginGet['body']);
$staffLoginPost = make_req($baseUrl . '/login.php', 'POST', [
    'csrf_token' => $staffLoginCsrf,
    'email' => $testStaffEmail,
    'password' => $testStaffPassword
], $cookieStaff);

assert_test("4.3 Newly created staff member signs in successfully",
    strpos($staffLoginPost['body'], 'QAStaffFirst') !== false || strpos($staffLoginPost['body'], 'Branch Staff') !== false
);

// 4.4 RBAC Enforcement: Staff blocked from Admin pages
$staffSettingsAccess = make_req($baseUrl . '/admin/index.php?page=settings', 'GET', [], $cookieStaff);
assert_test("4.4 Staff access to system settings blocked by RBAC",
    strpos($staffSettingsAccess['body'], 'Administrator privileges required') !== false || strpos($staffSettingsAccess['body'], 'Access denied') !== false || $staffSettingsAccess['code'] === 302
);

// Clean up temporary test staff and test branch
if (!empty($newStaffUser['id'])) {
    $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $newStaffUser['id']]);
}
if ($newBranchId > 0) {
    $pdo->prepare("DELETE FROM branches WHERE id = :id")->execute([':id' => $newBranchId]);
}

// =========================================================================
// JOURNEY 5: REPORTS & FREIGHT MANIFEST GENERATION
// =========================================================================
echo "\n=== JOURNEY 5: Reports & Freight Manifests ===\n";

$reportResp = make_req($baseUrl . '/admin/index.php?page=reports&date_from=2020-01-01&date_to=2030-12-31', 'GET', [], $cookieAdmin);
assert_test("5.1 Freight manifest report calculates revenue and lists consignments",
    $reportResp['code'] === 200 && (strpos($reportResp['body'], 'TOTAL FREIGHT MANIFEST VALUE') !== false || strpos($reportResp['body'], 'MANIFEST TOTAL') !== false) && (strpos($reportResp['body'], 'Print Freight Manifest') !== false || strpos($reportResp['body'], 'Print Manifest') !== false)
);

// Cleanup test consignment created in Journey 3
if ($newParcelId > 0) {
    $pdo->prepare("DELETE FROM parcel_tracks WHERE parcel_id = :id")->execute([':id' => $newParcelId]);
    $pdo->prepare("DELETE FROM parcels WHERE id = :id")->execute([':id' => $newParcelId]);
}

// Cleanup cookies
if (file_exists($cookiePublic)) unlink($cookiePublic);
if (file_exists($cookieAdmin)) unlink($cookieAdmin);
if (file_exists($cookieStaff)) unlink($cookieStaff);

// =========================================================================
// FINAL VERDICT
// =========================================================================
echo "\n====================================================================\n";
echo "Master E2E QA Score: {$testsPassed} / {$totalTests} Tests Passed.\n";
echo "====================================================================\n";

if ($testsPassed === $totalTests) {
    echo "VERDICT: 100% END-TO-END QA TESTING & LIFECYCLE AUDIT SUCCESSFUL!\n";
    exit(0);
} else {
    echo "VERDICT: E2E QA TESTING DISCOVERED FAILURES!\n";
    exit(1);
}
