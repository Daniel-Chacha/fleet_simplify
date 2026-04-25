<?php
require_once __DIR__ . '/config/init.php';

// Already signed in? Send to the right dashboard.
$u = current_user();
if ($u) {
    if ($u['role'] === 'user')     redirect('user/dashboard.php');
    if ($u['role'] === 'mechanic') redirect('mechanic/dashboard.php');
    if ($u['role'] === 'admin')    redirect('admin/dashboard.php');
}

// Real numbers from the database for the trust strip. Falls back to demo defaults
// when the DB is unreachable so the landing page is still viewable.
$approvedMechs = 14; $totalDrivers = 20; $completedJobs = 20; $townsCovered = 10;
try {
    $pdo = db(true);
    $approvedMechs = (int)$pdo->query("SELECT COUNT(*) FROM mechanics WHERE status='approved'")->fetchColumn();
    $totalDrivers  = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $completedJobs = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetchColumn();
    $townsCovered  = (int)$pdo->query("SELECT COUNT(DISTINCT town) FROM mechanics WHERE status='approved'")->fetchColumn();
} catch (Throwable $ex) { /* keep fallback values */ }

$flash_success = get_flash('success');
$flash_error   = get_flash('error');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FleetSimplify VBMS — Roadside help in minutes for Kenyan drivers</title>
<meta name="description" content="FleetSimplify connects drivers and mechanics across Kenya: real-time GPS tracking, in-app chat, M-Pesa payments, and full fleet analytics in one platform.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/main.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/landing.css')) ?>">
</head>
<body class="landing-body"
      <?= $flash_success ? 'data-flash-success="' . e($flash_success) . '"' : '' ?>
      <?= $flash_error ? 'data-flash-error="' . e($flash_error) . '"' : '' ?>>
<div id="toast-root"></div>

<!-- ========================= NAV ========================= -->
<nav class="lp-nav">
    <div class="lp-nav-inner">
        <a href="#top" class="brand-fs">
            <span class="brand-mark">FS</span>
            <span class="brand-name">FleetSimplify</span>
            <span class="brand-sub">VBMS</span>
        </a>
        <button class="lp-burger" id="lp-burger" aria-label="Menu">☰</button>
        <div class="lp-nav-links" id="lp-links">
            <a href="#features">Features</a>
            <a href="#how">How it works</a>
            <a href="#roles">For you</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
        </div>
        <div class="lp-nav-cta">
            <button class="btn btn-outline btn-sm" type="button" data-chooser="signin">Sign in</button>
            <button class="btn btn-sm" type="button" data-chooser="register">Get started</button>
        </div>
    </div>
</nav>

<!-- ========================= HERO ========================= -->
<header class="lp-hero" id="top">
    <div class="lp-hero-inner">
        <div>
            <span class="lp-eyebrow"><span class="dot"></span>Trusted by <?= (int)$totalDrivers ?>+ drivers across Kenya</span>
            <h1>Get back on the road, <span class="accent">fast.</span></h1>
            <p class="lead">FleetSimplify is the all-in-one breakdown management platform that connects drivers with verified mechanics — live GPS tracking, real-time chat, instant M-Pesa payments, and fleet analytics. From a flat tire to a full engine rebuild, help is one tap away.</p>
            <div class="lp-hero-cta">
                <button class="btn" type="button" data-chooser="register">Get started — it's free →</button>
                <button class="btn btn-outline" type="button" data-chooser="signin">Sign in</button>
            </div>
            <div class="lp-hero-stats">
                <div><span class="num"><?= (int)$approvedMechs ?>+</span><span class="lbl">Verified mechanics</span></div>
                <div><span class="num"><?= (int)$townsCovered ?></span><span class="lbl">Towns covered</span></div>
                <div><span class="num">24 / 7</span><span class="lbl">Always on call</span></div>
                <div><span class="num"><?= (int)$completedJobs ?>+</span><span class="lbl">Jobs completed</span></div>
            </div>
        </div>
        <div class="lp-hero-image">
            <img src="https://images.unsplash.com/photo-1486006920555-c77dcf18193c?auto=format&fit=crop&w=1200&q=70"
                 alt="Mechanic working on a vehicle"
                 loading="eager">
            <div class="lp-hero-card float-tr">
                <div class="icon">★</div>
                <div>
                    <div class="t">4.6 / 5 average</div>
                    <div class="s">across 15+ ratings</div>
                </div>
            </div>
            <div class="lp-hero-card float-bl">
                <div class="icon">G</div>
                <div>
                    <div class="t">Live GPS tracking</div>
                    <div class="s">See your mechanic en-route</div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ========================= FEATURES ========================= -->
