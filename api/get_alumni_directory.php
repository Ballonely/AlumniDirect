<?php
/**
 * GET /api/get_alumni_directory.php
 * Powers the Alumni Directory table on pv_staff.php. Supports:
 *   ?q=        free-text search on name / school_ID
 *   ?batch=    filter by graduation_Year
 *   ?program=  filter by program_Name
 *   ?page=     1-indexed page number (20 rows per page)
 */

require_once __DIR__ . '/require_staff.php';
require_once __DIR__ . '/db.php';

$q       = trim($_GET['q'] ?? '');
$batch   = trim($_GET['batch'] ?? '');
$program = trim($_GET['program'] ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = ["s.account_ID IS NULL"]; // exclude staff accounts from the alumni directory
$params = [];

if ($q !== '') {
    $where[] = "(CONCAT(a.first_Name, ' ', a.last_Name) LIKE :q OR a.school_ID LIKE :q)";
    $params['q'] = "%$q%";
}
if ($batch !== '') {
    $where[] = "g.graduation_Year = :batch";
    $params['batch'] = $batch;
}
if ($program !== '') {
    $where[] = "p.program_Name = :program";
    $params['program'] = $program;
}
$whereSql = implode(' AND ', $where);

$sql = "
    SELECT a.account_ID, a.first_Name, a.last_Name, a.school_ID,
           g.graduation_Year, p.program_Name,
           EXISTS (
             SELECT 1 FROM modifications m
             WHERE m.account_ID = a.account_ID AND m.status = 'Pending'
           ) AS has_pending
    FROM account a
    LEFT JOIN staff s ON s.account_ID = a.account_ID
    LEFT JOIN graduation g ON g.account_ID = a.account_ID
    LEFT JOIN program p ON p.program_ID = g.program_ID
    WHERE $whereSql
    ORDER BY a.last_Name, a.first_Name
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM account a
    LEFT JOIN staff s ON s.account_ID = a.account_ID
    LEFT JOIN graduation g ON g.account_ID = a.account_ID
    LEFT JOIN program p ON p.program_ID = g.program_ID
    WHERE $whereSql
");
foreach ($params as $k => $v) {
    $countStmt->bindValue($k, $v);
}
$countStmt->execute();
$total = (int) $countStmt->fetchColumn();

$alumni = array_map(function ($r) {
    return [
        'id'        => $r['school_ID'] ?: ('A-' . $r['account_ID']),
        'name'      => trim($r['first_Name'] . ' ' . $r['last_Name']),
        'batch'     => $r['graduation_Year'] ?: '—',
        'program'   => $r['program_Name'] ?: '—',
        'status'    => $r['has_pending'] ? 'Pending Update' : 'Verified',
        'accountId' => (int) $r['account_ID'],
    ];
}, $rows);

echo json_encode([
    'alumni'  => $alumni,
    'page'    => $page,
    'perPage' => $perPage,
    'total'   => $total,
]);
