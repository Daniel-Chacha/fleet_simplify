<?php
require_once __DIR__ . '/../config/init.php';

$u = require_any_role();
if (!in_array($u['role'], ['user', 'mechanic'], true)) {
    json_response(['ok' => false, 'error' => 'Not authorized.'], 403);
}

$bid = (int)($_GET['booking_id'] ?? 0);
$since = (int)($_GET['since_id'] ?? 0);
if ($bid <= 0) json_response(['ok' => false, 'error' => 'Missing booking_id.'], 400);

$pdo = db();
try {
    // Verify caller belongs to the booking.
    $col = $u['role'] === 'user' ? 'user_id' : 'mechanic_id';
    $st = $pdo->prepare("SELECT id FROM bookings WHERE id = :id AND $col = :uid LIMIT 1");
    $st->execute([':id' => $bid, ':uid' => $u['uid']]);
    if (!$st->fetchColumn()) json_response(['ok' => false, 'error' => 'Not authorized.'], 403);

    $st = $pdo->prepare('SELECT id, sender_type, sender_id, message, sent_at FROM messages
                         WHERE booking_id = :b AND id > :since ORDER BY id ASC');
    $st->execute([':b' => $bid, ':since' => $since]);
    $msgs = $st->fetchAll();
    // Format sent_at for display
    foreach ($msgs as &$m) $m['sent_at'] = date('d M H:i', strtotime($m['sent_at']));
    json_response(['ok' => true, 'messages' => $msgs]);
} catch (Throwable $ex) {
    error_log('get-messages: ' . $ex->getMessage());
    json_response(['ok' => false, 'error' => 'Server error.'], 500);
}
