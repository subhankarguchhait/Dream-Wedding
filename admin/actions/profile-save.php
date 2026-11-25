<?php
/** Update your own name/email (op=details) or password (op=password). */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
$me = require_admin();
require_post_csrf();
$row = db_one('SELECT * FROM admins WHERE id = ?', [(int)$me['id']]);

if (post_str('op', 10) === 'details') {
    $name = post_str('name', 120);
    $email = mb_strtolower(post_str('email', 190));
    $e = [];
    if (mb_strlen($name) < 2) $e['name'] = 'Enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $e['email'] = 'Enter a valid email address.';
    elseif (db_val('SELECT id FROM admins WHERE email = ? AND id <> ?', [$email, (int)$me['id']])) $e['email'] = 'Another administrator already uses this email.';
    if ($email !== $row['email'] && !password_verify((string)($_POST['current_password'] ?? ''), $row['password_hash'])) $e['current_password'] = 'Enter your current password to change your email.';
    if ($e) respond(false, 'Please fix the highlighted fields.', ['errors' => $e]);
    db_update('admins', ['name' => $name, 'email' => $email, 'updated_at' => now()], 'id = ?', [(int)$me['id']]);
    log_activity('Updated own profile');
    flash('success', 'Profile updated.');
    respond(true, 'Profile updated.', ['redirect' => 'admin/profile.php']);
}

if (post_str('op', 10) === 'password') {
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $e = [];
    if (!password_verify($current, $row['password_hash'])) $e['current_password'] = 'Your current password is incorrect.';
    elseif (($p = password_problem($new, $row['email'])) !== '') $e['new_password'] = $p;
    elseif ($new !== (string)($_POST['confirm_password'] ?? '')) $e['confirm_password'] = 'The passwords do not match.';
    elseif (password_verify($new, $row['password_hash'])) $e['new_password'] = 'Choose a password different from the current one.';
    if ($e) respond(false, 'Please fix the highlighted fields.', ['errors' => $e]);
    db_update('admins', ['password_hash' => password_hash($new, PASSWORD_DEFAULT), 'updated_at' => now()], 'id = ?', [(int)$me['id']]);
    session_regenerate_id(true);
    log_activity('Changed own password');
    respond(true, 'Password changed. Use the new password next time you log in.', ['reset' => true]);
}
respond(false, 'Unknown action.');
