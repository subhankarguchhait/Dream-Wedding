<?php
/** Delete an enquiry and its notes (POST + CSRF). */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();
require_post_csrf();

$id = (int)($_POST['id'] ?? 0);
$row = $id ? db_one('SELECT id, name, enquiry_id FROM enquiries WHERE id = ?', [$id]) : null;
if (!$row) respond(false, 'Enquiry not found.', [], null, 404);
db_tx(function () use ($id) {
    db_run('DELETE FROM enquiry_notes WHERE enquiry_id = ?', [$id]); // also ON DELETE CASCADE
    db_run('DELETE FROM enquiries WHERE id = ?', [$id]);
});
log_activity('Deleted enquiry', $row['name'] . ' (' . $row['enquiry_id'] . ')');
if (!empty($_POST['back'])) { flash('success', 'Enquiry deleted.'); respond(true, 'Enquiry deleted.', ['redirect' => 'admin/enquiries.php']); }
respond(true, 'Enquiry deleted.', ['remove' => true]);
