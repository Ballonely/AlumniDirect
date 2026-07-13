<?php
/*
  GET /api/get_account.php
  Returns the full editable profile for the currently logged-in account,
  plus the lookup lists (colleges, industries) needed to populate the
  College and Industry dropdowns on the Edit Profile page.

  NOTE: This assumes your login flow sets $_SESSION['account_ID'] on
  successful login. Adjust the session key below if your login script
  names it differently.
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

// ── ACCOUNT ──────────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT account_ID, first_Name, last_Name, middle_Name, suffix,
    school_ID, email, phone, nickname, date_Of_Birth, gender, bio, profile_Quote,
    show_Email, show_Phone, show_Employment, photo, photo_Type
    FROM account WHERE account_ID = ?');
$stmt->execute([$accountId]);
$account = $stmt->fetch();

if (!$account) {
    http_response_code(404);
    echo json_encode(['error' => 'Account not found']);
    exit;
}

// PDO returns BLOB columns as plain strings — base64-encode for safe
// transport as JSON.
$account['photo'] = $account['photo'] !== null ? base64_encode($account['photo']) : null;

// ── ACADEMIC (program + graduation + college) ───────────────
$stmt = $pdo->prepare('SELECT p.program_ID, p.program_Name, g.college_ID, g.graduation_Year
    FROM program p
    LEFT JOIN graduation g ON g.program_ID = p.program_ID AND g.account_ID = p.account_ID
    WHERE p.account_ID = ? LIMIT 1');
$stmt->execute([$accountId]);
$academic = $stmt->fetch() ?: [
    'program_ID' => null, 'program_Name' => '', 'college_ID' => null, 'graduation_Year' => ''
];

// ── EMPLOYMENT (one primary record) ─────────────────────────
$stmt = $pdo->prepare('SELECT employment_ID, sector_ID, occupation, employer, description
    FROM employment WHERE account_ID = ? LIMIT 1');
$stmt->execute([$accountId]);
$employment = $stmt->fetch() ?: [
    'employment_ID' => null, 'sector_ID' => null, 'occupation' => '', 'employer' => '', 'description' => ''
];

// ── AWARDS ───────────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT award_ID, award_Title, award_Description, year_received
    FROM awards WHERE account_ID = ? ORDER BY year_received DESC');
$stmt->execute([$accountId]);
$awards = $stmt->fetchAll();

// ── LOOKUP LISTS ─────────────────────────────────────────────
$colleges   = $pdo->query('SELECT college_ID, college_Name FROM college ORDER BY college_Name')->fetchAll();
$industries = $pdo->query('SELECT sector_ID, sector_Name FROM industry_sector ORDER BY sector_Name')->fetchAll();

echo json_encode([
    'account'    => $account,
    'academic'   => $academic,
    'employment' => $employment,
    'awards'     => $awards,
    'colleges'   => $colleges,
    'industries' => $industries,
]);
