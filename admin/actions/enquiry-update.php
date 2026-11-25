<?php
/** Change an enquiry's status (POST + CSRF). */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();
require_post_csrf();

$id = (int)($_POST['id'] ?? 0);
$row = $id ? db_one('SELECT id, name, status FROM enquiries WHERE id = ?', [$id]) : null;
if (!$row) respond(false, 'Enquiry not found.', [], null, 404);
$status = post_str('status', 20);
if (!isset(ENQUIRY_STATUSES[$status])) respond(false, 'Choose a valid status.');
db_update('enquiries', ['status' => $status, 'updated_at' => now()], 'id = ?', [$id]);
if ($status !== $row['status']) log_activity('Changed enquiry status', $row['name'] . ' → ' . ENQUIRY_STATUSES[$status]);
respond(true, 'Marked as ' . ENQUIRY_STATUSES[$status] . '.', ['status' => $status, 'label' => ENQUIRY_STATUSES[$status]]);
