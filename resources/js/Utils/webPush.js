import axios from 'axios';

/**
 * Convert VAPID base64 public key to Uint8Array for pushManager
 */
export function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

/**
 * Check if the browser supports Web Push & Service Workers
 */
export function isPushSupported() {
  return (
    typeof window !== 'undefined' &&
    'serviceWorker' in navigator &&
    'PushManager' in window &&
    'Notification' in window
  );
}

/**
 * Get current browser notification permission
 */
export function getNotificationPermission() {
  if (!isPushSupported()) return 'unsupported';
  return Notification.permission;
}

/**
 * Subscribe current browser to Web Push
 */
export async function subscribeToWebPush(customVapidKey = null) {
  if (!isPushSupported()) {
    throw new Error('Web Push is not supported on this browser or device.');
  }

  // Request user permission
  const permission = await Notification.requestPermission();
  if (permission !== 'granted') {
    throw new Error('Notification permission was ' + permission);
  }

  // Get VAPID public key if not provided
  let vapidKey = customVapidKey;
  if (!vapidKey) {
    const res = await axios.get('/push/public-key');
    vapidKey = res.data.public_key;
  }

  if (!vapidKey) {
    throw new Error('VAPID public key is missing on the server.');
  }

  // Ensure Service Worker is registered and ready
  await navigator.serviceWorker.register('/sw.js');
  const registration = await navigator.serviceWorker.ready;

  // Subscribe with PushManager
  const applicationServerKey = urlBase64ToUint8Array(vapidKey);
  const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: applicationServerKey,
  });

  const subJson = subscription.toJSON();

  // Send subscription to server
  const res = await axios.post('/push/subscribe', {
    endpoint: subJson.endpoint,
    public_key: subJson.keys?.p256dh,
    auth_token: subJson.keys?.auth,
    content_encoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0],
  });

  return { success: true, subscription: subJson, data: res.data };
}

/**
 * Check if current browser is already subscribed
 */
export async function getCurrentSubscription() {
  if (!isPushSupported()) return null;
  try {
    const registration = await navigator.serviceWorker.ready;
    return await registration.pushManager.getSubscription();
  } catch (e) {
    return null;
  }
}
