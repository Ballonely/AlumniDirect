<?php
/**
 * db.php
 *
 * Database connection. Edit the constants below to match your local
 * MySQL setup before running anything else.
 *
 * Anyone deploying this for real should pull these values from environment
 * variables instead of hardcoding them in a file that might get committed
 * to version control. For a school project, hardcoded is fine — just don't
 * carry this pattern into a real job.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'alumnidirectorydb');
define('DB_USER', 'root');
define('DB_PASS', ''); // set your MySQL root/user password here

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}
