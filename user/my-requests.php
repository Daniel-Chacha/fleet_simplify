<?php
require_once __DIR__ . '/../config/init.php';
require_role('user');
$u = current_user();
$pdo = db();

$cnt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = :uid');
$cnt->execute([':uid' => $u['uid']]);
$total = (int)$cnt->fetchColumn();
$pg = paginate($total);

$st = $pdo->prepare("
    SELECT b.*, m.business_name AS mechanic_name,
        (SELECT 1 FROM ratings r WHERE r.booking_id = b.id) AS rated
    FROM bookings b
    LEFT JOIN mechanics m ON m.id = b.mechanic_id
    WHERE b.user_id = :uid
    ORDER BY b.created_at DESC
    LIMIT :lim OFFSET :off
");
$st->bindValue(':uid', $u['uid'],     PDO::PARAM_INT);
$st->bindValue(':lim', $pg['per'],    PDO::PARAM_INT);
$st->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

$page_title = 'My Requests';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-user.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h"><h2 style="margin:0">All your requests</h2></div>

            <div class="card">
                <?php if (!$rows): ?>
                    <p class="text-muted">You haven't requested a service yet. <a href="<?= e(url('user/find-services.php')) ?>">Find a mechanic →</a></p>
                <?php else: ?>
                    <div class="table-wrap"><table class="table">
                        <thead><tr>
                            <th>Booking #</th><th>Vehicle</th><th>Cause</th><th>Severity</th>
                            <th>Mechanic</th><th>Status</th><th>Amount</th><th>Date</th><th></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= e($r['booking_number']) ?></td>
                                <td><?= e($r['vehicle_plate']) ?> <small class="text-muted">· <?= e($r['vehicle_type']) ?></small></td>
                                <td><?= e($r['breakdown_cause']) ?></td>
                                <td><?= e($r['severity']) ?></td>
                                <td><?= e($r['mechanic_name'] ?: '—') ?></td>
                                <td><?= status_badge($r['status']) ?></td>
                                <td><?= e(fmt_kes((float)$r['amount'])) ?></td>
                                <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline" href="<?= e(url('user/chat.php?booking_id=' . (int)$r['id'])) ?>">Chat</a>
                                    <?php if ($r['status'] === 'completed' && !$r['rated']): ?>
                                        <button class="btn btn-sm" onclick="openRate(<?= (int)$r['id'] ?>, <?= (int)$r['mechanic_id'] ?>)">Rate</button>
                                    <?php endif; ?>
                                </td>
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

<!-- Rate modal -->
<div class="modal-backdrop" id="rate-modal">
    <div class="modal">
        <div class="modal-h">
            <h3>Rate this service</h3>
            <button class="modal-x" data-modal-close>×</button>
        </div>
        <form method="post" action="<?= e(url('api/submit-rating.php')) ?>">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="booking_id" id="rate-bid">
            <input type="hidden" name="mechanic_id" id="rate-mid">
            <div class="form-row">
                <label>Stars</label>
                <div class="star-input">
                    <input type="radio" name="rating" id="s5" value="5"><label for="s5">★</label>
                    <input type="radio" name="rating" id="s4" value="4"><label for="s4">★</label>
                    <input type="radio" name="rating" id="s3" value="3" checked><label for="s3">★</label>
                    <input type="radio" name="rating" id="s2" value="2"><label for="s2">★</label>
                    <input type="radio" name="rating" id="s1" value="1"><label for="s1">★</label>
                </div>
            </div>
            <div class="form-row">
                <label>Repair time (minutes)</label>
                <input type="number" name="repair_time_minutes" min="0" max="2000" placeholder="e.g. 60">
            </div>
            <div class="form-row">
                <label>Comment (optional)</label>
                <textarea name="comment" maxlength="500" placeholder="How did it go?"></textarea>
            </div>
            <div class="f-between">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn">Submit rating</button>
            </div>
        </form>
    </div>
</div>

<?php
$inline_js = <<<JS
window.openRate = function (bid, mid) {
    document.getElementById('rate-bid').value = bid;
    document.getElementById('rate-mid').value = mid;
    openModal('rate-modal');
};
JS;
include __DIR__ . '/../partials/footer.php';
?>
