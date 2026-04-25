<?php
require_once __DIR__ . '/../config/init.php';
require_role('admin');
$u = current_user();
$pdo = db();

$drivers   = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$mechs     = (int)$pdo->query('SELECT COUNT(*) FROM mechanics')->fetchColumn();
$pendingM  = (int)$pdo->query("SELECT COUNT(*) FROM mechanics WHERE status='pending'")->fetchColumn();
$bookings  = (int)$pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();

$st = $pdo->query("SELECT
    SUM(status='new')         AS new_cnt,
    SUM(status='in_progress') AS prog_cnt,
    SUM(status='completed')   AS done_cnt,
    SUM(status='rejected')    AS rej_cnt
    FROM bookings");
$bk = $st->fetch();

$recent = $pdo->query("
    SELECT b.id, b.booking_number, b.vehicle_plate, b.status, b.created_at,
           u.name AS user_name, m.business_name AS mech_name
    FROM bookings b JOIN users u ON u.id = b.user_id
    LEFT JOIN mechanics m ON m.id = b.mechanic_id
    ORDER BY b.created_at DESC LIMIT 8
")->fetchAll();

$page_title = 'Admin Dashboard';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-admin.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <section class="stats-section">
                <h3 class="stats-h">People &amp; activity <small>Counts across all roles &middot; click any card to drill in</small></h3>
                <div class="stats">
                    <a class="stat-card" href="<?= e(url('admin/drivers.php')) ?>" title="View all drivers">
                        <div class="stat-icon">D</div>
                        <div><div class="stat-val"><?= $drivers ?></div><div class="stat-label">Drivers</div></div>
                    </a>
                    <a class="stat-card s2" href="<?= e(url('admin/mechanics.php')) ?>" title="View all mechanics">
                        <div class="stat-icon">M</div>
                        <div><div class="stat-val"><?= $mechs ?></div><div class="stat-label">Mechanics</div></div>
                    </a>
                    <a class="stat-card s4" href="<?= e(url('admin/mechanics.php?tab=pending')) ?>" title="Review pending mechanic approvals">
                        <div class="stat-icon">!</div>
                        <div><div class="stat-val"><?= $pendingM ?></div><div class="stat-label">Pending approvals</div></div>
                    </a>
                    <a class="stat-card s3" href="<?= e(url('admin/bookings.php?tab=all')) ?>" title="View every booking">
                        <div class="stat-icon">B</div>
                        <div><div class="stat-val"><?= $bookings ?></div><div class="stat-label">Total bookings</div></div>
                    </a>
                </div>
            </section>

            <section class="stats-section">
                <h3 class="stats-h">Bookings by status <small>Live pipeline &middot; click any card to filter the bookings table</small></h3>
                <div class="stats">
                    <a class="stat-card" href="<?= e(url('admin/bookings.php?tab=new')) ?>" title="New requests">
                        <div class="stat-icon">N</div>
                        <div><div class="stat-val"><?= (int)$bk['new_cnt'] ?></div><div class="stat-label">New</div></div>
                    </a>
                    <a class="stat-card s2" href="<?= e(url('admin/bookings.php?tab=approved')) ?>" title="In-progress jobs">
                        <div class="stat-icon">P</div>
                        <div><div class="stat-val"><?= (int)$bk['prog_cnt'] ?></div><div class="stat-label">In progress</div></div>
                    </a>
                    <a class="stat-card s3" href="<?= e(url('admin/bookings.php?tab=completed')) ?>" title="Completed jobs">
                        <div class="stat-icon">C</div>
                        <div><div class="stat-val"><?= (int)$bk['done_cnt'] ?></div><div class="stat-label">Completed</div></div>
                    </a>
                    <a class="stat-card s4" href="<?= e(url('admin/bookings.php?tab=rejected')) ?>" title="Rejected requests">
                        <div class="stat-icon">R</div>
                        <div><div class="stat-val"><?= (int)$bk['rej_cnt'] ?></div><div class="stat-label">Rejected</div></div>
                    </a>
                </div>
            </section>

            <div class="card">
                <div class="f-between mb-16">
                    <h3 style="margin:0">Recent bookings</h3>
                    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/bookings.php')) ?>">View all</a>
                </div>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Booking #</th><th>Driver</th><th>Vehicle</th><th>Mechanic</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><?= e($r['booking_number']) ?></td>
                            <td><?= e($r['user_name']) ?></td>
                            <td><?= e($r['vehicle_plate']) ?></td>
                            <td><?= e($r['mech_name'] ?: '—') ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td><?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
