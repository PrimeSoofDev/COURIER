/**
 * GaaTiTrack Design System JavaScript
 * Lightweight, accessible UI utilities for toasts, modals, confirmation dialogs, and navigation.
 */

(function(window, document) {
  'use strict';

  // 1. Mobile Menu Toggle
  function initMobileMenu() {
    const toggleBtn = document.querySelector('.gt-mobile-toggle');
    const navMenu = document.querySelector('.gt-nav-menu');
    if (toggleBtn && navMenu) {
      toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        const isOpen = navMenu.classList.toggle('is-open');
        toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });

      document.addEventListener('click', function(e) {
        if (!navMenu.contains(e.target) && !toggleBtn.contains(e.target)) {
          navMenu.classList.remove('is-open');
          toggleBtn.setAttribute('aria-expanded', 'false');
        }
      });
    }
  }

  // 2. Toast Notification System
  function ensureToastContainer() {
    let container = document.getElementById('gt-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'gt-toast-container';
      container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;max-width:380px;width:calc(100% - 40px);';
      document.body.appendChild(container);
    }
    return container;
  }

  function gtToast(message, type = 'info', duration = 4000) {
    const container = ensureToastContainer();
    const toast = document.createElement('div');
    toast.className = `gt-alert gt-alert-${type === 'error' ? 'error' : (type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'info'))}`;
    toast.style.cssText = 'pointer-events:auto;box-shadow:0 10px 25px -5px rgba(15,23,42,0.15);margin:0;animation:gtFadeIn 200ms ease;display:flex;align-items:center;justify-content:space-between;gap:12px;';

    const text = document.createElement('div');
    text.textContent = message;

    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = 'background:none;border:none;font-size:18px;cursor:pointer;color:inherit;opacity:0.7;padding:0 4px;';
    closeBtn.onclick = () => removeToast(toast);

    toast.appendChild(text);
    toast.appendChild(closeBtn);
    container.appendChild(toast);

    const timer = setTimeout(() => {
      removeToast(toast);
    }, duration);

    function removeToast(el) {
      clearTimeout(timer);
      el.style.opacity = '0';
      el.style.transform = 'translateY(-6px)';
      el.style.transition = 'all 200ms ease';
      setTimeout(() => {
        if (el.parentNode) el.parentNode.removeChild(el);
      }, 200);
    }
  }

  // 3. Accessible Confirmation Dialog
  function gtConfirm(title, message, onConfirm) {
    let modal = document.getElementById('gt-confirm-modal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'gt-confirm-modal';
      modal.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:10000;display:flex;align-items:center;justify-content:center;padding:20px;';
      document.body.appendChild(modal);
    }

    modal.innerHTML = `
      <div class="gt-card" style="max-width:440px;width:100%;box-shadow:0 20px 35px -5px rgba(15,23,42,0.25);animation:gtPopIn 180ms ease;">
        <div class="gt-card-header">
          <h4 style="margin:0;font-size:1.1rem;">${escapeHtml(title)}</h4>
        </div>
        <div class="gt-card-body">
          <p style="margin:0;color:var(--gt-navy-600);">${escapeHtml(message)}</p>
        </div>
        <div class="gt-card-footer" style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" class="gt-btn gt-btn-secondary gt-btn-sm" id="gt-confirm-cancel">Cancel</button>
          <button type="button" class="gt-btn gt-btn-danger gt-btn-sm" id="gt-confirm-ok">Continue</button>
        </div>
      </div>
    `;

    modal.style.display = 'flex';

    const cancelBtn = modal.querySelector('#gt-confirm-cancel');
    const okBtn = modal.querySelector('#gt-confirm-ok');

    function closeModal() {
      modal.style.display = 'none';
    }

    cancelBtn.onclick = closeModal;
    okBtn.onclick = () => {
      closeModal();
      if (typeof onConfirm === 'function') onConfirm();
    };

    modal.onclick = (e) => {
      if (e.target === modal) closeModal();
    };
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // Inject animation keyframes
  const style = document.createElement('style');
  style.textContent = `
    @keyframes gtFadeIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes gtPopIn { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }
  `;
  document.head.appendChild(style);

  // Initialize on DOM load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileMenu);
  } else {
    initMobileMenu();
  }

  // Export to window
  window.gtToast = gtToast;
  window.gtConfirm = gtConfirm;

})(window, document);
