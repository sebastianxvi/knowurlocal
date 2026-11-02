/* KNOWURLOCAL PWA service worker.
 * Network-only by design: no application pages, API responses, or private data are cached.
 */
self.addEventListener("install", (event) => {
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener("fetch", (event) => {
    const requestUrl = new URL(event.request.url);
    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    // Forward requests to the network without caching. Offline behavior is not provided.
    event.respondWith(fetch(event.request));
});
