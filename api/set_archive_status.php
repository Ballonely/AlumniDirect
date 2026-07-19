<?php
/**
 * api/set_archive_status.php
 */

session_start();
header('Content-Type: application/json');

if (empty($_SESSION['staff_ID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

$accountId = (int) ($body['accountId'] ?? $body['account_ID'] ?? $body['account_id'] ?? 0);
$archive   = $body['archive'] ?? null;

if (!$accountId || $archive === null) {
    http_response_code(400);
    echo json_encode([
        'error'    => 'accountId (int) and archive (bool) are required',
        'received' => $body,
    ]);
    exit;
}

require __DIR__ . '/db.php';

// Check if is_archived column even exists
$colCheck = $pdo->query("SHOW COLUMNS FROM account LIKE 'is_archived'")->fetch();
if (!$colCheck) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Column is_archived does not exist on the account table. Run migrate_add_archived.sql first.',
    ]);
    exit;
}

// Check the account actually exists
$exists = $pdo->prepare("SELECT account_ID FROM account WHERE account_ID = ?");
$exists->execute([$accountId]);
if (!$exists->fetch()) {
    http_response_code(404);
    echo json_encode([
        'error'     => 'No account row found with this ID',
        'accountId' => $accountId,
    ]);
    exit;
}

// Prevent staff archiving their own account
$selfCheck = $pdo->prepare("SELECT account_ID FROM staff WHERE staff_ID = ?");
$selfCheck->execute([$_SESSION['staff_ID']]);
$staffAccountId = (int) $selfCheck->fetchColumn();

if ($staffAccountId === $accountId) {
    http_response_code(403);
    echo json_encode(['error' => 'You cannot archive your own account']);
    exit;
}

// Apply change
$stmt = $pdo->prepare("UPDATE account SET is_archived = ? WHERE account_ID = ?");
$stmt->execute([$archive ? 1 : 0, $accountId]);

echo json_encode([
    'success'     => true,
    'accountId'   => $accountId,
    'is_archived' => $archive ? 1 : 0,
]);
