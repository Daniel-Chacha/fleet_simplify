<?php
require_once __DIR__ . '/../config/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'error' => 'POST only.'], 405);
verify_csrf($_POST['csrf'] ?? '');

$u = require_any_role();
if (!in_array($u['role'], ['user', 'mechanic'], true)) {
    json_response(['ok' => false, 'error' => 'Not authorized.'], 403);
}

$bid = (int)($_POST['booking_id'] ?? 0);
$msg = trim($_POST['message'] ?? '');
if ($bid <= 0 || $msg === '') json_response(['ok' => false, 'error' => 'Missing fields.'], 400);
if (mb_strlen($msg) > 2000) json_response(['ok' => false, 'error' => 'Message too long.'], 400);

$pdo = db();
try {
    // Verify caller belongs to the booking.
    $col = $u['role'] === 'user' ? 'user_id' : 'mechanic_id';
    $st = $pdo->prepare("SELECT id FROM bookings WHERE id = :id AND $col = :uid LIMIT 1");
    $st->execute([':id' => $bid, ':uid' => $u['uid']]);
    if (!$st->fetchColumn()) json_response(['ok' => false, 'error' => 'Not authorized.'], 403);

    $st = $pdo->prepare('INSERT INTO messages (booking_id, sender_type, sender_id, message) VALUES (:b, :st, :sid, :msg)');
    $st->execute([':b' => $bid, ':st' => $u['role'], ':sid' => $u['uid'], ':msg' => $msg]);
    json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $ex) {
    error_log('send-message: ' . $ex->getMessage());
    json_response(['ok' => false, 'error' => 'Server error.'], 500);
}
