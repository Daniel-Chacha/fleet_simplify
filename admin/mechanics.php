<?php
require_once __DIR__ . '/../config/init.php';
require_role('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf'] ?? '');
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'approve') {
        $pdo->prepare("UPDATE mechanics SET status='approved' WHERE id = :id")->execute([':id' => $id]);
        flash('success', 'Mechanic approved.');
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE mechanics SET status='pending' WHERE id = :id")->execute([':id' => $id]);
        flash('success', 'Mechanic set back to pending.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM mechanics WHERE id = :id')->execute([':id' => $id]);
        flash('success', 'Mechanic deleted.');
    } elseif ($action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $business = trim($_POST['business_name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $mobile = trim($_POST['mobile'] ?? '');
        $town = trim($_POST['town'] ?? '');
        $services = trim($_POST['services'] ?? '');
        if (!validate_email($email) || !validate_mobile($mobile) || $name === '' || $business === '') {
            flash('error', 'Invalid input.');
        } else {
            try {
                $pdo->prepare('UPDATE mechanics SET name=:n, business_name=:b, email=:e, mobile=:m, town=:t, service_description=:sd WHERE id=:id')
                    ->execute([':n'=>$name, ':b'=>$business, ':e'=>$email, ':m'=>$mobile, ':t'=>$town, ':sd'=>$services, ':id'=>$id]);
                flash('success', 'Mechanic updated.');
            } catch (PDOException $ex) {
                flash('error', (int)$ex->getCode() === 23000 ? 'Email already in use.' : 'Update failed.');
            }
        }
    }
    $back_qs = http_build_query([
        'tab'  => $_POST['tab']  ?? 'all',
        'per'  => $_POST['per']  ?? 10,
        'page' => $_POST['page'] ?? 1,
    ]);
    redirect('admin/mechanics.php?' . $back_qs);
}

// Status filter tab
$tab = $_GET['tab'] ?? 'all';
if (!in_array($tab, ['all', 'pending', 'approved'], true)) $tab = 'all';

// Counts for badge labels next to each tab
$counts = ['all' => 0, 'pending' => 0, 'approved' => 0];
foreach ($pdo->query("SELECT status, COUNT(*) AS c FROM mechanics GROUP BY status") as $r) {
    if (isset($counts[$r['status']])) $counts[$r['status']] = (int)$r['c'];
}
$counts['all'] = $counts['pending'] + $counts['approved'];

$total = $counts[$tab];
$pg = paginate($total);

if ($tab === 'all') {
    $st = $pdo->prepare('SELECT * FROM mechanics ORDER BY status DESC, business_name LIMIT :lim OFFSET :off');
} else {
    $st = $pdo->prepare('SELECT * FROM mechanics WHERE status = :s ORDER BY business_name LIMIT :lim OFFSET :off');
    $st->bindValue(':s', $tab);
}
$st->bindValue(':lim', $pg['per'],    PDO::PARAM_INT);
$st->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

$page_title = 'Mechanics';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-admin.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h"><h2 style="margin:0">Mechanics</h2></div>

            <div class="tabs">
                <a href="?tab=all&amp;per=<?= (int)$pg['per'] ?>"      class="<?= $tab==='all'      ? 'active' : '' ?>">All <span class="text-muted">(<?= $counts['all'] ?>)</span></a>
                <a href="?tab=pending&amp;per=<?= (int)$pg['per'] ?>"  class="<?= $tab==='pending'  ? 'active' : '' ?>">Pending <span class="text-muted">(<?= $counts['pending'] ?>)</span></a>
                <a href="?tab=approved&amp;per=<?= (int)$pg['per'] ?>" class="<?= $tab==='approved' ? 'active' : '' ?>">Approved <span class="text-muted">(<?= $counts['approved'] ?>)</span></a>
            </div>

            <div class="card">
                <?php if (!$rows): ?>
                    <p class="text-muted">No mechanics in this view.</p>
                <?php else: ?>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>#</th><th>Business / Name</th><th>Location</th><th>Email</th><th>Service Type</th><th>Mobile</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= e($r['business_name']) ?></strong><br><small class="text-muted"><?= e($r['name']) ?></small></td>
                            <td><?= e($r['town']) ?></td>
                            <td><?= e($r['email']) ?></td>
                            <td><?= e($r['service_description']) ?></td>
                            <td><?= e($r['mobile']) ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="tab"  value="<?= e($tab) ?>">
                                        <input type="hidden" name="per"  value="<?= (int)$pg['per'] ?>">
                                        <input type="hidden" name="page" value="<?= (int)$pg['page'] ?>">
                                        <button class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="tab"  value="<?= e($tab) ?>">
                                        <input type="hidden" name="per"  value="<?= (int)$pg['per'] ?>">
                                        <input type="hidden" name="page" value="<?= (int)$pg['page'] ?>">
                                        <button class="btn btn-sm btn-outline">Set Pending</button>
                                    </form>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline"
                                        onclick='openEdit(<?= json_encode([
                                            "id"=>(int)$r["id"],"name"=>$r["name"],"business_name"=>$r["business_name"],
                                            "email"=>$r["email"],"mobile"=>$r["mobile"],"town"=>$r["town"],"services"=>$r["service_description"]
                                        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'>Edit</button>
                                <form method="post" style="display:inline" onsubmit="return confirm('Delete this mechanic?')">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="tab"  value="<?= e($tab) ?>">
                                    <input type="hidden" name="per"  value="<?= (int)$pg['per'] ?>">
                                    <input type="hidden" name="page" value="<?= (int)$pg['page'] ?>">
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

<div class="modal-backdrop" id="edit-modal">
    <div class="modal">
        <div class="modal-h"><h3>Edit mechanic</h3><button class="modal-x" data-modal-close>×</button></div>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="ed-id">
            <input type="hidden" name="tab"  value="<?= e($tab) ?>">
            <input type="hidden" name="per"  value="<?= (int)$pg['per'] ?>">
            <input type="hidden" name="page" value="<?= (int)$pg['page'] ?>">
            <div class="form-grid-2">
                <div class="form-row"><label>Business name</label><input type="text" name="business_name" id="ed-business" required></div>
                <div class="form-row"><label>Mechanic name</label><input type="text" name="name" id="ed-name" required></div>
            </div>
            <div class="form-grid-2">
                <div class="form-row"><label>Email</label><input type="email" name="email" id="ed-email" required></div>
                <div class="form-row"><label>Mobile</label><input type="tel" name="mobile" id="ed-mobile" maxlength="10" required></div>
            </div>
            <div class="form-grid-2">
                <div class="form-row"><label>Town</label><input type="text" name="town" id="ed-town" required></div>
                <div class="form-row"><label>Services (comma-separated)</label><input type="text" name="services" id="ed-services"></div>
            </div>
            <div class="f-between"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button type="submit" class="btn">Save</button></div>
        </form>
    </div>
</div>

<?php
$inline_js = <<<'JS'
window.openEdit = function (m) {
    document.getElementById('ed-id').value = m.id;
    document.getElementById('ed-name').value = m.name;
    document.getElementById('ed-business').value = m.business_name;
    document.getElementById('ed-email').value = m.email;
    document.getElementById('ed-mobile').value = m.mobile;
    document.getElementById('ed-town').value = m.town;
    document.getElementById('ed-services').value = m.services;
    openModal('edit-modal');
};
JS;
include __DIR__ . '/../partials/footer.php';
?>
