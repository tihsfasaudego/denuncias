
const CACHE_NAME = 'hsfa-denuncias-v1';
const urlsToCache = [
    '/',
    '/css/hsfa-theme.css',
    '/css/styles.css',
    '/js/scripts.js',
    '/css/images/logo1.png'
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});
