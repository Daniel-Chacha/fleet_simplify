# Web Push notifications — design & operations guide

FleetSimplify VBMS uses the W3C **Push API** + **Service Workers** to alert mechanics about new booking requests **even when their browser tab is closed** — no mobile app, no SMS bill. This document explains how the pieces fit together, how to set the system up, how to test it, and how to debug it when something goes wrong.

---

## 1. Why Web Push (and what it isn't)

The original mechanic alerter polled the API every 10 s while the dashboard tab was open and rang a Web Audio beep. That works *only* while the mechanic actively has the page open, which is rarely true in practice. Web Push fixes that: the operating system itself wakes the browser when a push arrives, even after the user has closed every tab.

**What Web Push gives us**

* Native OS-level notification with sound + vibration on Chrome, Firefox, Edge, Opera, Brave, Samsung Internet.
* Works on Android Chrome and Firefox, on macOS / Windows / Linux desktops.
* Free — no per-message cost; the push services are run by Google (FCM), Mozilla (Mozilla Push Service), Apple (APNs), and Microsoft (WNS).

**What Web Push doesn't give us**

* Reliable iOS Safari delivery — Apple only added Web Push in iOS 16.4 *and* the site must be installed as a Home-Screen PWA first.
* Delivery guarantees if the browser is force-quit on iOS or the device is permanently offline.
* Anything if the user has clicked "Block" on the notification permission prompt.

For drivers in the field on cheap Android phones this is fine. For iOS-heavy fleets, plan to add SMS as a backup channel later (the wiring slot is already in `api/booking-actions.php`).

---

## 2. Architecture at a glance

```
                                       (1) GET /mechanic/dashboard.php
   Mechanic browser                    ──────────────────────────►   FleetSimplify (Apache + PHP)
   ┌────────────────────────┐          ◄───────────── HTML w/ public VAPID key + push-toggle button
   │  push.js               │
   │  ┌───────────────────┐ │   (2) registers /sw.js, calls pushManager.subscribe(publicKey)
   │  │ sw.js (background)│ │
   │  └───────────────────┘ │   (3) browser ↔ FCM/Mozilla/etc. handshake → returns
   │                        │       a "subscription" = { endpoint, p256dh, auth }
   │                        │
   │   POST /api/push-subscribe.php  (subscription details + CSRF)
   │   ────────────────────────────────────────────────────────►   stored in `push_subscriptions`
   │
   │ ────  later  ────────────────────────────────────────────
   │
   │   <user creates a booking>                                    POST /api/booking-actions.php
   │                                                                 │  case 'create':
   │                                                                 │    INSERT bookings…
   │                                                                 │    notify_mechanic(mid, payload)
   │                                                                 │       │
   │                                                                 │       └─ for each subscription:
   │                                                                 │             ECDH + HKDF + AES-128-GCM
   │                                                                 │             VAPID JWT (ES256)
   │                                                                 │             POST endpoint
   │                                                                 ▼
   │                                                       FCM / Mozilla Push Service / APNs / WNS
   │                                                                 │
   │  ◄──── encrypted push ──────────────────────────────────────────┘
   │
   │  sw.js fires "push" event → showNotification() → OS displays banner with sound/vibe
   │  Mechanic taps banner → "notificationclick" event → opens /mechanic/chat.php?booking_id=…
```

A "subscription" is just three pieces of opaque data the browser hands to your server:

| Field | What it is |
|-------|------------|
| `endpoint` | A URL on the **push service** (FCM, Mozilla, etc.) that *only* this browser can read from. Looks like `https://fcm.googleapis.com/wp/<long-token>` for Chrome. |
| `p256dh`   | The **browser's** P-256 public key (65 bytes uncompressed, base64url). Used to ECDH-derive the encryption key. |
| `auth`     | A 16-byte **per-subscription secret** mixed into the HKDF chain. Lets the browser verify nobody else has injected a push behind your back. |

