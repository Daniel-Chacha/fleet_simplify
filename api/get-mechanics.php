<?php
require_once __DIR__ . '/../config/init.php';

$pdo = db();
$for = $_GET['for'] ?? 'list';

try {
    if ($for === 'notifications') {
        require_role('mechanic');
        $u = current_user();
        $st = $pdo->prepare("
            SELECT b.id, b.booking_number, b.vehicle_plate, b.vehicle_type, b.breakdown_cause, b.breakdown_location,
                   b.severity, u.name AS driver_name
            FROM bookings b JOIN users u ON u.id = b.user_id
            WHERE b.mechanic_id = :m AND b.status = 'new'
            ORDER BY b.created_at DESC
        ");
        $st->execute([':m' => $u['uid']]);
        json_response(['ok' => true, 'bookings' => $st->fetchAll()]);
    }

    if ($for === 'location') {
        // User-side: get a specific mechanic's last known location.
        require_role('user');
        $mid = (int)($_GET['mechanic_id'] ?? 0);
        if ($mid <= 0) json_response(['ok' => false, 'error' => 'Invalid mechanic.'], 400);
        // Verify caller has a booking with this mechanic before disclosing location.
        $u = current_user();
        $st = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = :uid AND mechanic_id = :m');
        $st->execute([':uid' => $u['uid'], ':m' => $mid]);
        if ((int)$st->fetchColumn() === 0) json_response(['ok' => false, 'error' => 'Not authorized.'], 403);

        $st = $pdo->prepare('SELECT latitude, longitude, updated_at FROM locations WHERE mechanic_id = :m');
        $st->execute([':m' => $mid]);
        $loc = $st->fetch();
        json_response(['ok' => true, 'location' => $loc ?: null]);
    }

    // Default: list approved mechanics (driver-side directory).
    require_role('user');
    $st = $pdo->query("
        SELECT id, name, business_name, town, address, service_description, availability
        FROM mechanics WHERE status='approved' ORDER BY business_name
    ");
    json_response(['ok' => true, 'mechanics' => $st->fetchAll()]);
} catch (Throwable $ex) {
    error_log('get-mechanics: ' . $ex->getMessage());
    json_response(['ok' => false, 'error' => 'Server error.'], 500);
}
