<?php
$active = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <span class="brand"><span class="brand-mark">FS</span> FleetSimplify</span>
    <nav>
        <div class="heading">Mechanic</div>
        <a href="<?= e(url('mechanic/dashboard.php')) ?>"        class="<?= $active === 'dashboard.php'        ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= e(url('mechanic/notifications.php')) ?>"    class="<?= $active === 'notifications.php'    ? 'active' : '' ?>">Notifications</a>
        <a href="<?= e(url('mechanic/update-business.php')) ?>"  class="<?= $active === 'update-business.php'  ? 'active' : '' ?>">Business Profile</a>
        <a href="<?= e(url('mechanic/feedback.php')) ?>"         class="<?= $active === 'feedback.php'         ? 'active' : '' ?>">Customer Feedback</a>
        <a href="<?= e(url('mechanic/profile.php')) ?>"          class="<?= $active === 'profile.php'          ? 'active' : '' ?>">My Profile</a>
        <div class="logout-link">
            <a href="<?= e(url('auth/logout.php')) ?>" style="color:#FF8554">Sign out</a>
        </div>
    </nav>
</aside>
