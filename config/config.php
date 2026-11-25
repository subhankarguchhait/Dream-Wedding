<?php
/**
 * Application configuration — edit the values in the "EDIT ME" block.
 */
declare(strict_types=1);

/* ============================== EDIT ME ============================== */

// 'development' shows PHP errors on screen; 'production' hides them and logs to storage/logs/.
const APP_ENV = 'development';

// Leave '' to auto-detect (works for http://localhost/dream-spark/ and for a domain root).
// Or set it explicitly, e.g. 'https://www.dreammframmeevent.com/'  (must end with /)
const BASE_URL_OVERRIDE = '';

// One-time key for admin/setup.php (creates the first administrator).
// Change it to a long random phrase BEFORE opening setup.php. Setup refuses to run with the default value.
const SETUP_KEY = 'CHANGE-ME-to-a-long-random-phrase';

// Secret used to sign form timestamps. Change to any long random string.
const APP_SECRET = 'change-this-secret-too-8f3a1c9e7b2d4f60';

const APP_TIMEZONE = 'Asia/Kolkata';

/* =========================== Normally fixed =========================== */

const APP_NAME = 'Dreamm Weddings';
const ROOT_PATH = __DIR__ . '/..';
const CONFIG_PATH = __DIR__;
const UPLOAD_DIR = 'assets/uploads';                 // relative to ROOT_PATH and to the site URL
const UPLOAD_MAX_BYTES = 8 * 1024 * 1024;            // 8 MB per image (wedding photos are large; they are resized on upload)
const VIDEO_MAX_BYTES = 60 * 1024 * 1024;            // 60 MB per hero video (MP4/WebM). Prefer short, compressed clips.
const UPLOAD_MAX_DIMENSION = 2000;                   // raster images are resized to fit 2000px
const LOG_PATH = ROOT_PATH . '/storage/logs';

const SESSION_NAME = 'dfe_session';
const SESSION_IDLE_SECONDS = 2 * 60 * 60;            // admin auto-logout after 2 h idle
const LOGIN_MAX_ATTEMPTS = 5;                        // failed logins allowed…
const LOGIN_WINDOW_MINUTES = 15;                     // …per this many minutes (per IP and per email)
const ENQUIRY_MAX_PER_HOUR = 3;                      // booking enquiries per IP per hour
const ENQUIRY_MIN_SECONDS = 3;                       // a person needs at least this long to fill the form

date_default_timezone_set(APP_TIMEZONE);

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOG_PATH . '/php-errors.log');
}

/** Absolute base URL of the site, always ending with "/". */
function base_url(): string
{
    static $url = null;
    if ($url !== null) return $url;
    if (BASE_URL_OVERRIDE !== '') return $url = rtrim(BASE_URL_OVERRIDE, '/') . '/';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = preg_replace('/[^A-Za-z0-9.:\-\[\]]/', '', $host);      // guard against header injection
    // Project root relative to the document root
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $root = realpath(ROOT_PATH) ?: ROOT_PATH;
    $path = ($docRoot !== '' && str_starts_with($root, $docRoot)) ? substr($root, strlen($docRoot)) : '';
    $path = '/' . trim(str_replace('\\', '/', $path), '/');
    return $url = ($https ? 'https' : 'http') . '://' . $host . rtrim($path, '/') . '/';
}

function is_https(): bool
{
    return str_starts_with(base_url(), 'https://');
}
