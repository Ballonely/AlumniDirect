<?php
/**
 * get_alumni.php
 *
 * Returns the alumni directory list as JSON.
 *
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$sql = "
    SELECT
        a.account_ID,
        a.first_Name,
        a.last_Name,
        p.program_Name,
        g.graduation_Year,
        c.college_Name,
        e.occupation,
        e.description AS employment_description,
        s.sector_Name,
        (a.photo IS NOT NULL) AS has_photo
    FROM account a
    -- Exclude any account that has a matching staff row (covers the one
    -- staff account now and any added later without code changes).
    LEFT JOIN staff st ON st.account_ID = a.account_ID
    LEFT JOIN graduation g ON g.account_ID = a.account_ID
    LEFT JOIN program p ON p.program_ID = g.program_ID
    LEFT JOIN college c ON c.college_ID = g.college_ID
    LEFT JOIN employment e ON e.account_ID = a.account_ID
    LEFT JOIN industry_sector s ON s.sector_ID = e.sector_ID
    WHERE st.account_ID IS NULL
    GROUP BY a.account_ID
    ORDER BY a.account_ID
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

$result = array_map(function ($row) {
    $fullName = trim($row['first_Name'] . ' ' . $row['last_Name']);
    $initials = mb_substr($row['first_Name'], 0, 1) . mb_substr($row['last_Name'], 0, 1);

    return [
        'id'        => (int) $row['account_ID'],
        'initials'  => strtoupper($initials),
        'name'      => $fullName,
        'program'   => $row['program_Name'],
        'grad'      => $row['graduation_Year'],
        'college'   => $row['college_Name'],
        'role'      => $row['occupation'],
        'org'       => null, // schema has no employer-name column — see note below
        'industry'  => $row['sector_Name'],
        'sector'    => $row['sector_Name'],
        'tagline'   => $row['occupation'] ? $row['occupation'] . ' · ' . $row['sector_Name'] : null,
        'image_url' => $row['has_photo'] ? "../api/get_image.php?id={$row['account_ID']}" : null,
    ];
}, $rows);

echo json_encode($result);
