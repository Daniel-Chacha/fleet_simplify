<?php
require_once __DIR__ . '/../config/init.php';
require_role('user');
$u = current_user();

$pdo = db();

$st = $pdo->prepare("SELECT
    SUM(status='new')         AS open_new,
    SUM(status='in_progress') AS in_prog,
    SUM(status='completed')   AS completed,
    SUM(status='rejected')    AS rejected
  FROM bookings WHERE user_id = :uid");
$st->execute([':uid' => $u['uid']]);
$counts = $st->fetch() ?: ['open_new'=>0,'in_prog'=>0,'completed'=>0,'rejected'=>0];

$st = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE user_id = :uid AND status = 'pending'");
$st->execute([':uid' => $u['uid']]);
$pending_pay = (int)$st->fetchColumn();

$st = $pdo->prepare("
    SELECT b.id, b.booking_number, b.vehicle_plate, b.breakdown_cause, b.status, b.created_at,
           m.business_name AS mechanic_name
    FROM bookings b
    LEFT JOIN mechanics m ON m.id = b.mechanic_id
    WHERE b.user_id = :uid
    ORDER BY b.created_at DESC
    LIMIT 6
");
$st->execute([':uid' => $u['uid']]);
$recent = $st->fetchAll();

$page_title = 'Driver Dashboard';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-user.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <section class="stats-section">
                <h3 class="stats-h">Your activity <small>At a glance</small></h3>
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-icon">N</div>
                        <div><div class="stat-val"><?= (int)$counts['open_new'] ?></div><div class="stat-label">Open Requests</div></div>
                    </div>
                    <div class="stat-card s2">
                        <div class="stat-icon">P</div>
                        <div><div class="stat-val"><?= (int)$counts['in_prog'] ?></div><div class="stat-label">In Progress</div></div>
                    </div>
                    <div class="stat-card s3">
                        <div class="stat-icon">C</div>
                        <div><div class="stat-val"><?= (int)$counts['completed'] ?></div><div class="stat-label">Completed</div></div>
                    </div>
                    <div class="stat-card s4">
                        <div class="stat-icon">$</div>
                        <div><div class="stat-val"><?= $pending_pay ?></div><div class="stat-label">Pending Payments</div></div>
                    </div>
                </div>
            </section>

            <div class="card">
                <div class="f-between mb-16">
                    <h3 style="margin:0">Recent requests</h3>
                    <a class="btn btn-sm" href="<?= e(url('user/find-services.php')) ?>">Request a service</a>
                </div>
                <?php if (!$recent): ?>
                    <p class="text-muted">No requests yet. Tap "Request a service" to get started.</p>
                <?php else: ?>
                    <div class="table-wrap"><table class="table">
                        <thead><tr><th>Booking</th><th>Vehicle</th><th>Cause</th><th>Mechanic</th><th>Status</th><th>Date</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><?= e($r['booking_number']) ?></td>
                            <td><?= e($r['vehicle_plate']) ?></td>
                            <td><?= e($r['breakdown_cause']) ?></td>
                            <td><?= e($r['mechanic_name'] ?: '—') ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td><?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?></td>
                            <td><a class="btn btn-sm btn-outline" href="<?= e(url('user/chat.php?booking_id=' . (int)$r['id'])) ?>">Open</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
