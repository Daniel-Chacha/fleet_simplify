<?php
require_once __DIR__ . '/../config/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false, 'error' => 'POST only.'], 405);
verify_csrf($_POST['csrf'] ?? '');
require_role('mechanic');

$u = current_user();
$lat = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
$lng = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;
if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    json_response(['ok' => false, 'error' => 'Invalid coordinates.'], 400);
}

try {
    $pdo = db();
    $st = $pdo->prepare('
        INSERT INTO locations (mechanic_id, latitude, longitude)
        VALUES (:m, :lat, :lng)
        ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude)
    ');
    $st->execute([':m' => $u['uid'], ':lat' => $lat, ':lng' => $lng]);
    json_response(['ok' => true]);
} catch (Throwable $ex) {
    error_log('update-location: ' . $ex->getMessage());
    json_response(['ok' => false, 'error' => 'Server error.'], 500);
}
