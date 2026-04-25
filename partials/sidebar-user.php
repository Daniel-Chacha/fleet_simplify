<?php
$active = basename($_SERVER['PHP_SELF']);
$u = current_user();
?>
<aside class="sidebar">
    <span class="brand"><span class="brand-mark">FS</span> FleetSimplify</span>
    <nav>
        <div class="heading">Driver</div>
        <a href="<?= e(url('user/dashboard.php')) ?>"      class="<?= $active === 'dashboard.php'      ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= e(url('user/find-services.php')) ?>"  class="<?= $active === 'find-services.php'  ? 'active' : '' ?>">Find On-Road Services</a>
        <a href="<?= e(url('user/my-requests.php')) ?>"    class="<?= $active === 'my-requests.php'    ? 'active' : '' ?>">My Requests</a>
        <a href="<?= e(url('user/payment.php')) ?>"        class="<?= $active === 'payment.php'        ? 'active' : '' ?>">Payment</a>
        <a href="<?= e(url('user/profile.php')) ?>"        class="<?= $active === 'profile.php'        ? 'active' : '' ?>">My Profile</a>
        <div class="logout-link">
            <a href="<?= e(url('auth/logout.php')) ?>" style="color:#FF8554">Sign out</a>
        </div>
    </nav>
</aside>
