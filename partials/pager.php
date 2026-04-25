<?php
// Reusable pagination component.
// Expects $pg (array from paginate()), and optional $base_qs to merge with the link.
// $base_qs is an associative array of additional query-string parameters to preserve
// (e.g. tab=approved); pass [] if none.
$pg = $pg ?? null;
$base_qs = $base_qs ?? [];
if (!$pg) return;

$build = function (array $extra) use ($base_qs) {
    return '?' . http_build_query(array_merge($base_qs, $extra));
};

$first = max(1, $pg['page'] - 2);
$last  = min($pg['pages'], $pg['page'] + 2);

$range_from = $pg['total'] === 0 ? 0 : ($pg['offset'] + 1);
$range_to   = min($pg['total'], $pg['offset'] + $pg['per']);
?>
<div class="pager-bar">
    <div class="pager-info">
        Show
        <select class="pager-per" onchange="window.location.href=this.value">
            <?php foreach ($pg['allowed'] as $p): ?>
                <option value="<?= e($build(['per' => $p, 'page' => 1])) ?>" <?= $p === $pg['per'] ? 'selected' : '' ?>><?= $p ?></option>
            <?php endforeach; ?>
        </select>
        entries · showing <strong><?= $range_from ?>&ndash;<?= $range_to ?></strong> of <strong><?= $pg['total'] ?></strong>
    </div>
    <div class="pager">
        <?php if ($pg['page'] > 1): ?>
            <a href="<?= e($build(['page' => 1, 'per' => $pg['per']])) ?>">«</a>
            <a href="<?= e($build(['page' => $pg['page'] - 1, 'per' => $pg['per']])) ?>">‹</a>
        <?php else: ?>
            <span class="disabled">«</span><span class="disabled">‹</span>
        <?php endif; ?>

        <?php if ($first > 1): ?>
            <a href="<?= e($build(['page' => 1, 'per' => $pg['per']])) ?>">1</a>
            <?php if ($first > 2): ?><span class="dots">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $first; $i <= $last; $i++): ?>
            <?php if ($i === $pg['page']): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= e($build(['page' => $i, 'per' => $pg['per']])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($last < $pg['pages']): ?>
            <?php if ($last < $pg['pages'] - 1): ?><span class="dots">…</span><?php endif; ?>
            <a href="<?= e($build(['page' => $pg['pages'], 'per' => $pg['per']])) ?>"><?= $pg['pages'] ?></a>
        <?php endif; ?>

        <?php if ($pg['page'] < $pg['pages']): ?>
            <a href="<?= e($build(['page' => $pg['page'] + 1, 'per' => $pg['per']])) ?>">›</a>
            <a href="<?= e($build(['page' => $pg['pages'], 'per' => $pg['per']])) ?>">»</a>
        <?php else: ?>
            <span class="disabled">›</span><span class="disabled">»</span>
        <?php endif; ?>
    </div>
</div>
