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

// Whitelist: only these account columns may be written directly.
// Prevents a tampered field_Name from writing to an arbitrary column.
const ALLOWED_FIELDS = [
    'first_Name', 'last_Name', 'middle_Name', 'suffix', 'email', 'phone',
    'nickname', 'bio', 'profile_Quote',
];

// These fields live outside the account table and need special handling.
const RELATIONAL_FIELDS = ['program_Name', 'college_Name', 'graduation_Year', 'occupation', 'employer', 'sector_Name', 'awards'];

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

        // Collect all changed values into a lookup map first
        $changes = [];
        foreach ($details as $d) {
            $changes[$d['field_Name']] = $d['new_Value'];
        }

        //  Direct account-column writes 
        foreach ($changes as $fieldName => $newVal) {
            if (!in_array($fieldName, ALLOWED_FIELDS, true)) {
                continue;
            }
            $upd = $pdo->prepare("UPDATE account SET `$fieldName` = :val WHERE account_ID = :aid");
            $upd->execute(['val' => $newVal, 'aid' => $mod['account_ID']]);
        }

        //  Program / Graduation writes 
        $hasAcadChange = array_key_exists('program_Name', $changes)
                      || array_key_exists('college_Name', $changes)
                      || array_key_exists('graduation_Year', $changes);

        if ($hasAcadChange) {
            // Resolve college name → ID if it changed
            $collegeId = null;
            if (isset($changes['college_Name']) && $changes['college_Name'] !== '') {
                $cRow = $pdo->prepare('SELECT college_ID FROM college WHERE college_Name = :n LIMIT 1');
                $cRow->execute(['n' => $changes['college_Name']]);
                $collegeId = $cRow->fetchColumn() ?: null;
            } else {
                // Keep existing college_ID
                $cRow = $pdo->prepare(
                    'SELECT g.college_ID FROM graduation g WHERE g.account_ID = :id LIMIT 1'
                );
                $cRow->execute(['id' => $mod['account_ID']]);
                $collegeId = $cRow->fetchColumn() ?: null;
            }

            // Upsert program
            $pRow = $pdo->prepare('SELECT program_ID FROM program WHERE account_ID = :id LIMIT 1');
            $pRow->execute(['id' => $mod['account_ID']]);
            $programId = $pRow->fetchColumn();
            if ($programId) {
                if (isset($changes['program_Name'])) {
                    $pdo->prepare('UPDATE program SET program_Name = :n WHERE program_ID = :pid')
                        ->execute(['n' => $changes['program_Name'], 'pid' => $programId]);
                }
            } else {
                $pdo->prepare('INSERT INTO program (account_ID, program_Name) VALUES (:aid, :n)')
                    ->execute(['aid' => $mod['account_ID'], 'n' => $changes['program_Name'] ?? '']);
                $programId = (int) $pdo->lastInsertId();
            }

            // Upsert graduation
            $gRow = $pdo->prepare('SELECT graduation_ID FROM graduation WHERE account_ID = :id LIMIT 1');
            $gRow->execute(['id' => $mod['account_ID']]);
            $gradId = $gRow->fetchColumn();
            $gradYear = $changes['graduation_Year']
                ?? ($pdo->prepare('SELECT graduation_Year FROM graduation WHERE account_ID = :id LIMIT 1')
                       ->execute(['id' => $mod['account_ID']]) ? null : null); // keep existing if not changing

            if ($gradId) {
                $upd = $pdo->prepare(
                    'UPDATE graduation SET program_ID = :pid, college_ID = :cid'
                    . (isset($changes['graduation_Year']) ? ', graduation_Year = :yr' : '')
                    . ' WHERE graduation_ID = :gid'
                );
                $params = ['pid' => $programId, 'cid' => $collegeId, 'gid' => $gradId];
                if (isset($changes['graduation_Year'])) $params['yr'] = $changes['graduation_Year'];
                $upd->execute($params);
            } else {
                $pdo->prepare(
                    'INSERT INTO graduation (account_ID, program_ID, college_ID, graduation_Year)
                     VALUES (:aid, :pid, :cid, :yr)'
                )->execute([
                    'aid' => $mod['account_ID'],
                    'pid' => $programId,
                    'cid' => $collegeId,
                    'yr'  => $changes['graduation_Year'] ?? null,
                ]);
            }
        }

        //  Employment writes 
        $hasEmpChange = array_key_exists('occupation', $changes)
                     || array_key_exists('employer', $changes)
                     || array_key_exists('sector_Name', $changes);

        if ($hasEmpChange) {
            $sectorId = null;
            if (isset($changes['sector_Name']) && $changes['sector_Name'] !== '') {
                $sRow = $pdo->prepare('SELECT sector_ID FROM industry_sector WHERE sector_Name = :n LIMIT 1');
                $sRow->execute(['n' => $changes['sector_Name']]);
                $sectorId = $sRow->fetchColumn() ?: null;
            } else {
                $sRow = $pdo->prepare(
                    'SELECT sector_ID FROM employment WHERE account_ID = :id LIMIT 1'
                );
                $sRow->execute(['id' => $mod['account_ID']]);
                $sectorId = $sRow->fetchColumn() ?: null;
            }

            $eRow = $pdo->prepare('SELECT employment_ID FROM employment WHERE account_ID = :id LIMIT 1');
            $eRow->execute(['id' => $mod['account_ID']]);
            $empId = $eRow->fetchColumn();
            if ($empId) {
                $setParts = [];
                $eParams  = ['eid' => $empId];
                if (isset($changes['occupation'])) { $setParts[] = 'occupation = :occ'; $eParams['occ'] = $changes['occupation']; }
                if (isset($changes['employer']))   { $setParts[] = 'employer = :emp';   $eParams['emp'] = $changes['employer']; }
                if ($sectorId !== null)             { $setParts[] = 'sector_ID = :sid';  $eParams['sid'] = $sectorId; }
                if ($setParts) {
                    $pdo->prepare('UPDATE employment SET ' . implode(', ', $setParts) . ' WHERE employment_ID = :eid')
                        ->execute($eParams);
                }
            } else {
                $pdo->prepare(
                    'INSERT INTO employment (account_ID, sector_ID, occupation, employer)
                     VALUES (:aid, :sid, :occ, :emp)'
                )->execute([
                    'aid' => $mod['account_ID'],
                    'sid' => $sectorId,
                    'occ' => $changes['occupation'] ?? '',
                    'emp' => $changes['employer']   ?? '',
                ]);
            }
        }

        //  Awards write (replace full set) 
        if (array_key_exists('awards', $changes)) {
            $pdo->prepare('DELETE FROM awards WHERE account_ID = :id')
                ->execute(['id' => $mod['account_ID']]);

            // new_Value is stored as "Title (Year); Title (Year)"
            $parts = array_filter(array_map('trim', explode(';', $changes['awards'])));
            $insAw = $pdo->prepare(
                'INSERT INTO awards (account_ID, award_Title, year_received) VALUES (:aid, :title, :yr)'
            );
            foreach ($parts as $part) {
                if (preg_match('/^(.+)\s+\((\d{4})\)$/', $part, $m)) {
                    $insAw->execute([
                        'aid'   => $mod['account_ID'],
                        'title' => trim($m[1]),
                        'yr'    => $m[2],
                    ]);
                }
            }
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
