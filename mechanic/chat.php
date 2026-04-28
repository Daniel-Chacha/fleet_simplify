<?php
require_once __DIR__ . '/../config/init.php';
require_role('mechanic');
$u = current_user();
$pdo = db();

$bid = (int)($_GET['booking_id'] ?? 0);
if ($bid <= 0) redirect('mechanic/dashboard.php');

$st = $pdo->prepare("
    SELECT b.*, u.name AS user_name, u.mobile AS user_mobile
    FROM bookings b JOIN users u ON u.id = b.user_id
    WHERE b.id = :id AND b.mechanic_id = :m LIMIT 1
");
$st->execute([':id' => $bid, ':m' => $u['uid']]);
$booking = $st->fetch();
if (!$booking) redirect('mechanic/dashboard.php');

$st = $pdo->prepare('SELECT id, sender_type, sender_id, message, sent_at FROM messages WHERE booking_id = :b ORDER BY id ASC');
$st->execute([':b' => $bid]);
$messages = $st->fetchAll();
$last_id = $messages ? (int)end($messages)['id'] : 0;

// Mechanic's last known location (for the map). Falls back to Nairobi CBD if none on file.
$st = $pdo->prepare('SELECT latitude, longitude FROM locations WHERE mechanic_id = :m');
$st->execute([':m' => $u['uid']]);
$mechLoc = $st->fetch();
$mechLat = $mechLoc ? (float)$mechLoc['latitude']  : -1.2921;
$mechLng = $mechLoc ? (float)$mechLoc['longitude'] : 36.8219;

$page_title = 'Chat — ' . $booking['booking_number'];
include __DIR__ . '/../partials/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-mechanic.php'; ?>
    <main class="main">
        <?php $show_bell = true; include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h">
                <div>
                    <h2 style="margin:0">Booking <?= e($booking['booking_number']) ?></h2>
                    <small class="text-muted"><?= e($booking['vehicle_plate']) ?> · <?= e($booking['breakdown_cause']) ?> · <?= status_badge($booking['status']) ?></small>
                </div>
                <div class="flex gap-8">
                    <a class="btn btn-outline btn-sm" href="<?= e(url('mechanic/dashboard.php')) ?>">← Dashboard</a>
                    <?php if ($booking['status'] === 'in_progress'): ?>
                        <form method="post" action="<?= e(url('api/booking-actions.php')) ?>">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="complete">
                            <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
                            <button class="btn btn-success btn-sm">Mark complete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-16">
                <strong>Driver:</strong> <?= e($booking['user_name']) ?> · <?= e($booking['user_mobile']) ?>
                · <strong>Location:</strong> <?= e($booking['breakdown_location']) ?>
                · <strong>Severity:</strong> <?= e($booking['severity']) ?>
            </div>

            <?php if ($booking['driver_lat'] && $booking['driver_lng']): ?>
            <div class="gps-panel">
                <div class="f-between mb-8">
                    <strong>Driver location — <?= e($booking['user_name']) ?></strong>
                    <span class="flex gap-8" style="align-items:center">
                        <span id="eta-pill" class="eta-pill">Computing ETA…</span>
                        <button id="loc-btn" type="button" class="btn btn-sm btn-outline">Update my location</button>
                    </span>
                </div>
                <div id="track-map" class="gps-map"></div>
            </div>
            <?php else: ?>
            <div class="card mb-16 text-muted">
                Driver did not share GPS coordinates with this booking — map unavailable.
            </div>
            <?php endif; ?>

            <div class="chat-shell">
                <div class="chat-h"><div><h3>Conversation</h3><small>with <?= e($booking['user_name']) ?></small></div></div>
                <div class="chat-body" id="chat-body">
                    <?php foreach ($messages as $m): $mine = $m['sender_type'] === 'mechanic'; ?>
                        <div class="bubble <?= $mine ? 'sent' : 'received' ?>">
                            <div><?= e($m['message']) ?></div>
                            <small><?= e(date('d M H:i', strtotime($m['sent_at']))) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form id="chat-form" class="chat-form" autocomplete="off">
                    <input type="text" id="chat-input" placeholder="Type a message…" required>
                    <button type="submit" class="btn">Send</button>
                </form>
            </div>
        </div>
    </main>
</div>
<?php
$cfg = [
    'bookingId' => $bid,
    'getUrl'    => url('api/get-messages.php'),
    'sendUrl'   => url('api/send-message.php'),
    'csrf'      => csrf_token(),
    'role'      => 'mechanic',
    'lastId'    => $last_id,
];
$cfgJson = json_encode($cfg);

$track_js = '';
if ($booking['driver_lat'] && $booking['driver_lng']) {
    $track_cfg = [
        'elId'      => 'track-map',
        'driverLat' => (float)$booking['driver_lat'],
        'driverLng' => (float)$booking['driver_lng'],
        'mechLat'   => $mechLat,
        'mechLng'   => $mechLng,
        'btnId'     => 'loc-btn',
        'etaId'     => 'eta-pill',
        'updateUrl' => url('api/update-location.php'),
        'csrf'      => csrf_token(),
    ];
    $track_js = 'document.addEventListener("DOMContentLoaded", function () { window.fsTrackDriver(' . json_encode($track_cfg) . '); });';
}

$inline_js = "window.__chat = $cfgJson; $track_js";
$extra_js = ['assets/js/chat.js', 'assets/js/gps-tracking.js'];
?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
