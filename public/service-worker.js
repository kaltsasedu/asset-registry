const CACHE = 'asset-registry-pwa-v1';
const STATIC_ASSETS = ['/manifest.webmanifest', '/pwa-icon.svg'];
self.addEventListener('install', event => { event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(STATIC_ASSETS))); self.skipWaiting(); });
self.addEventListener('activate', event => { event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key !== CACHE).map(key => caches.delete(key)))).then(() => self.clients.claim())); });
self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;
    const url = new URL(event.request.url);
    if (! STATIC_ASSETS.includes(url.pathname)) return;
    event.respondWith(caches.match(event.request).then(cached => cached || fetch(event.request)));
});
