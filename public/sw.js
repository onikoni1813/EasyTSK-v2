// Easytsk V2 PWA Service Worker
// Offline access not required: Network-Only pattern for live data consistency

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

// Web Push Notification Event Listener
self.addEventListener('push', (event) => {
  let data = {};
  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data = { title: 'EasyTSK Notification', body: event.data.text() };
    }
  }

  const title = data.title || 'EasyTSK Update';
  const options = {
    body: data.body || 'You have a new update from EasyTSK!',
    icon: data.icon || '/icon-192.png',
    badge: data.badge || '/icon-192.png',
    image: data.image || null,
    tag: data.tag || 'easytsk-push-' + Date.now(),
    vibrate: data.vibrate || [200, 100, 200],
    data: {
      url: data.url || '/tasks',
      dateOfArrival: Date.now()
    },
    actions: [
      { action: 'open_url', title: 'Open EasyTSK 🚀' }
    ]
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

// Notification Click Event Listener
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const targetUrl = event.notification.data?.url || '/tasks';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      // If a window is already open, focus it and navigate
      for (let client of windowClients) {
        if ('focus' in client) {
          if (client.url.includes(self.location.origin)) {
            client.focus();
            return client.navigate(targetUrl);
          }
        }
      }
      // If no window is open, open a new one
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
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
