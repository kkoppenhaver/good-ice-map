// Minimal service worker. Required for Chrome on Android to install the site
// as a real WebAPK (rather than a Chrome shortcut), which is what makes the
// manifest icon and share_target take effect.
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', () => {
    // Pass through to the network — no caching.
});
