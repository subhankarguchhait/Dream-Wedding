<?php
/** Shared filters for the Enquiries page and the CSV export. Returns [whereSql, params, filterValues]. */
declare(strict_types=1);
defined('APP_NAME') || exit;

function enquiry_filters(): array
{
    $where = ['1=1'];
    $p = [];
    $f = ['q' => get_str('q', 100), 'status' => get_str('status', 20), 'event_type' => get_str('event_type', 60), 'service' => get_str('service', 100),
        'from' => get_str('from', 10), 'to' => get_str('to', 10)];
    if ($f['q'] !== '') {
        $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR partner_name LIKE ? OR event_location LIKE ? OR enquiry_id LIKE ?)';
        for ($i = 0; $i < 6; $i++) $p[] = '%' . $f['q'] . '%';
    }
    if (isset(ENQUIRY_STATUSES[$f['status']])) { $where[] = 'status = ?'; $p[] = $f['status']; } else $f['status'] = '';
    if ($f['event_type'] !== '') { $where[] = 'event_type = ?'; $p[] = $f['event_type']; }
    if ($f['service'] !== '') { $where[] = 'service = ?'; $p[] = $f['service']; }
    // Date range filters the EVENT date (wedding day), which is what the studio plans around
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['from'])) { $where[] = 'event_date >= ?'; $p[] = $f['from']; } else $f['from'] = '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['to'])) { $where[] = 'event_date <= ?'; $p[] = $f['to']; } else $f['to'] = '';
    return [implode(' AND ', $where), $p, $f];
}
