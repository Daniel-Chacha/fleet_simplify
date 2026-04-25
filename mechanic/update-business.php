<?php
require_once __DIR__ . '/../config/init.php';
require_role('mechanic');
$u = current_user();
$pdo = db();

$st = $pdo->prepare('SELECT * FROM mechanics WHERE id = :id');
$st->execute([':id' => $u['uid']]);
$me = $st->fetch();
if (!$me) redirect('auth/logout.php');

// Categorised list of services. Each top-level key is a category; values are services.
$SERVICE_CATEGORIES = [
    'Roadside'      => ['Towing & Recovery','Battery Services & Jump Starts','Tire Services','Mobile Welding','Lockout Assistance'],
    'Mechanical'    => ['Engine Repairs','Brake Repairs','Transmission & Clutch','Suspension & Steering','Diesel Specialist'],
    'Electrical'    => ['Electrical Diagnostics','AC & Heating','Headlight & Signal Repair'],
    'Bodywork'      => ['Bodywork & Dent Repair','Glass Replacement','Paint & Polish'],
    'Maintenance'   => ['Oil & Lubrication','Wheel Alignment','Pre-trip Inspections'],
];
$ALL_SERVICES = [];
foreach ($SERVICE_CATEGORIES as $cat => $items) foreach ($items as $svc) $ALL_SERVICES[] = $svc;

