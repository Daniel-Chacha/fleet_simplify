<?php
require_once __DIR__ . '/../config/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'error' => 'POST only.'], 405);
verify_csrf($_POST['csrf'] ?? '');
require_role('admin');

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$next = ($action === 'approve') ? 'approved' : (($action === 'reject') ? 'pending' : null);
if ($id <= 0 || !$next) json_response(['ok' => false, 'error' => 'Invalid action.'], 400);

try {
    $st = db()->prepare('UPDATE mechanics SET status = :s WHERE id = :id');
    $st->execute([':s' => $next, ':id' => $id]);
    json_response(['ok' => true]);
} catch (Throwable $ex) {
    error_log('approve-mechanic: ' . $ex->getMessage());
    json_response(['ok' => false, 'error' => 'Server error.'], 500);
}
