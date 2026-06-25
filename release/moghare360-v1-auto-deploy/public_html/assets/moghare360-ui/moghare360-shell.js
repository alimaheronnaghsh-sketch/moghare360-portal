/**
 * MOGHARE360 ERP — Application Shell UI Behavior
 * Mission 32 — Sidebar toggle, mobile overlay, active menu (UI only)
 * No database calls. No auth calls. No write actions.
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    var shell = document.querySelector('[data-m360-shell]');
    if (!shell) {
      return;
    }

    var overlay = document.querySelector('[data-m360-shell-overlay]');
    var toggleButtons = document.querySelectorAll('[data-m360-shell-toggle]');
    var navLinks = document.querySelectorAll('.m360-shell-nav-link');

    function isMobile() {
      return window.matchMedia('(max-width: 1024px)').matches;
    }

    function openMobileSidebar() {
      shell.classList.add('is-mobile-sidebar-open');
      if (overlay) {
        overlay.classList.add('is-visible');
      }
      document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
      shell.classList.remove('is-mobile-sidebar-open');
      if (overlay) {
        overlay.classList.remove('is-visible');
      }
      document.body.style.overflow = '';
    }

    function toggleDesktopCollapse() {
      shell.classList.toggle('is-sidebar-collapsed');
    }

    toggleButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        if (isMobile()) {
          if (shell.classList.contains('is-mobile-sidebar-open')) {
            closeMobileSidebar();
          } else {
            openMobileSidebar();
          }
        } else {
          toggleDesktopCollapse();
        }
      });
    });

    if (overlay) {
      overlay.addEventListener('click', closeMobileSidebar);
    }

    navLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        if (isMobile()) {
          closeMobileSidebar();
        }

        navLinks.forEach(function (item) {
          item.classList.remove('is-active');
        });
        link.classList.add('is-active');
      });
    });

    window.addEventListener('resize', function () {
      if (!isMobile()) {
        closeMobileSidebar();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeMobileSidebar();
      }
    });
  });
})();
