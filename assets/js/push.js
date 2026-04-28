/* FleetSimplify VBMS — client-side Web Push subscription helper.
 *
 * Wires up a single button:
 *   <button id="push-toggle" data-public-key="..." data-subscribe-url="..." data-unsubscribe-url="..." data-csrf="...">
 * The button text + state are managed automatically based on the current
 * notification permission and existing subscription.
 *
 * The expected lifecycle:
 *   1. User clicks the button.
 *   2. Browser prompts for permission (only if granted/default).
 *   3. We register /sw.js, ask the push service for a subscription, and
 *      POST { endpoint, p256dh, auth } to api/push-subscribe.php.
 *   4. To turn off, clicking again unsubscribes locally and notifies the server.
 */

(function () {
    'use strict';

    var btn = document.getElementById('push-toggle');
    if (!btn) return;

    var statusEl = document.getElementById('push-status');
    var publicKey      = btn.dataset.publicKey;
    var subscribeUrl   = btn.dataset.subscribeUrl;
    var unsubscribeUrl = btn.dataset.unsubscribeUrl;
    var csrf           = btn.dataset.csrf;

    function setStatus(label, kind) {
        if (statusEl) {
            statusEl.textContent = label;
            statusEl.className = 'push-status push-status-' + (kind || 'idle');
        }
    }

    function unsupported() {
        return !('serviceWorker' in navigator)
            || !('PushManager'    in window)
            || !('Notification'   in window);
    }

    if (unsupported()) {
        btn.disabled = true;
        btn.textContent = 'Push not supported';
        setStatus('This browser doesn\'t support Web Push (try Chrome/Firefox/Edge over HTTPS).', 'warn');
        return;
    }
    if (Notification.permission === 'denied') {
        btn.disabled = true;
        btn.textContent = 'Notifications blocked';
        setStatus('You blocked notifications for this site. Click the lock icon in the address bar to re-enable.', 'warn');
        return;
    }

    function urlBase64ToUint8Array(b64) {
        var pad = '='.repeat((4 - b64.length % 4) % 4);
        var s = (b64 + pad).replace(/-/g, '+').replace(/_/g, '/');
        var raw = atob(s);
        var arr = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
        return arr;
    }
    function arrayBufferToBase64Url(buf) {
        var bytes = new Uint8Array(buf);
        var s = '';
        for (var i = 0; i < bytes.length; i++) s += String.fromCharCode(bytes[i]);
        return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function refreshUI(sub) {
        if (sub) {
            btn.textContent = '🔕 Disable notifications';
            btn.dataset.state = 'on';
            setStatus('You\'ll get a notification on this device when a driver requests your help — even if the tab is closed.', 'on');
        } else {
            btn.textContent = '🔔 Enable notifications';
            btn.dataset.state = 'off';
            setStatus('Click to receive booking alerts even when this tab is closed.', 'idle');
        }
    }

    async function init() {
        try {
            var reg = await navigator.serviceWorker.register('/sw.js');
            // SW path needs to resolve from site root — if the app is mounted at /fleet_simplify/,
            // adjust the register path. We try the site-root first, then fall back.
            try { await navigator.serviceWorker.ready; } catch (_) {}
            var sub = await reg.pushManager.getSubscription();
            refreshUI(sub);
        } catch (err) {
            // Fallback: register relative to the script origin.
            try {
                var reg2 = await navigator.serviceWorker.register('sw.js');
                await navigator.serviceWorker.ready;
                var sub2 = await reg2.pushManager.getSubscription();
                refreshUI(sub2);
            } catch (err2) {
                btn.disabled = true;
                btn.textContent = 'Service worker error';
                setStatus('Could not register the service worker (' + (err2.message || err.message) + '). HTTPS is required except on localhost.', 'warn');
            }
        }
    }

    async function enable() {
        btn.disabled = true;
        btn.textContent = 'Enabling…';
        try {
            var perm = await Notification.requestPermission();
            if (perm !== 'granted') {
                refreshUI(null);
                setStatus('Permission was not granted.', 'warn');
                return;
            }
            var reg = await navigator.serviceWorker.ready;
            var sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicKey)
            });

            var fd = new FormData();
            fd.append('csrf', csrf);
            fd.append('endpoint', sub.endpoint);
            fd.append('p256dh',   arrayBufferToBase64Url(sub.getKey('p256dh')));
            fd.append('auth',     arrayBufferToBase64Url(sub.getKey('auth')));
            fd.append('user_agent', navigator.userAgent.slice(0, 250));

            var r = await fetch(subscribeUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
            var data = await r.json();
            if (!data.ok) throw new Error(data.error || 'subscribe failed');
            refreshUI(sub);
            if (window.toast) window.toast('Browser notifications enabled.', 'success');
        } catch (err) {
            refreshUI(null);
            setStatus('Failed to enable: ' + err.message, 'warn');
            if (window.toast) window.toast('Could not enable notifications.', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    async function disable() {
        btn.disabled = true;
        btn.textContent = 'Disabling…';
        try {
            var reg = await navigator.serviceWorker.ready;
            var sub = await reg.pushManager.getSubscription();
            if (sub) {
                var endpoint = sub.endpoint;
                await sub.unsubscribe();
                var fd = new FormData();
                fd.append('csrf', csrf);
                fd.append('endpoint', endpoint);
                await fetch(unsubscribeUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
            }
            refreshUI(null);
            if (window.toast) window.toast('Notifications disabled.', 'info');
        } catch (err) {
            setStatus('Failed to disable: ' + err.message, 'warn');
        } finally {
            btn.disabled = false;
        }
    }

    btn.addEventListener('click', function () {
        if (btn.dataset.state === 'on') disable();
        else enable();
    });

    init();
})();
