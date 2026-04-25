<?php
require_once __DIR__ . '/../config/init.php';

$errors = [];
$fields = ['business_name','name','email','mobile','town','address','licence_no'];
$old = array_fill_keys($fields, '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf'] ?? '');
    foreach ($fields as $f) $old[$f] = trim($_POST[$f] ?? '');
    $old['email'] = strtolower($old['email']);
    $pw  = $_POST['password'] ?? '';
    $pw2 = $_POST['password2'] ?? '';

    foreach (['business_name','name','town','address','licence_no'] as $f) {
        if ($old[$f] === '') $errors[$f] = 'Required.';
    }
    if (!validate_email($old['email'])) $errors['email'] = 'Enter a valid email.';
    if (!validate_mobile($old['mobile'])) $errors['mobile'] = 'Mobile must be 10 digits and start with 07 or 01.';
    if ($pw_msg = validate_password($pw)) $errors['password'] = $pw_msg;
    if ($pw !== $pw2) $errors['password2'] = 'Passwords do not match.';

    if (!$errors) {
        try {
            $hash = password_hash($pw, PASSWORD_BCRYPT);
            $st = db()->prepare(
                'INSERT INTO mechanics (name, email, password, mobile, town, address, licence_no, business_name, status)
                 VALUES (:n, :e, :p, :m, :t, :a, :l, :b, "pending")'
            );
            $st->execute([
                ':n' => $old['name'], ':e' => $old['email'], ':p' => $hash,
                ':m' => $old['mobile'], ':t' => $old['town'], ':a' => $old['address'],
                ':l' => $old['licence_no'], ':b' => $old['business_name'],
            ]);
            flash('success', 'Account created. Awaiting admin approval — you can sign in once approved.');
            redirect('auth/mechanic-login.php');
        } catch (PDOException $ex) {
            if ((int)$ex->getCode() === 23000) {
                $errors['email'] = 'Email already registered.';
            } else {
                error_log('mechanic-register: ' . $ex->getMessage());
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
<title>Mechanic Registration — FleetSimplify</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/main.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>">
</head>
<body class="auth-shell">
<div class="auth-card wide">
    <div class="text-center mb-16"><span class="brand"><span class="brand-mark">FS</span> FleetSimplify</span></div>
    <h1>Register your business</h1>
    <p class="auth-sub">Receive new requests, build your reputation.</p>

    <?php if (!empty($errors['_'])): ?><div class="alert alert-error"><?= e($errors['_']) ?></div><?php endif; ?>

    <form method="post" data-validate-form novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="form-grid-2">
            <div class="form-row">
                <label>Business Name</label>
                <input type="text" name="business_name" data-validate="required" value="<?= e($old['business_name']) ?>">
                <span class="field-error"><?= e($errors['business_name'] ?? '') ?></span>
            </div>
            <div class="form-row">
                <label>Mechanic Name</label>
                <input type="text" name="name" data-validate="required" value="<?= e($old['name']) ?>">
                <span class="field-error"><?= e($errors['name'] ?? '') ?></span>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-row">
                <label>Email</label>
                <input type="email" name="email" data-validate="email" value="<?= e($old['email']) ?>">
                <span class="field-error"><?= e($errors['email'] ?? '') ?></span>
            </div>
            <div class="form-row">
                <label>Mobile</label>
                <input type="tel" name="mobile" data-validate="mobile" value="<?= e($old['mobile']) ?>" placeholder="07xxxxxxxx" maxlength="10">
                <span class="field-error"><?= e($errors['mobile'] ?? '') ?></span>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-row">
                <label>Town</label>
                <input type="text" name="town" data-validate="required" value="<?= e($old['town']) ?>">
                <span class="field-error"><?= e($errors['town'] ?? '') ?></span>
            </div>
            <div class="form-row">
                <label>Licence No.</label>
                <input type="text" name="licence_no" data-validate="required" value="<?= e($old['licence_no']) ?>">
                <span class="field-error"><?= e($errors['licence_no'] ?? '') ?></span>
            </div>
        </div>
        <div class="form-row">
            <label>Address</label>
            <input type="text" name="address" data-validate="required" value="<?= e($old['address']) ?>">
            <span class="field-error"><?= e($errors['address'] ?? '') ?></span>
        </div>
        <div class="form-grid-2">
            <div class="form-row">
                <label>Password</label>
                <div class="password-wrap">
                    <input type="password" id="pw1" name="password" data-validate="password" data-pw-rules="#pwr-reg-mech">
                    <button type="button" class="password-toggle">Show</button>
                </div>
                <span class="field-error"><?= e($errors['password'] ?? '') ?></span>
            </div>
            <div class="form-row">
                <label>Confirm Password</label>
                <div class="password-wrap">
                    <input type="password" name="password2" data-validate="password" data-match="#pw1">
                    <button type="button" class="password-toggle">Show</button>
                </div>
                <span class="field-error"><?= e($errors['password2'] ?? '') ?></span>
            </div>
        </div>
        <?php $rules_id = 'pwr-reg-mech'; include __DIR__ . '/../partials/pw-rules.php'; ?>
        <button type="submit" class="btn btn-block mt-16">Submit for approval</button>
    </form>

    <div class="auth-foot">Already approved? <a href="<?= e(url('auth/mechanic-login.php')) ?>">Sign in</a></div>
    <div class="text-center mt-16"><a class="back-link" href="<?= e(url('index.php')) ?>" style="color:var(--grey-500)">← Back to home</a></div>
</div>
<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
