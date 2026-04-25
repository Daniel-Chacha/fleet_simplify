<?php
require_once __DIR__ . '/../config/init.php';
require_role('admin');
$pdo = db();

function fetch_pairs(PDO $pdo, string $sql): array {
    $out = [];
    foreach ($pdo->query($sql) as $row) {
        $vals = array_values($row);
        $label = (string)($vals[0] ?? '');
        $value = (int)($vals[1] ?? 0);
        if ($label === '' || $label === null) $label = 'Unknown';
        $out[] = ['label' => $label, 'value' => $value];
    }
    return $out;
}

/**
 * Generate human-readable insights from a categorical dataset.
 * Returns: ['summary' => '...', 'bullets' => [...], 'recommendation' => '...']
 */
function chart_insights(array $data, string $unit = 'cases'): array {
    if (!$data) return ['summary' => 'No data available yet.', 'bullets' => [], 'recommendation' => 'Collect more data before drawing conclusions.'];
    $total = array_sum(array_column($data, 'value'));
    if ($total === 0) return ['summary' => 'All categories are zero.', 'bullets' => [], 'recommendation' => '—'];

    usort($data, function ($a, $b) { return $b['value'] - $a['value']; });
    $top = $data[0];
    $bottom = end($data);
    $topPct = round(($top['value'] / $total) * 100, 1);
    $bottomPct = round(($bottom['value'] / $total) * 100, 1);
    $cnt = count($data);
    $avg = round($total / $cnt, 1);

    $bullets = [
        "Total observed: <strong>{$total}</strong> {$unit} across <strong>{$cnt}</strong> categories.",
        "Largest category: <strong>" . htmlspecialchars($top['label']) . "</strong> with <strong>{$top['value']}</strong> ({$topPct}% of total).",
        "Smallest category: <strong>" . htmlspecialchars($bottom['label']) . "</strong> with <strong>{$bottom['value']}</strong> ({$bottomPct}% of total).",
        "Average per category: <strong>{$avg}</strong> {$unit}.",
    ];

    // Concentration / pareto check
    $halfShare = 0; $cum = 0;
    foreach ($data as $i => $row) {
        $cum += $row['value'];
        if ($cum >= $total / 2) { $halfShare = $i + 1; break; }
    }
    $concentration = $halfShare <= max(1, ceil($cnt / 4))
        ? "Distribution is <em>highly skewed</em> — the top {$halfShare} categor" . ($halfShare === 1 ? 'y' : 'ies') . " account for half of all {$unit}."
        : "Distribution is fairly balanced — it takes the top {$halfShare} categories to reach 50% of {$unit}.";
    $bullets[] = $concentration;

    $rec = $halfShare <= 2
        ? "Focus mitigation efforts on <strong>" . htmlspecialchars($top['label']) . "</strong> first — addressing it would meaningfully reduce overall {$unit}."
        : "Address multiple categories in parallel; no single category dominates.";

    return [
        'summary' => "{$total} {$unit} recorded; led by <strong>" . htmlspecialchars($top['label']) . "</strong> ({$topPct}%).",
        'bullets' => $bullets,
        'recommendation' => $rec,
    ];
}

function trend_insights(array $monthly): array {
    $values = array_column($monthly, 'value');
    $total = array_sum($values);
    if ($total === 0) return ['summary' => 'No bookings recorded this year yet.', 'bullets' => [], 'recommendation' => '—'];

    $maxIdx = 0; $minIdx = 0;
    for ($i = 1; $i < count($values); $i++) {
        if ($values[$i] > $values[$maxIdx]) $maxIdx = $i;
        if ($values[$i] < $values[$minIdx] && $values[$i] > 0) $minIdx = $i;
    }
    $now = (int)date('n') - 1;
    $recent = array_slice($values, max(0, $now - 2), 3);
    $prior  = array_slice($values, max(0, $now - 5), 3);
    $rSum = array_sum($recent); $pSum = array_sum($prior) ?: 1;
    $delta = round((($rSum - $pSum) / $pSum) * 100, 1);
    $direction = $delta > 5 ? 'rising' : ($delta < -5 ? 'falling' : 'flat');

    $bullets = [
        "Total bookings this year: <strong>{$total}</strong>.",
        "Peak month: <strong>" . htmlspecialchars($monthly[$maxIdx]['label']) . "</strong> with <strong>{$values[$maxIdx]}</strong> bookings.",
        "Quietest month (with activity): <strong>" . htmlspecialchars($monthly[$minIdx]['label']) . "</strong> with <strong>{$values[$minIdx]}</strong>.",
        "Trend over the last 3 months versus the previous 3: <strong>" . ($delta >= 0 ? '+' : '') . $delta . '%</strong> (' . $direction . ').',
    ];
    $rec = $direction === 'rising'
        ? 'Capacity planning: bookings are accelerating. Verify mechanic availability and inventory.'
        : ($direction === 'falling'
            ? 'Activity is declining — consider outreach to drivers or mechanic onboarding incentives.'
            : 'Demand is steady. Maintain current operational capacity.');
    return [
        'summary' => "{$total} bookings YTD, currently {$direction}.",
        'bullets' => $bullets,
        'recommendation' => $rec,
    ];
}

