<?php
/**
 * FleetSimplify VBMS — Web Push configuration & helpers.
 *
 * Replace the keys below with your own (run `php bin/gen-vapid.php` to mint
 * a fresh pair). The keys here are for *demo* use — anyone with the private
 * key can send pushes that browsers will trust as coming from your origin.
 */

require_once __DIR__ . '/../lib/web-push.php';

// === DEMO VAPID KEYPAIR ===
// Public key is sent to the browser at subscription time. Private key signs
// each outbound push (never leaves the server). Generated with
//   openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC])
// and base64url-encoded with no padding.
const FS_VAPID_PUBLIC  = 'BJ61aoy2njqt9QJk5cgAzxZbgfPLSTXU6yGwu-449AxRqfQBNUAFOksnS8Ng6bX0M2LCfvc75Qrr2kh388TN6dY';
const FS_VAPID_PRIVATE = 'ml692RaoRbvXBSMc80A_ITSCy5DxqKyieNpTiSyB6sU';

// VAPID `sub` claim — Chrome/Edge require either a mailto: URL or an https:
// URL the push service can use to contact a real human if your server starts
// abusing the API.
const FS_VAPID_SUBJECT = 'mailto:nyansapolabs.dev@gmail.com';

/**
 * Returns a memoised WebPush instance.
 */
function push_client(): \FleetSimplify\WebPush\WebPush
{
    static $client = null;
    if ($client === null) {
        $client = new \FleetSimplify\WebPush\WebPush(FS_VAPID_PUBLIC, FS_VAPID_PRIVATE, FS_VAPID_SUBJECT);
    }
    return $client;
}

/**
 * Send `$payload` (will be JSON-encoded) to every push subscription registered
 * for `$mechanic_id`. Stale subscriptions (HTTP 404 / 410) are auto-deleted.
 *
 * Returns the number of successful sends.
 */
function notify_mechanic(int $mechanic_id, array $payload): int
{
    $pdo = db();
    $st = $pdo->prepare('SELECT id, endpoint, p256dh, auth_secret FROM push_subscriptions WHERE mechanic_id = :m');
    $st->execute([':m' => $mechanic_id]);
    $rows = $st->fetchAll();
    if (!$rows) return 0;

    $client  = push_client();
    $body    = json_encode($payload);
    $sent    = 0;
    $delIds  = [];
    $touchIds = [];

    foreach ($rows as $row) {
        $r = $client->send([
            'endpoint' => $row['endpoint'],
            'p256dh'   => $row['p256dh'],
            'auth'     => $row['auth_secret'],
        ], $body, 60 * 60);  // 1-hour TTL — booking alerts are time-sensitive

        if ($r['ok']) {
            $sent++;
            $touchIds[] = (int)$row['id'];
        } elseif ($r['status'] === 404 || $r['status'] === 410) {
            // Browser revoked or unsubscribed — drop the row.
            $delIds[] = (int)$row['id'];
        } else {
            // Transient failure (network / 5xx). Log and move on.
            error_log('[FleetSimplify] push send failed: status=' . $r['status'] . ' err=' . ($r['error'] ?? ''));
        }
    }

    if ($delIds) {
        $in = implode(',', array_fill(0, count($delIds), '?'));
        $pdo->prepare("DELETE FROM push_subscriptions WHERE id IN ($in)")->execute($delIds);
    }
    if ($touchIds) {
        $in = implode(',', array_fill(0, count($touchIds), '?'));
        $pdo->prepare("UPDATE push_subscriptions SET last_used_at = NOW() WHERE id IN ($in)")->execute($touchIds);
    }
    return $sent;
}
