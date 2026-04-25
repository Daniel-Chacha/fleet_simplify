<?php
require_once __DIR__ . '/../config/init.php';
require_role('mechanic');
$u = current_user();
$pdo = db();

$st = $pdo->prepare('SELECT status FROM mechanics WHERE id = :id');
$st->execute([':id' => $u['uid']]);
$status = $st->fetchColumn();

$cnt = $pdo->prepare('SELECT COUNT(*) FROM ratings WHERE mechanic_id = :m');
$cnt->execute([':m' => $u['uid']]);
$total = (int)$cnt->fetchColumn();
$pg = paginate($total);

$st = $pdo->prepare("
    SELECT r.rating, r.comment, r.created_at, b.booking_number, u.name AS user_name
    FROM ratings r
    JOIN bookings b ON b.id = r.booking_id
    JOIN users u ON u.id = r.user_id
    WHERE r.mechanic_id = :m
    ORDER BY r.created_at DESC
    LIMIT :lim OFFSET :off
");
$st->bindValue(':m',   $u['uid'],     PDO::PARAM_INT);
$st->bindValue(':lim', $pg['per'],    PDO::PARAM_INT);
$st->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
$st->execute();
$ratings = $st->fetchAll();

$st = $pdo->prepare('SELECT ROUND(AVG(rating),2) FROM ratings WHERE mechanic_id = :m');
$st->execute([':m' => $u['uid']]);
$avg = $st->fetchColumn();

// Distribution (across ALL ratings, not just the current page)
$dist = [1=>0,2=>0,3=>0,4=>0,5=>0];
$st = $pdo->prepare('SELECT rating, COUNT(*) AS c FROM ratings WHERE mechanic_id = :m GROUP BY rating');
$st->execute([':m' => $u['uid']]);
foreach ($st as $r) $dist[(int)$r['rating']] = (int)$r['c'];

$page_title = 'Customer Feedback';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-mechanic.php'; ?>
    <main class="main">
        <?php $show_bell = ($status === 'approved'); include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h"><h2 style="margin:0">Customer feedback</h2></div>

            <div class="stats">
                <div class="stat-card"><div class="stat-icon">★</div><div><div class="stat-val"><?= e($avg ?: '—') ?></div><div class="stat-label">Average rating</div></div></div>
                <div class="stat-card s2"><div class="stat-icon">N</div><div><div class="stat-val"><?= $total ?></div><div class="stat-label">Total ratings</div></div></div>
                <?php for ($i = 5; $i >= 4; $i--): ?>
                <div class="stat-card s3"><div class="stat-icon"><?= $i ?>★</div><div><div class="stat-val"><?= $dist[$i] ?></div><div class="stat-label"><?= $i ?>-star reviews</div></div></div>
                <?php endfor; ?>
            </div>

            <div class="card">
                <h3 class="card-h">All reviews</h3>
                <?php if (!$ratings): ?>
                    <p class="text-muted">No ratings yet.</p>
                <?php else: ?>
                    <div class="table-wrap"><table class="table">
                        <thead><tr><th>Date</th><th>Booking</th><th>Driver</th><th>Stars</th><th>Comment</th></tr></thead>
                        <tbody>
                        <?php foreach ($ratings as $r): ?>
                            <tr>
                                <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
                                <td><?= e($r['booking_number']) ?></td>
                                <td><?= e($r['user_name']) ?></td>
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
