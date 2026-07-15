<?php
/**
 * GET /api/get_pending_modifications.php
 * Returns every modification request with status='Pending', each with
 * its full field-level diff (modification_detail) and any uploaded
 * verification attachments — everything the review card needs.
 */

require_once __DIR__ . '/require_staff.php';
require_once __DIR__ . '/db.php';

$mods = $pdo->query("
    SELECT m.modification_ID, m.account_ID, m.time_Modified,
           a.first_Name, a.last_Name, p.program_Name, g.graduation_Year
    FROM modifications m
    JOIN account a ON a.account_ID = m.account_ID
    LEFT JOIN graduation g ON g.account_ID = a.account_ID
    LEFT JOIN program p ON p.program_ID = g.program_ID
    WHERE m.status = 'Pending'
    ORDER BY m.time_Modified ASC
")->fetchAll();

$detailStmt = $pdo->prepare(
    "SELECT field_Label, old_Value, new_Value FROM modification_detail WHERE modification_ID = :id"
);
$attachStmt = $pdo->prepare(
    "SELECT attachment_ID, file_Name FROM modification_attachment WHERE modification_ID = :id"
);

$result = [];
foreach ($mods as $m) {
    $detailStmt->execute(['id' => $m['modification_ID']]);
    $details = $detailStmt->fetchAll();

    $attachStmt->execute(['id' => $m['modification_ID']]);
    $attachments = $attachStmt->fetchAll();

    $initials = strtoupper(substr($m['first_Name'], 0, 1) . substr($m['last_Name'], 0, 1));

    $result[] = [
        'id'        => 'req-' . $m['modification_ID'],
        'initials'  => $initials,
        'name'      => trim($m['first_Name'] . ' ' . $m['last_Name']),
        'meta'      => trim(($m['program_Name'] ?: 'Program N/A') . ', Batch ' . ($m['graduation_Year'] ?: 'N/A')),
        'date'      => date('M j, Y · H:i', strtotime($m['time_Modified'])) . ' PHT',
        'current'   => array_map(fn($d) => ['label' => $d['field_Label'], 'value' => $d['old_Value']], $details),
        'requested' => array_map(fn($d) => ['label' => $d['field_Label'], 'value' => $d['new_Value']], $details),
        'blobs'     => array_map(fn($b) => ['id' => $b['attachment_ID'], 'name' => $b['file_Name']], $attachments),
    ];
}

echo json_encode(['pending' => $result]);
