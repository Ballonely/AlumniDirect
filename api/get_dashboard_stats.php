<?php
/**
 * GET /api/get_dashboard_stats.php
 * Feeds the 3 summary cards on the Dashboard tab of pv_staff.php.
 */

require_once __DIR__ . '/require_staff.php';
require_once __DIR__ . '/db.php';

// Total verified alumni = accounts with a graduation record that
// aren't themselves staff.
$totalVerified = (int) $pdo->query(
    "SELECT COUNT(DISTINCT g.account_ID)
     FROM graduation g
     LEFT JOIN staff s ON s.account_ID = g.account_ID
     WHERE s.account_ID IS NULL"
)->fetchColumn();

$pendingCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM modifications WHERE status = 'Pending'"
)->fetchColumn();

$recentUpdates = (int) $pdo->query(
    "SELECT COUNT(*) FROM modifications WHERE time_Modified >= (NOW() - INTERVAL 7 DAY)"
)->fetchColumn();

echo json_encode([
    'totalVerifiedAlumni'      => $totalVerified,
    'pendingVerifications'     => $pendingCount,
    'profileUpdatesLast7Days'  => $recentUpdates,
]);
