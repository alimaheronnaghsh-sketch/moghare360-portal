/**
 * MOGHARE360 PWA client — registration, install prompt, updates, logout cache clear.
 * No business logic. Safe for public shell pages.
 */
(function (global) {
  'use strict';

  var SW_URL = 'service-worker.js';
  var deferredPrompt = null;

  function isStandalone() {
    return (global.matchMedia && global.matchMedia('(display-mode: standalone)').matches)
      || global.navigator.standalone === true;
  }

  function clearAllCaches() {
    var tasks = [];
    if (global.caches && caches.keys) {
      tasks.push(
        caches.keys().then(function (keys) {
          return Promise.all(keys.map(function (k) { return caches.delete(k); }));
        })
      );
    }
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
      navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_CACHES' });
    }
    return Promise.all(tasks);
  }

  function register() {
    if (!('serviceWorker' in navigator)) {
      return Promise.resolve(null);
    }
    return navigator.serviceWorker.register(SW_URL, { scope: './' }).then(function (reg) {
      if (reg.waiting) {
        reg.waiting.postMessage({ type: 'SKIP_WAITING' });
      }
      reg.addEventListener('updatefound', function () {
        var worker = reg.installing;
        if (!worker) return;
        worker.addEventListener('statechange', function () {
          if (worker.state === 'installed' && navigator.serviceWorker.controller) {
            // New version ready — activate immediately and refresh public shell
            worker.postMessage({ type: 'SKIP_WAITING' });
            if (global.document && document.body) {
              document.body.setAttribute('data-m360-pwa-update', 'ready');
            }
          }
        });
      });
      return reg;
    });
  }

  function wireInstallPrompt() {
    global.addEventListener('beforeinstallprompt', function (e) {
      e.preventDefault();
      deferredPrompt = e;
      global.dispatchEvent(new CustomEvent('m360-pwa-installable'));
      if (document.body) {
        document.body.setAttribute('data-m360-pwa-installable', '1');
      }
    });
    global.addEventListener('appinstalled', function () {
      deferredPrompt = null;
      if (document.body) {
        document.body.setAttribute('data-m360-pwa-installed', '1');
      }
    });
  }

  function promptInstall() {
    if (!deferredPrompt) {
      return Promise.resolve({ ok: false, reason: 'no_prompt' });
    }
    deferredPrompt.prompt();
    return deferredPrompt.userChoice.finally(function () {
      deferredPrompt = null;
    });
  }

  /**
   * Call on logout / session expiry pages.
   */
  function onLogout() {
    return clearAllCaches().then(function () {
      if ('serviceWorker' in navigator) {
        return navigator.serviceWorker.getRegistrations().then(function (regs) {
          // Keep SW for public shell installability; only clear caches.
          // Re-register after clear so public assets can recache safely.
          return register();
        });
      }
      return null;
    });
  }

  // Deep-link / standalone: ensure relative navigations stay in scope
  function handleDeepLinkHints() {
    if (!isStandalone()) return;
    document.documentElement.setAttribute('data-m360-display', 'standalone');
  }

  wireInstallPrompt();

  global.M360PWA = {
    register: register,
    promptInstall: promptInstall,
    clearAllCaches: clearAllCaches,
    onLogout: onLogout,
    isStandalone: isStandalone,
    cacheVersionHint: 'm360-pwa-static-v1-20260803'
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      handleDeepLinkHints();
      register();
    });
  } else {
    handleDeepLinkHints();
    register();
  }
})(window);
