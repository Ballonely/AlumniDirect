<?php
/**
 * get_image.php?id=1
 *
 * Streams the account's photo BLOB back as a real image, with the correct
 * Content-Type, instead of returning it as JSON/base64. Browsers can use
 * this directly in an <img src="get_image.php?id=1"> tag.
 */

require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    exit('Missing or invalid id parameter');
}

$stmt = $pdo->prepare("SELECT photo, photo_Type FROM account WHERE account_ID = :id");
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();

if (!$row || $row['photo'] === null) {
    http_response_code(404);
    exit('No image found for this account');
}

header('Content-Type: ' . ($row['photo_Type'] ?: 'application/octet-stream'));
header('Cache-Control: public, max-age=86400');
echo $row['photo'];