// ---------- Datasets ----------
$causes        = fetch_pairs($pdo, "SELECT breakdown_cause, COUNT(*) FROM bookings GROUP BY breakdown_cause ORDER BY 2 DESC");
$locations     = fetch_pairs($pdo, "SELECT breakdown_location, COUNT(*) FROM bookings GROUP BY breakdown_location ORDER BY 2 DESC");
$vehicleTypes  = fetch_pairs($pdo, "SELECT vehicle_type, COUNT(*) FROM bookings GROUP BY vehicle_type ORDER BY 2 DESC");
$repairMethods = fetch_pairs($pdo, "SELECT repair_method, COUNT(*) FROM bookings WHERE repair_method IS NOT NULL AND repair_method <> '' GROUP BY repair_method ORDER BY 2 DESC");
$severity      = fetch_pairs($pdo, "SELECT severity, COUNT(*) FROM bookings GROUP BY severity ORDER BY 2 DESC");
$downtime      = fetch_pairs($pdo, "SELECT downtime_reason, COUNT(*) FROM bookings WHERE downtime_reason IS NOT NULL AND downtime_reason <> '' GROUP BY downtime_reason ORDER BY 2 DESC");

$partsTally = [];
foreach ($pdo->query("SELECT spare_parts_used FROM bookings WHERE spare_parts_used IS NOT NULL AND spare_parts_used <> ''") as $row) {
    foreach (explode(',', $row['spare_parts_used']) as $p) {
        $p = trim($p);
        if ($p === '') continue;
        $partsTally[$p] = ($partsTally[$p] ?? 0) + 1;
    }
}
arsort($partsTally);
$spareParts = [];
foreach ($partsTally as $k => $v) $spareParts[] = ['label' => $k, 'value' => $v];

