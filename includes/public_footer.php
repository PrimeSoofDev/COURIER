  </main>

  <!-- Public Footer -->
  <footer class="gt-footer">
    <div class="gt-container">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2.5rem;">
        
        <!-- Column 1: Brand & Identity -->
        <div>
          <div class="gt-brand" style="margin-bottom: 1rem;">
            <div class="gt-brand-logo" style="background:#0284c7;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
              </svg>
            </div>
            <div class="gt-brand-title" style="color:#ffffff;">GaaTi<span style="color:#38bdf8;">Track</span></div>
          </div>
          <p style="color: var(--gt-navy-400); font-size: 0.9rem; line-height: 1.6;">
            A premier logistics and express courier network providing end-to-end transparency, timely deliveries, and nationwide parcel solutions.
          </p>
        </div>

        <!-- Column 2: Navigation -->
        <div>
          <h4>Quick Navigation</h4>
          <ul style="list-style: none; padding: 0; margin: 0; line-height: 2; font-size: 0.9rem;">
            <li><a href="<?php echo APP_URL; ?>/index.php">Home</a></li>
            <li><a href="<?php echo APP_URL; ?>/about.php">About GaaTiTrack</a></li>
            <li><a href="<?php echo APP_URL; ?>/services.php">Logistics Services</a></li>
            <li><a href="<?php echo APP_URL; ?>/tracking.php">Track a Parcel</a></li>
            <li><a href="<?php echo APP_URL; ?>/contact.php">Contact & Support</a></li>
          </ul>
        </div>

        <!-- Column 3: Logistics Services -->
        <div>
          <h4>Our Core Solutions</h4>
          <ul style="list-style: none; padding: 0; margin: 0; line-height: 2; font-size: 0.9rem;">
            <li><a href="<?php echo APP_URL; ?>/services.php#express">Express Parcel Delivery</a></li>
            <li><a href="<?php echo APP_URL; ?>/services.php#domestic">Domestic Hub-to-Hub</a></li>
            <li><a href="<?php echo APP_URL; ?>/services.php#b2b">Corporate Cargo Logistics</a></li>
            <li><a href="<?php echo APP_URL; ?>/services.php#pickup">Branch Counter Pickup</a></li>
          </ul>
        </div>

        <!-- Column 4: Verified Contact -->
        <div>
          <h4>Contact & Headquarters</h4>
          <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; font-size: 0.9rem; color: var(--gt-navy-400);">
            <li style="display: flex; gap: 8px; margin-bottom: 0.5rem;">
              <span aria-hidden="true">📍</span>
              <span><?php echo e($_SESSION['system']['address'] ?? '1250 Broadway, Suite 3200, New York, NY 10001, United States'); ?></span>
            </li>
            <li style="display: flex; gap: 8px; margin-bottom: 0.5rem;">
              <span aria-hidden="true">📞</span>
              <a href="tel:<?php echo e($_SESSION['system']['contact'] ?? '+18005550199'); ?>"><?php echo e($_SESSION['system']['contact'] ?? '+1 (800) 555-0199'); ?></a>
            </li>
            <li style="display: flex; gap: 8px;">
              <span aria-hidden="true">✉️</span>
              <a href="mailto:<?php echo e($_SESSION['system']['email'] ?? 'support@gaatitrack.com'); ?>"><?php echo e($_SESSION['system']['email'] ?? 'support@gaatitrack.com'); ?></a>
            </li>
          </ul>
        </div>

      </div>

      <!-- Footer Bottom -->
      <div class="gt-footer-bottom">
        <div>
          &copy; <?php echo date('Y'); ?> <?php echo e($_SESSION['system']['name']); ?>. All rights reserved.
        </div>
        <div style="display: flex; gap: 1.5rem;">
          <a href="<?php echo APP_URL; ?>/login.php" style="color: var(--gt-navy-400); font-weight: 500;">Staff Portal</a>
          <a href="<?php echo APP_URL; ?>/tracking.php" style="color: var(--gt-navy-400);">Live Tracker</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Design System Interactive Scripts -->
  <script src="<?php echo APP_URL; ?>/assets/js/design-system.js"></script>
</body>
</html>