<section class="lp-section" id="features">
    <div class="lp-section-inner">
        <h2>Everything you need on one platform</h2>
        <p class="sub">Built for Kenyan roads — from highway breakdowns to workshop scheduling. No app downloads required.</p>
        <div class="lp-features">
            <div class="lp-feat">
                <div class="icon">G</div>
                <h3>Live GPS tracking</h3>
                <p>See your mechanic on a real-time OpenStreetMap with continuously updated ETA, so you know exactly when help arrives.</p>
            </div>
            <div class="lp-feat">
                <div class="icon">C</div>
                <h3>Real-time chat</h3>
                <p>WhatsApp-style messaging between driver and mechanic — share details, photos, and location updates without leaving the platform.</p>
            </div>
            <div class="lp-feat">
                <div class="icon">$</div>
                <h3>Multi-method payments</h3>
                <p>Pay your way — M-Pesa, bank transfer, or card. Server-side amount validation and unique transaction references keep every shilling safe.</p>
            </div>
            <div class="lp-feat">
                <div class="icon">A</div>
                <h3>Fleet analytics</h3>
                <p>Eleven live charts on the admin reports page — breakdown causes, monthly trends, repair methods, downtime drivers, and more.</p>
            </div>
        </div>
    </div>
</section>

<!-- ========================= HOW IT WORKS ========================= -->
<section class="lp-section alt" id="how">
    <div class="lp-section-inner">
        <h2>From breakdown to repair in 4 steps</h2>
        <p class="sub">A streamlined flow whether you're stranded on the highway or scheduling a workshop visit.</p>
        <div class="lp-how">
            <div class="lp-step">
                <h3>Sign up</h3>
                <p>Create a free driver account in 30 seconds — name, mobile, password.</p>
            </div>
            <div class="lp-step">
                <h3>Request a mechanic</h3>
                <p>Pick from approved mechanics nearby, attach your GPS coords, and submit your breakdown details.</p>
            </div>
            <div class="lp-step">
                <h3>Track &amp; chat</h3>
                <p>Watch your mechanic approach on a live map and chat in real time as they get ready for the job.</p>
            </div>
            <div class="lp-step">
                <h3>Pay &amp; rate</h3>
                <p>Pay via M-Pesa, bank, or card directly in-app, then leave a star rating to help the next driver.</p>
            </div>
        </div>
    </div>
</section>

