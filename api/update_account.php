<?php
/*
  POST /api/update_account.php
  Instead of writing profile changes directly to the database, this now
  creates a pending modification request that staff must approve first.
  Only photo uploads and the visibility toggles (show_Email / show_Phone /
  show_Employment) take effect immediately, because those are low-risk
  preference changes that don't need a staff review.

  Fields that require verification (anything visible on the public
  alumni directory):
    first_Name, last_Name, middle_Name, suffix, nickname, email, phone,
    bio, profile_Quote, employer, occupation, program, college, gradYear,
    sector, and awards.

  On approval, decide_modification.php already writes the whitelisted
  account columns back. Program / graduation / employment / awards
  changes are noted in the modification_detail as informational labels
  so staff can see what changed; the actual DB writes for those tables
  will need to be added to decide_modification.php if you want them
  auto-applied (see NOTE below).
*/

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if (empty($_SESSION['account_ID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
$accountId = (int) $_SESSION['account_ID'];

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

// ── Human-readable labels for the review card in pv_staff ────────────
const FIELD_LABELS = [
    'first_Name'    => 'First Name',
    'last_Name'     => 'Last Name',
    'middle_Name'   => 'Middle Name',
    'suffix'        => 'Suffix',
    'nickname'      => 'Nickname / Preferred Name',
    'email'         => 'Email Address',
    'phone'         => 'Phone Number',
    'bio'           => 'Career Story / Bio',
    'profile_Quote' => 'Profile Quote',
    'employer'      => 'Employer',
    'occupation'    => 'Job Title / Occupation',
    'program_Name'  => 'Program',
    'college_Name'  => 'College',
    'graduation_Year' => 'Graduation Year',
    'sector_Name'   => 'Industry / Sector',
];

try {
    $pdo->beginTransaction();

    // ── 1. Apply low-risk preference changes immediately ─────────────
    $stmt = $pdo->prepare(
        'UPDATE account
         SET show_Email = :showEmail, show_Phone = :showPhone,
             show_Employment = :showEmployment
         WHERE account_ID = :id'
    );
    $stmt->execute([
        ':showEmail'      => $body['showEmail']      ? 1 : 0,
        ':showPhone'      => $body['showPhone']      ? 1 : 0,
        ':showEmployment' => $body['showEmployment'] ? 1 : 0,
        ':id'             => $accountId,
    ]);

    // Photo is also applied immediately (it's a binary blob, not a
    // text field that needs a staff diff view).
    if (!empty($body['photoBase64'])) {
        $photoData = base64_decode($body['photoBase64']);
        $stmt = $pdo->prepare(
            'UPDATE account SET photo = :photo, photo_Type = :photoType
             WHERE account_ID = :id'
        );
        $stmt->bindParam(':photo', $photoData, PDO::PARAM_LOB);
        $stmt->bindValue(':photoType', $body['photoType']);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
    }

    // ── 2. Fetch the current account values to build the diff ────────
    $cur = $pdo->prepare(
        'SELECT first_Name, last_Name, middle_Name, suffix, nickname,
                email, phone, bio, profile_Quote
         FROM account WHERE account_ID = :id'
    );
    $cur->execute([':id' => $accountId]);
    $currentAccount = $cur->fetch();

    // Current academic / employment data
    $curAcad = $pdo->prepare(
        'SELECT p.program_Name, c.college_Name, g.graduation_Year
         FROM graduation g
         LEFT JOIN program p ON p.program_ID = g.program_ID
         LEFT JOIN college c ON c.college_ID = g.college_ID
         WHERE g.account_ID = :id LIMIT 1'
    );
    $curAcad->execute([':id' => $accountId]);
    $currentAcad = $curAcad->fetch() ?: ['program_Name' => '', 'college_Name' => '', 'graduation_Year' => ''];

    $curEmp = $pdo->prepare(
        'SELECT e.occupation, e.employer, s.sector_Name
         FROM employment e
         LEFT JOIN industry_sector s ON s.sector_ID = e.sector_ID
         WHERE e.account_ID = :id LIMIT 1'
    );
    $curEmp->execute([':id' => $accountId]);
    $currentEmp = $curEmp->fetch() ?: ['occupation' => '', 'employer' => '', 'sector_Name' => ''];

    // ── 3. Build the diff — only fields that actually changed ─────────
    // Map incoming payload keys → db column names and current values.
    $comparisons = [
        'first_Name'      => [$currentAccount['first_Name'],      $body['firstName']   ?? ''],
        'last_Name'       => [$currentAccount['last_Name'],       $body['lastName']    ?? ''],
        'middle_Name'     => [$currentAccount['middle_Name'],     $body['middleName']  ?? ''],
        'suffix'          => [$currentAccount['suffix'],          $body['suffix']      ?? ''],
        'nickname'        => [$currentAccount['nickname'],        $body['nickname']    ?? ''],
        'email'           => [$currentAccount['email'],           $body['email']       ?? ''],
        'phone'           => [$currentAccount['phone'],           $body['phone']       ?? ''],
        'bio'             => [$currentAccount['bio'],             $body['bio']         ?? ''],
        'profile_Quote'   => [$currentAccount['profile_Quote'],   $body['profileQuote'] ?? ''],
        'employer'        => [$currentEmp['employer'],            $body['employer']    ?? ''],
        'occupation'      => [$currentEmp['occupation'],          $body['jobTitle']    ?? ''],
        'program_Name'    => [$currentAcad['program_Name'],       $body['programName'] ?? ''],
        'college_Name'    => [$currentAcad['college_Name'],       ''],   // filled below
        'graduation_Year' => [$currentAcad['graduation_Year'],    $body['gradYear']    ?? ''],
        'sector_Name'     => [$currentEmp['sector_Name'],         ''],   // filled below
    ];

    // Resolve foreign-key IDs to names for the diff display
    if (!empty($body['collegeId'])) {
        $row = $pdo->prepare('SELECT college_Name FROM college WHERE college_ID = :id');
        $row->execute([':id' => $body['collegeId']]);
        $comparisons['college_Name'][1] = ($row->fetchColumn() ?: '');
    }
    if (!empty($body['sectorId'])) {
        $row = $pdo->prepare('SELECT sector_Name FROM industry_sector WHERE sector_ID = :id');
        $row->execute([':id' => $body['sectorId']]);
        $comparisons['sector_Name'][1] = ($row->fetchColumn() ?: '');
    }

    $changedFields = [];
    foreach ($comparisons as $fieldName => [$oldVal, $newVal]) {
        $old = trim((string) ($oldVal ?? ''));
        $new = trim((string) ($newVal ?? ''));
        if ($old !== $new) {
            $changedFields[] = [
                'field_Name'  => $fieldName,
                'field_Label' => FIELD_LABELS[$fieldName] ?? $fieldName,
                'old_Value'   => $old,
                'new_Value'   => $new,
            ];
        }
    }

    // Awards diff — serialize to a readable string for the review card
    $curAwardsStmt = $pdo->prepare(
        'SELECT award_Title, year_received FROM awards WHERE account_ID = :id ORDER BY year_received DESC'
    );
    $curAwardsStmt->execute([':id' => $accountId]);
    $currentAwards = $curAwardsStmt->fetchAll();
    $currentAwardsStr = implode('; ', array_map(
        fn($a) => $a['award_Title'] . ' (' . $a['year_received'] . ')', $currentAwards
    ));
    $newAwards = array_filter($body['awards'] ?? [], fn($a) => trim($a['title']) && trim($a['year']));
    $newAwardsStr = implode('; ', array_map(
        fn($a) => trim($a['title']) . ' (' . trim($a['year']) . ')', $newAwards
    ));
    if (trim($currentAwardsStr) !== trim($newAwardsStr)) {
        $changedFields[] = [
            'field_Name'  => 'awards',
            'field_Label' => 'Awards & Recognition',
            'old_Value'   => $currentAwardsStr ?: '(none)',
            'new_Value'   => $newAwardsStr     ?: '(none)',
        ];
    }

    // ── 4. Nothing changed — nothing to queue ─────────────────────────
    if (empty($changedFields)) {
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'pending' => false,
            'message' => 'No profile changes detected. Visibility settings saved.',
        ]);
        exit;
    }

    // ── 5. Check for an already-pending request from this account ─────
    $existingPending = $pdo->prepare(
        "SELECT modification_ID FROM modifications
         WHERE account_ID = :id AND status = 'Pending' LIMIT 1"
    );
    $existingPending->execute([':id' => $accountId]);
    if ($existingPending->fetch()) {
        // Don't let the alumnus stack up multiple pending requests —
        // they should wait for the first one to be decided.
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode([
            'error' => 'You already have a modification request pending staff review. '
                     . 'Please wait for it to be approved or denied before submitting new changes.',
        ]);
        exit;
    }

    // ── 6. Insert the modification request header ─────────────────────
    $modStmt = $pdo->prepare(
        "INSERT INTO modifications (account_ID, staff_ID, status, is_Verified, time_Modified)
         VALUES (:aid, NULL, 'Pending', 0, NOW())"
    );
    $modStmt->execute([':aid' => $accountId]);
    $modId = (int) $pdo->lastInsertId();

    // ── 7. Insert one detail row per changed field ────────────────────
    $detStmt = $pdo->prepare(
        'INSERT INTO modification_detail
             (modification_ID, field_Name, field_Label, old_Value, new_Value)
         VALUES (:mid, :fname, :flabel, :oval, :nval)'
    );
    foreach ($changedFields as $f) {
        $detStmt->execute([
            ':mid'    => $modId,
            ':fname'  => $f['field_Name'],
            ':flabel' => $f['field_Label'],
            ':oval'   => $f['old_Value'],
            ':nval'   => $f['new_Value'],
        ]);
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'pending' => true,
        'message' => 'Your changes have been submitted for staff review. '
                   . 'They will appear on your public profile once approved.',
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Request failed: ' . $e->getMessage()]);
}
