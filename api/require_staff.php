<?php
/**
 * require_staff.php
 * Include this at the top of any staff-only endpoint. Mirrors the
 * guard get_account.php uses for $_SESSION['account_ID']: no valid
 * staff session -> 401, so a non-staff caller gets nothing back.
 */

session_start();
header('Content-Type: application/json');

if (empty($_SESSION['staff_ID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in as staff']);
    exit;
}
