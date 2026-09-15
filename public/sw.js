/* DHP offline shell — plain and human-readable.
 *
 * WHAT IT DOES:
 * - Static files (CSS, JS, images) are served from cache first, so pages
 *   open fast and keep working with no internet.
 * - Pages and form submissions always go to the server first (never cached),
 *   so staff never see stale patient data.
 * - If a page cannot load at all (server unreachable), /offline is shown.
 */
var SW_VERSION = 'dhp-offline-v1';
var STATIC_CACHE = 'dhp-static-v1';

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(STATIC_CACHE).then(function (cache) {
            return cache.add('/offline').catch(function () {});
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(keys.map(function (key) {
                if (key !== STATIC_CACHE) {
                    return caches.delete(key);
                }
            }));
        }).then(function () { return self.clients.claim(); })
    );
});

function isStaticFile(url) {
    return url.pathname.indexOf('/build/') === 0 ||
        url.pathname.indexOf('/images/') === 0 ||
        url.pathname.indexOf('/storage/') === 0 ||
        url.host.indexOf('fonts.bunny.net') !== -1;
}

self.addEventListener('fetch', function (event) {
    var req = event.request;

    // Only handle GET requests; let POSTs (forms) pass through untouched.
    if (req.method !== 'GET') {
        return;
    }

    var url = new URL(req.url);

    // Static files: cache first, update the cache in the background.
    if (isStaticFile(url)) {
        event.respondWith(
            caches.open(STATIC_CACHE).then(function (cache) {
                return cache.match(req).then(function (cached) {
                    var network = fetch(req).then(function (res) {
                        if (res && res.status === 200) {
                            cache.put(req, res.clone());
                        }
                        return res;
                    }).catch(function () { return cached; });
                    return cached || network;
                });
            })
        );
        return;
    }

    // Pages: always try the network first; fall back to the offline page.
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(function () {
                return caches.match('/offline').then(function (cached) {
                    return cached || fetch('/offline');
                });
            })
        );
    }
});
