<?php
require_once __DIR__ . '/../config/init.php';
require_role('user');
$u = current_user();
$pdo = db();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf'] ?? '');
    $bid = (int)($_POST['booking_id'] ?? 0);
    $method = $_POST['method'] ?? '';

    if (!in_array($method, ['mpesa','bank','card'], true)) {
        $errors['_'] = 'Choose a payment method.';
    } else {
        // Validate booking belongs to user and amount is server-known.
        $st = $pdo->prepare('SELECT id, amount FROM bookings WHERE id = :id AND user_id = :uid');
        $st->execute([':id' => $bid, ':uid' => $u['uid']]);
        $b = $st->fetch();
        if (!$b) { $errors['_'] = 'Invalid booking.'; }
        else {
            $amount = (float)$b['amount'];
            $detail = '';
            if ($method === 'mpesa') {
                $phone = trim($_POST['mpesa_phone'] ?? '');
                if (!validate_mobile($phone)) $errors['mpesa_phone'] = 'Enter a valid Kenyan mobile number.';
                $detail = $phone;
            } elseif ($method === 'bank') {
                $bank = trim($_POST['bank_name'] ?? '');
                $acct = trim($_POST['bank_acct'] ?? '');
                if ($bank === '' || strlen($acct) < 4) $errors['_'] = 'Enter bank name and a valid account number.';
                $detail = $bank . ' ****' . substr(preg_replace('/\s+/', '', $acct), -4);
            } elseif ($method === 'card') {
                $card = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
                $exp  = trim($_POST['card_exp'] ?? '');
                $cvv  = preg_replace('/\D/', '', $_POST['card_cvv'] ?? '');
                if (strlen($card) < 13 || strlen($card) > 19) $errors['card_number'] = 'Enter a valid card number.';
                if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $exp)) $errors['card_exp'] = 'Use MM/YY format.';
                if (strlen($cvv) < 3 || strlen($cvv) > 4) $errors['card_cvv'] = 'CVV must be 3–4 digits.';
                $detail = '**** **** **** ' . substr($card, -4);
                // Note: PAN/CVV are NEVER persisted.
            }

            if (!$errors) {
                $ref = gen_txn_ref();
                // Random demo: mpesa always completes; bank/card complete with 90% chance.
                $ok = ($method === 'mpesa') || (random_int(1,10) > 1);
                $status = $ok ? 'completed' : 'failed';
                $st = $pdo->prepare('INSERT INTO payments (booking_id, user_id, amount, method, status, transaction_ref, detail_masked) VALUES (:b,:u,:a,:m,:s,:r,:d)');
                $st->execute([':b'=>$bid, ':u'=>$u['uid'], ':a'=>$amount, ':m'=>$method, ':s'=>$status, ':r'=>$ref, ':d'=>$detail]);
                flash('success', $ok ? 'Payment successful. Ref: ' . $ref : 'Payment failed. Please try another method.');
                redirect('user/payment.php');
            }
        }
    }
}

