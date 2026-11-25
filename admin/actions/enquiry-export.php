<?php
/** Download the currently filtered enquiries as CSV (opens in Excel). Read-only, admin session required. */
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/enquiry-filters.php';
require_admin();

[$whereSql, $params] = enquiry_filters();
$rows = db_all("SELECT * FROM enquiries WHERE $whereSql ORDER BY created_at DESC", $params);
$notes = [];
foreach (db_all('SELECT enquiry_id, note, created_at FROM enquiry_notes ORDER BY created_at') as $n) $notes[(int)$n['enquiry_id']][] = substr($n['created_at'], 0, 10) . ': ' . $n['note'];
log_activity('Exported enquiries CSV', count($rows) . ' rows');

/** Prevent spreadsheet formula injection. */
function csv_safe(?string $v): string
{
    $v = (string)$v;
    return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="dreamm-framme-enquiries-' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₹ correctly
fputcsv($out, ['Reference', 'Received', 'Status', 'Name', 'Partner', 'Email', 'Phone', 'Event type', 'Event date', 'Location', 'Days', 'Service', 'Budget', 'Message', 'Internal notes']);
foreach ($rows as $r) {
    fputcsv($out, array_map('csv_safe', [$r['enquiry_id'], $r['created_at'], ENQUIRY_STATUSES[$r['status']] ?? $r['status'], $r['name'], $r['partner_name'], $r['email'], $r['phone'],
        $r['event_type'], $r['event_date'], $r['event_location'], (string)$r['event_days'], $r['service'], $r['budget'], $r['message'], implode(' | ', $notes[(int)$r['id']] ?? [])]));
}
fclose($out);
