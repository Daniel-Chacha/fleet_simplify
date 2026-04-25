<?php
require_once __DIR__ . '/../config/init.php';
require_role('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf'] ?? '');
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        if ($name === '' || !validate_email($email) || !validate_mobile($mobile)) {
            flash('error', 'Invalid input.');
        } else {
            try {
                $pdo->prepare('UPDATE users SET name=:n, email=:e, mobile=:m WHERE id=:id')
                    ->execute([':n'=>$name, ':e'=>$email, ':m'=>$mobile, ':id'=>$id]);
                flash('success', 'Driver updated.');
            } catch (PDOException $ex) {
                flash('error', (int)$ex->getCode() === 23000 ? 'Email already in use.' : 'Update failed.');
            }
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $id]);
        flash('success', 'Driver deleted.');
    }
    $qs = ['page' => (int)($_POST['page'] ?? 1), 'per' => (int)($_POST['per'] ?? 10)];
    redirect('admin/drivers.php?' . http_build_query($qs));
}

$total = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$pg = paginate($total);

$st = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.mobile, u.created_at,
        (SELECT vehicle_plate FROM bookings b WHERE b.user_id = u.id ORDER BY b.created_at DESC LIMIT 1) AS plate,
        (SELECT vehicle_type  FROM bookings b WHERE b.user_id = u.id ORDER BY b.created_at DESC LIMIT 1) AS vtype
    FROM users u ORDER BY u.id ASC LIMIT :lim OFFSET :off
");
$st->bindValue(':lim', $pg['per'],    PDO::PARAM_INT);
$st->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

$page_title = 'Drivers';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-admin.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h"><h2 style="margin:0">Drivers</h2></div>
            <div class="card">
                <div class="table-wrap"><table class="table">
                    <thead><tr>
                        <th>Car Plate</th><th>Vehicle</th><th>Driver ID</th><th>Name</th><th>Mobile</th><th>Email</th><th>Joined</th><th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= e($r['plate'] ?: '—') ?></td>
                            <td><?= e($r['vtype'] ?: '—') ?></td>
                            <td>#<?= (int)$r['id'] ?></td>
                            <td><?= e($r['name']) ?></td>
                            <td><?= e($r['mobile']) ?></td>
                            <td><?= e($r['email']) ?></td>
                            <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline"
                                        onclick='openEdit(<?= json_encode([
                                            "id" => (int)$r["id"], "name" => $r["name"], "email" => $r["email"], "mobile" => $r["mobile"]
                                        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'>Edit</button>
                                <form method="post" style="display:inline" onsubmit="return confirm('Delete this driver?')">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="page" value="<?= (int)$pg['page'] ?>">
                                    <input type="hidden" name="per"  value="<?= (int)$pg['per'] ?>">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>

                <?php $base_qs = []; include __DIR__ . '/../partials/pager.php'; ?>
            </div>
        </div>
    </main>
</div>

<div class="modal-backdrop" id="edit-modal">
    <div class="modal">
        <div class="modal-h"><h3>Edit driver</h3><button class="modal-x" data-modal-close>×</button></div>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="ed-id">
            <input type="hidden" name="page" value="<?= (int)$pg['page'] ?>">
                                    <input type="hidden" name="per"  value="<?= (int)$pg['per'] ?>">
            <div class="form-row"><label>Name</label><input type="text" name="name" id="ed-name" required></div>
            <div class="form-row"><label>Email</label><input type="email" name="email" id="ed-email" required></div>
            <div class="form-row"><label>Mobile</label><input type="tel" name="mobile" id="ed-mobile" maxlength="10" required></div>
            <div class="f-between"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button type="submit" class="btn">Save</button></div>
        </form>
    </div>
</div>

<?php
$inline_js = <<<'JS'
window.openEdit = function (d) {
    document.getElementById('ed-id').value = d.id;
    document.getElementById('ed-name').value = d.name;
    document.getElementById('ed-email').value = d.email;
    document.getElementById('ed-mobile').value = d.mobile;
    openModal('edit-modal');
};
JS;
include __DIR__ . '/../partials/footer.php';
?>
