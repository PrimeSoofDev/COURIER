<?php
/**
 * GaaTiTrack - Services Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

$currentPage = 'services';
$pageTitle = 'Our Services - Professional Courier & Freight Solutions';
require_once __DIR__ . '/includes/public_header.php';
?>

<!-- Premium Glassmorphism Services Hero Section -->
<section class="gt-hero-section">
  <div class="gt-hero-glow gt-hero-glow-1" style="right:0;top:-100px;background:radial-gradient(circle,rgba(37,99,235,0.45) 0%,transparent 70%);" aria-hidden="true"></div>
  <div class="gt-hero-glow gt-hero-glow-2" style="left:-40px;bottom:-60px;background:radial-gradient(circle,rgba(56,189,248,0.28) 0%,transparent 70%);" aria-hidden="true"></div>
  <div class="gt-hero-pattern" aria-hidden="true"></div>

  <div class="gt-hero-container">
    
    <!-- Glass Breadcrumb -->
    <div class="gt-glass-breadcrumb gt-anim-fade-up">
      <a href="<?php echo APP_URL; ?>/index.php">Home</a>
      <span class="sep">/</span>
      <span class="current">Services</span>
    </div>

    <div class="gt-hero-grid">
      
      <!-- Left Column: Capabilities Headline & Services Navigation -->
      <div class="gt-anim-fade-up-d1">
        <div class="gt-glass-badge">
          <span class="gt-glass-badge-dot"></span>
          INTEGRATED FREIGHT & PARCEL SOLUTIONS
        </div>

        <h1 class="gt-hero-title">
          Delivery Solutions <span class="gt-gradient-text">Built Around Your Business.</span>
        </h1>

        <p class="gt-hero-desc">
          From rapid doorstep dispatches to scheduled regional freight transfers and commercial multi-parcel consolidations, GaaTiTrack delivers dependable transit solutions tailored for modern business logistics.
        </p>

        <!-- Primary & Secondary CTAs -->
        <div class="gt-hero-actions">
          <a href="#express" class="gt-btn-hero-primary">
            Explore Our Services
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <polyline points="19 12 12 19 5 12"></polyline>
            </svg>
          </a>
          <a href="<?php echo APP_URL; ?>/contact.php" class="gt-btn-hero-glass">
            Contact Our Team
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
          </a>
        </div>

        <!-- Four Core Supported Service Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; margin-top: 1.5rem;">
          <a href="#express" style="text-decoration: none; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.14); border-radius: 12px; padding: 10px 12px; display: block; backdrop-filter: blur(10px); transition: border-color 0.2s, background 0.2s;">
            <div style="font-size: 0.75rem; color: var(--gt-hero-cyan); font-weight: 700; margin-bottom: 2px;">⚡ Direct Handover</div>
            <div style="font-size: 0.8rem; font-weight: 600; color: #ffffff;">Express Doorstep</div>
          </a>
          <a href="#pickup" style="text-decoration: none; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.14); border-radius: 12px; padding: 10px 12px; display: block; backdrop-filter: blur(10px); transition: border-color 0.2s, background 0.2s;">
            <div style="font-size: 0.75rem; color: var(--gt-hero-cyan); font-weight: 700; margin-bottom: 2px;">🏢 Branch Desk</div>
            <div style="font-size: 0.8rem; font-weight: 600; color: #ffffff;">Counter Pickup</div>
          </a>
          <a href="#freight" style="text-decoration: none; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.14); border-radius: 12px; padding: 10px 12px; display: block; backdrop-filter: blur(10px); transition: border-color 0.2s, background 0.2s;">
            <div style="font-size: 0.75rem; color: var(--gt-hero-cyan); font-weight: 700; margin-bottom: 2px;">🚛 Scheduled Line</div>
            <div style="font-size: 0.8rem; font-weight: 600; color: #ffffff;">Hub-to-Hub Freight</div>
          </a>
          <a href="#multi" style="text-decoration: none; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.14); border-radius: 12px; padding: 10px 12px; display: block; backdrop-filter: blur(10px); transition: border-color 0.2s, background 0.2s;">
            <div style="font-size: 0.75rem; color: var(--gt-hero-cyan); font-weight: 700; margin-bottom: 2px;">📦 Bulk Consignments</div>
            <div style="font-size: 0.8rem; font-weight: 600; color: #ffffff;">Commercial Multi</div>
          </a>
        </div>

      </div>

      <!-- Right Column: Freight Fleet Photography & Floating Service Badge -->
      <div class="gt-hero-visual-col gt-anim-fade-up-d2">
        <div class="gt-hero-image-frame">
          <img 
            src="<?php echo APP_URL; ?>/assets/images/heroes/hero-services.jpg" 
            alt="Intermodal freight logistics shipping containers and cargo operations"
            fetchpriority="high"
            onerror="this.src='https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=1200&q=80';"
          >
          <div class="gt-hero-image-overlay"></div>
        </div>

        <!-- Floating Hubs Badge -->
        <div class="gt-float-card-milestone">
          <div style="width: 8px; height: 8px; border-radius: 50%; background: #38bdf8; box-shadow: 0 0 10px #38bdf8;"></div>
          <div style="font-size: 0.78rem; font-weight: 700; color: #ffffff;">Consolidated Freight Fleet</div>
          <span style="font-size: 0.7rem; color: #22c55e; background: rgba(34,197,94,0.15); padding: 2px 7px; border-radius: 9999px;">Active Fleet</span>
        </div>

        <!-- Floating Service Status Card -->
        <div class="gt-float-card-status" style="width: 300px;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
            <span style="font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--gt-hero-electric);">Service Guarantees</span>
            <span style="font-size: 0.68rem; font-weight: 700; color: var(--gt-hero-cyan);">Full Visibility</span>
          </div>
          <div style="font-size: 0.88rem; font-weight: 700; color: #ffffff; margin-bottom: 4px;">Milestone Verification</div>
          <div style="font-size: 0.74rem; color: var(--gt-hero-muted); line-height: 1.5;">
            Every consignment logged with physical branch timestamps, receiver identity verification, and printable waybills.
          </div>
        </div>

      </div>

    </div>
  </div>
</section>

<!-- Core Services Grid Section -->
<section style="padding: 4.5rem 0; background: #ffffff;">
  <div class="gt-container">
    
    <div style="display: flex; flex-direction: column; gap: 3rem;">
      
      <!-- Service 1: Direct Doorstep Delivery -->
      <div id="express" class="gt-card" style="padding: 2.5rem; scroll-margin-top: 100px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; align-items: center;">
          <div>
            <div style="display: inline-flex; align-items: center; gap: 8px; background: #e0f2fe; color: #0284c7; padding: 0.25rem 0.75rem; border-radius: var(--gt-radius-full); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.75rem;">
              Direct Dispatch
            </div>
            <h2 style="font-size: 1.85rem; margin-bottom: 1rem; color: var(--gt-navy-950);">
              Express Doorstep Delivery
            </h2>
            <p style="color: var(--gt-navy-600); font-size: 1rem; line-height: 1.7; margin-bottom: 1.5rem;">
              Our direct-to-address service ensures your consignment is collected at the origin branch, transported through sorting nodes, and dispatched directly to the recipient's residential or commercial doorway.
            </p>

            <div style="margin-bottom: 1.5rem;">
              <h4 style="font-size: 0.95rem; margin-bottom: 0.75rem; color: var(--gt-navy-900);">Key Benefits:</h4>
              <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem; color: var(--gt-navy-700);">
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> Maximum recipient convenience with direct physical handover
                </li>
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> "Out for Delivery" milestone notifications with recipient contact verification
                </li>
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> Transparent delivery audit confirmation logged in system records
                </li>
              </ul>
            </div>

            <div style="background: var(--gt-bg-muted); padding: 1rem 1.25rem; border-radius: var(--gt-radius-md); font-size: 0.85rem; color: var(--gt-navy-600); margin-bottom: 1.75rem;">
              <strong>Intended Use Case:</strong> Ideal for e-commerce deliveries, personal gifts, critical business documentation, and customer parcel fulfillment.
            </div>

            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
              <a href="<?php echo APP_URL; ?>/contact.php?service=express" class="gt-btn gt-btn-primary">
                Inquire at Branch
              </a>
              <a href="<?php echo APP_URL; ?>/tracking.php" class="gt-btn gt-btn-outline">
                Track Active Parcel
              </a>
            </div>
          </div>

          <!-- Feature Specs Box -->
          <div style="background: var(--gt-bg-body); border: 1px solid var(--gt-border); border-radius: var(--gt-radius-lg); padding: 2rem;">
            <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem; color: var(--gt-navy-950);">Service Parameters</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem;">
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Delivery Scope:</span>
                <strong style="color: var(--gt-navy-900);">Destination Branch Radius</strong>
              </div>
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Status Tracking:</span>
                <strong style="color: var(--gt-navy-900);">Full 10-Milestone Audit</strong>
              </div>
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Unsuccessful Attempts:</span>
                <strong style="color: var(--gt-navy-900);">Logged with Reschedule Flag</strong>
              </div>
              <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--gt-navy-500);">Physical Intake:</span>
                <strong style="color: var(--gt-navy-900);">Weighed & Dimension Measured</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Service 2: Branch Counter Pickup -->
      <div id="pickup" class="gt-card" style="padding: 2.5rem; scroll-margin-top: 100px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; align-items: center;">
          <div>
            <div style="display: inline-flex; align-items: center; gap: 8px; background: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: var(--gt-radius-full); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.75rem;">
              Counter Hold & Collect
            </div>
            <h2 style="font-size: 1.85rem; margin-bottom: 1rem; color: var(--gt-navy-950);">
              Branch Counter Pickup
            </h2>
            <p style="color: var(--gt-navy-600); font-size: 1rem; line-height: 1.7; margin-bottom: 1.5rem;">
              Recipients can choose to have parcels routed to their closest local GaaTiTrack branch hub. Once the shipment reaches the branch, the status updates to "Ready to Pickup" for collection at the customer's convenience.
            </p>

            <div style="margin-bottom: 1.5rem;">
              <h4 style="font-size: 0.95rem; margin-bottom: 0.75rem; color: var(--gt-navy-900);">Key Benefits:</h4>
              <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem; color: var(--gt-navy-700);">
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> Eliminates missed delivery attempts when the recipient is not home
                </li>
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> Secure storage within verified branch premises until claimed
                </li>
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> Verification via reference number and recipient identification
                </li>
              </ul>
            </div>

            <div style="background: var(--gt-bg-muted); padding: 1rem 1.25rem; border-radius: var(--gt-radius-md); font-size: 0.85rem; color: var(--gt-navy-600); margin-bottom: 1.75rem;">
              <strong>Intended Use Case:</strong> Working professionals with unpredictable schedules, rural recipients near hub cities, and security-sensitive items.
            </div>

            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
              <a href="<?php echo APP_URL; ?>/about.php#branches" class="gt-btn gt-btn-primary">
                View Pickup Hubs
              </a>
              <a href="<?php echo APP_URL; ?>/contact.php" class="gt-btn gt-btn-secondary">
                Branch Questions
              </a>
            </div>
          </div>

          <!-- Feature Specs Box -->
          <div style="background: var(--gt-bg-body); border: 1px solid var(--gt-border); border-radius: var(--gt-radius-lg); padding: 2rem;">
            <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem; color: var(--gt-navy-950);">Service Parameters</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem;">
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Holding Location:</span>
                <strong style="color: var(--gt-navy-900);">Designated Pickup Branch</strong>
              </div>
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Readiness Notice:</span>
                <strong style="color: var(--gt-navy-900);">"Ready to Pickup" Status</strong>
              </div>
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Collection Proof:</span>
                <strong style="color: var(--gt-navy-900);">Consignment Reference ID</strong>
              </div>
              <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--gt-navy-500);">Handover Status:</span>
                <strong style="color: var(--gt-navy-900);">Logged as "Picked-up"</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Service 3: Domestic Hub-to-Hub Freight -->
      <div id="domestic" class="gt-card" style="padding: 2.5rem; scroll-margin-top: 100px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; align-items: center;">
          <div>
            <div style="display: inline-flex; align-items: center; gap: 8px; background: #fef3c7; color: #b45309; padding: 0.25rem 0.75rem; border-radius: var(--gt-radius-full); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.75rem;">
              Inter-City Transport
            </div>
            <h2 style="font-size: 1.85rem; margin-bottom: 1rem; color: var(--gt-navy-950);">
              Regional Hub-to-Hub Freight
            </h2>
            <p style="color: var(--gt-navy-600); font-size: 1rem; line-height: 1.7; margin-bottom: 1.5rem;">
              Scheduled overland transport coordinating bulk parcels and freight between our registered branches across the United States. Connects major sorting centers in New York, Chicago, Los Angeles, and connecting regional distribution hubs.
            </p>

            <div style="margin-bottom: 1.5rem;">
              <h4 style="font-size: 0.95rem; margin-bottom: 0.75rem; color: var(--gt-navy-900);">Key Benefits:</h4>
              <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem; color: var(--gt-navy-700);">
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> Consolidated line-haul vehicles operating on structured dispatch windows
                </li>
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> Arrival logs recorded upon container unloading at intermediate hubs
                </li>
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> Balanced cubic capacity management preventing parcel compression
                </li>
              </ul>
            </div>

            <div style="background: var(--gt-bg-muted); padding: 1rem 1.25rem; border-radius: var(--gt-radius-md); font-size: 0.85rem; color: var(--gt-navy-600); margin-bottom: 1.75rem;">
              <strong>Intended Use Case:</strong> Retail distribution, bulk shipments between merchant branches, and regional stock transfers.
            </div>

            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
              <a href="<?php echo APP_URL; ?>/contact.php?service=freight" class="gt-btn gt-btn-primary">
                Inquire on Routes
              </a>
              <a href="<?php echo APP_URL; ?>/about.php" class="gt-btn gt-btn-secondary">
                Our Branch Network
              </a>
            </div>
          </div>

          <!-- Feature Specs Box -->
          <div style="background: var(--gt-bg-body); border: 1px solid var(--gt-border); border-radius: var(--gt-radius-lg); padding: 2rem;">
            <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem; color: var(--gt-navy-950);">Service Parameters</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem;">
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Transit Mode:</span>
                <strong style="color: var(--gt-navy-900);">Scheduled Overland Transport</strong>
              </div>
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Mid-Transit Logs:</span>
                <strong style="color: var(--gt-navy-900);">"Shipped" & "In-Transit"</strong>
              </div>
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Hub Ingress:</span>
                <strong style="color: var(--gt-navy-900);">"Arrived At Destination"</strong>
              </div>
              <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--gt-navy-500);">Load Monitoring:</span>
                <strong style="color: var(--gt-navy-900);">Secured Freight Manifests</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Service 4: Multi-Item Consignments -->
      <div id="b2b" class="gt-card" style="padding: 2.5rem; scroll-margin-top: 100px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; align-items: center;">
          <div>
            <div style="display: inline-flex; align-items: center; gap: 8px; background: #f3e8ff; color: #7e22ce; padding: 0.25rem 0.75rem; border-radius: var(--gt-radius-full); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.75rem;">
              Grouped Shipments
            </div>
            <h2 style="font-size: 1.85rem; margin-bottom: 1rem; color: var(--gt-navy-950);">
              Multi-Parcel Consignment Handling
            </h2>
            <p style="color: var(--gt-navy-600); font-size: 1rem; line-height: 1.7; margin-bottom: 1.5rem;">
              For clients dispatching several boxes or items in a single booking, our intake system accommodates multiple items under individual weight and dimensional logs with consolidated price calculations.
            </p>

            <div style="margin-bottom: 1.5rem;">
              <h4 style="font-size: 0.95rem; margin-bottom: 0.75rem; color: var(--gt-navy-900);">Key Benefits:</h4>
              <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem; color: var(--gt-navy-700);">
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> Itemized dimensions (height, width, length) and individual weights per piece
                </li>
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> Synchronized status transitions across all consignments in the order
                </li>
                <li style="display: flex; gap: 8px; align-items: center;">
                  <span style="color: #16a34a; font-weight: bold;">✓</span> Itemized printed documentation available through branch staff
                </li>
              </ul>
            </div>

            <div style="background: var(--gt-bg-muted); padding: 1rem 1.25rem; border-radius: var(--gt-radius-md); font-size: 0.85rem; color: var(--gt-navy-600); margin-bottom: 1.75rem;">
              <strong>Intended Use Case:</strong> Manufacturers, warehouse suppliers, corporate mailrooms, and wholesale distributors.
            </div>

            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
              <a href="<?php echo APP_URL; ?>/contact.php?service=multi" class="gt-btn gt-btn-primary">
                Consult with Branch
              </a>
              <a href="<?php echo APP_URL; ?>/tracking.php" class="gt-btn gt-btn-outline">
                Track Multi-Consignment
              </a>
            </div>
          </div>

          <!-- Feature Specs Box -->
          <div style="background: var(--gt-bg-body); border: 1px solid var(--gt-border); border-radius: var(--gt-radius-lg); padding: 2rem;">
            <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem; color: var(--gt-navy-950);">Service Parameters</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem;">
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Piece Intake:</span>
                <strong style="color: var(--gt-navy-900);">Multi-Item Dimensional Entry</strong>
              </div>
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Billing Structure:</span>
                <strong style="color: var(--gt-navy-900);">Per-Piece + Cumulative Total</strong>
              </div>
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.75rem;">
                <span style="color: var(--gt-navy-500);">Tracking References:</span>
                <strong style="color: var(--gt-navy-900);">Unique 12-Digit Reference ID</strong>
              </div>
              <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--gt-navy-500);">Documentation:</span>
                <strong style="color: var(--gt-navy-900);">Printable Parcel Receipts</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</section>

<!-- Pricing Transparency Note -->
<section style="padding: 4.5rem 0; background: var(--gt-bg-body); border-top: 1px solid var(--gt-border); border-bottom: 1px solid var(--gt-border);">
  <div class="gt-container" style="max-width: 860px; text-align: center;">
    <div style="color: var(--gt-primary); font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Pricing Transparency</div>
    <h2 style="font-size: 2rem; margin-bottom: 1.25rem;">How Shipping Charges Are Calculated</h2>
    <p style="color: var(--gt-navy-600); font-size: 1rem; line-height: 1.7; margin-bottom: 2rem;">
      At GaaTiTrack, we do not post arbitrary or fabricated rates. Shipping charges are calculated based on your consignment's exact physical parameters recorded during intake at your local branch:
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; text-align: left; margin-bottom: 2.5rem;">
      <div class="gt-card" style="padding: 1.25rem; background: #ffffff;">
        <div style="font-size: 1.25rem; margin-bottom: 0.5rem;">⚖️</div>
        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Gross Weight</h4>
        <p style="font-size: 0.85rem; color: var(--gt-navy-600); margin: 0;">Verified weight recorded in kilograms.</p>
      </div>

      <div class="gt-card" style="padding: 1.25rem; background: #ffffff;">
        <div style="font-size: 1.25rem; margin-bottom: 0.5rem;">📏</div>
        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Dimensions</h4>
        <p style="font-size: 0.85rem; color: var(--gt-navy-600); margin: 0;">Length, width, and height for vehicle volume.</p>
      </div>

      <div class="gt-card" style="padding: 1.25rem; background: #ffffff;">
        <div style="font-size: 1.25rem; margin-bottom: 0.5rem;">🛣️</div>
        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Route Distance</h4>
        <p style="font-size: 0.85rem; color: var(--gt-navy-600); margin: 0;">Transit mileage between origin and destination hubs.</p>
      </div>

      <div class="gt-card" style="padding: 1.25rem; background: #ffffff;">
        <div style="font-size: 1.25rem; margin-bottom: 0.5rem;">📦</div>
        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">Delivery Type</h4>
        <p style="font-size: 0.85rem; color: var(--gt-navy-600); margin: 0;">Doorstep direct delivery vs. branch counter pickup.</p>
      </div>
    </div>

    <div style="background: #ffffff; border: 1px solid var(--gt-border); border-radius: var(--gt-radius-md); padding: 1.5rem; text-align: left; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
      <div>
        <div style="font-weight: 700; color: var(--gt-navy-950);">Need a rate estimate for an upcoming consignment?</div>
        <div style="font-size: 0.875rem; color: var(--gt-navy-600);">Speak with a representative at your nearest branch office or submit an inquiry.</div>
      </div>
      <a href="<?php echo APP_URL; ?>/contact.php" class="gt-btn gt-btn-primary">
        Contact for Rate Inquiry
      </a>
    </div>

  </div>
</section>

<!-- Call to Action Banner -->
<section style="background: linear-gradient(135deg, var(--gt-navy-950) 0%, var(--gt-navy-900) 100%); color: #ffffff; padding: 4rem 0;">
  <div class="gt-container" style="text-align: center; max-width: 680px;">
    <h2 style="color: #ffffff; font-size: 2rem; margin-bottom: 1rem;">Already Have a Consignment in Transit?</h2>
    <p style="color: var(--gt-navy-400); font-size: 1.05rem; margin-bottom: 2rem; line-height: 1.6;">
      Check the real-time status, timeline milestones, and current hub location instantly using your 12-digit reference number.
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
      <a href="<?php echo APP_URL; ?>/tracking.php" class="gt-btn gt-btn-primary gt-btn-lg">
        Track Your Consignment
      </a>
      <a href="<?php echo APP_URL; ?>/about.php" class="gt-btn gt-btn-secondary gt-btn-lg">
        View Branch Directory
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
