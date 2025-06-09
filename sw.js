// Service Worker for YankesDokpol PWA
const CACHE_NAME = 'yankesdokpol-cache-v2';

// Assets to cache immediately (minimal set for offline functionality)
const CORE_ASSETS = [
  './offline.html',
  './assets/css/style.css',
  './assets/js/pwa.js',
  '/assets/img/icons/icon-72x72.png',
  '/assets/img/icons/icon-96x96.png',
  '/assets/img/icons/icon-128x128.png',
  '/assets/img/icons/icon-144x144.png',
  '/assets/img/icons/icon-152x152.png',
  '/assets/img/icons/icon-192x192.png',
  '/assets/img/icons/icon-384x384.png',
  '/assets/img/icons/icon-512x512.png',
  '/manifest.json'
];

// Additional assets to cache when they're requested
const CACHEABLE_EXTENSIONS = [
  '.css',
  '.js',
  '.png',
  '.jpg',
  '.jpeg',
  '.gif',
  '.svg',
  '.ico',
  '.woff',
  '.woff2',
  '.ttf',
  '.eot'
];

// Install event - cache core assets only
self.addEventListener('install', event => {
  console.log('[ServiceWorker] Install');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[ServiceWorker] Pre-caching core assets');
        return cache.addAll(CORE_ASSETS).catch(error => {
          console.error('[ServiceWorker] Failed to cache core assets:', error);
          // Continue with installation even if some resources fail to cache
          return Promise.resolve();
        });
      })
      .then(() => {
        console.log('[ServiceWorker] Skipping waiting');
        return self.skipWaiting();
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('[ServiceWorker] Activate');
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            console.log('[ServiceWorker] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
    .then(() => {
      console.log('[ServiceWorker] Claiming clients');
      return self.clients.claim();
    })
    .catch(error => {
      console.error('[ServiceWorker] Activation error:', error);
    })
  );
});

// Helper function to determine if a request should be cached
function shouldCache(url) {
  // Don't cache PHP files except for specific ones
  if (url.endsWith('.php') && 
      !url.endsWith('form_peserta.php') && 
      !url.endsWith('index.php')) {
    return false;
  }
  
  // Cache static assets based on extension
  const extension = url.split('.').pop();
  return CACHEABLE_EXTENSIONS.includes('.' + extension);
}

// Fetch event - serve from cache or network with improved error handling
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip cross-origin requests
  const url = new URL(event.request.url);
  const isSameOrigin = url.origin === self.location.origin;
  
  // For HTML navigations (page loads), use network-first strategy
  const isHTMLRequest = event.request.headers.get('accept') && 
                        event.request.headers.get('accept').includes('text/html');

  if (isHTMLRequest) {
    // For HTML pages, always try network first, fall back to cache, then offline page
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Cache the latest version in the background
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseToCache);
          });
          return response;
        })
        .catch(() => {
          // If network fails, try to serve from cache
          return caches.match(event.request)
            .then(cachedResponse => {
              if (cachedResponse) {
                return cachedResponse;
              }
              // If not in cache, show offline page
              return caches.match('./offline.html');
            });
        })
    );
    return;
  }

  // For non-HTML requests, use cache-first strategy
  if (isSameOrigin && shouldCache(url.pathname)) {
    event.respondWith(
      caches.match(event.request)
        .then(cachedResponse => {
          if (cachedResponse) {
            // Return cached response
            return cachedResponse;
          }

          // If not in cache, fetch from network
          return fetch(event.request)
            .then(response => {
              // Cache the response if valid
              if (response && response.status === 200) {
                const responseToCache = response.clone();
                caches.open(CACHE_NAME).then(cache => {
                  cache.put(event.request, responseToCache);
                });
              }
              return response;
            })
            .catch(error => {
              console.error('[ServiceWorker] Fetch error:', error);
              // Return a simple error response
              return new Response('Network error occurred', {
                status: 503,
                statusText: 'Service Unavailable'
              });
            });
        })
    );
    return;
  }

  // For all other requests, just fetch from network
  // This prevents the service worker from interfering with other requests
  // No respondWith() means the browser handles the request normally
});

// Handle push notifications (if needed in the future)
self.addEventListener('push', event => {
  const title = 'YankesDokpol';
  const options = {
    body: event.data ? event.data.text() : 'New notification',
    icon: './assets/img/icons/icon-192x192.png',
    badge: './assets/img/icons/badge-72x72.png'
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('./')
  );
});

// Log any errors that occur within the Service Worker
self.addEventListener('error', function(event) {
  console.error('[ServiceWorker] Error:', event.message, event.filename, event.lineno);
});

console.log('[ServiceWorker] Service Worker loaded successfully');
