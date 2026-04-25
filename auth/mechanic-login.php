<?php
require_once __DIR__ . '/../config/init.php';

$err = '';
$success = get_flash('success');
$timeout_err = get_flash('error');
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $pw = $_POST['password'] ?? '';
    if (!$email || !$pw) {
        $err = 'Email and password are required.';
    } else {
        $st = db()->prepare('SELECT id, name, password, status FROM mechanics WHERE email = :e LIMIT 1');
        $st->execute([':e' => $email]);
        $u = $st->fetch();
        if ($u && password_verify($pw, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['role'] = 'mechanic';
            $_SESSION['uid']  = (int)$u['id'];
            $_SESSION['name'] = $u['name'];
            $_SESSION['mech_status'] = $u['status'];
            $_SESSION['last_activity'] = time();
            redirect('mechanic/dashboard.php');
        } else {
            $err = 'Invalid email or password.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mechanic Login — FleetSimplify</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/main.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>">
</head>
<body class="auth-shell">
<div class="auth-card">
    <div class="text-center mb-16"><span class="brand"><span class="brand-mark">FS</span> FleetSimplify</span></div>
    <h1>Mechanic Sign In</h1>
    <p class="auth-sub">Welcome back to your workshop.</p>

    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($timeout_err): ?><div class="alert alert-warning"><?= e($timeout_err) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>

    <form method="post" data-validate-form novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="form-row">
            <label>Email</label>
            <input type="email" name="email" data-validate="email" value="<?= e($email) ?>">
            <span class="field-error"></span>
        </div>
        <div class="form-row">
            <label>Password</label>
            <div class="password-wrap">
                <input type="password" name="password" data-validate="required">
                <button type="button" class="password-toggle">Show</button>
            </div>
            <span class="field-error"></span>
        </div>
        <button type="submit" class="btn btn-block">Sign in</button>
    </form>

    <div class="auth-foot">New to FleetSimplify? <a href="<?= e(url('auth/mechanic-register.php')) ?>">Register your business</a></div>
    <div class="text-center mt-16"><a class="back-link" href="<?= e(url('index.php')) ?>" style="color:var(--grey-500)">← Back to home</a></div>
</div>
<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
