// Easytsk V2 PWA Service Worker
// Offline access not required: Network-Only pattern for live data consistency

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  // Only handle HTTP/HTTPS GET requests
  if (event.request.method !== 'GET' || !event.request.url.startsWith('http')) {
    return;
  }

  // Network-Only strategy: pass through directly to network
  event.respondWith(
    fetch(event.request).catch((error) => {
      // Return simple offline response if completely disconnected from network
      return new Response(
        '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Offline - Easytsk V2</title><style>body{background-color:#02040a;color:#f8fafc;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;padding:20px;}h1{color:#818cf8;font-size:24px;}p{color:#94a3b8;font-size:14px;max-width:400px;}</style></head><body><div><h1>You are offline</h1><p>An active internet connection is required to access Easytsk V2 tasks and rewards.</p></div></body></html>',
        {
          headers: { 'Content-Type': 'text/html' }
        }
      );
    })
  );
});
