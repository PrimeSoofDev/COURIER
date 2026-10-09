<?php
/**
 * GaaTiTrack - About Us Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

$currentPage = 'about';
$pageTitle = 'About Us - Our Mission, Values & Logistics Network';
require_once __DIR__ . '/includes/public_header.php';

// Fetch active branches for the verified network overview
$branchesQuery = $conn->query("SELECT branch_code, street, city, state, zip_code, country, contact FROM branches ORDER BY city ASC, id ASC");
$branchesList = [];
if ($branchesQuery) {
    while ($b = $branchesQuery->fetch_assoc()) {
        $branchesList[] = $b;
    }
}
?>

<!-- Premium Glassmorphism About Hero Section -->
<section class="gt-hero-section">
  <div class="gt-hero-glow gt-hero-glow-1" style="right:-50px;top:-80px;background:radial-gradient(circle,rgba(37,99,235,0.4) 0%,transparent 70%);" aria-hidden="true"></div>
  <div class="gt-hero-glow gt-hero-glow-2" style="left:-60px;bottom:-100px;background:radial-gradient(circle,rgba(103,232,249,0.25) 0%,transparent 70%);" aria-hidden="true"></div>
  <div class="gt-hero-pattern" aria-hidden="true"></div>

  <div class="gt-hero-container">
    
    <!-- Glass Breadcrumb -->
    <div class="gt-glass-breadcrumb gt-anim-fade-up">
      <a href="<?php echo APP_URL; ?>/index.php">Home</a>
      <span class="sep">/</span>
      <span class="current">About Us</span>
    </div>

    <div class="gt-hero-grid">
      
      <!-- Left Column: Editorial Headline & Story -->
      <div class="gt-anim-fade-up-d1">
        <div class="gt-glass-badge">
          <span class="gt-glass-badge-dot"></span>
          MOVING WHAT MATTERS WITH PRECISION
        </div>

        <h1 class="gt-hero-title">
          Moving What Matters. <span class="gt-gradient-text">Delivering Trust.</span>
        </h1>

        <p class="gt-hero-desc">
          GaaTiTrack is an integrated courier management and parcel logistics enterprise headquartered in New York. We bridge senders and recipients across the United States through transparent, milestone-driven freight networks and secure consignment custody.
        </p>

        <!-- CTAs -->
        <div class="gt-hero-actions">
          <a href="<?php echo APP_URL; ?>/tracking.php" class="gt-btn-hero-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            Track a Consignment
          </a>
          <a href="#network" class="gt-btn-hero-glass">
            View Hub Network
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <polyline points="19 12 12 19 5 12"></polyline>
            </svg>
          </a>
        </div>

        <!-- Pillar Trust Highlights -->
        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 1.5rem;">
          <div style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.14); border-radius: 12px; padding: 8px 14px; font-size: 0.8rem; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(10px);">
            <span style="color: var(--gt-hero-cyan); font-weight: 700;">✓</span>
            <span style="color: #e2e8f0;">100% Scan Visibility</span>
          </div>
          <div style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.14); border-radius: 12px; padding: 8px 14px; font-size: 0.8rem; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(10px);">
            <span style="color: var(--gt-hero-cyan); font-weight: 700;">✓</span>
            <span style="color: #e2e8f0;">Direct Hub Transfer</span>
          </div>
          <div style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.14); border-radius: 12px; padding: 8px 14px; font-size: 0.8rem; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(10px);">
            <span style="color: var(--gt-hero-cyan); font-weight: 700;">✓</span>
            <span style="color: #e2e8f0;">Zero Blind Spots</span>
          </div>
        </div>

      </div>

      <!-- Right Column: Warehouse Photography & Floating Brand Values -->
      <div class="gt-hero-visual-col gt-anim-fade-up-d2">
        <div class="gt-hero-image-frame">
          <img 
            src="<?php echo APP_URL; ?>/assets/images/heroes/hero-about.jpg" 
            alt="Modern logistics sorting hub and warehouse dispatch operations"
            fetchpriority="high"
            onerror="this.src='https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=80';"
          >
          <div class="gt-hero-image-overlay"></div>
        </div>

        <!-- Floating Origin Badge -->
        <div class="gt-float-card-milestone">
          <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--gt-hero-cyan); box-shadow: 0 0 10px var(--gt-hero-cyan);"></div>
          <div style="font-size: 0.78rem; font-weight: 700; color: #ffffff;">New York Headquarters</div>
          <span style="font-size: 0.7rem; color: #cbd5e1; background: rgba(255,255,255,0.12); padding: 2px 7px; border-radius: 9999px;">Est. Logistics</span>
        </div>

        <!-- Floating Brand Values Panel -->
        <div class="gt-float-card-status" style="width: 310px;">
          <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--gt-hero-electric); margin-bottom: 8px;">
            Core Operational Pillars
          </div>

          <div style="display: flex; flex-direction: column; gap: 6px; font-size: 0.78rem;">
            <div style="display: flex; align-items: center; gap: 8px; color: #ffffff;">
              <span style="color: var(--gt-hero-cyan);">✦</span>
              <span><strong>Customer-Focused Service:</strong> Dedicated desk support</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; color: #ffffff;">
              <span style="color: var(--gt-hero-cyan);">✦</span>
              <span><strong>Shipment Visibility:</strong> Real-time event recording</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; color: #ffffff;">
              <span style="color: var(--gt-hero-cyan);">✦</span>
              <span><strong>Reliable Operations:</strong> Verified branch handovers</span>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>
</section>

<!-- Company Overview & Mission / Vision Section -->
<section style="padding: 4.5rem 0; background: #ffffff;">
  <div class="gt-container">
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 3.5rem; align-items: center; margin-bottom: 4rem;">
      <div>
        <div style="color: var(--gt-primary); font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Who We Are</div>
        <h2 style="font-size: 2rem; margin-bottom: 1.25rem;">Building Trust into Every Delivery</h2>
        <p style="color: var(--gt-navy-600); font-size: 1rem; line-height: 1.7; margin-bottom: 1.25rem;">
          In the fast-moving logistics landscape, senders and recipients need certainty. GaaTiTrack was developed to eliminate parcel tracking ambiguity by recording and communicating every critical transition of a consignment’s journey.
        </p>
        <p style="color: var(--gt-navy-600); font-size: 1rem; line-height: 1.7; margin-bottom: 1.5rem;">
          From the initial consignment intake at our local branch offices to sorting, regional transit, and final delivery, our operations adhere to structured tracking standards so you always know where your package is.
        </p>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
          <a href="<?php echo APP_URL; ?>/tracking.php" class="gt-btn gt-btn-primary">
            Track a Shipment
          </a>
          <a href="<?php echo APP_URL; ?>/services.php" class="gt-btn gt-btn-secondary">
            Explore Services
          </a>
        </div>
      </div>

      <!-- Mission & Vision Cards -->
      <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <div class="gt-card" style="padding: 1.75rem; border-left: 4px solid var(--gt-primary);">
          <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 0.75rem;">
            <div style="width: 36px; height: 36px; border-radius: var(--gt-radius-md); background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">🎯</div>
            <h3 style="margin: 0; font-size: 1.2rem;">Our Mission</h3>
          </div>
          <p style="margin: 0; font-size: 0.9375rem; color: var(--gt-navy-600); line-height: 1.6;">
            To deliver exceptional courier and transport services characterized by transparency, verified status updates, safe package handling, and accessible customer assistance across all service locations.
          </p>
        </div>

        <div class="gt-card" style="padding: 1.75rem; border-left: 4px solid var(--gt-accent);">
          <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 0.75rem;">
            <div style="width: 36px; height: 36px; border-radius: var(--gt-radius-md); background: #ccfbf1; color: #0d9488; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">🔭</div>
            <h3 style="margin: 0; font-size: 1.2rem;">Our Vision</h3>
          </div>
          <p style="margin: 0; font-size: 0.9375rem; color: var(--gt-navy-600); line-height: 1.6;">
            To build a cohesive, technology-enabled hub network where individuals and commercial clients experience frictionless dispatch, verifiable security, and complete peace of mind.
          </p>
        </div>

      </div>
    </div>

  </div>
</section>

<!-- Core Values Section -->
<section style="padding: 4.5rem 0; background: var(--gt-bg-body); border-top: 1px solid var(--gt-border); border-bottom: 1px solid var(--gt-border);">
  <div class="gt-container">
    
    <div style="text-align: center; max-width: 680px; margin: 0 auto 3rem auto;">
      <div style="color: var(--gt-primary); font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Guiding Principles</div>
      <h2 style="font-size: 2rem; margin-bottom: 1rem;">The Values That Drive Our Operations</h2>
      <p style="color: var(--gt-navy-600); font-size: 1rem;">
        Every operational step taken by our branch staff and dispatch managers reflects these foundational standards.
      </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
      
      <div class="gt-card" style="padding: 1.5rem; background: #ffffff;">
        <div style="font-size: 1.75rem; margin-bottom: 1rem;">🛡️</div>
        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Uncompromised Transparency</h3>
        <p style="font-size: 0.875rem; color: var(--gt-navy-600); line-height: 1.6; margin: 0;">
          We record 10 explicit tracking milestones from acceptance to delivery, providing an unalterable history of physical transit.
        </p>
      </div>

      <div class="gt-card" style="padding: 1.5rem; background: #ffffff;">
        <div style="font-size: 1.75rem; margin-bottom: 1rem;">⚖️</div>
        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Dimensional Accuracy</h3>
        <p style="font-size: 0.875rem; color: var(--gt-navy-600); line-height: 1.6; margin: 0;">
          All packages are systematically weighed and measured (length, width, height) to ensure fair billing and balanced vehicle capacity.
        </p>
      </div>

      <div class="gt-card" style="padding: 1.5rem; background: #ffffff;">
        <div style="font-size: 1.75rem; margin-bottom: 1rem;">🤝</div>
        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Customer Accessibility</h3>
        <p style="font-size: 0.875rem; color: var(--gt-navy-600); line-height: 1.6; margin: 0;">
          Direct communication with actual branch managers and support personnel instead of unresolved automated queues.
        </p>
      </div>

      <div class="gt-card" style="padding: 1.5rem; background: #ffffff;">
        <div style="font-size: 1.75rem; margin-bottom: 1rem;">🔄</div>
        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Operational Resilience</h3>
        <p style="font-size: 0.875rem; color: var(--gt-navy-600); line-height: 1.6; margin: 0;">
          Dual delivery pathways: direct doorstep handover or secure counter pickup at regional destination centers.
        </p>
      </div>

    </div>

  </div>
</section>

<!-- Verified Branch Network Directory -->
<section style="padding: 4.5rem 0; background: #ffffff;">
  <div class="gt-container">
    
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2.5rem;">
      <div>
        <div style="color: var(--gt-primary); font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Network Coverage</div>
        <h2 style="font-size: 2rem; margin: 0;">Our Active Branch Locations</h2>
        <p style="color: var(--gt-navy-600); font-size: 0.95rem; margin-top: 0.5rem; margin-bottom: 0;">
          Verified physical collection and distribution centers currently registered in our operational network.
        </p>
      </div>
      <div>
        <span class="gt-badge gt-badge-primary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
          <?php echo count($branchesList); ?> Active Hubs
        </span>
      </div>
    </div>

    <!-- Branches Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
      <?php if (!empty($branchesList)): ?>
        <?php foreach ($branchesList as $branch): ?>
          <div class="gt-card" style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <span style="font-family: var(--gt-font-display); font-weight: 700; color: var(--gt-primary); font-size: 0.8125rem;">
                  HUB: <?php echo e($branch['branch_code']); ?>
                </span>
                <span class="gt-badge gt-badge-0" style="font-size: 0.75rem;">Operational</span>
              </div>
              <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem; color: var(--gt-navy-950);">
                <?php echo e(ucwords($branch['city'] ?: 'Regional')); ?>, <?php echo e(ucwords($branch['state'] ?: $branch['country'])); ?>
              </h3>
              <p style="font-size: 0.875rem; color: var(--gt-navy-600); line-height: 1.5; margin-bottom: 1rem;">
                📍 <?php echo e($branch['street']); ?>, <?php echo e($branch['city']); ?> <?php echo e($branch['zip_code']); ?>
              </p>
            </div>
            
            <div style="border-top: 1px solid var(--gt-border); padding-top: 0.75rem; font-size: 0.8125rem; color: var(--gt-navy-600); display: flex; align-items: center; justify-content: space-between;">
              <span>Contact:</span>
              <strong style="color: var(--gt-navy-900);"><?php echo e($branch['contact']); ?></strong>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="grid-column: 1 / -1;">
          <?php echo render_empty_state('No Hubs Listed', 'Branch locations are being updated.'); ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<!-- Call to Action Banner -->
<section style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 4rem 0;">
  <div class="gt-container" style="text-align: center; max-width: 680px;">
    <h2 style="color: #ffffff; font-size: 2rem; margin-bottom: 1rem;">Ready to Experience Seamless Parcel Tracking?</h2>
    <p style="color: #e0f2fe; font-size: 1.05rem; margin-bottom: 2rem; line-height: 1.6;">
      Whether you need to verify where a package is currently located or inquire about shipping rates between branches, our team is ready to assist.
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
      <a href="<?php echo APP_URL; ?>/tracking.php" class="gt-btn gt-btn-secondary gt-btn-lg">
        Track Your Shipment
      </a>
      <a href="<?php echo APP_URL; ?>/contact.php" class="gt-btn gt-btn-outline gt-btn-lg" style="color: #ffffff; border-color: #ffffff;">
        Contact Branch Support
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
