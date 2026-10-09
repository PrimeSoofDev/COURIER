<?php
/**
 * GaaTiTrack - Contact Us Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/components.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$successMsg = '';
$errorMsg = '';
$formData = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => ''
];

// Pre-fill subject if navigated from services page (?service=...)
if (isset($_GET['service'])) {
    $serviceMap = [
        'express' => 'Inquiry: Express Doorstep Delivery',
        'pickup' => 'Inquiry: Branch Counter Pickup',
        'freight' => 'Inquiry: Regional Hub-to-Hub Freight',
        'multi' => 'Inquiry: Multi-Parcel Consignments'
    ];
    $sKey = strtolower(trim($_GET['service']));
    if (isset($serviceMap[$sKey])) {
        $formData['subject'] = $serviceMap[$sKey];
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Verification
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errorMsg = 'Security validation failed (CSRF token mismatch). Please refresh and submit again.';
    }
    // 2. Anti-Spam Honeypot Verification
    elseif (!empty($_POST['website_hp'])) {
        // Bot detected via filled invisible honeypot
        $errorMsg = 'Spam submission detected.';
    } else {
        // 3. Rate Limiting Check (Max 5 submissions per 10 minutes)
        $now = time();
        if (!isset($_SESSION['contact_rate'])) {
            $_SESSION['contact_rate'] = ['count' => 0, 'start' => $now];
        }
        if ($now - $_SESSION['contact_rate']['start'] > 600) {
            $_SESSION['contact_rate'] = ['count' => 0, 'start' => $now];
        }
        if ($_SESSION['contact_rate']['count'] >= 5) {
            $errorMsg = 'Submission limit reached. Please wait a few minutes before submitting another message.';
        } else {
            // 4. Input Sanitization & Validation
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');

            $formData['name'] = $name;
            $formData['email'] = $email;
            $formData['subject'] = $subject;
            $formData['message'] = $message;

            // Check for header injection attempts
            if (preg_match('/[\r\n]/', $email) || preg_match('/[\r\n]/', $subject)) {
                $errorMsg = 'Invalid characters detected in form fields.';
            } elseif (strlen($name) < 2 || strlen($name) > 100) {
                $errorMsg = 'Please enter a valid full name (between 2 and 100 characters).';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errorMsg = 'Please enter a valid email address.';
            } elseif (strlen($subject) < 3 || strlen($subject) > 150) {
                $errorMsg = 'Please enter an inquiry subject (between 3 and 150 characters).';
            } elseif (strlen($message) < 10 || strlen($message) > 3000) {
                $errorMsg = 'Please provide a detailed message (between 10 and 3,000 characters).';
            } else {
                // 5. Secure Log & Audit Recording
                $logEntry = sprintf(
                    "[%s] [INQUIRY] Name: %s | Email: %s | Subject: %s | Message Length: %d chars | IP: %s%s",
                    date('Y-m-d H:i:s'),
                    $name,
                    $email,
                    $subject,
                    strlen($message),
                    $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                    PHP_EOL
                );
                @file_put_contents(__DIR__ . '/logs/contact_inquiries.log', $logEntry, FILE_APPEND);

                // Increment submission count
                $_SESSION['contact_rate']['count']++;

                // Reset form fields
                $formData = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
                $successMsg = 'Thank you! Your message has been received and routed to our branch support team. We will review your inquiry shortly.';
            }
        }
    }
}

// Fetch active branches for quick phone contact card
$branches = [];
try {
    $bQuery = $pdo->query("SELECT branch_code, city, state, contact FROM branches ORDER BY city ASC LIMIT 5");
    $branches = $bQuery->fetchAll();
} catch (Exception $e) {}

$currentPage = 'contact';
$pageTitle = 'Contact Us - Customer Support & Branch Directory';
require_once __DIR__ . '/includes/public_header.php';
?>

<!-- Premium Glassmorphism Contact Hero Section -->
<section class="gt-hero-section">
  <div class="gt-hero-glow gt-hero-glow-1" style="right:0;top:-80px;background:radial-gradient(circle,rgba(37,99,235,0.4) 0%,transparent 70%);" aria-hidden="true"></div>
  <div class="gt-hero-glow gt-hero-glow-2" style="left:-60px;bottom:-80px;background:radial-gradient(circle,rgba(56,189,248,0.25) 0%,transparent 70%);" aria-hidden="true"></div>
  <div class="gt-hero-pattern" aria-hidden="true"></div>

  <div class="gt-hero-container">
    
    <!-- Glass Breadcrumb -->
    <div class="gt-glass-breadcrumb gt-anim-fade-up">
      <a href="<?php echo APP_URL; ?>/index.php">Home</a>
      <span class="sep">/</span>
      <span class="current">Contact Us</span>
    </div>

    <div class="gt-hero-grid">
      
      <!-- Left Column: Friendly Outreach Copy & Immediate Action CTAs -->
      <div class="gt-anim-fade-up-d1">
        <div class="gt-glass-badge">
          <span class="gt-glass-badge-dot"></span>
          CUSTOMER ASSISTANCE & BRANCH DIRECTORY
        </div>

        <h1 class="gt-hero-title">
          Let's Get Your Delivery Moving. <span class="gt-gradient-text">We're Here to Help.</span>
        </h1>

        <p class="gt-hero-desc">
          Have questions about consignment transit, regional branch pickup counters, or commercial shipping agreements? Reach out to our dedicated logistics support specialists.
        </p>

        <!-- Action CTAs -->
        <div class="gt-hero-actions">
          <a href="#contact-form" class="gt-btn-hero-primary">
            Send an Inquiry
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <polyline points="19 12 12 19 5 12"></polyline>
            </svg>
          </a>
          <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $_SESSION['system']['contact'])); ?>" class="gt-btn-hero-glass">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
            <?php echo e($_SESSION['system']['contact']); ?>
          </a>
        </div>

        <!-- Support Channels Pills -->
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 1.5rem;">
          <div style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.14); border-radius: 12px; padding: 8px 14px; font-size: 0.78rem; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(10px);">
            <span style="color: var(--gt-hero-cyan);">✉</span>
            <span style="color: #e2e8f0;"><?php echo e($_SESSION['system']['email']); ?></span>
          </div>
          <div style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.14); border-radius: 12px; padding: 8px 14px; font-size: 0.78rem; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(10px);">
            <span style="color: var(--gt-hero-cyan);">🕒</span>
            <span style="color: #e2e8f0;">Mon – Sat: 8:00 AM – 8:00 PM EST</span>
          </div>
        </div>

      </div>

      <!-- Right Column: Professional Customer Support Photography & Floating Contact Card -->
      <div class="gt-hero-visual-col gt-anim-fade-up-d2">
        <div class="gt-hero-image-frame">
          <img 
            src="<?php echo APP_URL; ?>/assets/images/heroes/hero-contact.jpg" 
            alt="Customer service specialist providing logistics support"
            fetchpriority="high"
            onerror="this.src='https://images.unsplash.com/photo-1534536281715-e28d76689b4d?auto=format&fit=crop&w=1200&q=80';"
          >
          <div class="gt-hero-image-overlay"></div>
        </div>

        <!-- Floating Live Desk Badge -->
        <div class="gt-float-card-milestone">
          <div style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 10px #22c55e;"></div>
          <div style="font-size: 0.78rem; font-weight: 700; color: #ffffff;">Operations Desk Active</div>
          <span style="font-size: 0.7rem; color: var(--gt-hero-cyan); background: rgba(56,189,248,0.15); padding: 2px 7px; border-radius: 9999px;">Quick Response</span>
        </div>

        <!-- Floating Contact Info Card -->
        <div class="gt-float-card-status" style="width: 310px;">
          <div style="font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--gt-hero-electric); margin-bottom: 6px;">
            Headquarters Office
          </div>
          <div style="font-size: 0.88rem; font-weight: 700; color: #ffffff; margin-bottom: 4px;">New York Flagship</div>
          <div style="font-size: 0.74rem; color: var(--gt-hero-muted); line-height: 1.5; margin-bottom: 8px;">
            <?php echo e($_SESSION['system']['address']); ?>
          </div>
          <div style="display: flex; align-items: center; gap: 6px; font-size: 0.72rem; color: var(--gt-hero-cyan);">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Direct Dispatch & Customer Care</span>
          </div>
        </div>

      </div>

    </div>
  </div>
</section>

<!-- Main Contact Form & Details Section -->
<section id="contact-form" style="padding: 4.5rem 0; background: #ffffff; scroll-margin-top: 80px;">
  <div class="gt-container">
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 3.5rem; align-items: flex-start;">
      
      <!-- Left Column: Contact Form -->
      <div>
        <div class="gt-card" style="padding: 2.25rem; box-shadow: var(--gt-shadow-md);">
          <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--gt-navy-950);">Send an Inquiry</h2>
          <p style="font-size: 0.9375rem; color: var(--gt-navy-600); margin-bottom: 1.75rem;">
            Complete the form below and our operations desk will follow up via your email.
          </p>

          <?php if (!empty($successMsg)): ?>
            <div class="gt-alert gt-alert-success" style="margin-bottom: 1.5rem;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
              <div>
                <div style="font-weight: 700;">Inquiry Submitted</div>
                <div style="font-size: 0.875rem;"><?php echo e($successMsg); ?></div>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($errorMsg)): ?>
            <div class="gt-alert gt-alert-error" style="margin-bottom: 1.5rem;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
              <div>
                <div style="font-weight: 700;">Submission Error</div>
                <div style="font-size: 0.875rem;"><?php echo e($errorMsg); ?></div>
              </div>
            </div>
          <?php endif; ?>

          <form action="<?php echo APP_URL; ?>/contact.php" method="POST" id="contact-form">
            <!-- CSRF Protection Token -->
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <!-- Anti-Spam Invisible Honeypot Field -->
            <div style="display:none;" aria-hidden="true">
              <label for="website_hp">Leave this field blank</label>
              <input type="text" name="website_hp" id="website_hp" tabindex="-1" autocomplete="off">
            </div>

            <div class="gt-form-group">
              <label for="name" class="gt-label">Full Name <span style="color:#dc2626;">*</span></label>
              <input 
                type="text" 
                name="name" 
                id="name" 
                class="gt-input" 
                placeholder="e.g. Rahul Sharma" 
                value="<?php echo e($formData['name']); ?>" 
                required
              >
            </div>

            <div class="gt-form-group">
              <label for="email" class="gt-label">Email Address <span style="color:#dc2626;">*</span></label>
              <input 
                type="email" 
                name="email" 
                id="email" 
                class="gt-input" 
                placeholder="you@domain.com" 
                value="<?php echo e($formData['email']); ?>" 
                required
              >
            </div>

            <div class="gt-form-group">
              <label for="subject" class="gt-label">Subject <span style="color:#dc2626;">*</span></label>
              <input 
                type="text" 
                name="subject" 
                id="subject" 
                class="gt-input" 
                placeholder="e.g. Consignment Rate Inquiry or Pickup Status" 
                value="<?php echo e($formData['subject']); ?>" 
                required
              >
            </div>

            <div class="gt-form-group">
              <label for="message" class="gt-label">Message Details <span style="color:#dc2626;">*</span></label>
              <textarea 
                name="message" 
                id="message" 
                class="gt-textarea" 
                rows="5" 
                placeholder="Please describe your shipment details, pickup branch, or tracking reference question..." 
                required
              ><?php echo e($formData['message']); ?></textarea>
              <div class="gt-form-hint">Please provide any applicable consignment reference numbers.</div>
            </div>

            <button type="submit" class="gt-btn gt-btn-primary gt-btn-block gt-btn-lg">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
              Send Message
            </button>
          </form>

        </div>
      </div>

      <!-- Right Column: Verified Office Coordinates & Branch Directory -->
      <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <!-- Corporate Headquarters Card -->
        <div class="gt-card" style="padding: 2rem; border-top: 4px solid var(--gt-primary);">
          <h3 style="font-size: 1.25rem; margin-bottom: 1.25rem; color: var(--gt-navy-950);">Corporate Headquarters</h3>
          
          <div style="display: flex; flex-direction: column; gap: 1.25rem; font-size: 0.9375rem;">
            
            <div style="display: flex; gap: 12px;">
              <div style="width: 38px; height: 38px; border-radius: var(--gt-radius-md); background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.15rem;">
                📍
              </div>
              <div>
                <div style="font-weight: 700; color: var(--gt-navy-900);">Main Office Address</div>
                <div style="color: var(--gt-navy-600); margin-top: 0.2rem; line-height: 1.5;">
                  <?php echo e($_SESSION['system']['address'] ?? '1250 Broadway, Suite 3200, New York, NY 10001, United States'); ?>
                </div>
              </div>
            </div>

            <div style="display: flex; gap: 12px;">
              <div style="width: 38px; height: 38px; border-radius: var(--gt-radius-md); background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.15rem;">
                📞
              </div>
              <div>
                <div style="font-weight: 700; color: var(--gt-navy-900);">Support Telephone</div>
                <div style="color: var(--gt-navy-600); margin-top: 0.2rem;">
                  <a href="tel:<?php echo e($_SESSION['system']['contact'] ?? '+18005550199'); ?>" style="font-weight: 600; color: var(--gt-primary);">
                    <?php echo e($_SESSION['system']['contact'] ?? '+1 (800) 555-0199'); ?>
                  </a>
                </div>
              </div>
            </div>

            <div style="display: flex; gap: 12px;">
              <div style="width: 38px; height: 38px; border-radius: var(--gt-radius-md); background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.15rem;">
                ✉️
              </div>
              <div>
                <div style="font-weight: 700; color: var(--gt-navy-900);">Inquiry Email</div>
                <div style="color: var(--gt-navy-600); margin-top: 0.2rem;">
                  <a href="mailto:<?php echo e($_SESSION['system']['email'] ?? 'support@gaatitrack.com'); ?>" style="color: var(--gt-primary);">
                    <?php echo e($_SESSION['system']['email'] ?? 'support@gaatitrack.com'); ?>
                  </a>
                </div>
              </div>
            </div>

            <div style="display: flex; gap: 12px;">
              <div style="width: 38px; height: 38px; border-radius: var(--gt-radius-md); background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.15rem;">
                🕒
              </div>
              <div>
                <div style="font-weight: 700; color: var(--gt-navy-900);">Operating Hours</div>
                <div style="color: var(--gt-navy-600); margin-top: 0.2rem; font-size: 0.875rem;">
                  Monday – Saturday: 9:00 AM – 7:00 PM IST<br>
                  Sunday: Closed (Emergency dispatch on call)
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Regional Branch Hotlines Card -->
        <div class="gt-card" style="padding: 2rem;">
          <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem; color: var(--gt-navy-950);">Regional Branch Contacts</h3>
          <p style="font-size: 0.875rem; color: var(--gt-navy-600); margin-bottom: 1.25rem;">
            Direct phone lines for regional branch hubs currently registered in our operational database:
          </p>

          <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
            <?php foreach ($branches as $b): ?>
              <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--gt-border); padding-bottom: 0.5rem;">
                <div>
                  <span style="font-weight: 600; color: var(--gt-navy-900);"><?php echo e(ucwords($b['city'])); ?> Hub</span>
                  <span style="color: var(--gt-navy-500); font-size: 0.75rem; margin-left: 4px;">(<?php echo e($b['branch_code']); ?>)</span>
                </div>
                <a href="tel:<?php echo e($b['contact']); ?>" style="color: var(--gt-primary); font-weight: 600;">
                  <?php echo e($b['contact']); ?>
                </a>
              </div>
            <?php endforeach; ?>
          </div>

          <div style="margin-top: 1.25rem;">
            <a href="<?php echo APP_URL; ?>/about.php#branches" style="font-size: 0.85rem; font-weight: 600;">
              View Complete Address Directory &rarr;
            </a>
          </div>
        </div>

      </div>

    </div>

  </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
