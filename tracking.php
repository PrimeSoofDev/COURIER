<?php
/**
 * GaaTiTrack - Dedicated Public Tracking Portal
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/geocoding.php';

// Rate Limiting (Prevent automated enumeration)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isRateLimited = false;
$now = time();
if (!isset($_SESSION['track_rate'])) {
    $_SESSION['track_rate'] = ['count' => 1, 'start' => $now];
} else {
    if ($now - $_SESSION['track_rate']['start'] < 60) {
        $_SESSION['track_rate']['count']++;
        if ($_SESSION['track_rate']['count'] > 35) {
            $isRateLimited = true;
        }
    } else {
        $_SESSION['track_rate'] = ['count' => 1, 'start' => $now];
    }
}

$trackingRef = isset($_GET['ref']) ? trim($_GET['ref']) : '';
$searched = false;
$errorMsg = '';
$parcel = null;
$trackingEvents = [];
$originBranch = null;
$destBranch = null;

if (!empty($trackingRef)) {
    $searched = true;
    if ($isRateLimited) {
        $errorMsg = 'Too many tracking requests. Please wait a minute and try again.';
    } elseif (!preg_match('/^[a-zA-Z0-9\-_]{6,30}$/', $trackingRef)) {
        $errorMsg = 'Invalid tracking reference format. Please enter a valid 12-digit tracking number.';
    } else {
        // Query shipment using secure prepared statement
        try {
            $stmt = $pdo->prepare("SELECT * FROM parcels WHERE reference_number = :ref LIMIT 1");
            $stmt->execute([':ref' => $trackingRef]);
            $parcel = $stmt->fetch();

            if (!$parcel) {
                $errorMsg = "No shipment found matching tracking number '{$trackingRef}'. Please verify the number from your dispatch receipt.";
            } else {
                // Fetch origin branch
                if (!empty($parcel['from_branch_id'])) {
                    $bStmt = $pdo->prepare("SELECT branch_code, city, state, country, contact FROM branches WHERE id = :id");
                    $bStmt->execute([':id' => $parcel['from_branch_id']]);
                    $originBranch = $bStmt->fetch();
                }

                // Fetch destination branch
                if (!empty($parcel['to_branch_id'])) {
                    $bStmt = $pdo->prepare("SELECT branch_code, city, state, country, contact FROM branches WHERE id = :id");
                    $bStmt->execute([':id' => $parcel['to_branch_id']]);
                    $destBranch = $bStmt->fetch();
                }

                // Fetch tracking event history
                $tStmt = $pdo->prepare("SELECT status, date_created FROM parcel_tracks WHERE parcel_id = :pid ORDER BY date_created ASC, id ASC");
                $tStmt->execute([':pid' => $parcel['id']]);
                $history = $tStmt->fetchAll();

                // Initial event (Booking / Acceptance)
                $trackingEvents[] = [
                    'status_code' => 0,
                    'status_label' => 'Item Accepted by Courier',
                    'date' => $parcel['date_created'],
                    'note' => 'Consignment booked & recorded at ' . ($originBranch ? $originBranch['city'] . ' Hub' : 'Origin Hub')
                ];

                foreach ($history as $h) {
                    $trackingEvents[] = [
                        'status_code' => (int)$h['status'],
                        'status_label' => get_status_label((int)$h['status']),
                        'date' => $h['date_created'],
                        'note' => 'Consignment status updated to ' . get_status_label((int)$h['status'])
                    ];
                }
            }
        } catch (PDOException $e) {
            app_log("Tracking search query error: " . $e->getMessage(), 'ERROR');
            $errorMsg = 'A temporary database error occurred while querying the tracking system. Please try again.';
        }
    }
}

// Sample reference for visitors
$sampleTracking = '201406231415';
try {
    $sampleStmt = $pdo->query("SELECT reference_number FROM parcels ORDER BY id DESC LIMIT 1");
    if ($sampleRow = $sampleStmt->fetch()) {
        $sampleTracking = $sampleRow['reference_number'];
    }
} catch (Exception $e) {}

$currentPage = 'tracking';
$pageTitle = !empty($parcel) ? "Tracking #{$trackingRef}" : 'Live Parcel Tracking';
require_once __DIR__ . '/includes/public_header.php';
?>
<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/plugins/leaflet/leaflet.css">
<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/tracking-map.css">

<!-- Premium Glassmorphism Tracking Hero Section -->
<section class="gt-hero-section">
  <div class="gt-hero-glow gt-hero-glow-1" style="right:5%;top:-80px;background:radial-gradient(circle,rgba(37,99,235,0.45) 0%,transparent 70%);" aria-hidden="true"></div>
  <div class="gt-hero-glow gt-hero-glow-2" style="left:0;bottom:-60px;background:radial-gradient(circle,rgba(103,232,249,0.22) 0%,transparent 70%);" aria-hidden="true"></div>
  <div class="gt-hero-pattern" aria-hidden="true"></div>

  <div class="gt-hero-container">
    
    <!-- Glass Breadcrumbs -->
    <div class="gt-glass-breadcrumb gt-anim-fade-up">
      <a href="<?php echo APP_URL; ?>/index.php">Home</a>
      <span class="sep">/</span>
      <span class="current">Shipment Tracking</span>
    </div>

    <div class="gt-hero-grid" style="align-items: center;">
      
      <!-- Left Column: Copy & Tracking Confidence Features -->
      <div class="gt-anim-fade-up-d1">
        <div class="gt-glass-badge">
          <span class="gt-glass-badge-dot"></span>
          REAL-TIME CONSIGNMENT TRACKER
        </div>

        <h1 class="gt-hero-title">
          Track Your Shipment. <span class="gt-gradient-text">Stay in Control.</span>
        </h1>

        <p class="gt-hero-desc">
          Get visibility into your shipment's journey with our tracking system. Monitor verified milestone transitions from initial booking scan through sorting and final doorstep delivery.
        </p>

        <!-- Trust Features List -->
        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 1.5rem;">
          <div style="display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: #e2e8f0;">
            <div style="width: 24px; height: 24px; border-radius: 50%; background: rgba(56,189,248,0.18); color: var(--gt-hero-cyan); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0;">✓</div>
            <span><strong>Instant Event Sync:</strong> Hub scanning logs update public records immediately.</span>
          </div>
          <div style="display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: #e2e8f0;">
            <div style="width: 24px; height: 24px; border-radius: 50%; background: rgba(56,189,248,0.18); color: var(--gt-hero-cyan); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0;">✓</div>
            <span><strong>Recipient Privacy Shield:</strong> Phone numbers and names are masked to prevent identity disclosure.</span>
          </div>
          <div style="display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: #e2e8f0;">
            <div style="width: 24px; height: 24px; border-radius: 50%; background: rgba(56,189,248,0.18); color: var(--gt-hero-cyan); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0;">✓</div>
            <span><strong>Multi-Stage Stepper:</strong> Clear visual progress across Accepted, Shipped, In-Transit, and Delivered.</span>
          </div>
        </div>

      </div>

      <!-- Right Column: Prominent Glass Tracking Console & Fleet Visual -->
      <div class="gt-anim-fade-up-d2">
        <div class="gt-glass-card" style="padding: 2rem; border-color: rgba(56,189,248,0.3); box-shadow: 0 25px 60px -10px rgba(0,0,0,0.5);">
          
          <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1.25rem;">
            <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(37,99,235,0.25); border: 1px solid rgba(56,189,248,0.35); display: flex; align-items: center; justify-content: center; color: var(--gt-hero-cyan); flex-shrink: 0;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
            </div>
            <div>
              <div style="font-size: 1.1rem; font-weight: 700; color: #ffffff; font-family: var(--gt-font-display);">Consignment Lookup Console</div>
              <div style="font-size: 0.78rem; color: var(--gt-hero-muted);">Live connection to central freight registry</div>
            </div>
          </div>

          <!-- Tracking Form with Real Backend Connection -->
          <form action="<?php echo APP_URL; ?>/tracking.php" method="GET" id="hero-tracking-form">
            <div style="margin-bottom: 1rem;">
              <label for="tracking-input" style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                12-Digit Reference Number <span style="color: var(--gt-hero-cyan);">*</span>
              </label>
              <div style="position: relative;">
                <input 
                  type="text" 
                  name="ref" 
                  id="tracking-input"
                  value="<?php echo e($trackingRef); ?>"
                  placeholder="e.g. <?php echo e($sampleTracking); ?>" 
                  required 
                  autocomplete="off"
                  aria-label="Tracking Reference Number"
                  pattern="[a-zA-Z0-9\-_]{6,30}"
                  title="Please enter a valid tracking reference number"
                  style="width: 100%; padding: 12px 14px 12px 42px; background: rgba(7,20,38,0.7); border: 1.5px solid rgba(255,255,255,0.18); border-radius: 10px; font-size: 0.95rem; color: #ffffff; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                  onfocus="this.style.borderColor='var(--gt-hero-electric)'; this.style.boxShadow='0 0 0 3px rgba(56,189,248,0.2)';"
                  onblur="this.style.borderColor='rgba(255,255,255,0.18)'; this.style.boxShadow='none';"
                >
                <div style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gt-hero-muted); pointer-events: none;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                  </svg>
                </div>
              </div>
            </div>

            <button type="submit" id="track-btn" class="gt-btn-hero-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 0.95rem;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              Track Consignment
            </button>
          </form>

          <!-- Quick Test Link -->
          <div style="margin-top: 1rem; padding-top: 0.85rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.78rem; color: var(--gt-hero-muted); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <span>Sample reference:</span>
            <a href="<?php echo APP_URL; ?>/tracking.php?ref=<?php echo urlencode($sampleTracking); ?>" style="color: var(--gt-hero-cyan); text-decoration: underline; font-weight: 600;">
              <?php echo e($sampleTracking); ?>
            </a>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<!-- Tracking Results Area -->
<section style="padding: 3.5rem 0 5rem 0;">
  <div class="gt-container">

    <?php if ($searched && !empty($errorMsg)): ?>
      <!-- Error / Unknown State -->
      <div class="gt-alert gt-alert-warning" style="margin-bottom: 2rem;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
          <div style="font-weight: 700;">Consignment Not Found</div>
          <div style="font-size: 0.875rem;"><?php echo e($errorMsg); ?></div>
        </div>
      </div>

      <?php 
      echo render_empty_state(
        'Check Your Reference Number', 
        'Please confirm that the 12-digit number matches your dispatch receipt. If you recently dropped off your package, it may take a few minutes to be accepted into the digital system.',
        '<a href="' . APP_URL . '/contact.php" class="gt-btn gt-btn-secondary gt-btn-sm">Contact Branch Support</a>'
      ); 
      ?>

    <?php elseif ($searched && $parcel): ?>
      <!-- Successful Result State -->
      <?php 
        $statusCode = (int)$parcel['status'];
        $statusLabel = get_status_label($statusCode);
        $isDelivered = in_array($statusCode, [7, 8], true);
        $isFailed = ($statusCode === 9);

        // Helper to mask recipient name for privacy protection
        $recipientParts = explode(' ', trim($parcel['recipient_name'] ?? 'Recipient'));
        $maskedRecipient = e($recipientParts[0]);
        if (isset($recipientParts[1])) {
            $maskedRecipient .= ' ' . substr($recipientParts[1], 0, 1) . '***';
        }

        // Geocoding Coordinates & Route Generation
        $originCoords = resolve_branch_coordinates($originBranch, $parcel['sender_address']);
        $destCoords = resolve_destination_coordinates($destBranch, $parcel['recipient_address']);
        if (!$destCoords) {
            $destCoords = [
                'lat' => 41.8789,
                'lng' => -87.6359,
                'city' => 'Chicago',
                'state' => 'IL',
                'label' => 'Destination Area',
                'address' => $parcel['recipient_address'],
                'verified' => false
            ];
        }

        $routeWaypoints = calculate_route_waypoints($originCoords, $destCoords, 60);
        $progressPercent = get_milestone_route_progress($statusCode);
        $progressPercentDisplay = round($progressPercent * 100);
        $waypointIndex = (int)round($progressPercent * (count($routeWaypoints) - 1));
        $currentPos = $routeWaypoints[$waypointIndex] ?? $originCoords;
        $estDelivery = date('M d, Y', strtotime($parcel['date_created']) + (3 * 86400));
        $latestEventTime = !empty($trackingEvents) ? end($trackingEvents)['date'] : $parcel['date_created'];

        // JSON payload for Leaflet and live updates
        $mapPayload = [
            'ref' => $parcel['reference_number'],
            'status_code' => $statusCode,
            'status_label' => $statusLabel,
            'is_delivered' => $isDelivered,
            'origin' => $originCoords,
            'destination' => $destCoords,
            'current_pos' => $currentPos,
            'waypoints' => $routeWaypoints,
            'progress_percent' => $progressPercentDisplay,
            'est_delivery' => $estDelivery,
            'latest_update' => date('M d, Y - h:i A', strtotime($latestEventTime))
        ];
      ?>

      <!-- 1. Executive Shipment Summary Cards Grid -->
      <div class="gt-summary-grid">
        <div class="gt-summary-card">
          <div class="gt-summary-label">Consignment Reference</div>
          <div class="gt-summary-val" style="display:flex;align-items:center;justify-content:space-between;">
            <span id="summary-ref"><?php echo e($parcel['reference_number']); ?></span>
            <span class="gt-live-beacon" title="Live database synchronization active">
              <span class="gt-live-beacon-dot"></span> Sync
            </span>
          </div>
          <div class="gt-summary-sub">Registered: <?php echo date('M d, Y', strtotime($parcel['date_created'])); ?></div>
        </div>

        <div class="gt-summary-card">
          <div class="gt-summary-label">Current Shipment Status</div>
          <div class="gt-summary-val" id="summary-status-badge">
            <?php echo render_status_badge($statusCode); ?>
          </div>
          <div class="gt-summary-sub" id="summary-latest-note">
            <?php echo !empty($trackingEvents) ? e(end($trackingEvents)['status_label']) : 'Accepted at origin'; ?>
          </div>
        </div>

        <div class="gt-summary-card">
          <div class="gt-summary-label">Route Corridor</div>
          <div class="gt-summary-val" style="font-size:1.1rem;color:var(--gt-navy-900);">
            <?php echo e($originCoords['city']); ?> &rarr; <?php echo e($destCoords['city']); ?>
          </div>
          <div class="gt-summary-sub">
            <?php echo $parcel['type'] == 2 ? 'Branch Counter Pickup' : 'Express Doorstep Delivery'; ?>
          </div>
        </div>

        <div class="gt-summary-card">
          <div class="gt-summary-label">Estimated Delivery</div>
          <div class="gt-summary-val" style="color:<?php echo $isDelivered ? '#16a34a' : '#0284c7'; ?>;">
            <?php echo $isDelivered ? 'Delivered' : e($estDelivery); ?>
          </div>
          <div class="gt-summary-sub">
            <?php echo $isDelivered ? 'Consignment Signed & Closed' : 'Standard 2–3 Business Days'; ?>
          </div>
        </div>
      </div>

      <!-- 2. Interactive Live Shipment Map Dashboard -->
      <div class="gt-live-map-card">
        <div class="gt-live-map-header">
          <div class="gt-live-map-title-wrap">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--gt-map-electric)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
              <line x1="8" y1="2" x2="8" y2="18"></line>
              <line x1="16" y1="6" x2="16" y2="22"></line>
            </svg>
            <div style="font-weight:700;font-size:1.05rem;color:#ffffff;font-family:var(--gt-font-display);">
              Interactive Route Map & GPS Transit Simulation
            </div>
          </div>

          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <button type="button" class="gt-map-btn" id="btn-replay-journey" title="Animate parcel marker along route">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
              Replay Route
            </button>
            <button type="button" class="gt-map-btn" id="btn-recenter-map" title="Fit entire shipment route in view">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
              Recenter
            </button>
            <button type="button" class="gt-map-btn" id="btn-refresh-live" title="Check live server for status updates">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" id="refresh-spin"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
              Sync Status
            </button>
          </div>
        </div>

        <!-- Canvas Wrapper -->
        <div class="gt-map-canvas-wrap">
          <div id="gt-shipment-map" role="region" aria-label="Interactive Shipment Transit Map"></div>

          <!-- Floating Glass HUD Panel -->
          <div class="gt-map-hud-panel" id="map-hud-panel">
            <div class="gt-map-hud-header">
              <div class="gt-map-hud-ref"><?php echo e($parcel['reference_number']); ?></div>
              <span id="hud-status-badge"><?php echo render_status_badge($statusCode); ?></span>
            </div>

            <div class="gt-map-hud-metric">
              <div class="gt-map-hud-metric-icon" style="background:rgba(56,189,248,0.2);color:#38bdf8;">🏢</div>
              <div>
                <div style="font-size:0.7rem;color:#94a3b8;text-transform:uppercase;font-weight:700;">Origin Hub</div>
                <div style="font-weight:600;color:#ffffff;"><?php echo e($originCoords['label']); ?></div>
              </div>
            </div>

            <div class="gt-map-hud-metric">
              <div class="gt-map-hud-metric-icon" style="background:rgba(34,197,94,0.2);color:#4ade80;">📍</div>
              <div>
                <div style="font-size:0.7rem;color:#94a3b8;text-transform:uppercase;font-weight:700;">Destination</div>
                <div style="font-weight:600;color:#ffffff;"><?php echo e($destCoords['label']); ?></div>
              </div>
            </div>

            <div class="gt-map-hud-metric" style="margin-bottom:0;">
              <div class="gt-map-hud-metric-icon" style="background:rgba(255,255,255,0.1);color:#ffffff;">⏱</div>
              <div>
                <div style="font-size:0.7rem;color:#94a3b8;text-transform:uppercase;font-weight:700;">Last Verified Milestone</div>
                <div style="font-weight:500;color:#e2e8f0;font-size:0.78rem;" id="hud-latest-time"><?php echo date('M d, Y - h:i A', strtotime($latestEventTime)); ?></div>
              </div>
            </div>
          </div>

          <!-- Map Legend Panel -->
          <div class="gt-map-legend-panel">
            <div class="gt-legend-item">
              <span class="gt-legend-dot" style="background:#38bdf8;box-shadow:0 0 6px #38bdf8;"></span>
              <span>Origin Hub</span>
            </div>
            <div class="gt-legend-item">
              <span class="gt-legend-line-done"></span>
              <span>Completed Transit</span>
            </div>
            <div class="gt-legend-item">
              <span class="gt-legend-line-pending"></span>
              <span>Scheduled Route</span>
            </div>
            <div class="gt-legend-item">
              <span class="gt-legend-dot" style="background:#22c55e;box-shadow:0 0 6px #22c55e;"></span>
              <span>Destination</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Dynamic 7-Stage Milestone Stepper -->
      <div class="gt-milestone-stepper">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:8px;">
          <div>
            <h3 style="margin:0 0 2px 0;font-size:1.15rem;color:#0f172a;font-family:var(--gt-font-display);">Milestone Progress Tracker</h3>
            <div style="font-size:0.8rem;color:#64748b;">Current transit lifecycle scan: <strong><?php echo e($statusLabel); ?></strong> (<?php echo $progressPercentDisplay; ?>% Complete)</div>
          </div>
          <span style="font-size:0.78rem;font-weight:700;color:#0284c7;background:#e0f2fe;padding:4px 12px;border-radius:9999px;">
            Step <?php echo $statusCode >= 7 ? '7 of 7' : ($statusCode + 1) . ' of 7'; ?>
          </span>
        </div>

        <div class="gt-stepper-track">
          <div class="gt-stepper-line"></div>
          <div class="gt-stepper-fill" id="stepper-fill" style="width:<?php echo max(5, min(95, $progressPercentDisplay)); ?>%;"></div>

          <?php 
            $lifecycleSteps = [
              0 => ['label' => 'Accepted', 'icon' => '📦', 'code' => 0],
              1 => ['label' => 'Collected', 'icon' => '🏢', 'code' => 1],
              2 => ['label' => 'Shipped', 'icon' => '🚛', 'code' => 2],
              3 => ['label' => 'In-Transit', 'icon' => '🛣️', 'code' => 3],
              4 => ['label' => 'Arrived Hub', 'icon' => '🏬', 'code' => 4],
              5 => ['label' => ($parcel['type'] == 2 ? 'Ready Pickup' : 'Out for Delivery'), 'icon' => '📍', 'code' => 5],
              7 => ['label' => ($parcel['type'] == 2 ? 'Picked Up' : 'Delivered'), 'icon' => '✅', 'code' => 7]
            ];
            foreach ($lifecycleSteps as $stepCode => $sInfo):
              $isStepDone = ($statusCode >= $stepCode);
              $isStepCurrent = ($statusCode === $stepCode || ($statusCode === 6 && $stepCode === 5) || ($statusCode === 8 && $stepCode === 7));
              $nodeClass = $isStepDone ? 'completed' : '';
              if ($isStepCurrent) $nodeClass = 'active';
              if ($stepCode === 7 && $isDelivered) $nodeClass = 'delivered';
          ?>
            <div class="gt-stepper-node <?php echo $nodeClass; ?>" id="step-node-<?php echo $stepCode; ?>">
              <div class="gt-stepper-circle">
                <?php echo $sInfo['icon']; ?>
              </div>
              <div class="gt-stepper-label"><?php echo $sInfo['label']; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 4. Consignment Route & Specifications Detail Grid -->
      <div class="gt-card" style="margin-bottom: 2rem;">
        <div class="gt-card-body" style="padding: 1.75rem;">
          <h3 style="font-size:1.1rem;margin:0 0 1.25rem 0;color:var(--gt-navy-950);">Consignment Specifications & Custody</h3>
          
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; background: var(--gt-bg-muted); padding: 1.5rem; border-radius: var(--gt-radius-md);">
            <div>
              <div style="font-size: 0.75rem; color: var(--gt-navy-500); text-transform: uppercase; font-weight: 600;">Origin Hub</div>
              <div style="font-weight: 700; color: var(--gt-navy-900); font-size: 0.95rem; margin-top: 0.25rem;">
                <?php echo e($originCoords['label']); ?>
              </div>
              <div style="font-size: 0.8125rem; color: var(--gt-navy-500);">
                <?php echo date('M d, Y', strtotime($parcel['date_created'])); ?>
              </div>
            </div>

            <div>
              <div style="font-size: 0.75rem; color: var(--gt-navy-500); text-transform: uppercase; font-weight: 600;">Destination Hub</div>
              <div style="font-weight: 700; color: var(--gt-navy-900); font-size: 0.95rem; margin-top: 0.25rem;">
                <?php echo e($destCoords['label']); ?>
              </div>
              <div style="font-size: 0.8125rem; color: var(--gt-navy-500);">
                <?php echo $parcel['type'] == 1 ? 'Doorstep Delivery' : 'Branch Pickup'; ?>
              </div>
            </div>

            <div>
              <div style="font-size: 0.75rem; color: var(--gt-navy-500); text-transform: uppercase; font-weight: 600;">Consignment Specs</div>
              <div style="font-weight: 700; color: var(--gt-navy-900); font-size: 0.95rem; margin-top: 0.25rem;">
                <?php echo e($parcel['weight'] ?: 'Standard Weight'); ?>
              </div>
              <div style="font-size: 0.8125rem; color: var(--gt-navy-500);">
                <?php echo e($parcel['length'] . 'x' . $parcel['width'] . 'x' . $parcel['height']); ?>
              </div>
            </div>

            <div>
              <div style="font-size: 0.75rem; color: var(--gt-navy-500); text-transform: uppercase; font-weight: 600;">Recipient</div>
              <div style="font-weight: 700; color: var(--gt-navy-900); font-size: 0.95rem; margin-top: 0.25rem;">
                <?php echo $maskedRecipient; ?>
              </div>
              <div style="font-size: 0.8125rem; color: var(--gt-navy-500);">
                <?php echo $isDelivered ? 'Delivered' : 'In Progress'; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 5. Chronological Transit Milestone History Timeline -->
      <div class="gt-card">
        <div class="gt-card-header" style="display:flex;justify-content:space-between;align-items:center;padding:1.25rem 1.5rem;">
          <h3 style="font-size: 1.15rem; margin: 0;">Transit Milestone History</h3>
          <span style="font-size: 0.8125rem; color: var(--gt-navy-500);" id="timeline-count"><?php echo count($trackingEvents); ?> recorded event(s)</span>
        </div>
        <div class="gt-card-body" style="padding:1.5rem;">
          <div class="gt-timeline" id="gt-timeline-list">
            <?php 
              // Reverse chronological display so latest update is on top
              $reversedEvents = array_reverse($trackingEvents);
              foreach ($reversedEvents as $idx => $event): 
                $isLatest = ($idx === 0);
            ?>
              <div class="gt-timeline-item <?php echo $isLatest ? 'is-completed' : ''; ?>">
                <div class="gt-timeline-dot"></div>
                <div class="gt-timeline-content" style="<?php echo $isLatest ? 'border-color: var(--gt-primary); background: #f0f9ff;' : ''; ?>">
                  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <div class="gt-timeline-status" style="<?php echo $isLatest ? 'color: var(--gt-primary);' : ''; ?>">
                      <?php echo e($event['status_label']); ?>
                      <?php if ($isLatest): ?>
                        <span class="gt-badge gt-badge-primary" style="font-size: 0.7rem; margin-left: 6px;">Latest Update</span>
                      <?php endif; ?>
                    </div>
                    <div class="gt-timeline-time">
                      <?php echo date('M d, Y - h:i A', strtotime($event['date'])); ?>
                    </div>
                  </div>
                  <div style="font-size: 0.875rem; color: var(--gt-navy-600); margin-top: 0.35rem;">
                    <?php echo e($event['note']); ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Interactive Map & Real-time Live Polling Script -->
      <script src="<?php echo APP_URL; ?>/assets/plugins/leaflet/leaflet.js"></script>
      <script>
      (function() {
        var mapData = <?php echo json_encode($mapPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        if (!mapData || !mapData.origin || !mapData.destination) return;

        function escapeHtml(str) {
          if (!str) return '';
          return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
          });
        }

        // Initialize Map
        var mapContainer = document.getElementById('gt-shipment-map');
        if (!mapContainer) return;

        var map = L.map('gt-shipment-map', {
          zoomControl: false,
          attributionControl: true
        }).setView([mapData.current_pos.lat, mapData.current_pos.lng], 5);

        // CartoDB Voyager tiles (clear, modern, high performance)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> &copy; <a href="https://carto.com/" target="_blank">CARTO</a>',
          subdomains: 'abcd',
          maxZoom: 19
        }).addTo(map);

        // Add Zoom Control to Bottom-Right
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // Markers & Polylines State
        var origin = mapData.origin;
        var destination = mapData.destination;
        var waypoints = mapData.waypoints || [];
        var currentPos = mapData.current_pos;

        // Origin Marker (Logistics Hub)
        var originIcon = L.divIcon({
          className: 'gt-custom-icon',
          html: '<div class="gt-marker-hub" title="Origin: ' + escapeHtml(origin.city) + '">🏢</div>',
          iconSize: [36, 36],
          iconAnchor: [18, 18]
        });
        L.marker([origin.lat, origin.lng], { icon: originIcon })
          .addTo(map)
          .bindPopup('<div class="gt-map-popup"><div class="gt-map-popup-title">' + escapeHtml(origin.label) + '</div><div class="gt-map-popup-desc">Origin Dispatch Hub</div></div>');

        // Destination Marker
        var destIcon = L.divIcon({
          className: 'gt-custom-icon',
          html: '<div class="gt-marker-destination" title="Destination: ' + escapeHtml(destination.city) + '">' + (mapData.is_delivered ? '✅' : '📍') + '</div>',
          iconSize: [36, 36],
          iconAnchor: [18, 18]
        });
        L.marker([destination.lat, destination.lng], { icon: destIcon })
          .addTo(map)
          .bindPopup('<div class="gt-map-popup"><div class="gt-map-popup-title">' + escapeHtml(destination.label) + '</div><div class="gt-map-popup-desc">Delivery Destination Hub</div></div>');

        // Route Splitting & Polylines
        var completedIndex = Math.round((mapData.progress_percent / 100) * (waypoints.length - 1));
        var completedPts = waypoints.slice(0, completedIndex + 1);
        var remainingPts = waypoints.slice(completedIndex);

        var remainingPolyline = null;
        if (remainingPts.length > 1) {
          remainingPolyline = L.polyline(remainingPts.map(function(p){ return [p.lat, p.lng]; }), {
            color: '#94a3b8',
            weight: 3.5,
            dashArray: '6, 8',
            opacity: 0.65
          }).addTo(map);
        }

        var completedPolyline = null;
        if (completedPts.length > 1) {
          completedPolyline = L.polyline(completedPts.map(function(p){ return [p.lat, p.lng]; }), {
            color: '#0284c7',
            weight: 4.5,
            opacity: 0.95
          }).addTo(map);
        }

        // Animated Vehicle / Parcel Marker
        var vehicleIcon = L.divIcon({
          className: 'gt-custom-vehicle',
          html: '<div class="gt-marker-vehicle" id="gt-vehicle-marker"><div class="gt-marker-pulse-ring"></div><span>' + (mapData.is_delivered ? '📦' : '🚚') + '</span></div>',
          iconSize: [44, 44],
          iconAnchor: [22, 22]
        });
        var vehicleMarker = L.marker([currentPos.lat, currentPos.lng], { icon: vehicleIcon })
          .addTo(map)
          .bindPopup('<div class="gt-map-popup"><div class="gt-map-popup-title">' + escapeHtml(mapData.status_label) + '</div><div class="gt-map-popup-desc">Reported Milestone Position</div></div>');

        // Auto Fit Bounds
        function fitRouteBounds() {
          if (waypoints.length > 0) {
            var bounds = L.latLngBounds(waypoints.map(function(p){ return [p.lat, p.lng]; }));
            map.fitBounds(bounds, { padding: [60, 60], maxZoom: 8 });
          }
        }
        fitRouteBounds();

        // Animated Replay of Parcel Journey
        var isReplaying = false;
        function replayJourney() {
          if (isReplaying || completedIndex <= 0) return;
          if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            vehicleMarker.setLatLng([currentPos.lat, currentPos.lng]);
            return;
          }
          isReplaying = true;
          var step = 0;
          var animInterval = setInterval(function() {
            if (step > completedIndex) {
              clearInterval(animInterval);
              isReplaying = false;
              vehicleMarker.setLatLng([currentPos.lat, currentPos.lng]);
              return;
            }
            var pt = waypoints[step];
            vehicleMarker.setLatLng([pt.lat, pt.lng]);
            step++;
          }, 35);
        }

        // Attach Button Listeners
        var btnReplay = document.getElementById('btn-replay-journey');
        if (btnReplay) btnReplay.addEventListener('click', replayJourney);

        var btnRecenter = document.getElementById('btn-recenter-map');
        if (btnRecenter) btnRecenter.addEventListener('click', fitRouteBounds);

        // Real-Time Background Status Polling (Every 30s)
        var btnRefresh = document.getElementById('btn-refresh-live');
        var spinIcon = document.getElementById('refresh-spin');

        function fetchLiveStatus(manual) {
          if (spinIcon) spinIcon.style.animation = 'gtBeaconPulse 0.8s infinite';

          fetch('<?php echo APP_URL; ?>/api/tracking.php?ref=' + encodeURIComponent(mapData.ref))
            .then(function(res){ return res.json(); })
            .then(function(data){
              if (spinIcon) spinIcon.style.animation = 'none';
              if (!data || !data.success) return;

              // Check if status changed
              if (data.status_code !== mapData.status_code) {
                mapData.status_code = data.status_code;
                mapData.status_label = data.status_label;
                mapData.is_delivered = data.is_delivered;
                mapData.progress_percent = data.progress_percent;

                // Move vehicle marker to new position smoothly
                if (data.current_position) {
                  vehicleMarker.setLatLng([data.current_position.lat, data.current_position.lng]);
                }

                // Update HUD and Summary badges
                var hudBadge = document.getElementById('hud-status-badge');
                if (hudBadge) hudBadge.innerHTML = '<span class="gt-badge gt-badge-' + data.status_code + '">' + escapeHtml(data.status_label) + '</span>';

                var sumBadge = document.getElementById('summary-status-badge');
                if (sumBadge) sumBadge.innerHTML = '<span class="gt-badge gt-badge-' + data.status_code + '">' + escapeHtml(data.status_label) + '</span>';

                var hudTime = document.getElementById('hud-latest-time');
                if (hudTime) hudTime.textContent = data.latest_update;

                // Update Stepper fill
                var stepperFill = document.getElementById('stepper-fill');
                if (stepperFill) stepperFill.style.width = Math.max(5, Math.min(95, data.progress_percent)) + '%';
              }
            })
            .catch(function(err){
              if (spinIcon) spinIcon.style.animation = 'none';
            });
        }

        if (btnRefresh) {
          btnRefresh.addEventListener('click', function() {
            fetchLiveStatus(true);
          });
        }

        // Auto poll every 30 seconds
        setInterval(function() {
          fetchLiveStatus(false);
        }, 30000);

      })();
      </script>

    <?php else: ?>
      <!-- Default Prompt State (When visitor first arrives) -->
      <div style="text-align: center; padding: 2rem 0;">
        <div style="max-width: 500px; margin: 0 auto;">
          <div style="width: 64px; height: 64px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 1.25rem auto;">
            🔍
          </div>
          <h3 style="font-size: 1.35rem; margin-bottom: 0.75rem; color: var(--gt-navy-950);">Enter a Tracking Number Above</h3>
          <p style="color: var(--gt-navy-600); font-size: 0.95rem; line-height: 1.6;">
            Your tracking number can be found on your parcel receipt or shipping notification. Real-time timestamps and event locations will be displayed immediately.
          </p>
        </div>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
