
      </main>
    </div><!-- /.gt-content-scroll -->
  </div><!-- /.gt-admin-main -->
</div><!-- /.gt-admin-shell -->

<script src="<?php echo APP_URL; ?>/assets/js/design-system.js"></script>
<script>
/* Remove any legacy collapsed key from previous builds */
localStorage.removeItem('gt_sidebar_collapsed');

(function () {
  'use strict';

  const sidebar    = document.getElementById('admin-sidebar');
  const toggleBtn  = document.getElementById('sb-toggle-btn');
  const overlay    = document.getElementById('sb-overlay');
  const MOBILE_BP  = 900;
  const KEY        = 'gt_sb_collapsed';

  if (!sidebar || !toggleBtn) return;

  /* ── Helpers ── */
  function isMobile() { return window.innerWidth <= MOBILE_BP; }

  /* ── Restore desktop collapse state ── */
  if (!isMobile() && localStorage.getItem(KEY) === '1') {
    sidebar.classList.add('is-collapsed');
    toggleBtn.setAttribute('aria-expanded', 'false');
  }

  /* ── Toggle click ── */
  toggleBtn.addEventListener('click', function () {
    if (isMobile()) {
      // Mobile: slide in/out
      const isOpen = sidebar.classList.toggle('mobile-open');
      overlay.classList.toggle('open', isOpen);
      toggleBtn.setAttribute('aria-expanded', String(isOpen));
      document.body.style.overflow = isOpen ? 'hidden' : '';
    } else {
      // Desktop: collapse / expand icon rail
      const isCollapsed = sidebar.classList.toggle('is-collapsed');
      localStorage.setItem(KEY, isCollapsed ? '1' : '0');
      toggleBtn.setAttribute('aria-expanded', String(!isCollapsed));
    }
  });

  /* ── Overlay click closes mobile ── */
  overlay.addEventListener('click', function () {
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('open');
    toggleBtn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  });

  /* ── ESC closes mobile sidebar ── */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
      sidebar.classList.remove('mobile-open');
      overlay.classList.remove('open');
      document.body.style.overflow = '';
      toggleBtn.focus();
    }
  });

  /* ── Close mobile sidebar on nav click ── */
  sidebar.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () {
      if (isMobile()) {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
      }
    });
  });

  /* ── Handle resize: clear mobile state when going desktop ── */
  window.addEventListener('resize', function () {
    if (!isMobile()) {
      sidebar.classList.remove('mobile-open');
      overlay.classList.remove('open');
      document.body.style.overflow = '';
      // Re-apply saved collapse state
      if (localStorage.getItem(KEY) === '1') {
        sidebar.classList.add('is-collapsed');
      } else {
        sidebar.classList.remove('is-collapsed');
      }
    }
  });
})();
</script>

</body>
</html>
