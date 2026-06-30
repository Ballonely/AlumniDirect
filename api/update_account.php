<?php
/*
  POST /api/update_account.php
  Saves changes made on the Edit Profile page for the currently logged-in
  account. Expects a JSON body — see the `payload` shape built by
  doSave() in pv_main.html.
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

try {
    $pdo->beginTransaction();

    // ── ACCOUNT ──────────────────────────────────────────────
    $stmt = $pdo->prepare('UPDATE account SET
        first_Name = :firstName, last_Name = :lastName, middle_Name = :middleName,
        suffix = :suffix, nickname = :nickname, date_Of_Birth = :dob, gender = :gender,
        email = :email, phone = :phone, bio = :bio, profile_Quote = :profileQuote,
        show_Email = :showEmail, show_Phone = :showPhone, show_Employment = :showEmployment
        WHERE account_ID = :accountId');
    $stmt->execute([
        ':firstName'      => $body['firstName'],
        ':lastName'       => $body['lastName'],
        ':middleName'     => $body['middleName'],
        ':suffix'         => $body['suffix'],
        ':nickname'       => $body['nickname'],
        ':dob'            => $body['dob'] !== '' ? $body['dob'] : null,
        ':gender'         => $body['gender'],
        ':email'          => $body['email'],
        ':phone'          => $body['phone'],
        ':bio'            => $body['bio'],
        ':profileQuote'   => $body['profileQuote'],
        ':showEmail'      => $body['showEmail'] ? 1 : 0,
        ':showPhone'      => $body['showPhone'] ? 1 : 0,
        ':showEmployment' => $body['showEmployment'] ? 1 : 0,
        ':accountId'      => $accountId,
    ]);

    // Photo is optional — only update it if a new one was uploaded.
    if (!empty($body['photoBase64'])) {
        $photoData = base64_decode($body['photoBase64']);
        $stmt = $pdo->prepare('UPDATE account SET photo = :photo, photo_Type = :photoType WHERE account_ID = :accountId');
        $stmt->bindParam(':photo', $photoData, PDO::PARAM_LOB);
        $stmt->bindValue(':photoType', $body['photoType']);
        $stmt->bindValue(':accountId', $accountId, PDO::PARAM_INT);
        $stmt->execute();
    }

    // ── PROGRAM + GRADUATION ─────────────────────────────────
    $stmt = $pdo->prepare('SELECT program_ID FROM program WHERE account_ID = ? LIMIT 1');
    $stmt->execute([$accountId]);
    $existingProgram = $stmt->fetch();

    if ($existingProgram) {
        $programId = $existingProgram['program_ID'];
        $stmt = $pdo->prepare('UPDATE program SET program_Name = ? WHERE program_ID = ?');
        $stmt->execute([$body['programName'], $programId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO program (account_ID, program_Name) VALUES (?, ?)');
        $stmt->execute([$accountId, $body['programName']]);
        $programId = $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare('SELECT graduation_ID FROM graduation WHERE account_ID = ? LIMIT 1');
    $stmt->execute([$accountId]);
    $existingGrad = $stmt->fetch();

    $collegeId = $body['collegeId'] !== '' ? $body['collegeId'] : null;
    $gradYear  = $body['gradYear'] !== '' ? $body['gradYear'] : null;

    if ($existingGrad) {
        $stmt = $pdo->prepare('UPDATE graduation SET program_ID = ?, college_ID = ?, graduation_Year = ?
            WHERE graduation_ID = ?');
        $stmt->execute([$programId, $collegeId, $gradYear, $existingGrad['graduation_ID']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO graduation (account_ID, program_ID, college_ID, graduation_Year)
            VALUES (?, ?, ?, ?)');
        $stmt->execute([$accountId, $programId, $collegeId, $gradYear]);
    }

    // ── EMPLOYMENT ────────────────────────────────────────────
    $stmt = $pdo->prepare('SELECT employment_ID FROM employment WHERE account_ID = ? LIMIT 1');
    $stmt->execute([$accountId]);
    $existingEmployment = $stmt->fetch();

    $sectorId = $body['sectorId'] !== '' ? $body['sectorId'] : null;

    if ($existingEmployment) {
        $stmt = $pdo->prepare('UPDATE employment SET sector_ID = ?, occupation = ?, employer = ?
            WHERE employment_ID = ?');
        $stmt->execute([$sectorId, $body['jobTitle'], $body['employer'], $existingEmployment['employment_ID']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO employment (account_ID, sector_ID, occupation, employer)
            VALUES (?, ?, ?, ?)');
        $stmt->execute([$accountId, $sectorId, $body['jobTitle'], $body['employer']]);
    }

    // ── AWARDS (replace the full set each save) ──────────────
    $stmt = $pdo->prepare('DELETE FROM awards WHERE account_ID = ?');
    $stmt->execute([$accountId]);

    if (!empty($body['awards']) && is_array($body['awards'])) {
        $stmt = $pdo->prepare('INSERT INTO awards (account_ID, award_Title, year_received) VALUES (?, ?, ?)');
        foreach ($body['awards'] as $award) {
            if (trim($award['title']) === '' || trim($award['year']) === '') continue;
            $stmt->execute([$accountId, $award['title'], $award['year']]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Save failed: ' . $e->getMessage()]);
}
