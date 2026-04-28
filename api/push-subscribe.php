<?php
require_once __DIR__ . '/../config/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'error' => 'POST only.'], 405);
verify_csrf($_POST['csrf'] ?? '');
require_role('mechanic');

$u = current_user();
$endpoint = trim($_POST['endpoint'] ?? '');
$p256dh   = trim($_POST['p256dh']   ?? '');
$auth     = trim($_POST['auth']     ?? '');
$ua       = substr(trim($_POST['user_agent'] ?? ''), 0, 250);

if ($endpoint === '' || $p256dh === '' || $auth === '') {
    json_response(['ok' => false, 'error' => 'Missing fields.'], 400);
}
// Reject anything that doesn't look like a real push-service URL.
if (!preg_match('#^https://#', $endpoint)) {
    json_response(['ok' => false, 'error' => 'Invalid endpoint.'], 400);
}
if (strlen($endpoint) > 2000) {
    json_response(['ok' => false, 'error' => 'Endpoint too long.'], 400);
}

$endpointHash = hash('sha256', $endpoint);

try {
    $st = db()->prepare(
        'INSERT INTO push_subscriptions (mechanic_id, endpoint, endpoint_hash, p256dh, auth_secret, user_agent)
         VALUES (:m, :e, :h, :p, :a, :ua)
         ON DUPLICATE KEY UPDATE
            mechanic_id = VALUES(mechanic_id),
            p256dh      = VALUES(p256dh),
            auth_secret = VALUES(auth_secret),
            user_agent  = VALUES(user_agent),
            last_used_at = NULL'
    );
    $st->execute([
        ':m'  => $u['uid'],
        ':e'  => $endpoint,
        ':h'  => $endpointHash,
        ':p'  => $p256dh,
        ':a'  => $auth,
        ':ua' => $ua,
    ]);
    json_response(['ok' => true]);
} catch (Throwable $ex) {
    error_log('push-subscribe: ' . $ex->getMessage());
    json_response(['ok' => false, 'error' => 'Server error.'], 500);
}