<!-- ========================= ROLES ========================= -->
<section class="lp-section" id="roles">
    <div class="lp-section-inner">
        <h2>Choose the experience that fits you</h2>
        <p class="sub">FleetSimplify works for drivers, mechanics, and administrators — three tailored dashboards in one platform.</p>
        <div class="lp-roles">
            <article class="lp-role">
                <div class="image">
                    <span class="role-tag">For drivers</span>
                    <img src="https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?auto=format&fit=crop&w=900&q=70" alt="Driver behind the wheel" loading="lazy">
                </div>
                <div class="body">
                    <h3>Drivers &amp; fleet owners</h3>
                    <ul class="lp-role-features">
                        <li>Find approved mechanics near you</li>
                        <li>Track ETA on a live map</li>
                        <li>Chat in real time</li>
                        <li>Pay with M-Pesa, bank, or card</li>
                    </ul>
                    <div class="actions">
                        <a class="btn btn-sm" href="<?= e(url('auth/user-register.php')) ?>">Register</a>
                        <a class="btn btn-outline btn-sm" href="<?= e(url('auth/user-login.php')) ?>">Sign in</a>
                    </div>
                </div>
            </article>

            <article class="lp-role">
                <div class="image">
                    <span class="role-tag">For mechanics</span>
                    <img src="https://images.unsplash.com/photo-1632823469850-2f77dd9c7f93?auto=format&fit=crop&w=900&q=70" alt="Mechanic at a garage" loading="lazy">
                </div>
                <div class="body">
                    <h3>Mechanics &amp; garages</h3>
                    <ul class="lp-role-features">
                        <li>Receive instant booking alerts</li>
                        <li>Pick from 18+ services to offer</li>
                        <li>Build a verified reputation</li>
                        <li>Get paid faster</li>
                    </ul>
                    <div class="actions">
                        <a class="btn btn-sm" href="<?= e(url('auth/mechanic-register.php')) ?>">List your business</a>
                        <a class="btn btn-outline btn-sm" href="<?= e(url('auth/mechanic-login.php')) ?>">Sign in</a>
                    </div>
                </div>
            </article>

            <article class="lp-role">
                <div class="image">
                    <span class="role-tag">For admins</span>
                    <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=900&q=70" alt="Analytics dashboard on a laptop" loading="lazy">
                </div>
                <div class="body">
                    <h3>Administrators</h3>
                    <ul class="lp-role-features">
                        <li>Approve mechanics &amp; assign jobs</li>
                        <li>11 live analytics charts</li>
                        <li>Export reports as PNG</li>
                        <li>Manage drivers &amp; bookings</li>
                    </ul>
                    <div class="actions">
                        <a class="btn btn-sm" href="<?= e(url('auth/admin-login.php')) ?>">Admin sign in</a>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- ========================= STATS STRIP ========================= -->
<section class="lp-stats-strip">
    <div class="grid">
        <div><span class="num"><?= (int)$totalDrivers ?>+</span><div class="lbl">Drivers using FleetSimplify</div></div>
        <div><span class="num"><?= (int)$approvedMechs ?>+</span><div class="lbl">Verified mechanics</div></div>
        <div><span class="num"><?= (int)$completedJobs ?>+</span><div class="lbl">Jobs completed</div></div>
        <div><span class="num">24 / 7</span><div class="lbl">Always on standby</div></div>
    </div>
</section>

<!-- ========================= TESTIMONIALS ========================= -->
<section class="lp-section alt">
    <div class="lp-section-inner">
        <h2>What our community says</h2>
        <p class="sub">Real feedback from drivers and mechanics on FleetSimplify.</p>
        <div class="lp-testimonials">
            <div class="lp-testi">
                <div class="stars">★★★★★</div>
                <p class="quote">"Towed quickly and got me back on the road same day. Top notch."</p>
                <div class="who">
                    <img class="avatar" src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=70" alt="James K.">
                    <div><div class="name">James Kariuki</div><div class="role">Driver, Nairobi</div></div>
                </div>
            </div>
            <div class="lp-testi">
                <div class="stars">★★★★★</div>
                <p class="quote">"Arrived in 20 minutes — saved my trip. The map tracking made it so easy to find each other."</p>
                <div class="who">
                    <img class="avatar" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=70" alt="Grace A.">
                    <div><div class="name">Grace Achieng</div><div class="role">Driver, Mombasa</div></div>
                </div>
            </div>
            <div class="lp-testi">
                <div class="stars">★★★★★</div>
                <p class="quote">"The notification beep is a lifesaver — I never miss a request, even when working. My business has grown 30% since joining."</p>
                <div class="who">
                    <img class="avatar" src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=70" alt="Stephen K.">
                    <div><div class="name">Stephen Karanja</div><div class="role">Mechanic, AutoHub Garage</div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========================= ABOUT ========================= -->