**VAPID keys** (Voluntary Application Server Identification) are your *server's* long-lived ECDSA keypair (also P-256). The public half is sent to the browser at subscription time; the private half signs every push as a JWT in the `Authorization: vapid t=…, k=…` header. They prove to the push service that this push is coming from the same origin the user originally subscribed to.

---

## 3. The encryption format (RFC 8291 / `aes128gcm`)

Every payload has to be encrypted end-to-end so that the push service (FCM / Mozilla) can route it but never read it. The whole thing, in plain English:

1. Generate a fresh **ephemeral P-256 keypair** for this single push (call it `as`).
2. Run **ECDH** between `as_priv` and the browser's `p256dh` public key → 32-byte shared secret.
3. **HKDF** that shared secret using the subscription's `auth` secret as the salt, with `"WebPush: info\0" || ua_pub || as_pub` as info → 32-byte intermediate IKM.
4. Generate a fresh **16-byte random salt** for this message.
5. **HKDF-expand** the IKM using the salt twice — once with info `"Content-Encoding: aes128gcm\0"` (16 bytes → CEK) and once with `"Content-Encoding: nonce\0"` (12 bytes → nonce).
6. Pad the plaintext with a **single `0x02` byte** (the "this is the only/last record" marker) and **AES-128-GCM**-encrypt it with the CEK + nonce. Append the 16-byte auth tag.
7. Frame it: `salt(16) || rs(4 BE) || idlen(1) || keyid=as_public(65) || ciphertext|tag`.
8. POST to the endpoint with `Content-Encoding: aes128gcm`, `TTL: <seconds>`, `Authorization: vapid t=<JWT>, k=<server pub key>`.

The implementation lives in [lib/web-push.php](../lib/web-push.php). Every step is annotated. If you want to change record size or TTL, those are constants near the top. If something looks suspicious, the file is < 250 lines and uses only PHP-native OpenSSL primitives — no Composer, no hidden behaviour.

---

## 4. Files in the system

| File | Role |
|------|------|
| [lib/web-push.php](../lib/web-push.php) | Stand-alone WebPush class — does the encryption + JWT + HTTP send. |
| [config/push.php](../config/push.php) | VAPID keys + `notify_mechanic($id, $payload)` helper. |
| [bin/gen-vapid.php](../bin/gen-vapid.php) | CLI script to mint a fresh VAPID keypair. |
| [sql/push-migration.sql](../sql/push-migration.sql) | `push_subscriptions` table. |
| [api/push-subscribe.php](../api/push-subscribe.php) | Browser POSTs here when a mechanic clicks **Enable**. |
| [api/push-unsubscribe.php](../api/push-unsubscribe.php) | Counterpart when they click **Disable** or the SW unsubscribes. |
| [sw.js](../sw.js) | Service worker — handles `push` and `notificationclick` events. |
| [assets/js/push.js](../assets/js/push.js) | UI glue: registers SW, manages the toggle button, calls the API. |
| [mechanic/dashboard.php](../mechanic/dashboard.php) | Hosts the `#push-toggle` button. |
| [api/booking-actions.php](../api/booking-actions.php) | Calls `notify_mechanic()` after `INSERT INTO bookings` and after admin assigns. |

---

## 5. One-time setup (per deployment)

1. **Apply the migration**:
   ```bash
   /opt/lampp/bin/mysql -u root fleetsimplify < sql/push-migration.sql
   ```
   …or in phpMyAdmin: select database **fleetsimplify** → **Import** → pick the file.

2. **Mint your own VAPID keypair** (the demo one is committed for convenience but anyone reading the repo can spoof pushes from your origin):
   ```bash
   php bin/gen-vapid.php
   ```
   Paste the printed `FS_VAPID_PUBLIC` and `FS_VAPID_PRIVATE` into [config/push.php](../config/push.php).
   Set `FS_VAPID_SUBJECT` to a real `mailto:` your team monitors — Chrome **will** email there if the push service ever decides your origin is abusing the API.

