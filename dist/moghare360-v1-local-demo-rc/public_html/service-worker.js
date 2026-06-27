/* MOGHARE360 public shell — full replace v2 (2026-06-26) */
const CACHE_NAME = 'moghare360-public-v1-full-replace-v2-20260626';
const STATIC_ASSETS = [
  './',
  './index.php',
  './assets/css/mirror.css?v=full-replace-v2',
  './assets/css/moghare360-v1-luxury-ui.css?v=full-replace-v2',
  './manifest.webmanifest'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  if (req.method !== 'GET') {
    return;
  }

  if (url.pathname.includes('customer-request') ||
      url.pathname.includes('staff-login') ||
      url.pathname.includes('owner-login') ||
      url.pathname.includes('company-owner') ||
      url.pathname.includes('mirror-health') ||
      url.pathname.includes('/api/')) {
    return;
  }

  const isStatic = /\.(css|png|jpg|jpeg|webp|svg|webmanifest|js)(\?|$)/i.test(url.pathname + url.search) ||
    url.pathname.endsWith('index.php');

  if (!isStatic) {
    return;
  }

  event.respondWith(
    caches.match(req).then((cached) => cached || fetch(req).then((res) => {
      if (res && res.status === 200 && res.type === 'basic') {
        const copy = res.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
      }
      return res;
    }))
  );
});
