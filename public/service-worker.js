/**
 * Excellent Scholars Hub service worker.
 *
 * Three jobs:
 *
 *   1. Make the app installable. Chrome/Edge only offer "Install app" (and only
 *      let a page raise a system notification) when the site is served over HTTPS
 *      and has a registered service worker with a `fetch` handler. The pages are
 *      server rendered and need no offline cache, so the fetch handler is a
 *      network pass-through — nothing is intercepted.
 *   2. Show the notifications Web Push delivers (`push`), even when the app is
 *      closed.
 *   3. Open the right page when a notification is tapped (`notificationclick`).
 *
 * Registered from resources/views/layouts/app.blade.php through
 * public/assets/js/esh-pwa.js; the install button lives in
 * resources/views/partials/pwa-install-button.blade.php.
 */

var NOTIFICATION_FALLBACK_URL = '/notifications';
var DEFAULT_TITLE = 'Excellent Scholars Hub';
var DEFAULT_ICON = '/icons/icon-192.png';
var DEFAULT_BADGE = '/icons/icon-32.png';

self.addEventListener('install', function () {
    // Take over as soon as this version is fetched, without waiting for the
    // previous worker to be released by every open tab.
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

// Network-only: the handler is what makes the app installable. No caching yet.
self.addEventListener('fetch', function () {
    // Intentionally empty - the browser falls back to its normal network request.
});

// ---------------------------------------------------------------- Web Push
//
// The push service delivers the encrypted payload that
// App\Services\WebPushService::payload() built. It arrives here whether or not
// the app is open.
//
// A subscription is created with `userVisibleOnly: true`, so every push MUST
// result in a notification; a browser that receives one and sees nothing shown
// eventually revokes the subscription.
self.addEventListener('push', function (event) {
    var payload = {};

    if (event.data) {
        try {
            payload = event.data.json() || {};
        } catch (e) {
            // Encrypted with a payload we did not write: show it as plain text.
            payload = { body: event.data.text() };
        }
    }

    var options = {
        body: payload.body || '',
        icon: payload.icon || DEFAULT_ICON,
        badge: payload.badge || DEFAULT_BADGE,
        // Reusing a tag replaces the earlier notification instead of stacking a
        // second one for the same alert.
        tag: payload.tag || undefined,
        data: { url: payload.url || NOTIFICATION_FALLBACK_URL },
        vibrate: [120, 60, 120]
    };

    event.waitUntil(self.registration.showNotification(payload.title || DEFAULT_TITLE, options));
});

// The browser rotated the endpoint (or dropped it). Ask any open page to
// re-subscribe and post the new endpoint; the next visit does the same, because
// esh-pwa.js always re-checks on load.
self.addEventListener('pushsubscriptionchange', function (event) {
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (var i = 0; i < clientList.length; i++) {
                clientList[i].postMessage({ type: 'esh:push-resubscribe' });
            }
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    var target = NOTIFICATION_FALLBACK_URL;

    if (event.notification.data && event.notification.data.url) {
        target = event.notification.data.url;
    }

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];

                if (client.url.indexOf(target) !== -1 && 'focus' in client) {
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(target);
            }

            return undefined;
        })
    );
});
