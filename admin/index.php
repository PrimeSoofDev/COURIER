<?php
/**
 * GaaTiTrack Admin - Central Controller Router
 */

require_once __DIR__ . '/auth_check.php';

// Strict Whitelist of Allowed Admin Views
$allowedViews = [
    'dashboard'       => 'dashboard.php',
    'parcels'         => 'parcels.php',
    'new_parcel'      => 'parcel_form.php',
    'edit_parcel'     => 'parcel_form.php',
    'view_parcel'     => 'parcel_view.php',
    'tracking_events' => 'tracking_events.php',
    'reports'         => 'reports.php',
    'branches'        => 'branches.php',
    'new_branch'      => 'branch_form.php',
    'edit_branch'     => 'branch_form.php',
    'staff'           => 'staff.php',
    'new_staff'       => 'staff_form.php',
    'edit_staff'      => 'staff_form.php',
    'settings'        => 'settings.php',
];

$requestedPage = $_GET['page'] ?? 'dashboard';

// Prevent Local File Inclusion (LFI) via strict mapping
if (!array_key_exists($requestedPage, $allowedViews)) {
    $requestedPage = 'dashboard';
}

// Role-based protection: Restrict administration modules to Admins only
$adminOnlyPages = ['branches', 'new_branch', 'edit_branch', 'staff', 'new_staff', 'edit_staff', 'settings'];
if (in_array($requestedPage, $adminOnlyPages, true)) {
    require_admin();
}

$viewFile = __DIR__ . '/views/' . $allowedViews[$requestedPage];
if (!file_exists($viewFile)) {
    $viewFile = __DIR__ . '/views/dashboard.php';
}

$currentPage = $requestedPage;

// Render selected view
require_once $viewFile;
