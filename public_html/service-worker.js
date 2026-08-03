/**
 * MOGHARE360 secure PWA service worker
 * Version: m360-pwa-static-v1-20260803
 *
 * Policy:
 * - Cache ONLY approved versioned static assets (CSS/JS/icons/public shell).
 * - NEVER cache authenticated HTML, APIs, invoices, OTP, profiles, uploads, cookies.
 * - PHP / ERP / API / auth routes: network-only (bypass).
 * - Navigation offline: offline.html shell only.
 */
/* eslint-disable no-restricted-globals */

const CACHE_VERSION = 'm360-pwa-static-v1-20260803';
const OFFLINE_URL = './offline.html';

const PRECACHE_URLS = [
  OFFLINE_URL,
  './manifest.webmanifest',
  './assets/js/m360-pwa.js',
  './assets/css/mirror.css',
  './assets/css/moghare360-v1-luxury-ui.css',
  './assets/css/m360-design-system.css',
  './assets/moghare360-ui/moghare360-design-tokens.css',
  './assets/icons/icon-192.png',
  './assets/icons/icon-512.png'
];

/** Path / query signals that must never be cached. */
function isSensitiveRequest(url) {
  const path = (url.pathname || '').toLowerCase();
  const search = (url.search || '').toLowerCase();
  const hay = path + search;

  if (/\.php($|\?)/i.test(path)) {
    return true;
  }
  if (path.includes('/api/') || path.endsWith('/api')) {
    return true;
  }

  const blocked = [
    'erp-', 'staff-', 'owner-', 'company-owner', 'personnel',
    'customer-', 'otp', 'invoice', 'settlement', 'payroll',
    'medical', 'upload', 'signature', 'finance', 'hr-',
    'contract', 'session', 'logout', 'login', 'mirror-health',
    'profile', 'private', 'backup', 'sql', 'migrate', 'tools/'
  ];
  return blocked.some((token) => hay.includes(token));
}

function isStaticAsset(url) {
  return /\.(css|js|png|jpg|jpeg|webp|svg|ico|woff2?|ttf|webmanifest)(\?|$)/i.test(
    url.pathname + url.search
  );
}

function isApprovedStatic(url) {
  if (!isStaticAsset(url)) {
    return false;
  }
  if (isSensitiveRequest(url)) {
    return false;
  }
  const path = url.pathname.toLowerCase();
  // Allow only assets under public asset trees / root manifest
  return (
    path.includes('/assets/') ||
    path.endsWith('/manifest.webmanifest') ||
    path.endsWith('manifest.webmanifest') ||
    path.endsWith('/offline.html') ||
    path.endsWith('offline.html')
  );
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_VERSION)
      .then((cache) => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
      .catch(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((key) => key !== CACHE_VERSION)
          .map((key) => caches.delete(key))
      )
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('message', (event) => {
  const data = event.data || {};
  if (data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (data.type === 'CLEAR_CACHES') {
    event.waitUntil(
      caches.keys().then((keys) => Promise.all(keys.map((k) => caches.delete(k))))
    );
  }
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') {
    return;
  }

  let url;
  try {
    url = new URL(req.url);
  } catch (e) {
    return;
  }

  // Cross-origin: do not intercept
  if (url.origin !== self.location.origin) {
    return;
  }

  // Sensitive / PHP / API: network-only, no cache put
  if (isSensitiveRequest(url)) {
    event.respondWith(
      fetch(req, { cache: 'no-store' }).catch(() =>
        req.mode === 'navigate'
          ? caches.match(OFFLINE_URL)
          : Response.error()
      )
    );
    return;
  }

  // Navigations: network-first → offline shell (never cache HTML documents)
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req, { cache: 'no-store' })
        .then((res) => res)
        .catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }

  // Approved static assets only: stale-while-revalidate within allowlist
  if (isApprovedStatic(url)) {
    event.respondWith(
      caches.open(CACHE_VERSION).then((cache) =>
        cache.match(req).then((cached) => {
          const networkPromise = fetch(req).then((res) => {
            if (res && res.ok && res.type === 'basic') {
              cache.put(req, res.clone());
            }
            return res;
          }).catch(() => cached);
          return cached || networkPromise;
        })
      )
    );
  }
  // Everything else: browser default (no SW caching)
});
