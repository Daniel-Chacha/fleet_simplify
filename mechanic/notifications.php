<?php
require_once __DIR__ . '/../config/init.php';
require_role('mechanic');
$u = current_user();
$pdo = db();

$st = $pdo->prepare('SELECT status FROM mechanics WHERE id = :id');
$st->execute([':id' => $u['uid']]);
$status = $st->fetchColumn();

$page_title = 'Notifications';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-mechanic.php'; ?>
    <main class="main">
        <?php $show_bell = ($status === 'approved'); include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h"><h2 style="margin:0">Incoming requests</h2></div>

            <?php if ($status !== 'approved'): ?>
                <div class="alert alert-warning"><strong>Pending approval.</strong> You'll start seeing requests once an admin approves your business.</div>
            <?php else: ?>
                <div class="notif-panel" id="notif-panel"><div class="notif-empty">Listening for requests…</div></div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php
if ($status === 'approved') {
    $notifCfg = [
        'fetchUrl' => url('api/get-mechanics.php?for=notifications'),
        'actionUrl' => url('api/booking-actions.php'),
        'chatBase' => url('mechanic/chat.php'),
        'csrf' => csrf_token(),
    ];
    $inline_js = 'window.__notif = ' . json_encode($notifCfg) . ';';
    $extra_js = ['assets/js/notifications.js'];
}
include __DIR__ . '/../partials/footer.php';
?>
