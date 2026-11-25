<?php
/** Create or update an administrator account (administrators only). */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
$me = require_admin('admin');
require_post_csrf();

$id = (int)($_POST['id'] ?? 0);
$d = ['name' => post_str('name', 120), 'email' => mb_strtolower(post_str('email', 190)), 'role' => post_str('role', 10), 'status' => post_str('status', 10)];
$pw = (string)($_POST['password'] ?? '');
$e = [];
if (mb_strlen($d['name']) < 2) $e['name'] = 'Enter a name.';
if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e['email'] = 'Enter a valid email address.';
elseif (db_val('SELECT id FROM admins WHERE email = ? AND id <> ?', [$d['email'], $id])) $e['email'] = 'Another administrator already uses this email.';
if (!isset(ROLES[$d['role']])) $d['role'] = 'editor';
if (!in_array($d['status'], ['active', 'disabled'], true)) $d['status'] = 'active';
if (!$id || $pw !== '') { $problem = password_problem($pw, $d['email']); if ($problem) $e['password'] = $problem; }
if ($id === (int)$me['id'] && ($d['role'] !== 'admin' || $d['status'] !== 'active')) $e['role'] = 'You can\'t demote or disable your own account.';
if ($e) respond(false, 'Please fix the highlighted fields.', ['errors' => $e]);

if ($id) {
    if (!db_val('SELECT id FROM admins WHERE id = ?', [$id])) respond(false, 'Administrator not found.', [], null, 404);
    $data = $d + ['updated_at' => now()];
    if ($pw !== '') $data['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    db_update('admins', $data, 'id = ?', [$id]);
    log_activity('Updated administrator', $d['email']);
    flash('success', "{$d['name']}'s account was updated.");
} else {
    db_insert('admins', $d + ['password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'created_at' => now(), 'updated_at' => now()]);
    log_activity('Added administrator', $d['email']);
    flash('success', "{$d['name']} can now log in with {$d['email']}.");
}
respond(true, 'Saved.', ['redirect' => 'admin/settings.php#admins']);
