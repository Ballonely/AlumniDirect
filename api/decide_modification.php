<?php
/**
 * POST /api/decide_modification.php
 * Body (JSON): { modificationId, decision: "approve" | "deny", comment: "" }
 *
 * On approve: writes each modification_detail's new_Value into the
 * matching column on `account` for that alumnus, then marks the
 * request Approved and is_Verified = 1.
 * On deny: just marks the request Denied — the account is untouched.
 */

require_once __DIR__ . '/require_staff.php';
require_once __DIR__ . '/db.php';

// Whitelist: only these account columns may be written by an approval.
// Prevents a tampered field_Name from writing to an arbitrary column.
const ALLOWED_FIELDS = [
    'first_Name', 'last_Name', 'middle_Name', 'suffix', 'email', 'phone',
    'nickname', 'bio', 'profile_Quote', 'employer', 'occupation',
];

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$modId    = (int) ($body['modificationId'] ?? 0);
$decision = $body['decision'] ?? '';
$comment  = trim($body['comment'] ?? '');

if (!$modId || !in_array($decision, ['approve', 'deny'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $modStmt = $pdo->prepare(
        "SELECT account_ID, status FROM modifications WHERE modification_ID = :id FOR UPDATE"
    );
    $modStmt->execute(['id' => $modId]);
    $mod = $modStmt->fetch();

    if (!$mod) {
        throw new Exception('Modification request not found.');
    }
    if ($mod['status'] !== 'Pending') {
        throw new Exception('This request has already been decided.');
    }

    if ($decision === 'approve') {
        $detailStmt = $pdo->prepare(
            "SELECT field_Name, new_Value FROM modification_detail WHERE modification_ID = :id"
        );
        $detailStmt->execute(['id' => $modId]);
        $details = $detailStmt->fetchAll();

        foreach ($details as $d) {
            if (!in_array($d['field_Name'], ALLOWED_FIELDS, true)) {
                continue; // silently skip anything not whitelisted
            }
            $col = $d['field_Name']; // safe: checked against ALLOWED_FIELDS above
            $upd = $pdo->prepare("UPDATE account SET `$col` = :val WHERE account_ID = :aid");
            $upd->execute(['val' => $d['new_Value'], 'aid' => $mod['account_ID']]);
        }
    }

    $newStatus = $decision === 'approve' ? 'Approved' : 'Denied';
    $upd = $pdo->prepare(
        "UPDATE modifications
         SET status = :status, is_Verified = :verified, admin_Comment = :comment,
             staff_ID = :staffId
         WHERE modification_ID = :id"
    );
    $upd->execute([
        'status'   => $newStatus,
        'verified' => $decision === 'approve' ? 1 : 0,
        'comment'  => $comment !== '' ? $comment : null,
        'staffId'  => $_SESSION['staff_ID'],
        'id'       => $modId,
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'status' => $newStatus]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
