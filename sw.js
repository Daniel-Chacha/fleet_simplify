/* FleetSimplify VBMS — service worker.
 *
 * Lives at the site root so its scope covers every page. It runs in a
 * separate thread that the browser keeps alive even after every tab is
 * closed; that's what makes "background" push notifications possible.
 *
 * Two events matter:
 *   - "push"             — fires when our server posts an encrypted message.
 *   - "notificationclick"— fires when the mechanic taps the notification.
 */

self.addEventListener('install',  function (e) { self.skipWaiting(); });
self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()); });

self.addEventListener('push', function (event) {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (_) {}
    var title = data.title || 'New booking request';
    var body  = data.body  || 'A driver needs your help.';
    var url   = data.url   || '/';
    var tag   = data.tag   || 'fs-booking';

    event.waitUntil(self.registration.showNotification(title, {
        body: body,
        icon: data.icon || '/assets/icons/notify-192.png',
        badge: data.badge || '/assets/icons/badge-72.png',
        tag: tag,
        renotify: true,
        requireInteraction: true,
        vibrate: [200, 80, 200, 80, 200],
        data: { url: url }
    }));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var target = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil((async function () {
        var allClients = await clients.matchAll({ type: 'window', includeUncontrolled: true });
        // If a tab already has the target URL open, just focus it.
        for (var i = 0; i < allClients.length; i++) {
            var c = allClients[i];
            try {
                if (new URL(c.url).pathname === new URL(target, c.url).pathname) {
                    return c.focus();
                }
            } catch (_) {}
        }
        // Otherwise, open a new tab.
        return clients.openWindow(target);
    })());
});