3. **Serve over HTTPS**. The `Push API` and `Service Worker API` only work over `https://` (or `http://localhost` for development). In LAMPP the simplest path is to enable the bundled SSL vhost on `https://localhost/`.

4. **Make sure `sw.js` resolves at the **site root** (`https://example.com/sw.js`).** Service workers can only see pages within their scope, and a SW served from `/sw.js` covers the whole origin. If you ever serve the project from a sub-path (e.g. `localhost/fleet_simplify/`), the SW scope shrinks accordingly — `push.js` already handles both cases by trying `/sw.js` first and falling back to a relative `sw.js`.

That's it. From the mechanic's side they just click **🔔 Enable notifications** on their dashboard once.

---

## 6. End-to-end test

> Use two browser profiles (or one regular + one Incognito) so you can play "user" and "mechanic" simultaneously.

1. **Mechanic (browser A)** — sign in as `steve.karanja@autohub.co.ke` / `Mech@123`. On the dashboard, click **🔔 Enable notifications**. Grant permission. The button should flip to **🔕 Disable notifications** and the status text should turn green.

2. Inspect the database:
   ```sql
   SELECT mechanic_id, LEFT(endpoint,60) AS endpoint, LENGTH(p256dh) AS pubkey_len
   FROM push_subscriptions;
   ```
   You should see one row with `pubkey_len = 87` (base64url length of 65-byte key, no padding).

3. **Driver (browser B)** — sign in as `james.kariuki@example.com` / `User@123`. Go to **Find services**, request a booking from Stephen Karanja's "AutoHub Garage".

4. Within 1–3 seconds, **a system notification should appear on browser A** with the booking number and breakdown cause, regardless of whether the mechanic dashboard tab is currently focused. Closing the tab entirely should not stop it.

5. Click the notification → it opens (or focuses) the mechanic's chat page for that booking.

To watch the wire: open Chrome DevTools → **Application** tab → **Service workers**. The push event log shows every received push and any errors decoding it.

To watch the server-side send, tail the PHP error log (`/opt/lampp/logs/error_log` or wherever your `error_log` writes) while requesting a booking. Successful sends are silent; failures (404, 410, etc.) are logged with `[FleetSimplify] push send failed: status=…`.

---

## 7. The lifecycle of a subscription

| Event | What happens |
|-------|--------------|
| User clicks **Enable** | Browser asks for permission. If granted, `pushManager.subscribe()` runs the handshake with the push service. The browser stores the subscription locally and gives us its three identifiers. We POST them to `/api/push-subscribe.php`, which `INSERT … ON DUPLICATE KEY UPDATE`s into `push_subscriptions`. |
| Server sends a push | `notify_mechanic()` loads every row for that mechanic, encrypts a copy of the payload per row (each browser has its own keys), and POSTs to its endpoint. |
| Push service returns 201 | Delivered. We `UPDATE … SET last_used_at = NOW()`. |
| Push service returns 404 / 410 | The browser uninstalled or revoked. We `DELETE` the row immediately so we never try again. |
| Push service returns 5xx | Transient. Logged via `error_log`; the row stays so the next push attempts again. |
| Mechanic clicks **Disable** | We call `subscription.unsubscribe()` locally, then POST the endpoint to `/api/push-unsubscribe.php` so the row is removed server-side. |
| User clears the browser's site data | The local subscription is gone but the server row lingers until the next failed push, then auto-cleans. |
| You re-mint VAPID keys | **Every** existing subscription becomes invalid — the public key was baked into the handshake. Mechanics must click **Enable** again. The push service will start returning 410 for stale rows; the cleanup is automatic. |

---

## 8. Security notes

