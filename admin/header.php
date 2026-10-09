<?php
/**
 * GaaTiTrack Admin - Shared Header & Navigation Layout
 */

require_once __DIR__ . '/auth_check.php';

$activePage = $currentPage ?? 'dashboard';
$adminTitle = $pageTitle ?? 'Operations Console';

// Fetch branch name for staff
$userBranchName = 'Global Operations';
if (!is_admin() && user_branch_id() > 0) {
    try {
        $bStmt = $pdo->prepare("SELECT city, branch_code FROM branches WHERE id = :id");
        $bStmt->execute([':id' => user_branch_id()]);
        if ($bRow = $bStmt->fetch()) {
            $userBranchName = $bRow['city'] . ' Hub (' . $bRow['branch_code'] . ')';
        }
    } catch (Exception $e) {}
}

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Nav groups
$navGroups = [
  ['label' => 'Operations', 'items' => [
    ['page' => 'dashboard',       'label' => 'Dashboard',         'icon' => 'grid'],
  ]],
  ['label' => 'Consignment', 'items' => [
    ['page' => 'parcels',         'label' => 'All Shipments',      'icon' => 'package'],
    ['page' => 'new_parcel',      'label' => 'Book New Parcel',    'icon' => 'plus-circle'],
    ['page' => 'tracking_events', 'label' => 'Update Tracking',    'icon' => 'clock'],
    ['page' => 'reports',         'label' => 'Reports & Manifests','icon' => 'file-text'],
  ]],
  ['label' => 'Administration', 'admin_only' => true, 'items' => [
    ['page' => 'branches',        'label' => 'Branch Hubs',        'icon' => 'home'],
    ['page' => 'staff',           'label' => 'Staff Accounts',     'icon' => 'users'],
    ['page' => 'settings',        'label' => 'System Settings',    'icon' => 'settings'],
  ]],
];

