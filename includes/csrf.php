<?php
/**
 * CSRF protection: one random token per session, sent with every POST
 * (hidden field "csrf_token" or header "X-CSRF-Token"), compared in constant time.
 */
declare(strict_types=1);
defined('APP_NAME') || exit; // no direct access

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

/** Stop the request unless it is a POST with a valid CSRF token. */
function require_post_csrf(): void
{
    if (!is_post()) {
        respond(false, 'This action must be submitted with POST.', [], null, 405);
    }
    if (!csrf_valid()) {
        respond(false, 'Your session expired or the form was tampered with. Please refresh the page and try again.', ['csrf' => true], null, 419);
    }
}

/** Signed timestamp for spam timing checks (can't be faked without APP_SECRET). */
function form_stamp(): string
{
    $t = (string)time();
    return $t . '.' . hash_hmac('sha256', $t, APP_SECRET);
}

/** Seconds since the form was rendered, or null if the stamp is invalid. */
function form_stamp_age(string $stamp): ?int
{
    if (!preg_match('/^(\d{9,11})\.([a-f0-9]{64})$/', $stamp, $m)) return null;
    if (!hash_equals(hash_hmac('sha256', $m[1], APP_SECRET), $m[2])) return null;
    return time() - (int)$m[1];
}
