<?php
/**
 * Handles registration form submission from AlumniRegister.html.
 * Validates input again on the server (never trust the front end alone),
 * hashes the password, inserts a new row into the account table,
 * then auto-logs the user in and sends them straight to AlumniProfile.html.
 *
 */

session_start();
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/pb_register.html');
    exit;
}

$firstname  = trim($_POST['firstname'] ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$lastname   = trim($_POST['lastname'] ?? '');
$suffix     = trim($_POST['suffix'] ?? '');
$email      = trim($_POST['email'] ?? '');
$schoolid   = trim($_POST['schoolid'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirmpassword'] ?? '';

$errors = [];

if ($firstname === '' || $lastname === '')        $errors[] = 'name';
if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) $errors[] = 'email';
if (!preg_match('/^\d{8}$/', $schoolid))           $errors[] = 'schoolid';
if (strlen($password) < 8)                        $errors[] = 'password';
if ($password !== $confirm)                        $errors[] = 'confirm';

if (!empty($errors)) {
    header('Location: ../pages/pb_register.html?error=' . implode(',', $errors));
    exit;
}

// Check for duplicate email before inserting
$check = $pdo->prepare('SELECT account_ID FROM account WHERE email = ?');
$check->execute([$email]);
if ($check->fetch()) {
    header('Location: ../pages/pb_register.html?error=emailtaken');
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// middlename and suffix are optional columns in the schema (nullable) —
// store NULL instead of an empty string when the user left them blank
$middlenameValue = $middlename === '' ? null : $middlename;
$suffixValue     = $suffix === '' ? null : $suffix;

$stmt = $pdo->prepare(
    'INSERT INTO account (first_Name, middle_Name, last_Name, suffix, school_ID, email, password)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$firstname, $middlenameValue, $lastname, $suffixValue, $schoolid, $email, $hash]);

// Auto-login: treat a fresh registration as an authenticated session
$_SESSION['account_ID'] = $pdo->lastInsertId();

header('Location: ../pages/pv_main.html');
exit;
