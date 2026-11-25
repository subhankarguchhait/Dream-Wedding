<?php
/**
 * Public booking enquiry endpoint (AJAX, falls back to a normal POST).
 * Defences: POST only · CSRF token · honeypot · signed minimum-time check · per-IP hourly limit ·
 * duplicate detection · strict server-side validation against the options set in Admin → Settings · prepared statements.
 */
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

function booking_reply(bool $ok, string $message, array $errors = [], int $status = 0): never
{
    if (is_ajax()) json_response(['ok' => $ok, 'message' => $message, 'errors' => (object)$errors], $status ?: ($ok ? 200 : 422));
    flash($ok ? 'success' : 'error', $message);
    redirect('contact.php' . ($ok ? '?sent=1' : '') . '#contactForm');
}

if (!is_post()) booking_reply(false, 'Please use the booking form.', [], 405);
if (!csrf_valid()) booking_reply(false, 'Your session expired. Please refresh the page and send your enquiry again.', [], 419);

// 1. Honeypot: pretend success so bots learn nothing.
if (post_str('website') !== '') booking_reply(true, 'Thank you! Your enquiry has been received.');

// 2. Timing check with a signed stamp.
$age = form_stamp_age(post_str('form_stamp', 100));
if ($age === null || $age > 86400) booking_reply(false, 'This form has expired. Please refresh the page and try again.', [], 419);
if ($age < ENQUIRY_MIN_SECONDS) booking_reply(false, 'That was very quick! Please take a moment to check your details and submit again.');

// 3. Validation
$maxDays = max(1, min(30, (int)(setting('max_event_days') ?: 7)));
$d = [
    'name' => post_str('name', 100), 'partner_name' => post_str('partner_name', 100),
    'email' => mb_strtolower(post_str('email', 150)), 'phone' => post_str('phone', 20),
    'event_type' => post_str('event_type', 60), 'event_date' => post_str('event_date', 10),
    'event_location' => post_str('event_location', 150), 'service' => post_str('service', 100),
    'budget' => post_str('budget', 80), 'event_days' => post_str('event_days', 3),
    'message' => clean_text((string)($_POST['message'] ?? ''), 3000, true),
];
$e = [];
$nameRe = "/^[\p{L}\p{M} .'\-]+$/u";
if (mb_strlen($d['name']) < 2) $e['name'] = 'Please enter your full name.';
elseif (!preg_match($nameRe, $d['name'])) $e['name'] = 'Please use letters only in your name.';
if ($d['partner_name'] !== '' && !preg_match($nameRe, $d['partner_name'])) $e['partner_name'] = "Please use letters only in your partner's name.";
if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e['email'] = 'Please enter a valid email address.';
$digits = preg_replace('/\D/', '', $d['phone']);
if (!preg_match('/^[+()\d\s\-]{7,20}$/', $d['phone']) || strlen($digits) < 7 || strlen($digits) > 15) $e['phone'] = 'Please enter a valid phone number.';
if (!in_array($d['event_type'], option_list('event_types'), true)) $e['event_type'] = 'Please choose an event type from the list.';
if (!in_array($d['service'], option_list('service_options'), true)) $e['service'] = 'Please choose a service from the list.';
if (!in_array($d['budget'], option_list('budget_options'), true)) $e['budget'] = 'Please choose a budget range.';
$date = DateTimeImmutable::createFromFormat('!Y-m-d', $d['event_date']);
if (!$date || $date->format('Y-m-d') !== $d['event_date']) $e['event_date'] = 'Please choose your event date.';
elseif ($d['event_date'] < date('Y-m-d')) $e['event_date'] = 'The event date cannot be in the past.';
elseif ($d['event_date'] > date('Y-m-d', strtotime('+3 years'))) $e['event_date'] = 'Please choose a date within the next 3 years.';
if (mb_strlen($d['event_location']) < 2) $e['event_location'] = 'Please tell us where the event will be.';
if (!ctype_digit($d['event_days']) || (int)$d['event_days'] < 1 || (int)$d['event_days'] > $maxDays) $e['event_days'] = "Please enter between 1 and $maxDays days.";
if (mb_strlen($d['message']) < 10) $e['message'] = 'Please tell us a little more (at least 10 characters).';
elseif (preg_match_all('#https?://#i', $d['message']) > 3) $e['message'] = 'Please include no more than 3 links.';
if (($_POST['consent'] ?? '') !== '1') $e['consent'] = 'Please confirm we may contact you.';

if ($e) booking_reply(false, 'Please correct the highlighted fields.', $e);
$d['event_days'] = (int)$d['event_days'];

try {
    // 4. Rate limit per IP
    $recent = (int)db_val('SELECT COUNT(*) FROM enquiries WHERE ip_address = ? AND created_at >= ?', [client_ip(), date('Y-m-d H:i:s', time() - 3600)]);
    if ($recent >= ENQUIRY_MAX_PER_HOUR) booking_reply(false, 'You have sent several enquiries recently. Please try again later or call us directly.', [], 429);

    // 5. Duplicate submission within 24 hours (double-click, resubmits)
    $hash = hash('sha256', $d['email'] . '|' . $d['event_date'] . '|' . mb_strtolower(preg_replace('/\s+/', ' ', $d['message'])));
    if (db_val('SELECT id FROM enquiries WHERE message_hash = ? AND created_at >= ?', [$hash, date('Y-m-d H:i:s', time() - 86400)])) {
        booking_reply(true, 'We already have this enquiry — thank you! Our team will be in touch soon.');
    }

    $ref = 'DFE-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    db_insert('enquiries', $d + [
        'enquiry_id' => $ref, 'status' => 'new', 'message_hash' => $hash, 'ip_address' => client_ip(),
        'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255), 'created_at' => now(), 'updated_at' => now(),
    ]);
} catch (DatabaseUnavailable $ex) {
    booking_reply(false, 'Sorry, we could not save your enquiry right now. Please email or call us directly.', [], 503);
} catch (Throwable $ex) {
    log_error('Enquiry save failed: ' . $ex->getMessage());
    booking_reply(false, 'Sorry, something went wrong while sending. Please try again or contact us directly.', [], 500);
}

booking_reply(true, (setting('booking_success') ?: 'Thank you! Your enquiry has been received.') . " Your reference: $ref");