* **End-to-end encryption.** The push service can route the message but cannot decrypt it. The browser will silently drop any push it can't decrypt with the keys it generated.
* **VAPID origin binding.** The JWT `aud` claim is the origin of the push endpoint, signed with our private key. A different attacker can't replay our pushes, and we can't accidentally push to subscriptions issued by someone else.
* **CSRF.** Both `/api/push-subscribe.php` and `/api/push-unsubscribe.php` require the same per-session CSRF token as every other POST in the app. The token is rendered into the dashboard's `data-csrf` attribute on the toggle button.
* **Endpoint hashing.** The `endpoint` column is a TEXT (push URLs are too long for MySQL's 767-byte index limit), so we additionally store a `sha256(endpoint)` and put the UNIQUE constraint on that. Identity is preserved without truncation.
* **No payload secrets.** The notification payload is JSON `{ title, body, url, tag }`. Don't put PII or invoice totals in it — the *browser's* push service log may briefly retain encrypted copies, and the OS notification UI displays them on the lock screen. Keep payloads to "you have a new request, click to view" and let the mechanic load full details after they sign in.

---

## 9. Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| **Enable** button is grey-disabled with "Push not supported" | Browser doesn't expose `serviceWorker` / `PushManager`. Most often this is HTTP (must be HTTPS) or an iOS Safari without the PWA installed. | Switch to HTTPS or test on Chrome/Firefox/Edge. On iOS, install the site to Home Screen first. |
| Click **Enable** → "Permission was not granted" | User clicked **Block** in the OS prompt. Browsers cache that decision indefinitely. | In Chrome, click the lock icon next to the URL → **Site settings** → reset Notifications. |
| Subscription is created but no push arrives | (a) PHP cURL extension missing on server. (b) HTTPS cert on the push service rejected (rare). (c) Notifications muted at OS level (Windows Focus Assist, macOS Do Not Disturb). | (a) `apt install php-curl` then restart Apache. (b) Update CA bundle. (c) Check the OS notification centre. |
| Server logs `status=403` on `https://fcm.googleapis.com/...` | VAPID JWT signature invalid — usually a stale private key after rotation. | Re-mint VAPID keys with `php bin/gen-vapid.php`, paste into `config/push.php`, then have mechanics click **Enable** again. |
| Server logs `status=413 Payload too large` | The `aes128gcm` body is bigger than the push service's per-message cap (FCM ≈ 4 KB). | Trim the payload — title + short body + URL is plenty. Don't include full booking JSON. |
| Notification fires twice | Two browsers on the same mechanic account both have active subscriptions — by design. | If undesired, change the schema to `UNIQUE (mechanic_id)` and have new subscriptions clobber old ones. |
| `notificationclick` opens a new tab every time | The URL the SW tries to focus doesn't `pathname`-match any open tab. | Open the chat page once before clicking the notification, or change the matching logic in `sw.js`. |

---

## 10. Adding a second channel later (escalation)

The `notify_mechanic()` helper is the single chokepoint for "tell this mechanic something happened". If you ever want to fall back to SMS when no push subscription is registered (or after N seconds of un-acknowledgement), add it inside that function:

```php
$pushed = /* … existing push loop … */;
if ($pushed === 0) {
    // No browser subscriptions → fall back to SMS.
    notify_mechanic_sms($mechanic_id, $payload['body']);
}
```

Keep the public API the same (`notify_mechanic(int, array)`); upgrade the implementation. Booking-creation code in `api/booking-actions.php` doesn't need to change.

---

## 11. Limitations to keep in mind

* **iOS Safari** delivery is conditional on PWA install + iOS 16.4+.
* The browser may **batch or drop pushes** if the device has been offline for longer than the `TTL` we send (we use 1 hour, which is right for a roadside-assistance alert).
* **Apple's APNs** can throttle pushes if you send too many to the same endpoint in quick succession; the rate limit is generous but worth knowing.
* Browser vendors **regularly tighten** the rules around when notifications can fire silently, when sound is allowed, etc. Test on real Chrome / Firefox builds before counting on a behaviour for production.

If a notification *must* arrive (life-safety scenario), Web Push alone is not sufficient — pair it with SMS or voice.
