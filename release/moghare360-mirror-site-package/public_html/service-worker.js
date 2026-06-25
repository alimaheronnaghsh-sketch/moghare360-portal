/* MOGHARE360 Mirror PWA — static assets only. No customer/business data cache. */
const CACHE_NAME = 'moghare360-mirror-shell-v1';
const STATIC_ASSETS = [
  './',
  './index.php',
  './assets/css/mirror.css',
  './assets/css/moghare360-v1-luxury-ui.css',
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

  const isStatic = /\.(css|png|jpg|jpeg|webp|svg|webmanifest|js)$/i.test(url.pathname) ||
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
