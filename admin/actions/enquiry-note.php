<?php
/** Add (op=add) or delete (op=delete) an internal note on an enquiry. Admin session · POST · CSRF. */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
$admin = require_admin();
require_post_csrf();

if (post_str('op', 10) === 'add') {
    $eid = (int)($_POST['enquiry_id'] ?? 0);
    $enq = $eid ? db_one('SELECT id, name FROM enquiries WHERE id = ?', [$eid]) : null;
    if (!$enq) respond(false, 'Enquiry not found.', [], null, 404);
    $note = clean_text((string)($_POST['note'] ?? ''), 2000, true);
    if (mb_strlen($note) < 2) respond(false, 'Please write a note.', ['errors' => ['note' => 'Please write a note.']]);
    db_insert('enquiry_notes', ['enquiry_id' => $eid, 'admin_id' => (int)$admin['id'], 'note' => $note, 'created_at' => now()]);
    db_update('enquiries', ['updated_at' => now()], 'id = ?', [$eid]);
    log_activity('Added enquiry note', $enq['name']);
    respond(true, 'Note added.', ['reload' => true]);
}
if (post_str('op', 10) === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id || !db_val('SELECT id FROM enquiry_notes WHERE id = ?', [$id])) respond(false, 'Note not found.', [], null, 404);
    db_run('DELETE FROM enquiry_notes WHERE id = ?', [$id]);
    respond(true, 'Note deleted.', ['reload' => true]);
}
respond(false, 'Unknown action.', [], null, 400);