// Pending bookings with no completed payment yet
$st = $pdo->prepare("
    SELECT b.id, b.booking_number, b.vehicle_plate, b.amount, b.status,
           (SELECT COUNT(*) FROM payments p WHERE p.booking_id = b.id AND p.status = 'completed') AS paid_done
    FROM bookings b
    WHERE b.user_id = :uid AND b.amount > 0
    HAVING paid_done = 0
    ORDER BY b.created_at DESC
");
$st->execute([':uid' => $u['uid']]);
$pending = $st->fetchAll();

$st = $pdo->prepare("
    SELECT p.*, b.booking_number, b.vehicle_plate
    FROM payments p JOIN bookings b ON b.id = p.booking_id
    WHERE p.user_id = :uid
    ORDER BY p.created_at DESC
");
$st->execute([':uid' => $u['uid']]);
$history = $st->fetchAll();

$page_title = 'Payment';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-user.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h"><h2 style="margin:0">Payments</h2></div>

            <?php if (!empty($errors['_'])): ?><div class="alert alert-error mb-16"><?= e($errors['_']) ?></div><?php endif; ?>

            <div class="card mb-16">
                <h3 class="card-h">Pay for a booking</h3>
                <?php if (!$pending): ?>
                    <p class="text-muted">No outstanding bookings. 🎉</p>
                <?php else: ?>
                <form method="post" id="pay-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <div class="form-row">
                        <label>Booking</label>
                        <select name="booking_id" required>
                            <?php foreach ($pending as $b): ?>
                                <option value="<?= (int)$b['id'] ?>" data-amt="<?= (float)$b['amount'] ?>">
                                    <?= e($b['booking_number']) ?> · <?= e($b['vehicle_plate']) ?> · <?= e(fmt_kes((float)$b['amount'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="pay-tabs" role="tablist">
                        <button type="button" data-method="mpesa" class="active">M-Pesa</button>
                        <button type="button" data-method="bank">Bank Transfer</button>
                        <button type="button" data-method="card">Card</button>
                    </div>
                    <input type="hidden" name="method" id="method" value="mpesa">

                    <div id="pay-mpesa" class="pay-section">
                        <div class="form-row">
                            <label>M-Pesa phone number</label>
                            <input type="tel" name="mpesa_phone" placeholder="07xxxxxxxx" maxlength="10">
                            <span class="field-error"><?= e($errors['mpesa_phone'] ?? '') ?></span>
                        </div>
                    </div>

                    <div id="pay-bank" class="pay-section hidden">
                        <div class="form-grid-2">
                            <div class="form-row"><label>Bank name</label><input type="text" name="bank_name" placeholder="e.g. KCB"></div>
                            <div class="form-row"><label>Account number</label><input type="text" name="bank_acct"></div>
                        </div>
                    </div>

                    <div id="pay-card" class="pay-section hidden">
                        <div class="form-row">
                            <label>Card number</label>
                            <input type="text" name="card_number" id="card_number" placeholder="1234 5678 9012 3456" inputmode="numeric" maxlength="19">
                            <span class="field-error"><?= e($errors['card_number'] ?? '') ?></span>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-row"><label>Expiry (MM/YY)</label><input type="text" name="card_exp" placeholder="MM/YY" maxlength="5"><span class="field-error"><?= e($errors['card_exp'] ?? '') ?></span></div>
                            <div class="form-row"><label>CVV</label><input type="password" name="card_cvv" maxlength="4" inputmode="numeric"><span class="field-error"><?= e($errors['card_cvv'] ?? '') ?></span></div>
                        </div>
                    </div>

                    <button type="submit" class="btn">Pay <span id="pay-amt"></span></button>
                </form>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3 class="card-h">Payment history</h3>
                <?php if (!$history): ?>
                    <p class="text-muted">No payments yet.</p>
                <?php else: ?>
                    <div class="table-wrap"><table class="table">
                        <thead><tr><th>Date</th><th>Booking</th><th>Vehicle</th><th>Method</th><th>Reference</th><th>Detail</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($history as $p): ?>
                                <tr>
                                    <td><?= e(date('d M Y, H:i', strtotime($p['created_at']))) ?></td>
                                    <td><?= e($p['booking_number']) ?></td>
                                    <td><?= e($p['vehicle_plate']) ?></td>
                                    <td><?= e(strtoupper($p['method'])) ?></td>
                                    <td class="no-wrap"><?= e($p['transaction_ref']) ?></td>
                                    <td><?= e($p['detail_masked']) ?></td>
                                    <td class="no-wrap"><?= e(fmt_kes((float)$p['amount'])) ?></td>
                                    <td><?= status_badge($p['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php
$inline_js = <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('pay-form');
    if (!form) return;
    var card = document.getElementById('card_number');
    if (card) window.maskCardInput(card);
    var sel = form.querySelector('[name=booking_id]');
    var amt = document.getElementById('pay-amt');
    function refreshAmt() {
        if (!sel || !amt) return;
        var opt = sel.selectedOptions[0];
        if (!opt) return;
        var v = parseFloat(opt.dataset.amt || 0);
        amt.textContent = ' KES ' + v.toLocaleString();
    }
    if (sel) { sel.addEventListener('change', refreshAmt); refreshAmt(); }
    document.querySelectorAll('.pay-tabs button').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('.pay-tabs button').forEach(function (x) { x.classList.remove('active'); });
            b.classList.add('active');
            var m = b.dataset.method;
            document.getElementById('method').value = m;
            ['mpesa','bank','card'].forEach(function (x) {
                var el = document.getElementById('pay-' + x);
                if (el) el.classList.toggle('hidden', x !== m);
            });
        });
    });

    // Auto-format MM/YY
    var exp = form.querySelector('[name=card_exp]');
    if (exp) exp.addEventListener('input', function () {
        var v = exp.value.replace(/\D/g, '').slice(0, 4);
        if (v.length >= 3) v = v.slice(0,2) + '/' + v.slice(2);
        exp.value = v;
    });
});
JS;
include __DIR__ . '/../partials/footer.php';
?>
