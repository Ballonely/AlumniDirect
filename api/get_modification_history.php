<?php
/**
 * api/get_modification_history.php
 *
 * Returns Approved and Denied modifications from the past 7 days.
 * GET params:
 *   ?days=7      how many days back to look (default 7, max 90)
 *   ?status=     'Approved' | 'Denied' | '' (both, default)
 *   ?page=1      pagination (20 per page)
 */

require_once __DIR__ . '/require_staff.php';
require_once __DIR__ . '/db.php';

$days    = min(90, max(1, (int) ($_GET['days'] ?? 7)));
$status  = $_GET['status'] ?? '';
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = ["m.status IN ('Approved','Denied')", "m.time_Modified >= NOW() - INTERVAL :days DAY"];
$params = ['days' => $days];

if ($status === 'Approved' || $status === 'Denied') {
    $where[] = "m.status = :status";
    $params['status'] = $status;
}
$whereSql = implode(' AND ', $where);

$sql = "
    SELECT
        m.modification_ID,
        m.status,
        m.action_Type,
        m.time_Modified,
        m.admin_Comment,
        CONCAT(a.first_Name, ' ', a.last_Name) AS alumni_name,
        a.school_ID,
        CONCAT(sa.first_Name, ' ', sa.last_Name) AS staff_name,
        GROUP_CONCAT(
            CONCAT(md.field_Label, '||', COALESCE(md.old_Value,'—'), '||', COALESCE(md.new_Value,'—'))
            ORDER BY md.detail_ID
            SEPARATOR ';;'
        ) AS field_changes
    FROM modifications m
    JOIN account a ON a.account_ID = m.account_ID
    LEFT JOIN staff s ON s.staff_ID = m.staff_ID
    LEFT JOIN account sa ON sa.account_ID = s.account_ID
    LEFT JOIN modification_detail md ON md.modification_ID = m.modification_ID
    WHERE $whereSql
    GROUP BY m.modification_ID
    ORDER BY m.time_Modified DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue('limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$countSql = "SELECT COUNT(*) FROM modifications m
    JOIN account a ON a.account_ID = m.account_ID
    WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $k => $v) {
    $countStmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$countStmt->execute();
$total = (int) $countStmt->fetchColumn();

$history = array_map(function ($r) {
    // Parse field changes from the GROUP_CONCAT string
    $changes = [];
    if ($r['field_changes']) {
        foreach (explode(';;', $r['field_changes']) as $chunk) {
            $parts = explode('||', $chunk);
            if (count($parts) === 3) {
                $changes[] = [
                    'label' => $parts[0],
                    'old'   => $parts[1],
                    'new'   => $parts[2],
                ];
            }
        }
    }
    return [
        'id'          => (int) $r['modification_ID'],
        'status'      => $r['status'],
        'actionType'  => $r['action_Type'],
        'date'        => date('M j, Y · H:i', strtotime($r['time_Modified'])),
        'alumniName'  => $r['alumni_name'],
        'alumniId'    => $r['school_ID'] ?? '—',
        'staffName'   => $r['staff_name'] ?? 'System',
        'comment'     => $r['admin_Comment'] ?? '',
        'changes'     => $changes,
    ];
}, $rows);

echo json_encode([
    'history' => $history,
    'page'    => $page,
    'perPage' => $perPage,
    'total'   => $total,
    'days'    => $days,
]);
