<?php
/**
 * Admin authentication & authorisation.
 *  - Passwords: password_hash()/password_verify(), rehash when the algorithm improves.
 *  - Sessions: HttpOnly + SameSite=Lax cookies, strict mode, ID regenerated on login,
 *    browser fingerprint check, idle timeout.
 *  - Brute force: LOGIN_MAX_ATTEMPTS failures per LOGIN_WINDOW_MINUTES per IP and per email.
 *  - Roles: "admin" (everything) and "editor" (content, media, enquiries).
 */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access

const ROLES = ['admin' => 'Administrator', 'editor' => 'Editor'];

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => parse_url(base_url(), PHP_URL_PATH) ?: '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** The signed-in admin row, or null. */
function current_admin(): ?array
{
    static $admin = false;
    if ($admin !== false) return $admin;
    $admin = null;
    $id = $_SESSION['admin_id'] ?? null;
    if (!$id) return null;

    // Idle timeout + fingerprint (stops a stolen cookie being used from a different browser)
    $fp = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . APP_SECRET);
    if (($_SESSION['fp'] ?? '') !== $fp || (time() - ($_SESSION['last_seen'] ?? 0)) > SESSION_IDLE_SECONDS) {
        logout_admin();
        return null;
    }
    $_SESSION['last_seen'] = time();

    $row = db_one("SELECT id, name, email, role, status, last_login FROM admins WHERE id = ?", [(int)$id]);
    if (!$row || $row['status'] !== 'active') { logout_admin(); return null; }
    return $admin = $row;
}

function is_admin_role(): bool { return (current_admin()['role'] ?? '') === 'admin'; }

/** Guard for admin pages. Redirects (or returns 401 JSON) when not allowed. */
function require_admin(string $role = 'editor'): array
{
    try {
        $a = current_admin();
    } catch (DatabaseUnavailable $e) {
        admin_fatal('Database connection failed', 'Check the credentials in <code>config/database.php</code> and that MySQL is running (XAMPP Control Panel → MySQL → Start).');
    }
    if (!$a) {
        if (is_ajax()) json_response(['ok' => false, 'message' => 'Your session has ended. Please log in again.', 'login' => true], 401);
        $next = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'dashboard.php');
        redirect('admin/login.php' . (preg_match('/^[a-z\-]+\.php$/', $next) ? '?next=' . $next : ''));
    }
    if ($role === 'admin' && $a['role'] !== 'admin') {
        if (is_ajax()) json_response(['ok' => false, 'message' => 'Only administrators can do that.'], 403);
        flash('error', 'Only administrators can open that page.');
        redirect('admin/dashboard.php');
    }
    return $a;
}

/* ------------------------------------------------------------ login logic */

function login_is_locked(string $email): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_WINDOW_MINUTES * 60);
    $byIp = (int)db_val('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND success = 0 AND attempted_at >= ?', [client_ip(), $since]);
    $byEmail = (int)db_val('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND success = 0 AND attempted_at >= ?', [mb_strtolower($email), $since]);
    return $byIp >= LOGIN_MAX_ATTEMPTS * 2 || $byEmail >= LOGIN_MAX_ATTEMPTS;
}

function record_login_attempt(string $email, bool $success): void
{
    db_insert('login_attempts', ['email' => mb_substr(mb_strtolower($email), 0, 190), 'ip_address' => client_ip(), 'success' => $success ? 1 : 0, 'attempted_at' => now()]);
    // Housekeeping: keep the table small
    if (random_int(1, 50) === 1) db_run('DELETE FROM login_attempts WHERE attempted_at < ?', [date('Y-m-d H:i:s', time() - 7 * 86400)]);
}

/**
 * Attempt a login. Returns [true, admin] or [false, error message].
 * The same generic message is used for "no such user" and "wrong password".
 */
function attempt_login(string $email, string $password): array
{
    $email = mb_strtolower(trim($email));
    if (login_is_locked($email)) {
        return [false, 'Too many failed attempts. Please wait ' . LOGIN_WINDOW_MINUTES . ' minutes and try again.'];
    }
    $row = db_one('SELECT * FROM admins WHERE email = ?', [$email]);
    // Verify against a dummy hash when the user doesn't exist so timing doesn't reveal valid emails
    $hash = $row['password_hash'] ?? password_hash(random_bytes(12), PASSWORD_DEFAULT);
    $ok = password_verify($password, $hash) && $row && $row['status'] === 'active';
    record_login_attempt($email, $ok);
    if (!$ok) return [false, 'Incorrect email or password.'];

    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        db_update('admins', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [(int)$row['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$row['id'];
    $_SESSION['fp'] = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . APP_SECRET);
    $_SESSION['last_seen'] = time();
    $_SESSION['login_at'] = time();
    unset($_SESSION['csrf_token']);                       // fresh token for the new privilege level
    db_update('admins', ['last_login' => now(), 'updated_at' => now()], 'id = ?', [(int)$row['id']]);
    return [true, $row];
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}

/** Password policy for new/changed passwords. Returns an error message or ''. */
function password_problem(string $pw, string $email = ''): string
{
    if (mb_strlen($pw) < 10) return 'Use at least 10 characters.';
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) return 'Use a mix of letters and numbers.';
    $local = $email !== '' ? explode('@', $email)[0] : '';
    if (strlen($local) >= 4 && stripos($pw, $local) !== false) return 'Don\'t include your email name in the password.';
    return '';
}

/** Render a minimal error page for the admin area and stop. */
function admin_fatal(string $title, string $html): never
{
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>' . e($title) . '</title><body style="font-family:system-ui;background:#f7f1e6;color:#10150f;display:grid;place-items:center;min-height:100vh;margin:0">'
        . '<div style="max-width:560px;background:#fff;padding:32px;border-radius:16px;box-shadow:0 20px 50px -20px rgba(0,0,0,.3)"><h1 style="margin-top:0;color:#0d241d">' . e($title) . '</h1><p>' . $html . '</p></div>';
    exit;
}
