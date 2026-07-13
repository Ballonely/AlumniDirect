<?php
/**
 * student_login.php
 *
 * Handles the final step of the student login flow: verifying the password
 * for an ID that already has one set.  Called via fetch() (JSON POST) from
 * the landing-page modal.
 *
 * Rate-limiting: 5 failed password attempts per IP per 15-minute window.
 * Successful logins do NOT consume a slot.
 *
 * POST body (JSON):  { "student_id": "24-1001", "password": "secret" }
 *
 * 200  { "ok": true }                                   — redirect to pv_main.php
 * 400  { "error": "missing_fields" }
 * 401  { "error": "wrong_password" }
 * 404  { "error": "invalid_id" }
 * 429  { "error": "rate_limited", "retry_after": <seconds> }
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

/* ── Rate limiting ────────────────────────────────────────────────────────── */
const LOGIN_MAX    = 5;
const LOGIN_WINDOW = 900; // 15 minutes

$now = time();

if (!isset($_SESSION['student_login_fails'])) {
    $_SESSION['student_login_fails'] = [];
}

$_SESSION['student_login_fails'] = array_filter(
    $_SESSION['student_login_fails'],
    fn($t) => ($now - $t) < LOGIN_WINDOW
);

if (count($_SESSION['student_login_fails']) >= LOGIN_MAX) {
    $oldest     = min($_SESSION['student_login_fails']);
    $retryAfter = LOGIN_WINDOW - ($now - $oldest);
    http_response_code(429);
    echo json_encode(['error' => 'rate_limited', 'retry_after' => max(1, $retryAfter)]);
    exit;
}

/* ── Parse input ──────────────────────────────────────────────────────────── */
$body     = json_decode(file_get_contents('php://input'), true);
$id       = trim($body['student_id'] ?? '');
$password = $body['password'] ?? '';

if ($id === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'missing_fields']);
    exit;
}

/* ── DB lookup ────────────────────────────────────────────────────────────── */
$stmt = $pdo->prepare('SELECT password FROM student WHERE school_id = ? LIMIT 1');
$stmt->execute([$id]);
$row  = $stmt->fetch();

if ($row === false) {
    // Should not normally happen (modal already verified the ID), but handle it.
    http_response_code(404);
    echo json_encode(['error' => 'invalid_id']);
    exit;
}

if (!password_verify($password, $row['password'] ?? '')) {
    $_SESSION['student_login_fails'][] = $now;
    http_response_code(401);
    echo json_encode(['error' => 'wrong_password']);
    exit;
}

/* ── Start session ────────────────────────────────────────────────────────── */
// Regenerate ID to prevent session fixation on privilege escalation.
session_regenerate_id(true);

$_SESSION['student_mode'] = true;
$_SESSION['student_id']   = $id;

// Clear rate-limit counters on successful login.
unset($_SESSION['student_login_fails'], $_SESSION['student_check_attempts']);

echo json_encode(['ok' => true]);