<section class="lp-section" id="about">
    <div class="lp-section-inner">
        <div class="lp-about-grid">
            <div class="lp-about-image">
                <img src="https://images.unsplash.com/photo-1542013936693-884638332954?auto=format&fit=crop&w=1100&q=70" alt="Vehicle on a Kenyan highway" loading="lazy">
            </div>
            <div class="lp-about-text">
                <h2>About FleetSimplify</h2>
                <p style="color:var(--grey-700);font-size:1.05rem;line-height:1.6">FleetSimplify VBMS was built to solve a uniquely Kenyan problem: drivers stranded on highways or rural roads with no easy way to reach a trusted mechanic. We connect verified roadside professionals with drivers who need them — backed by live tracking, secure payments, and transparent ratings.</p>
                <div class="pillars">
                    <div class="pillar">
                        <h4>🛡️ Verified mechanics</h4>
                        <p>Every business is admin-approved before going live, with licence number on file.</p>
                    </div>
                    <div class="pillar">
                        <h4>🔒 Secure by default</h4>
                        <p>Bcrypt-hashed passwords, CSRF protection, and PCI-conscious payment handling.</p>
                    </div>
                    <div class="pillar">
                        <h4>📍 Built for Kenya</h4>
                        <p>M-Pesa integration, KES pricing, and coverage across <?= (int)$townsCovered ?>+ towns.</p>
                    </div>
                    <div class="pillar">
                        <h4>📊 Data-driven</h4>
                        <p>Eleven live analytics charts help fleet operators understand breakdown patterns.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========================= CONTACT ========================= -->
<section class="lp-section alt" id="contact">
    <div class="lp-section-inner">
        <h2>Get in touch</h2>
        <p class="sub">Got a question, partnership idea, or feedback? We'd love to hear from you.</p>
        <div class="lp-contact-info">
            <h3>Reach out</h3>
            <p>We typically reply within one business day. For urgent breakdown assistance, please use the in-app request flow after signing in.</p>
            <div class="ci-grid">
                <div class="ci-item">
                    <div class="ci-icon">@</div>
                    <div>
                        <div class="ci-label">Email</div>
                        <div class="ci-value">hello@fleetsimplify.local</div>
                    </div>
                </div>
                <div class="ci-item">
                    <div class="ci-icon">☎</div>
                    <div>
                        <div class="ci-label">Phone</div>
                        <div class="ci-value">+254 700 000 000</div>
                    </div>
                </div>
                <div class="ci-item">
                    <div class="ci-icon">⌖</div>
                    <div>
                        <div class="ci-label">Office</div>
                        <div class="ci-value">Westlands, Nairobi, Kenya</div>
                    </div>
                </div>
                <div class="ci-item">
                    <div class="ci-icon">⏰</div>
                    <div>
                        <div class="ci-label">Support hours</div>
                        <div class="ci-value">24 / 7 in-app, Mon–Fri 8am–6pm office</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========================= FOOTER ========================= -->
<footer class="lp-footer">
    <div class="lp-footer-inner">
        <div>
            <span class="brand-fs on-dark">
                <span class="brand-mark">FS</span>
                <span class="brand-name">FleetSimplify</span>
                <span class="brand-sub">VBMS</span>
            </span>
            <p class="desc">A Vehicle Breakdown Management System connecting drivers, mechanics, and fleet operations across Kenya — built for safer roads.</p>
        </div>
        <div>
            <h5>Platform</h5>
            <ul>
                <li><a href="#features">Features</a></li>
                <li><a href="#how">How it works</a></li>
                <li><a href="#about">About us</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </div>
        <div>
            <h5>For drivers</h5>
            <ul>
                <li><a href="<?= e(url('auth/user-register.php')) ?>">Create account</a></li>
                <li><a href="<?= e(url('auth/user-login.php')) ?>">Sign in</a></li>
                <li><a href="#how">How it works</a></li>
            </ul>
        </div>
        <div>
            <h5>For mechanics</h5>
            <ul>
                <li><a href="<?= e(url('auth/mechanic-register.php')) ?>">List your business</a></li>
                <li><a href="<?= e(url('auth/mechanic-login.php')) ?>">Sign in</a></li>
                <li><a href="<?= e(url('auth/admin-login.php')) ?>">Admin login</a></li>
            </ul>
        </div>
    </div>
    <div class="copy">© <?= date('Y') ?> FleetSimplify · Built for safer roads in Kenya · v1.0</div>
