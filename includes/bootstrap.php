<?php
/**
 * Loaded first by every page and endpoint.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/defaults.php';
require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

start_secure_session();

// Baseline security headers (also set in .htaccess for static files)
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