// SVG icon helper
function nav_icon(string $name): string {
  $icons = [
    'grid'       => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
    'package'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
    'plus-circle'=> '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
    'clock'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'file-text'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
    'home'       => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    'users'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'settings'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    'globe'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
    'logout'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
    'chevron'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>',
    'menu'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
    'search'     => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
  ];
  return $icons[$name] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($adminTitle); ?> | GaaTiTrack Portal</title>
  <link rel="icon" type="image/x-icon" href="<?php echo APP_URL; ?>/assets/uploads/logo1.jpg">
  <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/design-system.css">

  <style>
    /* ── Reset ── */
    *, *::before, *::after { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; padding: 0; }
    body { background: #f1f5f9; font-family: var(--gt-font, 'Inter', sans-serif); }

    /* ─────────────────────────────────────
       SHELL  — full viewport, no body scroll
    ───────────────────────────────────── */
    .gt-admin-shell {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    /* ─────────────────────────────────────
       SIDEBAR
    ───────────────────────────────────── */
    :root {
      --sb-w: 252px;
      --sb-icon: 64px;
      --sb-bg: #0d1626;
      --sb-transition: 0.26s cubic-bezier(0.4,0,0.2,1);
    }

    .gt-sidebar {
      width: var(--sb-w);
      min-width: var(--sb-w);   /* prevents flex from shrinking it */
      height: 100vh;
      background: var(--sb-bg);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      overflow: hidden;         /* clips text during collapse */
      transition: width var(--sb-transition), min-width var(--sb-transition);
      position: relative;
      z-index: 200;
    }

    /* Collapsed state — icon rail */
    .gt-sidebar.is-collapsed {
      width: var(--sb-icon);
      min-width: var(--sb-icon);
    }

    /* ── Brand row ── */
    .sb-brand {
      height: 64px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 0 14px;
      flex-shrink: 0;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      white-space: nowrap;
      overflow: hidden;
    }

    .sb-brand-logo {
      width: 34px;
      height: 34px;
      min-width: 34px;
      background: linear-gradient(135deg,#0284c7,#0ea5e9);
      border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 3px 10px rgba(2,132,199,.45);
    }

    .sb-brand-text {
      font-size: 1.05rem;
      font-weight: 700;
      color: #fff;
      letter-spacing: -0.02em;
      transition: opacity var(--sb-transition), max-width var(--sb-transition);
      max-width: 160px;
      overflow: hidden;   /* required for max-width clip */
      white-space: nowrap;
    }
    .sb-brand-text span { color: #38bdf8; }
    .gt-sidebar.is-collapsed .sb-brand-text {
      opacity: 0; max-width: 0;
    }

    /* ── User badge ── */
    .sb-user {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 14px;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      background: rgba(255,255,255,0.02);
      flex-shrink: 0;
      white-space: nowrap;
      overflow: hidden;
    }

    .sb-avatar {
      width: 34px; height: 34px; min-width: 34px;
      border-radius: 50%;
      background: linear-gradient(135deg,#1d4ed8,#7c3aed);
      display: flex; align-items: center; justify-content: center;
      font-size: 0.8rem; font-weight: 700; color: #fff;
    }

    .sb-user-info {
      overflow: hidden;
      max-width: 160px;
      min-width: 0;
      transition: opacity var(--sb-transition), max-width var(--sb-transition);
      white-space: nowrap;
    }
    .gt-sidebar.is-collapsed .sb-user-info {
      opacity: 0; max-width: 0;
    }

    .sb-user-name {
      font-size: 0.82rem; font-weight: 600; color: #f1f5f9;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .sb-user-role {
      font-size: 0.68rem; color: #64748b; margin-top: 1px;
      display: flex; align-items: center; gap: 4px;
    }
    .sb-role-dot {
      width: 6px; height: 6px; border-radius: 50%; background: #22c55e; flex-shrink: 0;
    }

    /* ── Nav scroll area ── */
    .sb-nav {
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 10px 8px;
      scrollbar-width: thin;
      scrollbar-color: rgba(255,255,255,0.08) transparent;
    }
    .sb-nav::-webkit-scrollbar { width: 3px; }
    .sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

    /* ── Section label ── */
    .sb-section {
      font-size: 0.62rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.09em; color: #2d4a6a;
      padding: 10px 10px 3px;
      white-space: nowrap; overflow: hidden;
      max-height: 30px;
      transition: opacity var(--sb-transition), max-height var(--sb-transition),
                  padding var(--sb-transition), margin var(--sb-transition);
    }
    .gt-sidebar.is-collapsed .sb-section {
      opacity: 0;
      max-height: 0;
      padding: 0;
      margin: 0;
      pointer-events: none;
    }

    /* ── Nav link ── */
    .sb-link {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 10px;
      border-radius: 8px;
      color: #94a3b8;
      font-size: 0.83rem; font-weight: 500;
      text-decoration: none;
      white-space: nowrap;
      overflow: hidden;
      transition: background 0.16s, color 0.16s;
      margin-bottom: 2px;
      position: relative;
    }
    .sb-link:hover   { background: rgba(255,255,255,0.06); color: #e2e8f0; }
    .sb-link.active  {
      background: linear-gradient(90deg,#0369a1,#0284c7);
      color: #fff; font-weight: 600;
      box-shadow: 0 2px 8px rgba(2,132,199,.3);
    }

    .sb-link-icon { flex-shrink: 0; display: flex; align-items: center; line-height: 1; }

    .sb-link-label {
      overflow: hidden;     /* CRITICAL: clips text at max-width:0 */
      max-width: 160px;
      white-space: nowrap;
      flex-shrink: 1;
      transition: opacity var(--sb-transition), max-width var(--sb-transition);
    }
    .gt-sidebar.is-collapsed .sb-link-label {
      opacity: 0;
      max-width: 0;
      pointer-events: none;
    }

    /* Tooltip shown on collapsed hover */
    .gt-sidebar.is-collapsed .sb-link::after {
      content: attr(data-tip);
      position: fixed;
      left: calc(var(--sb-icon) + 10px);
      background: #1e3a5f;
      color: #e2e8f0;
      font-size: 0.78rem; font-weight: 500;
      padding: 5px 11px;
      border-radius: 6px;
      white-space: nowrap;
      pointer-events: none;
      opacity: 0;
      z-index: 9999;
      box-shadow: 0 4px 14px rgba(0,0,0,.45);
      transform: translateY(-50%) translateX(4px);
      transition: opacity 0.12s;
    }
    .gt-sidebar.is-collapsed .sb-link:hover::after { opacity: 1; }

    /* ── Sidebar footer ── */
    .sb-footer {
      border-top: 1px solid rgba(255,255,255,0.06);
      padding: 8px;
      flex-shrink: 0;
    }

    .sb-logout {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 10px; border-radius: 8px;
      color: #f87171; font-size: 0.83rem; font-weight: 500;
      text-decoration: none;
      white-space: nowrap; overflow: hidden;
      transition: background 0.16s;
      position: relative;
    }
    .sb-logout:hover { background: rgba(248,113,113,0.1); color: #fca5a5; }

    .sb-logout .sb-link-label {
      overflow: hidden; max-width: 160px;
      transition: opacity var(--sb-transition), max-width var(--sb-transition);
    }
    .gt-sidebar.is-collapsed .sb-logout .sb-link-label {
      opacity: 0; max-width: 0;
    }
    .gt-sidebar.is-collapsed .sb-logout::after {
      content: attr(data-tip);
      position: fixed;
      left: calc(var(--sb-icon) + 10px);
      background: #1e3a5f; color: #e2e8f0;
      font-size: 0.78rem; font-weight: 500;
      padding: 5px 11px; border-radius: 6px;
      white-space: nowrap; pointer-events: none;
      opacity: 0; z-index: 9999;
      box-shadow: 0 4px 14px rgba(0,0,0,.45);
      transform: translateY(-50%) translateX(4px);
      transition: opacity 0.12s;
    }
    .gt-sidebar.is-collapsed .sb-logout:hover::after { opacity: 1; }

    /* ── Collapse toggle (in topbar, always visible) ── */
    .sb-toggle-btn {
      width: 36px; height: 36px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: transparent;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      color: #475569;
      transition: background 0.16s, color 0.16s;
      flex-shrink: 0;
    }
    .sb-toggle-btn:hover { background: #f1f5f9; color: #0f172a; }
    .sb-toggle-btn svg {
      transition: transform var(--sb-transition);
    }
    .gt-sidebar.is-collapsed ~ .gt-admin-main .sb-toggle-btn svg {
      transform: rotate(180deg);
    }

    /* ─────────────────────────────────────
       MAIN COLUMN
    ───────────────────────────────────── */
    .gt-admin-main {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* ── Topbar (sticky, does NOT scroll) ── */
    .gt-topbar {
      height: 64px;
      background: #fff;
      border-bottom: 1px solid #e2e8f0;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 1.5rem;
      flex-shrink: 0;
      z-index: 100;
      gap: 12px;
    }

    /* ── Page content scrolls here ── */
    .gt-content-scroll {
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
    }

    .gt-content-wrapper {
      padding: 2rem 1.75rem;
      max-width: 1440px;
      width: 100%;
      margin: 0 auto;
    }

    /* ─────────────────────────────────────
       MOBILE OVERLAY
    ───────────────────────────────────── */
    .sb-overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.55);
      z-index: 199;
      backdrop-filter: blur(2px);
    }
    .sb-overlay.open { display: block; }

    /* ─────────────────────────────────────
       MOBILE  (≤ 900 px)
    ───────────────────────────────────── */
    @media (max-width: 900px) {
      .gt-sidebar {
        position: fixed;
        top: 0; left: 0;
        width: var(--sb-w) !important;
        min-width: var(--sb-w) !important;
        transform: translateX(-100%);
        transition: transform var(--sb-transition);
        z-index: 300;
      }
      .gt-sidebar.mobile-open { transform: translateX(0); }

      /* Always show labels on mobile, even if collapsed class exists */
      .gt-sidebar .sb-brand-text,
      .gt-sidebar .sb-user-info,
      .gt-sidebar .sb-link-label,
      .gt-sidebar .sb-section,
      .gt-sidebar .sb-logout .sb-link-label { opacity: 1 !important; max-width: 200px !important; max-height: 30px !important; padding: revert !important; }
    }

    /* ─────────────────────────────────────
       SKIP LINK
    ───────────────────────────────────── */
    .gt-skip-link {
      position: absolute; top: -100px; left: 16px;
      background: #0284c7; color: #fff;
      padding: 8px 16px; border-radius: 0 0 8px 8px;
      font-size: 0.875rem; font-weight: 600;
      z-index: 9999; text-decoration: none;
    }
    .gt-skip-link:focus { top: 0; }
  </style>
</head>
<body>

<a href="#admin-main-content" class="gt-skip-link">Skip to main content</a>
<div class="sb-overlay" id="sb-overlay"></div>

<div class="gt-admin-shell">

  <!-- ═══════════════ SIDEBAR ═══════════════ -->
  <aside class="gt-sidebar" id="admin-sidebar" aria-label="Admin navigation">

    <!-- Brand -->
    <div class="sb-brand">
      <div class="sb-brand-logo">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
          <line x1="12" y1="22.08" x2="12" y2="12"/>
        </svg>
      </div>
      <span class="sb-brand-text">GaaTi<span>Track</span></span>
    </div>

    <!-- User -->
    <div class="sb-user">
      <div class="sb-avatar"><?php echo strtoupper(substr($_SESSION['login_name'] ?? 'S', 0, 1)); ?></div>
      <div class="sb-user-info">
        <div class="sb-user-name"><?php echo e($_SESSION['login_name'] ?? 'Staff Member'); ?></div>
        <div class="sb-user-role">
          <span class="sb-role-dot"></span>
          <?php echo is_admin() ? 'Administrator' : 'Branch Staff'; ?>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="sb-nav" id="sidebar-nav" aria-label="Main navigation">

      <?php foreach ($navGroups as $group): ?>
        <?php if (!empty($group['admin_only']) && !is_admin()) continue; ?>
        <div class="sb-section"><?php echo e($group['label']); ?></div>
        <?php foreach ($group['items'] as $item): ?>
          <a href="<?php echo APP_URL; ?>/admin/index.php?page=<?php echo $item['page']; ?>"
             class="sb-link <?php echo $activePage === $item['page'] ? 'active' : ''; ?>"
             data-tip="<?php echo e($item['label']); ?>"
             <?php echo $activePage === $item['page'] ? 'aria-current="page"' : ''; ?>>
            <span class="sb-link-icon"><?php echo nav_icon($item['icon']); ?></span>
            <span class="sb-link-label"><?php echo e($item['label']); ?></span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>

      <!-- External -->
      <div class="sb-section">External</div>
      <a href="<?php echo APP_URL; ?>/index.php" target="_blank" rel="noopener"
         class="sb-link" data-tip="Public Website">
        <span class="sb-link-icon"><?php echo nav_icon('globe'); ?></span>
        <span class="sb-link-label">Public Website ↗</span>
      </a>

    </nav>

    <!-- Logout -->
    <div class="sb-footer">
      <a href="<?php echo APP_URL; ?>/logout.php" class="sb-logout" data-tip="Logout">
        <span class="sb-link-icon"><?php echo nav_icon('logout'); ?></span>
        <span class="sb-link-label">Logout</span>
      </a>
    </div>

  </aside><!-- /sidebar -->

  <!-- ═══════════════ MAIN COLUMN ═══════════════ -->
  <div class="gt-admin-main" id="admin-main">

    <!-- Topbar -->
    <header class="gt-topbar">
      <div style="display:flex;align-items:center;gap:10px;">

        <!-- Sidebar toggle (desktop collapse / mobile open) -->
        <button class="sb-toggle-btn" id="sb-toggle-btn"
                aria-label="Toggle sidebar" aria-expanded="false" aria-controls="admin-sidebar">
          <?php echo nav_icon('menu'); ?>
        </button>

        <!-- Quick search -->
        <form action="<?php echo APP_URL; ?>/admin/index.php" method="GET"
              style="display:flex;align-items:center;gap:6px;">
          <input type="hidden" name="page" value="parcels">
          <div style="position:relative;display:flex;align-items:center;">
            <span style="position:absolute;left:9px;color:#94a3b8;pointer-events:none;line-height:0;">
              <?php echo nav_icon('search'); ?>
            </span>
            <input type="search" name="q" placeholder="Search reference #..."
                   class="gt-input"
                   style="padding:.4rem .75rem .4rem 2rem;font-size:.8125rem;width:200px;border-radius:8px;"
                   value="<?php echo e($_GET['q'] ?? ''); ?>">
          </div>
          <button type="submit" class="gt-btn gt-btn-secondary gt-btn-sm">Search</button>
        </form>
      </div>

      <!-- Right side -->
      <div style="display:flex;align-items:center;gap:12px;">
        <span style="font-size:.8rem;color:#64748b;white-space:nowrap;">
          Signed in as <strong style="color:#1e293b;"><?php echo e($_SESSION['login_name'] ?? ''); ?></strong>
        </span>
        <a href="<?php echo APP_URL; ?>/logout.php"
           style="display:flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid #fca5a5;
                  border-radius:7px;color:#dc2626;font-size:.8rem;font-weight:600;text-decoration:none;
                  transition:background .16s;white-space:nowrap;"
           onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
          <?php echo nav_icon('logout'); ?> Logout
        </a>
      </div>
    </header>

    <!-- Scrollable content pane -->
    <div class="gt-content-scroll">
      <main class="gt-content-wrapper" id="admin-main-content">

        <?php if (!empty($flashSuccess)): ?>
          <div class="gt-alert gt-alert-success" style="margin-bottom:1.5rem;">
            <span style="font-weight:700;">Success:</span> <?php echo e($flashSuccess); ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($flashError)): ?>
          <div class="gt-alert gt-alert-error" style="margin-bottom:1.5rem;">
            <span style="font-weight:700;">Alert:</span> <?php echo e($flashError); ?>
          </div>
        <?php endif; ?>
