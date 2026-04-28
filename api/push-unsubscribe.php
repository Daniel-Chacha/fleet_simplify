<?php
require_once __DIR__ . '/../config/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'error' => 'POST only.'], 405);
verify_csrf($_POST['csrf'] ?? '');
require_role('mechanic');

$u = current_user();
$endpoint = trim($_POST['endpoint'] ?? '');
if ($endpoint === '') json_response(['ok' => false, 'error' => 'Missing endpoint.'], 400);

try {
    $st = db()->prepare('DELETE FROM push_subscriptions WHERE mechanic_id = :m AND endpoint_hash = :h');
    $st->execute([':m' => $u['uid'], ':h' => hash('sha256', $endpoint)]);
    json_response(['ok' => true, 'deleted' => $st->rowCount()]);
} catch (Throwable $ex) {
    error_log('push-unsubscribe: ' . $ex->getMessage());
    json_response(['ok' => false, 'error' => 'Server error.'], 500);
}