$current_services = array_filter(array_map('trim', explode(',', $me['service_description'])));

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf'] ?? '');
    $business = trim($_POST['business_name'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $services = $_POST['services'] ?? [];
    $availability = trim($_POST['availability'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $town = trim($_POST['town'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');

    foreach (['business_name'=>$business,'name'=>$name,'town'=>$town,'address'=>$address,'availability'=>$availability] as $k => $v) {
        if ($v === '') $errors[$k] = 'Required.';
    }
    if (!validate_mobile($mobile)) $errors['mobile'] = 'Mobile must be 10 digits and start with 07 or 01.';
    $services = array_values(array_intersect($ALL_SERVICES, is_array($services) ? $services : []));
    if (!$services) $errors['services'] = 'Select at least one service.';

    if (!$errors) {
        $st = $pdo->prepare('UPDATE mechanics SET business_name=:b, name=:n, service_description=:sd, availability=:av, address=:a, town=:t, mobile=:m WHERE id=:id');
        $st->execute([
            ':b'=>$business, ':n'=>$name, ':sd'=>implode(',', $services), ':av'=>$availability,
            ':a'=>$address, ':t'=>$town, ':m'=>$mobile, ':id'=>$u['uid']
        ]);
        $_SESSION['name'] = $name;
        flash('success', 'Business profile updated.');
        redirect('mechanic/update-business.php');
    }
    // re-render with submitted values
    $me['business_name'] = $business; $me['name'] = $name; $me['town'] = $town;
    $me['address'] = $address; $me['availability'] = $availability; $me['mobile'] = $mobile;
    $current_services = $services;
}

$page_title = 'Business Profile';
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-mechanic.php'; ?>
    <main class="main">
        <?php $show_bell = ($me['status'] === 'approved'); include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h">
                <h2 style="margin:0">Business profile</h2>
                <?php if ($me['status'] === 'approved'): ?>
                    <span class="badge badge-success">APPROVED</span>
                <?php else: ?>
                    <span class="badge badge-warning">PENDING APPROVAL</span>
                <?php endif; ?>
            </div>

            <div class="card">
                <form method="post" data-validate-form novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <div class="form-grid-2">
                        <div class="form-row"><label>Business Name</label><input type="text" name="business_name" data-validate="required" value="<?= e($me['business_name']) ?>"><span class="field-error"><?= e($errors['business_name'] ?? '') ?></span></div>
                        <div class="form-row"><label>Mechanic Name</label><input type="text" name="name" data-validate="required" value="<?= e($me['name']) ?>"><span class="field-error"><?= e($errors['name'] ?? '') ?></span></div>
                    </div>

                    <div class="form-row">
                        <label>Services offered <span class="text-muted" style="font-weight:400">— pick all that apply</span></label>
                        <div class="svc-picker" id="svc-picker">
                            <div class="svc-bar">
                                <input type="search" id="svc-search" class="svc-search" placeholder="Search services…">
                                <select id="svc-cat" class="svc-cat" aria-label="Filter by category">
                                    <option value="">All categories</option>
                                    <?php foreach (array_keys($SERVICE_CATEGORIES) as $cat): ?>
                                        <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="svc-counter"><strong id="svc-count">0</strong> selected</span>
                            </div>
                            <div class="svc-grid" id="svc-grid">
                                <?php foreach ($SERVICE_CATEGORIES as $cat => $items): ?>
                                    <?php foreach ($items as $sv): $checked = in_array($sv, $current_services, true); ?>
                                        <label class="svc-chip <?= $checked ? 'is-active' : '' ?>" data-svc="<?= e($sv) ?>" data-cat="<?= e($cat) ?>">
                                            <input type="checkbox" name="services[]" value="<?= e($sv) ?>" <?= $checked ? 'checked' : '' ?>>
                                            <span class="svc-tick">✓</span>
                                            <span class="svc-name"><?= e($sv) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                                <div class="svc-empty hidden" id="svc-empty">No services match your search.</div>
                            </div>
                            <div class="svc-pills" id="svc-pills"><span class="pill-empty">Pick at least one service above.</span></div>
                        </div>
                        <span class="field-error"><?= e($errors['services'] ?? '') ?></span>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-row">
                            <label>Availability</label>
                            <input type="text" name="availability" data-validate="required" value="<?= e($me['availability']) ?>" placeholder="e.g. 24/7 or Mon–Fri 8am–6pm">
                            <span class="field-error"><?= e($errors['availability'] ?? '') ?></span>
                        </div>
                        <div class="form-row">
                            <label>Mobile</label>
                            <input type="tel" name="mobile" data-validate="mobile" value="<?= e($me['mobile']) ?>" maxlength="10">
                            <span class="field-error"><?= e($errors['mobile'] ?? '') ?></span>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-row"><label>Town</label><input type="text" name="town" data-validate="required" value="<?= e($me['town']) ?>"><span class="field-error"><?= e($errors['town'] ?? '') ?></span></div>
                        <div class="form-row"><label>Address</label><input type="text" name="address" data-validate="required" value="<?= e($me['address']) ?>"><span class="field-error"><?= e($errors['address'] ?? '') ?></span></div>
                    </div>

                    <button type="submit" class="btn">Save business details</button>
                </form>
            </div>
        </div>
    </main>
</div>
<?php
$inline_js = <<<'JS'
(function () {
    const picker = document.getElementById('svc-picker');
    if (!picker) return;
    const grid    = document.getElementById('svc-grid');
    const search  = document.getElementById('svc-search');
    const cat     = document.getElementById('svc-cat');
    const pills   = document.getElementById('svc-pills');
    const counter = document.getElementById('svc-count');
    const empty   = document.getElementById('svc-empty');
    const chips   = Array.from(grid.querySelectorAll('.svc-chip'));

    function refreshPills() {
        const sel = chips.filter(function (c) { return c.querySelector('input').checked; });
        counter.textContent = sel.length;
        if (!sel.length) {
            pills.innerHTML = '<span class="pill-empty">Pick at least one service above.</span>';
            return;
        }
        pills.innerHTML = sel.map(function (c) {
            return '<span class="pill">' +
                c.dataset.svc.replace(/[&<>"']/g, function (x) { return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[x]; }) +
                '<button type="button" data-rm="' + c.dataset.svc.replace(/"/g, '&quot;') + '" aria-label="Remove">×</button></span>';
        }).join('');
    }
    function applyFilter() {
        const q = (search.value || '').toLowerCase().trim();
        const c = cat.value;
        let visible = 0;
        chips.forEach(function (chip) {
            const matchQ = !q || chip.dataset.svc.toLowerCase().indexOf(q) >= 0;
            const matchC = !c || chip.dataset.cat === c;
            const show = matchQ && matchC;
            chip.classList.toggle('is-hidden', !show);
            if (show) visible++;
        });
        empty.classList.toggle('hidden', visible > 0);
    }

    chips.forEach(function (chip) {
        chip.addEventListener('click', function (e) {
            // Toggle without resubmitting form
            const inp = chip.querySelector('input');
            if (e.target !== inp) {
                e.preventDefault();
                inp.checked = !inp.checked;
            }
            chip.classList.toggle('is-active', inp.checked);
            refreshPills();
        });
    });
    pills.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-rm]');
        if (!btn) return;
        const name = btn.dataset.rm;
        chips.forEach(function (c) {
            if (c.dataset.svc === name) {
                c.classList.remove('is-active');
                c.querySelector('input').checked = false;
            }
        });
        refreshPills();
    });
    search.addEventListener('input', applyFilter);
    cat.addEventListener('change', applyFilter);

    refreshPills();
})();
JS;
include __DIR__ . '/../partials/footer.php';
?>
