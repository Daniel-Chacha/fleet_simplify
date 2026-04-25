<?php
require_once __DIR__ . '/../config/init.php';

$errors = [];
$old = ['name' => '', 'email' => '', 'mobile' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf'] ?? '');
    $name    = trim($_POST['name'] ?? '');
    $email   = trim(strtolower($_POST['email'] ?? ''));
    $mobile  = trim($_POST['mobile'] ?? '');
    $pw      = $_POST['password'] ?? '';
    $pw2     = $_POST['password2'] ?? '';
    $old     = compact('name', 'email', 'mobile');

    if ($name === '') $errors['name'] = 'Name is required.';
    if (!validate_email($email)) $errors['email'] = 'Enter a valid email.';
    if (!validate_mobile($mobile)) $errors['mobile'] = 'Mobile must be 10 digits and start with 07 or 01.';
    if ($pw_msg = validate_password($pw)) $errors['password'] = $pw_msg;
    if ($pw !== $pw2) $errors['password2'] = 'Passwords do not match.';

    if (!$errors) {
        try {
            $hash = password_hash($pw, PASSWORD_BCRYPT);
            $st = db()->prepare('INSERT INTO users (name, email, password, mobile) VALUES (:n, :e, :p, :m)');
            $st->execute([':n' => $name, ':e' => $email, ':p' => $hash, ':m' => $mobile]);
            flash('success', 'Registration successful. Please sign in.');
            redirect('auth/user-login.php');
        } catch (PDOException $ex) {
            if ((int)$ex->getCode() === 23000) {
                $errors['email'] = 'That email is already registered.';
            } else {
                error_log('user-register: ' . $ex->getMessage());
                $errors['_'] = 'Could not register. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Driver Registration — FleetSimplify</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/main.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>">
</head>
<body class="auth-shell">
<div class="auth-card wide">
    <div class="text-center mb-16"><span class="brand"><span class="brand-mark">FS</span> FleetSimplify</span></div>
    <h1>Create driver account</h1>
    <p class="auth-sub">Get help on the road in seconds.</p>

    <?php if (!empty($errors['_'])): ?><div class="alert alert-error"><?= e($errors['_']) ?></div><?php endif; ?>

    <form method="post" data-validate-form novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="form-grid-2">
            <div class="form-row">
                <label>Full Name</label>
                <input type="text" name="name" data-validate="required" value="<?= e($old['name']) ?>" autocomplete="name">
                <span class="field-error"><?= e($errors['name'] ?? '') ?></span>
            </div>
            <div class="form-row">
                <label>Email</label>
                <input type="email" name="email" data-validate="email" value="<?= e($old['email']) ?>" autocomplete="email">
                <span class="field-error"><?= e($errors['email'] ?? '') ?></span>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-row">
                <label>Mobile</label>
                <input type="tel" name="mobile" data-validate="mobile" value="<?= e($old['mobile']) ?>" placeholder="07xxxxxxxx" maxlength="10">
                <span class="field-error"><?= e($errors['mobile'] ?? '') ?></span>
            </div>
            <div class="form-row">
                <label>Password</label>
                <div class="password-wrap">
                    <input type="password" id="pw1" name="password" data-validate="password" data-pw-rules="#pwr-reg-user" autocomplete="new-password">
                    <button type="button" class="password-toggle">Show</button>
                </div>
                <?php $rules_id = 'pwr-reg-user'; include __DIR__ . '/../partials/pw-rules.php'; ?>
                <span class="field-error"><?= e($errors['password'] ?? '') ?></span>
            </div>
        </div>
        <div class="form-row">
            <label>Confirm Password</label>
            <div class="password-wrap">
                <input type="password" name="password2" data-validate="password" data-match="#pw1" autocomplete="new-password">
                <button type="button" class="password-toggle">Show</button>
            </div>
            <span class="field-error"><?= e($errors['password2'] ?? '') ?></span>
        </div>
        <button type="submit" class="btn btn-block">Create account</button>
    </form>

    <div class="auth-foot">Already have an account? <a href="<?= e(url('auth/user-login.php')) ?>">Sign in</a></div>
    <div class="text-center mt-16"><a class="back-link" href="<?= e(url('index.php')) ?>" style="color:var(--grey-500)">← Back to home</a></div>
</div>
<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
