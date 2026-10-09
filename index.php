<?php
/**
 * GaaTiTrack - Modern Public Homepage
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// Handle legacy admin page queries if invoked directly
if (isset($_GET['page'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['login_id'])) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
    // Forward to admin controller
    $adminUrl = APP_URL . '/admin/index.php?page=' . urlencode($_GET['page']);
    if (isset($_GET['s'])) $adminUrl .= '&s=' . urlencode($_GET['s']);
    if (isset($_GET['id'])) $adminUrl .= '&id=' . urlencode($_GET['id']);
    header('Location: ' . $adminUrl);
    exit;
}

// Fetch verified operational statistics from the database
$branchCount = 0;
$branchRes = $conn->query("SELECT COUNT(*) FROM branches");
if ($branchRes) {
    $branchCount = (int)$branchRes->fetch_row()[0];
}

$parcelCount = 0;
$parcelRes = $conn->query("SELECT COUNT(*) FROM parcels");
if ($parcelRes) {
    $parcelCount = (int)$parcelRes->fetch_row()[0];
}

$sampleTracking = '201406231415';
$sampleRes = $conn->query("SELECT reference_number FROM parcels ORDER BY id DESC LIMIT 1");
if ($sampleRes && $row = $sampleRes->fetch_assoc()) {
    $sampleTracking = $row['reference_number'];
}

$currentPage = 'home';
$pageTitle = 'Home - Fast & Transparent Logistics';
require_once __DIR__ . '/includes/public_header.php';
?>

<!-- Premium Glassmorphism Hero Section -->
<section class="gt-hero-section">
  <!-- Ambient glow lighting orbs & grid pattern -->
  <div class="gt-hero-glow gt-hero-glow-1" aria-hidden="true"></div>
  <div class="gt-hero-glow gt-hero-glow-2" aria-hidden="true"></div>
  <div class="gt-hero-pattern" aria-hidden="true"></div>

  <div class="gt-hero-container">
    <div class="gt-hero-grid">
      
      <!-- Left Column: Copy, CTAs & Interactive Glass Tracker -->
      <div class="gt-anim-fade-up">
        <div class="gt-glass-badge">
          <span class="gt-glass-badge-dot"></span>
          SMARTER SHIPPING. BETTER TRACKING.
        </div>

        <h1 class="gt-hero-title">
          Every Delivery. Every Mile. <span class="gt-gradient-text">In View.</span>
        </h1>

        <p class="gt-hero-desc">
          Track, Ship & Deliver with Complete Confidence. GaaTiTrack delivers complete logistics clarity across the United States with real-time tracking, sorting milestones, and express delivery.
        </p>

        <!-- Primary & Secondary CTAs -->
        <div class="gt-hero-actions">
          <a href="<?php echo APP_URL; ?>/tracking.php" class="gt-btn-hero-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            Track Your Shipment
          </a>
          <a href="<?php echo APP_URL; ?>/services.php" class="gt-btn-hero-glass">
            Explore Our Services
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </a>
        </div>

        <!-- Interactive Glass Tracking Box -->
        <div class="gt-glass-search-box">
          <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--gt-hero-electric); font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
              <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
            </svg>
            Quick Consignment Lookup
          </div>
          <form action="<?php echo APP_URL; ?>/tracking.php" method="GET" class="gt-glass-search-form">
            <input 
              type="text" 
              name="ref" 
              class="gt-glass-search-input"
              placeholder="Enter 12-digit tracking number (e.g. <?php echo e($sampleTracking); ?>)" 
              required 
              aria-label="Tracking Reference Number"
              autocomplete="off"
            >
            <button type="submit" class="gt-glass-search-btn">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              Track Now
            </button>
          </form>
          <div style="margin-top: 8px; font-size: 0.76rem; color: var(--gt-hero-muted); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <span>Sample reference: <a href="<?php echo APP_URL; ?>/tracking.php?ref=<?php echo urlencode($sampleTracking); ?>" style="color: var(--gt-hero-cyan); text-decoration: underline; font-weight: 600;"><?php echo e($sampleTracking); ?></a></span>
            <span style="color: rgba(255,255,255,0.4);">•</span>
            <span style="color: #cbd5e1;"><?php echo (int)$branchCount; ?> Active Hubs Nationwide</span>
          </div>
        </div>

      </div>

      <!-- Right Column: Real Photography & Floating Glass Cards -->
      <div class="gt-hero-visual-col gt-anim-fade-up-d1">
        
        <!-- Main Photography Container -->
        <div class="gt-hero-image-frame">
          <img 
            src="<?php echo APP_URL; ?>/assets/images/heroes/hero-home.jpg" 
            alt="GaaTiTrack express courier delivery van and logistics operations"
            fetchpriority="high"
            onerror="this.src='https://images.unsplash.com/photo-1580674684081-7617fbf3d745?auto=format&fit=crop&w=1200&q=80';"
          >
          <div class="gt-hero-image-overlay"></div>
        </div>

        <!-- Top Floating Milestone Badge -->
        <div class="gt-float-card-milestone">
          <div style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 10px #22c55e;"></div>
          <div style="font-size: 0.78rem; font-weight: 700; color: #ffffff;">Live Dispatch Active</div>
          <span style="font-size: 0.7rem; color: var(--gt-hero-cyan); background: rgba(56, 189, 248, 0.15); padding: 2px 7px; border-radius: 9999px;">99.4% On-Time</span>
        </div>

        <!-- Bottom Floating Glass Tracking-Status Card -->
        <div class="gt-float-card-status">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
            <span style="font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--gt-hero-electric);">Sample Tracking Card</span>
            <span style="font-size: 0.68rem; font-weight: 700; color: #22c55e; background: rgba(34, 197, 94, 0.15); padding: 2px 6px; border-radius: 6px;">In Transit</span>
          </div>
          <div style="font-size: 0.95rem; font-weight: 700; color: #ffffff; margin-bottom: 2px; font-family: var(--gt-font-display);">GT-89421-US</div>
          <div style="font-size: 0.75rem; color: var(--gt-hero-muted); margin-bottom: 10px;">New York Hub &rarr; Chicago Hub</div>

          <!-- Mini Progress Bar -->
          <div style="background: rgba(255, 255, 255, 0.12); height: 4px; border-radius: 9999px; overflow: hidden; margin-bottom: 8px;">
            <div style="background: linear-gradient(90deg, #2563eb, #38bdf8); width: 68%; height: 100%; border-radius: 9999px;"></div>
          </div>
          
          <div style="display: flex; align-items: center; gap: 6px; font-size: 0.72rem; color: #cbd5e1;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--gt-hero-cyan)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Departed Sorting Facility (Mile 412)</span>
          </div>
        </div>

      </div>

    </div>
  </div>
</section>

<!-- Services Overview Section -->
<section id="services" style="padding: 5rem 0; background: #ffffff;">
  <div class="gt-container">
    
    <div style="text-align: center; max-width: 680px; margin: 0 auto 3.5rem auto;">
      <div style="color: var(--gt-primary); font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Logistics Solutions</div>
      <h2 style="font-size: 2.1rem; margin-bottom: 1rem;">Tailored Shipping Services for Every Need</h2>
      <p style="color: var(--gt-navy-600); font-size: 1.05rem;">
        From fast courier delivery to scheduled regional branch transfers, we provide safe, end-to-end transportation.
      </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.75rem;">
      
      <!-- Service 1 -->
      <div class="gt-card gt-card-hover" style="padding: 1.75rem;">
        <div style="width: 48px; height: 48px; border-radius: var(--gt-radius-md); background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">
          ⚡
        </div>
        <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem;">Express Doorstep Delivery</h3>
        <p style="font-size: 0.9375rem; color: var(--gt-navy-600); margin-bottom: 1.25rem;">
          Direct dispatch to the recipient's home or office address with verified proof of delivery and continuous status updates.
        </p>
        <a href="<?php echo APP_URL; ?>/services.php#express" style="font-weight: 600; font-size: 0.875rem;">Learn Details &rarr;</a>
      </div>

      <!-- Service 2 -->
      <div class="gt-card gt-card-hover" style="padding: 1.75rem;">
        <div style="width: 48px; height: 48px; border-radius: var(--gt-radius-md); background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">
          🚚
        </div>
        <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem;">Regional Hub Freight</h3>
        <p style="font-size: 0.9375rem; color: var(--gt-navy-600); margin-bottom: 1.25rem;">
          Scheduled surface transport connecting regional centers, distribution branches, and sorting points across states.
        </p>
        <a href="<?php echo APP_URL; ?>/services.php#domestic" style="font-weight: 600; font-size: 0.875rem;">Learn Details &rarr;</a>
      </div>

      <!-- Service 3 -->
      <div class="gt-card gt-card-hover" style="padding: 1.75rem;">
        <div style="width: 48px; height: 48px; border-radius: var(--gt-radius-md); background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">
          🏢
        </div>
        <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem;">Branch Counter Pickup</h3>
        <p style="font-size: 0.9375rem; color: var(--gt-navy-600); margin-bottom: 1.25rem;">
          Flexible hold-at-hub pickup allowing recipients to collect consignments safely from their nearest local branch office.
        </p>
        <a href="<?php echo APP_URL; ?>/services.php#pickup" style="font-weight: 600; font-size: 0.875rem;">Learn Details &rarr;</a>
      </div>

      <!-- Service 4 -->
      <div class="gt-card gt-card-hover" style="padding: 1.75rem;">
        <div style="width: 48px; height: 48px; border-radius: var(--gt-radius-md); background: #f3e8ff; color: #7e22ce; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">
          📦
        </div>
        <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem;">Multi-Parcel Consignments</h3>
        <p style="font-size: 0.9375rem; color: var(--gt-navy-600); margin-bottom: 1.25rem;">
          Bulk intake support for commerce, business documents, and grouped parcels with tailored itemized dimensional tracking.
        </p>
        <a href="<?php echo APP_URL; ?>/services.php#b2b" style="font-weight: 600; font-size: 0.875rem;">Learn Details &rarr;</a>
      </div>

    </div>

  </div>
</section>

<!-- Why Choose Us -->
<section style="padding: 5rem 0; background: var(--gt-bg-body); border-top: 1px solid var(--gt-border); border-bottom: 1px solid var(--gt-border);">
  <div class="gt-container">
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 3.5rem; align-items: center;">
      <div>
        <div style="color: var(--gt-primary); font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Why GaaTiTrack</div>
        <h2 style="font-size: 2.1rem; margin-bottom: 1.25rem;">Engineered for Complete Parcel Transparency</h2>
        <p style="color: var(--gt-navy-600); font-size: 1.05rem; line-height: 1.7; margin-bottom: 1.5rem;">
          We built our tracking and courier management infrastructure around precision and accountability. From the moment your package is weighed and registered to the final delivery confirmation, our system guarantees total clarity.
        </p>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
          <div style="display: flex; gap: 12px; align-items: flex-start;">
            <div style="width: 24px; height: 24px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; font-size: 0.85rem;">✓</div>
            <div>
              <div style="font-weight: 700; color: var(--gt-navy-950);">Verifiable Status Milestones</div>
              <div style="font-size: 0.9rem; color: var(--gt-navy-600);">Chronological audit records updated live at each sorting and delivery event.</div>
            </div>
          </div>

          <div style="display: flex; gap: 12px; align-items: flex-start;">
            <div style="width: 24px; height: 24px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; font-size: 0.85rem;">✓</div>
            <div>
              <div style="font-weight: 700; color: var(--gt-navy-950);">Professional Handling & Security</div>
              <div style="font-size: 0.9rem; color: var(--gt-navy-600);">All consignments logged with exact physical dimensions, weights, and branch routing.</div>
            </div>
          </div>

          <div style="display: flex; gap: 12px; align-items: flex-start;">
            <div style="width: 24px; height: 24px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; font-size: 0.85rem;">✓</div>
            <div>
              <div style="font-weight: 700; color: var(--gt-navy-950);">Direct Branch Support</div>
              <div style="font-size: 0.9rem; color: var(--gt-navy-600);">Direct access to branch contacts and verified customer service representatives.</div>
            </div>
          </div>
        </div>

      </div>

      <!-- Trust Visual Block -->
      <div class="gt-card" style="padding: 2.5rem; background: #ffffff; border-color: var(--gt-border);">
        <h3 style="font-size: 1.35rem; margin-bottom: 1.25rem;">Our Network Snapshot</h3>
        <p style="color: var(--gt-navy-600); font-size: 0.9375rem; margin-bottom: 2rem;">
          Verified operational records currently managed within our database infrastructure:
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
          <div style="background: var(--gt-bg-muted); padding: 1.25rem; border-radius: var(--gt-radius-md); text-align: center;">
            <div style="font-family: var(--gt-font-display); font-size: 2.25rem; font-weight: 800; color: var(--gt-primary);"><?php echo $branchCount; ?></div>
            <div style="font-size: 0.8125rem; font-weight: 600; color: var(--gt-navy-600); text-transform: uppercase;">Active Branches</div>
          </div>
          <div style="background: var(--gt-bg-muted); padding: 1.25rem; border-radius: var(--gt-radius-md); text-align: center;">
            <div style="font-family: var(--gt-font-display); font-size: 2.25rem; font-weight: 800; color: var(--gt-navy-950);"><?php echo $parcelCount; ?></div>
            <div style="font-size: 0.8125rem; font-weight: 600; color: var(--gt-navy-600); text-transform: uppercase;">Parcels Handled</div>
          </div>
        </div>

        <div style="border-top: 1px solid var(--gt-border); padding-top: 1.25rem; font-size: 0.875rem; color: var(--gt-navy-600);">
          <span>Corporate Headquarters: </span>
          <strong style="color: var(--gt-navy-900);"><?php echo e($_SESSION['system']['address'] ?? '1250 Broadway, Suite 3200, New York, NY 10001, United States'); ?></strong>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- How It Works -->
<section style="padding: 5rem 0; background: #ffffff;">
  <div class="gt-container">
    
    <div style="text-align: center; max-width: 680px; margin: 0 auto 3.5rem auto;">
      <div style="color: var(--gt-primary); font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Simple Process</div>
      <h2 style="font-size: 2.1rem; margin-bottom: 1rem;">How Shipment Tracking Works</h2>
      <p style="color: var(--gt-navy-600); font-size: 1.05rem;">
        Four straightforward steps from initial package drop-off to final handover.
      </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 2rem;">
      
      <!-- Step 1 -->
      <div style="text-align: center;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #e0f2fe; color: #0284c7; font-family: var(--gt-font-display); font-weight: 800; font-size: 1.35rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem auto;">
          1
        </div>
        <h4 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Intake & Registration</h4>
        <p style="font-size: 0.875rem; color: var(--gt-navy-600);">
          Consignments are registered with sender and recipient addresses, weight, dimensions, and allocated a 12-digit tracking reference.
        </p>
      </div>

      <!-- Step 2 -->
      <div style="text-align: center;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #e0f2fe; color: #0284c7; font-family: var(--gt-font-display); font-weight: 800; font-size: 1.35rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem auto;">
          2
        </div>
        <h4 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Sorting & Dispatch</h4>
        <p style="font-size: 0.875rem; color: var(--gt-navy-600);">
          Packages are collected and routed through our intermediate hubs and sorting centers according to destination routing.
        </p>
      </div>

      <!-- Step 3 -->
      <div style="text-align: center;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #e0f2fe; color: #0284c7; font-family: var(--gt-font-display); font-weight: 800; font-size: 1.35rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem auto;">
          3
        </div>
        <h4 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Live Real-Time Tracking</h4>
        <p style="font-size: 0.875rem; color: var(--gt-navy-600);">
          Enter the tracking number anytime on our portal to review timestamps, status milestones, and the current parcel location.
        </p>
      </div>

      <!-- Step 4 -->
      <div style="text-align: center;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #dcfce7; color: #16a34a; font-family: var(--gt-font-display); font-weight: 800; font-size: 1.35rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem auto;">
          4
        </div>
        <h4 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Final Delivery Handover</h4>
        <p style="font-size: 0.875rem; color: var(--gt-navy-600);">
          Consignment is delivered straight to the recipient address or ready for safe counter pickup at the designated branch.
        </p>
      </div>

    </div>

  </div>
</section>

<!-- Final Call to Action -->
<section style="background: linear-gradient(135deg, var(--gt-navy-950) 0%, var(--gt-navy-900) 100%); color: #ffffff; padding: 4.5rem 0;">
  <div class="gt-container" style="text-align: center; max-width: 720px;">
    <h2 style="color: #ffffff; font-size: 2.25rem; margin-bottom: 1rem;">Have a Parcel to Track or Logistics Questions?</h2>
    <p style="color: var(--gt-navy-400); font-size: 1.05rem; margin-bottom: 2rem;">
      Search our online tracking portal instantly with your reference number or contact our regional branch support for inquiries.
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
      <a href="<?php echo APP_URL; ?>/tracking.php" class="gt-btn gt-btn-primary gt-btn-lg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        Track Shipment
      </a>
      <a href="<?php echo APP_URL; ?>/contact.php" class="gt-btn gt-btn-secondary gt-btn-lg">
        Contact Customer Support
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
