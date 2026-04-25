<?php
// FleetSimplify VBMS — application init: session, security helpers, common utilities.
// Include this at the top of every PHP page.

require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------
// Session — secure cookie params, idle timeout 30 min.
// ---------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('FSSESSID');
    session_start();
}

const FS_SESSION_TIMEOUT = 1800; // 30 minutes
if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > FS_SESSION_TIMEOUT) {
    $role = $_SESSION['role'] ?? null;
    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['flash_error'] = 'Your session timed out. Please sign in again.';
    redirect_to_login($role);
}
$_SESSION['last_activity'] = time();

// ---------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(?string $token): void {
    if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function flash(string $key, string $msg): void {
    $_SESSION['flash_' . $key] = $msg;
}
function get_flash(string $key): ?string {
    $k = 'flash_' . $key;
    if (!isset($_SESSION[$k])) return null;
    $v = $_SESSION[$k];
    unset($_SESSION[$k]);
    return $v;
}

function validate_email(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}
function validate_mobile(string $m): bool {
    return (bool)preg_match('/^(07|01)[0-9]{8}$/', $m);
}

/**
 * Returns null if the password meets all rules, or a human message describing
 * the first failed rule. Used by registration + password-change handlers so the
 * server enforces the same requirements that main.js shows live.
 */
function validate_password(string $pw): ?string {
    if (strlen($pw) < 8)                return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/',     $pw)) return 'Password must contain an uppercase letter.';
    if (!preg_match('/[a-z]/',     $pw)) return 'Password must contain a lowercase letter.';
    if (!preg_match('/[0-9]/',     $pw)) return 'Password must contain a number.';
    if (!preg_match('/[^A-Za-z0-9]/', $pw)) return 'Password must contain a special character.';
    return null;
}

function gen_booking_number(): string {
    $year = date('Y');
    $st = db()->prepare(
        "SELECT booking_number FROM bookings
         WHERE booking_number LIKE :p ORDER BY id DESC LIMIT 1"
    );
    $st->execute([':p' => "BK-{$year}-%"]);
    $last = $st->fetchColumn();
    $next = 1;
    if ($last && preg_match('/BK-\d{4}-(\d{4})/', $last, $m)) {
        $next = ((int)$m[1]) + 1;
    }
    return sprintf('BK-%s-%04d', $year, $next);
}

function gen_txn_ref(): string {
    return 'TXN-' . strtoupper(bin2hex(random_bytes(8)));
}

function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
    return 2 * $R * asin(min(1.0, sqrt($a)));
}

function fmt_kes(float $n): string {
    return 'KES ' . number_format($n, 2);
}

/**
 * Resolve page/per-page from $_GET in a safe way.
 * - $allowed: list of valid per-page sizes (default 10/25/50/100).
 * - Returns ['page' => int, 'per' => int, 'pages' => int, 'offset' => int, 'total' => int]
 *   (caller passes total).
 */
function paginate(int $total, array $allowed = [10, 25, 50, 100], int $default = 10): array {
    $per = (int)($_GET['per'] ?? $default);
    if (!in_array($per, $allowed, true)) $per = $default;
    $pages = max(1, (int)ceil($total / $per));
    $page  = max(1, (int)($_GET['page'] ?? 1));
    if ($page > $pages) $page = $pages;
    return [
        'total'  => $total,
        'per'    => $per,
        'page'   => $page,
        'pages'  => $pages,
        'offset' => ($page - 1) * $per,
        'allowed'=> $allowed,
    ];
}

function status_badge(string $status): string {
    $map = [
        'new'         => ['New',         'badge-warning'],
        'in_progress' => ['In Progress', 'badge-info'],
        'completed'   => ['Completed',   'badge-grey'],
        'rejected'    => ['Rejected',    'badge-danger'],
        'pending'     => ['Pending',     'badge-warning'],
        'approved'    => ['Approved',    'badge-success'],
        'failed'      => ['Failed',      'badge-danger'],
    ];
    [$label, $cls] = $map[$status] ?? [ucfirst($status), 'badge-grey'];
    return '<span class="badge ' . $cls . '">' . e($label) . '</span>';
}

// ---------------------------------------------------------------
// Auth helpers
// ---------------------------------------------------------------
function current_user(): ?array {
    if (!empty($_SESSION['role']) && !empty($_SESSION['uid'])) {
        return [
            'role' => $_SESSION['role'],
            'uid'  => (int)$_SESSION['uid'],
            'name' => $_SESSION['name'] ?? '',
        ];
    }
    return null;
}

function base_url(): string {
    // Resolve project base path (handles Apache subdirs and PHP built-in server).
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $segments = explode('/', trim($script, '/'));
    // Drop the trailing file + its parent (e.g. /auth/login.php → strip 2 parts).
    // We just want the application root.
    $known = ['auth', 'user', 'mechanic', 'admin', 'api', 'partials'];
    $base = '';
    $parts = [];
    foreach ($segments as $seg) {
        if (in_array($seg, $known, true) || preg_match('/\.php$/', $seg)) break;
        $parts[] = $seg;
    }
    $base = '/' . implode('/', $parts);
    if ($base === '/') return '';
    return rtrim($base, '/');
}

function url(string $path): string {
    return base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): void {
    header('Location: ' . url($path));
    exit;
}

function redirect_to_login(?string $role): void {
    switch ($role) {
        case 'mechanic': redirect('auth/mechanic-login.php'); break;
        case 'admin':    redirect('auth/admin-login.php');    break;
        default:         redirect('auth/user-login.php');
    }
}

function require_role(string $role): void {
    $u = current_user();
    if (!$u || $u['role'] !== $role) {
        redirect_to_login($role);
    }
}

function require_any_role(): array {
    $u = current_user();
    if (!$u) redirect_to_login(null);
    return $u;
}

// ---------------------------------------------------------------
// JSON helper for API endpoints
// ---------------------------------------------------------------
function json_response($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
