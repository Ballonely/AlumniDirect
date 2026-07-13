<?php
/**
 * get_alumni_detail.php?id=1
 *
 * Returns a single alumnus's full profile as JSON: basic info, all
 * employment rows, all awards, and graduation/program/college info.
 * Contact fields (email, phone) are returned only when the alumnus
 * has opted in via show_Email / show_Phone.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid id parameter']);
    exit;
}

// ── Basic account + graduation info ──────────────────────────
$stmt = $pdo->prepare("
    SELECT
        a.account_ID, a.first_Name, a.last_Name, a.middle_Name, a.suffix,
        a.email, a.phone, a.show_Email, a.show_Phone,
        a.bio, (a.photo IS NOT NULL) AS has_photo,
        p.program_Name, g.graduation_Year, c.college_Name
    FROM account a
    LEFT JOIN graduation g ON g.account_ID = a.account_ID
    LEFT JOIN program p ON p.program_ID = g.program_ID
    LEFT JOIN college c ON c.college_ID = g.college_ID
    WHERE a.account_ID = :id
    LIMIT 1
");
$stmt->execute(['id' => $id]);
$account = $stmt->fetch();

if (!$account) {
    http_response_code(404);
    echo json_encode(['error' => 'Alumnus not found']);
    exit;
}

// ── All employment rows for this account ─────────────────────
$stmt = $pdo->prepare("
    SELECT e.occupation, e.description, s.sector_Name
    FROM employment e
    LEFT JOIN industry_sector s ON s.sector_ID = e.sector_ID
    WHERE e.account_ID = :id
");
$stmt->execute(['id' => $id]);
$employment = $stmt->fetchAll();

// ── All awards for this account ───────────────────────────────
$stmt = $pdo->prepare("
    SELECT award_Title, award_Description, year_received
    FROM awards
    WHERE account_ID = :id
    ORDER BY year_received DESC
");
$stmt->execute(['id' => $id]);
$awards = $stmt->fetchAll();

$fullName = trim($account['first_Name'] . ' ' . $account['last_Name']);
$initials = strtoupper(mb_substr($account['first_Name'], 0, 1) . mb_substr($account['last_Name'], 0, 1));

echo json_encode([
    'id'          => (int) $account['account_ID'],
    'initials'    => $initials,
    'name'        => $fullName,
    'program'     => $account['program_Name'],
    'grad'        => $account['graduation_Year'],
    'college'     => $account['college_Name'],
    // Contact — always return the flag; only return the value when the
    // alumnus has chosen to make it public.
    'show_email'  => (bool) $account['show_Email'],
    'email'       => $account['show_Email'] ? $account['email'] : null,
    'show_phone'  => (bool) $account['show_Phone'],
    'phone'       => $account['show_Phone'] ? $account['phone'] : null,
    'employment'  => $employment,
    'awards'      => $awards,
    'image_url'   => $account['has_photo'] ? "../api/get_image.php?id={$account['account_ID']}" : null,
    'career_story' => $account['bio'],
]);
