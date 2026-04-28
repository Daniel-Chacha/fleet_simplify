<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/push.php';
require_role('mechanic');
$u = current_user();
$pdo = db();

$st = $pdo->prepare('SELECT id, name, business_name, status FROM mechanics WHERE id = :id');
$st->execute([':id' => $u['uid']]);
$me = $st->fetch();
if (!$me) redirect('auth/logout.php');
$_SESSION['mech_status'] = $me['status'];

$page_title = 'Mechanic Dashboard';
include __DIR__ . '/../partials/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-mechanic.php'; ?>
    <main class="main">
        <?php $show_bell = ($me['status'] === 'approved'); include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">

        <?php if ($me['status'] !== 'approved'): ?>
            <div class="pending-screen">
                <div class="icon">⏳</div>
                <h2>Awaiting Admin Approval</h2>
                <p class="text-muted">Hi <?= e($me['name']) ?> — your business <strong><?= e($me['business_name']) ?></strong> is pending review by an administrator. You'll receive notifications and bookings once approved.</p>
                <span class="badge badge-warning">PENDING APPROVAL</span>
                <p class="mt-16"><a class="btn btn-outline" href="<?= e(url('mechanic/update-business.php')) ?>">Edit business details</a></p>
            </div>
        <?php else:
            // KPIs
            $st = $pdo->prepare("SELECT
                SUM(status='new')         AS new_cnt,
                SUM(status='in_progress') AS prog_cnt,
                SUM(status='completed')   AS done_cnt
                FROM bookings WHERE mechanic_id = :m");
            $st->execute([':m' => $u['uid']]);
            $k = $st->fetch();
            $st = $pdo->prepare("SELECT ROUND(AVG(rating),1) FROM ratings WHERE mechanic_id = :m");
            $st->execute([':m' => $u['uid']]);
            $avg = $st->fetchColumn() ?: '—';

            // Active jobs
            $st = $pdo->prepare("
                SELECT b.id, b.booking_number, b.vehicle_plate, b.breakdown_cause, b.status, b.created_at, u.name AS user_name, u.mobile
                FROM bookings b JOIN users u ON u.id = b.user_id
                WHERE b.mechanic_id = :m AND b.status IN ('new','in_progress')
                ORDER BY b.created_at DESC
            ");
            $st->execute([':m' => $u['uid']]);
            $active = $st->fetchAll();

            // Existing location (for map centre)
            $st = $pdo->prepare('SELECT latitude, longitude FROM locations WHERE mechanic_id = :m');
            $st->execute([':m' => $u['uid']]);
            $loc = $st->fetch();
            $lat = $loc['latitude'] ?? -1.2921;
            $lng = $loc['longitude'] ?? 36.8219;
        ?>
            <section class="stats-section">
                <h3 class="stats-h">Workshop overview <small>Your assigned bookings</small></h3>
                <div class="stats">
                    <div class="stat-card"><div class="stat-icon">N</div><div><div class="stat-val"><?= (int)($k['new_cnt'] ?? 0) ?></div><div class="stat-label">New requests</div></div></div>
                    <div class="stat-card s2"><div class="stat-icon">P</div><div><div class="stat-val"><?= (int)($k['prog_cnt'] ?? 0) ?></div><div class="stat-label">In progress</div></div></div>
                    <div class="stat-card s3"><div class="stat-icon">C</div><div><div class="stat-val"><?= (int)($k['done_cnt'] ?? 0) ?></div><div class="stat-label">Completed</div></div></div>
                    <div class="stat-card s4"><div class="stat-icon">★</div><div><div class="stat-val"><?= e((string)$avg) ?></div><div class="stat-label">Avg rating</div></div></div>
                </div>
            </section>

            <section class="push-card mb-16">
                <div class="push-card-h">
                    <div>
                        <h3>Browser notifications</h3>
                        <p id="push-status" class="push-status push-status-idle">Click to receive booking alerts even when this tab is closed.</p>
                    </div>
                    <button id="push-toggle"
                            class="btn"
                            type="button"
                            data-public-key="<?= e(FS_VAPID_PUBLIC) ?>"
                            data-subscribe-url="<?= e(url('api/push-subscribe.php')) ?>"
                            data-unsubscribe-url="<?= e(url('api/push-unsubscribe.php')) ?>"
                            data-csrf="<?= e(csrf_token()) ?>">
                        🔔 Enable notifications
                    </button>
                </div>
            </section>

            <div class="find-grid">
                <div>
                    <div class="card mb-16">
                        <div class="f-between mb-8">
                            <h3 style="margin:0">Your location</h3>
                            <button id="loc-btn" class="btn btn-sm">Update My Location</button>
                        </div>
                        <div id="mech-map" class="gps-map"></div>
                    </div>

                    <div class="card">
                        <h3 class="card-h">Active jobs</h3>
                        <?php if (!$active): ?>
                            <p class="text-muted">No active jobs right now. Stay safe out there.</p>
                        <?php else: ?>
                            <div class="table-wrap"><table class="table">
                                <thead><tr><th>#</th><th>Driver</th><th>Vehicle</th><th>Cause</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    <?php foreach ($active as $a): ?>
                                    <tr>
                                        <td><?= e($a['booking_number']) ?></td>
                                        <td><?= e($a['user_name']) ?> · <?= e($a['mobile']) ?></td>
                                        <td><?= e($a['vehicle_plate']) ?></td>
                                        <td><?= e($a['breakdown_cause']) ?></td>
                                        <td><?= status_badge($a['status']) ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline" href="<?= e(url('mechanic/chat.php?booking_id=' . (int)$a['id'])) ?>">Chat</a>
                                            <?php if ($a['status'] === 'in_progress'): ?>
                                                <form method="post" action="<?= e(url('api/booking-actions.php')) ?>" style="display:inline">
                                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="complete">
                                                    <input type="hidden" name="booking_id" value="<?= (int)$a['id'] ?>">
                                                    <button class="btn btn-sm btn-success">Mark complete</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="notif-panel">
                    <h3 style="margin:0 0 10px">Incoming requests</h3>
                    <div id="notif-panel"><div class="notif-empty">Listening for requests…</div></div>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </main>
</div>

<?php
if ($me['status'] === 'approved') {
    $notifCfg = [
        'fetchUrl' => url('api/get-mechanics.php?for=notifications'),
        'actionUrl' => url('api/booking-actions.php'),
        'chatBase' => url('mechanic/chat.php'),
        'csrf' => csrf_token(),
    ];
    $mapCfg = [
        'elId' => 'mech-map', 'lat' => (float)$lat, 'lng' => (float)$lng,
        'btnId' => 'loc-btn',
        'updateUrl' => url('api/update-location.php'), 'csrf' => csrf_token(),
    ];
    $inline_js = 'window.__notif = ' . json_encode($notifCfg) . ';'
               . 'document.addEventListener("DOMContentLoaded", function () { window.fsMechanicMap(' . json_encode($mapCfg) . '); });';
    $extra_js = ['assets/js/notifications.js', 'assets/js/gps-tracking.js', 'assets/js/push.js'];
}
?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
