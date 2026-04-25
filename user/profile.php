<?php
require_once __DIR__ . '/../config/init.php';
require_role('user');
$u = current_user();
$pdo = db();

$st = $pdo->prepare('SELECT id, name, email, mobile, created_at FROM users WHERE id = :id');
$st->execute([':id' => $u['uid']]);
$me = $st->fetch();
if (!$me) { redirect('auth/logout.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf'] ?? '');
    $action = $_POST['form'] ?? '';
    if ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        if ($name === '') $errors['name'] = 'Required.';
        if (!validate_mobile($mobile)) $errors['mobile'] = 'Mobile must be 10 digits and start with 07 or 01.';
        if (!$errors) {
            $pdo->prepare('UPDATE users SET name = :n, mobile = :m WHERE id = :id')
                ->execute([':n' => $name, ':m' => $mobile, ':id' => $u['uid']]);
            $_SESSION['name'] = $name;
            flash('success', 'Profile updated.');
            redirect('user/profile.php');
        }
        $me['name'] = $name; $me['mobile'] = $mobile;
    }
    elseif ($action === 'password') {
        $cur = $_POST['current'] ?? '';
        $new = $_POST['new'] ?? '';
        $new2 = $_POST['new2'] ?? '';
        $st = $pdo->prepare('SELECT password FROM users WHERE id = :id');
        $st->execute([':id' => $u['uid']]);
        $hash = $st->fetchColumn();
        if (!password_verify($cur, $hash)) $errors['current'] = 'Current password is incorrect.';
        if ($pw_msg = validate_password($new)) $errors['new'] = $pw_msg;
        if ($new !== $new2) $errors['new2'] = 'Passwords do not match.';
        if (!$errors) {
            $pdo->prepare('UPDATE users SET password = :p WHERE id = :id')
                ->execute([':p' => password_hash($new, PASSWORD_BCRYPT), ':id' => $u['uid']]);
            flash('success', 'Password changed.');
            redirect('user/profile.php');
        }
    }
}

$page_title = 'My Profile';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-user.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h"><h2 style="margin:0">My Profile</h2></div>

            <div class="card mb-16">
                <h3 class="card-h">Personal details</h3>
                <form method="post" data-validate-form novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="form" value="profile">
                    <div class="form-grid-2">
                        <div class="form-row">
                            <label>Name</label>
                            <input type="text" name="name" data-validate="required" value="<?= e($me['name']) ?>">
                            <span class="field-error"><?= e($errors['name'] ?? '') ?></span>
                        </div>
                        <div class="form-row">
                            <label>Email</label>
                            <input type="email" value="<?= e($me['email']) ?>" disabled>
                            <span class="field-help">Email cannot be changed.</span>
                        </div>
                    </div>
                    <div class="form-row">
                        <label>Mobile</label>
                        <input type="tel" name="mobile" data-validate="mobile" value="<?= e($me['mobile']) ?>" maxlength="10">
                        <span class="field-error"><?= e($errors['mobile'] ?? '') ?></span>
                    </div>
                    <button type="submit" class="btn">Save changes</button>
                </form>
            </div>

            <div class="card">
                <h3 class="card-h">Change password</h3>
                <form method="post" data-validate-form novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="form" value="password">
                    <div class="form-row">
                        <label>Current password</label>
                        <div class="password-wrap"><input type="password" name="current" data-validate="required"><button type="button" class="password-toggle">Show</button></div>
                        <span class="field-error"><?= e($errors['current'] ?? '') ?></span>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-row">
                            <label>New password</label>
                            <div class="password-wrap"><input type="password" id="np" name="new" data-validate="password" data-pw-rules="#pwr-prof-user"><button type="button" class="password-toggle">Show</button></div>
                            <span class="field-error"><?= e($errors['new'] ?? '') ?></span>
                        </div>
                        <div class="form-row">
                            <label>Confirm new password</label>
                            <div class="password-wrap"><input type="password" name="new2" data-validate="password" data-match="#np"><button type="button" class="password-toggle">Show</button></div>
                            <span class="field-error"><?= e($errors['new2'] ?? '') ?></span>
                        </div>
                    </div>
                    <?php $rules_id = 'pwr-prof-user'; include __DIR__ . '/../partials/pw-rules.php'; ?>
                    <button type="submit" class="btn mt-16">Update password</button>
                </form>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
