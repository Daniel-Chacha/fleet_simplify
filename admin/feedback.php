<?php
require_once __DIR__ . '/../config/init.php';
require_role('admin');
$pdo = db();

$total = (int)$pdo->query('SELECT COUNT(*) FROM ratings')->fetchColumn();
$pg = paginate($total);

$st = $pdo->prepare("
    SELECT r.rating, r.comment, r.created_at, b.booking_number, u.name AS driver, m.business_name AS mechanic
    FROM ratings r
    JOIN bookings b ON b.id = r.booking_id
    JOIN users u    ON u.id = r.user_id
    JOIN mechanics m ON m.id = r.mechanic_id
    ORDER BY r.created_at DESC
    LIMIT :lim OFFSET :off
");
$st->bindValue(':lim', $pg['per'],    PDO::PARAM_INT);
$st->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

$page_title = 'Feedback';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-admin.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h"><h2 style="margin:0">All driver feedback</h2></div>
            <div class="card">
                <?php if (!$rows): ?>
                    <p class="text-muted">No feedback yet.</p>
                <?php else: ?>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Date</th><th>Booking</th><th>Driver</th><th>Mechanic</th><th>Stars</th><th>Comment</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
                            <td><?= e($r['booking_number']) ?></td>
                            <td><?= e($r['driver']) ?></td>
                            <td><?= e($r['mechanic']) ?></td>
                            <td><span class="stars-ro"><?= str_repeat('★', (int)$r['rating']) . '<span class="off">' . str_repeat('★', 5 - (int)$r['rating']) . '</span>' ?></span></td>
                            <td><?= e($r['comment']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                <?php $base_qs = []; include __DIR__ . '/../partials/pager.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
