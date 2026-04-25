<?php
require_once __DIR__ . '/../config/init.php';
require_role('user');
$u = current_user();
$pdo = db();

// All approved mechanics with current location.
$st = $pdo->query("
    SELECT m.id, m.business_name, m.name, m.town, m.address, m.service_description, m.availability,
           l.latitude, l.longitude,
           (SELECT ROUND(AVG(rating),1) FROM ratings r WHERE r.mechanic_id = m.id) AS avg_rating,
           (SELECT COUNT(*) FROM ratings r WHERE r.mechanic_id = m.id) AS rating_count
    FROM mechanics m
    LEFT JOIN locations l ON l.mechanic_id = m.id
    WHERE m.status = 'approved'
    ORDER BY m.town, m.business_name
");
$mechanics = $st->fetchAll();

// Filter towns for dropdown
$towns = array_unique(array_filter(array_map(function ($m) { return $m['town']; }, $mechanics)));

$page_title = 'Find On-Road Services';
$extra_css = [];
include __DIR__ . '/../partials/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-user.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h">
                <h2 style="margin:0">Find an approved mechanic</h2>
            </div>

            <div class="toolbar">
                <input type="search" id="search" class="grow" placeholder="Search by name or service…">
                <select id="town-filter">
                    <option value="">All towns</option>
                    <?php foreach ($towns as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
                </select>
            </div>

            <div class="find-grid">
                <div class="map-pane"><div id="services-map" class="map"></div></div>
                <div class="mech-list" id="mech-list">
                    <?php if (!$mechanics): ?>
                        <p class="text-muted">No approved mechanics available right now.</p>
                    <?php endif; ?>
                    <?php foreach ($mechanics as $m): ?>
                        <?php $services = array_filter(array_map('trim', explode(',', $m['service_description']))); ?>
                        <div class="mech card" data-name="<?= e(strtolower($m['business_name'].' '.$m['name'])) ?>" data-town="<?= e($m['town']) ?>" data-services="<?= e(strtolower($m['service_description'])) ?>">
                            <div style="flex:1">
                                <div class="mech-name"><?= e($m['business_name']) ?> <span class="text-muted" style="font-weight:400">— <?= e($m['name']) ?></span></div>
                                <div class="mech-meta"><?= e($m['town']) ?> · <?= e($m['availability']) ?>
                                    <?php if ($m['avg_rating']): ?>
                                        · <span class="stars">★ <?= e($m['avg_rating']) ?></span>
                                        <span class="text-muted">(<?= (int)$m['rating_count'] ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mech-services">
                                    <?php foreach ($services as $sv): ?><span class="chip"><?= e($sv) ?></span><?php endforeach; ?>
                                </div>
                                <button class="btn btn-sm" onclick="openRequest(<?= (int)$m['id'] ?>, '<?= e(addslashes($m['business_name'])) ?>')">Request Service</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Request modal -->
<div class="modal-backdrop" id="req-modal">
    <div class="modal">
        <div class="modal-h">
            <h3>Request service from <span id="req-mech"></span></h3>
            <button class="modal-x" data-modal-close>×</button>
        </div>
        <form method="post" action="<?= e(url('api/booking-actions.php')) ?>" data-validate-form novalidate>
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="mechanic_id" id="req-mid" value="">
            <input type="hidden" name="driver_lat" id="req-lat" value="">
            <input type="hidden" name="driver_lng" id="req-lng" value="">
            <div class="form-grid-2">
                <div class="form-row">
                    <label>Vehicle plate</label>
                    <input type="text" name="vehicle_plate" data-validate="required" required maxlength="20" placeholder="e.g. KCA 123A">
                    <span class="field-error"></span>
                </div>
                <div class="form-row">
                    <label>Vehicle type</label>
                    <select name="vehicle_type" required>
                        <option value="Cars">Cars</option>
                        <option value="Trucks">Trucks</option>
                        <option value="Vans">Vans</option>
                        <option value="Buses">Buses</option>
                        <option value="Motorcycles">Motorcycles</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-row">
                    <label>Breakdown cause</label>
                    <select name="breakdown_cause" required>
                        <option>Engine Failure</option>
                        <option>Electrical Faults</option>
                        <option>Tire Punctures</option>
                        <option>Battery Problems</option>
                        <option>Fuel System Issues</option>
                        <option>Brake Repairs</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Severity</label>
                    <select name="severity" required>
                        <option>Minor</option>
                        <option>Moderate</option>
                        <option>Major</option>
                        <option>Critical</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <label>Breakdown location</label>
                <select name="breakdown_location" required>
                    <option>Highway</option>
                    <option>City Roads</option>
                    <option>Rural Roads</option>
                    <option>Parking Yard</option>
                    <option>Workshop</option>
                </select>
                <div class="field-help">Your GPS coordinates will be attached automatically (if allowed).</div>
            </div>
            <div class="f-between gap-12">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn">Send request</button>
            </div>
        </form>
    </div>
</div>

<?php
$mechMarkers = json_encode(array_values(array_filter(array_map(function ($m) {
    if (!$m['latitude'] || !$m['longitude']) return null;
    return ['id' => (int)$m['id'], 'business_name' => $m['business_name'], 'town' => $m['town'], 'latitude' => $m['latitude'], 'longitude' => $m['longitude']];
}, $mechanics))));

$inline_js = <<<JS
window.openRequest = function (mid, name) {
    document.getElementById('req-mid').value = mid;
    document.getElementById('req-mech').textContent = name;
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            document.getElementById('req-lat').value = pos.coords.latitude.toFixed(7);
            document.getElementById('req-lng').value = pos.coords.longitude.toFixed(7);
        }, function () {
            document.getElementById('req-lat').value = '-1.2921';
            document.getElementById('req-lng').value = '36.8219';
        });
    } else {
        document.getElementById('req-lat').value = '-1.2921';
        document.getElementById('req-lng').value = '36.8219';
    }
    openModal('req-modal');
};

document.getElementById('search').addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#mech-list .mech').forEach(function (el) {
        var hay = el.dataset.name + ' ' + el.dataset.services;
        el.style.display = hay.indexOf(q) >= 0 ? '' : 'none';
    });
});
document.getElementById('town-filter').addEventListener('change', function () {
    var t = this.value;
    document.querySelectorAll('#mech-list .mech').forEach(function (el) {
        el.style.display = (!t || el.dataset.town === t) ? '' : 'none';
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var lat = -1.2921, lng = 36.8219;
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            window.fsServicesMap({ elId:'services-map', lat: pos.coords.latitude, lng: pos.coords.longitude, markers: $mechMarkers });
        }, function () {
            window.fsServicesMap({ elId:'services-map', lat: lat, lng: lng, markers: $mechMarkers });
        });
    } else {
        window.fsServicesMap({ elId:'services-map', lat: lat, lng: lng, markers: $mechMarkers });
    }
});
JS;

$extra_js = ['assets/js/gps-tracking.js'];
?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
