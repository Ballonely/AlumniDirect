<?php
/**
 * Handles login form submission from AlumniDirectoryLog.html.
 * On success: starts a session, redirects to AlumniProfile.html.
 * On failure: redirects back to the login page with an error code.
 */

session_start();
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../AlumniDirectoryLog.html');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: ../AlumniDirectoryLog.html?error=missing');
    exit;
}

$stmt = $pdo->prepare('SELECT account_ID, password FROM account WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    // Login successful
    $_SESSION['account_ID'] = $user['account_ID'];
    header('Location: ../AlumniProfile.html');
    exit;
} else {
    header('Location: ../AlumniDirectoryLog.html?error=invalid');
    exit;
}
