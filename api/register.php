<?php
/**
 * Handles registration form submission from AlumniRegister.html.
 * Validates input again on the server (never trust the front end alone),
 * hashes the password, inserts a new row into the account table,
 * then auto-logs the user in and sends them straight to AlumniProfile.html.
 *
 * REQUIRES the schema patch in patch.sql to be run first
 * (adds school_ID column + unique constraint on email).
 */

session_start();
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('../Location: AlumniRegister.html');
    exit;
}

$firstname = trim($_POST['firstname'] ?? '');
$lastname  = trim($_POST['lastname'] ?? '');
$email     = trim($_POST['email'] ?? '');
$schoolid  = trim($_POST['schoolid'] ?? '');
$password  = $_POST['password'] ?? '';
$confirm   = $_POST['confirmpassword'] ?? '';

$errors = [];

if ($firstname === '' || $lastname === '')        $errors[] = 'name';
if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) $errors[] = 'email';
if (!preg_match('/^\d{8}$/', $schoolid))           $errors[] = 'schoolid';
if (strlen($password) < 8)                        $errors[] = 'password';
if ($password !== $confirm)                        $errors[] = 'confirm';

if (!empty($errors)) {
    header('Location: ../AlumniRegister.html?error=' . implode(',', $errors));
    exit;
}

// Check for duplicate email before inserting
$check = $pdo->prepare('SELECT account_ID FROM account WHERE email = ?');
$check->execute([$email]);
if ($check->fetch()) {
    header('Location: ../AlumniRegister.html?error=emailtaken');
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO account (first_Name, last_Name, school_ID, email, password)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$firstname, $lastname, $schoolid, $email, $hash]);

// Auto-login: treat a fresh registration as an authenticated session
$_SESSION['account_ID'] = $pdo->lastInsertId();

header('Location: ../AlumniProfile.html');
exit;
