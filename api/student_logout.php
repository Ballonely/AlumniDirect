<?php
/**
 * student_logout.php
 *
 * Destroys the student-mode session and sends the visitor back to the
 * public landing page. Mirrors logout.php but targets pb_landing.html
 * instead of pb_login.html.
 *
 * Place this file alongside logout.php in your /api/ directory.
 */

session_start();
session_unset();
session_destroy();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: ../pages/pb_landing.html');
exit;
