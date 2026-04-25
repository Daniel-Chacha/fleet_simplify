<?php
// Renders the topbar with greeting + (optional) bell + sign out.
// Expects: $u = current_user(); optional $show_bell = true/false; optional $page_title.
$u = $u ?? current_user();
$show_bell = $show_bell ?? false;
$page_title = $page_title ?? '';
?>
<div class="topbar">
    <div class="greeting">
        <?= e($page_title ?: 'Dashboard') ?>
        <small>Hello, <?= e($u['name'] ?? '') ?></small>
    </div>
    <div class="topbar-actions">
        <?php if ($show_bell): ?>
            <a class="bell" id="notif-bell" href="<?= e(url('mechanic/notifications.php')) ?>" title="Notifications">
                🔔<span class="count" style="display:none">0</span>
            </a>
        <?php endif; ?>
        <a class="btn btn-outline btn-sm" href="<?= e(url('auth/logout.php')) ?>">Sign out</a>
    </div>
</div>
