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

$page_title = 'Chat — ' . $booking['booking_number'];
include __DIR__ . '/../partials/header.php';
?>
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
$inline_js = 'window.__chat = ' . json_encode($cfg) . ';';
$extra_js = ['assets/js/chat.js'];
include __DIR__ . '/../partials/footer.php';
?>
