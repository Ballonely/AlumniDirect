<?php
/**
 * student_check.php
 *
 * Called by the landing-page modal via fetch() to check whether a student ID
 * exists in the student table and whether a password has already been set.
 *
 * Rate-limiting: a maximum of 10 ID-check attempts per IP per 5-minute window
 * are stored in PHP session.  Submitting an incorrect password (student_login.php)
 * has its own, stricter limit.
 *
 * POST body (JSON):  { "student_id": "24-1001" }
 *
 * 200 responses:
 *   { "status": "needs_password" }   — ID found, password column IS NULL
 *   { "status": "has_password"    }  — ID found, password already set
 *
 * Error responses (also JSON):
 *   400  { "error": "missing_id"    }
 *   404  { "error": "invalid_id"    }
 *   429  { "error": "rate_limited", "retry_after": <seconds> }
 *   500  (db.php already calls die() with JSON on failure)
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';   // provides $pdo

/*  Rate limiting  */
// 10 check attempts per IP per 5-minute rolling window, tracked in session.
const CHECK_MAX     = 10;
const CHECK_WINDOW  = 300; // seconds

$now = time();

if (!isset($_SESSION['student_check_attempts'])) {
    $_SESSION['student_check_attempts'] = [];
}

// Drop timestamps older than the window
$_SESSION['student_check_attempts'] = array_filter(
    $_SESSION['student_check_attempts'],
    fn($t) => ($now - $t) < CHECK_WINDOW
);

if (count($_SESSION['student_check_attempts']) >= CHECK_MAX) {
    $oldest      = min($_SESSION['student_check_attempts']);
    $retryAfter  = CHECK_WINDOW - ($now - $oldest);
    http_response_code(429);
    echo json_encode(['error' => 'rate_limited', 'retry_after' => max(1, $retryAfter)]);
    exit;
}

/*  Parse input  */
$body = json_decode(file_get_contents('php://input'), true);
$raw  = trim($body['student_id'] ?? '');

if ($raw === '') {
    http_response_code(400);
    echo json_encode(['error' => 'missing_id']);
    exit;
}

// Record this attempt now (after the missing-id guard so blank submits don't count)
$_SESSION['student_check_attempts'][] = $now;

/*  DB lookup  */
$stmt = $pdo->prepare('SELECT password FROM student WHERE school_id = ? LIMIT 1');
$stmt->execute([$raw]);
$row  = $stmt->fetch();

if ($row === false) {
    http_response_code(404);
    echo json_encode(['error' => 'invalid_id']);
    exit;
}

// ID found — tell the client whether a password is already set
$status = ($row['password'] === null) ? 'needs_password' : 'has_password';
echo json_encode(['status' => $status]);
