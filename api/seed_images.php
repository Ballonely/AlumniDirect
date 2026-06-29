<?php
/**
 * seed_images.php
 *
 * Run this once, after schema.sql and seed_data.sql, to load the sample
 * avatar images into the account.photo BLOB column.
 *
 * Usage (command line):   php seed_images.php
 * Usage (browser):        visit this file directly, once, then delete it
 *                         or move it out of any publicly-served folder.
 *
 * Why this is a separate script and not part of seed_data.sql:
 * Binary image data does not belong inside a plain-text .sql file. Putting
 * it there means hex-encoding every byte, which bloats the file and is
 * painful to maintain. A prepared statement with a bound parameter is the
 * normal way to get binary data into a BLOB column.
 * 
 * I'll automate this soon, this is just placeholder for now.
 */

require_once __DIR__ . '/db.php';

$imageDir = __DIR__ . '/../sample-images/';

$imageMap = [
    9001 => 'AB.png',
    9002 => 'CD.png',
    9003 => 'EF.png',
    9004 => 'GH.png',
    9005 => 'IJ.png',
    9006 => 'KL.png',
    9007 => 'MN.png',
    9008 => 'OP.png',
    9009 => 'QR.png',
];

// Prepare FIRST, before the loop
$update = $pdo->prepare(
    "UPDATE account SET photo = :photo, photo_Type = :photo_type WHERE account_ID = :id"
);

foreach ($imageMap as $accountId => $filename) {
    $path = $imageDir . $filename;

    if (!file_exists($path)) {
        echo "SKIPPED — file not found: $path\n";
        continue;
    }

    $imageData = file_get_contents($path);
    $mimeType  = mime_content_type($path);

    $update->bindValue(':photo', $imageData, PDO::PARAM_STR);
    $update->bindValue(':photo_type', $mimeType, PDO::PARAM_STR);
    $update->bindValue(':id', $accountId, PDO::PARAM_INT);

    try {
        $update->execute();
        echo "Loaded $filename into account_ID $accountId\n";
    } catch (PDOException $e) {
        echo "ERROR for $accountId: " . $e->getMessage() . "\n";
    }
}

echo "\nDone.\n";