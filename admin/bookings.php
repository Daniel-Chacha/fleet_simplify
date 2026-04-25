<?php
require_once __DIR__ . '/../config/init.php';
require_role('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf'] ?? '');
    $action = $_POST['action'] ?? '';
    $bid = (int)($_POST['id'] ?? 0);
    if ($action === 'assign') {
        $mid = (int)$_POST['mechanic_id'];
        if ($mid > 0) {
            $pdo->prepare('UPDATE bookings SET mechanic_id = :m WHERE id = :id')
                ->execute([':m' => $mid, ':id' => $bid]);
            flash('success', 'Mechanic assigned.');
        }
    } elseif ($action === 'status') {
        $st = $_POST['status'] ?? '';
        if (in_array($st, ['new','in_progress','completed','rejected'], true)) {
            $pdo->prepare('UPDATE bookings SET status = :s WHERE id = :id')
                ->execute([':s' => $st, ':id' => $bid]);
            flash('success', 'Booking status updated.');
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM bookings WHERE id = :id')->execute([':id' => $bid]);
        flash('success', 'Booking deleted.');
    }
    redirect('admin/bookings.php?tab=' . urlencode($_POST['tab'] ?? 'new'));
}

$tab = $_GET['tab'] ?? 'new';
$tab_status_map = ['new'=>'new','approved'=>'in_progress','rejected'=>'rejected','completed'=>'completed'];
if ($tab !== 'all' && !isset($tab_status_map[$tab])) $tab = 'new';

// Per-tab counts (for badges next to tab names)
$counts = ['new'=>0,'approved'=>0,'rejected'=>0,'completed'=>0,'all'=>0];
foreach ($pdo->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status") as $r) {
    if ($r['status'] === 'in_progress')      $counts['approved']  = (int)$r['c'];
    elseif (isset($counts[$r['status']]))    $counts[$r['status']] = (int)$r['c'];
}
$counts['all'] = $counts['new'] + $counts['approved'] + $counts['completed'] + $counts['rejected'];

$total = $counts[$tab];
$pg = paginate($total);

if ($tab === 'all') {
    $st = $pdo->prepare("
        SELECT b.*, u.name AS user_name, u.email, u.mobile, m.business_name AS mech_name
        FROM bookings b
        JOIN users u ON u.id = b.user_id
        LEFT JOIN mechanics m ON m.id = b.mechanic_id
        ORDER BY b.created_at DESC
        LIMIT :lim OFFSET :off
    ");
} else {
    $status = $tab_status_map[$tab];
    $st = $pdo->prepare("
        SELECT b.*, u.name AS user_name, u.email, u.mobile, m.business_name AS mech_name
        FROM bookings b
        JOIN users u ON u.id = b.user_id
        LEFT JOIN mechanics m ON m.id = b.mechanic_id
        WHERE b.status = :s
        ORDER BY b.created_at DESC
        LIMIT :lim OFFSET :off
    ");
    $st->bindValue(':s', $status);
}
$st->bindValue(':lim', $pg['per'],    PDO::PARAM_INT);
$st->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

$mechs = $pdo->query("SELECT id, business_name FROM mechanics WHERE status='approved' ORDER BY business_name")->fetchAll();

$page_title = 'Bookings';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-admin.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h"><h2 style="margin:0">Bookings</h2></div>
            <div class="tabs">
                <a href="?tab=all&amp;per=<?= (int)$pg['per'] ?>"        class="<?= $tab==='all'?'active':'' ?>">All <span class="text-muted">(<?= $counts['all'] ?>)</span></a>
                <a href="?tab=new&amp;per=<?= (int)$pg['per'] ?>"        class="<?= $tab==='new'?'active':'' ?>">New Requests <span class="text-muted">(<?= $counts['new'] ?>)</span></a>
                <a href="?tab=approved&amp;per=<?= (int)$pg['per'] ?>"   class="<?= $tab==='approved'?'active':'' ?>">In Progress <span class="text-muted">(<?= $counts['approved'] ?>)</span></a>
                <a href="?tab=rejected&amp;per=<?= (int)$pg['per'] ?>"   class="<?= $tab==='rejected'?'active':'' ?>">Rejected <span class="text-muted">(<?= $counts['rejected'] ?>)</span></a>
                <a href="?tab=completed&amp;per=<?= (int)$pg['per'] ?>"  class="<?= $tab==='completed'?'active':'' ?>">Completed <span class="text-muted">(<?= $counts['completed'] ?>)</span></a>
            </div>

            <div class="card">
                <?php if (!$rows): ?>
                    <p class="text-muted">Nothing here.</p>
                <?php else: ?>
                <div class="table-wrap"><table class="table">
                    <thead><tr>
                        <th>#</th><th>Booking #</th><th>Driver</th><th>Mobile</th><th>Email</th>
                        <th>Vehicle</th><th>Date</th><th>Mechanic</th><th>Status</th><th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($r['booking_number']) ?></td>
                            <td><?= e($r['user_name']) ?></td>
                            <td><?= e($r['mobile']) ?></td>
                            <td><?= e($r['email']) ?></td>
                            <td><?= e($r['vehicle_plate']) ?> <small class="text-muted">· <?= e($r['vehicle_type']) ?></small></td>
                            <td><?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?></td>
                            <td><?= e($r['mech_name'] ?: '—') ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td>
                                <form method="post" style="display:inline-block;margin-right:6px">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="assign">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                    <select name="mechanic_id" onchange="this.form.submit()">
                                        <option value="">Assign…</option>
                                        <?php foreach ($mechs as $m): ?>
                                            <option value="<?= (int)$m['id'] ?>" <?= (int)$r['mechanic_id']===(int)$m['id']?'selected':'' ?>><?= e($m['business_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <form method="post" style="display:inline-block;margin-right:6px">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <?php foreach (['new','in_progress','completed','rejected'] as $s): ?>
                                            <option value="<?= e($s) ?>" <?= $r['status']===$s?'selected':'' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <form method="post" style="display:inline-block" onsubmit="return confirm('Delete booking?')">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                <?php $base_qs = ['tab' => $tab]; include __DIR__ . '/../partials/pager.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
