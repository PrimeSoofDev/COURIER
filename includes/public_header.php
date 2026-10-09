<?php
/**
 * GaaTiTrack - Public Portal Header & Navigation
 */

if (!defined('GAATITRACK_CONFIG_LOADED')) {
    require_once __DIR__ . '/../config.php';
}
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/components.php';

// Fetch system settings for title & branding
if (!isset($_SESSION['system'])) {
    $sysQuery = $conn->query("SELECT * FROM system_settings LIMIT 1");
    if ($sysQuery && $sysQuery->num_rows > 0) {
        $_SESSION['system'] = $sysQuery->fetch_assoc();
    } else {
        $_SESSION['system'] = [
            'name' => 'GaaTiTrack Logistics USA',
            'email' => 'support@gaatitrack.com',
            'contact' => '+1 (800) 555-0199',
            'address' => '1250 Broadway, Suite 3200, New York, NY 10001, United States',
            'cover_img' => ''
        ];
    }
}

$activePage = $currentPage ?? 'home';
$pageTitle = $pageTitle ?? 'Fast, Reliable Courier & Freight Tracking';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($pageTitle); ?> | <?php echo e($_SESSION['system']['name']); ?></title>
  <meta name="description" content="GaaTiTrack provides seamless parcel and freight shipping, transparent real-time tracking, and express deliveries across nationwide hubs.">
  <link rel="icon" type="image/x-icon" href="<?php echo APP_URL; ?>/assets/uploads/logo1.jpg">
  
  <!-- Design System Stylesheet -->
  <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/design-system.css">
  <!-- Premium Glassmorphism Hero Design System -->
  <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/hero-glassmorphism.css">
</head>
<body>

  <!-- Accessible Skip to Main Content Link -->
  <a href="#main-content" class="gt-skip-link">Skip to main content</a>

  <!-- Main Navigation Header -->
  <header class="gt-header">
    <div class="gt-container gt-nav-wrap">
      <a href="<?php echo APP_URL; ?>/index.php" class="gt-brand" aria-label="GaaTiTrack Homepage">
        <div class="gt-brand-logo" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
            <line x1="12" y1="22.08" x2="12" y2="12"></line>
          </svg>
        </div>
        <div class="gt-brand-title">GaaTi<span>Track</span></div>
      </a>

      <!-- Navigation Links -->
      <nav aria-label="Main Navigation">
        <ul class="gt-nav-menu">
          <li><a href="<?php echo APP_URL; ?>/index.php" class="gt-nav-link <?php echo $activePage === 'home' ? 'active' : ''; ?>">Home</a></li>
          <li><a href="<?php echo APP_URL; ?>/about.php" class="gt-nav-link <?php echo $activePage === 'about' ? 'active' : ''; ?>">About Us</a></li>
          <li><a href="<?php echo APP_URL; ?>/services.php" class="gt-nav-link <?php echo $activePage === 'services' ? 'active' : ''; ?>">Services</a></li>
          <li><a href="<?php echo APP_URL; ?>/tracking.php" class="gt-nav-link <?php echo $activePage === 'tracking' ? 'active' : ''; ?>">Track Shipment</a></li>
          <li><a href="<?php echo APP_URL; ?>/contact.php" class="gt-nav-link <?php echo $activePage === 'contact' ? 'active' : ''; ?>">Contact</a></li>
        </ul>
      </nav>

      <!-- Action Buttons -->
      <div class="gt-nav-actions">
        <a href="<?php echo APP_URL; ?>/tracking.php" class="gt-btn gt-btn-primary gt-btn-sm">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          Track Parcel
        </a>
        <a href="<?php echo APP_URL; ?>/login.php" class="gt-btn gt-btn-secondary gt-btn-sm" title="Internal Operations Login">
          Portal Login
        </a>
        <button class="gt-mobile-toggle" aria-label="Toggle Navigation Menu" aria-expanded="false">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>
  </header>
  <main style="flex: 1;" id="main-content">
