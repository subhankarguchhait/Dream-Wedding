<?php
/** Remove an administrator (administrators only; you can't remove yourself or the last admin). */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
$me = require_admin('admin');
require_post_csrf();

$id = (int)($_POST['id'] ?? 0);
$u = $id ? db_one('SELECT id, name, email, role FROM admins WHERE id = ?', [$id]) : null;
if (!$u) respond(false, 'Administrator not found.', [], null, 404);
if ($id === (int)$me['id']) respond(false, 'You can\'t remove your own account.');
if ($u['role'] === 'admin' && (int)db_val("SELECT COUNT(*) FROM admins WHERE role = 'admin' AND status = 'active'") <= 1) respond(false, 'At least one active administrator must remain.');
db_run('DELETE FROM admins WHERE id = ?', [$id]);
log_activity('Removed administrator', $u['email']);
respond(true, "{$u['name']} no longer has access.", ['remove' => true]);
