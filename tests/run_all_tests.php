<?php
/**
 * Master Automated Test Battery Runner
 * Executes all 8 unit, functional, security, and accessibility test suites.
 */

$testSuites = [
    'Phase 7 Tracking Suite'         => 'tests/test_tracking.php',
    'Phase 8 Contact Suite'          => 'tests/test_contact.php',
    'Phase 9 Auth & Bcrypt Suite'    => 'tests/test_auth.php',
    'Phase 10 Admin Dashboard Suite' => 'tests/test_dashboard.php',
    'Phase 11 Admin Operations Suite'=> 'tests/test_admin_modules.php',
    'Phase 11 HTTP End-to-End Suite' => 'tests/test_admin_http.php',
    'Phase 12 Security Hardening'    => 'tests/test_security_hardening.php',
    'Phase 13 Responsiveness & a11y' => 'tests/test_responsiveness_a11y.php',
];

echo "====================================================================\n";
echo "      GAATITRACK MASTER TEST BATTERY - ALL PHASES VERIFICATION      \n";
echo "====================================================================\n\n";

$allPassed = true;
$passedCount = 0;

foreach ($testSuites as $name => $file) {
    echo sprintf("%-40s ... ", $name);
    $cmd = 'php ' . escapeshellarg(__DIR__ . '/../' . $file);
    exec($cmd, $output, $returnCode);

    if ($returnCode === 0) {
        echo "PASS\n";
        $passedCount++;
    } else {
        echo "FAIL (exit code {$returnCode})\n";
        $allPassed = false;
    }
    $output = [];
}

echo "\n--------------------------------------------------------------------\n";
echo "Master Battery Result: {$passedCount} / " . count($testSuites) . " Suites Passed\n";
echo "--------------------------------------------------------------------\n";

if ($allPassed) {
    echo "VERDICT: 100% OF ALL SYSTEM TEST SUITES PASSED FLAWLESSLY!\n";
    exit(0);
} else {
    echo "VERDICT: TEST BATTERY FAILURE!\n";
    exit(1);
}