</footer>

<!-- ========================= AUTH ROLE CHOOSER ========================= -->
<div class="auth-chooser" id="auth-chooser" role="dialog" aria-modal="true" aria-labelledby="ac-title">
    <div class="auth-chooser-box">
        <button class="auth-chooser-x" type="button" aria-label="Close" onclick="closeChooser()">×</button>
        <div class="auth-chooser-h">
            <span class="brand-fs lg">
                <span class="brand-mark">FS</span>
                <span class="brand-name">FleetSimplify</span>
                <span class="brand-sub">VBMS</span>
            </span>
            <h2 id="ac-title">Choose how you'd like to continue</h2>
            <p id="ac-sub">Pick the role that matches you — drivers, mechanics, and admins each have a tailored experience.</p>
        </div>

        <div class="auth-chooser-grid">
            <div class="ac-card driver">
                <div class="ac-icon">D</div>
                <h3>I'm a Driver</h3>
                <p>Find help on the road, track your mechanic live, and pay in-app.</p>
                <div class="ac-buttons">
                    <a class="btn"          href="<?= e(url('auth/user-login.php')) ?>">Sign in</a>
                    <a class="btn btn-outline" href="<?= e(url('auth/user-register.php')) ?>">Register</a>
                </div>
            </div>

            <div class="ac-card mechanic">
                <div class="ac-icon">M</div>
                <h3>I'm a Mechanic</h3>
                <p>Receive instant booking alerts and grow your roadside business.</p>
                <div class="ac-buttons">
                    <a class="btn"          href="<?= e(url('auth/mechanic-login.php')) ?>">Sign in</a>
                    <a class="btn btn-outline" href="<?= e(url('auth/mechanic-register.php')) ?>">Register</a>
                </div>
            </div>

            <div class="ac-card admin">
                <div class="ac-icon">A</div>
                <h3>I'm an Administrator</h3>
                <p>Approve mechanics, oversee bookings, and explore live analytics.</p>
                <div class="ac-buttons">
                    <a class="btn btn-block" href="<?= e(url('auth/admin-login.php')) ?>">Sign in</a>
                </div>
                <div class="ac-note">Admin accounts are created internally — contact your operations lead for access.</div>
            </div>
        </div>
    </div>
</div>

<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var burger = document.getElementById('lp-burger');
    var links  = document.getElementById('lp-links');
    if (burger && links) {
        burger.addEventListener('click', function () { links.classList.toggle('is-open'); });
        links.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () { links.classList.remove('is-open'); });
        });
    }
    // Smooth-scroll for in-page anchors
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var id = a.getAttribute('href');
            if (id === '#' || id === '#top') return;
            var t = document.querySelector(id);
            if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });

    // Role chooser modal
    var chooser = document.getElementById('auth-chooser');
    var title   = document.getElementById('ac-title');
    var sub     = document.getElementById('ac-sub');
    function openChooser(mode) {
        if (mode === 'register') {
            title.textContent = "Create your FleetSimplify account";
            sub.textContent   = "Choose the role that fits — drivers and mechanics register here, admins are added internally.";
        } else {
            title.textContent = "Welcome back — sign in";
            sub.textContent   = "Pick your role to continue to the right dashboard.";
        }
        chooser.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
    window.closeChooser = function () {
        chooser.classList.remove('is-open');
        document.body.style.overflow = '';
    };
    document.querySelectorAll('[data-chooser]').forEach(function (el) {
        el.addEventListener('click', function () { openChooser(el.dataset.chooser); });
    });
    chooser.addEventListener('click', function (e) {
        if (e.target === chooser) closeChooser();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && chooser.classList.contains('is-open')) closeChooser();
    });
});
</script>
</body>
</html>
