<?php
// FleetSimplify VBMS — PDO connection.
// Edit these constants to match your local MySQL setup, or set the
// matching environment variables (FS_DB_HOST, FS_DB_NAME, FS_DB_USER, FS_DB_PASS).

const FS_DB_HOST = 'localhost';
const FS_DB_NAME = 'fleetsimplify';
const FS_DB_USER = 'root';
const FS_DB_PASS = '';
const FS_DB_CHARSET = 'utf8mb4';

/**
 * Lazy PDO connection.
 * - $soft=false (default): on connection failure, sends 500 and exits — used by app pages.
 * - $soft=true:            re-throws PDOException — used by the landing page so it can
 *                          render with sensible fallbacks even when MySQL is down.
 */
function db(bool $soft = false): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = getenv('FS_DB_HOST') ?: FS_DB_HOST;
    $name = getenv('FS_DB_NAME') ?: FS_DB_NAME;
    $user = getenv('FS_DB_USER') ?: FS_DB_USER;
    $pass = getenv('FS_DB_PASS') !== false ? getenv('FS_DB_PASS') : FS_DB_PASS;

    $dsn = "mysql:host={$host};dbname={$name};charset=" . FS_DB_CHARSET;
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $opts);
    } catch (PDOException $e) {
        error_log('[FleetSimplify] DB connection failed: ' . $e->getMessage());
        if ($soft) throw $e;
        http_response_code(500);
        exit('Database connection error. Check config/db.php and ensure MySQL is reachable.');
    }
    return $pdo;
}
