<?php
$active = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <span class="brand"><span class="brand-mark">FS</span> FleetSimplify</span>
    <nav>
        <div class="heading">Admin</div>
        <a href="<?= e(url('admin/dashboard.php')) ?>" class="<?= $active === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= e(url('admin/drivers.php'))   ?>" class="<?= $active === 'drivers.php'   ? 'active' : '' ?>">Drivers</a>
        <a href="<?= e(url('admin/bookings.php'))  ?>" class="<?= $active === 'bookings.php'  ? 'active' : '' ?>">Bookings</a>
        <a href="<?= e(url('admin/mechanics.php')) ?>" class="<?= $active === 'mechanics.php' ? 'active' : '' ?>">Mechanics</a>
        <a href="<?= e(url('admin/feedback.php'))  ?>" class="<?= $active === 'feedback.php'  ? 'active' : '' ?>">Feedback</a>
        <a href="<?= e(url('admin/reports.php'))   ?>" class="<?= $active === 'reports.php'   ? 'active' : '' ?>">Reports</a>
        <div class="logout-link">
            <a href="<?= e(url('auth/logout.php')) ?>" style="color:#FF8554">Sign out</a>
        </div>
    </nav>
</aside>
