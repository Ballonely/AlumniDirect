<?php
/* Logs the user out by destroying the session. */

session_start();
session_unset();
session_destroy();
header('Location: ../pages/pb_login.html');
exit;
