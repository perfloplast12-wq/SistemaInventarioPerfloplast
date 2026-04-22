const CACHE_NAME = 'perfloplast-v1';
const ASSETS_TO_CACHE = [
  '/admin/login',
  '/css/dashboard.css',
  '/images/logo-perfloplast-premium.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

self.addEventListener('fetch', (event) => {
  // Solo cachear peticiones GET de assets estáticos o páginas principales
  if (event.request.method !== 'GET') return;

  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request).then((fetchResponse) => {
        // Opcional: Cachear dinámicamente nuevos assets estáticos
        if (event.request.url.includes('.css') || event.request.url.includes('.js') || event.request.url.includes('.png')) {
           const cacheCopy = fetchResponse.clone();
           caches.open(CACHE_NAME).then(cache => cache.put(event.request, cacheCopy));
        }
        return fetchResponse;
      });
    }).catch(() => {
      // Fallback si no hay internet y no está en cache
      if (event.request.mode === 'navigate') {
        return caches.match('/admin/login');
      }
    })
  );
});
