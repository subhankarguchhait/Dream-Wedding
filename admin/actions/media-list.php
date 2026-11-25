<?php
/** JSON list of library images for the image picker (GET, admin only). */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$folder = in_array(get_str('folder'), MEDIA_FOLDERS, true) ? get_str('folder') : '';
$q = get_str('q', 100);
$where = ['1=1'];
$params = [];
if ($folder) { $where[] = 'folder = ?'; $params[] = $folder; }
if ($q !== '') { $where[] = '(original_name LIKE ? OR alt_text LIKE ?)'; array_push($params, "%$q%", "%$q%"); }
$rows = db_all('SELECT id, file_path, original_name, file_name, alt_text, folder, uploaded_at FROM media WHERE ' . implode(' AND ', $where) . ' ORDER BY uploaded_at DESC, id DESC LIMIT 200', $params);
json_response(['ok' => true, 'items' => array_map(fn($r) => [
    'id' => (int)$r['id'], 'path' => $r['file_path'], 'url' => media_url($r['file_path']), 'name' => $r['original_name'] ?: $r['file_name'],
    'alt' => $r['alt_text'], 'folder' => $r['folder'], 'date' => format_date($r['uploaded_at']),
], $rows)]);