$providers = fetch_pairs($pdo, "
    SELECT m.business_name, COUNT(b.id) FROM bookings b JOIN mechanics m ON m.id = b.mechanic_id
    GROUP BY m.id, m.business_name ORDER BY 2 DESC
");
$incidentMap = ['driver_handling'=>'Driver Handling','poor_vehicle_checks'=>'Poor Vehicle Checks','road_conditions'=>'Road Conditions','other'=>'Other'];
$incidents = fetch_pairs($pdo, "SELECT cause, COUNT(*) FROM incident_reports GROUP BY cause ORDER BY 2 DESC");
foreach ($incidents as &$it) { $it['label'] = $incidentMap[$it['label']] ?? $it['label']; } unset($it);

$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthlyMap = array_fill(1, 12, 0);
foreach ($pdo->query("SELECT MONTH(created_at) AS m, COUNT(*) AS c FROM bookings WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY m") as $row) {
    $monthlyMap[(int)$row['m']] = (int)$row['c'];
}
$monthly = [];
for ($i = 1; $i <= 12; $i++) $monthly[] = ['label' => $months[$i-1], 'value' => $monthlyMap[$i]];

$topVehicles = fetch_pairs($pdo, "SELECT vehicle_plate, COUNT(*) FROM bookings GROUP BY vehicle_plate ORDER BY 2 DESC LIMIT 15");

// ---------- Chart definitions: diverse types + per-chart insights ----------
$charts = [
    'causes' => [
        'title'   => 'Breakdown causes',
        'sub'     => 'Why vehicles break down on the road',
        'type'    => 'bar',
        'data'    => $causes,
        'unit'    => 'breakdowns',
        'insight' => chart_insights($causes, 'breakdowns'),
    ],
    'locations' => [
        'title'   => 'Breakdown locations',
        'sub'     => 'Where breakdowns happen',
        'type'    => 'donut',
        'data'    => $locations,
        'unit'    => 'breakdowns',
        'insight' => chart_insights($locations, 'breakdowns'),
    ],
    'vehicleTypes' => [
        'title'   => 'Breakdown by vehicle type',
        'sub'     => 'Which classes of vehicle need help most',
        'type'    => 'hbar',
        'data'    => $vehicleTypes,
        'unit'    => 'breakdowns',
        'insight' => chart_insights($vehicleTypes, 'breakdowns'),
    ],
    'repairMethods' => [
        'title'   => 'Repair methods',
        'sub'     => 'How completed jobs were resolved',
        'type'    => 'donut',
        'data'    => $repairMethods,
        'unit'    => 'completed jobs',
        'insight' => chart_insights($repairMethods, 'completed jobs'),
    ],
    'severity' => [
        'title'   => 'Severity distribution',
        'sub'     => 'How serious are breakdowns on average',
        'type'    => 'pie',
        'data'    => $severity,
        'unit'    => 'cases',
        'insight' => chart_insights($severity, 'cases'),
    ],
    'downtime' => [
        'title'   => 'Downtime drivers',
        'sub'     => 'Top reasons for delay',
        'type'    => 'bar',
        'data'    => $downtime,
        'unit'    => 'cases',
        'insight' => chart_insights($downtime, 'cases'),
    ],
    'spareParts' => [
        'title'   => 'Spare parts usage',
        'sub'     => 'Most-used parts during repair',
        'type'    => 'hbar',
        'data'    => $spareParts,
        'unit'    => 'usages',
        'insight' => chart_insights($spareParts, 'usages'),
    ],
    'providers' => [
        'title'   => 'Service providers caseload',
        'sub'     => 'How work is distributed across mechanics',
        'type'    => 'pie',
        'data'    => $providers,
        'unit'    => 'jobs',
        'insight' => chart_insights($providers, 'jobs'),
    ],
    'incidents' => [
        'title'   => 'Driver incident reports',
        'sub'     => 'Root causes of avoidable breakdowns',
        'type'    => 'donut',
        'data'    => $incidents,
        'unit'    => 'reports',
        'insight' => chart_insights($incidents, 'reports'),
    ],
    'monthly' => [
        'title'   => 'Monthly breakdown trend (' . date('Y') . ')',
        'sub'     => 'Booking volume month-by-month',
        'type'    => 'line',
        'data'    => $monthly,
        'unit'    => 'bookings',
        'keepZeros' => true,
        'insight' => trend_insights($monthly),
    ],
    'topVehicles' => [
        'title'   => 'Breakdown frequency per vehicle (top 15)',
        'sub'     => 'Repeat-offender vehicles in the fleet',
        'type'    => 'hbar',
        'data'    => $topVehicles,
        'unit'    => 'breakdowns',
        'insight' => chart_insights($topVehicles, 'breakdowns'),
    ],
];

// Strip insight payload from the JSON we send to the client (it carries HTML; the server already
// rendered it into the modal markup below). The client only needs data + type for drawing.
$client_charts = [];
foreach ($charts as $k => $c) {
    $client_charts[$k] = [
        'type' => $c['type'],
        'data' => $c['data'],
        'keepZeros' => !empty($c['keepZeros']),
        'title' => $c['title'],
    ];
}

$page_title = 'Reports';
$extra_css = ['assets/css/reports.css'];
include __DIR__ . '/../partials/header.php';
?>
<div class="app">
    <?php include __DIR__ . '/../partials/sidebar-admin.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <div class="content">
            <div class="page-h">
                <h2 style="margin:0">Breakdown analytics</h2>
                <small class="text-muted">Live data — click <strong>Details</strong> on any chart for insights, or <strong>Export</strong> to download a PNG report.</small>
            </div>

            <div class="charts-grid">
                <?php foreach ($charts as $key => $c):
                    $full = ($key === 'topVehicles');
                    $needsLegend = in_array($c['type'], ['pie','donut'], true);
                ?>
                <div class="chart-card" data-chart-card="<?= e($key) ?>" <?= $full ? 'style="grid-column:1/-1"' : '' ?>>
                    <div class="chart-head">
                        <div>
                            <h3><?= e($c['title']) ?></h3>
                            <div class="chart-meta"><?= e($c['sub']) ?></div>
                        </div>
                        <div class="chart-actions">
                            <button class="btn btn-sm btn-outline" type="button" onclick="fsChartDetails('<?= e($key) ?>')">Details</button>
                            <button class="btn btn-sm" type="button" onclick="fsChartExport('<?= e($key) ?>')">Export</button>
                        </div>
                    </div>
                    <canvas data-chart="<?= e($key) ?>"<?= $full ? ' style="height:380px"' : '' ?>></canvas>
                    <?php if ($needsLegend): ?><div class="legend" data-legend="<?= e($key) ?>"></div><?php endif; ?>

                    <!-- Insight payload (read by the modal on Details click; hidden from view) -->
                    <template data-insight>
                        <div class="insight-summary"><?= $c['insight']['summary'] ?></div>
                        <ul class="insight-bullets">
                            <?php foreach ($c['insight']['bullets'] as $b): ?>
                                <li><?= $b ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="insight-rec"><strong>Recommendation:</strong> <?= $c['insight']['recommendation'] ?></div>
                    </template>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<!-- Details modal -->
<div class="modal-backdrop" id="chart-modal">
    <div class="modal modal-lg">
        <div class="modal-h">
            <h3 id="cm-title">Chart details</h3>
            <button class="modal-x" data-modal-close>×</button>
        </div>
        <div class="cm-body" id="cm-body"></div>
        <div class="f-between mt-16">
            <button class="btn btn-outline" type="button" data-modal-close>Close</button>
            <button class="btn" type="button" id="cm-export">Export this chart</button>
        </div>
    </div>
</div>

<!-- Export progress overlay -->
<div class="export-overlay" id="export-overlay">
    <div class="export-card">
        <div class="export-spinner"></div>
        <h3>Preparing your report…</h3>
        <div class="export-bar"><div class="export-bar-fill" id="export-bar-fill"></div></div>
        <div class="export-status" id="export-status">Rendering chart…</div>
    </div>
</div>

<?php
$inline_js = 'window.__charts = ' . json_encode($client_charts, JSON_UNESCAPED_UNICODE) . ';';
$extra_js = ['assets/js/charts.js', 'assets/js/reports.js'];
include __DIR__ . '/../partials/footer.php';
?>
