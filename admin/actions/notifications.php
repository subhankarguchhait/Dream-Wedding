<?php
/** Polled by the admin bell every 60 s. */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();
$items = db_all("SELECT id, name, event_type, message, created_at FROM enquiries WHERE status = 'new' ORDER BY created_at DESC LIMIT 5");
json_response(['ok' => true, 'count' => (int)db_val("SELECT COUNT(*) FROM enquiries WHERE status = 'new'"), 'items' => array_map(fn($r) => [
    'id' => (int)$r['id'], 'title' => $r['name'] . ' · ' . ($r['event_type'] ?: 'Enquiry'), 'text' => time_ago($r['created_at']) . ' — ' . mb_substr($r['message'], 0, 80),
], $items)]);
