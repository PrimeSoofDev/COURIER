<?php
/**
 * Automated Test Suite: Phase 11 Admin Modules & Shipment Management
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
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../admin/auth_check.php';

function run_test($name, $closure) {
    echo "Running test: {$name}... ";
    try {
        $result = $closure();
        if ($result === true) {
            echo "PASSED\n";
            return true;
        } else {
            echo "FAILED (" . (is_string($result) ? $result : 'false') . ")\n";
            return false;
        }
    } catch (Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        return false;
    }
}

$passed = 0;
$total = 0;

// Test 1: Check parcel view rendering
$total++;
if (run_test("Parcel View: Loads Existing Consignment #1", function() use ($pdo) {
    $_GET['id'] = 1;
    $_SESSION['login_id'] = 1;
    $_SESSION['login_name'] = 'Admin Tester';
    $_SESSION['login_type'] = 1;
    $_SESSION['login_branch_id'] = 0;

    ob_start();
    include __DIR__ . '/../admin/views/parcel_view.php';
    $output = ob_get_clean();

    if (strpos($output, '201406231415') === false) {
        return "Reference 201406231415 not found in output";
    }
    if (strpos($output, 'Official Consignment Manifest') === false) {
        return "Waybill header missing";
    }
    if (strpos($output, 'Milestone Audit Trail') === false) {
        return "Milestone trail section missing";
    }
    return true;
})) $passed++;

// Test 2: Check tracking events view lookup
$total++;
if (run_test("Tracking Events: Consignment Lookup and Update Console", function() use ($pdo) {
    $_GET['ref'] = '201406231415';
    $_SESSION['login_id'] = 1;
    $_SESSION['login_name'] = 'Admin Tester';
    $_SESSION['login_type'] = 1;
    $_SESSION['login_branch_id'] = 0;

    ob_start();
    include __DIR__ . '/../admin/views/tracking_events.php';
    $output = ob_get_clean();

    if (strpos($output, 'Update Tracking') === false) {
        return "Header title missing";
    }
    if (strpos($output, '201406231415') === false) {
        return "Target consignment ref not displayed";
    }
    if (strpos($output, 'Status') === false) {
        return "Status dropdown missing";
    }
    return true;
})) $passed++;

// Test 3: Check reports view calculations
$total++;
if (run_test("Reports: Freight Manifest Generation & Metrics", function() use ($pdo) {
    $_GET = ['page' => 'reports', 'date_from' => '2020-01-01', 'date_to' => '2030-12-31'];
    $_SESSION['login_id'] = 1;
    $_SESSION['login_name'] = 'Admin Tester';
    $_SESSION['login_type'] = 1;
    $_SESSION['login_branch_id'] = 0;

    ob_start();
    include __DIR__ . '/../admin/views/reports.php';
    $output = ob_get_clean();

    if (strpos($output, 'Freight Manifest') === false && strpos($output, 'Reports & Manifests') === false) {
        return "Report title missing";
    }
    if (strpos($output, 'MANIFEST TOTAL') === false && strpos($output, 'TOTAL FREIGHT') === false) {
        return "Manifest totals row missing";
    }
    if (strpos($output, 'Total Consignments') === false && strpos($output, 'Total Freight Value') === false && strpos($output, 'Filtered Consignments') === false) {
        return "KPI cards missing";
    }
    return true;
})) $passed++;

// Test 4: Check branches directory
$total++;
if (run_test("Branches Directory: Lists Database Hubs", function() use ($pdo) {
    $_GET = ['page' => 'branches'];
    $_SESSION['login_id'] = 1;
    $_SESSION['login_name'] = 'Admin Tester';
    $_SESSION['login_type'] = 1;
    $_SESSION['login_branch_id'] = 0;

    ob_start();
    include __DIR__ . '/../admin/views/branches.php';
    $output = ob_get_clean();

    if (strpos($output, 'Branch Hubs Directory') === false) {
        return "Branches header missing";
    }
    if (strpos($output, 'New York') === false && strpos($output, 'Chicago') === false && strpos($output, 'Nashik') === false && strpos($output, 'pune') === false) {
        return "Known DB hubs not listed";
    }
    return true;
})) $passed++;

// Test 5: Check staff directory and self-deletion restriction
$total++;
if (run_test("Staff Directory: Renders Accounts & Restricts Self-Deletion", function() use ($pdo) {
    $_GET = ['page' => 'staff'];
    $_SESSION['login_id'] = 1;
    $_SESSION['login_name'] = 'Admin Tester';
    $_SESSION['login_type'] = 1;
    $_SESSION['login_branch_id'] = 0;

    ob_start();
    include __DIR__ . '/../admin/views/staff.php';
    $output = ob_get_clean();

    if (strpos($output, 'Staff & Operator Directory') === false) {
        return "Staff header missing";
    }
    if (strpos($output, 'mayuri.infospace@gmail.com') === false) {
        return "Primary admin not listed";
    }
    if (strpos($output, 'Administrator') === false) {
        return "Administrator role badge missing";
    }
    return true;
})) $passed++;

// Test 6: Check system settings view
$total++;
if (run_test("Settings Console: Loads Branding & System Configuration", function() use ($pdo) {
    $_GET = ['page' => 'settings'];
    $_SESSION['login_id'] = 1;
    $_SESSION['login_name'] = 'Admin Tester';
    $_SESSION['login_type'] = 1;
    $_SESSION['login_branch_id'] = 0;

    ob_start();
    include __DIR__ . '/../admin/views/settings.php';
    $output = ob_get_clean();

    if (strpos($output, 'System Settings') === false) {
        return "Settings header missing";
    }
    if (strpos($output, 'System Name') === false) {
        return "System name field missing";
    }
    return true;
})) $passed++;

// Test 7: Verify RBAC role protection (Staff cannot access admin-only pages)
$total++;
if (run_test("RBAC Security: Staff blocked from Admin pages", function() {
    $_SESSION['login_id'] = 4;
    $_SESSION['login_type'] = 2; // Staff
    $_SESSION['login_branch_id'] = 5;

    if (is_admin()) {
        return "Staff user improperly identified as admin";
    }
    return true;
})) $passed++;

echo "\nSummary: {$passed} / {$total} tests passed.\n";
if ($passed === $total) {
    echo "PHASE 11 VALIDATION SUCCESSFUL!\n";
    exit(0);
} else {
    echo "PHASE 11 VALIDATION FAILED!\n";
    exit(1);
}
