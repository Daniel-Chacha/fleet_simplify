<?php
require_once __DIR__ . '/../config/init.php';
require_role('user');
$u = current_user();
$pdo = db();

$bid = (int)($_GET['booking_id'] ?? 0);
if ($bid <= 0) redirect('user/my-requests.php');

$st = $pdo->prepare("
    SELECT b.*, m.business_name AS mechanic_name, m.id AS m_id
    FROM bookings b LEFT JOIN mechanics m ON m.id = b.mechanic_id
    WHERE b.id = :id AND b.user_id = :uid LIMIT 1
");
$st->execute([':id' => $bid, ':uid' => $u['uid']]);
$booking = $st->fetch();
if (!$booking) redirect('user/my-requests.php');

$st = $pdo->prepare('SELECT id, sender_type, sender_id, message, sent_at FROM messages WHERE booking_id = :b ORDER BY id ASC');
$st->execute([':b' => $bid]);
$messages = $st->fetchAll();
$last_id = $messages ? (int)end($messages)['id'] : 0;

// Mechanic location for ETA panel
$loc = null;
if ($booking['mechanic_id']) {
    $st = $pdo->prepare('SELECT latitude, longitude FROM locations WHERE mechanic_id = :m');
    $st->execute([':m' => $booking['mechanic_id']]);
    $loc = $st->fetch();
}

$page_title = 'Chat — ' . $booking['booking_number'];
include __DIR__ . '/../partials/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-user.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h">
                <div>
                    <h2 style="margin:0">Booking <?= e($booking['booking_number']) ?></h2>
                    <small class="text-muted"><?= e($booking['vehicle_plate']) ?> · <?= e($booking['breakdown_cause']) ?> · <?= status_badge($booking['status']) ?></small>
                </div>
                <a class="btn btn-outline btn-sm" href="<?= e(url('user/my-requests.php')) ?>">← All requests</a>
            </div>

            <?php if ($booking['mechanic_id']): ?>
            <div class="gps-panel">
                <div class="f-between mb-8">
                    <strong>Mechanic en-route — <?= e($booking['mechanic_name']) ?></strong>
                    <span id="eta-pill" class="eta-pill">Locating…</span>
                </div>
                <div id="track-map" class="gps-map"></div>
            </div>
            <?php endif; ?>

            <div class="chat-shell">
                <div class="chat-h">
                    <div>
                        <h3>Conversation</h3>
                        <small><?= e($booking['mechanic_name'] ?: 'Awaiting assignment') ?></small>
                    </div>
                </div>
                <div class="chat-body" id="chat-body">
                    <?php foreach ($messages as $m): $mine = $m['sender_type'] === 'user'; ?>
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
    'role'      => 'user',
    'lastId'    => $last_id,
];
$cfgJson = json_encode($cfg);

$track_js = '';
if ($booking['mechanic_id'] && $booking['driver_lat'] && $booking['driver_lng']) {
    $track_cfg = [
        'elId'       => 'track-map',
        'driverLat'  => (float)$booking['driver_lat'],
        'driverLng'  => (float)$booking['driver_lng'],
        'fetchUrl'   => url('api/get-mechanics.php?for=location&mechanic_id=' . (int)$booking['mechanic_id']),
        'etaId'      => 'eta-pill',
    ];
    $track_js = 'document.addEventListener("DOMContentLoaded", function () { window.fsTrackMechanic(' . json_encode($track_cfg) . '); });';
}

$inline_js = "window.__chat = $cfgJson; $track_js";
$extra_js = ['assets/js/chat.js', 'assets/js/gps-tracking.js'];
?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
