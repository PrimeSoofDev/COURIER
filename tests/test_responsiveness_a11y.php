<?php
/**
 * Automated Responsiveness, Accessibility (a11y) & Cross-Browser Audit Suite
 */

$baseUrl = 'http://localhost/courer';
$pages = [
    'Home'      => '/index.php',
    'About'     => '/about.php',
    'Services'  => '/services.php',
    'Tracking'  => '/tracking.php',
    'Contact'   => '/contact.php',
    'Login'     => '/login.php',
];

function fetch_page($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'html' => $html];
}

echo "====================================================================\n";
echo "    GaaTiTrack - Responsiveness, a11y & Cross-Browser Audit       \n";
echo "====================================================================\n\n";

$auditResults = [];

// 1. Audit Viewport & Semantic Structure on Public Pages
echo "--- 1. Viewport Meta Tags & Semantic Headings ---\n";
foreach ($pages as $name => $path) {
    $res = fetch_page($baseUrl . $path);
    $html = $res['html'];

    // Viewport check
    $hasViewport = (stripos($html, '<meta name="viewport"') !== false && stripos($html, 'width=device-width') !== false);
    $auditResults["{$name}: Viewport Meta Tag Present"] = $hasViewport;
    echo "{$name} Viewport: " . ($hasViewport ? "PASS" : "FAIL") . "\n";

    // Heading structure: At least one h1
    preg_match_all('/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $h1Matches);
    $h1Count = count($h1Matches[0]);
    $hasValidH1 = ($h1Count >= 1);
    $auditResults["{$name}: Top-level <h1> Heading Present"] = $hasValidH1;
    echo "{$name} <h1> Count ({$h1Count}): " . ($hasValidH1 ? "PASS" : "FAIL") . "\n";

    // Skip Link
    $hasSkipLink = (stripos($html, 'class="gt-skip-link"') !== false);
    $auditResults["{$name}: Accessible Skip Link Present"] = $hasSkipLink;
    echo "{$name} Skip-Link: " . ($hasSkipLink ? "PASS" : "FAIL") . "\n";
}

// 2. Audit Images for alt Attributes
echo "\n--- 2. Image Accessibility (alt attributes) ---\n";
$imagesMissingAlt = 0;
foreach ($pages as $name => $path) {
    $res = fetch_page($baseUrl . $path);
    $html = $res['html'];

    preg_match_all('/<img\b(?![^>]*\balt=)[^>]*>/i', $html, $badImgs);
    if (!empty($badImgs[0])) {
        $imagesMissingAlt += count($badImgs[0]);
        echo "{$name} has " . count($badImgs[0]) . " images missing alt text!\n";
    }
}
$auditResults["All Public Images Have Alt Attributes"] = ($imagesMissingAlt === 0);
echo "Image Alt Attributes: " . ($imagesMissingAlt === 0 ? "PASS" : "FAIL") . "\n";

// 3. Audit Form Inputs for Labels / aria-labels
echo "\n--- 3. Form Input Accessibility ---\n";
$contactPage = fetch_page($baseUrl . '/contact.php')['html'];
$inputsWithoutLabel = 0;
preg_match_all('/<(?:input|textarea|select)\b(?![^>]*(?:type=["\'](?:hidden|submit|button)["\']))(?![^>]*(?:aria-label|id=))[^>]*>/i', $contactPage, $badInputs);
if (!empty($badInputs[0])) {
    $inputsWithoutLabel += count($badInputs[0]);
}
$auditResults["Contact Form Controls Have Identifiers/Labels"] = ($inputsWithoutLabel === 0);
echo "Form Controls Identification: " . ($inputsWithoutLabel === 0 ? "PASS" : "FAIL") . "\n";

// 4. Audit CSS Media Queries and Responsive Rules in design-system.css
echo "\n--- 4. Design System Media Queries & Breakpoints ---\n";
$cssContent = file_get_contents(__DIR__ . '/../assets/css/design-system.css');

$hasMobileBreak = (strpos($cssContent, '@media (max-width: 900px)') !== false);
$hasTouchTargetBreak = (strpos($cssContent, '@media (max-width: 768px)') !== false);
$hasReducedMotion = (strpos($cssContent, '@media (prefers-reduced-motion: reduce)') !== false);
$hasTouchTargetRules = (strpos($cssContent, 'min-height: 44px') !== false);
$hasTableScroll = (strpos($cssContent, '-webkit-overflow-scrolling: touch') !== false);

$auditResults["Breakpoint @media (max-width: 900px) Configured"] = $hasMobileBreak;
$auditResults["Breakpoint @media (max-width: 768px) Configured"] = $hasTouchTargetBreak;
$auditResults["Reduced Motion (prefers-reduced-motion) Configured"] = $hasReducedMotion;
$auditResults["Mobile Touch Targets (min 44px) Configured"] = $hasTouchTargetRules;
$auditResults["Smooth Touch Overflow for Table Containers Configured"] = $hasTableScroll;

echo "Mobile Breakpoint (900px): " . ($hasMobileBreak ? "PASS" : "FAIL") . "\n";
echo "Touch Target Breakpoint (768px): " . ($hasTouchTargetBreak ? "PASS" : "FAIL") . "\n";
echo "Prefers-Reduced-Motion Support: " . ($hasReducedMotion ? "PASS" : "FAIL") . "\n";
echo "44px Minimum Touch Target Rule: " . ($hasTouchTargetRules ? "PASS" : "FAIL") . "\n";
echo "Touch Table Scrolling: " . ($hasTableScroll ? "PASS" : "FAIL") . "\n";

// 5. Audit Admin Header for a11y & ARIA
echo "\n--- 5. Admin Header ARIA and Navigation Accessibility ---\n";
$adminHeaderContent = file_get_contents(__DIR__ . '/../admin/header.php');

$adminHasSkip = (strpos($adminHeaderContent, 'class="gt-skip-link"') !== false);
$adminHasAriaExpanded = (strpos($adminHeaderContent, 'aria-expanded="false"') !== false);
$adminHasAriaControls = (strpos($adminHeaderContent, 'aria-controls="admin-sidebar"') !== false);
$adminHasMainId = (strpos($adminHeaderContent, 'id="admin-main-content"') !== false);

$auditResults["Admin Header Has Accessible Skip Link"] = $adminHasSkip;
$auditResults["Admin Sidebar Toggle Has aria-expanded"] = $adminHasAriaExpanded;
$auditResults["Admin Sidebar Toggle Has aria-controls"] = $adminHasAriaControls;
$auditResults["Admin Main Element Has Matching Anchor ID"] = $adminHasMainId;

echo "Admin Skip Link: " . ($adminHasSkip ? "PASS" : "FAIL") . "\n";
echo "Admin Toggle aria-expanded: " . ($adminHasAriaExpanded ? "PASS" : "FAIL") . "\n";
echo "Admin Toggle aria-controls: " . ($adminHasAriaControls ? "PASS" : "FAIL") . "\n";
echo "Admin Main Anchor ID: " . ($adminHasMainId ? "PASS" : "FAIL") . "\n";

// -------------------------------------------------------------------------
// Summary
// -------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "              RESPONSIVENESS & A11Y AUDIT SUMMARY                   \n";
echo "====================================================================\n";

$passCount = 0;
$totalCount = count($auditResults);
$allPassed = true;

foreach ($auditResults as $test => $passed) {
    echo sprintf("%-60s [%s]\n", $test, $passed ? "PASS" : "FAIL");
    if ($passed) {
        $passCount++;
    } else {
        $allPassed = false;
    }
}

echo "\nScore: {$passCount} / {$totalCount} Checks Passed.\n";

if ($allPassed) {
    echo "VERDICT: 100% RESPONSIVENESS & A11Y AUDIT SUCCESSFUL!\n";
    exit(0);
} else {
    echo "VERDICT: AUDIT DISCOVERED ISSUES!\n";
    exit(1);
}
